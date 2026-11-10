
<?php

require_once __DIR__ . "/Conexion.php";

class Producto
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    // Obtener todos los productos.
    public function obtenerProductos(): array
    {
        $sql = "SELECT Cproducto, Cantidad FROM Stock
                ORDER BY Cproducto";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un producto por su código.
    public function obtenerProducto(int $codigo): ?array
    {
        $sql = "SELECT Cproducto, Cantidad
                FROM Stock
                WHERE Cproducto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo]);

        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $producto ?: null;
    }

    // Comprobar si hay suficiente stock.
    public function comprobarStock(
        int $codigo,
        int $cantidad
    ): bool {
        if ($cantidad <= 0) {
            return false;
        }

        $producto = $this->obtenerProducto($codigo);

        return $producto !== null
            && (int) $producto["CANTIDAD"] >= $cantidad;
    }

    // Insertar un nuevo producto.
    public function insertarProducto(
        int $codigo,
        int $cantidad
    ): bool {
        if ($cantidad < 0) {
            throw new InvalidArgumentException(
                "La cantidad no puede ser negativa"
            );
        }

        $sql = "INSERT INTO Stock (Cproducto, Cantidad)
                VALUES (?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$codigo, $cantidad]);
    }

    // Actualizar la cantidad disponible.
    public function actualizarStock(
        int $codigo,
        int $cantidad
    ): bool {
        if ($cantidad < 0) {
            throw new InvalidArgumentException(
                "La cantidad no puede ser negativa"
            );
        }

        $sql = "UPDATE Stock
                SET Cantidad = ?
                WHERE Cproducto = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$cantidad, $codigo]);
    }
}
