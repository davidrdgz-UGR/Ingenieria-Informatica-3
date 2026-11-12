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

  <script src="app.js"></script>
</body>
</html>