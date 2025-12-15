import TokenManager from '../../lib/auth/TokenManager.js';
import AuthChannel from '../../lib/auth/AuthChannel.js';
import PersistenceService from '../../lib/auth/PersistenceService.js';
import HeartbeatService from '../../lib/auth/HeartbeatService.js';

/**
 * Alpine.js Authentication Store
 *
 * Global state management for authentication using Alpine.js.
 * Coordinates TokenManager, AuthChannel, PersistenceService, and HeartbeatService.
 *
 * State Properties:
 * - user: Current user object or null
 * - isAuthenticated: Boolean authentication status
 * - loading: Boolean loading state
 * - error: Error message or null
 * - sessionId: Current session ID
 * - loginTimestamp: ISO timestamp of last login
 * - theme: User theme preference (light/dark)
 * - language: User language preference (es/en)
 *
 * Methods:
 * - init(): Initialize store and restore session
 * - login(email, password): Authenticate user
 * - register(data): Register new user
 * - logout(): End user session
 * - loadUser(): Fetch current user data
 * - refreshToken(): Refresh JWT token
 * - setTheme(theme): Update theme preference
 * - setLanguage(lang): Update language preference
 * - clearError(): Clear error message
 */
export default () => ({
    // State
    user: null,
    isAuthenticated: false,
    loading: false,
    error: null,
    sessionId: null,
    loginTimestamp: null,
    theme: 'light',
    language: 'es',

    // Services
    tokenManager: null,
    authChannel: null,
    persistenceService: null,
    heartbeatService: null,

    /**
     * Initialize the auth store
     * Called on page load via x-init
     */
    async init() {
        console.log('[AuthStore] Initializing...');
        this.loading = true;

        try {
            // Initialize services
            this.tokenManager = new TokenManager();
            this.authChannel = new AuthChannel();
            this.persistenceService = new PersistenceService();
            this.heartbeatService = new HeartbeatService(
                this.tokenManager,
                () => this.handleSessionExpired()
            );

            // Restore auth state from persistence
            const persistedState = this.persistenceService.load();

            if (persistedState) {
                console.log('[AuthStore] Found persisted state');

                // Restore state properties
                this.user = persistedState.user;
                this.isAuthenticated = persistedState.isAuthenticated;
                this.sessionId = persistedState.sessionId;
                this.loginTimestamp = persistedState.loginTimestamp;
                this.theme = persistedState.theme || 'light';
                this.language = persistedState.language || 'es';

                // Validate token
                const token = this.tokenManager.getAccessToken();

                if (token) {
                    if (this.tokenManager.isTokenExpired(token)) {
                        console.log('[AuthStore] Token expired, attempting refresh...');

                        try {
                            await this.refreshToken();
                            console.log('[AuthStore] Token refreshed successfully');
                        } catch (error) {
                            console.error('[AuthStore] Token refresh failed:', error);
                            this.handleSessionExpired();
                            return;
                        }
                    }

                    // Load current user data
                    try {
                        await this.loadUser();
                        console.log('[AuthStore] User loaded successfully');

                        // Start heartbeat service
                        this.heartbeatService.start();
                    } catch (error) {
                        console.error('[AuthStore] Failed to load user:', error);
                        this.handleSessionExpired();
                        return;
                    }
                } else {
                    console.log('[AuthStore] No token found in persisted state');
                    this.clearState();
                }
            } else {
                console.log('[AuthStore] No persisted state found');
            }

            // Subscribe to multi-tab events
            this.subscribeToAuthEvents();

        } catch (error) {
            console.error('[AuthStore] Initialization error:', error);
            this.error = 'Failed to initialize authentication';
        } finally {
            this.loading = false;
        }
    },

    /**
     * Subscribe to AuthChannel events for multi-tab synchronization
     */
    subscribeToAuthEvents() {
        console.log('[AuthStore] Subscribing to auth events...');

        this.authChannel.subscribe((event) => {
            console.log('[AuthStore] Received event:', event.type);

            switch (event.type) {
                case 'LOGOUT':
                    console.log('[AuthStore] Logout event from another tab');
                    this.handleRemoteLogout();
                    break;

                case 'TOKEN_REFRESHED':
                    console.log('[AuthStore] Token refreshed in another tab');
                    this.handleRemoteTokenRefresh(event.data);
                    break;

                case 'SESSION_EXPIRED':
                    console.log('[AuthStore] Session expired in another tab');
                    this.handleSessionExpired();
                    break;

                case 'LOGIN':
                    console.log('[AuthStore] Login event from another tab');
                    this.handleRemoteLogin(event.data);
                    break;

                default:
                    console.log('[AuthStore] Unknown event type:', event.type);
            }
        });
    },

    /**
     * Handle logout event from another tab
     */
    handleRemoteLogout() {
        this.clearState();
        window.location.href = '/login';
    },

    /**
     * Handle token refresh from another tab
     */
    handleRemoteTokenRefresh(data) {
        if (data && data.token) {
            this.tokenManager.setAccessToken(data.token);
            this.persistState();
        }
    },

    /**
     * Handle login event from another tab
     */
    handleRemoteLogin(data) {
        if (data && data.user) {
            this.user = data.user;
            this.isAuthenticated = true;
            this.persistState();
        }
    },

    /**
     * Handle session expiration
     */
    handleSessionExpired() {
        console.log('[AuthStore] Session expired');
        this.clearState();
        this.error = 'Your session has expired. Please login again.';

        // Redirect to login after a brief delay
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    },

    /**
     * Login user
     * @param {string} email - User email
     * @param {string} password - User password
     * @returns {Promise<{success: boolean, user: object}>}
     */
    async login(email, password) {
        console.log('[AuthStore] Login attempt for:', email);
        this.loading = true;
        this.error = null;

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ email, password }),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Login failed');
            }

            // Extract data from response
            const { data } = result;
            const { accessToken, user, expiresIn, sessionId } = data;

            // Save access token to localStorage
            // SECURITY: refresh_token is in HttpOnly cookie (browser managed, not JavaScript)
            this.tokenManager.setAccessToken(accessToken);

            // Update state
            this.user = user;
            this.isAuthenticated = true;
            this.sessionId = sessionId;
            this.loginTimestamp = new Date().toISOString();

            // Persist state
            this.persistState();

            // Broadcast login event
            this.authChannel.broadcast('LOGIN', { user, sessionId });

            // Start heartbeat
            this.heartbeatService.start();

            console.log('[AuthStore] Login successful');

            return { success: true, user };

        } catch (error) {
            console.error('[AuthStore] Login error:', error);
            this.error = error.message || 'Login failed';
            return { success: false, error: this.error };
        } finally {
            this.loading = false;
        }
    },

    /**
     * Register new user
     * @param {object} data - Registration data
     * @returns {Promise<{success: boolean, user: object}>}
     */
    async register(data) {
        console.log('[AuthStore] Register attempt for:', data.email);
        this.loading = true;
        this.error = null;

        try {
            const response = await fetch('/api/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Registration failed');
            }

            // Extract data from response
            const { data: responseData } = result;
            const { accessToken, user, sessionId } = responseData;

            // Save access token to localStorage
            // SECURITY: refresh_token is in HttpOnly cookie (browser managed, not JavaScript)
            this.tokenManager.setAccessToken(accessToken);

            // Update state
            this.user = user;
            this.isAuthenticated = true;
            this.sessionId = sessionId;
            this.loginTimestamp = new Date().toISOString();

            // Persist state
            this.persistState();

            // Broadcast login event
            this.authChannel.broadcast('LOGIN', { user, sessionId });

            // Start heartbeat
            this.heartbeatService.start();

            console.log('[AuthStore] Registration successful');

            return { success: true, user };

        } catch (error) {
            console.error('[AuthStore] Registration error:', error);
            this.error = error.message || 'Registration failed';
            return { success: false, error: this.error };
        } finally {
            this.loading = false;
        }
    },

    /**
     * Logout user
     */
    async logout() {
        console.log('[AuthStore] Logout initiated');
        this.loading = true;

        try {
            const token = this.tokenManager.getAccessToken();

            if (token) {
                await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                    },
                });
            }

            // Broadcast logout event
            this.authChannel.broadcast('LOGOUT', {});

            // Clear state
            this.clearState();

            console.log('[AuthStore] Logout successful');

            // Redirect to login
            window.location.href = '/login';

        } catch (error) {
            console.error('[AuthStore] Logout error:', error);
            // Clear state anyway
            this.clearState();
            window.location.href = '/login';
        } finally {
            this.loading = false;
        }
    },

    /**
     * Load current user data
     */
    async loadUser() {
        console.log('[AuthStore] Loading user data...');
        const token = this.tokenManager.getAccessToken();

        if (!token) {
            throw new Error('No access token available');
        }

        const response = await fetch('/api/auth/status', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`,
            },
        });

        if (!response.ok) {
            throw new Error('Failed to load user data');
        }

        const result = await response.json();
        const { data } = result;

        if (data.isAuthenticated && data.user) {
            this.user = data.user;
            this.isAuthenticated = true;
            this.persistState();
        } else {
            throw new Error('User not authenticated');
        }
    },

    /**
     * Refresh JWT token using HttpOnly cookie
     *
     * SECURITY: No refresh token in body - browser sends HttpOnly cookie automatically
     */
    async refreshToken() {
        console.log('[AuthStore] Refreshing token...');

        const response = await fetch('/api/auth/refresh', {
            method: 'POST',
            credentials: 'include', // CRITICAL: Send HttpOnly cookie
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
            // NO BODY - refresh token comes from HttpOnly cookie
        });

        if (!response.ok) {
            throw new Error('Token refresh failed');
        }

        const result = await response.json();
        const { data } = result;

        // Update access token in localStorage
        // New refresh token comes in Set-Cookie header (browser handles automatically)
        this.tokenManager.setAccessToken(data.accessToken);

        // Broadcast token refresh
        this.authChannel.broadcast('TOKEN_REFRESHED', {
            token: data.accessToken,
        });

        this.persistState();
    },

    /**
     * Set theme preference
     * @param {string} theme - Theme name (light/dark)
     */
    setTheme(theme) {
        console.log('[AuthStore] Setting theme:', theme);
        this.theme = theme;
        this.persistState();

        // Apply theme to document
        document.documentElement.setAttribute('data-theme', theme);
    },

    /**
     * Set language preference
     * @param {string} lang - Language code (es/en)
     */
    setLanguage(lang) {
        console.log('[AuthStore] Setting language:', lang);
        this.language = lang;
        this.persistState();
    },

    /**
     * Clear error message
     */
    clearError() {
        this.error = null;
    },

    /**
     * Persist current state to localStorage
     */
    persistState() {
        this.persistenceService.save({
            user: this.user,
            isAuthenticated: this.isAuthenticated,
            sessionId: this.sessionId,
            loginTimestamp: this.loginTimestamp,
            theme: this.theme,
            language: this.language,
        });
    },

    /**
     * Clear all state and tokens
     */
    clearState() {
        console.log('[AuthStore] Clearing state...');

        // Clear state
        this.user = null;
        this.isAuthenticated = false;
        this.sessionId = null;
        this.loginTimestamp = null;
        this.error = null;

        // Clear tokens
        this.tokenManager.clearTokens();

        // Clear persistence
        this.persistenceService.clear();

        // Stop heartbeat
        if (this.heartbeatService) {
            this.heartbeatService.stop();
        }
    },
});
