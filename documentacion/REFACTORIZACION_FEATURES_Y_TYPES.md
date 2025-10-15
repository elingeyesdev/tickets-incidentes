# Refactorización Completa - Features y Types

## Resumen General

Hemos completado una refactorización exhaustiva del frontend siguiendo las reglas de arquitectura establecidas en `.cursor/rules/arquitecture-frontend.mdc`. Esta refactorización incluye:

1. **VerifyEmail movido a Public**
2. **Types globales y específicos de Features**
3. **Lógica de negocio movida a Features**
4. **Estructura completa de Features con .gitkeep**
5. **Corrección del error "Page not found"**

---

## 1. VerifyEmail Movido a Public

### Problema
`VerifyEmail.tsx` estaba en `Pages/Auth/` pero no requiere autenticación previa (se accede desde un link de email).

### Solución
```bash
# Movido de:
resources/js/Pages/Auth/VerifyEmail.tsx

# A:
resources/js/Pages/Public/VerifyEmail.tsx
```

### Justificación
- Se accede desde un email público (no autenticado)
- Evita problemas de "doble auth" (intentar autenticar para acceder a una página de verificación)
- Mantiene consistencia: páginas públicas en `Public/`

---

## 2. Types Globales y Específicos

### A. Types Globales (`types/models.ts`)

Basados en la documentación de Features, creamos tipos completos para:

```typescript
// User & Profile
export interface User {
    id: string;
    userCode: string;
    email: string;
    emailVerified: boolean;
    status: UserStatus;
    profile: UserProfile;
    roleContexts: RoleContext[];
    // ...
}

export interface UserProfile {
    firstName: string;
    lastName: string;
    displayName: string;
    phoneNumber: string | null;
    avatarUrl: string | null;
    theme: 'light' | 'dark';
    language: 'es' | 'en';
    timezone: string;
    // ...
}

// Roles & Permissions
export type RoleCode = 'USER' | 'AGENT' | 'COMPANY_ADMIN' | 'PLATFORM_ADMIN';

export interface RoleContext {
    roleCode: RoleCode;
    roleName: string;
    company: CompanyBasicInfo | null;
    dashboardPath: string;
    assignedAt: string;
}

// Company
export interface Company {
    id: string;
    companyCode: string;
    name: string;
    email: string;
    status: CompanyStatus;
    plan: CompanyPlan;
    // ...
}

// Authentication
export interface AuthPayload {
    accessToken: string;
    refreshToken: string;
    tokenType: string;
    expiresIn: number;
    user: User;
    roleContexts: RoleContext[];
    sessionId: string;
    loginTimestamp: string;
}
```

### B. Types Específicos de Authentication (`Features/authentication/types.ts`)

```typescript
// Forms
export interface LoginFormData {
    email: string;
    password: string;
    rememberMe?: boolean;
}

export interface RegisterFormData {
    email: string;
    password: string;
    passwordConfirmation: string;
    firstName: string;
    lastName: string;
    acceptsTerms: boolean;
    acceptsPrivacyPolicy: boolean;
}

// GraphQL Inputs
export interface LoginInput {
    email: string;
    password: string;
}

export interface RegisterInput {
    email: string;
    password: string;
    passwordConfirmation: string;
    firstName: string;
    lastName: string;
    acceptsTerms: boolean;
    acceptsPrivacyPolicy: boolean;
}

// GraphQL Responses
export interface LoginResponse {
    login: AuthPayload;
}

export interface RegisterResponse {
    register: AuthPayload;
}

export interface VerifyEmailResponse {
    verifyEmail: {
        success: boolean;
        message: string;
        user: User;
    };
}

// Validation
export interface ValidationErrors {
    email?: string;
    password?: string;
    passwordConfirmation?: string;
    firstName?: string;
    lastName?: string;
    acceptsTerms?: string;
    acceptsPrivacyPolicy?: string;
}

// Context
export interface AuthContextValue {
    user: User | null;
    loading: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (data: RegisterInput) => Promise<void>;
    logout: (everywhere?: boolean) => Promise<void>;
    refreshUser: () => Promise<void>;
    canAccessRoute: (path: string) => boolean;
}
```

### C. Types GraphQL (Compatibilidad)

`types/graphql.ts` ahora re-exporta desde `Features` y `types/models.ts` para mantener compatibilidad con código existente:

```typescript
// Re-exports desde Features
export type {
    LoginInput,
    RegisterInput,
    LoginResponse,
    RegisterResponse,
    // ...
} from '@/Features/authentication/types';

// Re-exports desde models
export type {
    User,
    UserProfile,
    RoleCode,
    Company,
    // ...
} from './models';
```

---

## 3. Lógica de Negocio en Features

### A. Hook: `useLogin`

**Ubicación**: `Features/authentication/hooks/useLogin.ts`

**Responsabilidades**:
- Manejo del formulario de login
- Validaciones en tiempo real (email, password)
- Gestión de estado (touched, errors, loading)
- Llamada a GraphQL mutation
- Guardar tokens
- Redirección post-login

