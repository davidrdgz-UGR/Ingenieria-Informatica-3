
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
        $sql = "SELECT Cproducto, Nombre, Cantidad
                FROM Stock ORDER BY Cproducto";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un producto por su código.
    public function obtenerProducto(int $codigo): ?array
    {
        $sql = "SELECT Cproducto, Nombre, Cantidad
                FROM Stock WHERE Cproducto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo]);

        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $producto ?: null;
    }

    // Comprobar si existe un producto.
    public function existeProducto(int $codigo): bool
    {
        return $this->obtenerProducto($codigo) !== null;
    }

    // Comprobar si hay suficiente stock.
    public function comprobarStock(int $codigo, int $cantidad): bool
    {
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
        string $nombre,
        int $cantidad
    ): bool {

        if ($codigo <= 0) {
            throw new InvalidArgumentException(
                "El código debe ser positivo"
            );
        }

        if (trim($nombre) === "" || mb_strlen($nombre) > 50) {
            throw new InvalidArgumentException(
                "El nombre debe tener entre 1 y 50 caracteres"
            );
        }

        if ($cantidad < 0) {
            throw new InvalidArgumentException(
                "La cantidad no puede ser negativa"
            );
        }

        if ($this->existeProducto($codigo)) {
            throw new Exception(
                "Ya existe un producto con ese código"
            );
        }

        $sql = "INSERT INTO Stock
                (Cproducto, Nombre, Cantidad)
                VALUES (?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$codigo, trim($nombre), $cantidad]);
    }

    // Actualizar únicamente la cantidad disponible.
    public function actualizarStock(int $codigo, int $cantidad): bool
    {
        if ($cantidad < 0) {
            throw new InvalidArgumentException(
                "La cantidad no puede ser negativa"
            );
        }

        $sql = "UPDATE Stock
                SET Cantidad = ?
                WHERE Cproducto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cantidad, $codigo]);

        return $stmt->rowCount() > 0;
    }

    // Modificar nombre y cantidad de un producto.
    public function actualizarProducto(
        int $codigo,
        string $nombre,
        int $cantidad
    ): bool {

        if ($codigo <= 0 ||
            trim($nombre) === "" ||
            mb_strlen($nombre) > 50 ||
            $cantidad < 0) {

            throw new InvalidArgumentException(
                "Datos del producto no válidos"
            );
        }

        $sql = "UPDATE Stock
                SET Nombre = ?, Cantidad = ?
                WHERE Cproducto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($nombre),
            $cantidad,
            $codigo
        ]);

        return $stmt->rowCount() > 0;
    }

    // Eliminar un producto.
    public function eliminarProducto(int $codigo): bool
    {
        if ($codigo <= 0) {
            throw new InvalidArgumentException(
                "Código no válido"
            );
        }

        $sql = "DELETE FROM Stock
                WHERE Cproducto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo]);

        return $stmt->rowCount() > 0;
    }
}


/* ============================================
   PRUEBAS DE LA CLASE PRODUCTO
   ============================================ */

// Ejecutar las pruebas solo al abrir directamente este archivo.
if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {

    echo "<pre>";

    $db = null;
    $conexion = null;

    // Usamos un código de prueba que no pertenece
    // a los 10 productos iniciales.
    $codigoPrueba = 9999;

    try {

        // 1. CONEXIÓN
        $conexion = new Conexion();
        $db = $conexion->obtenerConexion();
        $producto = new Producto($db);

        echo "1. CONEXIÓN CORRECTA\n\n";

        // 2. OBTENER PRODUCTOS
        echo "2. OBTENER PRODUCTOS\n";

        print_r($producto->obtenerProductos());

        // 3. OBTENER UN PRODUCTO
        echo "\n3. OBTENER PRODUCTO (ID 1)\n";

        print_r($producto->obtenerProducto(1));

        // 4. COMPROBAR STOCK
        echo "\n4. COMPROBAR STOCK\n";

        echo $producto->comprobarStock(1, 2)
            ? "OK: Hay suficiente stock.\n"
            : "No hay suficiente stock.\n";

        // 5. INSERTAR PRODUCTO DE PRUEBA
        echo "\n5. INSERTAR PRODUCTO\n";

        if ($producto->existeProducto($codigoPrueba)) {
            throw new Exception(
                "Ya existe el producto 9999. No se modificará."
            );
        }

        $resultado = $producto->insertarProducto(
            $codigoPrueba,
            "Producto Prueba",
            20
        );

        echo $resultado
            ? "OK: Producto insertado.\n"
            : "ERROR: No se pudo insertar.\n";

        // Confirmar el INSERT para que otras sesiones puedan verlo.
        $db->exec("COMMIT");

        // 6. COMPROBAR PRODUCTO INSERTADO
        echo "\n6. CONSULTAR PRODUCTO INSERTADO\n";

        print_r($producto->obtenerProducto($codigoPrueba));

        // 7. ACTUALIZAR STOCK
        echo "\n7. ACTUALIZAR STOCK\n";

        $resultado = $producto->actualizarStock(
            $codigoPrueba,
            15
        );

        echo $resultado
            ? "OK: Stock actualizado.\n"
            : "ERROR: Stock no actualizado.\n";

        print_r($producto->obtenerProducto($codigoPrueba));

        // 8. ACTUALIZAR PRODUCTO
        echo "\n8. MODIFICAR PRODUCTO\n";

        $resultado = $producto->actualizarProducto(
            $codigoPrueba,
            "Producto Modificado",
            30
        );

        echo $resultado
            ? "OK: Producto modificado.\n"
            : "ERROR: No se pudo modificar.\n";

        print_r($producto->obtenerProducto($codigoPrueba));

        // Confirmar modificaciones.
        $db->exec("COMMIT");

        // 9. ELIMINAR PRODUCTO
        echo "\n9. ELIMINAR PRODUCTO\n";

        $resultado = $producto->eliminarProducto($codigoPrueba);

        echo $resultado
            ? "OK: Producto eliminado.\n"
            : "ERROR: Producto no eliminado.\n";

        $db->exec("COMMIT");

        // 10. COMPROBAR ELIMINACIÓN
        echo "\n10. COMPROBAR ELIMINACIÓN\n";

        $resultado = $producto->obtenerProducto($codigoPrueba);

        echo $resultado === null
            ? "OK: El producto ya no existe.\n"
            : "ERROR: El producto sigue existiendo.\n";

        echo "\nPRUEBAS FINALIZADAS.\n";

    } catch (Throwable $e) {

        echo "\nERROR: " . $e->getMessage() . "\n";

        // Deshacer los cambios pendientes si hay un error.
        if ($db instanceof PDO) {
            try {
                $db->exec("ROLLBACK");
            } catch (Throwable $ignorado) {
            }
        }

    } finally {

        // Liberar recursos.
        $producto = null;
        $db = null;

        if ($conexion !== null) {
            $conexion->cerrarConexion();
        }

        $conexion = null;

        echo "</pre>";
    }
}
