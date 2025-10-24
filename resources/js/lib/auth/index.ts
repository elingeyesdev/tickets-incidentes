/**
 * index.ts (Barrel File)
 *
 * Este archivo exporta todos los componentes públicos del sistema de autenticación
 * desde un único punto de entrada.
 *
 * Esto permite importaciones limpias y centralizadas en el resto de la aplicación, como:
 * import { TokenManager, authLogger } from '@/lib/auth';
 */

// Exportar la instancia singleton del TokenManager
export { TokenManager } from './TokenManager';
export { PersistenceService } from './PersistenceService';
export { TokenRefreshService } from './TokenRefreshService';
export { HeartbeatService } from './HeartbeatService';
export { authMachine } from './AuthMachine';

// Exportar constantes y configuraciones
export { AuthChannel } from './AuthChannel';
export * from './constants';

// Exportar todas las definiciones de tipos
export * from './types';

// Exportar funciones de utilidad
export * from './utils';
