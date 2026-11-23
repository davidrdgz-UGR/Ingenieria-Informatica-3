
<?php

require_once __DIR__ . "/Conexion.php";

/* ============================================
   GESTIÓN DE PEDIDOS
   Seminario 1 - DDSI
   ============================================ */

class Pedidos
{
    private PDO $db;

    public function __construct(PDO $conexion)
    {
        $this->db = $conexion;
    }

    // Obtener todos los pedidos (sin los detalles).
    public function obtenerPedidos(): array
    {
        $sql = "SELECT Cpedido, Ccliente,
                       TO_CHAR(Fecha_pedido, 'YYYY-MM-DD') AS Fecha_pedido
                FROM Pedido
                ORDER BY Cpedido";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Comprobar que la fecha tiene el formato YYYY-MM-DD.
    private function validarFecha(string $fecha): void
    {
        $f = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if (!$f || $f->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('Fecha no válida');
        }
    }

    // Crear únicamente la cabecera del pedido.
    public function crearPedido(int $codigo, int $cliente, string $fecha): bool
    {
        if ($codigo <= 0 || $cliente <= 0) {
            throw new InvalidArgumentException('Los identificadores deben ser positivos');
        }

        $this->validarFecha($fecha);

        // DATE usa el formato ISO, sin depender de NLS_DATE_FORMAT de Oracle.
        // La fecha ya está validada antes de incluirla en el SQL.
        $sql = "INSERT INTO Pedido (Cpedido, Ccliente, Fecha_pedido)
                VALUES (?, ?, DATE '$fecha')";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$codigo, $cliente]);
    }

    // Modificar cliente y fecha, sin cambiar el código del pedido.
    public function actualizarPedido(int $codigo, int $cliente, string $fecha): bool
    {
        if ($codigo <= 0 || $cliente <= 0) {
            throw new InvalidArgumentException('Los identificadores deben ser positivos');
        }

        $this->validarFecha($fecha);

        $sql = "UPDATE Pedido
                SET Ccliente = ?, Fecha_pedido = DATE '$fecha'
                WHERE Cpedido = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cliente, $codigo]);
        return $stmt->rowCount() > 0;
    }

    // Borrar la cabecera; la API se ocupa antes de los detalles y el stock.
    public function eliminarPedido(int $codigo): bool
    {
        if ($codigo <= 0) {
            throw new InvalidArgumentException('Código de pedido no válido');
        }

        $stmt = $this->db->prepare('DELETE FROM Pedido WHERE Cpedido = ?');
        $stmt->execute([$codigo]);
        return $stmt->rowCount() > 0;
    }
}

/* ============================================
   PRUEBAS DE LA CLASE PEDIDOS
   ============================================ */

// Estas pruebas solo se ejecutan si abrimos directamente este archivo.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    echo '<pre>';

    try {
        $conexion = new Conexion();
        $db = $conexion->obtenerConexion();
        $pedidos = new Pedidos($db);

        echo "CONEXIÓN CORRECTA\n\n";
        echo "OBTENER PEDIDOS\n";
        print_r($pedidos->obtenerPedidos());

        $pedidos = null;
        $db = null;
        $conexion->cerrarConexion();
    } catch (Throwable $e) {
        echo 'ERROR: ' . htmlspecialchars($e->getMessage()) . "\n";
    }

    echo '</pre>';
}
