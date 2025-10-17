/**
 * Onboarding Helpers
 * Lógica centralizada para verificar estado de onboarding del usuario
 *
 * El proceso de onboarding incluye:
 * 1. Verificación de email (emailVerified)
 * 2. Completar perfil (firstName, lastName)
 * 3. Configurar preferencias (theme, language, timezone)
 * 4. Marcar onboarding como completado (onboardingCompletedAt)
 */

import type { User } from '@/types';

/**
 * Verifica si el usuario ha completado el proceso de onboarding
 */
export function hasCompletedOnboarding(user: User | null): boolean {
    if (!user) return false;
    return user.onboardingCompletedAt !== null && user.onboardingCompletedAt !== undefined;
}

/**
 * Verifica si el usuario ha verificado su email
 */
export function hasVerifiedEmail(user: User | null): boolean {
    if (!user) return false;
    return user.emailVerified === true;
}

/**
 * Verifica si el usuario tiene perfil completo (nombre y apellido)
 */
export function hasCompletedProfile(user: User | null): boolean {
    if (!user) return false;
    if (!user.profile) return false;
    return !!(user.profile.firstName && user.profile.lastName);
}

/**
 * Verifica si el usuario tiene preferencias configuradas
 */
export function hasConfiguredPreferences(user: User | null): boolean {
    if (!user) return false;
    if (!user.preferences) return false;
    // Verificar que tenga al menos theme, language y timezone
    return !!(
        user.preferences.theme &&
        user.preferences.language &&
        user.preferences.timezone
    );
}

/**
 * Determina el siguiente paso del onboarding para el usuario
 * Retorna null si ya completó onboarding
 */
export function getNextOnboardingStep(user: User | null): string | null {
    if (!user) return '/login';

    // Si ya completó onboarding, no hay siguiente paso
    if (hasCompletedOnboarding(user)) {
        return null;
    }

    // Si no verificó email, debe ir a verificar
    if (!hasVerifiedEmail(user)) {
        return '/verify-email';
    }

    // Si no tiene perfil completo, ir a completar perfil
    if (!hasCompletedProfile(user)) {
        return '/onboarding/profile';
    }

    // Si no tiene preferencias, ir a configurar preferencias
    if (!hasConfiguredPreferences(user)) {
        return '/onboarding/preferences';
    }

    // Si llegó aquí, debería marcar onboarding como completado
    // pero eso lo hace el backend después de /onboarding/preferences
    return null;
}

/**
 * Determina si el usuario necesita completar onboarding
 */
export function needsOnboarding(user: User | null): boolean {
    return !hasCompletedOnboarding(user);
}

/**
 * Obtiene el progreso del onboarding en porcentaje (0-100)
 */
export function getOnboardingProgress(user: User | null): number {
    if (!user) return 0;

    let completed = 0;
    const totalSteps = 4;

    // 1. Email verificado
    if (hasVerifiedEmail(user)) completed++;

    // 2. Perfil completo
    if (hasCompletedProfile(user)) completed++;

    // 3. Preferencias configuradas
    if (hasConfiguredPreferences(user)) completed++;

    // 4. Onboarding marcado como completado
    if (hasCompletedOnboarding(user)) completed++;

    return Math.round((completed / totalSteps) * 100);
}

/**
 * Obtiene información de estado del onboarding
 */
export function getOnboardingStatus(user: User | null) {
    return {
        isCompleted: hasCompletedOnboarding(user),
        hasVerifiedEmail: hasVerifiedEmail(user),
        hasCompletedProfile: hasCompletedProfile(user),
        hasConfiguredPreferences: hasConfiguredPreferences(user),
        nextStep: getNextOnboardingStep(user),
        progress: getOnboardingProgress(user),
    };
}