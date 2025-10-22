/**
 * useAuthMachine.ts
 *
 * Hook de React para interactuar con la máquina de estados de autenticación.
 * Proporciona una API limpia y reactiva para ser consumida por el AuthContext
 * y otros componentes de la UI.
 */

import { useMachine } from '@xstate/react';
import { useEffect } from 'react';
import { authMachine } from '@/lib/auth/AuthMachine';
import { AuthChannel, TokenManager, extractUserIdFromJWT } from '@/lib/auth';
import { authLogger } from '@/lib/auth/constants';

export const useAuthMachine = () => {
    const [state, send] = useMachine(authMachine);

    useEffect(() => {
        // 1. Lógica de inicialización al cargar la aplicación
        const initialTokenObject = TokenManager.getAccessTokenObject();

        if (initialTokenObject) {
            authLogger.info('AuthMachine: Valid session detected on init.');
            // Aquí necesitaríamos obtener los datos del usuario. Por ahora, simulamos.
            // En una implementación real, haríamos una query `getMe`.
            const user = { id: extractUserIdFromJWT(initialTokenObject.token) };
            send({ 
                type: 'SESSION_DETECTED', 
                token: initialTokenObject, 
                user, 
                lastSelectedRole: TokenManager.getLastSelectedRole()
            });
        } else {
            authLogger.info('AuthMachine: No valid session on init.');
            send({ type: 'SESSION_INVALID' });
        }

        // 2. Suscribirse a eventos de otras pestañas
        const unsubscribe = AuthChannel.subscribe((event) => {
            authLogger.info(`AuthMachine: Event received from AuthChannel: ${event.type}`);
            switch (event.type) {
                case 'LOGOUT':
                case 'SESSION_EXPIRED':
                    send({ type: 'LOGOUT' });
                    break;
                case 'LOGIN':
                    // Otra pestaña ha iniciado sesión, recargar la página para obtener el estado completo
                    window.location.reload();
                    break;
                case 'TOKEN_REFRESHED':
                    // El token se refrescó en otra pestaña, la máquina de estados
                    // podría necesitar actualizar su contexto si gestionara el token directamente.
                    // Por ahora, el TokenManager lo maneja globalmente.
                    break;
            }
        });

        // 3. Suscribirse a eventos del TokenManager
        const unsubscribeOnExpiry = TokenManager.onExpiry(() => {
            authLogger.info('AuthMachine: Received expiry event from TokenManager.');
            send({ type: 'TOKEN_EXPIRED' });
        });

        // Limpieza al desmontar el componente
        return () => {
            unsubscribe();
            unsubscribeOnExpiry();
        };
    }, [send]); // `send` es una función estable

    // Exponer una API simplificada para el resto de la aplicación
    return {
        state: state.value,
        context: state.context,
        isAuthenticated: state.matches('authenticated'),
        isLoading: state.matches('initializing'),
        isRefreshing: state.matches('refreshing'),
        error: state.context.error,

        // Acciones
        send, // Exponer send para acciones más complejas si es necesario
    };
};
