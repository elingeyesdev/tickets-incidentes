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

import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/contexts';
import { getDefaultDashboard } from '@/config/permissions';
import type { RoleCode } from '@/types';

interface PublicRouteProps {
    children: React.ReactNode;
}

export function PublicRoute({ children }: PublicRouteProps) {
    const { user, loading, hasCompletedOnboarding } = useAuth();

    useEffect(() => {
        if (loading) return;

        if (user) {
            const currentPath = window.location.pathname;

            // Debug log
            console.log('🔵 PublicRoute: Usuario autenticado detectado', {
                currentPath,
                hasOnboarding: hasCompletedOnboarding(),
                roles: user.roleContexts.map(rc => rc.roleCode)
            });

            // Usuario autenticado, verificar onboarding
            if (!hasCompletedOnboarding()) {
                // Redirigir a onboarding solo si no está ya ahí
                if (currentPath !== '/onboarding/profile') {
                    console.log('🔵 PublicRoute: Redirigiendo a onboarding');
                    router.visit('/onboarding/profile');
                }
                return;
            }

            // Tiene onboarding completo, redirigir a su dashboard
            const userRoles = user.roleContexts.map((rc) => rc.roleCode as RoleCode);
            const dashboardPath = getDefaultDashboard(userRoles);

            // Solo redirigir si no está ya en la ruta de destino
            if (currentPath !== dashboardPath && !currentPath.startsWith(dashboardPath)) {
                console.log('🔵 PublicRoute: Redirigiendo a dashboard', dashboardPath);
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
