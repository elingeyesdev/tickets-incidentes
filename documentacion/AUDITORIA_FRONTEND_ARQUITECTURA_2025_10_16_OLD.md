# 🏗️ AUDITORÍA FRONTEND - ARQUITECTURA 3 ZONAS
## Sistema Helpdesk | Fecha: 16 Octubre 2025

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Diagnóstico Detallado](#diagnóstico-detallado)
3. [Propuestas de Solución](#propuestas-de-solución)
4. [Plan de Implementación](#plan-de-implementación)
5. [Arquitectura Profesional Propuesta](#arquitectura-profesional-propuesta)

---

## 1. RESUMEN EJECUTIVO

### VEREDICTO GENERAL: ⭐⭐⭐⭐ (8.5/10)

**TU ARQUITECTURA TIENE CIMIENTOS SÓLIDOS Y PROFESIONALES.**

#### ✅ Fortalezas Principales

1. **Separación clara de 3 zonas** (Public, Onboarding, Authenticated)
2. **Middleware robusto** con logging y auditoría
3. **Autenticación JWT profesional** con refresh automático
4. **Contextos globales bien organizados** (Auth, Theme, Locale, Notifications)
5. **UX de onboarding fluida** con barra de progreso y validaciones

#### ⚠️ Áreas Críticas de Mejora

1. **Campo `onboarding_completed` sin actualización automática**
2. **Zona onboarding accesible después de completar**
3. **Lógica de onboarding duplicada** (frontend + backend)
4. **Falta guards de navegación en frontend**
5. **Servicio de onboarding no centralizado**

---

## 2. DIAGNÓSTICO DETALLADO

### 2.1 ARQUITECTURA ACTUAL: 3 ZONAS

```
┌─────────────────────────────────────────────────────────────┐
│                      ZONA PÚBLICA                           │
│  Middleware: guest:sanctum                                  │
│  Rutas: /, /login, /register-user, /solicitud-empresa      │
│  Layout: PublicLayout                                       │
│  Estado: ✅ BIEN IMPLEMENTADO                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
                  Usuario se registra
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    ZONA ONBOARDING                          │
│  Middleware: auth:sanctum                                   │
│  Rutas: /verify-email, /onboarding/*                       │
│  Layout: OnboardingLayout                                   │
│  Estado: ⚠️ FUNCIONAL PERO MEJORABLE                        │
│  Issues:                                                    │
│  - No impide re-acceso si ya completó                      │
│  - onboarding_completed no se actualiza automáticamente    │
└─────────────────────────────────────────────────────────────┘
                            ↓
              Usuario completa perfil + preferencias
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                  ZONA AUTHENTICATED                         │
│  Middleware: auth:sanctum + onboarding.completed + role:X  │
│  Rutas: /tickets, /agent/*, /empresa/*, /platform/*       │
│  Layout: AuthenticatedLayout                                │
│  Estado: ✅ BIEN IMPLEMENTADO                               │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 FLUJO DE ONBOARDING ACTUAL

```typescript
┌─────────────────────────────────────────────────────────┐
│  PASO 1: Registro                                       │
│  - Mutation: registerMutation                           │
│  - Crea usuario con onboarding_completed = false       │
│  - Guarda tokens (localStorage + httpOnly cookie)      │
│  - Redirect: /verify-email                             │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  PASO 2: Verificación Email (OPCIONAL)                 │
│  - Mutation: verifyEmailMutation                       │
│  - Marca email_verified = true                         │
│  - Permite "omitir" con advertencia (máx 2 tickets)   │
│  - ⚠️ ISSUE: Restricción no implementada en backend    │
│  - Redirect: /onboarding/profile                       │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  PASO 3: Completar Perfil                              │
│  - Mutation: updateMyProfileMutation                   │
│  - Actualiza: firstName, lastName, phoneNumber         │
│  - ❌ ISSUE: NO marca onboarding_completed = true      │
│  - Progreso: 0% → 50%                                  │
│  - Redirect: /onboarding/preferences                   │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  PASO 4: Configurar Preferencias                       │
│  - Mutation: updateMyPreferencesMutation               │
│  - Actualiza: theme, language, timezone, notifications │
│  - ❌ ISSUE: NO marca onboarding_completed = true      │
│  - Progreso: 50% → 100%                                │
│  - Redirect: dashboard o /role-selector                │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  PASO 5: Dashboard                                      │
│  - Middleware valida onboarding manualmente            │
│  - Verifica: firstName, lastName, roles.isNotEmpty()   │
│  - ⚠️ ISSUE: Lógica duplicada, no usa campo de BD     │
└─────────────────────────────────────────────────────────┘
```

### 2.3 PROBLEMAS IDENTIFICADOS

#### 🔴 CRÍTICO 1: Campo `onboarding_completed` Inactivo

**Ubicación del problema:**
- `/app/Features/UserManagement/Models/User.php` - Campo existe
- `/app/Features/UserManagement/Database/Migrations/2025_10_16_000001_add_onboarding_fields_to_users_table.php` - Migración creada
- Mutations NO actualizan el campo

**Evidencia:**
```php
// ✅ Campo existe en modelo
protected $fillable = [
    'onboarding_completed',
    'onboarding_completed_at',
];

// ❌ UpdateMyProfileMutation - No actualiza onboarding_completed
public function resolve($root, array $args)
{
    $user = auth()->user();
    $this->userService->updateProfile($user, $args['input']);

    // FALTA: $onboardingService->checkAndMarkCompleted($user);

    return $user->fresh(['profile', 'roleContexts']);
}

// ❌ UpdateMyPreferencesMutation - No actualiza onboarding_completed
public function resolve($root, array $args)
{
    $user = auth()->user();
    $this->userService->updatePreferences($user, $args['input']);

    // FALTA: $onboardingService->checkAndMarkCompleted($user);

    return $user->fresh(['profile', 'roleContexts']);
}
```

**Impacto:**
- Middleware hace verificaciones manuales (redundante)
- No hay registro de cuándo completó onboarding
- GraphQL necesita resolver custom para `onboardingCompleted`
- Dificulta auditoría y analytics

#### 🔴 CRÍTICO 2: Zona Onboarding Sin Protección Post-Completado

**Problema:**
Usuario que YA completó onboarding puede volver a `/onboarding/profile`.

**Ubicación:**
`/routes/web.php` (líneas 67-83)

```php
// ❌ ACTUAL: Sin validación de onboarding completado
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/onboarding/profile', fn() =>
        Inertia::render('Authenticated/Onboarding/CompleteProfile')
    )->name('onboarding.profile');

    Route::get('/onboarding/preferences', fn() =>
        Inertia::render('Authenticated/Onboarding/ConfigurePreferences')
    )->name('onboarding.preferences');
});

// ✅ DEBERÍA SER:
Route::middleware(['auth:sanctum', 'onboarding.pending'])->group(function () {
    // Solo usuarios SIN onboarding completado
});
```

**Comportamiento no deseado:**
1. Usuario completa onboarding → va a `/tickets`
2. Usuario navega a `/onboarding/profile` → Puede acceder ❌
3. Ve formulario de perfil nuevamente
4. Puede modificar datos sin validaciones adicionales

#### 🟡 IMPORTANTE: Lógica de Onboarding Duplicada

**Frontend (5 lugares distintos):**
1. `AuthContext.tsx` - Verifica si tiene perfil completo
2. `VerifyEmail.tsx` - Decide siguiente paso
3. `CompleteProfile.tsx` - Valida si debe estar ahí
4. `ConfigurePreferences.tsx` - Decide dashboard final
5. `RoleSelector.tsx` - Maneja múltiples roles

**Backend (3 lugares distintos):**
1. `EnsureOnboardingCompleted.php` - Middleware que verifica manualmente
2. `OnboardingCompletedResolver.php` - Resolver GraphQL custom
3. `RegisterMutation.php` - Crea usuario con onboarding_completed = false

**Violación del principio DRY:**
Si mañana decides agregar un paso (ej: "seleccionar empresa a seguir"), debes actualizar 8+ lugares.

---

## 3. PROPUESTAS DE SOLUCIÓN

### SOLUCIÓN 1: Servicio Centralizado de Onboarding ⭐⭐⭐⭐⭐

**Objetivo:** Única fuente de verdad para lógica de onboarding.

**Implementación:**

```php
<?php

namespace App\Features\UserManagement\Services;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Enums\UserStatus;
use App\Features\UserManagement\Events\OnboardingCompleted;
use Illuminate\Support\Facades\Log;

/**
 * Servicio Centralizado de Onboarding
 *
 * Responsabilidades:
 * - Verificar estado de onboarding
 * - Marcar onboarding como completado automáticamente
 * - Obtener siguiente paso del flujo
 * - Validar requisitos de completado
 */
class OnboardingService
{
    /**
     * Verifica si el usuario completó el onboarding
     * Usa SOLO el campo de BD (fuente de verdad)
     */
    public function hasCompletedOnboarding(User $user): bool
    {
        return $user->onboarding_completed === true;
    }

    /**
     * Verifica requisitos y marca como completado si aplica
     * Se llama automáticamente después de updateProfile y updatePreferences
     *
     * @return bool True si se marcó como completado, false si falta algo
     */
    public function checkAndMarkCompleted(User $user): bool
    {
        // Si ya está marcado, no hacer nada
        if ($this->hasCompletedOnboarding($user)) {
            return true;
        }

        // Verificar requisitos mínimos
        $requirements = $this->checkRequirements($user);

        // Si cumple TODOS los requisitos, marcar como completado
        if ($requirements['all_met']) {
            $this->markAsCompleted($user);
            return true;
        }

        return false;
    }

    /**
     * Verifica requisitos de onboarding
     *
     * @return array
     */
    public function checkRequirements(User $user): array
    {
        $hasProfile = $user->profile !== null;
        $hasFirstName = $hasProfile && !empty($user->profile->first_name);
        $hasLastName = $hasProfile && !empty($user->profile->last_name);
        $hasRoles = $user->roles()->active()->count() > 0;
        $isActive = $user->status === UserStatus::ACTIVE;

        // Email verificado NO es requisito (diseño actual permite omitir)
        $hasEmailVerified = $user->email_verified;

        return [
            'has_profile' => $hasProfile,
            'has_first_name' => $hasFirstName,
            'has_last_name' => $hasLastName,
            'has_roles' => $hasRoles,
            'is_active' => $isActive,
            'has_email_verified' => $hasEmailVerified,
            'all_met' => $hasProfile && $hasFirstName && $hasLastName && $hasRoles && $isActive,
        ];
    }

    /**
     * Marca onboarding como completado
     * Dispara evento OnboardingCompleted
     */
    private function markAsCompleted(User $user): void
    {
        $user->onboarding_completed = true;
        $user->onboarding_completed_at = now();
        $user->save();

        // Disparar evento para logging, analytics, emails, etc.
        event(new OnboardingCompleted($user));

        Log::info('Onboarding completado automáticamente', [
            'user_id' => $user->id,
            'email' => $user->email,
            'completed_at' => $user->onboarding_completed_at,
        ]);
    }

    /**
     * Obtiene el siguiente paso de onboarding
     *
     * @return string|null Ruta del siguiente paso o null si completó
     */
    public function getNextStep(User $user): ?string
    {
        // Si ya completó, no hay siguiente paso
        if ($this->hasCompletedOnboarding($user)) {
            return null;
        }

        $requirements = $this->checkRequirements($user);

        // Verificar en orden de prioridad
        if (!$requirements['has_first_name'] || !$requirements['has_last_name']) {
            return '/onboarding/profile';
        }

        // Preferencias es opcional pero si llegó aquí, debe configurarlas
        // Este paso podría ser omitible según tu diseño
        // return '/onboarding/preferences';

        // Si no tiene roles, ir a selector
        if (!$requirements['has_roles']) {
            return '/role-selector';
        }

        // Si no está activo, algo está mal
        if (!$requirements['is_active']) {
            return '/verify-email'; // O página de reactivación
        }

        return null;
    }

    /**
     * Resetea el onboarding (solo para testing o admin)
     */
    public function resetOnboarding(User $user): void
    {
        $user->onboarding_completed = false;
        $user->onboarding_completed_at = null;
        $user->save();

        Log::warning('Onboarding reseteado manualmente', [
            'user_id' => $user->id,
            'reset_by' => auth()->id(),
        ]);
    }

    /**
     * Fuerza marcar como completado (solo admin)
     */
    public function forceMarkCompleted(User $user): void
    {
        $this->markAsCompleted($user);

        Log::warning('Onboarding marcado como completado manualmente (force)', [
            'user_id' => $user->id,
            'forced_by' => auth()->id(),
        ]);
    }
}
```

**Uso en Mutations:**

```php
// app/Features/UserManagement/GraphQL/Mutations/UpdateMyProfileMutation.php
<?php

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\UserService;
use App\Features\UserManagement\Services\OnboardingService;

class UpdateMyProfileMutation
{
    public function __construct(
        private UserService $userService,
        private OnboardingService $onboardingService
    ) {}

    public function __invoke($root, array $args)
    {
        $user = auth()->user();

        // Actualizar perfil
        $this->userService->updateProfile($user, $args['input']);

        // ✅ NUEVO: Verificar y marcar onboarding si aplica
        $this->onboardingService->checkAndMarkCompleted($user);

        return $user->fresh(['profile', 'roleContexts']);
    }
}
```

```php
// app/Features/UserManagement/GraphQL/Mutations/UpdateMyPreferencesMutation.php
<?php

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\UserService;
use App\Features\UserManagement\Services\OnboardingService;

class UpdateMyPreferencesMutation
{
    public function __construct(
        private UserService $userService,
        private OnboardingService $onboardingService
    ) {}

    public function __invoke($root, array $args)
    {
        $user = auth()->user();

        // Actualizar preferencias
        $this->userService->updatePreferences($user, $args['input']);

        // ✅ NUEVO: Verificar y marcar onboarding si aplica
        $this->onboardingService->checkAndMarkCompleted($user);

        return $user->fresh(['profile', 'roleContexts']);
    }
}
```

### SOLUCIÓN 2: Middleware para Proteger Zona Onboarding Post-Completado

**Objetivo:** Impedir que usuarios que YA completaron onboarding vuelvan a rutas de onboarding.

**Implementación:**

```php
<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Features\UserManagement\Services\OnboardingService;

/**
 * Middleware: EnsureOnboardingPending
 *
 * Verifica que el usuario NO haya completado el onboarding
 * Usado en rutas de onboarding (/onboarding/*)
 * Si ya completó, redirige a su dashboard
 */
class EnsureOnboardingPending
{
    public function __construct(
        private OnboardingService $onboardingService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        // Si YA completó onboarding, redirigir a dashboard
        if ($this->onboardingService->hasCompletedOnboarding($user)) {
            Log::info('Usuario con onboarding completado intentó acceder a ruta de onboarding', [
                'user_id' => $user->id,
                'route' => $request->path(),
            ]);

            // Redirigir a dashboard según roles
            $primaryRole = $user->roles()->active()->first()?->role_code;

            $dashboard = match($primaryRole) {
                'PLATFORM_ADMIN' => '/platform/dashboard',
                'COMPANY_ADMIN' => '/empresa/dashboard',
                'AGENT' => '/agent/dashboard',
                'USER' => '/tickets',
                default => '/role-selector',
            };

            return redirect($dashboard)
                ->with('info', 'Ya completaste el proceso de configuración inicial.');
        }

        return $next($request);
    }
}
```

**Registrar middleware:**

```php
// bootstrap/app.php
$middleware->alias([
    'role' => \App\Shared\Http\Middleware\EnsureUserHasRole::class,
    'onboarding.completed' => \App\Shared\Http\Middleware\EnsureOnboardingCompleted::class,
    'onboarding.pending' => \App\Shared\Http\Middleware\EnsureOnboardingPending::class, // ✅ NUEVO
    'guest' => \App\Shared\Http\Middleware\RedirectIfAuthenticated::class,
]);
```

**Aplicar en rutas:**

```php
// routes/web.php

// ✅ ACTUALIZADO: Rutas de onboarding solo para usuarios SIN completar
Route::middleware(['auth:sanctum', 'onboarding.pending'])->group(function () {
    Route::get('/verify-email', fn(Request $request) =>
        Inertia::render('Public/VerifyEmail', ['token' => $request->query('token')])
    )->name('verify-email');

    Route::get('/onboarding/profile', fn() =>
        Inertia::render('Authenticated/Onboarding/CompleteProfile')
    )->name('onboarding.profile');

    Route::get('/onboarding/preferences', fn() =>
        Inertia::render('Authenticated/Onboarding/ConfigurePreferences')
    )->name('onboarding.preferences');
});
```

### SOLUCIÓN 3: Simplificar Middleware EnsureOnboardingCompleted

**Objetivo:** Usar el servicio centralizado en lugar de lógica manual.

**Implementación:**

```php
<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Features\UserManagement\Services\OnboardingService;

/**
 * Middleware: EnsureOnboardingCompleted
 *
 * Verifica que el usuario haya completado el onboarding
 * Usado en rutas autenticadas (zona authenticated)
 */
class EnsureOnboardingCompleted
{
    public function __construct(
        private OnboardingService $onboardingService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        // Rutas que NO requieren onboarding completado
        $excludedRoutes = [
            'verify-email',
            'onboarding.profile',
            'onboarding.preferences',
            'role-selector',
        ];

        if (in_array($request->route()->getName(), $excludedRoutes)) {
            return $next($request);
        }

        // ✅ SIMPLIFICADO: Usar servicio centralizado
        if (!$this->onboardingService->hasCompletedOnboarding($user)) {
            // Obtener siguiente paso del flujo
            $nextStep = $this->onboardingService->getNextStep($user);

            Log::info('Usuario sin onboarding completado redirigido', [
                'user_id' => $user->id,
                'route' => $request->path(),
                'next_step' => $nextStep,
            ]);

            return redirect($nextStep ?? '/onboarding/profile')
                ->with('warning', 'Completa tu perfil para continuar');
        }

        return $next($request);
    }
}
```

### SOLUCIÓN 4: Guard de Navegación en Frontend (React)

**Objetivo:** Validación proactiva de acceso a rutas antes de renderizar.

**Implementación:**

```typescript
// resources/js/guards/RouteGuard.tsx
import React, { useEffect, useState } from 'react';
import { useAuth } from '@/contexts';
import { getDefaultDashboard } from '@/config/permissions';
import { RoleCode } from '@/types';

interface RouteGuardProps {
    children: React.ReactNode;
    requiresAuth?: boolean;
    requiresOnboarding?: boolean;
    requiresEmailVerified?: boolean;
    requiredRoles?: RoleCode[];
    redirectIfCompleted?: boolean; // Para rutas de onboarding
}

export const RouteGuard: React.FC<RouteGuardProps> = ({
    children,
    requiresAuth = true,
    requiresOnboarding = true,
    requiresEmailVerified = false,
    requiredRoles = [],
    redirectIfCompleted = false,
}) => {
    const { user, isAuthenticated, loading } = useAuth();
    const [shouldRender, setShouldRender] = useState(false);

    useEffect(() => {
        if (loading) return;

        // 1. Verificar autenticación
        if (requiresAuth && !isAuthenticated) {
            window.location.href = '/login';
            return;
        }

        // Si no requiere auth, permitir
        if (!requiresAuth) {
            setShouldRender(true);
            return;
        }

        // 2. Verificar email (si es requerido)
        if (requiresEmailVerified && !user!.emailVerified) {
            window.location.href = '/verify-email';
            return;
        }

        // 3. Rutas de onboarding: redirigir si YA completó
        if (redirectIfCompleted && user!.onboardingCompleted) {
            const roles = user!.roleContexts.map(rc => rc.roleCode);
            const dashboard = getDefaultDashboard(roles);
            window.location.href = dashboard;
            return;
        }

        // 4. Verificar onboarding completado (si es requerido)
        if (requiresOnboarding && !user!.onboardingCompleted) {
            // Redirigir al paso que falta
            if (!user!.profile?.firstName || !user!.profile?.lastName) {
                window.location.href = '/onboarding/profile';
            } else if (user!.roleContexts.length === 0) {
                window.location.href = '/role-selector';
            } else {
                window.location.href = '/onboarding/preferences';
            }
            return;
        }

        // 5. Verificar roles
        if (requiredRoles.length > 0) {
            const userRoles = user!.roleContexts.map(rc => rc.roleCode);
            const hasRole = requiredRoles.some(role => userRoles.includes(role));

            if (!hasRole) {
                const dashboard = getDefaultDashboard(userRoles);
                window.location.href = dashboard;
                return;
            }
        }

        // Todas las validaciones pasaron
        setShouldRender(true);
    }, [user, isAuthenticated, loading]);

    if (loading || !shouldRender) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    return <>{children}</>;
};
```

**Uso en páginas:**

```typescript
// Pages/Agent/Dashboard.tsx
import { RouteGuard } from '@/guards/RouteGuard';
import { AuthenticatedLayout } from '@/Layouts/Authenticated/AuthenticatedLayout';

export default function AgentDashboard() {
    return (
        <RouteGuard
            requiresAuth={true}
            requiresOnboarding={true}
            requiredRoles={['AGENT']}
        >
            <AuthenticatedLayout title="Dashboard Agente">
                {/* contenido */}
            </AuthenticatedLayout>
        </RouteGuard>
    );
}
```

```typescript
// Pages/Authenticated/Onboarding/CompleteProfile.tsx
import { RouteGuard } from '@/guards/RouteGuard';
import { OnboardingLayout } from '@/Layouts/Onboarding/OnboardingLayout';

export default function CompleteProfile() {
    return (
        <RouteGuard
            requiresAuth={true}
            requiresOnboarding={false}
            redirectIfCompleted={true} // ✅ Redirige si ya completó
        >
            <OnboardingLayout title="Completa tu Perfil">
                {/* formulario */}
            </OnboardingLayout>
        </RouteGuard>
    );
}
```

### SOLUCIÓN 5: Evento OnboardingCompleted para Analytics

**Objetivo:** Trackear cuándo los usuarios completan onboarding para analytics y emails.

**Implementación:**

```php
<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: OnboardingCompleted
 *
 * Disparado cuando un usuario completa el onboarding
 * Usado para:
 * - Enviar email de bienvenida
 * - Trackear en analytics
 * - Asignar recursos iniciales
 */
class OnboardingCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user
    ) {}
}
```

**Listener para email de bienvenida:**

```php
<?php

