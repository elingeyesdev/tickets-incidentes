@extends('layouts.auth')

@section('title', 'Registrarse')

@section('content')
<div class="card">
    <div class="card-header text-center">
        <h4 class="mb-0">
            <i class="fas fa-user-plus me-2"></i> Crear Cuenta
        </h4>
    </div>

    <div class="card-body p-4">
        <div id="alerts"></div>

        <form id="registerForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="firstName" class="form-label">Nombre</label>
                    <input
                        type="text"
                        class="form-control"
                        id="firstName"
                        name="firstName"
                        placeholder="Juan"
                        required
                        minlength="2"
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label for="lastName" class="form-label">Apellido</label>
                    <input
                        type="text"
                        class="form-control"
                        id="lastName"
                        name="lastName"
                        placeholder="Pérez"
                        required
                        minlength="2"
                    >
                </div>
            </div>

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
                <small class="text-muted d-block mt-1">Usarás este email para iniciar sesión</small>
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
                    minlength="8"
                >
                <small class="text-muted d-block mt-1">Mínimo 8 caracteres, debe contener letras, números y símbolos</small>
            </div>

            <div class="mb-3">
                <label for="passwordConfirmation" class="form-label">Confirmar Contraseña</label>
                <input
                    type="password"
                    class="form-control"
                    id="passwordConfirmation"
                    name="passwordConfirmation"
                    placeholder="••••••••"
                    required
                    minlength="8"
                >
            </div>

            <div class="mb-3 form-check">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="acceptsTerms"
                    name="acceptsTerms"
                    required
                >
                <label class="form-check-label" for="acceptsTerms">
                    Acepto los <a href="#" target="_blank">términos de servicio</a>
                </label>
            </div>

            <div class="mb-3 form-check">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="acceptsPrivacyPolicy"
                    name="acceptsPrivacyPolicy"
                    required
                >
                <label class="form-check-label" for="acceptsPrivacyPolicy">
                    Acepto la <a href="#" target="_blank">política de privacidad</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-user-plus me-2"></i> Crear Cuenta
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center text-muted">
            <p class="mb-0">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión aquí</a></p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        firstName: document.getElementById('firstName').value,
        lastName: document.getElementById('lastName').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        passwordConfirmation: document.getElementById('passwordConfirmation').value,
        acceptsTerms: document.getElementById('acceptsTerms').checked,
        acceptsPrivacyPolicy: document.getElementById('acceptsPrivacyPolicy').checked,
    };

    const alertsDiv = document.getElementById('alerts');

    // Validar contraseñas coinciden
    if (formData.password !== formData.passwordConfirmation) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> Las contraseñas no coinciden
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        return;
    }

    try {
        const response = await apiRequest('/auth/register', 'POST', formData);

        // Guardar token
        localStorage.setItem('accessToken', response.accessToken);

        alertsDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> ¡Bienvenido, ${response.user.displayName}! Redirigiendo...
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Redirigir a verificación de email o dashboard
        setTimeout(() => {
            if (!response.user.emailVerified) {
                window.location.href = '{{ route('verify.email') }}';
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
