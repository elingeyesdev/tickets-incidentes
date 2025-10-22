/**
 * AuthMachine.ts
 *
 * Define la máquina de estados finitos (FSM) para la autenticación usando XState.
 * Esta máquina es el cerebro de la UI, orquestando los estados y transiciones
 * de una manera predecible y libre de race conditions.
 */

import { assign, createMachine, setup } from 'xstate';
import { TokenRefreshService } from './TokenRefreshService';
import { TokenManager } from './TokenManager';
import type { AccessToken, AuthMachineContext, RefreshError } from './types';
import { authLogger } from './constants';

// Define los eventos que la máquina puede recibir
export type AuthMachineEvent =
    | { type: 'SESSION_DETECTED'; token: AccessToken; user: unknown }
    | { type: 'SESSION_INVALID' }
    | { type: 'LOGIN'; token: AccessToken; user: unknown }
    | { type: 'LOGOUT' }
    | { type: 'TOKEN_EXPIRED' }
    | { type: 'RETRY' };

export const authMachine = createMachine({
    id: 'auth',
    types: {} as {
        context: AuthMachineContext;
        events: AuthMachineEvent;
        output: { accessToken: AccessToken | null };
    },
    initial: 'initializing',
    context: {
        accessToken: null,
        user: null,
        error: null,
        retryCount: 0,
    },
    states: {
        initializing: {
            on: {
                SESSION_DETECTED: {
                    target: 'authenticated',
                    actions: 'setAuthData',
                },
                SESSION_INVALID: 'unauthenticated',
            },
        },
        unauthenticated: {
            entry: 'clearAuthData',
            on: {
                LOGIN: {
                    target: 'authenticated',
                    actions: 'setAuthData',
                },
            },
        },
        authenticated: {
            on: {
                TOKEN_EXPIRED: 'refreshing',
                LOGOUT: 'unauthenticated',
            },
        },
        refreshing: {
            invoke: {
                id: 'refreshTokenService',
                src: 'refreshToken',
                onDone: {
                    target: 'authenticated',
                    actions: assign({
                        accessToken: ({ event }) => event.output.accessToken,
                        retryCount: 0,
                        error: null,
                    }),
                },
                onError: {
                    target: 'expired',
                    actions: assign({
                        error: ({ event }) => event.data as RefreshError,
                        retryCount: ({ context }) => context.retryCount + 1,
                    }),
                },
            },
        },
        expired: {
            entry: ['clearAuthData', 'clearTokenManager'],
            on: {
                LOGIN: {
                    target: 'authenticated',
                    actions: 'setAuthData',
                },
            },
        },
    },
}, {
    actions: {
        setAuthData: assign({
            accessToken: ({ event }) => (event.type === 'LOGIN' || event.type === 'SESSION_DETECTED') ? event.token : null,
            user: ({ event }) => (event.type === 'LOGIN' || event.type === 'SESSION_DETECTED') ? event.user : null,
            error: null,
            retryCount: 0,
        }),
        clearAuthData: assign({
            accessToken: null,
            user: null,
            error: null,
            retryCount: 0,
        }),
        clearTokenManager: () => {
            TokenManager.clearToken();
        },
    },
    actors: {
        refreshToken: setup(() => {
            return async () => {
                authLogger.info('Invoking refreshToken actor from state machine...');
                const result = await TokenRefreshService.refresh();
                if (!result.success) {
                    throw result.error;
                }
                const token = TokenManager.validateToken();
                return {
                    accessToken: token.isValid ? token.accessToken : null,
                };
            };
        }),
    },
});