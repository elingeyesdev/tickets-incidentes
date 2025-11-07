/**
 * TokenManager.js
 *
 * Core JWT token management system for Blade frontend.
 * Handles access token storage, automatic refresh, retry logic, and observer pattern.
 *
 * Features:
 * - LocalStorage-based token persistence
 * - Automatic token refresh at 80% TTL
 * - Exponential backoff + jitter for retries
 * - Observer pattern (onRefresh, onExpiry callbacks)
 * - Fetch wrapper with auto-refresh on 401
 * - Request retry queue during refresh
 *
 * @author Helpdesk System
 * @version 1.0.0
 */

class TokenManager {
  /**
   * LocalStorage keys
   */
  static STORAGE_KEYS = {
    ACCESS_TOKEN: 'helpdesk_access_token',
    TOKEN_EXPIRY: 'helpdesk_token_expiry',
    TOKEN_ISSUED_AT: 'helpdesk_token_issued_at'
  };

  /**
   * Configuration constants
   */
  static CONFIG = {
    REFRESH_THRESHOLD: 0.8,    // Refresh at 80% of TTL
    MAX_RETRY_ATTEMPTS: 3,      // Maximum refresh retry attempts
    BASE_RETRY_DELAY: 1000,     // Base delay for exponential backoff (1s)
    MAX_RETRY_DELAY: 10000,     // Maximum delay between retries (10s)
    DEFAULT_TTL: 3600           // Default TTL in seconds (1 hour)
  };

  /**
   * Initialize TokenManager
   */
  constructor() {
    // Observers
    this.refreshCallbacks = [];
    this.expiryCallbacks = [];

    // Refresh state
    this.refreshTimer = null;
    this.isRefreshing = false;
    this.refreshPromise = null;

    // Retry queue for pending requests
    this.retryQueue = [];

    // Statistics
    this.stats = {
      totalRefreshes: 0,
      successfulRefreshes: 0,
      failedRefreshes: 0,
      lastRefreshAt: null,
      lastRefreshSuccess: null
    };

    // Auto-schedule refresh if token exists
    this._initializeRefreshTimer();

    this._log('TokenManager initialized');
  }

  /**
   * Set tokens and schedule auto-refresh
   *
   * @param {string} accessToken - JWT access token
   * @param {number} expiresIn - TTL in seconds (default: 3600)
   */
  setTokens(accessToken, expiresIn = TokenManager.CONFIG.DEFAULT_TTL) {
    const now = Date.now();
    const expiryTimestamp = now + (expiresIn * 1000);

    try {
      localStorage.setItem(TokenManager.STORAGE_KEYS.ACCESS_TOKEN, accessToken);
      localStorage.setItem(TokenManager.STORAGE_KEYS.TOKEN_EXPIRY, expiryTimestamp.toString());
      localStorage.setItem(TokenManager.STORAGE_KEYS.TOKEN_ISSUED_AT, now.toString());

      this._log('Tokens set', { expiresIn, expiryTimestamp });

      // Schedule auto-refresh
      this._scheduleRefresh(expiresIn);
    } catch (error) {
      this._logError('Failed to set tokens in localStorage', error);
      throw new Error('Failed to store tokens');
    }
  }

  /**
   * Get access token (validates TTL before returning)
   *
   * @returns {string|null} Access token or null if expired/missing
   */
  getAccessToken() {
    const token = localStorage.getItem(TokenManager.STORAGE_KEYS.ACCESS_TOKEN);
    const expiryTimestamp = localStorage.getItem(TokenManager.STORAGE_KEYS.TOKEN_EXPIRY);

    if (!token || !expiryTimestamp) {
      return null;
    }

    // Check if token is expired
    const now = Date.now();
    const expiry = parseInt(expiryTimestamp, 10);

    if (now >= expiry) {
      this._log('Token expired', { now, expiry });
      this._notifyExpiry();
      this.clearTokens();
      return null;
    }

    return token;
  }

