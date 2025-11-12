<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

    <title>@yield('title', 'Dashboard') - HELPDESK</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">

    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Custom CSS -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @yield('css')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        @include('app.shared.navbar')

        <!-- Main Sidebar Container -->
        @include('app.shared.sidebar')

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
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

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Error Messages -->
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

                    <!-- Success Message -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Success!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Warning Message -->
                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
                            {{ session('warning') }}
                        </div>
                    @endif

                    <!-- Info Message -->
                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-info"></i> Info!</h5>
                            {{ session('info') }}
                        </div>
                    @endif

                    <!-- Page Content -->
                    @yield('content')
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; 2025 <a href="/">HELPDESK</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 1.0.0
            </div>
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
    </div>

    <!-- Scripts -->
    <!-- jQuery -->
    <script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('vendor/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- jQuery Validation (AdminLTE v3 Official Plugin - Composer) -->
    <script src="{{ asset('vendor/adminlte/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/plugins/jquery-validation/additional-methods.min.js') }}"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- JWT Token Manager Setup (synchronous) -->
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

    <!-- JWT Token Manager -->
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

    @yield('js')
</body>
</html>
