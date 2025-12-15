@extends('layouts.authenticated')

@section('title', 'Configuración de la Empresa')

@section('content_header', 'Configuración de la Empresa')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Configuración</li>
@endsection

@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Spinner de Carga -->
        <div id="loadingSpinner" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando configuración de la empresa...</p>
        </div>

        <!-- Contenedor Principal (oculto inicialmente) -->
        <div id="settingsContainer" style="display: none;">
            <!-- Nota: Las notificaciones ahora usan Toasts de AdminLTE v3 -->

            <!-- 1. INFORMACIÓN BÁSICA -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Información Básica
                    </h3>
                </div>
                <form id="basicInfoForm">
                    <div class="card-body">
                        <div class="row">
                            <!-- Nombre Comercial -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre Comercial <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           placeholder="Ej: Acme Corporation" required minlength="2" maxlength="200">
                                    <small class="form-text text-muted">Mínimo 2 caracteres</small>
                                </div>
                            </div>

                            <!-- Nombre Legal -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="legal_name">Nombre Legal</label>
                                    <input type="text" class="form-control" id="legal_name" name="legal_name"
                                           placeholder="Ej: Acme Corp S.A." maxlength="200">
                                </div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea class="form-control" id="description" name="description"
                                      placeholder="Describe tu empresa..." rows="3" maxlength="1000"></textarea>
                            <small class="form-text text-muted">Máximo 1000 caracteres</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Información Básica
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. INFORMACIÓN DE CONTACTO -->
            <div class="card card-outline card-info mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-phone"></i> Información de Contacto
                    </h3>
                </div>
                <form id="contactInfoForm">
                    <div class="card-body">
                        <div class="row">
                            <!-- Email de Soporte -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="support_email">Email de Soporte</label>
                                    <input type="email" class="form-control" id="support_email" name="support_email"
                                           placeholder="support@example.com" maxlength="255">
                                </div>
                            </div>

                            <!-- Teléfono -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Teléfono</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           placeholder="+56912345678" maxlength="20">
                                </div>
                            </div>
                        </div>

                        <!-- Sitio Web -->
                        <div class="form-group">
                            <label for="website">Sitio Web</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   placeholder="https://example.com" maxlength="255">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Guardar Contacto
                        </button>
                    </div>
                </form>
            </div>

            <!-- 3. DIRECCIÓN DE CONTACTO -->
            <div class="card card-outline card-secondary mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-map-marker-alt"></i> Dirección de Contacto
                    </h3>
                </div>
                <form id="addressForm">
                    <div class="card-body">
                        <!-- Dirección -->
                        <div class="form-group">
                            <label for="contact_address">Dirección</label>
                            <input type="text" class="form-control" id="contact_address" name="contact_address"
                                   placeholder="Calle, número, apartamento" maxlength="255">
                        </div>

                        <div class="row">
                            <!-- Ciudad -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_city">Ciudad</label>
                                    <input type="text" class="form-control" id="contact_city" name="contact_city"
                                           placeholder="Santiago" maxlength="100">
                                </div>
                            </div>

                            <!-- Estado/Provincia -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_state">Estado/Provincia</label>
                                    <input type="text" class="form-control" id="contact_state" name="contact_state"
                                           placeholder="Metropolitana" maxlength="100">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- País -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_country">País</label>
                                    <input type="text" class="form-control" id="contact_country" name="contact_country"
                                           placeholder="Chile" maxlength="100">
                                </div>
                            </div>

                            <!-- Código Postal -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_postal_code">Código Postal</label>
                                    <input type="text" class="form-control" id="contact_postal_code" name="contact_postal_code"
                                           placeholder="8320000" maxlength="20">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-save"></i> Guardar Dirección
                        </button>
                    </div>
                </form>
            </div>

            <!-- 4. INFORMACIÓN LEGAL -->
            <div class="card card-outline card-warning mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-contract"></i> Información Legal
                    </h3>
                </div>
                <form id="legalInfoForm">
                    <div class="card-body">
                        <div class="row">
                            <!-- Tax ID / RUT / NIT -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_id">RUT / NIT / Tax ID</label>
                                    <input type="text" class="form-control" id="tax_id" name="tax_id"
                                           placeholder="12.345.678-9" maxlength="50">
                                </div>
                            </div>

                            <!-- Representante Legal -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="legal_representative">Representante Legal</label>
                                    <input type="text" class="form-control" id="legal_representative" name="legal_representative"
                                           placeholder="Juan Pérez García" maxlength="200">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Guardar Información Legal
                        </button>
                    </div>
                </form>
            </div>

            <!-- 5. CONFIGURACIÓN OPERATIVA -->
            <div class="card card-outline card-success mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i> Configuración Operativa
                    </h3>
                </div>
                <form id="operativeForm">
                    <div class="card-body">
                        <!-- Zona Horaria -->
                        <div class="form-group">
                            <label for="timezone">Zona Horaria</label>
                            <select class="form-control" id="timezone" name="timezone">
                                <option value="">Seleccionar zona horaria...</option>
                                <optgroup label="América Latina">
                                    <option value="America/Santiago">Chile (Santiago)</option>
                                    <option value="America/Argentina/Buenos_Aires">Argentina (Buenos Aires)</option>
                                    <option value="America/Bogota">Colombia (Bogotá)</option>
                                    <option value="America/Lima">Perú (Lima)</option>
                                    <option value="America/Mexico_City">México (Ciudad de México)</option>
                                    <option value="America/Caracas">Venezuela (Caracas)</option>
                                    <option value="America/La_Paz">Bolivia (La Paz)</option>
                                </optgroup>
                                <optgroup label="Europa">
                                    <option value="Europe/Madrid">España (Madrid)</option>
                                    <option value="Europe/London">Reino Unido (Londres)</option>
                                    <option value="Europe/Paris">Francia (París)</option>
                                    <option value="Europe/Berlin">Alemania (Berlín)</option>
                                </optgroup>
                                <optgroup label="Asia">
                                    <option value="Asia/Shanghai">China (Shanghái)</option>
                                    <option value="Asia/Tokyo">Japón (Tokio)</option>
                                    <option value="Asia/Bangkok">Tailandia (Bangkok)</option>
                                </optgroup>
                                <optgroup label="UTC">
                                    <option value="UTC">UTC (Tiempo Universal)</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Horarios de Operación -->
                        <div class="form-group">
                            <label>Horarios de Operación</label>
                            <div id="businessHoursContainer">
                                <!-- Será poblado por JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar Configuración Operativa
                        </button>
                    </div>
                </form>
            </div>

            <!-- 5.5 CONFIGURACIÓN DE FUNCIONALIDADES -->
            <div class="card card-outline card-purple mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i> Configuración de Funcionalidades
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Áreas / Departamentos</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="areasEnabledToggle">
                            <label class="custom-control-label" for="areasEnabledToggle">
                                Habilitar gestión de Áreas y Departamentos
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Si se habilita, podrás gestionar áreas y asignar tickets a departamentos específicos.
                        </small>
                    </div>
                </div>
            </div>

            <!-- 6. BRANDING -->
            <div class="card card-outline card-danger mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-palette"></i> Branding
                    </h3>
                </div>
                <form id="brandingForm">
                    <div class="card-body">
                        <!-- Logo y Favicon en fila -->
                        <div class="row d-flex align-items-stretch">
                            <!-- Logo de la Empresa -->
                            <div class="col-md-6 d-flex flex-column">
                                <div class="form-group flex-grow-1 d-flex flex-column">
                                    <label class="mb-3"><i class="fas fa-image mr-1"></i> Logo de la Empresa</label>
                                    
                                    <div class="bg-light rounded p-3 text-center border flex-grow-1 d-flex flex-column justify-content-center" id="logoSection">
                                        <!-- Preview del logo -->
                                        <div id="logoPreviewContainer" style="display: none;">
                                            <img id="logoImg" src="" alt="Logo de la empresa" class="img-fluid mb-2"
                                                style="max-width: 150px; max-height: 80px; object-fit: contain;">
                                        </div>
                                        
                                        <!-- Placeholder -->
                                        <div id="logoPlaceholder">
                                            <i class="fas fa-building fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0 small">Sin logo</p>
                                        </div>
                                        
                                        <!-- Input file oculto -->
                                        <input type="file" id="logo_file"
                                            accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                                            style="display: none;">
                                        <input type="hidden" id="logo_url" name="logo_url">
                                        
                                        <!-- Botones -->
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-primary btn-sm" id="btnSelectLogo">
                                                <i class="fas fa-upload mr-1"></i>
                                                <span id="btnSelectLogoText">Subir</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm ml-1" id="btnRemoveLogo" style="display: none;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Progreso -->
                                        <div id="logoUploadProgress" class="mt-2" style="display: none;">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                                    role="progressbar" style="width: 100%"></div>
                                            </div>
                                        </div>
                                        
                                        <small class="text-muted d-block mt-2" style="font-size: 0.7rem;">
                                            JPEG, PNG, GIF, WebP, SVG • Máx 5MB
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Favicon -->
                            <div class="col-md-6 d-flex flex-column">
                                <div class="form-group flex-grow-1 d-flex flex-column">
                                    <label class="mb-3"><i class="fas fa-star mr-1"></i> Favicon</label>
                                    
                                    <div class="bg-light rounded p-3 text-center border flex-grow-1 d-flex flex-column justify-content-center" id="faviconSection">
                                        <!-- Preview del favicon -->
                                        <div id="faviconPreviewContainer" style="display: none;">
                                            <img id="faviconImg" src="" alt="Favicon de la empresa" class="img-fluid mb-2"
                                                style="max-width: 64px; max-height: 64px; object-fit: contain;">
                                        </div>
                                        
                                        <!-- Placeholder -->
                                        <div id="faviconPlaceholder">
                                            <i class="fas fa-star fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0 small">Sin favicon</p>
                                        </div>
                                        
                                        <!-- Input file oculto -->
                                        <input type="file" id="favicon_file"
                                            accept="image/x-icon,image/png,image/jpeg,image/gif"
                                            style="display: none;">
                                        <input type="hidden" id="favicon_url" name="favicon_url">
                                        
                                        <!-- Botones -->
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-secondary btn-sm" id="btnSelectFavicon">
                                                <i class="fas fa-upload mr-1"></i>
                                                <span id="btnSelectFaviconText">Subir</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm ml-1" id="btnRemoveFavicon" style="display: none;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Progreso -->
                                        <div id="faviconUploadProgress" class="mt-2" style="display: none;">
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" 
                                                    role="progressbar" style="width: 100%"></div>
                                            </div>
                                        </div>
                                        
                                        <small class="text-muted d-block mt-2" style="font-size: 0.7rem;">
                                            ICO, PNG, JPEG, GIF • Cuadrado • Máx 1MB
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colores -->
                        <div class="row">
                            <!-- Color Primario -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="primary_color">Color Primario</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color">
                                        <input type="text" class="form-control" id="primary_color_text" placeholder="#007bff" maxlength="7">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <div id="primaryColorPreview" style="width: 20px; height: 20px; border-radius: 4px; background-color: #007bff;"></div>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Color Secundario -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="secondary_color">Color Secundario</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color">
                                        <input type="text" class="form-control" id="secondary_color_text" placeholder="#6c757d" maxlength="7">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <div id="secondaryColorPreview" style="width: 20px; height: 20px; border-radius: 4px; background-color: #6c757d;"></div>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save"></i> Guardar Branding
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Mensaje de Error General -->
        <div id="generalErrorAlert" class="alert alert-danger alert-dismissible fade show" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="generalErrorText">Error al cargar la configuración</span>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    </div>