  /**
   * Clear all tokens and timers
   */
  clearTokens() {
    try {
      localStorage.removeItem(TokenManager.STORAGE_KEYS.ACCESS_TOKEN);
      localStorage.removeItem(TokenManager.STORAGE_KEYS.TOKEN_EXPIRY);
      localStorage.removeItem(TokenManager.STORAGE_KEYS.TOKEN_ISSUED_AT);

      // Clear refresh timer
      if (this.refreshTimer) {
        clearTimeout(this.refreshTimer);
        this.refreshTimer = null;
      }

      this._log('Tokens cleared');
    } catch (error) {
      this._logError('Failed to clear tokens', error);
    }
  }

  /**
   * Refresh access token using refresh token cookie
   *
   * @param {number} attempt - Current retry attempt (default: 1)
   * @returns {Promise<{accessToken: string, expiresIn: number}>}
   */
  async refresh(attempt = 1) {
    // If already refreshing, return existing promise
    if (this.isRefreshing && this.refreshPromise) {
      this._log('Refresh already in progress, returning existing promise');
      return this.refreshPromise;
    }

    this.isRefreshing = true;
    this.stats.totalRefreshes++;
    this.stats.lastRefreshAt = new Date().toISOString();

    this._log('Starting token refresh', { attempt });

    this.refreshPromise = this._performRefresh(attempt);

    try {
      const result = await this.refreshPromise;

      // Update tokens
      this.setTokens(result.accessToken, result.expiresIn);

      // Update stats
      this.stats.successfulRefreshes++;
      this.stats.lastRefreshSuccess = true;

      // Notify observers
      this._notifyRefresh(result);

      this._log('Token refresh successful', result);

      return result;
    } catch (error) {
      this.stats.failedRefreshes++;
      this.stats.lastRefreshSuccess = false;

      this._logError('Token refresh failed', error);

      // Clear tokens on final failure
      if (attempt >= TokenManager.CONFIG.MAX_RETRY_ATTEMPTS) {
        this.clearTokens();
        this._notifyExpiry();
      }

      throw error;
    } finally {
      this.isRefreshing = false;
      this.refreshPromise = null;
    }
  }

