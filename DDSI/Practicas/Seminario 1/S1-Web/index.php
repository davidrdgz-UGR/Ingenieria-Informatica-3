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
    
    <nav class="selector">
      <label for="selector-tabla">Tabla:</label>

      <select id="selector-tabla" >
        <option value="" selected disabled>
          No hay tablas disponibles
        </option>

        <option value="" disabled hidden>
          Selecciona una tabla :
        </option>
      </select>

      <button id="btn-crear" class="btn">
        + Crear tabla
      </button>
    </nav>

  </header>

  <main class="contenido">
    <section class="tarjeta" hidden>
      <div class="barra-herramientas">
        <h2 id="titulo-tabla"></h2>
        <div class="acciones">
          <button id="btn-editar" class="btn">Editar tabla</button>
          <!-- Estos botones solo se ven en modo edición -->
          <button id="btn-fila" class="btn btn-secundario" hidden>Añadir fila</button>
          <button id="btn-columna" class="btn btn-secundario" hidden>Añadir columna</button>
          <button id="btn-guardar" class="btn btn-principal" hidden>Guardar cambios</button>
        </div>
      </div>

      <div class="tabla-contenedor">
        <table id="tabla">
          <thead></thead>
          <tbody></tbody>
        </table>
      </div>

      <p id="mensaje" class="mensaje" role="status"></p>
    </section>
  </main>


  <script src="app.js"></script>
</body>
</html>
