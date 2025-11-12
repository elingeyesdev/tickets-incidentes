@extends('layouts.authenticated')

@section('title', 'Gestión de Usuarios - Dashboard')

@section('content_header', 'Gestión de Usuarios')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Admin</a></li>
    <li class="breadcrumb-item active">Usuarios</li>
@endsection

@section('content')
<!-- Row 1: Filters and Actions -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <!-- Filter by Status -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-status" class="mb-1">Estado:</label>
                        <select id="filter-status" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="active">Activos</option>
                            <option value="suspended">Suspendidos</option>
                            <option value="deleted">Eliminados</option>
                        </select>
                    </div>

                    <!-- Filter by Role -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-role" class="mb-1">Rol:</label>
                        <select id="filter-role" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="USER">Cliente</option>
                            <option value="AGENT">Agente</option>
                            <option value="COMPANY_ADMIN">Admin Empresa</option>
                            <option value="PLATFORM_ADMIN">Admin Plataforma</option>
                        </select>
                    </div>

                    <!-- Filter by Email Verified -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-email-verified" class="mb-1">Email Verificado:</label>
                        <select id="filter-email-verified" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="true">Verificados</option>
                            <option value="false">Sin Verificar</option>
                        </select>
                    </div>

                    <!-- Search Box -->
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-2">
                        <label for="search-users" class="mb-1">Buscar:</label>
                        <input type="text" id="search-users" class="form-control form-control-sm"
                               placeholder="Email, código o nombre...">
                    </div>

                    <!-- Refresh Button -->
                    <div class="col-lg-1 col-md-6 col-sm-12 mb-2">
                        <label class="mb-1">&nbsp;</label>
                        <button id="btn-refresh" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row mt-2">
                    <div class="col-12">
                        <small class="text-muted">
                            Total: <strong id="total-count">0</strong> |
                            Activos: <strong id="active-count">0</strong> |
                            Suspendidos: <strong id="suspended-count">0</strong> |
                            Eliminados: <strong id="deleted-count">0</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Users Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Usuarios del Sistema
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="users-table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Código</th>
                                <th style="width: 20%;">Email</th>
                                <th style="width: 15%;">Nombre</th>
                                <th style="width: 15%;">Rol</th>
                                <th style="width: 10%;">Estado</th>
                                <th style="width: 10%;">Email Verificado</th>
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
                    <i class="fas fa-user-circle"></i> Detalles del Usuario
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Column: Basic Info -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Información General</h6>

                        <div class="form-group">
                            <label class="text-muted mb-1">Código de Usuario:</label>
                            <p class="mb-2"><strong id="detail-user-code">-</strong></p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Email:</label>
                            <p class="mb-2" id="detail-email">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Nombre Completo:</label>
                            <p class="mb-2" id="detail-full-name">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Teléfono:</label>
                            <p class="mb-2" id="detail-phone">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Estado:</label>
                            <p class="mb-2" id="detail-status-badge">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Email Verificado:</label>
                            <p class="mb-2" id="detail-email-verified">-</p>
                        </div>
                    </div>

                    <!-- Right Column: Additional Info -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Preferencias y Estadísticas</h6>

                        <div class="form-group">
                            <label class="text-muted mb-1">Tema:</label>
                            <p class="mb-2" id="detail-theme">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Idioma:</label>
                            <p class="mb-2" id="detail-language">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Zona Horaria:</label>
                            <p class="mb-2" id="detail-timezone">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Proveedor de Autenticación:</label>
                            <p class="mb-2" id="detail-auth-provider">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Tickets Creados:</label>
                            <p class="mb-2"><strong id="detail-tickets-count">0</strong></p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Tickets Resueltos:</label>
                            <p class="mb-2"><strong id="detail-resolved-tickets">0</strong></p>
                        </div>
                    </div>
                </div>

                <!-- Roles Section -->
                <div class="row mt-3">
                    <div class="col-12">
                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-shield-alt"></i> Roles Asignados
                        </h6>
                    </div>
                    <div class="col-12" id="detail-roles-container">
                        <!-- Roles will be populated here -->
                    </div>
                </div>

                <!-- Activity Section -->
                <div class="row mt-3">
                    <div class="col-12">
                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Actividad</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted mb-1">Último Login:</label>
                        <p class="mb-2" id="detail-last-login">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted mb-1">Última Actividad:</label>
                        <p class="mb-2" id="detail-last-activity">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted mb-1">Rating Promedio:</label>
                        <p class="mb-2" id="detail-average-rating">-</p>
                    </div>
                </div>

                <!-- Dates -->
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="text-muted mb-1">Creado:</label>
                        <p class="mb-2" id="detail-created-at">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted mb-1">Última Actualización:</label>
                        <p class="mb-2" id="detail-updated-at">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btn-modal-change-status" class="btn btn-warning">
                    <i class="fas fa-ban"></i> Cambiar Estado
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Change Status -->
<div class="modal fade" id="modal-change-status" tabindex="-1" role="dialog" aria-labelledby="modalChangeStatusLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white" id="modalChangeStatusLabel">
                    <i class="fas fa-exclamation-triangle"></i> Cambiar Estado de Usuario
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Usuario: <strong id="status-change-user-name">-</strong>
                </div>

                <div class="form-group">
                    <label for="status-change-new-status">Nuevo Estado:</label>
                    <select id="status-change-new-status" class="form-control" required>
                        <option value="">Seleccionar estado...</option>
                        <option value="active">Activo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status-change-reason">Razón (Requerida si suspende):</label>
                    <textarea id="status-change-reason" class="form-control" rows="3"
                              placeholder="Mínimo 10 caracteres"></textarea>
                    <small class="form-text text-muted">Mínimo 10, máximo 500 caracteres</small>
                </div>

                <input type="hidden" id="status-change-user-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-confirm-status-change" class="btn btn-warning">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Delete User -->