**Uso**:
```typescript
const {
    formData,
    setFormData,
    showPassword,
    setShowPassword,
    touched,
    setTouched,
    validation,
    loading,
    error,
    isFormValid,
    handleSubmit,
    handleGoogleLogin,
} = useLogin();
```

### B. Hook: `useRegister`

**Ubicación**: `Features/authentication/hooks/useRegister.ts`

**Responsabilidades**:
- Manejo del formulario de registro
- Validaciones en tiempo real (email, password, confirmación, nombres)
- Indicador de fortaleza de contraseña
- Gestión de estado (touched, errors, loading)
- Llamada a GraphQL mutation
- Guardar tokens
- Redirección post-registro

**Uso**:
```typescript
const {
    formData,
    setFormData,
    showPassword,
    setShowPassword,
    showPasswordConfirmation,
    setShowPasswordConfirmation,
    touched,
    setTouched,
    validation,
    loading,
    error,
    isFormValid,
    handleSubmit,
    handleGoogleRegister,
} = useRegister();
```

### C. Actualización de Páginas

**Login.tsx** y **Register.tsx** ahora son componentes "tontos" que solo renderizan UI:

```typescript
// Antes: 300+ líneas con lógica mezclada
// Ahora: ~200 líneas solo UI

import { useLogin } from '@/Features/authentication';

function LoginContent() {
    const { t } = useLocale();
    const { formData, setFormData, handleSubmit, ... } = useLogin();
    
    return (
        <form onSubmit={handleSubmit}>
            {/* Solo UI */}
        </form>
    );
}
```

---

## 4. Estructura Completa de Features

### Carpetas Creadas

```
resources/js/Features/
├── authentication/
│   ├── components/      (vacío - .gitkeep)
│   ├── hooks/
│   │   ├── index.ts     (exporta todos los hooks)
│   │   ├── useLogin.ts
│   │   └── useRegister.ts
│   ├── utils/           (vacío - .gitkeep)
│   ├── services/        (vacío - .gitkeep)
│   ├── types.ts         (tipos específicos del feature)
│   └── index.ts         (exporta todo el feature)
│
├── tickets/
│   ├── components/      (.gitkeep)
│   ├── hooks/           (.gitkeep)
│   ├── utils/           (.gitkeep)
│   ├── services/        (.gitkeep)
│   └── types/           (.gitkeep)
│
├── companies/
│   ├── components/      (.gitkeep)
│   ├── hooks/           (.gitkeep)
│   ├── utils/           (.gitkeep)
│   ├── services/        (.gitkeep)
│   └── types/           (.gitkeep)
│
├── agents/
│   ├── components/      (.gitkeep)
│   ├── hooks/           (.gitkeep)
│   ├── utils/           (.gitkeep)
│   ├── services/        (.gitkeep)
│   └── types/           (.gitkeep)
│
└── profile/
    ├── components/      (.gitkeep)
    ├── hooks/           (.gitkeep)
    ├── utils/           (.gitkeep)
    ├── services/        (.gitkeep)
    └── types/           (.gitkeep)
```

### Propósito de Cada Carpeta

- **`components/`**: Componentes específicos del feature (ej: `LoginForm`, `TicketCard`)
- **`hooks/`**: Lógica de negocio reutilizable (ej: `useLogin`, `useTickets`)
- **`utils/`**: Funciones auxiliares (ej: validaciones, formatters)
- **`services/`**: Lógica de API/negocio compleja (ej: `TicketService`)
- **`types/`**: Tipos TypeScript específicos del feature

---

## 5. Corrección del Error "Page not found"

### Problema
```
Uncaught (in promise) Error: Page not found: Public/Welcome
    resolve app.tsx:28
```

### Causa
El resolver de Inertia en `app.tsx` estaba buscando en `./pages/**/*.tsx` (lowercase) pero las páginas están en `./Pages/**/*.tsx` (PascalCase).

### Solución
```typescript
// app.tsx - ANTES
resolve: (name) => {
    const pages = import.meta.glob<any>('./pages/**/*.tsx', { eager: true });
    const page = pages[`./pages/${name}.tsx`];
    // ...
},

// app.tsx - AHORA
resolve: (name) => {
    const pages = import.meta.glob<any>('./Pages/**/*.tsx', { eager: true });
    const page = pages[`./Pages/${name}.tsx`];
    // ...
},
```

### Rutas Actualizadas
```php
// routes/web.php
Route::get('/verify-email', function (Request $request) {
    return Inertia::render('Public/VerifyEmail', [
        'token' => $request->query('token'),
    ]);
})->name('verify-email');
```

---

## 6. Imports Corregidos

### Problema
Algunos archivos importaban desde rutas incorrectas:
- `@/Layouts/PublicLayout` ❌
- `@/Layouts/AuthenticatedLayout` ❌

### Solución
```bash
# Corrección global de imports
find . -type f -name "*.tsx" -exec sed -i \
  "s|from '@/Layouts/PublicLayout'|from '@/Layouts/Public/PublicLayout'|g" {} +
```

