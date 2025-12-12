{{-- 
    Widget Principal de Helpdesk
    
    Esta vista maneja el flujo completo:
    1. Spinner con mensajes de estado
    2. Validación de API Key (empresa registrada)
    3. Verificación de usuario
    4. Login automático o formulario de registro
    5. Carga de vista de tickets
--}}
@extends('layouts.widget')

@section('title', 'Centro de Soporte - Helpdesk')

@section('content')
    <div id="widget-app">
        {{-- ============================================================== --}}
        {{-- LOADER / SPINNER --}}
        {{-- ============================================================== --}}
        <div id="widget-loader" class="widget-loader" @if($hasToken) style="display: none;" @endif>
            <div class="spinner"></div>
            <h5 class="mb-3">Conectando con Helpdesk...</h5>
            
            <div class="status-list">
                <div class="status-item" id="status-connect">
                    <span class="status-icon"><i class="fas fa-circle-notch fa-spin"></i></span>
                    <span class="status-text">Conectando con Helpdesk API...</span>
                </div>
                <div class="status-item" id="status-company">
                    <span class="status-icon"><i class="far fa-circle"></i></span>
                    <span class="status-text">Verificando empresa...</span>
                </div>
                <div class="status-item" id="status-user">
                    <span class="status-icon"><i class="far fa-circle"></i></span>
                    <span class="status-text">Verificando cuenta de usuario...</span>
                </div>
                <div class="status-item" id="status-auth">
                    <span class="status-icon"><i class="far fa-circle"></i></span>
                    <span class="status-text">Iniciando sesión...</span>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- EMPRESA NO REGISTRADA --}}
        {{-- ============================================================== --}}
        <div id="widget-company-not-found" class="company-not-found" style="display: none;">
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <h4 class="text-warning mb-3">Tu empresa no está registrada</h4>
            <p class="text-muted mb-4">
                Para usar el Centro de Soporte, tu empresa debe estar registrada en Helpdesk.
            </p>
            
            <div class="card card-outline card-warning">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-clipboard-list mr-2"></i>Solicita acceso:</h6>
                    <p class="mb-3">
                        <a href="https://proyecto-de-ultimo-minuto.online/solicitud-empresa" 
                           target="_blank" 
                           class="btn btn-warning btn-block">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Formulario de Solicitud
                        </a>
                    </p>
                    
                    <hr>
                    
                    <h6 class="mb-3"><i class="fas fa-headset mr-2"></i>O contacta al administrador:</h6>
                    <p class="mb-2">
                        <i class="fas fa-envelope mr-2 text-muted"></i>
                        <a href="mailto:lukqs05@gmail.com">lukqs05@gmail.com</a>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-phone mr-2 text-muted"></i>
                        <a href="tel:+59162119184">62119184</a>
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- FORMULARIO DE REGISTRO (usuario no existe) --}}
        {{-- ============================================================== --}}
        <div id="widget-register-form" class="widget-auth-form" style="display: none;">
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="Helpdesk">
            </div>
            
            <div class="card card-outline card-primary">
                <div class="card-header text-center">
                    <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Crear cuenta en Helpdesk</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center mb-3">
                        Para acceder al Centro de Soporte, crea tu contraseña.
                    </p>
                    
                    {{-- Info del usuario (auto-detectada) --}}
                    <div class="alert alert-light border mb-3">
                        <div class="mb-2">
                            <i class="fas fa-envelope mr-2 text-muted"></i>
                            <strong id="register-email"></strong>
                        </div>
                        <div>
                            <i class="fas fa-user mr-2 text-muted"></i>
                            <span id="register-name"></span>
                        </div>
                    </div>
                    
                    <form id="form-register">
                        <div class="form-group">
                            <label for="register-password">
                                <i class="fas fa-lock mr-1"></i>Crea tu contraseña
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="register-password" 
                                   name="password"
                                   placeholder="Mínimo 8 caracteres"
                                   required
                                   minlength="8">
                        </div>
                        
                        <div class="form-group">
                            <label for="register-password-confirm">
                                <i class="fas fa-lock mr-1"></i>Confirmar contraseña
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="register-password-confirm" 
                                   name="password_confirmation"
                                   placeholder="Repite la contraseña"
                                   required>
                        </div>
                        
                        <div id="register-error" class="alert alert-danger" style="display: none;"></div>
                        
                        <button type="submit" class="btn btn-primary btn-block" id="btn-register">
                            <i class="fas fa-check mr-2"></i>Crear cuenta y continuar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- FORMULARIO DE LOGIN (fallback) --}}
        {{-- ============================================================== --}}
        <div id="widget-login-form" class="widget-auth-form" style="display: none;">
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="Helpdesk">
            </div>
            
            <div class="card card-outline card-info">
                <div class="card-header text-center">
                    <h5 class="mb-0"><i class="fas fa-sign-in-alt mr-2"></i>Iniciar sesión</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center mb-3">
                        Ingresa tu contraseña para acceder.
                    </p>
                    
                    {{-- Email (auto-detectado) --}}
                    <div class="form-group">
                        <label><i class="fas fa-envelope mr-1"></i>Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="login-email" 
                               readonly>
                    </div>
                    
                    <form id="form-login">
                        <div class="form-group">
                            <label for="login-password">
                                <i class="fas fa-lock mr-1"></i>Contraseña
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="login-password" 
                                   name="password"
                                   required>
                        </div>
                        
                        <div id="login-error" class="alert alert-danger" style="display: none;"></div>
                        
                        <button type="submit" class="btn btn-info btn-block" id="btn-login">
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- CONTENEDOR DE TICKETS --}}
        {{-- Si llegamos aquí con hasToken true, redirigimos a /widget/tickets --}}
        {{-- ============================================================== --}}
        @if($hasToken)
            <script>
                // Redirigir a la vista de tickets
                window.location.href = '{{ config("app.url") }}/widget/tickets?token={{ $token }}';
            </script>
            <div class="widget-loader">
                <div class="spinner"></div>
                <h5>Cargando tickets...</h5>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // ========================================================================
    // CONFIGURACIÓN
    // ========================================================================
    const CONFIG = {
        apiUrl: '{{ config("app.url") }}/api',
        apiKey: '{{ $apiKey ?? "" }}',
        widgetUrl: '{{ config("app.url") }}/widget',
        // Datos del usuario del sistema externo (pasados por el paquete)
        userData: {
            email: '{{ request()->query("email", "") }}',
            firstName: '{{ request()->query("first_name", "") }}',
            lastName: '{{ request()->query("last_name", "") }}',
        }
    };

    // ========================================================================
    // ESTADO
    // ========================================================================
    const state = {
        currentStep: 'init',
        apiKeyValid: false,
        userExists: false,
        token: null,
        company: null,
    };

    // ========================================================================
    // SELECTORES DOM
    // ========================================================================
    const DOM = {
        loader: document.getElementById('widget-loader'),
        companyNotFound: document.getElementById('widget-company-not-found'),
        registerForm: document.getElementById('widget-register-form'),
        loginForm: document.getElementById('widget-login-form'),
        ticketsContainer: document.getElementById('widget-tickets-container'),
        
        // Status items
        statusConnect: document.getElementById('status-connect'),
        statusCompany: document.getElementById('status-company'),
        statusUser: document.getElementById('status-user'),
        statusAuth: document.getElementById('status-auth'),
    };

    // ========================================================================
    // UTILIDADES
    // ========================================================================
    
    function updateStatus(stepId, status) {
        const el = document.getElementById('status-' + stepId);
        if (!el) return;
        
        const icon = el.querySelector('.status-icon i');
        
        // Reset classes
        el.classList.remove('active', 'completed', 'error');
        
        switch(status) {
            case 'active':
                el.classList.add('active');
                icon.className = 'fas fa-circle-notch fa-spin';
                break;
            case 'completed':
                el.classList.add('completed');
                icon.className = 'fas fa-check-circle';
                break;
            case 'error':
                el.classList.add('error');
                icon.className = 'fas fa-times-circle';
                break;
            default:
                icon.className = 'far fa-circle';
        }
    }

    function showView(viewName) {
        // Ocultar todas las vistas (con verificación null-safe)
        if (DOM.loader) DOM.loader.style.display = 'none';
        if (DOM.companyNotFound) DOM.companyNotFound.style.display = 'none';
        if (DOM.registerForm) DOM.registerForm.style.display = 'none';
        if (DOM.loginForm) DOM.loginForm.style.display = 'none';
        if (DOM.ticketsContainer) DOM.ticketsContainer.style.display = 'none';
        
        // Mostrar la vista solicitada
        switch(viewName) {
            case 'loader':
                if (DOM.loader) DOM.loader.style.display = 'flex';
                break;
            case 'company-not-found':
                if (DOM.companyNotFound) DOM.companyNotFound.style.display = 'block';
                break;
            case 'register':
                if (DOM.registerForm) {
                    DOM.registerForm.style.display = 'block';
                    // Pre-llenar datos
                    const emailEl = document.getElementById('register-email');
                    const nameEl = document.getElementById('register-name');
                    if (emailEl) emailEl.textContent = CONFIG.userData.email;
                    if (nameEl) nameEl.textContent = CONFIG.userData.firstName + ' ' + CONFIG.userData.lastName;
                }
                break;
            case 'login':
                if (DOM.loginForm) {
                    DOM.loginForm.style.display = 'block';
                    const loginEmailEl = document.getElementById('login-email');
                    if (loginEmailEl) loginEmailEl.value = CONFIG.userData.email;
                }
                break;
            case 'tickets':
                if (DOM.ticketsContainer) DOM.ticketsContainer.style.display = 'block';
                break;
        }
    }

    async function apiCall(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Service-Key': CONFIG.apiKey,
            },
        };
        
        const response = await fetch(CONFIG.apiUrl + endpoint, {
            ...defaultOptions,
            ...options,
            headers: { ...defaultOptions.headers, ...options.headers },
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw { response, data };
        }
        
        return data;
    }

    // ========================================================================
    // FLUJO PRINCIPAL
    // ========================================================================

    async function initWidget() {
        // Si ya tenemos token, ir directo a tickets
        if (window.widgetTokenManager && window.widgetTokenManager.isAuthenticated()) {
            console.log('[Widget] Token ya presente, mostrando tickets');
            showView('tickets');
            return;
        }

        // Si no hay API Key, no podemos continuar
        if (!CONFIG.apiKey) {
            console.error('[Widget] No API Key provided');
            showView('company-not-found');
            return;
        }

        // Si no hay email del usuario, algo está mal
        if (!CONFIG.userData.email) {
            console.error('[Widget] No user email provided');
            showView('login');
            return;
        }

        showView('loader');
        
        try {
            // PASO 1: Validar API Key
            updateStatus('connect', 'active');
            await sleep(500); // UX delay
            
            const keyResult = await apiCall('/external/validate-key', { method: 'POST' });
            
            if (!keyResult.success) {
                throw new Error('API Key inválida');
            }
            
            state.company = keyResult.company;
            state.apiKeyValid = true;
            updateStatus('connect', 'completed');
            
            // PASO 2: Verificar empresa
            updateStatus('company', 'active');
            await sleep(300);
            updateStatus('company', 'completed');
            
            // PASO 3: Verificar usuario
            updateStatus('user', 'active');
            await sleep(300);
            
            const userResult = await apiCall('/external/check-user', {
                method: 'POST',
                body: JSON.stringify({ email: CONFIG.userData.email }),
            });
            
            state.userExists = userResult.exists;
            updateStatus('user', 'completed');
            
            // PASO 4: Login o mostrar registro
            updateStatus('auth', 'active');
            
            if (state.userExists) {
                // Usuario existe, intentar login automático
                await sleep(300);
                
                const loginResult = await apiCall('/external/login', {
                    method: 'POST',
                    body: JSON.stringify({ email: CONFIG.userData.email }),
                });
                
                if (loginResult.success && loginResult.accessToken) {
                    updateStatus('auth', 'completed');
                    await sleep(300);
                    
                    // Guardar token y mostrar tickets
                    window.widgetTokenManager.setToken(loginResult.accessToken);
                    loadTicketsView(loginResult.accessToken);
                } else {
                    // Login automático falló, mostrar formulario
                    updateStatus('auth', 'error');
                    await sleep(300);
                    showView('login');
                }
            } else {
                // Usuario no existe, mostrar formulario de registro
                updateStatus('auth', 'completed');
                await sleep(300);
                showView('register');
            }
            
        } catch (error) {
            console.error('[Widget] Error en flujo:', error);
            
            // Determinar qué vista mostrar según el error
            if (error.data && error.data.code === 'INVALID_API_KEY') {
                showView('company-not-found');
            } else if (error.data && error.data.code === 'COMPANY_NOT_FOUND') {
                showView('company-not-found');
            } else {
                // Error genérico, mostrar login como fallback
                showView('login');
            }
        }
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function loadTicketsView(token) {
        // Redirigir a la vista de tickets con el token
        const baseUrl = '{{ config("app.url") }}/widget/tickets';
        window.location.href = baseUrl + '?token=' + encodeURIComponent(token);
    }

    // ========================================================================
    // EVENT HANDLERS
    // ========================================================================

    // Formulario de registro
    document.getElementById('form-register')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btn-register');
        const errorDiv = document.getElementById('register-error');
        const password = document.getElementById('register-password').value;
        const passwordConfirm = document.getElementById('register-password-confirm').value;
        
        // Validación básica
        if (password !== passwordConfirm) {
            errorDiv.textContent = 'Las contraseñas no coinciden.';
            errorDiv.style.display = 'block';
            return;
        }
        
        if (password.length < 8) {
            errorDiv.textContent = 'La contraseña debe tener al menos 8 caracteres.';
            errorDiv.style.display = 'block';
            return;
        }
        
        errorDiv.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creando cuenta...';
        
        try {
            const result = await apiCall('/external/register', {
                method: 'POST',
                body: JSON.stringify({
                    email: CONFIG.userData.email,
                    firstName: CONFIG.userData.firstName,
                    lastName: CONFIG.userData.lastName,
                    password: password,
                    password_confirmation: passwordConfirm,
                }),
            });
            
            if (result.success && result.accessToken) {
                window.widgetTokenManager.setToken(result.accessToken);
                loadTicketsView(result.accessToken);
            }
        } catch (error) {
            console.error('[Widget] Error en registro:', error);
            
            let message = 'Error al crear la cuenta.';
            if (error.data && error.data.errors) {
                const errors = error.data.errors;
                message = Object.values(errors).flat().join(' ');
            }
            
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Crear cuenta y continuar';
        }
    });

    // Formulario de login
    document.getElementById('form-login')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btn-login');
        const errorDiv = document.getElementById('login-error');
        const password = document.getElementById('login-password').value;
        
        errorDiv.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Iniciando sesión...';
        
        try {
            const result = await apiCall('/external/login-manual', {
                method: 'POST',
                body: JSON.stringify({
                    email: CONFIG.userData.email,
                    password: password,
                }),
            });
            
            if (result.success && result.accessToken) {
                window.widgetTokenManager.setToken(result.accessToken);
                loadTicketsView(result.accessToken);
            }
        } catch (error) {
            console.error('[Widget] Error en login:', error);
            
            let message = 'Email o contraseña incorrectos.';
            if (error.data && error.data.message) {
                message = error.data.message;
            }
            
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Iniciar sesión';
        }
    });

    // ========================================================================
    // INICIAR
    // ========================================================================
    document.addEventListener('DOMContentLoaded', initWidget);

})();
</script>
@endpush
