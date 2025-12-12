@extends('layouts.authenticated')

@section('title', 'Gestión de API Keys - Platform Admin')
@section('content_header', 'Gestión de API Keys')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">API Keys</li>
@endsection

@section('content')

{{-- Statistics Small Boxes --}}
<div class="row" id="statsRow">
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-total" class="small-box bg-info" style="cursor:pointer" data-filter="">
            <div class="overlay dark" id="stat-total-overlay"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
            <div class="inner">
                <h3>0</h3>
                <p>Total API Keys</p>
            </div>
            <div class="icon"><i class="fas fa-key"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-active" class="small-box bg-success" style="cursor:pointer" data-filter="active">
            <div class="overlay dark" id="stat-active-overlay"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
            <div class="inner">
                <h3>0</h3>
                <p>Activas</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-revoked" class="small-box bg-danger" style="cursor:pointer" data-filter="revoked">
            <div class="overlay dark" id="stat-revoked-overlay"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
            <div class="inner">
                <h3>0</h3>
                <p>Revocadas</p>
            </div>
            <div class="icon"><i class="fas fa-ban"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div id="stat-used-today" class="small-box bg-purple">
            <div class="overlay dark" id="stat-used-today-overlay"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
            <div class="inner">
                <h3>0</h3>
                <p>Usadas Hoy</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
</div>

{{-- API Keys Table Card --}}
<div class="card card-outline card-primary" id="apiKeysTableCard">
    {{-- Loading Overlay --}}
    <div class="overlay" id="tableOverlay" style="display:none;">
        <i class="fas fa-2x fa-sync-alt fa-spin"></i>
    </div>
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-key"></i> API Keys del Sistema</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" id="btnCreateApiKey">
                <i class="fas fa-plus"></i> Nueva API Key
            </button>
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
                            <input type="text" class="form-control" id="searchInput" placeholder="Nombre, key o empresa...">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Empresa</label>
                        <select class="form-control form-control-sm" id="companyFilter">
                            <option value="">Todas</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Estado</label>
                        <select class="form-control form-control-sm" id="statusFilter">
                            <option value="" selected>Todos</option>
                            <option value="active">Activas</option>
                            <option value="revoked">Revocadas</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Tipo</label>
                        <select class="form-control form-control-sm" id="typeFilter">
                            <option value="">Todos</option>
                            <option value="production">Producción</option>
                            <option value="development">Desarrollo</option>
                            <option value="testing">Testing</option>
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
            <p class="text-muted mt-3">Cargando API Keys...</p>
        </div>
        
        {{-- Table --}}
        <div id="tableContainer" style="display:none">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:20%">Empresa</th>
                            <th style="width:20%">API Key</th>
                            <th style="width:15%">Nombre</th>
                            <th style="width:10%">Tipo</th>
                            <th style="width:8%">Estado</th>
                            <th style="width:12%">Último Uso</th>
                            <th style="width:15%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="apiKeysTableBody"></tbody>
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
@include('app.platform-admin.api-keys.partials.create-api-key-modal')

