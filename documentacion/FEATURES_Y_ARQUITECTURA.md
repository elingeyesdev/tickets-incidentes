# 🏗️ FEATURES Y ARQUITECTURA - GUÍA COMPLETA

> Explicación detallada de la organización de carpetas y el concepto de Features
> Fecha: Octubre 2025

---

## 📚 TABLA DE CONTENIDOS

1. [¿Qué es Features?](#qué-es-features)
2. [Diferencias: Components vs Features vs Pages vs Layouts](#diferencias)
3. [Types Global vs Types por Feature](#types)
4. [Ejemplos Prácticos](#ejemplos)
5. [Reglas de Oro](#reglas)

---

## 🎯 ¿QUÉ ES FEATURES?

**Features/** es donde vive la **LÓGICA DE NEGOCIO** de tu aplicación.

### Analogía Simple:

Imagina que estás construyendo una casa:

- **`Components/`** = Ladrillos, puertas, ventanas (reutilizables en cualquier casa)
- **`Features/`** = Cocina, baño, sala (funcionalidades específicas de TU casa)
- **`Pages/`** = Habitaciones completas ya decoradas
- **`Layouts/`** = Planos de distribución de la casa

---

## 🔍 DIFERENCIAS CLAVE

### 1. **`Components/`** - UI Genérica Reutilizable

```
Components/
├── ui/
│   ├── Button.tsx          ← Botón genérico (sin lógica de negocio)
│   ├── Input.tsx           ← Input genérico
│   ├── Card.tsx            ← Card genérica
│   └── Alert.tsx           ← Alerta genérica
└── navigation/
    └── Sidebar.tsx         ← Sidebar genérico (recibe config)
```

**Características**:
- ✅ **Sin lógica de negocio** (solo UI)
- ✅ **Reutilizable en CUALQUIER proyecto**
- ✅ **Props genéricos**
- ✅ **No sabe de GraphQL, mutations, o features específicos**

**Ejemplo**:
```tsx
// Components/ui/Button.tsx
export const Button = ({ onClick, children, variant }) => (
    <button onClick={onClick} className={getVariantClass(variant)}>
        {children}
    </button>
);

// ✅ Genérico - se puede usar en cualquier proyecto
```

---

### 2. **`Features/`** - Lógica de Negocio Específica

```
Features/
├── authentication/
│   ├── hooks/
│   │   ├── useAuth.ts          ← Hook con lógica de auth
│   │   ├── useLogin.ts         ← Lógica específica de login
│   │   └── useRegister.ts      ← Lógica específica de registro
│   ├── components/
│   │   ├── LoginForm.tsx       ← Formulario CON lógica de login
│   │   ├── RegisterForm.tsx    ← Formulario CON lógica de registro
│   │   └── PasswordStrength.tsx ← Componente específico de auth
│   └── types.ts                ← Tipos SOLO de authentication
│
├── tickets/
│   ├── hooks/
│   │   ├── useTickets.ts       ← Lógica para obtener tickets
│   │   ├── useCreateTicket.ts  ← Lógica para crear ticket
│   │   └── useTicketFilters.ts ← Lógica de filtros
│   ├── components/
│   │   ├── TicketCard.tsx      ← Card específica de tickets
│   │   ├── TicketList.tsx      ← Lista con lógica de tickets
│   │   ├── CreateTicketForm.tsx ← Formulario con lógica
│   │   └── TicketStatusBadge.tsx ← Badge específico
│   └── types.ts                ← Tipos SOLO de tickets
│
└── profile/
    ├── hooks/
    │   ├── useProfile.ts
    │   └── useUpdateProfile.ts
    ├── components/
    │   ├── ProfileForm.tsx
    │   └── AvatarUpload.tsx
    └── types.ts
```

**Características**:
- ✅ **CON lógica de negocio**
- ✅ **Específico de TU aplicación**
- ✅ **Usa GraphQL, mutations, queries**
- ✅ **No es reutilizable en otros proyectos**

**Ejemplo**:
```tsx
// Features/authentication/components/LoginForm.tsx
import { useLogin } from '../hooks/useLogin';
import { Button, Input } from '@/Components/ui';

export const LoginForm = () => {
    const { login, loading, error } = useLogin();  // ← Lógica específica
    
    const handleSubmit = async (e) => {
        await login(formData);  // ← Lógica de negocio
    };
    
    return (
        <form onSubmit={handleSubmit}>
            <Input />  {/* ← Usa componente genérico */}
            <Button loading={loading} />  {/* ← Usa componente genérico */}
        </form>
    );
};

// ❌ NO reutilizable - específico de ESTE proyecto
```

---

### 3. **`Pages/`** - Páginas Completas (Inertia)

```
Pages/
├── Public/
│   ├── Welcome.tsx         ← Página completa de bienvenida
│   └── Login.tsx           ← Página completa de login
├── User/
│   └── Dashboard.tsx       ← Dashboard del usuario
└── Agent/
    └── Dashboard.tsx       ← Dashboard del agente
```

**Características**:
- ✅ **Páginas completas** con Layout
- ✅ **Orquesta Features y Components**
- ✅ **Punto de entrada de Inertia**

**Ejemplo**:
```tsx
// Pages/Public/Login.tsx
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { LoginForm } from '@/Features/authentication/components/LoginForm';

export default function Login() {
    return (
        <PublicLayout title="Login">
            <LoginForm />  {/* ← Usa feature completo */}
        </PublicLayout>
    );
}
```

---

### 4. **`Layouts/`** - Estructura de Página

```
Layouts/
├── Public/
│   └── PublicLayout.tsx    ← Layout para páginas públicas
├── User/
│   └── UserLayout.tsx      ← Layout para usuarios
└── Agent/
    └── AgentLayout.tsx     ← Layout para agentes
```

**Características**:
- ✅ **Estructura común** (header, sidebar, footer)
- ✅ **Wrappea Pages**
- ✅ **Contextos y providers**

---

## 📦 TYPES: Global vs Feature

### 1. **`types/`** (Global) - Tipos Compartidos

```typescript
// types/index.d.ts

// ✅ Usado por MUCHOS features
export interface User {
    id: string;
    email: string;
    profile: UserProfile;
    roleContexts: RoleContext[];
}

// ✅ Usado en TODA la app
export type RoleCode = 'USER' | 'AGENT' | 'COMPANY_ADMIN' | 'PLATFORM_ADMIN';

// ✅ Usado por TODAS las páginas de Inertia
export interface PageProps {
    auth?: {
        user: User;
    };
}
```

**Regla**: Si el tipo se usa en **2 o más features diferentes**, va en `types/` global.

---

### 2. **`Features/{feature}/types.ts`** - Tipos Específicos

```typescript
// Features/authentication/types.ts

// ❌ Solo usado en authentication
export interface LoginFormData {
    email: string;
    password: string;
    rememberMe: boolean;
}

// ❌ Solo usado en authentication
export interface RegisterFormData {
    email: string;
    password: string;
    firstName: string;
    lastName: string;
}
```

**Regla**: Si el tipo SOLO se usa en **ESE feature**, va en `Features/{feature}/types.ts`.

---

## 💡 EJEMPLOS PRÁCTICOS

### Ejemplo 1: Crear un Ticket

#### ❌ INCORRECTO (Todo en Components):
```
Components/ui/
└── CreateTicketButton.tsx    // ← Mezcla UI con lógica de negocio
```

#### ✅ CORRECTO (Separado):
```
Components/ui/
└── Button.tsx                 // ← UI genérica

Features/tickets/
├── hooks/
│   └── useCreateTicket.ts     // ← Lógica de negocio
├── components/
│   └── CreateTicketForm.tsx   // ← Componente con lógica específica
└── types.ts                   // ← Tipos específicos

Pages/User/Tickets/
└── Create.tsx                 // ← Orquesta todo
```

---

### Ejemplo 2: Login

#### Estructura Completa:

```
Components/ui/
├── Button.tsx                 // ← Botón genérico
├── Input.tsx                  // ← Input genérico
└── Card.tsx                   // ← Card genérica

Features/authentication/
├── hooks/
│   └── useLogin.ts            // ← Lógica: llama mutation, guarda token
├── components/
│   └── LoginForm.tsx          // ← Form: usa useLogin + Components/ui
└── types.ts
    └── LoginFormData          // ← Tipo específico de login

Pages/Public/
└── Login.tsx                  // ← Página: usa LoginForm + PublicLayout
```

**Flujo**:
1. `Pages/Public/Login.tsx` renderiza la página
2. Usa `Features/authentication/components/LoginForm.tsx`
3. LoginForm usa `Features/authentication/hooks/useLogin.ts` (lógica)
4. LoginForm usa `Components/ui/Button.tsx` y `Input.tsx` (UI)

---

## 🎯 REGLAS DE ORO

### 1. **Components/** - UI Sin Lógica
- ✅ Genérico, reutilizable
- ✅ Props simples
- ✅ No GraphQL, no mutations
- ❌ No lógica de negocio

### 2. **Features/** - Lógica de Negocio
- ✅ Específico de tu app
- ✅ Usa GraphQL, mutations
- ✅ Lógica en hooks
- ✅ Componentes con lógica específica
- ❌ No es reutilizable en otros proyectos

### 3. **Pages/** - Orquesta
- ✅ Combina Features + Components
- ✅ Usa Layouts
- ✅ Punto de entrada de Inertia

### 4. **Layouts/** - Estructura
- ✅ Header, Sidebar, Footer
- ✅ Providers y contextos

---

## 📊 COMPARACIÓN VISUAL

| Aspecto | Components | Features | Pages | Layouts |
|---------|-----------|----------|-------|---------|
| **Lógica de negocio** | ❌ No | ✅ Sí | ⚠️ Solo orquesta | ❌ No |
| **GraphQL/Mutations** | ❌ No | ✅ Sí | ❌ No | ❌ No |
| **Reutilizable** | ✅ Sí | ❌ No | ❌ No | ⚠️ Por rol |
| **Props** | Genéricos | Específicos | - | Config |
| **Ejemplo** | Button | LoginForm | Login.tsx | UserLayout |

---

## 🎓 CUÁNDO USAR QUÉ

### Pregúntate:

1. **"¿Es UI genérica sin lógica?"** → `Components/`
2. **"¿Es lógica específica de una funcionalidad?"** → `Features/`
3. **"¿Es una página completa?"** → `Pages/`
4. **"¿Es estructura común de varias páginas?"** → `Layouts/`

---

## 🚀 BENEFICIOS

### Con Features/:
- ✅ **Organización clara** por funcionalidad
- ✅ **Escalable** (agregar feature = nueva carpeta)
- ✅ **Fácil de mantener** (todo de tickets en un lugar)
- ✅ **Fácil de testear** (lógica aislada en hooks)
- ✅ **Trabajo en equipo** (cada dev un feature)

### Sin Features/ (todo mezclado):
- ❌ Difícil encontrar código
- ❌ Duplicación de lógica
- ❌ Difícil de mantener
- ❌ Difícil de testear
- ❌ Conflictos en git

---

## 📝 RESUMEN

```
Components/          ← UI genérica (Button, Input)
Features/            ← Lógica de negocio (useLogin, TicketForm)
Pages/               ← Páginas completas (orquesta todo)
Layouts/             ← Estructura común (header, sidebar)

types/               ← Tipos GLOBALES (User, RoleCode)
Features/*/types.ts  ← Tipos ESPECÍFICOS (LoginFormData)
```

---

**Autor**: Claude Sonnet 4.5  
**Proyecto**: HELPDESK Multi-Tenant  
**Fecha**: Octubre 2025

