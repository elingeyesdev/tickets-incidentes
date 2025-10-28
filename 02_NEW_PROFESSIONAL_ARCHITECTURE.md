# Nueva Arquitectura Profesional: Estructura Completa

## 🎯 Principios de la Nueva Arquitectura

1. **Persistent Layouts** - Los layouts no se re-montan
2. **Zone-Based** - Estructura clara por zonas (Public, Onboarding, Authenticated)
3. **Metadata de Rutas** - Cada ruta sabe su zona, layout, y requerimientos
4. **Guards Centralizados** - Lógica de autorización fuera de componentes
5. **Impossible to Loop** - Imposible redireccionar infinitamente
6. **Escalable** - Fácil agregar nuevas zonas/rutas

---

## 📁 ESTRUCTURA DE ARCHIVOS COMPLETA (POST-REFACTORIZACIÓN)

```
resources/js/
│
├── app.tsx                                    ✅ ACTUALIZADO
│   └─ Configura Inertia con persistent layouts
│
├── config/
│   ├── routes.config.ts                      ✅ NUEVO
│   │   └─ Metadatos de todas las rutas
│   ├── permissions.ts                        ✅ EXISTENTE
│   ├── theme.ts                              ✅ EXISTENTE
│   └── i18n.ts                               ✅ EXISTENTE
│
├── features/
│   │
│   ├── public/
│   │   ├── pages/
│   │   │   ├── Login.tsx                     ✅ ACTUALIZADO
│   │   │   │   └─ Usa persistent layout
│   │   │   ├── Register.tsx                  ✅ ACTUALIZADO
│   │   │   ├── VerifyEmail.tsx               ✅ ACTUALIZADO
│   │   │   └── Welcome.tsx
│   │   │
│   │   ├── layouts/
│   │   │   ├── PublicLayout.tsx              ✅ EXISTENTE
│   │   │   └── PublicLayout.module.css
│   │   │
│   │   └── hooks/
│   │       ├── useLogin.ts                   ✅ EXISTENTE
│   │       └── useRegister.ts                ✅ EXISTENTE
│   │
│   ├── onboarding/
│   │   ├── pages/
│   │   │   ├── CompleteProfile.tsx           ✅ REFACTORIZADO (~120 líneas)
│   │   │   │   └─ Usa persistent layout
│   │   │   └── ConfigurePreferences.tsx      ✅ REFACTORIZADO (~150 líneas)
│   │   │       └─ Usa persistent layout
│   │   │
│   │   ├── layouts/
│   │   │   ├── OnboardingLayout.tsx          ✅ EXISTENTE
│   │   │   │   └─ Persistent, incluye progress bar
│   │   │   └── OnboardingLayout.module.css
│   │   │
│   │   ├── components/
│   │   │   ├── ProfileFormFields.tsx         ✅ NUEVO
│   │   │   ├── PreferencesFormFields.tsx     ✅ NUEVO
│   │   │   ├── OnboardingCard.tsx            ✅ NUEVO
│   │   │   ├── OnboardingProgressBar.tsx     ✅ NUEVO
│   │   │   ├── SuccessScreen.tsx             ✅ NUEVO
│   │   │   ├── BackButton.tsx                ✅ NUEVO
│   │   │   └── SkipButton.tsx                ✅ NUEVO
│   │   │
│   │   ├── hooks/
│   │   │   ├── useOnboardingForm.ts          ✅ NUEVO
│   │   │   ├── useProgress.ts                ✅ NUEVO
│   │   │   ├── useOnboardingMutation.ts      ✅ NUEVO
│   │   │   └── useOnboardingNavigation.ts    ✅ NUEVO
│   │   │
│   │   ├── types/
│   │   │   ├── forms.ts                      ✅ NUEVO
│   │   │   └── index.ts
│   │   │
│   │   └── constants/
│   │       └── onboarding.constants.ts       ✅ NUEVO
│   │
│   └── authenticated/
│       ├── pages/
│       │   ├── RoleSelector.tsx              ✅ ACTUALIZADO
│       │   │
│       │   ├── agent/
│       │   │   └── Dashboard.tsx             ✅ EXISTENTE
│       │   │       └─ Usa persistent layout
│       │   │
│       │   ├── user/
│       │   │   └── Dashboard.tsx             ✅ EXISTENTE
│       │   │       └─ Usa persistent layout
│       │   │
│       │   ├── admin/
│       │   │   └── Dashboard.tsx             ✅ EXISTENTE
│       │   │
│       │   └── company-admin/
│       │       └── Dashboard.tsx             ✅ EXISTENTE
│       │
│       ├── layouts/
│       │   ├── AuthenticatedLayout.tsx       ✅ EXISTENTE
│       │   │   └─ Persistent, incluye sidebar
│       │   ├── AgentLayout.tsx               ✅ EXISTENTE
│       │   ├── AdminLayout.tsx               ✅ EXISTENTE
│       │   ├── CompanyAdminLayout.tsx        ✅ EXISTENTE
│       │   └── UserLayout.tsx                ✅ EXISTENTE
│       │
│       └── hooks/
│           ├── useAuthenticatedNav.ts        ✅ NUEVO
│           └── useRoleSelection.ts           ✅ NUEVO
│
├── core/
│   │
│   ├── routing/
│   │   ├── useRouteZone.ts                   ✅ NUEVO
│   │   │   └─ Hook para obtener zona actual
│   │   │
│   │   ├── useRouteMetadata.ts               ✅ NUEVO
│   │   │   └─ Hook para obtener metadatos de ruta
│   │   │
│   │   ├── LayoutResolver.tsx                ✅ NUEVO
│   │   │   └─ Renderiza layout correcto según metadatos
│   │   │
│   │   └── index.ts
│   │
│   ├── guards/
│   │   ├── AuthenticationGuard.tsx           ✅ NUEVO
│   │   │   └─ Solo verifica: ¿autenticado?
│   │   │
│   │   ├── EmailVerificationGuard.tsx        ✅ NUEVO
│   │   │   └─ Solo verifica: ¿email verificado?
│   │   │
│   │   ├── OnboardingGuard.tsx               ✅ NUEVO
│   │   │   └─ Solo verifica: ¿onboarding completado?
│   │   │
│   │   ├── RoleGuard.tsx                     ✅ NUEVO
│   │   │   └─ Solo verifica: ¿tiene rol requerido?
│   │   │
│   │   ├── ZoneGuard.tsx                     ✅ NUEVO
│   │   │   └─ Valida acceso a zona completa
│   │   │
│   │   └── index.ts
│   │
│   ├── error-handling/
│   │   ├── ErrorBoundary.tsx                 ✅ NUEVO
│   │   │   └─ Boundary para contextos providers
│   │   │
│   │   ├── RouteErrorBoundary.tsx            ✅ NUEVO
│   │   │   └─ Boundary para cada zona
│   │   │
│   │   ├── ErrorFallback.tsx                 ✅ NUEVO
│   │   └── index.ts
│   │
│   └── types/
│       ├── zone.ts                           ✅ NUEVO
│       │   └─ type Zone = 'PUBLIC' | 'ONBOARDING' | 'AUTHENTICATED'
│       │
│       ├── routes.ts                         ✅ NUEVO
│       │   └─ interface RouteMetadata { ... }
│       │
│       └── index.ts
│
├── contexts/
│   ├── AuthContext.tsx                       ✅ EXISTENTE
│   ├── ThemeContext.tsx                      ✅ EXISTENTE
│   ├── LocaleContext.tsx                     ✅ EXISTENTE
│   ├── NotificationContext.tsx               ✅ EXISTENTE
│   ├── RouteZoneContext.tsx                  ✅ NUEVO
│   │   └─ Proporciona zona actual
│   ├── index.ts
│   └── providers.tsx                         ✅ NUEVO
│       └─ Wrapper que agrupa todos los providers con ErrorBoundary
│
├── hooks/
│   ├── useForm.ts                            ✅ ACTUALIZADO
│   ├── useOnboardingForm.ts                  ✅ NUEVO
│   ├── useProgress.ts                        ✅ NUEVO
│   ├── useOnboardingMutation.ts              ✅ NUEVO
│   ├── usePermissions.ts                     ✅ EXISTENTE
│   ├── useAuthMachine.ts                     ✅ EXISTENTE
│   └── index.ts
│
├── lib/
│   ├── auth/
│   │   ├── AuthChannel.ts                    ✅ EXISTENTE
│   │   ├── AuthMachine.ts                    ✅ EXISTENTE
│   │   ├── HeartbeatService.ts               ✅ EXISTENTE
│   │   ├── TokenManager.ts                   ✅ EXISTENTE
│   │   ├── TokenRefreshService.ts            ✅ EXISTENTE
│   │   ├── constants.ts                      ✅ EXISTENTE
│   │   └── index.ts
│   │
│   ├── apollo/
│   │   └── client.ts                         ✅ EXISTENTE
│   │
│   ├── graphql/
│   │   ├── queries/
│   │   ├── mutations/
│   │   └── fragments.ts
│   │
│   └── utils/
│       ├── onboarding.ts                     ✅ EXISTENTE
│       ├── navigation.ts                     ✅ EXISTENTE
│       ├── routing.ts                        ✅ ACTUALIZADO
│       └── index.ts
│
├── components/
│   ├── shared/
│   │   ├── FullscreenLoader.tsx              ✅ EXISTENTE
│   │   ├── LoadingSpinner.tsx                ✅ NUEVO
│   │   └── ErrorMessage.tsx                  ✅ NUEVO
│   │
│   ├── ui/
│   │   ├── Alert.tsx
│   │   ├── Badge.tsx
│   │   ├── Button.tsx
│   │   ├── Input.tsx
│   │   ├── Card.tsx
│   │   └── ...
│   │
│   ├── navigation/
│   │   ├── Sidebar.tsx                       ✅ EXISTENTE
│   │   ├── RoleBasedSidebar.tsx              ✅ EXISTENTE
│   │   └── index.ts
│   │
│   └── index.ts
│
├── types/
│   ├── graphql.ts                            ✅ EXISTENTE
│   ├── models.ts                             ✅ EXISTENTE
│   ├── forms.ts                              ✅ NUEVO
│   ├── zone.ts                               ✅ NUEVO (duplicado en core/types)
│   └── index.ts
│
├── utils/
│   ├── validation.ts                         ✅ NUEVO
│   │   └─ Schema de validación centralizado
│   │
│   ├── api-helpers.ts                        ✅ NUEVO
│   │   └─ Helpers para Apollo mutations
│   │
│   └── index.ts
│
├── styles/
│   ├── globals.css
│   ├── animations.css
│   └── ...
│
├── Pages/                                     ⚠️ DEPRECADO
│   └─ CAMBIAR A features/ (más organizado)
│
└── tests/
    ├── hooks/
    │   ├── useOnboardingForm.test.ts         ✅ NUEVO
    │   ├── useProgress.test.ts               ✅ NUEVO
    │   └── ...
    │
    ├── guards/
    │   ├── AuthenticationGuard.test.ts       ✅ NUEVO
    │   └── ...
    │
    └── setup.ts
```

