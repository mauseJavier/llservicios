<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura de Servicios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-top: 20px;
        }
        p {
            color: #555;
            font-size: 16px;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        strong {
            color: #e74c3c;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

    {{-- {
        "cliente_id": 2,
        "nombreCliente": "Nola Johnston",
        "correoCliente": "cydney93@example.org",
        "cantidad": 2,
        "servicios": [
          {
            "nombreServicio": "Nyah Boyle PhD",
            "cantidad": 1,
            "precio": 666.33,
            "total": 666.33,
            "fecha": "2023-12-21 17:11:53"
          },
          {
            "nombreServicio": "Nyah Boyle PhD",
            "cantidad": 1,
            "precio": 777.33,
            "total": 777.33,
            "fecha": "2023-12-21 17:11:53"
          }
        ],
        "total": 1443.66
      } --}}


    <div class="container">
        <h1>Factura de Servicios</h1>
        <p>Estimado cliente {{$datos['nombreCliente']}}, a continuación se detallan los servicios que debe abonar: {{$datos['cantidad']}} </p>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Empresa</th>
                    <th>Servicio</th>
                    
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datos['servicios'] as $item)
                <tr>
                    <td>{{$item->fecha}}</td>
                    <td>{{$item->nombreEmpresa}}</td>
                    <td>{{$item->nombreServicio}}</td>
                    
                    <td>{{$item->cantidad}}</td>
                    <td>${{$item->precio}}</td>
                    <td>${{$item->total}}</td>
                </tr>
                @endforeach


                <!-- Agrega más filas según tus necesidades -->
            </tbody>
        </table>
        <p>Total a abonar: <strong>${{$datos['total']}}</strong></p>
                
        <p>Realice el pago del servicio en la plataforma: {{env('APP_URL')}}</p>
        
        <p>Para registrarse, visite: {{env('APP_URL')}}/registro</p>


        <p>Por favor, realice el pago antes de la fecha de vencimiento.</p>
        <p>¡Gracias por confiar en nuestros servicios!</p>
        <p>
            Visitanos:
            <a href="{{env('APP_URL')}}">{{env('APP_URL')}}</a>
        </p>
    </div>
</body>
</html>
