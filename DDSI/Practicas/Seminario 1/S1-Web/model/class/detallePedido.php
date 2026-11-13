
<?php

require_once __DIR__ . "/Conexion.php";

class DetallePedido
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    // Obtener todos los detalles de pedidos.
    public function obtenerDetalles(): array
    {
        $sql = "SELECT Cpedido, Cproducto, Cantidad
                FROM Detalle_Pedido
                ORDER BY Cpedido, Cproducto";

        return $this->db->query($sql)
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener los detalles de un pedido concreto.
    public function obtenerDetallesPedido(int $codigoPedido): array
    {
        $sql = "SELECT Cpedido, Cproducto, Cantidad
                FROM Detalle_Pedido
                WHERE Cpedido = ?
                ORDER BY Cproducto";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigoPedido]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un detalle concreto.
    public function obtenerDetalle(
        int $codigoPedido,
        int $codigoProducto
    ): ?array {

        $sql = "SELECT Cpedido, Cproducto, Cantidad
                FROM Detalle_Pedido
                WHERE Cpedido = ? AND Cproducto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigoPedido, $codigoProducto]);

        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

        return $detalle ?: null;
    }

    // Comprobar si existe un detalle.
    public function existeDetalle(
        int $codigoPedido,
        int $codigoProducto
    ): bool {
        return $this->obtenerDetalle(
            $codigoPedido,
            $codigoProducto
        ) !== null;
    }

    // Insertar un detalle y descontar el stock.
    public function insertarDetalle(
        int $codigoPedido,
        int $codigoProducto,
        int $cantidad
    ): bool {

        if ($codigoPedido <= 0 ||
            $codigoProducto <= 0 ||
            $cantidad <= 0) {

            throw new InvalidArgumentException(
                "Los códigos y la cantidad deben ser positivos"
            );
        }

        if ($this->existeDetalle($codigoPedido, $codigoProducto)) {
            throw new Exception(
                "El producto ya está incluido en el pedido"
            );
        }

        // Bloquear el producto y consultar el stock.
        $sql = "SELECT Cantidad FROM Stock
                WHERE Cproducto = ? FOR UPDATE";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigoProducto]);

        $stock = $stmt->fetchColumn();
        $stmt->closeCursor();

        if ($stock === false || (int) $stock < $cantidad) {
            throw new Exception(
                "El producto no existe o no hay stock suficiente"
            );
        }

        // Descontar el stock.
        $sql = "UPDATE Stock
                SET Cantidad = Cantidad - ?
                WHERE Cproducto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cantidad, $codigoProducto]);

        // Insertar el detalle.
        $sql = "INSERT INTO Detalle_Pedido
                (Cpedido, Cproducto, Cantidad)
                VALUES (?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $codigoPedido,
            $codigoProducto,
            $cantidad
        ]);
    }

    // Modificar la cantidad de un detalle.
    public function actualizarDetalle(
        int $codigoPedido,
        int $codigoProducto,
        int $nuevaCantidad
    ): bool {

        if ($nuevaCantidad <= 0) {
            throw new InvalidArgumentException(
                "La cantidad debe ser positiva"
            );
        }

        // Bloquear el producto para evitar cambios simultáneos.
        $stmt = $this->db->prepare(
            "SELECT Cantidad FROM Stock
             WHERE Cproducto = ? FOR UPDATE"
        );
        $stmt->execute([$codigoProducto]);

        $stock = $stmt->fetchColumn();
        $stmt->closeCursor();

        if ($stock === false) {
            throw new Exception("El producto no existe");
        }

        // Consultar el detalle actual.
        $stmt = $this->db->prepare(
            "SELECT Cantidad FROM Detalle_Pedido
             WHERE Cpedido = ? AND Cproducto = ?
             FOR UPDATE"
        );
        $stmt->execute([$codigoPedido, $codigoProducto]);

        $cantidadAnterior = $stmt->fetchColumn();
        $stmt->closeCursor();

        if ($cantidadAnterior === false) {
            return false;
        }

        // Diferencia entre la nueva cantidad y la anterior.
        $diferencia = $nuevaCantidad - (int) $cantidadAnterior;

        if ($diferencia > (int) $stock) {
            throw new Exception("Stock insuficiente");
        }

        // Actualizar el stock (si disminuye, se devuelve).
        $stmt = $this->db->prepare(
            "UPDATE Stock
             SET Cantidad = Cantidad - ?
             WHERE Cproducto = ?"
        );
        $stmt->execute([$diferencia, $codigoProducto]);

        // Actualizar la cantidad del detalle.
        $stmt = $this->db->prepare(
            "UPDATE Detalle_Pedido
             SET Cantidad = ?
             WHERE Cpedido = ? AND Cproducto = ?"
        );

        $stmt->execute([
            $nuevaCantidad,
            $codigoPedido,
            $codigoProducto
        ]);

        return true;
    }

    // Eliminar un detalle. El trigger de Oracle devuelve el stock.
    public function eliminarDetalle(
        int $codigoPedido,
        int $codigoProducto
    ): bool {

        if ($codigoPedido <= 0 || $codigoProducto <= 0) {
            throw new InvalidArgumentException("Códigos no válidos");
        }

        $stmt = $this->db->prepare(
            "DELETE FROM Detalle_Pedido
             WHERE Cpedido = ? AND Cproducto = ?"
        );

        $stmt->execute([$codigoPedido, $codigoProducto]);

        return $stmt->rowCount() > 0;
    }

    // Eliminar todos los detalles de un pedido.
    // El trigger restaura el stock de cada fila eliminada.
    public function eliminarDetallesPedido(int $codigoPedido): bool
    {
        if ($codigoPedido <= 0) {
            throw new InvalidArgumentException("Código de pedido no válido");
        }

        $stmt = $this->db->prepare(
            "DELETE FROM Detalle_Pedido WHERE Cpedido = ?"
        );

        return $stmt->execute([$codigoPedido]);
    }

}


