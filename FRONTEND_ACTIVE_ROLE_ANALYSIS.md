# 🔍 ANÁLISIS EXHAUSTIVO: Implementación Frontend Active Role System

## 📋 RESUMEN EJECUTIVO

El sistema actual de autenticación frontend YA tiene una base sólida que almacena `active_role` en localStorage, pero **NO usa la API `POST /api/auth/select-role`** para persistir el cambio en el backend. Esto causa que el JWT no se actualice con el nuevo `active_role`, haciendo que las políticas del backend no funcionen correctamente.

---

## 🗺️ MAPEO COMPLETO DE ARCHIVOS A MODIFICAR

### 1. Archivos de Autenticación Core

| Archivo | Propósito | Cambios Necesarios |
|---------|-----------|-------------------|
| `resources/js/lib/auth/TokenManager.js` | Gestión de tokens JWT | ✅ **Agregar** método `selectRole()` para llamar API |
| `resources/js/alpine/stores/authStore.js` | Estado global Alpine.js | ✅ **Agregar** métodos `selectRole()`, `getAvailableRoles()` |
| `resources/js/lib/api/ApiClient.js` | Cliente HTTP | ✅ Sin cambios necesarios |

### 2. Vistas de Autenticación

| Archivo | Propósito | Cambios Necesarios |
|---------|-----------|-------------------|
| `resources/views/public/login.blade.php` | Login UI | ✅ **Modificar** para llamar API `select-role` después de detectar múltiples roles |
| `resources/views/auth-flow/role-selector.blade.php` | Selector de rol | 🔴 **REESCRIBIR COMPLETAMENTE** para usar API `select-role` |
| `resources/views/public/register.blade.php` | Registro | ✅ **Verificar** manejo de rol único |
| `resources/views/public/welcome.blade.php` | Landing | ✅ **Verificar** detección de roles |

### 3. Layout y Navegación

| Archivo | Propósito | Cambios Necesarios |
|---------|-----------|-------------------|
| `resources/views/layouts/authenticated.blade.php` | Layout principal | ✅ **Agregar** lógica para detectar múltiples roles |
| `resources/views/app/shared/navbar.blade.php` | Barra de navegación | 🔴 **AGREGAR** botón "Cambiar Rol" si usuario tiene 2+ roles |
| `resources/views/app/shared/sidebar.blade.php` | Menú lateral | ✅ **Agregar** opción "Cambiar Rol" en menú |

### 4. Modales/Componentes Nuevos

| Archivo | Propósito | Estado |
|---------|-----------|--------|
| `resources/views/app/shared/role-switch-modal.blade.php` | Modal para cambiar rol | 🆕 **CREAR** |

---

## 🔄 FLUJO ACTUAL vs FLUJO CORRECTO

### ❌ Flujo Actual (INCORRECTO)

```
Login → API retorna JWT con roles[]
     ↓
Frontend detecta múltiples roles
     ↓
Redirige a /role-selector
     ↓
role-selector.blade.php:
  - Lee roles desde JWT en localStorage
  - Usuario selecciona rol
  - GUARDA en localStorage.setItem('active_role', {...})  ⚠️ SOLO LOCAL
  - Redirige al dashboard
     ↓
Dashboard carga con active_role de localStorage
     ↓
API requests usan JWT ORIGINAL (sin active_role claim)  ❌
     ↓
Backend usa getActiveCompanyId() pero JWT NO tiene active_role  ❌
```

### ✅ Flujo Correcto (A IMPLEMENTAR)

```
Login → API retorna JWT con roles[]
     ↓
Frontend detecta múltiples roles
     ↓
Redirige a /role-selector
     ↓
role-selector.blade.php:
  - Lee roles desde JWT
  - Usuario selecciona rol
  - LLAMA API: POST /api/auth/select-role  ✅
  - API retorna NUEVO JWT con active_role claim  ✅
  - Guarda nuevo JWT en localStorage  ✅
  - Redirige al dashboard
     ↓
Dashboard carga con nuevo JWT
     ↓
API requests usan JWT con active_role claim  ✅
     ↓
Backend usa getActiveCompanyId() correctamente  ✅
```

---

## 📝 DETALLE DE CAMBIOS POR ARCHIVO

### 1. `role-selector.blade.php` - REESCRITURA COMPLETA

