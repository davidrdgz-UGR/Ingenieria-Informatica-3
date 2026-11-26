"""Lectura segura de datos por teclado."""
from datetime import date, datetime


def pedir_entero(mensaje, minimo=1, defecto=None):
    """Pide un número entero >= minimo. Con Enter se usa 'defecto' (si hay)."""
    sufijo = f" [{defecto}]" if defecto is not None else ""
    while True:
        texto = input(f"{mensaje}{sufijo}: ").strip()
        if texto == "" and defecto is not None:
            return defecto
        try:
            numero = int(texto)
        except ValueError:
            print("  Introduce un número entero.")
            continue
        if numero < minimo:
            print(f"  El valor debe ser como mínimo {minimo}.")
            continue
        return numero


def pedir_fecha(mensaje, defecto=None):
    """Pide una fecha AAAA-MM-DD. Con Enter se usa 'defecto' (si hay)."""
    sufijo = f" [{defecto.isoformat()}]" if defecto is not None else ""
    while True:
        texto = input(f"{mensaje}{sufijo}: ").strip()
        if texto == "" and defecto is not None:
            return defecto
        try:
            return datetime.strptime(texto, "%Y-%m-%d").date()
        except ValueError:
            print("  Formato no válido. Usa AAAA-MM-DD (por ejemplo 2026-11-26).")
