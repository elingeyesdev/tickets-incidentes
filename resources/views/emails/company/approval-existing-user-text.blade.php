Hola {{ $displayName }},

Tenemos excelentes noticias para ti!

Tu solicitud para crear la empresa {{ $company->name }} ha sido aprobada exitosamente.

INFORMACIÓN DE TU EMPRESA
-------------------------
Nombre: {{ $company->name }}
Código: {{ $company->company_code }}
Tu rol: Administrador de Empresa

COMO ADMINISTRADOR DE EMPRESA, AHORA PUEDES:
- Gestionar usuarios de tu empresa
- Crear y asignar tickets de soporte
- Configurar las preferencias de tu empresa
- Acceder a reportes y estadísticas
- Administrar agentes de soporte

ACCEDER AL DASHBOARD
---------------------
Inicia sesión con tu cuenta existente y accede al dashboard en:

{{ $dashboardUrl }}

Ya puedes comenzar a utilizar todas las funcionalidades de tu empresa.

Si necesitas ayuda para comenzar, no dudes en contactarnos.

---

Bienvenido al ecosistema de Helpdesk System!

Este es un email automático, por favor no respondas a este mensaje.

© {{ date('Y') }} Helpdesk System. Todos los derechos reservados.
