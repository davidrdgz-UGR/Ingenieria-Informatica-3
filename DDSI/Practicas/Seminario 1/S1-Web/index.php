<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Práctica DDSI</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <header class="cabecera">
    <h1>Seminario 1 - DDSI</h1>

    <div class="selector">
      <label for="selector-tabla">Tabla:</label>
      <select id="selector-tabla"></select>

      <button id="btn-reiniciar" type="button" class="btn btn-peligro">Reiniciar BD</button>
    </div>

    
  </header>

  <main class="contenido">
    <section class="tarjeta" hidden>
      <div class="barra-herramientas">
        <h2 id="titulo-tabla"></h2>
        <div class="acciones">
          <!-- <button id="btn-editar" class="btn">Editar contenido</button> -->
          <!-- Solo se ve en modo edición -->
          <button id="btn-guardar" class="btn btn-principal" hidden>Guardar cambios</button>
        </div>
      </div>

      <div class="tabla-contenedor">
        <table id="tabla">
          <thead></thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="acciones-tabla">
        <button id="btn-anadir" class="btn btn-principal" hidden>+ Añadir fila</button>
      </div>

      <form id="formulario" class="formulario" hidden>
        <div id="formulario-campos" class="formulario-campos"></div>
        <div class="formulario-botones">
          <button type="submit" class="btn btn-principal">Confirmar</button>
          <button type="button" id="btn-cancelar" class="btn">Cancelar</button>
        </div>
      </form>

      <p id="mensaje" class="mensaje" role="status"></p>
    </section>
  </main>


  
<!-- Ventana de confirmación para reiniciar la BD -->
<dialog id="modal-reiniciar" class="modal-reiniciar">
  <div class="modal-icono">⚠</div>

  <h2>¿Reiniciar base de datos?</h2>

  <p>
    Se eliminarán todos los datos actuales y se
    restaurarán las tablas con sus datos iniciales.
  </p>

  <p class="modal-advertencia">
    Esta acción no se puede deshacer.
  </p>

  <div class="modal-botones">
    <button id="btn-cancelar-reinicio" type="button" class="btn">
      Cancelar
    </button>

    <button id="btn-confirmar-reinicio" type="button"
            class="btn btn-peligro">
      Sí, reiniciar
    </button>
  </div>
</dialog>


  <script src="app.js"></script>
</body>
</html>