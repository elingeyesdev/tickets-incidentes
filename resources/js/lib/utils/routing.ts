/**
 * Routing Utilities
 * Funciones helper para navegación y redirecciones
 */

import { getDefaultDashboard } from '@/config/permissions';
import type { User, RoleCode } from '@/types';

/**
 * Obtiene la URL de dashboard apropiada para un usuario
 *
 * @param user - Usuario actual (o null si no está autenticado)
 * @returns URL del dashboard apropiado según el rol del usuario
 *
 * @example
 * ```tsx
 * const dashboardUrl = getUserDashboardUrl(user);
 * router.visit(dashboardUrl); // Redirige a /admin/dashboard, /empresa/dashboard, etc.
 * ```
 */
export const getUserDashboardUrl = (user: User | null): string => {
    if (!user) return '/login';

    const userRoles = user.roleContexts.map((rc) => rc.roleCode as RoleCode);
    return getDefaultDashboard(userRoles);
};

/**
 * Verifica si una ruta es de onboarding
 *
 * @param path - Ruta a verificar
 * @returns true si es una ruta de onboarding
 */
export const isOnboardingRoute = (path: string): boolean => {
    return path.startsWith('/onboarding/') || path === '/verify-email';
};

/**
 * Verifica si una ruta es pública (accesible sin autenticación)
 *
 * @param path - Ruta a verificar
 * @returns true si es una ruta pública
 */
export const isPublicRoute = (path: string): boolean => {
    const publicRoutes = ['/', '/login', '/register', '/register-user', '/solicitud-empresa'];
    return publicRoutes.includes(path) || path.startsWith('/verify-email');
};

/**
 * Verifica si una ruta es de autenticación (login/register)
 *
 * @param path - Ruta a verificar
 * @returns true si es una ruta de autenticación
 */
export const isAuthRoute = (path: string): boolean => {
    const authRoutes = ['/login', '/register', '/register-user', '/solicitud-empresa'];
    return authRoutes.includes(path);
};

/**
 * Obtiene el breadcrumb friendly name para una ruta
 * Útil para navegación
 *
 * @param path - Ruta actual
 * @returns Nombre legible para breadcrumbs
 */
export const getRouteName = (path: string): string => {
    const routeNames: Record<string, string> = {
        '/': 'Inicio',
        '/login': 'Iniciar Sesión',
        '/register': 'Registro',
        '/register-user': 'Registro de Usuario',
        '/solicitud-empresa': 'Solicitud de Empresa',
        '/verify-email': 'Verificar Email',
        '/onboarding/profile': 'Completar Perfil',
        '/onboarding/preferences': 'Configurar Preferencias',
        '/tickets': 'Tickets',
        '/agent/dashboard': 'Dashboard de Agente',
        '/empresa/dashboard': 'Dashboard de Empresa',
        '/admin/dashboard': 'Dashboard de Administrador',
    };

    return routeNames[path] || path;
};
