@extends('layouts.authenticated')

@section('title', 'Gestión de Áreas')

@section('content_header', 'Gestión de Áreas')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Áreas</li>
@endsection

@section('content')

{{-- Loading State (Initial Check) --}}
<div id="initialLoading" class="text-center py-5">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="sr-only">Cargando...</span>
    </div>
    <p class="text-muted mt-3">Verificando configuración de áreas...</p>
</div>

{{-- Feature Disabled View --}}
<div id="featureDisabled" style="display: none;">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-outline card-warning">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Funcionalidad de Áreas Desactivada
                    </h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-building fa-5x text-muted mb-3"></i>
                        <h4>Las áreas están desactivadas para tu empresa</h4>
                        <p class="text-muted">
                            Las áreas permiten organizar los tickets por departamentos o equipos dentro de tu empresa.
                            Esta funcionalidad es opcional y está diseñada para empresas medianas y grandes.
                        </p>
                    </div>

                    <div class="callout callout-info">
                        <h5><i class="icon fas fa-info"></i> ¿Qué son las áreas?</h5>
                        <p>
                            Las áreas te permiten clasificar tickets por departamento (ej: Ventas, Soporte, Recursos Humanos).
                            Esto facilita la asignación de tickets a equipos específicos y mejora la organización interna.
                        </p>
                        <ul class="mb-0">
                            <li>Organiza tickets por departamento o equipo</li>
                            <li>Facilita la asignación y seguimiento</li>
                            <li>Mejora la colaboración entre equipos</li>
                            <li>Genera reportes por área</li>
                        </ul>
                    </div>

                    <div class="callout callout-info">
                        <h5><i class="icon fas fa-info-circle"></i> Nota</h5>
                        <p class="mb-0">
                            Una vez activada, podrás gestionar áreas libremente. Si en el futuro no las necesitas,
                            puedes desactivar esta funcionalidad desde la sección de Configuración.
                        </p>
                    </div>

                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-warning btn-lg" id="btnEnableFeature">
                            <i class="fas fa-toggle-on"></i> Activar Funcionalidad de Áreas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Feature Enabled View (CRUD) --}}
<div id="featureEnabled" style="display: none;">
    {{-- Fila: Estadísticas Rápidas --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-box bg-light elevation-2" style="cursor: pointer;" data-filter="" id="infoBoxAll">
                <span class="info-box-icon bg-primary"><i class="fas fa-building"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total de Áreas</span>
                    <span class="info-box-number" id="totalAreas">0</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-light" style="cursor: pointer;" data-filter="active" id="infoBoxActive">
                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Áreas Activas</span>
                    <span class="info-box-number" id="activeAreas">0</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-light" style="cursor: pointer;" data-filter="inactive" id="infoBoxInactive">
                <span class="info-box-icon bg-warning"><i class="fas fa-times-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Áreas Inactivas</span>
                    <span class="info-box-number" id="inactiveAreas">0</span>
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

    {{-- Tarjeta Principal: Lista de Áreas --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Áreas de la Empresa</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" id="btnCreateArea">
                    <i class="fas fa-plus"></i> Nueva Área
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
                <p class="text-muted mt-3">Cargando áreas...</p>
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
                        <tbody id="areasTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No hay áreas registradas</p>
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
            <small><i class="fas fa-info-circle"></i> Las áreas permiten organizar los tickets por departamento o equipo dentro de tu empresa.</small>
        </div>
    </div>
</div>

{{-- Modal: Confirmar Activación de Funcionalidad --}}
<div class="modal fade" id="confirmEnableModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Activación
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="confirmEnableForm">
                <div class="modal-body">
                    <div class="callout callout-info">
                        <h5><i class="icon fas fa-info-circle"></i> Confirmación requerida</h5>
                        <p>
                            Estás a punto de activar la funcionalidad de áreas para tu empresa.
                            Esta acción habilitará nuevas opciones de organización para tus tickets.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="confirmText">Para confirmar, escribe <strong>CONFIRMAR</strong> en mayúsculas:</label>
                        <input type="text" class="form-control" id="confirmText" name="confirmText"
                               placeholder="Escribe CONFIRMAR" autocomplete="off" required>
                        <small class="form-text text-muted">Debes escribir exactamente: CONFIRMAR (en mayúsculas)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" id="btnConfirmEnable" class="btn btn-warning" disabled>
                        <i class="fas fa-toggle-on"></i> Activar Áreas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Crear Área --}}
<div class="modal fade" id="createAreaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nueva Área
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createAreaForm">
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
                        <input type="text" class="form-control" id="createName" name="name"
                               placeholder="Ej: Soporte Técnico" required minlength="2" maxlength="100">
                        <small class="form-text text-muted">Mínimo 2 caracteres, máximo 100</small>
                    </div>
                    <div class="form-group">
                        <label for="createDescription">Descripción</label>
                        <textarea class="form-control" id="createDescription" name="description" rows="3"
                                  placeholder="Describe el propósito de esta área..." maxlength="500"></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres (opcional)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" id="btnCreateSubmit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Área
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Editar Área --}}
<div class="modal fade" id="editAreaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Editar Área
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editAreaForm">
                <input type="hidden" id="editAreaId" name="id">
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
                        <input type="text" class="form-control" id="editName" name="name" required minlength="2" maxlength="100">
                        <small class="form-text text-muted">Mínimo 2 caracteres, máximo 100</small>
                    </div>
                    <div class="form-group">
                        <label for="editDescription">Descripción</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3" maxlength="500"></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres (opcional)</small>
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
                    <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" id="btnEditSubmit" class="btn btn-info">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Input Hidden: Company ID --}}
