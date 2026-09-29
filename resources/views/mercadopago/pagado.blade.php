<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $titulo }} - {{ env('APP_NAME') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css" />
    <style>
      body {
        display: flex;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        background: #f8f9fa;
      }
      .pago-container {
        max-width: 480px;
        margin: 0 auto;
        padding: 1rem;
      }
      .icono {
        font-size: 4rem;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <main class="pago-container">
      <article>
        <div class="icono">&#10003;</div>
        <hgroup style="text-align: center;">
          <h3>{{ $titulo }}</h3>
          <p>{{ $mensaje }}</p>
        </hgroup>

        <a href="{{ route('inicio') }}" role="button">Volver al Inicio</a>
      </article>
    </main>
  </body>
</html>