
<?php

class Conexion
{
    private PDO $conexion;

    public function __construct()
    {
        // DSN ODBC configurado previamente en el equipo.
        $host = "oracle0.ugr.es";
        $puerto = "1521";
        $servicio = "practbd";
        $usuario = "x5123750";
        $password = "x5123750";

        $dsn = "odbc:OracleDDSI";

        $this->conexion = new PDO($dsn, $usuario, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    public function obtenerConexion(): PDO
    {
        return $this->conexion;
    }

    public function cerrarConexion(): void
    {
        unset($this->conexion);
    }
}

/* PRUEBAS Y CONEXIÓN */

$conexion = new Conexion();
$db = $conexion->obtenerConexion();

echo "<h2>Conexión con Oracle correcta</h2>";

// 2. Mostrar el usuario conectado
$sql = "SELECT * FROM PRUEBA_DDSI";

$stmt = $db->query($sql);

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($productos);