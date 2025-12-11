# 🎫 Widget Embebible de Helpdesk - Plan de Implementación

> **Fecha de creación:** 2025-12-11
> **Autor:** Lucas De La Quintana Montenegro
> **Estado:** Planificación
> **Paquete:** `lukehowland/helpdeskwidget`

---

## 📋 Índice

1. [Visión General](#visión-general)
2. [Flujo del Widget con Spinner](#flujo-del-widget-con-spinner)
3. [Endpoints Necesarios](#endpoints-necesarios)
4. [Plan de Implementación](#plan-de-implementación)
5. [Fase 1: Backend - Sistema de API Keys](#fase-1-backend---sistema-de-api-keys)
6. [Fase 2: Backend - Endpoints Externos](#fase-2-backend---endpoints-externos)
7. [Fase 3: Widget Embebible](#fase-3-widget-embebible)
8. [Fase 4: Paquete Laravel](#fase-4-paquete-laravel)
9. [Guía de Instalación para Proyectos Externos](#guía-de-instalación-para-proyectos-externos)

---

## Visión General

### Objetivo
Crear un widget embebible que permita a proyectos externos de la academia integrar el sistema de tickets de Helpdesk de forma **plug & play**.

### Características Clave
- ✅ Spinner con mensajes descriptivos durante la carga
- ✅ Verificación de API Key de empresa
- ✅ Login automático si usuario existe
- ✅ Registro semi-automático (solo pide contraseña) si usuario no existe
- ✅ Vista IDÉNTICA a la vista actual de tickets
- ✅ Manejo de errores sin mostrar mensajes técnicos
- ✅ Fallback a formularios de login/registro si algo falla

### Nombre del Paquete
```
lukehowland/helpdeskwidget
```

---

## Flujo del Widget con Spinner

### Diagrama de Flujo Completo

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  WIDGET SE CARGA                                                            │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │                                                                       │  │
│  │                          ⏳                                           │  │
│  │                     [Spinner]                                         │  │
│  │                                                                       │  │
│  │              "Conectando con Helpdesk API..."                         │  │
│  │                                                                       │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                              │                                              │
│                              ▼                                              │
│                    PASO 1: Verificar API Key                                │
│                              │                                              │
│              ┌───────────────┴───────────────┐                              │
│              │                               │                              │
│              ▼                               ▼                              │
│        API Key VÁLIDA               API Key NO EXISTE                       │
│              │                               │                              │
│              │                               ▼                              │
│              │               ┌─────────────────────────────────────────┐    │
│              │               │  ❌ Tu empresa no está registrada       │    │
│              │               │                                         │    │
│              │               │  Por favor, solicita acceso en:         │    │
│              │               │  🔗 proyecto-de-ultimo-minuto.online/   │    │
│              │               │     solicitud-empresa                   │    │
│              │               │                                         │    │
│              │               │  O contacta al administrador:           │    │
│              │               │  📧 lukqs05@gmail.com                   │    │
│              │               │  📱 62119184                            │    │
│              │               └─────────────────────────────────────────┘    │
│              │                                                              │
│              ▼                                                              │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │              "Verificando cuenta de usuario..."                       │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                              │                                              │
│                              ▼                                              │
│                    PASO 2: Verificar Usuario                                │
│                              │                                              │
│              ┌───────────────┴───────────────┐                              │
│              │                               │                              │
│              ▼                               ▼                              │
│       Usuario EXISTE               Usuario NO EXISTE                        │
│              │                               │                              │
│              ▼                               ▼                              │
│  ┌─────────────────────┐       ┌─────────────────────────────────────────┐  │
│  │ "Iniciando sesión..." │       │  MOSTRAR FORMULARIO DE CONTRASEÑA      │  │
│  └─────────────────────┘       │  (antes de intentar registro)            │  │
│              │                  │                                         │  │
│              ▼                  │  ┌───────────────────────────────────┐  │  │
│       PASO 3: Login             │  │ 👤 Crear cuenta en Helpdesk       │  │  │
│       Automático                │  │                                   │  │  │
│              │                  │  │ Email: juan@gmail.com ✓           │  │  │
│              │                  │  │ Nombre: Juan Pérez ✓              │  │  │
│              │                  │  │                                   │  │  │
│ ┌────────────┴──────────┐       │  │ Crea tu contraseña:               │  │  │
│ │                       │       │  │ ┌─────────────────────────────┐   │  │  │
│ ▼                       ▼       │  │ │ ••••••••••                  │   │  │  │
│ LOGIN OK          LOGIN FALLA   │  │ └─────────────────────────────┘   │  │  │
│ │                       │       │  │                                   │  │  │
│ │                       ▼       │  │ Confirmar contraseña:             │  │  │
│ │          ┌──────────────────┐ │  │ ┌─────────────────────────────┐   │  │  │
│ │          │ MOSTRAR FORM     │ │  │ │ ••••••••••                  │   │  │  │
│ │          │ LOGIN MANUAL     │ │  │ └─────────────────────────────┘   │  │  │
│ │          │                  │ │  │                                   │  │  │
│ │          │ Email: (auto)    │ │  │ [  Crear cuenta y continuar  ]    │  │  │
│ │          │ Contraseña: ___  │ │  └───────────────────────────────────┘  │  │
│ │          │                  │ │                                         │  │
│ │          │ [Iniciar sesión] │ │            │                            │  │
│ │          └──────────────────┘ │            ▼                            │  │
│ │                       │       │     PASO 4: Registro                    │  │
│ │                       │       │            │                            │  │
│ │                       │       │     ┌──────┴──────┐                     │  │
│ │                       │       │     │             │                     │  │
│ │                       │       │     ▼             ▼                     │  │
│ │                       │       │  REG. OK     REG. FALLA                 │  │
│ │                       │       │     │             │                     │  │
│ │◄──────────────────────┤       │     │             ▼                     │  │
│ │                       │       │     │    ┌──────────────────┐           │  │
│ │◄──────────────────────┼───────┼─────┘    │ MOSTRAR FORM     │           │  │
│ │                       │       │          │ REGISTRO MANUAL  │           │  │
│ ▼                       │       │          │ (con más campos) │           │  │
│                         │       │          └──────────────────┘           │  │
│  ┌───────────────────────────────────────────────────────────────────┐    │  │
│  │                                                                   │    │  │
│  │                    ✅ WIDGET CARGADO                              │    │  │
│  │                                                                   │    │  │
│  │    (Vista IDÉNTICA a shared/tickets/index.blade.php)              │    │  │
│  │                                                                   │    │  │
│  │    ┌──────────┐ ┌──────────────────────────────────────────┐      │    │  │
│  │    │ Carpetas │ │ Lista de Tickets                         │      │    │  │
│  │    │          │ │                                          │      │    │  │
│  │    │ ☐ Todos  │ │  [TKT-001] Error en facturación   🔴     │      │    │  │
│  │    │ ☐ Nuevos │ │  [TKT-002] Consulta sobre envíos  🟢     │      │    │  │
│  │    │ ☐ Pend.  │ │                                          │      │    │  │
│  │    │          │ │                                          │      │    │  │
│  │    └──────────┘ └──────────────────────────────────────────┘      │    │  │
│  │                                                                   │    │  │
│  └───────────────────────────────────────────────────────────────────┘    │  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Mensajes del Spinner (en orden)

| Paso | Mensaje del Spinner | Duración Est. |
|------|---------------------|---------------|
| 1 | "Conectando con Helpdesk API..." | 0.5-1s |
| 2 | "Verificando empresa..." | 0.3-0.5s |
| 3 | "Verificando cuenta de usuario..." | 0.3-0.5s |
| 4 | "Iniciando sesión..." | 0.3-0.5s |
| 5 | "Cargando tus tickets..." | 0.5-1s |

### Principios de UX

1. **NUNCA mostrar errores técnicos** - Solo mensajes amigables o formularios alternativos
2. **Todo en background** - El spinner mantiene al usuario informado
3. **Pedir contraseña ANTES de intentar registro** - No hacer trabajo en vano
4. **Fallback a formularios manuales** - Si algo falla, el usuario puede completar manualmente
5. **Vista idéntica a la actual** - No simplificar, mostrar la experiencia completa

---

## Endpoints Necesarios

### Nuevos Endpoints a Crear

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/external/validate-key` | Valida si API Key existe y está activa | API Key |
| `POST` | `/api/external/check-user` | Verifica si email existe en Helpdesk | API Key |
| `POST` | `/api/external/login` | Login automático (trusted, sin password) | API Key |
| `POST` | `/api/external/register` | Registro con contraseña | API Key |

### Detalle de Cada Endpoint

#### 1. `POST /api/external/validate-key`
```json
// Request
Headers: { "X-Service-Key": "your_api_key_here" }
Body: {} // Vacío

// Response (éxito)
{
    "success": true,
    "company": {
        "id": "uuid",
        "name": "Inventarios S.A.",
        "logoUrl": "https://..."
    }
}

// Response (error)
{
    "success": false,
    "code": "INVALID_API_KEY"
}
```

#### 2. `POST /api/external/check-user`
```json
// Request
Headers: { "X-Service-Key": "your_api_key_here" }
Body: { "email": "juan@gmail.com" }

// Response
{
    "success": true,
    "exists": true,  // o false
    "user": {        // Solo si exists = true
        "displayName": "Juan Pérez"
    }
}
```

#### 3. `POST /api/external/login`
```json
// Request
Headers: { "X-Service-Key": "your_api_key_here" }
Body: { "email": "juan@gmail.com" }

// Response (éxito)
{
    "success": true,
    "accessToken": "eyJhbGciOiJIUzI1NiIs...",
    "expiresIn": 3600
}

// Response (error - requiere login manual)
{
    "success": false,
    "code": "MANUAL_LOGIN_REQUIRED",
    "message": "Por favor, ingresa tu contraseña"
}
```

#### 4. `POST /api/external/register`
```json
// Request
Headers: { "X-Service-Key": "your_api_key_here" }
Body: {
    "email": "juan@gmail.com",
    "firstName": "Juan",
    "lastName": "Pérez",
    "password": "miContraseña123",
    "passwordConfirmation": "miContraseña123"
}

// Response (éxito)
{
    "success": true,
    "accessToken": "eyJhbGciOiJIUzI1NiIs...",
    "expiresIn": 3600
}

// Response (error)
{
    "success": false,
    "code": "VALIDATION_ERROR",
    "errors": {
        "password": ["La contraseña debe tener al menos 8 caracteres"]
    }
}
```

---

## Plan de Implementación

### Tabla de Tareas Completa

#### FASE 1: Backend - Sistema de API Keys
| # | Tarea | Archivo a Crear/Modificar | Tiempo Est. | Prioridad |
|---|-------|---------------------------|-------------|-----------|
| 1.1 | Crear migración `service_api_keys` | `database/migrations/xxx_create_service_api_keys_table.php` | 15 min | 🔴 Alta |
| 1.2 | Crear modelo `ServiceApiKey` | `app/Features/ExternalIntegration/Models/ServiceApiKey.php` | 20 min | 🔴 Alta |
| 1.3 | Crear middleware `ValidateServiceApiKey` | `app/Features/ExternalIntegration/Http/Middleware/ValidateServiceApiKey.php` | 20 min | 🔴 Alta |
| 1.4 | Registrar middleware en Kernel | `app/Http/Kernel.php` | 5 min | 🔴 Alta |

#### FASE 2: Backend - Endpoints Externos
| # | Tarea | Archivo a Crear/Modificar | Tiempo Est. | Prioridad |
|---|-------|---------------------------|-------------|-----------|
| 2.1 | Crear `ExternalAuthController` | `app/Features/ExternalIntegration/Http/Controllers/ExternalAuthController.php` | 45 min | 🔴 Alta |
| 2.2 | Crear `ExternalAuthService` | `app/Features/ExternalIntegration/Services/ExternalAuthService.php` | 30 min | 🔴 Alta |
| 2.3 | Crear `ValidateKeyRequest` | `app/Features/ExternalIntegration/Http/Requests/ValidateKeyRequest.php` | 10 min | 🔴 Alta |
| 2.4 | Crear `CheckUserRequest` | `app/Features/ExternalIntegration/Http/Requests/CheckUserRequest.php` | 10 min | 🔴 Alta |
| 2.5 | Crear `ExternalLoginRequest` | `app/Features/ExternalIntegration/Http/Requests/ExternalLoginRequest.php` | 10 min | 🔴 Alta |
| 2.6 | Crear `ExternalRegisterRequest` | `app/Features/ExternalIntegration/Http/Requests/ExternalRegisterRequest.php` | 15 min | 🔴 Alta |
| 2.7 | Agregar rutas en `api.php` | `routes/api.php` | 10 min | 🔴 Alta |

#### FASE 3: Widget Embebible (Vista)
| # | Tarea | Archivo a Crear/Modificar | Tiempo Est. | Prioridad |
|---|-------|---------------------------|-------------|-----------|
| 3.1 | Crear layout `widget.blade.php` | `resources/views/layouts/widget.blade.php` | 30 min | 🔴 Alta |
| 3.2 | Crear `WidgetController` | `app/Features/ExternalIntegration/Http/Controllers/WidgetController.php` | 20 min | 🔴 Alta |
| 3.3 | Crear vista principal del widget | `resources/views/widget/index.blade.php` | 45 min | 🔴 Alta |
| 3.4 | Crear componente Spinner/Loader | `resources/views/widget/components/loader.blade.php` | 20 min | 🔴 Alta |
| 3.5 | Crear formulario de login | `resources/views/widget/components/login-form.blade.php` | 25 min | 🟡 Media |
| 3.6 | Crear formulario de registro | `resources/views/widget/components/register-form.blade.php` | 25 min | 🟡 Media |
| 3.7 | Crear vista de empresa no registrada | `resources/views/widget/components/company-not-found.blade.php` | 15 min | 🟡 Media |
| 3.8 | Copiar/adaptar vista de tickets | `resources/views/widget/tickets/index.blade.php` | 60 min | 🔴 Alta |
| 3.9 | Copiar/adaptar partials de tickets | `resources/views/widget/tickets/partials/*` | 45 min | 🔴 Alta |
| 3.10 | Agregar rutas web del widget | `routes/web.php` | 10 min | 🔴 Alta |
| 3.11 | Crear JavaScript del flujo de auth | `public/js/widget-auth.js` | 60 min | 🔴 Alta |

#### FASE 4: Paquete Laravel
| # | Tarea | Archivo a Crear | Tiempo Est. | Prioridad |
|---|-------|-----------------|-------------|-----------|
| 4.1 | Crear estructura del paquete | `packages/helpdeskwidget/` | 15 min | 🔴 Alta |
| 4.2 | Crear `composer.json` del paquete | `packages/helpdeskwidget/composer.json` | 10 min | 🔴 Alta |
| 4.3 | Crear `HelpdeskWidgetServiceProvider` | `packages/helpdeskwidget/src/HelpdeskWidgetServiceProvider.php` | 20 min | 🔴 Alta |
| 4.4 | Crear componente `HelpdeskWidget` | `packages/helpdeskwidget/src/Components/HelpdeskWidget.php` | 30 min | 🔴 Alta |
| 4.5 | Crear configuración | `packages/helpdeskwidget/config/helpdesk.php` | 10 min | 🔴 Alta |
| 4.6 | Crear vista del componente | `packages/helpdeskwidget/resources/views/components/widget.blade.php` | 20 min | 🔴 Alta |
| 4.7 | Crear README.md | `packages/helpdeskwidget/README.md` | 30 min | 🟡 Media |
| 4.8 | Publicar en GitHub | - | 15 min | 🟡 Media |

#### FASE 5: Panel de Gestión API Keys (Opcional)
| # | Tarea | Archivo a Crear/Modificar | Tiempo Est. | Prioridad |
|---|-------|---------------------------|-------------|-----------|
| 5.1 | Crear vista gestión de API Keys | `resources/views/app/platform-admin/api-keys/index.blade.php` | 60 min | 🟢 Baja |
| 5.2 | Crear endpoints CRUD de API Keys | `app/Features/ExternalIntegration/Http/Controllers/ApiKeyController.php` | 45 min | 🟢 Baja |

### Resumen de Tiempos

| Fase | Tiempo Estimado | Prioridad |
|------|-----------------|-----------|
| Fase 1: API Keys Backend | 1 hora | 🔴 Alta |
| Fase 2: Endpoints Externos | 2.5 horas | 🔴 Alta |
| Fase 3: Widget (Vista) | 5.5 horas | 🔴 Alta |
| Fase 4: Paquete Laravel | 2.5 horas | 🔴 Alta |
| Fase 5: Panel Gestión | 2 horas | 🟢 Opcional |
| **TOTAL (sin Fase 5)** | **~11.5 horas** | |

---

## Estructura de Archivos a Crear

### En Helpdesk (tu proyecto)

```
app/Features/ExternalIntegration/          ← NUEVO FEATURE
├── Http/
│   ├── Controllers/
│   │   ├── ExternalAuthController.php
│   │   ├── WidgetController.php
│   │   └── ApiKeyController.php
│   ├── Middleware/
│   │   └── ValidateServiceApiKey.php
│   └── Requests/
│       ├── ValidateKeyRequest.php
│       ├── CheckUserRequest.php
│       ├── ExternalLoginRequest.php
│       └── ExternalRegisterRequest.php
├── Models/
│   └── ServiceApiKey.php
├── Services/
│   └── ExternalAuthService.php
└── Database/
    └── Migrations/
        └── xxxx_create_service_api_keys_table.php

resources/views/
├── layouts/
│   └── widget.blade.php                   ← Layout mínimo para widget
└── widget/
    ├── index.blade.php                    ← Punto de entrada del widget
    ├── components/
    │   ├── loader.blade.php               ← Spinner con mensajes
    │   ├── login-form.blade.php           ← Formulario login
    │   ├── register-form.blade.php        ← Formulario registro
    │   └── company-not-found.blade.php    ← Empresa no registrada
    └── tickets/
        ├── index.blade.php                ← Copia de shared/tickets/index
        └── partials/                      ← Copias adaptadas
            ├── tickets-list.blade.php
            ├── create-ticket.blade.php
            └── ticket-detail.blade.php

public/js/
└── widget-auth.js                         ← JavaScript del flujo de auth

routes/
├── api.php                                ← Agregar rutas /api/external/*
└── web.php                                ← Agregar rutas /widget/*
```

### Paquete Laravel (repositorio separado)

```
packages/helpdeskwidget/
├── composer.json
├── README.md
├── LICENSE
├── src/
│   ├── HelpdeskWidgetServiceProvider.php
│   └── Components/
│       └── HelpdeskWidget.php
├── resources/
│   └── views/
│       └── components/
│           └── widget.blade.php
└── config/
    └── helpdesk.php
```

---

## Guía de Instalación para Proyectos Externos

### Lo que hacen tus compañeros (5 minutos)

#### Paso 1: Instalar el paquete
```bash
composer require lukehowland/helpdeskwidget
```

#### Paso 2: Agregar a `.env`
```env
HELPDESK_URL=https://proyecto-de-ultimo-minuto.online
HELPDESK_API_KEY=key_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

#### Paso 3: Usar en cualquier vista
```blade
<x-helpdesk-widget />
```

#### Opciones de personalización
```blade
{{-- Altura personalizada --}}
<x-helpdesk-widget height="600px" />

{{-- En un modal --}}
<div class="modal">
    <x-helpdesk-widget height="100%" />
</div>
```

### ¡Eso es TODO! 🎉

---

## Notas Importantes

### Seguridad
- API Keys se transmiten solo server-to-server (nunca expuestas al frontend)
- Los tokens JWT tienen expiración corta (1 hora)
- Rate limiting en todos los endpoints externos
- Logs de uso de API Keys para auditoría

### UX
- Spinner con mensajes descriptivos mantiene al usuario informado
- Nunca se muestran errores técnicos
- Siempre hay un fallback (formularios manuales)
- Vista de tickets idéntica a la actual

### Mantenimiento
- Las vistas de tickets en widget son copias adaptadas (no symlinks)
- Cambios en la vista principal deben replicarse manualmente al widget
- Considerar en el futuro extraer a componentes compartidos

---

> **Documento actualizado:** 2025-12-11
> **Versión:** 2.0
