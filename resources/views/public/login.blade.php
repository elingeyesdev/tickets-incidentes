@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="login-page">
    <div class="login-box">
        <div class="card card-outline card-primary">
            <!-- Logo Header -->
            <div class="card-header text-center">
                <a href="{{ url('/') }}" class="h1"><b>Help</b>Desk</a>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <p class="login-box-msg">Inicia sesión para comenzar tu sesión</p>

                <!-- Alerts Container -->
                <div id="alerts"></div>

                <!-- Login Form -->
                <form id="loginForm">
                    <!-- Email Input Group -->
                    <div class="form-group">
                        <div class="input-group mb-3">
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="Email"
                                required
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted small">Usa el correo registrado en tu cuenta</small>
                    </div>

                    <!-- Password Input Group -->
                    <div class="form-group">
                        <div class="input-group mb-3">
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Contraseña"
                                required
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input
                                    type="checkbox"
                                    id="rememberMe"
                                    name="rememberMe"
                                >
                                <label for="rememberMe">
                                    Recuérdame
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">
                                Ingresar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Divider -->
                <p class="mb-1 mt-3">
                    <a href="{{ route('password.request') }}">Olvidé mi contraseña</a>
                </p>
                <p class="mb-0">
                    <a href="{{ route('register') }}" class="text-center">Registrar una nueva cuenta</a>
                </p>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    <!-- /.login-box -->
</div>
<!-- /.login-page -->

@endsection

@section('scripts')
<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const alertsDiv = document.getElementById('alerts');

    try {
        const response = await apiRequest('/auth/login', 'POST', {
            email: email,
            password: password,
            rememberMe: document.getElementById('rememberMe').checked
        });

        // Guardar token
        localStorage.setItem('accessToken', response.accessToken);

        // Mostrar éxito y redirigir
        alertsDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> Bienvenido, ${response.user.displayName}!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Redirigir al dashboard después de 1.5 segundos
        setTimeout(() => {
            // Verificar el rol para redirigir al dashboard correcto
            const primaryRole = response.user.roleContexts[0];
            if (primaryRole) {
                window.location.href = primaryRole.dashboardPath || '{{ route('dashboard') }}';
            } else {
                window.location.href = '{{ route('dashboard') }}';
            }
        }, 1500);

    } catch (error) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
});
</script>
@endsection
