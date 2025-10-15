/**
 * AuthContext - Gestión Global de Autenticación con GraphQL
 * 
 * Responsabilidades:
 * - Estado de autenticación mediante GraphQL
 * - Usuario actual desde accessToken + authStatus query
 * - Roles y permisos
 * - Login/Logout con GraphQL mutations
 */

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useLazyQuery, useMutation } from '@apollo/client/react';
import { apolloClient, TokenStorage, getTempUserData, clearTempUserData } from '@/lib/apollo/client';
import { AUTH_STATUS_QUERY } from '@/lib/graphql/queries/auth.queries';
import { LOGOUT_MUTATION } from '@/lib/graphql/mutations/auth.mutations';
import { canAccessRoute as checkRoutePermission } from '@/config/permissions';
import type { User, RoleCode } from '@/types';

interface AuthContextType {
    user: User | null;
    isAuthenticated: boolean;
    loading: boolean;
    hasRole: (role: RoleCode | RoleCode[]) => boolean;
    canAccessRoute: (path: string) => boolean;
    logout: (everywhere?: boolean) => Promise<void>;
    updateUser: (user: User) => void;
    refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
    children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
    const [user, setUser] = useState<User | null>(null);
    const [loading, setLoading] = useState(true);

    const [getAuthStatus] = useLazyQuery(AUTH_STATUS_QUERY, {
        fetchPolicy: 'network-only',
    });

    const [logoutMutation] = useMutation(LOGOUT_MUTATION);

    // Al montar, verificar si hay accessToken y obtener usuario
    useEffect(() => {
        const token = TokenStorage.getAccessToken();
        
        if (token) {
            // Primero intentar leer datos temporales (recién logueado/registrado)
            const tempData = getTempUserData();
            
            if (tempData && tempData.user && tempData.roleContexts) {
                // Usar datos temporales y construir el usuario completo
                const fullUser: User = {
                    ...tempData.user,
                    roleContexts: tempData.roleContexts,
                };
                setUser(fullUser);
                setLoading(false);
                
                // Limpiar datos temporales después de usarlos
                clearTempUserData();
            } else {
                // No hay datos temporales, obtener usuario actual desde el backend
                getAuthStatus().then((result: any) => {
                    if (result.data?.authStatus?.isAuthenticated) {
                        setUser(result.data.authStatus.user);
                    } else {
                        setUser(null);
                    }
                    setLoading(false);
                }).catch(() => {
                    setUser(null);
                    setLoading(false);
                });
            }
        } else {
            // No hay token, no autenticado
            setUser(null);
            setLoading(false);
        }
    }, [getAuthStatus]);

    const isAuthenticated = user !== null;

    /**
     * Verifica si el usuario tiene un rol específico o alguno de una lista
     */
    const hasRole = (role: RoleCode | RoleCode[]): boolean => {
        if (!user) return false;

        const roles = Array.isArray(role) ? role : [role];
        const userRoles = user.roleContexts.map((rc) => rc.roleCode);

        return roles.some((r) => userRoles.includes(r));
    };

    /**
     * Verifica si el usuario puede acceder a una ruta específica
     * Usa la configuración centralizada de permisos
     */
    const canAccessRoute = (path: string): boolean => {
        if (!user) return false;

        // Rutas públicas (accesibles sin autenticación)
        const publicRoutes = ['/login', '/register', '/register-user', '/solicitud-empresa', '/verify-email', '/'];
        if (publicRoutes.some(route => path === route || path.startsWith(route + '/'))) {
            return true;
        }

        // Usar configuración centralizada de permisos
        const userRoles = user.roleContexts.map((rc) => rc.roleCode);
        return checkRoutePermission(userRoles, path);
    };

    /**
     * Cierra sesión del usuario mediante GraphQL
     */
    const logout = async (everywhere: boolean = false) => {
        try {
            await logoutMutation({ variables: { everywhere } });
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
        } finally {
            // Limpiar tokens locales
            TokenStorage.clearTokens();
            
            // Limpiar caché de Apollo
            await apolloClient.clearStore();
            
            // Limpiar estado
            setUser(null);
            
            // Redirigir a login
            window.location.href = '/login';
        }
    };

    /**
     * Actualiza el usuario en el contexto
     * Útil después de actualizar perfil o preferencias
     */
    const updateUser = (updatedUser: User) => {
        setUser(updatedUser);
    };

    /**
     * Refresca los datos del usuario desde el servidor
     */
    const refreshUser = async () => {
        const token = TokenStorage.getAccessToken();
        if (token) {
            const result: any = await getAuthStatus();
            if (result.data?.authStatus?.isAuthenticated) {
                setUser(result.data.authStatus.user);
            }
        }
    };

    const value: AuthContextType = {
        user,
        isAuthenticated,
        loading,
        hasRole,
        canAccessRoute,
        logout,
        updateUser,
        refreshUser,
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

/**
 * Hook para usar el contexto de autenticación
 */
export const useAuth = (): AuthContextType => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};

