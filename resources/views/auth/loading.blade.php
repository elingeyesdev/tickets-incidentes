<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loading... - HELPDESK</title>
    
    {{-- Google Font: Inter (Professional, Clean) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4F46E5; /* Indigo 600 */
            --bg-color: #F9FAFB; /* Gray 50 */
            --text-color: #111827; /* Gray 900 */
            --subtext-color: #6B7280; /* Gray 500 */
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .loader-container {
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        .logo {
            width: 64px;
            height: 64px;
            margin-bottom: 24px;
            /* Placeholder for actual logo */
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 16px;
        }

        .status-text {
            color: var(--subtext-color);
            font-size: 0.875rem;
            font-weight: 500;
            letter-spacing: 0.025em;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="loader-container">
        <div class="logo">H</div>
        <div class="spinner"></div>
        <div class="status-text" id="status-text">Initializing...</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const statusText = document.getElementById('status-text');
            
            function updateStatus(text) {
                statusText.textContent = text;
            }

            // Artificial delay to show the loading screen (Professional UX)
            setTimeout(async () => {
                try {
                    updateStatus('Checking authentication...');
                    
                    // Call the check-status endpoint
                    const response = await fetch('/auth/check-status', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.status === 'authenticated') {
                        updateStatus('Session verified. Redirecting...');
                        
                        // CRITICAL: Sync localStorage with the valid token from server
                        if (data.access_token) {
                            const now = Date.now();
                            // If expires_in is provided, use it, otherwise default to 1 hour
                            const ttl = data.expires_in ? (data.expires_in * 1000) : (3600 * 1000);
                            
                            localStorage.setItem('access_token', data.access_token);
                            localStorage.setItem('helpdesk_token_expiry', (now + ttl).toString());
                            localStorage.setItem('helpdesk_token_issued_at', now.toString());
                            
                            console.log('[Auth Loader] Token synced to localStorage');
                        }

                        // Redirect to dashboard
                        window.location.href = '/app/dashboard';
                    } else {
                        updateStatus('Redirecting to welcome page...');
                        // Clear artifacts
                        localStorage.removeItem('access_token');
                        localStorage.removeItem('helpdesk_token_expiry');
                        localStorage.removeItem('helpdesk_token_issued_at');
                        
                        // Redirect to welcome/login
                        window.location.href = '/welcome';
                    }

                } catch (error) {
                    console.error('[Auth Loader] Error:', error);
                    updateStatus('Connection error. Redirecting...');
                    // Fallback to welcome on error
                    window.location.href = '/welcome';
                }
            }, 1500); // 1.5 second delay
        });
    </script>
</body>
</html>
