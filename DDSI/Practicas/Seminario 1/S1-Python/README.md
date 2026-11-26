# Seminario 1 - DDSI (Python + ODBC)

Aplicación de terminal que trabaja con `Stock`, `Pedido` y `Detalle_Pedido`
en Oracle mediante ODBC (pyodbc).

## Requisitos
1. Python 3.10 o superior.
2. `pip install pyodbc`
3. Un driver ODBC de Oracle (Oracle Instant Client + ODBC) y un origen de
   datos (DSN) llamado `OracleDDSI` con:
   - Servidor: `oracle0.ugr.es`  - Puerto: `1521`  - Service name: `practbd`
4. Estar en Eduroam o con la VPN de la Universidad activa.

## Ejecutar
```
python main.py
```
Usuario y contraseña se piden al arrancar. También se pueden definir con las
variables de entorno `DDSI_USER` y `DDSI_PASSWORD` (y `DDSI_DSN` si el DSN
tiene otro nombre). No escribas credenciales en el código.

## Alta de pedido (transacción)
Todo el alta es UNA transacción sobre la conexión principal:

| Paso | SQL |
|------|-----|
| Datos del pedido | `INSERT` en Pedido (aquí empieza la transacción) + `SAVEPOINT sp_detalles` |
| 1. Añadir detalle | comprueba stock (`SELECT ... FOR UPDATE`), `UPDATE` Stock, `INSERT` Detalle_Pedido |
| 2. Eliminar detalles | `ROLLBACK TO SAVEPOINT sp_detalles` (el pedido sigue abierto) |
| 3. Cancelar pedido | `ROLLBACK` |
| 4. Finalizar pedido | `COMMIT` |
| 5. Comparar sesiones | muestra las tablas desde la conexión principal y desde otra |

Tras las opciones 1, 2 y 3 se muestra el contenido de la BD, como pide el
enunciado. La opción 5 del menú del pedido es un añadido para ver, en medio
de la transacción, cómo los cambios sin confirmar solo los ve la sesión que
los hace.

## Estructura
- `main.py`      menús y flujo del programa
- `pedidos.py`   lógica de la transacción del pedido (SAVEPOINT, ROLLBACK, COMMIT)
- `esquema.py`   tablas del enunciado y reinicio de la BD
- `visual.py`    impresión de las tablas y comparación de sesiones
- `bd.py`        conexiones ODBC (sin autocommit) y cierre
- `entrada.py`   lectura segura de números y fechas
- `config.py`    lectura de credenciales
