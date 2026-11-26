
window.verBD = async function () {
  try {
    // Consultar la transacción activa desde el Worker
    const respuesta = await fetch("api/pedidos.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      cache: "no-store",
      body: JSON.stringify({ accion: "estado" })
    });

    const estado = await respuesta.json();

    if (!respuesta.ok || !estado.ok) {
      throw new Error(estado.error || "Error al consultar Oracle");
    }

    // Si hay una transacción, consultar desde su misma conexión.
    // Si no, consultar las tablas normalmente.
    let tablas;

    if (estado.activo) {
      tablas = estado.tablas;
      console.log("🟡 TRANSACCIÓN ABIERTA - Sin COMMIT");
      console.log("Pedido:", estado.pedido?.Cpedido);
      console.log("SAVEPOINTS:", estado.savepoints);
    } else {
      const consulta = await fetch("api/tablas.php", {
        cache: "no-store"
      });

      tablas = await consulta.json();

      if (!consulta.ok || tablas.error) {
        throw new Error(tablas.error || "Error al leer tablas");
      }

      console.log("🟢 SIN TRANSACCIÓN ACTIVA");
    }

    console.log("========== ESTADO REAL DE ORACLE ==========");

    for (const [nombre, tabla] of Object.entries(tablas)) {
      console.group(`📋 ${nombre}`);

      const registros = tabla.filas.map(fila =>
        Object.fromEntries(
          tabla.columnas.map((columna, i) => [columna, fila[i]])
        )
      );

      console.table(registros);
      console.groupEnd();
    }

    console.log("Consulta realizada:", new Date().toLocaleTimeString());

  } catch (error) {
    console.error("❌", error.message);
  }
};

verBD();
