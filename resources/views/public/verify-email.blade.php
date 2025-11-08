@extends('layouts.onboarding')

@section('title', 'Verificar Email')

@section('step_title')
    <span x-data="{ state: Alpine.store('verifyEmail') ? Alpine.store('verifyEmail').verificationStatus : 'pending' }">
        <template x-if="state === 'pending'">Verifica tu Email</template>
        <template x-if="state === 'verifying'">Verificando...</template>
        <template x-if="state === 'success'">¡Email Verificado!</template>
        <template x-if="state === 'error'">Error de Verificación</template>
    </span>
@endsection

@section('step_subtitle')
    Paso 1 de 3
@endsection

@section('content')
<div x-data="verifyEmailForm()" x-init="init()">
    <!-- Pending State -->
    <template x-if="verificationStatus === 'pending'">
        <div>
            <div class="text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-envelope fa-4x text-primary"></i>
                </div>
                <p class="text-muted mb-3">
                    Hemos enviado un correo de verificación a:
                </p>
                <p class="fw-bold text-primary mb-4" x-text="userEmail"></p>
                <p class="text-muted small">
                    Por favor, revisa tu bandeja de entrada y haz clic en el enlace de verificación.
                </p>
            </div>

            <!-- Resend Section -->
            <div class="d-grid gap-2 mb-3">
                <button
                    type="button"
                    class="btn btn-outline-primary position-relative"
                    @click="resendVerification()"
                    :disabled="!canResend || loading"
                >
                    <template x-if="!canResend">
                        <span>
                            <i class="fas fa-clock me-2"></i>
                            Reenviar en <strong x-text="countdown"></strong>s
                        </span>
                    </template>
                    <template x-if="canResend && !loading">
                        <span>
                            <i class="fas fa-paper-plane me-2"></i>
                            Reenviar Correo
                        </span>
                    </template>
                    <template x-if="loading">
                        <span>
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Enviando...
                        </span>
                    </template>

                    <!-- Resend Counter Badge -->
                    <template x-if="resendCount > 0">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="resendCount"></span>
                    </template>
                </button>

                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="skip()"
                    :disabled="loading"
                >
                    <i class="fas fa-forward me-2"></i>
                    Omitir por Ahora
                </button>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-info small" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                ¿No recibes el correo? Revisa tu carpeta de spam.
            </div>
        </div>
    </template>

    <!-- Verifying State -->
    <template x-if="verificationStatus === 'verifying'">
        <div class="text-center py-5">
            <div class="mb-4">
                <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    <span class="visually-hidden">Verificando...</span>
                </div>
            </div>
            <h5 class="fw-bold mb-2">Verificando tu email...</h5>
            <p class="text-muted">Por favor, espera un momento.</p>
        </div>
    </template>

    <!-- Success State -->
    <template x-if="verificationStatus === 'success'">
        <div class="text-center py-4">
            <div class="mb-4">
                <div class="checkmark-circle">
                    <i class="fas fa-check-circle fa-5x text-success checkmark-animated"></i>
                </div>
            </div>
            <h5 class="fw-bold mb-2">¡Email verificado exitosamente!</h5>
            <p class="text-muted mb-4">
                Tu cuenta ha sido verificada correctamente.
            </p>

            <div class="alert alert-success" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <span>Redirigiendo al dashboard en <strong x-text="redirectCountdown"></strong> segundos...</span>
            </div>

            <button
                type="button"
                class="btn btn-success w-100"
                @click="goToDashboard()"
            >
                <i class="fas fa-arrow-right me-2"></i>
                Continuar al Dashboard
            </button>
        </div>
    </template>

    <!-- Error State -->
    <template x-if="verificationStatus === 'error'">
        <div class="text-center py-4">
            <div class="mb-4">
                <i class="fas fa-times-circle fa-5x text-danger"></i>
            </div>
            <h5 class="fw-bold mb-2">Error al Verificar Email</h5>
            <p class="text-muted mb-4" x-text="message"></p>

            <div class="d-grid gap-2">
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="resendVerification()"
                    :disabled="loading"
                >
                    <template x-if="loading">
                        <span>
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Reenviando...
                        </span>
                    </template>
                    <template x-if="!loading">
                        <span>
                            <i class="fas fa-redo me-2"></i>
                            Reenviar Correo
                        </span>
                    </template>
                </button>

                <a href="/login" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Volver al Login
                </a>
            </div>
        </div>
    </template>
</div>
@endsection

@section('footer_actions')
<div class="text-center" x-data="{ status: Alpine.store('verifyEmail') ? Alpine.store('verifyEmail').verificationStatus : 'pending' }">
    <template x-if="status === 'pending'">
        <small class="text-muted">
            Al omitir, algunas funcionalidades pueden estar limitadas
        </small>
    </template>
</div>
@endsection

@section('additional_info')
<p class="small mb-0">
    <a href="/login" class="text-white text-decoration-none">
        <i class="fas fa-arrow-left me-2"></i>
        Volver al login
    </a>
