<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Helpdesk')</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css">

    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">

    <!-- Bootstrap Color Picker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.5.3/css/bootstrap-colorpicker.min.css">

    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">

    <!-- Bootstrap4 Duallistbox -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap4-duallistbox/4.0.2/bootstrap-duallistbox.min.css">

    <!-- BS Stepper -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bs-stepper/1.7.0/css/bs-stepper.min.css">

    <!-- dropzonejs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">

    <!-- AdminLTE CSS v3 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    <!-- DataTables Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/css/dataTables.bootstrap4.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.27/sweetalert2.min.css">

    @yield('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('dashboard') }}" class="nav-link">
                        <i class="fas fa-home mr-2"></i> Inicio
                    </a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- User Account Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown" id="userMenu">
                        <div class="dropdown-header">Cargando...</div>
                    </div>
                </li>

                <!-- Fullscreen Toggle -->
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Left side column. contains the sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="{{ route('dashboard') }}" class="brand-link">
                <img src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light"><strong>Helpdesk</strong></span>
            </a>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" id="sidebarMenu">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- Users Management -->
                    <li class="nav-item" id="usersMenuParent" style="display: none;">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Gestionar Usuarios</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Companies Management -->
                    <li class="nav-item" id="companiesMenuParent" style="display: none;">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p>Empresas <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.companies') }}" class="nav-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Gestionar Empresas</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-header">Cuenta</li>

                    <!-- Profile -->
                    <li class="nav-item">
                        <a href="{{ route('profile.index') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Mi Perfil</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('header')</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                                <li class="breadcrumb-item active">@yield('breadcrumb', 'Página')</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="content">
                <div class="container-fluid">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Éxito!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>

        <!-- Control Sidebar (optional) -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
    </div>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <!-- jQuery UI 1.11.4 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui/1.13.2/jquery-ui.min.js"></script>

    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>

    <!-- Bootstrap 4 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>

    <!-- overlayScrollbars -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/2.3.0/browser/overlayscrollbars.browser.es6.min.js"></script>

    <!-- AdminLTE App -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

    <!-- DataTables & Plugins -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.27/sweetalert2.min.js"></script>

    <script>
        const API_URL = '{{ env('APP_URL', 'http://localhost:8000') }}/api';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Helper para hacer requests a la API
        async function apiRequest(endpoint, method = 'GET', data = null) {
            const token = localStorage.getItem('accessToken');

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

            if (response.status === 401) {
                localStorage.removeItem('accessToken');
                sessionStorage.removeItem('accessToken');
                Swal.fire('Sesión Expirada', 'Por favor, inicia sesión nuevamente.', 'warning').then(() => {
                    window.location.href = '{{ route('login') }}';
                });
                return;
            }

            if (!response.ok) {
                const result = await response.json();
                throw new Error(result.message || result.errors?.message || `Error ${response.status}`);
            }

            return await response.json();
        }

        // Cargar menú dinámico según roles
        async function loadUserMenuItems() {
            try {
                const token = localStorage.getItem('accessToken');
                if (!token) {
                    window.location.href = '{{ route('login') }}';
                    return;
                }

                const response = await apiRequest('/auth/status');
                const user = response.user;

                // Mostrar items admin solo si tiene rol de admin
                const isAdmin = user.roleContexts.some(r =>
                    r.roleCode === 'PLATFORM_ADMIN' || r.roleCode === 'COMPANY_ADMIN'
                );

                if (isAdmin) {
                    document.getElementById('usersMenuParent').style.display = '';
                    document.getElementById('companiesMenuParent').style.display = '';
                }

                // Actualizar nombre en menú de usuario
                document.getElementById('userMenu').innerHTML = `
                    <div class="dropdown-header">${user.displayName}</div>
                    <small class="dropdown-item text-muted">${user.email}</small>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('profile.index') }}">
                        <i class="fas fa-user mr-2"></i> Perfil
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="fas fa-cog mr-2"></i> Configuración
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="logout()">
                        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                    </a>
                `;

            } catch (error) {
                console.error('Error cargando datos de usuario:', error);
            }
        }

        async function logout() {
            const result = await Swal.fire({
                title: '¿Cerrar sesión?',
                text: '¿Está seguro que desea cerrar sesión?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                try {
                    const token = localStorage.getItem('accessToken');
                    if (token) {
                        await apiRequest('/auth/logout', 'POST');
                    }
                } catch (e) {
                    console.error('Error al cerrar sesión:', e);
                }

                localStorage.removeItem('accessToken');
                sessionStorage.removeItem('accessToken');
                window.location.href = '{{ route('login') }}';
            }
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', loadUserMenuItems);
    </script>

    @yield('scripts')
</body>

</html>