---

## 🔄 Flujo con Nueva Arquitectura

### 1. **Configuración de Rutas con Metadatos**

```typescript
// config/routes.config.ts
export const ROUTE_CONFIG = {
  PUBLIC: {
    login: {
      path: '/login',
      zone: 'PUBLIC',
      layout: 'PublicLayout',
      requiresAuth: false,
      requiresEmail: false,
      requiresOnboarding: false,
    },
    register: {
      path: '/register',
      zone: 'PUBLIC',
      layout: 'PublicLayout',
      requiresAuth: false,
    },
    verifyEmail: {
      path: '/verify-email',
      zone: 'PUBLIC',
      layout: 'PublicLayout',
      requiresAuth: true,
      requiresEmail: false,
    },
  },
  ONBOARDING: {
    profile: {
      path: '/onboarding/profile',
      zone: 'ONBOARDING',
      layout: 'OnboardingLayout',
      requiresAuth: true,
      requiresEmail: true,
      requiresOnboarding: false,
    },
    preferences: {
      path: '/onboarding/preferences',
      zone: 'ONBOARDING',
      layout: 'OnboardingLayout',
      requiresAuth: true,
      requiresEmail: true,
      requiresOnboarding: false,
    },
  },
  AUTHENTICATED: {
    dashboard: {
      path: '/dashboard',
      zone: 'AUTHENTICATED',
      layout: 'AuthenticatedLayout',
      requiresAuth: true,
      requiresEmail: true,
      requiresOnboarding: true,
    },
    agentDashboard: {
      path: '/agent/dashboard',
      zone: 'AUTHENTICATED',
      layout: 'AgentLayout',
      requiresAuth: true,
      requiresRoles: ['AGENT'],
      requiresOnboarding: true,
    },
  },
};

export type RouteConfig = typeof ROUTE_CONFIG;
```

