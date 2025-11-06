@extends('layouts.auth')

@section('title', 'Recuperar Contraseña')

@section('content')
<div class="login-box">
    <div class="card card-outline card-warning">
        <div class="card-header text-center">
            <h4 class="mb-0">
                <i class="fas fa-key"></i> Recuperar Contraseña
            </h4>
        </div>

        <div class="card-body">
            <div id="alerts"></div>

            <p class="text-muted mb-3">
                Ingresa tu email y te enviaremos un enlace para resetear tu contraseña.
            </p>

            <form id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="tu@email.com"
                            required
                        >
                    </div>
                    <small class="form-text text-muted">Debes usar el email registrado en tu cuenta</small>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-warning btn-block" id="submitBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Enlace de Recuperación
                        </button>
                    </div>
                </div>
            </form>

            <hr class="my-3">

            <div class="text-center">
                <p class="mb-1">
                    <a href="{{ route('login') }}">
                        <i class="fas fa-sign-in-alt"></i> Volver a Iniciar Sesión
                    </a>
                </p>
                <p class="mb-0">
                    ¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate aquí</a>
                </p>
            </div>
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
    const submitBtn = document.getElementById('submitBtn');

    // Deshabilitar botón y mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
        const response = await apiRequest('/auth/password-reset', 'POST', {
            email: email
        });

        // Mensaje de seguridad: "Si el email existe..."
        alertsDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> ¡Listo!</h5>
                Si el email existe en nuestro sistema, recibirás un enlace para resetear tu contraseña en los próximos minutos.
            </div>
        `;

        // Limpiar el formulario
        document.getElementById('forgotPasswordForm').reset();

    } catch (error) {
        alertsDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Error</h5>
                ${error.message}
            </div>
        `;
    } finally {
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Enlace de Recuperación';
    }
});
</script>
@endsection
