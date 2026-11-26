"""Datos de conexión a Oracle.

Las credenciales NO se escriben en el código: se leen de variables de
entorno o se piden por teclado. Así no se cuelan en la entrega (.zip).
"""
import getpass
import os


def obtener_credenciales():
    """Devuelve (dsn, usuario, contraseña).

    DDSI_DSN       nombre del origen de datos ODBC (por defecto: OracleDDSI)
    DDSI_USER      usuario de Oracle
    DDSI_PASSWORD  contraseña de Oracle
    """
    dsn = os.environ.get("DDSI_DSN", "OracleDDSI")
    usuario = os.environ.get("DDSI_USER") or input("Usuario Oracle: ").strip()
    password = os.environ.get("DDSI_PASSWORD") or getpass.getpass("Contraseña: ")
    return dsn, usuario, password
