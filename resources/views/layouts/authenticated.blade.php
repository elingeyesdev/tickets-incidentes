<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

    {{-- Title --}}
    <title>@yield('title', 'Dashboard') - HELPDESK</title>

    {{-- Custom stylesheets (pre AdminLTE) --}}
    @yield('adminlte_css_pre')

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

    {{-- Ekko Lightbox --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/5.3.0/ekko-lightbox.css">

    {{-- Google Font: Source Sans Pro --}}
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

    {{-- Extra Configured Plugins Stylesheets --}}
    @include('adminlte::plugins', ['type' => 'css'])

    {{-- Custom Stylesheets (post AdminLTE) --}}
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('css')
    @yield('css')
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        {{-- Preloader --}}
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{ asset('vendor/adminlte/dist/img/AdminLTELogo.png') }}" alt="HELPDESK Logo" height="60" width="60">
        </div>

        {{-- Navbar --}}
        @include('app.shared.navbar')

        {{-- Main Sidebar Container --}}
        @include('app.shared.sidebar')

        {{-- Content Wrapper. Contains page content --}}
        <div class="content-wrapper">
            {{-- Content Header (Page header) --}}
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('content_header', 'Dashboard')</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                @yield('breadcrumbs')
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main content --}}
            <section class="content">
                <div class="container-fluid">
                    {{-- SweetAlert2 Toast Notifications --}}
                    @if (session('success') || session('warning') || session('info') || session('error') || $errors->any())
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 4000,
                                    timerProgressBar: true,
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer)
                                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                                    }
                                });

                                @if (session('success'))
                                    Toast.fire({
                                        icon: 'success',
                                        title: '{{ session('success') }}'
                                    });
                                @endif

                                @if (session('warning'))
                                    Toast.fire({
                                        icon: 'warning',
                                        title: '{{ session('warning') }}'
                                    });
                                @endif

                                @if (session('info'))
                                    Toast.fire({
                                        icon: 'info',
                                        title: '{{ session('info') }}'
                                    });
                                @endif

                                @if (session('error'))
                                    Toast.fire({
                                        icon: 'error',
                                        title: '{{ session('error') }}'
                                    });
                                @endif

                                @if ($errors->any())
                                    Toast.fire({
                                        icon: 'error',
                                        title: 'Por favor corrige los errores del formulario.'
                                    });
                                @endif
                                });
                        </script>
                    @endif

                    {{-- Page Content --}}
                    @yield('content')
                </div>
            </section>
        </div>

        {{-- Footer --}}
        <footer class="main-footer">
            <strong>Copyright &copy; 2025 <a href="/">HELPDESK</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 1.0.0
            </div>
        </footer>

        {{-- Control Sidebar --}}
        <aside class="control-sidebar control-sidebar-dark">
            {{-- Control sidebar content goes here --}}
        </aside>
    </div>

    {{-- Base Scripts --}}
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

    {{-- bs-custom-file-input (AdminLTE v3 Official Plugin for File Inputs) --}}
    <script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input@1.3.4/dist/bs-custom-file-input.min.js"></script>

    {{-- Extra Configured Plugins Scripts --}}
    @include('adminlte::plugins', ['type' => 'js'])

    {{-- Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    {{-- Ekko Lightbox --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/5.3.0/ekko-lightbox.min.js"></script>

    {{-- jQuery Validation Plugin --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- JWT Token Manager Setup (synchronous) --}}
    <script>
        // CRITICAL: If server refreshed the token (server-side auto-refresh), inject it immediately
        // This MUST run before any other scripts to prevent redirect loops
        @if(request()->attributes->has('server_refreshed_token'))
            (function injectServerRefreshedToken() {
                const serverToken = @json(request()->attributes->get('server_refreshed_token'));
                const now = Date.now();
                const expiryTimestamp = now + (serverToken.expires_in * 1000);

                localStorage.setItem('access_token', serverToken.access_token);
                localStorage.setItem('helpdesk_token_expiry', expiryTimestamp.toString());
                localStorage.setItem('helpdesk_token_issued_at', now.toString());

                // CRITICAL: Extract and set active_role from the new token
                try {
                    const payload = JSON.parse(atob(serverToken.access_token.split('.')[1]));
                    
                    // If the token has an active_role claim, use it
                    if (payload.active_role) {
                        localStorage.setItem('active_role', JSON.stringify(payload.active_role));
                        console.log('[Server Refresh] Active role updated from token', payload.active_role);
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
                        console.log('[Server Refresh] Active role inferred from single role', activeRole);
                    }
                } catch (e) {
                    console.error('[Server Refresh] Failed to parse active role from new token:', e);
                }

                console.log('[Server Refresh] Token injected into localStorage', {
                    expiresIn: serverToken.expires_in,
                    expiryTimestamp
                });
            })();
        @endif

        // IMPORTANT: Handle token from login.blade.php that was saved directly to localStorage
        // without expiry metadata. This ensures TokenManager can properly track and refresh the token.
        (function setupTokenInitial() {
            const rawAccessToken = localStorage.getItem('access_token');
            const tokenExpiry = localStorage.getItem('helpdesk_token_expiry');

            if (rawAccessToken && !tokenExpiry) {
                // Token exists but without expiry info - try to decode JWT first
                try {
                    const payload = JSON.parse(atob(rawAccessToken.split('.')[1]));
                    if (payload.exp) {
                        const expiryTimestamp = payload.exp * 1000; // Convert to ms
                        const now = Date.now();

                        localStorage.setItem('helpdesk_token_expiry', expiryTimestamp.toString());
                        localStorage.setItem('helpdesk_token_issued_at', now.toString()); // Approx
                        console.log('[TokenManager Setup] Initialized token metadata from JWT exp claim');
                        return;
                    }
                } catch (e) {
                    console.error('[TokenManager Setup] Failed to decode JWT for expiry:', e);
                }

                // Fallback if decoding fails
                const now = Date.now();
                const defaultTTL = 3600; // 1 hour in seconds
                const expiryTimestamp = now + (defaultTTL * 1000);

                localStorage.setItem('helpdesk_token_expiry', expiryTimestamp.toString());
                localStorage.setItem('helpdesk_token_issued_at', now.toString());
                console.log('[TokenManager Setup] Initialized token metadata with default TTL (3600s)');
            }
        })();

        // IMPORTANT: Define getUserFromJWT globally BEFORE navbar tries to use it
        // This is needed because navbar.blade.php is rendered synchronously
        window.getUserFromJWT = function () {
            const token = localStorage.getItem('access_token');
            const expiryTimestamp = localStorage.getItem('helpdesk_token_expiry');

            if (!token || !expiryTimestamp) return null;

            // Check if token is expired
            const now = Date.now();
            const expiry = parseInt(expiryTimestamp, 10);
            if (now >= expiry) {
                console.log('[getUserFromJWT] Token expired');
                return null;
            }

            try {
                const payload = JSON.parse(atob(token.split('.')[1]));

                // Get active role from localStorage (set during login)
                let activeRole = null;
                const activeRoleStr = localStorage.getItem('active_role');
                if (activeRoleStr) {
                    try {
                        activeRole = JSON.parse(activeRoleStr);
                    } catch (e) {
                        console.error('[getUserFromJWT] Error parsing active_role:', e);
                    }
                }

                return {
                    name: payload.name || 'User',
                    email: payload.email || '',
                    activeRole: activeRole,
                };
            } catch (error) {
                console.error('[getUserFromJWT] Error parsing JWT:', error);
                return null;
            }
        };
    </script>

    {{-- JWT Token Manager --}}
    <script type="module">
        import tokenManager from '/js/lib/auth/TokenManager.js?v={{ time() }}';

        console.log('[Dashboard] TokenManager module loaded', tokenManager);

        // Make tokenManager available globally (already instantiated in module)
        window.tokenManager = tokenManager;

        // Add global logout function
        window.logout = async function () {
            console.log('[LOGOUT FRONTEND] Logout initiated');
            
            try {
                const token = tokenManager.getAccessToken();
                
                console.log('[LOGOUT FRONTEND] Current token state:', {
                    hasToken: !!token,
                    tokenLength: token ? token.length : 0,
                    localStorageKeys: Object.keys(localStorage),
                });

                if (token) {
                    console.log('[LOGOUT FRONTEND] Calling logout API');
                    
                    const response = await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                        },
                    });
                    
                    console.log('[LOGOUT FRONTEND] Logout API response:', {
                        status: response.status,
                        ok: response.ok,
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        console.log('[LOGOUT FRONTEND] Logout API response data:', data);
                    }
                } else {
                    console.warn('[LOGOUT FRONTEND] No token available, skipping API call');
                }

                // Clear tokens
                console.log('[LOGOUT FRONTEND] Clearing tokens from TokenManager');
                tokenManager.clearTokens();
                
                // Also clear any other auth-related items
                console.log('[LOGOUT FRONTEND] Clearing additional localStorage items');
                localStorage.removeItem('active_role');
                localStorage.removeItem('last_email');
                
                console.log('[LOGOUT FRONTEND] localStorage after cleanup:', Object.keys(localStorage));

                // Redirect to login
                console.log('[LOGOUT FRONTEND] Redirecting to /login');
                window.location.href = '/login';
            } catch (error) {
                console.error('[LOGOUT FRONTEND] Logout error:', error);
                // Clear tokens anyway
                console.log('[LOGOUT FRONTEND] Error occurred, clearing tokens anyway');
                tokenManager.clearTokens();
                localStorage.removeItem('active_role');
                localStorage.removeItem('last_email');
                window.location.href = '/login';
            }
        };

        // Check authentication on page load
        document.addEventListener('DOMContentLoaded', function () {
            const token = tokenManager.getAccessToken();
            console.log('[Auth Check] Token available:', !!token);
            // GUARDIA DE FRONTEND:
            // Si no hay token en JS, pero estamos en una página protegida (lo cual significa que el servidor nos dejó entrar),
            // entonces hay una desincronización. Redirigimos a la "Aduana" (/) para que nos resincronice.
            if (!token) {
                console.warn('[Auth Check] No valid token found in localStorage. Redirecting to Auth Loader for resync.');
                // Pasamos la URL actual para volver después
                window.location.href = '/?redirect_to=' + encodeURIComponent(window.location.href);
            }
        });
    </script>

    {{-- Ekko Lightbox Initialization --}}
    <script>
        $(document).on('click', '[data-toggle="lightbox"]', function (event) {
            event.preventDefault();
            $(this).ekkoLightbox({
                alwaysShowClose: true,
                wrapping: true
            });
        });
    </script>

    {{-- Custom Scripts --}}
    @stack('scripts')
    @yield('js')
</body>

</html>