</section>

<script>
    // Variables globales
    const apiUrl = '/api';
    const token = localStorage.getItem('access_token');
    let currentCompanyData = null;

    // ========== CONFIGURATION ==========
    const CONFIG = {
        TOAST_DELAY: 3000
    };

    // ========== TOAST NOTIFICATIONS (AdminLTE v3 - Same as Categories) ==========
    const Toast = {
        /**
         * Show success toast
         */
        success(message, title = 'Éxito') {
            $(document).Toasts('create', {
                class: 'bg-success',
                title: title,
                body: message,
                autohide: true,
                delay: CONFIG.TOAST_DELAY,
                icon: 'fas fa-check-circle'
            });
        },

        /**
         * Show error toast
         */
        error(message, title = 'Error') {
            $(document).Toasts('create', {
                class: 'bg-danger',
                title: title,
                body: message,
                autohide: true,
                delay: CONFIG.TOAST_DELAY + 2000, // Errors stay longer
                icon: 'fas fa-exclamation-circle'
            });
        },

        /**
         * Show warning toast
         */
        warning(message, title = 'Advertencia') {
            $(document).Toasts('create', {
                class: 'bg-warning',
                title: title,
                body: message,
                autohide: true,
                delay: CONFIG.TOAST_DELAY,
                icon: 'fas fa-exclamation-triangle'
            });
        },

        /**
         * Show info toast
         */
        info(message, title = 'Información') {
            $(document).Toasts('create', {
                class: 'bg-info',
                title: title,
                body: message,
                autohide: true,
                delay: CONFIG.TOAST_DELAY,
                icon: 'fas fa-info-circle'
            });
        }
    };

    // ========== API ERROR HANDLING ==========
    /**
     * Translate specific validation errors to Spanish
     */
    function translateValidationError(message) {
        const translations = {
            // Company name
            'The name field is required': 'El nombre es obligatorio',
            'The name must be at least 2 characters': 'El nombre debe tener al menos 2 caracteres',
            'The name must not be greater than 200 characters': 'El nombre no puede exceder 200 caracteres',
            'The name has already been taken': 'Ya existe una empresa con este nombre',
            
            // Email
            'The support email must be a valid email address': 'El email de soporte debe ser válido',
            'The support email must not be greater than 255 characters': 'El email no puede exceder 255 caracteres',
            
            // Phone
            'The phone must not be greater than 20 characters': 'El teléfono no puede exceder 20 caracteres',
            
            // Website
            'The website must be a valid URL': 'El sitio web debe ser una URL válida',
            'The website must not be greater than 255 characters': 'El sitio web no puede exceder 255 caracteres',
            
            // Tax ID
            'The tax id must not be greater than 50 characters': 'El RUT/NIT no puede exceder 50 caracteres',
            
            // Logo/Favicon
            'The logo must be an image': 'El logo debe ser una imagen',
            'The logo must not be greater than 5120 kilobytes': 'El logo no puede superar 5MB',
            'The favicon must be an image': 'El favicon debe ser una imagen',
            'The favicon must not be greater than 1024 kilobytes': 'El favicon no puede superar 1MB',
            
            // Generic
            'The given data was invalid': 'Los datos proporcionados no son válidos'
        };

        // Check for exact match
        if (translations[message]) {
            return translations[message];
        }

        // Check for partial matches
        for (const [key, value] of Object.entries(translations)) {
            if (message.toLowerCase().includes(key.toLowerCase())) {
                return value;
            }
        }

        return message;
    }

    /**
     * Translate API errors to user-friendly Spanish messages
     */
    function translateError(error, response = null) {
        console.error('[Settings] API Error:', error);

        // If we have a response object with status
        if (response) {
            const status = response.status;
            
            switch (status) {
                case 401:
                case 419:
                    return 'Tu sesión ha expirado. Por favor, recarga la página e inicia sesión nuevamente.';
                case 403:
                    return error?.message || 'No tienes permiso para realizar esta acción.';
                case 404:
                    return 'La empresa no fue encontrada.';
                case 422:
                    // Validation errors - extract all messages
                    if (error?.errors) {
                        const messages = Object.values(error.errors)
                            .flat()
                            .map(msg => translateValidationError(msg))
                            .join('. ');
                        return messages;
                    }
                    return error?.message || 'Error de validación. Por favor revisa los campos.';
                case 429:
                    return 'Demasiadas solicitudes. Por favor espera un momento antes de intentar nuevamente.';
                case 500:
                    return 'Error interno del servidor. Por favor intenta nuevamente más tarde.';
                default:
                    return error?.message || 'Error al procesar la solicitud. Inténtalo nuevamente.';
            }
        }

        // Fallback for generic errors
        return error?.message || error || 'Error al procesar la solicitud. Inténtalo nuevamente.';
    }

    // ====== ALERTAS (Legacy compatibility + Toast wrapper) ======
    function showSuccessAlert(message) {
        Toast.success(message);
    }

    function showErrorAlert(message) {
        Toast.error(message);
    }

    // ====== INICIALIZACIÓN ======
    document.addEventListener('DOMContentLoaded', function() {
        loadCompanySettings();
        loadFeatureSettings();
        setupEventListeners();
    });

    // ====== SETUP DE EVENTOS ======
    function setupEventListeners() {
        // Form: Información Básica
        document.getElementById('basicInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings('basicInfoForm', ['name', 'legal_name', 'description']);
        });

        // Form: Información de Contacto
        document.getElementById('contactInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings('contactInfoForm', ['support_email', 'phone', 'website']);
        });

        // Form: Dirección
        document.getElementById('addressForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings('addressForm', ['contact_address', 'contact_city', 'contact_state', 'contact_country', 'contact_postal_code']);
        });

        // Form: Información Legal
        document.getElementById('legalInfoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings('legalInfoForm', ['tax_id', 'legal_representative']);
        });

        // Form: Configuración Operativa
        document.getElementById('operativeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings('operativeForm', ['timezone', 'business_hours']);
        });

        // Form: Branding
        document.getElementById('brandingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings('brandingForm', ['logo_url', 'favicon_url', 'primary_color', 'secondary_color']);
        });

        // ====== LOGO FILE UPLOAD ======
        const btnSelectLogo = document.getElementById('btnSelectLogo');
        const btnSelectLogoText = document.getElementById('btnSelectLogoText');
        const btnRemoveLogo = document.getElementById('btnRemoveLogo');
        const logoFileInput = document.getElementById('logo_file');
        const logoUploadProgress = document.getElementById('logoUploadProgress');
        const logoPreviewContainer = document.getElementById('logoPreviewContainer');
        const logoPlaceholder = document.getElementById('logoPlaceholder');
        const logoImg = document.getElementById('logoImg');
        const logoUrlInput = document.getElementById('logo_url');

        // Función para actualizar UI según si hay logo o no
        function updateLogoUI(hasLogo) {
            if (hasLogo) {
                logoPreviewContainer.style.display = 'block';
                logoPlaceholder.style.display = 'none';
                btnSelectLogoText.textContent = 'Cambiar logo';
                btnRemoveLogo.style.display = 'inline-block';
            } else {
                logoPreviewContainer.style.display = 'none';
                logoPlaceholder.style.display = 'block';
                btnSelectLogoText.textContent = 'Subir logo';
                btnRemoveLogo.style.display = 'none';
            }
        }

        // Click en botón abre el selector de archivo
        btnSelectLogo.addEventListener('click', function () {
            logoFileInput.click();
        });

        // Eliminar logo
        btnRemoveLogo.addEventListener('click', function () {
            if (!confirm('¿Eliminar logo? Esta acción no se puede deshacer.')) {
                return;
            }

            logoUrlInput.value = '';
            logoImg.src = '';
            currentCompanyData.logoUrl = null;
            updateLogoUI(false);

            saveSettings('brandingForm', ['logo_url', 'favicon_url', 'primary_color', 'secondary_color']);
            showSuccessAlert('Logo eliminado correctamente');
        });

        // Cuando se selecciona un archivo
        logoFileInput.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            // Validar tamaño (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showErrorAlert('El logo no puede superar 5MB');
                this.value = '';
                return;
            }

            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            if (!allowedTypes.includes(file.type)) {
                showErrorAlert('Formato no soportado. Use JPEG, PNG, GIF, WebP o SVG.');
                this.value = '';
                return;
            }

            // Mostrar progreso
            btnSelectLogo.disabled = true;
            logoUploadProgress.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('logo', file);

                const response = await fetch(`${apiUrl}/companies/${currentCompanyData.id}/logo`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    if (response.status === 401 || response.status === 419) {
                        throw new Error('Sesión expirada. Por favor recarga la página.');
                    }
                    throw new Error('Error del servidor. Intente nuevamente.');
                }

                const data = await response.json();

                if (response.ok && data.data?.logoUrl) {
                    logoUrlInput.value = data.data.logoUrl;
                    logoImg.src = data.data.logoUrl;
                    currentCompanyData.logoUrl = data.data.logoUrl;
                    updateLogoUI(true);
                    showSuccessAlert('Logo subido correctamente');
                } else {
                    // Use translateError for better error messages
                    const errorMessage = translateError(data, response);
                    throw new Error(errorMessage);
                }
            } catch (error) {
                console.error('[Logo Upload] Error:', error);
                showErrorAlert(error.message || 'No se pudo subir el logo. Intente nuevamente.');
            } finally {
                btnSelectLogo.disabled = false;
                logoUploadProgress.style.display = 'none';
                logoFileInput.value = '';
            }
        });

        // ====== FAVICON FILE UPLOAD ======
        const btnSelectFavicon = document.getElementById('btnSelectFavicon');
        const btnSelectFaviconText = document.getElementById('btnSelectFaviconText');
        const btnRemoveFavicon = document.getElementById('btnRemoveFavicon');
        const faviconFileInput = document.getElementById('favicon_file');
        const faviconUploadProgress = document.getElementById('faviconUploadProgress');
        const faviconPreviewContainer = document.getElementById('faviconPreviewContainer');
        const faviconPlaceholder = document.getElementById('faviconPlaceholder');
        const faviconImg = document.getElementById('faviconImg');
        const faviconUrlInput = document.getElementById('favicon_url');

        // Función para actualizar UI según si hay favicon o no
        function updateFaviconUI(hasFavicon) {
            if (hasFavicon) {
                faviconPreviewContainer.style.display = 'block';
                faviconPlaceholder.style.display = 'none';
                btnSelectFaviconText.textContent = 'Cambiar';
                btnRemoveFavicon.style.display = 'inline-block';
            } else {
                faviconPreviewContainer.style.display = 'none';
                faviconPlaceholder.style.display = 'block';
                btnSelectFaviconText.textContent = 'Subir';
                btnRemoveFavicon.style.display = 'none';
            }
        }

        // Click en botón abre el selector de archivo
        btnSelectFavicon.addEventListener('click', function () {
            faviconFileInput.click();
        });

        // Eliminar favicon
        btnRemoveFavicon.addEventListener('click', function () {
            if (!confirm('¿Eliminar favicon?')) {
                return;
            }

            faviconUrlInput.value = '';
            faviconImg.src = '';
            currentCompanyData.faviconUrl = null;
            updateFaviconUI(false);

            saveSettings('brandingForm', ['logo_url', 'favicon_url', 'primary_color', 'secondary_color']);
            showSuccessAlert('Favicon eliminado correctamente');
        });

        // Cuando se selecciona un archivo
        faviconFileInput.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            // Validar tamaño (max 1MB para favicons)
            if (file.size > 1 * 1024 * 1024) {
                showErrorAlert('El favicon no puede superar 1MB');
                this.value = '';
                return;
            }

            // Validar tipo
            const allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showErrorAlert('Formato no soportado. Use ICO, PNG, JPEG o GIF.');
                this.value = '';
                return;
            }

            // Mostrar progreso
            btnSelectFavicon.disabled = true;
            faviconUploadProgress.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('favicon', file);

                const response = await fetch(`${apiUrl}/companies/${currentCompanyData.id}/favicon`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    if (response.status === 401 || response.status === 419) {
                        throw new Error('Sesión expirada. Por favor recarga la página.');
                    }
                    throw new Error('Error del servidor. Intente nuevamente.');
                }

                const data = await response.json();

                if (response.ok && data.data?.faviconUrl) {
                    faviconUrlInput.value = data.data.faviconUrl;
                    faviconImg.src = data.data.faviconUrl;
                    currentCompanyData.faviconUrl = data.data.faviconUrl;
                    updateFaviconUI(true);
                    showSuccessAlert('Favicon subido correctamente');
                } else {
                    // Use translateError for better error messages
                    const errorMessage = translateError(data, response);
                    throw new Error(errorMessage);
                }
            } catch (error) {
                console.error('[Favicon Upload] Error:', error);
                showErrorAlert(error.message || 'No se pudo subir el favicon. Intente nuevamente.');
            } finally {
                btnSelectFavicon.disabled = false;
                faviconUploadProgress.style.display = 'none';
                faviconFileInput.value = '';
            }
        });

        // Sync color inputs
        document.getElementById('primary_color').addEventListener('change', function() {
            document.getElementById('primary_color_text').value = this.value;
            document.getElementById('primaryColorPreview').style.backgroundColor = this.value;
        });

        document.getElementById('secondary_color').addEventListener('change', function() {
            document.getElementById('secondary_color_text').value = this.value;
            document.getElementById('secondaryColorPreview').style.backgroundColor = this.value;
        });

        document.getElementById('primary_color_text').addEventListener('change', function() {
            if (isValidHexColor(this.value)) {
                document.getElementById('primary_color').value = this.value;
                document.getElementById('primaryColorPreview').style.backgroundColor = this.value;
            }
        });

        document.getElementById('secondary_color_text').addEventListener('change', function() {
            if (isValidHexColor(this.value)) {
                document.getElementById('secondary_color').value = this.value;
                document.getElementById('secondaryColorPreview').style.backgroundColor = this.value;
            }
        });

        // Toggle: Áreas
        document.getElementById('areasEnabledToggle').addEventListener('change', function() {
            updateAreaSettings(this.checked);
        });
    }

    // ====== CARGAR CONFIGURACIÓN DE EMPRESA ======
    async function loadCompanySettings() {
        const companyId = '{{ $companyId }}';

        try {
            const response = await fetch(`${apiUrl}/companies/${companyId}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw { data, response };
            }

            currentCompanyData = data.data || data;
            populateForm();
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('settingsContainer').style.display = 'block';
        } catch (error) {
            console.error('[Settings] Error loading configuration:', error);
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('generalErrorAlert').style.display = 'block';
            
            // Use translateError if we have response info
            const errorMessage = error.response 
                ? translateError(error.data, error.response)
                : 'Error al cargar la configuración de la empresa';
            document.getElementById('generalErrorText').textContent = errorMessage;
        }
    }

    // ====== CARGAR CONFIGURACIÓN DE FUNCIONALIDADES ======
    async function loadFeatureSettings() {
        try {
            const response = await fetch(`${apiUrl}/companies/me/settings/areas-enabled`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                document.getElementById('areasEnabledToggle').checked = data.data.areas_enabled;
            }
        } catch (error) {
            console.error('[Settings] Error loading areas settings:', error);
        }
    }

    // ====== ACTUALIZAR CONFIGURACIÓN DE ÁREAS ======
    async function updateAreaSettings(enabled) {
        const toggle = document.getElementById('areasEnabledToggle');
        const label = toggle.nextElementSibling;
        const originalLabel = label.textContent;
        
        // Optimistic UI update handled by checkbox, but let's show loading state
        label.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
        toggle.disabled = true;

        try {
            const response = await fetch(`${apiUrl}/companies/me/settings/areas-enabled`, {
                method: 'PATCH',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ enabled: enabled })
            });

            const data = await response.json();

            if (!response.ok) {
                throw { data, response };
            }

            if (data.success) {
                showSuccessAlert('Configuración de áreas actualizada correctamente');
            } else {
                throw new Error(data.message || 'Error al actualizar');
            }
        } catch (error) {
            console.error('[Settings] Error updating areas:', error);
            
            // Use translateError if we have response info
            const errorMessage = error.response 
                ? translateError(error.data, error.response)
                : (error.message || 'Error al actualizar la configuración');
            showErrorAlert(errorMessage);
            toggle.checked = !enabled; // Revert change
        } finally {
            toggle.disabled = false;
            label.textContent = originalLabel.trim(); // Restore label
        }
    }

    // ====== POBLAR FORMULARIO CON DATOS ======
    function populateForm() {
        // Información Básica
        setInputValue('name', currentCompanyData.name);
        setInputValue('legal_name', currentCompanyData.legalName);
        setInputValue('description', currentCompanyData.description);

        // Información de Contacto
        setInputValue('support_email', currentCompanyData.supportEmail);
        setInputValue('phone', currentCompanyData.phone);
        setInputValue('website', currentCompanyData.website);

        // Dirección
        setInputValue('contact_address', currentCompanyData.contactAddress);
        setInputValue('contact_city', currentCompanyData.contactCity);
        setInputValue('contact_state', currentCompanyData.contactState);
        setInputValue('contact_country', currentCompanyData.contactCountry);
        setInputValue('contact_postal_code', currentCompanyData.contactPostalCode);

        // Información Legal
        setInputValue('tax_id', currentCompanyData.taxId);
        setInputValue('legal_representative', currentCompanyData.legalRepresentative);

        // Configuración Operativa
        setInputValue('timezone', currentCompanyData.timezone);
        renderBusinessHours(currentCompanyData.businessHours);

        // Branding
        setInputValue('logo_url', currentCompanyData.logoUrl);
        setInputValue('favicon_url', currentCompanyData.faviconUrl);
        setInputValue('primary_color', currentCompanyData.primaryColor);
        setInputValue('secondary_color', currentCompanyData.secondaryColor);

        // Mostrar logo si existe
        const logoImg = document.getElementById('logoImg');
        const logoPreviewContainer = document.getElementById('logoPreviewContainer');
        const logoPlaceholder = document.getElementById('logoPlaceholder');
        const btnSelectLogoText = document.getElementById('btnSelectLogoText');
        const btnRemoveLogo = document.getElementById('btnRemoveLogo');
        
        if (currentCompanyData.logoUrl) {
            logoImg.src = currentCompanyData.logoUrl;
            logoPreviewContainer.style.display = 'block';
            logoPlaceholder.style.display = 'none';
            btnSelectLogoText.textContent = 'Cambiar logo';
            btnRemoveLogo.style.display = 'inline-block';
        } else {
            logoPreviewContainer.style.display = 'none';
            logoPlaceholder.style.display = 'block';
            btnSelectLogoText.textContent = 'Subir logo';
            btnRemoveLogo.style.display = 'none';
        }

        // Mostrar favicon si existe
        const faviconImg = document.getElementById('faviconImg');
        const faviconPreviewContainer = document.getElementById('faviconPreviewContainer');
        const faviconPlaceholder = document.getElementById('faviconPlaceholder');
        const btnSelectFaviconText = document.getElementById('btnSelectFaviconText');
        const btnRemoveFavicon = document.getElementById('btnRemoveFavicon');
        
        if (currentCompanyData.faviconUrl) {
            faviconImg.src = currentCompanyData.faviconUrl;
            faviconPreviewContainer.style.display = 'block';
            faviconPlaceholder.style.display = 'none';
            btnSelectFaviconText.textContent = 'Cambiar';
            btnRemoveFavicon.style.display = 'inline-block';
        } else {
            faviconPreviewContainer.style.display = 'none';
            faviconPlaceholder.style.display = 'block';
            btnSelectFaviconText.textContent = 'Subir';
            btnRemoveFavicon.style.display = 'none';
        }

        // Sync color displays
        document.getElementById('primary_color_text').value = currentCompanyData.primaryColor || '#007bff';
        document.getElementById('secondary_color_text').value = currentCompanyData.secondaryColor || '#6c757d';
        document.getElementById('primaryColorPreview').style.backgroundColor = currentCompanyData.primaryColor || '#007bff';
        document.getElementById('secondaryColorPreview').style.backgroundColor = currentCompanyData.secondaryColor || '#6c757d';
    }

    // ====== HELPERS ======
    function setInputValue(selector, value) {
        const element = document.getElementById(selector);
        if (element) element.value = value || '';
    }

    function isValidHexColor(color) {
        return /^#[0-9A-Fa-f]{6}$/.test(color);
    }

    // ====== RENDERIZAR HORARIOS DE OPERACIÓN ======
    function renderBusinessHours(businessHours) {
        const container = document.getElementById('businessHoursContainer');
        container.innerHTML = '';

        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayLabels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        const hoursData = businessHours || {};

        days.forEach((day, index) => {
            const data = hoursData[day] || { open: '09:00', close: '18:00', is_open: true };

            const dayHtml = `
                <div class="card card-sm mb-2" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <strong>${dayLabels[index]}</strong>
                            </div>
                            <div class="col-md-2">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input dayToggle" id="toggle_${day}"
                                           data-day="${day}" ${data.is_open ? 'checked' : ''}>
                                    <label class="custom-control-label" for="toggle_${day}">
                                        Abierto
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Desde</span>
                                    </div>
                                    <input type="time" class="form-control openTime" data-day="${day}"
                                           value="${data.open || '09:00'}" ${!data.is_open ? 'disabled' : ''}>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Hasta</span>
                                    </div>
                                    <input type="time" class="form-control closeTime" data-day="${day}"
                                           value="${data.close || '18:00'}" ${!data.is_open ? 'disabled' : ''}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML += dayHtml;
        });

        // Agregar listeners a los toggles
        document.querySelectorAll('.dayToggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const day = this.dataset.day;
                const openInput = document.querySelector(`.openTime[data-day="${day}"]`);
                const closeInput = document.querySelector(`.closeTime[data-day="${day}"]`);
                openInput.disabled = !this.checked;
                closeInput.disabled = !this.checked;
            });
        });
    }

    // ====== RECOLECTAR HORARIOS ======
    function collectBusinessHours() {
        const businessHours = {};
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        days.forEach(day => {
            const toggle = document.getElementById(`toggle_${day}`);
            const openInput = document.querySelector(`.openTime[data-day="${day}"]`);
            const closeInput = document.querySelector(`.closeTime[data-day="${day}"]`);

            businessHours[day] = {
                is_open: toggle.checked,
                open: toggle.checked ? openInput.value : null,
                close: toggle.checked ? closeInput.value : null
            };
        });

        return businessHours;
    }

    // ====== GUARDAR CONFIGURACIÓN ======
    async function saveSettings(formId, fields) {
        const companyId = '{{ $companyId }}';
        const payload = {};

        fields.forEach(field => {
            if (field === 'business_hours') {
                payload[field] = collectBusinessHours();
            } else {
                const input = document.getElementById(field);
                if (input) {
                    payload[field] = input.value || null;
                }
            }
        });

        // Mostrar estado de carga
        const submitBtn = document.querySelector(`#${formId} button[type="submit"]`);
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        try {
            const response = await fetch(`${apiUrl}/companies/${companyId}`, {
                method: 'PATCH',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                // Use translateError with response status for better error messages
                const errorMessage = translateError(data, response);
                throw new Error(errorMessage);
            }

            currentCompanyData = data.data || data;
            showSuccessAlert('Cambios guardados correctamente');
        } catch (error) {
            console.error('[Settings] Error saving:', error);
            showErrorAlert(error.message || 'Error al guardar los cambios');
        } finally {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
</script>

<style>
    .form-control-color {
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
    }

    .card-sm {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }

    .card-body.p-3 {
        padding: 1rem !important;
    }

    .input-group-sm .form-control,
    .input-group-sm .input-group-text {
        font-size: 0.875rem;
    }
</style>
@endsection