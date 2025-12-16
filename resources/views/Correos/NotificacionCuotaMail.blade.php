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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            color: #333333;
        }
        .email-wrapper {
            width: 100%;
            padding: 40px 20px;
            background-color: #f4f7fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .highlight {
            color: #667eea;
            font-weight: 600;
        }
        .info-box {
            background-color: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .info-box p {
            margin: 0;
            line-height: 1.8;
            color: #555555;
        }
        .amount {
            font-size: 24px;
            color: #667eea;
            font-weight: 700;
            display: inline-block;
            margin: 5px 0;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .secondary-link {
            text-align: center;
            margin: 20px 0;
            font-size: 14px;
            color: #666666;
        }
        .secondary-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 8px 0;
            color: #666666;
            font-size: 14px;
            line-height: 1.6;
        }
        .date {
            color: #999999;
            font-size: 13px;
            font-style: italic;
        }
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 10px;
            }
            .content {
                padding: 30px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .amount {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <h1>💳 Notificación de Servicio</h1>
            </div>
            
            <div class="content">
                <p class="greeting">Estimado/a <span class="highlight">{{$datos[0]->nombreCliente}}</span>,</p>
                
                <div class="info-box">
                    <p>Le informamos que ya se encuentra disponible la cuota de su servicio:</p>
                    <p style="margin-top: 15px;">
                        📋 <strong>Servicio:</strong> <span class="highlight">{{$datos[0]->nombreServicio}}</span>
                    </p>
                    <p style="margin-top: 10px;">
                        💰 <strong>Importe a abonar:</strong> <span class="amount">${{number_format($datos[0]->precioServicio * $datos[0]->cantidadServicio, 2, ',', '.')}}</span>
                    </p>
                    <p style="margin-top: 10px;" class="date">
                        📅 Fecha: {{$datos[0]->fechaServicio}}
                    </p>
                </div>
                
                <div class="button-container">
                    <a href="{{env('APP_URL')}}" class="btn">Realizar Pago</a>
                </div>
                
                <div class="secondary-link">
                    <p>¿Aún no tiene cuenta? <a href="{{env('APP_URL')}}/registro">Regístrese aquí</a></p>
                </div>
            </div>
            
            <div class="footer">
                <p><strong>¡Agradecemos su confianza!</strong></p>
                <p>Quedamos a su disposición para cualquier consulta.</p>
                <p style="margin-top: 15px;">Gracias por elegir nuestros servicios 🙏</p>
            </div>
        </div>
    </div>
</body>
</html>
