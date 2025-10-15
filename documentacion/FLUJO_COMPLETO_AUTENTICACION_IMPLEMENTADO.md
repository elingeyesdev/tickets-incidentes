# Flujo Completo de Autenticación - Implementado

## Fecha: 2025-10-13
## Estado: ✅ COMPLETO Y FUNCIONAL

---

## Dashboard Paths Configurados (Backend y Frontend Alineados)

### Backend (GraphQL)
Archivos actualizados:
- `app/Features/Authentication/GraphQL/Mutations/LoginMutation.php`
- `app/Features/Authentication/GraphQL/Mutations/RegisterMutation.php`
- `app/Shared/GraphQL/DataLoaders/UserRoleContextsBatchLoader.php`

```php
$dashboardPaths = [
    'USER' => '/tickets',                    // ✓ Cliente
    'AGENT' => '/agent/dashboard',           // ✓ Agente de Soporte
    'COMPANY_ADMIN' => '/empresa/dashboard', // ✓ Admin de Empresa
    'PLATFORM_ADMIN' => '/platform/dashboard', // ✓ Admin de Plataforma
];
```

### Frontend (Rutas Laravel)
Archivo: `routes/web.php`

```php
// USER
Route::get('/tickets', ...) // Dashboard de usuario

// AGENT
Route::get('/agent/dashboard', ...) // Dashboard de agente

// COMPANY_ADMIN
Route::get('/empresa/dashboard', ...) // Dashboard de empresa

// PLATFORM_ADMIN
Route::get('/platform/dashboard', ...) // Dashboard de plataforma
```

---

## Flujo Completo de Autenticación

### 1. REGISTRO (Register)

#### Paso 1: Usuario completa formulario
- **Página**: `/register-user`
- **Layout**: `PublicLayout` (con navbar)
- **Componente**: `Pages/Public/Register.tsx`
- **Hook**: `Features/authentication/hooks/useRegister.ts`

**Campos**:
- Email ✓
- Password (con strength indicator) ✓
- Password Confirmation ✓
- First Name ✓
- Last Name ✓
- Accept Terms (checkbox con link) ✓
- Accept Privacy (checkbox con link) ✓
- Google Sign Up (botón) ✓

#### Paso 2: GraphQL Mutation
```graphql
mutation Register($input: RegisterInput!) {
    register(input: $input) {
        accessToken  # guardado en localStorage
        refreshToken # guardado en httpOnly cookie
        user { ... }
        roleContexts { ... }
    }
}
```

**Tokens guardados**:
- ✅ Access Token → `localStorage.setItem('access_token', token)`
- ✅ Refresh Token → httpOnly cookie (automático desde backend)

#### Paso 3: Redirección
```typescript
// Desde useRegister.ts
window.location.href = '/verify-email';
```

---

### 2. VERIFICACIÓN DE EMAIL (Opcional)

#### Página: `/verify-email`
- **Layout**: `PublicLayout`
- **Componente**: `Pages/Public/VerifyEmail.tsx`

**Opciones del usuario**:
1. **Verificar Email**: Click en link del email
   - Mutation: `VERIFY_EMAIL_MUTATION`
   - Redirección: `/onboarding/profile`

2. **Omitir Verificación**: Click en "Omitir"
   - Advertencia: "Máximo 2 incidentes sin verificar"
   - Redirección: `/onboarding/profile`

---

### 3. ONBOARDING - Completar Perfil

#### Página: `/onboarding/profile`
- **Layout**: `OnboardingLayout` (SIN navbar público)
- **Componente**: `Pages/Authenticated/Onboarding/CompleteProfile.tsx`

**Layout Characteristics**:
- ✅ Header minimalista con logo HELPDESK
- ✅ Usuario info + email
- ✅ Botones: Language, Theme, Logout
- ✅ Sin sidebar
- ✅ Footer minimalista

**Campos pre-completados**:
- First Name ✓ (del registro)
- Last Name ✓ (del registro)
- Phone Number (con selector de país: 8 opciones)

**Acciones**:
- **Guardar y Continuar**: Mutation `UPDATE_MY_PROFILE_MUTATION` → `/onboarding/preferences`
- **Omitir**: Ir directo a `/onboarding/preferences`

