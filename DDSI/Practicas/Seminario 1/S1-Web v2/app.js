
/* ------------------------------------------------------------------
   TABLAS FIJAS DEL ENUNCIADO
   La estructura (columnas) no cambia. Las filas son de ejemplo y se
   sustituirán por los datos reales de la BBDD en cargarTablas().
------------------------------------------------------------------- */
let tablas = {
  /* Stock: {
    columnas: ["Cproducto", "Nombre", "Cantidad"],
    filas: [
      [1, "Teclado", 50],
      [2, "Ratón", 40],
      [3, "Monitor", 30],
      [4, "Portátil", 25],
      [5, "Auriculares", 60],
      [6, "Webcam", 15],
      [7, "Cable HDMI", 80],
      [8, "Impresora", 10],
      [9, "Altavoces", 45],
      [10, "Micrófono", 20],
    ],
  },
  Pedido: {
    columnas: ["Cpedido", "Ccliente", "Fecha_pedido"],
    filas: [],
  },
  Detalle_Pedido: {
    columnas: ["Cpedido", "Cproducto", "Cantidad"],
    filas: [],
  },
  Clientes: {
    columnas: ["ID_Cliente", "Nombre"],
    filas: [
      [1, "Ana"],
      [2, "Luis"],
      [3, "Marta"],
    ],
  }, */
};

/* ------------------------------------------------------------------
   FORMULARIOS DE ALTA (uno por tabla)
   campos: en el mismo orden que las columnas de la tabla.
   validar: devuelve un texto de error, o null si todo está bien.
   Tipos: number, text (maxlength), date y select (opciones sacadas de
   otra tabla; numero:true si el valor debe guardarse como número).
   valorInicial: valor por defecto al abrir el formulario.
   max / pista: límite y texto de ayuda que dependen de otros campos.
   Las tablas que no aparecen aquí no muestran el botón "Añadir fila".
------------------------------------------------------------------- */
const formularios = {
  Stock: {
    campos: [
      { nombre: "Cproducto", tipo: "number", min: 1, requerido: true },
      { nombre: "Nombre", tipo: "text", maxlength: 50, requerido: true },
      { nombre: "Cantidad", tipo: "number", min: 0, requerido: true },
    ],
    validar(valores) {
      const existe = tablas.Stock.filas.some(
        (fila) => Number(fila[0]) === Number(valores.Cproducto)
      );
      if (existe) return "Ya existe un producto con ese Cproducto.";
      if (!valores.Nombre.trim()) return "El nombre no puede estar vacío.";
      return null;
    },
  },

  Clientes: {
    campos: [
      { nombre: "ID_Cliente", tipo: "number", min: 1, requerido: true,
        valorInicial: () => siguienteCodigo("Clientes") },
      { nombre: "Nombre", tipo: "text", maxlength: 50, requerido: true },
    ],
    validar(valores) {
      const existe = tablas.Clientes.filas.some(
        (fila) => Number(fila[0]) === Number(valores.ID_Cliente)
      );
      if (existe) return "Ya existe un cliente con ese ID_Cliente.";
      if (!valores.Nombre.trim()) return "El nombre no puede estar vacío.";
      return null;
    },
  },

  Pedido: {
    campos: [
      { nombre: "Cpedido", tipo: "number", min: 1, requerido: true,
        valorInicial: () => siguienteCodigo("Pedido") },

      // Relación: Ccliente se elige entre los clientes que existen
      { nombre: "Ccliente", tipo: "select", numero: true, requerido: true,
        placeholder: "Selecciona un cliente",
        textoVacio: "No hay clientes registrados",
        opciones: () =>
          tablas.Clientes.filas.map(([id, nombre]) => ({
            valor: id,
            texto: `${id} - ${nombre}`,
          })) },

      { nombre: "Fecha_pedido", tipo: "date", requerido: true,
        valorInicial: hoy },
    ],
    validar(valores) {
      const existe = tablas.Pedido.filas.some(
        (fila) => Number(fila[0]) === Number(valores.Cpedido)
      );
      if (existe) return "Ya existe un pedido con ese Cpedido.";

      const clienteOk = tablas.Clientes.filas.some(
        (fila) => Number(fila[0]) === Number(valores.Ccliente)
      );
      if (!clienteOk) return "El cliente seleccionado no existe.";
      return null;
    },
  },

  Detalle_Pedido: {
    campos: [
      // Relación: el pedido al que pertenece el detalle
      { nombre: "Cpedido", tipo: "select", numero: true, requerido: true,
        placeholder: "Selecciona un pedido",
        textoVacio: "No hay pedidos",
        opciones: () =>
          tablas.Pedido.filas.map(([cpedido, ccliente]) => {
            const cliente = tablas.Clientes.filas.find(
              (fila) => Number(fila[0]) === Number(ccliente)
            );
            return {
              valor: cpedido,
              texto: cliente
                ? `Pedido ${cpedido} (${cliente[1]})`
                : `Pedido ${cpedido} (cliente ${ccliente})`,
            };
          }) },

      // Relación: solo productos con stock, mostrando lo disponible
      { nombre: "Cproducto", tipo: "select", numero: true, requerido: true,
        placeholder: "Selecciona un producto",
        textoVacio: "No hay productos con stock",
        opciones: () =>
          tablas.Stock.filas
            .filter((fila) => Number(fila[2]) > 0)
            .map((fila) => ({
              valor: fila[0],
              texto: `${fila[0]} - ${fila[1]} (${fila[2]} disp.)`,
            })) },

      // La cantidad no puede superar el stock del producto elegido
      { nombre: "Cantidad", tipo: "number", min: 1, requerido: true,
        max: (valores) => (valores.Cproducto ? stockDe(valores.Cproducto) : null),
        pista: (valores) =>
          valores.Cproducto ? `Disponible: ${stockDe(valores.Cproducto)}` : "" },
    ],
    validar(valores) {
      const pedidoOk = tablas.Pedido.filas.some(
        (fila) => Number(fila[0]) === Number(valores.Cpedido)
      ) || (
        pedidoPendiente !== null &&
        Number(pedidoPendiente.Cpedido) === Number(valores.Cpedido)
      );
      if (!pedidoOk) return "El pedido seleccionado no existe.";

      if (!filaStock(valores.Cproducto)) {
        return "El producto seleccionado no existe.";
      }

      const repetido = tablas.Detalle_Pedido.filas.some(
        (fila) =>
          Number(fila[0]) === Number(valores.Cpedido) &&
          Number(fila[1]) === Number(valores.Cproducto)
      );

      if (repetido) {
        return "Ese producto ya está en el pedido.";
      }

      const cantidad = Number(valores.Cantidad);
      const disponible = stockDe(valores.Cproducto);

      if (cantidad > disponible) {
        return `No hay stock suficiente: quedan ${disponible} unidades.`;
      }
      return null;
    },

  },
};

