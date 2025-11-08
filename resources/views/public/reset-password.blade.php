@extends('layouts.guest')

@section('title', 'Restablecer Contraseña - Helpdesk')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <!-- Reset Password Card -->
        <div class="card shadow-sm border-0" x-data="resetPasswordForm()" x-init="init()">
            <div class="card-header bg-primary text-white text-center py-4">
                <h4 class="mb-0">
                    <i class="fas fa-lock-open me-2"></i>
                    Restablecer Contraseña
                </h4>
            </div>

            <div class="card-body p-4">
                <!-- Token Invalid State -->
                <template x-if="tokenInvalid">
                    <div>
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-times-circle fa-4x text-danger"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Token Inválido o Expirado</h5>
                            <p class="text-muted">
                                El enlace de restablecimiento es inválido o ha expirado.
                                Por favor, solicita un nuevo enlace.
                            </p>
                        </div>

                        <a href="/forgot-password" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-redo me-2"></i>
                            Solicitar Nuevo Enlace
                        </a>

                        <a href="/login" class="btn btn-outline-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Volver al Login
                        </a>
                    </div>
                </template>

                <!-- Form State -->
                <template x-if="!tokenInvalid">
                    <div>
                        <!-- Info Message -->
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Ingresa tu nueva contraseña. Debe tener al menos 8 caracteres.
                        </div>

                        <!-- Error Alert -->
                        <template x-if="error">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span x-text="error"></span>
                                <button type="button" class="btn-close" @click="clearError()" aria-label="Close"></button>
                            </div>
                        </template>

                        <!-- Reset Password Form -->
                        <form @submit.prevent="handleSubmit()">
                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock text-muted me-1"></i>
                                    Nueva Contraseña
                                </label>
                                <div class="input-group">
                                    <input
                                        :type="showPassword ? 'text' : 'password'"
                                        id="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.password }"
                                        x-model="formData.password"
                                        placeholder="Mínimo 8 caracteres"
                                        required
                                        autocomplete="new-password"
                                        :disabled="loading"
                                    >
                                    <button
                                        class="btn btn-outline-secondary"
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        :disabled="loading"
                                    >
                                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                                <template x-if="errors.password">
                                    <div class="invalid-feedback d-block" x-text="errors.password"></div>
                                </template>
                            </div>

                            <!-- Password Confirmation Input -->
                            <div class="mb-4">
                                <label for="passwordConfirmation" class="form-label">
                                    <i class="fas fa-lock text-muted me-1"></i>
                                    Confirmar Nueva Contraseña
                                </label>
                                <div class="input-group">
                                    <input
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        id="passwordConfirmation"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.passwordConfirmation }"
                                        x-model="formData.passwordConfirmation"
                                        placeholder="Repite tu nueva contraseña"
                                        required
                                        autocomplete="new-password"
                                        :disabled="loading"
                                    >
                                    <button
                                        class="btn btn-outline-secondary"
                                        type="button"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        :disabled="loading"
                                    >
                                        <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                                <template x-if="errors.passwordConfirmation">
                                    <div class="invalid-feedback d-block" x-text="errors.passwordConfirmation"></div>
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
                                        Restableciendo contraseña...
                                    </span>
                                </template>
                                <template x-if="!loading">
                                    <span>
                                        <i class="fas fa-check me-2"></i>
                                        Restablecer Contraseña
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
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('resetPasswordForm', () => ({
        // Form data
        formData: {
            password: '',
            passwordConfirmation: ''
        },

        // UI state
        showPassword: false,
        showPasswordConfirmation: false,
        loading: false,
        error: null,
        errors: {},
        tokenInvalid: false,

        // Token from URL
        token: '',

        /**
         * Initialize component
         */
        init() {
            console.log('[ResetPasswordForm] Initialized');

            // Get token from URL query parameter
            const urlParams = new URLSearchParams(window.location.search);
            this.token = urlParams.get('token');

            if (!this.token) {
                console.error('[ResetPasswordForm] No token found in URL');
                this.tokenInvalid = true;
            } else {
                console.log('[ResetPasswordForm] Token found');
            }
        },

        /**
         * Handle form submission
         */
        async handleSubmit() {
            console.log('[ResetPasswordForm] Submit attempt');
            this.loading = true;
            this.error = null;
            this.errors = {};

            // Validation
            if (!this.validateForm()) {
                this.loading = false;
                return;
            }

            try {
                const response = await fetch('/api/auth/password-reset/confirm', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        token: this.token,
                        password: this.formData.password,
                        passwordConfirmation: this.formData.passwordConfirmation
                    }),
                });

                const result = await response.json();

                if (!response.ok) {
                    // Check if token is invalid
                    if (response.status === 400 || response.status === 404) {
                        this.tokenInvalid = true;
                        throw new Error('Token inválido o expirado');
                    }

                    throw new Error(result.message || 'Error al restablecer contraseña');
                }

                console.log('[ResetPasswordForm] Password reset successful');

                // Show success message and redirect
                alert('Contraseña restablecida exitosamente. Redirigiendo al login...');
                window.location.href = '/login';

            } catch (error) {
                console.error('[ResetPasswordForm] Error:', error);
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

            // Password validation
            if (!this.formData.password) {
                this.errors.password = 'La contraseña es requerida';
                isValid = false;
            } else if (this.formData.password.length < 8) {
                this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
                isValid = false;
            }

            // Password confirmation validation
            if (!this.formData.passwordConfirmation) {
                this.errors.passwordConfirmation = 'Debes confirmar tu contraseña';
                isValid = false;
            } else if (this.formData.password !== this.formData.passwordConfirmation) {
                this.errors.passwordConfirmation = 'Las contraseñas no coinciden';
                isValid = false;
            }

            return isValid;
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

    .fa-times-circle {
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