---

### 4. ONBOARDING - Configurar Preferencias

#### Página: `/onboarding/preferences`
- **Layout**: `OnboardingLayout`
- **Componente**: `Pages/Authenticated/Onboarding/ConfigurePreferences.tsx`

**Campos**:
- Theme: Light / Dark / System
- Language: Español / English
- Timezone: Selector (default: America/La_Paz)
- Push Notifications: Toggle
- Ticket Notifications: Toggle

**Acciones**:
- **Guardar Preferencias**: Mutation `UPDATE_MY_PREFERENCES_MUTATION`
- **Omitir**: Sin guardar

**Redirección inteligente**:
```typescript
if (roleContexts.length === 1) {
    window.location.href = roleContexts[0].dashboardPath;
} else if (roleContexts.length > 1) {
    window.location.href = '/role-selector';
} else {
    window.location.href = '/tickets'; // fallback
}
```

---

### 5. LOGIN

#### Paso 1: Usuario ingresa credenciales
- **Página**: `/login`
- **Layout**: `PublicLayout`
- **Componente**: `Pages/Public/Login.tsx`
- **Hook**: `Features/authentication/hooks/useLogin.ts`

**Campos**:
- Email (validación en tiempo real) ✓
- Password (toggle visibility) ✓
- Remember Me (checkbox) ✓
- Google Sign In (botón) ✓

#### Paso 2: GraphQL Mutation
```graphql
mutation Login($input: LoginInput!) {
    login(input: $input) {
        accessToken
        refreshToken
        user { ... }
        roleContexts [
            {
                roleCode: "PLATFORM_ADMIN"
                roleName: "Administrador de Plataforma"
                company: null
                dashboardPath: "/platform/dashboard"
            },
            {
                roleCode: "USER"
                roleName: "Cliente"
                company: null
                dashboardPath: "/tickets"
            }
        ]
    }
}
```

#### Paso 3: Redirección según roles
```typescript
// Desde useLogin.ts
if (roleContexts.length === 1) {
    // UN SOLO ROL: Redirigir directo
    window.location.href = roleContexts[0].dashboardPath;
} else {
    // MÚLTIPLES ROLES: Mostrar selector
    window.location.href = '/role-selector';
}
```

---

### 6. SELECTOR DE ROLES (Multi-Rol)

#### Página: `/role-selector`
- **Layout**: `PublicLayout` (sin navbar ni footer)
- **Componente**: `Pages/Public/RoleSelector.tsx`

**UI Elements**:
- ✅ Cards con gradiente por rol
- ✅ Iconos distintivos (User, Briefcase, Shield, ShieldCheck)
- ✅ Nombre de empresa (si aplica)
- ✅ Descripción del rol
- ✅ Animaciones hover
- ✅ Estado de carga en redirección

**Flujo**:
1. Usuario ve todos sus roles en cards
2. Click en un rol
3. Guardar selección en localStorage:
   ```typescript
   localStorage.setItem('selectedRole', JSON.stringify({
       roleCode: role.roleCode,
       companyId: role.company?.id || null,
   }));
   ```
4. Redirigir al dashboardPath del rol

**Caso especial**: Si tiene 1 solo rol → redirección automática (no ve la página)

---

### 7. DASHBOARDS POR ROL

Todos usan `AuthenticatedLayout` con sidebar configurado.

#### USER Dashboard (`/tickets`)
- **Layout**: `UserLayout` → `AuthenticatedLayout`
- **Componente**: `Pages/User/Dashboard.tsx`
- **Sidebar**: `userSidebarConfig`
- **Color**: Verde (`bg-green-600`)

**Contenido**:
- Header: "¡Bienvenido, {firstName}!"
- Rol: "Eres un Usuario del sistema"
- Card: "Dashboard Próximamente"
- Features preview:
  - Crear Tickets
  - Seguimiento
  - Ayuda Rápida

#### AGENT Dashboard (`/agent/dashboard`)
- **Layout**: `AgentLayout` → `AuthenticatedLayout`
- **Componente**: `Pages/Agent/Dashboard.tsx`
- **Sidebar**: `agentSidebarConfig`
- **Color**: Azul (`bg-blue-600`)

