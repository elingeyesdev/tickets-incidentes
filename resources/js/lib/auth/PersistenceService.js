/**
 * PersistenceService.js
 *
 * IndexedDB persistence for authentication session restoration.
 * Provides secure storage for JWT tokens and user data with automatic cleanup.
 *
 * Features:
 * - IndexedDB storage for auth state
 * - LocalStorage fallback for unsupported browsers
 * - TTL validation (don't restore expired tokens)
 * - Secure token handling
 * - Automatic migration between storage backends
 *
 * @author Helpdesk System
 * @version 1.0.0
 */

class PersistenceService {
  /**
   * Configuration
   */
  static CONFIG = {
    DB_NAME: 'helpdesk_auth',
    DB_VERSION: 1,
    STORE_NAME: 'sessions',
    STORAGE_KEY: 'helpdesk_auth_state', // Fallback localStorage key
    MAX_RETRY_ATTEMPTS: 3
  };

  /**
   * Initialize PersistenceService
   */
  constructor() {
    this.db = null;
    this.backend = null; // 'indexeddb' or 'localstorage'
    this.isInitialized = false;
    this.initPromise = null;

    this._log('PersistenceService created');
  }

  /**
   * Initialize storage backend
   *
   * @private
   * @returns {Promise<void>}
   */
  async _initialize() {
    if (this.isInitialized) {
      return;
    }

    if (this.initPromise) {
      return this.initPromise;
    }

    this.initPromise = this._initializeBackend();

    try {
      await this.initPromise;
      this.isInitialized = true;
    } catch (error) {
      this._logError('Initialization failed', error);
      throw error;
    } finally {
      this.initPromise = null;
    }
  }

  /**
   * Initialize storage backend (try IndexedDB, fallback to localStorage)
   *
   * @private
   * @returns {Promise<void>}
   */
  async _initializeBackend() {
    // Try IndexedDB first
    if (this._isIndexedDBAvailable()) {
      try {
        await this._initializeIndexedDB();
        this.backend = 'indexeddb';
        this._log('IndexedDB initialized');
        return;
      } catch (error) {
        this._logError('IndexedDB initialization failed, falling back to localStorage', error);
      }
    }

    // Fallback to localStorage
    this.backend = 'localstorage';
    this._log('Using localStorage fallback');
  }

  /**
   * Check if IndexedDB is available
   *
   * @private
   * @returns {boolean}
   */
  _isIndexedDBAvailable() {
    return typeof indexedDB !== 'undefined';
  }

  /**
   * Initialize IndexedDB
   *
   * @private
   * @returns {Promise<void>}
   */
  _initializeIndexedDB() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(
        PersistenceService.CONFIG.DB_NAME,
        PersistenceService.CONFIG.DB_VERSION
      );

      request.onerror = () => {
        reject(new Error('IndexedDB open failed'));
      };

