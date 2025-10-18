/**
 * PublicRoute - Protección para rutas públicas
 * SOLUCIÓN ANTI-LOOP: Usa SessionStorage para persistir flag durante re-montajes
 *
 * Comportamiento:
 * - Si NO está autenticado: Permitir acceso ✓
 * - Si está autenticado SIN onboarding: Redirigir a /onboarding/profile
 * - Si está autenticado CON onboarding: Redirigir a dashboard según rol
 *
 * Usado en: Welcome, Login, Register, RequestCompany
 */

import { useEffect } from 'react';
import { useAuth } from '@/hooks';
import { getUserDashboardUrl, safeRedirect } from '@/lib/utils';

interface PublicRouteProps {
    children: React.ReactNode;
}

export function PublicRoute({ children }: PublicRouteProps) {
    const { user, loading, hasCompletedOnboarding } = useAuth();

    useEffect(() => {
        if (loading) return;

        if (user) {
            const currentPath = window.location.pathname;
            console.log('[PublicRoute] Usuario autenticado detectado, path actual:', currentPath);

            // Usuario autenticado, verificar onboarding
            if (!hasCompletedOnboarding()) {
                // Redirigir a onboarding solo si no está ya ahí
                if (currentPath !== '/onboarding/profile') {
                    console.log('[PublicRoute] Onboarding incompleto, redirigiendo a /onboarding/profile');
                    safeRedirect('/onboarding/profile', { replace: true });
                }
                return;
            }

            // Tiene onboarding completo, redirigir a su dashboard
            const dashboardPath = getUserDashboardUrl(user);
            console.log('[PublicRoute] Onboarding completo, dashboard destino:', dashboardPath);

            // Solo redirigir si no está ya en la ruta de destino
            if (currentPath !== dashboardPath && !currentPath.startsWith(dashboardPath)) {
                console.log('[PublicRoute] Redirigiendo a dashboard...');
                safeRedirect(dashboardPath, { replace: true });
            } else {
                console.log('[PublicRoute] Ya está en el dashboard, no redirigir');
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
                    <p className="mt-4 text-gray-600 dark:text-gray-400">Verificando...</p>
                </div>
            </div>
        );
    }

    // Usuario no autenticado, permitir acceso
    if (!user) {
        return <>{children}</>;
    }

    // Usuario autenticado (se está redirigiendo o ya está en su zona)
    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
            <div className="text-center">
                <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p className="mt-4 text-gray-600 dark:text-gray-400">Redirigiendo...</p>
            </div>
        </div>
    );
}