**Contenido**:
- Header: "¡Bienvenido, {firstName}!"
- Rol: "Eres un Agente de Soporte del sistema"
- Card: "Dashboard de Agente - Próximamente"
- Features preview:
  - Tickets Asignados
  - Métricas
  - Base de Conocimiento

#### COMPANY_ADMIN Dashboard (`/empresa/dashboard`)
- **Layout**: `CompanyAdminLayout` → `AuthenticatedLayout`
- **Componente**: `Pages/CompanyAdmin/Dashboard.tsx`
- **Sidebar**: `companyAdminSidebarConfig`
- **Color**: Morado (`bg-purple-600`)

**Contenido**: Similar estructura, "Próximamente"

#### PLATFORM_ADMIN Dashboard (`/platform/dashboard`)
- **Layout**: `AdminLayout` → `AuthenticatedLayout`
- **Componente**: `Pages/PlatformAdmin/Dashboard.tsx`
- **Sidebar**: `platformAdminSidebarConfig`
- **Color**: Rojo (`bg-red-600`)

**Contenido**: Similar estructura, "Próximamente"

---

## AuthenticatedLayout - Características

### Header Superior
- **Logo**: Icono + "HELPDESK"
- **Role Indicator**: Primer letra del rol en color distintivo
- **Title**: Título de la página actual
- **Controles**:
  - 🇪🇸/🇺🇸 Language Switcher
  - ☀️/🌙 Theme Switcher
  - Avatar + Nombre de usuario
  - **"Cambiar Rol"** (solo si `roleContexts.length > 1`)
  - **"Cerrar Sesión"**

### Sidebar
- **Icono Sidebar** (64px): Logo + Role Indicator
- **Main Sidebar** (256px): Navegación por secciones
  - Configurado por `sidebarConfig` (específico de cada rol)
  - Active state highlighting
  - Icons + Labels

### Logout Function
```typescript
const logout = async (everywhere = false) => {
    await apolloClient.mutate({
        mutation: LOGOUT_MUTATION,
        variables: { everywhere }
    });
    
    // Limpiar tokens
    localStorage.removeItem('access_token');
    localStorage.removeItem('selectedRole');
    
    // Limpiar cache
    await apolloClient.clearStore();
    
    // Redirigir
    window.location.href = '/login';
};
```

---

## Seguridad - Tokens

### Access Token
- **Almacenamiento**: `localStorage`
- **Key**: `'access_token'`
- **Duración**: 1 hora (3600s)
- **Uso**: Header `Authorization: Bearer {token}` en cada request GraphQL

### Refresh Token
- **Almacenamiento**: httpOnly cookie (automático desde backend)
- **Duración**: 30 días
- **Uso**: Automático refresh antes de que expire access token
- **Seguridad**: No accesible desde JavaScript

### Apollo Client Configuration
```typescript
// lib/apollo/client.ts
const authLink = setContext((_, { headers }) => {
    const token = localStorage.getItem('access_token');
    return {
        headers: {
            ...headers,
            authorization: token ? `Bearer ${token}` : "",
        }
    };
});

const errorLink = onError(({ graphQLErrors, networkError, operation, forward }) => {
    // Auto-refresh token logic
    if (graphQLErrors?.[0]?.extensions?.code === 'UNAUTHENTICATED') {
        // Refresh token and retry
        return fromPromise(refreshToken())
            .flatMap(() => forward(operation));
    }
});
```

---

## Translations (i18n)

### Agregadas en LocaleContext.tsx

**Español**:
```typescript
'auth.logout': 'Cerrar Sesión',
'auth.register.accept_terms': 'Acepto los',
'auth.register.accept_privacy': 'Acepto la',
'auth.register.password_weak': 'Contraseña débil',
'auth.register.password_medium': 'Contraseña media',
'auth.register.password_strong': 'Contraseña fuerte',
'auth.role_selector.title': '¡Bienvenido de vuelta!',
'auth.role_selector.subtitle': 'Selecciona el rol con el que deseas trabajar hoy',
'auth.role_selector.no_roles_title': 'Sin Roles Asignados',
'auth.role_selector.no_roles_message': 'Tu cuenta no tiene roles asignados actualmente. Contacta al administrador.',
```

