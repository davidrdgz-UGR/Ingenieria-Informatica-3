"""Tablas del enunciado y su (re)creación.

Stock          (Cproducto, Cantidad)
Pedido         (Cpedido, Ccliente, Fecha_pedido)
Detalle_Pedido (Cpedido, Cproducto, Cantidad)
Todos los dominios son numéricos excepto Fecha_pedido.
"""

# (nombre en Oracle, sentencia de creación). Orden: de independiente a dependiente.
TABLAS = [
    ("STOCK", """
        CREATE TABLE Stock (
            Cproducto NUMBER PRIMARY KEY,
            Cantidad  NUMBER NOT NULL CHECK (Cantidad >= 0)
        )"""),
    ("PEDIDO", """
        CREATE TABLE Pedido (
            Cpedido      NUMBER PRIMARY KEY,
            Ccliente     NUMBER NOT NULL,
            Fecha_pedido DATE   NOT NULL
        )"""),
    ("DETALLE_PEDIDO", """
        CREATE TABLE Detalle_Pedido (
            Cpedido   NUMBER NOT NULL REFERENCES Pedido(Cpedido),
            Cproducto NUMBER NOT NULL REFERENCES Stock(Cproducto),
            Cantidad  NUMBER NOT NULL CHECK (Cantidad > 0),
            PRIMARY KEY (Cpedido, Cproducto)
        )"""),
]

# Las 10 tuplas predefinidas de Stock: (Cproducto, Cantidad)
STOCK_INICIAL = [
    (1, 50), (2, 40), (3, 30), (4, 25), (5, 60),
    (6, 15), (7, 80), (8, 10), (9, 45), (10, 20),
]


def existe_tabla(cursor, nombre):
    """True si la tabla existe en el esquema del usuario (Oracle)."""
    cursor.execute(
        "SELECT COUNT(*) FROM user_tables WHERE table_name = ?", (nombre,)
    )
    return cursor.fetchone()[0] > 0


def reiniciar_bd(conexion):
    """Borra las tablas, las vuelve a crear e inserta los 10 productos.

    OJO: en Oracle las sentencias DDL (DROP, CREATE) confirman
    automáticamente cualquier transacción pendiente.
    """
    cursor = conexion.cursor()
    try:
        # 1. Borrar (al revés: primero las que dependen de otras)
        for nombre, _ in reversed(TABLAS):
            if existe_tabla(cursor, nombre):
                cursor.execute(f"DROP TABLE {nombre}")

        # 2. Crear
        for _, sentencia in TABLAS:
            cursor.execute(sentencia)

        # 3. Insertar los 10 productos iniciales
        cursor.executemany(
            "INSERT INTO Stock (Cproducto, Cantidad) VALUES (?, ?)",
            STOCK_INICIAL,
        )
        conexion.commit()
    except Exception:
        conexion.rollback()
        raise
    finally:
        cursor.close()