</p>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    // Create global store for verification status
    Alpine.store('verifyEmail', {
        verificationStatus: 'pending'
    });

    Alpine.data('verifyEmailForm', () => ({
        // UI state
        verificationStatus: 'pending', // pending, verifying, success, error
        message: '',
        loading: false,

        // Token from URL
        token: '',

        // User email (from authStore)
        userEmail: '',

        // Resend control
        canResend: false,
        countdown: 60,
        resendCount: 0,
        countdownTimer: null,

        // Redirect countdown
        redirectCountdown: 3,
        redirectTimer: null,

        // Services
        authStore: null,

        /**
         * Initialize component
         */
        async init() {
            console.log('[VerifyEmailForm] Initialized');

            // Get authStore
            this.authStore = Alpine.store('auth');

            // Get user email from authStore
            if (this.authStore && this.authStore.user) {
                this.userEmail = this.authStore.user.email;
            } else {
                this.userEmail = 'tu correo electrónico';
            }

            // Check URL for token
            const urlParams = new URLSearchParams(window.location.search);
            this.token = urlParams.get('token');

            if (this.token) {
                console.log('[VerifyEmailForm] Token found in URL, auto-verifying...');
                await this.verifyWithToken();
            } else {
                console.log('[VerifyEmailForm] No token, showing pending state');
                this.startCountdown();
            }
        },

        /**
         * Verify email with token
         */
        async verifyWithToken() {
            this.verificationStatus = 'verifying';
            this.updateGlobalStore();

            try {
                const response = await fetch('/api/auth/email/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        token: this.token
                    }),
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al verificar email');
                }

                console.log('[VerifyEmailForm] Verification successful');

                // Update state to success
                this.verificationStatus = 'success';
                this.message = '¡Email verificado exitosamente!';
                this.updateGlobalStore();

                // Start redirect countdown
                this.startRedirectCountdown();

                // If opened in new tab (from email), close it
                if (window.opener) {
                    setTimeout(() => {
                        window.close();
                    }, 3000);
                }

            } catch (error) {
                console.error('[VerifyEmailForm] Verification error:', error);
                this.verificationStatus = 'error';
                this.message = error.message || 'Error al verificar tu email. El token puede estar expirado.';
                this.updateGlobalStore();
            }
        },

        /**
         * Resend verification email
         */
        async resendVerification() {
            console.log('[VerifyEmailForm] Resending verification email');
            this.loading = true;

            try {
                const token = this.authStore.tokenManager.getAccessToken();

                const response = await fetch('/api/auth/email/verify/resend', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                    },
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al reenviar correo');
                }

                console.log('[VerifyEmailForm] Resend successful');

                // Increment resend count
                this.resendCount++;

                // Reset countdown
                this.canResend = false;
                this.countdown = 60;
                this.startCountdown();

                // Show success message
                alert('Correo de verificación reenviado. Por favor, revisa tu bandeja de entrada.');

            } catch (error) {
                console.error('[VerifyEmailForm] Resend error:', error);
                alert('Error al reenviar el correo. Por favor, intenta nuevamente.');
            } finally {
                this.loading = false;
            }
        },

        /**
         * Skip verification
         */
        skip() {
            console.log('[VerifyEmailForm] Skip verification');

            // Show confirmation dialog
            if (confirm('¿Estás seguro de que deseas omitir la verificación?\n\nOmitir la verificación puede limitar algunas funcionalidades de tu cuenta.')) {
                // Redirect to dashboard or role selector
                window.location.href = '/dashboard';
            }
        },

        /**
         * Start countdown timer for resend button
         */
        startCountdown() {
            this.countdownTimer = setInterval(() => {
                this.countdown--;

                if (this.countdown <= 0) {
                    clearInterval(this.countdownTimer);
                    this.canResend = true;
                }
            }, 1000);
        },

        /**
         * Start redirect countdown timer
         */
        startRedirectCountdown() {
            this.redirectTimer = setInterval(() => {
                this.redirectCountdown--;

                if (this.redirectCountdown <= 0) {
                    clearInterval(this.redirectTimer);
                    this.goToDashboard();
                }
            }, 1000);
        },

        /**
         * Go to dashboard
         */
        goToDashboard() {
            window.location.href = '/dashboard';
        },

        /**
         * Update global store
         */
        updateGlobalStore() {
            Alpine.store('verifyEmail').verificationStatus = this.verificationStatus;
        }
    }));
});
</script>
@endsection

@section('css')
<style>
    /* Checkmark animation */
    .checkmark-animated {
        animation: checkmarkBounce 0.6s ease-in-out;
    }

    @keyframes checkmarkBounce {
        0% {
            opacity: 0;
            transform: scale(0);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Spinner pulse */
    .spinner-border {
        animation: spinner-border 0.75s linear infinite, pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Badge positioning */
    .badge {
        font-size: 0.7rem;
    }

    /* Button hover effects */
    .btn:hover:not(:disabled) {
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    .btn-success:hover:not(:disabled) {
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    }

    .btn-primary:hover:not(:disabled) {
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    /* Icon animations */
    .fa-envelope {
        animation: fadeInDown 0.5s ease-in-out;
    }

    .fa-times-circle {
        animation: fadeInScale 0.5s ease-in-out;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.5);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .fa-4x {
            font-size: 3rem;
        }

        .fa-5x {
            font-size: 3.5rem;
        }
    }
</style>
@endsection