namespace App\Features\UserManagement\Listeners;

use App\Features\UserManagement\Events\OnboardingCompleted;
use App\Features\UserManagement\Notifications\WelcomeNotification;

class SendWelcomeEmail
{
    public function handle(OnboardingCompleted $event): void
    {
        $event->user->notify(new WelcomeNotification());
    }
}
```

**Registrar en EventServiceProvider:**

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Features\UserManagement\Events\OnboardingCompleted;
use App\Features\UserManagement\Listeners\SendWelcomeEmail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OnboardingCompleted::class => [
            SendWelcomeEmail::class,
            // TrackOnboardingInAnalytics::class,
            // AssignDefaultResources::class,
        ],
    ];
}
```

---

## 4. PLAN DE IMPLEMENTACIÓN

### FASE 1: Fundamentos (1-2 días) 🔴 CRÍTICO

#### Paso 1.1: Crear OnboardingService

```bash
# Crear servicio
php artisan make:class Features/UserManagement/Services/OnboardingService

# Crear evento
php artisan make:event Features/UserManagement/Events/OnboardingCompleted

# Crear listener
php artisan make:listener Features/UserManagement/Listeners/SendWelcomeEmail --event=OnboardingCompleted
```

**Archivos a crear/modificar:**
1. `app/Features/UserManagement/Services/OnboardingService.php` (CREAR)
2. `app/Features/UserManagement/Events/OnboardingCompleted.php` (CREAR)
3. `app/Features/UserManagement/Listeners/SendWelcomeEmail.php` (CREAR)
4. `app/Providers/EventServiceProvider.php` (MODIFICAR - registrar listener)

