<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Reminder - Helpdesk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .email-header {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .email-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .email-header .ticket-code {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }

        .email-body {
            padding: 30px 20px;
        }

        .greeting {
            margin-bottom: 25px;
        }

        .greeting p {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
        }

        .reminder-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #28a745, #1e7e34);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .ticket-info {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }

        .ticket-info h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            width: 120px;
            flex-shrink: 0;
        }

        .info-value {
            color: #333;
        }

        .ticket-status {
            display: inline-block;
            background-color: #d4edda;
            color: #155724;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        .reminder-message {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }

        .reminder-message p {
            font-size: 15px;
            color: #856404;
            margin-bottom: 10px;
        }

        .reminder-message p:last-child {
            margin-bottom: 0;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            margin-top: 10px;
            margin-bottom: 30px;
            transition: opacity 0.3s ease;
        }

        .cta-button:hover {
            opacity: 0.9;
            text-decoration: none;
            color: white;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .email-footer a {
            color: #28a745;
            text-decoration: none;
        }

        .email-footer a:hover {
            text-decoration: underline;
        }

        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }

        @media (max-width: 600px) {
            .email-container {
                max-width: 100% !important;
            }

            .email-header {
                padding: 20px 15px;
            }

            .email-header h1 {
                font-size: 20px;
            }

            .email-body {
                padding: 20px 15px;
            }

            .ticket-info {
                padding: 15px;
            }

            .info-row {
                flex-direction: column;
            }

            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="reminder-icon">🔔</div>
            <h1>Recordatorio de Ticket</h1>
            <div class="ticket-code">{{ $ticket->ticket_code }}</div>
        </div>

        <!-- Body -->
        <div class="email-body">
            <!-- Greeting -->
            <div class="greeting">
                <p>Hola {{ $displayName }},</p>
                <p>Te enviamos este recordatorio sobre tu ticket de soporte.</p>
            </div>

            <!-- Ticket Information -->
            <div class="ticket-info">
                <h3>📋 Información del Ticket</h3>
                <div class="info-row">
                    <span class="info-label">Código:</span>
                    <span class="info-value">{{ $ticket->ticket_code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Título:</span>
                    <span class="info-value">{{ $ticket->title }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value">
                        <span class="ticket-status">{{ $ticketStatus }}</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Creado:</span>
                    <span class="info-value">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <!-- Reminder Message -->
            <div class="reminder-message">
                <p><strong>⚠️ Este es un recordatorio amigable:</strong></p>
                <p>Por favor, revisa tu ticket para ver si hay actualizaciones del equipo de soporte o para proporcionar información adicional que pueda ayudarnos a resolver tu caso más rápido.</p>
            </div>

            <!-- CTA Button -->
            <a href="{{ $ticketViewUrl }}" class="cta-button">
                Ver Ticket en Helpdesk
            </a>

            <div class="divider"></div>

            <!-- Info about this email -->
            <p style="font-size: 13px; color: #666; margin-top: 20px;">
                <strong>¿Por qué recibes este email?</strong><br>
                Este es un recordatorio amigable de nuestro equipo de soporte. Queremos asegurarnos de que tu ticket se resuelva de la mejor manera posible.
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                © {{ now()->year }} Helpdesk. Todos los derechos reservados. <br>
                <a href="{{ config('app.frontend_url', config('app.url')) }}">Visita nuestro sitio web</a>
            </p>
        </div>
    </div>
</body>
</html>
