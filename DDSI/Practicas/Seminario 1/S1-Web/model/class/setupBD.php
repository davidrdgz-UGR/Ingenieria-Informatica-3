
<?php

require_once __DIR__ . "/Conexion.php";

/* ============================================
   CONFIGURACIÓN / REINICIO DE LA BASE DE DATOS
   Seminario 1 - DDSI
   ============================================ */

try {
    $conexion = new Conexion();
    $db = $conexion->obtenerConexion();

    echo "<pre>";
    echo "CONFIGURACIÓN DE LA BASE DE DATOS\n\n";

    // 1. ELIMINAR TABLAS EXISTENTES
    // Primero eliminamos las tablas dependientes.

    $ordenBorrado = [
        "DETALLE_PEDIDO",
        "PEDIDO",
        "CLIENTES",
        "STOCK"
    ];

    foreach ($ordenBorrado as $nombre) {

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM user_tables
             WHERE table_name = ?"
        );

        $stmt->execute([$nombre]);

        if ((int) $stmt->fetchColumn() > 0) {
            $db->exec("DROP TABLE $nombre");

            echo "Tabla $nombre eliminada.\n";
        }
    }

    // 2. CREAR LAS TABLAS

    echo "\nCREANDO TABLAS...\n";

    $tablas = [

        "STOCK" => "
            CREATE TABLE Stock (
                Cproducto NUMBER PRIMARY KEY,
                Nombre VARCHAR2(50) NOT NULL,
                Cantidad NUMBER DEFAULT 0 NOT NULL,
                CONSTRAINT chk_stock_cantidad
                    CHECK (Cantidad >= 0)
            )
        ",

        "CLIENTES" => "
            CREATE TABLE Clientes (
                ID_Cliente NUMBER PRIMARY KEY,
                Nombre VARCHAR2(50) NOT NULL
            )
        ",

        "PEDIDO" => "
            CREATE TABLE Pedido (
                Cpedido NUMBER PRIMARY KEY,
                Ccliente NUMBER NOT NULL,
                Fecha_pedido DATE NOT NULL,

                CONSTRAINT fk_pedido_cliente
                    FOREIGN KEY (Ccliente)
                    REFERENCES Clientes(ID_Cliente)
            )
        ",

        "DETALLE_PEDIDO" => "
            CREATE TABLE Detalle_Pedido (
                Cpedido NUMBER NOT NULL,
                Cproducto NUMBER NOT NULL,
                Cantidad NUMBER NOT NULL,

                CONSTRAINT pk_detalle
                    PRIMARY KEY (Cpedido, Cproducto),

                CONSTRAINT fk_detalle_pedido
                    FOREIGN KEY (Cpedido)
                    REFERENCES Pedido(Cpedido),

                CONSTRAINT fk_detalle_producto
                    FOREIGN KEY (Cproducto)
                    REFERENCES Stock(Cproducto),

                CONSTRAINT chk_detalle_cantidad
                    CHECK (Cantidad > 0)
            )
        "
    ];

    foreach ($tablas as $nombre => $sql) {
        $db->exec($sql);
        echo "Tabla $nombre creada.\n";
    }

    // 3. INSERTAR LOS 10 PRODUCTOS INICIALES

    echo "\nINSERTANDO PRODUCTOS...\n";

    $productos = [
        [1, "Teclado", 50],
        [2, "Ratón", 40],
        [3, "Monitor", 30],
        [4, "Portátil", 25],
        [5, "Auriculares", 60],
        [6, "Webcam", 15],
        [7, "Cable HDMI", 80],
        [8, "Impresora", 10],
        [9, "Altavoces", 45],
        [10, "Micrófono", 20]
    ];

    $stmt = $db->prepare(
        "INSERT INTO Stock (Cproducto, Nombre, Cantidad)
         VALUES (?, ?, ?)"
    );

    foreach ($productos as $producto) {
        $stmt->execute($producto);
    }

    // 4. INSERTAR CLIENTES INICIALES

    echo "\nINSERTANDO CLIENTES...\n";

    $clientes = [
        [1, "Ana"],
        [2, "Luis"],
        [3, "Marta"]
    ];

    $stmt = $db->prepare(
        "INSERT INTO Clientes (ID_Cliente, Nombre)
         VALUES (?, ?)"
    );

    foreach ($clientes as $cliente) {
        $stmt->execute($cliente);
    }

    // Confirmar inserciones.
    $db->exec("COMMIT");

    // 5. COMPROBAR RESULTADO

    echo "\nRESUMEN DE LA BASE DE DATOS\n";

    foreach (array_keys($tablas) as $nombre) {

        $total = $db->query(
            "SELECT COUNT(*) FROM $nombre"
        )->fetchColumn();

        echo "$nombre: $total registros.\n";
    }

    echo "\nBase de datos preparada correctamente.\n";
    echo "</pre>";

    $stmt = null;
    $db = null;
    $conexion->cerrarConexion();

} catch (Throwable $e) {

    echo "<pre>";
    echo "ERROR: " .
        htmlspecialchars($e->getMessage()) .
        "</pre>";
}

?>
