# 📐 Estructura Frontend Helpdesk - Consumidor de API JWT

**Última actualización:** 6 de Noviembre de 2025
**Versión:** 1.0
**Autor:** Claude Code + Luke
**Estado:** Propuesta - En Review
**Enfoque:** API-First, Stateless JWT, SPA-Ready

---

## 🎯 Visión General

El frontend es un **consumidor stateless de API JWT**. No hay Laravel Sessions. Todo está basado en:
- **JWT Access Token** (corta duración, en localStorage)
- **Refresh Token** (larga duración, en localStorage)
- **Role Contexts** (múltiples roles por usuario en el JWT)
- **Selectores de rol dinámicos** cuando tiene múltiples roles

**No es Blade tradicional con sessions.** Es una arquitectura moderna, lista para móvil y web.

---

## 🔐 Cómo Funciona tu JWT

### Flujo de Login

```
Usuario hace POST /api/auth/login { email, password }
    ↓
Backend: TokenService.generateAccessToken()
    ↓
Retorna:
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "a1b2c3d4e5f6...",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "profile": { firstName, lastName, avatarUrl, theme, language },
    "roleContexts": [
      { code: "agent", company_id: "uuid-empresa-1" },
      { code: "company_admin", company_id: "uuid-empresa-2" },
      { code: "user", company_id: null }
    ]
  },
  "expiresIn": 3600
}
```

### Estructura del Access Token (JWT)

```json
{
  "iss": "helpdesk.local",
  "aud": "helpdesk-app",
  "iat": 1699276800,
  "exp": 1699280400,
  "sub": "user-uuid",
  "user_id": "user-uuid",
  "email": "user@example.com",
  "session_id": "refresh-token-uuid",
  "roles": [
    { "code": "agent", "company_id": "uuid-empresa-1" },
    { "code": "company_admin", "company_id": "uuid-empresa-2" },
    { "code": "user", "company_id": null }
  ]
}
```

### ¿Qué significa?

- **accessToken** - JWT firmado, válido 15-60 minutos
- **refreshToken** - Token largo, válido 7-30 días, guardado en BD con hash
- **roleContexts** - Array de roles con su contexto de empresa
- **Stateless** - No hay sesión servidor, todo en el JWT

---

## 📱 Flujo de Usuario (sin Sessions)

