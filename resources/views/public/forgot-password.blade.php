@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Recuperar Contraseña')

@section('auth_body')
    <div x-data="forgotPasswordForm()" x-init="init()">
        <!-- Loading Spinner (while validating token) -->
        <div x-show="validatingToken" class="text-center mb-4">
            <div class="spinner-border" role="status">
                <span class="sr-only">Validando...</span>
            </div>
            <p class="text-muted mt-2">Validando tu enlace...</p>
        </div>

        <!-- Error Alert -->
        <div x-show="error" class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" @click="error = false" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span x-text="errorMessage"></span>
        </div>

        <!-- STEP 1: Email Request Form -->
        <form @submit.prevent="submitEmail()" novalidate x-show="step === 1">
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

            {{-- Send reset link button --}}
            <button
                type="submit"
                class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}"
                :disabled="loading"
            >
                <span x-show="!loading">
                    <span class="fas fa-share-square"></span>
                    {{ __('adminlte::adminlte.send_password_reset_link') }}
                </span>
                <span x-show="loading">
                    <span class="spinner-border spinner-border-sm mr-2"></span>
                    Enviando...
                </span>
            </button>
        </form>

        <!-- STEP 2: Code + Password (shown after email is sent or if token from email) -->
        <form @submit.prevent="submitPassword()" novalidate x-show="step === 2">
            @csrf

            {{-- Email sent alert (only if no token) --}}
            <div x-show="!formData.token && showEmailAlert" class="alert alert-info alert-dismissible fade show" role="alert">
                <button type="button" class="close" @click="showEmailAlert = false" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fas fa-info-circle mr-2"></i>
                <strong>¡Email enviado!</strong>
                <p class="mb-0 mt-2">Se ha enviado un enlace y un código de 6 dígitos a <strong x-text="formData.email"></strong></p>
            </div>

            {{-- Token valid alert (only if token from email) --}}
            <div x-show="showTokenAlert" class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                <strong>¡Link válido!</strong>
                <p class="mb-0 mt-2">Ahora ingresa tu nueva contraseña</p>
            </div>

            {{-- Success alert --}}
            <div x-show="resetSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                <strong>¡Éxito!</strong>
                <p class="mb-0 mt-2">Tu contraseña ha sido restablecida. Redirigiendo...</p>
            </div>

            {{-- Code field (only if no token) - 6 separate inputs --}}
            <div x-show="!formData.token">
                <label class="form-label mb-2">Código de 6 dígitos:</label>
                <div class="d-flex justify-content-center gap-2 mb-3" style="gap: 0.5rem;">
                    <input
                        type="text"
                        x-model="codeDigits[0]"
                        @input="onCodeInput(0, $event)"
                        @keydown.backspace="onCodeBackspace(0, $event)"
                        class="form-control text-center"
                        :class="{ 'is-invalid': errors.code }"
                        placeholder="0"
                        maxlength="1"
                        inputmode="numeric"
                        :disabled="loading"
                        style="max-width: 50px; font-size: 18px; font-weight: bold;"
                        autofocus
                    >
                    <input
                        type="text"
                        x-model="codeDigits[1]"
                        @input="onCodeInput(1, $event)"
                        @keydown.backspace="onCodeBackspace(1, $event)"
                        class="form-control text-center"
                        :class="{ 'is-invalid': errors.code }"
                        placeholder="0"
                        maxlength="1"
                        inputmode="numeric"
                        :disabled="loading"
                        style="max-width: 50px; font-size: 18px; font-weight: bold;"
                    >
                    <input
                        type="text"
                        x-model="codeDigits[2]"
                        @input="onCodeInput(2, $event)"
                        @keydown.backspace="onCodeBackspace(2, $event)"
                        class="form-control text-center"
                        :class="{ 'is-invalid': errors.code }"
                        placeholder="0"
                        maxlength="1"
                        inputmode="numeric"
                        :disabled="loading"
                        style="max-width: 50px; font-size: 18px; font-weight: bold;"
                    >
                    <input
                        type="text"
                        x-model="codeDigits[3]"
                        @input="onCodeInput(3, $event)"
                        @keydown.backspace="onCodeBackspace(3, $event)"
                        class="form-control text-center"
                        :class="{ 'is-invalid': errors.code }"
                        placeholder="0"
                        maxlength="1"
                        inputmode="numeric"
                        :disabled="loading"
                        style="max-width: 50px; font-size: 18px; font-weight: bold;"
                    >
                    <input
                        type="text"
                        x-model="codeDigits[4]"
                        @input="onCodeInput(4, $event)"
                        @keydown.backspace="onCodeBackspace(4, $event)"
                        class="form-control text-center"
                        :class="{ 'is-invalid': errors.code }"
                        placeholder="0"
                        maxlength="1"
                        inputmode="numeric"
                        :disabled="loading"
                        style="max-width: 50px; font-size: 18px; font-weight: bold;"
                    >
                    <input
                        type="text"
                        x-model="codeDigits[5]"
                        @input="onCodeInput(5, $event)"
                        @keydown.backspace="onCodeBackspace(5, $event)"
                        class="form-control text-center"
                        :class="{ 'is-invalid': errors.code }"
                        placeholder="0"
                        maxlength="1"
                        inputmode="numeric"
                        :disabled="loading"
                        style="max-width: 50px; font-size: 18px; font-weight: bold;"
                    >
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
                    name="passwordConfirmation"
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

            {{-- Confirm password button --}}
            <button
                type="submit"
                class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}"
                :disabled="loading"
            >
                <span x-show="!loading">
                    <span class="fas fa-sync-alt"></span>
                    Restablecer Contraseña
                </span>
                <span x-show="loading">
                    <span class="spinner-border spinner-border-sm mr-2"></span>
                    Procesando...
                </span>
            </button>

            {{-- Back button --}}
            <button
                type="button"
                class="btn btn-outline-secondary btn-block mt-2"
                @click="goBackToEmail()"
            >
                <span class="fas fa-arrow-left mr-2"></span>Volver
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
        <a href="{{ route('register') }}">
            {{ __('adminlte::adminlte.register_a_new_membership') }}
        </a>
    </p>