<input type="hidden" id="companyId" value="{{ $companyId }}">

@endsection

@push('scripts')
    {{-- SweetAlert2 para confirmaciones críticas --}}
    @section('plugins.Sweetalert2', true)
    <script>
    (function() {
        'use strict';

        console.log('[Areas] Initializing...');

        // ========== CONFIGURATION ==========
        const CONFIG = {
            API_BASE: '/api',
            TOAST_DELAY: 3000,
            DEBOUNCE_DELAY: 300,
            CONFIRMATION_TEXT: 'CONFIRMAR'
        };

        // ========== STATE ==========
        const state = {
            companyId: null,
            featureEnabled: null,
            currentPage: 1,
            currentFilter: 'active',
            currentSearch: '',
            areas: [],
            isLoading: false,
            isOperating: false
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

            translateError(error) {
                console.error('[Areas] API Error:', error);
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
                        return 'El área no fue encontrada.';
                    case 422:
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

            translateValidationError(message) {
                const translations = {
                    'The name has already been taken': 'Ya existe un área con este nombre en tu empresa',
                    'The name field is required': 'El nombre es obligatorio',
                    'The name must be at least 2 characters': 'El nombre debe tener al menos 2 caracteres',
                    'The name must not be greater than 100 characters': 'El nombre no puede exceder 100 caracteres',
                    'The description must not be greater than 500 characters': 'La descripción no puede exceder 500 caracteres'
                };
                return translations[message] || message;
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
            async checkFeatureStatus() {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/companies/me/settings/areas-enabled`, {
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
                    return { success: true, enabled: data.data.areas_enabled };
                } catch (error) {
                    return { success: false, message: Utils.translateError(error) };
                }
            },

            async enableFeature() {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/companies/me/settings/areas-enabled`, {
                        method: 'PATCH',
                        headers: {
                            'Authorization': `Bearer ${Utils.getToken()}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ enabled: true })
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

            async loadStatistics() {
                try {
                    const params = new URLSearchParams({
                        company_id: state.companyId,
                        per_page: 100,
                        page: 1
                    });

                    const response = await fetch(`${CONFIG.API_BASE}/areas?${params}`, {
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
                    const allAreas = data.data || [];
                    const total = data.meta.total || 0;
                    const active = allAreas.filter(a => a.is_active).length;
                    const inactive = total - active;
                    const tickets = allAreas.reduce((sum, a) => sum + (a.active_tickets_count || 0), 0);

                    UI.updateStatisticsBoxes(total, active, inactive, tickets);
                } catch (error) {
                    console.error('[Areas] Error loading statistics:', error);
                }
            },

            async loadAreas() {
                if (state.isLoading || state.isOperating) {
                    console.log('[Areas] Load blocked - operation in progress');
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

                    const response = await fetch(`${CONFIG.API_BASE}/areas?${params}`, {
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
                    state.areas = data.data || [];

                    UI.hideLoading();
                    UI.renderTable(state.areas);
                    UI.updatePagination(data.meta, data.links);
                    this.loadStatistics();
                } catch (error) {
                    UI.hideLoading();
                    UI.showError(Utils.translateError(error));
                } finally {
                    state.isLoading = false;
                }
            },

            async createArea(formData) {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/areas`, {
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

                    state.areas.unshift(data.data);
                    return { success: true, data: data.data };
                } catch (error) {
                    return { success: false, message: Utils.translateError(error) };
                }
            },

            async updateArea(areaId, formData) {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/areas/${areaId}`, {
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

                    const index = state.areas.findIndex(a => a.id === areaId);
                    if (index !== -1) {
                        state.areas[index] = { ...state.areas[index], ...data.data };
                    }

                    return { success: true, data: data.data };
                } catch (error) {
                    return { success: false, message: Utils.translateError(error) };
                }
            },

            async deleteArea(areaId) {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/areas/${areaId}`, {
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

                    state.areas = state.areas.filter(a => a.id !== areaId);
                    return { success: true };
                } catch (error) {
                    return { success: false, message: Utils.translateError(error) };
                }
            }
        };

        // ========== UI RENDERING ==========
        const UI = {
            showInitialLoading() {
                $('#initialLoading').show();
                $('#featureDisabled').hide();
                $('#featureEnabled').hide();
            },

            showFeatureDisabled() {
                $('#initialLoading').hide();
                $('#featureDisabled').show();
                $('#featureEnabled').hide();
            },

            showFeatureEnabled() {
                $('#initialLoading').hide();
                $('#featureDisabled').hide();
                $('#featureEnabled').show();
            },

            showLoading() {
                $('#loadingSpinner').show();
                $('#tableContainer').hide();
                $('#errorMessage').hide();
            },

            hideLoading() {
                $('#loadingSpinner').hide();
                $('#tableContainer').show();
            },

            showError(message) {
                $('#loadingSpinner').hide();
                $('#tableContainer').hide();
                $('#errorText').text(message);
                $('#errorMessage').show();
            },

            renderTable(areas) {
                const tbody = $('#areasTableBody');

                if (!areas || areas.length === 0) {
                    tbody.html(`
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No hay áreas registradas</p>
                            </td>
                        </tr>
                    `);
                    return;
                }

                const rows = areas.map(area => `
                    <tr data-id="${area.id}">
                        <td><strong>${Utils.escapeHtml(area.name)}</strong></td>
                        <td><span class="text-muted">${Utils.escapeHtml(area.description || '—')}</span></td>
                        <td class="text-center">
                            ${area.is_active
                                ? '<span class="badge badge-success">Activa</span>'
                                : '<span class="badge badge-warning">Inactiva</span>'
                            }
                        </td>
                        <td class="text-center">
                            <span class="badge badge-info">${area.active_tickets_count || 0}</span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info btn-edit" data-id="${area.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${area.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                tbody.html(rows);
                this.attachRowEvents();
            },

            attachRowEvents() {
                $('.btn-edit').off('click').on('click', function() {
                    Modals.openEdit($(this).data('id'));
                });

                $('.btn-delete').off('click').on('click', function() {
                    Modals.openDelete($(this).data('id'));
                });
            },

            updatePagination(meta, links) {
                $('#paginationInfo').text(`Mostrando ${meta.from || 0} a ${meta.to || 0} de ${meta.total || 0}`);

                const controls = $('#paginationControls');
                controls.empty();

                if (meta.last_page === 1) return;

                const prevDisabled = !links.prev ? 'disabled' : '';
                controls.append(`
                    <li class="page-item ${prevDisabled}">
                        <a class="page-link" href="#" data-action="prev">Anterior</a>
                    </li>
                `);

                for (let i = 1; i <= meta.last_page; i++) {
                    const active = i === meta.current_page ? 'active' : '';
                    controls.append(`
                        <li class="page-item ${active}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `);
                }

                const nextDisabled = !links.next ? 'disabled' : '';
                controls.append(`
                    <li class="page-item ${nextDisabled}">
                        <a class="page-link" href="#" data-action="next">Siguiente</a>
                    </li>
                `);

                controls.find('a').on('click', function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    const action = $(this).data('action');

                    if (page) {
                        state.currentPage = page;
                        API.loadAreas();
                    } else if (action === 'prev') {
                        state.currentPage--;
                        API.loadAreas();
                    } else if (action === 'next') {
                        state.currentPage++;
                        API.loadAreas();
                    }
                });
            },

            updateStatisticsBoxes(total, active, inactive, tickets) {
                $('#totalAreas').text(total);
                $('#activeAreas').text(active);
                $('#inactiveAreas').text(inactive);
                $('#totalActiveTickets').text(tickets);
            },

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
            openEnableConfirmation() {
                console.log('[Areas] Opening enable confirmation modal');

                const $form = $('#confirmEnableForm');
                const $confirmText = $('#confirmText');
                const $btnConfirmEnable = $('#btnConfirmEnable');

                $form[0].reset();
                $confirmText.val('');
                $btnConfirmEnable.prop('disabled', true);

                $('#confirmEnableModal').modal('show');
            },

            async handleEnableFeature() {
                const $btn = $('#btnConfirmEnable');
                const originalHtml = $btn.html();

                if (state.isOperating) return;
                state.isOperating = true;

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Activando...');

                const result = await API.enableFeature();

                if (result.success) {
                    Toast.success('Funcionalidad de áreas activada correctamente');

                    setTimeout(() => {
                        $('#confirmEnableModal').modal('hide');
                        state.featureEnabled = true;
                        state.isOperating = false; // Reset BEFORE loading areas
                        UI.showFeatureEnabled();
                        UI.updateFilterSelection();
                        API.loadAreas();
                    }, 500);
                } else {
                    Toast.error(result.message);
                    $btn.prop('disabled', false).html(originalHtml);
                    state.isOperating = false;
                }
            },

            openCreate() {
                const $form = $('#createAreaForm');
                $form[0].reset();

                const validator = $form.data('validator');
                if (validator) {
                    validator.resetForm();
                    $form.find('.is-invalid').removeClass('is-invalid');
                    $form.find('.form-text').show();
                }

                $('#createErrorAlert').hide();
                $('#btnCreateSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Área');
                $('#createAreaModal').modal('show');
            },

            openEdit(areaId) {
                const area = state.areas.find(a => a.id === areaId);
                if (!area) {
                    Toast.error('Área no encontrada');
                    return;
                }

                const $form = $('#editAreaForm');
                $form[0].reset();

                const validator = $form.data('validator');
                if (validator) {
                    validator.resetForm();
                    $form.find('.is-invalid').removeClass('is-invalid');
                    $form.find('.form-text').show();
                }

                $('#editAreaId').val(area.id);
                $('#editName').val(area.name);
                $('#editDescription').val(area.description || '');
                $('#editIsActive').prop('checked', area.is_active);

                $('#editErrorAlert').hide();
                $('#btnEditSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
                $('#editAreaModal').modal('show');
            },

            openDelete(areaId) {
                const area = state.areas.find(a => a.id === areaId);
                if (!area) {
                    Toast.error('Área no encontrada');
                    return;
                }

                if (area.active_tickets_count > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se puede eliminar',
                        html: `
                            <p>Esta área tiene <strong>${area.active_tickets_count} ticket(s) activo(s)</strong>.</p>
                            <p class="text-muted small">Cierra o resuelve estos tickets antes de eliminar el área.</p>
                        `,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }

                Swal.fire({
                    title: '¿Eliminar área?',
                    html: `
                        <p>¿Estás seguro de eliminar <strong>${Utils.escapeHtml(area.name)}</strong>?</p>
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
                        await this.performDelete(areaId);
                    }
                });
            },

            async performDelete(areaId) {
                const result = await API.deleteArea(areaId);

                if (result.success) {
                    Toast.success('Área eliminada correctamente');

                    $(`tr[data-id="${areaId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        if (state.areas.length === 0) {
                            UI.renderTable([]);
                        }
                    });

                    API.loadStatistics();
                } else {
                    Toast.error(result.message);
                }
            },

            async handleCreate(e) {
                e.preventDefault();

                const $form = $('#createAreaForm');
                if (!$form.valid()) return;

                const $btn = $('#btnCreateSubmit');
                const $errorAlert = $('#createErrorAlert');
                const $errorMsg = $('#createErrorMessage');

                if (state.isOperating) return;
                state.isOperating = true;

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
                $errorAlert.hide();

                const formData = {
                    name: $('#createName').val().trim(),
                    description: $('#createDescription').val().trim() || undefined
                };

                const result = await API.createArea(formData);

                if (result.success) {
                    Toast.success('Área creada correctamente');

                    setTimeout(() => {
                        $('#createAreaModal').modal('hide');
                        state.isOperating = false;
                    }, 300);

                    const tbody = $('#areasTableBody');
                    if (tbody.find('td[colspan="5"]').length) {
                        tbody.empty();
                    }

                    UI.renderTable(state.areas);
                    API.loadStatistics();
                } else {
                    $errorMsg.text(result.message);
                    $errorAlert.show();
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Crear Área');
                    state.isOperating = false;
                }
            },

            async handleEdit(e) {
                e.preventDefault();

                const $form = $('#editAreaForm');
                if (!$form.valid()) return;

                const $btn = $('#btnEditSubmit');
                const $errorAlert = $('#editErrorAlert');
                const $errorMsg = $('#editErrorMessage');

                if (state.isOperating) return;
                state.isOperating = true;

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $errorAlert.hide();

                const areaId = $('#editAreaId').val();
                const formData = {
                    name: $('#editName').val().trim(),
                    description: $('#editDescription').val().trim() || undefined,
                    is_active: $('#editIsActive').prop('checked')
                };

                const result = await API.updateArea(areaId, formData);

                if (result.success) {
                    Toast.success('Área actualizada correctamente');

                    setTimeout(() => {
                        $('#editAreaModal').modal('hide');
                        state.isOperating = false;
                    }, 300);

                    UI.renderTable(state.areas);
                    API.loadStatistics();
                } else {
                    $errorMsg.text(result.message);
                    $errorAlert.show();
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
                    state.isOperating = false;
                }
            }
        };

        // ========== FORM VALIDATION ==========
        const Validation = {
            init() {
                if (typeof $.fn.validate === 'undefined') {
                    console.warn('[Areas] jQuery Validation not available');
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
                        minlength: 2,
                        maxlength: 100
                    },
                    description: {
                        maxlength: 500
                    }
                };

                const messages = {
                    name: {
                        required: 'El nombre es obligatorio',
                        minlength: 'El nombre debe tener al menos 2 caracteres',
                        maxlength: 'El nombre no puede exceder 100 caracteres'
                    },
                    description: {
                        maxlength: 'La descripción no puede exceder 500 caracteres'
                    }
                };

                $('#createAreaForm').validate({ ...config, rules, messages });
                $('#editAreaForm').validate({ ...config, rules, messages });
            }
        };

        // ========== EVENT LISTENERS ==========
        const Events = {
            init() {
                // Enable feature button
                $('#btnEnableFeature').on('click', () => Modals.openEnableConfirmation());

                // Confirmation text validation
                $(document).on('input', '#confirmText', function() {
                    const isValid = $(this).val().trim() === CONFIG.CONFIRMATION_TEXT;
                    $('#btnConfirmEnable').prop('disabled', !isValid);
                });

                // Confirm enable form
                $('#confirmEnableForm').on('submit', (e) => {
                    e.preventDefault();
                    Modals.handleEnableFeature();
                });

                // Create button
                $('#btnCreateArea').on('click', () => Modals.openCreate());

                // Form submissions
                $('#createAreaForm').on('submit', (e) => Modals.handleCreate(e));
                $('#editAreaForm').on('submit', (e) => Modals.handleEdit(e));

                // Filters
                $('#statusFilter').on('change', function() {
                    state.currentFilter = $(this).val();
                    state.currentPage = 1;
                    UI.updateFilterSelection();
                    API.loadAreas();
                });

                // Search with debounce
                let searchTimeout;
                $('#searchInput').on('input', function() {
                    clearTimeout(searchTimeout);
                    const search = $(this).val().toLowerCase();

                    searchTimeout = setTimeout(() => {
                        if (search) {
                            const filtered = state.areas.filter(a =>
                                a.name.toLowerCase().includes(search) ||
                                (a.description && a.description.toLowerCase().includes(search))
                            );
                            UI.renderTable(filtered);
                        } else {
                            UI.renderTable(state.areas);
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
                    API.loadAreas();
                });

                // Info boxes as filters
                $('#infoBoxAll').on('click', () => {
                    state.currentFilter = '';
                    state.currentPage = 1;
                    $('#statusFilter').val('');
                    UI.updateFilterSelection();
                    API.loadAreas();
                });

                $('#infoBoxActive').on('click', () => {
                    state.currentFilter = 'active';
                    state.currentPage = 1;
                    $('#statusFilter').val('active');
                    UI.updateFilterSelection();
                    API.loadAreas();
                });

                $('#infoBoxInactive').on('click', () => {
                    state.currentFilter = 'inactive';
                    state.currentPage = 1;
                    $('#statusFilter').val('inactive');
                    UI.updateFilterSelection();
                    API.loadAreas();
                });

                // Reset button states when modals close
                $('#createAreaModal').on('hidden.bs.modal', () => {
                    $('#btnCreateSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Área');
                    state.isOperating = false;
                });

                $('#editAreaModal').on('hidden.bs.modal', () => {
                    $('#btnEditSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
                    state.isOperating = false;
                });

                $('#confirmEnableModal').on('hidden.bs.modal', () => {
                    $('#btnConfirmEnable').prop('disabled', true).html('<i class="fas fa-toggle-on"></i> Activar Áreas');
                    state.isOperating = false;
                });
            }
        };

        // ========== INITIALIZATION ==========
        async function init() {
            state.companyId = $('#companyId').val();
            if (!state.companyId) {
                console.error('[Areas] Company ID not found');
                Toast.error('Error de configuración: ID de empresa no encontrado');
                return;
            }

            console.log('[Areas] Company ID:', state.companyId);

            UI.showInitialLoading();
            const statusResult = await API.checkFeatureStatus();

            if (!statusResult.success) {
                Toast.error(statusResult.message);
                UI.showFeatureDisabled();
                Events.init();
                return;
            }

            state.featureEnabled = statusResult.enabled;

            if (state.featureEnabled) {
                UI.showFeatureEnabled();
                Validation.init();
                Events.init();
                UI.updateFilterSelection();
                API.loadAreas();
            } else {
                UI.showFeatureDisabled();
                Events.init();
            }

            console.log('[Areas] ✓ Initialized successfully');
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
                    console.error('[Areas] Dependencies not loaded after 10 seconds');
                }
            }, 100);
        }
    })();
    </script>
@endpush
