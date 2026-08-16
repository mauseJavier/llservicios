{{-- [
    {
        "nombreCliente": "Victoria Beier",
        "nombreServicio": "Dr. Elian Lueilwitz Jr.",
        "cantidadServicio": 1,
        "precioOriginal": 100.00,
        "totalOriginal": 100.00,
        "tipoRecargo": "porcentaje",
        "valorRecargo": 10.00,
        "montoRecargo": 10.00,
        "totalConRecargo": 110.00,
        "fechaVencimiento": "16-08-2026"
    }
] --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de Recargo por Mora</title>
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
            background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
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
            color: #e67e22;
            font-weight: 600;
        }
        .info-box {
            background-color: #fff8f0;
            border-left: 4px solid #f2994a;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .info-box p {
            margin: 0;
            line-height: 1.8;
            color: #555555;
        }
        .recargo-box {
            background-color: #fff3f3;
            border: 1px solid #f5c6c6;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .recargo-box p {
            margin: 0;
            line-height: 1.8;
            color: #555555;
        }
        .amount {
            font-size: 24px;
            color: #e67e22;
            font-weight: 700;
            display: inline-block;
            margin: 5px 0;
        }
        .total-new {
            font-size: 26px;
            color: #dc3545;
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
            background: linear-gradient(135deg, #f2994a 0%, #e67e22 100%);
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
            color: #e67e22;
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
            .total-new {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <h1>⚠️ Aviso de Recargo por Mora</h1>
            </div>
            
            <div class="content">
                <p class="greeting">Estimado/a <span class="highlight">{{$datos['nombreCliente']}}</span>,</p>
                
                <div class="info-box">
                    <p>Le informamos desde <strong>{{$datos['nombreEmpresa']}}</strong> que su servicio se encuentra <strong>vencido</strong> y se le ha aplicado un recargo por mora:</p>
                    <p style="margin-top: 15px;">
                        📋 <strong>Servicio:</strong> <span class="highlight">{{$datos['nombreServicio']}}</span>
                    </p>
                    <p style="margin-top: 10px;">
                        🔢 <strong>Cantidad:</strong> {{$datos['cantidadServicio']}}
                    </p>
                    <p style="margin-top: 10px;">
                        💰 <strong>Total original:</strong> <span class="amount">${{number_format($datos['totalOriginal'], 2, ',', '.')}}</span>
                    </p>
                    <p style="margin-top: 10px;" class="date">
                        📅 Vencimiento: {{$datos['fechaVencimiento']}}
                    </p>
                </div>
                
                <div class="recargo-box">
                    <p>📌 <strong>Recargo por mora aplicado:</strong></p>
                    @if($datos['tipoRecargo'] === 'fijo')
                        <p style="margin-top: 10px;">💵 <strong>Recargo fijo:</strong> <span class="amount">${{number_format($datos['montoRecargo'], 2, ',', '.')}}</span></p>
                    @else
                        <p style="margin-top: 10px;">📈 <strong>Recargo porcentual ({{$datos['valorRecargo']}}%):</strong> <span class="amount">${{number_format($datos['montoRecargo'], 2, ',', '.')}}</span></p>
                    @endif
                    <p style="margin-top: 15px;">
                        💳 <strong>Nuevo total a abonar:</strong> <span class="total-new">${{number_format($datos['totalConRecargo'], 2, ',', '.')}}</span>
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
                <p>Le recomendamos regularizar su situación para evitar nuevos recargos.</p>
                <p style="margin-top: 15px;">Gracias por elegir nuestros servicios 🙏</p>
            </div>
        </div>
    </div>
</body>
</html>