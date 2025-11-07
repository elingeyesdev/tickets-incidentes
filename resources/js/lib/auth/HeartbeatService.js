/**
 * HeartbeatService.js
 *
 * Session keepalive and inactivity detection service.
 * Periodically pings the server to maintain session and detect failures.
 *
 * Features:
 * - Periodic status checks (5-minute interval)
 * - Failure tracking with max retry limit
 * - Automatic logout on max failures
 * - Statistics tracking
 * - Configurable ping interval
 *
 * @author Helpdesk System
 * @version 1.0.0
 */

class HeartbeatService {
  /**
   * Configuration
   */
  static CONFIG = {
    PING_INTERVAL: 300000,      // 5 minutes (300000ms)
    MAX_FAILURES: 3,             // Max consecutive failures before logout
    ENDPOINT: '/api/auth/status', // Status check endpoint
    TIMEOUT: 10000               // Request timeout (10s)
  };

  /**
   * Initialize HeartbeatService
   */
  constructor() {
    this.timer = null;
    this.isRunning = false;
    this.consecutiveFailures = 0;

    // Statistics
    this.stats = {
      totalPings: 0,
      successfulPings: 0,
      failedPings: 0,
      lastPingAt: null,
      lastPingSuccess: null
    };

    this._log('HeartbeatService initialized');
  }

  /**
   * Start heartbeat service
   *
   * @param {Object} tokenManager - TokenManager instance for authenticated requests
   */
  start(tokenManager = null) {
    if (this.isRunning) {
      this._log('Heartbeat already running');
      return;
    }

    this.tokenManager = tokenManager;
    this.isRunning = true;
    this.consecutiveFailures = 0;

    this._log('Starting heartbeat', {
      interval: HeartbeatService.CONFIG.PING_INTERVAL / 1000 + 's',
      maxFailures: HeartbeatService.CONFIG.MAX_FAILURES
    });

    // Start immediate ping
    this._schedulePing(0);
  }

  /**
   * Stop heartbeat service
   */
  stop() {
    if (!this.isRunning) {
      return;
    }

    this._log('Stopping heartbeat');

    if (this.timer) {
      clearTimeout(this.timer);
      this.timer = null;
    }

    this.isRunning = false;
    this.consecutiveFailures = 0;
  }

  /**
   * Schedule next ping
   *
   * @private
   * @param {number} delay - Delay in milliseconds (default: PING_INTERVAL)
   */
  _schedulePing(delay = HeartbeatService.CONFIG.PING_INTERVAL) {
    if (this.timer) {
      clearTimeout(this.timer);
    }

    this.timer = setTimeout(async () => {
      await this._executePing();

      // Schedule next ping if still running
      if (this.isRunning) {
        this._schedulePing();
      }
    }, delay);
  }

  /**
   * Execute ping request
   *
   * @private
   */
  async _executePing() {
    this.stats.totalPings++;
    this.stats.lastPingAt = new Date().toISOString();

    this._log('Executing ping', { attempt: this.stats.totalPings });

    try {
      const success = await this.ping();

      if (success) {
        this._handlePingSuccess();
      } else {
        this._handlePingFailure();
      }
    } catch (error) {
      this._logError('Ping execution error', error);
      this._handlePingFailure();
    }
  }