/* Siguiente código libre de una tabla (máximo de la 1ª columna + 1) */
function siguienteCodigo(nombreTabla) {
  const codigos = tablas[nombreTabla].filas.map((fila) => Number(fila[0]));
  return codigos.length ? Math.max(...codigos) + 1 : 1;
}

/* Fecha de hoy en formato YYYY-MM-DD (hora local) */
function hoy() {
  return new Date().toLocaleDateString("sv-SE");
}

/* Fila de Stock de un producto (o undefined si no existe) */
function filaStock(cproducto) {
  return tablas.Stock.filas.find((fila) => Number(fila[0]) === Number(cproducto));
}

/* Unidades disponibles de un producto */
function stockDe(cproducto) {
  const fila = filaStock(cproducto);
  return fila ? Number(fila[2]) : 0;
}

/* ------------------------------------------------------------------
   RELACIONES VISUALES
   Columnas que guardan un código pero se muestran con el nombre entre
   paréntesis, p. ej. "1 (Ana)".
------------------------------------------------------------------- */
function conNombre(nombreTabla, codigo) {
  const fila = tablas[nombreTabla].filas.find(
    (f) => Number(f[0]) === Number(codigo)
  );
  return fila ? `${codigo} (${fila[1]})` : codigo;
}

const relaciones = {
  Pedido: {
    Ccliente: (codigo) => conNombre("Clientes", codigo),
  },
  Detalle_Pedido: {
    Cproducto: (codigo) => conNombre("Stock", codigo),

    Cpedido: (codigo) => {
      const pedido = tablas.Pedido.filas.find(
        (f) => Number(f[0]) === Number(codigo)
      );
      const cliente =
        pedido &&
        tablas.Clientes.filas.find((f) => Number(f[0]) === Number(pedido[1]));
      return cliente ? `${codigo} (${cliente[1]})` : codigo;
    },
  },
};

let tablaActual = null;

/* Pedido cuya transacción Oracle sigue abierta en el proceso persistente. */
let pedidoPendiente = null;
let modoEdicion = false;

/* ---------- Referencias al DOM ---------- */
const selectorTabla = document.getElementById("selector-tabla");
const titulo = document.getElementById("titulo-tabla");
const tabla = document.getElementById("tabla");
const thead = tabla.querySelector("thead");
const tbody = tabla.querySelector("tbody");
const btnReiniciar = document.getElementById("btn-reiniciar");

