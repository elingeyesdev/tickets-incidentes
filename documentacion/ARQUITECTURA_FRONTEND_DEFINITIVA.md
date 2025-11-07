# 🏗️ Arquitectura Frontend Helpdesk - Definición Completa

**Versión:** 1.0 DEFINITIVA
**Fecha:** 6 de Noviembre de 2025
**Basado en:** JWT Stateless + API-First + Blade Templates + AdminLTE v3
**Estado:** Listo para implementación

---

## 📖 Tabla de Contenidos

1. [Visión General](#visión-general)
2. [Arquitectura de Tu Sistema Actual](#arquitectura-de-tu-sistema-actual)
3. [Estructura de Directorios Completa](#estructura-de-directorios-completa)
4. [Los 3 Layouts Base](#los-3-layouts-base)
5. [Las 3 Zonas del Frontend](#las-3-zonas-del-frontend)
6. [Flujo Completo de Usuario](#flujo-completo-de-usuario)
7. [Endpoints API que Consumirás](#endpoints-api-que-consumirás)
8. [Gestión de JWT en Cliente](#gestión-de-jwt-en-cliente)
9. [Middlewares Blade](#middlewares-blade)
10. [Componentes Reutilizables](#componentes-reutilizables)
11. [Decisiones Técnicas](#decisiones-técnicas)
12. [Plan de Implementación](#plan-de-implementación)

---

## 🎯 Visión General

**Tu frontend es:**
- ✅ **Stateless** (sin Laravel Sessions)
- ✅ **API-First** (consume tu API JWT)
- ✅ **Blade Templates** (no React/Vue)
- ✅ **JavaScript para formularios** (fetch API + JWT)
- ✅ **AdminLTE v3** (UI consistency)
- ✅ **Multi-dispositivo** (web + móvil mismo JWT)
- ✅ **3 zonas claramente separadas** (público, auth-flow, autenticado)

---

## 🔐 Arquitectura de Tu Sistema Actual

### JWT Flow en Tu API

```
1. Usuario hace POST /api/auth/login { email, password }
   ↓
2. Backend (AuthService → TokenService):
   - Valida credenciales
   - Crea AccessToken (JWT, 15-60 min)
   - Crea RefreshToken (BD con hash, 7-30 días)
   - Retorna ambos + user data
   ↓
3. Respuesta API:
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "a1b2c3d4e5f6...",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "profile": { firstName, lastName, avatarUrl, theme, language },
    "roleContexts": [
      { code: "agent", companyId: "uuid-empresa-1" },
      { code: "company_admin", companyId: "uuid-empresa-2" },
      { code: "user", companyId: null }
    ]
  },
  "expiresIn": 3600
}
   ↓
4. Frontend almacena en localStorage (sin sesión)
   ↓
5. Cada request incluye:
   Authorization: Bearer {accessToken}
   ↓
6. Si accessToken expira:
   POST /api/auth/refresh { refreshToken }
   → Backend rotea refresh token
   → Retorna nuevo access token
```

### Componentes Clave de Tu API

```
JWT Validation (Middleware):
├── AuthenticateJwt.php         ✅ Validar JWT en requests
├── EnsureUserHasRole.php       ✅ Validar rol específico
└── Middleware personalizado    (verificar web.auth)

JWT Generation (Services):
├── TokenService                ✅ Generar access + refresh tokens
├── AuthService                 ✅ Lógica de login/register
└── PasswordResetService        ✅ Reset de contraseña

Models:
├── User                        ✅ Usuarios + getAllRolesForJWT()
├── RefreshToken               ✅ Tokens en BD
├── UserRole                   ✅ Roles por usuario/empresa
└── Company                    ✅ Empresas

Controllers API:
├── AuthController             ✅ /api/auth/*
├── OnboardingController       ✅ /api/onboarding/*
├── UserController             ✅ /api/user/*
└── Otros por features         ✅ /api/tickets/*, etc
```

**IMPORTANTE:** Tu API es stateless y multi-dispositivo listo. ✅

---

## 📁 Estructura de Directorios Completa

```
resources/
│
├`── views/
│   │
│   ├── layouts/                       [LAYOUTSS BASE - 3 PLANTILLAS]
│   │   ├── public.blade.php           [ZONA PÚBLICA] Navbar + Footer
│   │   ├── auth-flow.blade.php        [ZONA AUTH-FLOW] Centrado sin sidebar
│   │   └── app.blade.php              [ZONA AUTENTICADA] Navbar + Sidebar + Content
│   │
│   ├── public/                        [ZONA PÚBLICA - SIN JWT]
│   │   ├── welcome.blade.php          🏠 Homepage / Landing page
│   │   ├── login.blade.php            🔑 Login form (POST /api/auth/login)
│   │   ├── register.blade.php         📝 Register form (POST /api/auth/register)
│   │   ├── register-company.blade.php 🏢 Company request (POST /api/company-requests)
│   │   ├── forgot-password.blade.php  🔐 Reset request (POST /api/password-reset/request)
│   │   └── reset-password.blade.php   🔐 Reset confirm (POST /api/password-reset/confirm)
│   │
│   ├── auth-flow/                     [ZONA AUTH-FLOW - JWT ✅, ROL ❌]
│   │   ├── role-selector.blade.php    👤 Selector de rol (si tiene múltiples)
│   │   │                                 POST /auth/select-role { roleCode, companyId }
│   │   │
│   │   └── onboarding/
│   │       ├── complete-profile.blade.php    ℹ️ Completar datos personales
│   │       │                                    PUT /api/onboarding/profile
│   │       ├── preferences.blade.php         ⚙️ Tema, idioma, notificaciones
│   │       │                                    PUT /api/onboarding/preferences
│   │       └── verify-email.blade.php        ✉️ Verificar email
│   │                                           POST /api/email-verification/send
│   │                                           POST /api/email-verification/confirm
│   │
│   ├── app/                           [ZONA AUTENTICADA - JWT ✅, ROL ✅]
│   │   │
│   │   ├── shared/                    [COMPONENTES COMPARTIDOS]
│   │   │   ├── navbar.blade.php       📊 Barra superior (user, notificaciones, logout)
│   │   │   ├── sidebar.blade.php      📌 Menu sidebar dinámico según rol
│   │   │   └── footer.blade.php       📄 Footer
│   │   │
│   │   ├── platform-admin/            [ROL: PLATFORM_ADMIN]
│   │   │   ├── dashboard.blade.php           📊 Dashboard global del sistema
│   │   │   ├── users/
│   │   │   │   ├── index.blade.php          📋 Listar usuarios
│   │   │   │   │                               GET /api/users?page=1&limit=20
│   │   │   │   ├── show.blade.php           👤 Detalles usuario
│   │   │   │   │                               GET /api/users/{id}
│   │   │   │   └── edit.blade.php           ✏️ Editar usuario
│   │   │   │                                   PUT /api/users/{id}
│   │   │   ├── companies/
│   │   │   │   ├── index.blade.php          📋 Listar empresas
│   │   │   │   │                               GET /api/companies?page=1&limit=20
│   │   │   │   ├── show.blade.php           🏢 Detalles empresa
│   │   │   │   │                               GET /api/companies/{id}
│   │   │   │   └── edit.blade.php           ✏️ Editar empresa
│   │   │   │                                   PUT /api/companies/{id}
│   │   │   └── company-requests/
│   │   │       ├── index.blade.php          ⏳ Solicitudes pendientes
│   │   │       │                               GET /api/company-requests?status=pending
│   │   │       └── show.blade.php           🔍 Revisar solicitud (aprobar/rechazar)
│   │   │                                       PUT /api/company-requests/{id}
│   │   │
│   │   ├── company-admin/             [ROL: COMPANY_ADMIN]
│   │   │   ├── dashboard.blade.php           📊 Dashboard empresa`
│   │   │   ├── company/
│   │   │   │   ├── settings.blade.php       ⚙️ Datos empresa (nombre, email, teléfono)
│   │   │   │   │                               PUT /api/companies/{id}
│   │   │   │   ├── branding.blade.php       🎨 Logo, favicon, colores
│   │   │   │   │                               PUT /api/companies/{id}/branding
│   │   │   │   └── business-hours.blade.php 🕐 Horarios de atención
│   │   │   │                                   PUT /api/companies/{id}/business-hours
│   │   │   ├── agents/
│   │   │   │   ├── index.blade.php          📋 Listar agentes
│   │   │   │   │                               GET /api/companies/{id}/agents
│   │   │   │   ├── create.blade.php         ➕ Invitar agente
│   │   │   │   │                               POST /api/companies/{id}/agents
│   │   │   │   ├── show.blade.php           👤 Detalles agente
│   │   │   │   └── edit.blade.php           ✏️ Editar agente
│   │   │   │                                   PUT /api/agents/{id}
│   │   │   ├── categories/
│   │   │   │   ├── index.blade.php          📋 Categorías tickets
│   │   │   │   │                               GET /api/companies/{id}/categories
│   │   │   │   ├── create.blade.php         ➕ Crear
│   │   │   │   │                               POST /api/companies/{id}/categories
│   │   │   │   └── edit.blade.php           ✏️ Editar
│   │   │   │                                   PUT /api/categories/{id}
│   │   │   ├── macros/
│   │   │   │   ├── index.blade.php          📋 Respuestas predefinidas
│   │   │   │   │                               GET /api/companies/{id}/macros
│   │   │   │   ├── create.blade.php         ➕ Crear
│   │   │   │   └── edit.blade.php           ✏️ Editar
│   │   │   ├── help-center/
│   │   │   │   ├── articles/
│   │   │   │   │   ├── index.blade.php      📋 Artículos
│   │   │   │   │   │                           GET /api/companies/{id}/articles
│   │   │   │   │   ├── create.blade.php     ➕ Crear
│   │   │   │   │   └── edit.blade.php       ✏️ Editar
│   │   │   │   └── categories/
│   │   │   │       └── index.blade.php      📋 Categorías help center
│   │   │   └── analytics/
│   │   │       ├── dashboard.blade.php      📊 Reportes principales
│   │   │       │                               GET /api/companies/{id}/analytics
│   │   │       ├── tickets-metrics.blade.php 📈 Métricas tickets
│   │   │       └── performance.blade.php    📊 Desempeño agentes
│   │   │
│   │   ├── agent/                     [ROL: AGENT]
│   │   │   ├── dashboard.blade.php           📊 Dashboard agente
│   │   │   ├── tickets/
│   │   │   │   ├── index.blade.php          📋 Mis tickets (filtros)
│   │   │   │   │                               GET /api/tickets?assigned=me&status=open
│   │   │   │   └── show.blade.php           🎫 Ticket detalle + responder
│   │   │   │                                   GET /api/tickets/{id}
│   │   │   │                                   POST /api/tickets/{id}/responses
│   │   │   │                                   PUT /api/tickets/{id}
│   │   │   ├── internal-notes.blade.php     📝 Mis notas internas
│   │   │   │                                   GET /api/internal-notes
│   │   │   │                                   POST /api/internal-notes
│   │   │   └── help-center/
│   │   │       └── index.blade.php          📚 Base de conocimiento
│   │   │
│   │   └── user/                      [ROL: USER - Cliente Final]
│   │       ├── dashboard.blade.php           📊 Dashboard usuario
│   │       ├── tickets/
│   │       │   ├── index.blade.php          📋 Mis tickets
│   │       │   │                               GET /api/tickets?created-by=me
│   │       │   ├── create.blade.php         ➕ Crear ticket
│   │       │   │                               POST /api/tickets
│   │       │   └── show.blade.php           🎫 Ver ticket + responder
│   │       │                                   GET /api/tickets/{id}
│   │       │                                   POST /api/tickets/{id}/responses
│   │       ├── profile/
│   │       │   └── edit.blade.php           ✏️ Mi perfil personal
│   │       │                                   PUT /api/profile
│   │       └── help-center/
│   │           └── index.blade.php          📚 Centro ayuda público
│   │
│   ├── shared/                        [COMPONENTES REUTILIZABLES]
│   │   ├── components/
│   │   │   ├── alert.blade.php              ⚠️ Alert (success, error, warning, info)
│   │   │   ├── card.blade.php               📦 Card container
│   │   │   ├── button.blade.php             🔘 Button (variants: primary, danger, etc)
│   │   │   ├── badge.blade.php              🏷️ Badge (status, priority, etc)
│   │   │   ├── modal.blade.php              🪟 Modal dialog
│   │   │   ├── form-input.blade.php         📝 Input text/email/password
│   │   │   ├── form-select.blade.php        📋 Select dropdown
│   │   │   ├── form-checkbox.blade.php      ☑️ Checkbox
│   │   │   ├── form-textarea.blade.php      📄 Textarea
│   │   │   ├── table.blade.php              📊 Table base
│   │   │   ├── pagination.blade.php         ➡️ Pagination
│   │   │   ├── breadcrumb.blade.php         🗺️ Breadcrumb navigation
│   │   │   ├── loading-spinner.blade.php    ⌛ Loading spinner
│   │   │   ├── empty-state.blade.php        📭 No data state
│   │   │   └── error-boundary.blade.php     ❌ Error display
│   │   │
│   │   └── js/
│   │       ├── auth-manager.js              🔑 Gestión JWT/localStorage
│   │       ├── api-client.js                📡 HTTP client con auto-refresh
│   │       ├── form-handler.js              📋 Manejador de forms
│   │       └── notifications.js             🔔 Notificaciones toast/alerts
│   │
│   ├── css/
│   │   ├── app.css                     💅 CSS global (imports de todo)
│   │   ├── public.css                  💅 CSS zona pública (específico)
│   │   ├── auth-flow.css               💅 CSS zona auth-flow
│   │   ├── app-authenticated.css       💅 CSS zona autenticada
│   │   ├── components.css              💅 CSS componentes (reutilizable)
│   │   ├── utilities.css               💅 Utilities (spacing, colors, etc)
│   │   └── responsive.css              💅 Responsive breakpoints
│   │
│   └── emails/                        [YA EXISTE]
│       └── ...
│
└── js/                                [JAVASCRIPT GLOBAL]
    ├── bootstrap.js                   🚀 Inicialización app
    ├── auth-manager.js                🔑 Gestión JWT (copiar a shared)
    ├── api-client.js                  📡 API client (copiar a shared)
    └── utils.js                       🛠️ Utilidades globales
```

---

## 🎨 Los 3 Layouts Base

### 1️⃣ Layout Público: `public.blade.php`

**Propósito:** Homepage, login, register, reset password (sin JWT)
**Template:** AdminLTE Navbar + Footer
**Características:** Responsive, sin sidebar, footer

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Helpdesk')</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    @yield('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                <i class="fas fa-headset"></i> <b>Help</b>Desk
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/#features">Características</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/#about">Quiénes Somos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Registrarse</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container-fluid">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <h5>Helpdesk System</h5>
                    <p class="text-muted">Solución integral de ticketing para empresas.</p>
                </div>
                <div class="col-md-4">
                    <h5>Enlaces</h5>
                    <ul class="list-unstyled text-muted">
                        <li><a href="/#features" class="text-muted">Características</a></li>
                        <li><a href="/#pricing" class="text-muted">Precios</a></li>
                        <li><a href="/#contact" class="text-muted">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contacto</h5>
                    <p class="text-muted">
                        Email: info@helpdesk.local<br>
                        Teléfono: +591 2 1234567
                    </p>
                </div>
            </div>
            <hr class="bg-secondary">
            <p class="text-center text-muted mb-0">&copy; 2025 Helpdesk System. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
    <script src="{{ asset('js/auth-manager.js') }}"></script>

    @yield('scripts')
</body>
</html>
```

---

### 2️⃣ Layout Auth-Flow: `auth-flow.blade.php`

**Propósito:** Role selector, onboarding (con JWT pero sin rol seleccionado)
**Template:** AdminLTE login-page (centrado)
**Características:** Sin sidebar, card centrada, layout minimalista

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Helpdesk - Configuración')</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.27/sweetalert2.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/auth-flow.css') }}">

    <style>
        html, body {
            height: 100%;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        .auth-flow-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .auth-flow-box {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            padding: 40px;
        }
    </style>

    @yield('styles')
</head>
<body>
    <div class="auth-flow-container">
        <div class="auth-flow-box">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.27/sweetalert2.min.js"></script>
    <script src="{{ asset('js/auth-manager.js') }}"></script>
    <script src="{{ asset('js/api-client.js') }}"></script>

    @yield('scripts')
</body>
</html>
```

---

### 3️⃣ Layout Autenticado: `app.blade.php`

**Propósito:** Todos los dashboards y vistas autenticadas
**Template:** AdminLTE full app (navbar + sidebar + content)
**Características:** Sidebar dinámico, navbar con user menu, responsive

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Helpdesk')</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app-authenticated.css') }}">

    @yield('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        @include('app.shared.navbar')

        <!-- Sidebar -->
        @include('app.shared.sidebar')

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Header con breadcrumb -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('page-title', 'Dashboard')</h1>
                        </div>
                        <div class="col-sm-6 text-end">
                            @yield('breadcrumbs')
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>

        <!-- Footer -->
        @include('app.shared.footer')
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.27/sweetalert2.min.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/auth-manager.js') }}"></script>
    <script src="{{ asset('js/api-client.js') }}"></script>
    <script src="{{ asset('js/form-handler.js') }}"></script>
    <script src="{{ asset('js/notifications.js') }}"></script>

    @yield('scripts')
</body>
</html>
```

---

## 🔓 Las 3 Zonas del Frontend

### ZONA 1: PÚBLICA ❌ JWT

**Acceso:** Cualquiera (sin token)
**Middleware:** Ninguno (o `web` para CSRF)
**Rutas:** `/login`, `/register`, `/forgot-password`, `/`
**Layout:** `public.blade.php`

**Vistas:**
- `public/welcome.blade.php` - Homepage
- `public/login.blade.php` - Form login
- `public/register.blade.php` - Form registro
- `public/register-company.blade.php` - Solicitar empresa
- `public/forgot-password.blade.php` - Pedir reset
- `public/reset-password.blade.php` - Hacer reset

**Formularios:** Todos vía JavaScript + fetch (sin @csrf tradicional)

```javascript
// Ejemplo: Form login
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const response = await ApiClient.post('/auth/login', {
        email: document.getElementById('email').value,
        password: document.getElementById('password').value
    });

    // Guardar tokens
    AuthManager.save(response);

    // Redirigir
    window.location.href = '/auth-flow/role-selector';
});
```

---

### ZONA 2: AUTH-FLOW ✅ JWT, ❌ ROL

**Acceso:** Con JWT válido (recién logueado)
**Middleware:** `auth:jwt` (pero NO `role:selected`)
**Rutas:** `/auth-flow/*`, `/onboarding/*`
**Layout:** `auth-flow.blade.php`

**Lógica:**
1. Usuario loguea → recibe JWT
2. Frontend chequea: `roleContexts.length > 1` OR `mustCompleteOnboarding`
3. Si sí → muestra role-selector o onboarding
4. Usuario selecciona rol o completa onboarding
5. `POST /auth/select-role` → actualiza contexto
6. Redirige a `/app/dashboard`

**Vistas:**
- `auth-flow/role-selector.blade.php` - Selector de rol
- `auth-flow/onboarding/complete-profile.blade.php` - Perfil
- `auth-flow/onboarding/preferences.blade.php` - Preferencias
- `auth-flow/onboarding/verify-email.blade.php` - Verificación email

**Ejemplo: Role Selector**
```blade
@extends('layouts.auth-flow')

@section('content')
<div class="text-center mb-4">
    <h2>Selecciona tu Rol</h2>
    <p class="text-muted">Tienes múltiples roles. Elige con cuál quieres comenzar.</p>
</div>

<div id="rolesList"></div>

@endsection

@section('scripts')
<script>
async function loadRoles() {
    const auth = AuthManager.get();
    const html = auth.user.roleContexts
        .map(role => `
            <div class="card mb-2 cursor-pointer role-card" onclick="selectRole('${role.code}', '${role.companyId}')">
                <div class="card-body">
                    <h5>${role.code}</h5>
                    <p class="text-muted">Empresa: ${role.companyId || 'Personal'}</p>
                </div>
            </div>
        `)
        .join('');

    document.getElementById('rolesList').innerHTML = html;
}

async function selectRole(roleCode, companyId) {
    const response = await ApiClient.post('/auth/select-role', {
        roleCode,
        companyId
    });

    AuthManager.save(response);
    window.location.href = '/app/dashboard';
}

loadRoles();
</script>
@endsection
```

---

### ZONA 3: AUTENTICADA ✅ JWT, ✅ ROL

**Acceso:** Con JWT + rol seleccionado activo
**Middleware:** `auth:jwt`, `role:selected`
**Rutas:** `/app/*` (por rol)
**Layout:** `app.blade.php`

**Vistas por rol:**
- **platform-admin/** - Admin global
- **company-admin/** - Admin empresa
- **agent/** - Agente soporte
- **user/** - Cliente final

**Cada request incluye:**
```javascript
// Header automático en ApiClient
Authorization: Bearer {accessToken}
```

**Si token expira:**
```javascript
// Automático en ApiClient.request()
POST /api/auth/refresh { refreshToken }
→ obtiene nuevo access token
→ reinenta request original
```

---

## 🔄 Flujo Completo de Usuario

```
┌──────────────────────────────────────────────────────────────┐
│ 1. USUARIO ANÓNIMO                                           │
│    GET / → view('public.welcome') [ZONA PÚBLICA]             │
│    Navbar: "Iniciar Sesión" | "Registrarse"                 │
└──────────────────────────────────────────────────────────────┘
                            ↓
┌──────────────────────────────────────────────────────────────┐
│ 2. CLICK EN "INICIAR SESIÓN"                                 │
│    GET /login → view('public.login') [ZONA PÚBLICA]          │
│    Form con: email, password, "Recuérdame"                  │
└──────────────────────────────────────────────────────────────┘
                            ↓
┌──────────────────────────────────────────────────────────────┐
│ 3. SUBMIT FORM (JavaScript, no form tradicional)             │
│    POST /api/auth/login { email, password }                  │
│                                                               │
│    Backend valida → TokenService genera JWT                  │
│    Retorna:                                                  │
│    {                                                         │
│      "accessToken": "...",                                   │
│      "refreshToken": "...",                                  │
│      "user": { ... roleContexts: [...] },                    │
│      "expiresIn": 3600                                       │
│    }                                                         │
└──────────────────────────────────────────────────────────────┘
                            ↓
┌──────────────────────────────────────────────────────────────┐
│ 4. FRONTEND GUARDA EN localStorage (NO sesión)               │
│    AuthManager.save(response)                                │
│    localStorage['helpdesk-auth'] = JSON.stringify(auth)      │
│                                                               │
│    Verifica:                                                 │
│    - ¿roleContexts.length > 1?                              │
│    - ¿mustCompleteOnboarding?                               │
└──────────────────────────────────────────────────────────────┘
                            ↓
            ┌───────────────┴───────────────┐
            │                               │
    ┌───────▼──────────┐       ┌───────────▼─────────┐
    │ Múltiples roles  │       │ Falta onboarding    │
    │ O pendiente      │       │ O verificar email   │
    └───────┬──────────┘       └─────────┬───────────┘
            │                            │
            ↓                            ↓
    GET /auth-flow/             GET /auth-flow/
    role-selector        o      onboarding/profile
    [ZONA AUTH-FLOW]            [ZONA AUTH-FLOW]
            │                            │
            └────────────┬───────────────┘
                         ↓
            ┌────────────────────────┐
            │ Usuario selecciona rol │
            │ o completa onboarding  │
            │ POST /auth/select-role │
            │ o PUT /api/onboarding/ │
            └────────┬───────────────┘
                     ↓
            Backend retorna nuevos
            access/refresh tokens
            con rol activo
                     ↓
            AuthManager.save(response)
            localStorage actualizado
                     ↓
            Redirige a GET /app/dashboard
                     │
                     └─────────────────────────────────────┐
                                                            │
                                          ┌─────────────────▼──────────┐
                                          │ 5. ZONA AUTENTICADA        │
                                          │    Middleware: auth:jwt    │
                                          │    role:selected           │
                                          │                            │
                                          │ GET /app/dashboard         │
                                          │ → Renderiza view según rol │
                                          │   app.blade.php con        │
                                          │   sidebar dinámico         │
                                          └────────────┬───────────────┘
                                                       │
                          ┌────────────────────────────┼────────────────────────────┐
                          │                            │                            │
                    ┌─────▼──────┐            ┌────────▼────┐           ┌──────────▼──┐
                    │ PLATFORM   │            │ COMPANY     │           │ AGENT      │
                    │ ADMIN      │            │ ADMIN       │           │            │
                    │ dashboard  │            │ dashboard   │           │ dashboard  │
                    │ users/     │            │ agents/     │           │ tickets/   │
                    │ companies/ │            │ settings/   │           │ notes/     │
                    │ requests/  │            │ help-center │           │            │
                    └────────────┘            └─────────────┘           └────────────┘
                                                       │
                                          ┌────────────┴──────────┐
                                          │                       │
                                     ┌────▼─────┐           ┌─────▼───┐
                                     │ USER     │           │  LOGOUT │
                                     │ dashboard│           │          │
                                     │ tickets/ │      POST /api/      │
                                     │ profile/ │      auth/logout     │
                                     └──────────┘      AuthManager     │
                                                       .logout()       │
                                                                       │
                                          Redirige a /login ◄──────────┘
```

---

## 📡 Endpoints API que Consumirás

### Zona Pública (sin JWT)

```
POST   /api/auth/login
       { email, password, rememberMe? }
       → { accessToken, refreshToken, user, expiresIn }

POST   /api/auth/register
       { email, password, passwordConfirmation, firstName, lastName, acceptsTerms, acceptsPrivacyPolicy }
       → { accessToken, refreshToken, user, expiresIn }

POST   /api/company-requests
       { companyName, adminEmail, businessDescription, ... }
       → { message, requestId }

POST   /api/password-reset/request
       { email }
       → { message }

POST   /api/password-reset/confirm
       { token, password, passwordConfirmation }
       → { message }
```

### Zona Auth-Flow (con JWT, sin rol)

```
POST   /auth/select-role
       Header: Authorization: Bearer {accessToken}
       { roleCode, companyId }
       → { accessToken, refreshToken, expiresIn }

PUT    /api/onboarding/profile
       Header: Authorization: Bearer {accessToken}
       { firstName, lastName, phoneNumber, timezone, theme, language }
       → { user }

PUT    /api/onboarding/preferences
       Header: Authorization: Bearer {accessToken}
       { theme, language, pushNotifications, notificationsTickets }
       → { user }

POST   /api/email-verification/send
       Header: Authorization: Bearer {accessToken}
       → { message }

POST   /api/email-verification/confirm
       Header: Authorization: Bearer {accessToken}
       { token }
       → { user }
```

### Zona Autenticada (con JWT + rol)

```
GET    /api/user/me
       Header: Authorization: Bearer {accessToken}
       → { user (completo) }

POST   /api/auth/refresh
       { refreshToken }
       → { accessToken, refreshToken, expiresIn }

POST   /api/auth/logout
       Header: Authorization: Bearer {accessToken}
       { refreshToken? }
       → { message }

POST   /api/auth/logout-all
       Header: Authorization: Bearer {accessToken}
       → { message }

GET    /api/users (PLATFORM_ADMIN)
GET    /api/companies (PLATFORM_ADMIN)
GET    /api/tickets (todos los roles)
POST   /api/tickets/{id}/responses (todos)
PUT    /api/tickets/{id} (agent, admin)
... (muchos más)
```

---

## 💾 Gestión de JWT en Cliente

### AuthManager.js

```javascript
/**
 * Gestión centralizada de JWT y auth state
 * Sin sesiones Laravel, todo en localStorage
 */
class AuthManager {
  static STORAGE_KEY = 'helpdesk-auth';
  static STORAGE_EXPIRE_MARGIN = 5 * 60 * 1000; // 5 minutos antes de expirar

  // Guardar después de login/refresh
  static save(response) {
    const auth = {
      accessToken: response.accessToken,
      refreshToken: response.refreshToken,
      user: response.user,
      expiresAt: Date.now() + (response.expiresIn * 1000)
    };
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(auth));

    // Event para notificar cambio de auth
    window.dispatchEvent(new CustomEvent('auth-updated', { detail: auth }));
  }

  // Obtener auth actual
  static get() {
    const stored = localStorage.getItem(this.STORAGE_KEY);
    return stored ? JSON.parse(stored) : null;
  }

  // Verificar si token está expirado (cercano a expirar)
  static isExpiringSoon() {
    const auth = this.get();
    if (!auth) return true;
    return Date.now() >= (auth.expiresAt - this.STORAGE_EXPIRE_MARGIN);
  }

  // Obtener access token para headers
  static getAccessToken() {
    const auth = this.get();
    return auth?.accessToken;
  }

  // Obtener refresh token
  static getRefreshToken() {
    const auth = this.get();
    return auth?.refreshToken;
  }

  // Obtener usuario actual
  static getUser() {
    const auth = this.get();
    return auth?.user;
  }

  // Cambiar rol activo
  static setActiveRole(roleCode, companyId) {
    const auth = this.get();
    if (auth) {
      auth.activeRole = { code: roleCode, companyId };
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(auth));
      window.dispatchEvent(new CustomEvent('role-changed', {
        detail: { roleCode, companyId }
      }));
    }
  }

  // Obtener rol activo
  static getActiveRole() {
    const auth = this.get();
    return auth?.activeRole;
  }

  // Logout (eliminar localStorage)
  static logout() {
    localStorage.removeItem(this.STORAGE_KEY);
    window.dispatchEvent(new CustomEvent('auth-cleared'));
  }

  // Verificar si está autenticado
  static isAuthenticated() {
    const auth = this.get();
    return auth?.accessToken && !this.isExpired();
  }

  // Verificar si token está expirado
  static isExpired() {
    const auth = this.get();
    if (!auth) return true;
    return Date.now() >= (auth.expiresAt || 0);
  }
}
```

### ApiClient.js

```javascript
/**
 * Cliente HTTP con auto-refresh de JWT
 * Todos los requests incluyen Authorization header
 * Si token expira, auto-refresca y reintenta
 */
class ApiClient {
  static BASE_URL = 'http://localhost:8000/api';
  static RETRY_LIMIT = 1; // Máximo 1 reintento después de refresh

  static async request(endpoint, options = {}, retryCount = 0) {
    const url = `${this.BASE_URL}${endpoint}`;
    const auth = AuthManager.get();

    // Headers base
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers
    };

    // Agregar JWT si existe
    if (auth?.accessToken) {
      headers['Authorization'] = `Bearer ${auth.accessToken}`;
    }

    // Verificar si token está casi expirado
    if (AuthManager.isExpiringSoon() && auth?.refreshToken) {
      await this.refreshToken(auth.refreshToken);
      // Actualizar header con nuevo token
      const newAuth = AuthManager.get();
      if (newAuth?.accessToken) {
        headers['Authorization'] = `Bearer ${newAuth.accessToken}`;
      }
    }

    // Hacer request
    let response = await fetch(url, {
      ...options,
      headers
    });

    // Si 401 (no autorizado) y no hemos reintentado
    if (response.status === 401 && retryCount < this.RETRY_LIMIT && auth?.refreshToken) {
      const refreshed = await this.refreshToken(auth.refreshToken);
      if (refreshed) {
        // Reintent request con nuevo token
        return this.request(endpoint, options, retryCount + 1);
      } else {
        // Refresh falló → logout
        AuthManager.logout();
        window.location.href = '/login';
        throw new Error('Sesión expirada. Por favor, inicia sesión nuevamente.');
      }
    }

    // Parsear response
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `Error ${response.status}`);
    }

    return data;
  }

  static async refreshToken(refreshToken) {
    try {
      const response = await fetch(`${this.BASE_URL}/auth/refresh`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refreshToken })
      });

      if (response.ok) {
        const data = await response.json();
        AuthManager.save(data);
        return true;
      }

      return false;
    } catch (error) {
      console.error('Refresh failed:', error);
      return false;
    }
  }

  // GET
  static get(endpoint) {
    return this.request(endpoint, { method: 'GET' });
  }

  // POST
  static post(endpoint, body) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(body)
    });
  }

  // PUT
  static put(endpoint, body) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(body)
    });
  }

  // DELETE
  static delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }

  // PATCH
  static patch(endpoint, body) {
    return this.request(endpoint, {
      method: 'PATCH',
      body: JSON.stringify(body)
    });
  }
}
```

---

## 🔐 Middlewares Blade

### 1. Reemplazar `web.auth` por `auth:jwt`

**Problema:** `web.auth` es misterioso, probablemente inicia sesión

**Solución:**
```php
// routes/web.php
// ANTES:
Route::middleware(['web.auth'])->group(function () { ... });

