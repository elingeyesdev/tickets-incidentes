@extends('layouts.authenticated')

@section('title', 'Gestión de Solicitudes - Dashboard')

@section('content_header', 'Gestión de Solicitudes de Empresa')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Admin</a></li>
    <li class="breadcrumb-item active">Solicitudes</li>
@endsection

@section('content')
<!-- Row 1: Filters and Actions -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <!-- Filter by Status -->
                    <div class="col-lg-3 col-md-4 col-sm-12 mb-2">
                        <label for="filter-status" class="mb-1">Filtrar por Estado:</label>
                        <select id="filter-status" class="form-control form-control-sm">
                            <option value="">Todas las solicitudes</option>
                            <option value="pending">Pendientes</option>
                            <option value="approved">Aprobadas</option>
                            <option value="rejected">Rechazadas</option>
                        </select>
                    </div>

                    <!-- Search Box -->
                    <div class="col-lg-5 col-md-5 col-sm-12 mb-2">
                        <label for="search-requests" class="mb-1">Buscar:</label>
                        <input type="text" id="search-requests" class="form-control form-control-sm"
                               placeholder="Buscar por nombre empresa o email...">
                    </div>

                    <!-- Refresh Button -->
                    <div class="col-lg-2 col-md-3 col-sm-12 mb-2 d-flex align-items-end">
                        <button id="btn-refresh" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt"></i> Refrescar
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="col-lg-2 col-md-12 col-sm-12 mb-2 text-right">
                        <small class="text-muted">Total: <strong id="total-count">0</strong></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Requests Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice"></i> Solicitudes de Empresas
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="requests-table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Código</th>
                                <th style="width: 20%;">Empresa</th>
                                <th style="width: 18%;">Email Admin</th>
                                <th style="width: 12%;">Industria</th>
                                <th style="width: 10%;">Estado</th>
                                <th style="width: 13%;">Fecha Registro</th>
                                <th style="width: 15%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loaded dynamically via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Los datos se cargan dinámicamente desde la API
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: View Details -->
<div class="modal fade" id="modal-details" tabindex="-1" role="dialog" aria-labelledby="modalDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalDetailsLabel">
                    <i class="fas fa-info-circle"></i> Detalles de Solicitud
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Column: Company Data -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Información de la Empresa</h6>

                        <div class="form-group">
                            <label class="text-muted mb-1">Código de Solicitud:</label>
                            <p class="mb-2"><strong id="detail-request-code">-</strong></p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Nombre de Empresa:</label>
                            <p class="mb-2"><strong id="detail-company-name">-</strong></p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Razón Social:</label>
                            <p class="mb-2" id="detail-legal-name">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Email Administrador:</label>
                            <p class="mb-2" id="detail-admin-email">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Teléfono:</label>
                            <p class="mb-2" id="detail-phone">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Sitio Web:</label>
                            <p class="mb-2" id="detail-website">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Industria:</label>
                            <p class="mb-2" id="detail-industry">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Usuarios Estimados:</label>
                            <p class="mb-2" id="detail-users">-</p>
                        </div>
                    </div>

                    <!-- Right Column: Contact & Additional Data -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Información de Contacto</h6>

                        <div class="form-group">
                            <label class="text-muted mb-1">Dirección:</label>
                            <p class="mb-2" id="detail-address">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Ciudad:</label>
                            <p class="mb-2" id="detail-city">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">País:</label>
                            <p class="mb-2" id="detail-country">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Código Postal:</label>
                            <p class="mb-2" id="detail-postal">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Tax ID (RUT/NIT):</label>
                            <p class="mb-2" id="detail-tax-id">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Descripción del Negocio:</label>
                            <textarea class="form-control" id="detail-description" rows="4" readonly></textarea>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Fecha de Registro:</label>
                            <p class="mb-2" id="detail-created-at">-</p>
                        </div>
                    </div>
                </div>

                <!-- Review Information (if status != pending) -->
                <div id="review-info" class="row mt-3" style="display: none;">
                    <div class="col-12">
                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Información de Revisión</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted mb-1">Revisado por:</label>
                        <p class="mb-2" id="detail-reviewed-by">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted mb-1">Fecha de Revisión:</label>
                        <p class="mb-2" id="detail-reviewed-at">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted mb-1">Estado:</label>
                        <p class="mb-2" id="detail-status-badge">-</p>
                    </div>
                    <div class="col-12" id="rejection-reason-container" style="display: none;">
                        <label class="text-muted mb-1">Motivo de Rechazo:</label>
                        <textarea class="form-control" id="detail-rejection-reason" rows="3" readonly></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btn-modal-approve" class="btn btn-success" style="display: none;">
                    <i class="fas fa-check"></i> Aprobar
                </button>
                <button type="button" id="btn-modal-reject" class="btn btn-danger" style="display: none;">
                    <i class="fas fa-ban"></i> Rechazar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Approve Request -->
