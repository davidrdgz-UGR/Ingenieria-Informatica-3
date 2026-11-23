<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

ini_set('session.gc_maxlifetime', '3600');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../worker/config.php';

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function llamarProcesoWorker(array $peticion): array
{
    $direccion = 'tcp://' . PEDIDO_WORKER_HOST . ':' . PEDIDO_WORKER_PORT;
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($direccion, $errno, $errstr, 3);

    if (!$socket) {
        throw new RuntimeException(
            'No está iniciado el proceso persistente de transacciones. ' .
            'Abre iniciar_worker.bat y vuelve a intentarlo. Detalle: ' . $errstr
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
            throw new RuntimeException('No se pudo enviar la operación al proceso persistente.');
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
                : 'El proceso de transacciones cerró la conexión sin responder.'
        );
    }

    return json_decode($linea, true, 512, JSON_THROW_ON_ERROR);
}

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    responder(400, ['ok' => false, 'error' => 'Petición JSON incorrecta.']);
}

$accion = $datos['accion'] ?? '';
$permitidas = ['estado', 'iniciar', 'anadir', 'deshacer', 'limpiar', 'confirmar', 'cancelar'];
if (!in_array($accion, $permitidas, true)) {
    responder(400, ['ok' => false, 'error' => 'Acción no permitida.']);
}

$token = $_SESSION['pedido_tx_token'] ?? null;

// La consulta de estado también sirve para recuperar una transacción al recargar la web.
if ($accion === 'estado' && !$token) {
    session_write_close();
    responder(200, ['ok' => true, 'activo' => false]);
}

if ($accion === 'iniciar') {
    if ($token) {
        session_write_close();
        responder(409, [
            'ok' => false,
            'error' => 'Esta sesión ya tiene un pedido abierto. Recarga la página para recuperarlo.'
        ]);
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['pedido_tx_token'] = $token;
    session_write_close();
} else {
    if (!$token) {
        session_write_close();
        responder(409, ['ok' => false, 'error' => 'No hay ninguna transacción activa en esta sesión.']);
    }
    session_write_close();
}

try {
    $respuesta = llamarProcesoWorker([
        'secret' => PEDIDO_WORKER_SECRET,
        'token' => $token,
        'accion' => $accion,
        'datos' => [
            'Cpedido' => $datos['Cpedido'] ?? null,
            'Ccliente' => $datos['Ccliente'] ?? null,
            'Fecha_pedido' => $datos['Fecha_pedido'] ?? null,
            'Cproducto' => $datos['Cproducto'] ?? null,
            'Cantidad' => $datos['Cantidad'] ?? null
        ]
    ]);

    $cerrarSesionPedido =
        (in_array($accion, ['confirmar', 'cancelar'], true) && !empty($respuesta['ok'])) ||
        ($accion === 'iniciar' && empty($respuesta['ok']));

    if (($respuesta['codigo'] ?? '') === 'SESSION_NOT_FOUND') {
        $cerrarSesionPedido = true;
    }

    if ($cerrarSesionPedido) {
        session_start();
        if (isset($_SESSION['pedido_tx_token']) &&
            hash_equals((string)$_SESSION['pedido_tx_token'], (string)$token)) {
            unset($_SESSION['pedido_tx_token']);
        }
        session_write_close();
    }

    if (($respuesta['codigo'] ?? '') === 'SESSION_NOT_FOUND' && $accion === 'estado') {
        responder(200, ['ok' => true, 'activo' => false]);
    }

    if (empty($respuesta['ok'])) {
        responder(($respuesta['codigo'] ?? '') === 'SESSION_NOT_FOUND' ? 409 : 400, $respuesta);
    }

    responder(200, $respuesta);

} catch (Throwable $e) {
    if (in_array($accion, ['iniciar', 'estado'], true)) {
        session_start();
        if (isset($_SESSION['pedido_tx_token']) &&
            hash_equals((string)$_SESSION['pedido_tx_token'], (string)$token)) {
            unset($_SESSION['pedido_tx_token']);
        }
        session_write_close();
    }

    if ($accion === 'estado') {
        // Si el worker no está disponible, su conexión Oracle ya no puede
        // conservar la transacción: la página podrá cargar el estado confirmado.
        responder(200, ['ok' => true, 'activo' => false]);
    }

    error_log('DDSI api/pedidos.php: ' . $e->getMessage());
    responder(503, ['ok' => false, 'error' => $e->getMessage()]);
}
