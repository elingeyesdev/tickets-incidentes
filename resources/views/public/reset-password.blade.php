@extends('layouts.auth')

@section('title', 'Resetear Contraseña')

@section('content')
<div class="card">
    <div class="card-header text-center">
        <h4 class="mb-0">
            <i class="fas fa-redo me-2"></i> Resetear Contraseña
        </h4>
    </div>

    <div class="card-body p-4">
        <div id="alerts"></div>

        <p class="text-muted mb-4">
            Ingresa tu nueva contraseña para completar el proceso de recuperación.
        </p>

        <form id="resetPasswordForm">
            <div class="mb-3">
                <label for="token" class="form-label">Token de Recuperación</label>
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
                <p class="text-muted" id="tokenDisplay">
                    <i class="fas fa-check-circle text-success me-2"></i> Token recibido correctamente
                </p>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Nueva Contraseña</label>
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

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i> Resetear Contraseña
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center text-muted">
            <p class="mb-0"><a href="{{ route('login') }}">Volver a Iniciar Sesión</a></p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
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
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> No se encontró el token de recuperación. Verifica el enlace en tu email.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        return;
    }

    // Validar que las contraseñas coincidan
    if (password !== passwordConfirmation) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> Las contraseñas no coinciden
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';

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
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> ¡Contraseña actualizada exitosamente! Redirigiendo...
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Redirigir al dashboard después de 1.5 segundos
        setTimeout(() => {
            window.location.href = '{{ route('dashboard') }}';
        }, 1500);

    } catch (error) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i> Resetear Contraseña';
    }
});
</script>
@endsection
