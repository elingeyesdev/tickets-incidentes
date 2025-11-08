<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Helpdesk</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">

    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Custom CSS -->
    @yield('css')
</head>
<body class="hold-transition layout-top-nav" x-data="authStore()" x-init="init()">
    <div class="wrapper">
        <!-- Navbar -->
        @include('app.components.navbar')

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('content_header')</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                @yield('breadcrumbs')
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="content">
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

                    <!-- Alpine.js Error Display -->
                    <div x-show="error" x-cloak>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" @click="clearError()" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <span x-text="error"></span>
                        </div>
                    </div>

                    <!-- Loading Overlay -->
                    <div x-show="loading" x-cloak>
                        <div class="overlay">
                            <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                        </div>
                    </div>

                    <!-- Page Content -->
                    @yield('content')
                </div>
            </div>
        </div>

        <!-- Footer -->
        @include('app.components.footer')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    <script src="{{ mix('js/app.js') }}"></script>

    <!-- Alpine.js x-cloak style -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @yield('js')
</body>
</html>