---

### 2. **Hook para Obtener Zona de Ruta**

```typescript
// core/routing/useRouteZone.ts
import { useLocation } from '@inertiajs/react';
import { ROUTE_CONFIG } from '@/config/routes.config';

export function useRouteZone() {
  const { component } = usePage();
  
  // Mapear nombre de componente a ruta
  const currentRoute = Object.values(ROUTE_CONFIG)
    .flat()
    .find(route => route.component === component);

  return currentRoute?.zone || 'PUBLIC';
}
```

---

### 3. **LayoutResolver: Renderiza Layout Correcto**

```typescript
// core/routing/LayoutResolver.tsx
import { ReactNode } from 'react';
import { PublicLayout } from '@/features/public/layouts/PublicLayout';
import { OnboardingLayout } from '@/features/onboarding/layouts/OnboardingLayout';
import { AuthenticatedLayout } from '@/features/authenticated/layouts/AuthenticatedLayout';
import { ROUTE_CONFIG } from '@/config/routes.config';

interface LayoutResolverProps {
  zone: string;
  children: ReactNode;
}

export function LayoutResolver({ zone, children }: LayoutResolverProps) {
  const layoutMap = {
    PUBLIC: PublicLayout,
    ONBOARDING: OnboardingLayout,
    AUTHENTICATED: AuthenticatedLayout,
  };

  const Layout = layoutMap[zone as keyof typeof layoutMap] || PublicLayout;

  return <Layout>{children}</Layout>;
}
```