#### Paso 1.2: Actualizar Mutations para Usar OnboardingService

**Archivos a modificar:**
1. `app/Features/UserManagement/GraphQL/Mutations/UpdateMyProfileMutation.php`
2. `app/Features/UserManagement/GraphQL/Mutations/UpdateMyPreferencesMutation.php`

**Cambios:**
```php
// Inyectar OnboardingService en constructor
public function __construct(
    private UserService $userService,
    private OnboardingService $onboardingService // ✅ AGREGAR
) {}

// Llamar después de actualizar
$this->onboardingService->checkAndMarkCompleted($user); // ✅ AGREGAR
```

#### Paso 1.3: Ejecutar Migración de Onboarding

```bash
# Verificar que la migración existe
ls app/Features/UserManagement/Database/Migrations/ | grep onboarding

# Ejecutar migración
php artisan migrate

# Verificar columnas en BD
psql -U postgres -d helpdesk_db -c "SELECT column_name, data_type FROM information_schema.columns WHERE table_name='users' AND column_name LIKE 'onboarding%';"
```

#### Paso 1.4: Simplificar OnboardingCompletedResolver

**Archivo:** `app/Shared/GraphQL/Resolvers/OnboardingCompletedResolver.php`

**Cambio:**
```php
<?php

namespace App\Shared\GraphQL\Resolvers;

use App\Features\UserManagement\Models\User;

class OnboardingCompletedResolver
{
    public function __invoke($root): bool
    {
        // ✅ SIMPLIFICADO: Leer directamente del campo de BD
        if (is_array($root)) {
            return (bool) ($root['onboarding_completed'] ?? false);
        }

        return (bool) ($root->onboarding_completed ?? false);
    }
}
```

