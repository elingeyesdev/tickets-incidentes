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
            min-height: 100vh;
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

    {{-- Widget Token Manager --}}
    <script>
        // Token obtenido de la URL o parámetro
        (function() {
            'use strict';
            
            // Leer token de URL
            const urlParams = new URLSearchParams(window.location.search);
            const urlToken = urlParams.get('token');
            
            // Widget Token Manager (simplificado, solo para el widget)
            window.widgetTokenManager = {
                _token: urlToken || null,
                
                setToken: function(token) {
                    this._token = token;
                    console.log('[WidgetTokenManager] Token set');
                },
                
                getAccessToken: function() {
                    return this._token;
                },
                
                isAuthenticated: function() {
                    return !!this._token;
                },
                
                clearTokens: function() {
                    this._token = null;
                }
            };
            
            // Alias para compatibilidad con código existente
            window.tokenManager = window.widgetTokenManager;
            
            console.log('[Widget] Token manager initialized, authenticated:', window.widgetTokenManager.isAuthenticated());
        })();
    </script>

    @stack('scripts')
    @yield('js')
</body>

</html>
