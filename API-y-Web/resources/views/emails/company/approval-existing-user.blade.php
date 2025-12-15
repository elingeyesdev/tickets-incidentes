<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Empresa Aprobada - Helpdesk</title>
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
            border-bottom: 2px solid #28a745;
        }
        .header h1 {
            color: #28a745;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .company-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .company-info h3 {
            margin-top: 0;
            color: #28a745;
        }
        .dashboard-button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #28a745;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .dashboard-button:hover {
            background-color: #218838;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .info-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
            <h1>Solicitud Aprobada</h1>
        </div>

        <div class="content">
            <p><strong>Hola {{ $displayName }},</strong></p>

            <p>Tenemos excelentes noticias para ti!</p>

            <p>Tu solicitud para crear la empresa <strong>{{ $company->name }}</strong> ha sido aprobada exitosamente.</p>
        </div>

        <div class="company-info">
            <h3>Información de tu Empresa</h3>
            <p><strong>Nombre:</strong> {{ $company->name }}</p>
            <p><strong>Código:</strong> {{ $company->company_code }}</p>
            <p><strong>Tu rol:</strong> Administrador de Empresa</p>
        </div>

        <div class="info-box">
            <strong>Como administrador de empresa, ahora puedes:</strong>
            <ul>
                <li>Gestionar usuarios de tu empresa</li>
                <li>Crear y asignar tickets de soporte</li>
                <li>Configurar las preferencias de tu empresa</li>
                <li>Acceder a reportes y estadísticas</li>
                <li>Administrar agentes de soporte</li>
            </ul>
        </div>

        <div class="button-container">
            <a href="{{ $dashboardUrl }}" class="dashboard-button">
                Acceder al Dashboard
            </a>
        </div>

        <div class="content">
            <p>Ya puedes iniciar sesión con tu cuenta existente y comenzar a utilizar todas las funcionalidades de tu empresa.</p>

            <p>Si necesitas ayuda para comenzar, no dudes en contactarnos.</p>
        </div>

        <div class="footer">
            <p>Bienvenido al ecosistema de Helpdesk System!</p>
            <p>Este es un email automático, por favor no respondas a este mensaje.</p>
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 15px 0;">
            <p>&copy; {{ date('Y') }} Helpdesk System. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