@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    console.log('[API Keys] Initializing...');

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
    // STATE
    // =========================================================================
    const state = {
        apiKeys: [],
        currentPage: 1,
        filters: {
            status: '',
            company_id: '',
            type: '',
            search: '',
            order_by: 'created_at',
            order_direction: 'desc'
        },
        isLoading: false,
        meta: null,
        links: null,
        companies: [],
        companiesLoaded: false
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
            if (!dateString) return 'Nunca';
            return new Date(dateString).toLocaleDateString('es-ES', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit'
            });
        },
        formatRelativeTime(dateString) {
            if (!dateString) return 'Nunca';
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
        getStatusBadge(isActive) {
            return isActive
                ? '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Activa</span>'
                : '<span class="badge badge-danger"><i class="fas fa-ban"></i> Revocada</span>';
        },
        getTypeBadge(type) {
            const badges = {
                production: '<span class="badge badge-primary">Producción</span>',
                development: '<span class="badge badge-warning">Desarrollo</span>',
                testing: '<span class="badge badge-info">Testing</span>'
            };
            return badges[type] || '<span class="badge badge-secondary">Desconocido</span>';
        },
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Toast.success('API Key copiada al portapapeles');
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                Toast.success('API Key copiada al portapapeles');
            });
        }
    };

    // =========================================================================
    // TOAST
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
    
    window.showToast = function(type, message) {
        if (type === 'success') Toast.success(message);
        else if (type === 'error') Toast.error(message);
        else if (type === 'warning') Toast.warning(message);
    };

    // =========================================================================
    // API LAYER
    // =========================================================================
    const API = {
        async loadApiKeys() {
            if (state.isLoading) return;
            state.isLoading = true;
            UI.showLoading();
            
            try {
                const params = new URLSearchParams({
                    page: state.currentPage,
                    per_page: CONFIG.PER_PAGE
                });
                
                if (state.filters.status) params.append('status', state.filters.status);
                if (state.filters.company_id) params.append('company_id', state.filters.company_id);
                if (state.filters.type) params.append('type', state.filters.type);
                if (state.filters.search) params.append('search', state.filters.search);
                if (state.filters.order_by) params.append('order_by', state.filters.order_by);
                if (state.filters.order_direction) params.append('order_direction', state.filters.order_direction);
                
                const response = await fetch(`${CONFIG.API_BASE}/admin/api-keys?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw { status: response.status, data: data };
                }
                
                state.apiKeys = data.data || [];
                state.meta = data.meta;
                state.links = data.links;
                
                UI.hideLoading();
                UI.renderTable();
                UI.updatePagination();
                this.loadStatistics();
                
            } catch (error) {
                console.error('[API Keys] Load error:', error);
                UI.showError(Utils.translateError(error));
            } finally {
                state.isLoading = false;
            }
        },
        
        async loadStatistics() {
            $('#stat-total-overlay, #stat-active-overlay, #stat-revoked-overlay, #stat-used-today-overlay').show();
            
            try {
                const response = await fetch(`${CONFIG.API_BASE}/admin/api-keys/statistics`, {
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) return;
                
                const data = await response.json();
                
                $('#stat-total .inner h3').text(data.total || 0);
                $('#stat-active .inner h3').text(data.active || 0);
                $('#stat-revoked .inner h3').text(data.revoked || 0);
                $('#stat-used-today .inner h3').text(data.used_today || 0);
                
            } catch (error) {
                console.error('[API Keys] Stats error:', error);
            } finally {
                $('#stat-total-overlay, #stat-active-overlay, #stat-revoked-overlay, #stat-used-today-overlay').hide();
            }
        },
        
        async loadCompanies() {
            if (state.companiesLoaded) return;
            
            try {
                const response = await fetch(`${CONFIG.API_BASE}/companies?per_page=100&status=active`, {
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) return;
                
                const data = await response.json();
                state.companies = data.data || [];
                state.companiesLoaded = true;
                
                const $select = $('#companyFilter');
                $select.html('<option value="">Todas</option>');
                state.companies.forEach(company => {
                    $select.append(`<option value="${company.id}">${Utils.escapeHtml(company.name)}</option>`);
                });
                
            } catch (error) {
                console.error('[API Keys] Companies error:', error);
            }
        },
        
        async revokeKey(id) {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/admin/api-keys/${id}/revoke`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw { status: response.status, data: data };
                }
                
                Toast.success(data.message || 'API Key revocada');
                this.loadApiKeys();
                
            } catch (error) {
                Toast.error(Utils.translateError(error));
            }
        },
        
        async activateKey(id) {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/admin/api-keys/${id}/activate`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${Utils.getToken()}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw { status: response.status, data: data };
                }
                
                Toast.success(data.message || 'API Key activada');
                this.loadApiKeys();
                
            } catch (error) {
                Toast.error(Utils.translateError(error));
            }
        },
        
        async deleteKey(id) {
            try {
                const response = await fetch(`${CONFIG.API_BASE}/admin/api-keys/${id}`, {
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
                
                Toast.success(data.message || 'API Key eliminada');
                this.loadApiKeys();
                
            } catch (error) {
                Toast.error(Utils.translateError(error));
            }
        }
    };

    // =========================================================================
    // UI LAYER
    // =========================================================================
    const UI = {
        showLoading() {
            $('#tableOverlay').show();
            $('#loadingSpinner').show();
            $('#tableContainer, #errorMessage').hide();
        },
        
        hideLoading() {
            $('#tableOverlay').hide();
            $('#loadingSpinner').hide();
            $('#tableContainer').show();
        },
        
        showError(message) {
            $('#tableOverlay, #loadingSpinner, #tableContainer').hide();
            $('#errorText').text(message);
            $('#errorMessage').show();
        },
        
        renderTable() {
            const $tbody = $('#apiKeysTableBody');
            
            if (!state.apiKeys.length) {
                $tbody.html(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-key fa-2x mb-2"></i>
                            <p>No hay API Keys</p>
                        </td>
                    </tr>
                `);
                return;
            }
            
            $tbody.html(state.apiKeys.map(apiKey => {
                const companyName = apiKey.company?.name || 'N/A';
                const companyCode = apiKey.company?.companyCode || '';
                
                return `
                    <tr data-id="${apiKey.id}">
                        <td>
                            <strong>${Utils.escapeHtml(companyName)}</strong><br>
                            <small class="text-muted">${Utils.escapeHtml(companyCode)}</small>
                        </td>
                        <td>
                            <code class="text-primary" style="font-size: 0.85rem">${Utils.escapeHtml(apiKey.key)}</code>
                            <button class="btn btn-xs btn-outline-secondary ml-1 btn-copy" 
                                    data-key="${Utils.escapeHtml(apiKey.key_full)}" 
                                    title="Copiar API Key completa">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                        <td>${Utils.escapeHtml(apiKey.name)}</td>
                        <td>${Utils.getTypeBadge(apiKey.type)}</td>
                        <td>${Utils.getStatusBadge(apiKey.is_active)}</td>
                        <td>
                            <small>${Utils.formatRelativeTime(apiKey.last_used_at)}</small><br>
                            <small class="text-muted">${apiKey.usage_count || 0} usos</small>
                        </td>
                        <td class="text-nowrap">
                            ${apiKey.is_active ? `
                                <button class="btn btn-sm btn-warning btn-revoke" data-id="${apiKey.id}" title="Revocar">
                                    <i class="fas fa-ban"></i>
                                </button>
                            ` : `
                                <button class="btn btn-sm btn-success btn-activate" data-id="${apiKey.id}" title="Activar">
                                    <i class="fas fa-check"></i>
                                </button>
                            `}
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${apiKey.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join(''));
            
            this.attachRowEvents();
        },
        
        attachRowEvents() {
            $('.btn-copy').off('click').on('click', function() {
                Utils.copyToClipboard($(this).data('key'));
            });
            
            $('.btn-revoke').off('click').on('click', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: '¿Revocar API Key?',
                    text: 'La API Key dejará de funcionar inmediatamente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, revocar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        API.revokeKey(id);
                    }
                });
            });
            
            $('.btn-activate').off('click').on('click', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: '¿Activar API Key?',
                    text: 'La API Key volverá a funcionar.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, activar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        API.activateKey(id);
                    }
                });
            });
            
            $('.btn-delete').off('click').on('click', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: '¿Eliminar API Key?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        API.deleteKey(id);
                    }
                });
            });
        },
        
        updatePagination() {
            const meta = state.meta;
            const links = state.links;
            
            const from = meta && meta.total > 0 ? ((meta.current_page - 1) * meta.per_page) + 1 : 0;
            const to = meta ? Math.min(meta.current_page * meta.per_page, meta.total) : 0;
            $('#paginationInfo').text(`Mostrando ${from} a ${to} de ${meta?.total || 0}`);
            
            const $controls = $('#paginationControls');
            $controls.empty();
            
            if (!meta || meta.last_page <= 1) return;
            
            $controls.append(`
                <li class="page-item ${!links?.prev ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-action="prev"><i class="fas fa-chevron-left"></i></a>
                </li>
            `);
            
            for (let i = 1; i <= meta.last_page; i++) {
                $controls.append(`
                    <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }
            
            $controls.append(`
                <li class="page-item ${!links?.next ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-action="next"><i class="fas fa-chevron-right"></i></a>
                </li>
            `);
            
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
                
                API.loadApiKeys();
            });
        },
        
        updateFilterSelection() {
            $('.small-box').removeClass('elevation-3');
            const status = state.filters.status;
            if (status === 'active') {
                $('#stat-active').addClass('elevation-3');
            } else if (status === 'revoked') {
                $('#stat-revoked').addClass('elevation-3');
            } else {
                $('#stat-total').addClass('elevation-3');
            }
        }
    };

    // =========================================================================
    // EVENT HANDLERS
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
                API.loadApiKeys();
            }, CONFIG.DEBOUNCE_DELAY);
        });
        
        // Filter handlers
        $('#statusFilter').on('change', function() {
            state.filters.status = $(this).val();
            state.currentPage = 1;
            UI.updateFilterSelection();
            API.loadApiKeys();
        });
        
        $('#companyFilter').on('change', function() {
            state.filters.company_id = $(this).val();
            state.currentPage = 1;
            API.loadApiKeys();
        });
        
        $('#typeFilter').on('change', function() {
            state.filters.type = $(this).val();
            state.currentPage = 1;
            API.loadApiKeys();
        });
        
        // Reset filters
        $('#btnResetFilters').on('click', function() {
            state.filters = {
                status: 'active',
                company_id: '',
                type: '',
                search: '',
                order_by: 'created_at',
                order_direction: 'desc'
            };
            state.currentPage = 1;
            
            $('#searchInput').val('');
            $('#statusFilter').val('active');
            $('#companyFilter').val('');
            $('#typeFilter').val('');
            
            UI.updateFilterSelection();
            API.loadApiKeys();
        });
        
        // Refresh button
        $('#btnRefresh').on('click', () => API.loadApiKeys());
        
        // Create button
        $('#btnCreateApiKey').on('click', function() {
            if (typeof CreateApiKeyModal !== 'undefined') {
                CreateApiKeyModal.open();
            }
        });
        
        // Small-box click handlers
        $('#stat-total').on('click', () => {
            state.filters.status = '';
            state.currentPage = 1;
            $('#statusFilter').val('');
            UI.updateFilterSelection();
            API.loadApiKeys();
        });
        
        $('#stat-active').on('click', () => {
            state.filters.status = 'active';
            state.currentPage = 1;
            $('#statusFilter').val('active');
            UI.updateFilterSelection();
            API.loadApiKeys();
        });
        
        $('#stat-revoked').on('click', () => {
            state.filters.status = 'revoked';
            state.currentPage = 1;
            $('#statusFilter').val('revoked');
            UI.updateFilterSelection();
            API.loadApiKeys();
        });
        
        // Refresh on API Key created
        $(document).on('apiKeyCreated', function() {
            API.loadApiKeys();
        });
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================
    $(document).ready(function() {
        console.log('[API Keys] DOM Ready, initializing...');
        initEvents();
        UI.updateFilterSelection();
        API.loadCompanies();
        API.loadApiKeys();
    });

})();
</script>
@endpush
