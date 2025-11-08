@extends('layouts.guest')

@section('title', 'Registro - Helpdesk')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <!-- Register Card -->
        <div class="card shadow-sm border-0" x-data="registerForm()" x-init="init()">
            <div class="card-header bg-primary text-white text-center py-4">
                <h4 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Crear Cuenta
                </h4>
            </div>

            <div class="card-body p-4">
                <!-- Error Alert -->
                <template x-if="error">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span x-text="error"></span>
                        <button type="button" class="btn-close" @click="clearError()" aria-label="Close"></button>
                    </div>
                </template>

                <!-- Register Form -->
                <form @submit.prevent="handleRegister()">
                    <!-- Name Row -->
                    <div class="row">
                        <!-- First Name -->
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label">
                                <i class="fas fa-user text-muted me-1"></i>
                                Nombre
                            </label>
                            <input
                                type="text"
                                id="firstName"
                                class="form-control"
                                :class="{ 'is-invalid': errors.firstName }"
                                x-model="formData.firstName"
                                placeholder="Juan"
                                required
                                autocomplete="given-name"
                                :disabled="loading"
                            >
                            <template x-if="errors.firstName">
                                <div class="invalid-feedback" x-text="errors.firstName"></div>
                            </template>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label">
                                <i class="fas fa-user text-muted me-1"></i>
                                Apellido
                            </label>
                            <input
                                type="text"
                                id="lastName"
                                class="form-control"
                                :class="{ 'is-invalid': errors.lastName }"
                                x-model="formData.lastName"
                                placeholder="Pérez"
                                required
                                autocomplete="family-name"
                                :disabled="loading"
                            >
                            <template x-if="errors.lastName">
                                <div class="invalid-feedback" x-text="errors.lastName"></div>
                            </template>
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope text-muted me-1"></i>
                            Correo Electrónico
                        </label>
                        <input
                            type="email"
                            id="email"
                            class="form-control"
                            :class="{ 'is-invalid': errors.email }"
                            x-model="formData.email"
                            placeholder="usuario@ejemplo.com"
                            required
                            autocomplete="email"
                            :disabled="loading"
                        >
                        <template x-if="errors.email">
                            <div class="invalid-feedback" x-text="errors.email"></div>
                        </template>
                    </div>

                    <!-- Password Input -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock text-muted me-1"></i>
                            Contraseña
                        </label>
                        <div class="input-group">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                class="form-control"
                                :class="{ 'is-invalid': errors.password }"
                                x-model="formData.password"
                                @input="calculatePasswordStrength()"
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

                        <!-- Password Strength Indicator -->
                        <template x-if="formData.password.length > 0">
                            <div class="mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div
                                        class="progress-bar"
                                        :class="{
                                            'bg-danger': passwordStrength <= 1,
                                            'bg-warning': passwordStrength >= 2 && passwordStrength <= 3,
                                            'bg-success': passwordStrength >= 4
                                        }"
                                        :style="`width: ${(passwordStrength / 5) * 100}%`"
                                    ></div>
                                </div>
                                <small class="text-muted" x-text="getPasswordStrengthText()"></small>
                            </div>
                        </template>

                        <template x-if="errors.password">
                            <div class="invalid-feedback d-block" x-text="errors.password"></div>
                        </template>
                    </div>

                    <!-- Password Confirmation Input -->
                    <div class="mb-3">
                        <label for="passwordConfirmation" class="form-label">
                            <i class="fas fa-lock text-muted me-1"></i>
                            Confirmar Contraseña
                        </label>
                        <div class="input-group">
                            <input
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                id="passwordConfirmation"
                                class="form-control"
                                :class="{ 'is-invalid': errors.passwordConfirmation }"
                                x-model="formData.passwordConfirmation"
                                placeholder="Repite tu contraseña"
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

                    <!-- Accept Terms Checkbox -->
                    <div class="form-check mb-2">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            :class="{ 'is-invalid': errors.acceptsTerms }"
                            id="acceptsTerms"
                            x-model="formData.acceptsTerms"
                            required
                            :disabled="loading"
                        >
                        <label class="form-check-label" for="acceptsTerms">
                            Acepto los
                            <a href="/terms" target="_blank" class="text-decoration-none">Términos y Condiciones</a>
                        </label>
                        <template x-if="errors.acceptsTerms">
                            <div class="invalid-feedback d-block" x-text="errors.acceptsTerms"></div>
                        </template>
                    </div>

                    <!-- Accept Privacy Policy Checkbox -->
                    <div class="form-check mb-3">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            :class="{ 'is-invalid': errors.acceptsPrivacyPolicy }"
                            id="acceptsPrivacyPolicy"
                            x-model="formData.acceptsPrivacyPolicy"
                            required
                            :disabled="loading"
                        >
                        <label class="form-check-label" for="acceptsPrivacyPolicy">
                            Acepto la
                            <a href="/privacy" target="_blank" class="text-decoration-none">Política de Privacidad</a>
                        </label>
                        <template x-if="errors.acceptsPrivacyPolicy">
                            <div class="invalid-feedback d-block" x-text="errors.acceptsPrivacyPolicy"></div>
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
                                Creando cuenta...
                            </span>
                        </template>
                        <template x-if="!loading">
                            <span>
                                <i class="fas fa-user-plus me-2"></i>
                                Crear Cuenta
                            </span>
                        </template>
                    </button>

                    <!-- Divider -->
                    <div class="position-relative mb-3">
                        <hr>
                        <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 text-muted small">
                            O
                        </span>
                    </div>

                    <!-- Google Register Button (Placeholder) -->
                    <button
                        type="button"
                        class="btn btn-outline-secondary w-100 mb-3"
                        disabled
                        title="Próximamente disponible"
                    >
                        <i class="fab fa-google me-2"></i>
                        Registrarse con Google
                        <small class="d-block text-muted" style="font-size: 0.75rem;">(Próximamente)</small>
                    </button>
                </form>
            </div>

            <!-- Card Footer -->
            <div class="card-footer text-center bg-light">
                <p class="mb-0">
                    ¿Ya tienes cuenta?
                    <a href="/login" class="text-decoration-none fw-bold">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('registerForm', () => ({
        // Form data
        formData: {
            firstName: '',
            lastName: '',
            email: '',
            password: '',
            passwordConfirmation: '',
            acceptsTerms: false,
            acceptsPrivacyPolicy: false
        },

        // UI state
        showPassword: false,
        showPasswordConfirmation: false,
        passwordStrength: 0,
        loading: false,
        error: null,
        errors: {},

        // Services
        authStore: null,

        /**
         * Initialize component
         */
        init() {
            console.log('[RegisterForm] Initialized');
            this.authStore = Alpine.store('auth');

            // Check if already authenticated
            if (this.authStore && this.authStore.isAuthenticated) {
                console.log('[RegisterForm] User already authenticated, redirecting...');
                window.location.href = '/dashboard';
            }
        },

        /**
         * Handle registration submission
         */
        async handleRegister() {
            console.log('[RegisterForm] Register attempt');
            this.loading = true;
            this.error = null;
            this.errors = {};

            // Validation
            if (!this.validateForm()) {
                this.loading = false;
                return;
            }

            try {
                const result = await this.authStore.register({
                    firstName: this.formData.firstName,
                    lastName: this.formData.lastName,
                    email: this.formData.email,
                    password: this.formData.password,
                    passwordConfirmation: this.formData.passwordConfirmation,
                    acceptsTerms: this.formData.acceptsTerms,
                    acceptsPrivacyPolicy: this.formData.acceptsPrivacyPolicy
                });

                if (result.success) {
                    console.log('[RegisterForm] Registration successful');
                    // Redirect to verify email
                    window.location.href = '/verify-email';
                } else {
                    console.error('[RegisterForm] Registration failed:', result.error);
                    this.error = result.error || 'Error al registrar usuario';
                }
            } catch (error) {
                console.error('[RegisterForm] Registration error:', error);
                this.error = 'Error de conexión. Por favor, intenta nuevamente.';
            } finally {
                this.loading = false;
            }
        },

        /**
         * Validate form data
         */
        validateForm() {
            let isValid = true;

            // First name validation
            if (!this.formData.firstName || this.formData.firstName.trim().length < 2) {
                this.errors.firstName = 'El nombre debe tener al menos 2 caracteres';
                isValid = false;
            }

            // Last name validation
            if (!this.formData.lastName || this.formData.lastName.trim().length < 2) {
                this.errors.lastName = 'El apellido debe tener al menos 2 caracteres';
                isValid = false;
            }

            // Email validation
            if (!this.formData.email) {
                this.errors.email = 'El correo electrónico es requerido';
                isValid = false;
            } else if (!this.isValidEmail(this.formData.email)) {
                this.errors.email = 'Formato de correo electrónico inválido';
                isValid = false;
            }

            // Password validation
            if (!this.formData.password) {
                this.errors.password = 'La contraseña es requerida';
                isValid = false;
            } else if (this.formData.password.length < 8) {
                this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
                isValid = false;
            } else if (!this.isPasswordComplex(this.formData.password)) {
                this.errors.password = 'La contraseña debe contener letras, números y símbolos';
                isValid = false;
            }

            // Password confirmation validation
            if (this.formData.password !== this.formData.passwordConfirmation) {
                this.errors.passwordConfirmation = 'Las contraseñas no coinciden';
                isValid = false;
            }

            // Terms validation
            if (!this.formData.acceptsTerms) {
                this.errors.acceptsTerms = 'Debes aceptar los términos y condiciones';
                isValid = false;
            }

            // Privacy policy validation
            if (!this.formData.acceptsPrivacyPolicy) {
                this.errors.acceptsPrivacyPolicy = 'Debes aceptar la política de privacidad';
                isValid = false;
            }

            return isValid;
        },

        /**
         * Calculate password strength (0-5)
         */
        calculatePasswordStrength() {
            const password = this.formData.password;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            this.passwordStrength = strength;
        },

        /**
         * Get password strength text
         */
        getPasswordStrengthText() {
            const texts = {
                0: 'Muy débil',
                1: 'Débil',
                2: 'Regular',
                3: 'Media',
                4: 'Fuerte',
                5: 'Muy fuerte'
            };
            return texts[this.passwordStrength] || 'Muy débil';
        },

        /**
         * Check if password is complex enough
         */
        isPasswordComplex(password) {
            const hasLetters = /[a-zA-Z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSymbols = /[^a-zA-Z0-9]/.test(password);
            return hasLetters && hasNumbers && hasSymbols;
        },

        /**
         * Email validation helper
         */
        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
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
        max-width: 600px;
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

    .progress {
        border-radius: 3px;
    }

    @media (max-width: 768px) {
        .card {
            margin: 0 1rem;
        }
    }
</style>
@endsection
