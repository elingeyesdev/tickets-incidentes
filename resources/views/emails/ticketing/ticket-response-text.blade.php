HELPDESK SUPPORT TICKET RESPONSE
================================

{{ $ticket->title }}
Ticket: {{ $ticket->ticket_code }}

---

Hi {{ $displayName }},

The support team has responded to your ticket. Here's what they said:

---

AGENT RESPONSE
{{ str_repeat('=', 50) }}

From: {{ $agentDisplayName }}
Date: {{ now()->format('M d, Y \a\t H:i') }}

{{ $response->content }}

---

STATUS
{{ str_repeat('=', 50) }}
{{ $ticketStatus }}

---

@if ($conversationHistory->count() > 1)
CONVERSATION HISTORY
{{ str_repeat('=', 50) }}

@foreach ($conversationHistory as $message)
    @if (!$message['is_current_response'])
[{{ strtoupper($message['author_role']) }}] {{ $message['author_name'] }} - {{ $message['created_at_formatted'] }}
{{ str_repeat('-', 50) }}
{{ $message['content'] }}

    @endif
@endforeach
@endif

---

VIEW YOUR TICKET
{{ str_repeat('=', 50) }}

To view your full ticket and manage it, visit:
{{ $ticketViewUrl }}

---

Why are you receiving this email?

You're receiving this email because you have an open support ticket with Helpdesk. The agent has responded to your ticket and you should take action if needed.

---

© {{ now()->year }} Helpdesk. All rights reserved.
{{ config('app.frontend_url', config('app.url')) }}