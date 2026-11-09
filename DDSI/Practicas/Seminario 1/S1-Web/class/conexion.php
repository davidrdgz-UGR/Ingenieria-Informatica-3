
<?php

class Conexion
{
    private PDO $conexion;

    public function __construct()
    {
        // DSN ODBC configurado previamente en el equipo.
        $dsn = "odbc:OracleDDSI";
        $usuario = "TU_USUARIO";
        $password = "TU_PASSWORD";

        $this->conexion = new PDO($dsn, $usuario, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_AUTOCOMMIT => false
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
