/**
 * AuthContext - Gestión Global de Autenticación con GraphQL
 *
 * Responsabilidades:
 * - Estado de autenticación mediante GraphQL
 * - Usuario actual desde accessToken + authStatus query
 * - Roles y permisos
 * - Login/Logout con GraphQL mutations
 *
 * Optimizaciones:
 * - Estado de 3 fases: 'initializing' | 'authenticated' | 'unauthenticated'
 * - Caché de datos temporales (post-login/register)
 * - Ejecución única del useEffect (sin re-verificaciones innecesarias)
 * - Fullscreen loader durante inicialización
 */

import React, { createContext, useContext, useState, useEffect, useMemo, ReactNode } from 'react';
import { useLazyQuery, useMutation } from '@apollo/client/react';
import { apolloClient, TokenStorage, getTempUserData, clearTempUserData } from '@/lib/apollo/client';
import { AUTH_STATUS_QUERY } from '@/lib/graphql/queries/auth.queries';
import { LOGOUT_MUTATION } from '@/lib/graphql/mutations/auth.mutations';
import { canAccessRoute as checkRoutePermission } from '@/config/permissions';
import { hasCompletedOnboarding as checkOnboardingCompleted } from '@/lib/utils/onboarding';
import type { User, RoleCode } from '@/types';
import type { AuthStatusQuery, AuthStatusQueryVariables, LogoutMutation, LogoutMutationVariables } from '@/types/graphql-generated';

type AuthState = 'initializing' | 'authenticated' | 'unauthenticated';

interface AuthContextType {
    user: User | null;
    authState: AuthState;
    isAuthenticated: boolean;
    loading: boolean;
    hasRole: (role: RoleCode | RoleCode[]) => boolean;
    canAccessRoute: (path: string) => boolean;
    hasCompletedOnboarding: () => boolean;
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
    const [authState, setAuthState] = useState<AuthState>('initializing');

    const [getAuthStatus] = useLazyQuery<AuthStatusQuery, AuthStatusQueryVariables>(AUTH_STATUS_QUERY, {
        fetchPolicy: 'network-only',
    });

    const [logoutMutation] = useMutation<LogoutMutation, LogoutMutationVariables>(LOGOUT_MUTATION);

    // Computed values
    const isAuthenticated = authState === 'authenticated';
    const loading = authState === 'initializing';

    // Inicialización de autenticación (ejecuta UNA SOLA VEZ al montar)
    useEffect(() => {
        let isMounted = true;

        const initializeAuth = async () => {
            setAuthState('initializing');

            const token = TokenStorage.getAccessToken();

            if (!token) {
                // Sin token = no autenticado
                if (isMounted) {
                    setUser(null);
                    setAuthState('unauthenticated');
                }
                return;
            }

            // Tiene token - primero intentar usar caché temporal
            try {
                const tempData = getTempUserData();

                if (tempData && tempData.user && tempData.roleContexts) {
                    // Usar datos temporales inmediatamente (sin llamada al servidor)
                    const fullUser: User = {
                        ...tempData.user,
                        roleContexts: tempData.roleContexts,
                    };

                    if (isMounted) {
                        setUser(fullUser);
                        setAuthState('authenticated');
                    }

                    // Limpiar caché después de usarlo
                    clearTempUserData();
                    return;
                }

                // No hay caché temporal, consultar servidor
                const result = await getAuthStatus();

                if (isMounted) {
                    if (result.data?.authStatus?.isAuthenticated) {
                        setUser(result.data.authStatus.user as User);
                        setAuthState('authenticated');
                    } else {
                        setUser(null);
                        setAuthState('unauthenticated');
                    }
                }
            } catch (error) {
                console.error('Error initializing auth:', error);
                if (isMounted) {
                    setUser(null);
                    setAuthState('unauthenticated');
                }
            }
        };

        initializeAuth();

        return () => {
            isMounted = false;
        };
    }, []); // Array vacío = ejecuta UNA SOLA VEZ al montar

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

            // Actualizar estado
            setUser(null);
            setAuthState('unauthenticated');

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
        setAuthState('authenticated');
    };

    /**
     * Refresca los datos del usuario desde el servidor
     */
    const refreshUser = async () => {
        const token = TokenStorage.getAccessToken();
        if (token) {
            const result = await getAuthStatus();
            if (result.data?.authStatus?.isAuthenticated) {
                setUser(result.data.authStatus.user as User);
                setAuthState('authenticated');
            } else {
                setUser(null);
                setAuthState('unauthenticated');
            }
        }
    };

    /**
     * Verifica si el usuario ha completado el proceso de onboarding
     * (VerifyEmail → CompleteProfile → ConfigurePreferences)
     * Usa el helper centralizado de onboarding
     */
    const hasCompletedOnboarding = (): boolean => {
        return checkOnboardingCompleted(user);
    };

    // Memoizar el contexto para prevenir re-renderizados innecesarios
    const value: AuthContextType = useMemo(() => ({
        user,
        authState,
        isAuthenticated,
        loading,
        hasRole,
        canAccessRoute,
        hasCompletedOnboarding,
        logout,
        updateUser,
        refreshUser,
    }), [user, authState]);

    // Mostrar fullscreen loader durante inicialización
    if (authState === 'initializing') {
        return (
            <div className="fixed inset-0 flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 z-50">
                <div className="text-center">
                    <div className="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mb-4"></div>
                    <p className="text-lg text-gray-700 dark:text-gray-300 font-medium">
                        Inicializando...
                    </p>
                </div>
            </div>
        );
    }

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

