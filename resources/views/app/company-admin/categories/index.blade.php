@extends('layouts.authenticated')

@section('title', 'Gestión de Categorías')

@section('content_header', 'Gestión de Categorías de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Categorías</li>
@endsection

@section('content')
{{-- Fila: Estadísticas Rápidas (Clickeables como filtros) --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="info-box bg-light" style="cursor: pointer;" data-filter="" id="infoBoxAll">
            <span class="info-box-icon bg-primary"><i class="fas fa-folder"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total de Categorías</span>
                <span class="info-box-number" id="totalCategories">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light elevation-2" style="cursor: pointer;" data-filter="active" id="infoBoxActive">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Categorías Activas</span>
                <span class="info-box-number" id="activeCategories">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light" style="cursor: pointer;" data-filter="inactive" id="infoBoxInactive">
            <span class="info-box-icon bg-warning"><i class="fas fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Categorías Inactivas</span>
                <span class="info-box-number" id="inactiveCategories">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-info"><i class="fas fa-ticket-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tickets Activos</span>
                <span class="info-box-number" id="totalActiveTickets">0</span>
            </div>
        </div>
    </div>
</div>

{{-- Tarjeta Principal: Lista de Categorías --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Categorías de Tickets</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" id="btnCreateCategory">
                <i class="fas fa-plus"></i> Nueva Categoría
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Sección de Filtros --}}
        <div class="p-3 border-bottom">
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="searchInput" placeholder="Buscar por nombre o descripción...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <select class="form-control" id="statusFilter">
                            <option value="">Todos los Estados</option>
                            <option value="active" selected>Activas</option>
                            <option value="inactive">Inactivas</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-default" id="btnResetFilters">
                        <i class="fas fa-eraser"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        {{-- Spinner de Carga --}}
        <div id="loadingSpinner" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando categorías...</p>
        </div>

        {{-- Contenedor de Tabla --}}
        <div id="tableContainer" style="display: none;">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Nombre</th>
                            <th style="width: 35%;">Descripción</th>
                            <th style="width: 10%;" class="text-center">Estado</th>
                            <th style="width: 15%;" class="text-center">Tickets Activos</th>
                            <th style="width: 15%;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesTableBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No hay categorías registradas</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mensaje de Error --}}
        <div id="errorMessage" class="alert alert-danger m-3" style="display: none;">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>
    </div>

    {{-- Footer: Paginación --}}
    <div class="card-footer border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" id="paginationInfo">Mostrando 0 de 0</small>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                </ul>
            </nav>
        </div>
    </div>

    <div class="card-footer text-muted">
        <small><i class="fas fa-info-circle"></i> Las categorías son utilizadas para organizar los tickets de su empresa.</small>
    </div>
</div>

{{-- Modal: Crear Categoría --}}
<div class="modal fade" id="createCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nueva Categoría
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createCategoryForm">
                <div class="modal-body">
                    <!-- Alert de error dentro del modal -->
                    <div id="createErrorAlert" class="alert alert-danger" role="alert" style="display: none;">
                        <button type="button" class="close" aria-label="Close" onclick="$('#createErrorAlert').hide()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><i class="icon fas fa-ban"></i> Error:</strong> <span id="createErrorMessage"></span>
                    </div>

                    <div class="form-group">
                        <label for="createName">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="createName" name="name" placeholder="Ej: Soporte Técnico" required maxlength="100">
                        <small class="form-text text-muted">Mínimo 3 caracteres, máximo 100</small>
                    </div>
                    <div class="form-group">
                        <label for="createDescription">Descripción</label>
                        <textarea class="form-control" id="createDescription" name="description" rows="3" placeholder="Describe el propósito de esta categoría..." maxlength="500"></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnCreateSubmit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Editar Categoría --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Editar Categoría
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editCategoryForm">
                <input type="hidden" id="editCategoryId" name="id">
                <div class="modal-body">
                    <!-- Alert de error dentro del modal -->
                    <div id="editErrorAlert" class="alert alert-danger" role="alert" style="display: none;">
                        <button type="button" class="close" aria-label="Close" onclick="$('#editErrorAlert').hide()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><i class="icon fas fa-ban"></i> Error:</strong> <span id="editErrorMessage"></span>
                    </div>

                    <div class="form-group">
                        <label for="editName">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editName" name="name" required maxlength="100">
                        <small class="form-text text-muted">Mínimo 3 caracteres, máximo 100</small>
                    </div>
                    <div class="form-group">
                        <label for="editDescription">Descripción</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3" maxlength="500"></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label for="editIsActive">Estado</label>
                        <div class="custom-control custom-switch custom-switch-on-success custom-switch-off-secondary">
                            <input type="checkbox" class="custom-control-input" id="editIsActive" name="is_active">
                            <label class="custom-control-label" for="editIsActive">
                                Activa
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnEditSubmit" class="btn btn-info">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Input Hidden: Company ID (obtenido del JWT via controlador) --}}
<input type="hidden" id="companyId" value="{{ $companyId }}">

@endsection

@push('scripts')
    {{-- SweetAlert2 para confirmaciones críticas (eliminación con tickets activos) --}}
    @section('plugins.Sweetalert2', true)
    <script>
    (function() {
        'use strict';

        console.log('[Categories] Initializing...');

        // ========== CONFIGURATION ==========
        const CONFIG = {
            API_BASE: '/api',
            TOAST_DELAY: 3000,
            DEBOUNCE_DELAY: 300
        };

        // ========== STATE ==========
        const state = {
            companyId: null,
            currentPage: 1,
            currentFilter: 'active',
            currentSearch: '',
            categories: [],
            isLoading: false,
            isOperating: false
        };

        // ========== UTILITIES ==========
        const Utils = {
            /**
             * Get JWT token from localStorage
             */
            getToken() {
                return localStorage.getItem('access_token');
            },

            /**
             * Escape HTML to prevent XSS
             */
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },

            /**
             * Translate API errors to Spanish
             */
            translateError(error) {
                console.error('[Categories] API Error:', error);

                const status = error.response?.status;
                const data = error.response?.data;

                switch (status) {
                    case 401:
                        return 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
                    case 403:
                        return data?.message || 'No tienes permiso para realizar esta acción.';
                    case 404:
                        return 'La categoría no fue encontrada.';
                    case 422:
                        // Validation errors - extract all messages
                        if (data?.errors) {
                            const messages = Object.values(data.errors)
                                .flat()
                                .map(msg => this.translateValidationError(msg))
                                .join('. ');
                            return messages;
                        }
                        return data?.message || 'Error de validación.';
                    default:
                        return data?.message || 'Error al procesar la solicitud. Inténtalo nuevamente.';
                }
            },

            /**
             * Translate specific validation errors
             */
            translateValidationError(message) {
                const translations = {
                    'The name has already been taken': 'Ya existe una categoría con este nombre en tu empresa',
                    'The name field is required': 'El nombre es obligatorio',
                    'The name must be at least 3 characters': 'El nombre debe tener al menos 3 caracteres',
                    'The name must not be greater than 100 characters': 'El nombre no puede exceder 100 caracteres',
                    'The description must not be greater than 500 characters': 'La descripción no puede exceder 500 caracteres',
                    'A category with this name already exists in your company': 'Ya existe una categoría con este nombre en tu empresa'
                };

                return translations[message] || message;
            }
        };

        // ========== TOAST NOTIFICATIONS (AdminLTE v3) ==========
        const Toast = {
            /**
             * Show success toast
             */
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

            /**
             * Show error toast
             */
            error(message, title = 'Error') {
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: title,
                    body: message,
                    autohide: true,
                    delay: CONFIG.TOAST_DELAY + 2000, // Errors stay longer
                    icon: 'fas fa-exclamation-circle'
                });
            },

            /**
             * Show warning toast
             */
            warning(message, title = 'Advertencia') {
                $(document).Toasts('create', {
                    class: 'bg-warning',
                    title: title,
                    body: message,
                    autohide: true,
                    delay: CONFIG.TOAST_DELAY,
                    icon: 'fas fa-exclamation-triangle'
                });
            },

            /**
             * Show info toast
             */
            info(message, title = 'Información') {
                $(document).Toasts('create', {
                    class: 'bg-info',
                    title: title,
                    body: message,
                    autohide: true,
                    delay: CONFIG.TOAST_DELAY,
                    icon: 'fas fa-info-circle'
                });
            }
        };

        // ========== API ==========
        const API = {
            /**
             * Load global statistics (all categories, unfiltered)
             */
            async loadStatistics() {
                try {
                    const params = new URLSearchParams({
                        company_id: state.companyId,
                        per_page: 100, // Get all categories (max allowed)
                        page: 1
                    });
                    // No is_active filter - get ALL categories

                    const response = await fetch(`${CONFIG.API_BASE}/tickets/categories?${params}`, {
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
                    const allCategories = data.data || [];

                    // Calculate global statistics
                    const total = data.meta.total || 0;
                    const active = allCategories.filter(c => c.is_active).length;
                    const inactive = total - active;
                    const tickets = allCategories.reduce((sum, c) => sum + (c.active_tickets_count || 0), 0);

                    UI.updateStatisticsBoxes(total, active, inactive, tickets);

                } catch (error) {
                    console.error('[Categories] Error loading statistics:', error);
                    // Don't show error toast, statistics are secondary
                }
            },

            /**
             * Load categories from API (paginated, filtered)
             */
            async loadCategories() {
                if (state.isLoading || state.isOperating) {
                    console.log('[Categories] Load blocked - operation in progress');
                    return;
                }

                state.isLoading = true;
                UI.showLoading();

                try {
                    const params = new URLSearchParams({
                        company_id: state.companyId,
                        page: state.currentPage,
                        per_page: 10
                    });

                    if (state.currentFilter && state.currentFilter !== '') {
                        params.append('is_active', state.currentFilter === 'active' ? 'true' : 'false');
                    }

                    const response = await fetch(`${CONFIG.API_BASE}/tickets/categories?${params}`, {
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
                    state.categories = data.data || [];

                    UI.hideLoading();
                    UI.renderTable(state.categories);
                    UI.updatePagination(data.meta, data.links);

                    // Load global statistics separately
                    this.loadStatistics();

                } catch (error) {
                    UI.hideLoading();
                    UI.showError(Utils.translateError(error));
                } finally {
                    state.isLoading = false;
                }
            },

            /**
             * Create category
             */
            async createCategory(formData) {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/tickets/categories`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${Utils.getToken()}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw { response: { status: response.status, data: data } };
                    }

                    // Optimistic update
                    state.categories.unshift(data.data);
                    return { success: true, data: data.data };

                } catch (error) {
                    return { success: false, message: Utils.translateError(error) };
                }
            },

            /**
             * Update category
             */
            async updateCategory(categoryId, formData) {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/tickets/categories/${categoryId}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${Utils.getToken()}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw { response: { status: response.status, data: data } };
                    }

                    // Optimistic update
                    const index = state.categories.findIndex(c => c.id === categoryId);
                    if (index !== -1) {
                        state.categories[index] = { ...state.categories[index], ...data.data };
                    }

                    return { success: true, data: data.data };

                } catch (error) {
                    return { success: false, message: Utils.translateError(error) };
                }
            },

            /**
             * Delete category
             */
            async deleteCategory(categoryId) {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/tickets/categories/${categoryId}`, {
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

                    // Optimistic update
                    state.categories = state.categories.filter(c => c.id !== categoryId);
                    return { success: true };

                } catch (error) {
                    return { success: false, message: Utils.translateError(error) };
                }
            }
        };

        // ========== UI RENDERING ==========
        const UI = {
            /**
             * Show loading state
             */
            showLoading() {
                $('#loadingSpinner').show();
                $('#tableContainer').hide();
                $('#errorMessage').hide();
            },

            /**
             * Hide loading state
             */
            hideLoading() {
                $('#loadingSpinner').hide();
                $('#tableContainer').show();
            },

            /**
             * Show error in table area
             */
            showError(message) {
                $('#loadingSpinner').hide();
                $('#tableContainer').hide();
                $('#errorText').text(message);
                $('#errorMessage').show();
            },

            /**
             * Render categories table
             */
            renderTable(categories) {
                const tbody = $('#categoriesTableBody');

                if (!categories || categories.length === 0) {
                    tbody.html(`
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No hay categorías registradas</p>
                            </td>
                        </tr>
                    `);
                    return;
                }

                const rows = categories.map(cat => `
                    <tr data-id="${cat.id}">
                        <td><strong>${Utils.escapeHtml(cat.name)}</strong></td>
                        <td><span class="text-muted">${Utils.escapeHtml(cat.description || '—')}</span></td>
                        <td class="text-center">
                            ${cat.is_active
                                ? '<span class="badge badge-success">Activa</span>'
                                : '<span class="badge badge-warning">Inactiva</span>'
                            }
                        </td>
                        <td class="text-center">
                            <span class="badge badge-info">${cat.active_tickets_count || 0}</span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info btn-edit" data-id="${cat.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${cat.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                tbody.html(rows);
                this.attachRowEvents();
            },

            /**
             * Attach event listeners to table rows
             */
            attachRowEvents() {
                $('.btn-edit').off('click').on('click', function() {
                    Modals.openEdit($(this).data('id'));
                });

                $('.btn-delete').off('click').on('click', function() {
                    Modals.openDelete($(this).data('id'));
                });
            },

            /**
             * Update pagination
             */
            updatePagination(meta, links) {
                $('#paginationInfo').text(`Mostrando ${meta.from || 0} a ${meta.to || 0} de ${meta.total || 0}`);

                const controls = $('#paginationControls');
                controls.empty();

                if (meta.last_page === 1) return;

                // Previous button
                const prevDisabled = !links.prev ? 'disabled' : '';
                controls.append(`
                    <li class="page-item ${prevDisabled}">
                        <a class="page-link" href="#" data-action="prev">Anterior</a>
                    </li>
                `);

                // Page numbers
                for (let i = 1; i <= meta.last_page; i++) {
                    const active = i === meta.current_page ? 'active' : '';
                    controls.append(`
                        <li class="page-item ${active}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `);
                }

                // Next button
                const nextDisabled = !links.next ? 'disabled' : '';
                controls.append(`
                    <li class="page-item ${nextDisabled}">
                        <a class="page-link" href="#" data-action="next">Siguiente</a>
                    </li>
                `);

                // Attach pagination events
                controls.find('a').on('click', function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    const action = $(this).data('action');

                    if (page) {
                        state.currentPage = page;
                        API.loadCategories();
                    } else if (action === 'prev') {
                        state.currentPage--;
                        API.loadCategories();
                    } else if (action === 'next') {
                        state.currentPage++;
                        API.loadCategories();
                    }
                });
            },

            /**
             * Update statistics boxes with global data
             */
            updateStatisticsBoxes(total, active, inactive, tickets) {
                $('#totalCategories').text(total);
                $('#activeCategories').text(active);
                $('#inactiveCategories').text(inactive);
                $('#totalActiveTickets').text(tickets);
            },

            /**
             * Update info box selection visual
             */
            updateFilterSelection() {
                $('.info-box').removeClass('elevation-2');

                if (state.currentFilter === '' || state.currentFilter === 'all') {
                    $('#infoBoxAll').addClass('elevation-2');
                } else if (state.currentFilter === 'active') {
                    $('#infoBoxActive').addClass('elevation-2');
                } else if (state.currentFilter === 'inactive') {
                    $('#infoBoxInactive').addClass('elevation-2');
                }
            }
        };

        // ========== MODALS ==========
        const Modals = {
            /**
             * Open create modal
             */
            openCreate() {
                const $form = $('#createCategoryForm');
                $form[0].reset();

                // Reset validation
                const validator = $form.data('validator');
                if (validator) {
                    validator.resetForm();
                    $form.find('.is-invalid').removeClass('is-invalid');
                    $form.find('.form-text').show();
                }

                // Hide error alert
                $('#createErrorAlert').hide();

                // Reset button
                $('#btnCreateSubmit')
                    .prop('disabled', false)
                    .html('<i class="fas fa-save"></i> Crear Categoría');

                $('#createCategoryModal').modal('show');
            },

            /**
             * Open edit modal
             */
            openEdit(categoryId) {
                const category = state.categories.find(c => c.id === categoryId);
                if (!category) {
                    Toast.error('Categoría no encontrada');
                    return;
                }

                const $form = $('#editCategoryForm');
                $form[0].reset();

                // Reset validation
                const validator = $form.data('validator');
                if (validator) {
                    validator.resetForm();
                    $form.find('.is-invalid').removeClass('is-invalid');
                    $form.find('.form-text').show();
                }

                // Fill form
                $('#editCategoryId').val(category.id);
                $('#editName').val(category.name);
                $('#editDescription').val(category.description || '');
                $('#editIsActive').prop('checked', category.is_active);

                // Hide error alert
                $('#editErrorAlert').hide();

                // Reset button
                $('#btnEditSubmit')
                    .prop('disabled', false)
                    .html('<i class="fas fa-save"></i> Guardar Cambios');

                $('#editCategoryModal').modal('show');
            },

            /**
             * Open delete confirmation
             */
            openDelete(categoryId) {
                const category = state.categories.find(c => c.id === categoryId);
                if (!category) {
                    Toast.error('Categoría no encontrada');
                    return;
                }

                // Check for active tickets (keep SweetAlert for this descriptive error)
                if (category.active_tickets_count > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se puede eliminar',
                        html: `
                            <p>Esta categoría tiene <strong>${category.active_tickets_count} ticket(s) activo(s)</strong>.</p>
                            <p class="text-muted small">Cierra o resuelve estos tickets antes de eliminar la categoría.</p>
                        `,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }

                // Confirmation dialog (keep SweetAlert for confirmation)
                Swal.fire({
                    title: '¿Eliminar categoría?',
                    html: `
                        <p>¿Estás seguro de eliminar <strong>${Utils.escapeHtml(category.name)}</strong>?</p>
                        <p class="text-muted small">Esta acción no se puede deshacer.</p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        await this.performDelete(categoryId);
                    }
                });
            },

            /**
             * Perform delete operation
             */
            async performDelete(categoryId) {
                const result = await API.deleteCategory(categoryId);

                if (result.success) {
                    Toast.success('Categoría eliminada correctamente');

                    // Remove row from table
                    $(`tr[data-id="${categoryId}"]`).fadeOut(300, function() {
                        $(this).remove();

                        // Show empty message if no categories
                        if (state.categories.length === 0) {
                            UI.renderTable([]);
                        }
                    });

                    // Reload statistics
                    API.loadStatistics();
                } else {
                    Toast.error(result.message);
                }
            },

            /**
             * Handle create form submission
             */
            async handleCreate() {
                const $form = $('#createCategoryForm');
                if (!$form.valid()) return;

                const $btn = $('#btnCreateSubmit');
                const $errorAlert = $('#createErrorAlert');
                const $errorMsg = $('#createErrorMessage');

                // Start operation - hide any previous errors
                state.isOperating = true;
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
                $errorAlert.hide(); // Use hide() instead of addClass('d-none') for reliability

                const formData = {
                    name: $('#createName').val().trim(),
                    description: $('#createDescription').val().trim()
                };

                const result = await API.createCategory(formData);

                if (result.success) {
                    Toast.success('Categoría creada correctamente');

                    // Close modal after short delay
                    setTimeout(() => {
                        $('#createCategoryModal').modal('hide');
                        state.isOperating = false;
                    }, 300);

                    // Add new row to table
                    const tbody = $('#categoriesTableBody');
                    const emptyRow = tbody.find('td[colspan="5"]');
                    if (emptyRow.length) {
                        tbody.empty();
                    }

                    UI.renderTable(state.categories);

                    // Reload statistics
                    API.loadStatistics();
                } else {
                    // Show error in modal
                    $errorMsg.text(result.message);
                    $errorAlert.show(); // Use show() for reliability

                    // Re-enable button
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Crear Categoría');
                    state.isOperating = false;
                }
            },

            /**
             * Handle edit form submission
             */
            async handleEdit() {
                const $form = $('#editCategoryForm');
                if (!$form.valid()) return;

                const $btn = $('#btnEditSubmit');
                const $errorAlert = $('#editErrorAlert');
                const $errorMsg = $('#editErrorMessage');

                // Start operation - hide any previous errors
                state.isOperating = true;
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $errorAlert.hide(); // Use hide() instead of addClass('d-none') for reliability

                const categoryId = $('#editCategoryId').val();
                const formData = {
                    name: $('#editName').val().trim(),
                    description: $('#editDescription').val().trim(),
                    is_active: $('#editIsActive').prop('checked')
                };

                const result = await API.updateCategory(categoryId, formData);

                if (result.success) {
                    Toast.success('Categoría actualizada correctamente');

                    // Close modal after short delay
                    setTimeout(() => {
                        $('#editCategoryModal').modal('hide');
                        state.isOperating = false;
                    }, 300);

                    // Update table
                    UI.renderTable(state.categories);

                    // Reload statistics
                    API.loadStatistics();
                } else {
                    // Show error in modal
                    $errorMsg.text(result.message);
                    $errorAlert.show(); // Use show() for reliability

                    // Re-enable button
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
                    state.isOperating = false;
                }
            }
        };

        // ========== FORM VALIDATION ==========
        const Validation = {
            init() {
                if (typeof $.fn.validate === 'undefined') {
                    console.warn('[Categories] jQuery Validation not available');
                    return;
                }

                const config = {
                    errorElement: 'span',
                    errorClass: 'invalid-feedback',
                    errorPlacement: (error, element) => {
                        error.addClass('invalid-feedback');
                        element.closest('.form-group').append(error);
                    },
                    highlight: (element) => {
                        $(element).addClass('is-invalid');
                        $(element).closest('.form-group').find('.form-text').hide();
                    },
                    unhighlight: (element) => {
                        $(element).removeClass('is-invalid');
                        $(element).closest('.form-group').find('.form-text').show();
                    }
                };

                const rules = {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100
                    },
                    description: {
                        maxlength: 500
                    }
                };

                const messages = {
                    name: {
                        required: 'El nombre es obligatorio',
                        minlength: 'El nombre debe tener al menos 3 caracteres',
                        maxlength: 'El nombre no puede exceder 100 caracteres'
                    },
                    description: {
                        maxlength: 'La descripción no puede exceder 500 caracteres'
                    }
                };

                $('#createCategoryForm').validate({ ...config, rules, messages });
                $('#editCategoryForm').validate({ ...config, rules, messages });
            }
        };

        // ========== EVENT LISTENERS ==========
        const Events = {
            init() {
                // Create button
                $('#btnCreateCategory').on('click', () => Modals.openCreate());

                // Form submissions
                $('#btnCreateSubmit').on('click', (e) => {
                    e.preventDefault();
                    Modals.handleCreate();
                });

                $('#btnEditSubmit').on('click', (e) => {
                    e.preventDefault();
                    Modals.handleEdit();
                });

                // Filters
                $('#statusFilter').on('change', function() {
                    state.currentFilter = $(this).val();
                    state.currentPage = 1;
                    UI.updateFilterSelection();
                    API.loadCategories();
                });

                // Search with debounce
                let searchTimeout;
                $('#searchInput').on('input', function() {
                    clearTimeout(searchTimeout);
                    const search = $(this).val().toLowerCase();

                    searchTimeout = setTimeout(() => {
                        if (search) {
                            const filtered = state.categories.filter(c =>
                                c.name.toLowerCase().includes(search) ||
                                (c.description && c.description.toLowerCase().includes(search))
                            );
                            UI.renderTable(filtered);
                        } else {
                            UI.renderTable(state.categories);
                        }
                    }, CONFIG.DEBOUNCE_DELAY);
                });

                // Reset filters
                $('#btnResetFilters').on('click', () => {
                    state.currentFilter = 'active';
                    state.currentSearch = '';
                    state.currentPage = 1;
                    $('#statusFilter').val('active');
                    $('#searchInput').val('');
                    UI.updateFilterSelection();
                    API.loadCategories();
                });

                // Info boxes as filters
                $('#infoBoxAll').on('click', () => {
                    state.currentFilter = '';
                    state.currentPage = 1;
                    $('#statusFilter').val('');
                    UI.updateFilterSelection();
                    API.loadCategories();
                });

                $('#infoBoxActive').on('click', () => {
                    state.currentFilter = 'active';
                    state.currentPage = 1;
                    $('#statusFilter').val('active');
                    UI.updateFilterSelection();
                    API.loadCategories();
                });

                $('#infoBoxInactive').on('click', () => {
                    state.currentFilter = 'inactive';
                    state.currentPage = 1;
                    $('#statusFilter').val('inactive');
                    UI.updateFilterSelection();
                    API.loadCategories();
                });

                // Reset button states when modal closes
                $('#createCategoryModal').on('hidden.bs.modal', () => {
                    $('#btnCreateSubmit')
                        .prop('disabled', false)
                        .html('<i class="fas fa-save"></i> Crear Categoría');
                    state.isOperating = false;
                });

                $('#editCategoryModal').on('hidden.bs.modal', () => {
                    $('#btnEditSubmit')
                        .prop('disabled', false)
                        .html('<i class="fas fa-save"></i> Guardar Cambios');
                    state.isOperating = false;
                });
            }
        };

        // ========== INITIALIZATION ==========
        function init() {
            // Get company ID
            state.companyId = $('#companyId').val();
            if (!state.companyId) {
                console.error('[Categories] Company ID not found');
                Toast.error('Error de configuración: ID de empresa no encontrado');
                return;
            }

            console.log('[Categories] Company ID:', state.companyId);

            // Initialize components
            Validation.init();
            Events.init();
            UI.updateFilterSelection();

            // Load initial data
            API.loadCategories();

            console.log('[Categories] ✓ Initialized successfully');
        }

        // Wait for dependencies and initialize
        if (typeof jQuery !== 'undefined' && typeof Swal !== 'undefined') {
            $(document).ready(init);
        } else {
            let attempts = 0;
            const checkDeps = setInterval(() => {
                attempts++;
                if (typeof jQuery !== 'undefined' && typeof Swal !== 'undefined') {
                    clearInterval(checkDeps);
                    $(document).ready(init);
                } else if (attempts > 100) {
                    clearInterval(checkDeps);
                    console.error('[Categories] Dependencies not loaded after 10 seconds');
                }
            }, 100);
        }
    })();
    </script>
@endpush
