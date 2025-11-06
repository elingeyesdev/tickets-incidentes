@extends('layouts.auth')

@section('title', 'Verificar Email')

@section('content')
<div class="login-page">
    <div class="login-box">
        <div class="card card-outline card-success">
            <div class="card-header text-center">
                <div class="mb-3">
                    <i class="fas fa-envelope-circle-check fa-3x text-success"></i>
                </div>
                <h3 class="mb-0">
                    <b>Verifica tu Email</b>
                </h3>
            </div>

            <div class="card-body">
                <div id="alerts"></div>

                <p class="text-muted text-center mb-4">
                    Hemos enviado un enlace de verificación a tu correo electrónico. Por favor, revisa tu bandeja de entrada e ingresa el token a continuación.
                </p>

                <!-- Input de token con botón -->
                <div class="mb-4">
                    <label class="form-label">Token de Verificación</label>
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            id="verificationCode"
                            placeholder="Pega tu token de verificación aquí"
                        >
                        <button class="btn btn-success" type="button" onclick="verifyCode()">
                            <i class="fas fa-check me-2"></i> Verificar
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Copia el token largo que recibiste por email
                    </small>
                </div>

                <hr class="my-4">

                <!-- Botón reenviar -->
                <div class="text-center mb-3">
                    <p class="text-muted mb-2">¿No recibiste el email?</p>
                    <button class="btn btn-outline-success btn-sm" onclick="resendEmail()">
                        <i class="fas fa-paper-plane me-2"></i> Reenviar Email
                    </button>
                </div>

                <!-- Link cambiar cuenta -->
                <div class="text-center">
                    <a href="#" class="text-muted" onclick="logout()">
                        <i class="fas fa-user-slash me-1"></i> Cambiar cuenta
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const alertsDiv = document.getElementById('alerts');

async function verifyCode() {
    const token = document.getElementById('verificationCode').value.trim();

    if (!token) {
        showErrorAlert('Por favor ingresa el token de verificación');
        return;
    }

    try {
        const accessToken = localStorage.getItem('accessToken');
        if (!accessToken) {
            throw new Error('No hay sesión activa');
        }

        const response = await apiRequest('/auth/email/verify', 'POST', {
            token: token
        });

        showSuccessAlert('¡Email verificado exitosamente! Redirigiendo...');

        setTimeout(() => {
            window.location.href = '{{ route('dashboard') }}';
        }, 1500);

    } catch (error) {
        showErrorAlert(error.message);
    }
}

async function resendEmail() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Reenviando...';

    try {
        const token = localStorage.getItem('accessToken');
        if (!token) {
            throw new Error('No hay sesión activa');
        }

        await apiRequest('/auth/email/verify/resend', 'POST');

        showSuccessAlert('Email de verificación reenviado. Revisa tu bandeja de entrada.');

        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 3000);

    } catch (error) {
        showErrorAlert(error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function logout() {
    event.preventDefault();

    if (!confirm('¿Deseas cambiar de cuenta?')) {
        return;
    }

    try {
        const token = localStorage.getItem('accessToken');
        if (token) {
            await apiRequest('/auth/logout', 'POST');
        }
    } catch (e) {
        console.error('Error al cerrar sesión:', e);
    }

    localStorage.removeItem('accessToken');
    sessionStorage.removeItem('accessToken');
    window.location.href = '{{ route('login') }}';
}

function showErrorAlert(message) {
    alertsDiv.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}

function showSuccessAlert(message) {
    alertsDiv.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}
</script>
@endsection
