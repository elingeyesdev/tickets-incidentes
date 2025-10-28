# Frontend Refactoring Plan - Helpdesk

## 📊 Current Structure Overview

```
resources/js/
├── Components/
│   ├── guards/
│   │   ├── OnboardingRoute.tsx          ✅ (nuevo)
│   │   ├── PublicRoute.tsx              ⚠️ (revisar)
│   │   └── index.ts
│   ├── Auth/
│   │   ├── AuthGuard.tsx                ⚠️ (revisar)
│   │   └── RoleSwitcher.tsx
│   ├── Skeleton/                        ✅ (bien organizado)
│   ├── ui/                              ✅ (bien)
│   ├── navigation/
│   ├── layout/
│   └── Shared/
│       └── FullscreenLoader.tsx
├── Features/
│   └── authentication/
│       ├── hooks/
│       │   ├── useLogin.ts              ✅ (bien)
│       │   └── useRegister.ts           ✅ (bien)
│       └── types.ts
├── Layouts/                             ✅ (bien)
├── Pages/
│   ├── Authenticated/Onboarding/
│   │   ├── CompleteProfile.tsx          🔴 (FAT - 389 líneas)
│   │   └── ConfigurePreferences.tsx     🔴 (FAT - 523 líneas)
│   ├── Public/
│   │   ├── VerifyEmail.tsx              ⚠️ (grande)
│   │   └── ...
│   └── ...
├── components/
│   └── Auth/AuthGuard.tsx
├── contexts/
│   ├── AuthContext.tsx                  ✅ (bien organizado)
│   ├── ThemeContext.tsx                 ✅
│   ├── LocaleContext.tsx                ✅
│   ├── NotificationContext.tsx          ✅
│   └── index.ts
├── hooks/
│   ├── useForm.ts                       ✅ (existe pero limitado)
│   ├── useAuthMachine.ts
│   ├── usePermissions.ts
│   └── index.ts
├── lib/
│   ├── auth/                            ✅ (bien)
│   ├── apollo/                          ✅ (bien)
│   ├── graphql/
│   │   ├── queries/
│   │   ├── mutations/
│   │   └── fragments.ts
│   └── utils/
├── config/                              ✅ (bien)
├── types/                               ✅ (bien)
└── tests/
```

---

## 🔴 PROBLEMAS IDENTIFICADOS

### 1. **Fat Components** (~400 líneas cada uno)
```
CompleteProfile.tsx (389 líneas)
ConfigurePreferences.tsx (523 líneas)
VerifyEmail.tsx (~427 líneas)
```

**Problemas:**
- Mezclan lógica de validación + UI + mutaciones
- Difícil de testear
- Difícil de mantener
- Mucho boilerplate repetido

---

### 2. **Patrones Repetidos Sin Extraer**
```typescript
// CompleteProfile.tsx línea 114-213
const handleSubmit = async (e: FormEvent) => { ... }

// ConfigurePreferences.tsx línea 83-237
const handleSubmit = async (e: FormEvent) => { ... }

// Mismo patrón:
// 1. Validar
// 2. Iniciar progress
// 3. Llamar mutation
// 4. Mostrar success/error
// 5. Redirigir
```

**Deberían estar en:** `useOnboardingStep()` hook reutilizable

---

### 3. **Guards Ineficientes**
```typescript
// AuthGuard.tsx línea 48-49
const isOnOnboardingPage = typeof window !== 'undefined' && 
    window.location.pathname.startsWith('/onboarding/');
```

**Problemas:**
- `window.location.pathname` es frágil
- Acoplado a URLs específicas
- Hace demasiado (auth + email + onboarding + role)

**Deberías:**
- Separar responsabilidades en guards específicos
- Usar React Router context en lugar de pathname

---

### 4. **Dependencias en useEffect Problemáticas**
```typescript
// AuthGuard.tsx línea 71
}, [authLoading, isAuthenticated, user, lastSelectedRole, allowedRoles]
```

**Problema:**
- `user` es objeto grande que cambia frecuentemente
- Causa re-renders innecesarios

**Mejor:**
```typescript
}, [authLoading, isAuthenticated, user?.id, lastSelectedRole, allowedRoles]
```

---

### 5. **Sin Error Boundaries**
```typescript
// Si falla en AuthProvider, TODA la app se cae
<AuthProvider>
    <ThemeProvider>
        <LocaleProvider>
            <NotificationProvider>
                <App />
            </NotificationProvider>
        </LocaleProvider>
    </ThemeProvider>
</AuthProvider>
```

**Necesitas:**
- ErrorBoundary en cada provider
- Fallback UI

---