/* const btnEditar = document.getElementById("btn-editar"); */
const btnGuardar = document.getElementById("btn-guardar");
const mensaje = document.getElementById("mensaje");
const tarjeta = document.querySelector(".tarjeta");

/* Formulario */
const btnAnadir = document.getElementById("btn-anadir");
const formulario = document.getElementById("formulario");
const formularioCampos = document.getElementById("formulario-campos");
const btnCancelar = document.getElementById("btn-cancelar");

/* ---------- Controles de transacción ----------
   Se añaden usando los estilos ya existentes, sin tocar HTML ni CSS.
   Los formularios de Pedido y Detalle_Pedido NO se modifican.
------------------------------------------------------------------- */

const panelPedido = document.createElement("div");
panelPedido.className = "formulario";
panelPedido.hidden = true;

const resumenPedido = document.createElement("p");
const accionesPedido = document.createElement("div");
accionesPedido.className = "formulario-botones";

function crearBotonPedido(texto, clase, accion) {
  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = `btn ${clase}`.trim();
  boton.textContent = texto;
  boton.addEventListener("click", accion);
  return boton;
}

const btnDeshacerUltimo = crearBotonPedido(
  "Deshacer último producto", "", deshacerUltimoProducto
);

const btnLimpiarDetalles = crearBotonPedido(
  "Limpiar detalles", "", limpiarDetalles
);

const btnCancelarPedido = crearBotonPedido(
  "Cancelar pedido", "btn-peligro", () => procesarPedido("cancelar")
);

const btnFinalizarPedido = crearBotonPedido(
  "Finalizar pedido", "btn-principal", () => procesarPedido("finalizar")
);

accionesPedido.append(
  btnDeshacerUltimo,
  btnLimpiarDetalles,
  btnCancelarPedido,
  btnFinalizarPedido
);

panelPedido.append(resumenPedido, accionesPedido);
tarjeta.querySelector(".acciones-tabla").after(panelPedido);

function cantidadDetallesPedido() {
  if (!pedidoPendiente || !tablas.Detalle_Pedido) return 0;
  return tablas.Detalle_Pedido.filas.filter(
    (fila) => Number(fila[0]) === Number(pedidoPendiente.Cpedido)
  ).length;
}

function actualizarPanelPedido() {
  const activo = pedidoPendiente !== null;

  panelPedido.hidden =
    !activo || !["Pedido", "Detalle_Pedido"].includes(tablaActual);

  if (activo) {
    const cliente = tablas.Clientes?.filas.find(
      (fila) => Number(fila[0]) === Number(pedidoPendiente.Ccliente)
    );

    const cantidadProductos = cantidadDetallesPedido();

    resumenPedido.textContent =
      `Pedido ${pedidoPendiente.Cpedido} de ` +
      `${cliente ? cliente[1] : pedidoPendiente.Ccliente} ` +
      `— ${cantidadProductos} producto(s) en Oracle. ` +
      `Transacción abierta, pendiente de COMMIT.`;
  }

  btnDeshacerUltimo.disabled =
    !activo || cantidadDetallesPedido() === 0 || operacionPendiente;

  btnLimpiarDetalles.disabled =
    !activo || cantidadDetallesPedido() === 0 || operacionPendiente;

  btnCancelarPedido.disabled = !activo || operacionPendiente;
  btnFinalizarPedido.disabled = !activo || operacionPendiente;

  btnAnadir.disabled =
    operacionPendiente || (activo && tablaActual !== "Detalle_Pedido");
}

function aplicarEstadoPedido(resultado) {
  if (resultado.tablas) {
    tablas = resultado.tablas;
  }

  pedidoPendiente = resultado.activo
    ? resultado.pedido
    : null;

  actualizarPanelPedido();
}

async function llamarApiPedido(accion, datos = {}) {
  const respuesta = await fetch("api/pedidos.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    cache: "no-store",
    body: JSON.stringify({ accion, ...datos })
  });

  const texto = await respuesta.text();
  let resultado;

  try {
    resultado = JSON.parse(texto);
  } catch {
    throw new Error(
      `Respuesta no válida del servidor (HTTP ${respuesta.status}). ` +
      "Comprueba que el proceso de transacciones esté iniciado."
    );
  }

  if (!respuesta.ok || !resultado.ok) {
    throw new Error(resultado.error || `Error HTTP ${respuesta.status}`);
  }

  return resultado;
}

