{{--
    Approve Request Modal - Platform Admin
    Confirmation modal for approving company requests
    Following AdminLTE v3 Official Patterns
--}}

<div class="modal fade" id="approveRequestModal" tabindex="-1" role="dialog" aria-labelledby="approveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {{-- Modal Header --}}
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white" id="approveRequestModalLabel">
                    <i class="fas fa-check-circle"></i> Aprobar Solicitud
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body">
                {{-- Info Callout --}}
                <div class="callout callout-info">
                    <h5><i class="fas fa-info-circle"></i> Confirmar Aprobación</h5>
                    <p class="mb-0">
                        ¿Deseas aprobar la solicitud <strong id="approve-request-code">-</strong>?
                    </p>
                </div>

                {{-- Company Info --}}
                <div class="card card-outline card-primary">
                    <div class="card-body p-3">
                        <p class="mb-2">
                            <i class="fas fa-building text-primary"></i>
                            <strong>Empresa:</strong> <span id="approve-company-name">-</span>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope text-primary"></i>
                            <strong>Admin:</strong> <span id="approve-admin-email">-</span>
                        </p>
                    </div>
                </div>

                {{-- What will happen --}}
                <div class="alert alert-light border">
                    <h6 class="alert-heading"><i class="fas fa-list-ul"></i> Al aprobar:</h6>
                    <ul class="mb-0 pl-3">
                        <li>Se creará la empresa en el sistema</li>
                        <li>Se creará la cuenta del administrador</li>
                        <li>Se enviará email con credenciales de acceso</li>
                    </ul>
                </div>

                {{-- Send Email Option --}}
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="approve-send-email" checked>
                        <label class="custom-control-label" for="approve-send-email">
                            <i class="fas fa-envelope"></i> Enviar email de bienvenida con credenciales
                        </label>
                    </div>
                </div>

                {{-- Hidden field --}}
                <input type="hidden" id="approve-request-id">

                {{-- Error Alert --}}
                <div id="approveErrorAlert" class="alert alert-danger" style="display:none;">
                    <i class="fas fa-exclamation-circle"></i> <span id="approveErrorMessage"></span>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmApprove">
                    <i class="fas fa-check"></i> Confirmar Aprobación
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    console.log('[ApproveRequestModal] Script loaded');

    function initApproveRequestModal() {
        console.log('[ApproveRequestModal] Initializing...');

        // Confirm approve button
        $('#btnConfirmApprove').off('click').on('click', function() {
            const requestId = $('#approve-request-id').val();
            const sendEmail = $('#approve-send-email').is(':checked');

            if (!requestId) {
                $('#approveErrorMessage').text('ID de solicitud no encontrado');
                $('#approveErrorAlert').show();
                return;
            }

            // Trigger approve event
            $(document).trigger('confirmApproveRequest', [requestId, sendEmail]);
        });

        // Reset on modal close
        $('#approveRequestModal').on('hidden.bs.modal', function() {
            $('#approveErrorAlert').hide();
            $('#approve-send-email').prop('checked', true);
            ApproveRequestModal.resetButton();
        });

        console.log('[ApproveRequestModal] ✓ Initialized');
    }

    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initApproveRequestModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initApproveRequestModal);
            }
        }, 100);
    }
})();

// Global ApproveRequestModal object
window.ApproveRequestModal = {
    open: function(request) {
        if (!request) {
            console.error('[ApproveRequestModal] No request data provided');
            return;
        }

        console.log('[ApproveRequestModal] Opening for request:', request.id);

        // Populate modal
        $('#approve-request-id').val(request.id);
        $('#approve-request-code').text(request.requestCode || 'N/A');
        $('#approve-company-name').text(request.companyName || 'N/A');
        $('#approve-admin-email').text(request.adminEmail || 'N/A');

        // Reset state
        $('#approveErrorAlert').hide();
        $('#approve-send-email').prop('checked', true);
        this.resetButton();

        // Show modal
        $('#approveRequestModal').modal('show');
    },

    setLoading: function(loading) {
        const $btn = $('#btnConfirmApprove');
        if (loading) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        } else {
            this.resetButton();
        }
    },

    resetButton: function() {
        $('#btnConfirmApprove').prop('disabled', false).html('<i class="fas fa-check"></i> Confirmar Aprobación');
    },

    showError: function(message) {
        $('#approveErrorMessage').text(message);
        $('#approveErrorAlert').show();
    },

    close: function() {
        $('#approveRequestModal').modal('hide');
    }
};
</script>