/* ============================================
   PRUEBAS DE LA CLASE DETALLE PEDIDO
   ============================================ */

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {

    echo "<pre>";

    $db = null;
    $conexion = null;

    // Reservamos un código de pedido para las pruebas.
    $codigoPedido = 9999;
    $codigoProducto = 1;

    try {

        // 1. CONEXIÓN
        $conexion = new Conexion();
        $db = $conexion->obtenerConexion();

        $detalle = new DetallePedido($db);

        echo "1. CONEXIÓN CORRECTA\n\n";

        // 2. OBTENER DETALLES
        echo "2. OBTENER DETALLES\n";
        print_r($detalle->obtenerDetalles());

        // 3. OBTENER DETALLES DE UN PEDIDO
        echo "\n3. OBTENER DETALLES DEL PEDIDO\n";
        print_r($detalle->obtenerDetallesPedido($codigoPedido));

        // 4. CREAR PEDIDO TEMPORAL
        echo "\n4. CREAR PEDIDO TEMPORAL\n";

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM Pedido WHERE Cpedido = ?"
        );

        $stmt->execute([$codigoPedido]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new Exception(
                "El pedido 9999 ya existe. No se modificará."
            );
        }

        // Obtener el primer cliente existente.
        $idCliente = $db->query(
            "SELECT MIN(ID_Cliente) FROM Clientes"
        )->fetchColumn();

        if ($idCliente === null) {
            throw new Exception("No existen clientes");
        }

        $stmt = $db->prepare(
            "INSERT INTO Pedido
             (Cpedido, Ccliente, Fecha_pedido)
             VALUES (?, ?, SYSDATE)"
        );

        $stmt->execute([$codigoPedido, $idCliente]);

        echo "Pedido temporal creado.\n";

        // 5. INSERTAR DETALLE
        echo "\n5. INSERTAR DETALLE\n";

        $resultado = $detalle->insertarDetalle(
            $codigoPedido,
            $codigoProducto,
            2
        );

        echo $resultado
            ? "OK: Detalle insertado.\n"
            : "ERROR: No se pudo insertar.\n";

        print_r(
            $detalle->obtenerDetalle(
                $codigoPedido,
                $codigoProducto
            )
        );

        // 6. ACTUALIZAR DETALLE
        echo "\n6. ACTUALIZAR DETALLE\n";

        $resultado = $detalle->actualizarDetalle(
            $codigoPedido,
            $codigoProducto,
            3
        );

        echo $resultado
            ? "OK: Cantidad actualizada.\n"
            : "ERROR: No se actualizó.\n";

        // 7. COMPROBAR STOCK
        echo "\n7. COMPROBAR STOCK\n";

        $stmt = $db->prepare(
            "SELECT Cantidad FROM Stock
             WHERE Cproducto = ?"
        );

        $stmt->execute([$codigoProducto]);

        echo "Stock disponible: " .
             $stmt->fetchColumn() . "\n";

        // 8. ELIMINAR DETALLE
        echo "\n8. ELIMINAR DETALLE\n";

        $resultado = $detalle->eliminarDetalle(
            $codigoPedido,
            $codigoProducto
        );

        echo $resultado
            ? "OK: Detalle eliminado.\n"
            : "ERROR: No se eliminó.\n";

        // 9. COMPROBAR QUE NO EXISTE
        echo "\n9. COMPROBAR ELIMINACIÓN\n";

        echo !$detalle->existeDetalle(
            $codigoPedido,
            $codigoProducto
        )
            ? "OK: El detalle ya no existe.\n"
            : "ERROR: El detalle sigue existiendo.\n";

        // 10. PROBAR ELIMINAR TODOS LOS DETALLES
        echo "\n10. ELIMINAR TODOS LOS DETALLES\n";

        $detalle->insertarDetalle(
            $codigoPedido,
            $codigoProducto,
            1
        );

        $detalle->eliminarDetallesPedido($codigoPedido);

        echo empty($detalle->obtenerDetallesPedido($codigoPedido))
            ? "OK: Todos los detalles eliminados.\n"
            : "ERROR: Quedan detalles.\n";

        // 11. ELIMINAR PEDIDO TEMPORAL
        echo "\n11. LIMPIAR PRUEBAS\n";

        $stmt = $db->prepare(
            "DELETE FROM Pedido WHERE Cpedido = ?"
        );

        $stmt->execute([$codigoPedido]);

        // Confirmar todos los cambios.
        $db->exec("COMMIT");

        echo "Pedido temporal eliminado.\n";
        echo "\nPRUEBAS FINALIZADAS.\n";

    } catch (Throwable $e) {

        echo "\nERROR: " . $e->getMessage() . "\n";

        if ($db instanceof PDO) {
            try {
                $db->exec("ROLLBACK");
            } catch (Throwable $ignorado) {
            }
        }

    } finally {

        $detalle = null;
        $db = null;

        if ($conexion !== null) {
            $conexion->cerrarConexion();
        }

        echo "</pre>";
    }
}
