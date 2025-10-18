/**
 * OnboardingRoute - Protección para rutas de onboarding
 * SOLUCIÓN ANTI-LOOP: Usa SessionStorage para persistir flag durante re-montajes
 *
 * Comportamiento:
 * - Si NO está autenticado: Redirigir a /login
 * - Si está autenticado SIN onboarding: Permitir acceso ✓
 * - Si está autenticado CON onboarding: Redirigir a dashboard según rol
 *
 * Usado en: VerifyEmail, CompleteProfile, ConfigurePreferences
 * NO se usa en RoleSelector (esa página requiere onboarding completo)
 */

import { useEffect } from 'react';
import { useAuth } from '@/hooks';
import { getUserDashboardUrl, safeRedirect } from '@/lib/utils';

interface OnboardingRouteProps {
    children: React.ReactNode;
}

export function OnboardingRoute({ children }: OnboardingRouteProps) {
    const { user, loading, hasCompletedOnboarding } = useAuth();

    useEffect(() => {
        if (loading) return;

        const currentPath = window.location.pathname;

        if (!user) {
            // No autenticado, redirigir a login solo si no está ya ahí
            if (currentPath !== '/login') {
                console.log('[OnboardingRoute] Usuario no autenticado, redirigiendo a /login');
                safeRedirect('/login', { replace: true });
            }
            return;
        }

        if (hasCompletedOnboarding()) {
            // Onboarding ya completo, redirigir a dashboard
            const dashboardPath = getUserDashboardUrl(user);
            console.log('[OnboardingRoute] Onboarding completo, redirigiendo a dashboard:', dashboardPath);

            // Solo redirigir si no está ya en la ruta de destino
            if (currentPath !== dashboardPath && !currentPath.startsWith(dashboardPath)) {
                safeRedirect(dashboardPath, { replace: true });
            }
        }
        // IMPORTANTE: hasCompletedOnboarding está memoizado en AuthContext con useCallback
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [user, loading]);

    // Mostrar loading mientras verifica
    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
                <div className="text-center">
                    <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p className="mt-4 text-gray-600 dark:text-gray-400">Cargando...</p>
                </div>
            </div>
        );
    }

    // Usuario autenticado sin onboarding completo, permitir acceso
    if (user && !hasCompletedOnboarding()) {
        return <>{children}</>;
    }

    // Cualquier otro caso (se está redirigiendo)
    return null;
}