async function deshacerUltimoProducto() {
  if (!pedidoPendiente || operacionPendiente || cantidadDetallesPedido() === 0) return;

  operacionPendiente = true;
  actualizarPanelPedido();

  try {
    const resultado = await llamarApiPedido("deshacer");
    aplicarEstadoPedido(resultado);
    cerrarFormulario();
    pintarTabla();
    mostrarMensaje(resultado.mensaje);
  } catch (error) {
    mostrarMensaje(`No se pudo deshacer el producto: ${error.message}`);
  } finally {
    operacionPendiente = false;
    actualizarPanelPedido();
  }
}

async function limpiarDetalles() {
  if (!pedidoPendiente || operacionPendiente || cantidadDetallesPedido() === 0) return;

  operacionPendiente = true;
  actualizarPanelPedido();

  try {
    const resultado = await llamarApiPedido("limpiar");
    aplicarEstadoPedido(resultado);
    cerrarFormulario();
    pintarTabla();
    mostrarMensaje(resultado.mensaje);
  } catch (error) {
    mostrarMensaje(`No se pudieron limpiar los detalles: ${error.message}`);
  } finally {
    operacionPendiente = false;
    actualizarPanelPedido();
  }
}

async function procesarPedido(tipo) {
  if (!pedidoPendiente || operacionPendiente) return;

  operacionPendiente = true;
  actualizarPanelPedido();

  try {
    const accion = tipo === "finalizar" ? "confirmar" : "cancelar";
    const resultado = await llamarApiPedido(accion);

    aplicarEstadoPedido(resultado);
    cerrarFormulario();
    filaEditando = null;
    mostrarMensaje(resultado.mensaje);

    // Después del COMMIT/ROLLBACK la transacción termina.
    await cargarTablas();
    pintarTabla();

  } catch (error) {
    mostrarMensaje(`Error en la transacción: ${error.message}`);
  } finally {
    operacionPendiente = false;
    actualizarPanelPedido();
  }
}

/* ---------- Conexión con la BBDD ---------- */
async function cargarTablas() {
  // Si hay un pedido abierto, consultar SIEMPRE desde su misma conexión Oracle.
  if (pedidoPendiente) {
    const estado = await llamarApiPedido("estado");
    if (estado.activo) {
      aplicarEstadoPedido(estado);
      return tablas;
    }
    pedidoPendiente = null;
  }

  const respuesta = await fetch("api/tablas.php", { cache: "no-store" });

  if (!respuesta.ok) {
    throw new Error(`Error HTTP ${respuesta.status} al cargar las tablas`);
  }

  const datos = await respuesta.json();
  validarTablasRecibidas(datos);
  tablas = datos;
  return tablas;
}

function validarTablasRecibidas(datos) {
  for (const nombre of ["Stock", "Clientes", "Pedido", "Detalle_Pedido"]) {
    if (!datos[nombre] || !Array.isArray(datos[nombre].columnas) ||
        !Array.isArray(datos[nombre].filas)) {
      throw new Error(`La tabla ${nombre} no tiene el formato esperado.`);
    }
  }
}

async function restaurarPedidoActivo() {
  const estado = await llamarApiPedido("estado");
  if (estado.activo) {
    aplicarEstadoPedido(estado);
  } else {
    pedidoPendiente = null;
    await cargarTablas();
  }
}

/* ---------- Escritura de registros en Oracle ---------- */
async function enviarOperacion(
  accion,
  nombre,
  fila = null,
  clave = null
) {
  if (pedidoPendiente) {
    throw new Error(
      "Termina o cancela el pedido abierto antes de modificar otras tablas."
    );
  }

  const respuesta = await fetch("api/filas.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      accion,
      tabla: nombre,
      fila,
      clave
    })
  });

  const texto = await respuesta.text();
  let resultado;

  try {
    resultado = JSON.parse(texto);
  } catch {
    throw new Error(
      `El servidor no devolvió JSON válido ` +
      `(HTTP ${respuesta.status}).`
    );
  }

  if (!respuesta.ok || !resultado.ok) {
    throw new Error(
      resultado.error || `Error HTTP ${respuesta.status}`
    );
  }

  return resultado;
}

/* Las claves no se modifican: el detalle utiliza clave compuesta. */
function claveDe(nombre, fila) {
  return nombre === "Detalle_Pedido"
    ? [fila[0], fila[1]]
    : [fila[0]];
}

/* Campos que permitimos editar */
const columnasEditables = {
  Stock: [1, 2],
  Clientes: [1],
  Pedido: [1, 2],
  Detalle_Pedido: [2]
};

