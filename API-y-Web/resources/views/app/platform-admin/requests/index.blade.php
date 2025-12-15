@extends('layouts.authenticated')

@section('title', 'Gestión de Solicitudes - Platform Admin')
@section('content_header', 'Gestión de Solicitudes de Empresa')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Solicitudes</li>
@endsection

@section('content')

{{-- Statistics Small Boxes (AdminLTE v3 Official) --}}
<div class="row">
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-total" class="small-box bg-info" style="cursor:pointer" data-filter="">
            <div class="inner">
                <h3>0</h3>
                <p>Total Solicitudes</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-pending" class="small-box bg-warning" style="cursor:pointer" data-filter="pending">
            <div class="inner">
                <h3>0</h3>
                <p>Pendientes</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-approved" class="small-box bg-success" style="cursor:pointer" data-filter="approved">
            <div class="inner">
                <h3>0</h3>
                <p>Aprobadas</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-rejected" class="small-box bg-danger" style="cursor:pointer" data-filter="rejected">
            <div class="inner">
                <h3>0</h3>
                <p>Rechazadas</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
</div>

{{-- Requests Table Card --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice"></i> Solicitudes de Empresas</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="maximize">
                <i class="fas fa-expand"></i>
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Filters Section --}}
        <div class="p-3 border-bottom bg-light">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Buscar</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="searchInput" placeholder="Empresa, email o código...">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Estado</label>
                        <select class="form-control form-control-sm" id="statusFilter">
                            <option value="">Todos</option>
                            <option value="pending" selected>Pendientes</option>
                            <option value="approved">Aprobadas</option>
                            <option value="rejected">Rechazadas</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Industria</label>
                        <select class="form-control form-control-sm" id="industryFilter">
                            <option value="">Todas</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Ordenar por</label>
                        <select class="form-control form-control-sm" id="orderByFilter">
                            <option value="created_at">Más recientes</option>
                            <option value="company_name">Nombre empresa</option>
                            <option value="admin_email">Email</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="text-sm mb-1 d-block">&nbsp;</label>
                    <button type="button" class="btn btn-default btn-sm" id="btnResetFilters">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnRefresh">
                        <i class="fas fa-sync-alt"></i> Refrescar
                    </button>
                </div>
            </div>
        </div>

        {{-- Loading Spinner --}}
        <div id="loadingSpinner" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div>
            <p class="text-muted mt-3">Cargando solicitudes...</p>
        </div>

        {{-- Table --}}
        <div id="tableContainer" style="display:none">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:10%">Código</th>
                            <th style="width:22%">Empresa</th>
                            <th style="width:18%">Email Admin</th>
                            <th style="width:12%">Industria</th>
                            <th style="width:10%">Estado</th>
                            <th style="width:12%">Fecha</th>
                            <th style="width:16%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody"></tbody>
                </table>
            </div>
        </div>

        {{-- Error Message --}}
        <div id="errorMessage" class="alert alert-danger m-3" style="display:none">
            <i class="fas fa-exclamation-circle"></i> <span id="errorText"></span>
        </div>
    </div>

    {{-- Footer: Pagination --}}
    <div class="card-footer border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" id="paginationInfo">Mostrando 0 de 0</small>
            <nav><ul class="pagination pagination-sm mb-0" id="paginationControls"></ul></nav>
        </div>
    </div>
</div>

{{-- Include Modal Partials --}}
@include('app.platform-admin.requests.partials.view-request-modal')
@include('app.platform-admin.requests.partials.approve-request-modal')
@include('app.platform-admin.requests.partials.reject-request-modal')

