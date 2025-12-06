@extends('adminlte::auth.auth-page', ['authType' => 'register'])

@section('auth_header', 'Registrar una nueva cuenta')

@section('auth_body')
    <div x-data="registerForm()" x-init="init()" @keydown.enter="submit()">
        <!-- Error Alert -->
        <div x-show="error" class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" @click="error = false" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit()" novalidate>
            @csrf

            {{-- First Name field --}}
            <div class="input-group mb-3">
                <input
                    type="text"
                    name="firstName"
                    class="form-control"
                    :class="{ 'is-invalid': errors.firstName }"
                    x-model="formData.firstName"
                    @blur="validateFirstName"
                    placeholder="Nombre"
                    :disabled="loading"
                    autofocus
                    required
                >

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-user {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                <span class="invalid-feedback d-block" x-show="errors.firstName" x-text="errors.firstName"></span>
            </div>

            {{-- Last Name field --}}
            <div class="input-group mb-3">
                <input
                    type="text"
                    name="lastName"
                    class="form-control"
                    :class="{ 'is-invalid': errors.lastName }"
                    x-model="formData.lastName"
                    @blur="validateLastName"
                    placeholder="Apellido"
                    :disabled="loading"
                    required
                >

                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-user {{ config('adminlte.classes_auth_icon', '') }}"></span>
                    </div>
                </div>

                <span class="invalid-feedback d-block" x-show="errors.lastName" x-text="errors.lastName"></span>
            </div>

            {{-- Email field --}}
            <div class="input-group mb-3">
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                    x-model="formData.email"
                    @blur="validateEmail"
                    placeholder="Correo Electrónico"
                    :disabled="loading"
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

            {{-- Password Confirmation field --}}
            <div class="input-group mb-3">
                <input
                    type="password"
                    name="passwordConfirmation"
                    class="form-control"
                    :class="{ 'is-invalid': errors.passwordConfirmation }"
                    x-model="formData.passwordConfirmation"
                    @blur="validatePasswordConfirmation"
                    placeholder="Repetir contraseña"
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

            {{-- Terms and Conditions --}}
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="acceptsTerms"
                        name="acceptsTerms"
                        x-model="formData.acceptsTerms"
                        @change="validateTerms"
                        :disabled="loading"
                        required
                    >
                    <label class="custom-control-label" for="acceptsTerms">
                        Acepto los <a href="#" target="_blank">términos y condiciones</a>
                    </label>
                </div>
                <span class="invalid-feedback d-block" x-show="errors.acceptsTerms" x-text="errors.acceptsTerms"></span>
            </div>

            {{-- Privacy Policy --}}
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="acceptsPrivacyPolicy"
                        name="acceptsPrivacyPolicy"
                        x-model="formData.acceptsPrivacyPolicy"
                        @change="validatePrivacyPolicy"
                        :disabled="loading"
                        required
                    >
                    <label class="custom-control-label" for="acceptsPrivacyPolicy">
                        Acepto la <a href="#" target="_blank">política de privacidad</a>
                    </label>
                </div>
                <span class="invalid-feedback d-block" x-show="errors.acceptsPrivacyPolicy" x-text="errors.acceptsPrivacyPolicy"></span>
            </div>

            {{-- Register Button --}}
            <button
                type="submit"
                class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}"
                :disabled="loading"
            >
                <span x-show="!loading">
                    <span class="fas fa-user-plus"></span>
                    Registrarse
                </span>
                <span x-show="loading">
                    <span class="spinner-border spinner-border-sm mr-2"></span>
                    Registrando...
                </span>
            </button>
        </form>

        <!-- Google Sign Up Button -->
        <div class="mt-3">
            <button
                type="button"
                class="btn btn-block btn-danger"
                @click="registerWithGoogle()"
                :disabled="loading"
            >
                <i class="fab fa-google mr-2"></i>
                Registrarse con Google
            </button>
        </div>
    </div>
@stop

@section('auth_footer')
    <p class="my-0">
        <a href="{{ route('login') }}">
            Ya tengo una cuenta
        </a>
    </p>
@stop