  /**
   * Perform actual refresh request with retry logic
   *
   * @private
   * @param {number} attempt - Current retry attempt
   * @returns {Promise<{accessToken: string, expiresIn: number}>}
   */
  async _performRefresh(attempt) {
    try {
      const response = await fetch('/api/auth/refresh', {
        method: 'POST',
        credentials: 'include', // Send HttpOnly cookie
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        // Handle specific error codes
        if (response.status === 401) {
          throw new TokenExpiredException('Refresh token expired or invalid');
        }
        if (response.status === 422) {
          throw new TokenInvalidException('Refresh token validation failed');
        }

        throw new Error(`Refresh failed with status ${response.status}`);
      }

      const data = await response.json();

      // Parse response structure: { data: { accessToken, expiresIn } }
      if (!data.data || !data.data.accessToken) {
        throw new Error('Invalid refresh response structure');
      }

      return {
        accessToken: data.data.accessToken,
        expiresIn: data.data.expiresIn || TokenManager.CONFIG.DEFAULT_TTL
      };
    } catch (error) {
      // Retry logic with exponential backoff
      if (attempt < TokenManager.CONFIG.MAX_RETRY_ATTEMPTS && this._shouldRetry(error)) {
        const delay = this._calculateRetryDelay(attempt);
        this._log(`Retrying refresh after ${delay}ms`, { attempt, error: error.message });

        await this._sleep(delay);
        return this._performRefresh(attempt + 1);
      }

      throw error;
    }
  }

  /**
   * Determine if error should trigger retry
   *
   * @private
   * @param {Error} error - Error object
   * @returns {boolean}
   */
  _shouldRetry(error) {
    // Don't retry on auth errors (60000+ error codes)
    if (error instanceof TokenExpiredException || error instanceof TokenInvalidException) {
      return false;
    }

    // Retry on network errors
    if (error.message.includes('fetch') || error.message.includes('network')) {
      return true;
    }

    // Retry on 5xx server errors
    if (error.message.includes('status 5')) {
      return true;
    }

    return false;
  }

  /**
   * Calculate retry delay with exponential backoff + jitter
   *
   * @private
   * @param {number} attempt - Current attempt number
   * @returns {number} Delay in milliseconds
   */
  _calculateRetryDelay(attempt) {
    // Exponential backoff: 2^(attempt-1) * baseDelay
    const exponentialDelay = Math.pow(2, attempt - 1) * TokenManager.CONFIG.BASE_RETRY_DELAY;

    // Add jitter (random 0-50% of delay)
    const jitter = Math.random() * 0.5 * exponentialDelay;

    // Cap at max delay
    const totalDelay = Math.min(exponentialDelay + jitter, TokenManager.CONFIG.MAX_RETRY_DELAY);

    return Math.floor(totalDelay);
  }

  /**
   * Fetch wrapper with automatic token refresh on 401
   *
   * @param {string} url - Request URL
   * @param {Object} options - Fetch options
   * @returns {Promise<Response>}
   */
  async fetch(url, options = {}) {
    // Get current token
    const token = this.getAccessToken();

    if (!token) {
      this._log('No valid token for fetch, attempting refresh');

      try {
        await this.refresh();
      } catch (error) {
        this._logError('Token refresh failed before fetch', error);
        window.location.href = '/login?reason=session_expired';
        throw error;
      }
    }

    // Add authorization header
    const headers = {
      ...options.headers,
      'Authorization': `Bearer ${this.getAccessToken()}`,
      'Accept': 'application/json'
    };

    try {
      const response = await fetch(url, { ...options, headers });

      // Handle 401 - attempt refresh and retry
      if (response.status === 401) {
        this._log('Received 401, attempting token refresh and retry');

        // Try to refresh token
        try {
          await this.refresh();

          // Retry original request with new token
          const retryHeaders = {
            ...options.headers,
            'Authorization': `Bearer ${this.getAccessToken()}`,
            'Accept': 'application/json'
          };

          return fetch(url, { ...options, headers: retryHeaders });
        } catch (refreshError) {
          this._logError('Token refresh failed on 401', refreshError);

          // Redirect to login
          window.location.href = '/login?reason=session_expired';
          throw refreshError;
        }
      }

      return response;
    } catch (error) {
      this._logError('Fetch error', error);
      throw error;
    }
  }

  /**
   * Schedule automatic token refresh at 80% TTL
   *
   * @private
   * @param {number} expiresIn - TTL in seconds
   */
  _scheduleRefresh(expiresIn) {
    // Clear existing timer
    if (this.refreshTimer) {
      clearTimeout(this.refreshTimer);
    }

    // Calculate refresh time (80% of TTL)
    const refreshTime = expiresIn * 1000 * TokenManager.CONFIG.REFRESH_THRESHOLD;

    this._log('Scheduling auto-refresh', {
      expiresIn,
      refreshTime: Math.floor(refreshTime / 1000) + 's'
    });

    this.refreshTimer = setTimeout(async () => {
      this._log('Auto-refresh triggered');

      try {
        await this.refresh();
      } catch (error) {
        this._logError('Auto-refresh failed', error);
        this._notifyExpiry();
      }
    }, refreshTime);
  }

  /**
   * Initialize refresh timer on startup (if token exists)
   *
   * @private
   */
  _initializeRefreshTimer() {
    const expiryTimestamp = localStorage.getItem(TokenManager.STORAGE_KEYS.TOKEN_EXPIRY);
    const issuedAtTimestamp = localStorage.getItem(TokenManager.STORAGE_KEYS.TOKEN_ISSUED_AT);

    if (!expiryTimestamp || !issuedAtTimestamp) {
      return;
    }

    const now = Date.now();
    const expiry = parseInt(expiryTimestamp, 10);
    const issuedAt = parseInt(issuedAtTimestamp, 10);

    // Check if token is still valid
    if (now >= expiry) {
      this._log('Token expired on initialization');
      this.clearTokens();
      return;
    }

    // Calculate remaining TTL
    const remainingTTL = Math.floor((expiry - now) / 1000);
    const originalTTL = Math.floor((expiry - issuedAt) / 1000);

    this._log('Initializing refresh timer with existing token', {
      remainingTTL: remainingTTL + 's',
      originalTTL: originalTTL + 's'
    });

    // Schedule refresh based on remaining time
    this._scheduleRefresh(remainingTTL);
  }

  /**
   * Register callback for token refresh events
   *
   * @param {Function} callback - Callback function
   * @returns {Function} Cleanup function
   */
  onRefresh(callback) {
    this.refreshCallbacks.push(callback);
    return () => {
      this.refreshCallbacks = this.refreshCallbacks.filter(cb => cb !== callback);
    };
  }

  /**
   * Register callback for token expiry events
   *
   * @param {Function} callback - Callback function
   * @returns {Function} Cleanup function
   */
  onExpiry(callback) {
    this.expiryCallbacks.push(callback);
    return () => {
      this.expiryCallbacks = this.expiryCallbacks.filter(cb => cb !== callback);
    };
  }

  /**
   * Notify refresh observers
   *
   * @private
   * @param {Object} data - Refresh data
   */
  _notifyRefresh(data) {
    this._log('Notifying refresh observers', { count: this.refreshCallbacks.length });
    this.refreshCallbacks.forEach(callback => {
      try {
        callback(data);
      } catch (error) {
        this._logError('Refresh callback error', error);
      }
    });
  }

  /**
   * Notify expiry observers
   *
   * @private
   */
  _notifyExpiry() {
    this._log('Notifying expiry observers', { count: this.expiryCallbacks.length });
    this.expiryCallbacks.forEach(callback => {
      try {
        callback();
      } catch (error) {
        this._logError('Expiry callback error', error);
      }
    });
  }

  /**
   * Get statistics
   *
   * @returns {Object} Statistics object
   */
  getStats() {
    const successRate = this.stats.totalRefreshes > 0
      ? Math.round((this.stats.successfulRefreshes / this.stats.totalRefreshes) * 100)
      : 0;

    return {
      refreshes: this.stats.totalRefreshes,
      successfulRefreshes: this.stats.successfulRefreshes,
      failures: this.stats.failedRefreshes,
      successRate: successRate + '%',
      lastRefresh: this.stats.lastRefreshAt,
      lastRefreshSuccess: this.stats.lastRefreshSuccess,
      isRefreshing: this.isRefreshing
    };
  }

  /**
   * Sleep helper for retry delays
   *
   * @private
   * @param {number} ms - Milliseconds to sleep
   * @returns {Promise<void>}
   */
  _sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Log helper
   *
   * @private
   * @param {string} message - Log message
   * @param {Object} data - Additional data
   */
  _log(message, data = {}) {
    console.log(`[TokenManager] ${message}`, data);
  }

  /**
   * Error log helper
   *
   * @private
   * @param {string} message - Error message
   * @param {Error} error - Error object
   */
  _logError(message, error) {
    console.error(`[TokenManager] ${message}`, error);
  }
}

/**
 * Custom exception for token expiration errors
 */
class TokenExpiredException extends Error {
  constructor(message) {
    super(message);
    this.name = 'TokenExpiredException';
    this.code = 60001;
  }
}

/**
 * Custom exception for token invalid errors
 */
class TokenInvalidException extends Error {
  constructor(message) {
    super(message);
    this.name = 'TokenInvalidException';
    this.code = 60002;
  }
}

// Export singleton instance
const tokenManager = new TokenManager();

export { tokenManager, TokenManager, TokenExpiredException, TokenInvalidException };
export default tokenManager;
