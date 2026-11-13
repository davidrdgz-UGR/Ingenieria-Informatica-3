
<?php

/* ============================================
   API: AÑADIR, EDITAR Y ELIMINAR FILAS
   Seminario 1 - DDSI
   ============================================ */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// Validar números enteros recibidos desde JavaScript.
function entero($valor, string $campo, int $min = 1): int
{
    $numero = filter_var($valor, FILTER_VALIDATE_INT);

    if ($numero === false || $numero === null || $numero < $min) {
        throw new InvalidArgumentException("Valor no válido: $campo");
    }

    return $numero;
}

function nombre($valor): string
{
    if (!is_string($valor) || trim($valor) === '' || mb_strlen(trim($valor)) > 50) {
        throw new InvalidArgumentException('Nombre no válido (1 a 50 caracteres)');
    }

    return trim($valor);
}

$db = null;

try {
    require_once __DIR__ . '/../model/class/Conexion.php';
    require_once __DIR__ . '/../model/class/Producto.php';
    require_once __DIR__ . '/../model/class/Clientes.php';
    require_once __DIR__ . '/../model/class/Pedidos.php';
    require_once __DIR__ . '/../model/class/DetallePedido.php';

    $datos = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($datos)) {
        throw new InvalidArgumentException('Solicitud incorrecta');
    }

    $tabla = $datos['tabla'] ?? '';
    $accion = $datos['accion'] ?? '';
    $fila = $datos['fila'] ?? null;
    $clave = $datos['clave'] ?? null;

    if (!in_array($tabla, ['Stock', 'Clientes', 'Pedido', 'Detalle_Pedido'], true) ||
        !in_array($accion, ['crear', 'editar', 'eliminar'], true)) {
        throw new InvalidArgumentException('Tabla o acción no permitida');
    }

    if ($accion !== 'eliminar' && !is_array($fila)) {
        throw new InvalidArgumentException('Faltan los datos de la fila');
    }

    if ($accion !== 'crear' && !is_array($clave)) {
        throw new InvalidArgumentException('Falta la clave del registro');
    }

    $conexion = new Conexion();
    $db = $conexion->obtenerConexion();

    $productos = new Producto($db);
    $clientes = new Clientes($db);
    $pedidos = new Pedidos($db);
    $detalles = new DetallePedido($db);

    // Cada operación de la API se confirma o deshace entera.
    $db->beginTransaction();
    $exito = false;

    switch ($tabla) {
        case 'Stock':
            if ($accion === 'crear') {
                $exito = $productos->insertarProducto(
                    entero($fila[0] ?? null, 'Cproducto'),
                    nombre($fila[1] ?? null),
                    entero($fila[2] ?? null, 'Cantidad', 0)
                );
            } elseif ($accion === 'editar') {
                $exito = $productos->actualizarProducto(
                    entero($clave[0] ?? null, 'Cproducto'),
                    nombre($fila[1] ?? null),
                    entero($fila[2] ?? null, 'Cantidad', 0)
                );
            } else {
                $exito = $productos->eliminarProducto(
                    entero($clave[0] ?? null, 'Cproducto')
                );
            }
            break;

        case 'Clientes':
            if ($accion === 'crear') {
                $exito = $clientes->insertarCliente(
                    entero($fila[0] ?? null, 'ID_Cliente'),
                    nombre($fila[1] ?? null)
                );
            } elseif ($accion === 'editar') {
                $exito = $clientes->actualizarCliente(
                    entero($clave[0] ?? null, 'ID_Cliente'),
                    nombre($fila[1] ?? null)
                );
            } else {
                $exito = $clientes->eliminarCliente(
                    entero($clave[0] ?? null, 'ID_Cliente')
                );
            }
            break;

        case 'Pedido':
            if ($accion === 'crear') {
                // Solo Cpedido, Ccliente y Fecha_pedido.
                $exito = $pedidos->crearPedido(
                    entero($fila[0] ?? null, 'Cpedido'),
                    entero($fila[1] ?? null, 'Ccliente'),
                    (string) ($fila[2] ?? '')
                );
            } elseif ($accion === 'editar') {
                $exito = $pedidos->actualizarPedido(
                    entero($clave[0] ?? null, 'Cpedido'),
                    entero($fila[1] ?? null, 'Ccliente'),
                    (string) ($fila[2] ?? '')
                );
            } else {
                $id = entero($clave[0] ?? null, 'Cpedido');

                // Eliminar detalles y devolver su stock antes de borrar el pedido.
                $detalles->eliminarDetallesPedido($id);
                $exito = $pedidos->eliminarPedido($id);
            }
            break;

        case 'Detalle_Pedido':
            if ($accion === 'crear') {
                $exito = $detalles->insertarDetalle(
                    entero($fila[0] ?? null, 'Cpedido'),
                    entero($fila[1] ?? null, 'Cproducto'),
                    entero($fila[2] ?? null, 'Cantidad')
                );
            } elseif ($accion === 'editar') {
                $exito = $detalles->actualizarDetalle(
                    entero($clave[0] ?? null, 'Cpedido'),
                    entero($clave[1] ?? null, 'Cproducto'),
                    entero($fila[2] ?? null, 'Cantidad')
                );
            } else {
                $exito = $detalles->eliminarDetalle(
                    entero($clave[0] ?? null, 'Cpedido'),
                    entero($clave[1] ?? null, 'Cproducto')
                );
            }
            break;
    }

    if (!$exito) {
        throw new RuntimeException('No se encontró el registro o no se realizó el cambio');
    }

    $db->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Operación realizada correctamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($db instanceof PDO && $db->inTransaction()) {
        try {
            $db->rollBack();
        } catch (Throwable $ignorado) {
        }
    }

    error_log('DDSI filas.php: ' . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 400 : 409);

    // Mensaje de error útil en el entorno local de prácticas.
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