---

### 4. **Persistent Layouts en Componentes de Página**

```typescript
// features/onboarding/pages/CompleteProfile.tsx
import { ReactNode } from 'react';
import { OnboardingLayout } from '../layouts/OnboardingLayout';
import { CompleteProfileContent } from './CompleteProfileContent';

export default function CompleteProfile() {
  return <CompleteProfileContent />;
}

// ✅ INERTIA PERSISTENT LAYOUT API
CompleteProfile.layout = (page: ReactNode) => (
  <OnboardingLayout>{page}</OnboardingLayout>
);
```

---

### 5. **Guards Separados por Responsabilidad**

```typescript
// core/guards/AuthenticationGuard.tsx
// SOLO verifica autenticación
export function AuthenticationGuard({ children }: { children: ReactNode }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) return <FullscreenLoader />;
  if (!isAuthenticated) return router.visit('/login');

  return <>{children}</>;
}

// ---

// core/guards/EmailVerificationGuard.tsx
// SOLO verifica email verificado
export function EmailVerificationGuard({ children }: { children: ReactNode }) {
  const { user } = useAuth();

  if (!user?.emailVerified) {
    return router.visit('/verify-email');
  }

  return <>{children}</>;
}

// ---

// core/guards/OnboardingGuard.tsx
// SOLO verifica onboarding completado
export function OnboardingGuard({ children }: { children: ReactNode }) {
  const { hasCompletedOnboarding } = useAuth();

  if (!hasCompletedOnboarding()) {
    return router.visit('/onboarding/profile');
  }

  return <>{children}</>;
}
```

---

