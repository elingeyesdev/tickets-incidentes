<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Onboarding') - Helpdesk</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Custom CSS -->
    @yield('css')

    <style>
        .onboarding-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="onboarding-page login-page">
    <div class="login-box" style="width: 100%; max-width: 450px; margin: auto;">
        <!-- Card -->
        <div class="card card-outline card-primary">
            <!-- Card Header -->
            <div class="card-header text-center">
                <div class="mb-2">
                    <img src="{{ asset('logo.png') }}" alt="Helpdesk" height="50" onerror="this.style.display='none'">
                </div>
                <h4 class="mt-2 mb-1">@yield('step_title', 'Setup')</h4>
                <p class="text-muted small mb-0">@yield('step_subtitle')</p>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Errors!</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <i class="icon fas fa-check"></i> {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </div>

            <!-- Card Footer -->
            <div class="card-footer">
                @yield('footer_actions')
            </div>
        </div>

        <!-- Additional Info -->
        <div class="text-center mt-3 text-white">
            @yield('additional_info')
        </div>
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
