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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">

    {{-- Ekko Lightbox --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/5.3.0/ekko-lightbox.css">

    {{-- Google Font: Source Sans Pro --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

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
                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-ban"></i> Validation Errors!</h5>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Success Message --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Success!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Warning Message --}}
                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
                            {{ session('warning') }}
                        </div>
                    @endif

                    {{-- Info Message --}}
                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-info"></i> Info!</h5>
                            {{ session('info') }}
                        </div>
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
        // IMPORTANT: Handle token from login.blade.php that was saved directly to localStorage
        // without expiry metadata. This ensures TokenManager can properly track and refresh the token.
        (function setupTokenInitial() {
            const rawAccessToken = localStorage.getItem('access_token');
            const tokenExpiry = localStorage.getItem('helpdesk_token_expiry');

            if (rawAccessToken && !tokenExpiry) {
                // Token exists but without expiry info - compute and store expiry now
                // This handles the case where login.blade.php saves the token directly
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
        window.getUserFromJWT = function() {
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
        import tokenManager from '/js/lib/auth/TokenManager.js';

        // Make tokenManager available globally (already instantiated in module)
        window.tokenManager = tokenManager;

        // Add global logout function
        window.logout = async function() {
            try {
                const token = tokenManager.getAccessToken();

                if (token) {
                    await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                        },
                    });
                }

                // Clear tokens
                tokenManager.clearTokens();

                // Redirect to login
                window.location.href = '/login';
            } catch (error) {
                console.error('Logout error:', error);
                // Clear tokens anyway
                tokenManager.clearTokens();
                window.location.href = '/login';
            }
        };

        // Check authentication on page load
        document.addEventListener('DOMContentLoaded', function() {
            const token = tokenManager.getAccessToken();
            console.log('[Auth Check] Token available:', !!token);
            if (!token) {
                console.log('[Auth Check] No valid token found, redirecting to login');
                window.location.href = '/login?reason=session_expired';
            }
        });
    </script>

    {{-- Ekko Lightbox Initialization --}}
    <script>
    $(document).on('click', '[data-toggle="lightbox"]', function(event) {
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