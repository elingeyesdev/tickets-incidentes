@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="card">
    <div class="card-header text-center">
        <h4 class="mb-0">
            <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
        </h4>
    </div>

    <div class="card-body p-4">
        <div id="alerts"></div>

        <form id="loginForm">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="tu@email.com"
                    required
                >
                <small class="text-muted d-block mt-1">Usa el correo registrado en tu cuenta</small>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                >
            </div>

            <div class="mb-3 form-check">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="rememberMe"
                    name="rememberMe"
                >
                <label class="form-check-label" for="rememberMe">
                    Recuérdame
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center text-muted">
            <p class="mb-0">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate aquí</a></p>
            <p class="mb-0 mt-2"><a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a></p>
        </div>
    </div>
</div>

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
