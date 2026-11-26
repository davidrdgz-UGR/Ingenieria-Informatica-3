"""Seminario 1 - DDSI: acceso a bases de datos desde Python (ODBC).

Ejecutar:  python main.py
"""
from datetime import date

import bd
import config
import entrada
import esquema
import pedidos
import visual


# ---------------------------------------------------------------- menús
def mostrar_menu():
    print("\n==============================")
    print("   SEMINARIO 1 - DDSI")
    print("==============================")
    print("1. Borrar y crear de nuevo las tablas (10 productos en Stock)")
    print("2. Dar de alta nuevo pedido")
    print("3. Mostrar el contenido de las tablas")
    print("4. Salir")
    return input("Elige una opción: ").strip()


def mostrar_menu_pedido(cpedido):
    print(f"\n--- Pedido {cpedido} (transacción abierta, sin confirmar) ---")
    print("1. Añadir detalle de producto")
    print("2. Eliminar todos los detalles de producto")
    print("3. Cancelar pedido")
    print("4. Finalizar pedido")
    print("5. Comparar: mi sesión / otra sesión")
    return input("Elige una opción: ").strip()


# ------------------------------------------------------------- opciones
def opcion_reiniciar(sesiones):
    confirmar = input("Se borrarán todos los datos. ¿Continuar? (s/N): ")
    if confirmar.strip().lower() != "s":
        print("Operación cancelada.")
        return
    esquema.reiniciar_bd(sesiones.principal)
    print("Tablas creadas y 10 productos insertados.")
    visual.mostrar_contenido(sesiones.principal)


def opcion_alta_pedido(sesiones):
    """Alta de un pedido: toda la operación es UNA transacción."""
    conexion = sesiones.principal

    # 1. Datos básicos del pedido
    print("\n--- Nuevo pedido ---")
    cpedido = entrada.pedir_entero(
        "Código del pedido", defecto=pedidos.codigo_sugerido(conexion)
    )
    ccliente = entrada.pedir_entero("Código del cliente")
    fecha = entrada.pedir_fecha("Fecha del pedido (AAAA-MM-DD)", defecto=date.today())

    try:
        pedidos.iniciar_pedido(conexion, cpedido, ccliente, fecha)
    except pedidos.ErrorPedido as error:
        print(f"\n{error}")
        return

    print("\n>> Transacción iniciada: pedido insertado y SAVEPOINT creado.")
    print(">> Todavía NO es permanente: solo lo ve esta sesión.")
    visual.mostrar_contenido(conexion, "MI SESIÓN")

    # 2. Menú del pedido: se repite hasta cancelar o finalizar
    while True:
        opcion = mostrar_menu_pedido(cpedido)

        try:
            if opcion == "1":
                cproducto = entrada.pedir_entero("Código del producto")
                cantidad = entrada.pedir_entero("Cantidad")
                quedan = pedidos.anadir_detalle(conexion, cpedido, cproducto, cantidad)
                print(f"\nDetalle añadido. Quedan {quedan} unidades del producto {cproducto}.")
                visual.mostrar_contenido(conexion, "MI SESIÓN")

            elif opcion == "2":
                borrados = pedidos.eliminar_detalles(conexion, cpedido)
                print(
                    f"\nSe eliminaron {borrados} detalle(s) y se restauró el stock "
                    "(ROLLBACK TO SAVEPOINT). El pedido sigue abierto."
                )
                visual.mostrar_contenido(conexion, "MI SESIÓN")

            elif opcion == "3":
                pedidos.cancelar_pedido(conexion)
                print("\nPedido cancelado (ROLLBACK): no se ha guardado nada.")
                visual.mostrar_contenido(conexion, "MI SESIÓN")
                return

            elif opcion == "4":
                pedidos.finalizar_pedido(conexion)
                print("\nPedido finalizado (COMMIT): los cambios ya son permanentes.")
                return

            elif opcion == "5":
                visual.mostrar_comparacion(sesiones)

            else:
                print("Opción no válida.")

        except pedidos.ErrorPedido as error:
            # Error "normal" (p. ej. falta de stock): la transacción sigue viva
            print(f"\n{error}")
            visual.mostrar_contenido(conexion, "MI SESIÓN")

        except Exception as error:
            # Error inesperado de la BD: se deshace todo el pedido
            conexion.rollback()
            print(f"\nERROR: {error}\nSe ha deshecho el pedido (ROLLBACK).")
            return


# ----------------------------------------------------------- programa
def abrir_sesiones():
    dsn, usuario, password = config.obtener_credenciales()

    try:
        principal = bd.conectar(dsn, usuario, password)
    except Exception as error:
        print(f"\nNo se pudo conectar con la base de datos:\n  {error}")
        print("Comprueba la VPN/Eduroam, el DSN ODBC y el usuario/contraseña.")
        return None

    # Segunda conexión, solo para comparar lo que ven "los demás"
    try:
        observador = bd.conectar(dsn, usuario, password)
    except Exception:
        observador = None
        print("(Aviso: no se pudo abrir la segunda conexión; "
              "la comparación de sesiones no estará disponible.)")

    return bd.Sesiones(principal, observador)


def main():
    sesiones = abrir_sesiones()
    if sesiones is None:
        return

    print("\nConexión establecida.")

    try:
        while True:
            opcion = mostrar_menu()
            try:
                if opcion == "1":
                    opcion_reiniciar(sesiones)
                elif opcion == "2":
                    opcion_alta_pedido(sesiones)
                elif opcion == "3":
                    visual.mostrar_contenido(sesiones.principal)
                elif opcion == "4":
                    break
                else:
                    print("Opción no válida.")
            except Exception as error:
                # Un error nunca debe dejar una transacción a medias
                sesiones.principal.rollback()
                print(f"\nERROR: {error}")
    finally:
        sesiones.cerrar()
        print("\nConexión cerrada. Hasta luego.")


if __name__ == "__main__":
    main()