```
┌─────────────────────────────────────────────────────────────┐
│ USUARIO ANÓNIMO                                             │
│ GET / → Zona Pública (sin JWT)                              │
└─────────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────────┐
│ USUARIO HACE LOGIN                                          │
│ POST /api/auth/login { email, password }                    │
└─────────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────────┐
│ RESPUESTA API                                               │
│ { accessToken, refreshToken, user, roleContexts }           │
│ Frontend guarda en localStorage (NO en session!)             │
└─────────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────────┐
│ ¿Tiene múltiples roles?                                     │
├─────────────────────────────────────────────────────────────┤
│ Sí:  GET /auth-flow/role-selector                           │
│      User selecciona rol                                    │
│      POST /auth/select-role { roleCode, companyId }        │
│      Backend valida y retorna nuevos tokens                │
│ No:  Redirige directo a /app/dashboard                     │
└─────────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────────┐
│ ¿Necesita completar onboarding?                             │
├─────────────────────────────────────────────────────────────┤
│ Sí:  GET /auth-flow/onboarding/*                            │
│      Completa: perfil, preferencias, verifica email         │
│      POST /api/onboarding/complete                         │
│ No:  Sigue al dashboard                                    │
└─────────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────────┐
│ ZONA AUTENTICADA                                            │
│ GET /app/dashboard                                          │
│ Header: Authorization: Bearer {accessToken}                │
│ Middleware: auth:jwt, role:selected                        │
└─────────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────────┐
│ NAVEGACIÓN EN APP                                           │
│ Todas las requests incluyen JWT en header                   │
│ Si token expira: POST /api/auth/refresh                    │
│ Backend rotea refresh token automáticamente                │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 Estructura de Directorios (Revisada)

```
resources/
├── views/
│   ├── layouts/
│   │   ├── public.blade.php           [SIN JWT] Layout página normal
│   │   ├── auth-flow.blade.php        [CON JWT, SIN ROL] Layout centrado
│   │   └── app.blade.php              [CON JWT + ROL] Layout con sidebar
│   │
│   ├── public/                        [ZONA PÚBLICA - Sin JWT]
│   │   ├── welcome.blade.php          🏠 Homepage
│   │   ├── login.blade.php            🔑 Form login (POST /api/auth/login)
│   │   ├── register.blade.php         📝 Form registro (POST /api/auth/register)
│   │   ├── register-company.blade.php 🏢 Solicitar empresa (POST /api/company-requests)
│   │   ├── forgot-password.blade.php  🔐 Form solicitar reset (POST /api/password-reset/request)
│   │   └── reset-password.blade.php   🔐 Form reset con token (POST /api/password-reset/confirm)
│   │
│   ├── auth-flow/                     [ZONA AUTH FLOW - JWT ✅, Rol ❌]
│   │   ├── role-selector.blade.php    👤 Seleccionar rol (POST /auth/select-role)
│   │   └── onboarding/
│   │       ├── complete-profile.blade.php    ℹ️ Llenar nombre, teléfono, zona horaria
│   │       ├── preferences.blade.php         ⚙️ Tema, idioma, notificaciones
│   │       └── verify-email.blade.php        ✉️ Verificar email
│   │
│   └── app/                           [ZONA AUTENTICADA - JWT ✅, Rol ✅]
│       ├── shared/
│       │   ├── navbar.blade.php       📊 User info, notificaciones, logout
│       │   ├── sidebar.blade.php      📌 Menu dinámico según rol activo
│       │   └── footer.blade.php       📄 Footer
│       │
│       ├── platform-admin/            [👤 ROL: PLATFORM_ADMIN]
│       │   ├── dashboard.blade.php           📊 Dashboard global
│       │   ├── users/
│       │   │   ├── index.blade.php          📋 Listar usuarios (GET /api/users)
│       │   │   ├── show.blade.php           👤 Usuario detalle (GET /api/users/{id})
│       │   │   └── edit.blade.php           ✏️ Editar (PUT /api/users/{id})
│       │   ├── companies/
│       │   │   ├── index.blade.php          📋 Listar empresas (GET /api/companies)
│       │   │   ├── show.blade.php           🏢 Empresa detalle (GET /api/companies/{id})
│       │   │   └── edit.blade.php           ✏️ Editar (PUT /api/companies/{id})
│       │   └── company-requests/
│       │       ├── index.blade.php          ⏳ Solicitudes (GET /api/company-requests)
│       │       └── show.blade.php           🔍 Revisar (PUT /api/company-requests/{id})
│       │
│       ├── company-admin/             [👤 ROL: COMPANY_ADMIN]
│       │   ├── dashboard.blade.php           📊 Dashboard empresa
│       │   ├── company/
│       │   │   ├── settings.blade.php       ⚙️ Datos empresa (PUT /api/companies/{id})
│       │   │   ├── branding.blade.php       🎨 Logo, colores (PUT /api/companies/{id}/branding)
│       │   │   └── business-hours.blade.php 🕐 Horarios (PUT /api/companies/{id}/business-hours)
│       │   ├── agents/
│       │   │   ├── index.blade.php          📋 Agentes (GET /api/companies/{id}/agents)
│       │   │   ├── create.blade.php         ➕ Invitar (POST /api/companies/{id}/agents)
│       │   │   ├── show.blade.php           👤 Detalle (GET /api/agents/{id})
│       │   │   └── edit.blade.php           ✏️ Editar (PUT /api/agents/{id})
│       │   ├── categories/
│       │   │   ├── index.blade.php          📋 Categorías (GET /api/companies/{id}/categories)
│       │   │   ├── create.blade.php         ➕ Crear (POST)
│       │   │   └── edit.blade.php           ✏️ Editar (PUT)
│       │   ├── macros/
│       │   │   ├── index.blade.php          📋 Macros (GET /api/companies/{id}/macros)
│       │   │   ├── create.blade.php         ➕ Crear (POST)
│       │   │   └── edit.blade.php           ✏️ Editar (PUT)
│       │   ├── help-center/
│       │   │   ├── articles/
│       │   │   │   ├── index.blade.php      📋 Artículos (GET /api/companies/{id}/articles)
│       │   │   │   ├── create.blade.php     ➕ Crear (POST)
│       │   │   │   └── edit.blade.php       ✏️ Editar (PUT)
│       │   │   └── categories/
│       │   │       └── index.blade.php      📋 Categorías help center
│       │   └── analytics/
│       │       ├── dashboard.blade.php      📊 Reportes (GET /api/companies/{id}/analytics)
│       │       └── tickets-metrics.blade.php 📈 Métricas tickets
│       │
│       ├── agent/                     [👤 ROL: AGENT]
│       │   ├── dashboard.blade.php           📊 Dashboard agente
│       │   ├── tickets/
│       │   │   ├── index.blade.php          📋 Mis tickets (GET /api/tickets?filter=assigned-to-me)
│       │   │   └── show.blade.php           🎫 Ticket detalle + responder (GET/PUT /api/tickets/{id})
│       │   ├── internal-notes.blade.php     📝 Mis notas (GET /api/internal-notes)
│       │   └── help-center/
│       │       └── index.blade.php          📚 Base conocimiento (GET /api/help-center)
│       │
│       └── user/                      [👤 ROL: USER]
│           ├── dashboard.blade.php           📊 Dashboard usuario
│           ├── tickets/
│           │   ├── index.blade.php          📋 Mis tickets (GET /api/tickets?filter=created-by-me)
│           │   ├── create.blade.php         ➕ Crear ticket (POST /api/tickets)
│           │   └── show.blade.php           🎫 Ticket detalle (GET/PUT /api/tickets/{id})
│           ├── profile/
│           │   └── edit.blade.php           ✏️ Mi perfil (PUT /api/profile)
│           └── help-center/
│               └── index.blade.php          📚 Centro ayuda público (GET /api/help-center/public)
│
├── css/
│   ├── app.css                        💅 CSS global
│   ├── public.css                     💅 CSS zona pública
│   └── auth-flow.css                  💅 CSS zona auth-flow
│
└── emails/                            (Ya existe)
    └── ...
