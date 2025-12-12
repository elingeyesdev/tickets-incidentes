# 🚀 Widget Helpdesk v2.0 - Plan de Implementación

> **Fecha**: 2025-12-12
> **Objetivo**: Nueva versión del paquete `helpdeskwidget` con mejoras de UX, manejo de tokens, y facilidad de instalación.

---

## 📐 Diseño Visual de Referencia

### Estilo "OAuth Connection" (como GitHub, Google, etc.)

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│     ┌──────────┐                          ┌──────────┐          │
│     │          │                          │          │          │
│     │ HELPDESK │    ←── ─ ─ ─ ─ ─ ──→    │ EMPRESA  │          │
│     │   LOGO   │         conexión         │   LOGO   │          │
│     │          │                          │          │          │
│     └──────────┘                          └──────────┘          │
│                                                                 │
│              [ Conectar con Centro de Soporte ]                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Estados de la conexión:**
- **Sin conectar**: Línea punteada gris `─ ─ ─ ─ ─`
- **Conectando**: Línea animada `━ ━ ━ ━ ━` (animación de izq a der)
- **Conectado**: Línea sólida verde con checkmark `━━━━━ ✓`
- **Error/No registrada**: Línea roja con X `──×──`

---

## 📋 REQUERIMIENTOS COMPLETOS

---

### 1️⃣ AUTENTICACIÓN Y TOKENS

#### 1.1 Manejo de Token TTL (15 minutos)
| Atributo | Valor |
|----------|-------|
| **Problema** | El paquete obtiene token pero no maneja su expiración (TTL = 15 min) |
| **Impacto** | Después de 15 min, las llamadas API fallan con 401 |
| **Solución** | Implementar refresh token o re-autenticación automática |
| **Ubicación Cambios** | `paquete/src/HelpdeskService.php`, `ExternalAuthService.php`, Widget JS |

**Flujo propuesto:**
1. Paquete guarda `token` + `expires_at` en sesión
2. Antes de cada request, verificar si expiró
3. Si expiró → solicitar nuevo token automáticamente
4. Si falla → mostrar pantalla de "reconectar"

#### 1.2 Detección de Cambio de Usuario
| Atributo | Valor |
|----------|-------|
| **Problema** | Si el usuario del proyecto externo cierra sesión y entra otro, el widget sigue con el anterior |
| **Impacto** | Un usuario podría ver tickets de otro |
| **Solución** | Comparar email del usuario actual vs. el del token guardado |
| **Ubicación Cambios** | `paquete/src/View/Components/HelpdeskWidget.php` |

**Flujo propuesto:**
1. Al renderizar componente, obtener `auth()->user()->email`
2. Comparar con email guardado en sesión/token
3. Si son diferentes → invalidar token, mostrar pantalla de conexión
4. Si son iguales → usar token existente (si no expiró)

---

### 2️⃣ DISEÑO UI - EMPRESA NO REGISTRADA

**Archivo**: `resources/views/widget/index.blade.php` (líneas 47-81)

#### 2.1 Diseño con Logos Side-by-Side
```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│     ┌──────────┐                          ┌──────────┐          │
│     │ HELPDESK │         ──×──            │ EMPRESA  │          │
│     │   LOGO   │        (error)           │   LOGO   │          │
│     └──────────┘                          └──────────┘          │
│                                                                 │
│                ⚠️ Empresa no registrada                         │
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  Tu empresa no tiene acceso a Helpdesk.                 │   │
│   │                                                         │   │
│   │  📝 Solicitar acceso:                                   │   │
│   │     [ Formulario de Solicitud ]     (botón)             │   │
│   │                                                         │   │
│   │  📧 Contacto: lukqs05@gmail.com                         │   │
│   │  📞 Teléfono: 62119184                                  │   │
│   └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Elementos necesarios:**
- [ ] Logo Helpdesk (usar el de `/logo.png`)
- [ ] Logo de la empresa (obtener de API Key validation response)
- [ ] Icono de error en la línea de conexión (X roja)
- [ ] Card estilo AdminLTE limpio
- [ ] Botones con hover effects

---

### 3️⃣ DISEÑO UI - PANTALLA DE CONEXIÓN (Nueva)

**Esta pantalla aparece cuando:**
- API Key es válida (empresa registrada)
- Pero el usuario NO está conectado al Centro de Soporte

#### 3.1 Estado: Listo para Conectar
```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│     ┌──────────┐                          ┌──────────┐          │
│     │ HELPDESK │      ─ ─ ─ ─ ─ ─         │ EMPRESA  │          │
│     │   LOGO   │      (punteado)          │   LOGO   │          │
│     └──────────┘                          └──────────┘          │
│                                                                 │
│        Conecta con el Centro de Soporte de [Empresa]            │
│                                                                 │
│              [ 🔗 Conectar con Centro de Soporte ]              │
│                                                                 │
│           "Accede a soporte técnico, tickets y más"             │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 3.2 Estado: Conectando (Animación)
```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│     ┌──────────┐                          ┌──────────┐          │
│     │ HELPDESK │      ━━━━➤               │ EMPRESA  │          │
│     │   LOGO   │      (animado)           │   LOGO   │          │
│     └──────────┘                          └──────────┘          │
│                                                                 │
│                    Estableciendo conexión...                    │
│                         ⏳ Verificando usuario                  │
│                         ⏳ Iniciando sesión                     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 3.3 Estado: Conectado (Éxito)
```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│     ┌──────────┐                          ┌──────────┐          │
│     │ HELPDESK │      ━━━━━━━━✓           │ EMPRESA  │          │
│     │   LOGO   │      (verde)             │   LOGO   │          │
│     └──────────┘                          └──────────┘          │
│                                                                 │
│                    ✅ Conexión establecida                      │
│                                                                 │
│                         (Redirige automáticamente)              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Cambio importante**: Ya NO auto-conecta. El usuario debe hacer clic explícitamente.