### FASE 2: Middleware de Protección (1 día) 🟡

#### Paso 2.1: Crear EnsureOnboardingPending Middleware

```bash
php artisan make:middleware Shared/Http/Middleware/EnsureOnboardingPending
```

**Archivo a crear:**
`app/Shared/Http/Middleware/EnsureOnboardingPending.php`

#### Paso 2.2: Registrar Middleware

**Archivo:** `bootstrap/app.php`

**Agregar:**
```php
$middleware->alias([
    'role' => \App\Shared\Http\Middleware\EnsureUserHasRole::class,
    'onboarding.completed' => \App\Shared\Http\Middleware\EnsureOnboardingCompleted::class,
    'onboarding.pending' => \App\Shared\Http\Middleware\EnsureOnboardingPending::class, // ✅ NUEVO
    'guest' => \App\Shared\Http\Middleware\RedirectIfAuthenticated::class,
]);
```

#### Paso 2.3: Aplicar Middleware en Rutas

**Archivo:** `routes/web.php`

**Cambiar:**
```php
// ❌ ANTES
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/onboarding/profile', ...);
    Route::get('/onboarding/preferences', ...);
});

// ✅ DESPUÉS
Route::middleware(['auth:sanctum', 'onboarding.pending'])->group(function () {
    Route::get('/onboarding/profile', ...);
    Route::get('/onboarding/preferences', ...);
});
```

