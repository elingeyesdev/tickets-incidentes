/**
 * PublicRoute - Protección para rutas públicas
 *
 * Comportamiento:
 * - Si NO está autenticado: Permitir acceso ✓
 * - Si está autenticado SIN onboarding: Redirigir a /onboarding/profile
 * - Si está autenticado CON onboarding: Redirigir a dashboard según rol
 *
 * Usado en: Welcome, Login, Register, RequestCompany
 */

import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/hooks';
import { getUserDashboardUrl } from '@/lib/utils';

interface PublicRouteProps {
    children: React.ReactNode;
}

export function PublicRoute({ children }: PublicRouteProps) {
    const { user, loading, hasCompletedOnboarding } = useAuth();
    const hasRedirected = useRef(false);

    useEffect(() => {
        if (loading) return;
        if (hasRedirected.current) return; // Ya redirigió, evitar re-ejecuciones

        if (user) {
            const currentPath = window.location.pathname;

            // Usuario autenticado, verificar onboarding
            if (!hasCompletedOnboarding()) {
                // Redirigir a onboarding solo si no está ya ahí
                if (currentPath !== '/onboarding/profile') {
                    hasRedirected.current = true; // Marcar como redirigido
                    router.visit('/onboarding/profile');
                }
                return;
            }

            // Tiene onboarding completo, redirigir a su dashboard
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
                    <p className="mt-4 text-gray-600 dark:text-gray-400">Verificando...</p>
                </div>
            </div>
        );
    }

    // Usuario no autenticado, permitir acceso
    if (!user) {
        return <>{children}</>;
    }

    // Usuario autenticado (se está redirigiendo)
    return null;
}
