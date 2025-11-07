/**
 * Auth Library - Unified Exports
 *
 * JWT authentication system for Blade frontend.
 * This module provides all authentication-related services and utilities.
 *
 * Features:
 * - Token management (JWT access tokens)
 * - Multi-tab synchronization (auth events)
 * - Session persistence (IndexedDB/localStorage)
 * - Session keepalive (heartbeat)
 *
 * @author Helpdesk System
 * @version 1.0.0
 */

// Import singleton instances
import tokenManager from './TokenManager.js';
import authChannel from './AuthChannel.js';
import persistenceService from './PersistenceService.js';
import heartbeatService from './HeartbeatService.js';

// Import classes for advanced usage
import { TokenManager, TokenExpiredException, TokenInvalidException } from './TokenManager.js';
import { AuthChannel } from './AuthChannel.js';
import { PersistenceService } from './PersistenceService.js';
import { HeartbeatService } from './HeartbeatService.js';

/**
 * Auth library singleton instances
 */
export {
  // Singleton instances (recommended usage)
  tokenManager,
  authChannel,
  persistenceService,
  heartbeatService,

  // Classes (for advanced usage or testing)
  TokenManager,
  AuthChannel,
  PersistenceService,
  HeartbeatService,

  // Exceptions
  TokenExpiredException,
  TokenInvalidException
};

/**
 * Default export: Object containing all singleton instances
 *
 * Usage:
 *   import auth from '@/lib/auth';
 *   auth.tokenManager.setTokens(token, expiresIn);
 *   auth.heartbeatService.start(auth.tokenManager);
 */
export default {
  tokenManager,
  authChannel,
  persistenceService,
  heartbeatService
};
