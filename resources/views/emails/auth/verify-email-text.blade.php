Hola {{ $displayName }},

¡Bienvenido a Helpdesk System! Estamos emocionados de tenerte con nosotros.

Para completar tu registro y comenzar a usar tu cuenta, necesitamos verificar tu dirección de correo electrónico:

{{ $user->email }}

VERIFICAR MI CUENTA
-------------------
Haz clic en el siguiente enlace para verificar tu cuenta:

{{ $verificationUrl }}

⏱️ IMPORTANTE: Este enlace expirará en {{ $expiresInHours }} horas.

¿Por qué verificamos tu email?
- Confirmar que la dirección de correo es válida
- Proteger tu cuenta contra accesos no autorizados
- Enviarte notificaciones importantes sobre tus tickets

---

Si no creaste una cuenta en Helpdesk System, puedes ignorar este correo.

Este es un email automático, por favor no respondas a este mensaje.

© {{ date('Y') }} Helpdesk System. Todos los derechos reservados.
