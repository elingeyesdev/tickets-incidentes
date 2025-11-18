@extends('layouts.authenticated')

@section('title', 'Gestión de Artículos')

@section('content_header', 'Centro de Ayuda - Artículos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Artículos</li>
@endsection

@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Tarjeta Principal: Lista de Artículos -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Artículos del Centro de Ayuda</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" id="btnCreateArticle">
                        <i class="fas fa-plus"></i> Nuevo Artículo
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" id="searchInput"
                               placeholder="Buscar artículos..." autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm" id="categoryFilter">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm" id="statusFilter">
                            <option value="">Todos los estados</option>
                            <option value="DRAFT">Borradores</option>
                            <option value="PUBLISHED">Publicados</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary btn-sm btn-block" id="btnResetFilters">
                            <i class="fas fa-redo"></i> Limpiar
                        </button>
                    </div>
                </div>

                <!-- Estado de Carga -->
                <div id="loadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="text-muted mt-3">Cargando artículos...</p>
                </div>

                <!-- Tabla de Artículos (oculta inicialmente) -->
                <div id="tableContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Título</th>
                                    <th style="width: 20%;">Categoría</th>
                                    <th style="width: 12%;">Estado</th>
                                    <th style="width: 10%;">Vistas</th>
                                    <th style="width: 23%;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="articlesTableBody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox"></i> No hay artículos registrados
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted" id="paginationInfo">Mostrando 0 de 0</small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0" id="paginationControls">
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Mensaje de Error -->
                <div id="errorMessage" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="errorText"></span>
                </div>
            </div>

            <div class="card-footer text-muted">
                <small><i class="fas fa-info-circle"></i> Los artículos se crean como borradores y pueden ser publicados cuando estén listos.</small>
            </div>
        </div>

        <!-- Fila: Estadísticas Rápidas -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="info-box bg-light">
                    <span class="info-box-icon bg-primary"><i class="fas fa-file-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Artículos</span>
                        <span class="info-box-number" id="totalArticles">0</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-light">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Publicados</span>
                        <span class="info-box-number" id="publishedArticles">0</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-light">
                    <span class="info-box-icon bg-warning"><i class="fas fa-edit"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Borradores</span>
                        <span class="info-box-number" id="draftArticles">0</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-light">
                    <span class="info-box-icon bg-info"><i class="fas fa-eye"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Visualizaciones</span>
                        <span class="info-box-number" id="totalViews">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL: Crear/Editar Artículo -->
<div class="modal fade" id="articleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white" id="modalHeader">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nuevo Artículo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="articleForm">
                <input type="hidden" id="articleId" name="id">
                <div class="modal-body">
                    <!-- Título -->
                    <div class="form-group">
                        <label for="title">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                               placeholder="Ej: Cómo cambiar tu contraseña" required minlength="3" maxlength="255">
                        <small class="form-text text-muted">Mínimo 3 caracteres, máximo 255</small>
                    </div>

                    <!-- Categoría -->
                    <div class="form-group">
                        <label for="category_id">Categoría <span class="text-danger">*</span></label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Seleccionar categoría...</option>
                        </select>
                    </div>

                    <!-- Resumen/Excerpt -->
                    <div class="form-group">
                        <label for="excerpt">Resumen</label>
                        <textarea class="form-control" id="excerpt" name="excerpt"
                                  placeholder="Resumen breve del artículo..." rows="2" maxlength="500"></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres (opcional, se genera automáticamente si no se proporciona)</small>
                    </div>

                    <!-- Contenido -->
                    <div class="form-group">
                        <label for="content">Contenido <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content"
                                  placeholder="Escribe el contenido completo del artículo..." rows="8" required minlength="50" maxlength="20000"></textarea>
                        <small class="form-text text-muted">Mínimo 50 caracteres, máximo 20,000</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-success" id="btnSaveDraft">
                        <i class="fas fa-save"></i> Guardar como Borrador
                    </button>
                    <button type="button" class="btn btn-success" id="btnPublish">
                        <i class="fas fa-paper-plane"></i> Publicar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Ver Artículo -->
<div class="modal fade" id="viewArticleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewArticleTitle">Título del Artículo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Categoría:</strong> <span id="viewArticleCategory"></span>
                    </div>
                    <div class="col-md-6 text-right">
                        <strong>Vistas:</strong> <span id="viewArticleViews">0</span>
                    </div>
                </div>
                <hr>
                <div id="viewArticleContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('js/pages/articles.js?v=' . time()) }}"></script>
@endpush
@endsection