---

### 4️⃣ PERFIL DE USUARIO EN WIDGET

**Ubicación**: Vista de tickets del widget (sidebar o card inferior)

#### 4.1 Card "Mi Perfil de Helpdesk"
```
┌─────────────────────────────────────────────┐
│  Mi Perfil de Helpdesk                      │
├─────────────────────────────────────────────┤
│                                             │
│     ┌────────────┐                          │
│     │   AVATAR   │  Juan Pérez              │
│     │    (foto)  │  juan@email.com          │
│     │   ✏️(hover)│  Usuario desde: Nov 2024 │
│     └────────────┘                          │
│                                             │
│  [ 🚪 Salir del Centro de Soporte ]         │
│  [ 🌐 Visitar Sitio Oficial ]               │
│                                             │
└─────────────────────────────────────────────┘
```

**Funcionalidades:**
- [ ] Al hover sobre avatar → aparece icono de lápiz (✏️)
- [ ] Al clic en avatar → abre file browser para cambiar foto
- [ ] API endpoint necesario: `PUT /api/external/profile/avatar`
- [ ] Botón "Salir" → Limpia token, vuelve a pantalla de conexión
- [ ] Botón "Visitar Sitio Oficial" → SSO al sitio principal

---

### 5️⃣ SSO AL SITIO OFICIAL

**Flujo "Visitar Sitio Oficial":**

```
[Widget] → [Generar SSO Token] → [Redirect URL] → [Loading Page] → [Dashboard]
```

#### 5.1 Implementación Backend

**Nuevo endpoint**: `POST /api/external/sso-token`
```json
// Request
{
  "current_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}

// Response
{
  "success": true,
  "sso_url": "https://proyecto-de-ultimo-minuto.online/?sso_token=abc123..."
}
```

#### 5.2 Modificar Loading Page (`auth/loading.blade.php`)

Actualmente verifica sesión via cookies. Agregar:
1. Detectar `?sso_token=xxx` en URL
2. Validar token SSO
3. Establecer sesión y cookies
4. Redirigir a dashboard

---

### 6️⃣ COMANDO DE INSTALACIÓN DEL PAQUETE

**Comando**: `php artisan helpdeskwidget:install`

#### 6.1 Qué hace el comando:

1. **Publica configuración**
   ```bash
   → config/helpdeskwidget.php
   ```

2. **Crea vista Blade**
   ```bash
   → resources/views/helpdesk.blade.php
   ```
   
3. **Agrega ruta a web.php**
   ```php
   Route::get('helpdesk', function () {
       return view('helpdesk');
   })->name('helpdesk')->middleware('auth');
   ```

4. **Opcionalmente agrega al sidebar de AdminLTE**
   (si detecta `config/adminlte.php`)

#### 6.2 Vista que genera:

```blade
@extends('adminlte::page')

@section('title', 'Centro de Soporte')

@section('content_header')
    <h1>HelpDesk SaaS - Centro de Soporte</h1>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div id="helpdesk-widget-wrapper" style="width: 100%;">
                    <x-helpdesk-widget width="100%" />
                </div>
            </div>
        </div>
    </div>

    <style>
        #helpdesk-widget-wrapper iframe {
            width: 100% !important;
            border: none !important;
            display: block;
            min-height: 500px;
            transition: height 0.3s ease;
        }
    </style>

    <script>
        (function() {
            'use strict';

            console.log('🔍 [PARENT] Escuchando mensajes del widget');

            window.addEventListener('message', function(event) {
                if (event.data.type === 'widget-resize') {
                    const iframe = document.querySelector('#helpdesk-widget-wrapper iframe');
                    if (iframe) {
                        const newHeight = event.data.height;
                        console.log('📏 [PARENT] Recibido mensaje de resize:', newHeight);
                        iframe.style.height = newHeight + 'px';
                    }
                }
            });

            console.log('✅ [PARENT] Listener de postMessage configurado');
        })();
    </script>
@endsection
```

---

### 7️⃣ CONFIGURACIÓN SERVIDOR (Nginx/CORS)

**Estado**: ✅ Código listo, pendiente deploy

#### 7.1 Nginx - X-Frame-Options
```nginx
# docker/nginx/default.conf
location /widget/ {
    # NO X-Frame-Options - permite iframes
    add_header Content-Security-Policy "frame-ancestors *" always;
    try_files $uri /index.php?$query_string;
}
```

