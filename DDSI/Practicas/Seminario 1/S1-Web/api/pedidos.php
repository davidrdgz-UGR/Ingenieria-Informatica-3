
<?php

/* ============================================
   GESTIÓN DE PEDIDOS Y TRANSACCIONES
   Seminario 1 - DDSI
   ============================================ */

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

// Solo permitir peticiones POST.
if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "error" => "Método no permitido"
    ]);
    exit;
}

try {
    require_once __DIR__ . "/../model/class/Conexion.php";
    require_once __DIR__ . "/../model/class/Pedidos.php";

    // Recibir los datos enviados desde JavaScript.
    $datos = json_decode(
        file_get_contents("php://input"),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($datos)) {
        throw new InvalidArgumentException("Petición incorrecta");
    }

    $idPedido = filter_var(
        $datos["Cpedido"] ?? null,
        FILTER_VALIDATE_INT
    );

    $idCliente = filter_var(
        $datos["Ccliente"] ?? null,
        FILTER_VALIDATE_INT
    );

    $fecha = $datos["Fecha_pedido"] ?? null;
    $operaciones = $datos["operaciones"] ?? null;

    // Validar los datos del pedido.
    if (
        $idPedido === false || $idPedido === null || $idPedido <= 0 ||
        $idCliente === false || $idCliente === null || $idCliente <= 0 ||
        !is_string($fecha) ||
        !is_array($operaciones) ||
        !array_is_list($operaciones) ||
        count($operaciones) < 1 ||
        count($operaciones) > 100
    ) {
        throw new InvalidArgumentException(
            "Datos del pedido no válidos"
        );
    }

    // Comprobar que las operaciones recibidas son válidas.
    foreach ($operaciones as $indice => $operacion) {

        if (!is_array($operacion)) {
            throw new InvalidArgumentException(
                "Operación incorrecta"
            );
        }

        $tipo = $operacion["tipo"] ?? null;

        if ($tipo === "anadir") {

            $producto = filter_var(
                $operacion["producto"] ?? null,
                FILTER_VALIDATE_INT
            );

            $cantidad = filter_var(
                $operacion["cantidad"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                !$producto || !$cantidad ||
                $producto <= 0 || $cantidad <= 0
            ) {
                throw new InvalidArgumentException(
                    "Producto o cantidad incorrectos"
                );
            }

        } elseif ($tipo === "limpiar") {

            // Se procesará mediante ROLLBACK TO SAVEPOINT.

        } elseif (
            $tipo === "finalizar" ||
            $tipo === "cancelar"
        ) {

            if ($indice !== array_key_last($operaciones)) {
                throw new InvalidArgumentException(
                    "Finalizar o cancelar debe ser la última operación"
                );
            }

        } else {
            throw new InvalidArgumentException(
                "Operación no permitida"
            );
        }
    }

    // El pedido debe terminar con COMMIT o ROLLBACK.
    $ultima = $operaciones[array_key_last($operaciones)]["tipo"];

    if ($ultima !== "finalizar" && $ultima !== "cancelar") {
        throw new InvalidArgumentException(
            "El pedido debe finalizarse o cancelarse"
        );
    }

    // Conectar con Oracle.
    $conexion = new Conexion();
    $db = $conexion->obtenerConexion();

    $pedidos = new Pedidos($db);

    // Pedidos.php ejecuta toda la secuencia:
    // beginTransaction(), SAVEPOINT, COMMIT o ROLLBACK.
    $confirmado = $pedidos->crearPedido(
        $idPedido,
        $idCliente,
        $fecha,
        $operaciones
    );

    // Responder a JavaScript.
    echo json_encode([
        "ok" => true,
        "confirmado" => $confirmado,
        "mensaje" => $confirmado
            ? "Pedido confirmado (COMMIT)."
            : "Pedido cancelado (ROLLBACK)."
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Throwable $e) {

    // Pedidos.php deshace la transacción cuando falla.
    error_log("DDSI pedidos.php: " . $e->getMessage());

    http_response_code(
        $e instanceof InvalidArgumentException ? 400 : 409
    );

    // Mensajes de error para el entorno local de prácticas.
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
