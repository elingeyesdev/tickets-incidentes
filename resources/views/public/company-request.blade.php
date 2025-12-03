@extends('layouts.guest')

@section('title', 'Solicitud de Empresa - Helpdesk')

@section('css')
    <style>
        /* AdminLTE v3 - bs-stepper Minimal Customization */
        body {
            background-color: #f5f5f5;
        }

        .content-wrapper {
            margin-left: 0 !important;
            background-color: #f5f5f5;
        }

        .bs-stepper-content {
            padding: 20px;
        }

        .content {
            background: white;
            padding: 20px;
            border-radius: 0.25rem;
            border: 1px solid rgba(0, 0, 0, 0.125);
            min-height: 400px;
        }

        .content .form-group {
            margin-bottom: 1.5rem;
        }

        .content .form-group:last-child {
            margin-bottom: 0;
        }

        .content .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .content .form-control.is-invalid {
            border-color: #dc3545;
        }

        .content .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .valid-feedback {
            display: block;
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .btn-container {
            margin-top: 2rem;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .alert {
            margin-top: 20px;
        }

        .summary-block {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            border-radius: 0.25rem;
        }

        .summary-block h6 {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .summary-item {
            padding: 8px 0;
            font-size: 0.95rem;
        }

        .summary-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .summary-value {
            color: #333;
            margin-top: 3px;
            word-break: break-word;
        }

        .disclaimer {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 0.25rem;
            margin-top: 20px;
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }

        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }

        /* Description List Styling (AdminLTE Pattern) */
        dl.row {
            margin-bottom: 0;
        }

        dl.row dt {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        dl.row dd {
            color: #333;
            margin-bottom: 0.75rem;
            word-break: break-word;
        }

        dl.row dd:last-child {
            margin-bottom: 0;
        }

        /* Callout Styling (AdminLTE v3) */
        .callout {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 0.25rem;
        }

        .callout h5 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .callout-info {
            border-left: 4px solid #17a2b8;
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .callout-warning {
            border-left: 4px solid #ffc107;
            background-color: #fff3cd;
            color: #856404;
        }

        .card-footer {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
        }

        .float-right {
            float: right;
        }

        /* Toast Container Padding (Evitar pegado a esquina) */
        .toasts-container {
            top: 20px !important;
            right: 20px !important;
            bottom: 20px !important;
            left: 20px !important;
        }

        /* Toast individual styling */
        .toast {
            margin-bottom: 10px;
        }
    </style>
@endsection

@section('content')
    <div class="container mt-5 mb-5">
        <!-- Success Alert -->
        <div id="successAlert" class="alert alert-success" style="display: none; margin-bottom: 20px;">
            <button type="button" class="close" aria-label="Close" onclick="this.parentElement.style.display='none';">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>¡Éxito!</strong> Tu solicitud ha sido enviada correctamente. Nos pondremos en contacto pronto.
        </div>

        <!-- Error Alert -->
        <div id="errorAlert" class="alert alert-danger" style="display: none; margin-bottom: 20px;">
            <button type="button" class="close" aria-label="Close" onclick="this.parentElement.style.display='none';">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>Error:</strong> <span id="errorMessage"></span>
        </div>

        <!-- ============================================================
             BS-STEPPER FORM WIZARD
             ============================================================ -->
        <form id="companyRequestForm">
            <div class="bs-stepper">

                <!-- ========================================
                     STEP HEADER (Navigation)
                     ======================================== -->
                <div class="bs-stepper-header" role="tablist">

                    <!-- Step 1: Información Básica -->
                    <div class="step" data-target="#step1-content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step1-content" id="step1-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">Información Básica</span>
                        </button>
                    </div>

                    <div class="line"></div>

                    <!-- Step 2: Información de Negocio -->
                    <div class="step" data-target="#step2-content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step2-content" id="step2-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">Información de Negocio</span>
                        </button>
                    </div>

                    <div class="line"></div>

                    <!-- Step 3: Información de Contacto -->
                    <div class="step" data-target="#step3-content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step3-content" id="step3-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">Información de Contacto</span>
                        </button>
                    </div>

                    <div class="line"></div>

                    <!-- Step 4: Confirmación -->
                    <div class="step" data-target="#step4-content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step4-content" id="step4-trigger">
                            <span class="bs-stepper-circle">4</span>
                            <span class="bs-stepper-label">Confirmación</span>
                        </button>
                    </div>

                </div>

                <!-- ========================================
                     STEP CONTENT (Form Panels)
                     ======================================== -->
                <div class="bs-stepper-content">

                    <!-- ========== STEP 1: Información Básica ========== -->
                    <div id="step1-content" class="content" role="tabpanel" aria-labelledby="step1-trigger">
                        <h5 class="mb-4">
                            <i class="fas fa-building"></i> Información Básica de la Empresa
                        </h5>

                        <!-- Company Name -->
                        <div class="form-group">
                            <label for="companyName">Nombre de la Empresa <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="companyName"
                                name="company_name"
                                placeholder="Ej: Mi Empresa S.A."
                                required
                            >
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Admin Email -->
                        <div class="form-group">
                            <label for="adminEmail">Email del Administrador <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                class="form-control"
                                id="adminEmail"
                                name="admin_email"
                                placeholder="admin@example.com"
                                required
                            >
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Legal Name -->
                        <div class="form-group">
                            <label for="legalName">Razón Social (Opcional)</label>
                            <input
                                type="text"
                                class="form-control"
                                id="legalName"
                                name="legal_name"
                                placeholder="Razón social de la empresa"
                            >
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="btn-container">
                            <div></div>
                            <button type="button" class="btn btn-primary" onclick="stepper.next()">
                                Siguiente <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ========== STEP 2: Información de Negocio ========== -->
                    <div id="step2-content" class="content" role="tabpanel" aria-labelledby="step2-trigger">
                        <h5 class="mb-4">
                            <i class="fas fa-briefcase"></i> Información de Negocio
                        </h5>

                        <!-- Business Description -->
                        <div class="form-group">
                            <label for="businessDescription">Descripción de la Empresa <span class="text-danger">*</span></label>
                            <textarea
                                class="form-control"
                                id="businessDescription"
                                name="company_description"
                                rows="4"
                                placeholder="Describe brevemente tu empresa y su actividad (50-1000 caracteres)"
                                required
                            ></textarea>
                            <small class="text-muted d-block mt-2">
                                <span id="charCount">0</span>/1000 caracteres
                            </small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Industry Type -->
                        <div class="form-group">
                            <label for="industryType">Tipo de Industria <span class="text-danger">*</span></label>
                            <select
                                class="form-control"
                                id="industryType"
                                name="industry_id"
                                required
                            >
                                <option value="">Selecciona una industria...</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Website -->
                        <div class="form-group">
                            <label for="website">Sitio Web (Opcional)</label>
                            <input
                                type="url"
                                class="form-control"
                                id="website"
                                name="website"
                                placeholder="https://www.example.com"
                            >
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Estimated Users -->
                        <div class="form-group">
                            <label for="estimatedUsers">Usuarios Estimados (Opcional)</label>
                            <input
                                type="number"
                                class="form-control"
                                id="estimatedUsers"
                                name="estimated_users"
                                placeholder="100"
                                min="1"
                            >
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="btn-container">
                            <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                <i class="fas fa-arrow-left mr-2"></i> Anterior
                            </button>
                            <button type="button" class="btn btn-primary" onclick="stepper.next()">
                                Siguiente <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ========== STEP 3: Información de Contacto ========== -->
                    <div id="step3-content" class="content" role="tabpanel" aria-labelledby="step3-trigger">
                        <h5 class="mb-4">
                            <i class="fas fa-map-marker-alt"></i> Información de Contacto
                        </h5>

                        <p class="text-muted mb-4">Todos los campos en esta sección son opcionales</p>

                        <!-- Contact Address -->
                        <div class="form-group">
                            <label for="contactAddress">Dirección</label>
                            <input
                                type="text"
                                class="form-control"
                                id="contactAddress"
                                name="contact_address"
                                placeholder="Calle y número"
                            >
                        </div>

                        <!-- Contact City -->
                        <div class="form-group">
                            <label for="contactCity">Ciudad</label>
                            <input
                                type="text"
                                class="form-control"
                                id="contactCity"
                                name="contact_city"
                                placeholder="Ciudad"
                            >
                        </div>

                        <!-- Contact Country (Pre-select to avoid false data) -->
                        <div class="form-group">
                            <label for="contactCountry">País</label>
                            <select
                                class="form-control"
                                id="contactCountry"
                                name="contact_country"
                            >
                                <option value="">Selecciona un país...</option>
                                <option value="Argentina">🇦🇷 Argentina</option>
                                <option value="Bolivia">🇧🇴 Bolivia</option>
                                <option value="Brasil">🇧🇷 Brasil</option>
                                <option value="Chile">🇨🇱 Chile</option>
                                <option value="Colombia">🇨🇴 Colombia</option>
                                <option value="Costa Rica">🇨🇷 Costa Rica</option>
                                <option value="Cuba">🇨🇺 Cuba</option>
                                <option value="Ecuador">🇪🇨 Ecuador</option>
                                <option value="El Salvador">🇸🇻 El Salvador</option>
                                <option value="España">🇪🇸 España</option>
                                <option value="Estados Unidos">🇺🇸 Estados Unidos</option>
                                <option value="Guatemala">🇬🇹 Guatemala</option>
                                <option value="Honduras">🇭🇳 Honduras</option>
                                <option value="México">🇲🇽 México</option>
                                <option value="Nicaragua">🇳🇮 Nicaragua</option>
                                <option value="Panamá">🇵🇦 Panamá</option>
                                <option value="Paraguay">🇵🇾 Paraguay</option>
                                <option value="Perú">🇵🇪 Perú</option>
                                <option value="Puerto Rico">🇵🇷 Puerto Rico</option>
                                <option value="República Dominicana">🇩🇴 República Dominicana</option>
                                <option value="Uruguay">🇺🇾 Uruguay</option>
                                <option value="Venezuela">🇻🇪 Venezuela</option>
                                <option value="Otro">🌍 Otro</option>
                            </select>
                        </div>

                        <!-- Contact Postal Code -->
                        <div class="form-group">
                            <label for="contactPostalCode">Código Postal</label>
                            <input
                                type="text"
                                class="form-control"
                                id="contactPostalCode"
                                name="contact_postal_code"
                                placeholder="Código postal"
                            >
                        </div>

                        <!-- Tax ID -->
                        <div class="form-group">
                            <label for="taxId">ID Fiscal / RUT</label>
                            <input
                                type="text"
                                class="form-control"
                                id="taxId"
                                name="tax_id"
                                placeholder="Número de identificación fiscal"
                            >
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="btn-container">
                            <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                <i class="fas fa-arrow-left mr-2"></i> Anterior
                            </button>
                            <button type="button" class="btn btn-primary" onclick="stepper.next()">
                                Siguiente <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ========== STEP 4: Confirmación ========== -->
                    <div id="step4-content" class="content" role="tabpanel" aria-labelledby="step4-trigger">

                        <h5 class="mb-4">
                            <i class="fas fa-check-circle"></i> Paso 4: Revisar y Confirmar Solicitud
                        </h5>

                        <!-- Callout: Instrucciones Contextuales -->
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info"></i> Revise su solicitud</h5>
                            <p class="mb-0">
                                Por favor, verifique que toda la información a continuación sea correcta antes de enviarla.
                                Una vez enviada, nos pondremos en contacto para procesar su solicitud.
                            </p>
                        </div>

                        <!-- Summary Content (Dinámicamente poblado con Description Lists) -->
                        <div id="summaryContent"></div>

                        <!-- Aviso Legal -->
                        <hr>
                        <div class="callout callout-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Aviso Legal</h5>
                            <p class="mb-0">
                                Al enviar esta solicitud, usted acepta nuestros términos de servicio y política de privacidad.
                                Su información será procesada de acuerdo con la ley de protección de datos vigente.
                            </p>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="btn-container">
                            <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                <i class="fas fa-arrow-left mr-2"></i> Anterior
                            </button>
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="fas fa-paper-plane mr-2"></i> Confirmar y Enviar
                            </button>
                        </div>

                    </div>

                </div>

            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        /**
         * ============================================================
         * BS-STEPPER FORM WIZARD - AdminLTE v3 Official Implementation
         * ============================================================
         *
         * Este código implementa EXACTAMENTE las reglas del informe técnico:
         * 1. Estructura HTML: .bs-stepper, .step, .step-trigger, .content
         * 2. Inicialización: new Stepper(element, options)
         * 3. Validación: evento show.bs-stepper con event.preventDefault()
         * 4. Navegación: stepper.next(), stepper.previous()
         * ============================================================
         */

        // ============================================================
        // STEP 1: Global Variables
        // ============================================================
        let stepper;
        let industries = [];

        // ============================================================
        // STEP 2: Load Industries from API
        // ============================================================
        async function loadIndustries() {
            console.log('🔄 Cargando industrias desde API...');
            try {
                const response = await fetch('/api/company-industries');
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                if (data.data && Array.isArray(data.data)) {
                    industries = data.data;
                    console.log('✅ Industrias cargadas:', industries.length, 'items');
                    populateIndustriesSelect();
                    return industries;
                }
            } catch (error) {
                console.error('❌ Error al cargar industrias:', error);
                showError('Error al cargar las industrias. Por favor recarga la página.');
            }
            return [];
        }

        // ============================================================
        // STEP 3: Populate Industries Select
        // ============================================================
        function populateIndustriesSelect() {
            const select = document.getElementById('industryType');
            const placeholder = select.querySelector('option:first-child');

            industries.forEach(industry => {
                const option = document.createElement('option');
                option.value = industry.id;
                option.textContent = industry.name;
                select.appendChild(option);
            });

            console.log('✅ Select de industrias poblado');
        }

        // ============================================================
        // STEP 4: Initialize BS-Stepper
        // ============================================================
        document.addEventListener('DOMContentLoaded', function () {
            console.log('🚀 Inicializando bs-stepper...');

            // Get stepper element
            const stepperElement = document.querySelector('.bs-stepper');

            // Create stepper instance (VanillaJS approach - Official AdminLTE method)
            stepper = new Stepper(stepperElement, {
                linear: true,      // Fuerza navegación secuencial
                animation: true     // Habilita transiciones (requiere .fade en .content)
            });

            console.log('✅ bs-stepper inicializado correctamente');

            // Load industries
            loadIndustries();

            // Setup character counter for business description
            document.getElementById('businessDescription').addEventListener('input', function () {
                document.getElementById('charCount').textContent = this.value.length;
            });

            // ============================================================
            // STEP 5: Setup Validation Listener
            // ============================================================
            // Escuchar el evento ANTES de cambiar de paso
            stepperElement.addEventListener('show.bs-stepper', function (event) {
                console.log('🔍 Validando transición:', event.detail.from, '→', event.detail.to);

                // Validar el step desde el cual se viene (no el destino)
                if (!validateStep(event.detail.from)) {
                    console.log('❌ Validación fallida en step:', event.detail.from);
                    event.preventDefault(); // Prevenir cambio de step
                    return;
                }

                // Si vamos al paso 4 (confirmación), construir el resumen
                if (event.detail.to === 3) {
                    console.log('📋 Construyendo resumen para confirmación...');
                    buildSummary();
                }
            });

            // Setup form submission
            document.getElementById('companyRequestForm').addEventListener('submit', function (event) {
                event.preventDefault();
                submitForm();
            });

            console.log('✅ Listeners configurados correctamente');
        });

        // ============================================================
        // STEP 6: Validation Logic
        // ============================================================
        function validateField(fieldId, fieldName) {
            const field = document.getElementById(fieldId);
            const feedback = field.nextElementSibling;
            const value = field.value.trim();
            let error = '';

            switch (fieldName) {
                case 'companyName':
                    if (!value) {
                        error = 'El nombre de la empresa es requerido';
                    } else if (value.length < 2) {
                        error = 'Mínimo 2 caracteres';
                    } else if (value.length > 200) {
                        error = 'Máximo 200 caracteres';
                    }
                    break;

                case 'adminEmail':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!value) {
                        error = 'El email es requerido';
                    } else if (!emailRegex.test(value)) {
                        error = 'Email inválido';
                    }
                    break;

                case 'businessDescription':
                    if (!value) {
                        error = 'La descripción es requerida';
                    } else if (value.length < 50) {
                        error = `Faltan ${50 - value.length} caracteres (mínimo 50)`;
                    } else if (value.length > 1000) {
                        error = `Excede por ${value.length - 1000} caracteres (máximo 1000)`;
                    }
                    break;

                case 'industryType':
                    if (!value) {
                        error = 'Selecciona una industria';
                    }
                    break;

                case 'website':
                    if (value) {
                        try {
                            new URL(value);
                        } catch {
                            error = 'URL inválida (ej: https://example.com)';
                        }
                    }
                    break;
            }

            // Display or clear error
            if (error) {
                field.classList.add('is-invalid');
                if (feedback) {
                    feedback.textContent = error;
                }
                return false;
            } else {
                field.classList.remove('is-invalid');
                if (feedback) {
                    feedback.textContent = '';
                }
                return true;
            }
        }

        function validateStep(stepIndex) {
            console.log('🔍 Validando step:', stepIndex);

            switch (stepIndex) {
                case 0: // Step 1: Basic Info
                    return validateField('companyName', 'companyName') &&
                           validateField('adminEmail', 'adminEmail');

                case 1: // Step 2: Business Info
                    return validateField('businessDescription', 'businessDescription') &&
                           validateField('industryType', 'industryType') &&
                           validateField('website', 'website');

                case 2: // Step 3: Contact Info (all optional)
                    return true;

                case 3: // Step 4: Confirmation
                    return true;

                default:
                    return true;
            }
        }

        // ============================================================
        // STEP 7: Build Summary
        // ============================================================
        function buildSummary() {
            const data = getFormData();
            let html = '';

            // ============ Información Básica (Description List Pattern) ============
            html += '<h5 class="mb-3"><i class="fas fa-user-tie"></i> Información Básica</h5>';
            html += '<dl class="row" style="margin-bottom: 2rem;">';
            html += `<dt class="col-sm-4">Nombre de la Empresa</dt>
                <dd class="col-sm-8">${escapeHtml(data.companyName)}</dd>`;

            if (data.legalName) {
                html += `<dt class="col-sm-4">Razón Social</dt>
                    <dd class="col-sm-8">${escapeHtml(data.legalName)}</dd>`;
            }

            html += `<dt class="col-sm-4">Email Administrador</dt>
                <dd class="col-sm-8">${escapeHtml(data.adminEmail)}</dd>`;
            html += '</dl>';

            // ============ Información de Negocio (Description List Pattern) ============
            html += '<h5 class="mb-3"><i class="fas fa-briefcase"></i> Información de Negocio</h5>';
            html += '<dl class="row" style="margin-bottom: 2rem;">';
            html += `<dt class="col-sm-4">Descripción</dt>
                <dd class="col-sm-8">${escapeHtml(data.businessDescription)}</dd>`;

            html += `<dt class="col-sm-4">Industria</dt>
                <dd class="col-sm-8">${escapeHtml(data.industryName)}</dd>`;

            if (data.website) {
                html += `<dt class="col-sm-4">Sitio Web</dt>
                    <dd class="col-sm-8"><a href="${escapeHtml(data.website)}" target="_blank" rel="noopener">${escapeHtml(data.website)}</a></dd>`;
            }

            if (data.estimatedUsers) {
                html += `<dt class="col-sm-4">Usuarios Estimados</dt>
                    <dd class="col-sm-8">${escapeHtml(data.estimatedUsers)}</dd>`;
            }
            html += '</dl>';

            // ============ Información de Contacto (Description List Pattern) ============
            if (data.contactAddress || data.contactCity || data.contactCountry || data.contactPostalCode || data.taxId) {
                html += '<h5 class="mb-3"><i class="fas fa-map-marker-alt"></i> Información de Contacto</h5>';
                html += '<dl class="row">';

                if (data.contactAddress) {
                    html += `<dt class="col-sm-4">Dirección</dt>
                        <dd class="col-sm-8">${escapeHtml(data.contactAddress)}</dd>`;
                }

                if (data.contactCity) {
                    html += `<dt class="col-sm-4">Ciudad</dt>
                        <dd class="col-sm-8">${escapeHtml(data.contactCity)}</dd>`;
                }

                if (data.contactCountry) {
                    html += `<dt class="col-sm-4">País</dt>
                        <dd class="col-sm-8">${escapeHtml(data.contactCountry)}</dd>`;
                }

                if (data.contactPostalCode) {
                    html += `<dt class="col-sm-4">Código Postal</dt>
                        <dd class="col-sm-8">${escapeHtml(data.contactPostalCode)}</dd>`;
                }

                if (data.taxId) {
                    html += `<dt class="col-sm-4">ID Fiscal</dt>
                        <dd class="col-sm-8">${escapeHtml(data.taxId)}</dd>`;
                }

                html += '</dl>';
            }

            document.getElementById('summaryContent').innerHTML = html;
        }

        // ============================================================
        // STEP 8: Get Form Data
        // ============================================================
        function getFormData() {
            const industrySelect = document.getElementById('industryType');
            const selectedOption = industrySelect.options[industrySelect.selectedIndex];
            const industryName = selectedOption ? selectedOption.textContent : '';

            return {
                companyName: document.getElementById('companyName').value,
                adminEmail: document.getElementById('adminEmail').value,
                legalName: document.getElementById('legalName').value,
                businessDescription: document.getElementById('businessDescription').value,
                industryType: document.getElementById('industryType').value,
                industryName: industryName,
                website: document.getElementById('website').value,
                estimatedUsers: document.getElementById('estimatedUsers').value,
                contactAddress: document.getElementById('contactAddress').value,
                contactCity: document.getElementById('contactCity').value,
                contactCountry: document.getElementById('contactCountry').value,
                contactPostalCode: document.getElementById('contactPostalCode').value,
                taxId: document.getElementById('taxId').value
            };
        }

        // ============================================================
        // STEP 9: Submit Form with Enhanced Error Handling
        // ============================================================
        async function submitForm() {
            console.log('[Company Request] Iniciando envío de solicitud...');

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border mr-2"></span> Enviando...';

            try {
                const data = getFormData();
                const payload = {
                    company_name: data.companyName,
                    legal_name: data.legalName || null,
                    admin_email: data.adminEmail,
                    company_description: data.businessDescription,
                    request_message: `Solicitud de empresa enviada desde formulario web - Industria: ${data.industryName}`,
                    industry_id: data.industryType,
                    website: data.website || null,
                    estimated_users: data.estimatedUsers ? parseInt(data.estimatedUsers) : null,
                    contact_address: data.contactAddress || null,
                    contact_city: data.contactCity || null,
                    contact_country: data.contactCountry || null,
                    contact_postal_code: data.contactPostalCode || null,
                    tax_id: data.taxId || null
                };

                console.log('[Company Request] Payload a enviar:', payload);

                const response = await fetch('/api/company-requests', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json', // CRÍTICO: Le dice a Laravel que queremos JSON
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                console.log('[Company Request] Respuesta recibida - Status:', response.status);

                // Check if response is JSON before parsing
                const contentType = response.headers.get('content-type');
                const isJson = contentType && contentType.includes('application/json');

                let result = null;
                if (isJson) {
                    result = await response.json();
                    console.log('[Company Request] Datos JSON:', result);
                }

                // Handle success
                if (response.ok) {
                    console.log('[Company Request] ✅ Solicitud enviada exitosamente');

                    // Show AdminLTE Native Toasts (Non-blocking notification)
                    $(document).Toasts('create', {
                        class: 'bg-success',
                        title: 'Envío Exitoso',
                        subtitle: 'Justo ahora',
                        body: 'Tu solicitud ha sido procesada y registrada correctamente. Nos pondremos en contacto pronto.',
                        icon: 'fas fa-check-circle',
                        autohide: true,
                        delay: 6000,
                        position: 'topRight'
                    });

                    // Reset form and stepper
                    document.getElementById('companyRequestForm').reset();
                    stepper.reset();

                    // Redirect after 4 seconds
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 4000);
                    return; // Exit early on success
                }

                // Handle specific HTTP status codes
                if (response.status === 429) {
                    // Rate limit exceeded
                    console.log('[Company Request] ⏱️ Rate limit excedido');
                    $(document).Toasts('create', {
                        class: 'bg-warning',
                        title: 'Demasiadas Solicitudes',
                        subtitle: 'Límite de uso',
                        body: 'Has enviado demasiadas solicitudes. Por favor, espera unos minutos antes de intentar nuevamente. Límite: 3 solicitudes por hora.',
                        icon: 'fas fa-clock',
                        autohide: false,
                        position: 'topRight'
                    });
                    return;
                }

                if (response.status === 422 && isJson && result.errors) {
                    // Validation errors
                    console.log('[Company Request] ❌ Errores de validación detectados:', result.errors);
                    handleValidationErrors(result.errors);
                    return;
                }

                // Handle other errors
                const errorMessage = (isJson && result?.message)
                    ? result.message
                    : `Error del servidor (${response.status})`;

                throw new Error(errorMessage);
            } catch (error) {
                console.error('[Company Request] ❌ Error capturado:', error);

                // Show AdminLTE Native Toasts for Error (Critical notification)
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error al Enviar',
                    subtitle: 'Intenta nuevamente',
                    body: error.message || 'Ocurrió un error inesperado al procesar tu solicitud.',
                    icon: 'fas fa-exclamation-circle',
                    autohide: false, // Los errores NO se cierran automáticamente
                    position: 'topRight'
                });
            } finally {
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // ============================================================
        // Enhanced Validation Error Handler
        // ============================================================
        function handleValidationErrors(errors) {
            console.log('[Validation] Procesando errores de validación...');

            // Clear all previous error states
            document.querySelectorAll('.form-control').forEach(field => {
                field.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(feedback => {
                feedback.textContent = '';
            });

            // Map field names to their IDs for display in toasts
            const fieldLabels = {
                'tax_id': 'ID Fiscal / NIT',
                'admin_email': 'Email del Administrador',
                'website': 'Sitio Web',
                'company_name': 'Nombre de la Empresa',
                'company_description': 'Descripción de la Empresa',
                'industry_id': 'Tipo de Industria',
                'estimated_users': 'Usuarios Estimados',
                'contact_address': 'Dirección',
                'contact_city': 'Ciudad',
                'contact_country': 'País',
                'contact_postal_code': 'Código Postal',
                'legal_name': 'Razón Social'
            };

            // Process each field error
            Object.keys(errors).forEach(fieldName => {
                const errorMessages = errors[fieldName];
                console.log(`[Validation] Campo: ${fieldName}`, errorMessages);

                // Find the form field by name attribute
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    // Mark field as invalid
                    field.classList.add('is-invalid');

                    // Find and update the feedback element
                    const feedback = field.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback && errorMessages.length > 0) {
                        feedback.textContent = errorMessages[0]; // Show first error message
                        console.log(`[Validation] Mostrado error en campo: ${fieldName}`);
                    }
                }

                // Show each error in a toast (distinguished by type)
                errorMessages.forEach(message => {
                    const fieldLabel = fieldLabels[fieldName] || fieldName;

                    // Check if it's a warning (ADVERTENCIA prefix)
                    const isWarning = message.includes('ADVERTENCIA');

                    console.log(`[Toast] Mostrando ${isWarning ? 'advertencia' : 'error'}: ${message}`);

                    $(document).Toasts('create', {
                        class: isWarning ? 'bg-warning' : 'bg-danger',
                        title: isWarning ? 'Advertencia de Validación' : 'Error de Validación',
                        subtitle: fieldLabel,
                        body: message,
                        icon: isWarning ? 'fas fa-exclamation-circle' : 'fas fa-times-circle',
                        autohide: false, // Los errores NO se cierran automáticamente para que el usuario sepa por qué falló
                        position: 'topRight'
                    });
                });
            });

            console.log('[Validation] Procesamiento de errores completado');
        }

        // ============================================================
        // STEP 10: Helper Functions
        // ============================================================
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showError(message) {
            console.error('[Error]', message);
            $(document).Toasts('create', {
                class: 'bg-danger',
                title: 'Error',
                body: message,
                icon: 'fas fa-times-circle',
                autohide: false, // Los errores NO se cierran automáticamente
                position: 'topRight'
            });
        }

        // ============================================================
        // STEP 11: Clear Validation Errors on Input
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to clear errors when user starts typing
            document.querySelectorAll('.form-control').forEach(field => {
                field.addEventListener('input', function() {
                    // Check if field had error BEFORE clearing
                    const hadError = this.classList.contains('is-invalid');

                    // Remove error state
                    this.classList.remove('is-invalid');

                    // Clear error message
                    const feedback = this.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback && feedback.textContent) {
                        feedback.textContent = '';
                    }

                    // Only log if there was actually an error cleared
                    if (hadError) {
                        console.log(`[FormClean] ✓ Campo corregido: ${this.name || this.id}`);
                    }
                });
            });

            // Same for selects (change event)
            document.querySelectorAll('select.form-control').forEach(field => {
                field.addEventListener('change', function() {
                    const hadError = this.classList.contains('is-invalid');

                    this.classList.remove('is-invalid');
                    const feedback = this.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback && feedback.textContent) {
                        feedback.textContent = '';
                    }

                    if (hadError) {
                        console.log(`[FormClean] ✓ Campo corregido: ${this.name || this.id}`);
                    }
                });
            });

            console.log('[FormClean] ✓ Auto-limpieza de errores activada');
        });

    </script>
@endsection
