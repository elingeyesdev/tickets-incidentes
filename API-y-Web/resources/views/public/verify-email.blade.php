@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('adminlte_css')
    @parent
@stop

@section('auth_body')
    <div x-data="verifyEmailForm()" x-init="init()">
        <!-- Token Verification Status (Validating, Success, Error) -->
        <div x-show="validatingToken || tokenStatus === 'success' || tokenStatus === 'error'" class="text-center mb-4">
            
            <!-- Spinner -->
            <div x-show="tokenStatus === 'validating'" class="mb-3">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Validando...</span>
                </div>
                <h5 class="mt-3 text-muted">Verificando tu enlace...</h5>
            </div>

            <!-- Success Check -->
            <div x-show="tokenStatus === 'success'" class="mb-3">
                <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
                <h4 class="mt-3 text-success">¡Email Verificado!</h4>
                <p class="text-muted mt-2" x-text="successMessage"></p>
                <p class="small text-muted mt-3"><i class="fas fa-spinner fa-spin mr-1"></i> Redirigiendo al dashboard...</p>
            </div>

            <!-- Error Cross -->
            <div x-show="tokenStatus === 'error'" class="mb-3">
                <i class="fas fa-times-circle text-danger" style="font-size: 64px;"></i>
                <h4 class="mt-3 text-danger">Enlace no válido</h4>
                <p class="text-muted mt-2" x-text="errorMessage"></p>
                <div class="mt-4">
                    <button @click="showManualVerification()" class="btn btn-primary btn-sm">
                        Intentar con código manual
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 1: Initial - Ask to send email -->
        <div x-show="step === 1 && !validatingToken && tokenStatus !== 'success' && tokenStatus !== 'error'">
            <div class="text-center mb-4">
                <i class="fas fa-envelope-open-text text-primary" style="font-size: 64px;"></i>
            </div>
            
            <p class="text-muted text-center mb-4">
                Para completar tu registro, necesitamos verificar tu dirección de correo:
                <br>
                <strong x-text="userEmail"></strong>
            </p>

            <div class="row">
                <div class="col-12">
                    <!-- Send Email Button (Green) -->
                    <button
                        type="button"
                        class="btn btn-success btn-block"
                        @click="sendVerificationEmail()"
                        :disabled="loading || !canResend"
                    >
                        <span x-show="!loading && canResend">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Email
                        </span>
                        <span x-show="!loading && !canResend">
                            <i class="fas fa-clock mr-2"></i>Espera <span x-text="resendCountdown"></span>s
                        </span>
                        <span x-show="loading">
                            <span class="spinner-border spinner-border-sm mr-2"></span>
                            Enviando...
                        </span>
                    </button>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <!-- Skip Button -->
                    <a href="{{ route('dashboard') }}" class="btn btn-link btn-block text-muted">
                        Omitir por ahora
                    </a>
                </div>
            </div>
        </div>

        <!-- STEP 2: Email Sent - Enter Code -->
        <form @submit.prevent="verifyWithCode()" novalidate x-show="step === 2 && !validatingToken && tokenStatus !== 'success' && tokenStatus !== 'error'">
            @csrf

            <p class="login-box-msg">
                Hemos enviado un código de 6 dígitos a <strong x-text="userEmail"></strong>. Por favor revisa tu bandeja de entrada.
            </p>

            <!-- Success Message (Manual Code) -->
            <div x-show="verificationSuccess" class="alert alert-success text-center" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                <span x-text="successMessage"></span>
            </div>

            <!-- Error Message (Manual Code) -->
            <div x-show="verificationError" class="alert alert-danger text-center" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span x-text="errorMessage"></span>
            </div>

            {{-- Code field - 6 separate inputs --}}
            <div x-show="!verificationSuccess">
                <label class="form-label mb-2">Código de 6 dígitos:</label>
                <div class="d-flex justify-content-center gap-2 mb-3" style="gap: 0.5rem;" id="codeInputs">
                    <template x-for="(digit, index) in codeDigits" :key="index">
                        <input
                            type="text"
                            x-model="codeDigits[index]"
                            @input="onCodeInput(index, $event)"
                            @keydown.backspace="onCodeBackspace(index, $event)"
                            @paste="onCodePaste($event)"
                            class="form-control text-center"
                            :class="{ 'is-invalid': errors.code }"
                            placeholder="0"
                            maxlength="1"
                            inputmode="numeric"
                            :disabled="loading || verificationSuccess"
                            style="max-width: 50px; font-size: 18px; font-weight: bold;"
                            :autofocus="index === 0"
                        >
                    </template>
                </div>
                <span class="invalid-feedback d-block text-center" x-show="errors.code" x-text="errors.code"></span>

                <div class="row">
                    <div class="col-12">
                        {{-- Verify Button --}}
                        <button
                            type="submit"
                            class="btn btn-success btn-block"
                            :disabled="loading || verificationSuccess"
                        >
                            <span x-show="!loading">
                                <i class="fas fa-check mr-2"></i>Verificar Código
                            </span>
                            <span x-show="loading">
                                <span class="spinner-border spinner-border-sm mr-2"></span>
                                Verificando...
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Resend Section -->
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted mb-2">¿No recibiste el código?</p>
                    <button
                        type="button"
                        class="btn btn-link"
                        @click="sendVerificationEmail()"
                        :disabled="loading || !canResend"
                    >
                        <span x-show="canResend">
                            <i class="fas fa-redo mr-1"></i>Reenviar código
                        </span>
                        <span x-show="!canResend" class="text-muted">
                            Reintentar en <span x-text="resendCountdown" class="font-weight-bold"></span>s
                        </span>
                    </button>
                </div>

                <!-- Skip Link -->
                <div class="text-center mt-3">
                    <a href="{{ route('dashboard') }}" class="text-muted small">
                        <i class="fas fa-arrow-left mr-1"></i>Omitir verificación
                    </a>
                </div>
            </div>
        </form>
    </div>
