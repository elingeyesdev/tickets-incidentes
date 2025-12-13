@extends('layouts.authenticated')

@section('title', 'Gestión de Agentes')

@section('content_header', 'Gestión de Equipo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Agentes</li>
@endsection

@section('content')

{{-- Fila: Estadísticas Rápidas --}}
<div class="row mb-4">
    <div class="col-md-4">
        <div class="info-box bg-light elevation-2">
            <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Agentes Activos</span>
                <span class="info-box-number" id="totalAgents">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-warning"><i class="fas fa-envelope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Invitaciones Pendientes</span>
                <span class="info-box-number" id="pendingInvitations">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Invitaciones Aceptadas</span>
                <span class="info-box-number" id="acceptedInvitations">0</span>
            </div>
        </div>
    </div>
</div>

{{-- Tarjeta Principal: Equipo de Agentes (Estilo AdminLTE Contacts) --}}
<div class="card card-solid">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-tie mr-2"></i>Equipo de Agentes</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" id="btnInviteAgent">
                <i class="fas fa-user-plus"></i> Invitar Agente
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="card-body pb-0">
        {{-- Spinner de Carga --}}
        <div id="agentsLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando equipo...</p>
        </div>

        {{-- Grid de Agentes (AdminLTE Contacts Style) --}}
        <div id="agentsContainer" class="row" style="display: none;">
            {{-- Agentes se cargarán aquí dinámicamente --}}
        </div>

        {{-- Estado Vacío --}}
        <div id="noAgents" class="text-center py-5" style="display: none;">
            <i class="fas fa-users fa-4x text-muted mb-3"></i>
            <h5>No tienes agentes en tu equipo</h5>
            <p class="text-muted">Invita usuarios para que se conviertan en agentes de soporte.</p>
            <button type="button" class="btn btn-primary mt-2" id="btnInviteAgentEmpty">
                <i class="fas fa-user-plus"></i> Invitar Primer Agente
            </button>
        </div>

        {{-- Mensaje de Error --}}
        <div id="agentsError" class="alert alert-danger m-3" style="display: none;">
            <i class="fas fa-exclamation-circle"></i>
            <span id="agentsErrorText"></span>
        </div>
    </div>
</div>

{{-- Tarjeta: Invitaciones --}}
<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-paper-plane mr-2"></i>Invitaciones Enviadas</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Spinner de Carga --}}
        <div id="invitationsLoading" class="text-center py-5">
            <div class="spinner-border text-warning" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando invitaciones...</p>
        </div>

        {{-- Tabla de Invitaciones --}}
        <div id="invitationsContainer" style="display: none;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Mensaje</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="invitationsTableBody">
                        {{-- Invitaciones se cargarán aquí --}}
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Estado Vacío --}}
        <div id="noInvitations" class="text-center py-5" style="display: none;">
            <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
            <p class="text-muted">No hay invitaciones enviadas.</p>
        </div>
    </div>
</div>

{{-- Modal: Invitar Agente --}}
<div class="modal fade" id="inviteAgentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Invitar Nuevo Agente
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="inviteAgentForm">
                <div class="modal-body">
                    {{-- Alert de error dentro del modal --}}
                    <div id="inviteErrorAlert" class="alert alert-danger" role="alert" style="display: none;">
                        <button type="button" class="close" aria-label="Close" onclick="$('#inviteErrorAlert').hide()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><i class="icon fas fa-ban"></i> Error:</strong> <span id="inviteErrorMessage"></span>
                    </div>

                    <div class="callout callout-info">
                        <h5><i class="icon fas fa-info-circle"></i> Cómo funciona</h5>
                        <p class="mb-0">
                            Busca un usuario por su email. El usuario recibirá una notificación y podrá
                            aceptar o rechazar la invitación para convertirse en agente de tu empresa.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="searchUserSelect">Buscar Usuario <span class="text-danger">*</span></label>
                        <select class="form-control" id="searchUserSelect" name="user_id" style="width: 100%;">
                            <option></option>
                        </select>
                        <small class="form-text text-muted">Busca por email o nombre. Solo usuarios elegibles aparecerán.</small>
                    </div>

                    {{-- Usuario Seleccionado (se muestra después de seleccionar) --}}
                    <div id="selectedUserContainer" style="display: none;">
                        <label class="mb-2">Usuario seleccionado:</label>
                        <div class="card bg-light mb-3">
                            <div class="card-body py-2">
                                <div class="d-flex align-items-center">
                                    <img id="selectedUserAvatar" src="/vendor/adminlte/dist/img/user2-160x160.jpg" 
                                         class="img-circle mr-3" alt="Avatar" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <strong id="selectedUserName">Nombre Usuario</strong><br>
                                        <small id="selectedUserEmail" class="text-muted">email@ejemplo.com</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearSelection">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="selectedUserId" name="selected_user_id">
                    </div>

                    <div class="form-group">
                        <label for="inviteMessage">Mensaje Personalizado (Opcional)</label>
                        <textarea class="form-control" id="inviteMessage" name="message" rows="3"
                                  placeholder="Ej: ¡Hola! Te invitamos a unirte a nuestro equipo de soporte..." maxlength="1000"></textarea>
                        <small class="form-text text-muted">Máximo 1000 caracteres.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" id="btnSendInvite" class="btn btn-primary" disabled>
                        <i class="fas fa-paper-plane"></i> Enviar Invitación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Confirmar Remover Agente --}}
