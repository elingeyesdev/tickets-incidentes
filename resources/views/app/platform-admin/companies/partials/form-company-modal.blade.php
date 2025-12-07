{{-- 
    Form Company Modal Component (Create/Edit)
    
    Features:
    - jQuery Validation following adminlte-forms-validation.mdc
    - All inputs have name attribute (required for validation)
    - AdminLTE v3 styling
    - Proper error handling
    
    Usage: @include('app.platform-admin.companies.partials.form-company-modal')
--}}

{{-- Modal: Create/Edit Company --}}
<div class="modal fade" id="formCompanyModal" tabindex="-1" aria-labelledby="formCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" id="formCompanyModalHeader">
                <h5 class="modal-title text-white" id="formCompanyModalLabel">
                    <i class="fas fa-plus-circle"></i> <span id="formCompanyTitle">Nueva Empresa</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Error Alert --}}
                <div id="formCompanyErrorAlert" class="alert alert-danger" style="display:none">
                    <i class="fas fa-exclamation-circle"></i> <span id="formCompanyErrorMessage"></span>
                </div>
                
                <form id="companyForm">
                    <div class="row">
                        {{-- Left Column: Basic Info --}}
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-building"></i> Información General</h6>
                            
                            <div class="form-group">
                                <label for="formName">Nombre Comercial <span class="text-danger">*</span></label>
                                <input type="text" id="formName" name="name" class="form-control" 
                                       placeholder="Nombre de la empresa" required minlength="2" maxlength="255">
                                <small class="form-text text-muted">2-255 caracteres</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="formLegalName">Nombre Legal</label>
                                <input type="text" id="formLegalName" name="legal_name" class="form-control" 
                                       placeholder="Nombre legal completo" maxlength="255">
                                <small class="form-text text-muted">Opcional, máximo 255 caracteres</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="formSupportEmail">Email de Soporte <span class="text-danger">*</span></label>
                                <input type="email" id="formSupportEmail" name="support_email" class="form-control" 
                                       placeholder="soporte@empresa.com" required>
                                <small class="form-text text-muted">Email principal de contacto</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="formPhone">Teléfono</label>
                                <input type="text" id="formPhone" name="phone" class="form-control" 
                                       placeholder="+591 12345678" maxlength="20">
                                <small class="form-text text-muted">Máximo 20 caracteres</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="formWebsite">Sitio Web</label>
                                <input type="url" id="formWebsite" name="website" class="form-control" 
                                       placeholder="https://www.empresa.com">
                                <small class="form-text text-muted">URL completa con https://</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="formIndustryId">Industria <span class="text-danger">*</span></label>
                                <select id="formIndustryId" name="industry_id" class="form-control" required>
                                    <option value="">Cargando industrias...</option>
                                </select>
                                <small class="form-text text-muted">Requerida en creación</small>
                            </div>
                        </div>
                        
                        {{-- Right Column: Contact Info --}}
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-map-marker-alt"></i> Información de Contacto</h6>
                            
                            <div class="form-group">
                                <label for="formAddress">Dirección</label>
                                <input type="text" id="formAddress" name="contact_address" class="form-control" 
                                       placeholder="Calle y número" maxlength="255">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="formCity">Ciudad</label>
                                        <input type="text" id="formCity" name="contact_city" class="form-control" 
                                               placeholder="Ciudad" maxlength="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="formState">Estado/Región</label>
                                        <input type="text" id="formState" name="contact_state" class="form-control" 
                                               placeholder="Estado" maxlength="100">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="formCountry">País</label>
                                        <input type="text" id="formCountry" name="contact_country" class="form-control" 
                                               placeholder="País" maxlength="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="formPostalCode">Código Postal</label>
                                        <input type="text" id="formPostalCode" name="contact_postal_code" class="form-control" 
                                               placeholder="00000" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="formTaxId">Tax ID (RUT/NIT)</label>
                                <input type="text" id="formTaxId" name="tax_id" class="form-control" 
                                       placeholder="12.345.678-9" maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="formLegalRep">Representante Legal</label>
                                <input type="text" id="formLegalRep" name="legal_representative" class="form-control" 
                                       placeholder="Nombre completo" maxlength="255">
                            </div>
                            
                            <div class="form-group">
                                <label for="formTimezone">Zona Horaria <span class="text-danger">*</span></label>
                                <select id="formTimezone" name="timezone" class="form-control" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="America/La_Paz">America/La_Paz (Bolivia)</option>
                                    <option value="America/Santiago">America/Santiago (Chile)</option>
                                    <option value="America/Bogota">America/Bogota (Colombia)</option>
                                    <option value="America/Lima">America/Lima (Perú)</option>
                                    <option value="America/Buenos_Aires">America/Buenos_Aires (Argentina)</option>
                                    <option value="America/Sao_Paulo">America/Sao_Paulo (Brasil)</option>
                                    <option value="America/Mexico_City">America/Mexico_City (México)</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Admin Section (Only for Create) --}}
                    <div class="row mt-3" id="adminSection">
                        <div class="col-12">
                            <hr>
                            <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-user-shield"></i> Administrador</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="formAdminId">Usuario Administrador <span class="text-danger">*</span></label>
                                <select id="formAdminId" name="admin_user_id" class="form-control">
                                    <option value="">Cargando usuarios...</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Requerido en creación. No editable después.
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="formCompanyId" name="company_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btnSaveCompany" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Script for Form Company Modal --}}
<script>
(function() {
    console.log('[FormCompanyModal] Script loaded - waiting for jQuery...');
    
    let industriesLoaded = false;
    let adminUsersLoaded = false;
    let industriesCache = [];
    let adminUsersCache = [];
    
    function initFormCompanyModal() {
        console.log('[FormCompanyModal] Initializing...');
        
        const $modal = $('#formCompanyModal');
        const $form = $('#companyForm');
        const $btnSave = $('#btnSaveCompany');
        
        // Configure jQuery Validation (AdminLTE official pattern)
        if (typeof $.fn.validate !== 'undefined') {
            $form.validate({
                errorElement: 'span',
                errorClass: 'invalid-feedback',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass('is-invalid');
                    $(element).closest('.form-group').find('.form-text').hide();
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass('is-invalid');
                    $(element).closest('.form-group').find('.form-text').show();
                },
                rules: {
                    name: { required: true, minlength: 2, maxlength: 255 },
                    support_email: { required: true, email: true },
                    industry_id: { required: true },
                    timezone: { required: true },
                    admin_user_id: {
                        required: {
                            depends: function() {
                                return !$('#formCompanyId').val(); // Required only on create
                            }
                        }
                    }
                },
                messages: {
                    name: {
                        required: "El nombre es obligatorio",
                        minlength: "Mínimo 2 caracteres",
                        maxlength: "Máximo 255 caracteres"
                    },
                    support_email: {
                        required: "El email de soporte es obligatorio",
                        email: "Ingresa un email válido"
                    },
                    industry_id: { required: "Selecciona una industria" },
                    timezone: { required: "Selecciona una zona horaria" },
                    admin_user_id: { required: "Selecciona un administrador" }
                }
            });
            console.log('[FormCompanyModal] jQuery Validation configured');
        } else {
            console.warn('[FormCompanyModal] jQuery Validation not loaded');
        }
        
        // Save button click
        $btnSave.on('click', function() {
            if (typeof $.fn.validate !== 'undefined' && !$form.valid()) {
                return;
            }
            saveCompany();
        });
        
        // Re-validate selects on change (for Select2 compatibility)
        $('#formIndustryId, #formAdminId, #formTimezone').on('change', function() {
            if (typeof $.fn.validate !== 'undefined') {
                $form.validate().element(this);
            }
        });
        
        // Load initial data
        loadIndustries();
        loadAdminUsers();
        
        console.log('[FormCompanyModal] Initialization complete');
    }
    
    // Load industries
    function loadIndustries() {
        if (industriesLoaded) return;
        
        $.ajax({
            url: '/api/company-industries',
            method: 'GET',
            success: function(response) {
                industriesCache = response.data || [];
                industriesLoaded = true;
                populateIndustries();
            },
            error: function() {
                console.error('[FormCompanyModal] Error loading industries');
                $('#formIndustryId').html('<option value="">Error al cargar</option>');
            }
        });
    }
    
    function populateIndustries() {
        const $select = $('#formIndustryId');
        let html = '<option value="">Seleccionar industria...</option>';
        industriesCache.forEach(ind => {
            html += `<option value="${ind.id}">${ind.name}</option>`;
        });
        $select.html(html).prop('disabled', false);
    }
    
    // Load admin users
    function loadAdminUsers() {
        if (adminUsersLoaded) return;
        
        const token = window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
        
        $.ajax({
            url: '/api/users?status=ACTIVE&per_page=100',
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` },
            success: function(response) {
                adminUsersCache = response.data || [];
                adminUsersLoaded = true;
                populateAdminUsers();
            },
            error: function() {
                console.error('[FormCompanyModal] Error loading admin users');
                $('#formAdminId').html('<option value="">Error al cargar</option>');
            }
        });
    }
    
    function populateAdminUsers() {
        const $select = $('#formAdminId');
        let html = '<option value="">Seleccionar usuario...</option>';
        adminUsersCache.forEach(user => {
            html += `<option value="${user.id}">${user.userCode} - ${user.email}</option>`;
        });
        $select.html(html).prop('disabled', false);
    }
    
    // Save company
    function saveCompany() {
        const companyId = $('#formCompanyId').val();
        const isCreate = !companyId;
        const token = window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
        
        const payload = {
            name: $('#formName').val(),
            legal_name: $('#formLegalName').val() || null,
            support_email: $('#formSupportEmail').val(),
            phone: $('#formPhone').val() || null,
            website: $('#formWebsite').val() || null,
            contact_address: $('#formAddress').val() || null,
            contact_city: $('#formCity').val() || null,
            contact_state: $('#formState').val() || null,
            contact_country: $('#formCountry').val() || null,
            contact_postal_code: $('#formPostalCode').val() || null,
            tax_id: $('#formTaxId').val() || null,
            legal_representative: $('#formLegalRep').val() || null,
            timezone: $('#formTimezone').val()
        };
        
        // For create: add industry_id and admin_user_id
        if (isCreate) {
            payload.industry_id = $('#formIndustryId').val();
            payload.admin_user_id = $('#formAdminId').val();
        } else {
            // For edit: industry can be changed
            const industryId = $('#formIndustryId').val();
            if (industryId) payload.industry_id = industryId;
        }
        
        const method = isCreate ? 'POST' : 'PATCH';
        const url = isCreate ? '/api/companies' : `/api/companies/${companyId}`;
        
        const $btn = $('#btnSaveCompany');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        $('#formCompanyErrorAlert').hide();
        
        $.ajax({
            url: url,
            method: method,
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(payload),
            success: function(response) {
                if (response.data || response.success) {
                    $('#formCompanyModal').modal('hide');
                    $(document).trigger('companySaved', [isCreate ? 'create' : 'update']);
                    
                    if (typeof window.showToast === 'function') {
                        window.showToast('success', isCreate ? 'Empresa creada exitosamente' : 'Empresa actualizada exitosamente');
                    }
                } else {
                    showFormError(response.message || 'Error al guardar');
                }
            },
            error: function(xhr) {
                const errorMsg = translateError(xhr);
                showFormError(errorMsg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
            }
        });
    }
    
    function showFormError(message) {
        $('#formCompanyErrorMessage').text(message);
        $('#formCompanyErrorAlert').show();
    }
    
    function translateError(xhr) {
        const status = xhr.status;
        const data = xhr.responseJSON;
        
        if (status === 401) return 'Sesión expirada. Por favor recarga la página.';
        if (status === 403) return data?.message || 'No tienes permiso para esta acción.';
        if (status === 404) return 'Recurso no encontrado.';
        if (status === 422 && data?.errors) {
            return Object.values(data.errors).flat().join('. ');
        }
        return data?.message || 'Error al procesar la solicitud.';
    }
    
    // Open modal for create
    window.FormCompanyModal = {
        openCreate: function() {
            const $modal = $('#formCompanyModal');
            const $form = $('#companyForm');
            
            // Reset form
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $form.find('.form-text').show();
            $('#formCompanyErrorAlert').hide();
            
            // Set create mode
            $('#formCompanyId').val('');
            $('#formCompanyTitle').text('Nueva Empresa');
            $('#formCompanyModalHeader').removeClass('bg-info').addClass('bg-success');
            
            // Show admin section (required for create)
            $('#adminSection').show();
            $('#formAdminId').prop('required', true);
            $('#formIndustryId').prop('disabled', false);
            
            // Refresh selects if needed
            if (!industriesLoaded) loadIndustries();
            if (!adminUsersLoaded) loadAdminUsers();
            
            $modal.modal('show');
        },
        
        openEdit: function(companyData) {
            const $modal = $('#formCompanyModal');
            const $form = $('#companyForm');
            
            // Reset form
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $form.find('.form-text').show();
            $('#formCompanyErrorAlert').hide();
            
            // Set edit mode
            $('#formCompanyId').val(companyData.id);
            $('#formCompanyTitle').text('Editar Empresa');
            $('#formCompanyModalHeader').removeClass('bg-success').addClass('bg-info');
            
            // Hide admin section (cannot change admin)
            $('#adminSection').hide();
            $('#formAdminId').prop('required', false);
            
            // Populate form
            $('#formName').val(companyData.name || '');
            $('#formLegalName').val(companyData.legalName || '');
            $('#formSupportEmail').val(companyData.supportEmail || '');
            $('#formPhone').val(companyData.phone || '');
            $('#formWebsite').val(companyData.website || '');
            $('#formAddress').val(companyData.contactAddress || '');
            $('#formCity').val(companyData.contactCity || '');
            $('#formState').val(companyData.contactState || '');
            $('#formCountry').val(companyData.contactCountry || '');
            $('#formPostalCode').val(companyData.contactPostalCode || '');
            $('#formTaxId').val(companyData.taxId || '');
            $('#formLegalRep').val(companyData.legalRepresentative || '');
            $('#formTimezone').val(companyData.timezone || 'UTC');
            
            // Set industry (editable)
            if (industriesLoaded && companyData.industryId) {
                $('#formIndustryId').val(companyData.industryId);
            }
            
            $modal.modal('show');
        }
    };
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initFormCompanyModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initFormCompanyModal);
            }
        }, 100);
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[FormCompanyModal] jQuery did not load');
            }
        }, 10000);
    }
})();
</script>