#### Paso 2.4: Simplificar EnsureOnboardingCompleted

**Archivo:** `app/Shared/Http/Middleware/EnsureOnboardingCompleted.php`

**Reemplazar lógica manual con:**
```php
if (!$this->onboardingService->hasCompletedOnboarding($user)) {
    $nextStep = $this->onboardingService->getNextStep($user);
    return redirect($nextStep ?? '/onboarding/profile');
}
```

### FASE 3: Guards de Frontend (1 día) 🟢

#### Paso 3.1: Crear RouteGuard Component

```bash
mkdir -p resources/js/guards
touch resources/js/guards/RouteGuard.tsx
```

**Archivo a crear:**
`resources/js/guards/RouteGuard.tsx`

#### Paso 3.2: Aplicar Guards en Páginas Críticas

**Páginas a modificar:**
1. `resources/js/Pages/Agent/Dashboard.tsx`
2. `resources/js/Pages/CompanyAdmin/Dashboard.tsx`
3. `resources/js/Pages/PlatformAdmin/Dashboard.tsx`
4. `resources/js/Pages/User/Dashboard.tsx`
5. `resources/js/Pages/Authenticated/Onboarding/CompleteProfile.tsx` (con `redirectIfCompleted`)
6. `resources/js/Pages/Authenticated/Onboarding/ConfigurePreferences.tsx` (con `redirectIfCompleted`)

