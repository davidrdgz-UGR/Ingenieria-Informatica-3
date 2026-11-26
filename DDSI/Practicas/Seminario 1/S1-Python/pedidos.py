"""Alta de pedidos con control de transacciones.

Una transacción de pedido tiene esta forma:

    INSERT en Pedido            <- aquí COMIENZA la transacción
    SAVEPOINT sp_detalles       <- punto al que volver para quitar detalles
    ... añadir detalles (UPDATE Stock + INSERT Detalle_Pedido) ...
    ROLLBACK TO sp_detalles     <- "eliminar todos los detalles" (el pedido sigue)
    ROLLBACK                    <- "cancelar pedido" (se deshace TODO)
    COMMIT                      <- "finalizar pedido" (todo permanente)

Aquí solo hay lógica de base de datos; las preguntas al usuario están en main.py.
"""

SAVEPOINT = "sp_detalles"


class ErrorPedido(Exception):
    """Error de negocio (stock insuficiente, producto repetido...).

    NO deshace la transacción: el usuario puede corregirlo y seguir.
    """


def codigo_sugerido(conexion):
    """Siguiente código de pedido libre (máximo + 1)."""
    cursor = conexion.cursor()
    try:
        cursor.execute("SELECT NVL(MAX(Cpedido), 0) + 1 FROM Pedido")
        return int(cursor.fetchone()[0])
    finally:
        cursor.close()


def iniciar_pedido(conexion, cpedido, ccliente, fecha):
    """Inserta el pedido y deja puesto el SAVEPOINT.

    Con este INSERT empieza la transacción (la conexión no tiene autocommit).
    """
    cursor = conexion.cursor()
    try:
        cursor.execute("SELECT COUNT(*) FROM Pedido WHERE Cpedido = ?", (cpedido,))
        if cursor.fetchone()[0] > 0:
            raise ErrorPedido(f"Ya existe el pedido {cpedido}.")

        cursor.execute(
            "INSERT INTO Pedido (Cpedido, Ccliente, Fecha_pedido) "
            "VALUES (?, ?, TO_DATE(?, 'YYYY-MM-DD'))",
            (cpedido, ccliente, fecha.isoformat()),
        )
        cursor.execute(f"SAVEPOINT {SAVEPOINT}")
    finally:
        cursor.close()


def anadir_detalle(conexion, cpedido, cproducto, cantidad):
    """Añade un producto al pedido si hay stock y descuenta el stock.

    Devuelve el stock que queda del producto.
    Lanza ErrorPedido si el producto no existe, ya está en el pedido o no
    hay unidades suficientes (en ese caso no se modifica nada).
    """
    cursor = conexion.cursor()
    try:
        # FOR UPDATE bloquea la fila: nadie más puede gastar ese stock
        # mientras decidimos.
        cursor.execute(
            "SELECT Cantidad FROM Stock WHERE Cproducto = ? FOR UPDATE",
            (cproducto,),
        )
        fila = cursor.fetchone()
        if fila is None:
            raise ErrorPedido(f"El producto {cproducto} no existe.")

        stock = int(fila[0])
        if cantidad > stock:
            raise ErrorPedido(
                f"Stock insuficiente: del producto {cproducto} "
                f"quedan {stock} unidades y has pedido {cantidad}."
            )

        cursor.execute(
            "SELECT COUNT(*) FROM Detalle_Pedido WHERE Cpedido = ? AND Cproducto = ?",
            (cpedido, cproducto),
        )
        if cursor.fetchone()[0] > 0:
            raise ErrorPedido(
                f"El producto {cproducto} ya está en el pedido. "
                "Elimina los detalles para volver a elegirlo."
            )

        cursor.execute(
            "UPDATE Stock SET Cantidad = Cantidad - ? WHERE Cproducto = ?",
            (cantidad, cproducto),
        )
        cursor.execute(
            "INSERT INTO Detalle_Pedido (Cpedido, Cproducto, Cantidad) "
            "VALUES (?, ?, ?)",
            (cpedido, cproducto, cantidad),
        )
        return stock - cantidad
    finally:
        cursor.close()


def eliminar_detalles(conexion, cpedido):
    """Quita todos los detalles del pedido y devuelve el stock.

    Se hace con ROLLBACK TO SAVEPOINT: deshace los INSERT de detalle y los
    UPDATE de stock, pero el INSERT del pedido (anterior al savepoint) se
    conserva y la transacción sigue activa. Devuelve cuántos detalles había.
    """
    cursor = conexion.cursor()
    try:
        cursor.execute(
            "SELECT COUNT(*) FROM Detalle_Pedido WHERE Cpedido = ?", (cpedido,)
        )
        cantidad = int(cursor.fetchone()[0])
        cursor.execute(f"ROLLBACK TO SAVEPOINT {SAVEPOINT}")
        return cantidad
    finally:
        cursor.close()


def cancelar_pedido(conexion):
    """ROLLBACK: deshace el pedido, sus detalles y los descuentos de stock."""
    conexion.rollback()


def finalizar_pedido(conexion):
    """COMMIT: hace permanentes todos los cambios."""
    conexion.commit()
