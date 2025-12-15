@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Establecer Nueva Contraseña')

@section('auth_body')
    <div x-data="resetPasswordForm()" x-init="init()">
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

            {{-- Token field (from URL query string) --}}
            <input type="hidden" name="token" x-model="formData.token">

            {{-- Code field (6 digits - only shown if no token) --}}
            <div class="input-group mb-3" x-show="!formData.token">
                <input
                    type="text"
                    name="code"
                    class="form-control"
                    :class="{ 'is-invalid': errors.code }"
                    x-model="formData.code"
                    @blur="validateCode"
                    placeholder="Código de 6 dígitos"
                    :disabled="loading"
                    maxlength="6"
                    inputmode="numeric"
                    autofocus
                >

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-key {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                <span class="invalid-feedback d-block" x-show="errors.code" x-text="errors.code"></span>
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

            {{-- Password confirmation field --}}
            <div class="input-group mb-3">
                <input
                    type="password"
                    name="password_confirmation"
                    class="form-control"
                    :class="{ 'is-invalid': errors.passwordConfirmation }"
                    x-model="formData.passwordConfirmation"
                    @blur="validatePasswordConfirmation"
                    placeholder="{{ __('adminlte::adminlte.retype_password') }}"
                    :disabled="loading"
                    required
                >

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                <span class="invalid-feedback d-block" x-show="errors.passwordConfirmation" x-text="errors.passwordConfirmation"></span>
            </div>

            {{-- Confirm password reset button --}}
            <button
                type="submit"
                class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}"
                :disabled="loading"
            >
                <span x-show="!loading">
                    <span class="fas fa-sync-alt"></span>
                    {{ __('adminlte::adminlte.reset_password') }}
                </span>
                <span x-show="loading">
                    <span class="spinner-border spinner-border-sm mr-2"></span>
                    Reseteando...
                </span>
            </button>
        </form>
    </div>
@stop

@section('auth_footer')
    <p class="my-0">
        <a href="{{ route('login') }}">
            Volver a iniciar sesión
        </a>
    </p>
    <p class="my-0">
        <a href="{{ route('password.request') }}">
            ¿Otro problema con tu contraseña?
        </a>
    </p>
@stop

@section('adminlte_js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function resetPasswordForm() {
            return {
                formData: {
                    token: '',
                    code: '',
                    password: '',
                    passwordConfirmation: '',
                },
                errors: {
                    code: '',
                    password: '',
                    passwordConfirmation: '',
                },
                loading: false,
                success: false,
                error: false,
                successMessage: '',
                errorMessage: '',

                init() {
                    // Leer token del query string
                    const urlParams = new URLSearchParams(window.location.search);
                    this.formData.token = urlParams.get('token');

                    // Si no hay token, se debe ingresar un código de 6 dígitos
                    if (!this.formData.token) {
                        // El campo de código será visible para que lo ingrese manualmente
                    }
                },

                validateCode() {
                    this.errors.code = '';
                    if (!this.formData.code) {
                        this.errors.code = 'El código es requerido';
                        return false;
                    }
                    if (!/^\d{6}$/.test(this.formData.code)) {
                        this.errors.code = 'El código debe ser de 6 dígitos';
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
                    // Check for letters, numbers and special characters
                    const hasLetters = /[a-zA-Z]/.test(this.formData.password);
                    const hasNumbers = /[0-9]/.test(this.formData.password);
                    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(this.formData.password);

                    if (!hasLetters || !hasNumbers || !hasSpecial) {
                        this.errors.password = 'La contraseña debe contener letras, números y caracteres especiales';
                        return false;
                    }
                    return true;
                },

                validatePasswordConfirmation() {
                    this.errors.passwordConfirmation = '';
                    if (!this.formData.passwordConfirmation) {
                        this.errors.passwordConfirmation = 'Confirma tu contraseña';
                        return false;
                    }
                    if (this.formData.password !== this.formData.passwordConfirmation) {
                        this.errors.passwordConfirmation = 'Las contraseñas no coinciden';
                        return false;
                    }
                    return true;
                },

                async submit() {
                    // Validar según si tiene token o código
                    if (!this.formData.token && !this.validateCode()) {
                        return;
                    }

                    const passwordValid = this.validatePassword();
                    const passwordConfirmationValid = this.validatePasswordConfirmation();

                    if (!passwordValid || !passwordConfirmationValid) {
                        return;
                    }

                    this.loading = true;
                    this.error = false;
                    this.success = false;

                    try {
                        // Construir payload dinámicamente según si hay token o código
                        const payload = {
                            password: this.formData.password,
                            passwordConfirmation: this.formData.passwordConfirmation,
                        };

                        // Agregar token o código según corresponda
                        if (this.formData.token) {
                            payload.token = this.formData.token;
                        } else {
                            payload.code = this.formData.code;
                        }

                        const response = await fetch('/api/auth/password-reset/confirm', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Error al resetear la contraseña');
                        }

                        this.successMessage = '¡Contraseña reseteada exitosamente! Redirigiendo...';
                        this.success = true;

                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);

                    } catch (err) {
                        console.error('Reset password error:', err);
                        this.errorMessage = err.message || 'Error desconocido';
                        this.error = true;
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@stop