#### 7.2 CORS - Laravel
```php
// config/cors.php
'paths' => ['api/*', 'widget/*', 'sanctum/csrf-cookie'],
```

---

## 📦 ARCHIVOS A MODIFICAR

### En el Proyecto Helpdesk

| Archivo | Cambios |
|---------|---------|
| `resources/views/widget/index.blade.php` | Rediseño completo UI |
| `resources/views/widget/tickets/index.blade.php` | Card perfil + botones |
| `resources/views/auth/loading.blade.php` | Soporte SSO token |
| `app/Features/ExternalIntegration/Services/ExternalAuthService.php` | Refresh token |
| `app/Features/ExternalIntegration/Http/Controllers/ExternalAuthController.php` | Endpoint SSO |
| `docker/nginx/default.conf` | X-Frame-Options |
| `config/cors.php` | Rutas widget |

### En el Paquete helpdeskwidget

| Archivo | Cambios |
|---------|---------|
| `src/HelpdeskWidgetServiceProvider.php` | Registrar comando |
| `src/HelpdeskService.php` | Manejo token TTL |
| `src/View/Components/HelpdeskWidget.php` | Detección cambio usuario |
| `src/Console/Commands/InstallCommand.php` | **NUEVO** - Comando instalación |
| `resources/views/stubs/helpdesk-view.blade.php` | **NUEVO** - Template vista |
| `README.md` | Actualizar documentación |
| `composer.json` | Bump version a 2.0.0 |

---

## 📅 ORDEN DE IMPLEMENTACIÓN SUGERIDO

| # | Tarea | Prioridad | Estimado |
|---|-------|-----------|----------|
| 1 | Comando `helpdeskwidget:install` | 🔴 Alta | 30 min |
| 2 | Diseño UI - Logos side-by-side | 🔴 Alta | 1 hora |
| 3 | Diseño UI - Pantalla conexión | 🔴 Alta | 1 hora |
| 4 | Diseño UI - Empresa no registrada | 🟡 Media | 30 min |
| 5 | Card de perfil + botones | 🟡 Media | 45 min |
| 6 | Token refresh / detección cambio | 🟡 Media | 1 hora |
| 7 | SSO al sitio oficial | 🟢 Baja | 1 hora |
| 8 | Testing + deploy | 🔴 Alta | 30 min |

---

## ✅ CHECKLIST FINAL

- [x] Comando de instalación funcionando ✅ (InstallCommand.php)
- [x] UI con logos Helpdesk ↔ Empresa ✅ (widget/index.blade.php)
- [x] Pantalla de conexión (no auto-connect) ✅
- [x] Animación de conexión ✅ (CSS animations)
- [x] Card de perfil con cambio de avatar ✅ (widget/tickets/index.blade.php)
- [x] Botón "Salir del Centro de Soporte" ✅ (widgetTokenManager.logout)
- [x] Botón "Visitar Sitio Oficial" ✅ (básico, SSO TODO)
- [x] Manejo de token expirado ✅ (auto-refresh al 80%)
- [x] Detección de cambio de usuario ✅ (HelpdeskWidget.php)
- [x] Nginx/CORS configurado ✅ (falta docker compose restart nginx)
- [x] README actualizado ✅
- [ ] Nueva versión publicada (v2.0.0)

---

> **Nota**: Este documento sirve como guía técnica completa. Actualizarlo conforme se avance en la implementación.

---

## 📝 PROGRESO DE IMPLEMENTACIÓN

### Sesión 2025-12-12

**Completado:**
1. ✅ `paquete/src/Console/Commands/InstallCommand.php` - Nuevo comando de instalación
2. ✅ `paquete/src/HelpdeskWidgetServiceProvider.php` - Registrar comando
3. ✅ `paquete/README.md` - Documentación actualizada
4. ✅ `paquete/composer.json` - Versión 2.0.0
5. ✅ `resources/views/widget/index.blade.php` - Rediseño completo OAuth-style
6. ✅ `app/Features/ExternalIntegration/Http/Controllers/ExternalAuthController.php` - Endpoint refresh
7. ✅ `app/Features/ExternalIntegration/Services/ExternalAuthService.php` - validateTokenForRefresh
8. ✅ `app/Features/Authentication/Services/TokenService.php` - decodeTokenWithoutValidation
9. ✅ `routes/api.php` - Ruta /api/external/refresh
10. ✅ `paquete/src/HelpdeskService.php` - Refresh automático al 80%, detección cambio usuario
11. ✅ `paquete/src/View/Components/HelpdeskWidget.php` - Detección cambio usuario
12. ✅ `resources/views/layouts/widget.blade.php` - Token Manager con auto-refresh
13. ✅ `docker/nginx/default.conf` - Permitir iframes para /widget/
14. ✅ `config/cors.php` - Agregar widget/* a paths
15. ✅ `resources/views/widget/tickets/index.blade.php` - Card de perfil con avatar, logout, SSO

**Pendiente:**
- [ ] SSO completo (endpoint create-sso-token + loading page)
- [ ] `docker compose restart nginx` para aplicar cambios