@section('adminlte_js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function registerForm() {
            return {
                formData: {
                    firstName: '',
                    lastName: '',
                    email: '',
                    password: '',
                    passwordConfirmation: '',
                    acceptsTerms: false,
                    acceptsPrivacyPolicy: false,
                },
                errors: {
                    firstName: '',
                    lastName: '',
                    email: '',
                    password: '',
                    passwordConfirmation: '',
                    acceptsTerms: '',
                    acceptsPrivacyPolicy: '',
                },
                loading: false,
                error: false,
                errorMessage: '',

                init() {
                    // Initialize form
                },

                validateFirstName() {
                    this.errors.firstName = '';
                    if (!this.formData.firstName) {
                        this.errors.firstName = 'El nombre es requerido';
                        return false;
                    }
                    if (this.formData.firstName.length < 2) {
                        this.errors.firstName = 'El nombre debe tener al menos 2 caracteres';
                        return false;
                    }
                    return true;
                },

                validateLastName() {
                    this.errors.lastName = '';
                    if (!this.formData.lastName) {
                        this.errors.lastName = 'El apellido es requerido';
                        return false;
                    }
                    if (this.formData.lastName.length < 2) {
                        this.errors.lastName = 'El apellido debe tener al menos 2 caracteres';
                        return false;
                    }
                    return true;
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

                validateTerms() {
                    this.errors.acceptsTerms = '';
                    if (!this.formData.acceptsTerms) {
                        this.errors.acceptsTerms = 'Debes aceptar los términos y condiciones';
                        return false;
                    }
                    return true;
                },

                validatePrivacyPolicy() {
                    this.errors.acceptsPrivacyPolicy = '';
                    if (!this.formData.acceptsPrivacyPolicy) {
                        this.errors.acceptsPrivacyPolicy = 'Debes aceptar la política de privacidad';
                        return false;
                    }
                    return true;
                },

                decodeJWT(token) {
                    try {
                        const base64Url = token.split('.')[1];
                        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function (c) {
                            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                        }).join(''));
                        return JSON.parse(jsonPayload);
                    } catch (error) {
                        console.error('Failed to decode JWT:', error);
                        return { roles: [] };
                    }
                },

                getDashboardUrl(roleCode) {
                    const dashboardMap = {
                        'PLATFORM_ADMIN': '/app/admin/dashboard',
                        'COMPANY_ADMIN': '/app/company/dashboard',
                        'AGENT': '/app/agent/dashboard',
                        'USER': '/app/user/dashboard'
                    };
                    return dashboardMap[roleCode] || '/app/dashboard';
                },

                async submit() {
                    // Validar todos los campos
                    const firstNameValid = this.validateFirstName();
                    const lastNameValid = this.validateLastName();
                    const emailValid = this.validateEmail();
                    const passwordValid = this.validatePassword();
                    const passwordConfirmationValid = this.validatePasswordConfirmation();
                    const termsValid = this.validateTerms();
                    const privacyValid = this.validatePrivacyPolicy();

                    if (!firstNameValid || !lastNameValid || !emailValid || !passwordValid || !passwordConfirmationValid || !termsValid || !privacyValid) {
                        return;
                    }

                    this.loading = true;
                    this.error = false;

                    try {
                        const response = await fetch('/api/auth/register', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                            },
                            body: JSON.stringify(this.formData),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Error al registrarse');
                        }

                        // Guardar SOLO access token en localStorage
                        if (data.accessToken) {
                            localStorage.setItem('access_token', data.accessToken);
                        }

                        // Decodificar JWT para verificar roles
                        const payload = this.decodeJWT(data.accessToken);
                        const roles = payload.roles || [];

                        // Lógica inteligente de roles (similar a login)
                        if (roles.length === 1) {
                            // Auto-asignar el único rol
                            const activeRole = {
                                code: roles[0].code,
                                company_id: roles[0].company_id || null,
                                company_name: roles[0].company_name || null
                            };
                            localStorage.setItem('active_role', JSON.stringify(activeRole));

                            // Ir a /auth/prepare-web que establece cookie y redirija al dashboard
                            const dashboardUrl = this.getDashboardUrl(activeRole.code);
                            setTimeout(() => {
                                window.location.href = `/auth/prepare-web?token=${data.accessToken}&redirect=${encodeURIComponent(dashboardUrl)}`;
                            }, 1500);
                        } else if (roles.length > 1) {
                            // Múltiples roles: ir a role-selector
                            setTimeout(() => {
                                window.location.href = `/auth/prepare-web?token=${data.accessToken}&redirect=${encodeURIComponent('/auth-flow/role-selector')}`;
                            }, 1500);
                        } else {
                            // Fallback por defecto
                            setTimeout(() => {
                                window.location.href = `/auth/prepare-web?token=${data.accessToken}&redirect=${encodeURIComponent('/app/dashboard')}`;
                            }, 1500);
                        }

                    } catch (err) {
                        console.error('Register error:', err);
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
