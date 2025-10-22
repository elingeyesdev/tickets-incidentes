/**
 * Apollo Client Configuration
 * Professional GraphQL setup with centralized token management
 *
 * IMPORTANT: This file uses TokenManager as the single source of truth for tokens.
 * NO legacy TokenStorage - all token operations go through TokenManager.
 */

import {
    ApolloClient,
    InMemoryCache,
    HttpLink,
    from,
    Observable,
} from '@apollo/client';
import { onError } from '@apollo/client/link/error';
import { CombinedGraphQLErrors } from '@apollo/client/errors';
import { setContext } from '@apollo/client/link/context';
import { TokenManager, TokenRefreshService } from '@/lib/auth';

// ============================================
// GRAPHQL ENDPOINT
// ============================================

const httpLink = new HttpLink({
    uri: '/graphql',
    credentials: 'include', // Importante: Envía cookies httpOnly automáticamente
});

// ============================================
// AUTH LINK - Add token to request headers
// ============================================

const authLink = setContext((_, { headers }) => {
    // Use TokenManager as single source of truth for tokens
    const token = TokenManager.getAccessToken();

    return {
        headers: {
            ...headers,
            authorization: token ? `Bearer ${token}` : '',
            'X-Requested-With': 'XMLHttpRequest', // Laravel CSRF
        },
    };
});



// ============================================
// ERROR LINK - Manejo automático de refresh
// ============================================

const errorLink = onError(({ error, operation, forward }) => {
    // Check if this is a GraphQL error (Apollo Client v4 API)
    if (CombinedGraphQLErrors.is(error)) {
        // Iterate through GraphQL errors
        for (const err of error.errors) {
            // Si el error es de autenticación, usar el nuevo servicio de refresco
            if (err.extensions?.code === 'UNAUTHENTICATED' || err.extensions?.code === 'INVALID_TOKEN') {
                // Evitar loop infinito si la propia mutación de refresco falla
                if (operation.operationName === 'RefreshToken') {
                    TokenManager.clearToken();
                    window.location.href = '/login';
                    return; // Return undefined is valid for onError
                }

                // Return an Observable that handles the refresh and retry logic
                return new Observable(observer => {
                    (async () => {
                        try {
                            const result = await TokenRefreshService.refresh();
                            if (result.success && result.accessToken) {
                                // Reintentar la operación original con el nuevo token
                                operation.setContext({
                                    headers: {
                                        ...operation.getContext().headers,
                                        authorization: `Bearer ${result.accessToken}`,
                                    },
                                });
                                forward(operation).subscribe(observer);
                            } else {
                                // Si el refresco falla, redirigir a login
                                TokenManager.clearToken();
                                window.location.href = '/login';
                                observer.error(new Error('Session expired'));
                            }
                        } catch (error) {
                            observer.error(error);
                        }
                    })();
                });
            }
        }
    }
    // Return undefined is valid - Apollo will propagate the original error
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
// EXPORTS
// ============================================
// Only export the Apollo Client instance
// Token management is now centralized in TokenManager
// No more legacy helper functions!

