<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Title --}}
    <title>@yield('title', 'Helpdesk Widget')</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    {{-- Base AdminLTE Stylesheets --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    {{-- Select2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">

    {{-- Google Font: Source Sans Pro --}}
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

    {{-- Widget Specific Styles --}}
    <style>
        /* Reset body for iframe embedding */
        html, body {
            margin: 0;
            padding: 0;
            background: transparent !important;
            overflow-x: hidden;
        }

        /* Hide scrollbar but keep scrolling */
        body::-webkit-scrollbar {
            width: 6px;
        }
        body::-webkit-scrollbar-track {
            background: transparent;
        }
        body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Widget container */
        .widget-container {
            padding: 1rem;
            min-height: auto;
        }

        /* Loading screen */
        .widget-loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            text-align: center;
        }

        .widget-loader .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .widget-loader .status-text {
            color: #6c757d;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .widget-loader .status-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .widget-loader .status-item {
            display: flex;
            align-items: center;
            margin: 0.3rem 0;
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }

        .widget-loader .status-item.active {
            opacity: 1;
        }

        .widget-loader .status-item.completed {
            opacity: 1;
            color: #28a745;
        }

        .widget-loader .status-item.error {
            opacity: 1;
            color: #dc3545;
        }

        /* Company not found */
        .company-not-found {
            text-align: center;
            padding: 3rem 2rem;
        }

        .company-not-found .icon {
            font-size: 4rem;
            color: #ffc107;
            margin-bottom: 1.5rem;
        }

        /* Auth forms */
        .widget-auth-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .widget-auth-form .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .widget-auth-form .logo img {
            max-height: 60px;
        }

        /* Success transition */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Ticket content - full height usage */
        .widget-tickets {
            height: 100%;
        }

        /* Hide preloader in widget */
        .preloader {
            display: none !important;
        }

        /* Adjust sidebar for widget (if used) */
        .main-sidebar {
            top: 0 !important;
        }

        /* No navbar in widget */
        .content-wrapper {
            margin-left: 0 !important;
            margin-top: 0 !important;
            min-height: auto !important;
            height: auto !important;
        }

        /* Override AdminLTE wrapper to allow flexible height */
        .wrapper {
            min-height: auto !important;
        }

        /* Force body to auto height */
        body {
            min-height: auto !important;
        }
    </style>

    @stack('css')
    @yield('css')
</head>

<body class="hold-transition">
    <div class="widget-container">
        @yield('content')
    </div>

    {{-- Base Scripts --}}
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

    {{-- Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    {{-- jQuery Validation Plugin --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>

    {{-- Widget Token Manager (con auto-refresh) --}}
    <script>
        // Token Manager mejorado con auto-refresh
        (function() {
            'use strict';

            // Leer token de URL
            const urlParams = new URLSearchParams(window.location.search);
            const urlToken = urlParams.get('token');
            
            // Obtener API Key de la URL (para refresh endpoint)
            const apiKey = urlParams.get('api_key') || '';

            // Widget Token Manager mejorado
            window.widgetTokenManager = {
                _token: urlToken || null,
                _payload: null,
                _refreshTimer: null,
                _apiKey: apiKey,

                /**
                 * Establece un nuevo token y programa auto-refresh
                 */
                setToken: function(token) {
                    this._token = token;
                    this._payload = this._decodePayload(token);
                    console.log('[WidgetTokenManager] Token set');
                    
                    // Programar auto-refresh
                    this._scheduleRefresh();
                },

                getAccessToken: function() {
                    return this._token;
                },

                isAuthenticated: function() {
                    return !!this._token;
                },

                clearTokens: function() {
                    this._token = null;
                    this._payload = null;
                    if (this._refreshTimer) {
                        clearTimeout(this._refreshTimer);
                        this._refreshTimer = null;
                    }
                    console.log('[WidgetTokenManager] Tokens cleared');
                },

                /**
                 * Obtiene el tiempo restante antes de expiración (en segundos)
                 */
                getTimeToExpiry: function() {
                    if (!this._payload || !this._payload.exp) {
                        return 0;
                    }
                    return Math.max(0, this._payload.exp - Math.floor(Date.now() / 1000));
                },

                /**
                 * Verifica si el token está por expirar (menos del 20% restante)
                 */
                isExpiringSoon: function() {
                    if (!this._payload || !this._payload.exp || !this._payload.iat) {
                        return true;
                    }
                    const ttlTotal = this._payload.exp - this._payload.iat;
                    const threshold = this._payload.iat + (ttlTotal * 0.8);
                    const now = Math.floor(Date.now() / 1000);
                    return now >= threshold;
                },

                /**
                 * Intenta refrescar el token
                 */
                refreshToken: async function() {
                    if (!this._token) {
                        console.warn('[WidgetTokenManager] No token to refresh');
                        return false;
                    }

                    console.log('[WidgetTokenManager] Refreshing token...');

                    try {
                        const response = await fetch('/api/external/refresh', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': 'Bearer ' + this._token,
                                'X-Service-Key': this._apiKey,
                            },
                        });

                        if (!response.ok) {
                            console.error('[WidgetTokenManager] Refresh failed:', response.status);
                            return false;
                        }

                        const data = await response.json();
                        
                        if (data.success && data.accessToken) {
                            this.setToken(data.accessToken);
                            console.log('[WidgetTokenManager] Token refreshed successfully');
                            return true;
                        }

                        return false;
                    } catch (error) {
                        console.error('[WidgetTokenManager] Refresh error:', error);
                        return false;
                    }
                },

                /**
                 * Programa el auto-refresh al 80% del TTL
                 */
                _scheduleRefresh: function() {
                    if (this._refreshTimer) {
                        clearTimeout(this._refreshTimer);
                    }

                    if (!this._payload || !this._payload.exp || !this._payload.iat) {
                        return;
                    }

                    const ttlTotal = this._payload.exp - this._payload.iat;
                    const refreshAt = ttlTotal * 0.8; // 80% del tiempo
                    const now = Math.floor(Date.now() / 1000);
                    const createdAt = this._payload.iat;
                    const msUntilRefresh = ((createdAt + refreshAt) - now) * 1000;

                    if (msUntilRefresh <= 0) {
                        // Ya pasó el threshold, refrescar ahora
                        console.log('[WidgetTokenManager] Token past 80% threshold, refreshing now');
                        this.refreshToken();
                        return;
                    }

                    console.log('[WidgetTokenManager] Scheduling refresh in', Math.round(msUntilRefresh / 1000), 'seconds');
                    
                    this._refreshTimer = setTimeout(() => {
                        this.refreshToken();
                    }, msUntilRefresh);
                },

                /**
                 * Decodifica el payload del JWT (sin validar firma)
                 */
                _decodePayload: function(token) {
                    try {
                        const parts = token.split('.');
                        if (parts.length !== 3) return null;
                        
                        const payload = JSON.parse(atob(parts[1]));
                        return payload;
                    } catch (e) {
                        console.error('[WidgetTokenManager] Error decoding token:', e);
                        return null;
                    }
                },

                /**
                 * Cierra sesión del widget y redirige a la pantalla de conexión
                 */
                logout: function() {
                    this.clearTokens();
                    
                    // Obtener datos del usuario de la URL actual o del payload
                    const currentParams = new URLSearchParams(window.location.search);
                    const email = this._payload?.email || currentParams.get('email') || '';
                    
                    // Construir URL de desconexión
                    const logoutUrl = '/widget?' + new URLSearchParams({
                        api_key: this._apiKey,
                        email: email,
                        first_name: currentParams.get('first_name') || '',
                        last_name: currentParams.get('last_name') || '',
                    }).toString();
                    
                    console.log('[WidgetTokenManager] Logging out, redirecting to connect screen');
                    window.location.href = logoutUrl;
                }
            };

            // Inicializar con el token de URL
            if (urlToken) {
                window.widgetTokenManager._payload = window.widgetTokenManager._decodePayload(urlToken);
                window.widgetTokenManager._scheduleRefresh();
            }

            // Alias para compatibilidad con código existente
            window.tokenManager = window.widgetTokenManager;

            console.log('[Widget] Token manager initialized, authenticated:', window.widgetTokenManager.isAuthenticated());
            if (window.widgetTokenManager._payload) {
                console.log('[Widget] Token expires in:', window.widgetTokenManager.getTimeToExpiry(), 'seconds');
            }
        })();

        // WIDGET HEIGHT FIX: Remove inline min-height added by AdminLTE
        // This ensures the widget can grow/shrink based on content
        (function() {
            'use strict';

            function fixWidgetHeight() {
                const contentWrapper = document.querySelector('.content-wrapper');
                const wrapper = document.querySelector('.wrapper');
                const body = document.body;

                if (contentWrapper) {
                    contentWrapper.style.minHeight = 'auto';
                    contentWrapper.style.height = 'auto';
                    console.log('[Widget Height] Fixed .content-wrapper to auto height');
                }

                if (wrapper) {
                    wrapper.style.minHeight = 'auto';
                    console.log('[Widget Height] Fixed .wrapper to auto height');
                }

                if (body) {
                    body.style.minHeight = 'auto';
                    console.log('[Widget Height] Fixed body to auto height');
                }
            }

            // Run on DOMContentLoaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fixWidgetHeight);
            } else {
                fixWidgetHeight();
            }

            // Watch for changes and reapply if needed (every 1s for 10s)
            let attempts = 0;
            const interval = setInterval(() => {
                fixWidgetHeight();
                attempts++;
                if (attempts > 10) {
                    clearInterval(interval);
                    console.log('[Widget Height] Fix applied and stabilized');
                }
            }, 1000);
        })();

        // WIDGET HEIGHT COMMUNICATION: Send height to parent frame via postMessage
        (function() {
            'use strict';

            console.log('[Widget Height] Iniciando comunicación de altura con parent frame');

            let lastHeight = 0;
            let resizeTimeout;
            let rafId;

            function notifyParentHeight() {
                const contentWrapper = document.querySelector('.content-wrapper');
                const widget = document.querySelector('.widget-container');

                // Obtener la altura actual del contenido
                const height = (contentWrapper?.scrollHeight || widget?.scrollHeight || document.body.scrollHeight) + 20;

                console.log('[Widget Height] Altura calculada:', height);

                // Enviar mensaje al parent frame
                window.parent.postMessage({
                    type: 'widget-resize',
                    height: height
                }, '*');
            }

            function checkAndNotifyHeight() {
                const contentWrapper = document.querySelector('.content-wrapper');
                const widget = document.querySelector('.widget-container');
                const currentHeight = (contentWrapper?.scrollHeight || widget?.scrollHeight || document.body.scrollHeight) + 20;

                // Solo notificar si cambió la altura
                if (currentHeight !== lastHeight) {
                    console.log('[Widget Height] Altura cambió de', lastHeight, 'a', currentHeight);
                    lastHeight = currentHeight;
                    notifyParentHeight();
                }
            }

            // RESIZE OBSERVER: Detecta cambios en el tamaño de elementos
            function setupResizeObserver() {
                const contentWrapper = document.querySelector('.content-wrapper');
                if (!contentWrapper) return;

                const resizeObserver = new ResizeObserver(() => {
                    console.log('[Widget Height] ResizeObserver detectó cambio');
                    checkAndNotifyHeight();
                });

                resizeObserver.observe(contentWrapper);
                console.log('[Widget Height] ResizeObserver activado en .content-wrapper');
            }

            // Ejecutar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    notifyParentHeight();
                    setupResizeObserver();
                });
            } else {
                notifyParentHeight();
                setupResizeObserver();
            }

            // Notificar altura cada 100ms durante los primeros 2 segundos (para capturar todo)
            let initialCheckCount = 0;
            const initialInterval = setInterval(() => {
                checkAndNotifyHeight();
                initialCheckCount++;
                if (initialCheckCount >= 20) {
                    clearInterval(initialInterval);
                    console.log('[Widget Height] Verificación inicial completada');
                }
            }, 100);

            // MUTATION OBSERVER: Detecta cambios en el DOM de forma inmediata
            const observer = new MutationObserver(() => {
                // Usar debounce para evitar múltiples llamadas
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    console.log('[Widget Height] DOM cambió, verificando altura...');
                    checkAndNotifyHeight();
                }, 30); // 30ms de debounce (más rápido que antes)
            });

            // Configurar el observer
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style'],
                characterData: false
            });

            console.log('[Widget Height] MutationObserver activado');

            // Verificar cada 500ms como fallback
            setInterval(() => {
                checkAndNotifyHeight();
            }, 500);

            // RequestAnimationFrame para capturar cambios muy rápido
            function animationFrameCheck() {
                checkAndNotifyHeight();
                rafId = requestAnimationFrame(animationFrameCheck);
            }

            // Solo usar rAF durante 3 segundos para no sobrecargar
            animationFrameCheck();
            setTimeout(() => {
                cancelAnimationFrame(rafId);
                console.log('[Widget Height] RequestAnimationFrame desactivado');
            }, 3000);
        })();
    </script>

    @stack('scripts')
    @yield('js')
</body>

</html>
