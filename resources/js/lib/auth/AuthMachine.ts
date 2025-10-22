/**
 * AuthMachine.ts
 *
 * Define la máquina de estados finitos (FSM) para la autenticación usando XState.
 * Esta máquina es el cerebro de la UI, orquestando los estados y transiciones
 * de una manera predecible y libre de race conditions.
 */

import { assign, setup, fromPromise } from 'xstate';
import { TokenRefreshService } from './TokenRefreshService';
import { TokenManager } from './TokenManager';
import type { AccessToken, AuthMachineContext, RefreshError } from './types';
import { authLogger } from './constants';

// Define los eventos que la máquina puede recibir
export type AuthMachineEvent =
    | { type: 'SESSION_DETECTED'; token: AccessToken; user: unknown; lastSelectedRole: string | null }
    | { type: 'SESSION_INVALID' }
    | { type: 'LOGIN'; token: AccessToken; user: unknown; lastSelectedRole: string | null }
    | { type: 'LOGOUT' }
    | { type: 'TOKEN_EXPIRED' }
    | { type: 'RETRY' }
    | { type: 'ROLE_SELECTED'; role: string };

// XState v5 setup API: Define actors with proper typing
const authMachineSetup = setup({
    types: {
        context: {} as AuthMachineContext,
        events: {} as AuthMachineEvent,
    },
    actors: {
        // Define the refresh token actor using fromPromise
        refreshToken: fromPromise(async () => {
            authLogger.info('Invoking refreshToken actor from state machine...');
            const result = await TokenRefreshService.refresh();
            if (!result.success) {
                throw result.error;
            }
            // After successful refresh, get the token from TokenManager
            const token = TokenManager.validateToken();
            return {
                accessToken: token.isValid ? TokenManager.getAccessTokenObject() : null,
            };
        }),
    },
    actions: {
        setAuthData: assign({
            accessToken: ({ event }) => (event.type === 'LOGIN' || event.type === 'SESSION_DETECTED') ? event.token : null,
            user: ({ event }) => (event.type === 'LOGIN' || event.type === 'SESSION_DETECTED') ? event.user : null,
            lastSelectedRole: ({ event }) => (event.type === 'LOGIN' || event.type === 'SESSION_DETECTED') ? event.lastSelectedRole : null,
            error: null,
            retryCount: 0,
        }),
        setSelectedRole: assign({
            lastSelectedRole: ({ event }) => (event.type === 'ROLE_SELECTED') ? event.role : null,
        }),
        clearAuthData: assign({
            accessToken: null,
            user: null,
            error: null,
            retryCount: 0,
            lastSelectedRole: null,
        }),
        clearTokenManager: () => {
            TokenManager.clearToken();
        },
    },
});

export const authMachine = authMachineSetup.createMachine({
    id: 'auth',
    initial: 'initializing',
    context: {
        accessToken: null,
        user: null,
        error: null,
        retryCount: 0,
        lastSelectedRole: null,
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
                ROLE_SELECTED: {
                    actions: 'setSelectedRole'
                }
            },
        },
        refreshing: {
            invoke: {
                id: 'refreshTokenService',
                src: 'refreshToken',
                onDone: {
                    target: 'authenticated',
                    // In XState v5, event.output is automatically typed based on the actor's return type
                    actions: assign(({ event }) => ({
                        accessToken: event.output.accessToken,
                        retryCount: 0,
                        error: null,
                    })),
                },
                onError: {
                    target: 'expired',
                    // In XState v5, event.error is automatically typed
                    actions: assign(({ event, context }) => ({
                        error: event.error as RefreshError,
                        retryCount: context.retryCount + 1,
                    })),
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
});