```

---

## 🌐 Endpoints API que Consume el Frontend

### **Zona Pública**

```
POST   /api/auth/login
       Body: { email, password, rememberMe? }
       Response: { accessToken, refreshToken, user, expiresIn }

POST   /api/auth/register
       Body: { email, password, passwordConfirmation, firstName, lastName, acceptsTerms, acceptsPrivacyPolicy }
       Response: { accessToken, refreshToken, user, ... }

POST   /api/company-requests
       Body: { companyName, adminEmail, businessDescription, website, industryType, ... }
       Response: { request, message }

POST   /api/password-reset/request
       Body: { email }
       Response: { message }

POST   /api/password-reset/confirm
       Body: { token, password, passwordConfirmation }
       Response: { message }
```

### **Zona Auth Flow**

```
GET    /api/auth/status
       Header: Authorization: Bearer {accessToken}
       Response: { user, roleContexts, mustSelectRole, mustCompleteOnboarding }

POST   /auth/select-role
       Header: Authorization: Bearer {accessToken}
       Body: { roleCode, companyId }
       Response: { accessToken, refreshToken, user }

PUT    /api/onboarding/profile
       Header: Authorization: Bearer {accessToken}
       Body: { firstName, lastName, phoneNumber, timezone, ... }
       Response: { user }

PUT    /api/onboarding/preferences
       Header: Authorization: Bearer {accessToken}
       Body: { theme, language, pushNotifications, ... }
       Response: { user }

POST   /api/email-verification/send
       Header: Authorization: Bearer {accessToken}
       Response: { message }

POST   /api/email-verification/confirm
       Header: Authorization: Bearer {accessToken}
       Body: { token }
       Response: { user }
