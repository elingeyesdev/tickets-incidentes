{{-- 
    Widget Principal de Helpdesk v2.0
    
    Esta vista maneja el flujo completo:
    1. Pantalla de conexión (OAuth-style)
    2. Validación de API Key (empresa registrada)
    3. Verificación de usuario
    4. Login automático o formulario de registro
    5. Carga de vista de tickets
    
    Diseño: Logos side-by-side estilo OAuth (como GitHub)
--}}
@extends('layouts.widget')

@section('title', 'Centro de Soporte - Helpdesk')

@push('css')
<style>
    /* ================================================================
       OAUTH-STYLE CONNECTION SCREEN
       ================================================================ */
    
    .oauth-connection {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 450px;
        padding: 2rem;
        text-align: center;
    }

    /* Logos Container */
    .oauth-logos {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 2rem;
    }

    .oauth-logo {
        width: 90px;
        height: 90px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 2px solid #e9ecef;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .oauth-logo:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .oauth-logo img {
        max-width: 70px;
        max-height: 70px;
        object-fit: contain;
    }

    .oauth-logo .logo-placeholder {
        font-size: 2rem;
        color: #6c757d;
    }

    /* Connection Line */
    .oauth-connection-line {
        width: 100px;
        height: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin: 0 1rem;
    }

    /* Estados de la línea */
    .connection-line-content {
        width: 100%;
        height: 100%;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Línea punteada (desconectado) */
    .line-disconnected {
        width: 100%;
        height: 3px;
        background: repeating-linear-gradient(
            to right,
            #dee2e6,
            #dee2e6 8px,
            transparent 8px,
            transparent 16px
        );
    }

    /* Línea animada (conectando) */
    .line-connecting {
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #007bff 0%, #00d4ff 50%, #007bff 100%);
        background-size: 200% 100%;
        animation: connectingAnimation 1.5s ease-in-out infinite;
        border-radius: 2px;
    }

    @keyframes connectingAnimation {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Línea con checkmark (conectado) */
    .line-connected {
        width: 100%;
        height: 4px;
        background: #28a745;
        border-radius: 2px;
        position: relative;
    }

    .line-connected::after {
        content: '✓';
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        background: #28a745;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
        animation: popIn 0.3s ease;
    }

    @keyframes popIn {
        0% { transform: translate(-50%, -50%) scale(0); }
        50% { transform: translate(-50%, -50%) scale(1.2); }
        100% { transform: translate(-50%, -50%) scale(1); }
    }

    /* Línea con X (error) */
    .line-error {
        width: 100%;
        height: 4px;
        background: #dc3545;
        border-radius: 2px;
        position: relative;
    }

    .line-error::after {
        content: '×';
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        background: #dc3545;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: bold;
    }

    /* OAuth Text */
    .oauth-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #343a40;
        margin-bottom: 0.75rem;
    }

    .oauth-subtitle {
        font-size: 1rem;
        color: #6c757d;
        margin-bottom: 2rem;
        max-width: 380px;
    }

    /* Connect Button */
    .btn-oauth-connect {
        padding: 0.75rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .btn-oauth-connect:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .btn-oauth-connect:disabled {
        transform: none;
        box-shadow: none;
    }

    /* Status Steps (durante conexión) */
    .oauth-status-steps {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
        text-align: left;
    }

    .status-step {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #6c757d;
        opacity: 0.5;
        transition: opacity 0.3s, color 0.3s;
    }

    .status-step.active {
        opacity: 1;
        color: #007bff;
    }

    .status-step.completed {
        opacity: 1;
        color: #28a745;
    }

    .status-step.error {
        opacity: 1;
        color: #dc3545;
    }

    .status-step i {
        width: 20px;
        text-align: center;
    }

    /* ================================================================
       COMPANY NOT FOUND (Error State)
       ================================================================ */
    
    .oauth-error-card {
        max-width: 450px;
        margin: 1.5rem auto 0;
    }

    .oauth-error-card .card {
        border: none;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
    }

    .oauth-error-card .card-body {
        padding: 1.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0;
    }

    .contact-item i {
        width: 20px;
        color: #6c757d;
    }

    /* ================================================================
       AUTH FORMS (Register/Login) - Estilo AdminLTE Oficial
       ================================================================ */
    
    .widget-auth-form {
        max-width: 420px;
        margin: 0 auto;
        padding: 1.5rem;
    }

    .widget-auth-form .oauth-logos {
        margin-bottom: 1.5rem;
    }

    .widget-auth-form .card {
        border: none;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
    }

    /* Estilo login-box-msg como en AdminLTE oficial */
    .widget-auth-form .login-box-msg {
        margin: 0;
        padding: 0 20px 20px;
        text-align: center;
        color: #666;
    }

    /* Input groups estilo AdminLTE */
    .widget-auth-form .input-group {
        margin-bottom: 1rem;
    }

    .widget-auth-form .input-group .form-control {
        border-right: 0;
        height: calc(2.25rem + 2px);
    }

    .widget-auth-form .input-group .form-control:focus {
        border-color: #80bdff;
        box-shadow: none;
    }

    .widget-auth-form .input-group .form-control:focus + .input-group-append .input-group-text {
        border-color: #80bdff;
    }

    .widget-auth-form .input-group-append .input-group-text {
        background-color: transparent;
        border-left: 0;
        transition: border-color 0.15s ease-in-out;
    }

    /* Validación de campos */
    .widget-auth-form .form-control.is-invalid {
        border-color: #dc3545;
    }

    .widget-auth-form .form-control.is-invalid + .input-group-append .input-group-text {
        border-color: #dc3545;
    }

    .widget-auth-form .invalid-feedback {
        display: none;
        font-size: 80%;
        color: #dc3545;
        width: 100%;
        margin-top: 0.25rem;
    }

    .widget-auth-form .invalid-feedback:not(:empty) {
        display: block;
    }

    /* Callout estilo AdminLTE */
    .widget-auth-form .callout {
        border-radius: 0.25rem;
        box-shadow: none;
        border-left: 5px solid #17a2b8;
        background-color: #f4f6f9;
        padding: 0.75rem 1rem;
    }

    .widget-auth-form .callout-info {
        border-left-color: #17a2b8;
    }

    /* Botones con loading */
    .widget-auth-form .btn .btn-loading {
        display: none;
    }

    .widget-auth-form .btn:disabled .btn-text {
        display: none;
    }

    .widget-auth-form .btn:disabled .btn-loading {
        display: inline;
    }

    /* Alert dismissible */
    .widget-auth-form .alert-dismissible .close {
        padding: 0.5rem 1rem;
    }

    /* ================================================================
       TRANSITIONS
       ================================================================ */
    
    .view-transition {
        animation: fadeInUp 0.4s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@section('content')
    <div id="widget-app">
        {{-- ============================================================== --}}
        {{-- PANTALLA DE CONEXIÓN (OAuth-style) --}}
        {{-- ============================================================== --}}
        <div id="widget-connect-screen" class="oauth-connection" @if($hasToken) style="display: none;" @endif>
            
            {{-- Logos Side-by-Side --}}
            <div class="oauth-logos">
                {{-- Logo Helpdesk (actualizado) --}}
                <div class="oauth-logo" id="logo-helpdesk">
                    <img src="{{ asset('img/helpdesklogo.png') }}" alt="Helpdesk" onerror="this.parentElement.innerHTML='<i class=\'fas fa-headset logo-placeholder\'></i>'">
                </div>
                
                {{-- Línea de Conexión --}}
                <div class="oauth-connection-line">
                    <div class="connection-line-content">
                        <div id="connection-line" class="line-disconnected"></div>
                    </div>
                </div>
                
                {{-- Logo Empresa (se carga dinámicamente) --}}
                <div class="oauth-logo" id="logo-company">
                    <i class="fas fa-building logo-placeholder"></i>
                </div>
            </div>
            
            {{-- Título y Subtítulo --}}
            <h4 class="oauth-title" id="oauth-title">Conectar con Centro de Soporte</h4>
            <p class="oauth-subtitle" id="oauth-subtitle">
                Accede a soporte técnico, gestión de tickets y más.
            </p>
            
            {{-- Botón de Conexión --}}
            <button type="button" class="btn btn-primary btn-oauth-connect" id="btn-connect">
                Conectar con Helpdesk
            </button>
            
            {{-- Status Steps (se muestran durante conexión) --}}
            <div class="oauth-status-steps" id="oauth-status-steps" style="display: none;">
                <div class="status-step" id="step-api">
                    <i class="far fa-circle"></i>
                    <span>Conectando con Helpdesk API...</span>
                </div>
                <div class="status-step" id="step-company">
                    <i class="far fa-circle"></i>
                    <span>Verificando empresa...</span>
                </div>
                <div class="status-step" id="step-user">
                    <i class="far fa-circle"></i>
                    <span>Verificando cuenta de usuario...</span>
                </div>
                <div class="status-step" id="step-auth">
                    <i class="far fa-circle"></i>
                    <span>Iniciando sesión...</span>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- EMPRESA NO REGISTRADA (Error State con OAuth-style) --}}
        {{-- ============================================================== --}}
        <div id="widget-company-not-found" class="oauth-connection" style="display: none;">
            
            {{-- Logos con Error --}}
            <div class="oauth-logos">
                <div class="oauth-logo" id="logo-helpdesk-error">
                    <img src="{{ asset('img/helpdesklogo.png') }}" alt="Helpdesk" onerror="this.parentElement.innerHTML='<i class=\'fas fa-headset logo-placeholder\'></i>'">
                </div>
                
                <div class="oauth-connection-line">
                    <div class="connection-line-content">
                        <div class="line-error"></div>
                    </div>
                </div>
                
                <div class="oauth-logo" id="logo-company-error">
                    <i class="fas fa-building logo-placeholder"></i>
                </div>
            </div>
            
            {{-- Mensaje de Error --}}
            <h4 class="oauth-title text-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>Empresa no registrada
            </h4>
            <p class="oauth-subtitle">
                Tu empresa aún no tiene acceso al Centro de Soporte de Helpdesk.
            </p>
            
            {{-- Card con Opciones --}}
            <div class="oauth-error-card">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">
                            <i class="fas fa-clipboard-list mr-2 text-primary"></i>
                            Solicita acceso:
                        </h6>
                        
                        <a href="https://proyecto-de-ultimo-minuto.online/solicitud-empresa" 
                           target="_blank" 
                           class="btn btn-primary btn-block mb-3">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Formulario de Solicitud
                        </a>
                        
                        <hr class="my-3">
                        
                        <h6 class="mb-3">
                            <i class="fas fa-headset mr-2 text-info"></i>
                            Contacta al administrador:
                        </h6>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:lukqs05@gmail.com">lukqs05@gmail.com</a>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <a href="tel:+59162119184">+591 62119184</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- FORMULARIO DE REGISTRO (usuario no existe) --}}
        {{-- ============================================================== --}}
        <div id="widget-register-form" class="widget-auth-form view-transition" style="display: none;">
            
            {{-- Logos con conexión exitosa --}}
            <div class="oauth-logos">
                <div class="oauth-logo">
                    <img src="{{ asset('img/helpdesklogo.png') }}" alt="Helpdesk">
                </div>
                <div class="oauth-connection-line">
                    <div class="connection-line-content">
                        <div class="line-connected"></div>
                    </div>
                </div>
                <div class="oauth-logo" id="logo-company-register">
                    <i class="fas fa-building logo-placeholder"></i>
                </div>
            </div>
            
            <div class="card card-outline card-primary">
                <div class="card-header text-center">
                    <h5 class="mb-0">Crear cuenta en Helpdesk</h5>
                </div>
                <div class="card-body">
                    <p class="login-box-msg">Para acceder al Centro de Soporte, crea tu contraseña.</p>
                    
                    {{-- Error Alert (estilo oficial) --}}
                    <div id="register-alert-error" class="alert alert-danger alert-dismissible fade show" style="display: none;" role="alert">
                        <button type="button" class="close" onclick="this.parentElement.style.display='none'" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="register-error-message"></span>
                    </div>
                    
                    {{-- Info del usuario (auto-detectada) --}}
                    <div class="callout callout-info py-2 mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-envelope mr-2 text-info"></i>
                            <strong id="register-email"></strong>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user mr-2 text-info"></i>
                            <span id="register-name"></span>
                        </div>
                    </div>
                    
                    <form id="form-register" novalidate>
                        {{-- Password field (estilo oficial) --}}
                        <div class="input-group mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="register-password" 
                                   name="password"
                                   placeholder="Contraseña (mínimo 8 caracteres)"
                                   required
                                   minlength="8">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                            <span class="invalid-feedback d-block" id="register-password-error"></span>
                        </div>
                        
                        {{-- Password Confirmation field (estilo oficial) --}}
                        <div class="input-group mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="register-password-confirm" 
                                   name="password_confirmation"
                                   placeholder="Repetir contraseña"
                                   required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                            <span class="invalid-feedback d-block" id="register-password-confirm-error"></span>
                        </div>
                        
                        {{-- Submit button (estilo oficial) --}}
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-block" id="btn-register">
                                    <span class="btn-text">Crear cuenta y continuar</span>
                                    <span class="btn-loading" style="display: none;">
                                        <span class="spinner-border spinner-border-sm mr-2"></span>Procesando...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- FORMULARIO DE LOGIN (fallback) --}}
        {{-- ============================================================== --}}
        <div id="widget-login-form" class="widget-auth-form view-transition" style="display: none;">
            
            {{-- Logos con conexión exitosa --}}
            <div class="oauth-logos">
                <div class="oauth-logo">
                    <img src="{{ asset('img/helpdesklogo.png') }}" alt="Helpdesk">
                </div>
                <div class="oauth-connection-line">
                    <div class="connection-line-content">
                        <div class="line-connected"></div>
                    </div>
                </div>
                <div class="oauth-logo" id="logo-company-login">
                    <i class="fas fa-building logo-placeholder"></i>
                </div>
            </div>
            
            <div class="card card-outline card-primary">
                <div class="card-header text-center">
                    <h5 class="mb-0">Iniciar sesión</h5>
                </div>
                <div class="card-body">
                    <p class="login-box-msg">Ingresa tu contraseña para acceder al Centro de Soporte.</p>
                    
                    {{-- Error Alert (estilo oficial) --}}
                    <div id="login-alert-error" class="alert alert-danger alert-dismissible fade show" style="display: none;" role="alert">
                        <button type="button" class="close" onclick="this.parentElement.style.display='none'" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="login-error-message"></span>
                    </div>
                    
                    <form id="form-login" novalidate>
                        {{-- Email field (readonly, estilo oficial) --}}
                        <div class="input-group mb-3">
                            <input type="email" 
                                   class="form-control" 
                                   id="login-email" 
                                   placeholder="Correo Electrónico"
                                   readonly>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Password field (estilo oficial) --}}
                        <div class="input-group mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="login-password" 
                                   name="password"
                                   placeholder="Contraseña"
                                   required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                            <span class="invalid-feedback d-block" id="login-password-error"></span>
                        </div>
                        
                        {{-- Submit button (estilo oficial) --}}
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-block" id="btn-login">
                                    <span class="btn-text">Ingresar</span>
                                    <span class="btn-loading" style="display: none;">
                                        <span class="spinner-border spinner-border-sm mr-2"></span>Ingresando...
                                    </span>
                                </button>
                            </div>
                        </div>
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
            <div class="oauth-connection">
                <div class="oauth-logos">
                    <div class="oauth-logo">
                        <img src="{{ asset('logo.png') }}" alt="Helpdesk">
                    </div>
                    <div class="oauth-connection-line">
                        <div class="connection-line-content">
                            <div class="line-connecting"></div>
                        </div>
                    </div>
                    <div class="oauth-logo">
                        <i class="fas fa-ticket-alt logo-placeholder"></i>
                    </div>
                </div>
                <h4 class="oauth-title">Cargando tickets...</h4>
                <p class="oauth-subtitle">Redirigiendo al Centro de Soporte...</p>
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
        connectScreen: document.getElementById('widget-connect-screen'),
        companyNotFound: document.getElementById('widget-company-not-found'),
        registerForm: document.getElementById('widget-register-form'),
        loginForm: document.getElementById('widget-login-form'),
        
        // Connection elements
        btnConnect: document.getElementById('btn-connect'),
        connectionLine: document.getElementById('connection-line'),
        logoCompany: document.getElementById('logo-company'),
        oauthTitle: document.getElementById('oauth-title'),
        oauthSubtitle: document.getElementById('oauth-subtitle'),
        statusSteps: document.getElementById('oauth-status-steps'),
        
        // Steps
        stepApi: document.getElementById('step-api'),
        stepCompany: document.getElementById('step-company'),
        stepUser: document.getElementById('step-user'),
        stepAuth: document.getElementById('step-auth'),
    };

    // ========================================================================
    // UTILIDADES
    // ========================================================================
    
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function updateConnectionLine(status) {
        if (!DOM.connectionLine) return;
        
        DOM.connectionLine.className = '';
        
        switch(status) {
            case 'disconnected':
                DOM.connectionLine.className = 'line-disconnected';
                break;
            case 'connecting':
                DOM.connectionLine.className = 'line-connecting';
                break;
            case 'connected':
                DOM.connectionLine.className = 'line-connected';
                break;
            case 'error':
                DOM.connectionLine.className = 'line-error';
                break;
        }
    }

    function updateStep(stepId, status) {
        const step = document.getElementById('step-' + stepId);
        if (!step) return;
        
        const icon = step.querySelector('i');
        step.classList.remove('active', 'completed', 'error');
        
        switch(status) {
            case 'active':
                step.classList.add('active');
                icon.className = 'fas fa-circle-notch fa-spin';
                break;
            case 'completed':
                step.classList.add('completed');
                icon.className = 'fas fa-check-circle';
                break;
            case 'error':
                step.classList.add('error');
                icon.className = 'fas fa-times-circle';
                break;
            default:
                icon.className = 'far fa-circle';
        }
    }

    function setCompanyLogo(logoUrl) {
        const logos = [
            DOM.logoCompany,
            document.getElementById('logo-company-error'),
            document.getElementById('logo-company-register'),
            document.getElementById('logo-company-login'),
        ];
        
        logos.forEach(logo => {
            if (logo && logoUrl) {
                logo.innerHTML = `<img src="${logoUrl}" alt="Empresa" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-building logo-placeholder\\'></i>'">`;
            }
        });
    }

    function showView(viewName) {
        // Ocultar todas las vistas
        if (DOM.connectScreen) DOM.connectScreen.style.display = 'none';
        if (DOM.companyNotFound) DOM.companyNotFound.style.display = 'none';
        if (DOM.registerForm) DOM.registerForm.style.display = 'none';
        if (DOM.loginForm) DOM.loginForm.style.display = 'none';
        
        switch(viewName) {
            case 'connect':
                if (DOM.connectScreen) DOM.connectScreen.style.display = 'flex';
                break;
            case 'company-not-found':
                if (DOM.companyNotFound) DOM.companyNotFound.style.display = 'flex';
                break;
            case 'register':
                if (DOM.registerForm) DOM.registerForm.style.display = 'block';
                break;
            case 'login':
                if (DOM.loginForm) DOM.loginForm.style.display = 'block';
                break;
        }
        
        // Detener la animación de puntos cuando se cambia de vista
        stopDotsAnimation();
    }

    // ========================================================================
    // API CALLS
    // ========================================================================
    
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

    function loadTicketsView(token) {
        const url = CONFIG.widgetUrl + '/tickets?token=' + encodeURIComponent(token);
        window.location.href = url;
    }

    // ========================================================================
    // ANIMACIÓN DE PUNTOS SUSPENSIVOS
    // ========================================================================
    
    let dotsInterval = null;
    
    function startDotsAnimation() {
        const dots = ['.', '..', '...'];
        let index = 0;
        
        // Cambiar título y subtítulo
        DOM.oauthTitle.textContent = 'Conectando con Centro de Soporte';
        DOM.oauthSubtitle.innerHTML = '<span id="connecting-dots">.</span>';
        
        const dotsSpan = document.getElementById('connecting-dots');
        
        dotsInterval = setInterval(() => {
            index = (index + 1) % dots.length;
            if (dotsSpan) {
                dotsSpan.textContent = dots[index];
            }
        }, 750);
    }
    
    function stopDotsAnimation() {
        if (dotsInterval) {
            clearInterval(dotsInterval);
            dotsInterval = null;
        }
    }

    // ========================================================================
    // FLUJO DE CONEXIÓN
    // ========================================================================

    async function startConnection() {
        console.log('[Widget] Iniciando conexión...');
        
        // Cambiar UI a estado "conectando" - OCULTAR el botón y animar texto
        DOM.btnConnect.style.display = 'none';
        startDotsAnimation();
        DOM.statusSteps.style.display = 'flex';
        updateConnectionLine('connecting');
        
        try {
            // PASO 1: Validar API Key
            updateStep('api', 'active');
            await sleep(400);
            
            const keyResult = await apiCall('/external/validate-key', { method: 'POST' });
            
            if (!keyResult.success) {
                throw new Error('API Key inválida');
            }
            
            state.company = keyResult.company;
            state.apiKeyValid = true;
            
            // Actualizar logo de la empresa
            if (state.company.logoUrl) {
                setCompanyLogo(state.company.logoUrl);
            }
            
            updateStep('api', 'completed');
            
            // PASO 2: Verificar empresa
            updateStep('company', 'active');
            await sleep(300);
            updateStep('company', 'completed');
            
            // PASO 3: Verificar usuario
            updateStep('user', 'active');
            await sleep(300);
            
            const userResult = await apiCall('/external/check-user', {
                method: 'POST',
                body: JSON.stringify({ email: CONFIG.userData.email }),
            });
            
            state.userExists = userResult.exists;
            updateStep('user', 'completed');
            
            // PASO 4: Login o mostrar registro
            updateStep('auth', 'active');
            
            if (state.userExists) {
                // Usuario existe, intentar login automático
                await sleep(300);
                
                const loginResult = await apiCall('/external/login', {
                    method: 'POST',
                    body: JSON.stringify({ email: CONFIG.userData.email }),
                });
                
                if (loginResult.success && loginResult.accessToken) {
                    updateStep('auth', 'completed');
                    updateConnectionLine('connected');
                    await sleep(500);
                    
                    window.widgetTokenManager.setToken(loginResult.accessToken);
                    loadTicketsView(loginResult.accessToken);
                } else {
                    // Login automático falló, mostrar formulario
                    updateConnectionLine('connected');
                    document.getElementById('login-email').value = CONFIG.userData.email;
                    showView('login');
                }
            } else {
                // Usuario nuevo, mostrar registro
                updateConnectionLine('connected');
                document.getElementById('register-email').textContent = CONFIG.userData.email;
                document.getElementById('register-name').textContent = 
                    (CONFIG.userData.firstName + ' ' + CONFIG.userData.lastName).trim() || 'Usuario';
                showView('register');
            }
            
        } catch (error) {
            console.error('[Widget] Error en conexión:', error);
            
            updateConnectionLine('error');
            
            // Determinar tipo de error
            if (!state.apiKeyValid) {
                updateStep('api', 'error');
                showView('company-not-found');
            } else {
                // Error genérico
                updateStep('auth', 'error');
                DOM.btnConnect.disabled = false;
                DOM.btnConnect.innerHTML = '<i class="fas fa-redo mr-2"></i>Reintentar';
            }
        }
    }

    // ========================================================================
    // INICIALIZACIÓN
    // ========================================================================

    function initWidget() {
        console.log('[Widget v2] Inicializando...');
        
        // Si ya tenemos token, ir directo a tickets
        if (window.widgetTokenManager && window.widgetTokenManager.isAuthenticated()) {
            console.log('[Widget] Token ya presente, mostrando tickets');
            loadTicketsView(window.widgetTokenManager.getAccessToken());
            return;
        }

        // Si no hay API Key, mostrar error
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

        // Mostrar pantalla de conexión
        showView('connect');
        
        // Event listener para botón de conexión
        if (DOM.btnConnect) {
            DOM.btnConnect.addEventListener('click', startConnection);
        }
    }

    // ========================================================================
    // EVENT LISTENERS - FORMULARIOS
    // ========================================================================
    
    // Formulario de registro
    document.getElementById('form-register')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btn-register');
        const btnText = btn.querySelector('.btn-text');
        const btnLoading = btn.querySelector('.btn-loading');
        const alertError = document.getElementById('register-alert-error');
        const errorMessage = document.getElementById('register-error-message');
        const passwordInput = document.getElementById('register-password');
        const passwordConfirmInput = document.getElementById('register-password-confirm');
        const passwordError = document.getElementById('register-password-error');
        const passwordConfirmError = document.getElementById('register-password-confirm-error');
        
        const password = passwordInput.value;
        const passwordConfirm = passwordConfirmInput.value;
        
        // Reset errors
        passwordInput.classList.remove('is-invalid');
        passwordConfirmInput.classList.remove('is-invalid');
        passwordError.textContent = '';
        passwordConfirmError.textContent = '';
        alertError.style.display = 'none';
        
        // Validar contraseña
        if (password.length < 8) {
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = 'La contraseña debe tener al menos 8 caracteres';
            return;
        }
        
        // Validar confirmación
        if (password !== passwordConfirm) {
            passwordConfirmInput.classList.add('is-invalid');
            passwordConfirmError.textContent = 'Las contraseñas no coinciden';
            return;
        }
        
        // Mostrar loading
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        
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
            } else if (error.data && error.data.message) {
                message = error.data.message;
            }
            
            errorMessage.textContent = message;
            alertError.style.display = 'block';
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    });

    // Formulario de login
    document.getElementById('form-login')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btn-login');
        const btnText = btn.querySelector('.btn-text');
        const btnLoading = btn.querySelector('.btn-loading');
        const alertError = document.getElementById('login-alert-error');
        const errorMessage = document.getElementById('login-error-message');
        const passwordInput = document.getElementById('login-password');
        const passwordError = document.getElementById('login-password-error');
        
        const password = passwordInput.value;
        
        // Reset errors
        passwordInput.classList.remove('is-invalid');
        passwordError.textContent = '';
        alertError.style.display = 'none';
        
        // Validar contraseña
        if (!password) {
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = 'La contraseña es requerida';
            return;
        }
        
        // Mostrar loading
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        
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
            
            errorMessage.textContent = message;
            alertError.style.display = 'block';
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    });

    // ========================================================================
    // INICIAR
    // ========================================================================
    document.addEventListener('DOMContentLoaded', initWidget);

})();
</script>
@endpush
