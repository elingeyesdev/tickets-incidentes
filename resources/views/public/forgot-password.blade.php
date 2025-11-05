@extends('layouts.auth')

@section('title', 'Recuperar Contraseña')

@section('content')
<div class="card">
    <div class="card-header text-center">
        <h4 class="mb-0">
            <i class="fas fa-key me-2"></i> Recuperar Contraseña
        </h4>
    </div>

    <div class="card-body p-4">
        <div id="alerts"></div>

        <p class="text-muted mb-4">
            Ingresa tu email y te enviaremos un enlace para resetear tu contraseña.
        </p>

        <form id="forgotPasswordForm">
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
                <small class="text-muted d-block mt-1">Debes usar el email registrado en tu cuenta</small>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-paper-plane me-2"></i> Enviar Enlace de Recuperación
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center text-muted">
            <p class="mb-0"><a href="{{ route('login') }}">Volver a Iniciar Sesión</a></p>
            <p class="mb-0 mt-2">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate aquí</a></p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const alertsDiv = document.getElementById('alerts');
    const submitBtn = this.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Enviando...';

    try {
        const response = await apiRequest('/auth/password-reset', 'POST', {
            email: email
        });

        alertsDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <strong>¡Listo!</strong> Si el email existe en nuestro sistema, recibirás un enlace para resetear tu contraseña en los próximos minutos.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Limpiar el formulario
        document.getElementById('forgotPasswordForm').reset();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Enviar Enlace de Recuperación';

    } catch (error) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Enviar Enlace de Recuperación';
    }
});
</script>
@endsection