### Rutas Correctas
```typescript
// ✅ CORRECTO
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { AuthenticatedLayout } from '@/Layouts/Authenticated/AuthenticatedLayout';
import { UserLayout } from '@/Layouts/User/UserLayout';
```

---

## 7. Diferencia: Global Types vs Feature Types

### Global Types (`types/models.ts`)
**Cuándo usar**:
- Modelos de dominio compartidos entre múltiples features
- Tipos reutilizados en toda la aplicación
- Interfaces de base de datos
- Enums y constantes globales

**Ejemplos**:
```typescript
// types/models.ts
export interface User { ... }          // Usado en Auth, Profile, Tickets, etc.
export interface Company { ... }       // Usado en Companies, Agents, etc.
export type RoleCode = 'USER' | 'AGENT' | ...;  // Usado en Auth, Permissions, etc.
```

### Feature Types (`Features/{feature}/types.ts`)
**Cuándo usar**:
- Tipos específicos de un feature que no se reutilizan
- Inputs/Outputs de GraphQL específicos del feature
- Estados internos del feature
- Validaciones y errores específicos

**Ejemplos**:
```typescript
// Features/authentication/types.ts
export interface LoginFormData { ... }         // Solo para login
export interface ValidationErrors { ... }      // Solo para validación de auth
export interface EmailVerificationState { ... } // Solo para verificación de email

// Features/tickets/types.ts
export interface TicketFormData { ... }        // Solo para crear/editar tickets
export interface TicketFilters { ... }         // Solo para filtrar tickets
```

### Regla General
1. **¿Se usa en 2+ features?** → `types/models.ts`
2. **¿Es específico de 1 feature?** → `Features/{feature}/types.ts`
3. **¿Es un modelo de dominio?** → `types/models.ts`
4. **¿Es estado interno/UI?** → `Features/{feature}/types.ts`

---

## 8. Estado Final del Proyecto

### ✅ Compilación Exitosa
```bash
npm run build
# ✓ 2979 modules transformed
# ✓ built in 2.98s
```

### ✅ Estructura de Carpetas
- `Pages/` (PascalCase)
- `Layouts/` (PascalCase)
- `Components/` (PascalCase)
- `Features/` (completa con .gitkeep)
- `types/` (global + models)
- `config/` (permissions, theme, i18n)

### ✅ Feature Authentication Completo
- ✅ Types definidos
- ✅ Hooks implementados (useLogin, useRegister)
- ✅ Páginas actualizadas (Login, Register)
- ✅ Validaciones en tiempo real
- ✅ Gestión de errores

### ✅ Features Preparados (estructura)
- ✅ tickets/
- ✅ companies/
- ✅ agents/
- ✅ profile/

---

## 9. Próximos Pasos Sugeridos

### A. Implementar Features Restantes
```bash
# Ejemplo: Feature Tickets
Features/tickets/
├── components/
│   ├── TicketCard.tsx
│   ├── TicketList.tsx
│   └── TicketFilters.tsx
├── hooks/
│   ├── useTickets.ts
│   ├── useTicketDetail.ts
│   └── useCreateTicket.ts
├── types.ts
└── index.ts
```

### B. Migrar Lógica Existente
- Mover componentes específicos de features de `Components/` a `Features/{feature}/components/`
- Crear hooks para cada funcionalidad
- Definir types específicos

### C. Testing
```typescript
// tests/Feature/authentication/useLogin.test.ts
describe('useLogin', () => {
    it('should validate email correctly', () => {
        // ...
    });
});
```

---

## 10. Documentos Relacionados

- `/documentacion/FEATURES_Y_ARQUITECTURA.md` - Explicación detallada de Features
- `/documentacion/REFACTORIZACION_COMPLETA_RESUMEN.md` - Resumen de refactorizaciones previas
- `/.cursor/rules/arquitecture-frontend.mdc` - Reglas de arquitectura

---

## 11. Comandos Útiles

### Crear nuevo Feature
```bash
# Crear estructura
mkdir -p resources/js/Features/{nombre}/{components,hooks,utils,services}
touch resources/js/Features/{nombre}/types.ts
touch resources/js/Features/{nombre}/index.ts

# Añadir .gitkeep a carpetas vacías
find resources/js/Features/{nombre} -type d -empty -exec touch {}/.gitkeep \;
```

### Limpiar cache
```bash
docker exec helpdesk_app php artisan optimize:clear
docker exec helpdesk_app php artisan view:clear
npm run build
```

### Verificar imports
```bash
# Buscar imports incorrectos
grep -r "from '@/layouts" resources/js/  # Debe ser Layouts (PascalCase)
grep -r "from '@/pages" resources/js/    # Debe ser Pages (PascalCase)
grep -r "from '@/components" resources/js/ # Debe ser Components (PascalCase)
```

---

**Fecha**: 2025-10-13  
**Autor**: AI Assistant (Claude)  
**Estado**: ✅ COMPLETADO

