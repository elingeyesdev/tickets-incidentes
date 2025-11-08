@extends('layouts.guest')

@section('title', 'Recuperar Contraseña - Helpdesk')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <!-- Forgot Password Card -->
        <div class="card shadow-sm border-0" x-data="forgotPasswordForm()" x-init="init()">
            <div class="card-header bg-primary text-white text-center py-4">
                <h4 class="mb-0">
                    <i class="fas fa-key me-2"></i>
                    Recuperar Contraseña
                </h4>
            </div>

            <div class="card-body p-4">
                <!-- Success State -->
                <template x-if="submitted">
                    <div>
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-envelope-open-text fa-4x text-success"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Revisa tu correo electrónico</h5>
                            <p class="text-muted">
                                Hemos enviado instrucciones para restablecer tu contraseña a
                                <strong x-text="formData.email"></strong>
                            </p>
                        </div>

                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>Redirigiendo al login en <strong x-text="countdown"></strong> segundos...</span>
                        </div>

                        <a href="/login" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Volver al Login
                        </a>
                    </div>
                </template>

                <!-- Form State -->
                <template x-if="!submitted">
                    <div>
                        <!-- Info Message -->
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Ingresa tu correo electrónico y te enviaremos instrucciones para restablecer tu contraseña.
                        </div>

                        <!-- Error Alert -->
                        <template x-if="error">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span x-text="error"></span>
                                <button type="button" class="btn-close" @click="clearError()" aria-label="Close"></button>
                            </div>
                        </template>

                        <!-- Forgot Password Form -->
                        <form @submit.prevent="handleSubmit()">
                            <!-- Email Input -->
                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope text-muted me-1"></i>
                                    Correo Electrónico
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    class="form-control form-control-lg"
                                    :class="{ 'is-invalid': errors.email }"
                                    x-model="formData.email"
                                    placeholder="usuario@ejemplo.com"
                                    required
                                    autocomplete="email"
                                    :disabled="loading"
                                    autofocus
                                >
                                <template x-if="errors.email">
                                    <div class="invalid-feedback" x-text="errors.email"></div>
                                </template>
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                class="btn btn-primary w-100 mb-3"
                                :disabled="loading"
                            >
                                <template x-if="loading">
                                    <span>
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Enviando instrucciones...
                                    </span>
                                </template>
                                <template x-if="!loading">
                                    <span>
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Enviar Instrucciones
                                    </span>
                                </template>
                            </button>

                            <!-- Back to Login Link -->
                            <div class="text-center">
                                <a href="/login" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver al login
                                </a>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

        <!-- Additional Help -->
        <div class="text-center mt-4">
            <p class="text-muted small">
                ¿No recibes el correo?
                <a href="#" class="text-decoration-none" @click.prevent="checkSpam()">
                    Revisa tu carpeta de spam
                </a>
            </p>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('forgotPasswordForm', () => ({
        // Form data
        formData: {
            email: ''
        },

        // UI state
        loading: false,
        error: null,
        errors: {},
        submitted: false,
        countdown: 5,

        // Timer reference
        countdownTimer: null,

        /**
         * Initialize component
         */
        init() {
            console.log('[ForgotPasswordForm] Initialized');
        },

        /**
         * Handle form submission
         */
        async handleSubmit() {
            console.log('[ForgotPasswordForm] Submit attempt');
            this.loading = true;
            this.error = null;
            this.errors = {};

            // Validation
            if (!this.validateForm()) {
                this.loading = false;
                return;
            }

            try {
                const response = await fetch('/api/auth/password-reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email: this.formData.email
                    }),
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al enviar instrucciones');
                }

                console.log('[ForgotPasswordForm] Request successful');
                this.submitted = true;

                // Start countdown
                this.startCountdown();

            } catch (error) {
                console.error('[ForgotPasswordForm] Error:', error);
                this.error = error.message || 'Error de conexión. Por favor, intenta nuevamente.';
            } finally {
                this.loading = false;
            }
        },

        /**
         * Validate form data
         */
        validateForm() {
            let isValid = true;

            // Email validation
            if (!this.formData.email) {
                this.errors.email = 'El correo electrónico es requerido';
                isValid = false;
            } else if (!this.isValidEmail(this.formData.email)) {
                this.errors.email = 'Formato de correo electrónico inválido';
                isValid = false;
            }

            return isValid;
        },

        /**
         * Email validation helper
         */
        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Start countdown timer
         */
        startCountdown() {
            this.countdownTimer = setInterval(() => {
                this.countdown--;

                if (this.countdown <= 0) {
                    clearInterval(this.countdownTimer);
                    window.location.href = '/login';
                }
            }, 1000);
        },

        /**
         * Check spam folder (show alert)
         */
        checkSpam() {
            alert('Revisa tu carpeta de correo no deseado o spam. A veces los correos automáticos pueden llegar allí.');
        },

        /**
         * Clear error message
         */
        clearError() {
            this.error = null;
        }
    }));
});
</script>
@endsection

@section('css')
<style>
    .card {
        border-radius: 0.5rem;
        max-width: 500px;
        margin: 0 auto;
    }

    .card-header {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }

    .btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
    }

    .fa-envelope-open-text {
        animation: fadeInScale 0.5s ease-in-out;
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

    @media (max-width: 768px) {
        .card {
            margin: 0 1rem;
        }
    }
</style>
@endsection
