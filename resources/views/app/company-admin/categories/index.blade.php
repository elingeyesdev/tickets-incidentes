@extends('layouts.authenticated')

@section('title', 'Gestión de Categorías')

@section('content_header', 'Gestión de Categorías de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Categorías</li>
@endsection

@section('content')
<!-- Fila: Estadísticas Rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-primary"><i class="fas fa-folder"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total de Categorías</span>
                <span class="info-box-number" id="totalCategories">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Categorías Activas</span>
                <span class="info-box-number" id="activeCategories">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-warning"><i class="fas fa-edit"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Categorías Inactivas</span>
                <span class="info-box-number" id="inactiveCategories">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-info"><i class="fas fa-eye"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tickets Totales</span>
                <span class="info-box-number" id="totalTickets">0</span>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta Principal: Lista de Categorías -->
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
                <!-- Estado de Carga -->
                <div id="loadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="text-muted mt-3">Cargando categorías...</p>
                </div>

                <!-- Tabla de Categorías (oculta inicialmente) -->
                <div id="tableContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Nombre</th>
                                    <th style="width: 35%;">Descripción</th>
                                    <th style="width: 10%;">Estado</th>
                                    <th style="width: 15%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox"></i> No hay categorías registradas
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Controles de Paginación -->
                    <div class="card-footer border-top py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted" id="paginationInfo">Mostrando 0 de 0</small>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de Error -->
                <div id="errorMessage" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="errorText"></span>
                </div>
            </div>

            <div class="card-footer text-muted">
                <small><i class="fas fa-info-circle"></i> Las categorías son utilizadas para organizar los tickets.</small>
            </div>
        </div>

<!-- MODAL: Crear Categoría -->
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
                    <!-- Nombre -->
                    <div class="form-group">
                        <label for="createName">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="createName" name="name"
                               placeholder="Ej: Soporte Técnico" required minlength="3" maxlength="100">
                        <small class="form-text text-muted">Mínimo 3 caracteres, máximo 100</small>
                        <div id="createNameError" class="invalid-feedback"></div>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="createDescription">Descripción</label>
                        <textarea class="form-control" id="createDescription" name="description"
                                  placeholder="Describe el propósito de esta categoría..." rows="3" maxlength="500"></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres</small>
                        <div id="createDescriptionError" class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Editar Categoría -->
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
                    <!-- Nombre -->
                    <div class="form-group">
                        <label for="editName">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editName" name="name"
                               placeholder="Ej: Soporte Técnico" required minlength="3" maxlength="100">
                        <small class="form-text text-muted">Mínimo 3 caracteres, máximo 100</small>
                        <div id="editNameError" class="invalid-feedback"></div>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="editDescription">Descripción</label>
                        <textarea class="form-control" id="editDescription" name="description"
                                  placeholder="Describe el propósito de esta categoría..." rows="3" maxlength="500"></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres</small>
                        <div id="editDescriptionError" class="invalid-feedback"></div>
                    </div>

                    <!-- Estado -->
                    <div class="form-group">
                        <label for="editIsActive">Estado</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="editIsActive" name="is_active">
                            <label class="custom-control-label" for="editIsActive">
                                Activa
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Input hidden para company_id -->
<input type="hidden" id="companyId" value="{{ $companyId }}">

@push('scripts')
    <script src="{{ asset('js/pages/categories.js?v=' . time()) }}"></script>
@endpush
@endsection
