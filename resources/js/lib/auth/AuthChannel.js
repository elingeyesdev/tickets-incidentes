/**
 * AuthChannel.js
 *
 * Multi-tab synchronization system for authentication events.
 * Uses BroadcastChannel API with localStorage fallback for older browsers.
 *
 * Features:
 * - Cross-tab communication for auth events
 * - BroadcastChannel API (modern browsers)
 * - LocalStorage fallback (older browsers)
 * - Event broadcasting (LOGIN, LOGOUT, TOKEN_REFRESHED, SESSION_EXPIRED)
 * - Listener subscription/unsubscription
 *
 * @author Helpdesk System
 * @version 1.0.0
 */

class AuthChannel {
  /**
   * Event types
   */
  static EVENTS = {
    LOGIN: 'LOGIN',
    LOGOUT: 'LOGOUT',
    TOKEN_REFRESHED: 'TOKEN_REFRESHED',
    SESSION_EXPIRED: 'SESSION_EXPIRED'
  };

  /**
   * Configuration
   */
  static CONFIG = {
    CHANNEL_NAME: 'helpdesk_auth_channel',
    STORAGE_KEY: 'helpdesk_auth_event',
    EVENT_TTL: 5000 // Events expire after 5 seconds
  };

  /**
   * Initialize AuthChannel
   */
  constructor() {
    this.listeners = [];
    this.channel = null;
    this.storageListener = null;
    this.backend = this._detectBackend();

    this._initialize();
    this._log('AuthChannel initialized', { backend: this.backend });
  }

  /**
   * Detect which backend to use (BroadcastChannel or localStorage)
   *
   * @private
   * @returns {string} 'broadcast' or 'storage'
   */
  _detectBackend() {
    if (typeof BroadcastChannel !== 'undefined') {
      try {
        // Test BroadcastChannel creation
        const test = new BroadcastChannel('test');
        test.close();
        return 'broadcast';
      } catch (error) {
        this._log('BroadcastChannel not available, using localStorage fallback');
        return 'storage';
      }
    }

    return 'storage';
  }

  /**
   * Initialize the appropriate backend
   *
   * @private
   */
  _initialize() {
    if (this.backend === 'broadcast') {
      this._initializeBroadcastChannel();
    } else {
      this._initializeStorageFallback();
    }
  }

  /**
   * Initialize BroadcastChannel backend
   *
   * @private
   */
  _initializeBroadcastChannel() {
    try {
      this.channel = new BroadcastChannel(AuthChannel.CONFIG.CHANNEL_NAME);

      this.channel.onmessage = (event) => {
        this._handleIncomingEvent(event.data);
      };

      this.channel.onerror = (error) => {
        this._logError('BroadcastChannel error', error);
      };

      this._log('BroadcastChannel initialized');
    } catch (error) {
      this._logError('Failed to initialize BroadcastChannel', error);
      // Fallback to storage
      this.backend = 'storage';
      this._initializeStorageFallback();
    }
  }

  /**
   * Initialize localStorage fallback backend
   *
   * @private
   */
  _initializeStorageFallback() {
    this.storageListener = (event) => {
      // Only process events for our storage key
      if (event.key !== AuthChannel.CONFIG.STORAGE_KEY) {
        return;
      }

      // Ignore if newValue is null (key was removed)
      if (!event.newValue) {
        return;
      }

      try {
        const data = JSON.parse(event.newValue);

        // Check if event is still valid (not expired)
        if (data.timestamp && Date.now() - data.timestamp < AuthChannel.CONFIG.EVENT_TTL) {
          this._handleIncomingEvent(data);
        }
      } catch (error) {
        this._logError('Failed to parse storage event', error);
      }
    };

    window.addEventListener('storage', this.storageListener);
    this._log('Storage fallback initialized');
  }

  /**
   * Broadcast an event to other tabs
   *
   * @param {Object} event - Event object
   * @param {string} event.type - Event type (LOGIN, LOGOUT, etc.)
   * @param {Object} event.payload - Event payload
   */
  broadcast(event) {
    if (!event || !event.type) {
      this._logError('Invalid event', event);
      return;
    }

    const enrichedEvent = {
      ...event,
      timestamp: Date.now(),
      tabId: this._getTabId()
    };

    this._log('Broadcasting event', enrichedEvent);

    if (this.backend === 'broadcast') {
      this._broadcastViaBroadcastChannel(enrichedEvent);
    } else {
      this._broadcastViaStorage(enrichedEvent);
    }
  }

