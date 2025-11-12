@extends('layouts.guest')

@section('title', 'Solicitud de Empresa - Helpdesk')

@section('css')
    <style>
        /* AdminLTE v3 Wizard Customization */

        .content-wrapper {
            margin-left: 0 !important;
            background: #f5f5f5;
        }

        .wizard-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        /* Steps Header - AdminLTE Style */
        .steps-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 0 15px;
        }

        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-item.active .step-circle {
            background: #007bff;
            color: white;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.25);
            transform: scale(1.05);
        }

        .step-item.completed .step-circle {
            background: #28a745;
            color: white;
        }

        .step-item.pending .step-circle {
            background: #e9ecef;
            color: #6c757d;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6c757d;
            margin-top: 5px;
        }

        .step-item.active .step-label {
            color: #007bff;
        }

        .step-item.completed .step-label {
            color: #28a745;
        }

        /* Connector Lines - AdminLTE Style */
        .step-connector {
            position: absolute;
            top: 25px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }

        .step-item.completed .step-connector {
            background: #28a745;
        }

        /* Card Wrapper - AdminLTE Card */
        .wizard-card {
            background: white;
            border-radius: 0.25rem;
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.125);
            margin-bottom: 40px;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1.25rem;
            border-radius: 0.25rem 0.25rem 0 0;
        }

        .card-header h3 {
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .card-header .text-muted {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .card-body {
            padding: 2rem;
        }

        .card-footer {
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 1.25rem;
            border-radius: 0 0 0.25rem 0.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Form Controls - AdminLTE Style */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: #007bff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .form-control.is-valid {
            border-color: #28a745;
        }

        .form-control.is-valid:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
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

        .helper-text {
            display: block;
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        /* Input Group - AdminLTE Style */
        .input-group {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            width: 100%;
        }

        .input-group-prepend {
            margin-right: -1px;
        }

        .input-group-text {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            font-weight: 500;
            line-height: 1.5;
            color: #495057;
            text-align: center;
            white-space: nowrap;
            background: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .input-group .form-control {
            position: relative;
            z-index: 1;
            flex: 1 1 auto;
            margin-bottom: 0;
            border-radius: 0;
        }

        .input-group .form-control:first-child {
            border-radius: 0 0.25rem 0.25rem 0;
        }

        .input-group .input-group-prepend + .form-control {
            border-radius: 0 0.25rem 0.25rem 0;
        }

        .input-group-append {
            margin-left: -1px;
        }

        .input-group-append .form-control {
            border-radius: 0.25rem 0 0 0.25rem;
        }

        /* Textarea */
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Counter Text */
        .char-counter {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
            display: flex;
            justify-content: space-between;
        }

        .char-counter .count {
            font-weight: 600;
        }

        /* Buttons - AdminLTE Style */
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            color: #fff;
            background-color: #0069d9;
            border-color: #0062cc;
        }

        .btn-success {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            color: #fff;
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            color: #fff;
            background-color: #5a6268;
            border-color: #545b62;
        }

        .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        /* Alert - AdminLTE Style */
        .alert {
            position: relative;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        /* Summary Section - AdminLTE Callout */
        .callout {
            border-left: 4px solid #e9ecef;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }

        .callout-info {
            border-left-color: #17a2b8;
            background: #e7f3ff;
        }

        .callout h5 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .callout p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        /* Summary List */
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.95rem;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item .label {
            color: #6c757d;
            font-weight: 500;
        }

        .summary-item .value {
            color: #2c3e50;
            font-weight: 600;
        }

        /* Benefits Section */
        .benefits-section {
            background: #e7f3ff;
            border: 1px solid #b3d9f2;
            border-radius: 0.25rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .benefits-section h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .benefit-item:last-child {
            margin-bottom: 0;
        }

        .benefit-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 1rem;
            font-size: 0.9rem;
        }

        .benefit-text h6 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .benefit-text p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Spinner/Loading */
        .spinner-border {
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

        /* Responsive */
        @media (max-width: 768px) {
            .wizard-container {
                margin: 20px auto;
                padding: 10px;
            }

            .steps-header {
                margin-bottom: 30px;
                padding: 0;
            }

            .step-circle {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .step-label {
                font-size: 0.8rem;
            }

            .card-body {
                padding: 1rem;
            }

            .card-footer {
                flex-direction: column;
                gap: 1rem;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    <div class="content-wrapper" style="margin-left: 0;">
        <div class="wizard-container">

            <!-- Wizard Form with Alpine.js -->
            <form @submit.prevent="submitForm" x-data="wizardForm()" x-init="init()" x-cloak>

                <!-- Steps Header -->
                <div class="steps-header">
                    <div class="step-item" :class="currentStep >= 1 ? (currentStep > 1 ? 'completed' : 'active') : 'pending'">
                        <div class="step-circle">
                            <span x-show="currentStep <= 1">1</span>
                            <i class="fas fa-check" x-show="currentStep > 1"></i>
                        </div>
                        <div class="step-label">Básica</div>
                        <div class="step-connector"></div>
                    </div>

                    <div class="step-item" :class="currentStep >= 2 ? (currentStep > 2 ? 'completed' : 'active') : 'pending'">
                        <div class="step-circle">
                            <span x-show="currentStep <= 2">2</span>
                            <i class="fas fa-check" x-show="currentStep > 2"></i>
                        </div>
                        <div class="step-label">Negocio</div>
                        <div class="step-connector"></div>
                    </div>

                    <div class="step-item" :class="currentStep >= 3 ? (currentStep > 3 ? 'completed' : 'active') : 'pending'">
                        <div class="step-circle">
                            <span x-show="currentStep <= 3">3</span>
                            <i class="fas fa-check" x-show="currentStep > 3"></i>
                        </div>
                        <div class="step-label">Contacto</div>
                        <div class="step-connector"></div>
                    </div>

                    <div class="step-item" :class="currentStep >= 4 ? (currentStep > 4 ? 'completed' : 'active') : 'pending'">
                        <div class="step-circle">
                            <span x-show="currentStep <= 4">4</span>
                            <i class="fas fa-check" x-show="currentStep > 4"></i>
                        </div>
                        <div class="step-label">Confirmar</div>
                    </div>
                </div>

                <!-- Wizard Card -->
                <div class="wizard-card">

                    <!-- STEP 1: Información Básica -->
                    <div x-show="currentStep === 1" @click.away="">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-building mr-2"></i>
                                Información Básica
                            </h3>
                            <p class="text-muted">Datos principales de tu empresa</p>
                        </div>

                        <div class="card-body">

                            <!-- Nombre de la Empresa -->
                            <div class="form-group">
                                <label for="companyName">
                                    Nombre de la Empresa
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-building"></i>
                                        </span>
                                    </div>
                                    <input
                                        type="text"
                                        id="companyName"
                                        class="form-control"
                                        :class="form.companyName && !isValid('companyName') ? 'is-invalid' : form.companyName && isValid('companyName') ? 'is-valid' : ''"
                                        x-model="form.companyName"
                                        @blur="touched.companyName = true"
                                        placeholder="Ej: Innovación Digital SRL"
                                        required
                                    >
                                </div>
                                <template x-if="touched.companyName && !isValid('companyName')">
                                    <div class="invalid-feedback" style="display: block;">
                                        <span x-text="getError('companyName')"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Email del Administrador -->
                            <div class="form-group">
                                <label for="adminEmail">
                                    Email del Administrador
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                    </div>
                                    <input
                                        type="email"
                                        id="adminEmail"
                                        class="form-control"
                                        :class="form.adminEmail && !isValid('adminEmail') ? 'is-invalid' : form.adminEmail && isValid('adminEmail') ? 'is-valid' : ''"
                                        x-model="form.adminEmail"
                                        @blur="touched.adminEmail = true"
                                        placeholder="admin@tuempresa.com"
                                        required
                                    >
                                </div>
                                <template x-if="touched.adminEmail && !isValid('adminEmail')">
                                    <div class="invalid-feedback" style="display: block;">
                                        <span x-text="getError('adminEmail')"></span>
                                    </div>
                                </template>
                                <small class="helper-text">Email del administrador principal</small>
                            </div>

                            <!-- Razón Social (Opcional) -->
                            <div class="form-group">
                                <label for="legalName">Razón Social (Opcional)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-file-alt"></i>
                                        </span>
                                    </div>
                                    <input
                                        type="text"
                                        id="legalName"
                                        class="form-control"
                                        x-model="form.legalName"
                                        placeholder="Nombre legal completo de la empresa"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <a href="{{ route('welcome') }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i> Cancelar
                            </a>
                            <button type="button" @click="nextStep()" class="btn btn-primary">
                                Siguiente <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Descripción del Negocio -->
                    <div x-show="currentStep === 2" @click.away="">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-briefcase mr-2"></i>
                                Descripción del Negocio
                            </h3>
                            <p class="text-muted">Cuéntanos sobre tu empresa</p>
                        </div>

                        <div class="card-body">

                            <!-- Descripción del Negocio -->
                            <div class="form-group">
                                <label for="businessDescription">
                                    Descripción del Negocio
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    id="businessDescription"
                                    class="form-control"
                                    :class="form.businessDescription && !isValid('businessDescription') ? 'is-invalid' : form.businessDescription && isValid('businessDescription') ? 'is-valid' : ''"
                                    x-model="form.businessDescription"
                                    @blur="touched.businessDescription = true"
                                    placeholder="Describe tu empresa, servicios, experiencia... (Mínimo 50 caracteres)"
                                    required
                                ></textarea>
                                <div class="char-counter">
                                    <span x-text="`${form.businessDescription.length}/1000 caracteres`"></span>
                                    <template x-if="touched.businessDescription && !isValid('businessDescription')">
                                        <span class="text-danger" x-text="getError('businessDescription')"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Tipo de Industria -->
                            <div class="form-group">
                                <label for="industryType">
                                    Tipo de Industria
                                    <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="industryType"
                                    class="form-control"
                                    :class="form.industryType && !isValid('industryType') ? 'is-invalid' : form.industryType && isValid('industryType') ? 'is-valid' : ''"
                                    x-model="form.industryType"
                                    @blur="touched.industryType = true"
                                    required
                                >
                                    <option value="">Selecciona una industria</option>
                                    <template x-for="industry in industries" :key="industry.id">
                                        <option :value="industry.id" x-text="industry.name"></option>
                                    </template>
                                </select>
                                <template x-if="touched.industryType && !isValid('industryType')">
                                    <div class="invalid-feedback" style="display: block;">
                                        <span x-text="getError('industryType')"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Sitio Web (Opcional) -->
                            <div class="form-group">
                                <label for="website">Sitio Web (Opcional)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-globe"></i>
                                        </span>
                                    </div>
                                    <input
                                        type="url"
                                        id="website"
                                        class="form-control"
                                        :class="form.website && !isValid('website') ? 'is-invalid' : form.website && isValid('website') ? 'is-valid' : ''"
                                        x-model="form.website"
                                        @blur="touched.website = true"
                                        placeholder="https://tuempresa.com"
                                    >
                                </div>
                                <template x-if="touched.website && form.website && !isValid('website')">
                                    <div class="invalid-feedback" style="display: block;">
                                        <span x-text="getError('website')"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Usuarios Estimados (Opcional) -->
                            <div class="form-group">
                                <label for="estimatedUsers">Usuarios Estimados (Opcional)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-users"></i>
                                        </span>
                                    </div>
                                    <input
                                        type="number"
                                        id="estimatedUsers"
                                        class="form-control"
                                        x-model="form.estimatedUsers"
                                        placeholder="50"
                                        min="1"
                                        max="10000"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="button" @click="previousStep()" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Anterior
                            </button>
                            <button type="button" @click="nextStep()" class="btn btn-primary">
                                Siguiente <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Información de Contacto -->
                    <div x-show="currentStep === 3" @click.away="">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-address-card mr-2"></i>
                                Información de Contacto
                            </h3>
                            <p class="text-muted">Campos opcionales</p>
                        </div>

                        <div class="card-body">

                            <!-- Dirección -->
                            <div class="form-group">
                                <label for="contactAddress">Dirección</label>
                                <textarea
                                    id="contactAddress"
                                    class="form-control"
                                    style="min-height: 80px;"
                                    x-model="form.contactAddress"
                                    placeholder="Calle, número, piso..."
                                ></textarea>
                            </div>

                            <!-- Ciudad y País (Grid) -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contactCity">Ciudad</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </span>
                                            </div>
                                            <input
                                                type="text"
                                                id="contactCity"
                                                class="form-control"
                                                x-model="form.contactCity"
                                                placeholder="Cochabamba"
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contactCountry">País</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-globe"></i>
                                                </span>
                                            </div>
                                            <input
                                                type="text"
                                                id="contactCountry"
                                                class="form-control"
                                                x-model="form.contactCountry"
                                                placeholder="Bolivia"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Código Postal y RUT/NIT (Grid) -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contactPostalCode">Código Postal</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-hashtag"></i>
                                                </span>
                                            </div>
                                            <input
                                                type="text"
                                                id="contactPostalCode"
                                                class="form-control"
                                                x-model="form.contactPostalCode"
                                                placeholder="0000"
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="taxId">RUT/NIT</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-hashtag"></i>
                                                </span>
                                            </div>
                                            <input
                                                type="text"
                                                id="taxId"
                                                class="form-control"
                                                x-model="form.taxId"
                                                placeholder="987654321"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="button" @click="previousStep()" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Anterior
                            </button>
                            <button type="button" @click="nextStep()" class="btn btn-primary">
                                Siguiente <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 4: Confirmar y Enviar -->
                    <div x-show="currentStep === 4" @click.away="">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-shield-alt mr-2"></i>
                                Confirmar y Enviar
                            </h3>
                            <p class="text-muted">Revisa tu información antes de enviar</p>
                        </div>

                        <div class="card-body">

                            <!-- Alert Info -->
                            <template x-if="successMessage">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <span x-text="successMessage"></span>
                                </div>
                            </template>

                            <template x-if="errorMessage">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <span x-text="errorMessage"></span>
                                </div>
                            </template>

                            <!-- Resumen de Datos -->
                            <div class="callout callout-info">
                                <h5>
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Resumen de tu Solicitud
                                </h5>

                                <div class="summary-item">
                                    <span class="label">Empresa:</span>
                                    <span class="value" x-text="form.companyName"></span>
                                </div>

                                <template x-if="form.legalName">
                                    <div class="summary-item">
                                        <span class="label">Razón Social:</span>
                                        <span class="value" x-text="form.legalName"></span>
                                    </div>
                                </template>

                                <div class="summary-item">
                                    <span class="label">Email:</span>
                                    <span class="value" x-text="form.adminEmail"></span>
                                </div>

                                <div class="summary-item">
                                    <span class="label">Industria:</span>
                                    <span class="value" x-text="form.industryType"></span>
                                </div>

                                <template x-if="form.website">
                                    <div class="summary-item">
                                        <span class="label">Sitio Web:</span>
                                        <span class="value" x-text="form.website"></span>
                                    </div>
                                </template>

                                <template x-if="form.estimatedUsers">
                                    <div class="summary-item">
                                        <span class="label">Usuarios Estimados:</span>
                                        <span class="value" x-text="form.estimatedUsers"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Beneficios -->
                            <div class="benefits-section">
                                <h5>
                                    <i class="fas fa-star mr-2"></i>
                                    Al registrarte obtienes:
                                </h5>

                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="benefit-text">
                                        <h6>Acceso completo al sistema de tickets</h6>
                                        <p>Gestiona todas las incidencias de tu empresa en un solo lugar</p>
                                    </div>
                                </div>

                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="benefit-text">
                                        <h6>Dashboard personalizado</h6>
                                        <p>Métricas y reportes en tiempo real de tu empresa</p>
                                    </div>
                                </div>

                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="benefit-text">
                                        <h6>Respuesta rápida y eficiente</h6>
                                        <p>Clasificación automática por categorías y prioridades</p>
                                    </div>
                                </div>

                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="benefit-text">
                                        <h6>Gestión de equipo</h6>
                                        <p>Invita agentes y administra permisos fácilmente</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Legal Note -->
                            <p class="text-center text-muted small" style="margin-top: 2rem;">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Al enviar esta solicitud, aceptas que un administrador revisará tu información.<br>
                                Recibirás una respuesta en tu email en las próximas 24-48 horas.
                            </p>
                        </div>

                        <div class="card-footer">
                            <button type="button" @click="previousStep()" class="btn btn-secondary" :disabled="isSubmitting">
                                <i class="fas fa-arrow-left mr-1"></i> Anterior
                            </button>
                            <button type="submit" class="btn btn-success" :disabled="isSubmitting">
                                <template x-if="!isSubmitting">
                                    <span>
                                        <i class="fas fa-paper-plane mr-1"></i> Enviar Solicitud
                                    </span>
                                </template>
                                <template x-if="isSubmitting">
                                    <span>
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Enviando...
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Variable global para evitar cargar industrias múltiples veces
        window.companiesIndustriesLoaded = false;
        window.companiesIndustries = [];
        window.companiesIndustriesPromise = null;
        window.wizardFormInitialized = false;

        async function loadIndustriesGlobal() {
            // Si ya está cargado, retornar del cache
            if (window.companiesIndustriesLoaded) {
                console.log('✅ Industrias ya cargadas (desde cache)');
                return window.companiesIndustries;
            }

            // Si ya hay una promesa en curso, esperar a que termine
            if (window.companiesIndustriesPromise) {
                console.log('⏳ Esperando a que terminen las industrias...');
                return await window.companiesIndustriesPromise;
            }

            // Crear nueva promesa de carga
            console.log('🔄 Cargando industrias desde API...');
            window.companiesIndustriesPromise = (async () => {
                try {
                    const response = await fetch('/api/company-industries');
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const data = await response.json();
                    if (data.data && Array.isArray(data.data)) {
                        window.companiesIndustries = data.data;
                        window.companiesIndustriesLoaded = true;
                        console.log('✅ Industrias cargadas globalmente:', window.companiesIndustries.length, 'items');
                        return window.companiesIndustries;
                    }
                } catch (error) {
                    console.error('❌ Error loading industries:', error);
                }
                return [];
            })();

            return await window.companiesIndustriesPromise;
        }

        // Cargar industrias inmediatamente
        loadIndustriesGlobal();

        function wizardForm() {
            return {
                currentStep: 1,
                isSubmitting: false,
                successMessage: '',
                errorMessage: '',
                touched: {
                    companyName: false,
                    adminEmail: false,
                    businessDescription: false,
                    industryType: false,
                    website: false
                },
                industries: [],

                init() {
                    // Guard para evitar inicialización múltiple
                    if (window.wizardFormInitialized) {
                        console.log('⚠️ Wizard ya fue inicializado, saltando...');
                        return;
                    }

                    window.wizardFormInitialized = true;
                    console.log('🚀 Alpine.js inicializado (Primera vez)');

                    // Usar las industrias del cache global
                    if (window.companiesIndustriesLoaded) {
                        this.industries = window.companiesIndustries;
                        console.log('📦 Industrias asignadas desde cache:', this.industries.length, 'items');
                    } else {
                        // Si no están en cache, cargar ahora
                        this.loadIndustriesLocal();
                    }
                },

                form: {
                    companyName: '',
                    adminEmail: '',
                    legalName: '',
                    businessDescription: '',
                    industryType: '',
                    website: '',
                    estimatedUsers: '',
                    contactAddress: '',
                    contactCity: '',
                    contactCountry: '',
                    contactPostalCode: '',
                    taxId: ''
                },

                // Validation Methods
                isValid(field) {
                    const value = this.form[field];

                    switch(field) {
                        case 'companyName':
                            return value && value.length >= 2 && value.length <= 200;

                        case 'adminEmail':
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            return value && emailRegex.test(value);

                        case 'businessDescription':
                            return value && value.length >= 50 && value.length <= 1000;

                        case 'industryType':
                            return value && value.length > 0;

                        case 'website':
                            if (!value) return true; // Optional field
                            try {
                                new URL(value);
                                return true;
                            } catch {
                                return false;
                            }

                        default:
                            return true;
                    }
                },

                getError(field) {
                    const value = this.form[field];

                    switch(field) {
                        case 'companyName':
                            if (!value) return 'Campo requerido';
                            if (value.length < 2) return 'Mínimo 2 caracteres';
                            if (value.length > 200) return 'Máximo 200 caracteres';
                            return '';

                        case 'adminEmail':
                            if (!value) return 'Campo requerido';
                            return 'Email inválido';

                        case 'businessDescription':
                            if (!value) return 'Campo requerido';
                            if (value.length < 50) return `Faltan ${50 - value.length} caracteres`;
                            if (value.length > 1000) return `Excede por ${value.length - 1000} caracteres`;
                            return '';

                        case 'industryType':
                            return 'Selecciona una industria';

                        case 'website':
                            return 'URL inválida (ej: https://example.com)';

                        default:
                            return '';
                    }
                },

                canGoToNextStep() {
                    switch(this.currentStep) {
                        case 1:
                            return this.isValid('companyName') && this.isValid('adminEmail');

                        case 2:
                            return this.isValid('businessDescription') &&
                                   this.isValid('industryType') &&
                                   this.isValid('website');

                        case 3:
                        case 4:
                            return true;

                        default:
                            return false;
                    }
                },

                // Navigation Methods
                nextStep() {
                    if (this.currentStep === 1) {
                        this.touched.companyName = true;
                        this.touched.adminEmail = true;
                    } else if (this.currentStep === 2) {
                        this.touched.businessDescription = true;
                        this.touched.industryType = true;
                        this.touched.website = true;
                    }

                    if (this.canGoToNextStep()) {
                        this.currentStep++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } else {
                        this.errorMessage = 'Por favor, completa correctamente los campos requeridos';
                        setTimeout(() => {
                            this.errorMessage = '';
                        }, 5000);
                    }
                },

                previousStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                        this.errorMessage = '';
                        this.successMessage = '';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                // Load Industries Local
                async loadIndustriesLocal() {
                    const industries = await loadIndustriesGlobal();
                    this.industries = industries;
                    console.log('📦 Industrias asignadas localmente:', this.industries.length);
                },

                // Submit Method
                async submitForm() {
                    if (this.isSubmitting) return;

                    this.isSubmitting = true;
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        // Transform camelCase to snake_case with correct field mapping
                        const payload = {
                            company_name: this.form.companyName,
                            legal_name: this.form.legalName || null,
                            admin_email: this.form.adminEmail,
                            company_description: this.form.businessDescription,
                            request_message: `Solicitud de empresa enviada desde formulario web - Industria: ${this.form.industryType}`,
                            industry_id: this.form.industryType, // This is the UUID
                            website: this.form.website || null,
                            estimated_users: this.form.estimatedUsers ? parseInt(this.form.estimatedUsers) : null,
                            contact_address: this.form.contactAddress || null,
                            contact_city: this.form.contactCity || null,
                            contact_country: this.form.contactCountry || null,
                            contact_postal_code: this.form.contactPostalCode || null,
                            tax_id: this.form.taxId || null
                        };

                        console.log('Sending payload:', payload);

                        const response = await fetch('/api/company-requests', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Authorization': `Bearer ${localStorage.getItem('access_token') || ''}`
                            },
                            body: JSON.stringify(payload)
                        });

                        const responseData = await response.json();
                        console.log('Response:', responseData, 'Status:', response.status);

                        if (!response.ok) {
                            // Show validation errors if available
                            if (responseData.errors) {
                                const errorMessages = Object.entries(responseData.errors)
                                    .map(([field, messages]) => messages.join(', '))
                                    .join('\n');
                                throw new Error(errorMessages || 'Error al enviar la solicitud');
                            }
                            throw new Error(responseData.message || 'Error al enviar la solicitud');
                        }

                        this.successMessage = '✓ Solicitud enviada correctamente. Te enviaremos un email de confirmación pronto.';

                        // Clear form
                        this.form = {
                            companyName: '',
                            adminEmail: '',
                            legalName: '',
                            businessDescription: '',
                            industryType: '',
                            website: '',
                            estimatedUsers: '',
                            contactAddress: '',
                            contactCity: '',
                            contactCountry: '',
                            contactPostalCode: '',
                            taxId: ''
                        };

                        setTimeout(() => {
                            window.location.href = '/';
                        }, 3000);

                    } catch (error) {
                        console.error('Error:', error);
                        this.errorMessage = error.message || 'Error al enviar la solicitud. Por favor intenta nuevamente.';
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
@endsection