@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    console.log('[Requests] Initializing...');

    // =========================================================================
    // CONFIGURATION
    // =========================================================================
    const CONFIG = {
        API_BASE: '/api',
        TOAST_DELAY: 3000,
        DEBOUNCE_DELAY: 400,
        PER_PAGE: 15
    };

    // =========================================================================
    // STATE (Centralized)
    // =========================================================================
    const state = {
        requests: [],
        currentRequest: null,
        currentPage: 1,
        filters: {
            status: 'pending',
            industry_id: '',
            search: '',
            order_by: 'created_at',
            order_direction: 'desc'
        },
        isLoading: false,
        isOperating: false,
        meta: null,
        links: null,
        industries: [],
        industriesLoaded: false
    };

    // =========================================================================
    // UTILITIES
    // =========================================================================
    const Utils = {
        getToken() {
            return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
        },

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('es-ES', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit'
            });
        },

        translateError(error) {
            const status = error.status;
            const data = error.data;

            if (status === 401) return 'Sesión expirada. Por favor recarga la página.';
            if (status === 403) return data?.message || 'No tienes permiso para esta acción.';
            if (status === 404) return 'Solicitud no encontrada.';
            if (status === 422 && data?.errors) {
                return Object.values(data.errors).flat().join('. ');
            }
            return data?.message || 'Error al procesar la solicitud.';
        },

        getStatusBadge(status) {
            const s = (status || '').toLowerCase();
            const badges = {
                pending: '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>',
                approved: '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Aprobada</span>',
                rejected: '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rechazada</span>'
            };
            return badges[s] || '<span class="badge badge-secondary">Desconocido</span>';
        },

        getIndustryBadge(industryName, industryId) {
            const colors = ['badge-primary', 'badge-info', 'badge-success', 'badge-secondary'];
            const colorIndex = industryId ? (industryId.charCodeAt(0)) % colors.length : 0;
            return `<span class="badge ${colors[colorIndex]}">${this.escapeHtml(industryName)}</span>`;
        }
    };

    // =========================================================================
    // TOAST (AdminLTE v3 Official)
    // =========================================================================
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
        },

        warning(message, title = 'Advertencia') {
            $(document).Toasts('create', {
                class: 'bg-warning',
                title: title,
                body: message,
                autohide: true,
                delay: CONFIG.TOAST_DELAY,
                icon: 'fas fa-exclamation-triangle'
            });
        }
    };

    // Expose showToast globally for modals
    window.showToast = function(type, message) {
        if (type === 'success') Toast.success(message);
        else if (type === 'error') Toast.error(message);
        else if (type === 'warning') Toast.warning(message);
        else Toast.success(message);
    };

    // =========================================================================
    // API LAYER
    // =========================================================================
    const API = {
        async loadRequests() {
            if (state.isLoading) return;
            state.isLoading = true;
            UI.showLoading();

            try {
                const params = new URLSearchParams({
                    page: state.currentPage,
                    per_page: CONFIG.PER_PAGE
                });

                if (state.filters.status) params.append('status', state.filters.status);
                if (state.filters.industry_id) params.append('industry_id', state.filters.industry_id);
                if (state.filters.search) params.append('search', state.filters.search);
                if (state.filters.order_by) params.append('order_by', state.filters.order_by);
                if (state.filters.order_direction) params.append('order_direction', state.filters.order_direction);

                const response = await fetch(`${CONFIG.API_BASE}/company-requests?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw { status: response.status, data: data };
                }

                state.requests = data.data || [];
                state.meta = data.meta;
                state.links = data.links;

                UI.hideLoading();
                UI.renderTable();
                UI.updatePagination();
                this.loadStatistics();

            } catch (error) {
                console.error('[Requests] Load error:', error);
                UI.showError(Utils.translateError(error));
            } finally {
                state.isLoading = false;
            }
        },

        async loadStatistics() {
            try {
                const headers = {
                    'Authorization': `Bearer ${Utils.getToken()}`,
                    'Accept': 'application/json'
                };

                const [totalRes, pendingRes, approvedRes, rejectedRes] = await Promise.all([
                    fetch(`${CONFIG.API_BASE}/company-requests?per_page=1`, { headers }),
                    fetch(`${CONFIG.API_BASE}/company-requests?per_page=1&status=pending`, { headers }),
                    fetch(`${CONFIG.API_BASE}/company-requests?per_page=1&status=approved`, { headers }),
                    fetch(`${CONFIG.API_BASE}/company-requests?per_page=1&status=rejected`, { headers })
                ]);

                const [totalData, pendingData, approvedData, rejectedData] = await Promise.all([
                    totalRes.json(),
                    pendingRes.json(),
                    approvedRes.json(),
                    rejectedRes.json()
                ]);

                const total = totalData.meta?.total || 0;
                const pending = pendingData.meta?.total || 0;
                const approved = approvedData.meta?.total || 0;
                const rejected = rejectedData.meta?.total || 0;

                // Update small-box stats
                $('#stat-total .inner h3').text(total);
                $('#stat-pending .inner h3').text(pending);
                $('#stat-approved .inner h3').text(approved);
                $('#stat-rejected .inner h3').text(rejected);

            } catch (error) {
                console.error('[Requests] Stats error:', error);
            }
        },

        async loadIndustries() {
            if (state.industriesLoaded) return;

            try {
                const response = await fetch(`${CONFIG.API_BASE}/company-industries`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) return;

                const data = await response.json();
                state.industries = data.data || [];
                state.industriesLoaded = true;

                // Populate filter dropdown
                const $select = $('#industryFilter');
                $select.html('<option value="">Todas</option>');
                state.industries.forEach(ind => {
                    $select.append(`<option value="${ind.id}">${Utils.escapeHtml(ind.name)}</option>`);
                });

            } catch (error) {
                console.error('[Requests] Industries error:', error);
            }
        },

        async approveRequest(requestId, sendEmail) {
            const response = await fetch(`${CONFIG.API_BASE}/company-requests/${requestId}/approve`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${Utils.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ send_email: sendEmail })
            });

            const data = await response.json();

            if (!response.ok) {
                throw { status: response.status, data: data };
            }

            return data;
        },

        async rejectRequest(requestId, reason) {
            const response = await fetch(`${CONFIG.API_BASE}/company-requests/${requestId}/reject`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${Utils.getToken()}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            });

            const data = await response.json();

            if (!response.ok) {
                throw { status: response.status, data: data };
            }

            return data;
        }
    };

    // =========================================================================
    // UI LAYER
    // =========================================================================
    const UI = {
        showLoading() {
            $('#loadingSpinner').show();
            $('#tableContainer, #errorMessage').hide();
        },

        hideLoading() {
            $('#loadingSpinner').hide();
            $('#tableContainer').show();
        },

        showError(message) {
            $('#loadingSpinner, #tableContainer').hide();
            $('#errorText').text(message);
            $('#errorMessage').show();
        },

        renderTable() {
            const $tbody = $('#requestsTableBody');

            if (!state.requests.length) {
                $tbody.html(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No hay solicitudes disponibles</p>
                        </td>
                    </tr>
                `);
                return;
            }

            $tbody.html(state.requests.map(request => {
                const industryName = request.industry?.name || 'N/A';
                const industryId = request.industry?.id || '';
                const isPending = (request.status || '').toLowerCase() === 'pending';

                return `
                    <tr data-id="${request.id}">
                        <td><code>${Utils.escapeHtml(request.requestCode || 'N/A')}</code></td>
                        <td>
                            <strong>${Utils.escapeHtml(request.companyName || 'N/A')}</strong><br>
                            <small class="text-muted">${Utils.escapeHtml(request.legalName || '')}</small>
                        </td>
                        <td><small>${Utils.escapeHtml(request.adminEmail || 'N/A')}</small></td>
                        <td>${Utils.getIndustryBadge(industryName, industryId)}</td>
                        <td>${Utils.getStatusBadge(request.status)}</td>
                        <td><small>${Utils.formatDate(request.createdAt)}</small></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-primary btn-view" data-id="${request.id}" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${isPending ? `
                                <button class="btn btn-sm btn-success btn-approve" data-id="${request.id}" title="Aprobar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-reject" data-id="${request.id}" title="Rechazar">
                                    <i class="fas fa-ban"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            }).join(''));

            this.attachRowEvents();
        },

        attachRowEvents() {
            $('.btn-view').off('click').on('click', function() {
                Modals.openView($(this).data('id'));
            });
            $('.btn-approve').off('click').on('click', function() {
                Modals.openApprove($(this).data('id'));
            });
            $('.btn-reject').off('click').on('click', function() {
                Modals.openReject($(this).data('id'));
            });
        },

        updatePagination() {
            const meta = state.meta;
            const links = state.links;

            // Calculate from/to
            const from = meta && meta.total > 0 ? ((meta.current_page - 1) * meta.per_page) + 1 : 0;
            const to = meta ? Math.min(meta.current_page * meta.per_page, meta.total) : 0;
            $('#paginationInfo').text(`Mostrando ${from} a ${to} de ${meta?.total || 0}`);

            const $controls = $('#paginationControls');
            $controls.empty();

            if (!meta || meta.last_page <= 1) return;

            // Previous button
            $controls.append(`
                <li class="page-item ${!links?.prev ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-action="prev"><i class="fas fa-chevron-left"></i></a>
                </li>
            `);

            // Page numbers (max 5 visible)
            const maxButtons = 5;
            let startPage = Math.max(1, meta.current_page - Math.floor(maxButtons / 2));
            let endPage = Math.min(meta.last_page, startPage + maxButtons - 1);

            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                $controls.append(`
                    <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }

            // Next button
            $controls.append(`
                <li class="page-item ${!links?.next ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-action="next"><i class="fas fa-chevron-right"></i></a>
                </li>
            `);

            // Pagination click handlers
            $controls.find('a').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                const action = $(this).data('action');

                if (page) {
                    state.currentPage = page;
                } else if (action === 'prev' && state.currentPage > 1) {
                    state.currentPage--;
                } else if (action === 'next' && state.currentPage < meta.last_page) {
                    state.currentPage++;
                }

                API.loadRequests();
            });
        },

        updateFilterSelection() {
            $('.small-box').removeClass('elevation-3');
            const status = state.filters.status;
            if (status === 'pending') {
                $('#stat-pending').addClass('elevation-3');
            } else if (status === 'approved') {
                $('#stat-approved').addClass('elevation-3');
            } else if (status === 'rejected') {
                $('#stat-rejected').addClass('elevation-3');
            } else {
                $('#stat-total').addClass('elevation-3');
            }
        }
    };

    // =========================================================================
    // MODALS CONTROLLER
    // =========================================================================
    const Modals = {
        openView(requestId) {
            const request = state.requests.find(r => r.id === requestId);
            if (!request) {
                Toast.error('Solicitud no encontrada');
                return;
            }
            state.currentRequest = request;

            if (typeof ViewRequestModal !== 'undefined') {
                ViewRequestModal.open(request);
            } else {
                Toast.error('Error al abrir modal de vista');
            }
        },

        openApprove(requestId) {
            const request = state.requests.find(r => r.id === requestId);
            if (!request) {
                Toast.error('Solicitud no encontrada');
                return;
            }
            state.currentRequest = request;

            if (typeof ApproveRequestModal !== 'undefined') {
                ApproveRequestModal.open(request);
            } else {
                Toast.error('Error al abrir modal de aprobación');
            }
        },

        openReject(requestId) {
            const request = state.requests.find(r => r.id === requestId);
            if (!request) {
                Toast.error('Solicitud no encontrada');
                return;
            }
            state.currentRequest = request;

            if (typeof RejectRequestModal !== 'undefined') {
                RejectRequestModal.open(request);
            } else {
                Toast.error('Error al abrir modal de rechazo');
            }
        },

        async handleApprove(requestId, sendEmail) {
            if (state.isOperating) return;
            state.isOperating = true;

            ApproveRequestModal.setLoading(true);

            try {
                const result = await API.approveRequest(requestId, sendEmail);
                Toast.success(result.data?.message || result.message || 'Solicitud aprobada exitosamente');
                ApproveRequestModal.close();
                API.loadRequests();

            } catch (error) {
                console.error('[Requests] Approve error:', error);
                ApproveRequestModal.showError(Utils.translateError(error));
                ApproveRequestModal.setLoading(false);

            } finally {
                state.isOperating = false;
            }
        },

        async handleReject(requestId, reason) {
            if (state.isOperating) return;
            state.isOperating = true;

            RejectRequestModal.setLoading(true);

            try {
                const result = await API.rejectRequest(requestId, reason);
                Toast.success(result.data?.message || result.message || 'Solicitud rechazada exitosamente');
                RejectRequestModal.close();
                API.loadRequests();

            } catch (error) {
                console.error('[Requests] Reject error:', error);
                RejectRequestModal.showError(Utils.translateError(error));
                RejectRequestModal.setLoading(false);

            } finally {
                state.isOperating = false;
            }
        }
    };

    // =========================================================================
    // EVENT HANDLERS INITIALIZATION
    // =========================================================================
    function initEvents() {
        // Search with debounce
        let searchTimer;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimer);
            const value = $(this).val().trim();
            searchTimer = setTimeout(() => {
                state.filters.search = value;
                state.currentPage = 1;
                API.loadRequests();
            }, CONFIG.DEBOUNCE_DELAY);
        });

        // Filter handlers
        $('#statusFilter').on('change', function() {
            state.filters.status = $(this).val();
            state.currentPage = 1;
            UI.updateFilterSelection();
            API.loadRequests();
        });

        $('#industryFilter').on('change', function() {
            state.filters.industry_id = $(this).val();
            state.currentPage = 1;
            API.loadRequests();
        });

        $('#orderByFilter').on('change', function() {
            state.filters.order_by = $(this).val();
            API.loadRequests();
        });

        // Reset filters
        $('#btnResetFilters').on('click', function() {
            state.filters = {
                status: 'pending',
                industry_id: '',
                search: '',
                order_by: 'created_at',
                order_direction: 'desc'
            };
            state.currentPage = 1;

            $('#searchInput').val('');
            $('#statusFilter').val('pending');
            $('#industryFilter').val('');
            $('#orderByFilter').val('created_at');

            UI.updateFilterSelection();
            API.loadRequests();
        });

        // Refresh button
        $('#btnRefresh').on('click', () => API.loadRequests());

        // Small-box click handlers for filtering
        $('#stat-total').on('click', () => {
            state.filters.status = '';
            state.currentPage = 1;
            $('#statusFilter').val('');
            UI.updateFilterSelection();
            API.loadRequests();
        });

        $('#stat-pending').on('click', () => {
            state.filters.status = 'pending';
            state.currentPage = 1;
            $('#statusFilter').val('pending');
            UI.updateFilterSelection();
            API.loadRequests();
        });

        $('#stat-approved').on('click', () => {
            state.filters.status = 'approved';
            state.currentPage = 1;
            $('#statusFilter').val('approved');
            UI.updateFilterSelection();
            API.loadRequests();
        });

        $('#stat-rejected').on('click', () => {
            state.filters.status = 'rejected';
            state.currentPage = 1;
            $('#statusFilter').val('rejected');
            UI.updateFilterSelection();
            API.loadRequests();
        });

        // Modal events from partials
        $(document).on('openApproveModal', function(e, requestId) {
            Modals.openApprove(requestId);
        });

        $(document).on('openRejectModal', function(e, requestId) {
            Modals.openReject(requestId);
        });

        $(document).on('confirmApproveRequest', function(e, requestId, sendEmail) {
            Modals.handleApprove(requestId, sendEmail);
        });

        $(document).on('confirmRejectRequest', function(e, requestId, reason) {
            Modals.handleReject(requestId, reason);
        });
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================
    async function init() {
        const token = Utils.getToken();
        if (!token) {
            Toast.error('Token de autenticación no encontrado');
            UI.showError('Error de autenticación. Por favor inicie sesión nuevamente.');
            return;
        }

        initEvents();
        UI.updateFilterSelection();

        // Load industries for filter
        await API.loadIndustries();

        // Load requests
        await API.loadRequests();

        console.log('[Requests] ✓ Initialized successfully');
    }

    // Start when DOM is ready
    $(document).ready(init);

})();
</script>
@endpush
