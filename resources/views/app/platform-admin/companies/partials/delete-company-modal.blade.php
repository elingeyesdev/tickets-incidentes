{{-- 
    Delete Company Modal Component
    
    Features:
    - Confirmation by typing company name
    - SweetAlert2 for final confirmation
    - AdminLTE v3 styling
    - Proper error handling
    
    Usage: @include('app.platform-admin.companies.partials.delete-company-modal')
--}}

{{-- Modal: Delete Company --}}
<div class="modal fade" id="deleteCompanyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-trash"></i> Eliminar Empresa
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Error Alert --}}
                <div id="deleteCompanyErrorAlert" class="alert alert-danger" style="display:none">
                    <i class="fas fa-exclamation-circle"></i> <span id="deleteCompanyErrorMessage"></span>
                </div>
                
                <div class="callout callout-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¿Eliminar la empresa "<span id="deleteCompanyName">-</span>"?</strong>
                </div>
                
                <p>Esta acción es <strong class="text-danger">IRREVERSIBLE</strong> y eliminará:</p>
                <ul class="text-danger">
                    <li>Todos los datos de la empresa</li>
                    <li>Todos los agentes asociados</li>
                    <li>Todos los tickets y datos relacionados</li>
                    <li>Todas las categorías y configuraciones</li>
                </ul>
                
                <div class="alert alert-warning">
                    <strong><i class="fas fa-keyboard"></i> CONFIRMACIÓN:</strong><br>
                    Escribe el nombre exacto de la empresa para confirmar.
                </div>
                
                <div class="form-group">
                    <input type="text" id="deleteConfirmation" class="form-control" 
                           placeholder="Escribe el nombre de la empresa...">
                    <small class="form-text text-muted">El nombre debe coincidir exactamente</small>
                </div>
                
                <input type="hidden" id="deleteCompanyId">
                <input type="hidden" id="deleteCompanyExpected">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btnConfirmDelete" class="btn btn-danger" disabled>
                    <i class="fas fa-trash"></i> Eliminar Permanentemente
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Script for Delete Company Modal --}}
<script>
(function() {
    console.log('[DeleteCompanyModal] Script loaded - waiting for jQuery...');
    
    function initDeleteCompanyModal() {
        console.log('[DeleteCompanyModal] Initializing...');
        
        const $modal = $('#deleteCompanyModal');
        const $input = $('#deleteConfirmation');
        const $btn = $('#btnConfirmDelete');
        
        // Validate confirmation text
        $input.on('input', function() {
            const expected = $('#deleteCompanyExpected').val();
            const entered = $(this).val().trim();
            $btn.prop('disabled', entered !== expected);
        });
        
        // Delete button click
        $btn.on('click', function() {
            const companyId = $('#deleteCompanyId').val();
            const companyName = $('#deleteCompanyExpected').val();
            
            // Use SweetAlert2 for final confirmation
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Estás absolutamente seguro?',
                    html: `Vas a eliminar permanentemente <strong>${companyName}</strong>.<br>Esta acción NO se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeDelete(companyId);
                    }
                });
            } else {
                if (confirm(`¿Estás seguro de eliminar ${companyName}? Esta acción es irreversible.`)) {
                    executeDelete(companyId);
                }
            }
        });
        
        // Reset on modal close
        $modal.on('hidden.bs.modal', function() {
            $input.val('');
            $btn.prop('disabled', true);
            $('#deleteCompanyErrorAlert').hide();
        });
        
        console.log('[DeleteCompanyModal] Initialization complete');
    }
    
    function executeDelete(companyId) {
        const token = window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
        const $btn = $('#btnConfirmDelete');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');
        $('#deleteCompanyErrorAlert').hide();
        
        $.ajax({
            url: `/api/companies/${companyId}`,
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` },
            success: function(response) {
                $('#deleteCompanyModal').modal('hide');
                $(document).trigger('companyDeleted', [companyId]);
                
                if (typeof window.showToast === 'function') {
                    window.showToast('success', 'Empresa eliminada exitosamente');
                }
            },
            error: function(xhr) {
                const errorMsg = translateError(xhr);
                showError(errorMsg);
                $btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Eliminar Permanentemente');
            }
        });
    }
    
    function showError(message) {
        $('#deleteCompanyErrorMessage').text(message);
        $('#deleteCompanyErrorAlert').show();
    }
    
    function translateError(xhr) {
        const status = xhr.status;
        const data = xhr.responseJSON;
        
        if (status === 401) return 'Sesión expirada.';
        if (status === 403) return data?.message || 'No tienes permiso para eliminar esta empresa.';
        if (status === 404) return 'Empresa no encontrada.';
        if (status === 409) return data?.message || 'No se puede eliminar: tiene datos asociados.';
        if (status === 422 && data?.errors) {
            return Object.values(data.errors).flat().join('. ');
        }
        return data?.message || 'Error al eliminar la empresa.';
    }
    
    // Open modal
    window.DeleteCompanyModal = {
        open: function(companyData) {
            $('#deleteConfirmation').val('');
            $('#btnConfirmDelete').prop('disabled', true).html('<i class="fas fa-trash"></i> Eliminar Permanentemente');
            $('#deleteCompanyErrorAlert').hide();
            
            $('#deleteCompanyId').val(companyData.id);
            $('#deleteCompanyName').text(companyData.name || '-');
            $('#deleteCompanyExpected').val(companyData.name || '');
            
            $('#deleteCompanyModal').modal('show');
        }
    };
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initDeleteCompanyModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initDeleteCompanyModal);
            }
        }, 100);
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[DeleteCompanyModal] jQuery did not load');
            }
        }, 10000);
    }
})();
</script>