**Problema actual:**
```javascript
// LÍNEAS 309-325 - SOLO guarda en localStorage, NO llama API
async selectRole(roleCode, companyId) {
    // ...
    const activeRole = {
        code: selectedRole.code,
        company_id: selectedRole.company_id || null,
        company_name: selectedRole.company_name || null
    };
    localStorage.setItem('active_role', JSON.stringify(activeRole));  // ⚠️ Solo local
    
    // Redirect to role dashboard
    const dashboardUrl = this.getDashboardUrl(roleCode);
    window.location.href = dashboardUrl;  // JWT no cambia
}
```

**Solución:**
```javascript
async selectRole(roleCode, companyId) {
    try {
        const accessToken = localStorage.getItem('access_token');
        
        // 1. LLAMAR API para obtener nuevo JWT con active_role
        const response = await fetch('/api/auth/select-role', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${accessToken}`,
            },
            body: JSON.stringify({
                role_code: roleCode,
                company_id: companyId
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to select role');
        }
        
        const data = await response.json();
        
        // 2. Guardar NUEVO JWT con active_role claim
        localStorage.setItem('access_token', data.data.access_token);
        localStorage.setItem('active_role', JSON.stringify(data.data.active_role));
        
        // 3. Redirigir al dashboard con el NUEVO token
        const dashboardUrl = this.getDashboardUrl(roleCode);
        window.location.href = `/auth/prepare-web?token=${data.data.access_token}&redirect=${encodeURIComponent(dashboardUrl)}`;
        
    } catch (error) {
        console.error('Error selecting role:', error);
        this.errorMessage = 'Error al seleccionar el rol: ' + error.message;
        this.error = true;
    }
}
```

---

### 2. `navbar.blade.php` - AGREGAR BOTÓN CAMBIAR ROL

**Ubicación:** Entre "Active Role Display" y "Notifications"

```html
<!-- Role Switcher Button (Only if user has multiple roles) -->
<li class="nav-item" x-data="roleSwitcher()" x-show="hasMultipleRoles" x-cloak>
    <a class="nav-link" href="#" @click.prevent="openRoleSwitcher()" title="Cambiar Rol">
        <i class="fas fa-exchange-alt"></i>
        <span class="d-none d-md-inline ml-1">Cambiar Rol</span>
    </a>
</li>
```

**Script nuevo para navbar:**
```javascript
function roleSwitcher() {
    return {
        hasMultipleRoles: false,
        
        init() {
            this.checkMultipleRoles();
        },
        
        checkMultipleRoles() {
            const token = localStorage.getItem('access_token');
            if (!token) return;
            
            try {
                const payload = JSON.parse(atob(token.split('.')[1]));
                this.hasMultipleRoles = payload.roles && payload.roles.length > 1;
            } catch (e) {
                console.error('Error checking roles:', e);
            }
        },
        
        openRoleSwitcher() {
            // Abrir modal de cambio de rol
            window.dispatchEvent(new CustomEvent('open-role-modal'));
        }
    };
}
```

---

### 3. `login.blade.php` - MODIFICAR FLUJO MULTI-ROL

**Cambio en líneas ~290-310:**

```javascript
// ACTUAL (líneas 290-310):
} else if (roles.length > 1) {
    // Múltiples roles: ir a role-selector
    this.successMessage = 'Sesión iniciada. Selecciona un rol...';
    this.success = true;

    setTimeout(() => {
        window.location.href = `/auth/prepare-web?token=${data.accessToken}&redirect=${encodeURIComponent('/auth-flow/role-selector')}`;
    }, 1500);
}

