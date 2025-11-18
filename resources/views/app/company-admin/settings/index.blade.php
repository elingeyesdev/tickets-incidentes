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
            <!-- Alerta de Éxito -->
            <div id="successAlert" class="alert alert-success alert-dismissible fade show" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <span id="successText">Cambios guardados correctamente</span>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>

            <!-- Alerta de Error -->
            <div id="errorAlert" class="alert alert-danger alert-dismissible fade show" style="display: none;">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText">Error al guardar los cambios</span>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>

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

            <!-- 6. BRANDING -->
            <div class="card card-outline card-danger mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-palette"></i> Branding
                    </h3>
                </div>
                <form id="brandingForm">
                    <div class="card-body">
                        <!-- Logo URL -->
                        <div class="form-group">
                            <label for="logo_url">URL del Logo</label>
                            <div class="input-group">
                                <input type="url" class="form-control" id="logo_url" name="logo_url"
                                       placeholder="https://example.com/logo.png" maxlength="500">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="previewLogoBth">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                </div>
                            </div>
                            <div id="logoPreview" style="margin-top: 10px; display: none;">
                                <img id="logoImg" src="" alt="Logo Preview" style="max-width: 200px; max-height: 100px;">
                            </div>
                        </div>

                        <!-- Favicon URL -->
                        <div class="form-group">
                            <label for="favicon_url">URL del Favicon</label>
                            <input type="url" class="form-control" id="favicon_url" name="favicon_url"
                                   placeholder="https://example.com/favicon.ico" maxlength="500">
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

    // ====== INICIALIZACIÓN ======
    document.addEventListener('DOMContentLoaded', function() {
        loadCompanySettings();
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

        // Preview Logo
        document.getElementById('previewLogoBth').addEventListener('click', function() {
            const url = document.getElementById('logo_url').value;
            if (url) {
                document.getElementById('logoImg').src = url;
                document.getElementById('logoPreview').style.display = 'block';
            } else {
                alert('Por favor ingresa una URL válida');
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
    }

    // ====== CARGAR CONFIGURACIÓN DE EMPRESA ======
    function loadCompanySettings() {
        const companyId = '{{ $companyId }}';

        fetch(`${apiUrl}/companies/${companyId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            currentCompanyData = data.data || data;
            populateForm();
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('settingsContainer').style.display = 'block';
        })
        .catch(error => {
            console.error('Error cargando configuración:', error);
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('generalErrorAlert').style.display = 'block';
            document.getElementById('generalErrorText').textContent = 'Error al cargar la configuración de la empresa';
        });
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
    function saveSettings(formId, fields) {
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

        fetch(`${apiUrl}/companies/${companyId}`, {
            method: 'PATCH',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Error al guardar');
                });
            }
            return response.json();
        })
        .then(data => {
            currentCompanyData = data.data || data;
            showSuccessAlert('Cambios guardados correctamente');

            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorAlert(error.message || 'Error al guardar los cambios');

            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }

    // ====== ALERTAS ======
    function showSuccessAlert(message) {
        const alert = document.getElementById('successAlert');
        document.getElementById('successText').textContent = message;
        alert.style.display = 'block';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    }

    function showErrorAlert(message) {
        const alert = document.getElementById('errorAlert');
        document.getElementById('errorText').textContent = message;
        alert.style.display = 'block';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
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