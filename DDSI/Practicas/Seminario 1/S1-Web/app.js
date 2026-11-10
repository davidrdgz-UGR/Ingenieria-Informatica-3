/* ------------------------------------------------------------------
   DATOS DE EJEMPLO
   Más adelante sustituye este objeto por datos reales de tu BBDD
   (ver las funciones cargarTablas() y guardarTabla() más abajo).
------------------------------------------------------------------- */
let tablas = {
  clientes: {
    columnas: ["id", "nombre", "ciudad"],
    filas: [
      [1, "Ana", "Granada"],
      [2, "Luis", "Sevilla"],
    ],
  },
  productos: {
    columnas: ["id", "descripcion", "precio"],
    filas: [
      [1, "Teclado", 25],
      [2, "Ratón", 12],
    ],
  },
};

let tablaActual = null;
let modoEdicion = false;

/* ---------- Referencias al DOM ---------- */
const selector = document.getElementById("selector-tabla");
const titulo = document.getElementById("titulo-tabla");
const tabla = document.getElementById("tabla");
const thead = tabla.querySelector("thead");
const tbody = tabla.querySelector("tbody");
const btnEditar = document.getElementById("btn-editar");
const btnFila = document.getElementById("btn-fila");
const btnColumna = document.getElementById("btn-columna");
const btnGuardar = document.getElementById("btn-guardar");
const mensaje = document.getElementById("mensaje");

/* ---------- Conexión con la BBDD (pendiente) ---------- */
async function cargarTablas() {
  // Ejemplo cuando tengas un backend:
  // const resp = await fetch("/api/tablas");
  // tablas = await resp.json();
  return tablas;
}

async function guardarTabla(nombre, datos) {
  // Ejemplo cuando tengas un backend:
  // await fetch(`/api/tablas/${nombre}`, {
  //   method: "PUT",
  //   headers: { "Content-Type": "application/json" },
  //   body: JSON.stringify(datos),
  // });
  console.log("Guardar", nombre, datos);
}

/* ---------- Pintar la tabla ---------- */
function pintarTabla() {
  const { columnas, filas } = tablas[tablaActual];
  titulo.textContent = tablaActual;
  const editable = modoEdicion ? "true" : "false";

  thead.innerHTML = "";
  const filaCab = document.createElement("tr");
  columnas.forEach((col) => {
    const th = document.createElement("th");
    th.textContent = col;
    th.contentEditable = editable;
    filaCab.appendChild(th);
  });
  thead.appendChild(filaCab);

  tbody.innerHTML = "";
  filas.forEach((fila) => {
    const tr = document.createElement("tr");
    fila.forEach((valor) => {
      const td = document.createElement("td");
      td.textContent = valor;
      td.contentEditable = editable;
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  });

  tabla.classList.toggle("editando", modoEdicion);
}

/* Lee lo que hay en pantalla y lo guarda en el objeto "tablas" */
function leerTablaDelDOM() {
  const columnas = [...thead.querySelectorAll("th")].map((th) => th.textContent.trim());
  const filas = [...tbody.querySelectorAll("tr")].map((tr) =>
    [...tr.querySelectorAll("td")].map((td) => td.textContent.trim())
  );
  tablas[tablaActual] = { columnas, filas };
}

function mostrarMensaje(texto) {
  mensaje.textContent = texto;
}

/* ---------- Modo edición ---------- */
function activarEdicion(activo) {
  modoEdicion = activo;
  btnEditar.textContent = activo ? "Cancelar edición" : "Editar tabla";
  btnFila.hidden = btnColumna.hidden = btnGuardar.hidden = !activo;
  pintarTabla();
  mostrarMensaje(activo ? "Modo edición: haz clic en una celda para cambiarla." : "");
}

btnEditar.addEventListener("click", () => {
  if (modoEdicion) {
    // Cancelar: se descartan los cambios no guardados
    activarEdicion(false);
  } else {
    activarEdicion(true);
  }
});

btnFila.addEventListener("click", () => {
  leerTablaDelDOM();
  const nCols = tablas[tablaActual].columnas.length;
  tablas[tablaActual].filas.push(Array(nCols).fill(""));
  pintarTabla();
});

btnColumna.addEventListener("click", () => {
  leerTablaDelDOM();
  const t = tablas[tablaActual];
  t.columnas.push("nueva_columna");
  t.filas.forEach((fila) => fila.push(""));
  pintarTabla();
});

btnGuardar.addEventListener("click", async () => {
  leerTablaDelDOM();
  await guardarTabla(tablaActual, tablas[tablaActual]);
  activarEdicion(false);
  mostrarMensaje("Cambios guardados.");
});

/* ---------- Cambiar de tabla ---------- */
selector.addEventListener("change", () => {
  
  document.getElementsByClassName("tarjeta")[0].hidden = false;

  tablaActual = selector.value;
  activarEdicion(false);
});

/* ---------- Arranque ---------- */
async function iniciar() {
  await cargarTablas();
  
  Object.keys(tablas).forEach((nombre) => {
    const opcion = document.createElement("option");
    opcion.value = opcion.textContent = nombre;
    selector.appendChild(opcion);
  });
  
  if( selector.getElementById() != "start" ){ 
    tablaActual = Object.keys(tablas)[0];
    pintarTabla();
  }
}

iniciar();
