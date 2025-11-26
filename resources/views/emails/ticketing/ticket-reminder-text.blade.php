HELPDESK - RECORDATORIO DE TICKET
================================

🔔 {{ $ticket->title }}
Ticket: {{ $ticket->ticket_code }}

---

Hola {{ $displayName }},

Te enviamos este recordatorio sobre tu ticket de soporte.

---

INFORMACIÓN DEL TICKET
{{ str_repeat('=', 50) }}

Código:       {{ $ticket->ticket_code }}
Título:       {{ $ticket->title }}
Estado:       {{ $ticketStatus }}
Creado:       {{ $ticket->created_at->format('d/m/Y H:i') }}

---

⚠️ RECORDATORIO AMIGABLE
{{ str_repeat('=', 50) }}

Por favor, revisa tu ticket para ver si hay actualizaciones del equipo de soporte o para proporcionar información adicional que pueda ayudarnos a resolver tu caso más rápido.

---

VER TU TICKET
{{ str_repeat('=', 50) }}

Para ver tu ticket completo y gestionarlo, visita:
{{ $ticketViewUrl }}

---

¿Por qué recibes este email?

Este es un recordatorio amigable de nuestro equipo de soporte. Queremos asegurarnos de que tu ticket se resuelva de la mejor manera posible.

---

© {{ now()->year }} Helpdesk. Todos los derechos reservados.
{{ config('app.frontend_url', config('app.url')) }}
