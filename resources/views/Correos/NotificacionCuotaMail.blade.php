{{-- [
  {
    "nombreCliente": "Victoria Beier",
    "nombreServicio": "Dr. Elian Lueilwitz Jr.",
    "cantidadServicio": 1,
    "precioServicio": 4366.85,
    "fechaServicio": "19-12-2023"
  }
] --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Nueva Cuota de Servicio</title>
    <style>
        /* Estilos para el correo */
        body {
            font-family: Arial, sans-serif;
            /* background-color:aqua; */
            padding: 20px;
        }
        .container {
            max-width: 60%;
            margin: 0 auto;
            /* background-color:darkgrey; */
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color:dodgerblue
        }
        p {
            color:green
            margin-bottom: 15px; /* Espacio entre párrafos */
        }
        .highlight {
            /* background-color:aqua; */
            padding: 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡Notificacion de Servicio!</h1>
        <p>Estimado <span class="highlight">{{$datos[0]->nombreCliente}}</span>,</p>
        <p>Le informamos que ya se encuentra disponible la cuota de su servicio de <span class="highlight">{{$datos[0]->nombreServicio}}</span>. El importe a abonar es de <span class="highlight">${{$datos[0]->precioServicio}}</span>.</p>
        <p>¡Agradecemos su confianza y quedamos a su disposición para cualquier consulta!</p>
        <p>¡Gracias por elegir nuestros servicios!</p>
        <p>Fecha {{$datos[0]->fechaServicio}}</p>
    </div>
</body>
</html>