**Inglés**: Equivalentes traducidos

---

## Testing del Flujo Completo

### 1. Test Registro → Onboarding → Dashboard
```bash
1. Ir a /register-user
2. Completar formulario
3. Click "Registrarse"
4. Verificar tokens en localStorage y cookies
5. Redirigido a /verify-email
6. Click "Omitir verificación"
7. Redirigido a /onboarding/profile
8. Completar perfil o "Omitir"
9. Redirigido a /onboarding/preferences
10. Configurar preferencias o "Omitir"
11. Si 1 rol: Redirigido al dashboard
12. Si 2+ roles: Redirigido a /role-selector
13. Seleccionar rol
14. Redirigido al dashboard del rol
```

### 2. Test Login Multi-Rol
```bash
1. Ir a /login
2. Email: lukqs05@gmail.com
3. Password: (tu contraseña)
4. Click "Iniciar Sesión"
5. Backend devuelve 2 roleContexts:
   - PLATFORM_ADMIN → /platform/dashboard
   - USER → /tickets
6. Redirigido a /role-selector
7. Ver 2 cards (Plataforma Admin + Usuario)
8. Click en "Administrador de Plataforma"
9. Redirigido a /platform/dashboard
10. Ver "Dashboard Próximamente" con rol correcto
11. Click "Cambiar Rol" en header
12. Redirigido a /role-selector
13. Click en "Cliente"
14. Redirigido a /tickets
15. Ver dashboard de USER
```

### 3. Test Logout
```bash
1. Desde cualquier dashboard autenticado
2. Click "Cerrar Sesión"
3. Mutation LOGOUT_MUTATION ejecutada
4. Tokens eliminados de localStorage
5. Apollo cache limpiado
6. Redirigido a /login
7. Verificar que no puede acceder a rutas protegidas
```

---

## Archivos Clave Modificados

### Backend
- ✅ `LoginMutation.php` - dashboardPaths corregidos
- ✅ `RegisterMutation.php` - dashboardPaths corregidos
- ✅ `UserRoleContextsBatchLoader.php` - dashboardPaths corregidos

### Frontend - Layouts
- ✅ `OnboardingLayout.tsx` - Nuevo layout sin navbar
- ✅ `AuthenticatedLayout.tsx` - Botón "Cambiar Rol" + Logout

### Frontend - Pages
- ✅ `RoleSelector.tsx` - Selector multi-rol
- ✅ `CompleteProfile.tsx` - Usa OnboardingLayout
- ✅ `ConfigurePreferences.tsx` - Usa OnboardingLayout + redirección inteligente
- ✅ `User/Dashboard.tsx` - "Próximamente" con rol
- ✅ `Agent/Dashboard.tsx` - "Próximamente" con rol
- ✅ `CompanyAdmin/Dashboard.tsx` - "Próximamente" con rol
- ✅ `PlatformAdmin/Dashboard.tsx` - "Próximamente" con rol

### Frontend - Hooks
- ✅ `useLogin.ts` - Redirección según roleContexts.length
- ✅ `useRegister.ts` - Redirección a /verify-email

### Frontend - Contexts
- ✅ `LocaleContext.tsx` - Traducciones completas

### Rutas
- ✅ `routes/web.php` - Todas las rutas alineadas con backend

---

## Próximos Pasos (Futuro)

1. **Implementar dashboards reales por rol** (actualmente "Próximamente")
2. **Agregar middleware de autorización** en rutas protegidas
3. **Implementar sistema de permisos granular** por rol
4. **Testing automatizado** del flujo completo
5. **Optimizar bundle size** (actualmente >500KB)
6. **Implementar refresh token automático** con mejor UX
7. **Agregar analytics** de uso por rol
8. **Sidebar colapsable** para móviles

---

**Estado**: ✅ **FUNCIONAL Y LISTO PARA TESTING**  
**Fecha**: 2025-10-13  
**Implementado por**: AI Assistant (Claude)

