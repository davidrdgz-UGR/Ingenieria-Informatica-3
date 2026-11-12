/* ------------------------------------------------------------------
   TABLAS FIJAS DEL ENUNCIADO
   La estructura (columnas) no cambia. Las filas son de ejemplo y se
   sustituirán por los datos reales de la BBDD en cargarTablas().
------------------------------------------------------------------- */
let tablas = {
  Stock: {
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
  },
};

/* ------------------------------------------------------------------
   FORMULARIOS DE ALTA (uno por tabla)
   campos: en el mismo orden que las columnas de la tabla.
   validar: devuelve un texto de error, o null si todo está bien.
   Tipos: number, text (maxlength), date y select (opciones sacadas de
   otra tabla; numero:true si el valor debe guardarse como número).
   valorInicial: valor por defecto al abrir el formulario.
   max / pista: límite y texto de ayuda que dependen de otros campos.
   despuesDeGuardar: acción extra tras guardar (p. ej. descontar stock).
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
        textoVacio: "No hay pedidos: crea uno primero",
        opciones: () =>
          tablas.Pedido.filas.map(([cpedido, ccliente]) => {
            const cliente = tablas.Clientes.filas.find(
              (fila) => Number(fila[0]) === Number(ccliente)
            );
            return {
              valor: cpedido, // lo que se envía: solo el código
              texto: cliente
                ? `Pedido ${cpedido} (${cliente[1]})` // lo que se ve
                : `Pedido ${cpedido} (cliente ${ccliente})`, // cliente no encontrado
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
      );
      if (!pedidoOk) return "El pedido seleccionado no existe.";

      if (!filaStock(valores.Cproducto)) return "El producto seleccionado no existe.";

      const repetido = tablas.Detalle_Pedido.filas.some(
        (fila) =>
          Number(fila[0]) === Number(valores.Cpedido) &&
          Number(fila[1]) === Number(valores.Cproducto)
      );
      if (repetido) return "Ese producto ya está en el pedido.";

      const cantidad = Number(valores.Cantidad);
      const disponible = stockDe(valores.Cproducto);
      if (cantidad > disponible) {
        return `No hay stock suficiente: quedan ${disponible} unidades.`;
      }
      return null;
    },
    // Simula lo que hará el servidor: descontar el stock.
    // Con el backend se sustituirá por recargar las tablas desde la BBDD.
    despuesDeGuardar(fila) {
      const stock = filaStock(fila[1]);
      stock[2] = Number(stock[2]) - Number(fila[2]);
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

/* Unidades disponibles de un producto. OJO: usa la posición 2 de la fila
   de Stock (Cproducto, Producto/Nombre, Cantidad). */
function stockDe(cproducto) {
  const fila = filaStock(cproducto);
  return fila ? Number(fila[2]) : 0;
}

/* ------------------------------------------------------------------
   RELACIONES VISUALES
   Columnas que guardan un código pero se muestran con el nombre entre
   paréntesis, p. ej. "1 (Ana)". Solo cambia lo que se ve: los datos
   guardados siguen siendo únicamente el código.
------------------------------------------------------------------- */
function conNombre(nombreTabla, codigo) {
  const fila = tablas[nombreTabla].filas.find(
    (f) => Number(f[0]) === Number(codigo)
  );
  return fila ? `${codigo} (${fila[1]})` : codigo; // fila[1] = nombre
}

