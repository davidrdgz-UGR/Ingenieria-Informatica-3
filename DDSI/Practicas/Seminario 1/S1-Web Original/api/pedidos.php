
<?php

/* =============================================================
   API: TRANSACCIÓN COMPLETA DE UN PEDIDO
   Reutiliza Pedidos.php (cabecera) y DetallePedido.php (productos).
   No modifica el funcionamiento del CRUD de api/filas.php.
============================================================== */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

function enteroPositivo($valor, string $campo): int
{
    $numero = filter_var($valor, FILTER_VALIDATE_INT);
    if ($numero === false || $numero === null || $numero <= 0) {
        throw new InvalidArgumentException("Valor no válido: $campo");
    }
    return $numero;
}

function contarPorPedido(PDO $db, string $tabla, int $codigo): int
{
    // Solo se usa con estas dos tablas internas, nunca con datos del usuario.
    if (!in_array($tabla, ['Pedido', 'Detalle_Pedido'], true)) {
        throw new LogicException('Tabla incorrecta');
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM $tabla WHERE Cpedido = ?");
    $stmt->execute([$codigo]);
    return (int) $stmt->fetchColumn();
}

$db = null;

try {
    require_once __DIR__ . '/../model/class/Conexion.php';
    require_once __DIR__ . '/../model/class/Pedidos.php';
    require_once __DIR__ . '/../model/class/DetallePedido.php';

    $datos = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($datos)) {
        throw new InvalidArgumentException('Petición incorrecta');
    }

    $codigo = enteroPositivo($datos['Cpedido'] ?? null, 'Cpedido');
    $cliente = enteroPositivo($datos['Ccliente'] ?? null, 'Ccliente');
    $fecha = $datos['Fecha_pedido'] ?? null;
    $operaciones = $datos['operaciones'] ?? null;

    if (!is_string($fecha) ||
        !($fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)) ||
        $fechaValida->format('Y-m-d') !== $fecha) {
        throw new InvalidArgumentException('Fecha no válida');
    }

    if (!is_array($operaciones) || !array_is_list($operaciones) ||
        count($operaciones) < 1 || count($operaciones) > 200) {
        throw new InvalidArgumentException('Lista de operaciones no válida');
    }

    // Validar toda la secuencia antes de tocar la base de datos.
    foreach ($operaciones as $indice => $operacion) {
        if (!is_array($operacion)) {
            throw new InvalidArgumentException('Operación no válida');
        }

        $tipo = $operacion['tipo'] ?? null;

        if ($tipo === 'anadir') {
            enteroPositivo($operacion['producto'] ?? null, 'Cproducto');
            enteroPositivo($operacion['cantidad'] ?? null, 'Cantidad');
        } elseif ($tipo === 'deshacer') {
            // Se volverá al SAVEPOINT anterior al último detalle.
        } elseif ($tipo === 'limpiar') {
            // Se desharán los detalles creados después del SAVEPOINT.
        } elseif ($tipo === 'finalizar' || $tipo === 'cancelar') {
            if ($indice !== array_key_last($operaciones)) {
                throw new InvalidArgumentException('Finalizar o cancelar debe ser la última operación');
            }
        } else {
            throw new InvalidArgumentException('Operación desconocida');
        }
    }

    $decision = $operaciones[array_key_last($operaciones)]['tipo'];
    if (!in_array($decision, ['finalizar', 'cancelar'], true)) {
        throw new InvalidArgumentException('Falta finalizar o cancelar el pedido');
    }

    $conexion = new Conexion();
    $db = $conexion->obtenerConexion();
    $pedidos = new Pedidos($db);
    $detalles = new DetallePedido($db);

    // Una única conexión y una única transacción para cabecera, detalles y stock.
    $db->beginTransaction();

    if (!$pedidos->crearPedido($codigo, $cliente, $fecha)) {
        throw new RuntimeException('No se pudo crear el pedido');
    }

    // Volver aquí dejará intacta la cabecera y restaurará todo el stock.
    $db->exec('SAVEPOINT inicio_detalles');

    $pasos = ['Cabecera creada', 'SAVEPOINT inicio_detalles'];
    $numeroDetalles = 0;
    $limpiezas = 0;
    $deshechos = 0;
    $numeroSavepoint = 0;
    $savepoints = []; // Pila de puntos de guardado (uno después de cada detalle).

    foreach ($operaciones as $operacion) {
        switch ($operacion['tipo']) {
            case 'anadir':
                $producto = (int) $operacion['producto'];
                $cantidad = (int) $operacion['cantidad'];

                if (!$detalles->insertarDetalle($codigo, $producto, $cantidad)) {
                    throw new RuntimeException('No se pudo añadir el detalle');
                }

                $numeroDetalles++;
                $pasos[] = "Producto $producto: $cantidad unidad(es) insertadas y descontadas del stock";

                // Crear un SAVEPOINT real DESPUÉS de cada producto añadido.
                // Numeración siempre creciente para no reutilizar nombres.
                $numeroSavepoint++;
                $nombreSavepoint = "sp_detalle_$numeroSavepoint";
                $db->exec("SAVEPOINT $nombreSavepoint");
                $savepoints[] = $nombreSavepoint;
                $pasos[] = "SAVEPOINT $nombreSavepoint";
                break;

            case 'deshacer':
                if (!$savepoints) {
                    throw new InvalidArgumentException('No hay ningún producto que deshacer');
                }

                // Eliminamos el punto del último producto y volvemos al anterior.
                // Si era el primero, regresamos a inicio_detalles.
                array_pop($savepoints);
                $destino = $savepoints ? end($savepoints) : 'inicio_detalles';
                $db->exec("ROLLBACK TO SAVEPOINT $destino");
                $numeroDetalles--;
                $deshechos++;
                $pasos[] = "ROLLBACK TO SAVEPOINT $destino: último detalle y su descuento de stock deshechos";
                break;

            case 'limpiar':
                // Deshace los INSERT de detalles y los UPDATE del stock,
                // pero NO deshace la cabecera insertada antes del SAVEPOINT.
                $db->exec('ROLLBACK TO SAVEPOINT inicio_detalles');
                $numeroDetalles = 0;
                $savepoints = [];
                $limpiezas++;
                $pasos[] = 'ROLLBACK TO SAVEPOINT: detalles y stock restaurados';
                break;

            case 'cancelar':
                $db->rollBack();
                $pasos[] = 'ROLLBACK completo';

                echo json_encode([
                    'ok' => true,
                    'confirmado' => false,
                    'mensaje' => "Pedido $codigo cancelado (ROLLBACK). No se ha guardado.",
                    'pasos' => $pasos,
                    'estado' => [
                        'pedidos' => contarPorPedido($db, 'Pedido', $codigo),
                        'detalles' => contarPorPedido($db, 'Detalle_Pedido', $codigo)
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                exit;

            case 'finalizar':
                $db->commit();
                $pasos[] = 'COMMIT';

                $mensaje = "Pedido $codigo confirmado (COMMIT).";
                if ($limpiezas > 0 || $deshechos > 0) {
                    $mensaje .= " Deshechos $deshechos producto(s) y limpiados $limpiezas grupo(s) de detalles con SAVEPOINT.";
                }

                echo json_encode([
                    'ok' => true,
                    'confirmado' => true,
                    'mensaje' => $mensaje,
                    'pasos' => $pasos,
                    'estado' => [
                        'pedidos' => contarPorPedido($db, 'Pedido', $codigo),
                        'detalles' => contarPorPedido($db, 'Detalle_Pedido', $codigo)
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                exit;
        }
    }

} catch (Throwable $e) {
    if ($db instanceof PDO && $db->inTransaction()) {
        try {
            $db->rollBack();
        } catch (Throwable $ignorado) {
        }
    }

    error_log('DDSI pedidos.php: ' . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 400 : 409);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
