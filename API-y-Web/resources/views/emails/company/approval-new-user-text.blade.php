Hola {{ $displayName }},

Tenemos excelentes noticias! Tu solicitud para crear la empresa {{ $company->name }} ha sido aprobada exitosamente.

Hemos creado una cuenta para ti en nuestro sistema. A continuación encontrarás tus credenciales de acceso temporales.

TUS CREDENCIALES DE ACCESO
---------------------------
Email: {{ $user->email }}
Password Temporal: {{ $temporaryPassword }}

⚠️ IMPORTANTE - SEGURIDAD:
- Este password es TEMPORAL y expira en {{ $expiresInDays }} días
- Deberás cambiarlo en tu primer inicio de sesión
- No compartas este password con nadie
- Guarda este email en un lugar seguro hasta que cambies tu password

INFORMACIÓN DE TU EMPRESA
--------------------------
Nombre: {{ $company->name }}
Código: {{ $company->company_code }}
Tu rol: Administrador de Empresa

COMO ADMINISTRADOR DE EMPRESA, AHORA PUEDES:
- Gestionar usuarios de tu empresa
- Crear y asignar tickets de soporte
- Configurar las preferencias de tu empresa
- Acceder a reportes y estadísticas
- Administrar agentes de soporte

INICIAR SESIÓN
--------------
Accede al sistema en: {{ $loginUrl }}

PRÓXIMOS PASOS:
1. Haz clic en el enlace de arriba
2. Ingresa tu email y el password temporal
3. El sistema te pedirá cambiar tu password
4. Completa tu perfil y preferencias
5. Comienza a utilizar Helpdesk!

Si necesitas ayuda para comenzar, no dudes en contactarnos.

---

Bienvenido al ecosistema de Helpdesk System!

Este es un email automático, por favor no respondas a este mensaje.

© {{ date('Y') }} Helpdesk System. Todos los derechos reservados.
