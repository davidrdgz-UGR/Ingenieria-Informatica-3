<?php
/*
 * API DE TRANSACCIONES DE PEDIDOS
 * Seminario 1 - DDSI
 * Recibe las acciones de JavaScript y las reenvía al worker persistente.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Se conserva una marca sencilla de sesión para que filas.php bloquee
// modificaciones CRUD mientras hay un pedido en transacción. No guarda PDO
// ni identifica la transacción: el worker mantiene un único pedido global.
session_start();

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function llamarWorker(array $peticion): array
{
    $direccion = 'tcp://' . PEDIDO_WORKER_HOST . ':' . PEDIDO_WORKER_PORT;
    $errno = 0;
    $errstr = '';

    $socket = @stream_socket_client($direccion, $errno, $errstr, 3);

    if (!$socket) {
        throw new RuntimeException(
            'El proceso de transacciones no está iniciado. ' .
            'Abre iniciar_worker.bat. Detalle: ' . $errstr
        );
    }

    stream_set_timeout($socket, 120);

    $mensaje = json_encode(
        $peticion,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
    ) . "\n";

    $total = strlen($mensaje);
    $enviados = 0;

    while ($enviados < $total) {
        $n = fwrite($socket, substr($mensaje, $enviados));

        if ($n === false || $n === 0) {
            fclose($socket);
            throw new RuntimeException('No se pudo enviar la operación al worker.');
        }

        $enviados += $n;
    }

    stream_socket_shutdown($socket, STREAM_SHUT_WR);
    $linea = fgets($socket, 8 * 1024 * 1024);
    $meta = stream_get_meta_data($socket);
    fclose($socket);

    if ($linea === false) {
        throw new RuntimeException(
            !empty($meta['timed_out'])
                ? 'El proceso de transacciones ha tardado demasiado en responder.'
                : 'El worker cerró la conexión sin devolver una respuesta.'
        );
    }

    $respuesta = json_decode($linea, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($respuesta)) {
        throw new RuntimeException('El worker devolvió una respuesta incorrecta.');
    }

    return $respuesta;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Método no permitido.']);
}

require_once __DIR__ . '/../worker/config.php';

try {
    $datos = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($datos)) {
        throw new InvalidArgumentException('La petición debe ser un objeto JSON.');
    }

    $accion = $datos['accion'] ?? '';
    $accionesPermitidas = [
        'estado',
        'iniciar',
        'anadir',
        'deshacer',
        'limpiar',
        'confirmar',
        'cancelar'
    ];

    if (!in_array($accion, $accionesPermitidas, true)) {
        throw new InvalidArgumentException('Acción no permitida.');
    }

    $sesionTienePedido = !empty($_SESSION['pedido_tx_token']);

    // estado e iniciar pueden consultarse/iniciarse sin una marca previa.
    // El resto de acciones exige que esta sesión haya iniciado el pedido.
    if (!in_array($accion, ['estado', 'iniciar'], true) && !$sesionTienePedido) {
        responder(409, [
            'ok' => false,
            'error' => 'Esta sesión no tiene un pedido abierto.'
        ]);
    }

    if ($accion === 'estado' && !$sesionTienePedido) {
        responder(200, ['ok' => true, 'activo' => false, 'pedido' => null]);
    }

    session_write_close();

    // Solo se envían al worker los datos que necesita cada operación.
    $respuesta = llamarWorker([
        'secret' => PEDIDO_WORKER_SECRET,
        'accion' => $accion,
        'datos' => [
            'Cpedido' => $datos['Cpedido'] ?? null,
            'Ccliente' => $datos['Ccliente'] ?? null,
            'Fecha_pedido' => $datos['Fecha_pedido'] ?? null,
            'Cproducto' => $datos['Cproducto'] ?? null,
            'Cantidad' => $datos['Cantidad'] ?? null
        ]
    ]);

    // La sesión solo conserva una marca para proteger las operaciones CRUD.
    // El estado real de la transacción siempre vive en el worker y en Oracle.
    session_start();

    if (!empty($respuesta['ok']) && $accion === 'iniciar') {
        $_SESSION['pedido_tx_token'] = true;
    }

    if ((!empty($respuesta['ok']) && in_array($accion, ['confirmar', 'cancelar'], true)) ||
        (($respuesta['codigo'] ?? '') === 'NO_ACTIVE_TRANSACTION') ||
        ($accion === 'estado' && empty($respuesta['activo']))) {
        unset($_SESSION['pedido_tx_token']);
    }

    session_write_close();

    if (empty($respuesta['ok'])) {
        $codigoHttp = in_array(
            $respuesta['codigo'] ?? '',
            ['WORKER_BUSY', 'NO_ACTIVE_TRANSACTION'],
            true
        ) ? 409 : 400;

        responder($codigoHttp, $respuesta);
    }

    responder(200, $respuesta);

} catch (InvalidArgumentException $e) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    responder(400, ['ok' => false, 'error' => $e->getMessage()]);

} catch (Throwable $e) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    error_log('DDSI api/pedidos.php: ' . $e->getMessage());
    responder(503, ['ok' => false, 'error' => $e->getMessage()]);
}