```

### **Zona Autenticada**

```
GET    /api/user/me
       Header: Authorization: Bearer {accessToken}
       Response: { user con todos los datos }

POST   /api/auth/refresh
       Body: { refreshToken }
       Response: { accessToken, refreshToken, expiresIn }

POST   /api/auth/logout
       Header: Authorization: Bearer {accessToken}
       Body: { refreshToken? }
       Response: { message }

POST   /api/auth/logout-all
       Header: Authorization: Bearer {accessToken}
       Response: { message }

GET    /api/tickets
       Header: Authorization: Bearer {accessToken}
       Query: ?filter=assigned-to-me&status=open&page=1
       Response: { data: [...], pagination }

GET    /api/tickets/{id}
       Header: Authorization: Bearer {accessToken}
       Response: { ticket con respuestas }

POST   /api/tickets/{id}/responses
       Header: Authorization: Bearer {accessToken}
       Body: { content, attachmentIds? }
       Response: { response }

PUT    /api/tickets/{id}
       Header: Authorization: Bearer {accessToken}
       Body: { status, categoryId?, ... }
       Response: { ticket }

... (y muchos más según roles)
```

---

## 💾 Estado en Cliente (localStorage)

### Estructura Recomendada

```javascript
// localStorage['helpdesk-auth'] - JSON stringificado
{
  accessToken: "eyJ0eXAiOiJKV1QiLCJhbGc...",
  refreshToken: "a1b2c3d4e5f6g7h8i9j0...",
  user: {
    id: "uuid",
    email: "user@example.com",
    profile: {
      firstName: "Juan",
      lastName: "Pérez",
      avatarUrl: "https://...",
      theme: "light",
      language: "es"
    },
    roleContexts: [
      { code: "agent", companyId: "uuid-empresa-1" },
      { code: "company_admin", companyId: "uuid-empresa-2" },
      { code: "user", companyId: null }
    ]
  },
  activeRole: {
    code: "agent",
    companyId: "uuid-empresa-1"
  },
  expiresAt: 1699280400
}
```

### Helper JavaScript para Gestionar Auth

```javascript
class AuthManager {
  static STORAGE_KEY = 'helpdesk-auth';

  // Guardar después de login
  static save(response) {
    const auth = {
      accessToken: response.accessToken,
      refreshToken: response.refreshToken,
      user: response.user,
      expiresAt: Date.now() + (response.expiresIn * 1000)
    };
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(auth));
  }

  // Obtener auth actual
  static get() {
    const auth = localStorage.getItem(this.STORAGE_KEY);
    return auth ? JSON.parse(auth) : null;
  }

  // Verificar si token está expirado
  static isExpired() {
    const auth = this.get();
    if (!auth) return true;
    return Date.now() >= (auth.expiresAt || 0);
  }

  // Obtener access token para headers
  static getAccessToken() {
    return this.get()?.accessToken;
  }

  // Seleccionar rol activo
  static setActiveRole(roleCode, companyId) {
    const auth = this.get();
    if (auth) {
      auth.activeRole = { code: roleCode, companyId };
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(auth));
    }
  }

  // Logout (eliminar localStorage)
  static logout() {
    localStorage.removeItem(this.STORAGE_KEY);
  }
}
```

### Helper para Requests API

```javascript
class ApiClient {
  static BASE_URL = 'http://localhost:8000/api';

  static async request(endpoint, options = {}) {
    const url = `${this.BASE_URL}${endpoint}`;
    const auth = AuthManager.get();

    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers
    };

    // Agregar JWT si existe
    if (auth?.accessToken) {
      headers['Authorization'] = `Bearer ${auth.accessToken}`;
    }

    let response = await fetch(url, {
      ...options,
      headers
    });

    // Si token expirado, intentar refresh
    if (response.status === 401 && auth?.refreshToken) {
      const refreshed = await this.refreshToken(auth.refreshToken);
      if (refreshed) {
        // Reintentar request original con nuevo token
        headers['Authorization'] = `Bearer ${AuthManager.getAccessToken()}`;
        response = await fetch(url, { ...options, headers });
      }
    }

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'API Error');
    }

    return await response.json();
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
}
```

---

## 🔄 Flujo de Token Refresh (Automático)

```
Usuario hace request a /api/tickets
    ↓
