Hola {{ $displayName }},

Lamentamos informarte que tu solicitud para crear una empresa en Helpdesk System ha sido rechazada tras una revisión de nuestro equipo.

INFORMACIÓN DE LA SOLICITUD
----------------------------
Empresa solicitada: {{ $request->company_name }}
Email de contacto: {{ $request->admin_email }}
Código de solicitud: {{ $request->request_code }}

MOTIVO DEL RECHAZO
------------------
{{ $reason }}

¿QUÉ PUEDES HACER AHORA?
- Revisar el motivo del rechazo detalladamente
- Corregir la información proporcionada
- Enviar una nueva solicitud con los datos correctos
- Contactar a nuestro equipo de soporte si tienes dudas

CONTACTO
--------
Si consideras que este rechazo fue un error o necesitas más información sobre los requisitos para crear una empresa, no dudes en contactarnos.

Email de soporte: {{ $supportEmail }}

Agradecemos tu interés en Helpdesk System y esperamos poder ayudarte en el futuro.

---

Equipo de Helpdesk System

Este es un email automático, por favor no respondas a este mensaje.

© {{ date('Y') }} Helpdesk System. Todos los derechos reservados.
