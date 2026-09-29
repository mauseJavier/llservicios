<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
            color: #333333;
            line-height: 1.6;
        }
        .content p {
            margin: 15px 0;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .reset-button:hover {
            background-color: #45a049;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
            border-top: 1px solid #eeeeee;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .link-text {
            word-break: break-all;
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Restablecer Contraseña</h1>
        </div>
        
        <div class="content">
            <p>Hola,</p>
            
            <p>Has solicitado restablecer la contraseña de tu cuenta en <strong>{{ config('app.name') }}</strong>.</p>
            
            <p>Para continuar con el proceso, haz clic en el siguiente botón:</p>
            
            <div class="button-container">
                <a href="{{ $url }}" class="reset-button">Restablecer Contraseña</a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Importante:</strong> Este enlace expirará en {{ config('auth.passwords.users.expire', 60) }} minutos.
            </div>
            
            <p>Si no solicitaste restablecer tu contraseña, puedes ignorar este correo de forma segura. Tu contraseña no será cambiada.</p>
            
            <p class="link-text">
                <strong>Si tienes problemas con el botón, copia y pega este enlace en tu navegador:</strong><br>
                {{ $url }}
            </p>
        </div>
        
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