const relaciones = {
  Pedido: {
    Ccliente: (codigo) => conNombre("Clientes", codigo),
  },
  Detalle_Pedido: {
    Cproducto: (codigo) => conNombre("Stock", codigo),
    // El pedido no tiene nombre propio: se muestra el del cliente que lo hizo
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
let modoEdicion = false;

/* ---------- Referencias al DOM ---------- */
const selectorTabla = document.getElementById("selector-tabla");
const titulo = document.getElementById("titulo-tabla");
const tabla = document.getElementById("tabla");
const thead = tabla.querySelector("thead");
const tbody = tabla.querySelector("tbody");

/* const btnEditar = document.getElementById("btn-editar"); */
const btnGuardar = document.getElementById("btn-guardar");
const mensaje = document.getElementById("mensaje");
const tarjeta = document.querySelector(".tarjeta");

/* Formulario */
const btnAnadir = document.getElementById("btn-anadir");
const formulario = document.getElementById("formulario");
const formularioCampos = document.getElementById("formulario-campos");
const btnCancelar = document.getElementById("btn-cancelar");

/* ---------- Conexión con la BBDD (pendiente) ---------- */
async function cargarTablas() {
  // Cuando tengas el backend:
  // const resp = await fetch("/api/tablas");
  // tablas = await resp.json();
  return tablas;
}

async function guardarTabla(nombre, datos) {
  // Cuando tengas el backend:
  // await fetch(`/api/tablas/${nombre}`, {
  //   method: "PUT",
  //   headers: { "Content-Type": "application/json" },
  //   body: JSON.stringify(datos),
  // });
  console.log("Guardar", nombre, datos);
}

/* Guarda UNA fila nueva (la usa el formulario de alta) */
async function guardarFila(nombre, fila) {
  // Cuando tengas el backend:
  // const resp = await fetch(`/api/tablas/${nombre}/filas`, {
  //   method: "POST",
  //   headers: { "Content-Type": "application/json" },
  //   body: JSON.stringify(fila),
  // });
  // if (!resp.ok) throw new Error("No se pudo guardar");
  console.log("Guardar fila en", nombre, fila);
}

/* ---------- Pintar la tabla ---------- */
function pintarTabla() {
  if (!tablaActual) return; // sin tabla seleccionada no hay nada que pintar

  const { columnas, filas } = tablas[tablaActual];
  titulo.textContent = tablaActual;

  // Cabecera: los nombres de columna son fijos, nunca editables
  thead.innerHTML = "";
  const filaCab = document.createElement("tr");
  columnas.forEach((col) => {
    const th = document.createElement("th");
    th.textContent = col;
    filaCab.appendChild(th);
  });
  thead.appendChild(filaCab);

  // Cuerpo
  tbody.innerHTML = "";
  if (filas.length === 0) {
    const tr = document.createElement("tr");
    const td = document.createElement("td");
    td.colSpan = columnas.length;
    td.textContent = "Esta tabla no tiene filas.";
    td.className = "vacia";
    tr.appendChild(td);
    tbody.appendChild(tr);
  } else {
    filas.forEach((fila) => {
      const tr = document.createElement("tr");
      fila.forEach((valor, i) => {
        const td = document.createElement("td");
        // Con nombre entre paréntesis, salvo en modo edición (ahí, el código puro)
        const formato = relaciones[tablaActual]?.[columnas[i]];
        td.textContent = formato && !modoEdicion ? formato(valor) : valor;
        td.contentEditable = modoEdicion ? "true" : "false";
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });
  }

  tabla.classList.toggle("editando", modoEdicion);
  // Sin filas no hay nada que editar
 /*  btnEditar.disabled = filas.length === 0; */
}

/* Lee las celdas de la pantalla y las guarda en "tablas" */
function leerFilasDelDOM() {
  const filas = [...tbody.querySelectorAll("tr")].map((tr) =>
    [...tr.querySelectorAll("td")].map((td) => td.textContent.trim())
  );
  tablas[tablaActual].filas = filas;
}

function mostrarMensaje(texto) {
  mensaje.textContent = texto;
}

/* ---------- Modo edición (solo el contenido de las celdas) ---------- */
function activarEdicion(activo) {
  modoEdicion = activo;
  /* btnEditar.textContent = activo ? "Cancelar edición" : "Editar contenido"; */
  btnGuardar.hidden = !activo;
  pintarTabla();
  mostrarMensaje(activo ? "Modo edición: haz clic en una celda para cambiarla." : "");
}

/* btnEditar.addEventListener("click", () => activarEdicion(!modoEdicion)); */

btnGuardar.addEventListener("click", async () => {
  leerFilasDelDOM();
  await guardarTabla(tablaActual, tablas[tablaActual]);
  activarEdicion(false);
  mostrarMensaje("Cambios guardados.");
});

/* ---------- Formulario de alta ---------- */
function pintarFormulario() {
  const config = formularios[tablaActual];
  formularioCampos.innerHTML = "";

  const dinamicos = []; // campos con límite máximo o texto de ayuda

  config.campos.forEach((campo) => {
    const div = document.createElement("div");
    div.className = "campo";

    const label = document.createElement("label");
    label.htmlFor = `campo-${campo.nombre}`;
    label.textContent = campo.nombre;

    let input;
    if (campo.tipo === "select") {
      // Desplegable cuyas opciones salen de otra tabla (relación)
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
    } else {
      input = document.createElement("input");
      input.type = campo.tipo;
      if (campo.min !== undefined) input.min = campo.min;
      if (campo.tipo === "number") input.step = 1;
      if (campo.maxlength !== undefined) input.maxLength = campo.maxlength;
      if (campo.valorInicial) input.value = campo.valorInicial();
    }

    input.id = `campo-${campo.nombre}`;
    input.name = campo.nombre;
    input.required = !!campo.requerido;

    div.append(label, input);

    if (campo.max || campo.pista) {
      const pista = document.createElement("small");
      pista.className = "pista";
      div.appendChild(pista);
      dinamicos.push({ campo, input, pista });
    }

    formularioCampos.appendChild(div);
  });

  // Campos que dependen de otros (límite máximo y texto de ayuda)
  if (dinamicos.length) {
    const refrescar = () => {
      const valores = Object.fromEntries(new FormData(formulario));
      dinamicos.forEach(({ campo, input, pista }) => {
        if (campo.max) {
          const max = campo.max(valores);
          if (max === null) input.removeAttribute("max");
          else input.max = max;
        }
        pista.textContent = campo.pista ? campo.pista(valores) : "";
      });
    };
    formularioCampos.querySelectorAll("input, select").forEach((el) => {
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
  btnAnadir.hidden = !tablaActual || !formularios[tablaActual];
}

btnAnadir.addEventListener("click", () => {
  if (formulario.hidden) abrirFormulario();
  else cerrarFormulario();
});

btnCancelar.addEventListener("click", cerrarFormulario);

formulario.addEventListener("submit", async (e) => {
  e.preventDefault(); // evita que la página se recargue
  const config = formularios[tablaActual];
  const valores = Object.fromEntries(new FormData(formulario));

  const error = config.validar?.(valores);
  if (error) {
    mostrarMensaje(error);
    return;
  }

  // Fila en el mismo orden que las columnas; los number se convierten a número
  const fila = config.campos.map((campo) => {
    const valor = valores[campo.nombre];
    return campo.tipo === "number" || campo.numero ? Number(valor) : valor.trim();
  });

  await guardarFila(tablaActual, fila);
  tablas[tablaActual].filas.push(fila);
  config.despuesDeGuardar?.(fila); // p. ej. descontar el stock

  cerrarFormulario();
  pintarTabla();
  mostrarMensaje("Fila añadida correctamente.");
});

/* La tarjeta (y con ella la tabla) solo se ve si hay una tabla seleccionada */
function mostrarOcultarTarjeta() {
  tarjeta.hidden = !tablaActual;
}

/* ---------- Cambiar de tabla ---------- */
selectorTabla.addEventListener("change", () => {
  tablaActual = selectorTabla.value || null;
  mostrarOcultarTarjeta();
  prepararFormulario();
  activarEdicion(false);
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
    opcion.value = opcion.textContent = nombre;
    selectorTabla.appendChild(opcion);
  });
  selectorTabla.value = tablaActual ?? "";
}

/* ---------- Arranque ---------- */
async function iniciar() {
  await cargarTablas();
  rellenarSelector();
  mostrarOcultarTarjeta();
  prepararFormulario();
  pintarTabla();
}

iniciar();