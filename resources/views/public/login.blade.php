@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('adminlte_css_pre')
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
@stop

@php
    $loginUrl = View::getSection('login_url') ?? config('adminlte.login_url', 'login');
    $registerUrl = View::getSection('register_url') ?? config('adminlte.register_url', 'register');
    $passResetUrl = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset');

    if (config('adminlte.use_route_url', false)) {
        $loginUrl = $loginUrl ? route($loginUrl) : '';
        $registerUrl = $registerUrl ? route($registerUrl) : '';
        $passResetUrl = $passResetUrl ? route($passResetUrl) : '';
    } else {
        $loginUrl = $loginUrl ? url($loginUrl) : '';
        $registerUrl = $registerUrl ? url($registerUrl) : '';
        $passResetUrl = $passResetUrl ? url($passResetUrl) : '';
    }
@endphp

@section('auth_header', __('adminlte::adminlte.login_message'))

@section('auth_body')
    <div x-data="loginForm()" x-init="init()" @keydown.enter="submit()">
        <!-- Error Alert -->
        <div x-show="error" class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" @click="error = false" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span x-text="errorMessage"></span>
        </div>

        <!-- Success Alert -->
        <div x-show="success" class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" @click="success = false" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-check-circle mr-2"></i>
            <span x-text="successMessage"></span>
        </div>

        <form @submit.prevent="submit()" novalidate>
            @csrf

            {{-- Email field --}}
            <div class="input-group mb-3">
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                    x-model="formData.email"
                    @blur="validateEmail"
                    placeholder="{{ __('adminlte::adminlte.email') }}"
                    :disabled="loading"
                    autofocus
                    required
                >

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                <span class="invalid-feedback d-block" x-show="errors.email" x-text="errors.email"></span>
            </div>

            {{-- Password field --}}
            <div class="input-group mb-3">
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                    x-model="formData.password"
                    @blur="validatePassword"
                    placeholder="{{ __('adminlte::adminlte.password') }}"
                    :disabled="loading"
                    required
                >

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                <span class="invalid-feedback d-block" x-show="errors.password" x-text="errors.password"></span>
            </div>

            {{-- Login field --}}
            <div class="row">
                <div class="col-7">
                    <div class="icheck-primary" title="{{ __('adminlte::adminlte.remember_me_hint') }}">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                            x-model="formData.remember"
                            :disabled="loading"
                        >

                        <label for="remember">
                            {{ __('adminlte::adminlte.remember_me') }}
                        </label>
                    </div>
                </div>

                <div class="col-5">
                    <button
                        type="submit"
                        class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}"
                        :disabled="loading"
                    >
                        <span x-show="!loading">
                            <span class="fas fa-sign-in-alt"></span>
                            {{ __('adminlte::adminlte.sign_in') }}
                        </span>
                        <span x-show="loading">
                            <span class="spinner-border spinner-border-sm mr-2"></span>
                            Autenticando...
                        </span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Google Sign In Button -->
        <div class="mt-3">
            <button
                type="button"
                class="btn btn-block btn-danger"
                @click="loginWithGoogle()"
                :disabled="loading"
            >
                <i class="fab fa-google mr-2"></i>
                {{ __('adminlte::adminlte.sign_in_with') }} Google
            </button>
        </div>
    </div>
@stop

@section('auth_footer')
    {{-- Password reset link --}}
    @if($passResetUrl)
        <p class="my-0">
            <a href="{{ $passResetUrl }}">
                {{ __('adminlte::adminlte.i_forgot_my_password') }}
            </a>
        </p>
    @endif

    {{-- Register link --}}
    @if($registerUrl)
        <p class="my-0">
            <a href="{{ $registerUrl }}">
                {{ __('adminlte::adminlte.register_a_new_membership') }}
            </a>
        </p>
    @endif
@stop

@section('adminlte_css')
    <style>
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover:not(:disabled) {
            background-color: #c82333;
            border-color: #bd2130;
        }
    </style>
@stop

