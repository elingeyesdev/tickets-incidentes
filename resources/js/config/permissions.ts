/**
 * Permissions Configuration
 * Define qué roles pueden acceder a qué rutas
 */

export type RoleCode = 'USER' | 'AGENT' | 'COMPANY_ADMIN' | 'PLATFORM_ADMIN';

export interface RoutePermission {
    path: string;
    allowedRoles: RoleCode[];
    requiresEmailVerification?: boolean;
}

/**
 * Mapa de permisos por ruta
 * Define qué roles pueden acceder a cada sección
 */
export const routePermissions: RoutePermission[] = [
    // ============================================
    // USER Routes
    // ============================================
    {
        path: '/tickets',
        allowedRoles: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'],
    },
    {
        path: '/announcements',
        allowedRoles: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'],
    },
    {
        path: '/help-center',
        allowedRoles: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'],
    },
    {
        path: '/profile',
        allowedRoles: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'],
    },
    {
        path: '/settings',
        allowedRoles: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'],
    },

    // ============================================
    // AGENT Routes
    // ============================================
    {
        path: '/agent',
        allowedRoles: ['AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'],
    },

    // ============================================
    // COMPANY_ADMIN Routes
    // ============================================
    {
        path: '/empresa',
        allowedRoles: ['COMPANY_ADMIN', 'PLATFORM_ADMIN'],
    },

    // ============================================
    // PLATFORM_ADMIN Routes
    // ============================================
    {
        path: '/admin',
        allowedRoles: ['PLATFORM_ADMIN'],
    },
];

/**
 * Dashboard por defecto según el rol
 */
export const defaultDashboardByRole: Record<RoleCode, string> = {
    USER: '/tickets',
    AGENT: '/agent/dashboard',
    COMPANY_ADMIN: '/empresa/dashboard',
    PLATFORM_ADMIN: '/admin/dashboard',
};

/**
 * Verifica si un rol tiene acceso a una ruta
 */
export const canAccessRoute = (userRoles: RoleCode[], path: string): boolean => {
    // Si no hay roles, no tiene acceso
    if (!userRoles || userRoles.length === 0) return false;

    // PLATFORM_ADMIN tiene acceso a todo
    if (userRoles.includes('PLATFORM_ADMIN')) return true;

    // Buscar la regla de permiso para la ruta
    const permission = routePermissions.find(p => path.startsWith(p.path));
    
    // Si no hay regla específica, denegamos acceso por seguridad
    if (!permission) return false;

    // Verificar si alguno de los roles del usuario está permitido
    return userRoles.some(role => permission.allowedRoles.includes(role));
};

/**
 * Obtiene el dashboard por defecto del usuario según su rol principal
 */
export const getDefaultDashboard = (userRoles: RoleCode[]): string => {
    // Prioridad: PLATFORM_ADMIN > COMPANY_ADMIN > AGENT > USER
    if (userRoles.includes('PLATFORM_ADMIN')) return defaultDashboardByRole.PLATFORM_ADMIN;
    if (userRoles.includes('COMPANY_ADMIN')) return defaultDashboardByRole.COMPANY_ADMIN;
    if (userRoles.includes('AGENT')) return defaultDashboardByRole.AGENT;
    return defaultDashboardByRole.USER;
};