### FASE 4: Testing y Validación (1 día) 🔵

#### Paso 4.1: Tests Unitarios

```bash
# Crear tests
php artisan make:test Features/UserManagement/Services/OnboardingServiceTest --unit
php artisan make:test Features/UserManagement/Middleware/EnsureOnboardingPendingTest
```

**Tests a escribir:**

```php
// OnboardingServiceTest.php
public function test_marks_onboarding_as_completed_when_requirements_met()
public function test_does_not_mark_completed_if_missing_profile()
public function test_does_not_mark_completed_if_missing_roles()
public function test_gets_correct_next_step_for_incomplete_profile()
public function test_returns_null_for_completed_onboarding()

// EnsureOnboardingPendingTest.php
public function test_allows_access_if_onboarding_not_completed()
public function test_redirects_if_onboarding_completed()
```

#### Paso 4.2: Testing Manual

**Escenarios a probar:**

1. **Registro nuevo usuario:**
   - Verificar que `onboarding_completed = false` al registrar
   - Completar perfil → verificar que NO se marca como completado todavía
   - Completar preferencias → verificar que SÍ se marca como completado
   - Verificar que `onboarding_completed_at` tiene timestamp

2. **Protección de rutas de onboarding:**
   - Usuario con `onboarding_completed = true` intenta acceder a `/onboarding/profile`
   - Debería redirigir a su dashboard

3. **Protección de rutas autenticadas:**
   - Usuario con `onboarding_completed = false` intenta acceder a `/tickets`
   - Debería redirigir al paso que falta

4. **Flujo completo:**
   - Registro → Verify Email (omitir) → Complete Profile → Configure Preferences → Dashboard
   - Verificar que cada paso funciona correctamente

#### Paso 4.3: Verificar Queries GraphQL

```graphql
# Verificar que authStatus devuelve onboardingCompleted correctamente
query {
  authStatus {
    isAuthenticated
    user {
      id
      email
      onboardingCompleted  # ✅ Debe venir del campo de BD ahora
      profile {
        firstName
        lastName
      }
      roleContexts {
        roleCode
      }
    }
  }
}
```

### FASE 5: Limpieza y Documentación (medio día) 🟣

#### Paso 5.1: Limpiar Código Obsoleto

**Eliminar/Simplificar:**
1. Lógica de onboarding duplicada en frontend (confiar en backend)
2. Comentarios TODO relacionados con onboarding
3. Console.logs de debugging

#### Paso 5.2: Actualizar Documentación

**Archivos a actualizar:**
1. `README.md` - Agregar sección de onboarding
2. `documentacion/AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt` - Actualizar flujo
3. `documentacion/USER MANAGMENT FEATURE - DOCUMENTACION.txt` - Agregar OnboardingService
4. Crear `documentacion/ONBOARDING_FLOW.md` - Documentar flujo completo

**Contenido sugerido para ONBOARDING_FLOW.md:**