@section('adminlte_js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <script>
        function loginForm() {
            return {
                formData: {
                    email: '',
                    password: '',
                    remember: false,
                },
                errors: {
                    email: '',
                    password: '',
                },
                loading: false,
                success: false,
                error: false,
                successMessage: '',
                errorMessage: '',

                init() {
                    // Restaurar email guardado si existe
                    const savedEmail = localStorage.getItem('last_email');
                    if (savedEmail) {
                        this.formData.email = savedEmail;
                        this.formData.remember = true;
                    }

                    // Mostrar mensaje de registro exitoso si existe
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('registered') === '1') {
                        this.successMessage = 'Registro exitoso! Ahora inicia sesión con tus credenciales.';
                        this.success = true;
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }

                    // Inicializar Google Sign-In si está disponible
                    if (window.google) {
                        google.accounts.id.initialize({
                            client_id: '{{ config("services.google.client_id") }}',
                            callback: this.handleGoogleLogin.bind(this)
                        });
                    }
                },

                validateEmail() {
                    this.errors.email = '';
                    if (!this.formData.email) {
                        this.errors.email = 'El correo es requerido';
                        return false;
                    }
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.formData.email)) {
                        this.errors.email = 'Ingresa un correo válido';
                        return false;
                    }
                    return true;
                },

                validatePassword() {
                    this.errors.password = '';
                    if (!this.formData.password) {
                        this.errors.password = 'La contraseña es requerida';
                        return false;
                    }
                    if (this.formData.password.length < 8) {
                        this.errors.password = 'La contraseña debe tener al menos 8 caracteres';
                        return false;
                    }
                    return true;
                },

                async submit() {
                    // Validar campos
                    const emailValid = this.validateEmail();
                    const passwordValid = this.validatePassword();

                    if (!emailValid || !passwordValid) {
                        return;
                    }

                    this.loading = true;
                    this.error = false;
                    this.success = false;

                    try {
                        const response = await fetch('/api/auth/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                            },
                            body: JSON.stringify({
                                email: this.formData.email,
                                password: this.formData.password,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Error al iniciar sesión');
                        }

                        // Guardar email si remember me está activo
                        if (this.formData.remember) {
                            localStorage.setItem('last_email', this.formData.email);
                        } else {
                            localStorage.removeItem('last_email');
                        }

                        // Guardar tokens
                        if (data.data.accessToken) {
                            localStorage.setItem('access_token', data.data.accessToken);
                        }
                        if (data.data.refreshToken) {
                            localStorage.setItem('refresh_token', data.data.refreshToken);
                        }

                        this.successMessage = 'Sesión iniciada. Redirigiendo...';
                        this.success = true;

                        setTimeout(() => {
                            window.location.href = '/dashboard';
                        }, 1500);

                    } catch (err) {
                        console.error('Login error:', err);
                        this.errorMessage = err.message || 'Error desconocido';
                        this.error = true;
                    } finally {
                        this.loading = false;
                    }
                },

                async loginWithGoogle() {
                    // Iniciar el flujo de Google Sign-In
                    if (window.google) {
                        google.accounts.id.renderButton(
                            document.createElement('div'),
                            { theme: 'outline', size: 'large' }
                        );
                    }
                },

                async handleGoogleLogin(response) {
                    this.loading = true;
                    this.error = false;

                    try {
                        const apiResponse = await fetch('/api/auth/login/google', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                            },
                            body: JSON.stringify({
                                token: response.credential
                            }),
                        });

                        const data = await apiResponse.json();

                        if (!apiResponse.ok) {
                            throw new Error(data.message || 'Error al iniciar sesión con Google');
                        }

                        // Guardar tokens
                        if (data.data.accessToken) {
                            localStorage.setItem('access_token', data.data.accessToken);
                        }
                        if (data.data.refreshToken) {
                            localStorage.setItem('refresh_token', data.data.refreshToken);
                        }

                        this.successMessage = 'Sesión iniciada. Redirigiendo...';
                        this.success = true;

                        setTimeout(() => {
                            window.location.href = '/dashboard';
                        }, 1500);

                    } catch (err) {
                        console.error('Google login error:', err);
                        this.errorMessage = err.message || 'Error al iniciar sesión con Google';
                        this.error = true;
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@stop