  /**
   * Broadcast via BroadcastChannel
   *
   * @private
   * @param {Object} event - Event object
   */
  _broadcastViaBroadcastChannel(event) {
    try {
      this.channel.postMessage(event);
    } catch (error) {
      this._logError('Failed to broadcast via BroadcastChannel', error);
    }
  }

  /**
   * Broadcast via localStorage
   *
   * @private
   * @param {Object} event - Event object
   */
  _broadcastViaStorage(event) {
    try {
      // Write to storage (triggers 'storage' event in other tabs)
      localStorage.setItem(AuthChannel.CONFIG.STORAGE_KEY, JSON.stringify(event));

      // Clean up after a short delay (allows other tabs to read)
      setTimeout(() => {
        try {
          localStorage.removeItem(AuthChannel.CONFIG.STORAGE_KEY);
        } catch (error) {
          // Ignore cleanup errors
        }
      }, 100);
    } catch (error) {
      this._logError('Failed to broadcast via localStorage', error);
    }
  }

  /**
   * Handle incoming event from other tabs
   *
   * @private
   * @param {Object} event - Event object
   */
  _handleIncomingEvent(event) {
    // Ignore events from current tab
    if (event.tabId === this._getTabId()) {
      return;
    }

    this._log('Received event from other tab', event);

    // Notify all listeners
    this._notifyListeners(event);
  }

  /**
   * Subscribe to auth events
   *
   * @param {Function} listener - Listener function
   * @returns {Function} Cleanup function
   */
  subscribe(listener) {
    if (typeof listener !== 'function') {
      this._logError('Listener must be a function');
      return () => {};
    }

    this.listeners.push(listener);
    this._log('Listener subscribed', { total: this.listeners.length });

    // Return cleanup function
    return () => {
      this.listeners = this.listeners.filter(l => l !== listener);
      this._log('Listener unsubscribed', { total: this.listeners.length });
    };
  }

  /**
   * Notify all listeners of an event
   *
   * @private
   * @param {Object} event - Event object
   */
  _notifyListeners(event) {
    this.listeners.forEach(listener => {
      try {
        listener(event);
      } catch (error) {
        this._logError('Listener error', error);
      }
    });
  }

  /**
   * Get or create unique tab ID
   *
   * @private
   * @returns {string} Tab ID
   */
  _getTabId() {
    if (!this.tabId) {
      // Generate unique tab ID
      this.tabId = `tab_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
    return this.tabId;
  }

  /**
   * Destroy the channel and cleanup
   */
  destroy() {
    this._log('Destroying AuthChannel');

    if (this.backend === 'broadcast' && this.channel) {
      this.channel.close();
      this.channel = null;
    }

    if (this.backend === 'storage' && this.storageListener) {
      window.removeEventListener('storage', this.storageListener);
      this.storageListener = null;
    }

    this.listeners = [];
  }

  /**
   * Get debug information
   *
   * @returns {Object} Debug info
   */
  getDebugInfo() {
    return {
      backend: this.backend,
      tabId: this._getTabId(),
      listenersCount: this.listeners.length,
      channelActive: this.backend === 'broadcast' ? this.channel !== null : this.storageListener !== null
    };
  }

  /**
   * Helper: Broadcast LOGIN event
   *
   * @param {string} userId - User ID
   */
  broadcastLogin(userId) {
    this.broadcast({
      type: AuthChannel.EVENTS.LOGIN,
      payload: { userId, timestamp: Date.now() }
    });
  }

  /**
   * Helper: Broadcast LOGOUT event
   */
  broadcastLogout() {
    this.broadcast({
      type: AuthChannel.EVENTS.LOGOUT,
      payload: { timestamp: Date.now() }
    });
  }

  /**
   * Helper: Broadcast TOKEN_REFRESHED event
   */
  broadcastTokenRefreshed() {
    this.broadcast({
      type: AuthChannel.EVENTS.TOKEN_REFRESHED,
      payload: { timestamp: Date.now() }
    });
  }

  /**
   * Helper: Broadcast SESSION_EXPIRED event
   */
  broadcastSessionExpired() {
    this.broadcast({
      type: AuthChannel.EVENTS.SESSION_EXPIRED,
      payload: { timestamp: Date.now() }
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
    console.log(`[AuthChannel] ${message}`, data);
  }

  /**
   * Error log helper
   *
   * @private
   * @param {string} message - Error message
   * @param {Error} error - Error object
   */
  _logError(message, error) {
    console.error(`[AuthChannel] ${message}`, error);
  }
}

// Export singleton instance
const authChannel = new AuthChannel();

export { authChannel, AuthChannel };
export default authChannel;
