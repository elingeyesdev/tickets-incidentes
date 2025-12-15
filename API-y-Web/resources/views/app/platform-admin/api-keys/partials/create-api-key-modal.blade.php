{{--
    Create API Key Modal Component
    
    Features:
    - Select company from dropdown
    - Enter name and description
    - Select type (production/development/testing)
    - Shows generated key for copy
    - jQuery Validation integration
    - AdminLTE v3 styling
    
    Usage: @include('app.platform-admin.api-keys.partials.create-api-key-modal')
--}}

{{-- Modal --}}
<div class="modal fade" id="createApiKeyModal" tabindex="-1" aria-labelledby="createApiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- Loading Overlay --}}
            <div id="createApiKeyLoading" class="overlay" style="display:none;">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
            
            {{-- Header --}}
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="createApiKeyModalLabel">
                    <i class="fas fa-key mr-2"></i>Generar Nueva API Key
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            {{-- Body: Form --}}
            <div class="modal-body" id="createApiKeyFormBody">
                <form id="createApiKeyForm" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="apiKeyCompany">Empresa <span class="text-danger">*</span></label>
                                <select id="apiKeyCompany" name="company_id" class="form-control select2" required>
                                    <option value="">Selecciona una empresa...</option>
                                </select>
                                <small class="form-text text-muted">La empresa que usará esta API Key.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="apiKeyType">Tipo <span class="text-danger">*</span></label>
                                <select id="apiKeyType" name="type" class="form-control" required>
                                    <option value="">Selecciona un tipo...</option>
                                    <option value="production">Producción</option>
                                    <option value="development">Desarrollo</option>
                                    <option value="testing">Testing</option>
                                </select>
                                <small class="form-text text-muted">Indica el ambiente de uso.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="apiKeyName">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="apiKeyName" name="name" class="form-control" 
                               placeholder="Ej: API Key Principal, Sistema ERP, etc." 
                               required maxlength="100">
                        <small class="form-text text-muted">Un nombre descriptivo para identificar esta key.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="apiKeyDescription">Descripción</label>
                        <textarea id="apiKeyDescription" name="description" class="form-control" 
                                  rows="2" maxlength="500"
                                  placeholder="Descripción opcional del uso de esta key..."></textarea>
                        <small class="form-text text-muted float-right"><span id="descCharCount">0</span>/500</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="apiKeyExpires">Fecha de Expiración (opcional)</label>
                        <input type="date" id="apiKeyExpires" name="expires_at" class="form-control">
                        <small class="form-text text-muted">Deja vacío para que nunca expire.</small>
                    </div>
                </form>
            </div>
            
            {{-- Body: Success (shows generated key) --}}
            <div class="modal-body" id="createApiKeySuccessBody" style="display:none;">
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                    <h5 class="text-success">¡API Key generada exitosamente!</h5>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>¡Importante!</strong> Copia esta key ahora. No podrás verla completa después.
                </div>
                
                <div class="form-group">
                    <label>API Key:</label>
                    <div class="input-group">
                        <input type="text" id="generatedApiKey" class="form-control font-weight-bold text-primary" 
                               readonly style="font-family: monospace; font-size: 0.9rem;">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary btn-copy-generated" title="Copiar">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-6">
                        <strong>Empresa:</strong><br>
                        <span id="successCompanyName">-</span>
                    </div>
                    <div class="col-6">
                        <strong>Tipo:</strong><br>
                        <span id="successTypeBadge">-</span>
                    </div>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnGenerateApiKey">
                    <i class="fas fa-key mr-1"></i>Generar API Key
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Script --}}
<script>
(function() {
    console.log('[CreateApiKeyModal] Script loaded');
    
    function initCreateApiKeyModal() {
        console.log('[CreateApiKeyModal] Initializing');
        
        const $modal = $('#createApiKeyModal');
        const $form = $('#createApiKeyForm');
        const $loading = $('#createApiKeyLoading');
        const $formBody = $('#createApiKeyFormBody');
        const $successBody = $('#createApiKeySuccessBody');
        const $btnGenerate = $('#btnGenerateApiKey');
        const $companySelect = $('#apiKeyCompany');
        
        // Character counter
        $('#apiKeyDescription').on('input', function() {
            $('#descCharCount').text($(this).val().length);
        });
        
        // Load companies into select
        async function loadCompanies() {
            try {
                const token = window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
                const response = await fetch('/api/companies?per_page=100&status=active', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) return;
                
                const data = await response.json();
                const companies = data.data || [];
                
                $companySelect.html('<option value="">Selecciona una empresa...</option>');
                companies.forEach(company => {
                    const escaped = $('<div>').text(company.name).html();
                    $companySelect.append(`<option value="${company.id}">${escaped}</option>`);
                });
                
                // Initialize Select2
                if (typeof $.fn.select2 !== 'undefined') {
                    $companySelect.select2({
                        theme: 'bootstrap4',
                        dropdownParent: $modal,
                        placeholder: 'Selecciona una empresa...',
                        allowClear: true
                    });
                }
                
            } catch (error) {
                console.error('[CreateApiKeyModal] Error loading companies:', error);
            }
        }
        
        // Configure jQuery Validation
        if (typeof $.fn.validate !== 'undefined') {
            $form.validate({
                errorElement: 'span',
                errorClass: 'invalid-feedback',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                    $(element).closest('.form-group').find('.form-text').hide();
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                    $(element).closest('.form-group').find('.form-text').show();
                },
                rules: {
                    company_id: { required: true },
                    name: { required: true, maxlength: 100 },
                    type: { required: true },
                    description: { maxlength: 500 }
                },
                messages: {
                    company_id: { required: 'Debes seleccionar una empresa' },
                    name: { 
                        required: 'El nombre es obligatorio',
                        maxlength: 'Máximo 100 caracteres'
                    },
                    type: { required: 'Debes seleccionar un tipo' }
                }
            });
        }
        
        // Select2 validation fix
        $companySelect.on('change', function() {
            if ($form.data('validator')) {
                $form.validate().element('#apiKeyCompany');
            }
        });
        
        // Generate button handler
        $btnGenerate.on('click', async function() {
            if (!$form.valid()) return;
            
            $loading.show();
            $btnGenerate.prop('disabled', true);
            
            try {
                const token = window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
                const response = await fetch('/api/admin/api-keys', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        company_id: $companySelect.val(),
                        name: $('#apiKeyName').val(),
                        description: $('#apiKeyDescription').val(),
                        type: $('#apiKeyType').val(),
                        expires_at: $('#apiKeyExpires').val() || null
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw { status: response.status, data: data };
                }
                
                // Show success
                $formBody.hide();
                $successBody.show();
                $btnGenerate.hide();
                
                // Populate success data
                $('#generatedApiKey').val(data.data.key);
                $('#successCompanyName').text(data.data.company?.name || 'N/A');
                
                const typeBadges = {
                    production: '<span class="badge badge-primary">Producción</span>',
                    development: '<span class="badge badge-warning">Desarrollo</span>',
                    testing: '<span class="badge badge-info">Testing</span>'
                };
                $('#successTypeBadge').html(typeBadges[data.data.type] || data.data.type);
                
                // Trigger refresh on parent
                $(document).trigger('apiKeyCreated');
                
                if (typeof window.showToast === 'function') {
                    window.showToast('success', 'API Key generada exitosamente');
                }
                
            } catch (error) {
                console.error('[CreateApiKeyModal] Error:', error);
                
                let message = 'Error al generar API Key';
                if (error.data?.errors) {
                    message = Object.values(error.data.errors).flat().join('. ');
                } else if (error.data?.message) {
                    message = error.data.message;
                }
                
                if (typeof window.showToast === 'function') {
                    window.showToast('error', message);
                }
            } finally {
                $loading.hide();
                $btnGenerate.prop('disabled', false);
            }
        });
        
        // Copy generated key
        $modal.on('click', '.btn-copy-generated', function() {
            const key = $('#generatedApiKey').val();
            navigator.clipboard.writeText(key).then(() => {
                if (typeof window.showToast === 'function') {
                    window.showToast('success', 'API Key copiada al portapapeles');
                }
            }).catch(() => {
                // Fallback
                $('#generatedApiKey').select();
                document.execCommand('copy');
                if (typeof window.showToast === 'function') {
                    window.showToast('success', 'API Key copiada');
                }
            });
        });
        
        // Reset on modal close
        $modal.on('hidden.bs.modal', function() {
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $form.find('.form-text').show();
            $('#descCharCount').text('0');
            
            if ($companySelect.hasClass('select2-hidden-accessible')) {
                $companySelect.val(null).trigger('change');
            }
            
            $formBody.show();
            $successBody.hide();
            $btnGenerate.show();
        });
        
        // Open handler
        $modal.on('show.bs.modal', function() {
            loadCompanies();
        });
        
        // Expose API
        window.CreateApiKeyModal = {
            open: function() {
                $modal.modal('show');
            }
        };
        
        console.log('[CreateApiKeyModal] ✓ Initialized');
    }
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initCreateApiKeyModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initCreateApiKeyModal);
            }
        }, 100);
    }
})();
</script>
