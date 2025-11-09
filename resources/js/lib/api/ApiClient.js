/**
 * ApiClient.js
 *
 * Secure API client with automatic token refresh and request retry.
 * Works with HttpOnly cookie-based refresh tokens.
 *
 * SECURITY MODEL:
 * - Access tokens: Retrieved from localStorage via TokenManager
 * - Refresh tokens: HttpOnly cookies (browser sends automatically, JS cannot access)
 * - Automatic refresh on 401 errors
 * - Automatic retry of failed requests after refresh
 * - Redirect to login when refresh fails (session expired)
 *
 * Features:
 * - Auto-inject Authorization header
 * - Auto-refresh on 401
 * - Request retry with new token
 * - Concurrent request handling (debounce refresh)
 * - Type-safe error handling
 *
 * @author Helpdesk System
 * @version 1.0.0
 */

import tokenManager from '../auth/TokenManager.js';

class ApiClient {
  /**
   * Configuration
   */
  static CONFIG = {
    BASE_URL: '/api',
    TIMEOUT: 30000, // 30 seconds
    MAX_RETRIES: 1, // Only retry once after token refresh
  };

  /**
   * Refresh state management (prevent concurrent refreshes)
   */
  static refreshPromise = null;
  static isRefreshing = false;

  /**
   * Make authenticated API request with auto-refresh
   *
   * @param {string} url - Request URL (relative to BASE_URL)
   * @param {Object} options - Fetch options
   * @param {boolean} options._isRetry - Internal flag to prevent infinite retry loop
   * @returns {Promise<Response>}
   */
  static async request(url, options = {}) {
    // Prepare request URL
    const fullUrl = url.startsWith('http') ? url : `${ApiClient.CONFIG.BASE_URL}${url}`;

    // Get access token
    const token = tokenManager.getAccessToken();

    // Prepare headers
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...options.headers,
    };

    // Add Authorization header if token exists
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    // Merge options
    const requestOptions = {
      ...options,
      headers,
      credentials: 'include', // Always include cookies for HttpOnly refresh token
    };

    try {
      // Make request
      let response = await fetch(fullUrl, requestOptions);

      // Handle 401 Unauthorized - attempt refresh and retry
      if (response.status === 401 && !options._isRetry) {
        console.log('[ApiClient] Received 401, attempting token refresh...');

        try {
          // Attempt to refresh token
          await ApiClient.refresh();

          // Retry original request with new token
          console.log('[ApiClient] Token refreshed, retrying request...');
          const newToken = tokenManager.getAccessToken();

          if (newToken) {
            headers['Authorization'] = `Bearer ${newToken}`;
            requestOptions.headers = headers;
            requestOptions._isRetry = true; // Prevent infinite retry loop

            response = await fetch(fullUrl, requestOptions);
          }
        } catch (refreshError) {
          console.error('[ApiClient] Token refresh failed:', refreshError);

          // Redirect to login on refresh failure
          ApiClient.redirectToLogin('session_expired');
          throw new Error('Session expired. Please login again.');
        }
      }

      return response;
    } catch (error) {
      console.error('[ApiClient] Request error:', error);
      throw error;
    }
  }

  /**
   * Refresh access token using HttpOnly cookie
   *
   * IMPORTANT: No refresh token sent in body - browser sends HttpOnly cookie automatically
   * This method debounces concurrent refresh requests to prevent multiple calls
   *
   * @returns {Promise<void>}
   */
  static async refresh() {
    // If already refreshing, wait for existing refresh to complete
    if (ApiClient.isRefreshing && ApiClient.refreshPromise) {
      console.log('[ApiClient] Refresh already in progress, waiting...');
      return ApiClient.refreshPromise;
    }

    // Mark as refreshing
    ApiClient.isRefreshing = true;

    // Create refresh promise
    ApiClient.refreshPromise = (async () => {
      try {
        console.log('[ApiClient] Starting token refresh...');

        const response = await fetch('/api/auth/refresh', {
          method: 'POST',
          credentials: 'include', // CRITICAL: Send HttpOnly refresh_token cookie
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          }
          // NO BODY - refresh token comes from HttpOnly cookie automatically
        });

        if (!response.ok) {
          const error = await response.json().catch(() => ({}));
          throw new Error(error.message || 'Token refresh failed');
        }

        const data = await response.json();

        // Update access token in localStorage
        // New refresh token comes in Set-Cookie header (browser handles automatically)
        if (data.accessToken) {
          tokenManager.setTokens(data.accessToken, data.expiresIn);
          console.log('[ApiClient] Token refresh successful');
        } else {
          throw new Error('Invalid refresh response: missing accessToken');
        }
      } catch (error) {
        console.error('[ApiClient] Refresh failed:', error);
        throw error;
      } finally {
        ApiClient.isRefreshing = false;
        ApiClient.refreshPromise = null;
      }
    })();

    return ApiClient.refreshPromise;
  }

  /**
   * Redirect to login page
   *
   * @param {string} reason - Reason for redirect (e.g., 'session_expired')
   */
  static redirectToLogin(reason = '') {
    const url = reason ? `/login?reason=${reason}` : '/login';
    console.log(`[ApiClient] Redirecting to login: ${url}`);
    window.location.href = url;
  }

  /**
   * Convenience method: GET request
   *
   * @param {string} url - Request URL
   * @param {Object} options - Additional fetch options
   * @returns {Promise<Response>}
   */
  static async get(url, options = {}) {
    return ApiClient.request(url, {
      ...options,
      method: 'GET',
    });
  }

  /**
   * Convenience method: POST request
   *
   * @param {string} url - Request URL
   * @param {Object} data - Request body data
   * @param {Object} options - Additional fetch options
   * @returns {Promise<Response>}
   */
  static async post(url, data = {}, options = {}) {
    return ApiClient.request(url, {
      ...options,
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  /**
   * Convenience method: PUT request
   *
   * @param {string} url - Request URL
   * @param {Object} data - Request body data
   * @param {Object} options - Additional fetch options
   * @returns {Promise<Response>}
   */
  static async put(url, data = {}, options = {}) {
    return ApiClient.request(url, {
      ...options,
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  /**
   * Convenience method: PATCH request
   *
   * @param {string} url - Request URL
   * @param {Object} data - Request body data
   * @param {Object} options - Additional fetch options
   * @returns {Promise<Response>}
   */
  static async patch(url, data = {}, options = {}) {
    return ApiClient.request(url, {
      ...options,
      method: 'PATCH',
      body: JSON.stringify(data),
    });
  }

  /**
   * Convenience method: DELETE request
   *
   * @param {string} url - Request URL
   * @param {Object} options - Additional fetch options
   * @returns {Promise<Response>}
   */
  static async delete(url, options = {}) {
    return ApiClient.request(url, {
      ...options,
      method: 'DELETE',
    });
  }

  /**
   * Parse JSON response with error handling
   *
   * @param {Response} response - Fetch response
   * @returns {Promise<Object>}
   */
  static async parseResponse(response) {
    if (!response.ok) {
      const error = await response.json().catch(() => ({
        message: `HTTP ${response.status}: ${response.statusText}`
      }));

      throw new Error(error.message || 'Request failed');
    }

    return response.json();
  }

  /**
   * Helper: Check if user is authenticated
   *
   * @returns {boolean}
   */
  static isAuthenticated() {
    return tokenManager.getAccessToken() !== null;
  }
}

export { ApiClient };
export default ApiClient;
