<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pago Fallido - {{ env('APP_NAME') }}</title>
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
        <div class="icono">&#10007;</div>
        <hgroup style="text-align: center;">
          <h3>No se pudo procesar su pago</h3>
          <p>Por favor, verifique sus datos e intente nuevamente.</p>
        </hgroup>

        @if(isset($paymentId))
            <p><strong>ID del Pago:</strong> {{ $paymentId }}</p>
        @endif

        @if(isset($status))
            <p><strong>Estado:</strong> {{ $status }}</p>
        @endif

        @if(isset($externalReference))
            <p><strong>Referencia:</strong> {{ $externalReference }}</p>
        @endif

        <a href="{{ route('inicio') }}" role="button">Volver al Inicio</a>
      </article>
    </main>
  </body>
</html>