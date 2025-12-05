<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            width: 60px;
            height: 60px;
            background-color: #25D366;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .token {
            background-color: #f8f9fa;
            border: 2px solid #25D366;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .token-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #25D366;
            font-family: 'Courier New', monospace;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📱</div>
            <h1 style="color: #333; margin: 0;">Código de Verificación</h1>
        </div>

        <p>Hola <strong>{{ $userName }}</strong>,</p>

        <p>Has solicitado verificar tu cuenta. Usa el siguiente código para completar tu registro:</p>

        <div class="token">
            <div>Tu código de verificación es:</div>
            <div class="token-code">{{ $token }}</div>
        </div>

        <div class="warning">
            <strong>⚠️ Importante:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Este código es válido por <strong>15 minutos</strong></li>
                <li>No compartas este código con nadie</li>
                <li>Si no solicitaste este código, ignora este mensaje</li>
            </ul>
        </div>

        <p>También puedes haber recibido este código por WhatsApp. Puedes usar cualquiera de los dos métodos para verificar tu cuenta.</p>

        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            <p style="color: #999;">© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>