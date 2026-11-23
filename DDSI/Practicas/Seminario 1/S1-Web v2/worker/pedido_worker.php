<?php
/*
 * PROCESO PERSISTENTE DE TRANSACCIONES DDSI
 * Ejecutar desde consola: php worker/pedido_worker.php
 * Mantiene conexiones PDO/Oracle abiertas entre peticiones AJAX.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

set_time_limit(0);
ignore_user_abort(true);
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../model/class/conexion.php';
require_once __DIR__ . '/../model/class/pedidos.php';
require_once __DIR__ . '/../model/class/detallePedido.php';

if (!in_array('odbc', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "ERROR: El PHP de consola no tiene habilitado pdo_odbc.\n");
    fwrite(STDERR, "Comprueba C:\\xampp\\php\\php.ini y ejecuta php -m.\n");
    exit(1);
}

$direccion = 'tcp://' . PEDIDO_WORKER_HOST . ':' . PEDIDO_WORKER_PORT;
$servidor = @stream_socket_server($direccion, $errno, $errstr);

if (!$servidor) {
    fwrite(STDERR, "No se pudo iniciar el servicio local: $errstr ($errno)\n");
    exit(1);
}

stream_set_blocking($servidor, true);
$transacciones = [];

fwrite(STDOUT, "==============================================\n");
fwrite(STDOUT, " DDSI - SERVICIO PERSISTENTE DE TRANSACCIONES\n");
fwrite(STDOUT, " Escuchando en " . PEDIDO_WORKER_HOST . ':' . PEDIDO_WORKER_PORT . "\n");
fwrite(STDOUT, " Mantén esta ventana abierta mientras uses la web.\n");
fwrite(STDOUT, " Ctrl+C detiene el servicio y Oracle revierte\n");
fwrite(STDOUT, " las transacciones que sigan abiertas.\n");
fwrite(STDOUT, "==============================================\n\n");

function leerTablas(PDO $db): array
{
    $consultas = [
        'Stock' => [
            ['Cproducto', 'Nombre', 'Cantidad'],
            'SELECT Cproducto, Nombre, Cantidad FROM Stock ORDER BY Cproducto'
        ],
        'Clientes' => [
            ['ID_Cliente', 'Nombre'],
            'SELECT ID_Cliente, Nombre FROM Clientes ORDER BY ID_Cliente'
        ],
        'Pedido' => [
            ['Cpedido', 'Ccliente', 'Fecha_pedido'],
            "SELECT Cpedido, Ccliente, TO_CHAR(Fecha_pedido, 'YYYY-MM-DD') FROM Pedido ORDER BY Cpedido"
        ],
        'Detalle_Pedido' => [
            ['Cpedido', 'Cproducto', 'Cantidad'],
            'SELECT Cpedido, Cproducto, Cantidad FROM Detalle_Pedido ORDER BY Cpedido, Cproducto'
        ]
    ];

    $resultado = [];
    foreach ($consultas as $nombre => [$columnas, $sql]) {
        $filas = $db->query($sql)->fetchAll(PDO::FETCH_NUM);
        $resultado[$nombre] = [
            'columnas' => $columnas,
            'filas' => $filas
        ];
    }
    return $resultado;
}

function respuestaEstado(array $sesion): array
{
    return [
        'ok' => true,
        'activo' => true,
        'pedido' => $sesion['pedido'],
        'tablas' => leerTablas($sesion['db']),
        'savepoints' => $sesion['savepoints'],
        'mensaje' => 'Estado leído desde la misma conexión Oracle de la transacción.'
    ];
}

function ejecutarPeticion(array &$transacciones, array $peticion): array
{
    if (!isset($peticion['secret']) ||
        !hash_equals(PEDIDO_WORKER_SECRET, (string)$peticion['secret'])) {
        return ['ok' => false, 'error' => 'Petición no autorizada.'];
    }

    $token = $peticion['token'] ?? '';
    $accion = $peticion['accion'] ?? '';
    $datos = $peticion['datos'] ?? [];

    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return ['ok' => false, 'error' => 'Identificador de sesión no válido.'];
    }

    if ($accion === 'iniciar') {
        if (isset($transacciones[$token])) {
            return ['ok' => false, 'error' => 'Ya existe una transacción activa para esta sesión.'];
        }
        if (count($transacciones) > 0) {
            return ['ok' => false, 'error' => 'Ya hay otro pedido en transacción. Finalízalo o cancélalo antes de iniciar otro.'];
        }

        $codigo = filter_var($datos['Cpedido'] ?? null, FILTER_VALIDATE_INT);
        $cliente = filter_var($datos['Ccliente'] ?? null, FILTER_VALIDATE_INT);
        $fecha = $datos['Fecha_pedido'] ?? null;

        if (!$codigo || $codigo <= 0 || !$cliente || $cliente <= 0) {
            return ['ok' => false, 'error' => 'Código de pedido o cliente no válido.'];
        }
        $fechaObj = is_string($fecha)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)
            : false;
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
            return ['ok' => false, 'error' => 'Fecha no válida.'];
        }

        $conexion = null;
        $db = null;
        try {
            $conexion = new Conexion();
            $db = $conexion->obtenerConexion();
            $db->beginTransaction();

            $pedidos = new Pedidos($db);
            if (!$pedidos->crearPedido((int)$codigo, (int)$cliente, $fecha)) {
                throw new RuntimeException('No se pudo insertar la cabecera del pedido.');
            }

            $db->exec('SAVEPOINT inicio_detalles');

            $transacciones[$token] = [
                'conexion' => $conexion,
                'db' => $db,
                'pedido' => [
                    'Cpedido' => (int)$codigo,
                    'Ccliente' => (int)$cliente,
                    'Fecha_pedido' => $fecha
                ],
                'savepoints' => [],
                'contador' => 0,
                'ultima_actividad' => time()
            ];

            fwrite(STDOUT, '[' . date('H:i:s') . "] BEGIN pedido $codigo\n");
            fwrite(STDOUT, "  INSERT Pedido + SAVEPOINT inicio_detalles\n");

            return array_merge(respuestaEstado($transacciones[$token]), [
                'mensaje' => "Pedido $codigo insertado en Oracle. Transacción abierta."
            ]);
        } catch (Throwable $e) {
            if (isset($transacciones[$token])) {
                try {
                    if ($transacciones[$token]['db']->inTransaction()) {
                        $transacciones[$token]['db']->rollBack();
                    }
                } catch (Throwable $ignorado) {}
                unset($transacciones[$token]);
            } elseif ($db instanceof PDO && $db->inTransaction()) {
                try { $db->rollBack(); } catch (Throwable $ignorado) {}
            }
            unset($db, $conexion);
            throw $e;
        }
    }

    if (!isset($transacciones[$token])) {
        return [
            'ok' => false,
            'codigo' => 'SESSION_NOT_FOUND',
            'activo' => false,
            'error' => 'La transacción ya no está activa. Puede que el servicio se haya reiniciado o haya caducado.'
        ];
    }

    $sesion = &$transacciones[$token];
    $db = $sesion['db'];
    $sesion['ultima_actividad'] = time();

    switch ($accion) {
        case 'estado':
            return respuestaEstado($sesion);

        case 'anadir':
            $producto = filter_var($datos['Cproducto'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = filter_var($datos['Cantidad'] ?? null, FILTER_VALIDATE_INT);
            if (!$producto || $producto <= 0 || !$cantidad || $cantidad <= 0) {
                return ['ok' => false, 'activo' => true, 'error' => 'Producto o cantidad no válidos.'];
            }

            // Si falla cualquier parte del alta, solo se deshace esta operación.
            $db->exec('SAVEPOINT accion_actual');
            try {
                $detalles = new DetallePedido($db);
                if (!$detalles->insertarDetalle(
                    (int)$sesion['pedido']['Cpedido'],
                    (int)$producto,
                    (int)$cantidad
                )) {
                    throw new RuntimeException('No se pudo insertar el detalle.');
                }

                $sesion['contador']++;
                $nombreSavepoint = 'sp_detalle_' . $sesion['contador'];
                $db->exec('SAVEPOINT ' . $nombreSavepoint);
                $sesion['savepoints'][] = $nombreSavepoint;

                fwrite(STDOUT, '[' . date('H:i:s') . "] INSERT Detalle_Pedido producto $producto cantidad $cantidad\n");
                fwrite(STDOUT, "  UPDATE Stock + SAVEPOINT $nombreSavepoint\n");
            } catch (Throwable $e) {
                try { $db->exec('ROLLBACK TO SAVEPOINT accion_actual'); } catch (Throwable $ignorado) {}
                throw $e;
            }

            return array_merge(respuestaEstado($sesion), [
                'mensaje' => "Producto $producto añadido en Oracle. SAVEPOINT $nombreSavepoint creado."
            ]);

        case 'deshacer':
            if (!$sesion['savepoints']) {
                return ['ok' => false, 'activo' => true, 'error' => 'No hay productos que deshacer.'];
            }
            $cantidadSavepoints = count($sesion['savepoints']);
            $destino = $cantidadSavepoints > 1
                ? $sesion['savepoints'][$cantidadSavepoints - 2]
                : 'inicio_detalles';
            $db->exec('ROLLBACK TO SAVEPOINT ' . $destino);
            array_pop($sesion['savepoints']);
            fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK TO SAVEPOINT $destino\n");
            return array_merge(respuestaEstado($sesion), [
                'mensaje' => "Último producto deshecho. Oracle ha vuelto a $destino."
            ]);

        case 'limpiar':
            $db->exec('ROLLBACK TO SAVEPOINT inicio_detalles');
            $sesion['savepoints'] = [];
            fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK TO SAVEPOINT inicio_detalles\n");
            return array_merge(respuestaEstado($sesion), [
                'mensaje' => 'Detalles eliminados de la transacción y stock restaurado.'
            ]);

        case 'confirmar':
            // Consultar desde la misma conexión antes de confirmar; si falla la
            // consulta, todavía podremos revertir la transacción completa.
            $tablas = leerTablas($db);
            $db->commit();
            $pedido = $sesion['pedido'];
            unset($transacciones[$token]);
            fwrite(STDOUT, '[' . date('H:i:s') . "] COMMIT pedido " . $pedido['Cpedido'] . "\n");
            return [
                'ok' => true,
                'activo' => false,
                'pedido' => null,
                'tablas' => $tablas,
                'mensaje' => 'Pedido ' . $pedido['Cpedido'] . ' confirmado mediante COMMIT.'
            ];

        case 'cancelar':
            $pedido = $sesion['pedido'];
            $db->rollBack();
            unset($transacciones[$token]);
            $tablas = leerTablas($db);
            fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK completo pedido " . $pedido['Cpedido'] . "\n");
            return [
                'ok' => true,
                'activo' => false,
                'pedido' => null,
                'tablas' => $tablas,
                'mensaje' => 'Pedido ' . $pedido['Cpedido'] . ' cancelado mediante ROLLBACK completo.'
            ];

        default:
            return ['ok' => false, 'activo' => true, 'error' => 'Operación desconocida.'];
    }
}

while (true) {
    $cliente = @stream_socket_accept($servidor, 1);

    if ($cliente !== false) {
        stream_set_timeout($cliente, 120);
        $linea = fgets($cliente, 2 * 1024 * 1024);

        try {
            if ($linea === false) {
                throw new RuntimeException('No se recibió ninguna petición.');
            }
            $peticion = json_decode($linea, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($peticion)) {
                throw new InvalidArgumentException('Formato de petición incorrecto.');
            }
            $respuesta = ejecutarPeticion($transacciones, $peticion);
        } catch (Throwable $e) {
            error_log('DDSI worker: ' . $e->getMessage());
            $respuesta = ['ok' => false, 'error' => $e->getMessage()];
        }

        $salida = json_encode(
            $respuesta,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        ) . "\n";
        $longitud = strlen($salida);
        $escritos = 0;
        while ($escritos < $longitud) {
            $n = @fwrite($cliente, substr($salida, $escritos));
            if ($n === false || $n === 0) break;
            $escritos += $n;
        }
        fclose($cliente);
    }

    // Evitar dejar bloqueado el stock si el navegador abandona un pedido.
    $ahora = time();
    foreach ($transacciones as $token => $sesion) {
        if ($ahora - $sesion['ultima_actividad'] > PEDIDO_WORKER_IDLE_SECONDS) {
            try {
                if ($sesion['db']->inTransaction()) {
                    $sesion['db']->rollBack();
                }
            } catch (Throwable $e) {
                error_log('DDSI worker timeout rollback: ' . $e->getMessage());
            }
            fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK por inactividad\n");
            unset($transacciones[$token]);
        }
    }
}
