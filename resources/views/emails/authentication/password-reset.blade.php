@component('mail::message')
# Restablece tu Contraseña

Hola {{ $displayName }},

Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. 

## Opción 1: Usar el enlace directo
Haz clic en el siguiente enlace para restablecer tu contraseña:

@component('mail::button', ['url' => $resetUrl])
Restablecer Contraseña
@endcomponent

## Opción 2: Usar el código de verificación
Si prefieres, puedes usar este código de 6 dígitos en lugar del enlace:

**{{ $resetCode }}**

## Información Importante

- Este enlace y código expiran en **{{ $expiresInHours }} horas**
- No compartas este código con nadie
- Si no solicitaste este reset, puedes ignorar este email de forma segura

## ¿Tuviste problemas?

Si no puedes hacer clic en el enlace, copia y pega esta URL en tu navegador:
{{ $resetUrl }}

---

Atentamente,  
El equipo de Helpdesk
@endcomponent