let filaEditando = null;
let operacionPendiente = false;

/* ---------- Botones con iconos ---------- */
function botonAccion(texto, clase, accion) {
  const iconos = {
    Editar: `
      <svg viewBox="0 0 24 24" width="18" height="18"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20h9"/>
        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
      </svg>`,

    Eliminar: `
      <svg viewBox="0 0 24 24" width="18" height="18"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 6h18"/>
        <path d="M8 6V4h8v2"/>
        <path d="M5 6l1 14h12l1-14"/>
        <path d="M10 10v7"/>
        <path d="M14 10v7"/>
      </svg>`,

    Guardar: `
      <svg viewBox="0 0 24 24" width="18" height="18"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="m5 12 4 4L19 6"/>
      </svg>`,

    Cancelar: `
      <svg viewBox="0 0 24 24" width="18" height="18"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18"/>
        <path d="m6 6 12 12"/>
      </svg>`
  };

  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = `btn btn-icono ${clase}`;
  boton.title = texto;
  boton.setAttribute("aria-label", texto);

  if (iconos[texto]) {
    boton.innerHTML = iconos[texto];
  } else {
    boton.textContent = texto;
  }

  boton.addEventListener("click", accion);
  return boton;
}

/* ---------- Pintar la tabla y sus botones ---------- */
function pintarTabla() {
  if (!tablaActual || !tablas[tablaActual]) return;

  const {
    columnas,
    filas: filasGuardadas
  } = tablas[tablaActual];

  // Las filas proceden de Oracle, incluida la transacción abierta.
  const filas = [...filasGuardadas];

  titulo.textContent = tablaActual;
  thead.replaceChildren();
  tbody.replaceChildren();

  const cabecera = document.createElement("tr");

  [...columnas, "Acciones"].forEach((col) => {
    const th = document.createElement("th");
    th.textContent = col;
    cabecera.appendChild(th);
  });

  thead.appendChild(cabecera);

  if (!filas.length) {
    const tr = document.createElement("tr");
    const td = document.createElement("td");

    td.colSpan = columnas.length + 1;
    td.className = "vacia";
    td.textContent = "Esta tabla no tiene filas.";

    tr.appendChild(td);
    tbody.appendChild(tr);
    return;
  }

  filas.forEach((fila, indice) => {
    const tr = document.createElement("tr");

    const filaDePedidoActivo = pedidoPendiente && (
      (tablaActual === "Pedido" && Number(fila[0]) === Number(pedidoPendiente.Cpedido)) ||
      (tablaActual === "Detalle_Pedido" && Number(fila[0]) === Number(pedidoPendiente.Cpedido))
    );
    const editarEsta = !filaDePedidoActivo && filaEditando === indice;
    const controles = {};

    fila.forEach((valor, i) => {
      const td = document.createElement("td");

      if (
        editarEsta &&
        columnasEditables[tablaActual].includes(i)
      ) {
        const campo = formularios[tablaActual].campos[i];

        const input = document.createElement(
          campo.tipo === "select" ? "select" : "input"
        );

        if (campo.tipo === "select") {
          campo.opciones().forEach(({ valor: id, texto }) => {
            const opt = document.createElement("option");
            opt.value = id;
            opt.textContent = texto;
            input.appendChild(opt);
          });
        } else {
          input.type = campo.tipo;

          if (campo.min !== undefined) {
            input.min = campo.min;
          }

          if (campo.maxlength !== undefined) {
            input.maxLength = campo.maxlength;
          }

          if (campo.tipo === "number") {
            input.step = "1";
          }
        }

        input.value = valor;
        input.required = true;
        input.className = "celda-edicion";

        td.appendChild(input);
        controles[i] = input;

      } else {
        const formato = relaciones[tablaActual]?.[columnas[i]];

        td.textContent = formato
          ? formato(valor)
          : String(valor ?? "");
      }

      tr.appendChild(td);
    });

    const acciones = document.createElement("td");
    acciones.className = "acciones-fila";

    if (filaDePedidoActivo) {
      acciones.textContent = "En transacción";

    } else if (pedidoPendiente) {
      acciones.textContent = "Pedido en curso";

    } else if (editarEsta) {
      acciones.append(

        botonAccion(
          "Guardar",
          "btn-principal",
          () => guardarEdicion(indice, controles)
        ),

        botonAccion(
          "Cancelar",
          "",
          () => {
            filaEditando = null;
            pintarTabla();
          }
        ),

        botonAccion(
          "Eliminar",
          "btn-peligro",
          () => pedirEliminacion(indice)
        )
      );

    } else {
      acciones.append(
        botonAccion(
          "Editar",
          "",
          () => {
            filaEditando = indice;
            cerrarFormulario();
            pintarTabla();
          }
        )
      );
    }

    tr.appendChild(acciones);
    tbody.appendChild(tr);
  });
}