```markdown
# Flujo de Onboarding - Sistema Helpdesk

## Arquitectura

El onboarding utiliza un **Servicio Centralizado** (`OnboardingService`) como única fuente de verdad.

## Campo en BD

- `users.onboarding_completed` (boolean, default: false)
- `users.onboarding_completed_at` (timestamp, nullable)

## Requisitos para Completar

1. ✅ Perfil con `first_name` y `last_name`
2. ✅ Al menos 1 rol activo
3. ✅ Usuario con status = ACTIVE
4. ⚠️ Email verificado NO es requisito (diseño actual permite omitir)

## Pasos del Flujo

1. Registro → `onboarding_completed = false`
2. Verify Email (opcional) → puede omitir
3. Complete Profile → actualiza perfil, NO marca completado todavía
4. Configure Preferences → actualiza preferencias, **marca completado automáticamente**
5. Dashboard → acceso completo

## Validación Automática

El `OnboardingService.checkAndMarkCompleted()` se llama automáticamente después de:
- `UpdateMyProfileMutation`
- `UpdateMyPreferencesMutation`

Si cumple requisitos, marca `onboarding_completed = true` y dispara evento `OnboardingCompleted`.

## Protección de Rutas

### Zona Onboarding (onboarding.pending)
- Solo usuarios CON `onboarding_completed = false`
- Si ya completó, redirige a dashboard

### Zona Authenticated (onboarding.completed)
- Solo usuarios CON `onboarding_completed = true`
- Si no completó, redirige al paso que falta

## Evento OnboardingCompleted

Disparado cuando se marca onboarding como completado.

**Listeners:**
- `SendWelcomeEmail` - Envía email de bienvenida
- (Futuro) `TrackOnboardingInAnalytics` - Analytics
- (Futuro) `AssignDefaultResources` - Recursos iniciales

## Testing

Ver `tests/Feature/UserManagement/OnboardingFlowTest.php`
```

---

## 5. ARQUITECTURA PROFESIONAL PROPUESTA

### 5.1 Diagrama de Flujo Actualizado

```
┌─────────────────────────────────────────────────────────────┐
│  REGISTRO (zona pública)                                    │
│  - registerMutation                                         │
│  - Crea user con onboarding_completed = false              │
│  - Tokens guardados                                         │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  VERIFY EMAIL (zona onboarding)                             │
│  - middleware: auth:sanctum + onboarding.pending            │
│  - verifyEmailMutation marca email_verified = true          │
│  - Puede omitir con advertencia                            │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  COMPLETE PROFILE (zona onboarding)                         │
│  - middleware: auth:sanctum + onboarding.pending            │
│  - updateMyProfileMutation actualiza perfil                │
│  - OnboardingService.checkAndMarkCompleted() → NO cumple    │
│  - onboarding_completed sigue en false                     │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  CONFIGURE PREFERENCES (zona onboarding)                    │
│  - middleware: auth:sanctum + onboarding.pending            │
│  - updateMyPreferencesMutation actualiza preferencias      │
│  - OnboardingService.checkAndMarkCompleted() → ✅ CUMPLE    │
│  - onboarding_completed = true                             │
│  - Dispara OnboardingCompleted event                       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  DASHBOARD (zona authenticated)                             │
│  - middleware: auth:sanctum + onboarding.completed + role:X │
│  - Acceso completo según rol                               │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 Arquitectura de Capas de Protección

```
┌──────────────────────────────────────────────────────────────┐
│                    FRONTEND (React)                          │
│  Layer 1: RouteGuard Component                              │
│  - Validación proactiva antes de renderizar                 │
│  - Redireccionamiento del lado del cliente                  │
│  - UX sin flashes de contenido no autorizado                │
└──────────────────────────────────────────────────────────────┘
                          ↓
┌──────────────────────────────────────────────────────────────┐
│                 INERTIA MIDDLEWARE                           │
│  Layer 2: HandleInertiaRequests                             │
│  - Pasa datos del usuario a frontend                        │
│  - Sincroniza estado de autenticación                       │
└──────────────────────────────────────────────────────────────┘
                          ↓
┌──────────────────────────────────────────────────────────────┐
│                  ROUTE MIDDLEWARE                            │
│  Layer 3: Middleware Stack                                  │
│  - auth:sanctum (valida JWT token)                         │
│  - onboarding.completed o onboarding.pending                │
│  - role:USER,AGENT,etc (valida roles)                      │
└──────────────────────────────────────────────────────────────┘
                          ↓
┌──────────────────────────────────────────────────────────────┐
│               GRAPHQL DIRECTIVES                             │
│  Layer 4: @jwt Directive                                    │
│  - Validación de token en cada field                        │
│  - Validación de roles específicos                          │
│  - Contexto de usuario disponible                           │
└──────────────────────────────────────────────────────────────┘
                          ↓
┌──────────────────────────────────────────────────────────────┐
│                 SERVICE LAYER                                │
│  Layer 5: OnboardingService + Business Logic                │
│  - Única fuente de verdad para onboarding                   │
│  - Validación de reglas de negocio                          │
│  - Eventos y auditoría                                       │
└──────────────────────────────────────────────────────────────┘
                          ↓
