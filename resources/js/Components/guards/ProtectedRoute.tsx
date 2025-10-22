/**
 * ProtectedRoute - Protección para rutas autenticadas
 * SOLUCIÓN ANTI-LOOP: Usa SessionStorage para persistir flag durante re-montajes
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
import { useAuth } from '@/hooks';
import { safeRedirect } from '@/lib/utils';
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

        // 1. Verificar autenticación
        if (!user) {
            // Solo redirigir si no está ya en la ruta de destino
            if (currentPath !== redirectTo) {
                console.log('[ProtectedRoute] Usuario no autenticado, redirigiendo a:', redirectTo);
                safeRedirect(redirectTo, { replace: true });
            }
            return;
        }

        // 2. Verificar onboarding completado
        if (!hasCompletedOnboarding()) {
            // Solo redirigir si no está ya en onboarding
            if (currentPath !== '/onboarding/profile' && !currentPath.startsWith('/onboarding/')) {
                console.log('[ProtectedRoute] Onboarding incompleto, redirigiendo a /onboarding/profile');
                safeRedirect('/onboarding/profile', { replace: true });
            }
            return;
        }

        // 3. Verificar roles (si se especificaron)
        if (allowedRoles.length > 0) {
            const hasPermission = allowedRoles.some(role => hasRole(role));
            if (!hasPermission) {
                // Solo redirigir si no está ya en unauthorized
                if (currentPath !== '/unauthorized') {
                    console.log('[ProtectedRoute] Sin permisos, redirigiendo a /unauthorized');
                    safeRedirect('/unauthorized', { replace: true });
                }
                return;
            }
        }
        // IMPORTANTE: hasCompletedOnboarding y hasRole están memoizados en AuthContext con useCallback
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [user, loading, allowedRoles, redirectTo]);

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
