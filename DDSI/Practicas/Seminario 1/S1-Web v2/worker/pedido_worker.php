<?php
/*
 * WORKER PERSISTENTE DE TRANSACCIONES DDSI
 *
 * Mantiene UNA conexión PDO con Oracle y permite UNA transacción de pedido
 * abierta cada vez. Se inicia desde Windows con iniciar_worker.bat.
 *
 * Las peticiones AJAX llegan por un socket local. El proceso no termina
 * después de cada petición, por lo que conserva la misma conexión PDO.
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
    fwrite(STDERR, "ERROR: PHP CLI no tiene habilitado pdo_odbc.\n");
    fwrite(STDERR, "Comprueba el php.ini utilizado por C:\\xampp\\php\\php.exe.\n");
    exit(1);
}

// La conexión PDO se crea una sola vez y permanece abierta mientras viva el worker.
try {
    $conexion = new Conexion();
    $db = $conexion->obtenerConexion();
    fwrite(STDOUT, "Conexión Oracle establecida correctamente.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar con Oracle: " . $e->getMessage() . "\n");
    exit(1);
}

$direccion = 'tcp://' . PEDIDO_WORKER_HOST . ':' . PEDIDO_WORKER_PORT;
$servidor = @stream_socket_server($direccion, $errno, $errstr);

if (!$servidor) {
    fwrite(STDERR, "No se pudo iniciar el worker: $errstr ($errno)\n");
    exit(1);
}

stream_set_blocking($servidor, true);

// Único pedido/transacción activo. La conexión PDO está en $db y es reutilizada.
$transaccion = null;

fwrite(STDOUT, "==============================================\n");
fwrite(STDOUT, " DDSI - WORKER DE TRANSACCIONES\n");
fwrite(STDOUT, " Escuchando en " . PEDIDO_WORKER_HOST . ':' . PEDIDO_WORKER_PORT . "\n");
fwrite(STDOUT, " Una conexión Oracle y un pedido activo.\n");
fwrite(STDOUT, " Mantén esta ventana abierta mientras uses la web.\n");
fwrite(STDOUT, " Ctrl+C detiene el worker. Oracle revertirá la\n");
fwrite(STDOUT, " transacción que siga abierta al cerrar la conexión.\n");
fwrite(STDOUT, "==============================================\n\n");

/**
 * Consulta las tablas usando la misma conexión PDO de la transacción.
 * Por eso esta función también ve los INSERT y UPDATE todavía no confirmados.
 */
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

/** Devuelve el estado real del pedido consultando la conexión activa. */
function respuestaEstado(PDO $db, array $transaccion): array
{
    return [
        'ok' => true,
        'activo' => true,
        'pedido' => $transaccion['pedido'],
        'tablas' => leerTablas($db),
        'savepoints' => $transaccion['savepoints'],
        'mensaje' => 'Datos recuperados desde la conexión Oracle de la transacción.'
    ];
}

