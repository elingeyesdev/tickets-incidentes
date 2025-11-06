@extends('layouts.auth')

@section('title', 'Registrarse')

@section('content')
<div class="register-page">
    <div class="register-box">
        <div class="card card-outline card-success">
            <div class="card-header text-center">
                <a href="{{ route('home') }}" class="h1"><b>Help</b>Desk</a>
            </div>

            <div class="card-body">
                <p class="login-box-msg">Crear nueva cuenta</p>

                <div id="alerts"></div>

                <form id="registerForm">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <div class="input-group mb-3">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="firstName"
                                        name="firstName"
                                        placeholder="Nombre"
                                        required
                                        minlength="2"
                                    >
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                            <span class="fas fa-user"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <div class="input-group mb-3">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="lastName"
                                        name="lastName"
                                        placeholder="Apellido"
                                        required
                                        minlength="2"
                                    >
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                            <span class="fas fa-user"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                        <small class="text-muted">Usarás este email para iniciar sesión</small>
                    </div>

                    <div class="form-group">
                        <div class="input-group mb-3">
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Contraseña"
                                required
                                minlength="8"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Mínimo 8 caracteres, debe contener letras, números y símbolos</small>
                    </div>

                    <div class="form-group">
                        <div class="input-group mb-3">
                            <input
                                type="password"
                                class="form-control"
                                id="passwordConfirmation"
                                name="passwordConfirmation"
                                placeholder="Confirmar contraseña"
                                required
                                minlength="8"
                            >
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="icheck-primary">
                            <input
                                type="checkbox"
                                id="acceptsTerms"
                                name="acceptsTerms"
                                required
                            >
                            <label for="acceptsTerms">
                                Acepto los <a href="#" target="_blank">términos de servicio</a>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="icheck-primary">
                            <input
                                type="checkbox"
                                id="acceptsPrivacyPolicy"
                                name="acceptsPrivacyPolicy"
                                required
                            >
                            <label for="acceptsPrivacyPolicy">
                                Acepto la <a href="#" target="_blank">política de privacidad</a>
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-user-plus mr-2"></i> Crear Cuenta
                            </button>
                        </div>
                    </div>
                </form>

                <hr>

                <p class="mb-0 text-center">
                    <a href="{{ route('login') }}" class="text-center">Ya tengo cuenta</a>
                </p>
            </div>
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
