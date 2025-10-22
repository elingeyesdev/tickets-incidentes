/**
 * usePermissions Hook
 * Hook reutilizable para verificar permisos y roles del usuario
 *
 * Proporciona funciones helper para:
 * - Verificar roles específicos
 * - Verificar acceso a rutas
 * - Verificar si es admin/agente/usuario
 */

import { canAccessRoute } from '@/config/permissions';
import type { RoleCode } from '@/types/graphql';

// Import useAuth from contexts directly to avoid circular dependency
// Since this file is exported from hooks/index.ts
import { useAuth } from '@/contexts';

export function usePermissions() {
    const { user, hasRole } = useAuth();

    /**
     * Verifica si el usuario tiene un rol específico
     */
    const checkRole = (role: RoleCode | RoleCode[]): boolean => {
        return hasRole(role);
    };

    /**
     * Verifica si el usuario puede acceder a una ruta
     */
    const checkRoute = (path: string): boolean => {
        if (!user) return false;
        const userRoles = user.roleContexts.map((rc) => rc.roleCode as RoleCode);
        return canAccessRoute(userRoles, path);
    };

    /**
     * Verifica si el usuario es admin de plataforma
     */
    const isPlatformAdmin = (): boolean => {
        return checkRole('PLATFORM_ADMIN' as const);
    };

    /**
     * Verifica si el usuario es admin de empresa
     */
    const isCompanyAdmin = (): boolean => {
        return checkRole('COMPANY_ADMIN' as const);
    };

    /**
     * Verifica si el usuario es agente
     */
    const isAgent = (): boolean => {
        return checkRole('AGENT' as const);
    };

    /**
     * Verifica si el usuario es usuario final
     */
    const isUser = (): boolean => {
        return checkRole('USER' as const);
    };

    /**
     * Verifica si el usuario tiene algún rol administrativo
     * (PLATFORM_ADMIN o COMPANY_ADMIN)
     */
    const isAdmin = (): boolean => {
        return isPlatformAdmin() || isCompanyAdmin();
    };

    /**
     * Verifica si el usuario tiene algún rol de staff
     * (PLATFORM_ADMIN, COMPANY_ADMIN o AGENT)
     */
    const isStaff = (): boolean => {
        return isPlatformAdmin() || isCompanyAdmin() || isAgent();
    };

    /**
     * Obtiene todos los roles del usuario
     */
    const getUserRoles = (): RoleCode[] => {
        if (!user) return [];
        return user.roleContexts.map((rc) => rc.roleCode as RoleCode);
    };

    /**
     * Obtiene el rol principal del usuario
     * (el primero en la lista de roleContexts)
     */
    const getPrimaryRole = (): RoleCode | null => {
        if (!user || user.roleContexts.length === 0) return null;
        return user.roleContexts[0].roleCode as RoleCode;
    };

    /**
     * Verifica si el usuario tiene múltiples roles
     */
    const hasMultipleRoles = (): boolean => {
        if (!user) return false;
        return user.roleContexts.length > 1;
    };

    return {
        // Verificaciones de roles
        checkRole,
        checkRoute,

        // Verificaciones específicas
        isPlatformAdmin,
        isCompanyAdmin,
        isAgent,
        isUser,
        isAdmin,
        isStaff,

        // Información de roles
        getUserRoles,
        getPrimaryRole,
        hasMultipleRoles,
    };
}