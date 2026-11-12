
<?php

require_once __DIR__ . "/Conexion.php";

class Pedidos
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    // Obtener todos los pedidos.
    public function obtenerPedidos(): array
    {
        $sql = "SELECT Cpedido, Ccliente,
                TO_CHAR(Fecha_pedido, 'YYYY-MM-DD') AS Fecha_pedido
                FROM Pedido
                ORDER BY Cpedido";

        return $this->db->query($sql)
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener todos los detalles de los pedidos.
    public function obtenerDetalles(): array
    {
        $sql = "SELECT * FROM Detalle_Pedido
                ORDER BY Cpedido, Cproducto";

        return $this->db->query($sql)
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener los productos de un pedido concreto.
    public function obtenerDetallesPedido(int $idPedido): array
    {
        $sql = "SELECT * FROM Detalle_Pedido
                WHERE Cpedido = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idPedido]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear un pedido con sus operaciones.
    // Devuelve true si se confirma y false si se cancela.
    public function crearPedido(
        int $idPedido,
        int $idCliente,
        string $fecha,
        array $operaciones
    ): bool {

        if ($idPedido <= 0 || $idCliente <= 0) {
            throw new InvalidArgumentException(
                "Los identificadores deben ser positivos"
            );
        }

        $fechaValida = DateTimeImmutable::createFromFormat(
            "!Y-m-d", $fecha
        );

        if (!$fechaValida || $fechaValida->format("Y-m-d") !== $fecha) {
            throw new InvalidArgumentException("Fecha no válida");
        }

        try {
            // Comenzar la transacción.
            $this->db->beginTransaction();

            // 1. Crear el pedido.
            $sql = "INSERT INTO Pedido
                    (Cpedido, Ccliente, Fecha_pedido)
                    VALUES (?, ?, TO_DATE(?, 'YYYY-MM-DD'))";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPedido, $idCliente, $fecha]);

            // Punto de retorno para eliminar detalles.
            $this->db->exec("SAVEPOINT inicio_detalles");

            // 2. Procesar las operaciones.
            foreach ($operaciones as $indice => $operacion) {

                $tipo = $operacion["tipo"] ?? "";

                switch ($tipo) {

                    case "anadir":

                        $producto = filter_var(
                            $operacion["producto"] ?? null,
                            FILTER_VALIDATE_INT
                        );

                        $cantidad = filter_var(
                            $operacion["cantidad"] ?? null,
                            FILTER_VALIDATE_INT
                        );

                        if ($producto === false || $producto <= 0 ||
                            $cantidad === false || $cantidad <= 0) {
                            throw new Exception("Producto o cantidad no válida");
                        }

                        // Comprobar y bloquear el stock.
                        $sql = "SELECT Cantidad FROM Stock
                                WHERE Cproducto = ? FOR UPDATE";

                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$producto]);

                        $stock = $stmt->fetchColumn();
                        $stmt->closeCursor();

                        if ($stock === false || $stock < $cantidad) {
                            throw new Exception("Stock insuficiente");
                        }

                        // Evitar repetir productos en el mismo pedido.
                        $sql = "SELECT COUNT(*) FROM Detalle_Pedido
                                WHERE Cpedido = ? AND Cproducto = ?";

                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$idPedido, $producto]);

                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception(
                                "El producto ya está en el pedido"
                            );
                        }

                        // Descontar el stock.
                        $sql = "UPDATE Stock
                                SET Cantidad = Cantidad - ?
                                WHERE Cproducto = ?";

                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$cantidad, $producto]);

                        // Añadir detalle.
                        $sql = "INSERT INTO Detalle_Pedido
                                (Cpedido, Cproducto, Cantidad)
                                VALUES (?, ?, ?)";

                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            $idPedido, $producto, $cantidad
                        ]);

                        break;

                    case "limpiar":

                        // Eliminar los detalles y restaurar el stock.
                        // El pedido principal permanece.
                        $this->db->exec(
                            "ROLLBACK TO SAVEPOINT inicio_detalles"
                        );

                        break;

                    case "cancelar":

                        // Deshacer todo el pedido.
                        if ($indice !== array_key_last($operaciones)) {
                            throw new Exception(
                                "Cancelar debe ser la última operación"
                            );
                        }

                        $this->db->rollBack();
                        return false;

                    case "finalizar":

                        // Confirmar definitivamente el pedido.
                        if ($indice !== array_key_last($operaciones)) {
                            throw new Exception(
                                "Finalizar debe ser la última operación"
                            );
                        }

                        $this->db->commit();
                        return true;

                    default:
                        throw new Exception("Operación desconocida");
                }
            }

            throw new Exception(
                "El pedido debe finalizarse o cancelarse"
            );

        } catch (Throwable $e) {

            // Deshacer todos los cambios si ocurre un error.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}




/* ============================================
   PRUEBAS DE LA CLASE PEDIDOS
   ============================================ */

// Evitar ejecutar las pruebas al importar esta clase.
if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {

    echo "<pre>";

    try {
        // 1. CONEXIÓN
        $conexion = new Conexion();
        $db = $conexion->obtenerConexion();
        $pedidos = new Pedidos($db);

        echo "1. CONEXIÓN CORRECTA\n\n";

        // Comprobar que existen las tablas necesarias.
        $tablas = ["STOCK", "PEDIDO", "DETALLE_PEDIDO"];

        foreach ($tablas as $nombre) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM user_tables
                 WHERE table_name = ?"
            );
            $stmt->execute([$nombre]);

            if ((int) $stmt->fetchColumn() === 0) {
                throw new Exception(
                    "Falta crear la tabla $nombre en Oracle."
                );
            }
        }

        // 2. CONSULTAR PEDIDOS
        echo "2. OBTENER PEDIDOS\n";

        $resultado = $pedidos->obtenerPedidos();
        print_r($resultado);

        // 3. CONSULTAR DETALLES
        echo "\n3. OBTENER DETALLES\n";

        $resultado = $pedidos->obtenerDetalles();
        print_r($resultado);

        // 4. BUSCAR PRODUCTO CON STOCK
        echo "\n4. COMPROBAR STOCK\n";

        $sql = "SELECT Cproducto, Cantidad
                FROM Stock
                WHERE Cantidad >= 2
                ORDER BY Cproducto";

        $producto = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception(
                "Necesitas un producto con al menos 2 unidades."
            );
        }

        $idProducto = (int) $producto["CPRODUCTO"];
        $stockInicial = (int) $producto["CANTIDAD"];

        echo "Producto: $idProducto\n";
        echo "Stock inicial: $stockInicial\n";

        // 5. PREPARAR IDENTIFICADORES DE PRUEBA
        $sql = "SELECT NVL(MAX(Cpedido), 0) + 1 FROM Pedido";

        $idPedido = (int) $db->query($sql)->fetchColumn();

        // Elegir un cliente existente si hay tabla Clientes.
        $idCliente = 100;

        $existeClientes = (int) $db->query(
            "SELECT COUNT(*) FROM user_tables
             WHERE table_name = 'CLIENTES'"
        )->fetchColumn();

        if ($existeClientes) {
            $cliente = $db->query(
                "SELECT MIN(ID_Cliente) FROM Clientes"
            )->fetchColumn();

            if ($cliente === null) {
                throw new Exception(
                    "La tabla Clientes no tiene registros."
                );
            }

            $idCliente = (int) $cliente;
        }

        $fecha = date("Y-m-d");

        // 6. PROBAR COMMIT
        echo "\n5. CREAR PEDIDO Y COMMIT\n";

        $operaciones = [
            [
                "tipo" => "anadir",
                "producto" => $idProducto,
                "cantidad" => 2
            ],
            ["tipo" => "finalizar"]
        ];

        $resultado = $pedidos->crearPedido(
            $idPedido,
            $idCliente,
            $fecha,
            $operaciones
        );

        echo $resultado
            ? "Pedido confirmado correctamente.\n"
            : "ERROR: Pedido no confirmado.\n";

        // 7. COMPROBAR DETALLES DEL PEDIDO
        echo "\n6. DETALLES DEL PEDIDO\n";

        $detalles = $pedidos->obtenerDetallesPedido($idPedido);
        print_r($detalles);

        // 8. COMPROBAR STOCK ACTUALIZADO
        echo "\n7. STOCK DESPUÉS DEL PEDIDO\n";

        $stmt = $db->prepare(
            "SELECT Cantidad FROM Stock WHERE Cproducto = ?"
        );
        $stmt->execute([$idProducto]);

        $stockActual = (int) $stmt->fetchColumn();

        echo "Stock inicial: $stockInicial\n";
        echo "Stock actual: $stockActual\n";

        if ($stockActual === $stockInicial - 2) {
            echo "OK: Stock descontado correctamente.\n";
        } else {
            echo "ERROR: Stock incorrecto.\n";
        }

        // 9. PROBAR SAVEPOINT
        echo "\n8. PROBAR SAVEPOINT\n";

        $operaciones = [
            [
                "tipo" => "anadir",
                "producto" => $idProducto,
                "cantidad" => 1
            ],
            ["tipo" => "limpiar"],
            ["tipo" => "finalizar"]
        ];

        $pedidos->crearPedido(
            $idPedido + 1,
            $idCliente,
            $fecha,
            $operaciones
        );

        $detalles = $pedidos->obtenerDetallesPedido(
            $idPedido + 1
        );

        echo empty($detalles)
            ? "OK: SAVEPOINT elimina los detalles.\n"
            : "ERROR: Los detalles siguen existiendo.\n";

        // 10. PROBAR ROLLBACK
        echo "\n9. PROBAR CANCELACIÓN\n";

        $operaciones = [
            [
                "tipo" => "anadir",
                "producto" => $idProducto,
                "cantidad" => 1
            ],
            ["tipo" => "cancelar"]
        ];

        $resultado = $pedidos->crearPedido(
            $idPedido + 2,
            $idCliente,
            $fecha,
            $operaciones
        );

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM Pedido WHERE Cpedido = ?"
        );
        $stmt->execute([$idPedido + 2]);

        $existePedido = (int) $stmt->fetchColumn();

        echo (!$resultado && $existePedido === 0)
            ? "OK: Pedido cancelado mediante ROLLBACK.\n"
            : "ERROR: No se canceló correctamente.\n";

        echo "\nPRUEBAS FINALIZADAS.\n";

    } catch (Throwable $e) {
        echo "\nERROR: " . $e->getMessage() . "\n";
    }

    echo "</pre>";
}
