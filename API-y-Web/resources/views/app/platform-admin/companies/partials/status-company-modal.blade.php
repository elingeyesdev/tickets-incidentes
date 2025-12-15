{{-- 
    Status Company Modal Component
    
    Features:
    - Change company status (active/suspended)
    - Requires reason for suspension
    - jQuery Validation
    - AdminLTE v3 styling
    
    Usage: @include('app.platform-admin.companies.partials.status-company-modal')
--}}

{{-- Modal: Change Company Status --}}
<div class="modal fade" id="statusCompanyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Cambiar Estado de Empresa
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="statusCompanyForm">
                <div class="modal-body">
                    {{-- Error Alert --}}
                    <div id="statusCompanyErrorAlert" class="alert alert-danger" style="display:none">
                        <i class="fas fa-exclamation-circle"></i> <span id="statusCompanyErrorMessage"></span>
                    </div>
                    
                    <div class="callout callout-info">
                        <i class="fas fa-building"></i> 
                        Empresa: <strong id="statusCompanyName">-</strong>
                        <br>
                        <small class="text-muted">Estado actual: <span id="statusCompanyCurrent">-</span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="newCompanyStatus">Nuevo Estado <span class="text-danger">*</span></label>
                        <select id="newCompanyStatus" name="status" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <option value="active">Activa</option>
                            <option value="suspended">Suspendida</option>
                        </select>
                        <small class="form-text text-muted">Selecciona el nuevo estado</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="statusCompanyReason">Razón</label>
                        <textarea id="statusCompanyReason" name="reason" class="form-control" rows="3" 
                                  maxlength="500" placeholder="Razón del cambio de estado (requerida para suspensión)..."></textarea>
                        <small class="form-text text-muted">Requerida si se suspende la empresa</small>
                    </div>
                    
                    <input type="hidden" id="statusCompanyId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" id="btnConfirmStatus" class="btn btn-warning">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Script for Status Company Modal --}}
<script>
(function() {
    console.log('[StatusCompanyModal] Script loaded - waiting for jQuery...');
    
    function initStatusCompanyModal() {
        console.log('[StatusCompanyModal] Initializing...');
        
        const $modal = $('#statusCompanyModal');
        const $form = $('#statusCompanyForm');
        const $statusSelect = $('#newCompanyStatus');
        const $reasonInput = $('#statusCompanyReason');
        
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
                    status: { required: true },
                    reason: {
                        required: {
                            depends: function() {
                                return $statusSelect.val() === 'suspended';
                            }
                        },
                        minlength: {
                            param: 10,
                            depends: function() {
                                return $statusSelect.val() === 'suspended';
                            }
                        }
                    }
                },
                messages: {
                    status: { required: "Selecciona un estado" },
                    reason: { 
                        required: "La razón es obligatoria para suspender",
                        minlength: "La razón debe tener al menos 10 caracteres"
                    }
                }
            });
            console.log('[StatusCompanyModal] Validation configured');
        }
        
        // Re-validate on status change
        $statusSelect.on('change', function() {
            if (typeof $.fn.validate !== 'undefined') {
                $form.validate().element('#newCompanyStatus');
                if ($(this).val() !== 'suspended') {
                    $reasonInput.removeClass('is-invalid');
                }
            }
        });
        
        // Form submit
        $form.on('submit', function(e) {
            e.preventDefault();
            
            if (typeof $.fn.validate !== 'undefined' && !$form.valid()) {
                return;
            }
            
            const companyId = $('#statusCompanyId').val();
            const newStatus = $statusSelect.val();
            const reason = $reasonInput.val().trim();
            
            if (!companyId || !newStatus) {
                showError('Datos incompletos');
                return;
            }
            
            changeStatus(companyId, newStatus, reason);
        });
        
        // Reset on modal close
        $modal.on('hidden.bs.modal', function() {
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $form.find('.form-text').show();
            $('#statusCompanyErrorAlert').hide();
        });
        
        console.log('[StatusCompanyModal] Initialization complete');
    }
    
    function changeStatus(companyId, newStatus, reason) {
        const token = window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
        const $btn = $('#btnConfirmStatus');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        $('#statusCompanyErrorAlert').hide();
        
        const payload = { status: newStatus };
        if (reason) payload.reason = reason;
        
        $.ajax({
            url: `/api/companies/${companyId}/status`,
            method: 'PUT',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(payload),
            success: function(response) {
                $('#statusCompanyModal').modal('hide');
                $(document).trigger('companyStatusChanged', [companyId, newStatus]);
                
                if (typeof window.showToast === 'function') {
                    window.showToast('success', 'Estado de empresa actualizado');
                }
            },
            error: function(xhr) {
                const errorMsg = translateError(xhr);
                showError(errorMsg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Confirmar');
            }
        });
    }
    
    function showError(message) {
        $('#statusCompanyErrorMessage').text(message);
        $('#statusCompanyErrorAlert').show();
    }
    
    function translateError(xhr) {
        const status = xhr.status;
        const data = xhr.responseJSON;
        
        if (status === 401) return 'Sesión expirada.';
        if (status === 403) return data?.message || 'No tienes permiso.';
        if (status === 404) return 'Empresa no encontrada.';
        if (status === 422 && data?.errors) {
            return Object.values(data.errors).flat().join('. ');
        }
        return data?.message || 'Error al procesar.';
    }
    
    // Open modal
    window.StatusCompanyModal = {
        open: function(companyData) {
            const $form = $('#statusCompanyForm');
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $('#statusCompanyErrorAlert').hide();
            
            $('#statusCompanyId').val(companyData.id);
            $('#statusCompanyName').text(companyData.name || '-');
            
            const status = (companyData.status || '').toLowerCase();
            const statusLabels = { active: 'Activa', suspended: 'Suspendida' };
            $('#statusCompanyCurrent').text(statusLabels[status] || companyData.status || '-');
            
            $('#btnConfirmStatus').prop('disabled', false).html('<i class="fas fa-check"></i> Confirmar');
            
            $('#statusCompanyModal').modal('show');
        }
    };
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initStatusCompanyModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initStatusCompanyModal);
            }
        }, 100);
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[StatusCompanyModal] jQuery did not load');
            }
        }, 10000);
    }
})();
</script>
