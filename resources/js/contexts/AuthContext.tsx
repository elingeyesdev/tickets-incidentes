/**
 * AuthContext - Gestión Global de Autenticación con XState
 *
 * Responsabilidades:
 * - Estado de autenticación mediante XState machine (authMachine)
 * - Usuario actual desde TokenManager + authStatus query
 * - Roles y permisos
 * - Login/Logout con GraphQL mutations
 * - Multi-tab synchronization via AuthChannel
 * - Session keep-alive via HeartbeatService
 *
 * Architecture:
 * - Uses XState v5 state machine for predictable state management
 * - Subscribes to AuthChannel for cross-tab synchronization
 * - Starts/stops HeartbeatService based on auth state
 * - Uses TokenManager as single source of truth for tokens
 * - NO legacy code (TokenStorage, getTempUserData, etc.)
 */

import React, { createContext, useContext, useEffect, useMemo, useCallback, ReactNode } from 'react';
import { useLazyQuery, useMutation } from '@apollo/client/react';
import { useMachine } from '@xstate/react';
import { router } from '@inertiajs/react';
import { apolloClient } from '@/lib/apollo/client';
import { authMachine } from '@/lib/auth/AuthMachine';
import { TokenManager } from '@/lib/auth/TokenManager';
import { AuthChannel } from '@/lib/auth/AuthChannel';
import { HeartbeatService } from '@/lib/auth/HeartbeatService';
import { AUTH_STATUS_QUERY } from '@/lib/graphql/queries/auth.queries';
import { LOGOUT_MUTATION } from '@/lib/graphql/mutations/auth.mutations';
import { canAccessRoute as checkRoutePermission } from '@/config/permissions';
import { hasCompletedOnboarding as checkOnboardingCompleted } from '@/lib/utils/onboarding';
import { clearRedirectFlag } from '@/lib/utils/navigation';
import type { RoleCode as RoleCodeModels } from '@/types/models';
import type { RoleCode, RoleContext, AuthStatusQuery, AuthStatusQueryVariables, LogoutMutation, LogoutMutationVariables, UserAuthInfo } from '@/types/graphql';

type AuthState = 'initializing' | 'authenticated' | 'unauthenticated';

interface AuthContextType {
    user: UserAuthInfo | null;
    authState: AuthState;
    isAuthenticated: boolean;
    loading: boolean;
    hasRole: (role: RoleCode | RoleCode[]) => boolean;
    canAccessRoute: (path: string) => boolean;
    hasCompletedOnboarding: () => boolean;
    logout: (everywhere?: boolean) => Promise<void>;
    updateUser: (user: UserAuthInfo) => void;
    refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
    children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
    // XState v5 machine integration
    const [state, send] = useMachine(authMachine);

    // GraphQL queries and mutations
    const [getAuthStatus] = useLazyQuery<AuthStatusQuery, AuthStatusQueryVariables>(AUTH_STATUS_QUERY, {
        fetchPolicy: 'network-only',
    });

    const [logoutMutation] = useMutation<LogoutMutation, LogoutMutationVariables>(LOGOUT_MUTATION);

    // Computed values from XState machine
    const authState = state.value as AuthState;
    const user = state.context.user as UserAuthInfo | null;
    const isAuthenticated = authState === 'authenticated';
    const loading = authState === 'initializing';

    // SESSION DETECTION: Initialize authentication from TokenManager on mount
    useEffect(() => {
        const initializeAuth = async () => {
            // Check if a valid token exists in TokenManager
            const token = TokenManager.getAccessTokenObject();
            const validation = TokenManager.validateToken();

            if (token && validation.isValid) {
                // Valid token exists - fetch user data from server
                try {
                    const result = await getAuthStatus();

                    console.log('[AuthContext] Session detected, fetching user data:', result.data);

                    if (result.data?.authStatus?.isAuthenticated && result.data.authStatus.user) {
                        // Notify machine about detected session
                        send({
                            type: 'SESSION_DETECTED',
                            token: token,
                            user: result.data.authStatus.user,
                        });
                    } else {
                        // Token exists but server says not authenticated
                        send({ type: 'SESSION_INVALID' });
                    }
                } catch (error) {
                    console.error('[AuthContext] Error fetching user data:', error);
                    send({ type: 'SESSION_INVALID' });
                }
            } else {
                // No valid token found
                send({ type: 'SESSION_INVALID' });
            }
        };

        initializeAuth();
    }, []); // Run once on mount

    // MULTI-TAB SYNC: Subscribe to AuthChannel for cross-tab events
    useEffect(() => {
        const unsubscribe = AuthChannel.subscribe((event) => {
            console.log('[AuthContext] AuthChannel event received:', event.type);

            switch (event.type) {
                case 'LOGOUT':
                    // Another tab logged out - update machine state
                    send({ type: 'LOGOUT' });
                    break;

                case 'SESSION_EXPIRED':
                    // Session expired in another tab - force logout
                    send({ type: 'LOGOUT' });
                    TokenManager.clearToken();
                    router.visit('/login', {
                        data: { reason: 'expired' },
                        replace: true
                    });
                    break;

                case 'TOKEN_REFRESHED':
                    // Token was refreshed in another tab - machine will handle it automatically
                    console.log('[AuthContext] Token refreshed in another tab');
                    break;

                case 'LOGIN':
                    // Another tab logged in - could refresh to sync (optional)
                    console.log('[AuthContext] Login detected in another tab');
                    break;
            }
        });

        return unsubscribe;
    }, [send]);