      request.onsuccess = (event) => {
        this.db = event.target.result;
        resolve();
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // Create object store if it doesn't exist
        if (!db.objectStoreNames.contains(PersistenceService.CONFIG.STORE_NAME)) {
          const objectStore = db.createObjectStore(PersistenceService.CONFIG.STORE_NAME, {
            keyPath: 'id'
          });

          // Create indexes
          objectStore.createIndex('expiresAt', 'expiresAt', { unique: false });
          objectStore.createIndex('createdAt', 'createdAt', { unique: false });

          this._log('IndexedDB object store created');
        }
      };
    });
  }

  /**
   * Save authentication state
   *
   * @param {string} accessToken - JWT access token
   * @param {number} expiresAt - Expiry timestamp
   * @param {Object} user - User object (optional)
   * @param {string} sessionId - Session ID (optional)
   * @returns {Promise<void>}
   */
  async saveAuthState(accessToken, expiresAt, user = null, sessionId = null) {
    await this._initialize();

    const authState = {
      id: 'current', // Single session storage
      accessToken,
      expiresAt,
      user,
      sessionId,
      createdAt: Date.now(),
      updatedAt: Date.now()
    };

    this._log('Saving auth state', { expiresAt, hasUser: !!user });

    if (this.backend === 'indexeddb') {
      await this._saveToIndexedDB(authState);
    } else {
      this._saveToLocalStorage(authState);
    }
  }

  /**
   * Save to IndexedDB
   *
   * @private
   * @param {Object} authState - Auth state object
   * @returns {Promise<void>}
   */
  _saveToIndexedDB(authState) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([PersistenceService.CONFIG.STORE_NAME], 'readwrite');
      const objectStore = transaction.objectStore(PersistenceService.CONFIG.STORE_NAME);

      const request = objectStore.put(authState);

      request.onsuccess = () => {
        this._log('Auth state saved to IndexedDB');
        resolve();
      };

      request.onerror = () => {
        this._logError('Failed to save to IndexedDB', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Save to localStorage
   *
   * @private
   * @param {Object} authState - Auth state object
   */
  _saveToLocalStorage(authState) {
    try {
      localStorage.setItem(
        PersistenceService.CONFIG.STORAGE_KEY,
        JSON.stringify(authState)
      );
      this._log('Auth state saved to localStorage');
    } catch (error) {
      this._logError('Failed to save to localStorage', error);
      throw error;
    }
  }

  /**
   * Load authentication state
   *
   * @returns {Promise<Object|null>} Auth state or null
   */
  async loadAuthState() {
    await this._initialize();

    this._log('Loading auth state');

    let authState = null;

    if (this.backend === 'indexeddb') {
      authState = await this._loadFromIndexedDB();
    } else {
      authState = this._loadFromLocalStorage();
    }

    if (!authState) {
      this._log('No auth state found');
      return null;
    }

    // Validate TTL
    if (!this._isStateValid(authState)) {
      this._log('Auth state expired, clearing');
      await this.clearAuthState();
      return null;
    }

    this._log('Auth state loaded', { hasUser: !!authState.user });

    return {
      accessToken: authState.accessToken,
      expiresAt: authState.expiresAt,
      user: authState.user,
      sessionId: authState.sessionId
    };
  }

  /**
   * Load from IndexedDB
   *
   * @private
   * @returns {Promise<Object|null>}
   */
  _loadFromIndexedDB() {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([PersistenceService.CONFIG.STORE_NAME], 'readonly');
      const objectStore = transaction.objectStore(PersistenceService.CONFIG.STORE_NAME);

      const request = objectStore.get('current');

      request.onsuccess = () => {
        resolve(request.result || null);
      };

      request.onerror = () => {
        this._logError('Failed to load from IndexedDB', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Load from localStorage
   *
   * @private
   * @returns {Object|null}
   */
  _loadFromLocalStorage() {
    try {
      const data = localStorage.getItem(PersistenceService.CONFIG.STORAGE_KEY);

      if (!data) {
        return null;
      }

      return JSON.parse(data);
    } catch (error) {
      this._logError('Failed to load from localStorage', error);
      return null;
    }
  }

  /**
   * Validate auth state (check if not expired)
   *
   * @private
   * @param {Object} authState - Auth state object
   * @returns {boolean}
   */
  _isStateValid(authState) {
    if (!authState || !authState.expiresAt) {
      return false;
    }

    const now = Date.now();
    return now < authState.expiresAt;
  }

  /**
   * Clear authentication state
   *
   * @returns {Promise<void>}
   */
  async clearAuthState() {
    await this._initialize();

    this._log('Clearing auth state');

    if (this.backend === 'indexeddb') {
      await this._clearFromIndexedDB();
    } else {
      this._clearFromLocalStorage();
    }
  }

  /**
   * Clear from IndexedDB
   *
   * @private
   * @returns {Promise<void>}
   */
  _clearFromIndexedDB() {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([PersistenceService.CONFIG.STORE_NAME], 'readwrite');
      const objectStore = transaction.objectStore(PersistenceService.CONFIG.STORE_NAME);

      const request = objectStore.delete('current');

      request.onsuccess = () => {
        this._log('Auth state cleared from IndexedDB');
        resolve();
      };

      request.onerror = () => {
        this._logError('Failed to clear from IndexedDB', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Clear from localStorage
   *
   * @private
   */
  _clearFromLocalStorage() {
    try {
      localStorage.removeItem(PersistenceService.CONFIG.STORAGE_KEY);
      this._log('Auth state cleared from localStorage');
    } catch (error) {
      this._logError('Failed to clear from localStorage', error);
      throw error;
    }
  }

  /**
   * Get debug information
   *
   * @returns {Object} Debug info
   */
  getDebugInfo() {
    return {
      backend: this.backend,
      available: this._isIndexedDBAvailable(),
      initialized: this.isInitialized,
      dbName: PersistenceService.CONFIG.DB_NAME,
      storeName: PersistenceService.CONFIG.STORE_NAME
    };
  }

  /**
   * Cleanup expired sessions (maintenance task)
   *
   * @returns {Promise<number>} Number of cleaned sessions
   */
  async cleanupExpiredSessions() {
    await this._initialize();

    if (this.backend !== 'indexeddb') {
      // localStorage only stores one session, already handled by loadAuthState
      return 0;
    }

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([PersistenceService.CONFIG.STORE_NAME], 'readwrite');
      const objectStore = transaction.objectStore(PersistenceService.CONFIG.STORE_NAME);
      const index = objectStore.index('expiresAt');

      const now = Date.now();
      const range = IDBKeyRange.upperBound(now);

      const request = index.openCursor(range);
      let cleaned = 0;

      request.onsuccess = (event) => {
        const cursor = event.target.result;

        if (cursor) {
          cursor.delete();
          cleaned++;
          cursor.continue();
        } else {
          this._log(`Cleaned ${cleaned} expired sessions`);
          resolve(cleaned);
        }
      };

      request.onerror = () => {
        this._logError('Failed to cleanup expired sessions', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Log helper
   *
   * @private
   * @param {string} message - Log message
   * @param {Object} data - Additional data
   */
  _log(message, data = {}) {
    console.log(`[PersistenceService] ${message}`, data);
  }

  /**
   * Error log helper
   *
   * @private
   * @param {string} message - Error message
   * @param {Error} error - Error object
   */
  _logError(message, error) {
    console.error(`[PersistenceService] ${message}`, error);
  }
}

// Export singleton instance
const persistenceService = new PersistenceService();

export { persistenceService, PersistenceService };
export default persistenceService;