function mostrarMensaje(texto) {
  mensaje.textContent = texto;
}

/* ---------- Guardar edición ---------- */
async function guardarEdicion(indice, controles) {
  if (operacionPendiente) return;

  const nombre = tablaActual;
  const anterior = tablas[nombre].filas[indice];
  const nueva = [...anterior];

  for (const [i, control] of Object.entries(controles)) {
    if (!control.reportValidity()) return;

    const campo = formularios[nombre].campos[Number(i)];

    nueva[Number(i)] =
      campo.tipo === "number" || campo.numero
        ? Number(control.value)
        : control.value.trim();
  }

  operacionPendiente = true;

  try {
    await enviarOperacion(
      "editar",
      nombre,
      nueva,
      claveDe(nombre, anterior)
    );

    await cargarTablas();

    filaEditando = null;
    pintarTabla();
    mostrarMensaje("Registro actualizado correctamente.");

  } catch (error) {
    mostrarMensaje(`Error: ${error.message}`);

  } finally {
    operacionPendiente = false;
  }
}

/* ---------- Modal de confirmación de borrado ---------- */
const modalEliminar = document.createElement("dialog");
modalEliminar.className = "modal-reiniciar";

modalEliminar.innerHTML = `
  <h2>¿Eliminar registro?</h2>
  <p id="texto-eliminar">El registro se eliminará definitivamente.</p>
  <p class="modal-advertencia">Esta acción no se puede deshacer.</p>
  <div class="modal-botones">
    <button type="button" class="btn" id="cancelar-eliminar">
      Cancelar
    </button>
    <button type="button" class="btn btn-peligro" id="confirmar-eliminar">
      Sí, eliminar
    </button>
  </div>
`;

document.body.appendChild(modalEliminar);

let indiceEliminar = null;

modalEliminar
  .querySelector("#cancelar-eliminar")
  .addEventListener("click", () => {
    modalEliminar.close();
  });

modalEliminar.addEventListener("close", () => {
  indiceEliminar = null;
});

function pedirEliminacion(indice) {
  indiceEliminar = {
    nombre: tablaActual,
    fila: [...tablas[tablaActual].filas[indice]]
  };

  const id = claveDe(
    indiceEliminar.nombre,
    indiceEliminar.fila
  ).join(" / ");

  modalEliminar.querySelector("#texto-eliminar").textContent =
    `Se eliminará el registro ${id} de ${indiceEliminar.nombre}.` +
    (
      indiceEliminar.nombre === "Pedido"
        ? " También se borrarán sus detalles y se recuperará su stock."
        : ""
    );

  modalEliminar.showModal();
}

modalEliminar
  .querySelector("#confirmar-eliminar")
  .addEventListener("click", async (e) => {
    if (!indiceEliminar || operacionPendiente) return;

    operacionPendiente = true;

    const boton = e.currentTarget;
    boton.disabled = true;

    try {
      const { nombre, fila } = indiceEliminar;

      await enviarOperacion(
        "eliminar",
        nombre,
        null,
        claveDe(nombre, fila)
      );

      await cargarTablas();

      filaEditando = null;
      modalEliminar.close();
      pintarTabla();

      mostrarMensaje("Registro eliminado correctamente.");

    } catch (error) {
      modalEliminar.close();
      mostrarMensaje(`Error al eliminar: ${error.message}`);

    } finally {
      boton.disabled = false;
      operacionPendiente = false;
    }
  });