Frontend incluye: Authorization: Bearer {accessToken}
    ↓
Si token está EXPIRADO en cliente:
    ↓
ANTES de hacer request:
    ↓
POST /api/auth/refresh { refreshToken }
    ↓
Backend:
  1. Valida refreshToken
  2. Crea nuevo accessToken
  3. Rotea refreshToken (crea nuevo, invalida viejo)
  4. Retorna: { accessToken, refreshToken, expiresIn }
    ↓
Frontend:
  1. Guarda nuevos tokens en localStorage
  2. Reintenta request original con nuevo token
    ↓
Request se completa exitosamente
```

---

## 🛡️ Seguridad: localStorage vs sessionStorage

### localStorage (Recomendado para esta arquitectura)
✅ Persiste entre pestañas/ventanas (multi-tab sync)
✅ Persiste si cierras navegador (user vuelve logueado)
✅ Accesible desde cualquier parte del app
❌ Vulnerable a XSS (pero mitigable con CSP)

### sessionStorage
❌ Se pierde al cerrar pestaña
❌ No sincroniza entre pestañas (problema para multi-tab)

### HttpOnly Cookies (Mejor seguridad)
✅ No accesible desde JavaScript (protege de XSS)
✅ Enviado automático en requests
❌ CSRF vulnerable (mitigable con CSRF tokens)
❌ Más complejo de implementar

**Recomendación para tu proyecto:**
- **Zona Pública**: localStorage (credenciales básicas para testear)
- **Producción móvil**: HttpOnly cookies (máxima seguridad)
- **Producción web**: localStorage + CSP headers fuertes

---

## 🚀 Flujo Completo Login → Dashboard

```
1. Usuario en GET /login
   ↓
2. Completa form y hace POST /api/auth/login
   ↓
3. Backend retorna:
   {
     accessToken: "JWT...",
     refreshToken: "abc123...",
     user: { id, email, profile, roleContexts: [agent, company_admin, user] },
     expiresIn: 3600
   }
   ↓
4. Frontend guarda en localStorage:
   AuthManager.save(response)
   ↓
5. ¿Múltiples roles?
   SÍ → GET /auth-flow/role-selector
       User selecciona rol
       POST /auth/select-role { "agent", "uuid-empresa-1" }
       Backend actualiza activeRole en JWT
       Redirige a /app/dashboard
   NO → GET /app/dashboard
   ↓
6. ¿Onboarding completo?
   NO → Muestra onboarding wizard
   SÍ → Muestra dashboard
   ↓
7. En cada request:
   Header: Authorization: Bearer {accessToken}
   ↓
8. Si accessToken expirado:
   Automático: POST /api/auth/refresh { refreshToken }
   Obtiene nuevo access token + refresh token rotado
   Reintenta request
   ↓
9. Logout:
   POST /api/auth/logout { refreshToken }
   AuthManager.logout() (elimina localStorage)
   Redirige a /login
