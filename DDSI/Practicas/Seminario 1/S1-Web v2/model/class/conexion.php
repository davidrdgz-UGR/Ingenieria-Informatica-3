
<?php

class Conexion
{
    private PDO $conexion;

    public function __construct(){
        // DSN ODBC configurado en el Administrador ODBC de Windows.
        $usuario = "x5123750";
        $password = "x5123750";
        $dsn = "odbc:OracleDDSI";

        $this->conexion = new PDO($dsn, $usuario, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    public function obtenerConexion(): PDO{
        return $this->conexion;
    }

    public function cerrarConexion(): void{
        unset($this->conexion);
    }
}
