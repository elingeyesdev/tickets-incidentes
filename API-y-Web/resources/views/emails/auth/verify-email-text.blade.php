Hola {{ $displayName }},

¡Bienvenido a Helpdesk System! Estamos emocionados de tenerte con nosotros.

Para completar tu registro y comenzar a usar tu cuenta, necesitamos verificar tu dirección de correo electrónico:

{{ $user->email }}

OPCIÓN 1: USAR EL ENLACE DIRECTO
---------------------------------
Haz clic en el siguiente enlace para verificar tu cuenta:

{{ $verificationUrl }}

OPCIÓN 2: USAR EL CÓDIGO DE VERIFICACIÓN
-----------------------------------------
Si prefieres, puedes usar este código de 6 dígitos:

{{ $verificationCode }}

⏱️ INFORMACIÓN IMPORTANTE:
- Este enlace y código expiran en {{ $expiresInHours }} horas
- No compartas este código con nadie
- Si no creaste una cuenta, puedes ignorar este email de forma segura

¿Por qué verificamos tu email?
- Confirmar que la dirección de correo es válida
- Proteger tu cuenta contra accesos no autorizados
- Enviarte notificaciones importantes sobre tus tickets

---

Si no creaste una cuenta en Helpdesk System, puedes ignorar este correo.

Este es un email automático, por favor no respondas a este mensaje.

Atentamente,
El equipo de Helpdesk System

© {{ date('Y') }} Helpdesk System. Todos los derechos reservados.

