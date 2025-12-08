{{--
    Reject Request Modal - Platform Admin
    Confirmation modal for rejecting company requests
    Following AdminLTE v3 Official Patterns
--}}

<div class="modal fade" id="rejectRequestModal" tabindex="-1" role="dialog" aria-labelledby="rejectRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {{-- Modal Header --}}
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="rejectRequestModalLabel">
                    <i class="fas fa-ban"></i> Rechazar Solicitud
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body">
                {{-- Warning Callout --}}
                <div class="callout callout-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Confirmar Rechazo</h5>
                    <p class="mb-0">
                        ¿Deseas rechazar la solicitud <strong id="reject-request-code">-</strong>?
                    </p>
                </div>

                {{-- Company Info --}}
                <div class="card card-outline card-danger">
                    <div class="card-body p-3">
                        <p class="mb-2">
                            <i class="fas fa-building text-danger"></i>
                            <strong>Empresa:</strong> <span id="reject-company-name">-</span>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope text-danger"></i>
                            <strong>Solicitante:</strong> <span id="reject-admin-email">-</span>
                        </p>
                    </div>
                </div>

                {{-- Rejection Reason Form --}}
                <form id="rejectRequestForm">
                    <div class="form-group">
                        <label for="reject-reason">
                            Motivo del rechazo <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            id="reject-reason" 
                            name="reason"
                            class="form-control" 
                            rows="4" 
                            placeholder="Explica la razón del rechazo (mínimo 10 caracteres)..."
                            required
                            minlength="10"
                            maxlength="1000"
                        ></textarea>
                        <small class="form-text text-muted">
                            <span id="reject-char-count">0</span> / 10 caracteres mínimos
                        </small>
                    </div>

                    {{-- Hidden field --}}
                    <input type="hidden" id="reject-request-id">
                </form>

                {{-- Info about notification --}}
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Se notificará al solicitante sobre el rechazo vía email.
                </div>

                {{-- Error Alert --}}
                <div id="rejectErrorAlert" class="alert alert-danger" style="display:none;">
                    <i class="fas fa-exclamation-circle"></i> <span id="rejectErrorMessage"></span>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmReject" disabled>
                    <i class="fas fa-ban"></i> Confirmar Rechazo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    console.log('[RejectRequestModal] Script loaded');

    function initRejectRequestModal() {
        console.log('[RejectRequestModal] Initializing...');

        // Character counter and validation
        $('#reject-reason').off('input').on('input', function() {
            const count = $(this).val().length;
            $('#reject-char-count').text(count);

            // Enable/disable button based on minimum length
            if (count >= 10) {
                $('#btnConfirmReject').prop('disabled', false);
                $('#reject-char-count').removeClass('text-danger').addClass('text-success');
            } else {
                $('#btnConfirmReject').prop('disabled', true);
                $('#reject-char-count').removeClass('text-success').addClass('text-danger');
            }
        });

        // Confirm reject button
        $('#btnConfirmReject').off('click').on('click', function() {
            const requestId = $('#reject-request-id').val();
            const reason = $('#reject-reason').val().trim();

            if (!requestId) {
                $('#rejectErrorMessage').text('ID de solicitud no encontrado');
                $('#rejectErrorAlert').show();
                return;
            }

            if (reason.length < 10) {
                $('#rejectErrorMessage').text('El motivo debe tener al menos 10 caracteres');
                $('#rejectErrorAlert').show();
                return;
            }

            // Trigger reject event
            $(document).trigger('confirmRejectRequest', [requestId, reason]);
        });

        // Reset on modal close
        $('#rejectRequestModal').on('hidden.bs.modal', function() {
            $('#rejectErrorAlert').hide();
            $('#reject-reason').val('');
            $('#reject-char-count').text('0').removeClass('text-success text-danger');
            $('#btnConfirmReject').prop('disabled', true);
            RejectRequestModal.resetButton();
        });

        console.log('[RejectRequestModal] ✓ Initialized');
    }

    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initRejectRequestModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initRejectRequestModal);
            }
        }, 100);
    }
})();

// Global RejectRequestModal object
window.RejectRequestModal = {
    open: function(request) {
        if (!request) {
            console.error('[RejectRequestModal] No request data provided');
            return;
        }

        console.log('[RejectRequestModal] Opening for request:', request.id);

        // Populate modal
        $('#reject-request-id').val(request.id);
        $('#reject-request-code').text(request.requestCode || 'N/A');
        $('#reject-company-name').text(request.companyName || 'N/A');
        $('#reject-admin-email').text(request.adminEmail || 'N/A');

        // Reset state
        $('#rejectErrorAlert').hide();
        $('#reject-reason').val('');
        $('#reject-char-count').text('0').removeClass('text-success text-danger');
        $('#btnConfirmReject').prop('disabled', true);
        this.resetButton();

        // Show modal
        $('#rejectRequestModal').modal('show');
    },

    setLoading: function(loading) {
        const $btn = $('#btnConfirmReject');
        if (loading) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        } else {
            this.resetButton();
        }
    },

    resetButton: function() {
        const count = $('#reject-reason').val().length;
        $('#btnConfirmReject')
            .prop('disabled', count < 10)
            .html('<i class="fas fa-ban"></i> Confirmar Rechazo');
    },

    showError: function(message) {
        $('#rejectErrorMessage').text(message);
        $('#rejectErrorAlert').show();
    },

    close: function() {
        $('#rejectRequestModal').modal('hide');
    }
};
</script>
