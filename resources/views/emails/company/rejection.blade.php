<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Empresa Rechazada - Helpdesk</title>
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
            border-bottom: 2px solid #dc3545;
        }
        .header h1 {
            color: #dc3545;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .request-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .request-info h3 {
            margin-top: 0;
            color: #495057;
        }
        .reason-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .reason-box h3 {
            margin-top: 0;
            color: #721c24;
        }
        .reason-text {
            color: #721c24;
            font-style: italic;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 4px;
        }
        .info-box {
            background-color: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            color: #0c5460;
        }
        .contact-button {
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
        .contact-button:hover {
            background-color: #0056b3;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
            text-align: center;
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
            <h1>Solicitud Rechazada</h1>
        </div>

        <div class="content">
            <p><strong>Hola {{ $displayName }},</strong></p>

            <p>Lamentamos informarte que tu solicitud para crear una empresa en Helpdesk System ha sido rechazada tras una revisión de nuestro equipo.</p>
        </div>

        <div class="request-info">
            <h3>Información de la Solicitud</h3>
            <p><strong>Empresa solicitada:</strong> {{ $request->company_name }}</p>
            <p><strong>Email de contacto:</strong> {{ $request->admin_email }}</p>
            <p><strong>Código de solicitud:</strong> {{ $request->request_code }}</p>
        </div>

        <div class="reason-box">
            <h3>Motivo del Rechazo</h3>
            <div class="reason-text">
                {{ $reason }}
            </div>
        </div>

        <div class="info-box">
            <strong>¿Qué puedes hacer ahora?</strong>
            <ul>
                <li>Revisar el motivo del rechazo detalladamente</li>
                <li>Corregir la información proporcionada</li>
                <li>Enviar una nueva solicitud con los datos correctos</li>
                <li>Contactar a nuestro equipo de soporte si tienes dudas</li>
            </ul>
        </div>

        <div class="content">
            <p>Si consideras que este rechazo fue un error o necesitas más información sobre los requisitos para crear una empresa, no dudes en contactarnos.</p>

            <p><strong>Email de soporte:</strong> {{ $supportEmail }}</p>
        </div>

        <div class="button-container">
            <a href="mailto:{{ $supportEmail }}" class="contact-button">
                Contactar Soporte
            </a>
        </div>

        <div class="content">
            <p>Agradecemos tu interés en Helpdesk System y esperamos poder ayudarte en el futuro.</p>
        </div>

        <div class="footer">
            <p>Equipo de Helpdesk System</p>
            <p>Este es un email automático, por favor no respondas a este mensaje.</p>
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 15px 0;">
            <p>&copy; {{ date('Y') }} Helpdesk System. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
