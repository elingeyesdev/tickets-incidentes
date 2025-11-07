<!DOCTYPE html>
<html>
<head>
    <title>JWT System - Interactive Testing</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
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
        .form-group {
            margin-bottom: 15px;
        }
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
            transition: border-color 0.3s;
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
        .button-secondary {
            background: #6c757d;
        }
        .button-secondary:hover {
            background: #5a6268;
        }
        .output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
            line-height: 1.4;
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
        .status-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            border-left: 4px solid #667eea;
        }
        .status-box h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-label {
            color: #667eea;
            font-weight: 600;
        }
        .status-value {
            color: #666;
            word-break: break-all;
        }
        .wide {
            grid-column: 1 / -1;
        }
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 JWT System - Interactive API Testing</h1>
            <p>Test the JWT authentication system with your real backend API</p>
        </div>

        <div class="grid">
            <!-- Login Form -->
            <div class="card">
                <h2>1️⃣ Login & Get Tokens</h2>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="loginEmail" placeholder="test@example.com" value="test@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="loginPassword" placeholder="Password" value="">
                </div>
                <div class="button-group">
                    <button onclick="testLogin()">Login</button>
                    <button class="button-secondary" onclick="clearOutput('output-login')">Clear</button>
                </div>
                <div class="output" id="output-login"></div>
                <div class="status-box" id="status-login" style="display: none;">
                    <h4>✅ Login Successful</h4>
                    <div class="status-item">
                        <span class="status-label">Access Token:</span>
                        <span class="status-value" id="status-token">-</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Expires In:</span>
                        <span class="status-value" id="status-expires">-</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">User Email:</span>
                        <span class="status-value" id="status-user">-</span>
                    </div>
                </div>
            </div>

            <!-- Test Protected Endpoint -->
            <div class="card">
                <h2>2️⃣ Test Protected Endpoint</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Get current user status using access token</p>
                <div class="button-group">
                    <button onclick="testStatus()">Get Status</button>
                    <button class="button-secondary" onclick="clearOutput('output-status')">Clear</button>
                </div>
                <div class="output" id="output-status"></div>
            </div>

            <!-- Refresh Token -->
            <div class="card">
                <h2>3️⃣ Refresh Access Token</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Use HttpOnly cookie to get a new access token</p>
                <div class="button-group">
                    <button onclick="testRefresh()">Refresh Token</button>
                    <button class="button-secondary" onclick="clearOutput('output-refresh')">Clear</button>
                </div>
                <div class="output" id="output-refresh"></div>
            </div>

            <!-- Session Info -->
            <div class="card">
                <h2>4️⃣ View Session Info</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">View all active sessions</p>
                <div class="button-group">
                    <button onclick="testSessions()">Get Sessions</button>
                    <button class="button-secondary" onclick="clearOutput('output-sessions')">Clear</button>
                </div>
                <div class="output" id="output-sessions"></div>
            </div>

            <!-- Logout -->
            <div class="card">
                <h2>5️⃣ Logout</h2>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Invalidate current session and tokens</p>
                <div class="button-group">
                    <button onclick="testLogout()" style="background: #dc3545;">Logout</button>
                    <button class="button-secondary" onclick="clearOutput('output-logout')">Clear</button>
                </div>
                <div class="output" id="output-logout"></div>
            </div>

            <!-- LocalStorage Inspector -->
            <div class="card wide">
                <h2>📦 LocalStorage Inspector</h2>
                <div class="button-group">
                    <button onclick="inspectStorage()">Inspect Storage</button>
                    <button class="button-secondary" onclick="clearStorage()">Clear All</button>
                </div>
                <div class="output" id="output-storage" style="max-height: 200px;"></div>
            </div>

            <!-- Console Guide -->
            <div class="card wide">
                <h2>📋 Instructions</h2>
                <ol style="font-size: 13px; line-height: 1.8; margin-left: 20px; color: #666;">
                    <li><strong>Login first:</strong> Use the "Login" button to get tokens. Your credentials will be stored in localStorage.</li>
                    <li><strong>Inspect Storage:</strong> Click "Inspect Storage" to see what's saved locally (access token, expiry, etc).</li>
                    <li><strong>Test Protected:</strong> Use "Get Status" to test that your token works on protected endpoints.</li>
                    <li><strong>Refresh:</strong> Click "Refresh Token" to get a new access token using the HttpOnly refresh cookie.</li>
                    <li><strong>View Sessions:</strong> See all active sessions with device info (IP, user-agent).</li>
                    <li><strong>Logout:</strong> Click "Logout" to invalidate your tokens.</li>
                    <li><strong>DevTools:</strong> Open F12 → Console to see detailed logs. Look for [TokenManager], [AuthChannel], etc.</li>
                    <li><strong>Cookies:</strong> Open F12 → Application → Cookies to see the HttpOnly refresh_token cookie.</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        // ========== HELPERS ==========

        function log(containerId, message, type = 'info') {
            const container = document.getElementById(containerId);
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            container.appendChild(entry);
            container.scrollTop = container.scrollHeight;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        function clearOutput(containerId) {
            document.getElementById(containerId).innerHTML = '';
        }

        function getStoredToken() {
            return localStorage.getItem('helpdesk_access_token');
        }

        // ========== TESTS ==========

        async function testLogin() {
            const outputId = 'output-login';
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;

            clearOutput(outputId);
            clearOutput('output-status');
            clearOutput('output-refresh');
            clearOutput('output-logout');

            if (!email || !password) {
                log(outputId, '❌ Please enter email and password', 'error');
                return;
            }

            try {
                log(outputId, `⏳ Attempting login with ${email}...`, 'info');

                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    log(outputId, '✅ Login successful!', 'success');
                    log(outputId, `Token: ${data.data.accessToken.substring(0, 50)}...`, 'success');
                    log(outputId, `Expires In: ${data.data.expiresIn} seconds`, 'success');
                    log(outputId, `User: ${data.data.user.displayName} (${data.data.user.email})`, 'success');

                    // Store token
                    localStorage.setItem('helpdesk_access_token', data.data.accessToken);
                    localStorage.setItem('helpdesk_token_expiry', Date.now() + data.data.expiresIn * 1000);

                    // Show status box
                    const statusBox = document.getElementById('status-login');
                    statusBox.style.display = 'block';
                    document.getElementById('status-token').textContent = data.data.accessToken.substring(0, 40) + '...';
                    document.getElementById('status-expires').textContent = data.data.expiresIn + 's';
                    document.getElementById('status-user').textContent = data.data.user.email;

                    // Show roles
                    if (data.data.user.roleContexts && data.data.user.roleContexts.length > 0) {
                        const roles = data.data.user.roleContexts.map(r => r.roleName).join(', ');
                        log(outputId, `Roles: ${roles}`, 'info');
                    }

                    log(outputId, '✅ Token saved to localStorage', 'success');
                    log(outputId, '→ You can now use "Get Status" to test protected endpoints', 'info');

                } else {
                    log(outputId, `❌ Login failed: ${data.message}`, 'error');
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([field, messages]) => {
                            log(outputId, `  ${field}: ${messages.join(', ')}`, 'error');
                        });
                    }
                }
            } catch (e) {
                log(outputId, `❌ Error: ${e.message}`, 'error');
            }
        }

        async function testStatus() {
            const outputId = 'output-status';
            const token = getStoredToken();

            clearOutput(outputId);

            if (!token) {
                log(outputId, '❌ No token found. Please login first.', 'error');
                return;
            }

            try {
                log(outputId, '⏳ Fetching user status...', 'info');

                const response = await fetch('/api/auth/status', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const data = await response.json();

                if (response.ok) {
                    log(outputId, '✅ Status request successful!', 'success');
                    log(outputId, `Authenticated: ${data.data.isAuthenticated}`, 'success');
                    log(outputId, `User: ${data.data.user.displayName}`, 'success');
                    log(outputId, `Email: ${data.data.user.email}`, 'success');
                    log(outputId, `Email Verified: ${data.data.user.emailVerified ? 'Yes' : 'No'}`, 'info');
                    log(outputId, `Status: ${data.data.user.status}`, 'info');

                    if (data.data.user.roleContexts) {
                        log(outputId, `Roles: ${data.data.user.roleContexts.map(r => r.roleName).join(', ')}`, 'info');
                    }
                } else {
                    log(outputId, `❌ Status request failed: ${data.message}`, 'error');
                }
            } catch (e) {
                log(outputId, `❌ Error: ${e.message}`, 'error');
            }
        }

        async function testRefresh() {
            const outputId = 'output-refresh';

            clearOutput(outputId);

            try {
                log(outputId, '⏳ Requesting token refresh...', 'info');
                log(outputId, 'Note: HttpOnly cookie (refresh_token) is sent automatically', 'info');

                const response = await fetch('/api/auth/refresh', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include'
                });

                const data = await response.json();

                if (response.ok) {
                    log(outputId, '✅ Token refresh successful!', 'success');
                    log(outputId, `New Token: ${data.data.accessToken.substring(0, 50)}...`, 'success');
                    log(outputId, `Expires In: ${data.data.expiresIn} seconds`, 'success');

                    // Update stored token
                    localStorage.setItem('helpdesk_access_token', data.data.accessToken);
                    localStorage.setItem('helpdesk_token_expiry', Date.now() + data.data.expiresIn * 1000);

                    log(outputId, '✅ New token saved to localStorage', 'success');
                } else {
                    log(outputId, `❌ Refresh failed: ${data.message}`, 'error');
                }
            } catch (e) {
                log(outputId, `❌ Error: ${e.message}`, 'error');
            }
        }

        async function testSessions() {
            const outputId = 'output-sessions';
            const token = getStoredToken();

            clearOutput(outputId);

            if (!token) {
                log(outputId, '❌ No token found. Please login first.', 'error');
                return;
            }

            try {
                log(outputId, '⏳ Fetching active sessions...', 'info');

                const response = await fetch('/api/auth/sessions', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const data = await response.json();

                if (response.ok) {
                    log(outputId, `✅ Found ${data.data.length} active session(s):`, 'success');

                    data.data.forEach((session, index) => {
                        log(outputId, `\nSession ${index + 1}:`, 'info');
                        log(outputId, `  Device: ${session.device_name || 'Unknown'}`, 'info');
                        log(outputId, `  IP: ${session.ip_address || 'Unknown'}`, 'info');
                        log(outputId, `  Last Used: ${session.last_used_at || 'Never'}`, 'info');
                    });
                } else {
                    log(outputId, `❌ Failed to fetch sessions: ${data.message}`, 'error');
                }
            } catch (e) {
                log(outputId, `❌ Error: ${e.message}`, 'error');
            }
        }

        async function testLogout() {
            const outputId = 'output-logout';
            const token = getStoredToken();

            clearOutput(outputId);

            if (!token) {
                log(outputId, '⚠️  No token found, but clearing local storage anyway', 'info');
                clearStorage();
                return;
            }

            try {
                log(outputId, '⏳ Logging out...', 'info');

                const response = await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const data = await response.json();

                if (response.ok) {
                    log(outputId, '✅ Logout successful!', 'success');
                    log(outputId, 'Clearing local storage...', 'info');
                    clearStorage();
                    document.getElementById('status-login').style.display = 'none';
                    log(outputId, '✅ All tokens cleared', 'success');
                } else {
                    log(outputId, `❌ Logout failed: ${data.message}`, 'error');
                }
            } catch (e) {
                log(outputId, `❌ Error: ${e.message}`, 'error');
            }
        }

        function inspectStorage() {
            const outputId = 'output-storage';
            clearOutput(outputId);

            const keys = [
                'helpdesk_access_token',
                'helpdesk_token_expiry',
                'helpdesk_token_issued_at'
            ];

            log(outputId, 'localStorage contents:', 'info');

            keys.forEach(key => {
                const value = localStorage.getItem(key);
                if (value) {
                    if (key.includes('token') && !key.includes('expiry')) {
                        log(outputId, `${key}: ${value.substring(0, 50)}...`, 'success');
                    } else if (key.includes('expiry') || key.includes('issued')) {
                        const date = new Date(parseInt(value));
                        log(outputId, `${key}: ${value} (${date.toLocaleString()})`, 'info');
                    } else {
                        log(outputId, `${key}: ${value}`, 'info');
                    }
                } else {
                    log(outputId, `${key}: (empty)`, 'info');
                }
            });

            log(outputId, '\nCookies:', 'info');
            log(outputId, 'refresh_token: Check F12 → Application → Cookies (HttpOnly, not accessible from JS)', 'info');
        }

        function clearStorage() {
            const keys = [
                'helpdesk_access_token',
                'helpdesk_token_expiry',
                'helpdesk_token_issued_at'
            ];

            keys.forEach(key => localStorage.removeItem(key));
            console.log('LocalStorage cleared');
        }
    </script>
</body>
</html>
