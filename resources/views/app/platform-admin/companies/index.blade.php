@extends('layouts.authenticated')

@section('title', 'Gestión de Empresas - Platform Admin')
@section('content_header', 'Gestión de Empresas')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Empresas</li>
@endsection

@section('content')

{{-- Statistics Info Boxes (Clickable) --}}
<div class="row mb-3">
    <div class="col-md-4 col-sm-6">
        <div class="info-box bg-light elevation-2" style="cursor:pointer" data-filter="" id="infoBoxAll">
            <span class="info-box-icon bg-primary"><i class="fas fa-building"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Empresas</span>
                <span class="info-box-number" id="totalCompanies">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-box bg-light" style="cursor:pointer" data-filter="active" id="infoBoxActive">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Activas</span>
                <span class="info-box-number" id="activeCompanies">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="info-box bg-light" style="cursor:pointer" data-filter="suspended" id="infoBoxSuspended">
            <span class="info-box-icon bg-warning"><i class="fas fa-pause-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Suspendidas</span>
                <span class="info-box-number" id="suspendedCompanies">0</span>
            </div>
        </div>
    </div>
</div>

{{-- Filters Card (Collapsible) --}}
<div class="card card-outline card-secondary collapsed-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros Avanzados</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="text-sm">Buscar</label>
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Nombre, email o código...">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Estado</label>
                    <select class="form-control form-control-sm" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="active" selected>Activas</option>
                        <option value="suspended">Suspendidas</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Industria</label>
                    <select class="form-control form-control-sm" id="industryFilter">
                        <option value="">Todas</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="text-sm">Ordenar por</label>
                    <select class="form-control form-control-sm" id="orderByFilter">
                        <option value="created_at">Fecha Creación</option>
                        <option value="name">Nombre</option>
                        <option value="support_email">Email</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="text-sm d-block">&nbsp;</label>
                <button type="button" class="btn btn-default btn-sm" id="btnResetFilters"><i class="fas fa-eraser"></i> Limpiar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnRefresh"><i class="fas fa-sync-alt"></i> Aplicar</button>
                <button type="button" class="btn btn-success btn-sm" id="btnCreateCompany"><i class="fas fa-plus"></i> Nueva</button>
            </div>
        </div>
    </div>
</div>

