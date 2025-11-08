@extends('layouts.guest')

@section('title', 'Login - Helpdesk')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <!-- Login Card -->
        <div class="card shadow-sm border-0" x-data="loginForm()" x-init="init()">
            <div class="card-header bg-primary text-white text-center py-4">
                <h4 class="mb-0">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
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

                <!-- Login Form -->
                <form @submit.prevent="handleLogin()">
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
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
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

                    <!-- Remember Me & Forgot Password -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="rememberMe"
                                x-model="formData.rememberMe"
                                :disabled="loading"
                            >
                            <label class="form-check-label" for="rememberMe">
                                Recordarme
                            </label>
                        </div>
                        <a href="/forgot-password" class="text-decoration-none small">
                            ¿Olvidaste tu contraseña?
                        </a>
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
                                Iniciando sesión...
                            </span>
                        </template>
                        <template x-if="!loading">
                            <span>
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
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

                    <!-- Google Login Button (Placeholder) -->
                    <button
                        type="button"
                        class="btn btn-outline-secondary w-100 mb-3"
                        disabled
                        title="Próximamente disponible"
                    >
                        <i class="fab fa-google me-2"></i>
                        Continuar con Google
                        <small class="d-block text-muted" style="font-size: 0.75rem;">(Próximamente)</small>
                    </button>
                </form>
            </div>

            <!-- Card Footer -->
            <div class="card-footer text-center bg-light">
                <p class="mb-0">
                    ¿No tienes cuenta?
                    <a href="/register" class="text-decoration-none fw-bold">
                        Regístrate aquí
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
    Alpine.data('loginForm', () => ({
        // Form data
        formData: {
            email: '',
            password: '',
            rememberMe: false
        },

        // UI state
        showPassword: false,
        loading: false,
        error: null,
        errors: {},

        // Services
        authStore: null,

        /**
         * Initialize component
         */
        init() {
            console.log('[LoginForm] Initialized');
            this.authStore = Alpine.store('auth');

            // Check if already authenticated
            if (this.authStore && this.authStore.isAuthenticated) {
                console.log('[LoginForm] User already authenticated, redirecting...');
                window.location.href = '/dashboard';
            }
        },

        /**
         * Handle login submission
         */
        async handleLogin() {
            console.log('[LoginForm] Login attempt');
            this.loading = true;
            this.error = null;
            this.errors = {};

            // Validation
            if (!this.validateForm()) {
                this.loading = false;
                return;
            }

            try {
                const result = await this.authStore.login(
                    this.formData.email,
                    this.formData.password
                );

                if (result.success) {
                    console.log('[LoginForm] Login successful');
                    // Redirect to dashboard
                    window.location.href = '/dashboard';
                } else {
                    console.error('[LoginForm] Login failed:', result.error);
                    this.error = result.error || 'Error al iniciar sesión';
                }
            } catch (error) {
                console.error('[LoginForm] Login error:', error);
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

    @media (max-width: 768px) {
        .card {
            margin: 0 1rem;
        }
    }
</style>
@endsection
