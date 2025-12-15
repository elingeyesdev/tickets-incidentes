{{--
    Modal de Gestión de Roles de Usuario
    Partial reutilizable para asignar/quitar roles

    Usage: @include('app.platform-admin.users.partials.roles-modal')

    Requires:
    - SweetAlert2
    - AdminLTE Toasts
    - tokenManager
--}}

{{-- Modal: Gestionar Roles --}}
<div class="modal fade" id="rolesModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-indigo">
                <h5 class="modal-title">
                    <i class="fas fa-shield-alt"></i> Gestionar Roles
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- User Info Header --}}
                <div class="callout callout-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle fa-2x mr-3"></i>
                        <div>
                            <strong id="rolesUserName">-</strong>
                            <br>
                            <small class="text-muted" id="rolesUserEmail">-</small>
                        </div>
                    </div>
                </div>

                {{-- Loading Spinner --}}
                <div id="rolesLoadingSpinner" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando roles...</span>
                    </div>
                    <p class="text-muted mt-2">Cargando información de roles...</p>
                </div>

                {{-- Roles Content --}}
                <div id="rolesContent" style="display: none;">
                    {{-- Current Roles Section --}}
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-shield"></i> Roles Actuales
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="currentRolesList" class="p-3">
                                <p class="text-muted text-center mb-0">
                                    <i class="fas fa-info-circle"></i> Este usuario no tiene roles asignados
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Assign New Role Section --}}
                    <div class="card card-outline card-success mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i> Asignar Nuevo Rol
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="assignRoleForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="newRoleCode">Rol <span class="text-danger">*</span></label>
                                            <select id="newRoleCode" name="roleCode" class="form-control" required>
                                                <option value="">Seleccionar rol...</option>
                                            </select>
                                            <small class="form-text text-muted">Selecciona el rol a asignar</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="companySelectGroup" style="display: none;">
                                            <label for="roleCompanyId">Empresa <span class="text-danger">*</span></label>
                                            <select id="roleCompanyId" name="companyId" class="form-control">
                                                <option value="">Seleccionar empresa...</option>
                                            </select>
                                            <small class="form-text text-muted">Requerido para roles AGENT y COMPANY_ADMIN</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Alert de error --}}
                                <div id="assignRoleErrorAlert" class="alert alert-danger" role="alert" style="display: none;">
                                    <button type="button" class="close" aria-label="Close" onclick="$('#assignRoleErrorAlert').hide()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <strong><i class="icon fas fa-ban"></i> Error:</strong> 
                                    <span id="assignRoleErrorMessage"></span>
                                </div>

                                <div class="text-right">
                                    <button type="submit" id="btnAssignRole" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Asignar Rol
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Error Message --}}
                <div id="rolesErrorMessage" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="rolesErrorText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar Quitar Rol --}}
<div class="modal fade" id="removeRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-minus"></i> Quitar Rol
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="callout callout-warning">
                    <strong>¿Estás seguro de quitar este rol?</strong>
                </div>

                <p>
                    <strong>Rol:</strong> <span id="removeRoleName" class="badge badge-info">-</span><br>
                    <strong>Usuario:</strong> <span id="removeRoleUserEmail">-</span>
                </p>

                <div class="form-group">
                    <label for="removeRoleReason">Razón (opcional):</label>
                    <textarea id="removeRoleReason" class="form-control" rows="2"
                              placeholder="Motivo de la remoción del rol..." maxlength="500"></textarea>
                    <small class="form-text text-muted">Máximo 500 caracteres</small>
                </div>

                <input type="hidden" id="removeRoleId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btnConfirmRemoveRole" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Quitar Rol
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * RolesManager - Módulo para gestión de roles de usuario
 * Patrón: IIFE + Singleton
 */
