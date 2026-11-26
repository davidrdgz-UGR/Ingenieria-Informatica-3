"""Conexión con la base de datos mediante ODBC (pyodbc)."""


def conectar(dsn, usuario, password):
    """Abre una conexión SIN autocommit.

    Con autocommit=False cada sentencia DML (INSERT, UPDATE, DELETE) abre
    una transacción que NO es permanente hasta que llamemos a commit().
    Es lo que nos permite usar commit(), rollback() y SAVEPOINT.

    La conexión se mantiene abierta mientras dura el programa, por eso una
    transacción puede abarcar varias pantallas del menú (en una web, cada
    petición abre su propia conexión y eso no sería posible).
    """
    import pyodbc  # se importa aquí para dar un mensaje claro si falta

    cadena = f"DSN={dsn};UID={usuario};PWD={password}"
    return pyodbc.connect(cadena, autocommit=False)


def cerrar(conexion):
    """Cierra la conexión deshaciendo lo que haya quedado sin confirmar.

    Importante en el servidor de la ETSIIT: hay un máximo de 3 o 4
    conexiones simultáneas por usuario, así que no hay que dejarlas colgadas.
    """
    try:
        conexion.rollback()
    finally:
        conexion.close()


class Sesiones:
    """Las dos conexiones del programa.

    principal   donde trabajamos (y donde viven las transacciones).
    observador  una segunda conexión que solo LEE. Muestra lo que verían
                otros usuarios: Oracle no les enseña los cambios hasta COMMIT.
                Puede ser None si no se pudo abrir.
    """

    def __init__(self, principal, observador=None):
        self.principal = principal
        self.observador = observador

    def cerrar(self):
        try:
            if self.observador is not None:
                cerrar(self.observador)
        finally:
            cerrar(self.principal)