### 6. **Progress Bar Duplicada**
```typescript
// CompleteProfile.tsx línea 152-158
let currentProgress = 0;
const progressInterval = setInterval(() => {
    currentProgress += 1;
    if (currentProgress <= 45) {
        setProgressPercentage(currentProgress);
    }
}, 50);

// ConfigurePreferences.tsx línea 156-163
// MISMO CÓDIGO repetido
```

**Deberías:**
- Crear `useProgress()` hook
- Garantizar cleanup en unmount

---

### 7. **TypeScript Underutilizado**
```typescript
// CompleteProfile.tsx línea 46-51
const [formData, setFormData] = useState({
    firstName: user?.displayName?.split(' ')[0] || '',
    lastName: user?.displayName?.split(' ').slice(1).join(' ') || '',
    phoneNumber: '',
    countryCode: '+591',
});
// No hay tipos explícitos
```

**Deberías:**
```typescript
type ProfileFormData = {
    firstName: string;
    lastName: string;
    phoneNumber: string;
    countryCode: string;
};
```

---

### 8. **Validación Sin Centralizar**
```typescript
// CompleteProfile.tsx línea 84-107
const validation = {
    firstName: { valid: ..., message: ... },
    lastName: { valid: ..., message: ... },
    phoneNumber: { valid: ..., message: ... },
};

// ConfigurePreferences.tsx
// Misma validación? No, son diferentes, pero el patrón es igual
```

---

## ✅ PLAN DE REFACTORIZACIÓN

### **PRIORIDAD 1 - Crítico (1-2 días)**

#### 1.1 Crear `useOnboardingForm()` Hook
**Archivo:** `hooks/useOnboardingForm.ts`

```typescript
export function useOnboardingForm<T extends Record<string, any>>(
    initialData: T,
    schema: Record<string, ValidationRule>,
    onSubmit: (data: T) => Promise<void>
) {
    const [formData, setFormData] = useState<T>(initialData);
    const [touched, setTouched] = useState<Record<string, boolean>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const validation = useMemo(() => validateForm(formData, schema), [formData, schema]);
    const isFormValid = useMemo(() => Object.values(validation).every(v => v.valid), [validation]);

    const handleChange = (field: keyof T, value: any) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleBlur = (field: keyof T) => {
        setTouched(prev => ({ ...prev, [field]: true }));
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!isFormValid) {
            setTouched(Object.keys(formData).reduce((acc, key) => ({ ...acc, [key]: true }), {}));
            return;
        }
        setIsSubmitting(true);
        try {
            await onSubmit(formData);
        } finally {
            setIsSubmitting(false);
        }
    };

    return {
        formData,
        touched,
        validation,
        isFormValid,
        isSubmitting,
        handleChange,
        handleBlur,
        handleSubmit,
    };
}
```

**Uso:**
```typescript
const { formData, validation, handleChange, handleSubmit } = useOnboardingForm(
    { firstName: '', lastName: '' },
    profileSchema,
    async (data) => {
        await updateProfile({ variables: { input: data } });
    }
);
```

---

#### 1.2 Crear `useProgress()` Hook
**Archivo:** `hooks/useProgress.ts`

```typescript
export function useProgress(duration = 50) {
    const [progress, setProgress] = useState(0);
    const intervalRef = useRef<NodeJS.Timeout | null>(null);

    const start = useCallback((startValue = 0, maxValue = 100) => {
        let current = startValue;
        intervalRef.current = setInterval(() => {
            current += 1;
            if (current <= maxValue - 5) {
                setProgress(current);
            }
        }, duration);
    }, [duration]);

    const complete = useCallback(() => {
        if (intervalRef.current) clearInterval(intervalRef.current);
        setProgress(100);
    }, []);

    const reset = useCallback(() => {
        if (intervalRef.current) clearInterval(intervalRef.current);
        setProgress(0);
    }, []);

    useEffect(() => {
        return () => {
            if (intervalRef.current) clearInterval(intervalRef.current);
        };
    }, []);

    return { progress, start, complete, reset };
}
```

---

#### 1.3 Extraer Componentes de CompleteProfile
**Archivo:** `Pages/Authenticated/Onboarding/components/ProfileFormFields.tsx`

