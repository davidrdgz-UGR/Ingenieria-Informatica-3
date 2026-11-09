
<?php

require_once __DIR__ . "/Conexion.php";

class Pedidos
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    // Obtener todos los productos y sus existencias.
    public function obtenerStock(): array
    {
        $sql = "SELECT Cproducto, Cantidad FROM Stock";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener los pedidos existentes.
    public function obtenerPedidos(): array
    {
        $sql = "SELECT * FROM Pedido";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear un pedido con sus productos.
    public function crearPedido(
        int $idPedido,
        int $idCliente,
        string $fecha,
        array $operaciones
    ): bool {
        try {
            $this->db->beginTransaction();

            // Insertar el nuevo pedido.
            $sql = "INSERT INTO Pedido
                    (Cpedido, Ccliente, Fecha_pedido)
                    VALUES (?, ?, TO_DATE(?, 'YYYY-MM-DD'))";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPedido, $idCliente, $fecha]);

            // Punto al que volver para eliminar los detalles.
            $this->db->exec("SAVEPOINT inicio_detalles");

            foreach ($operaciones as $operacion) {

                if ($operacion["tipo"] === "limpiar") {
                    // Deshacer los detalles y restaurar el stock.
                    $this->db->exec(
                        "ROLLBACK TO SAVEPOINT inicio_detalles"
                    );
                    continue;
                }

                if ($operacion["tipo"] !== "anadir") {
                    throw new Exception("Operación desconocida");
                }

                $producto = (int) $operacion["producto"];
                $cantidad = (int) $operacion["cantidad"];

                if ($cantidad <= 0) {
                    throw new Exception("Cantidad no válida");
                }

                // Consultar y bloquear el stock del producto.
                $sql = "SELECT Cantidad FROM Stock
                        WHERE Cproducto = ? FOR UPDATE";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$producto]);
                $stock = $stmt->fetchColumn();

                if ($stock === false || $stock < $cantidad) {
                    throw new Exception("Stock insuficiente");
                }

                // Descontar el stock.
                $sql = "UPDATE Stock
                        SET Cantidad = Cantidad - ?
                        WHERE Cproducto = ?";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$cantidad, $producto]);

                // Añadir el producto al detalle del pedido.
                $sql = "INSERT INTO Detalle_Pedido
                        (Cpedido, Cproducto, Cantidad)
                        VALUES (?, ?, ?)";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idPedido, $producto, $cantidad]);
            }

            // Confirmar todos los cambios.
            $this->db->commit();
            return true;

        } catch (Throwable $e) {

            // Deshacer todo si ocurre algún error.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