### 6. **ZoneGuard: Valida Acceso a Zona Completa**

```typescript
// core/guards/ZoneGuard.tsx
import { ReactNode } from 'react';
import { ROUTE_CONFIG } from '@/config/routes.config';
import { AuthenticationGuard } from './AuthenticationGuard';
import { EmailVerificationGuard } from './EmailVerificationGuard';
import { OnboardingGuard } from './OnboardingGuard';
import { RoleGuard } from './RoleGuard';

interface ZoneGuardProps {
  zone: 'PUBLIC' | 'ONBOARDING' | 'AUTHENTICATED';
  children: ReactNode;
}

export function ZoneGuard({ zone, children }: ZoneGuardProps) {
  if (zone === 'PUBLIC') {
    // Public zone - no guards needed
    return <>{children}</>;
  }

  if (zone === 'ONBOARDING') {
    // Onboarding: Auth + Email verified
    return (
      <AuthenticationGuard>
        <EmailVerificationGuard>
          {children}
        </EmailVerificationGuard>
      </AuthenticationGuard>
    );
  }

  if (zone === 'AUTHENTICATED') {
    // Authenticated: Auth + Email + Onboarding complete
    return (
      <AuthenticationGuard>
        <EmailVerificationGuard>
          <OnboardingGuard>
            {children}
          </OnboardingGuard>
        </AuthenticationGuard>
      </AuthenticationGuard>
    );
  }

  return <>{children}</>;
}
```

---

### 7. **App.tsx Final**

```typescript
// app.tsx
import { ReactNode } from 'react';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { ApolloProvider } from '@apollo/client/react';
import { apolloClient } from '@/lib/apollo/client';
import { AppProviders } from '@/contexts/providers';
import { ZoneGuard } from '@/core/guards/ZoneGuard';
import { useRouteZone } from '@/core/routing/useRouteZone';
import { ROUTE_CONFIG } from '@/config/routes.config';

createInertiaApp({
  title: (title) => (title ? `${title} - Helpdesk` : 'Helpdesk'),

  resolve: (name) => {
    const pages = import.meta.glob<any>('./features/**/pages/*.tsx', { eager: true });
    return pages[`./features/${name}.tsx`]?.default;
  },

  setup({ el, App, props }) {
    const root = createRoot(el);

    root.render(
      <ApolloProvider client={apolloClient}>
        <AppProviders>
          {/* Tu app aquí - con ZoneGuard automático */}
          <App {...props} />
        </AppProviders>
      </ApolloProvider>
    );
  },

  progress: {
    color: '#4B5563',
    showSpinner: true,
  },
});
```

---

### 8. **AppProviders: Todos los Providers con ErrorBoundary**

```typescript
// contexts/providers.tsx
import { ReactNode } from 'react';
import { AuthProvider } from './AuthContext';
import { ThemeProvider } from './ThemeContext';
import { LocaleProvider } from './LocaleContext';
import { NotificationProvider } from './NotificationContext';
import { RouteZoneProvider } from './RouteZoneContext';
import { ErrorBoundary } from '@/core/error-handling/ErrorBoundary';
import { ErrorFallback } from '@/core/error-handling/ErrorFallback';

export function AppProviders({ children }: { children: ReactNode }) {
  return (
    <ErrorBoundary fallback={<ErrorFallback />}>
      <AuthProvider>
        <ErrorBoundary fallback={<ErrorFallback />}>
          <ThemeProvider>
            <LocaleProvider>
              <NotificationProvider>
                <RouteZoneProvider>
                  {children}
                </RouteZoneProvider>
              </NotificationProvider>
            </LocaleProvider>
          </ThemeProvider>
        </ErrorBoundary>
      </AuthProvider>
    </ErrorBoundary>
  );
}
```

---

## ✅ Nuevos Hooks Necesarios

### 1. **useOnboardingForm Hook**