/** Procesa una acción utilizando la única transacción global. */
function ejecutarPeticion(PDO $db, ?array &$transaccion, array $peticion): array
{
    if (!isset($peticion['secret']) ||
        !hash_equals(PEDIDO_WORKER_SECRET, (string)$peticion['secret'])) {
        return ['ok' => false, 'error' => 'Petición no autorizada.'];
    }

    $accion = $peticion['accion'] ?? '';
    $datos = $peticion['datos'] ?? [];

    if (!is_array($datos)) {
        return ['ok' => false, 'error' => 'Datos de la petición incorrectos.'];
    }

    // 1. INICIAR PEDIDO Y TRANSACCIÓN.
    if ($accion === 'iniciar') {
        if ($transaccion !== null) {
            return [
                'ok' => false,
                'codigo' => 'WORKER_BUSY',
                'error' => 'Ya existe un pedido abierto. Finalízalo o cancélalo antes de crear otro.'
            ];
        }

        $codigo = filter_var($datos['Cpedido'] ?? null, FILTER_VALIDATE_INT);
        $cliente = filter_var($datos['Ccliente'] ?? null, FILTER_VALIDATE_INT);
        $fecha = $datos['Fecha_pedido'] ?? null;

        if ($codigo === false || $codigo === null || $codigo <= 0 ||
            $cliente === false || $cliente === null || $cliente <= 0) {
            return ['ok' => false, 'error' => 'Código de pedido o cliente no válido.'];
        }

        $fechaObj = is_string($fecha)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)
            : false;

        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
            return ['ok' => false, 'error' => 'Fecha no válida.'];
        }

        try {
            $db->beginTransaction();

            $pedidos = new Pedidos($db);
            if (!$pedidos->crearPedido((int)$codigo, (int)$cliente, $fecha)) {
                throw new RuntimeException('No se pudo insertar la cabecera del pedido.');
            }

            $db->exec('SAVEPOINT inicio_detalles');

            $transaccion = [
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

            return array_merge(respuestaEstado($db, $transaccion), [
                'mensaje' => "Pedido $codigo insertado en Oracle. Transacción abierta."
            ]);

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (Throwable $ignorado) {
                }
            }

            $transaccion = null;
            throw $e;
        }
    }

    // Consultar estado cuando no hay pedido abierto es una respuesta válida.
    if ($accion === 'estado' && $transaccion === null) {
        return ['ok' => true, 'activo' => false, 'pedido' => null];
    }

    // Todas las demás acciones requieren un pedido activo.
    if ($transaccion === null) {
        return [
            'ok' => false,
            'codigo' => 'NO_ACTIVE_TRANSACTION',
            'activo' => false,
            'error' => 'No hay ningún pedido con una transacción abierta.'
        ];
    }

    $transaccion['ultima_actividad'] = time();

    switch ($accion) {
        case 'estado':
            return respuestaEstado($db, $transaccion);

        // 2. AÑADIR PRODUCTO Y CREAR SAVEPOINT.
        case 'anadir':
            $producto = filter_var($datos['Cproducto'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = filter_var($datos['Cantidad'] ?? null, FILTER_VALIDATE_INT);

            if ($producto === false || $producto === null || $producto <= 0 ||
                $cantidad === false || $cantidad === null || $cantidad <= 0) {
                return ['ok' => false, 'activo' => true, 'error' => 'Producto o cantidad no válidos.'];
            }

            // Este punto protege la operación actual si falla el INSERT o UPDATE.
            $db->exec('SAVEPOINT accion_actual');

            try {
                $detalles = new DetallePedido($db);
                $detalles->insertarDetalle(
                    (int)$transaccion['pedido']['Cpedido'],
                    (int)$producto,
                    (int)$cantidad
                );

                $transaccion['contador']++;
                $nombreSavepoint = 'sp_detalle_' . $transaccion['contador'];
                $db->exec('SAVEPOINT ' . $nombreSavepoint);
                $transaccion['savepoints'][] = $nombreSavepoint;

                fwrite(STDOUT, '[' . date('H:i:s') . "] INSERT Detalle_Pedido producto $producto cantidad $cantidad\n");
                fwrite(STDOUT, "  UPDATE Stock + SAVEPOINT $nombreSavepoint\n");

            } catch (Throwable $e) {
                try {
                    $db->exec('ROLLBACK TO SAVEPOINT accion_actual');
                } catch (Throwable $rollbackError) {
                    // Si no se puede volver al punto local, se revierte todo
                    // para no dejar la conexión en un estado incierto.
                    try {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                    } catch (Throwable $ignorado) {
                    }
                    $transaccion = null;
                }

                throw $e;
            }

            return array_merge(respuestaEstado($db, $transaccion), [
                'mensaje' => "Producto $producto añadido. SAVEPOINT $nombreSavepoint creado."
            ]);

        // 3. DESHACER EL ÚLTIMO PRODUCTO.
        case 'deshacer':
            if (count($transaccion['savepoints']) === 0) {
                return ['ok' => false, 'activo' => true, 'error' => 'No hay productos que deshacer.'];
            }

            $numero = count($transaccion['savepoints']);
            $destino = $numero > 1
                ? $transaccion['savepoints'][$numero - 2]
                : 'inicio_detalles';

            $db->exec('ROLLBACK TO SAVEPOINT ' . $destino);
            array_pop($transaccion['savepoints']);

            fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK TO SAVEPOINT $destino\n");

            return array_merge(respuestaEstado($db, $transaccion), [
                'mensaje' => "Último producto deshecho. Se ha recuperado el estado de $destino."
            ]);

        // 4. DESHACER TODOS LOS DETALLES, CONSERVANDO LA CABECERA.
        case 'limpiar':
            $db->exec('ROLLBACK TO SAVEPOINT inicio_detalles');
            $transaccion['savepoints'] = [];
            $transaccion['contador'] = 0;

            fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK TO SAVEPOINT inicio_detalles\n");

            return array_merge(respuestaEstado($db, $transaccion), [
                'mensaje' => 'Se han deshecho todos los detalles y se ha restaurado el stock.'
            ]);

        // 5. CONFIRMAR TODA LA TRANSACCIÓN.
        case 'confirmar':
            $tablas = leerTablas($db);
            $codigoPedido = $transaccion['pedido']['Cpedido'];

            $db->commit();
            $transaccion = null;

            fwrite(STDOUT, '[' . date('H:i:s') . "] COMMIT pedido $codigoPedido\n");

            return [
                'ok' => true,
                'activo' => false,
                'pedido' => null,
                'tablas' => $tablas,
                'mensaje' => "Pedido $codigoPedido confirmado mediante COMMIT."
            ];

        // 6. CANCELAR Y DESHACER TODA LA TRANSACCIÓN.
        case 'cancelar':
            $codigoPedido = $transaccion['pedido']['Cpedido'];

            $db->rollBack();
            $tablas = leerTablas($db);
            $transaccion = null;

            fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK completo pedido $codigoPedido\n");

            return [
                'ok' => true,
                'activo' => false,
                'pedido' => null,
                'tablas' => $tablas,
                'mensaje' => "Pedido $codigoPedido cancelado mediante ROLLBACK completo."
            ];

        default:
            return ['ok' => false, 'activo' => true, 'error' => 'Operación desconocida.'];
    }
}

// Bucle persistente: cada petición se procesa reutilizando $transaccion.
while (true) {
    $cliente = @stream_socket_accept($servidor, 1);

    if ($cliente !== false) {
        stream_set_timeout($cliente, 120);

        try {
            $linea = fgets($cliente, 2 * 1024 * 1024);

            if ($linea === false) {
                throw new RuntimeException('No se recibió ninguna petición.');
            }

            $peticion = json_decode($linea, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($peticion)) {
                throw new InvalidArgumentException('Formato de petición incorrecto.');
            }

            $respuesta = ejecutarPeticion($db, $transaccion, $peticion);

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
            if ($n === false || $n === 0) {
                break;
            }
            $escritos += $n;
        }

        fclose($cliente);
    }

    // Si el usuario abandona el pedido, liberamos los bloqueos tras 30 minutos.
    if ($transaccion !== null &&
        time() - $transaccion['ultima_actividad'] > PEDIDO_WORKER_IDLE_SECONDS) {

        try {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        } catch (Throwable $e) {
            error_log('DDSI worker timeout rollback: ' . $e->getMessage());
        }

        $transaccion = null;
        fwrite(STDOUT, '[' . date('H:i:s') . "] ROLLBACK por inactividad\n");
    }
}