```

---

## 📋 Routes Blade (routes/web.php)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    PublicController,
    AuthFlowController,
    OnboardingController,
    DashboardController,
    PlatformAdminController,
    CompanyAdminController,
    AgentController,
    UserController,
};

// ========================================
// ZONA PÚBLICA - Sin JWT (credenciales en request)
// ========================================
Route::middleware('web')->group(function () {
    // Páginas públicas
    Route::get('/', [PublicController::class, 'welcome'])->name('home');
    Route::get('/login', [PublicController::class, 'login'])->name('login');
    Route::get('/register', [PublicController::class, 'register'])->name('register');
    Route::get('/register-company', [PublicController::class, 'registerCompany'])->name('register-company');
    Route::get('/forgot-password', [PublicController::class, 'forgotPassword'])->name('password.request');
    Route::get('/reset-password/{token}', [PublicController::class, 'resetPassword'])->name('password.reset');
});

// ========================================
// ZONA AUTH FLOW - JWT presente, rol pendiente
// ========================================
Route::middleware('auth:jwt')->group(function () {
    // Auth status (verificar si necesita onboarding/role-selector)
    Route::get('/auth/status', [AuthFlowController::class, 'status'])->name('auth.status');

    // Selector de rol (si tiene múltiples)
    Route::get('/auth-flow/role-selector', [AuthFlowController::class, 'roleSelector'])->name('role-selector');
    Route::post('/auth/select-role', [AuthFlowController::class, 'selectRole'])->name('select-role');

    // Onboarding
    Route::prefix('auth-flow/onboarding')->group(function () {
        Route::get('/profile', [OnboardingController::class, 'profile'])->name('onboarding.profile');
        Route::put('/profile', [OnboardingController::class, 'updateProfile']);

        Route::get('/preferences', [OnboardingController::class, 'preferences'])->name('onboarding.preferences');
        Route::put('/preferences', [OnboardingController::class, 'updatePreferences']);

        Route::get('/verify-email', [OnboardingController::class, 'verifyEmail'])->name('onboarding.verify-email');
        Route::post('/verify-email/send', [OnboardingController::class, 'sendVerification']);
        Route::post('/verify-email/confirm', [OnboardingController::class, 'confirmEmail']);
    });
});

// ========================================
// ZONA AUTENTICADA - JWT + Rol seleccionado
// ========================================
Route::middleware('auth:jwt', 'role:selected')->prefix('app')->group(function () {

    // Dashboard genérico (redirige según rol)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ─────── PLATFORM_ADMIN ───────
    Route::middleware('role:platform_admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [PlatformAdminController::class, 'dashboard'])->name('admin.dashboard');

        // Usuarios
        Route::get('/users', [PlatformAdminController::class, 'usersIndex'])->name('admin.users.index');
        Route::get('/users/{id}', [PlatformAdminController::class, 'usersShow'])->name('admin.users.show');
        Route::get('/users/{id}/edit', [PlatformAdminController::class, 'usersEdit'])->name('admin.users.edit');

        // Empresas
        Route::get('/companies', [PlatformAdminController::class, 'companiesIndex'])->name('admin.companies.index');
        Route::get('/companies/{id}', [PlatformAdminController::class, 'companiesShow'])->name('admin.companies.show');

        // Solicitudes de empresa
        Route::get('/company-requests', [PlatformAdminController::class, 'requestsIndex'])->name('admin.requests.index');
        Route::get('/company-requests/{id}', [PlatformAdminController::class, 'requestsShow'])->name('admin.requests.show');
    });

    // ─────── COMPANY_ADMIN ───────
    Route::middleware('role:company_admin')->prefix('company')->group(function () {
        Route::get('/dashboard', [CompanyAdminController::class, 'dashboard'])->name('company.dashboard');

        // Configuración empresa
        Route::get('/settings', [CompanyAdminController::class, 'settings'])->name('company.settings');
        Route::put('/settings', [CompanyAdminController::class, 'updateSettings']);
        Route::get('/branding', [CompanyAdminController::class, 'branding'])->name('company.branding');
        Route::get('/business-hours', [CompanyAdminController::class, 'businessHours'])->name('company.business-hours');

        // Agentes
        Route::get('/agents', [CompanyAdminController::class, 'agentsIndex'])->name('company.agents.index');
        Route::get('/agents/create', [CompanyAdminController::class, 'agentsCreate'])->name('company.agents.create');
        Route::get('/agents/{id}', [CompanyAdminController::class, 'agentsShow'])->name('company.agents.show');

        // Categorías
        Route::get('/categories', [CompanyAdminController::class, 'categoriesIndex'])->name('company.categories.index');
        Route::get('/categories/create', [CompanyAdminController::class, 'categoriesCreate'])->name('company.categories.create');

        // Macros
        Route::get('/macros', [CompanyAdminController::class, 'macrosIndex'])->name('company.macros.index');
        Route::get('/macros/create', [CompanyAdminController::class, 'macrosCreate'])->name('company.macros.create');

        // Help Center
        Route::get('/help-center/articles', [CompanyAdminController::class, 'articlesIndex'])->name('company.articles.index');
        Route::get('/help-center/articles/create', [CompanyAdminController::class, 'articlesCreate'])->name('company.articles.create');

        // Analytics
        Route::get('/analytics', [CompanyAdminController::class, 'analytics'])->name('company.analytics');
    });

    // ─────── AGENT ───────
    Route::middleware('role:agent')->prefix('agent')->group(function () {
        Route::get('/dashboard', [AgentController::class, 'dashboard'])->name('agent.dashboard');

        // Tickets
        Route::get('/tickets', [AgentController::class, 'ticketsIndex'])->name('agent.tickets.index');
        Route::get('/tickets/{id}', [AgentController::class, 'ticketsShow'])->name('agent.tickets.show');

        // Notas internas
        Route::get('/internal-notes', [AgentController::class, 'notesIndex'])->name('agent.notes.index');

        // Help Center
        Route::get('/help-center', [AgentController::class, 'helpCenter'])->name('agent.help-center');
    });

    // ─────── USER ───────
    Route::middleware('role:user')->prefix('user')->group(function () {
        Route::get('/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');

        // Mis tickets
        Route::get('/tickets', [UserController::class, 'ticketsIndex'])->name('user.tickets.index');
        Route::get('/tickets/create', [UserController::class, 'ticketsCreate'])->name('user.tickets.create');
        Route::get('/tickets/{id}', [UserController::class, 'ticketsShow'])->name('user.tickets.show');

        // Mi perfil
        Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');

        // Help Center
        Route::get('/help-center', [UserController::class, 'helpCenter'])->name('user.help-center');
    });
});
```

