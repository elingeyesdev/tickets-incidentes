@extends('layouts.guest')

@section('title', 'Iniciar Sesión - Helpdesk')

@section('content')
    <div class="row align-items-center justify-content-center" style="min-height: calc(100vh - 200px);">
        <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-4">
            <!-- Login Box -->
            <div class="login-box">
                <!-- Logo/Branding -->
                <div class="login-logo text-center mb-4">
                    <img src="{{ asset('logo.png') }}" alt="Helpdesk" height="50" class="mb-2" onerror="this.style.display='none'">
                    <h2 class="text-primary font-weight-bold">Helpdesk</h2>
                    <p class="text-muted small">Iniciar sesión para continuar</p>
                </div>

                <!-- Login Card -->
                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="card-body p-0">
                        <!-- Form -->
                        <form method="POST" action="/api/login" id="loginForm" x-data="loginForm()" @submit.prevent="handleLogin()">
                            @csrf

                            <!-- Email Input Group -->
                            <div class="input-group mb-0 border-bottom">
                                <input
                                    type="email"
                                    id="email"
                                    class="form-control form-control-lg border-0"
                                    :class="{ 'is-invalid': errors.email }"
                                    x-model="formData.email"
                                    name="email"
                                    placeholder="Correo Electrónico"
                                    autocomplete="email"
                                    :disabled="loading"
                                    required
                                >
                                <div class="input-group-append">
                                    <div class="input-group-text bg-light border-0">
                                        <span class="fas fa-envelope text-muted"></span>
                                    </div>
                                </div>
                            </div>
                            <template x-if="errors.email">
                                <div class="text-danger small px-3 pt-2" x-text="errors.email"></div>
                            </template>

                            <!-- Password Input Group -->
                            <div class="input-group mb-0 border-bottom">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    class="form-control form-control-lg border-0"
                                    :class="{ 'is-invalid': errors.password }"
                                    x-model="formData.password"
                                    name="password"
                                    placeholder="Contraseña"
                                    autocomplete="current-password"
                                    :disabled="loading"
                                    required
                                >
                                <div class="input-group-append">
                                    <button
                                        class="btn btn-link border-0 bg-light"
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        :disabled="loading"
                                        tabindex="-1"
                                        style="color: #999; text-decoration: none;"
                                    >
                                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                            <template x-if="errors.password">
                                <div class="text-danger small px-3 pt-2" x-text="errors.password"></div>
                            </template>

                            <!-- Error Alert -->
                            <template x-if="error">
                                <div class="alert alert-danger m-3 mb-0" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <span x-text="error"></span>
                                </div>
                            </template>

                            <!-- Remember Me & Submit -->
                            <div class="row p-3 mb-0">
                                <div class="col-7">
                                    <div class="icheck-primary">
                                        <input
                                            type="checkbox"
                                            id="rememberMe"
                                            x-model="formData.rememberMe"
                                            name="remember"
                                            :disabled="loading"
                                        >
                                        <label for="rememberMe" class="small">
                                            Recordarme
                                        </label>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-block"
                                        :disabled="loading"
                                    >
                                        <template x-if="!loading">
                                            <span>
                                                <i class="fas fa-sign-in-alt me-1"></i>
                                                Entrar
                                            </span>
                                        </template>
                                        <template x-if="loading">
                                            <span>
                                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                <span class="d-none d-sm-inline">Entrando...</span>
                                            </span>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer bg-light border-top py-3">
                        <p class="mb-2 text-center">
                            <a href="{{ route('password.request') }}" class="text-muted small">
                                <i class="fas fa-key me-1"></i>
                                Olvidé mi contraseña
                            </a>
                        </p>
                        <p class="mb-0 text-center">
                            <small>¿No tienes cuenta?</small>
                            <a href="{{ route('register') }}" class="text-primary font-weight-bold small">
                                Regístrate
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Security Badge -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Conexión segura
                    </small>
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
<link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
<style>
    /* AdminLTE v3 Login v2 Style */
    main {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .login-box {
        width: 100%;
        position: relative;
    }

    .login-logo {
        margin-bottom: 1rem;
    }

    .login-logo h2 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        letter-spacing: -1px;
    }

    .login-logo p {
        font-size: 0.9rem;
        margin: 0;
    }

    .card {
        border-radius: 0.5rem;
        border: none;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .card-body {
        padding: 0;
    }

    /* Input Groups */
    .input-group {
        position: relative;
        margin-bottom: 0;
    }

    .input-group .form-control-lg {
        height: auto;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        border-radius: 0;
        border: none;
        background-color: #f8f9fa;
    }

    .input-group .form-control-lg:focus {
        background-color: #fff;
        border-color: transparent;
        box-shadow: none;
        outline: none;
    }

    .input-group .input-group-text {
        background-color: transparent;
        border: none;
        padding-right: 1rem;
    }

    .input-group .input-group-append .input-group-text {
        background-color: #f8f9fa;
    }

    .input-group .form-control-lg:focus + .input-group-append .input-group-text {
        background-color: #fff;
    }

    .input-group .btn-link {
        padding: 0.75rem 1rem;
        color: #999;
    }

    .input-group .btn-link:focus {
        box-shadow: none;
        outline: none;
    }

    .border-bottom {
        border-bottom-color: #e3e6f0 !important;
    }

    /* Remember Me (icheck-bootstrap) */
    .icheck-primary input:checked + label::before {
        background-color: #007bff;
        border-color: #007bff;
    }

    .icheck-primary input + label::before {
        border-color: #dee2e6;
    }

    /* Button */
    .btn-primary {
        background-color: #007bff;
        border: none;
        border-radius: 0.35rem;
        padding: 0.75rem 1rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .btn-primary:hover:not(:disabled) {
        background-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .btn-primary:active:not(:disabled) {
        transform: translateY(0);
    }

    .btn-primary:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.65;
    }

    .btn-block {
        display: block;
        width: 100%;
    }

    /* Card Footer */
    .card-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #e3e6f0;
        padding: 1rem;
    }

    .card-footer p {
        margin-bottom: 0.5rem;
    }

    .card-footer a {
        transition: color 0.3s ease;
    }

    .card-footer a:hover {
        text-decoration: underline;
    }

    /* Error Messages */
    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 0.25rem;
        color: #721c24;
    }

    .text-danger {
        color: #dc3545;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .login-box {
            margin: 0 0.5rem;
        }

        main {
            padding: 2rem 0;
        }

        .login-logo h2 {
            font-size: 1.5rem;
        }

        .col-5 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
</style>
@endsection
