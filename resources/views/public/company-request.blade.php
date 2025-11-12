@extends('layouts.guest')

@section('title', 'Solicitud de Empresa - Helpdesk')

@section('css')
    <style>
        /* AdminLTE v3 Customization for bs-stepper Wizard */
        .content-wrapper {
            margin-left: 0 !important;
            background: #f5f5f5;
        }

        .wizard-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        /* Override bs-stepper header styling to match AdminLTE v3 */
        .bs-stepper-header {
            background: white;
            padding: 30px 20px;
            border-radius: 0.25rem;
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.125);
            margin-bottom: 30px;
        }

        .bs-stepper-header .step-trigger {
            background: none;
            border: none;
            padding: 0;
        }

        .bs-stepper-header .step-trigger:focus {
            outline: none;
            box-shadow: none;
        }

        .bs-stepper-header .bs-stepper-circle {
            width: 50px;
            height: 50px;
            background: #e9ecef;
            color: #6c757d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.25rem;
            margin: 0 auto 10px;
            transition: all 0.3s ease;
        }

        .bs-stepper-header .step.active .bs-stepper-circle {
            background: #007bff;
            color: white;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.25);
            transform: scale(1.05);
        }

        .bs-stepper-header .step.done .bs-stepper-circle {
            background: #28a745;
            color: white;
        }

        .bs-stepper-header .bs-stepper-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .bs-stepper-header .step.active .bs-stepper-label {
            color: #007bff;
        }

        .bs-stepper-header .step.done .bs-stepper-label {
            color: #28a745;
        }

        /* Card styling */
        .card {
            border: 1px solid rgba(0, 0, 0, 0.125);
            background: white;
            margin-bottom: 20px;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 0.75rem 1.25rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.25rem;
        }

        .card-footer {
            background: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1rem 1.25rem;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Form control styling */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #ced4da;
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .invalid-feedback.show {
            display: block;
        }

        /* Button styling */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        /* Alert/Callout styling */
        .alert {
            padding: 0.75rem 1.25rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        /* Summary styling */
        .summary-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .summary-value {
            color: #333;
            margin-top: 0.25rem;
            word-break: break-word;
        }

        /* Loading state */
        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            vertical-align: text-bottom;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endsection

@section('content')
    <div class="wizard-container">
        <!-- Success Alert -->
        <div id="successAlert" class="alert alert-success" style="display: none;">
            <strong>¡Éxito!</strong> Tu solicitud ha sido enviada correctamente. Nos pondremos en contacto pronto.
        </div>

        <!-- Error Alert -->
        <div id="errorAlert" class="alert alert-danger" style="display: none;">
            <strong>Error:</strong> <span id="errorMessage"></span>
        </div>

        <!-- bs-stepper Form Wizard -->
        <form id="companyRequestForm">
            <!-- Step Header (bs-stepper-header) -->
            <div class="bs-stepper" id="stepper">
                <div class="bs-stepper-header" role="tablist">
                    <!-- Step 1 -->
                    <div class="step" data-target="#step1Content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step1Content" id="step1-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">Información Básica</span>
                        </button>
                    </div>

                    <div class="line"></div>

                    <!-- Step 2 -->
                    <div class="step" data-target="#step2Content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step2Content" id="step2-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">Información de Negocio</span>
                        </button>
                    </div>

                    <div class="line"></div>

                    <!-- Step 3 -->
                    <div class="step" data-target="#step3Content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step3Content" id="step3-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">Información de Contacto</span>
                        </button>
                    </div>

                    <div class="line"></div>

                    <!-- Step 4 -->
                    <div class="step" data-target="#step4Content">
                        <button type="button" class="step-trigger" role="tab" aria-controls="step4Content" id="step4-trigger">
                            <span class="bs-stepper-circle">4</span>
                            <span class="bs-stepper-label">Confirmación</span>
                        </button>
                    </div>
                </div>

                <!-- Step Content (bs-stepper-content) -->
                <div class="bs-stepper-content">
                    <!-- STEP 1: Información Básica -->
                    <div id="step1Content" class="content" role="tabpanel" aria-labelledby="step1-trigger">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-building"></i> Información Básica de la Empresa
                            </div>
                            <div class="card-body">
                                <!-- Company Name -->
                                <div class="form-group">
                                    <label for="companyName">Nombre de la Empresa *</label>
                                    <input type="text" class="form-control" id="companyName" name="companyName" placeholder="Ej: Mi Empresa S.A." required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Admin Email -->
                                <div class="form-group">
                                    <label for="adminEmail">Email del Administrador *</label>
                                    <input type="email" class="form-control" id="adminEmail" name="adminEmail" placeholder="admin@example.com" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Legal Name (Optional) -->
                                <div class="form-group">
                                    <label for="legalName">Razón Social (Opcional)</label>
                                    <input type="text" class="form-control" id="legalName" name="legalName" placeholder="Razón social de la empresa">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-primary" onclick="stepper.next()">
                                    Siguiente <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Información de Negocio -->
                    <div id="step2Content" class="content" role="tabpanel" aria-labelledby="step2-trigger">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-briefcase"></i> Información de Negocio
                            </div>
                            <div class="card-body">
                                <!-- Business Description -->
                                <div class="form-group">
                                    <label for="businessDescription">Descripción de la Empresa *</label>
                                    <textarea class="form-control" id="businessDescription" name="businessDescription" rows="4" placeholder="Describe brevemente tu empresa y su actividad (50-1000 caracteres)" required></textarea>
                                    <small class="text-muted">
                                        <span id="charCount">0</span>/1000 caracteres
                                    </small>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Industry Type -->
                                <div class="form-group">
                                    <label for="industryType">Tipo de Industria *</label>
                                    <select class="form-control" id="industryType" name="industryType" required>
                                        <option value="">Selecciona una industria...</option>
                                        <!-- Will be populated by JavaScript -->
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Website (Optional) -->
                                <div class="form-group">
                                    <label for="website">Sitio Web (Opcional)</label>
                                    <input type="url" class="form-control" id="website" name="website" placeholder="https://www.example.com">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Estimated Users (Optional) -->
                                <div class="form-group">
                                    <label for="estimatedUsers">Usuarios Estimados (Opcional)</label>
                                    <input type="number" class="form-control" id="estimatedUsers" name="estimatedUsers" placeholder="100" min="1">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                    <i class="fas fa-arrow-left mr-2"></i> Anterior
                                </button>
                                <button type="button" class="btn btn-primary" onclick="stepper.next()">
                                    Siguiente <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Información de Contacto -->
                    <div id="step3Content" class="content" role="tabpanel" aria-labelledby="step3-trigger">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-map-marker-alt"></i> Información de Contacto
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Todos los campos en esta sección son opcionales</p>

                                <!-- Contact Address -->
                                <div class="form-group">
                                    <label for="contactAddress">Dirección</label>
                                    <input type="text" class="form-control" id="contactAddress" name="contactAddress" placeholder="Calle y número">
                                </div>

                                <!-- Contact City -->
                                <div class="form-group">
                                    <label for="contactCity">Ciudad</label>
                                    <input type="text" class="form-control" id="contactCity" name="contactCity" placeholder="Ciudad">
                                </div>

                                <!-- Contact Country -->
                                <div class="form-group">
                                    <label for="contactCountry">País</label>
                                    <input type="text" class="form-control" id="contactCountry" name="contactCountry" placeholder="País">
                                </div>

                                <!-- Contact Postal Code -->
                                <div class="form-group">
                                    <label for="contactPostalCode">Código Postal</label>
                                    <input type="text" class="form-control" id="contactPostalCode" name="contactPostalCode" placeholder="Código postal">
                                </div>

                                <!-- Tax ID -->
                                <div class="form-group">
                                    <label for="taxId">ID Fiscal / RUT</label>
                                    <input type="text" class="form-control" id="taxId" name="taxId" placeholder="Número de identificación fiscal">
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                    <i class="fas fa-arrow-left mr-2"></i> Anterior
                                </button>
                                <button type="button" class="btn btn-primary" onclick="stepper.next()">
                                    Siguiente <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Confirmación -->
                    <div id="step4Content" class="content" role="tabpanel" aria-labelledby="step4-trigger">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-check-circle"></i> Confirmación de Solicitud
                            </div>
                            <div class="card-body">
                                <!-- Summary -->
                                <div class="alert alert-info">
                                    <strong>Revisión de tu solicitud:</strong> Por favor verifica los datos antes de enviar.
                                </div>

                                <div id="summaryContent">
                                    <!-- Summary will be inserted here by JavaScript -->
                                </div>

                                <!-- Legal Disclaimer -->
                                <div class="alert alert-warning mt-4">
                                    <strong><i class="fas fa-exclamation-triangle"></i> Aviso Legal:</strong>
                                    Al enviar esta solicitud, aceptas nuestros términos de servicio y política de privacidad. Tu información será procesada de acuerdo con la ley de protección de datos.
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                    <i class="fas fa-arrow-left mr-2"></i> Anterior
                                </button>
                                <button type="submit" class="btn btn-success" id="submitBtn" onclick="submitForm(event)">
                                    <i class="fas fa-paper-plane mr-2"></i> Enviar Solicitud
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        // ============================================================
        // STEP 1: Load Industries from API
        // ============================================================
        async function loadIndustries() {
            try {
                const response = await fetch('/api/company-industries');
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                if (data.data && Array.isArray(data.data)) {
                    console.log('✅ Industrias cargadas:', data.data.length, 'items');
                    return data.data;
                }
            } catch (error) {
                console.error('❌ Error loading industries:', error);
            }
            return [];
        }

        // ============================================================
        // STEP 2: Populate Industries Select
        // ============================================================
        async function initializeIndustries() {
            const industries = await loadIndustries();
            const select = document.getElementById('industryType');

            industries.forEach(industry => {
                const option = document.createElement('option');
                option.value = industry.id;
                option.textContent = industry.name;
                select.appendChild(option);
            });
        }

        // ============================================================
        // STEP 3: Initialize bs-stepper
        // ============================================================
        let stepper;
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize stepper
            const stepperElement = document.querySelector('.bs-stepper');
            stepper = new Stepper(stepperElement, {
                linear: true,
                animation: true
            });

            console.log('✅ bs-stepper inicializado');

            // Load industries
            initializeIndustries();

            // Setup character counter
            document.getElementById('businessDescription').addEventListener('input', function () {
                document.getElementById('charCount').textContent = this.value.length;
            });

            // Setup form validation on step change
            stepperElement.addEventListener('show.bs-stepper', function (event) {
                console.log('🔄 Validando paso:', event.detail.from, '→', event.detail.to);

                if (!validateStep(event.detail.from)) {
                    event.preventDefault();
                    return false;
                }
            });

            // Close alerts when clicking them
            document.getElementById('successAlert').addEventListener('click', function () {
                this.style.display = 'none';
            });
            document.getElementById('errorAlert').addEventListener('click', function () {
                this.style.display = 'none';
            });
        });

        // ============================================================
        // STEP 4: Validation Logic
        // ============================================================
        function validateField(fieldName) {
            const field = document.getElementById(fieldName);
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

            // Display error or clear it
            if (error) {
                field.classList.add('is-invalid');
                feedback.textContent = error;
                feedback.classList.add('show');
                return false;
            } else {
                field.classList.remove('is-invalid');
                feedback.textContent = '';
                feedback.classList.remove('show');
                return true;
            }
        }

        function validateStep(stepIndex) {
            console.log('🔍 Validando step:', stepIndex);

            switch (stepIndex) {
                case 0: // Step 1: Basic Info
                    return validateField('companyName') && validateField('adminEmail');

                case 1: // Step 2: Business Info
                    return validateField('businessDescription') &&
                           validateField('industryType') &&
                           validateField('website');

                case 2: // Step 3: Contact Info
                    // All optional, return true
                    return true;

                case 3: // Step 4: Confirmation
                    return true;

                default:
                    return true;
            }
        }

        // ============================================================
        // STEP 5: Build Summary on Step 4
        // ============================================================
        document.querySelector('.bs-stepper').addEventListener('show.bs-stepper', function (event) {
            if (event.detail.to === 3) {
                // Building summary for step 4
                const summary = buildSummary();
                document.getElementById('summaryContent').innerHTML = summary;
            }
        });

        function buildSummary() {
            const data = getFormData();
            let html = '';

            html += '<h6 class="mb-3">Información Básica</h6>';
            html += `<div class="summary-item">
                <div class="summary-label">Nombre de la Empresa</div>
                <div class="summary-value">${escapeHtml(data.companyName)}</div>
            </div>`;

            if (data.legalName) {
                html += `<div class="summary-item">
                    <div class="summary-label">Razón Social</div>
                    <div class="summary-value">${escapeHtml(data.legalName)}</div>
                </div>`;
            }

            html += `<div class="summary-item">
                <div class="summary-label">Email del Administrador</div>
                <div class="summary-value">${escapeHtml(data.adminEmail)}</div>
            </div>`;

            html += '<h6 class="mb-3 mt-4">Información de Negocio</h6>';
            html += `<div class="summary-item">
                <div class="summary-label">Descripción</div>
                <div class="summary-value">${escapeHtml(data.businessDescription)}</div>
            </div>`;

            html += `<div class="summary-item">
                <div class="summary-label">Industria</div>
                <div class="summary-value">${escapeHtml(data.industryName)}</div>
            </div>`;

            if (data.website) {
                html += `<div class="summary-item">
                    <div class="summary-label">Sitio Web</div>
                    <div class="summary-value"><a href="${escapeHtml(data.website)}" target="_blank">${escapeHtml(data.website)}</a></div>
                </div>`;
            }

            if (data.estimatedUsers) {
                html += `<div class="summary-item">
                    <div class="summary-label">Usuarios Estimados</div>
                    <div class="summary-value">${data.estimatedUsers}</div>
                </div>`;
            }

            if (data.contactAddress || data.contactCity || data.contactCountry || data.contactPostalCode || data.taxId) {
                html += '<h6 class="mb-3 mt-4">Información de Contacto</h6>';

                if (data.contactAddress) {
                    html += `<div class="summary-item">
                        <div class="summary-label">Dirección</div>
                        <div class="summary-value">${escapeHtml(data.contactAddress)}</div>
                    </div>`;
                }

                if (data.contactCity) {
                    html += `<div class="summary-item">
                        <div class="summary-label">Ciudad</div>
                        <div class="summary-value">${escapeHtml(data.contactCity)}</div>
                    </div>`;
                }

                if (data.contactCountry) {
                    html += `<div class="summary-item">
                        <div class="summary-label">País</div>
                        <div class="summary-value">${escapeHtml(data.contactCountry)}</div>
                    </div>`;
                }

                if (data.contactPostalCode) {
                    html += `<div class="summary-item">
                        <div class="summary-label">Código Postal</div>
                        <div class="summary-value">${escapeHtml(data.contactPostalCode)}</div>
                    </div>`;
                }

                if (data.taxId) {
                    html += `<div class="summary-item">
                        <div class="summary-label">ID Fiscal</div>
                        <div class="summary-value">${escapeHtml(data.taxId)}</div>
                    </div>`;
                }
            }

            return html;
        }

        // ============================================================
        // STEP 6: Get Form Data
        // ============================================================
        function getFormData() {
            const industrySelect = document.getElementById('industryType');
            const selectedOption = industrySelect.options[industrySelect.selectedIndex];

            return {
                companyName: document.getElementById('companyName').value,
                adminEmail: document.getElementById('adminEmail').value,
                legalName: document.getElementById('legalName').value,
                businessDescription: document.getElementById('businessDescription').value,
                industryType: document.getElementById('industryType').value,
                industryName: selectedOption.textContent,
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
        // STEP 7: Submit Form
        // ============================================================
        async function submitForm(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');

            // Disable button and show loading state
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner mr-2"></span> Enviando...';

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

                console.log('📤 Enviando payload:', payload);

                const response = await fetch('/api/company-requests', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok) {
                    console.log('✅ Solicitud enviada exitosamente:', result);
                    successAlert.style.display = 'block';
                    document.getElementById('companyRequestForm').reset();
                    stepper.reset();
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    // Redirect after 3 seconds
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 3000);
                } else {
                    throw new Error(result.message || 'Error al enviar la solicitud');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                document.getElementById('errorMessage').textContent = error.message;
                errorAlert.style.display = 'block';
            } finally {
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // ============================================================
        // STEP 8: Utility Functions
        // ============================================================
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
@endsection