(function() {
    'use strict';

    // Ensure we only initialize once
    if (window.RolesManager) {
        console.log('[RolesManager] Already initialized, skipping...');
        return;
    }

    console.log('[RolesManager] Initializing...');

    // ========== CONFIGURATION ==========
    const CONFIG = {
        API_BASE: '/api',
        TOAST_DELAY: 3000,
        ROLES_REQUIRING_COMPANY: ['AGENT', 'COMPANY_ADMIN']
    };

    // ========== STATE ==========
    const state = {
        currentUserId: null,
        currentUserEmail: null,
        currentUserName: null,
        currentUserRoles: [],
        availableRoles: [],
        companies: [],
        isLoading: false,
        isOperating: false
    };

    // ========== UTILITIES ==========
    const Utils = {
        getToken() {
            if (typeof window.tokenManager !== 'undefined' && window.tokenManager) {
                const token = window.tokenManager.getAccessToken();
                if (token) return token;
            }
            return localStorage.getItem('access_token');
        },

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        translateError(error) {
            const status = error.status;
            const data = error.data;

            switch (status) {
                case 400: return data?.message || 'Solicitud inválida.';
                case 401: return 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
                case 403: return data?.message || 'No tienes permiso para realizar esta acción.';
                case 404: return data?.message || 'Recurso no encontrado.';
                case 409: return data?.message || 'El usuario ya tiene este rol asignado.';
                case 422:
                    if (data?.errors) {
                        return Object.values(data.errors).flat().join('. ');
                    }
                    return data?.message || 'Error de validación.';
                default:
                    return data?.message || 'Error al procesar la solicitud.';
            }
        },

        getRoleBadgeClass(roleCode) {
            const classes = {
                'PLATFORM_ADMIN': 'badge-danger',
                'COMPANY_ADMIN': 'badge-warning',
                'AGENT': 'badge-info',
                'USER': 'badge-secondary'
            };
            return classes[roleCode] || 'badge-secondary';
        }
    };

    // ========== TOAST (AdminLTE Official) ==========
    const Toast = {
        success(message, title = 'Éxito') {
            $(document).Toasts('create', {
                class: 'bg-success',
                title: title,
                body: message,
                autohide: true,
                delay: CONFIG.TOAST_DELAY,
                icon: 'fas fa-check-circle'
            });
        },

        error(message, title = 'Error') {
            $(document).Toasts('create', {
                class: 'bg-danger',
                title: title,
                body: message,
                autohide: true,
                delay: CONFIG.TOAST_DELAY + 2000,
                icon: 'fas fa-exclamation-circle'
            });
        }
    };

    // ========== API ==========
    const API = {
        async loadUserDetails(userId) {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/users/${userId}`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { status: response.status, data: data };
                }

                return { success: true, data: data.data };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async loadAvailableRoles() {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/roles`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { status: response.status, data: data };
                }

                return { success: true, data: data.data || [] };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async loadCompanies() {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/companies/minimal?per_page=100`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { status: response.status, data: data };
                }

                return { success: true, data: data.data || [] };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async assignRole(userId, roleCode, companyId) {
            try {
                const payload = { roleCode };
                if (companyId) {
                    payload.companyId = companyId;
                }

                const response = await fetch(`${CONFIG.API_BASE}/users/${userId}/roles`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { status: response.status, data: data };
                }

                return { success: true, data: data.data, message: data.message };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async removeRole(roleId, reason) {
            try {
                let url = `${CONFIG.API_BASE}/users/roles/${roleId}`;
                if (reason) {
                    url += `?reason=${encodeURIComponent(reason)}`;
                }

                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { status: response.status, data: data };
                }

                return { success: true, message: data.message };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        }
    };

    // ========== UI ==========
    const UI = {
        showLoading() {
            $('#rolesLoadingSpinner').show();
            $('#rolesContent').hide();
            $('#rolesErrorMessage').hide();
        },

        hideLoading() {
            $('#rolesLoadingSpinner').hide();
            $('#rolesContent').show();
        },

        showError(message) {
            $('#rolesLoadingSpinner').hide();
            $('#rolesContent').hide();
            $('#rolesErrorText').text(message);
            $('#rolesErrorMessage').show();
        },

        renderCurrentRoles(roles) {
            const container = $('#currentRolesList');

            if (!roles || roles.length === 0) {
                container.html(`
                    <p class="text-muted text-center mb-0">
                        <i class="fas fa-info-circle"></i> Este usuario no tiene roles asignados
                    </p>
                `);
                return;
            }

            const html = roles.map(role => {
                const badgeClass = Utils.getRoleBadgeClass(role.roleCode);
                const companyInfo = role.company 
                    ? `<br><small><i class="fas fa-building"></i> ${Utils.escapeHtml(role.company.name)}</small>`
                    : '';
                const assignedBy = role.assignedBy
                    ? `por ${Utils.escapeHtml(role.assignedBy.email || 'Sistema')}`
                    : 'por Sistema';

                return `
                    <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                        <div>
                            <span class="badge ${badgeClass}">${Utils.escapeHtml(role.roleName || role.roleCode)}</span>
                            <code class="ml-2 small">${Utils.escapeHtml(role.roleCode)}</code>
                            ${companyInfo}
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt"></i> ${Utils.formatDate(role.assignedAt)} ${assignedBy}
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-role" 
                                data-role-id="${role.id}" 
                                data-role-name="${Utils.escapeHtml(role.roleName || role.roleCode)}"
                                title="Quitar este rol">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            }).join('');

            container.html(html);

            // Attach remove role events
            container.find('.btn-remove-role').off('click').on('click', function() {
                const roleId = $(this).data('role-id');
                const roleName = $(this).data('role-name');
                RolesManager.openRemoveRoleModal(roleId, roleName);
            });
        },

        populateRoleSelect(roles) {
            const select = $('#newRoleCode');
            select.html('<option value="">Seleccionar rol...</option>');

            roles.forEach(role => {
                const option = `<option value="${role.code}" 
                                        data-requires-company="${role.requiresCompany}"
                                        data-description="${Utils.escapeHtml(role.description || '')}">
                                    ${Utils.escapeHtml(role.name)}
                                </option>`;
                select.append(option);
            });
        },

        populateCompanySelect(companies) {
            const select = $('#roleCompanyId');
            select.html('<option value="">Seleccionar empresa...</option>');

            companies.forEach(company => {
                const option = `<option value="${company.id}">
                                    ${Utils.escapeHtml(company.name)} (${Utils.escapeHtml(company.companyCode)})
                                </option>`;
                select.append(option);
            });
        },

        toggleCompanySelect(roleCode) {
            const requiresCompany = CONFIG.ROLES_REQUIRING_COMPANY.includes(roleCode);
            if (requiresCompany) {
                $('#companySelectGroup').show();
                $('#roleCompanyId').prop('required', true);
            } else {
                $('#companySelectGroup').hide();
                $('#roleCompanyId').prop('required', false).val('');
            }
        },

        resetAssignForm() {
            $('#assignRoleForm')[0].reset();
            $('#companySelectGroup').hide();
            $('#assignRoleErrorAlert').hide();
            $('#btnAssignRole').prop('disabled', false).html('<i class="fas fa-plus"></i> Asignar Rol');
        }
    };

    // ========== ROLES MANAGER (Public API) ==========
    window.RolesManager = {
        async open(userId, userName, userEmail) {
            console.log('[RolesManager] Opening for user:', userId);

            state.currentUserId = userId;
            state.currentUserName = userName;
            state.currentUserEmail = userEmail;

            // Update modal header
            $('#rolesUserName').text(userName || 'Usuario');
            $('#rolesUserEmail').text(userEmail || '-');

            // Reset form
            UI.resetAssignForm();

            // Show modal
            $('#rolesModal').modal('show');

            // Load data
            UI.showLoading();

            try {
                // Load user details, roles, and companies in parallel
                const [userResult, rolesResult, companiesResult] = await Promise.all([
                    API.loadUserDetails(userId),
                    API.loadAvailableRoles(),
                    API.loadCompanies()
                ]);

                if (!userResult.success) {
                    UI.showError(userResult.message);
                    return;
                }

                // Store data
                state.currentUserRoles = userResult.data.roleContexts || [];
                state.availableRoles = rolesResult.success ? rolesResult.data : [];
                state.companies = companiesResult.success ? companiesResult.data : [];

                // Render UI
                UI.renderCurrentRoles(state.currentUserRoles);
                UI.populateRoleSelect(state.availableRoles);
                UI.populateCompanySelect(state.companies);

                UI.hideLoading();

            } catch (error) {
                console.error('[RolesManager] Error loading data:', error);
                UI.showError('Error al cargar la información de roles');
            }
        },

        openRemoveRoleModal(roleId, roleName) {
            $('#removeRoleId').val(roleId);
            $('#removeRoleName').text(roleName);
            $('#removeRoleUserEmail').text(state.currentUserEmail);
            $('#removeRoleReason').val('');
            $('#btnConfirmRemoveRole').prop('disabled', false).html('<i class="fas fa-trash"></i> Quitar Rol');

            $('#removeRoleModal').modal('show');
        },

        async handleAssignRole(e) {
            e.preventDefault();

            const roleCode = $('#newRoleCode').val();
            const companyId = $('#roleCompanyId').val();

            if (!roleCode) {
                Toast.error('Debes seleccionar un rol');
                return;
            }

            if (CONFIG.ROLES_REQUIRING_COMPANY.includes(roleCode) && !companyId) {
                Toast.error('Debes seleccionar una empresa para este rol');
                return;
            }

            if (state.isOperating) return;
            state.isOperating = true;

            const $btn = $('#btnAssignRole');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Asignando...');
            $('#assignRoleErrorAlert').hide();

            const result = await API.assignRole(state.currentUserId, roleCode, companyId || null);

            if (result.success) {
                Toast.success(result.message || 'Rol asignado correctamente');
                
                // Reload user data
                const userResult = await API.loadUserDetails(state.currentUserId);
                if (userResult.success) {
                    state.currentUserRoles = userResult.data.roleContexts || [];
                    UI.renderCurrentRoles(state.currentUserRoles);
                }

                UI.resetAssignForm();

                // Trigger refresh callback if exists
                if (typeof window.onRolesUpdated === 'function') {
                    window.onRolesUpdated();
                }
            } else {
                $('#assignRoleErrorMessage').text(result.message);
                $('#assignRoleErrorAlert').show();
            }

            $btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Asignar Rol');
            state.isOperating = false;
        },

        async handleRemoveRole() {
            const roleId = $('#removeRoleId').val();
            const reason = $('#removeRoleReason').val().trim();

            if (state.isOperating) return;
            state.isOperating = true;

            const $btn = $('#btnConfirmRemoveRole');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Quitando...');

            const result = await API.removeRole(roleId, reason || null);

            if (result.success) {
                Toast.success(result.message || 'Rol quitado correctamente');

                // Close remove modal
                $('#removeRoleModal').modal('hide');

                // Reload user data
                const userResult = await API.loadUserDetails(state.currentUserId);
                if (userResult.success) {
                    state.currentUserRoles = userResult.data.roleContexts || [];
                    UI.renderCurrentRoles(state.currentUserRoles);
                }

                // Trigger refresh callback if exists
                if (typeof window.onRolesUpdated === 'function') {
                    window.onRolesUpdated();
                }
            } else {
                Toast.error(result.message);
            }

            $btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Quitar Rol');
            state.isOperating = false;
        }
    };

    // ========== EVENT LISTENERS ==========
    function initEvents() {
        // Role select change - toggle company field
        $('#newRoleCode').on('change', function() {
            UI.toggleCompanySelect($(this).val());
        });

        // Assign role form submit
        $('#assignRoleForm').on('submit', function(e) {
            RolesManager.handleAssignRole(e);
        });

        // Confirm remove role
        $('#btnConfirmRemoveRole').on('click', function() {
            RolesManager.handleRemoveRole();
        });

        // Reset state when modals close
        $('#rolesModal').on('hidden.bs.modal', function() {
            state.isOperating = false;
            UI.resetAssignForm();
        });

        $('#removeRoleModal').on('hidden.bs.modal', function() {
            state.isOperating = false;
        });
    }

    // Initialize when jQuery is ready
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initEvents);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jQuery !== 'undefined') {
                initEvents();
            }
        });
    }

    console.log('[RolesManager] ✓ Initialized successfully');
})();
</script>
