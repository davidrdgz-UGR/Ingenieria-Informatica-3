
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../model/class/Conexion.php";
require_once __DIR__ . "/../model/class/Producto.php";
require_once __DIR__ . "/../model/class/Clientes.php";
require_once __DIR__ . "/../model/class/Pedidos.php";
require_once __DIR__ . "/../model/class/DetallePedido.php";

try {
    // Solo permitir peticiones GET.
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new Exception("Método no permitido");
    }

    // Conectar con Oracle.
    $conexion = new Conexion();
    $db = $conexion->obtenerConexion();

    // Crear los objetos.
    $producto = new Producto($db);
    $clientes = new Clientes($db);
    $pedidos = new Pedidos($db);
    $detalles = new DetallePedido($db);

    // Obtener los datos usando las clases.
    $datos = [
        "Stock" => $producto->obtenerProductos(),
        "Clientes" => $clientes->obtenerClientes(),
        "Pedido" => $pedidos->obtenerPedidos(),
        "Detalle_Pedido" => $detalles->obtenerDetalles()
    ];

    // Columnas que utiliza nuestra web.
    $columnas = [
        "Stock" => ["Cproducto", "Nombre", "Cantidad"],
        "Clientes" => ["ID_Cliente", "Nombre"],
        "Pedido" => ["Cpedido", "Ccliente", "Fecha_pedido"],
        "Detalle_Pedido" => ["Cpedido", "Cproducto", "Cantidad"]
    ];

    // Adaptar los datos al formato de app.js.
    $resultado = [];

    foreach ($datos as $nombre => $registros) {

        $filas = [];

        foreach ($registros as $registro) {

            $fila = [];

            foreach ($columnas[$nombre] as $columna) {
                $valor = $registro[strtoupper($columna)] ?? null;

                // Convertir las fechas al formato de la web.
                if ($nombre === "Pedido" &&
                    $columna === "Fecha_pedido" &&
                    $valor !== null) {

                    $valor = date(
                        "Y-m-d",
                        strtotime($valor)
                    );
                }

                $fila[] = $valor;
            }

            $filas[] = $fila;
        }

        $resultado[$nombre] = [
            "columnas" => $columnas[$nombre],
            "filas" => $filas
        ];
    }

    // Devolver los datos como JSON.
    echo json_encode(
        $resultado,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "error" => "No se pudieron cargar las tablas"
    ]);
}
?>
