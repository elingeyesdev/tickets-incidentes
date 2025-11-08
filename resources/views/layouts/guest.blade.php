<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Helpdesk')</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Custom CSS -->
    @yield('css')

    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
        }
    </style>
</head>
<body class="guest-page">
    <div class="wrapper d-flex flex-column" style="min-height: 100vh;">
        <!-- Navbar - Zona Pública -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <img src="{{ asset('logo.png') }}" alt="Helpdesk" height="40" onerror="this.style.display='none'">
                    <strong>HELPDESK</strong>
                </a>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('/') ? 'active' : '' }}" href="/">
                                <i class="fas fa-home mr-1"></i> Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('register*') ? 'active' : '' }}" href="{{ route('register') }}">
                                <i class="fas fa-user-plus mr-1"></i> Registro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('login*') ? 'active' : '' }}" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt mr-1"></i> Login
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer - Zona Pública -->
        <footer class="bg-dark text-white mt-5">
            <div class="container py-4">
                <div class="row">
                    <div class="col-md-6">
                        <h5>HELPDESK</h5>
                        <p class="text-muted">Sistema profesional de gestión de incidentes para empresas.</p>
                    </div>
                    <div class="col-md-3">
                        <h6>Enlaces rápidos</h6>
                        <ul class="list-unstyled small">
                            <li><a href="/" class="text-muted text-decoration-none">Inicio</a></li>
                            <li><a href="{{ route('register') }}" class="text-muted text-decoration-none">Registro</a></li>
                            <li><a href="{{ route('password.request') }}" class="text-muted text-decoration-none">Recuperar contraseña</a></li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h6>Información</h6>
                        <ul class="list-unstyled small">
                            <li><a href="#" class="text-muted text-decoration-none">Privacidad</a></li>
                            <li><a href="#" class="text-muted text-decoration-none">Términos de servicio</a></li>
                            <li><a href="#" class="text-muted text-decoration-none">Contacto</a></li>
                        </ul>
                    </div>
                </div>
                <hr class="bg-secondary">
                <div class="text-center">
                    <p class="mb-0 text-muted small">&copy; {{ date('Y') }} HELPDESK. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

    <!-- Alpine.js from CDN (development) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @yield('js')
</body>
</html>