@stop

@section('adminlte_js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function forgotPasswordForm() {
            return {
                step: 1,
                codeDigits: ['', '', '', '', '', ''],
                formData: {
                    email: '',
                    code: '',
                    password: '',
                    passwordConfirmation: '',
                    token: '',
                },
                errors: {
                    email: '',
                    code: '',
                    password: '',
                    passwordConfirmation: '',
                },
                loading: false,
                error: false,
                errorMessage: '',
                showEmailAlert: false,
                showTokenAlert: false,
                resetSuccess: false,
                validatingToken: false,

                init() {
                    // Leer token del query string (si viene del email)
                    const urlParams = new URLSearchParams(window.location.search);
                    const token = urlParams.get('token');

                    if (token) {
                        // Si tiene token desde el email, validar primero
                        this.validateToken(token);
                    } else {
                        // Si no tiene token, comienza en paso 1 (email)
                        this.step = 1;
                    }
                },

                async validateToken(token) {
                    this.validatingToken = true;

                    try {
                        const response = await fetch(`/api/auth/password-reset/status?token=${encodeURIComponent(token)}`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.isValid) {
                            throw new Error('Token inválido, expirado o ya fue usado');
                        }

                        // Token válido, pasar al paso 2
                        this.formData.token = token;
                        this.showTokenAlert = true;
                        this.step = 2;

                        // Auto-desaparecer alerta después de 6 segundos
                        setTimeout(() => {
                            this.showTokenAlert = false;
                        }, 6000);

                    } catch (err) {
                        console.error('Token validation error:', err);
                        this.errorMessage = err.message || 'Error al validar el token';
                        this.error = true;
                        this.step = 1;

                        // Auto-desaparecer error después de 6 segundos
                        setTimeout(() => {
                            this.error = false;
                        }, 6000);
                    } finally {
                        this.validatingToken = false;
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

                validateCode() {
                    this.errors.code = '';
                    // Construir código desde los 6 inputs
                    const code = this.codeDigits.join('');
                    this.formData.code = code;

                    if (!code || code.length !== 6) {
                        this.errors.code = 'El código debe ser de 6 dígitos';
                        return false;
                    }
                    if (!/^\d{6}$/.test(code)) {
                        this.errors.code = 'El código debe contener solo números';
                        return false;
                    }
                    return true;
                },

                onCodeInput(index, event) {
                    const value = event.target.value;
                    // Solo permitir números
                    if (!/^\d*$/.test(value)) {
                        this.codeDigits[index] = '';
                        return;
                    }
                    // Mantener solo un dígito
                    this.codeDigits[index] = value.slice(-1);

                    // Moverse al siguiente input si hay valor
                    if (this.codeDigits[index] && index < 5) {
                        setTimeout(() => {
                            const inputs = event.target.parentElement.querySelectorAll('input');
                            inputs[index + 1].focus();
                        }, 0);
                    }
                },

                onCodeBackspace(index, event) {
                    // Si se presiona backspace y el campo está vacío, moverse al anterior
                    if (!this.codeDigits[index] && index > 0) {
                        event.preventDefault();
                        setTimeout(() => {
                            const inputs = event.target.parentElement.querySelectorAll('input');
                            inputs[index - 1].focus();
                            this.codeDigits[index - 1] = '';
                        }, 0);
                    }
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

                async submitEmail() {
                    const emailValid = this.validateEmail();

                    if (!emailValid) {
                        return;
                    }

                    this.loading = true;
                    this.error = false;

                    try {
                        const response = await fetch('/api/auth/password-reset', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                            },
                            body: JSON.stringify({
                                email: this.formData.email,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Error al enviar el enlace de recuperación');
                        }

                        // Email enviado exitosamente, pasar al paso 2 (código + contraseña)
                        this.step = 2;
                        this.showEmailAlert = true;

                        // Ocultar alerta después de 6 segundos
                        setTimeout(() => {
                            this.showEmailAlert = false;
                        }, 6000);

                    } catch (err) {
                        console.error('Forgot password error:', err);
                        this.errorMessage = err.message || 'Error desconocido';
                        this.error = true;

                        // Auto-desaparecer error después de 6 segundos
                        setTimeout(() => {
                            this.error = false;
                        }, 6000);
                    } finally {
                        this.loading = false;
                    }
                },

                async submitPassword() {
                    // Validar código si no tiene token
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
                            throw new Error(data.message || 'Error al restablecer la contraseña');
                        }

                        // Guardar tokens
                        if (data.accessToken) {
                            localStorage.setItem('access_token', data.accessToken);
                        }
                        if (data.refreshToken) {
                            localStorage.setItem('refresh_token', data.refreshToken);
                        }

                        // Mostrar mensaje de éxito
                        this.resetSuccess = true;

                        // Redirigir a login después de 2 segundos
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);

                    } catch (err) {
                        console.error('Password reset error:', err);
                        this.errorMessage = err.message || 'Error desconocido';
                        this.error = true;

                        // Auto-desaparecer error después de 6 segundos
                        setTimeout(() => {
                            this.error = false;
                        }, 6000);
                    } finally {
                        this.loading = false;
                    }
                },

                goBackToEmail() {
                    this.step = 1;
                    this.codeDigits = ['', '', '', '', '', ''];
                    this.formData.code = '';
                    this.formData.password = '';
                    this.formData.passwordConfirmation = '';
                    this.errors.code = '';
                    this.errors.password = '';
                    this.errors.passwordConfirmation = '';
                    this.showEmailAlert = false;
                    this.showTokenAlert = false;
                },
            };
        }
    </script>
@stop