<div class="modal fade" id="modal-approve" tabindex="-1" role="dialog" aria-labelledby="modalApproveLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white" id="modalApproveLabel">
                    <i class="fas fa-check-circle"></i> Aprobar Solicitud
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>¿Deseas aprobar la solicitud <span id="approve-request-code">-</span>?</strong>
                </div>

                <p>Se creará la empresa <strong id="approve-company-name">-</strong> y se enviarán credenciales de acceso a <strong id="approve-admin-email">-</strong>.</p>

                <div class="form-group">
                    <div class="icheck-primary">
                        <input type="checkbox" id="send-email-checkbox" checked>
                        <label for="send-email-checkbox">
                            Enviar email de bienvenida con credenciales
                        </label>
                    </div>
                </div>

                <input type="hidden" id="approve-request-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-confirm-approve" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirmar Aprobación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Reject Request -->
<div class="modal fade" id="modal-reject" tabindex="-1" role="dialog" aria-labelledby="modalRejectLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="modalRejectLabel">
                    <i class="fas fa-ban"></i> Rechazar Solicitud
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¿Deseas rechazar la solicitud <span id="reject-request-code">-</span>?</strong>
                </div>

                <p>Esta acción notificará al solicitante <strong id="reject-admin-email">-</strong> sobre el rechazo.</p>

                <div class="form-group">
                    <label for="rejection-reason">Motivo del rechazo: <span class="text-danger">*</span></label>
                    <textarea
                        id="rejection-reason"
                        class="form-control"
                        rows="4"
                        placeholder="Explica la razón del rechazo (mínimo 10 caracteres)..."
                        required
                        minlength="10"></textarea>
                    <small class="form-text text-muted">
                        <span id="char-count">0</span> / 10 caracteres mínimos
                    </small>
                </div>

                <input type="hidden" id="reject-request-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-confirm-reject" class="btn btn-danger" disabled>
                    <i class="fas fa-ban"></i> Confirmar Rechazo
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // =====================================================================
        // CONFIGURATION
        // =====================================================================

        const token = window.tokenManager?.getAccessToken();
        const apiUrl = '/api';

        // Store current request data
        let allRequests = [];
        let currentRequest = null;

        // =====================================================================
        // FUNCTION 1: loadRequests(status = null)
        // Fetches all company requests from API with optional status filter
        // =====================================================================

        function loadRequests(status = null) {
            if (!token) {
                showAlert('error', 'No se encontró token de autenticación');
                return;
            }

            // Build URL with optional status filter
            let url = `${apiUrl}/company-requests`;
            if (status) {
                url += `?status=${status}`;
            }

            // Show loading state
            const tbody = document.querySelector('#requests-table tbody');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando solicitudes...</td></tr>';

            // Fetch requests from API
            fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.data && Array.isArray(data.data)) {
                    // Store requests for client-side filtering
                    allRequests = Array.isArray(data.data) ? data.data : [data.data];

                    // Render table
                    renderRequestsTable(allRequests);

                    // Update total count
                    document.getElementById('total-count').textContent = data.meta?.total || allRequests.length;
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error al cargar solicitudes</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading requests:', error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error de conexión: ' + error.message + '</td></tr>';
            });
        }

        // =====================================================================
        // FUNCTION: renderRequestsTable(requests)
        // Renders the requests table with data
        // =====================================================================

        function renderRequestsTable(requests) {
            const tbody = document.querySelector('#requests-table tbody');

            if (!requests || requests.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted"><i class="fas fa-inbox"></i> No hay solicitudes disponibles</td></tr>';
                return;
            }

            tbody.innerHTML = requests.map(req => {
                // Format status badge
                const statusBadge = getStatusBadge(req.status);

                // Format date
                const createdDate = formatDate(req.createdAt);

                // Show action buttons only for pending requests
                const isPending = req.status === 'PENDING' || req.status === 'pending';

                return `
                    <tr data-id="${req.id}">
                        <td><code>${req.requestCode || 'N/A'}</code></td>
                        <td>
                            <strong>${req.companyName || 'N/A'}</strong><br>
                            <small class="text-muted">${(req.industry && req.industry.name) || 'N/A'}</small>
                        </td>
                        <td>${req.adminEmail || 'N/A'}</td>
                        <td><span class="badge badge-secondary">${(req.industry && req.industry.name) || 'N/A'}</span></td>
                        <td>${statusBadge}</td>
                        <td><small>${createdDate}</small></td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-view-details" data-id="${req.id}" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${isPending ? `
                                <button class="btn btn-sm btn-success btn-approve" data-id="${req.id}" title="Aprobar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-reject" data-id="${req.id}" title="Rechazar">
                                    <i class="fas fa-ban"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            }).join('');

            // Attach event listeners to action buttons
            attachActionListeners();
        }

        // =====================================================================
        // FUNCTION: getStatusBadge(status)
        // Returns the HTML for status badge
        // =====================================================================

        function getStatusBadge(status) {
            const statusLower = status ? status.toLowerCase() : '';
            const badges = {
                'pending': '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>',
                'approved': '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Aprobada</span>',
                'rejected': '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rechazada</span>'
            };
            return badges[statusLower] || '<span class="badge badge-secondary">Desconocido</span>';
        }

        // =====================================================================
        // FUNCTION: formatDate(dateString)
        // Formats ISO date to readable format
        // =====================================================================

        function formatDate(dateString) {
            if (!dateString) return 'N/A';

            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('es-ES', options);
        }

        // =====================================================================
        // FUNCTION 2: openDetailsModal(requestId)
        // Opens the details modal and populates it with request data
        // =====================================================================

        function openDetailsModal(requestId) {
            // Find request in stored data
            currentRequest = allRequests.find(r => r.id === requestId);

            if (!currentRequest) {
                showAlert('error', 'No se encontró la solicitud');
                return;
            }

            // Populate modal fields - LEFT COLUMN
            document.getElementById('detail-request-code').textContent = currentRequest.requestCode || 'N/A';
            document.getElementById('detail-company-name').textContent = currentRequest.companyName || 'N/A';
            document.getElementById('detail-legal-name').textContent = currentRequest.legalName || 'N/A';
            document.getElementById('detail-admin-email').textContent = currentRequest.adminEmail || 'N/A';
            document.getElementById('detail-phone').textContent = currentRequest.phone || 'N/A';

            // Website with link
            const website = currentRequest.website || 'N/A';
            document.getElementById('detail-website').innerHTML = website !== 'N/A'
                ? `<a href="${website}" target="_blank">${website} <i class="fas fa-external-link-alt"></i></a>`
                : website;

            document.getElementById('detail-industry').textContent = (currentRequest.industry && currentRequest.industry.name) || 'N/A';
            document.getElementById('detail-users').textContent = currentRequest.estimatedUsers || 'N/A';

            // Populate modal fields - RIGHT COLUMN
            document.getElementById('detail-address').textContent = currentRequest.contactAddress || 'N/A';
            document.getElementById('detail-city').textContent = currentRequest.contactCity || 'N/A';
            document.getElementById('detail-country').textContent = currentRequest.contactCountry || 'N/A';
            document.getElementById('detail-postal').textContent = currentRequest.contactPostalCode || 'N/A';
            document.getElementById('detail-tax-id').textContent = currentRequest.taxId || 'N/A';
            document.getElementById('detail-description').value = currentRequest.businessDescription || 'N/A';
            document.getElementById('detail-created-at').textContent = formatDate(currentRequest.createdAt);

            // Show/hide review information
            const reviewInfo = document.getElementById('review-info');
            const statusLower = currentRequest.status?.toLowerCase();
            if (statusLower !== 'pending') {
                reviewInfo.style.display = 'block';
                document.getElementById('detail-reviewed-by').textContent = currentRequest.reviewer?.name || currentRequest.reviewer?.email || 'N/A';
                document.getElementById('detail-reviewed-at').textContent = formatDate(currentRequest.reviewedAt);
                document.getElementById('detail-status-badge').innerHTML = getStatusBadge(currentRequest.status);

                // Show rejection reason if rejected
                const rejectionContainer = document.getElementById('rejection-reason-container');
                if (statusLower === 'rejected' && currentRequest.rejectionReason) {
                    rejectionContainer.style.display = 'block';
                    document.getElementById('detail-rejection-reason').value = currentRequest.rejectionReason;
                } else {
                    rejectionContainer.style.display = 'none';
                }
            } else {
                reviewInfo.style.display = 'none';
            }

            // Show action buttons only for pending requests
            const isPending = statusLower === 'pending';
            document.getElementById('btn-modal-approve').style.display = isPending ? 'inline-block' : 'none';
            document.getElementById('btn-modal-reject').style.display = isPending ? 'inline-block' : 'none';

            // Open modal
            $('#modal-details').modal('show');
        }

        // =====================================================================
        // FUNCTION 3: approveRequest(requestId)
        // Shows confirmation modal and handles request approval
        // =====================================================================

        function approveRequest(requestId) {
            // Find request
            const request = allRequests.find(r => r.id === requestId);

            if (!request) {
                showAlert('error', 'No se encontró la solicitud');
                return;
            }

            // Populate approval modal
            document.getElementById('approve-request-code').textContent = request.requestCode || 'N/A';
            document.getElementById('approve-company-name').textContent = request.companyName || 'N/A';
            document.getElementById('approve-admin-email').textContent = request.adminEmail || 'N/A';
            document.getElementById('approve-request-id').value = requestId;
            document.getElementById('send-email-checkbox').checked = true;

            // Close details modal if open
            $('#modal-details').modal('hide');

            // Open approval modal
            $('#modal-approve').modal('show');
        }

        // =====================================================================
        // FUNCTION: confirmApprove()
        // Sends POST request to approve the company request
        // =====================================================================

        function confirmApprove() {
            const requestId = document.getElementById('approve-request-id').value;
            const sendEmail = document.getElementById('send-email-checkbox').checked;

            if (!requestId) {
                showAlert('error', 'ID de solicitud no encontrado');
                return;
            }

            // Disable button to prevent double-click
            const btnConfirm = document.getElementById('btn-confirm-approve');
            btnConfirm.disabled = true;
            btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            // POST to API
            fetch(`${apiUrl}/company-requests/${requestId}/approve`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    send_email: sendEmail
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.data?.success) {
                    showAlert('success', data.data?.message || data.message || 'Solicitud aprobada exitosamente');
                    $('#modal-approve').modal('hide');
                    loadRequests(); // Reload table
                } else {
                    showAlert('error', data.message || 'Error al aprobar la solicitud');
                }
            })
            .catch(error => {
                console.error('Error approving request:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
            })
            .finally(() => {
                // Re-enable button
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="fas fa-check"></i> Confirmar Aprobación';
            });
        }

        // =====================================================================
        // FUNCTION 4: rejectRequest(requestId)
        // Shows rejection modal and handles request rejection
        // =====================================================================

        function rejectRequest(requestId) {
            // Find request
            const request = allRequests.find(r => r.id === requestId);

            if (!request) {
                showAlert('error', 'No se encontró la solicitud');
                return;
            }

            // Populate rejection modal
            document.getElementById('reject-request-code').textContent = request.requestCode || 'N/A';
            document.getElementById('reject-admin-email').textContent = request.adminEmail || 'N/A';
            document.getElementById('reject-request-id').value = requestId;
            document.getElementById('rejection-reason').value = '';
            document.getElementById('char-count').textContent = '0';
            document.getElementById('btn-confirm-reject').disabled = true;

            // Close details modal if open
            $('#modal-details').modal('hide');

            // Open rejection modal
            $('#modal-reject').modal('show');
        }

        // =====================================================================
        // FUNCTION: confirmReject()
        // Sends POST request to reject the company request
        // =====================================================================

        function confirmReject() {
            const requestId = document.getElementById('reject-request-id').value;
            const reason = document.getElementById('rejection-reason').value.trim();

            if (!requestId) {
                showAlert('error', 'ID de solicitud no encontrado');
                return;
            }

            if (reason.length < 10) {
                showAlert('error', 'El motivo debe tener al menos 10 caracteres');
                return;
            }

            // Disable button
            const btnConfirm = document.getElementById('btn-confirm-reject');
            btnConfirm.disabled = true;
            btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            // POST to API
            fetch(`${apiUrl}/company-requests/${requestId}/reject`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.data?.success) {
                    showAlert('success', data.data?.message || data.message || 'Solicitud rechazada exitosamente');
                    $('#modal-reject').modal('hide');
                    loadRequests(); // Reload table
                } else {
                    showAlert('error', data.message || 'Error al rechazar la solicitud');
                }
            })
            .catch(error => {
                console.error('Error rejecting request:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
            })
            .finally(() => {
                // Re-enable button
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="fas fa-ban"></i> Confirmar Rechazo';
            });
        }

        // =====================================================================
        // FUNCTION 5: filterByStatus(status)
        // Filters requests by status
        // =====================================================================

        function filterByStatus(status) {
            loadRequests(status);
        }

        // =====================================================================
        // FUNCTION 6: searchRequests(query)
        // Client-side search filter for company name and email
        // =====================================================================

        function searchRequests(query) {
            query = query.toLowerCase().trim();

            if (!query) {
                renderRequestsTable(allRequests);
                return;
            }

            const filtered = allRequests.filter(req => {
                const companyName = (req.companyName || '').toLowerCase();
                const email = (req.adminEmail || '').toLowerCase();
                const code = (req.requestCode || '').toLowerCase();

                return companyName.includes(query) ||
                       email.includes(query) ||
                       code.includes(query);
            });

            renderRequestsTable(filtered);
        }

        // =====================================================================
        // FUNCTION: showAlert(type, message)
        // Shows toast notification
        // =====================================================================

        function showAlert(type, message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: type,
                title: message
            });
        }

        // =====================================================================
        // ATTACH EVENT LISTENERS TO ACTION BUTTONS
        // =====================================================================

        function attachActionListeners() {
            // View details buttons
            document.querySelectorAll('.btn-view-details').forEach(btn => {
                btn.addEventListener('click', function() {
                    openDetailsModal(this.dataset.id);
                });
            });

            // Approve buttons
            document.querySelectorAll('.btn-approve').forEach(btn => {
                btn.addEventListener('click', function() {
                    approveRequest(this.dataset.id);
                });
            });

            // Reject buttons
            document.querySelectorAll('.btn-reject').forEach(btn => {
                btn.addEventListener('click', function() {
                    rejectRequest(this.dataset.id);
                });
            });
        }

        // =====================================================================
        // EVENT LISTENERS
        // =====================================================================

        // Filter by status
        document.getElementById('filter-status').addEventListener('change', function() {
            const status = this.value;
            filterByStatus(status || null);
        });

        // Search box
        document.getElementById('search-requests').addEventListener('input', function() {
            searchRequests(this.value);
        });

        // Refresh button
        document.getElementById('btn-refresh').addEventListener('click', function() {
            const currentFilter = document.getElementById('filter-status').value;
            loadRequests(currentFilter || null);
        });

        // Approve button in details modal
        document.getElementById('btn-modal-approve').addEventListener('click', function() {
            if (currentRequest) {
                approveRequest(currentRequest.id);
            }
        });

        // Reject button in details modal
        document.getElementById('btn-modal-reject').addEventListener('click', function() {
            if (currentRequest) {
                rejectRequest(currentRequest.id);
            }
        });

        // Confirm approve button
        document.getElementById('btn-confirm-approve').addEventListener('click', confirmApprove);

        // Confirm reject button
        document.getElementById('btn-confirm-reject').addEventListener('click', confirmReject);

        // Character counter for rejection reason
        document.getElementById('rejection-reason').addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('char-count').textContent = count;
            document.getElementById('btn-confirm-reject').disabled = count < 10;
        });

        // =====================================================================
        // INITIALIZE: Load all requests on page load
        // =====================================================================

        loadRequests();
    });
</script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