// DESPUÉS:
Route::middleware('auth:jwt')->group(function () { ... });
```

### 2. Agregar middleware `role:selected`

**Propósito:** Verificar que usuario tiene rol activo seleccionado

**Implementación:**
```php
// app/Http/Middleware/EnsureRoleSelected.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRoleSelected
{
    public function handle(Request $request, Closure $next)
    {
        // Obtener JWT decodificado
        $jwt = auth('jwt')->payload();

        // Verificar si tiene rol activo
        // Podrías almacenar en SessionId del JWT o verificar request header
        // Por ahora, simplemente permite si tiene JWT válido

        return $next($request);
    }
}
```

### 3. Agregar middleware `role:admin`

```php
// app/Http/Middleware/EnsureUserRole.php (mejorado)
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        $jwt = auth('jwt')->payload();

        // Verificar si role está en JWT
        $userRoles = $jwt->roles ?? [];
        $hasRole = collect($userRoles)->pluck('code')->contains($role);

        if (!$hasRole) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
```

---

## 🧩 Componentes Reutilizables

### Alert Component

```blade
{{-- resources/views/shared/components/alert.blade.php --}}
@props(['type' => 'info', 'message', 'dismissible' => true])

<div class="alert alert-{{ $type }}{{ $dismissible ? ' alert-dismissible fade show' : '' }} role="alert">
    @switch($type)
        @case('success')
            <i class="fas fa-check-circle me-2"></i>
            @break
        @case('error')
            <i class="fas fa-exclamation-circle me-2"></i>
            @break
        @case('warning')
            <i class="fas fa-exclamation-triangle me-2"></i>
            @break
        @default
            <i class="fas fa-info-circle me-2"></i>
    @endswitch

    {{ $message }}

    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    @endif
