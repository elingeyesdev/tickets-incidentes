/**
 * OnboardingRoute - Protección para rutas de onboarding
 *
 * Comportamiento:
 * - Si NO está autenticado: Redirigir a /login
 * - Si está autenticado SIN onboarding: Permitir acceso ✓
 * - Si está autenticado CON onboarding: Redirigir a dashboard según rol
 *
 * Usado en: VerifyEmail, CompleteProfile, ConfigurePreferences
 * NO se usa en RoleSelector (esa página requiere onboarding completo)
 */

import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/hooks';
import { getUserDashboardUrl } from '@/lib/utils';

interface OnboardingRouteProps {
    children: React.ReactNode;
}

export function OnboardingRoute({ children }: OnboardingRouteProps) {
    const { user, loading, hasCompletedOnboarding } = useAuth();
    const hasRedirected = useRef(false);

    useEffect(() => {
        if (loading) return;
        if (hasRedirected.current) return; // Ya redirigió, evitar re-ejecuciones

        const currentPath = window.location.pathname;

        if (!user) {
            // No autenticado, redirigir a login solo si no está ya ahí
            if (currentPath !== '/login') {
                hasRedirected.current = true; // Marcar como redirigido
                router.visit('/login');
            }
            return;
        }

        if (hasCompletedOnboarding()) {
            // Onboarding ya completo, redirigir a dashboard
            const dashboardPath = getUserDashboardUrl(user);

            // Solo redirigir si no está ya en la ruta de destino
            if (currentPath !== dashboardPath && !currentPath.startsWith(dashboardPath)) {
                hasRedirected.current = true; // Marcar como redirigido
                router.visit(dashboardPath);
            }
        }
    }, [user, loading, hasCompletedOnboarding]);

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