---

## 🎨 Componentes Blade Base (Reutilizables)

```
resources/views/app/shared/components/
├── alert.blade.php              <!-- Success, Error, Warning, Info -->
├── card.blade.php               <!-- Card base -->
├── button.blade.php             <!-- Button variants -->
├── badge.blade.php              <!-- Status badges -->
├── modal.blade.php              <!-- Modal dialog -->
├── form-input.blade.php         <!-- Input text, email, etc -->
├── form-select.blade.php        <!-- Dropdown select -->
├── form-checkbox.blade.php      <!-- Checkbox -->
├── form-textarea.blade.php      <!-- Textarea -->
├── table.blade.php              <!-- Table base -->
├── pagination.blade.php         <!-- Pagination links -->
├── breadcrumb.blade.php         <!-- Breadcrumb navigation -->
└── loading-spinner.blade.php    <!-- Loading spinner -->
```

---

## ✅ Checklist Implementación

- [ ] Crear `AuthManager` class en JavaScript
- [ ] Crear `ApiClient` class en JavaScript
- [ ] Crear layout `public.blade.php`
- [ ] Crear layout `auth-flow.blade.php`
- [ ] Crear layout `app.blade.php`
- [ ] Crear navbar.blade.php dinámico
- [ ] Crear sidebar.blade.php dinámico por rol
- [ ] Crear vistas zona pública (login, register, etc)
- [ ] Crear vistas zona auth-flow (role-selector, onboarding)
- [ ] Implementar auto-refresh de access token
- [ ] Implementar logout + eliminar localStorage
- [ ] Crear componentes Blade reutilizables
- [ ] Implementar error handling centralizado
- [ ] Implementar notificaciones (toast alerts)

---

## 🔑 Clave Diferenciadora: Stateless

Este frontend:
- ❌ NO usa Laravel Sessions
- ✅ SÍ usa JWT en localStorage
- ✅ SÍ es compatible con móvil (mismo JWT)
- ✅ SÍ soporta múltiples dispositivos concurrentes
- ✅ SÍ tiene refresh token automático
- ✅ SÍ es listo para GraphQL (mismos tokens)

---

**Documento generado:** 6 de Noviembre de 2025
**Por:** Claude Code investigando tu JWT actual
**Estado:** ✅ Basado en tu arquitectura real
