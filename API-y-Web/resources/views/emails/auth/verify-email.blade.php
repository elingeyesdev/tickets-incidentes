<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu cuenta - Helpdesk</title>
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
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .verification-button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .verification-button:hover {
            background-color: #0056b3;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .code-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .code-box .code {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        .alternative-link {
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning strong {
            color: #856404;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        ul li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🔐 Verifica tu cuenta</h1>
        </div>

        <div class="content">
            <p><strong>Hola {{ $displayName }},</strong></p>

            <p>¡Bienvenido a <strong>Helpdesk System</strong>! Estamos emocionados de tenerte con nosotros.</p>

            <p>Para completar tu registro y comenzar a usar tu cuenta, necesitamos verificar tu dirección de correo electrónico:</p>

            <p><strong>{{ $user->email }}</strong></p>
        </div>

        <div class="content">
            <h3>Opción 1: Usar el enlace directo</h3>
            <p>Haz clic en el siguiente botón para verificar tu cuenta:</p>
        </div>

        <div class="button-container">
            <a href="{{ $verificationUrl }}" class="verification-button">
                ✓ Verificar mi cuenta
            </a>
        </div>

        <div class="content">
            <h3>Opción 2: Usar el código de verificación</h3>
            <p>Si prefieres, puedes usar este código de 6 dígitos en lugar del enlace:</p>
        </div>

        <div class="code-box">
            <div class="code">{{ $verificationCode }}</div>
        </div>

        <div class="warning">
            <strong>⏱️ Información Importante:</strong>
            <ul>
                <li>Este enlace y código expiran en <strong>{{ $expiresInHours }} horas</strong></li>
                <li>No compartas este código con nadie</li>
                <li>Si no creaste una cuenta, puedes ignorar este email de forma segura</li>
            </ul>
        </div>

        <div class="content">
            <p><strong>¿Por qué verificamos tu email?</strong></p>
            <ul>
                <li>Confirmar que la dirección de correo es válida</li>
                <li>Proteger tu cuenta contra accesos no autorizados</li>
                <li>Enviarte notificaciones importantes sobre tus tickets</li>
            </ul>
        </div>

        <div class="alternative-link">
            <p><strong>¿El botón no funciona?</strong> Copia y pega este enlace en tu navegador:</p>
            <p>{{ $verificationUrl }}</p>
        </div>

        <div class="footer">
            <p>Si no creaste una cuenta en Helpdesk System, puedes ignorar este correo.</p>
            <p>Este es un email automático, por favor no respondas a este mensaje.</p>
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 15px 0;">
            <p>Atentamente,<br>El equipo de <strong>Helpdesk System</strong></p>
            <p>&copy; {{ date('Y') }} Helpdesk System. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
