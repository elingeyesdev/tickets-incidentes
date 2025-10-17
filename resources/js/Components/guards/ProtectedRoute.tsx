/**
 * ProtectedRoute - Protección para rutas autenticadas
 *
 * Comportamiento:
 * - Si NO está autenticado: Redirigir a /login
 * - Si está autenticado SIN onboarding: Redirigir a /onboarding/profile
 * - Si está autenticado CON onboarding pero SIN rol permitido: Redirigir a /unauthorized
 * - Si está autenticado CON onboarding y CON rol: Permitir acceso ✓
 *
 * Usado en: Todos los dashboards y páginas de zona authenticated
 */

import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/contexts';
import type { RoleCode } from '@/types';

interface ProtectedRouteProps {
    children: React.ReactNode;
    allowedRoles?: RoleCode[];
    redirectTo?: string;
}

export function ProtectedRoute({
    children,
    allowedRoles = [],
    redirectTo = '/login'
}: ProtectedRouteProps) {
    const { user, loading, hasCompletedOnboarding, hasRole } = useAuth();

    useEffect(() => {
        if (loading) return;

        const currentPath = window.location.pathname;

        // Debug log
        console.log('🟡 ProtectedRoute: Verificando acceso', {
            currentPath,
            authenticated: !!user,
            hasOnboarding: user ? hasCompletedOnboarding() : false,
            allowedRoles
        });

        // 1. Verificar autenticación
        if (!user) {
            // Solo redirigir si no está ya en la ruta de destino
            if (currentPath !== redirectTo) {
                console.log('🟡 ProtectedRoute: No autenticado, redirigiendo a', redirectTo);
                router.visit(redirectTo);
            }
            return;
        }

        // 2. Verificar onboarding completado
        if (!hasCompletedOnboarding()) {
            // Solo redirigir si no está ya en onboarding
            if (currentPath !== '/onboarding/profile' && !currentPath.startsWith('/onboarding/')) {
                console.log('🟡 ProtectedRoute: Onboarding incompleto, redirigiendo a onboarding');
                router.visit('/onboarding/profile');
            }
            return;
        }

        // 3. Verificar roles (si se especificaron)
        if (allowedRoles.length > 0) {
            const hasPermission = allowedRoles.some(role => hasRole(role));
            if (!hasPermission) {
                // Solo redirigir si no está ya en unauthorized
                if (currentPath !== '/unauthorized') {
                    console.log('🟡 ProtectedRoute: Sin permisos, redirigiendo a unauthorized');
                    router.visit('/unauthorized');
                }
                return;
            }
        }
    }, [user, loading, allowedRoles, redirectTo, hasCompletedOnboarding, hasRole]);

    // Mostrar loading mientras verifica
    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                <div className="text-center">
                    <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p className="mt-4 text-gray-600 dark:text-gray-400">Cargando...</p>
                </div>
            </div>
        );
    }

    // Verificaciones fallidas (se está redirigiendo)
    if (!user) return null;
    if (!hasCompletedOnboarding()) return null;
    if (allowedRoles.length > 0) {
        const hasPermission = allowedRoles.some(role => hasRole(role));
        if (!hasPermission) return null;
    }

    // Todas las verificaciones pasadas, permitir acceso
    return <>{children}</>;
}
