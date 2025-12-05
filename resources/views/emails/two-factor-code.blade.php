<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 10px;
        }
        .code-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            margin: 30px 0;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            margin: 0;
            padding: 10px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 4px;
            display: inline-block;
        }
        .message {
            text-align: center;
            margin: 20px 0;
            color: #666;
        }
        .warning {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <h2 style="margin: 0; color: #333;">Código de Verificación</h2>
        </div>

        <p>Hola <strong>{{ $user->name }}</strong>,</p>

        <p>Has solicitado acceder a tu cuenta. Por favor usa el siguiente código de verificación:</p>

        <div class="code-box">
            <p style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9;">Tu código de verificación es:</p>
            <div class="code">{{ $code }}</div>
            <p style="margin: 10px 0 0 0; font-size: 12px; opacity: 0.9;">Este código expira en 5 minutos</p>
        </div>

        <p class="message">
            Ingresa este código en la página de verificación para completar tu inicio de sesión.
        </p>

        <div class="warning">
            <strong>⚠️ Importante:</strong> Si no solicitaste este código, ignora este correo y asegúrate de que tu cuenta esté segura.
        </div>

        <p style="color: #666; font-size: 14px;">
            Por tu seguridad, nunca compartas este código con nadie. El equipo de {{ config('app.name') }} nunca te pedirá este código.
        </p>

        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