{{-- Companies Table Card --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building"></i> Empresas del Sistema</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            <button type="button" class="btn btn-tool" data-card-widget="maximize"><i class="fas fa-expand"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="loadingSpinner" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div>
            <p class="text-muted mt-3">Cargando empresas...</p>
        </div>
        <div id="tableContainer" style="display:none">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:10%">Código</th>
                            <th style="width:22%">Empresa</th>
                            <th style="width:18%">Email Soporte</th>
                            <th style="width:12%">Industria</th>
                            <th style="width:8%">Estado</th>
                            <th style="width:8%">Agentes</th>
                            <th style="width:22%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="companiesTableBody"></tbody>
                </table>
            </div>
        </div>
        <div id="errorMessage" class="alert alert-danger m-3" style="display:none">
            <i class="fas fa-exclamation-circle"></i> <span id="errorText"></span>
        </div>
    </div>
    <div class="card-footer border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" id="paginationInfo">Mostrando 0 de 0</small>
            <nav><ul class="pagination pagination-sm mb-0" id="paginationControls"></ul></nav>
        </div>
    </div>
</div>

{{-- Include Modal Partials --}}
@include('app.platform-admin.companies.partials.view-company-modal')
@include('app.platform-admin.companies.partials.form-company-modal')
@include('app.platform-admin.companies.partials.status-company-modal')
@include('app.platform-admin.companies.partials.delete-company-modal')

@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    console.log('[Companies] Initializing...');

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
        companies: [],
        currentCompany: null,
        currentPage: 1,
        filters: {
            status: 'active',
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
            if (status === 404) return 'Recurso no encontrado.';
            if (status === 422 && data?.errors) {
                return Object.values(data.errors).flat().join('. ');
            }
            return data?.message || 'Error al procesar la solicitud.';
        },
        getStatusBadge(status) {
            const s = (status || '').toLowerCase();
            const badges = {
                active: '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Activa</span>',
                suspended: '<span class="badge badge-warning"><i class="fas fa-pause-circle"></i> Suspendida</span>'
            };
            return badges[s] || '<span class="badge badge-secondary">Desconocido</span>';
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
        async loadCompanies() {
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
                
                const response = await fetch(`${CONFIG.API_BASE}/companies?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw { status: response.status, data: data };
                }
                
                state.companies = data.data || [];
                state.meta = data.meta;
                state.links = data.links;
                
                UI.hideLoading();
                UI.renderTable();
                UI.updatePagination();
                this.loadStatistics();
                
            } catch (error) {
                console.error('[Companies] Load error:', error);
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
                
                const [totalRes, activeRes, suspendedRes] = await Promise.all([
                    fetch(`${CONFIG.API_BASE}/companies?per_page=1`, { headers }),
                    fetch(`${CONFIG.API_BASE}/companies?per_page=1&status=active`, { headers }),
                    fetch(`${CONFIG.API_BASE}/companies?per_page=1&status=suspended`, { headers })
                ]);
                
                const [totalData, activeData, suspendedData] = await Promise.all([
                    totalRes.json(),
                    activeRes.json(),
                    suspendedRes.json()
                ]);
                
                $('#totalCompanies').text(totalData.meta?.total || 0);
                $('#activeCompanies').text(activeData.meta?.total || 0);
                $('#suspendedCompanies').text(suspendedData.meta?.total || 0);
                
            } catch (error) {
                console.error('[Companies] Stats error:', error);
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
                console.error('[Companies] Industries error:', error);
            }
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
            const $tbody = $('#companiesTableBody');
            
            if (!state.companies.length) {
                $tbody.html(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No hay empresas</p>
                        </td>
                    </tr>
                `);
                return;
            }
            
            $tbody.html(state.companies.map(company => {
                const industryName = company.industry?.name || 'N/A';
                return `
                    <tr data-id="${company.id}">
                        <td><code>${Utils.escapeHtml(company.companyCode || 'N/A')}</code></td>
                        <td>
                            <strong>${Utils.escapeHtml(company.name || 'N/A')}</strong><br>
                            <small class="text-muted">${Utils.escapeHtml(company.legalName || '')}</small>
                        </td>
                        <td><small>${Utils.escapeHtml(company.supportEmail || 'N/A')}</small></td>
                        <td><span class="badge badge-info">${Utils.escapeHtml(industryName)}</span></td>
                        <td>${Utils.getStatusBadge(company.status)}</td>
                        <td><span class="badge badge-primary">${company.activeAgentsCount || 0}</span></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-primary btn-view" data-id="${company.id}" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-info btn-edit" data-id="${company.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-status" data-id="${company.id}" title="Estado">
                                <i class="fas fa-ban"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${company.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
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
            $('.btn-edit').off('click').on('click', function() {
                Modals.openEdit($(this).data('id'));
            });
            $('.btn-status').off('click').on('click', function() {
                Modals.openStatus($(this).data('id'));
            });
            $('.btn-delete').off('click').on('click', function() {
                Modals.openDelete($(this).data('id'));
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
            
            // Page numbers
            for (let i = 1; i <= meta.last_page; i++) {
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
                
                API.loadCompanies();
            });
        },
        
        updateFilterSelection() {
            $('.info-box').removeClass('elevation-2');
            const status = state.filters.status;
            if (status === 'active') {
                $('#infoBoxActive').addClass('elevation-2');
            } else if (status === 'suspended') {
                $('#infoBoxSuspended').addClass('elevation-2');
            } else {
                $('#infoBoxAll').addClass('elevation-2');
            }
        }
    };

    // =========================================================================
    // MODALS CONTROLLER
    // =========================================================================
    const Modals = {
        openView(companyId) {
            if (!companyId) {
                Toast.error('ID de empresa no válido');
                return;
            }
            
            // Store company from list for quick reference
            const company = state.companies.find(c => c.id === companyId);
            if (company) {
                state.currentCompany = company;
            }
            
            if (typeof ViewCompanyModal !== 'undefined') {
                // Modal now fetches complete data via API
                ViewCompanyModal.open(companyId);
            } else {
                Toast.error('Error al abrir modal de vista');
            }
        },
        
        openEdit(companyId) {
            const company = state.companies.find(c => c.id === companyId);
            if (!company) {
                Toast.error('Empresa no encontrada');
                return;
            }
            state.currentCompany = company;
            
            if (typeof FormCompanyModal !== 'undefined') {
                FormCompanyModal.openEdit(company);
            } else {
                Toast.error('Error al abrir modal de edición');
            }
        },
        
        openStatus(companyId) {
            const company = state.companies.find(c => c.id === companyId);
            if (!company) {
                Toast.error('Empresa no encontrada');
                return;
            }
            state.currentCompany = company;
            
            if (typeof StatusCompanyModal !== 'undefined') {
                StatusCompanyModal.open(company);
            } else {
                Toast.error('Error al abrir modal de estado');
            }
        },
        
        openDelete(companyId) {
            const company = state.companies.find(c => c.id === companyId);
            if (!company) {
                Toast.error('Empresa no encontrada');
                return;
            }
            state.currentCompany = company;
            
            if (typeof DeleteCompanyModal !== 'undefined') {
                DeleteCompanyModal.open(company);
            } else {
                Toast.error('Error al abrir modal de eliminación');
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
                API.loadCompanies();
            }, CONFIG.DEBOUNCE_DELAY);
        });
        
        // Filter handlers
        $('#statusFilter').on('change', function() {
            state.filters.status = $(this).val();
            state.currentPage = 1;
            UI.updateFilterSelection();
            API.loadCompanies();
        });
        
        $('#industryFilter').on('change', function() {
            state.filters.industry_id = $(this).val();
            state.currentPage = 1;
            API.loadCompanies();
        });
        
        $('#orderByFilter').on('change', function() {
            state.filters.order_by = $(this).val();
            API.loadCompanies();
        });
        
        // Reset filters
        $('#btnResetFilters').on('click', function() {
            state.filters = {
                status: 'active',
                industry_id: '',
                search: '',
                order_by: 'created_at',
                order_direction: 'desc'
            };
            state.currentPage = 1;
            
            $('#searchInput').val('');
            $('#statusFilter').val('active');
            $('#industryFilter').val('');
            $('#orderByFilter').val('created_at');
            
            UI.updateFilterSelection();
            API.loadCompanies();
        });
        
        // Refresh button
        $('#btnRefresh').on('click', () => API.loadCompanies());
        
        // Create button
        $('#btnCreateCompany').on('click', function() {
            if (typeof FormCompanyModal !== 'undefined') {
                FormCompanyModal.openCreate();
            }
        });
        
        // Info box click handlers
        $('#infoBoxAll').on('click', () => {
            state.filters.status = '';
            state.currentPage = 1;
            $('#statusFilter').val('');
            UI.updateFilterSelection();
            API.loadCompanies();
        });
        
        $('#infoBoxActive').on('click', () => {
            state.filters.status = 'active';
            state.currentPage = 1;
            $('#statusFilter').val('active');
            UI.updateFilterSelection();
            API.loadCompanies();
        });
        
        $('#infoBoxSuspended').on('click', () => {
            state.filters.status = 'suspended';
            state.currentPage = 1;
            $('#statusFilter').val('suspended');
            UI.updateFilterSelection();
            API.loadCompanies();
        });
        
        // Modal events from partials
        $(document).on('openEditCompanyModal', function(e, companyId) {
            Modals.openEdit(companyId);
        });
        
        $(document).on('openStatusCompanyModal', function(e, companyId) {
            Modals.openStatus(companyId);
        });
        
        $(document).on('openDeleteCompanyModal', function(e, companyId) {
            Modals.openDelete(companyId);
        });
        
        // Refresh on company operations
        $(document).on('companySaved', function() {
            API.loadCompanies();
        });
        
        $(document).on('companyStatusChanged', function() {
            API.loadCompanies();
        });
        
        $(document).on('companyDeleted', function() {
            API.loadCompanies();
        });
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================
    async function init() {
        const token = Utils.getToken();
        if (!token) {
            Toast.error('Token no encontrado');
            UI.showError('Error de autenticación. Por favor inicia sesión nuevamente.');
            return;
        }
        
        initEvents();
        UI.updateFilterSelection();
        
        await API.loadIndustries();
        await API.loadCompanies();
        
        console.log('[Companies] ✓ Initialized successfully');
    }

    $(document).ready(init);
})();
</script>
@endpush
