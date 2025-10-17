/**
 * Apollo Client Configuration
 * Professional GraphQL setup with token management
 */

import {
    ApolloClient,
    InMemoryCache,
    HttpLink,
    from,
    Observable,
} from '@apollo/client';
import { onError, ErrorResponse } from '@apollo/client/link/error';
import { setContext } from '@apollo/client/link/context';

// ============================================
// TOKEN STORAGE (Profesional y Seguro)
// ============================================



const TOKEN_KEY = 'helpdesk_access_token';
const TOKEN_EXPIRY_KEY = 'helpdesk_token_expiry';

export const TokenStorage = {
    getAccessToken(): string | null {
        // Verificar si el token ha expirado
        const expiry = localStorage.getItem(TOKEN_EXPIRY_KEY);
        if (expiry && Date.now() > parseInt(expiry)) {
            this.clearTokens();
            return null;
        }
        return localStorage.getItem(TOKEN_KEY);
    },

    setAccessToken(token: string, expiresIn: number): void {
        localStorage.setItem(TOKEN_KEY, token);
        // Guardar timestamp de expiración
        const expiryTime = Date.now() + expiresIn * 1000;
        localStorage.setItem(TOKEN_EXPIRY_KEY, expiryTime.toString());
    },

    clearTokens(): void {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(TOKEN_EXPIRY_KEY);
    },

    isTokenExpired(): boolean {
        const expiry = localStorage.getItem(TOKEN_EXPIRY_KEY);
        if (!expiry) return true;
        return Date.now() > parseInt(expiry);
    },
};

// ============================================
// GRAPHQL ENDPOINT
// ============================================

const httpLink = new HttpLink({
    uri: '/graphql',
    credentials: 'include', // Importante: Envía cookies httpOnly automáticamente
});

// ============================================
// AUTH LINK - Agregar token a headers
// ============================================

const authLink = setContext((_, { headers }) => {
    const token = TokenStorage.getAccessToken();

    return {
        headers: {
            ...headers,
            authorization: token ? `Bearer ${token}` : '',
            'X-Requested-With': 'XMLHttpRequest', // Laravel CSRF
        },
    };
});

// ============================================
// REFRESH TOKEN LOGIC
// ============================================

let isRefreshing = false;
let pendingRequests: Array<() => void> = [];

const resolvePendingRequests = () => {
    pendingRequests.forEach((callback) => callback());
    pendingRequests = [];
};

const REFRESH_TOKEN_MUTATION = `
    mutation RefreshToken {
        refreshToken {
            accessToken
            refreshToken
            tokenType
            expiresIn
        }
    }
`;

/**
 * Intenta refrescar el access token usando el refresh token (httpOnly cookie)
 */
const refreshAccessToken = async (): Promise<string | null> => {
    try {
        const response = await fetch('/graphql', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include', // Envía el refresh token cookie
            body: JSON.stringify({
                query: REFRESH_TOKEN_MUTATION,
            }),
        });

        const result = await response.json();

        if (result.errors) {
            console.error('Refresh token failed:', result.errors);
            return null;
        }

        const { accessToken, expiresIn } = result.data.refreshToken;
        TokenStorage.setAccessToken(accessToken, expiresIn);

        return accessToken;
    } catch (error) {
        console.error('Error refreshing token:', error);
        return null;
    }
};

// ============================================
// ERROR LINK - Manejo automático de refresh
// ============================================

const errorLink = onError(({ graphQLErrors, networkError, operation, forward }: ErrorResponse) => {
    if (graphQLErrors && graphQLErrors.length > 0) {
        for (const err of graphQLErrors) {
            // Si el error es de autenticación inválida
            if (err.extensions?.code === 'UNAUTHENTICATED' || err.extensions?.code === 'INVALID_TOKEN') {
                // Evitar loop infinito
                if (operation.operationName === 'RefreshToken') {
                    TokenStorage.clearTokens();
                    window.location.href = '/login';
                    return undefined;
                }

                // Si ya estamos refrescando, esperar
                if (isRefreshing) {
                    return new Observable((observer) => {
                        pendingRequests.push(() => {
                            forward(operation).subscribe(observer);
                        });
                    });
                }

                // Iniciar proceso de refresh
                isRefreshing = true;

                return new Observable((observer) => {
                    refreshAccessToken()
                        .then((newToken) => {
                            isRefreshing = false;
                            resolvePendingRequests();

                            if (!newToken) {
                                TokenStorage.clearTokens();
                                window.location.href = '/login';
                                observer.error(new Error('Failed to refresh token'));
                                return;
                            }

                            // Actualizar header de la operación
                            operation.setContext({
                                headers: {
                                    ...operation.getContext().headers,
                                    authorization: `Bearer ${newToken}`,
                                },
                            });

                            // Reintentar la operación
                            forward(operation).subscribe(observer);
                        })
                        .catch((error) => {
                            isRefreshing = false;
                            pendingRequests = [];
                            TokenStorage.clearTokens();
                            window.location.href = '/login';
                            observer.error(error);
                        });
                });
            }
        }
    }

    if (networkError) {
        console.error(`[Network error]: ${networkError}`);
    }

    return undefined;
});

// ============================================
// CACHE CONFIGURATION
// ============================================

const cache = new InMemoryCache({
    typePolicies: {
        Query: {
            fields: {
                // Configurar merge policies para paginación
                users: {
                    keyArgs: ['filters', 'orderBy'],
                    merge(_existing, incoming) {
                        return incoming;
                    },
                },
            },
        },
        User: {
            keyFields: ['id'],
        },
        Company: {
            keyFields: ['id'],
        },
    },
});

// ============================================
// APOLLO CLIENT INSTANCE
// ============================================

export const apolloClient = new ApolloClient({
    link: from([errorLink, authLink, httpLink]),
    cache,
    defaultOptions: {
        watchQuery: {
            fetchPolicy: 'cache-and-network',
            errorPolicy: 'all',
        },
        query: {
            fetchPolicy: 'network-only',
            errorPolicy: 'all',
        },
        mutate: {
            errorPolicy: 'all',
        },
    },
});

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Guarda los tokens después de login/register
 */
export const saveAuthTokens = (accessToken: string, expiresIn: number) => {
    TokenStorage.setAccessToken(accessToken, expiresIn);
    // El refresh token ya está guardado como httpOnly cookie por Laravel
};

interface TempUserData {
    user: {
        id: string;
        email: string;
        [key: string]: unknown;
    };
    roleContexts: Array<{
        roleCode: string;
        [key: string]: unknown;
    }>;
}

/**
 * Guarda el usuario completo en localStorage (temporal hasta que AuthContext cargue)
 */
export const saveUserData = (user: TempUserData['user'], roleContexts: TempUserData['roleContexts']) => {
    localStorage.setItem('helpdesk_user_temp', JSON.stringify({ user, roleContexts }));
};

/**
 * Obtiene el usuario temporal de localStorage
 */
export const getTempUserData = () => {
    const data = localStorage.getItem('helpdesk_user_temp');
    return data ? JSON.parse(data) : null;
};

/**
 * Limpia el usuario temporal
 */
export const clearTempUserData = () => {
    localStorage.removeItem('helpdesk_user_temp');
};

/**
 * Limpia todos los tokens al hacer logout
 */
export const clearAuthTokens = () => {
    TokenStorage.clearTokens();
    clearTempUserData();
    apolloClient.clearStore();
};

/**
 * Verifica si el usuario está autenticado
 */
export const isAuthenticated = (): boolean => {
    return TokenStorage.getAccessToken() !== null && !TokenStorage.isTokenExpired();
};

