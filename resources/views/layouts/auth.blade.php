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

    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">

    <!-- AdminLTE CSS v3 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.27/sweetalert2.min.css">

    <style>
        html, body {
            height: 100%;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        .login-page, .register-page, .reset-password-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-box, .register-box, .reset-password-box {
            width: 100%;
            max-width: 400px;
        }
        .box-body {
            padding: 1.5rem;
        }
    </style>

    @yield('styles')
</head>

<body>
    @yield('content')

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <!-- Bootstrap 4 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>

    <!-- AdminLTE App -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.27/sweetalert2.min.js"></script>

    <script>
        const API_URL = '{{ env('APP_URL', 'http://localhost:8000') }}/api';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Helper para hacer requests
        async function apiRequest(endpoint, method = 'GET', data = null) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                credentials: 'include',
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(`${API_URL}${endpoint}`, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || result.errors?.message || 'Error en la solicitud');
            }

            return result;
        }

        // Mostrar mensajes
        function showError(message) {
            Swal.fire('Error', message, 'error');
        }

        function showSuccess(message) {
            Swal.fire('Éxito', message, 'success');
        }
    </script>

    @yield('scripts')
</body>

</html>
