{{--
    View Request Modal - Platform Admin
    Displays detailed information about a company request
    Following AdminLTE v3 Official Patterns
--}}

<div class="modal fade" id="viewRequestModal" tabindex="-1" role="dialog" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            {{-- Modal Header --}}
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="viewRequestModalLabel">
                    <i class="fas fa-file-invoice"></i> Detalles de Solicitud
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body p-0">
                {{-- Request Header Info --}}
                <div class="p-3 bg-light border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="m-0">
                                <span id="view-company-name" class="font-weight-bold">-</span>
                            </h4>
                            <small class="text-muted">
                                <i class="fas fa-hashtag"></i> Código: <code id="view-request-code">-</code>
                            </small>
                        </div>
                        <div class="col-md-6 text-right">
                            <span id="view-status-badge">-</span>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt"></i> Registrado: <span id="view-created-at">-</span>
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Tabs Navigation --}}
                <ul class="nav nav-tabs nav-justified" id="requestTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-info" data-toggle="pill" href="#pane-info" role="tab">
                            <i class="fas fa-building"></i> Información
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-contact" data-toggle="pill" href="#pane-contact" role="tab">
                            <i class="fas fa-address-card"></i> Contacto
                        </a>
                    </li>
                    <li class="nav-item" id="tab-review-li" style="display:none;">
                        <a class="nav-link" id="tab-review" data-toggle="pill" href="#pane-review" role="tab">
                            <i class="fas fa-clipboard-check"></i> Revisión
                        </a>
                    </li>
                </ul>

                {{-- Tabs Content --}}
                <div class="tab-content p-3" id="requestTabsContent">
                    {{-- Tab: Company Information --}}
                    <div class="tab-pane fade show active" id="pane-info" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-building"></i> Nombre de Empresa</label>
                                    <p class="mb-2 font-weight-bold" id="view-company-name-full">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-file-signature"></i> Razón Social</label>
                                    <p class="mb-2" id="view-legal-name">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-industry"></i> Industria</label>
                                    <p class="mb-2" id="view-industry">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-id-card"></i> Tax ID (RUT/NIT)</label>
                                    <p class="mb-2" id="view-tax-id">-</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-envelope"></i> Email Administrador</label>
                                    <p class="mb-2" id="view-admin-email">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-globe"></i> Sitio Web</label>
                                    <p class="mb-2" id="view-website">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-users"></i> Usuarios Estimados</label>
                                    <p class="mb-2" id="view-estimated-users">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-phone"></i> Teléfono</label>
                                    <p class="mb-2" id="view-phone">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="text-muted mb-1"><i class="fas fa-align-left"></i> Descripción del Negocio</label>
                            <textarea id="view-description" class="form-control" rows="3" readonly></textarea>
                        </div>
                    </div>

                    {{-- Tab: Contact Information --}}
                    <div class="tab-pane fade" id="pane-contact" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-map-marker-alt"></i> Dirección</label>
                                    <p class="mb-2" id="view-address">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-city"></i> Ciudad</label>
                                    <p class="mb-2" id="view-city">-</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-globe-americas"></i> País</label>
                                    <p class="mb-2" id="view-country">-</p>
                                </div>
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-mail-bulk"></i> Código Postal</label>
                                    <p class="mb-2" id="view-postal-code">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab: Review Information (only shown if not pending) --}}
                    <div class="tab-pane fade" id="pane-review" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-user-check"></i> Revisado por</label>
                                    <p class="mb-2" id="view-reviewed-by">-</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-calendar-check"></i> Fecha de Revisión</label>
                                    <p class="mb-2" id="view-reviewed-at">-</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted mb-1"><i class="fas fa-info-circle"></i> Estado Final</label>
                                    <p class="mb-2" id="view-final-status">-</p>
                                </div>
                            </div>
                        </div>
                        <div id="rejection-reason-container" style="display:none;">
                            <div class="callout callout-danger">
                                <h5><i class="fas fa-ban"></i> Motivo de Rechazo</h5>
                                <p id="view-rejection-reason" class="mb-0">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <div id="view-actions-pending" style="display:none;">
                    <button type="button" class="btn btn-success" id="btnViewApprove">
                        <i class="fas fa-check"></i> Aprobar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnViewReject">
                        <i class="fas fa-ban"></i> Rechazar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    console.log('[ViewRequestModal] Script loaded');

    function initViewRequestModal() {
        console.log('[ViewRequestModal] Initializing...');

        // Button handlers
        $('#btnViewApprove').off('click').on('click', function() {
            const requestId = $('#viewRequestModal').data('request-id');
            if (requestId) {
                $('#viewRequestModal').modal('hide');
                $(document).trigger('openApproveModal', [requestId]);
            }
        });

        $('#btnViewReject').off('click').on('click', function() {
            const requestId = $('#viewRequestModal').data('request-id');
            if (requestId) {
                $('#viewRequestModal').modal('hide');
                $(document).trigger('openRejectModal', [requestId]);
            }
        });

        console.log('[ViewRequestModal] ✓ Initialized');
    }

    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initViewRequestModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initViewRequestModal);
            }
        }, 100);
    }
})();