@stop

@section('adminlte_js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function verifyEmailForm() {
            return {
                step: 1,
                codeDigits: ['', '', '', '', '', ''],
                formData: {
                    code: '',
                    token: '',
                },
                errors: {
                    code: '',
                },
                loading: false,
                validatingToken: false,
                tokenStatus: '', // '' | 'validating' | 'success' | 'error'
                verificationSuccess: false,
                verificationError: false,
                successMessage: '',
                errorMessage: '',
                userEmail: '',
                canResend: true,
                resendCountdown: 0,
                resendTimer: null,
                accessToken: '',

                init() {
                    // Get access token from localStorage
                    this.accessToken = localStorage.getItem('access_token') || '';

                    // Check for token in URL (from email link)
                    const urlParams = new URLSearchParams(window.location.search);
                    const token = urlParams.get('token');
                    const code = urlParams.get('code');

                    if (token) {
                        // If has token from email, verify directly
                        this.verifyWithToken(token);
                    } else if (code) {
                        // If has code in URL, fill it and go to step 2
                        this.step = 2;
                        this.fillCodeFromString(code);
                        this.checkEmailStatus();
                    } else {
                        // No token/code, check email status
                        this.checkEmailStatus();
                    }
                },
                
                showManualVerification() {
                    this.tokenStatus = '';
                    this.validatingToken = false;
                    this.step = 1;
                    this.checkEmailStatus();
                },

                async checkEmailStatus() {
                    if (!this.accessToken) {
                        // No access token, show warning
                        this.showToast('warning', 'Sesión requerida', 'Inicia sesión para verificar tu email');
                        return;
                    }

                    try {
                        const response = await fetch('/api/auth/email/status', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Authorization': `Bearer ${this.accessToken}`,
                            },
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            if (response.status === 401) {
                                this.showToast('warning', 'Sesión expirada', 'Tu sesión ha expirado, inicia sesión nuevamente');
                                setTimeout(() => window.location.href = '/login', 2000);
                                return;
                            }
                            throw new Error(data.message || 'Error al verificar estado');
                        }

                        // Store user email
                        this.userEmail = data.email || '';

                        // Check if already verified - redirect based on roles
                        if (data.isVerified) {
                            this.handleRedirect();
                            return;
                        }

                        // Check resend availability
                        if (!data.canResend && data.resendAvailableAt) {
                            this.startCountdownFromDate(data.resendAvailableAt);
                        }

                    } catch (err) {
                        console.error('Check status error:', err);
                        this.showToast('danger', 'Error', err.message || 'Error al verificar estado del email');
                    }
                },

                async verifyWithToken(token) {
                    this.validatingToken = true;
                    this.tokenStatus = 'validating';

                    try {
                        const response = await fetch('/api/auth/email/verify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ token }),
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(this.extractErrorMessage(data));
                        }

                        // Verification successful
                        this.tokenStatus = 'success';
                        this.successMessage = data.message || 'Tu correo ha sido verificado correctamente.';
                        
                        // Wait 2 seconds before redirecting
                        setTimeout(() => {
                            this.handleRedirect();
                        }, 2000);

                    } catch (err) {
                        console.error('Token verification error:', err);
                        this.tokenStatus = 'error';
                        this.errorMessage = err.message || 'El enlace de verificación no es válido o ha expirado.';
                    } finally {
                        this.validatingToken = false;
                        // Not clearing tokenStatus here to keep showing success/error message
                    }
                },

                fillCodeFromString(codeStr) {
                    const digits = codeStr.replace(/\D/g, '').slice(0, 6);
                    for (let i = 0; i < 6; i++) {
                        this.codeDigits[i] = digits[i] || '';
                    }
                },

                validateCode() {
                    this.errors.code = '';
                    const code = this.codeDigits.join('');
                    this.formData.code = code;

                    if (!code || code.length !== 6) {
                        this.errors.code = 'El código debe tener 6 dígitos';
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
                    // Only allow numbers
                    if (!/^\d*$/.test(value)) {
                        this.codeDigits[index] = '';
                        return;
                    }
                    // Keep only one digit
                    this.codeDigits[index] = value.slice(-1);
                    
                    // Clear error while typing
                    if (this.errors.code) {
                        this.errors.code = '';
                    }

                    // Move to next input if has value
                    if (this.codeDigits[index] && index < 5) {
                        setTimeout(() => {
                            const inputs = document.querySelector('#codeInputs').querySelectorAll('input');
                            if (inputs[index + 1]) {
                                inputs[index + 1].focus();
                            }
                        }, 0);
                    }
                },

                onCodeBackspace(index, event) {
                    // If backspace pressed and field is empty, move to previous
                    if (!this.codeDigits[index] && index > 0) {
                        event.preventDefault();
                        setTimeout(() => {
                            const inputs = document.querySelector('#codeInputs').querySelectorAll('input');
                            if (inputs[index - 1]) {
                                inputs[index - 1].focus();
                                this.codeDigits[index - 1] = '';
                            }
                        }, 0);
                    }
                },

                onCodePaste(event) {
                    event.preventDefault();
                    const pastedData = event.clipboardData.getData('text');
                    
                    // Clean and validate only digits
                    const digits = pastedData.replace(/\D/g, '');
                    
                    if (digits.length !== 6) {
                        this.errors.code = 'El código debe tener exactamente 6 dígitos';
                        return;
                    }
                    
                    // Distribute digits to inputs
                    for (let i = 0; i < 6; i++) {
                        this.codeDigits[i] = digits[i];
                    }
                    
                    // Focus last input
                    const inputs = document.querySelector('#codeInputs').querySelectorAll('input');
                    if (inputs[5]) {
                        inputs[5].focus();
                    }
                    
                    // Clear error
                    this.errors.code = '';
                },

                async sendVerificationEmail() {
                    if (!this.accessToken) {
                        this.showToast('warning', 'Sesión requerida', 'Inicia sesión para enviar el email de verificación');
                        return;
                    }

                    this.loading = true;
                    this.verificationError = false;

                    try {
                        const response = await fetch('/api/auth/email/verify/resend', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Authorization': `Bearer ${this.accessToken}`,
                            },
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            if (response.status === 401) {
                                this.showToast('warning', 'Sesión expirada', 'Tu sesión ha expirado, inicia sesión nuevamente');
                                setTimeout(() => window.location.href = '/login', 2000);
                                return;
                            }
                            if (response.status === 429) {
                                this.showToast('warning', 'Límite alcanzado', 'Has alcanzado el límite de intentos. Por favor espera unos minutos.');
                                return;
                            }
                            throw new Error(this.extractErrorMessage(data));
                        }

                        // Email sent successfully
                        this.showToast('success', '¡Email enviado!', data.message || 'Revisa tu bandeja de entrada');
                        
                        // Move to step 2
                        this.step = 2;
                        
                        // Start 60 second countdown
                        this.startCountdown(60);

                    } catch (err) {
                        console.error('Send email error:', err);
                        this.showToast('danger', 'Error', err.message || 'Error al enviar el email');
                    } finally {
                        this.loading = false;
                    }
                },

                async verifyWithCode() {
                    if (!this.validateCode()) {
                        return;
                    }

                    this.loading = true;
                    this.verificationError = false;
                    this.verificationSuccess = false;

                    try {
                        const response = await fetch('/api/auth/email/verify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                code: this.formData.code,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(this.extractErrorMessage(data));
                        }

                        // Verification successful - show message and redirect
                        this.verificationSuccess = true;
                        this.successMessage = '¡Email verificado exitosamente! Redirigiendo...';

                        setTimeout(() => {
                            this.handleRedirect();
                        }, 2000);

                    } catch (err) {
                        console.error('Verify code error:', err);
                        this.verificationError = true;
                        this.errorMessage = err.message || 'Error al verificar el código';
                        // Clear code inputs
                        this.codeDigits = ['', '', '', '', '', ''];
                    } finally {
                        this.loading = false;
                    }
                },

                startCountdown(seconds) {
                    this.canResend = false;
                    this.resendCountdown = seconds;

                    if (this.resendTimer) {
                        clearInterval(this.resendTimer);
                    }

                    this.resendTimer = setInterval(() => {
                        this.resendCountdown--;
                        if (this.resendCountdown <= 0) {
                            clearInterval(this.resendTimer);
                            this.canResend = true;
                            this.resendCountdown = 0;
                        }
                    }, 1000);
                },

                startCountdownFromDate(dateString) {
                    const availableAt = new Date(dateString);
                    const now = new Date();
                    const diffMs = availableAt - now;
                    const diffSeconds = Math.max(0, Math.ceil(diffMs / 1000));

                    if (diffSeconds > 0) {
                        this.startCountdown(diffSeconds);
                    }
                },

                showToast(type, title, message) {
                    // AdminLTE v3 Toast
                    $(document).Toasts('create', {
                        class: type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning' : 'bg-danger'),
                        title: title,
                        body: message,
                        autohide: true,
                        delay: 6000,
                        icon: type === 'success' ? 'fas fa-check-circle' : (type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-exclamation-circle'),
                    });
                },

                /**
                 * Extrae el mensaje de error de la respuesta de la API
                 */
                extractErrorMessage(data) {
                    if (data.message && typeof data.message === 'string') {
                        return data.message;
                    }
                    if (data.errors && typeof data.errors === 'object') {
                        const firstField = Object.keys(data.errors)[0];
                        if (firstField && Array.isArray(data.errors[firstField])) {
                            return data.errors[firstField][0];
                        }
                    }
                    if (data.error && typeof data.error === 'string') {
                        return data.error;
                    }
                    return 'Error de verificación. Inténtalo nuevamente.';
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

                handleRedirect() {
                    const payload = this.decodeJWT(this.accessToken);
                    const roles = payload.roles || [];

                    if (roles.length === 1) {
                         // Auto-asignar el único rol
                         const activeRole = {
                            code: roles[0].code,
                            company_id: roles[0].company_id || null,
                            company_name: roles[0].company_name || null
                        };
                        localStorage.setItem('active_role', JSON.stringify(activeRole));
                        const dashboardUrl = this.getDashboardUrl(roles[0].code);
                        window.location.href = dashboardUrl;
                    } else if (roles.length > 1) {
                        window.location.href = '/auth-flow/role-selector';
                    } else {
                         // Auto-asignar rol por defecto (USER) si no hay roles
                         const defaultRole = {
                            code: 'USER',
                            company_id: null,
                            company_name: null
                        };
                        localStorage.setItem('active_role', JSON.stringify(defaultRole));
                        window.location.href = '/app/user/dashboard';
                    }
                },
            };
        }
    </script>
@stop
