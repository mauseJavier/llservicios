<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago</title>
    <style>
        /* Estilos para el correo */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .content {
            color: #333;
            line-height: 1.6;
        }
        .content p {
            margin-bottom: 15px;
        }
        .highlight {
            background-color: #e8f5e9;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #2e7d32;
        }
        .info-box {
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 8px 0;
            font-size: 14px;
        }
        .info-box strong {
            color: #2e7d32;
            display: inline-block;
            min-width: 140px;
        }
        .total {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #888;
            font-size: 12px;
        }
        .attachment-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
        }
        .success-icon {
            color: #4CAF50;
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        
        <div class="header">
            <h1>¡Pago Confirmado!</h1>
            <p>Comprobante de Pago de Servicio</p>
        </div>

        <div class="content">
            <p>Estimado/a <span class="highlight">{{ $datos['nombreCliente'] ?? 'Cliente' }}</span>,</p>
            
            <p>Nos complace confirmar que hemos recibido exitosamente su pago correspondiente al servicio contratado.</p>

            <div class="info-box">
                <p><strong>Empresa:</strong> {{ $datos['nombreEmpresa'] ?? 'Sin especificar' }}</p>
                <p><strong>Servicio:</strong> {{ $datos['nombreServicio'] ?? 'Sin especificar' }}</p>
                <p><strong>Cantidad:</strong> {{ $datos['cantidad'] ?? 1 }}</p>
                <p><strong>Precio unitario:</strong> ${{ number_format($datos['precioUnitario'] ?? 0, 2) }}</p>
                <p><strong>Fecha de pago:</strong> {{ $datos['fechaPago'] ?? date('d/m/Y H:i') }}</p>
                @if(!empty($datos['mp_payment_id']))
                <p><strong>ID de pago:</strong> {{ $datos['mp_payment_id'] }}</p>
                @endif
                @if(!empty($datos['comentario']))
                <p><strong>Comentario:</strong> {{ $datos['comentario'] }}</p>
                @endif
            </div>

            <div class="total">
                Total Pagado: ${{ number_format($datos['total'] ?? 0, 2) }}
            </div>

            <div class="attachment-note">
                📎 <strong>Adjunto:</strong> Encontrará el comprobante de pago en formato PDF adjunto a este correo.
            </div>

            <p>Este comprobante es válido como prueba de pago. Por favor, consérvelo para sus registros.</p>
            
            <p>Si tiene alguna pregunta o necesita asistencia, no dude en contactarnos.</p>

            <p><strong>¡Gracias por confiar en nuestros servicios!</strong></p>
        </div>

        <div class="footer">
            <p>Este es un correo automático, por favor no responda a este mensaje.</p>
            <p>&copy; {{ date('Y') }} LLServicios.ar - Todos los derechos reservados</p>
            <p>{{ env('APP_URL') }}</p>
        </div>
    </div>
</body>
</html>