```typescript
interface ProfileFormFieldsProps {
    formData: ProfileFormData;
    touched: Record<string, boolean>;
    validation: Record<string, ValidationResult>;
    onChange: (field: string, value: string) => void;
    onBlur: (field: string) => void;
}

export function ProfileFormFields({
    formData,
    touched,
    validation,
    onChange,
    onBlur,
}: ProfileFormFieldsProps) {
    return (
        <>
            <div className="grid grid-cols-2 gap-4">
                <Input
                    label="Nombre *"
                    value={formData.firstName}
                    onChange={(e) => onChange('firstName', e.target.value)}
                    onBlur={() => onBlur('firstName')}
                    error={touched.firstName && !validation.firstName.valid ? validation.firstName.message : undefined}
                    rightIcon={touched.firstName && formData.firstName ? (
                        validation.firstName.valid ? <CheckCircle2 /> : <AlertCircle />
                    ) : null}
                />
                <Input
                    label="Apellido *"
                    value={formData.lastName}
                    onChange={(e) => onChange('lastName', e.target.value)}
                    onBlur={() => onBlur('lastName')}
                    error={touched.lastName && !validation.lastName.valid ? validation.lastName.message : undefined}
                    rightIcon={touched.lastName && formData.lastName ? (
                        validation.lastName.valid ? <CheckCircle2 /> : <AlertCircle />
                    ) : null}
                />
            </div>
            {/* Teléfono */}
        </>
    );
}
```

---

#### 1.4 Refactorizar CompleteProfile
**Archivo:** `Pages/Authenticated/Onboarding/CompleteProfile.tsx` (reducido a ~120 líneas)

```typescript
import { useState } from 'react';
import { useMutation } from '@apollo/client/react';
import { OnboardingRoute } from '@/components/guards/OnboardingRoute';
import { OnboardingLayout } from '@/Layouts/Onboarding/OnboardingLayout';
import { useOnboardingForm } from '@/hooks/useOnboardingForm';
import { useProgress } from '@/hooks/useProgress';
import { useAuth, useNotification } from '@/contexts';
import { UPDATE_MY_PROFILE_MUTATION } from '@/lib/graphql/mutations/users.mutations';
import { ProfileFormFields } from './components/ProfileFormFields';
import { OnboardingProgressBar } from './components/OnboardingProgressBar';
import { OnboardingCard } from './components/OnboardingCard';

type ProfileFormData = {
    firstName: string;
    lastName: string;
    phoneNumber: string;
    countryCode: string;
};

const profileSchema = {
    firstName: { min: 2, max: 100 },
    lastName: { min: 2, max: 100 },
    phoneNumber: { min: 7, max: 15 },
};

export default function CompleteProfile() {
    return (
        <OnboardingRoute>
            <OnboardingLayout title="Completar Perfil">
                <CompleteProfileContent />
            </OnboardingLayout>
        </OnboardingRoute>
    );
}

function CompleteProfileContent() {
    const { user, refreshUser } = useAuth();
    const { success: showSuccess, error: showError } = useNotification();
    const { progress, start, complete } = useProgress();
    const [updateProfile] = useMutation(UPDATE_MY_PROFILE_MUTATION);

    const { formData, validation, isFormValid, isSubmitting, handleChange, handleBlur, handleSubmit } =
        useOnboardingForm<ProfileFormData>(
            {
                firstName: user?.displayName?.split(' ')[0] || '',
                lastName: user?.displayName?.split(' ').slice(1).join(' ') || '',
                phoneNumber: '',
                countryCode: '+591',
            },
            profileSchema,
            async (data) => {
                start(0, 50);
                try {
                    await updateProfile({
                        variables: { input: { firstName: data.firstName, lastName: data.lastName, phoneNumber: data.phoneNumber } },
                    });
                    complete();
                    showSuccess('✅ Perfil actualizado');
                    await refreshUser();
                    setTimeout(() => window.location.href = '/onboarding/preferences', 800);
                } catch (error) {
                    showError(error instanceof Error ? error.message : 'Error al actualizar');
                }
            }
        );

    return (
        <OnboardingCard>
            <form onSubmit={handleSubmit} className="space-y-6">
                <ProfileFormFields
                    formData={formData}
                    touched={Object.keys(formData).reduce((acc, key) => ({ ...acc, [key]: true }), {})}
                    validation={validation}
                    onChange={handleChange}
                    onBlur={handleBlur}
                />

                <div className="border-t pt-6">
                    <Button type="submit" disabled={!isFormValid || isSubmitting} isLoading={isSubmitting}>
                        Continuar
                    </Button>
                </div>
            </form>

            <OnboardingProgressBar progress={progress} />
        </OnboardingCard>
    );
}
```

---

### **PRIORIDAD 2 - Alta (2-3 días)**

#### 2.1 Crear `useOnboardingMutation()` Hook
**Archivo:** `hooks/useOnboardingMutation.ts`

Centraliza lógica de mutations con progress bar.

#### 2.2 Separar Guards por Responsabilidad
```
guards/
├── AuthenticationGuard.tsx    (solo auth)
├── EmailVerificationGuard.tsx (solo email)
├── OnboardingGuard.tsx        (solo onboarding)
├── RoleGuard.tsx              (solo role)
└── ComposedGuard.tsx          (combina los anteriores)
```

