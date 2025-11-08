@extends('adminlte::master')

@section('adminlte_css')
    <style>
        .verify-email-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .verify-email-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            padding: 40px;
            text-align: center;
        }

        .verify-email-icon {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }

        .verify-email-card h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .verify-email-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .code-input {
            font-size: 24px;
            letter-spacing: 10px;
            text-align: center;
            margin: 20px 0;
        }

        .resend-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .countdown {
            color: #667eea;
            font-weight: bold;
        }
    </style>
@stop

@section('body')
    <div class="verify-email-container">
        <div class="verify-email-card" x-data="verifyEmailForm()" x-init="init()">
            <div class="verify-email-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>

            <h2>Verifica tu correo electrónico</h2>

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

            <p>
                Hemos enviado un código de verificación a tu correo electrónico.
                Ingresa el código para completar tu registro.
            </p>

            <form @submit.prevent="submit()" novalidate>
                @csrf

                {{-- Verification Code field --}}
                <div class="form-group">
                    <input
                        type="text"
                        name="code"
                        class="form-control form-control-lg code-input"
                        :class="{ 'is-invalid': errors.code }"
                        x-model="formData.code"
                        @input="formData.code = formData.code.toUpperCase()"
                        @blur="validateCode"
                        placeholder="000000"
                        maxlength="6"
                        :disabled="loading"
                        autofocus
                        required
                    >
                    <span class="invalid-feedback d-block" x-show="errors.code" x-text="errors.code"></span>
                </div>

                {{-- Verify Button --}}
                <button
                    type="submit"
                    class="btn btn-block btn-primary btn-lg"
                    :disabled="loading"
                >
                    <span x-show="!loading">
                        <i class="fas fa-check mr-2"></i>
                        Verificar correo
                    </span>
                    <span x-show="loading">
                        <span class="spinner-border spinner-border-sm mr-2"></span>
                        Verificando...
                    </span>
                </button>
            </form>

            <!-- Resend Section -->
            <div class="resend-section">
                <p class="text-muted mb-3">¿No recibiste el código?</p>

                <button
                    type="button"
                    class="btn btn-link"
                    @click="resendCode()"
                    :disabled="loading || !canResend"
                >
                    <span x-show="!loading">
                        Enviar código nuevamente
                    </span>
                    <span x-show="loading">
                        <span class="spinner-border spinner-border-sm mr-2"></span>
                        Enviando...
                    </span>
                </button>

                <div x-show="!canResend" class="countdown">
                    Puedes reintentar en <span x-text="resendCountdown"></span> segundos
                </div>
            </div>

            <!-- Help Link -->
            <div class="mt-4 pt-3 border-top">
                <p class="text-muted small mb-0">
                    Si tienes problemas, <a href="{{ route('login') }}">vuelve al inicio de sesión</a>
                </p>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function verifyEmailForm() {
            return {
                formData: {
                    code: '',
                },
                errors: {
                    code: '',
                },
                loading: false,
                success: false,
                error: false,
                successMessage: '',
                errorMessage: '',
                canResend: true,
                resendCountdown: 0,
                resendTimer: null,

                init() {
                    // Initialize
                },

                validateCode() {
                    this.errors.code = '';
                    if (!this.formData.code) {
                        this.errors.code = 'El código es requerido';
                        return false;
                    }
                    if (this.formData.code.length !== 6) {
                        this.errors.code = 'El código debe tener 6 caracteres';
                        return false;
                    }
                    if (!/^[0-9A-Z]+$/.test(this.formData.code)) {
                        this.errors.code = 'El código solo puede contener números y letras';
                        return false;
                    }
                    return true;
                },

                async submit() {
                    const codeValid = this.validateCode();

                    if (!codeValid) {
                        return;
                    }

                    this.loading = true;
                    this.error = false;
                    this.success = false;

                    try {
                        const response = await fetch('/api/auth/email/verify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                                'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                            },
                            body: JSON.stringify({
                                code: this.formData.code,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Código inválido');
                        }

                        this.successMessage = '¡Correo verificado exitosamente! Redirigiendo...';
                        this.success = true;

                        setTimeout(() => {
                            window.location.href = '/dashboard';
                        }, 2000);

                    } catch (err) {
                        console.error('Verify email error:', err);
                        this.errorMessage = err.message || 'Error desconocido';
                        this.error = true;
                    } finally {
                        this.loading = false;
                    }
                },

                async resendCode() {
                    this.loading = true;

                    try {
                        const response = await fetch('/api/auth/email/verify/resend', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': document.querySelector('input[name="_token"]').value,
                                'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                            },
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Error al enviar el código');
                        }

                        this.successMessage = 'Código enviado nuevamente a tu correo';
                        this.success = true;

                        // Start resend countdown
                        this.canResend = false;
                        this.resendCountdown = 60;

                        this.resendTimer = setInterval(() => {
                            this.resendCountdown--;
                            if (this.resendCountdown <= 0) {
                                clearInterval(this.resendTimer);
                                this.canResend = true;
                            }
                        }, 1000);

                    } catch (err) {
                        console.error('Resend code error:', err);
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
