
<?php

require_once __DIR__ . "/Conexion.php";

class Clientes
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    // Obtener todos los clientes.
    public function obtenerClientes(): array
    {
        $sql = "SELECT ID_Cliente, Nombre
                FROM Clientes ORDER BY ID_Cliente";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un cliente por su código.
    public function obtenerCliente(int $codigo): ?array
    {
        $sql = "SELECT ID_Cliente, Nombre
                FROM Clientes WHERE ID_Cliente = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo]);

        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cliente ?: null;
    }

    // Comprobar si existe un cliente.
    public function existeCliente(int $codigo): bool
    {
        return $this->obtenerCliente($codigo) !== null;
    }

    // Insertar un nuevo cliente.
    public function insertarCliente(int $codigo, string $nombre): bool
    {
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

        if ($this->existeCliente($codigo)) {
            throw new Exception(
                "Ya existe un cliente con ese código"
            );
        }

        $sql = "INSERT INTO Clientes (ID_Cliente, Nombre)
                VALUES (?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$codigo, trim($nombre)]);
    }

    // Modificar el nombre de un cliente.
    public function actualizarCliente(int $codigo, string $nombre): bool
    {
        if ($codigo <= 0 || trim($nombre) === "" ||
            mb_strlen($nombre) > 50) {
            throw new InvalidArgumentException(
                "Datos del cliente no válidos"
            );
        }

        $sql = "UPDATE Clientes
                SET Nombre = ?
                WHERE ID_Cliente = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([trim($nombre), $codigo]);

        return $stmt->rowCount() > 0;
    }

    // Eliminar un cliente.
    public function eliminarCliente(int $codigo): bool
    {
        if ($codigo <= 0) {
            throw new InvalidArgumentException(
                "Código no válido"
            );
        }

        $sql = "DELETE FROM Clientes WHERE ID_Cliente = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo]);

        return $stmt->rowCount() > 0;
    }
}


/* ============================================
   PRUEBAS DE LA CLASE CLIENTES
   ============================================ */

if (realpath($_SERVER["SCRIPT_FILENAME"] ?? "") === __FILE__) {

    echo "<pre>";

    $db = null;
    $conexion = null;
    $cliente = null;

    // Código reservado para nuestras pruebas.
    $codigoPrueba = 9999;

    try {

        // 1. CONEXIÓN
        $conexion = new Conexion();
        $db = $conexion->obtenerConexion();
        $cliente = new Clientes($db);

        echo "1. CONEXIÓN CORRECTA\n\n";

        // 2. OBTENER CLIENTES
        echo "2. OBTENER CLIENTES\n";
        print_r($cliente->obtenerClientes());

        // 3. OBTENER UN CLIENTE
        echo "\n3. OBTENER CLIENTE (ID 1)\n";
        print_r($cliente->obtenerCliente(1));

        // 4. COMPROBAR EXISTENCIA
        echo "\n4. COMPROBAR EXISTENCIA\n";

        echo $cliente->existeCliente(1)
            ? "OK: El cliente existe.\n"
            : "El cliente no existe.\n";

        // 5. INSERTAR CLIENTE
        echo "\n5. INSERTAR CLIENTE\n";

        if ($cliente->existeCliente($codigoPrueba)) {
            throw new Exception(
                "El cliente 9999 ya existe. No se modificará."
            );
        }

        $resultado = $cliente->insertarCliente(
            $codigoPrueba,
            "Cliente Prueba"
        );

        echo $resultado
            ? "OK: Cliente insertado.\n"
            : "ERROR: No se pudo insertar.\n";

        $db->exec("COMMIT");

        // 6. CONSULTAR CLIENTE INSERTADO
        echo "\n6. CONSULTAR CLIENTE INSERTADO\n";
        print_r($cliente->obtenerCliente($codigoPrueba));

        // 7. ACTUALIZAR CLIENTE
        echo "\n7. ACTUALIZAR CLIENTE\n";

        $resultado = $cliente->actualizarCliente(
            $codigoPrueba,
            "Cliente Modificado"
        );

        echo $resultado
            ? "OK: Cliente actualizado.\n"
            : "ERROR: No se pudo actualizar.\n";

        print_r($cliente->obtenerCliente($codigoPrueba));

        $db->exec("COMMIT");

        // 8. ELIMINAR CLIENTE
        echo "\n8. ELIMINAR CLIENTE\n";

        $resultado = $cliente->eliminarCliente($codigoPrueba);

        echo $resultado
            ? "OK: Cliente eliminado.\n"
            : "ERROR: No se pudo eliminar.\n";

        $db->exec("COMMIT");

        // 9. COMPROBAR ELIMINACIÓN
        echo "\n9. COMPROBAR ELIMINACIÓN\n";

        echo !$cliente->existeCliente($codigoPrueba)
            ? "OK: El cliente ya no existe.\n"
            : "ERROR: El cliente sigue existiendo.\n";

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

        $cliente = null;
        $db = null;

        if ($conexion !== null) {
            $conexion->cerrarConexion();
        }

        $conexion = null;

        echo "</pre>";
    }
}
