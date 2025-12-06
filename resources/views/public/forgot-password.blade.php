@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('adminlte_css')
    @parent
@stop

@section('auth_body')
    <div x-data="forgotPasswordForm()" x-init="init()">
        <!-- Loading Spinner (while validating token) -->
        <div x-show="validatingToken" class="text-center mb-4">
            <div class="spinner-border" role="status">
                <span class="sr-only">Validando...</span>
            </div>
            <p class="text-muted mt-2">Validando tu enlace...</p>
        </div>



        <!-- STEP 1: Email Request Form -->
        <form @submit.prevent="submitEmail()" novalidate x-show="step === 1">
            @csrf

            <p class="login-box-msg">¿Olvidaste tu contraseña? Aquí puedes recuperar fácilmente una nueva contraseña.</p>

            {{-- Email field --}}
            <div class="input-group mb-3">
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                    x-model="formData.email"
                    @focus="inputTouched.email = true"
                    @blur="inputTouched.email && validateEmail()"
                    @input="errors.email && validateEmail()"
                    placeholder="Correo Electrónico"
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

            <div class="row">
                <div class="col-12">
                    {{-- Send reset link button --}}
                    <button
                        type="submit"
                        class="btn btn-primary btn-block"
                        :disabled="loading"
                    >
                        <span x-show="!loading">
                            Solicitar nueva contraseña
                        </span>
                        <span x-show="loading">
                            <span class="spinner-border spinner-border-sm mr-2"></span>
                            Enviando...
                        </span>
                    </button>
                </div>
            </div>
        </form>

        <!-- STEP 2: Code + Password (shown after email is sent or if token from email) -->
        <form @submit.prevent="submitPassword()" novalidate x-show="step === 2">
            @csrf

            <p class="login-box-msg" x-show="!formData.token">
                Revisa tu correo <span x-show="formData.email" class="font-weight-bold" x-text="formData.email"></span>. Te hemos enviado un código de 6 dígitos para recuperar tu contraseña.
            </p>

            <p class="login-box-msg" x-show="formData.token">
                Estás a un paso de tu nueva contraseña, ingresa tu nueva contraseña ahora.
            </p>

            {{-- Code field (only if no token) - 6 separate inputs --}}
            <div x-show="!formData.token">
                <label class="form-label mb-2">Código de 6 dígitos:</label>
                <div class="d-flex justify-content-center gap-2 mb-3" style="gap: 0.5rem;" id="codeInputs">
                    <input
                        type="text"
                        x-model="codeDigits[0]"
                        @input="onCodeInput(0, $event)"
                        @keydown.backspace="onCodeBackspace(0, $event)"
                        @paste="onCodePaste($event)"
                        class="form-control text-center"
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
                    @focus="inputTouched.password = true"
                    @blur="inputTouched.password && validatePassword()"
                    @input="errors.password && validatePassword()"
                    placeholder="Contraseña"
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
                    @focus="inputTouched.passwordConfirmation = true"
                    @blur="inputTouched.passwordConfirmation && validatePasswordConfirmation()"
                    @input="errors.passwordConfirmation && validatePasswordConfirmation()"
                    placeholder="Confirmar Contraseña"
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

            <div class="row">
                <div class="col-12">
                    {{-- Confirm password button --}}
                    <button
                        type="submit"
                        class="btn btn-primary btn-block"
                        :disabled="loading"
                    >
                        <span x-show="!loading">
                            Cambiar contraseña
                        </span>
                        <span x-show="loading">
                            <span class="spinner-border spinner-border-sm mr-2"></span>
                            Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@stop

@section('auth_footer')
    <p class="mt-3 mb-1">
        <a href="{{ route('login') }}">
            Iniciar sesión
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
                inputTouched: {
                    email: false,
                    password: false,
                    passwordConfirmation: false,
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
                        this.step = 2;

                    } catch (err) {
                        console.error('Token validation error:', err);
                        // Mostrar error con Toast
                        this.showToast('danger', 'Error', err.message || 'Error al validar el token');
                        this.step = 1;


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
                    
                    // Limpiar error si hay mientras escribe
                    if (this.errors.code) {
                        this.errors.code = '';
                    }

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

                onCodePaste(event) {
                    event.preventDefault();
                    const pastedData = event.clipboardData.getData('text');
                    
                    // Limpiar y validar que solo contenga dígitos
                    const digits = pastedData.replace(/\D/g, '');
                    
                    if (digits.length !== 6) {
                        this.errors.code = 'El código debe tener exactamente 6 dígitos';
                        return;
                    }
                    
                    // Distribuir los dígitos en los inputs
                    for (let i = 0; i < 6; i++) {
                        this.codeDigits[i] = digits[i];
                    }
                    
                    // Enfocar el último input
                    const inputs = document.querySelector('#codeInputs').querySelectorAll('input');
                    inputs[5].focus();
                    
                    // Limpiar error si había
                    this.errors.code = '';
                },

                showToast(type, title, message) {
                    // AdminLTE v3 Toast
                    $(document).Toasts('create', {
                        class: type === 'success' ? 'bg-success' : (type === 'info' ? 'bg-info' : 'bg-danger'),
                        title: title,
                        body: message,
                        autohide: true,
                        delay: 8000,
                        icon: type === 'success' ? 'fas fa-check-circle' : (type === 'info' ? 'fas fa-info-circle' : 'fas fa-exclamation-circle'),
                    });
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

                    } catch (err) {
                        console.error('Forgot password error:', err);
                        // Mostrar error con Toast
                        this.showToast('danger', 'Error', err.message || 'Error desconocido');


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

                        // Guardar SOLO access token en localStorage
                        // SECURITY: refresh_token viene en HttpOnly cookie (no accesible a JavaScript)
                        if (data.accessToken) {
                            localStorage.setItem('access_token', data.accessToken);
                        }

                        // Redirigir a login después de 1 segundo
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 1000);

                    } catch (err) {
                        console.error('Password reset error:', err);
                        // Mostrar error con Toast
                        this.showToast('danger', 'Error', err.message || 'Error desconocido');


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