// ✅ CORRECTO - No necesita cambios aquí porque role-selector llamará la API
// El cambio está en role-selector.blade.php
```

---

### 4. NUEVO: `role-switch-modal.blade.php`

```html
<!-- Role Switch Modal -->
<div x-data="roleModal()" 
     x-show="isOpen" 
     x-cloak
     @open-role-modal.window="open()"
     class="modal fade show" 
     style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Cambiar Rol Activo
                </h5>
                <button type="button" class="close" @click="close()">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Loading -->
                <div x-show="loading" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Cargando roles...</p>
                </div>
                
                <!-- Roles List -->
                <div x-show="!loading" class="list-group">
                    <template x-for="role in roles" :key="role.code + (role.company_id || '')">
                        <button type="button" 
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                :class="{ 'active': isCurrentRole(role) }"
                                @click="selectRole(role)"
                                :disabled="switching">
                            <div>
                                <i :class="getRoleIcon(role.code)" class="mr-2"></i>
                                <strong x-text="getRoleName(role.code)"></strong>
                                <template x-if="role.company_name">
                                    <small class="d-block text-muted" x-text="role.company_name"></small>
                                </template>
                            </div>
                            <span x-show="isCurrentRole(role)" class="badge badge-success">
                                <i class="fas fa-check"></i> Actual
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function roleModal() {
    return {
        isOpen: false,
        loading: false,
        switching: false,
        roles: [],
        currentRole: null,
        
        async open() {
            this.isOpen = true;
            this.loading = true;
            await this.loadRoles();
            this.loading = false;
        },
        
        close() {
            this.isOpen = false;
        },
        
        async loadRoles() {
            const token = localStorage.getItem('access_token');
            
            const response = await fetch('/api/auth/available-roles', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            this.roles = data.data;
            this.currentRole = data.active_role;
        },
        
        isCurrentRole(role) {
            if (!this.currentRole) return false;
            return this.currentRole.code === role.code && 
                   this.currentRole.company_id === role.company_id;
        },
        
        getRoleIcon(code) {
            const icons = {
                'PLATFORM_ADMIN': 'fas fa-crown text-primary',
                'COMPANY_ADMIN': 'fas fa-building text-danger',
                'AGENT': 'fas fa-headset text-success',
                'USER': 'fas fa-user text-warning'
            };
            return icons[code] || 'fas fa-user';
        },
        
        getRoleName(code) {
            const names = {
                'PLATFORM_ADMIN': 'Administrador Global',
                'COMPANY_ADMIN': 'Administrador de Empresa',
                'AGENT': 'Agente de Soporte',
                'USER': 'Usuario Final'
            };
            return names[code] || code;
        },
        
        async selectRole(role) {
            if (this.isCurrentRole(role)) {
                this.close();
                return;
            }
            
            this.switching = true;
            
            try {
                const token = localStorage.getItem('access_token');
                
                const response = await fetch('/api/auth/select-role', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        role_code: role.code,
                        company_id: role.company_id
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to switch role');
                }
                
                const data = await response.json();
                
                // Actualizar localStorage con nuevo token
                localStorage.setItem('access_token', data.data.access_token);
                localStorage.setItem('active_role', JSON.stringify(data.data.active_role));
                
                // Actualizar cookie y recargar
                const dashboardUrl = role.dashboard_path || '/app/dashboard';
                window.location.href = `/auth/prepare-web?token=${data.data.access_token}&redirect=${encodeURIComponent(dashboardUrl)}`;
                
            } catch (error) {
                console.error('Error switching role:', error);
                alert('Error al cambiar de rol: ' + error.message);
            } finally {
                this.switching = false;
            }
        }
    };
}
</script>
```

---

### 5. `sidebar.blade.php` - AGREGAR OPCIÓN CAMBIAR ROL

**Agregar después de Dashboard, antes del menú de rol:**

```html
<!-- Switch Role (Only if multiple roles) -->
<template x-if="hasMultipleRoles">
    <li class="nav-item">
        <a href="#" @click.prevent="$dispatch('open-role-modal')" class="nav-link text-info">
            <i class="nav-icon fas fa-exchange-alt"></i>
            <p>Cambiar Rol</p>
        </a>
    </li>
</template>
```

**Agregar en `sidebarMenu()`:**
```javascript
hasMultipleRoles: false,

init() {
    // ... existing code ...
    this.checkMultipleRoles();
},

checkMultipleRoles() {
    const token = localStorage.getItem('access_token');
    if (!token) return;
    
    try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        this.hasMultipleRoles = payload.roles && payload.roles.length > 1;
    } catch (e) {
        console.error('Error checking roles:', e);
    }
}
```

---

### 6. `authenticated.blade.php` - INCLUIR MODAL

**Agregar antes del cierre de `</body>`:**

```php
{{-- Role Switch Modal (for multi-role users) --}}
@include('app.shared.role-switch-modal')
```

---

## 🧪 CASOS DE USO Y EDGE CASES

### Caso 1: Login con 1 rol
```
1. Usuario ingresa credenciales
2. API retorna JWT con roles = [{ code: 'USER', company_id: null }]
3. Frontend detecta 1 rol → auto-selecciona
4. Redirige directo a /app/user/dashboard
5. JWT ya tiene active_role (auto-asignado por backend para 1 rol)
```

### Caso 2: Login con 2+ roles
```
1. Usuario ingresa credenciales
2. API retorna JWT con roles = [
     { code: 'COMPANY_ADMIN', company_id: 'uuid-1' },
     { code: 'AGENT', company_id: 'uuid-2' }
   ]