  /**
   * Perform status check ping
   *
   * @returns {Promise<boolean>} Success status
   */
  async ping() {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), HeartbeatService.CONFIG.TIMEOUT);

      // Use tokenManager's fetch if available (handles auth headers)
      const fetchFn = this.tokenManager?.fetch?.bind(this.tokenManager) || fetch;

      const response = await fetchFn(HeartbeatService.CONFIG.ENDPOINT, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        },
        signal: controller.signal
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        this._log('Ping failed', { status: response.status });
        return false;
      }

      const data = await response.json();

      // Validate response structure: { data: { isAuthenticated, user } }
      if (!data.data || typeof data.data.isAuthenticated !== 'boolean') {
        this._log('Invalid ping response structure', data);
        return false;
      }

      if (!data.data.isAuthenticated) {
        this._log('Ping returned unauthenticated status');
        return false;
      }

      this._log('Ping successful', { user: data.data.user?.email || 'unknown' });
      return true;
    } catch (error) {
      if (error.name === 'AbortError') {
        this._log('Ping timeout');
      } else {
        this._logError('Ping error', error);
      }
      return false;
    }
  }

  /**
   * Handle successful ping
   *
   * @private
   */
  _handlePingSuccess() {
    this.stats.successfulPings++;
    this.stats.lastPingSuccess = true;
    this.consecutiveFailures = 0;

    this._log('Ping success', {
      successRate: this._calculateSuccessRate() + '%'
    });
  }

  /**
   * Handle failed ping
   *
   * @private
   */
  _handlePingFailure() {
    this.stats.failedPings++;
    this.stats.lastPingSuccess = false;
    this.consecutiveFailures++;

    this._log('Ping failure', {
      consecutiveFailures: this.consecutiveFailures,
      maxFailures: HeartbeatService.CONFIG.MAX_FAILURES
    });

    // Check if max failures reached
    if (this.consecutiveFailures >= HeartbeatService.CONFIG.MAX_FAILURES) {
      this._handleMaxFailures();
    }
  }

  /**
   * Handle max consecutive failures
   *
   * @private
   */
  _handleMaxFailures() {
    this._log('Max failures reached, logging out');

    // Stop heartbeat
    this.stop();

    // Clear tokens if tokenManager is available
    if (this.tokenManager && typeof this.tokenManager.clearTokens === 'function') {
      this.tokenManager.clearTokens();
    }

    // Redirect to login with reason
    window.location.href = '/login?reason=inactive';
  }

  /**
   * Get statistics
   *
   * @returns {Object} Statistics object
   */
  getStats() {
    return {
      totalPings: this.stats.totalPings,
      successfulPings: this.stats.successfulPings,
      failedPings: this.stats.failedPings,
      successRate: this._calculateSuccessRate() + '%',
      consecutiveFailures: this.consecutiveFailures,
      isRunning: this.isRunning,
      lastPingAt: this.stats.lastPingAt,
      lastPingSuccess: this.stats.lastPingSuccess
    };
  }

  /**
   * Calculate success rate percentage
   *
   * @private
   * @returns {number} Success rate (0-100)
   */
  _calculateSuccessRate() {
    if (this.stats.totalPings === 0) {
      return 0;
    }

    return Math.round((this.stats.successfulPings / this.stats.totalPings) * 100);
  }

  /**
   * Reset statistics
   */
  resetStats() {
    this.stats = {
      totalPings: 0,
      successfulPings: 0,
      failedPings: 0,
      lastPingAt: null,
      lastPingSuccess: null
    };
    this.consecutiveFailures = 0;

    this._log('Statistics reset');
  }

  /**
   * Update configuration (must stop and restart to apply)
   *
   * @param {Object} config - Configuration object
   * @param {number} config.pingInterval - Ping interval in milliseconds
   * @param {number} config.maxFailures - Max consecutive failures
   * @param {string} config.endpoint - Status check endpoint
   * @param {number} config.timeout - Request timeout
   */
  updateConfig(config) {
    if (config.pingInterval !== undefined) {
      HeartbeatService.CONFIG.PING_INTERVAL = config.pingInterval;
    }

    if (config.maxFailures !== undefined) {
      HeartbeatService.CONFIG.MAX_FAILURES = config.maxFailures;
    }

    if (config.endpoint !== undefined) {
      HeartbeatService.CONFIG.ENDPOINT = config.endpoint;
    }

    if (config.timeout !== undefined) {
      HeartbeatService.CONFIG.TIMEOUT = config.timeout;
    }

    this._log('Configuration updated', HeartbeatService.CONFIG);
  }

  /**
   * Get current configuration
   *
   * @returns {Object} Configuration object
   */
  getConfig() {
    return { ...HeartbeatService.CONFIG };
  }

  /**
   * Check if service is healthy (consecutive failures < max)
   *
   * @returns {boolean}
   */
  isHealthy() {
    return this.consecutiveFailures < HeartbeatService.CONFIG.MAX_FAILURES;
  }

  /**
   * Log helper
   *
   * @private
   * @param {string} message - Log message
   * @param {Object} data - Additional data
   */
  _log(message, data = {}) {
    console.log(`[HeartbeatService] ${message}`, data);
  }

  /**
   * Error log helper
   *
   * @private
   * @param {string} message - Error message
   * @param {Error} error - Error object
   */
  _logError(message, error) {
    console.error(`[HeartbeatService] ${message}`, error);
  }
}

// Export singleton instance
const heartbeatService = new HeartbeatService();

export { heartbeatService, HeartbeatService };
export default heartbeatService;