#### 2.3 Agregar Error Boundaries
```typescript
// contexts/ErrorBoundary.tsx
export function ErrorBoundary({ children }: { children: ReactNode }) {
    const [hasError, setHasError] = useState(false);

    useEffect(() => {
        const handler = () => setHasError(true);
        window.addEventListener('error', handler);
        return () => window.removeEventListener('error', handler);
    }, []);

    if (hasError) {
        return <ErrorFallback reset={() => setHasError(false)} />;
    }

    return <>{children}</>;
}
```

---

### **PRIORIDAD 3 - Media (3-4 días)**

#### 3.1 Crear Tipos Locales Explícitos
```typescript
// types/forms.ts
export type ProfileFormData = { ... };
export type PreferencesFormData = { ... };
export type ValidationResult = { valid: boolean; message: string };
```

#### 3.2 Refactorizar ConfigurePreferences
Misma estrategia que CompleteProfile.

#### 3.3 Mejorar useForm Hook
Actualizar `hooks/useForm.ts` para soportar más casos.

---

## 📁 ESTRUCTURA POST-REFACTORIZACIÓN

```
resources/js/
├── Components/
│   ├── guards/
│   │   ├── AuthenticationGuard.tsx  ✅ (NEW)
│   │   ├── EmailVerificationGuard.tsx ✅ (NEW)
│   │   ├── OnboardingGuard.tsx      ✅ (NEW)
│   │   ├── RoleGuard.tsx            ✅ (NEW)
│   │   ├── ComposedGuard.tsx        ✅ (NEW)
│   │   └── index.ts
│   ├── Auth/
│   │   └── ...
│   └── ErrorBoundary/              ✅ (NEW)
│       ├── ErrorBoundary.tsx
│       ├── ErrorFallback.tsx
│       └── index.ts
├── Features/
│   └── authentication/
│       └── hooks/
│           └── ...
├── Pages/
│   ├── Authenticated/Onboarding/
│   │   ├── CompleteProfile.tsx     🟢 (refactored: 120 líneas)
│   │   ├── ConfigurePreferences.tsx 🟢 (refactored: 150 líneas)
│   │   └── components/             ✅ (NEW)
│   │       ├── ProfileFormFields.tsx
│   │       ├── PreferencesFormFields.tsx
│   │       ├── OnboardingCard.tsx
│   │       ├── OnboardingProgressBar.tsx
│   │       └── SuccessScreen.tsx
│   └── ...
├── hooks/
│   ├── useForm.ts                 🟢 (actualizado)
│   ├── useOnboardingForm.ts       ✅ (NEW)
│   ├── useProgress.ts             ✅ (NEW)
│   ├── useOnboardingMutation.ts   ✅ (NEW)
│   └── index.ts
├── types/
│   ├── forms.ts                   ✅ (NEW)
│   ├── graphql.ts
│   └── index.ts
└── ...
```

---

## 📊 RESULTADOS ESPERADOS

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Líneas por componente | 389-523 | 120-150 | -70% |
| Testabilidad | ⭐⭐ | ⭐⭐⭐⭐⭐ | +300% |
| Reusabilidad | ⭐ | ⭐⭐⭐⭐ | +400% |
| Mantenibilidad | ⭐⭐ | ⭐⭐⭐⭐⭐ | +250% |
| Tiempo nuevas features | 1-2 días | 2-4 horas | -80% |
| Complejidad cognitiva | Alta | Baja | -60% |

---

## 🚀 IMPLEMENTACIÓN SUGERIDA

**Semana 1:**
1. Crear `useOnboardingForm()` hook
2. Crear `useProgress()` hook
3. Extraer componentes de CompleteProfile
4. Refactorizar CompleteProfile

**Semana 2:**
1. Aplicar lo mismo a ConfigurePreferences
2. Separar guards por responsabilidad
3. Agregar error boundaries

**Semana 3:**
1. Definir tipos locales
2. Testing de nuevos hooks
3. Documentación

---

## 💡 CHECKLIST

- [ ] useOnboardingForm hook creado
- [ ] useProgress hook creado
- [ ] ProfileFormFields componente extraído
- [ ] CompleteProfile refactorizado
- [ ] ConfigurePreferences refactorizado
- [ ] Guards separados por responsabilidad
- [ ] ErrorBoundary agregado
- [ ] Tipos locales definidos
- [ ] Tests creados
- [ ] Documentación actualizada

---

## 📝 CONCLUSIÓN

Tu código **está bien**, pero **no escala**. Con esta refactorización:
- ✅ Código más limpio y legible
- ✅ Más fácil de mantener
- ✅ Más fácil de testear
- ✅ Más fácil de extender
- ✅ Mejor experiencia de desarrollo

**Tiempo inversión:** 3-4 semanas  
**Retorno:** 10x productividad en futuras features
