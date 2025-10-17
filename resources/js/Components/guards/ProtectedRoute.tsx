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

import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/hooks';
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
    const hasRedirected = useRef(false);

    useEffect(() => {
        if (loading) return;
        if (hasRedirected.current) return; // Ya redirigió, evitar re-ejecuciones

        const currentPath = window.location.pathname;

        // 1. Verificar autenticación
        if (!user) {
            // Solo redirigir si no está ya en la ruta de destino
            if (currentPath !== redirectTo) {
                hasRedirected.current = true; // Marcar como redirigido
                router.visit(redirectTo);
            }
            return;
        }

        // 2. Verificar onboarding completado
        if (!hasCompletedOnboarding()) {
            // Solo redirigir si no está ya en onboarding
            if (currentPath !== '/onboarding/profile' && !currentPath.startsWith('/onboarding/')) {
                hasRedirected.current = true; // Marcar como redirigido
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
                    hasRedirected.current = true; // Marcar como redirigido
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