/* ---------- Formulario de alta ---------- */
function pintarFormulario() {
  const config = formularios[tablaActual];
  formularioCampos.innerHTML = "";

  const dinamicos = [];

  config.campos.forEach((campo) => {
    const div = document.createElement("div");
    div.className = "campo";

    const label = document.createElement("label");
    label.htmlFor = `campo-${campo.nombre}`;
    label.textContent = campo.nombre;

    let input;

    if (campo.tipo === "select") {
      input = document.createElement("select");
      const opciones = campo.opciones();

      const vacia = document.createElement("option");
      vacia.value = "";

      vacia.textContent = opciones.length
        ? campo.placeholder ?? "Selecciona una opción"
        : campo.textoVacio ?? "No hay opciones";

      vacia.disabled = true;
      vacia.selected = true;
      input.appendChild(vacia);

      opciones.forEach(({ valor, texto }) => {
        const opcion = document.createElement("option");
        opcion.value = valor;
        opcion.textContent = texto;
        input.appendChild(opcion);
      });

      if (
        tablaActual === "Detalle_Pedido" &&
        campo.nombre === "Cpedido" &&
        pedidoPendiente
      ) {
        input.value = String(pedidoPendiente.Cpedido);
      }

    } else {
      input = document.createElement("input");
      input.type = campo.tipo;

      if (campo.min !== undefined) {
        input.min = campo.min;
      }

      if (campo.tipo === "number") {
        input.step = 1;
      }

      if (campo.maxlength !== undefined) {
        input.maxLength = campo.maxlength;
      }

      if (campo.valorInicial) {
        input.value = campo.valorInicial();
      }
    }

    input.id = `campo-${campo.nombre}`;
    input.name = campo.nombre;
    input.required = !!campo.requerido;

    div.append(label, input);

    if (campo.max || campo.pista) {
      const pista = document.createElement("small");
      pista.className = "pista";
      div.appendChild(pista);

      dinamicos.push({
        campo,
        input,
        pista
      });
    }

    formularioCampos.appendChild(div);
  });

  // Campos con límite máximo o texto de ayuda dinámico.
  if (dinamicos.length) {
    const refrescar = () => {
      const valores = Object.fromEntries(new FormData(formulario));

      dinamicos.forEach(({ campo, input, pista }) => {
        if (campo.max) {
          const max = campo.max(valores);

          if (max === null) {
            input.removeAttribute("max");
          } else {
            input.max = max;
          }
        }

        pista.textContent =
          campo.pista ? campo.pista(valores) : "";
      });
    };

    formularioCampos
      .querySelectorAll("input, select")
      .forEach((el) => {
        el.addEventListener("input", refrescar);
        el.addEventListener("change", refrescar);
      });

    refrescar();
  }
}

function abrirFormulario() {
  pintarFormulario();
  formulario.hidden = false;
  formularioCampos.querySelector("input")?.focus();
}

function cerrarFormulario() {
  formulario.hidden = true;
  formulario.reset();
}

/* El botón "Añadir fila" solo aparece si la tabla tiene formulario */
function prepararFormulario() {
  cerrarFormulario();

  btnAnadir.hidden =
    !tablaActual || !formularios[tablaActual];

  actualizarPanelPedido();
}

btnAnadir.addEventListener("click", () => {
  if (formulario.hidden) {
    abrirFormulario();
  } else {
    cerrarFormulario();
  }
});

btnCancelar.addEventListener("click", cerrarFormulario);

/* ---------- Crear registros ---------- */
formulario.addEventListener("submit", async (e) => {
  e.preventDefault();

  if (operacionPendiente) return;

  const config = formularios[tablaActual];
  const valores = Object.fromEntries(new FormData(formulario));

  const error = config.validar?.(valores);

  if (error) {
    mostrarMensaje(error);
    return;
  }

  // Crear la cabecera directamente en Oracle e iniciar la transacción.
  if (tablaActual === "Pedido") {
    if (pedidoPendiente) {
      mostrarMensaje("Finaliza o cancela el pedido abierto antes de crear otro.");
      return;
    }

    operacionPendiente = true;
    actualizarPanelPedido();

    try {
      const resultado = await llamarApiPedido("iniciar", {
        Cpedido: Number(valores.Cpedido),
        Ccliente: Number(valores.Ccliente),
        Fecha_pedido: valores.Fecha_pedido
      });

      aplicarEstadoPedido(resultado);
      cerrarFormulario();
      pintarTabla();
      mostrarMensaje(
        "Pedido insertado en Oracle. Transacción abierta; puedes añadir productos."
      );
    } catch (error) {
      mostrarMensaje(`No se pudo iniciar el pedido: ${error.message}`);
    } finally {
      operacionPendiente = false;
      actualizarPanelPedido();
    }
    return;
  }

  // Añadir el detalle en Oracle dentro de la transacción que sigue abierta.
  if (tablaActual === "Detalle_Pedido" && pedidoPendiente) {
    if (Number(valores.Cpedido) !== Number(pedidoPendiente.Cpedido)) {
      mostrarMensaje("Solo puedes añadir productos al pedido que está en curso.");
      return;
    }

    operacionPendiente = true;
    actualizarPanelPedido();

    try {
      const resultado = await llamarApiPedido("anadir", {
        Cproducto: Number(valores.Cproducto),
        Cantidad: Number(valores.Cantidad)
      });

      aplicarEstadoPedido(resultado);
      cerrarFormulario();
      pintarTabla();
      mostrarMensaje(resultado.mensaje);
    } catch (error) {
      mostrarMensaje(`No se pudo añadir el producto: ${error.message}`);
    } finally {
      operacionPendiente = false;
      actualizarPanelPedido();
    }
    return;
  }

  // Resto de altas: guardar directamente en Oracle.
  const fila = config.campos.map((campo) => {
    const valor = valores[campo.nombre];

    return campo.tipo === "number" || campo.numero
      ? Number(valor)
      : valor.trim();
  });

  operacionPendiente = true;

  try {
    await enviarOperacion("crear", tablaActual, fila);
    await cargarTablas();

    cerrarFormulario();
    pintarTabla();
    mostrarMensaje("Fila añadida correctamente.");

  } catch (error) {
    mostrarMensaje(`Error al añadir: ${error.message}`);

  } finally {
    operacionPendiente = false;
  }
})

