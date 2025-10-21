{{-- <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago</title>
    <style>
        /* Estilos CSS para el recibo */
        body {
            font-family: Arial, sans-serif;
        }
        .recibo {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
        .titulo {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
        .pie {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        .detalle {
            margin-top: 20px;
        }
        .item {
          font-size: 13px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .item span {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="recibo">
        <div class="titulo">Recibo de Pago</div>
        <div class="detalle">
            <div class="item">
                <span>Fecha:</span>
                <span>{{$datos->created_at}}</span>
            </div>
            <div class="item">
                <span>Cliente:</span>
                <span>{{$datos->Cliente}}</span>
            </div>
            <div class="item">
                <span>Concepto:</span>
                <span>Pago de Servicio {{$datos->Servicio}}</span>
            </div>
            <div class="item">
                <span>Monto:</span>
                <span>${{$datos->importe}}</span>
            </div>
            <div class="item">
              <span>Comentario:</span>
              <span>{{$datos->comentario}}</span>
          </div>
        </div>
        <p class="pie">Usuario: {{$datos->nombreUsuario}}</p>
    </div>
</body>
</html> --}}

{{-- {
    "id": 1,
    "id_servicio_pagar": 8,
    "id_usuario": 1,
    "forma_pago": 1,
    "importe": 4402.92,
    "comentario": null,
    "created_at": "2024-05-06 18:27:20",
    "updated_at": "2024-05-06 18:27:20",
    "idServicioPagar": 8,
    "nombreUsuario": "DESMARET JAVIER NICOLAS",
    "Servicio": "Alexandre Waters",
    "Cliente": "Zoila Rowe Jr.",
    "idCliente": 8,
    "formaPago": "Efectivo"
  } --}}


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago</title>
    <style>
        /* Estilos CSS para el recibo */
        body {
            font-family: Arial, sans-serif;
        }
        .recibo {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
        .titulo {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
        .pie {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        .detalle {
            margin-top: 20px;
        }
        .item {
          font-size: 13px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .item span {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="recibo" style="">

    <img src="{{$empresa->logo}}"  alt="" width="80" style="margin-top: -10px; margin-left: 25%;"/>
        <div class="titulo">Recibo de Pago</div>
        <div class="titulo">{{$empresa->nombre}}</div>
        <div class="detalle">

            <div class="item">
                <span>Fecha:</span>
                <span>{{$datos->created_at}}</span>
            </div>

            <div class="item">
                <span>Cliente:</span>
                <span>{{$datos->Cliente}}</span>
            </div>

            <div class="item">
                <span>Concepto:</span>
                <span>{{$datos->Servicio}}</span>
            </div>
            
            <div class="item">
                <span>Monto:</span>
                <span>${{$datos->importe}}</span>
            </div>

            <div class="item">
                <span>Forma de pago:</span>
                <span> {{$datos->formaPago}}</span>
            </div>
            
            <div class="item">
              <span>Comentario:</span>
              <span>{{$datos->comentario}}</span>
          </div>
        </div>

        <p class="pie">Usuario: {{$datos->nombreUsuario}}</p>
    </div>
</body>


</html>
