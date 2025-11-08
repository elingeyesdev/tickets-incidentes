<!DOCTYPE html>
<html>
<head>
    <title>🔐 JWT System - Alpine.js Testing</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .header h1 { color: #333; margin-bottom: 10px; }
        .header p { color: #666; font-size: 14px; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            color: #333;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        button {
            flex: 1;
            padding: 10px 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        button:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
            line-height: 1.6;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #ccc;
            padding-left: 8px;
        }
        .log-success {
            border-left-color: #28a745;
            color: #155724;
            background: #d4edda;
        }
        .log-error {
            border-left-color: #dc3545;
            color: #721c24;
            background: #f8d7da;
        }
        .log-info {
            border-left-color: #17a2b8;
            color: #0c5460;
            background: #d1ecf1;
        }
        .wide { grid-column: 1 / -1; }
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 JWT System - Alpine.js Interactive Testing</h1>
            <p>Real test with Alpine.js + REST API</p>
        </div>

        <div class="grid">
            <!-- Login Form -->
            <div class="card" x-data="authApp()" @login.window="onLogin">
                <h2>1️⃣ Login & Get Tokens</h2>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" x-model="email" placeholder="javier.rodriguez@pilandina.com.bo">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" x-model="password" placeholder="Password">
                </div>
                <div class="button-group">
                    <button @click="login()" :disabled="isLoading">Login</button>
                    <button @click="clearLogs('login')" class="button-secondary">Clear</button>
                </div>
                <div class="output" x-ref="loginOutput"></div>
            </div>

            <!-- Status -->
            <div class="card" x-data="authApp()">
                <h2>2️⃣ Test Protected Endpoint</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">GET /api/auth/status</p>
                <div class="button-group">
                    <button @click="getStatus()" :disabled="isLoading">Get Status</button>
                    <button @click="clearLogs('status')" class="button-secondary">Clear</button>
                </div>
                <div class="output" x-ref="statusOutput"></div>
            </div>

            <!-- Refresh -->
            <div class="card" x-data="authApp()">
                <h2>3️⃣ Refresh Access Token</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">POST /api/auth/refresh</p>
                <div class="button-group">
                    <button @click="refresh()" :disabled="isLoading">Refresh Token</button>
                    <button @click="clearLogs('refresh')" class="button-secondary">Clear</button>
                </div>
                <div class="output" x-ref="refreshOutput"></div>
            </div>

            <!-- Storage -->
            <div class="card" x-data="authApp()">
                <h2>4️⃣ View localStorage</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Inspect stored tokens</p>
                <div class="button-group">
                    <button @click="inspectStorage()">Inspect</button>
                    <button @click="clearAllStorage()" class="button-secondary">Clear All</button>
                </div>
                <div class="output" x-ref="storageOutput"></div>
            </div>

            <!-- Logout -->
            <div class="card" x-data="authApp()">
                <h2>5️⃣ Logout</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">POST /api/auth/logout</p>
                <div class="button-group">
                    <button @click="logout()" :disabled="isLoading">Logout</button>
                    <button @click="clearLogs('logout')" class="button-secondary">Clear</button>
                </div>
                <div class="output" x-ref="logoutOutput"></div>
            </div>

            <!-- Info -->
            <div class="card wide">
                <h2>📋 How to Test</h2>
                <ul style="font-size: 12px; color: #666; line-height: 1.8;">
                    <li><strong>Step 1:</strong> Click "Login" with credentials</li>
                    <li><strong>Step 2:</strong> Click "Get Status" to test protected endpoint</li>
                    <li><strong>Step 3:</strong> Close and reopen this page - token stays in localStorage</li>
                    <li><strong>Step 4:</strong> Click "Refresh Token" to get new token</li>
                    <li><strong>Step 5:</strong> Click "Logout" to clear session</li>
                    <li><strong>DevTools:</strong> Open F12 → Console for detailed logs</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function authApp() {
            return {
                email: 'javier.rodriguez@pilandina.com.bo',
                password: 'mklmklmkl',
                isLoading: false,

                log(refName, message, type = 'info') {
                    const ref = this.$refs[refName];
                    if (!ref) return;

                    const entry = document.createElement('div');
                    entry.className = `log-entry log-${type}`;
                    entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
                    ref.appendChild(entry);
                    ref.scrollTop = ref.scrollHeight;
                    console.log(`[${type.toUpperCase()}] ${message}`);
                },

                clearLogs(refName) {
                    const ref = this.$refs[refName];
                    if (ref) ref.innerHTML = '';
                },

                async login() {
                    this.isLoading = true;
                    this.clearLogs('login');

                    try {
                        this.log('login', `⏳ Attempting login with ${this.email}...`, 'info');

                        const response = await fetch('/api/auth/login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({ email: this.email, password: this.password })
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.log('login', `❌ Login failed: ${data.message}`, 'error');
                            return;
                        }

                        const token = data.accessToken;
                        const expiresIn = data.expiresIn;
                        const user = data.user;

                        // Save to localStorage
                        localStorage.setItem('access_token', token);
                        localStorage.setItem('token_expires_at', Date.now() + (expiresIn * 1000));

                        this.log('login', '✅ Login successful!', 'success');
                        this.log('login', `📌 TOKEN: ${token}`, 'success');
                        this.log('login', `Expires In: ${expiresIn} seconds`, 'success');
                        this.log('login', `User: ${user.displayName || user.email}`, 'success');

                        if (user.roleContexts?.length > 0) {
                            const roles = user.roleContexts.map(r => r.roleName).join(', ');
                            this.log('login', `Roles: ${roles}`, 'info');
                        }

                    } catch (e) {
                        this.log('login', `❌ Error: ${e.message}`, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async getStatus() {
                    this.isLoading = true;
                    this.clearLogs('status');

                    try {
                        const token = localStorage.getItem('access_token');
                        if (!token) {
                            this.log('status', '❌ No token found. Please login first.', 'error');
                            return;
                        }

                        this.log('status', '⏳ Fetching user status...', 'info');

                        const response = await fetch('/api/auth/status', {
                            headers: { 'Authorization': `Bearer ${token}` }
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.log('status', `❌ Failed: ${data.message}`, 'error');
                            return;
                        }

                        this.log('status', '✅ Status request successful!', 'success');
                        this.log('status', `Authenticated: ${data.isAuthenticated}`, 'success');
                        this.log('status', `User: ${data.user.displayName}`, 'success');
                        this.log('status', `Email: ${data.user.email}`, 'info');

                    } catch (e) {
                        this.log('status', `❌ Error: ${e.message}`, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async refresh() {
                    this.isLoading = true;
                    this.clearLogs('refresh');

                    try {
                        this.log('refresh', '⏳ Requesting token refresh...', 'info');

                        const response = await fetch('/api/auth/refresh', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include'
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.log('refresh', `❌ Failed: ${data.message}`, 'error');
                            return;
                        }

                        const token = data.accessToken;
                        const expiresIn = data.expiresIn;

                        // Update localStorage
                        localStorage.setItem('access_token', token);
                        localStorage.setItem('token_expires_at', Date.now() + (expiresIn * 1000));

                        this.log('refresh', '✅ Token refresh successful!', 'success');
                        this.log('refresh', `📌 NEW TOKEN: ${token}`, 'success');
                        this.log('refresh', `Expires In: ${expiresIn} seconds`, 'success');

                    } catch (e) {
                        this.log('refresh', `❌ Error: ${e.message}`, 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                inspectStorage() {
                    this.clearLogs('storage');

                    const token = localStorage.getItem('access_token');
                    const expiresAt = localStorage.getItem('token_expires_at');

                    this.log('storage', 'localStorage contents:', 'info');

                    if (token) {
                        this.log('storage', `✅ access_token: ${token.substring(0, 50)}...`, 'success');
                    } else {
                        this.log('storage', '❌ access_token: (empty)', 'error');
                    }

                    if (expiresAt) {
                        const date = new Date(parseInt(expiresAt));
                        const expired = new Date() > date;
                        this.log('storage', `${expired ? '❌' : '✅'} token_expires_at: ${date.toLocaleString()}`, expired ? 'error' : 'success');
                    } else {
                        this.log('storage', '❌ token_expires_at: (empty)', 'error');
                    }
                },

                clearAllStorage() {
                    localStorage.removeItem('access_token');
                    localStorage.removeItem('token_expires_at');
                    this.clearLogs('storage');
                    this.log('storage', '✅ All storage cleared', 'success');
                },

                async logout() {
                    this.isLoading = true;
                    this.clearLogs('logout');

                    try {
                        const token = localStorage.getItem('access_token');
                        if (!token) {
                            this.log('logout', '⚠️ No token, clearing storage anyway', 'info');
                            this.clearAllStorage();
                            return;
                        }

                        this.log('logout', '⏳ Logging out...', 'info');

                        const response = await fetch('/api/auth/logout', {
                            method: 'POST',
                            headers: { 'Authorization': `Bearer ${token}` }
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.log('logout', `⚠️ Server logout failed: ${data.message}`, 'error');
                        } else {
                            this.log('logout', '✅ Server logout successful', 'success');
                        }

                        this.clearAllStorage();
                        this.log('logout', '✅ All tokens cleared', 'success');

                    } catch (e) {
                        this.log('logout', `⚠️ Error: ${e.message}`, 'error');
                        this.clearAllStorage();
                    } finally {
                        this.isLoading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