/* La tarjeta solo se ve si hay una tabla seleccionada */
function mostrarOcultarTarjeta() {
  tarjeta.hidden = !tablaActual;
}

/* ---------- Cambiar de tabla ---------- */
selectorTabla.addEventListener("change", async () => {
  tablaActual = selectorTabla.value || null;

  mostrarOcultarTarjeta();
  prepararFormulario();

  filaEditando = null;

  try {
    await cargarTablas();
    pintarTabla();
    actualizarPanelPedido();
  } catch (error) {
    mostrarMensaje(`No se pudieron actualizar los datos: ${error.message}`);
  }
});

function rellenarSelector() {
  selectorTabla.innerHTML = "";

  const opcionInicial = document.createElement("option");
  opcionInicial.textContent = "Selecciona una tabla";
  opcionInicial.value = "";
  opcionInicial.disabled = true;
  opcionInicial.hidden = true;

  selectorTabla.appendChild(opcionInicial);

  Object.keys(tablas).forEach((nombre) => {
    const opcion = document.createElement("option");

    opcion.value = nombre;
    opcion.textContent = nombre;

    selectorTabla.appendChild(opcion);
  });

  selectorTabla.value = tablaActual ?? "";
}

/* ---------- Reiniciar base de datos ---------- */
const modalReiniciar =
  document.getElementById("modal-reiniciar");

const btnCancelarReinicio =
  document.getElementById("btn-cancelar-reinicio");

const btnConfirmarReinicio =
  document.getElementById("btn-confirmar-reinicio");

btnReiniciar.addEventListener("click", () => {
  modalReiniciar.showModal();
});

btnCancelarReinicio.addEventListener("click", () => {
  modalReiniciar.close();
});

btnConfirmarReinicio.addEventListener("click", async () => {
  if (pedidoPendiente) {
    alert("Antes de reiniciar la base de datos, finaliza o cancela el pedido abierto.");
    modalReiniciar.close();
    return;
  }

  btnConfirmarReinicio.disabled = true;
  btnCancelarReinicio.disabled = true;
  btnConfirmarReinicio.textContent = "Reiniciando...";

  try {
    const respuesta = await fetch(
      "./model/class/setupBD.php",
      {
        method: "POST"
      }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok || !resultado.ok) {
      throw new Error(
        resultado.error ||
        "No se pudo reiniciar la base de datos."
      );
    }

    await cargarTablas();

    tablaActual = null;
    filaEditando = null;
    pedidoPendiente = null;

    rellenarSelector();
    mostrarOcultarTarjeta();
    prepararFormulario();

    modalReiniciar.close();

    alert("Base de datos reiniciada correctamente.");

  } catch (error) {
    console.error("Error al reiniciar la BD:", error);
    alert("Error: " + error.message);

  } finally {
    btnConfirmarReinicio.disabled = false;
    btnCancelarReinicio.disabled = false;
    btnConfirmarReinicio.textContent = "Sí, reiniciar";
  }
});

/* ---------- Arranque ---------- */
async function iniciar() {
  try {
    // Recuperar una transacción que siga abierta en el proceso persistente.
    await restaurarPedidoActivo();

    rellenarSelector();
    mostrarOcultarTarjeta();
    prepararFormulario();
    pintarTabla();

  } catch (error) {
    console.error("Error al cargar las tablas:", error);

    selectorTabla.innerHTML = "";

    const opcion = document.createElement("option");
    opcion.textContent = "Error al cargar las tablas";
    opcion.value = "";

    selectorTabla.appendChild(opcion);
    selectorTabla.disabled = true;

    mostrarMensaje(
      "No se pudieron cargar los datos de Oracle."
    );
  }
}

iniciar();
