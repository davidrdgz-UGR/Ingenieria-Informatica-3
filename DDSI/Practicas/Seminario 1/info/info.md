## **SEMINARIO 1 - DDSI | GRUPO: CEUTAFEET**

Para está práctica hemos decidido hacer una página web, a preferencia de nuestro integrante `David`.

La práctica se podría haber enfocado al funcionamiento en terminal, e incluso así podriamos haber controlado y mostrado mejor el funcionamiento interno del programa, pero se hará una explicacion a fondo de este y una noción básica del frontend.

La web hemos decidido hacerla con HTML, CSS, y JAVASCRIPT para el frontend, y PHP con consultas desde Javascript (AJAX) para el backend. \

En resumen, primero tenemos la estructura de la web en HTML y CSS para el diseño, le añadimos Javascript para hacerla dinámica ( añadir desplegables o formularios que aparecen dinámicamente con los botones, o cambiar entre tablas ), pero hay ciertos botones que tienen funcionalidades internas, aqui entramos en consultas AJAX, ya sea para traernos información del Servidor o enviarla, esta llega al código PHP, hace sus respectivas consultas a la BBDD y, en tiempo real Javascript hace esos cambios en la web, de está forma no tenemos que estar refrescando la página todo el rato.

Con esto ya podemos empezar con todas las explicaciones y documentación.


### **HTML / CSS / JAVASCRIPT  ( FRONTEND )**

Antes empezar a explicar toda la parte del servidor, vamos a hacer una breve explicación, no entraremos en detalle con el código, ya que no es el objetivo de la práctica.

Normalmente el codigo Javascript y el CSS se puede organizar en distintos archivos, pero como no es el caso de una página web que vayamos a extender más lo hemos dejado todo en sus archivos principales ( aunque estén algo desordenados y caóticos ).

- 1. Primero hemos creado una estructura básica en HTML, con las etiquetas básicas, un header para la barra superior, y dentro del main ya metemos el section con sus div's correspondientes y la tabla, que irá cambiando dinámicamente dependiendo de la elegida.

- 2. Lo siguiente es añadirle el estilo con CSS, el cuál funciona seleccionando por clases/ids o incluso por la propia etiqueta de HTML y funciona en cascada. CSS tiene una infinidad de opciones para añadir a las etiquetas, hemos añadido colores, distribución de los contenedores, paddings, borders etc.

- 3. Finalmente tenemos el código Javascript que tiene varias funciones, una de ellas es la creación de codigo HTML en tiempo real para el dinamismo, por ejemplo cuando cambiamos de tabla o queremos modificar o añadir datos, todo esto más de cara al cliente. De cara al servidor se encarga de consultas con los distintos archivos PHP mediante AJAX ( básicamente peticiones fetch ), ya sea para traernos información al cliente, como para enviar la información de los formularios y PHP interactue con la base de datos.

![cap1](cap/cap1.png)


### SETUP PHP Y ORACLE

Ahora vamos a explicar el proceso de preparación, para programar, conexión con la BD y todo lo relacionado.

Lo primero de todo hemos descargado XAMPP, que viene con todo incluido, PHP, SQL, Apache, etc. También tenemos la opción de añadir extensiones como PHP Server, pero aún así necesitamos tener PHP instalado. Tras descargarlo todo, añadimos nuestra ruta de PHP ( C:/xampp/php/ ) a nuestras variables de entorno y ya podríamos empezar. ( Puede que necesitamos configurar el IDE con ciertas rutas, pero depende de este. )

![cap2](cap/cap2.png)

Después necesitamos Oracle SQL Developer o por su defecto alguna de las extensiones que pueda sustituirla en nuestro IDE, para conectarnos con la Base de Datos de la UGR.

![cap3](cap/cap3.png)

Finalmente para conectar nuestro lenguaje con la Base de datos de Oracle necesitaremos ( dependiendo del sistema ) otras herramientas, en nuestro caso con PHP necesitamos Oracle Instant Client, junto con el ODBC Package ( También hemos añadido SQLPlus, que nos ayuda a conectarnos por terminal ), puede que también necesitemos añadir la ruta al PATH de variables de entorno.

Finalmente configuramos el ODBC entrando con Windows + R en `odbcad32` para poder conectarnos mediante php y ya tendriamos todo listo.

![cap4](cap/cap4.png)

![cap5](cap/cap5.png)

### setupDB.php / conexion.php

Este archivo se encarga de preparar y reiniciar nuestra base de datos Oracle. 

Para ello, utiliza la conexión definida en Conexion.php, elimina las tablas existentes, las vuelve a crear e introduce unos datos iniciales para realizar pruebas. En PHP tenemos varias formas de conectarnos, hemos usado PDO que es la más recomendable para una BD de Oracle y para nuestro caso.

La conexión en php se suele crear como un objeto, en el que tenemos los métodos obtenerConexion() y cerrarConexion().

```php

public function __construct(){
        // DSN ODBC configurado previamente en el equipo.
        $host = "oracle0.ugr.es";
        $puerto = "1521";
        $servicio = "practbd";
        $usuario = "x5123750";
        $password = "x5123750";
        // $dsn = "oci:dbname=//$host:$puerto/$servicio";

        $dsn = "odbc:OracleDDSI";

        $this->conexion = new PDO($dsn, $usuario, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

```

Una vez ya en setup.php, iniciamos la conexión importando el archivo de conexion.php y creando un objeto y usando sus métodos. Además este archivo solo permite peticiones POST desde el Frontend. Para borrar los datos creamos un array con el orden de borrado por si acaso, creamos la consulta y hacemos la solicitud.

```php

require_once __DIR__ . "/Conexion.php";

$conexion = new Conexion();
$db = $conexion->obtenerConexion();


// ==================================

foreach ($ordenBorrado as $nombre) {

    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM user_tables
            WHERE table_name = ?"
    );

    $stmt->execute([$nombre]);

    if ((int) $stmt->fetchColumn() > 0) {
        $db->exec("DROP TABLE $nombre");

        // echo "Tabla $nombre eliminada.\n";
    }
}

```

Después volvemos a crear la estructura de la BBDD en un array con la estructura y varias variables con sus datos por defecto y hacemos lo mismo, ejecutamos la consulta y ya tendríamos creada nuestra BD por defecto.

Finalmente cerramos la conexion con la base de datos, y enviamos los datos que necesitemos para la web al Frontend.


```php

    foreach ($tablas as $nombre => $sql) {
        $db->exec($sql);

        // echo "Tabla $nombre creada.\n";
    }

    /* ======================== */

    $stmt = $db->prepare(
        "INSERT INTO Stock (Cproducto, Nombre, Cantidad) VALUES (?, ?, ?)"
    );

    foreach ($productos as $producto) {
        $stmt->execute($producto);
    }

    foreach ($clientes as $cliente) {
        $stmt->execute($cliente);
    }

    $db->exec("COMMIT");
```