┌──────────────────────────────────────────────────────────────┐
│                    DATABASE                                  │
│  Layer 6: Constraints y Validaciones                        │
│  - onboarding_completed (boolean, default: false)           │
│  - Índices para performance                                 │
│  - Triggers para auditoría                                  │
└──────────────────────────────────────────────────────────────┘
```

### 5.3 Comparación: Antes vs Después

#### ANTES (Situación Actual)

**Problemas:**
- ❌ Campo `onboarding_completed` existe pero no se usa
- ❌ Lógica de verificación manual en middleware
- ❌ Lógica duplicada en 8+ lugares
- ❌ Zona onboarding accesible después de completar
- ❌ No hay evento cuando completa onboarding
- ❌ Difícil auditar cuándo completó

**Flujo:**
```
UpdateProfile → Actualiza perfil → NO marca completado
                                  ↓
                            Middleware verifica manualmente
                            (firstName && lastName && roles)
```

#### DESPUÉS (Propuesta)

**Mejoras:**
- ✅ `onboarding_completed` se actualiza automáticamente
- ✅ `OnboardingService` es única fuente de verdad
- ✅ Lógica centralizada en 1 solo lugar
- ✅ Zona onboarding protegida con `onboarding.pending`
- ✅ Evento `OnboardingCompleted` para extensibilidad
- ✅ Timestamp `onboarding_completed_at` para analytics

**Flujo:**
```
UpdateProfile → Actualiza perfil → OnboardingService.checkAndMarkCompleted()
                                  ↓
                            Si cumple requisitos:
                            - onboarding_completed = true
                            - onboarding_completed_at = now()
                            - Dispara OnboardingCompleted event
```

### 5.4 Beneficios de la Arquitectura Propuesta

1. **DRY (Don't Repeat Yourself):**
   - Lógica de onboarding en 1 solo lugar
   - Fácil de mantener y actualizar

2. **Single Source of Truth:**
   - Campo `onboarding_completed` en BD es la verdad
   - Todos confían en él

3. **Extensibilidad:**
   - Evento permite agregar nuevas acciones sin tocar código core
   - Listener para emails, analytics, recursos, etc.

4. **Testabilidad:**
   - Service fácil de testear unitariamente
   - Middleware simple de testear con mocks

5. **Auditoría:**
   - Timestamp de cuándo completó
   - Eventos logueados
   - Fácil reportes de métricas

6. **Performance:**
   - Query simple al campo booleano
   - No necesita joins complejos
   - Índices para búsquedas rápidas

---

## 6. CHECKLIST FINAL

### ✅ Checklist de Implementación

#### Backend
- [ ] Crear `OnboardingService`
- [ ] Crear evento `OnboardingCompleted`
- [ ] Crear listener `SendWelcomeEmail`
- [ ] Actualizar `UpdateMyProfileMutation` para usar servicio
- [ ] Actualizar `UpdateMyPreferencesMutation` para usar servicio
- [ ] Crear middleware `EnsureOnboardingPending`
- [ ] Registrar middleware en `bootstrap/app.php`
- [ ] Aplicar middleware en `routes/web.php`
- [ ] Simplificar `EnsureOnboardingCompleted`
- [ ] Simplificar `OnboardingCompletedResolver`
- [ ] Ejecutar migración de onboarding
- [ ] Registrar evento y listener en `EventServiceProvider`

#### Frontend
- [ ] Crear `RouteGuard` component
- [ ] Aplicar guard en todas las páginas de dashboards
- [ ] Aplicar guard con `redirectIfCompleted` en páginas de onboarding
- [ ] Limpiar lógica de onboarding duplicada en componentes
- [ ] Confiar en campo `onboardingCompleted` del backend

#### Testing
- [ ] Tests unitarios de `OnboardingService`
- [ ] Tests de middleware `EnsureOnboardingPending`
- [ ] Tests de middleware `EnsureOnboardingCompleted`
- [ ] Tests de integración del flujo completo
- [ ] Testing manual de todos los escenarios

#### Documentación
- [ ] Actualizar `README.md`
- [ ] Actualizar documentación de features
- [ ] Crear `ONBOARDING_FLOW.md`
- [ ] Comentarios en código explicando decisiones

---

## 7. CONCLUSIÓN

Tu arquitectura actual es **sólida y profesional**. Los cambios propuestos son **refinamientos** que la llevarán al siguiente nivel:

### Lo que tienes bien (80% del sistema):
✅ Separación clara de 3 zonas
✅ Middleware robusto con logging
✅ JWT authentication profesional
✅ Contextos globales bien organizados
✅ UX de onboarding fluida

### Lo que necesitas mejorar (20% crítico):
🔴 Actualizar automáticamente `onboarding_completed`
🔴 Proteger zona onboarding post-completado
🟡 Centralizar lógica en `OnboardingService`
🟢 Agregar guards de frontend

### Tiempo estimado de implementación:
- **Fase 1 (Crítico):** 1-2 días
- **Fase 2 (Importante):** 1 día
- **Fase 3 (Mejora):** 1 día
- **Fase 4 (Testing):** 1 día
- **Fase 5 (Docs):** 0.5 días

**TOTAL:** 4.5 - 5.5 días de trabajo

### Próximos pasos recomendados:

1. **Implementar Fase 1 primero** (OnboardingService + actualización automática)
2. **Testear exhaustivamente**
3. **Implementar Fase 2** (middleware de protección)
4. **Implementar Fase 3** (guards frontend) - Opcional pero recomendado
5. **Documentar todo**

**¿Listo para empezar? Te recomiendo comenzar con Fase 1, que es el cambio más crítico y de mayor impacto.**