</div>

{{-- Uso: --}}
{{-- <x-alert type="success" message="¡Guardado exitosamente!" /> --}}
```

### Card Component

```blade
{{-- resources/views/shared/components/card.blade.php --}}
@props(['title' => null, 'footer' => null])

<div class="card">
    @if($title)
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>

    @if($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>

{{-- Uso: --}}
{{-- <x-card title="Mi Tarjeta">
      Contenido aquí
    </x-card> --}}
```

### Form Input Component

```blade
{{-- resources/views/shared/components/form-input.blade.php --}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'error' => null,
    'icon' => null
])

<div class="mb-3">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

    <div class="input-group">
        @if($icon)
            <span class="input-group-text">
                <i class="fas fa-{{ $icon }}"></i>
            </span>
        @endif

        <input
            type="{{ $type }}"
            class="form-control @if($error) is-invalid @endif"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @if($required) required @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
        >
    </div>

    @if($error)
        <div class="invalid-feedback d-block">
            {{ $error }}
        </div>
    @endif
</div>
```

---

## 🎯 Decisiones Técnicas Finales

### ✅ JWT en localStorage (No HttpOnly Cookies)

**Por qué:**
- Frontend necesita acceso para refresh automático
- Multi-tab sync (mismo usuario 2 pestañas)
- Compatible con móvil (mismo localStorage)

**Mitigation:**
- CSP headers fuertes en servidor
- HTTPS siempre en producción
- Validación CSRF en formularios si es necesario

### ✅ Blade Templates (No React/Vue)

**Por qué:**
- Tu API ya soporta Blade
- AdminLTE integrado perfecto
- Menos JavaScript complexity
- Compatible con progressivo enhancement

**Cuando migrar a React:**
- Si quieres SPA interactiva
- Mobile app con React Native
- Real-time features (WebSockets)

### ✅ Sin Laravel Sessions

**Por qué:**
- Stateless = escalable
- Multi-dispositivo nativo
- Mobile API ready (mismo JWT)
- Refresh token rotation seguro

**Tradeoff:**
- No hay sesión servidor (pero JWT válida igual)
- Logout no es instantáneo (pero blacklist en caché)

### ✅ Auto-refresh de Access Token

**Implementación:**
```javascript
// En ApiClient, antes de cada request:
if (AuthManager.isExpiringSoon() && auth?.refreshToken) {
    await this.refreshToken(auth.refreshToken);
}
```

**Beneficio:** Usuario nunca ve "sesión expirada"

---

## 📋 Plan de Implementación (Orden)

### Fase 1: Configuración Base (1-2 horas)

- [ ] Crear los 3 layouts (public, auth-flow, app)
- [ ] Crear AuthManager.js
- [ ] Crear ApiClient.js
- [ ] Verificar middleware `auth:jwt` está registrado
- [ ] Reemplazar `web.auth` por `auth:jwt` en routes

### Fase 2: Zona Pública (2-3 horas)

- [ ] Crear `public/welcome.blade.php`
- [ ] Crear `public/login.blade.php` (con JavaScript fetch)
- [ ] Crear `public/register.blade.php`
- [ ] Crear `public/register-company.blade.php`
- [ ] Crear `public/forgot-password.blade.php`
- [ ] Crear `public/reset-password.blade.php`
- [ ] Crear CSS zona pública

### Fase 3: Zona Auth-Flow (2-3 horas)

- [ ] Crear `auth-flow/role-selector.blade.php`
- [ ] Crear `auth-flow/onboarding/complete-profile.blade.php`
- [ ] Crear `auth-flow/onboarding/preferences.blade.php`
- [ ] Crear `auth-flow/onboarding/verify-email.blade.php`
- [ ] Crear CSS zona auth-flow

### Fase 4: Zona Autenticada - Componentes (3-4 horas)

- [ ] Crear `app/shared/navbar.blade.php`
- [ ] Crear `app/shared/sidebar.blade.php` (dinámico por rol)
- [ ] Crear `app/shared/footer.blade.php`
- [ ] Crear componentes reutilizables en `shared/components/`
- [ ] Crear CSS zona autenticada

### Fase 5: Vistas por Rol (8-12 horas)

- [ ] **Platform Admin:** dashboard, users, companies, requests
- [ ] **Company Admin:** dashboard, settings, agents, categories, macros, help-center, analytics
- [ ] **Agent:** dashboard, tickets, internal-notes, help-center
- [ ] **User:** dashboard, tickets, profile, help-center

### Fase 6: Testing & Refinamiento (4-6 horas)

- [ ] Testing de flujos login → roles → dashboards
- [ ] Testing de auto-refresh de JWT
- [ ] Testing de logout
- [ ] Testing de responsive en móvil
- [ ] Testing de cambio de rol
- [ ] Bug fixes y optimizaciones

---

## 📊 Resumen Estructura Final

```
resources/views/                          (Este documento define TODO esto)
│
├── layouts/                              [3 layouts base]
│   ├── public.blade.php                 ✅
│   ├── auth-flow.blade.php              ✅
│   └── app.blade.php                    ✅
│
├── public/                              [6 vistas - ZONA PÚBLICA]
│   ├── welcome.blade.php
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── register-company.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
│
├── auth-flow/                           [4 vistas - ZONA AUTH-FLOW]
│   ├── role-selector.blade.php
│   └── onboarding/
│       ├── complete-profile.blade.php
│       ├── preferences.blade.php
│       └── verify-email.blade.php
│
├── app/                                 [Múltiples vistas - ZONA AUTENTICADA]
│   ├── shared/
│   │   ├── navbar.blade.php
│   │   ├── sidebar.blade.php
│   │   ├── footer.blade.php
│   │   └── components/
│   │       ├── alert.blade.php
│   │       ├── card.blade.php
│   │       ├── button.blade.php
│   │       └── ... (10+ componentes)
│   │
│   ├── platform-admin/                 [6+ vistas]
│   ├── company-admin/                  [10+ vistas]
│   ├── agent/                          [5+ vistas]
│   └── user/                           [5+ vistas]
│
├── shared/                              [Componentes globales]
│   ├── components/
│   └── js/
│       ├── auth-manager.js              ✅
│       ├── api-client.js                ✅
│       ├── form-handler.js
│       └── notifications.js
│
├── css/                                 [7 archivos CSS]
│   ├── app.css                          (imports)
│   ├── public.css
│   ├── auth-flow.css
│   ├── app-authenticated.css
│   ├── components.css
│   ├── utilities.css
│   └── responsive.css
│
└── emails/                              [YA EXISTE]
    └── ...

TOTAL: ~50-70 archivos .blade.php a crear
       ~7 archivos CSS
       ~2-3 archivos JavaScript
```

---

## ✨ Conclusión

**Tu frontend estará:**
- ✅ **Estateless** (sin sesiones Laravel)
- ✅ **API-First** (consume tu JWT)
- ✅ **Moderno** (admin-lte + blade + javascript)
- ✅ **Escalable** (multi-dispositivo, multi-rol)
- ✅ **Seguro** (JWT + refresh rotation + blacklist)
- ✅ **Mobile-Ready** (mismo JWT que app móvil)

**Listo para:**
- Web (Blade + JavaScript)
- Mobile (React Native con mismo JWT)
- Futuro (React SPA si quieres)

---

**Documento completado:** 6 de Noviembre de 2025
**Basado en:** Tu arquitectura JWT real + Blade + AdminLTE v3
**Estado:** ✅ Listo para implementación inmediata
