@extends('layouts.auth')

@section('title', 'Resetear Contraseña')

@section('content')
<div class="login-page">
    <div class="login-box">
        <div class="card card-outline card-info">
            <div class="card-header text-center">
                <a href="{{ route('login') }}" class="h1">
                    <i class="fas fa-ticket-alt"></i> <b>Help</b>Desk
                </a>
            </div>

            <div class="card-body">
                <p class="login-box-msg">
                    <i class="fas fa-redo text-info mr-2"></i>
                    Ingresa tu nueva contraseña
                </p>

                <div id="alerts"></div>

                <!-- Token Status Display -->
                <div class="alert alert-success" id="tokenStatus">
                    <h5><i class="icon fas fa-check-circle"></i> Token Verificado</h5>
                    Token de recuperación recibido correctamente
                </div>

                <form id="resetPasswordForm">
                    <!-- Hidden Token Fields -->
                    <input
                        type="hidden"
                        id="token"
                        name="token"
                        value="{{ request()->query('token') }}"
                    >
                    <input
                        type="hidden"
                        id="code"
                        name="code"
                        value="{{ request()->query('code') }}"
                    >

                    <!-- Password Requirements Info -->
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle"></i> Requisitos de Contraseña:</h5>
                        <ul class="mb-0 pl-3">
                            <li>Mínimo 8 caracteres</li>
                            <li>Debe contener letras (mayúsculas y minúsculas)</li>
                            <li>Debe contener números</li>
                            <li>Debe contener símbolos especiales</li>
                        </ul>
                    </div>

                    <!-- New Password Input -->
                    <div class="input-group mb-3">
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Nueva contraseña"
                            required
                            minlength="8"
                        >
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Input -->
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

                    <!-- Password Match Indicator -->
                    <div class="mb-3" id="passwordMatchIndicator" style="display: none;">
                        <small class="text-danger">
                            <i class="fas fa-times-circle"></i> Las contraseñas no coinciden
                        </small>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-info btn-block">
                                <i class="fas fa-save mr-2"></i> Resetear Contraseña
                            </button>
                        </div>
                    </div>
                </form>

                <hr class="my-3">

                <!-- Back to Login Link -->
                <p class="mb-0 text-center">
                    <a href="{{ route('login') }}" class="text-center">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a Iniciar Sesión
                    </a>
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
// Verificar token al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const token = document.getElementById('token').value;
    const code = document.getElementById('code').value;
    const tokenStatus = document.getElementById('tokenStatus');
    const alertsDiv = document.getElementById('alerts');

    if (!token && !code) {
        tokenStatus.classList.remove('alert-success');
        tokenStatus.classList.add('alert-danger');
        tokenStatus.innerHTML = `
            <h5><i class="icon fas fa-times-circle"></i> Token No Encontrado</h5>
            No se encontró el token de recuperación. Verifica el enlace en tu email.
        `;
        document.getElementById('resetPasswordForm').querySelector('button[type="submit"]').disabled = true;
    }
});

// Validación en tiempo real de coincidencia de contraseñas
document.getElementById('passwordConfirmation').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const passwordConfirmation = this.value;
    const indicator = document.getElementById('passwordMatchIndicator');

    if (passwordConfirmation.length > 0) {
        if (password !== passwordConfirmation) {
            indicator.style.display = 'block';
            indicator.className = 'mb-3';
            indicator.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle"></i> Las contraseñas no coinciden</small>';
        } else {
            indicator.style.display = 'block';
            indicator.className = 'mb-3';
            indicator.innerHTML = '<small class="text-success"><i class="fas fa-check-circle"></i> Las contraseñas coinciden</small>';
        }
    } else {
        indicator.style.display = 'none';
    }
});

// Validación de requisitos de contraseña
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const hasMinLength = password.length >= 8;
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSymbol = /[^a-zA-Z0-9]/.test(password);

    // Cambiar borde del input según validación
    if (password.length > 0) {
        if (hasMinLength && hasLetter && hasNumber && hasSymbol) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    } else {
        this.classList.remove('is-valid', 'is-invalid');
    }
});

// Manejar envío del formulario
document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const token = document.getElementById('token').value;
    const code = document.getElementById('code').value;
    const password = document.getElementById('password').value;
    const passwordConfirmation = document.getElementById('passwordConfirmation').value;
    const alertsDiv = document.getElementById('alerts');
    const submitBtn = this.querySelector('button[type="submit"]');

    // Validar que haya token o code
    if (!token && !code) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error</h5>
                No se encontró el token de recuperación. Verifica el enlace en tu email.
            </div>
        `;
        return;
    }

    // Validar que las contraseñas coincidan
    if (password !== passwordConfirmation) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error</h5>
                Las contraseñas no coinciden
            </div>
        `;
        return;
    }

    // Validar requisitos de contraseña
    const hasMinLength = password.length >= 8;
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSymbol = /[^a-zA-Z0-9]/.test(password);

    if (!hasMinLength || !hasLetter || !hasNumber || !hasSymbol) {
        alertsDiv.innerHTML = `
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-exclamation-triangle"></i> Advertencia</h5>
                La contraseña no cumple con los requisitos de seguridad
            </div>
        `;
        return;
    }

    // Deshabilitar botón y mostrar loading
    submitBtn.disabled = true;
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...';

    try {
        const payload = {
            password: password,
            passwordConfirmation: passwordConfirmation,
        };

        if (token) {
            payload.token = token;
        } else if (code) {
            payload.code = code;
        }

        const response = await apiRequest('/auth/password-reset/confirm', 'POST', payload);

        // Guardar token si el reset fue exitoso
        if (response.accessToken) {
            localStorage.setItem('accessToken', response.accessToken);
        }

        alertsDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-check"></i> Éxito</h5>
                ¡Contraseña actualizada exitosamente! Redirigiendo al dashboard...
            </div>
        `;

        // Redirigir al dashboard después de 1.5 segundos
        setTimeout(() => {
            window.location.href = '{{ route('dashboard') }}';
        }, 1500);

    } catch (error) {
        // Determinar tipo de error
        let errorTitle = 'Error';
        let errorMessage = error.message;

        if (error.message.includes('expirado') || error.message.includes('expired')) {
            errorTitle = 'Token Expirado';
            errorMessage = 'El token de recuperación ha expirado. Por favor, solicita un nuevo enlace de recuperación.';
        } else if (error.message.includes('inválido') || error.message.includes('invalid')) {
            errorTitle = 'Token Inválido';
            errorMessage = 'El token de recuperación no es válido. Verifica el enlace en tu email.';
        }

        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-ban"></i> ${errorTitle}</h5>
                ${errorMessage}
            </div>
        `;

        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;

        // Si el token expiró, ofrecer volver a solicitar recuperación
        if (error.message.includes('expirado') || error.message.includes('expired')) {
            setTimeout(() => {
                if (confirm('¿Deseas solicitar un nuevo enlace de recuperación?')) {
                    window.location.href = '{{ route('password.request') }}';
                }
            }, 2000);
        }
    }
});
</script>
@endsection
