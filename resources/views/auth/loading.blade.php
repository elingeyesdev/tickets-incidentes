<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cargando... - HELPDESK</title>

    {{-- AdminLTE & Bootstrap Styles (Minified for speed) --}}
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    <style>
        body {
            background-color: #f4f6f9; /* AdminLTE background color */
            height: 100vh;
            overflow: hidden; /* Prevent scrolling */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Preloader Styles Override to center it perfectly */
        .preloader {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #f4f6f9;
            height: 100%;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 9999;
        }

        .animation__shake {
            animation: shake 1500ms;
        }

        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -2px) rotate(-1deg); }
        }

        .status-text {
            margin-top: 20px;
            color: #6c757d; /* Secondary text color */
            font-weight: 500;
            font-size: 1.1rem;
            text-align: center;
            font-family: "Source Sans Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
    </style>
</head>
<body>

    {{-- AdminLTE Preloader Structure --}}
    <div class="preloader">
        <img class="animation__shake" src="{{ asset('vendor/adminlte/dist/img/AdminLTELogo.png') }}" alt="HELPDESK Logo" height="60" width="60">
        <div class="status-text" id="status-text">Iniciando sistema...</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const statusText = document.getElementById('status-text');
            
            function updateStatus(text) {
                statusText.textContent = text;
            }

            // Artificial delay to show the loading screen (Professional UX)
            // Matches the animation__shake duration roughly
            setTimeout(async () => {
                try {
                    updateStatus('Verificando sesión...');
                    
                    // Call the check-status endpoint
                    const response = await fetch('/auth/check-status', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.status === 'authenticated') {
                        updateStatus('Sesión verificada. Redirigiendo...');
                        
                        // CRITICAL: Sync localStorage with the valid token from server
                        if (data.access_token) {
                            const now = Date.now();
                            // If expires_in is provided, use it, otherwise default to 1 hour
                            const ttl = data.expires_in ? (data.expires_in * 1000) : (3600 * 1000);
                            
                            localStorage.setItem('access_token', data.access_token);
                            localStorage.setItem('helpdesk_token_expiry', (now + ttl).toString());
                            localStorage.setItem('helpdesk_token_issued_at', now.toString());
                            
                            // CRITICAL: Extract and set active_role from the new token
                            // This matches the logic in authenticated.blade.php
                            try {
                                const payload = JSON.parse(atob(data.access_token.split('.')[1]));
                                
                                // If the token has an active_role claim, use it
                                if (payload.active_role) {
                                    localStorage.setItem('active_role', JSON.stringify(payload.active_role));
                                } 
                                // Fallback: If no active_role in token, try to infer from roles list (if single role)
                                else if (payload.roles && payload.roles.length === 1) {
                                    const role = payload.roles[0];
                                    const activeRole = {
                                        code: role.code,
                                        company_id: role.company_id || null,
                                        company_name: role.company_name || null
                                    };
                                    localStorage.setItem('active_role', JSON.stringify(activeRole));
                                }
                            } catch (e) {
                                console.error('[Auth Loader] Failed to parse active role:', e);
                            }

                            console.log('[Auth Loader] Token and Role synced to localStorage');
                        }

                        // Redirect to dashboard
                        window.location.href = '/app/dashboard';
                    } else {
                        updateStatus('Redirigiendo al inicio...');
                        // Clear artifacts
                        localStorage.removeItem('access_token');
                        localStorage.removeItem('active_role');
                        localStorage.removeItem('helpdesk_token_expiry');
                        localStorage.removeItem('helpdesk_token_issued_at');
                        
                        // Redirect to welcome/login
                        window.location.href = '/welcome';
                    }

                } catch (error) {
                    console.error('[Auth Loader] Error:', error);
                    updateStatus('Error de conexión. Reintentando...');
                    // Fallback to welcome on error
                    window.location.href = '/welcome';
                }
            }, 1000); // 1 second delay for the shake animation to play
        });
    </script>
</body>
</html>