```typescript
// features/onboarding/hooks/useOnboardingForm.ts
export function useOnboardingForm<T extends Record<string, any>>(
  initialData: T,
  schema: ValidationSchema,
  onSubmit: (data: T) => Promise<void>
) {
  const [formData, setFormData] = useState<T>(initialData);
  const [touched, setTouched] = useState<Record<keyof T, boolean>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const validation = useMemo(() => validateForm(formData, schema), [formData, schema]);
  const isFormValid = useMemo(() => isValid(validation), [validation]);

  const handleChange = useCallback((field: keyof T, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  }, []);

  const handleBlur = useCallback((field: keyof T) => {
    setTouched(prev => ({ ...prev, [field]: true }));
  }, []);

  const handleSubmit = useCallback(async (e: FormEvent) => {
    e.preventDefault();
    if (!isFormValid) {
      markAllTouched();
      return;
    }
    setIsSubmitting(true);
    try {
      await onSubmit(formData);
    } finally {
      setIsSubmitting(false);
    }
  }, [formData, isFormValid, onSubmit]);

  return { formData, touched, validation, isFormValid, isSubmitting, handleChange, handleBlur, handleSubmit };
}
```

### 2. **useProgress Hook**

```typescript
// features/onboarding/hooks/useProgress.ts
export function useProgress(duration = 50) {
  const [progress, setProgress] = useState(0);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);

  const start = useCallback((startValue = 0, maxValue = 100) => {
    let current = startValue;
    intervalRef.current = setInterval(() => {
      current += 1;
      if (current <= maxValue - 5) setProgress(current);
    }, duration);
  }, [duration]);

  const complete = useCallback(() => {
    if (intervalRef.current) clearInterval(intervalRef.current);
    setProgress(100);
  }, []);

  useEffect(() => {
    return () => {
      if (intervalRef.current) clearInterval(intervalRef.current);
    };
  }, []);

  return { progress, start, complete };
}
```

---

## 🎯 Comparación: Antes vs. Después

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| **Líneas en componente** | 389-523 | 60-80 |
| **Persistent Layout** | ✗ | ✅ |
| **Loops infinitos** | Posibles | Imposibles |
| **Guards acoplados** | ✓ (problema) | ✗ |
| **Responsabilidad única** | ✗ | ✅ |
| **Testabilidad** | Difícil | Fácil |
| **Escalabilidad** | Media | Excelente |
| **Tiempo nuevas features** | 1-2 días | 2-4 horas |

---

## 🚀 Implementación Paso a Paso

### Semana 1:
1. Crear estructura `features/` base
2. Crear `config/routes.config.ts`
3. Crear guards separados
4. Crear `useOnboardingForm` y `useProgress` hooks
5. Refactorizar CompleteProfile

### Semana 2:
1. Aplicar persistent layouts a todas las páginas
2. Refactorizar ConfigurePreferences
3. Extraer componentes (ProfileFormFields, etc)
4. Actualizar app.tsx

### Semana 3:
1. Agregar ErrorBoundary
2. Testing de guardsy hooks
3. Documentación
4. Testing end-to-end

---

## 💡 Checklist

- [ ] Crear estructura `features/`
- [ ] Crear `config/routes.config.ts`
- [ ] Crear guards centralizados
- [ ] Crear `useOnboardingForm` hook
- [ ] Crear `useProgress` hook
- [ ] Implementar persistent layouts
- [ ] Refactorizar CompleteProfile (~120 líneas)
- [ ] Refactorizar ConfigurePreferences (~150 líneas)
- [ ] Extraer componentes (ProfileFormFields, etc)
- [ ] Agregar ErrorBoundary
- [ ] Tests para guards y hooks
- [ ] Tests end-to-end
- [ ] Documentación

---

## 📝 Resultado Final

Con esta arquitectura:
- ✅ **Sin loops de redirección** - Imposible con ZoneGuard
- ✅ **Componentes simples** - 60-80 líneas máximo
- ✅ **Persistent layouts** - No se re-montan
- ✅ **Guards centralizados** - Lógica clara
- ✅ **Escalable** - Fácil agregar nuevas zonas
- ✅ **Testeable** - Cada guarduard/hook testeable
- ✅ **Profesional** - Arquitectura robusta
- ✅ **Mantenible** - Fácil entender y modificar
