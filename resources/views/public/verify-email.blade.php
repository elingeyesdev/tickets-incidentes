@extends('layouts.auth')

@section('title', 'Verificar Email')

@section('content')
<div class="card">
    <div class="card-header text-center">
        <h4 class="mb-0">
            <i class="fas fa-envelope me-2"></i> Verificar tu Email
        </h4>
    </div>

    <div class="card-body p-4">
        <div id="alerts"></div>

        <div class="text-center mb-4">
            <i class="fas fa-envelope-circle-check text-success" style="font-size: 3rem;"></i>
            <p class="text-muted mt-3">
                Hemos enviado un enlace de verificación a tu email. Por favor, haz clic en el enlace para completar tu registro.
            </p>
        </div>

        <div class="mb-4">
            <label class="form-label">Código de Verificación (opcional)</label>
            <div class="input-group">
                <input
                    type="text"
                    class="form-control form-control-lg"
                    id="verificationCode"
                    placeholder="000000"
                    maxlength="6"
                    inputmode="numeric"
                >
                <button class="btn btn-primary" type="button" onclick="verifyCode()">
                    <i class="fas fa-check me-2"></i> Verificar
                </button>
            </div>
            <small class="text-muted d-block mt-2">Si recibiste un código en tu email, puedes ingresarlo aquí</small>
        </div>

        <hr class="my-4">

        <div class="text-center">
            <p class="text-muted mb-2">¿No recibiste el email?</p>
            <button class="btn btn-outline-primary btn-sm" onclick="resendEmail()">
                <i class="fas fa-redo me-2"></i> Reenviar Email
            </button>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                <a href="#" onclick="logout()">Cambiar cuenta</a>
            </small>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const alertsDiv = document.getElementById('alerts');

async function verifyCode() {
    const code = document.getElementById('verificationCode').value.trim();

    if (!code) {
        showErrorAlert('Por favor ingresa el código de verificación');
        return;
    }

    if (!/^\d{6}$/.test(code)) {
        showErrorAlert('El código debe ser 6 dígitos');
        return;
    }

    try {
        const token = localStorage.getItem('accessToken');
        if (!token) {
            throw new Error('No hay sesión activa');
        }

        const response = await apiRequest('/auth/email/verify', 'POST', {
            code: code
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

        showSuccessAlert('Email reenviado correctamente. Revisa tu bandeja de entrada.');

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
