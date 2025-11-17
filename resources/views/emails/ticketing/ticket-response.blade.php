<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Response - Helpdesk</title>
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
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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

        .ticket-status {
            display: inline-block;
            background-color: #e8f4f8;
            color: #0056b3;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .agent-response {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }

        .agent-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .agent-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff, #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .agent-info h3 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .agent-info p {
            font-size: 13px;
            color: #666;
            margin: 3px 0 0 0;
        }

        .response-content {
            color: #333;
            font-size: 15px;
            line-height: 1.6;
            word-wrap: break-word;
            white-space: pre-wrap;
        }

        .conversation-history {
            background-color: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 0;
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .conversation-history-title {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .message-item {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item.agent {
            background-color: #f0f7ff;
        }

        .message-item.user {
            background-color: #ffffff;
        }

        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 13px;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .message-avatar.agent {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .message-avatar.user {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        .message-meta h4 {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .message-meta .badge {
            display: inline-block;
            background-color: #e8f4f8;
            color: #0056b3;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
            text-transform: uppercase;
        }

        .message-meta .badge.agent {
            background-color: #c3e6ff;
            color: #004085;
        }

        .message-meta .time {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }

        .message-content {
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            word-wrap: break-word;
            white-space: pre-wrap;
            margin-top: 12px;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            margin-top: 25px;
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
            color: #007bff;
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

            .agent-response {
                padding: 15px;
            }

            .message-item {
                padding: 15px;
            }

            .agent-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .agent-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>{{ $ticket->title }}</h1>
            <div class="ticket-code">{{ $ticket->ticket_code }}</div>
        </div>

        <!-- Body -->
        <div class="email-body">
            <!-- Greeting -->
            <div class="greeting">
                <p>Hi {{ $displayName }},</p>
                <p>The support team has responded to your ticket. Here's what they said:</p>
            </div>

            <!-- Ticket Status -->
            <div class="ticket-status">
                Status: {{ $ticketStatus }}
            </div>

            <!-- Agent's Current Response -->
            <div class="agent-response">
                <div class="agent-header">
                    <div class="agent-avatar">{{ substr($agentDisplayName, 0, 1) }}</div>
                    <div class="agent-info">
                        <h3>{{ $agentDisplayName }}</h3>
                        <p>Support Agent • Just now</p>
                    </div>
                </div>

                <div class="response-content">
                    {!! nl2br(e($response->content)) !!}
                </div>
            </div>

            <!-- Conversation History -->
            @if ($conversationHistory->count() > 1)
                <div class="conversation-history">
                    <div class="conversation-history-title">
                        📋 Conversation History
                    </div>

                    @foreach ($conversationHistory as $message)
                        @if (!$message['is_current_response'])
                            <div class="message-item {{ $message['is_from_agent'] ? 'agent' : 'user' }}">
                                <div class="message-header">
                                    <div class="message-avatar {{ $message['is_from_agent'] ? 'agent' : 'user' }}">
                                        {{ substr($message['author_name'], 0, 1) }}
                                    </div>
                                    <div class="message-meta">
                                        <h4>
                                            {{ $message['author_name'] }}
                                            <span class="badge {{ $message['is_from_agent'] ? 'agent' : '' }}">
                                                {{ $message['author_role'] }}
                                            </span>
                                        </h4>
                                        <div class="time">{{ $message['created_at_formatted'] }}</div>
                                    </div>
                                </div>
                                <div class="message-content">
                                    {!! nl2br(e($message['content'])) !!}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <!-- CTA Button -->
            <a href="{{ $ticketViewUrl }}" class="cta-button">
                View Ticket in Helpdesk
            </a>

            <div class="divider"></div>

            <!-- Info about this email -->
            <p style="font-size: 13px; color: #666; margin-top: 20px;">
                <strong>Why are you receiving this email?</strong><br>
                You're receiving this email because you have an open support ticket with Helpdesk. The agent has responded to your ticket and you should take action if needed.
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                © {{ now()->year }} Helpdesk. All rights reserved. <br>
                <a href="{{ config('app.frontend_url', config('app.url')) }}">Visit our website</a>
            </p>
        </div>
    </div>
</body>
</html>