3. Frontend detecta 2+ roles
4. Redirige a /auth-flow/role-selector
5. Usuario selecciona COMPANY_ADMIN de empresa 1
6. Frontend llama POST /api/auth/select-role
7. API retorna NUEVO JWT con active_role: { code: 'COMPANY_ADMIN', company_id: 'uuid-1' }
8. Frontend guarda nuevo JWT
9. Redirige a /app/company/dashboard
```

### Caso 3: Cambio de rol en sesión activa
```
1. Usuario está en /app/company/dashboard como COMPANY_ADMIN
2. Hace clic en "Cambiar Rol" en navbar
3. Modal muestra sus roles disponibles
4. Selecciona "Agente - Pil Andina"
5. Frontend llama POST /api/auth/select-role
6. API retorna NUEVO JWT con active_role: { code: 'AGENT', company_id: 'uuid-2' }
7. Frontend actualiza localStorage y cookie
8. Redirige a /app/agent/dashboard
```

### Caso 4: Refresh token preserva active_role
```
1. Token expira después de 60 minutos
2. TokenManager detecta expiración
3. Llama POST /api/auth/refresh con HttpOnly cookie
4. Backend preserva active_role del token anterior
5. Retorna nuevo JWT con mismo active_role
6. Usuario continúa sin interrupción
```

### Caso 5: Usuario con mismo rol en 2 empresas
```
Roles: [
  { code: 'COMPANY_ADMIN', company_id: 'victoria-uuid', company_name: 'Victoria Veterinaria' },
  { code: 'COMPANY_ADMIN', company_id: 'pil-uuid', company_name: 'Pil Andina' }
]

1. Usuario ve rol "Company Admin" listado 2 veces
2. Cada uno muestra el nombre de la empresa
3. Puede cambiar entre empresas manteniendo mismo rol
```

---

## 📊 MATRIZ DE IMPACTO

| Componente | Complejidad | Riesgo | Tiempo Est. |
|------------|-------------|--------|-------------|
| role-selector.blade.php | Alta | Medio | 2h |
| navbar.blade.php | Media | Bajo | 1h |
| role-switch-modal.blade.php | Alta | Bajo | 2h |
| sidebar.blade.php | Baja | Bajo | 30min |
| authenticated.blade.php | Baja | Bajo | 15min |
| login.blade.php | Baja | Bajo | 30min |
| TokenManager.js | Media | Medio | 1h |
| **TOTAL** | | | **~7-8 horas** |

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Fase 1: Core (Crítico)
- [ ] Reescribir `role-selector.blade.php` para usar API `select-role`
- [ ] Crear `role-switch-modal.blade.php`
- [ ] Incluir modal en `authenticated.blade.php`

### Fase 2: Navegación
- [ ] Agregar botón "Cambiar Rol" en `navbar.blade.php`
- [ ] Agregar opción "Cambiar Rol" en `sidebar.blade.php`
- [ ] Estilizar para que solo aparezca si usuario tiene 2+ roles

### Fase 3: Sincronización
- [ ] Verificar que `login.blade.php` funciona correctamente
- [ ] Verificar que `TokenManager.js` maneja bien los nuevos tokens
- [ ] Verificar que refresh token preserva active_role

### Fase 4: Testing
- [ ] Probar login con 1 rol
- [ ] Probar login con 2+ roles
- [ ] Probar cambio de rol en sesión
- [ ] Probar refresh después de cambio de rol
- [ ] Probar logout y re-login

### Fase 5: Seeder de Usuario Complejo
- [ ] Crear seeder con usuario de 4 roles
- [ ] Crear datos de prueba para cada empresa
- [ ] Verificar funcionamiento completo

---

## 🔗 ENDPOINTS API DISPONIBLES

| Endpoint | Método | Propósito |
|----------|--------|-----------|
| `/api/auth/login` | POST | Login, retorna JWT con roles[] |
| `/api/auth/refresh` | POST | Refresh token, preserva active_role |
| `/api/auth/select-role` | POST | Cambia rol activo, retorna nuevo JWT |
| `/api/auth/available-roles` | GET | Lista roles disponibles del usuario |

---

## 🎯 SIGUIENTE PASO RECOMENDADO

1. **Implementar `role-selector.blade.php`** primero (es el más crítico)
2. Crear el modal de cambio de rol
3. Integrar en navbar/sidebar
4. Crear seeder de usuario complejo para testing

¿Procedemos con la implementación?
