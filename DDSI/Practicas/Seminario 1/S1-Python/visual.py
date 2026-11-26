"""Mostrar el contenido de las tablas por pantalla."""
from datetime import date, datetime
from decimal import Decimal

CONSULTAS = [
    ("STOCK", "SELECT Cproducto, Cantidad FROM Stock ORDER BY Cproducto"),
    ("PEDIDO", "SELECT Cpedido, Ccliente, Fecha_pedido FROM Pedido ORDER BY Cpedido"),
    ("DETALLE_PEDIDO",
     "SELECT Cpedido, Cproducto, Cantidad FROM Detalle_Pedido "
     "ORDER BY Cpedido, Cproducto"),
]


def _texto(valor):
    """Convierte un valor de la BD en texto legible."""
    if valor is None:
        return "NULL"
    if isinstance(valor, (datetime, date)):
        return valor.strftime("%Y-%m-%d")
    if isinstance(valor, Decimal) and valor == valor.to_integral_value():
        return str(int(valor))
    if isinstance(valor, float) and valor.is_integer():
        return str(int(valor))
    return str(valor)


def imprimir_tabla(titulo, columnas, filas):
    """Dibuja una tabla con bordes en la terminal."""
    textos = [[_texto(v) for v in fila] for fila in filas]
    anchos = [
        max([len(col)] + [len(fila[i]) for fila in textos])
        for i, col in enumerate(columnas)
    ]
    separador = "+" + "+".join("-" * (a + 2) for a in anchos) + "+"

    def linea(celdas):
        return "| " + " | ".join(c.ljust(a) for c, a in zip(celdas, anchos)) + " |"

    print(f"\n{titulo} ({len(textos)} filas)")
    print(separador)
    print(linea([c.upper() for c in columnas]))
    print(separador)
    if textos:
        for fila in textos:
            print(linea(fila))
    else:
        print("| " + "(vacía)".ljust(sum(anchos) + 3 * (len(anchos) - 1)) + " |")
    print(separador)


def mostrar_contenido(conexion, titulo_extra=""):
    """Muestra las tres tablas tal como las ve ESA conexión.

    En la conexión principal, mientras hay una transacción abierta, se ven
    los cambios aún sin confirmar (Oracle siempre enseña a cada sesión sus
    propios cambios).
    """
    cursor = conexion.cursor()
    try:
        if titulo_extra:
            print(f"\n=== {titulo_extra} ===")
        for nombre, sql in CONSULTAS:
            cursor.execute(sql)
            columnas = [d[0] for d in cursor.description]
            imprimir_tabla(nombre, columnas, cursor.fetchall())
    finally:
        cursor.close()


def mostrar_comparacion(sesiones):
    """Muestra las tablas desde la sesión principal y desde la otra sesión.

    Mientras hay una transacción sin confirmar, las dos vistas son distintas:
    la principal ve los cambios y la otra solo ve lo que ya está en COMMIT.
    """
    mostrar_contenido(
        sesiones.principal, "MI SESIÓN (incluye los cambios sin confirmar)"
    )

    if sesiones.observador is None:
        print("\n(No hay segunda conexión disponible para comparar.)")
        return

    mostrar_contenido(
        sesiones.observador,
        "OTRA SESIÓN (lo que ven los demás: solo lo confirmado con COMMIT)",
    )
    sesiones.observador.rollback()  # cierra su transacción de lectura