// Global ViewRequestModal object
window.ViewRequestModal = {
    open: function(request) {
        if (!request) {
            console.error('[ViewRequestModal] No request data provided');
            return;
        }

        console.log('[ViewRequestModal] Opening for request:', request.id);

        // Store request ID
        $('#viewRequestModal').data('request-id', request.id);

        // Helper function for safe value display
        const safeValue = (val) => val || 'N/A';
        const formatDate = (dateStr) => {
            if (!dateStr) return 'N/A';
            return new Date(dateStr).toLocaleDateString('es-ES', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit'
            });
        };

        // Status badge helper
        const getStatusBadge = (status) => {
            const s = (status || '').toLowerCase();
            const badges = {
                'pending': '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>',
                'approved': '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Aprobada</span>',
                'rejected': '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rechazada</span>'
            };
            return badges[s] || '<span class="badge badge-secondary">Desconocido</span>';
        };

        // Populate header
        $('#view-company-name').text(safeValue(request.companyName));
        $('#view-request-code').text(safeValue(request.requestCode));
        $('#view-status-badge').html(getStatusBadge(request.status));
        $('#view-created-at').text(formatDate(request.createdAt));

        // Populate Info tab
        $('#view-company-name-full').text(safeValue(request.companyName));
        $('#view-legal-name').text(safeValue(request.legalName));
        $('#view-industry').text(request.industry?.name || 'N/A');
        $('#view-tax-id').text(safeValue(request.taxId));
        $('#view-admin-email').text(safeValue(request.adminEmail));
        $('#view-estimated-users').text(safeValue(request.estimatedUsers));
        $('#view-phone').text(safeValue(request.phone));

        // Website with link
        const website = request.website;
        if (website) {
            $('#view-website').html(`<a href="${website}" target="_blank">${website} <i class="fas fa-external-link-alt"></i></a>`);
        } else {
            $('#view-website').text('N/A');
        }

        $('#view-description').val(safeValue(request.businessDescription));

        // Populate Contact tab
        $('#view-address').text(safeValue(request.contactAddress));
        $('#view-city').text(safeValue(request.contactCity));
        $('#view-country').text(safeValue(request.contactCountry));
        $('#view-postal-code').text(safeValue(request.contactPostalCode));

        // Handle review info and actions based on status
        const statusLower = (request.status || '').toLowerCase();
        const isPending = statusLower === 'pending';

        // Show/hide review tab
        if (isPending) {
            $('#tab-review-li').hide();
        } else {
            $('#tab-review-li').show();
            // Populate review info
            $('#view-reviewed-by').text(request.reviewer?.name || request.reviewer?.email || 'N/A');
            $('#view-reviewed-at').text(formatDate(request.reviewedAt));
            $('#view-final-status').html(getStatusBadge(request.status));

            // Show rejection reason if rejected
            if (statusLower === 'rejected' && request.rejectionReason) {
                $('#rejection-reason-container').show();
                $('#view-rejection-reason').text(request.rejectionReason);
            } else {
                $('#rejection-reason-container').hide();
            }
        }

        // Show/hide action buttons
        if (isPending) {
            $('#view-actions-pending').show();
        } else {
            $('#view-actions-pending').hide();
        }

        // Reset to first tab
        $('#tab-info').tab('show');

        // Show modal
        $('#viewRequestModal').modal('show');
    }
};
</script>
