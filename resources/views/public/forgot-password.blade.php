@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Recuperar Contraseña')

@section('auth_body')
    <div x-data="forgotPasswordForm()" x-init="init()">
        <!-- Status Message -->
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('status') }}
            </div>
        @endif

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
                formData: {
                    email: '',
                },
                errors: {
                    email: '',
                },
                loading: false,
                success: false,
                error: false,
                successMessage: '',
                errorMessage: '',

                init() {
                    // Initialize form
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

                async submit() {
                    const emailValid = this.validateEmail();

                    if (!emailValid) {
                        return;
                    }

                    this.loading = true;
                    this.error = false;
                    this.success = false;

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

                        this.successMessage = 'Se ha enviado un enlace de recuperación a tu correo electrónico.';
                        this.success = true;
                        this.formData.email = '';

                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 3000);

                    } catch (err) {
                        console.error('Forgot password error:', err);
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