<div class="modal fade" id="removeAgentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-minus"></i> Remover Agente
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>¿Estás seguro de remover este agente?</h5>
                    <p class="text-muted" id="removeAgentMessage">
                        El usuario <strong id="removeAgentName">-</strong> perderá acceso a las funciones de agente en tu empresa.
                    </p>
                </div>
                <div class="callout callout-warning">
                    <p class="mb-0">
                        <strong>Nota:</strong> Esta acción puede ser revertida invitando al usuario nuevamente.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btnConfirmRemove" class="btn btn-danger">
                    <i class="fas fa-user-minus"></i> Sí, Remover
                </button>
            </div>
            <input type="hidden" id="removeAgentRoleId">
        </div>
    </div>
</div>

{{-- Input Hidden: Company ID --}}
<input type="hidden" id="companyId" value="{{ $companyId }}">

{{-- Include Modal Partials (siguiendo arquitectura de platform-admin/companies) --}}
@include('app.company-admin.agents.partials.view-agent-modal')

@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    console.log('[Agents] Initializing Agent Management...');

    // ========== CONFIGURATION ==========
    const CONFIG = {
        API_BASE: '/api',
        TOAST_DELAY: 3000,
        DEBOUNCE_DELAY: 300,
        MIN_SEARCH_LENGTH: 3
    };

    // ========== STATE ==========
    const state = {
        companyId: null,
        agents: [],
        invitations: [],
        selectedUser: null,
        searchTimeout: null,
        isLoading: false
    };

    // ========== UTILITIES ==========
    const Utils = {
        getToken() {
            return localStorage.getItem('access_token');
        },

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        },

        formatRelativeTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Ahora';
            if (diffMins < 60) return `Hace ${diffMins} min`;
            if (diffHours < 24) return `Hace ${diffHours} horas`;
            if (diffDays < 7) return `Hace ${diffDays} días`;
            return this.formatDate(dateString);
        },

        translateError(error) {
            console.error('[Agents] API Error:', error);
            const status = error.response?.status;
            const data = error.response?.data;

            switch (status) {
                case 400:
                    return data?.message || 'Solicitud inválida.';
                case 401:
                    return 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
                case 403:
                    return data?.message || 'No tienes permiso para realizar esta acción.';
                case 404:
                    return 'Recurso no encontrado.';
                case 422:
                    if (data?.errors) {
                        const messages = Object.values(data.errors).flat().join('. ');
                        return messages;
                    }
                    return data?.message || 'Error de validación.';
                default:
                    return data?.message || 'Error al procesar la solicitud. Inténtalo nuevamente.';
            }
        },

        getAvatarUrl(profile) {
            if (profile?.avatarUrl) return profile.avatarUrl;
            const name = profile?.displayName || profile?.firstName || 'U';
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6c757d&color=fff&size=128`;
        },

        getDisplayName(user) {
            if (user.profile?.displayName) return user.profile.displayName;
            if (user.profile?.firstName && user.profile?.lastName) {
                return `${user.profile.firstName} ${user.profile.lastName}`;
            }
            if (user.profile?.firstName) return user.profile.firstName;
            return user.email?.split('@')[0] || 'Usuario';
        }
    };

    // ========== TOAST NOTIFICATIONS ==========
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
        /**
         * Load agents using /api/company/agents endpoint
         * This endpoint automatically infers companyId from JWT
         */
        async loadAgents() {
            try {
                // Use dedicated endpoint for agents (includes companyId from JWT)
                const response = await fetch(`${CONFIG.API_BASE}/company/agents`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw { response: { status: response.status, data: await response.json() } };
                }

                const data = await response.json();
                return { success: true, data: data.data, meta: data.meta };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async loadInvitations() {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/company/invitations`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw { response: { status: response.status, data: await response.json() } };
                }

                const data = await response.json();
                return { success: true, data: data.data };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        /**
         * Search users eligible for invitation using dedicated endpoint
         * Filters out users already agents or with pending invitations
         */
        async searchUsers(query) {
            try {
                // Use dedicated search endpoint that properly filters eligible users
                const response = await fetch(`${CONFIG.API_BASE}/company/invitations/search-users?search=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw { response: { status: response.status, data: await response.json() } };
                }

                const data = await response.json();
                return { success: true, data: data.data };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async createInvitation(userId, message) {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/company/invitations`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId, message: message || null })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { response: { status: response.status, data: data } };
                }

                return { success: true, data: data.data, message: data.message };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async cancelInvitation(invitationId) {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/company/invitations/${invitationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { response: { status: response.status, data: data } };
                }

                return { success: true };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        },

        async removeAgent(userRoleId) {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/company/agents/${userRoleId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { response: { status: response.status, data: data } };
                }

                return { success: true };
            } catch (error) {
                return { success: false, message: Utils.translateError(error) };
            }
        }
    };

    // ========== UI RENDERING ==========
    const UI = {
        updateStats(agentCount, pendingCount, acceptedCount) {
            $('#totalAgents').text(agentCount);
            $('#pendingInvitations').text(pendingCount);
            $('#acceptedInvitations').text(acceptedCount);
        },

        // ===== AGENTS (AdminLTE Contacts Style) =====
        showAgentsLoading() {
            $('#agentsLoading').show();
            $('#agentsContainer').hide();
            $('#noAgents').hide();
            $('#agentsError').hide();
        },

        /**
         * Render agents using AdminLTE v3 Contacts Card style
         * Reference: resources/views/vendor/adminlte/views/pages/examples/contacts.html
         * Data comes from /api/company/agents endpoint (snake_case fields)
         */
        showAgentsGrid(agents) {
            const container = $('#agentsContainer');
            container.empty();

            if (!agents || agents.length === 0) {
                $('#agentsLoading').hide();
                $('#agentsContainer').hide();
                $('#noAgents').show();
                return;
            }

            agents.forEach(agent => {
                // Data from /api/company/agents uses snake_case
                const displayName = agent.display_name || agent.email?.split('@')[0] || 'Usuario';
                const avatarUrl = agent.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=6c757d&color=fff&size=128`;
                const email = agent.email || '—';
                const phone = agent.phone_number || null;
                const assignedAt = agent.assigned_at ? Utils.formatDate(agent.assigned_at) : '—';
                const lastActivity = Utils.formatRelativeTime(agent.last_activity_at);
                const userRoleId = agent.id; // The id from /api/company/agents is the UserRole ID
                const userId = agent.user_id;
                const ticketsAssigned = agent.tickets_assigned || 0;
                const ticketsResolved = agent.tickets_resolved || 0;

                // Store agent data as JSON for profile modal
                const agentDataJson = JSON.stringify(agent).replace(/"/g, '&quot;');

                // AdminLTE v3 Contacts Card Template
                const cardHtml = `
                    <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                        <div class="card bg-light d-flex flex-fill">
                            <div class="card-header text-muted border-bottom-0">
                                <i class="fas fa-user-tie mr-1"></i> Agente de Soporte
                            </div>
                            <div class="card-body pt-0">
                                <div class="row">
                                    <div class="col-7">
                                        <h2 class="lead"><b>${Utils.escapeHtml(displayName)}</b></h2>
                                        <p class="text-muted text-sm mb-1">
                                            <i class="fas fa-envelope mr-1"></i> ${Utils.escapeHtml(email)}
                                        </p>
                                        <ul class="ml-4 mb-0 fa-ul text-muted">
                                            ${phone ? `
                                            <li class="small">
                                                <span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                                ${Utils.escapeHtml(phone)}
                                            </li>
                                            ` : ''}
                                            <li class="small">
                                                <span class="fa-li"><i class="fas fa-lg fa-calendar-check"></i></span>
                                                Asignado: ${assignedAt}
                                            </li>
                                            <li class="small">
                                                <span class="fa-li"><i class="fas fa-lg fa-ticket-alt"></i></span>
                                                Tickets: ${ticketsAssigned} asignados, ${ticketsResolved} resueltos
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-5 text-center">
                                        <img src="${avatarUrl}" alt="Avatar" class="img-circle img-fluid" style="max-width: 90px;">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="text-right">
                                    <button class="btn btn-sm btn-primary btn-view-profile" 
                                            data-agent='${agentDataJson}'
                                            title="Ver Perfil">
                                        <i class="fas fa-user"></i> Ver Perfil
                                    </button>
                                    ${userRoleId ? `
                                    <button class="btn btn-sm btn-danger btn-remove-agent ml-1" 
                                            data-id="${userRoleId}" 
                                            data-name="${Utils.escapeHtml(displayName)}"
                                            title="Remover del equipo">
                                        <i class="fas fa-user-minus"></i> Remover
                                    </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.append(cardHtml);
            });

            $('#agentsLoading').hide();
            $('#noAgents').hide();
            $('#agentsContainer').show();

            // Attach events
            $('.btn-remove-agent').off('click').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                Modals.openRemoveAgent(id, name);
            });

            // Attach View Profile events
            $('.btn-view-profile').off('click').on('click', function() {
                const agentData = $(this).data('agent');
                if (typeof ViewAgentModal !== 'undefined') {
                    ViewAgentModal.open(agentData);
                } else {
                    console.error('[Agents] ViewAgentModal not available');
                }
            });
        },

        showAgentsError(message) {
            $('#agentsLoading').hide();
            $('#agentsContainer').hide();
            $('#noAgents').hide();
            $('#agentsErrorText').text(message);
            $('#agentsError').show();
        },

        // ===== INVITATIONS =====
        showInvitationsLoading() {
            $('#invitationsLoading').show();
            $('#invitationsContainer').hide();
            $('#noInvitations').hide();
        },

        showInvitationsTable(invitations) {
            const tbody = $('#invitationsTableBody');
            tbody.empty();

            if (!invitations || invitations.length === 0) {
                $('#invitationsLoading').hide();
                $('#invitationsContainer').hide();
                $('#noInvitations').show();
                return;
            }

            invitations.forEach(inv => {
                const user = inv.user || {};
                const displayName = user.display_name || user.email?.split('@')[0] || 'Usuario';
                const avatarUrl = user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=6c757d&color=fff&size=80`;
                const statusBadge = this.getStatusBadge(inv.status, inv.status_label);
                const canCancel = inv.status === 'PENDING';

                const rowHtml = `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="${avatarUrl}" 
                                     class="img-circle mr-2" alt="Avatar" 
                                     style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <strong>${Utils.escapeHtml(displayName)}</strong><br>
                                    <small class="text-muted">${Utils.escapeHtml(user.email || '—')}</small>
                                </div>
                            </div>
                        </td>
                        <td><small class="text-muted">${Utils.escapeHtml(inv.message || '—')}</small></td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center"><small>${Utils.formatDate(inv.created_at)}</small></td>
                        <td class="text-center">
                            ${canCancel ? `
                                <button class="btn btn-sm btn-outline-danger btn-cancel-invitation" 
                                        data-id="${inv.id}" title="Cancelar Invitación">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : '—'}
                        </td>
                    </tr>
                `;
                tbody.append(rowHtml);
            });

            $('#invitationsLoading').hide();
            $('#noInvitations').hide();
            $('#invitationsContainer').show();

            // Attach events
            $('.btn-cancel-invitation').off('click').on('click', async function() {
                const id = $(this).data('id');
                const btn = $(this);
                
                const result = await Swal.fire({
                    title: '¿Cancelar invitación?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'No'
                });

                if (result.isConfirmed) {
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                    const apiResult = await API.cancelInvitation(id);
                    
                    if (apiResult.success) {
                        Toast.success('Invitación cancelada');
                        loadData();
                    } else {
                        Toast.error(apiResult.message);
                        btn.prop('disabled', false).html('<i class="fas fa-times"></i>');
                    }
                }
            });
        },

        getStatusBadge(status, label) {
            const colors = {
                'PENDING': 'warning',
                'ACCEPTED': 'success',
                'REJECTED': 'danger',
                'CANCELLED': 'secondary'
            };
            const color = colors[status] || 'secondary';
            return `<span class="badge badge-${color}">${Utils.escapeHtml(label)}</span>`;
        },
    };

    // ========== MODALS ==========
    const Modals = {
        openInvite() {
            state.selectedUser = null;
            $('#inviteAgentForm')[0].reset();
            $('#inviteErrorAlert').hide();
            $('#selectedUserContainer').hide();
            $('#btnSendInvite').prop('disabled', true);
            // Reset Select2
            if (typeof $.fn.select2 !== 'undefined') {
                $('#searchUserSelect').val(null).trigger('change');
            }
            $('#inviteAgentModal').modal('show');
        },

        selectUser(user) {
            state.selectedUser = user;
            $('#selectedUserId').val(user.id);
            $('#selectedUserName').text(user.display_name);
            $('#selectedUserEmail').text(user.email);
            $('#selectedUserAvatar').attr('src', user.avatar_url);
            $('#selectedUserContainer').show();
            $('#btnSendInvite').prop('disabled', false);
        },

        clearUserSelection() {
            state.selectedUser = null;
            $('#selectedUserId').val('');
            $('#selectedUserContainer').hide();
            $('#btnSendInvite').prop('disabled', true);
            // Reset Select2
            if (typeof $.fn.select2 !== 'undefined') {
                $('#searchUserSelect').val(null).trigger('change');
            }
        },

        openRemoveAgent(roleId, agentName) {
            $('#removeAgentRoleId').val(roleId);
            $('#removeAgentName').text(agentName);
            $('#removeAgentModal').modal('show');
        }
    };

    // ========== LOAD DATA ==========
    async function loadData() {
        // Load Agents
        UI.showAgentsLoading();
        const agentsResult = await API.loadAgents();
        
        if (agentsResult.success) {
            state.agents = agentsResult.data;
            UI.showAgentsGrid(state.agents);
        } else {
            UI.showAgentsError(agentsResult.message);
        }

        // Load Invitations
        UI.showInvitationsLoading();
        const invitationsResult = await API.loadInvitations();
        
        if (invitationsResult.success) {
            state.invitations = invitationsResult.data;
            UI.showInvitationsTable(state.invitations);
            
            // Update stats
            const pending = state.invitations.filter(i => i.status === 'PENDING').length;
            const accepted = state.invitations.filter(i => i.status === 'ACCEPTED').length;
            UI.updateStats(state.agents.length, pending, accepted);
        }
    }

    // ========== EVENT HANDLERS ==========
    function initEventHandlers() {
        // Open invite modal
        $('#btnInviteAgent, #btnInviteAgentEmpty').on('click', () => Modals.openInvite());

        // Clear user selection
        $('#btnClearSelection').on('click', () => Modals.clearUserSelection());

        // Initialize Select2 for user search
        initUserSearchSelect2();

        // Submit invitation
        $('#inviteAgentForm').on('submit', async function(e) {
            e.preventDefault();
            
            if (!state.selectedUser) {
                $('#inviteErrorMessage').text('Debes seleccionar un usuario.');
                $('#inviteErrorAlert').show();
                return;
            }

            const btn = $('#btnSendInvite');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');
            $('#inviteErrorAlert').hide();

            const message = $('#inviteMessage').val().trim();
            const result = await API.createInvitation(state.selectedUser.id, message);

            if (result.success) {
                $('#inviteAgentModal').modal('hide');
                Toast.success(result.message || 'Invitación enviada exitosamente');
                loadData();
            } else {
                $('#inviteErrorMessage').text(result.message);
                $('#inviteErrorAlert').show();
                btn.prop('disabled', false).html(originalHtml);
            }
        });

        // Confirm remove agent
        $('#btnConfirmRemove').on('click', async function() {
            const roleId = $('#removeAgentRoleId').val();
            const btn = $(this);
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Removiendo...');

            const result = await API.removeAgent(roleId);

            if (result.success) {
                $('#removeAgentModal').modal('hide');
                Toast.success('Agente removido exitosamente');
                loadData();
            } else {
                Toast.error(result.message);
            }

            btn.prop('disabled', false).html(originalHtml);
        });
    }

    // ========== SELECT2 FOR USER SEARCH ==========
    function initUserSearchSelect2() {
        // Check if Select2 is available
        if (typeof $.fn.select2 === 'undefined') {
            console.error('[Agents] Select2 not loaded, retrying...');
            setTimeout(initUserSearchSelect2, 500);
            return;
        }

        console.log('[Agents] Initializing Select2 for user search...');

        $('#searchUserSelect').select2({
            theme: 'bootstrap4',
            placeholder: 'Escribe para buscar por email o nombre...',
            allowClear: true,
            minimumInputLength: 2,
            dropdownParent: $('#inviteAgentModal'),
            ajax: {
                url: `${CONFIG.API_BASE}/company/invitations/search-users`,
                dataType: 'json',
                delay: 300,
                headers: {
                    'Authorization': `Bearer ${Utils.getToken()}`,
                    'Accept': 'application/json'
                },
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(response) {
                    const users = response.data || [];
                    return {
                        results: users.map(user => {
                            const displayName = user.display_name || user.email?.split('@')[0] || 'Usuario';
                            const avatarUrl = user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=6c757d&color=fff&size=80`;
                            return {
                                id: user.id,
                                text: `${displayName} (${user.email})`,
                                displayName: displayName,
                                email: user.email,
                                avatarUrl: avatarUrl
                            };
                        })
                    };
                },
                cache: true,
                error: function(xhr, status, error) {
                    console.error('[Agents] Select2 AJAX error:', error);
                }
            },
            templateResult: formatUserResult,
            templateSelection: formatUserSelection,
            language: {
                inputTooShort: function() {
                    return 'Escribe al menos 2 caracteres para buscar...';
                },
                noResults: function() {
                    return 'No se encontraron usuarios elegibles';
                },
                searching: function() {
                    return 'Buscando...';
                },
                errorLoading: function() {
                    return 'Error al buscar usuarios';
                }
            }
        });

        // Handle selection
        $('#searchUserSelect').on('select2:select', function(e) {
            const data = e.params.data;
            Modals.selectUser({
                id: data.id,
                display_name: data.displayName,
                email: data.email,
                avatar_url: data.avatarUrl
            });
        });

        // Handle clear
        $('#searchUserSelect').on('select2:clear', function() {
            Modals.clearUserSelection();
        });

        console.log('[Agents] Select2 initialized successfully');
    }

    // Select2 template for result dropdown
    function formatUserResult(user) {
        if (user.loading) {
            return $('<span><i class="fas fa-spinner fa-spin mr-2"></i>Buscando...</span>');
        }
        if (!user.id) {
            return user.text;
        }
        
        const $result = $(`
            <div class="d-flex align-items-center py-1">
                <img src="${Utils.escapeHtml(user.avatarUrl)}" 
                     class="img-circle mr-2" 
                     alt="Avatar" 
                     style="width: 32px; height: 32px; object-fit: cover;">
                <div>
                    <strong>${Utils.escapeHtml(user.displayName)}</strong><br>
                    <small class="text-muted">${Utils.escapeHtml(user.email)}</small>
                </div>
            </div>
        `);
        return $result;
    }

    // Select2 template for selected value
    function formatUserSelection(user) {
        if (!user.id) {
            return user.text;
        }
        return user.displayName || user.text;
    }

    // ========== INIT ==========
    function init() {
        console.log('[Agents] Waiting for jQuery...');
        
        if (typeof jQuery !== 'undefined') {
            $(document).ready(function() {
                console.log('[Agents] jQuery ready, initializing...');
                state.companyId = $('#companyId').val();
                initEventHandlers();
                loadData();
            });
        } else {
            const check = setInterval(function() {
                if (typeof jQuery !== 'undefined') {
                    clearInterval(check);
                    $(document).ready(function() {
                        console.log('[Agents] jQuery detected, initializing...');
                        state.companyId = $('#companyId').val();
                        initEventHandlers();
                        loadData();
                    });
                }
            }, 100);
        }
    }

    init();
})();
</script>
@endpush
