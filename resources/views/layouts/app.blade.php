<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Helpdesk') }} - @yield('title')</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        .main-sidebar {
            background-color: #2c3e50;
        }
        .brand-link {
            background-color: #34495e;
            border-bottom: 1px solid #1a252f;
        }
        .nav-link.active {
            background-color: #667eea;
            border-left: 4px solid #667eea;
        }
        .nav-link:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
        .content-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
        }
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #667eea;
            border-color: #667eea;
        }
        .btn-primary:hover {
            background-color: #5568d3;
            border-color: #5568d3;
        }
    </style>

    @yield('styles')
</head>
<body class="hold-transition light-mode sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <button class="navbar-toggler order-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a href="{{ route('profile.index') }}" class="nav-link">
                                <i class="fas fa-user"></i> Mi Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" onclick="logout()" class="nav-link">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar">
            <div class="sidebar">
                <div class="brand-link d-flex align-items-center justify-content-between px-3 py-3">
                    <a href="{{ route('dashboard') }}" class="text-white text-decoration-none">
                        <i class="fas fa-headset me-2"></i>
                        <strong>Helpdesk</strong>
                    </a>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-home"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- Usuarios (Admin) -->
                        @if(auth()->check() && (auth()->user()->hasRole('PLATFORM_ADMIN') || auth()->user()->hasRole('COMPANY_ADMIN')))
                            <li class="nav-item">
                                <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Usuarios</p>
                                </a>
                            </li>
                        @endif

                        <!-- Empresas (Admin) -->
                        @if(auth()->check() && (auth()->user()->hasRole('PLATFORM_ADMIN') || auth()->user()->hasRole('COMPANY_ADMIN')))
                            <li class="nav-item">
                                <a href="{{ route('admin.companies') }}" class="nav-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-building"></i>
                                    <p>Empresas</p>
                                </a>
                            </li>
                        @endif

                        <!-- Perfil -->
                        <li class="nav-item">
                            <a href="{{ route('profile.index') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-circle"></i>
                                <p>Mi Perfil</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content -->
        <div class="content-wrapper">
            <div class="content-header">
                <h1 class="mb-0">@yield('header')</h1>
            </div>

            <div class="content">
                <div class="container-fluid">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5 class="alert-heading">Error!</h5>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        const API_URL = '{{ env('APP_URL', 'http://localhost:8000') }}/api';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Configurar headers por defecto para todas las requests
        async function apiRequest(endpoint, method = 'GET', data = null) {
            // Obtener token del localStorage o sessionStorage
            const token = localStorage.getItem('accessToken') || sessionStorage.getItem('accessToken');

            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                credentials: 'include',
            };

            if (token) {
                options.headers['Authorization'] = `Bearer ${token}`;
            }

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(`${API_URL}${endpoint}`, options);

            // Si es 401, redirigir a login
            if (response.status === 401) {
                localStorage.removeItem('accessToken');
                sessionStorage.removeItem('accessToken');
                window.location.href = '{{ route('login') }}';
                return;
            }

            if (!response.ok) {
                const result = await response.json();
                throw new Error(result.message || `Error ${response.status}`);
            }

            return await response.json();
        }

        async function logout() {
            if (!confirm('¿Está seguro que desea cerrar sesión?')) {
                return;
            }

            try {
                // Hacer logout en el servidor
                const token = localStorage.getItem('accessToken');
                if (token) {
                    await apiRequest('/auth/logout', 'POST');
                }
            } catch (e) {
                console.error('Error al cerrar sesión:', e);
            }

            // Limpiar datos locales
            localStorage.removeItem('accessToken');
            sessionStorage.removeItem('accessToken');
            window.location.href = '{{ route('login') }}';
        }

        function showError(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.content').insertBefore(alertDiv, document.querySelector('.content').firstChild);
        }

        function showSuccess(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.content').insertBefore(alertDiv, document.querySelector('.content').firstChild);
        }
    </script>

    @yield('scripts')
</body>
</html>