    // HEARTBEAT SERVICE: Start/stop based on authentication state
    useEffect(() => {
        if (authState === 'authenticated') {
            console.log('[AuthContext] Starting HeartbeatService');
            HeartbeatService.start();
        } else {
            console.log('[AuthContext] Stopping HeartbeatService');
            HeartbeatService.stop();
        }

        return () => {
            HeartbeatService.stop();
        };
    }, [authState]);

    /**
     * Verifica si el usuario tiene un rol específico o alguno de una lista
     * IMPORTANTE: Memoizada con useCallback para evitar loops en useEffect
     */
    const hasRole = useCallback((role: RoleCode | RoleCode[]): boolean => {
        if (!user) return false;

        const roles = Array.isArray(role) ? role : [role];
        const userRoles = user.roleContexts.map((rc: RoleContext) => rc.roleCode);

        return roles.some((r) => userRoles.includes(r));
    }, [user]);

    /**
     * Verifica si el usuario puede acceder a una ruta específica
     * Usa la configuración centralizada de permisos
     * IMPORTANTE: Memoizada con useCallback para evitar loops en useEffect
     */
    const canAccessRoute = useCallback((path: string): boolean => {
        if (!user) return false;

        // Rutas públicas (accesibles sin autenticación)
        const publicRoutes = ['/login', '/register', '/register-user', '/solicitud-empresa', '/verify-email', '/'];
        if (publicRoutes.some(route => path === route || path.startsWith(route + '/'))) {
            return true;
        }

        // Usar configuración centralizada de permisos
        // Cast GraphQL enum to string for permissions.ts compatibility
        const userRoles = user.roleContexts.map((rc: RoleContext) => rc.roleCode as RoleCodeModels);
        return checkRoutePermission(userRoles, path);
    }, [user]);

    /**
     * LOGOUT: Closes user session via GraphQL and cleans up all state
     */
    const logout = async (everywhere: boolean = false) => {
        try {
            // Call logout mutation on backend
            await logoutMutation({ variables: { everywhere } });
        } catch (error) {
            console.error('[AuthContext] Error during logout mutation:', error);
        } finally {
            // Clear tokens via TokenManager (single source of truth)
            TokenManager.clearToken();

            // Broadcast logout event to other tabs
            AuthChannel.broadcast({
                type: 'LOGOUT',
                payload: { reason: 'manual', timestamp: Date.now() }
            });

            // Clear redirect flag from SessionStorage
            clearRedirectFlag();

            // Clear Apollo cache
            await apolloClient.clearStore();

            // Update XState machine
            send({ type: 'LOGOUT' });

            // Redirect to login
            router.visit('/login', { replace: true });
        }
    };

    /**
     * UPDATE USER: Updates user data in context
     * Useful after profile updates or preference changes
     */
    const updateUser = (updatedUser: UserAuthInfo) => {
        const token = TokenManager.getAccessTokenObject();
        if (token) {
            // Send LOGIN event to machine to update context with new user data
            send({
                type: 'LOGIN',
                token: token,
                user: updatedUser,
            });
        }
    };

    /**
     * REFRESH USER: Fetches fresh user data from server
     * Useful when you need to reload user info without re-authenticating
     */
    const refreshUser = async () => {
        const token = TokenManager.getAccessTokenObject();
        const validation = TokenManager.validateToken();

        if (token && validation.isValid) {
            try {
                const result = await getAuthStatus();
                console.log('[refreshUser] Query response:', result.data);
                console.log('[refreshUser] User object:', result.data?.authStatus?.user);

                if (result.data?.authStatus?.isAuthenticated && result.data.authStatus.user) {
                    // Update machine with fresh user data
                    send({
                        type: 'SESSION_DETECTED',
                        token: token,
                        user: result.data.authStatus.user,
                    });
                } else {
                    // Server says not authenticated
                    send({ type: 'SESSION_INVALID' });
                }
            } catch (error) {
                console.error('[refreshUser] Error fetching user data:', error);
                send({ type: 'SESSION_INVALID' });
            }
        } else {
            // No valid token
            send({ type: 'SESSION_INVALID' });
        }
    };

    /**
     * Verifica si el usuario ha completado el proceso de onboarding
     * (VerifyEmail → CompleteProfile → ConfigurePreferences)
     * Usa el helper centralizado de onboarding
     * IMPORTANTE: Memoizada con useCallback para evitar loops en useEffect
     */
    const hasCompletedOnboarding = useCallback((): boolean => {
        console.log('[hasCompletedOnboarding] user:', user);
        console.log('[hasCompletedOnboarding] onboardingCompletedAt:', user?.onboardingCompletedAt);
        const result = checkOnboardingCompleted(user);
        console.log('[hasCompletedOnboarding] result:', result);
        return result;
    }, [user]);

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
    }), [user, authState, isAuthenticated, loading, hasRole, canAccessRoute, hasCompletedOnboarding]);

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

