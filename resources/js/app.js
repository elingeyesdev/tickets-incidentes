/**
 * Alpine.js Application Entry Point
 *
 * This file initializes Alpine.js and registers all auth-related
 * services and stores for the Helpdesk system.
 */

import Alpine from 'alpinejs';
import TokenManager from './lib/auth/TokenManager.js';
import AuthChannel from './lib/auth/AuthChannel.js';
import PersistenceService from './lib/auth/PersistenceService.js';
import HeartbeatService from './lib/auth/HeartbeatService.js';
import authStore from './alpine/stores/authStore.js';

// Register Alpine globally
window.Alpine = Alpine;

/**
 * Create auth services bundle for global access
 */
window.auth = {
    tokenManager: new TokenManager(),
    authChannel: new AuthChannel(),
    persistenceService: new PersistenceService(),
    // HeartbeatService is instantiated per-session in authStore
};

/**
 * Register authStore as Alpine global store
 * Access via Alpine.store('auth') in components
 */
Alpine.store('auth', authStore);

/**
 * Register authStore as Alpine data function
 * Use via x-data="authStore()" in Blade templates
 */
Alpine.data('authStore', authStore);

/**
 * Start Alpine.js
 */
Alpine.start();

/**
 * Log initialization
 */
console.log('[APP] Initialized with Alpine.js v3.15.1');
console.log('[APP] Auth services registered:', Object.keys(window.auth));
console.log('[APP] authStore registered as Alpine data and store');