<div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-labelledby="modalDeleteLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="modalDeleteLabel">
                    <i class="fas fa-trash"></i> Eliminar Usuario
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¿Deseas eliminar al usuario <span id="delete-user-name">-</span>?</strong>
                </div>

                <p>Esta acción es <strong>irreversible</strong> (soft delete - se mantiene registro) y:</p>
                <ul>
                    <li>El usuario no podrá acceder al sistema</li>
                    <li>Se mantendrán todos sus registros en la base de datos</li>
                    <li>Sus tickets se conservarán para auditoría</li>
                </ul>

                <div class="form-group">
                    <label for="delete-reason">Razón de Eliminación:</label>
                    <textarea id="delete-reason" class="form-control" rows="3"
                              placeholder="(Opcional) Motivo de la eliminación"></textarea>
                </div>

                <input type="hidden" id="delete-user-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-confirm-delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Eliminar Permanentemente
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
        // CONFIGURATION & STATE
        // =====================================================================

        const token = window.tokenManager?.getAccessToken();
        const apiUrl = '/api';

        let allUsers = [];
        let currentUser = null;

        // =====================================================================
        // UTILITY: Format Status Badge
        // =====================================================================

        function getStatusBadge(status) {
            const statusLower = status ? status.toLowerCase() : '';
            const badges = {
                'active': '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Activo</span>',
                'suspended': '<span class="badge badge-warning"><i class="fas fa-pause-circle"></i> Suspendido</span>',
                'deleted': '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Eliminado</span>'
            };
            return badges[statusLower] || '<span class="badge badge-secondary">Desconocido</span>';
        }

        // =====================================================================
        // UTILITY: Format Date
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
        // UTILITY: Get Role Display Name
        // =====================================================================

        function getRoleDisplayName(roleCode) {
            const roleNames = {
                'USER': 'Cliente',
                'AGENT': 'Agente de Soporte',
                'COMPANY_ADMIN': 'Administrador de Empresa',
                'PLATFORM_ADMIN': 'Administrador de Plataforma'
            };
            return roleNames[roleCode] || roleCode;
        }

        // =====================================================================
        // FUNCTION 1: Load Users
        // =====================================================================

        function loadUsers(filters = {}) {
            if (!token) {
                showAlert('error', 'No se encontró token de autenticación');
                return;
            }

            const tbody = document.querySelector('#users-table tbody');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando usuarios...</td></tr>';

            // Build URL with filters
            let url = `${apiUrl}/users`;
            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.role) params.append('role', filters.role);
            if (filters.emailVerified !== undefined) params.append('emailVerified', filters.emailVerified);
            if (filters.search) params.append('search', filters.search);
            params.append('per_page', 50);

            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.data && Array.isArray(data.data)) {
                    allUsers = data.data;
                    renderUsersTable(allUsers);

                    // Update stats
                    document.getElementById('total-count').textContent = data.meta?.total || allUsers.length;
                    const activeCount = allUsers.filter(u => (u.status === 'ACTIVE' || u.status === 'active')).length;
                    const suspendedCount = allUsers.filter(u => (u.status === 'SUSPENDED' || u.status === 'suspended')).length;
                    const deletedCount = allUsers.filter(u => (u.status === 'DELETED' || u.status === 'deleted')).length;
                    document.getElementById('active-count').textContent = activeCount;
                    document.getElementById('suspended-count').textContent = suspendedCount;
                    document.getElementById('deleted-count').textContent = deletedCount;
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error al cargar usuarios</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading users:', error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error de conexión: ' + error.message + '</td></tr>';
            });
        }

        // =====================================================================
        // FUNCTION: Render Users Table
        // =====================================================================

        function renderUsersTable(users) {
            const tbody = document.querySelector('#users-table tbody');

            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted"><i class="fas fa-inbox"></i> No hay usuarios disponibles</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(user => {
                const statusBadge = getStatusBadge(user.status);
                const emailVerified = user.emailVerified
                    ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>'
                    : '<span class="badge badge-secondary"><i class="fas fa-times"></i></span>';

                // Get primary role
                const primaryRole = user.roleContexts && user.roleContexts.length > 0
                    ? getRoleDisplayName(user.roleContexts[0].roleCode)
                    : 'N/A';

                const displayName = user.profile
                    ? `${user.profile.firstName || ''} ${user.profile.lastName || ''}`.trim()
                    : 'N/A';

                return `
                    <tr data-id="${user.id}">
                        <td><code>${user.userCode || 'N/A'}</code></td>
                        <td>${user.email || 'N/A'}</td>
                        <td>${displayName}</td>
                        <td><span class="badge badge-info">${primaryRole}</span></td>
                        <td>${statusBadge}</td>
                        <td>${emailVerified}</td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-view-details" data-id="${user.id}" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-change-status" data-id="${user.id}" title="Cambiar Estado">
                                <i class="fas fa-ban"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${user.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            attachActionListeners();
        }

        // =====================================================================
        // FUNCTION 2: Open Details Modal
        // =====================================================================

        function openDetailsModal(userId) {
            currentUser = allUsers.find(u => u.id === userId);

            if (!currentUser) {
                showAlert('error', 'No se encontró el usuario');
                return;
            }

            // Basic info
            document.getElementById('detail-user-code').textContent = currentUser.userCode || 'N/A';
            document.getElementById('detail-email').textContent = currentUser.email || 'N/A';

            // Profile info
            const displayName = currentUser.profile
                ? `${currentUser.profile.firstName || ''} ${currentUser.profile.lastName || ''}`.trim()
                : 'N/A';
            document.getElementById('detail-full-name').textContent = displayName;
            document.getElementById('detail-phone').textContent = currentUser.profile?.phoneNumber || 'N/A';

            // Preferences
            document.getElementById('detail-theme').textContent = currentUser.profile?.theme || 'N/A';
            document.getElementById('detail-language').textContent = currentUser.profile?.language || 'N/A';
            document.getElementById('detail-timezone').textContent = currentUser.profile?.timezone || 'N/A';

            // Status and verification
            document.getElementById('detail-status-badge').innerHTML = getStatusBadge(currentUser.status);
            document.getElementById('detail-email-verified').innerHTML = currentUser.emailVerified
                ? '<span class="badge badge-success">Verificado</span>'
                : '<span class="badge badge-secondary">No Verificado</span>';

            document.getElementById('detail-auth-provider').textContent = currentUser.authProvider || 'Sistema Nativo';

            // Statistics
            document.getElementById('detail-tickets-count').textContent = currentUser.ticketsCount || 0;
            document.getElementById('detail-resolved-tickets').textContent = currentUser.resolvedTicketsCount || 0;

            const avgRating = currentUser.averageRating
                ? currentUser.averageRating.toFixed(2)
                : 'N/A';
            document.getElementById('detail-average-rating').textContent = avgRating;

            // Activity
            document.getElementById('detail-last-login').textContent = formatDate(currentUser.lastLoginAt);
            document.getElementById('detail-last-activity').textContent = formatDate(currentUser.lastActivityAt);

            // Dates
            document.getElementById('detail-created-at').textContent = formatDate(currentUser.createdAt);
            document.getElementById('detail-updated-at').textContent = formatDate(currentUser.updatedAt);

            // Render roles
            renderUserRoles(currentUser.roleContexts);

            $('#modal-details').modal('show');
        }

        // =====================================================================
        // FUNCTION: Render User Roles
        // =====================================================================

        function renderUserRoles(roleContexts) {
            const container = document.getElementById('detail-roles-container');

            if (!roleContexts || roleContexts.length === 0) {
                container.innerHTML = '<p class="text-muted">Sin roles asignados</p>';
                return;
            }

            container.innerHTML = roleContexts.map(role => {
                const companyInfo = role.company
                    ? `<br><small class="text-muted">Empresa: ${role.company.name}</small>`
                    : '';

                return `
                    <div class="mb-2 p-2 border rounded">
                        <strong>${role.roleName}</strong> (${role.roleCode})
                        ${companyInfo}
                    </div>
                `;
            }).join('');
        }

        // =====================================================================
        // FUNCTION 3: Open Change Status Modal
        // =====================================================================

        function openChangeStatusModal(userId) {
            currentUser = allUsers.find(u => u.id === userId);
            if (!currentUser) {
                showAlert('error', 'No se encontró el usuario');
                return;
            }

            document.getElementById('status-change-user-id').value = userId;
            document.getElementById('status-change-user-name').textContent = currentUser.email;
            document.getElementById('status-change-new-status').value = '';
            document.getElementById('status-change-reason').value = '';

            $('#modal-details').modal('hide');
            $('#modal-change-status').modal('show');
        }

        // =====================================================================
        // FUNCTION 4: Change User Status
        // =====================================================================

        function changeUserStatus() {
            const userId = document.getElementById('status-change-user-id').value;
            const newStatus = document.getElementById('status-change-new-status').value;
            const reason = document.getElementById('status-change-reason').value.trim();

            if (!newStatus) {
                showAlert('error', 'Debes seleccionar un estado');
                return;
            }

            if (newStatus === 'suspended' && reason.length < 10) {
                showAlert('error', 'La razón debe tener al menos 10 caracteres');
                return;
            }

            const btnConfirm = document.getElementById('btn-confirm-status-change');
            btnConfirm.disabled = true;
            btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            const payload = {
                status: newStatus,
                reason: reason || null
            };

            fetch(`${apiUrl}/users/${userId}/status`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.data || data.success) {
                    showAlert('success', 'Estado del usuario actualizado exitosamente');
                    $('#modal-change-status').modal('hide');
                    loadUsers();
                } else {
                    showAlert('error', data.message || 'Error al cambiar estado');
                }
            })
            .catch(error => {
                console.error('Error changing status:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
            })
            .finally(() => {
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="fas fa-check"></i> Confirmar';
            });
        }

        // =====================================================================
        // FUNCTION 5: Open Delete Modal
        // =====================================================================

        function openDeleteModal(userId) {
            currentUser = allUsers.find(u => u.id === userId);
            if (!currentUser) {
                showAlert('error', 'No se encontró el usuario');
                return;
            }

            document.getElementById('delete-user-id').value = userId;
            document.getElementById('delete-user-name').textContent = currentUser.email;
            document.getElementById('delete-reason').value = '';

            $('#modal-details').modal('hide');
            $('#modal-delete').modal('show');
        }

        // =====================================================================
        // FUNCTION 6: Delete User
        // =====================================================================

        function deleteUser() {
            const userId = document.getElementById('delete-user-id').value;
            const reason = document.getElementById('delete-reason').value.trim();

            const btnDelete = document.getElementById('btn-confirm-delete');
            btnDelete.disabled = true;
            btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

            fetch(`${apiUrl}/users/${userId}?reason=${encodeURIComponent(reason || '')}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.data?.success) {
                    showAlert('success', 'Usuario eliminado exitosamente');
                    $('#modal-delete').modal('hide');
                    loadUsers();
                } else {
                    showAlert('error', data.message || 'Error al eliminar usuario');
                    btnDelete.disabled = false;
                    btnDelete.innerHTML = '<i class="fas fa-trash"></i> Eliminar Permanentemente';
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
                btnDelete.disabled = false;
                btnDelete.innerHTML = '<i class="fas fa-trash"></i> Eliminar Permanentemente';
            });
        }

        // =====================================================================
        // FUNCTION: Show Alert
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
        // ATTACH ACTION LISTENERS
        // =====================================================================

        function attachActionListeners() {
            document.querySelectorAll('.btn-view-details').forEach(btn => {
                btn.addEventListener('click', function() {
                    openDetailsModal(this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-change-status').forEach(btn => {
                btn.addEventListener('click', function() {
                    openChangeStatusModal(this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    openDeleteModal(this.dataset.id);
                });
            });
        }

        // =====================================================================
        // EVENT LISTENERS
        // =====================================================================

        document.getElementById('filter-status').addEventListener('change', function() {
            loadUsers({ status: this.value });
        });

        document.getElementById('filter-role').addEventListener('change', function() {
            loadUsers({ role: this.value });
        });

        document.getElementById('filter-email-verified').addEventListener('change', function() {
            const verified = this.value === 'true' ? true : (this.value === 'false' ? false : undefined);
            loadUsers({ emailVerified: verified });
        });

        document.getElementById('search-users').addEventListener('input', function() {
            loadUsers({ search: this.value });
        });

        document.getElementById('btn-refresh').addEventListener('click', function() {
            loadUsers();
        });

        document.getElementById('btn-modal-change-status').addEventListener('click', function() {
            if (currentUser) {
                openChangeStatusModal(currentUser.id);
            }
        });

        document.getElementById('btn-confirm-status-change').addEventListener('click', changeUserStatus);
        document.getElementById('btn-confirm-delete').addEventListener('click', deleteUser);

        // =====================================================================
        // INITIALIZE
        // =====================================================================

        loadUsers();
    });
</script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
