@extends('layouts.authenticated')

@section('title', 'Gestión de Anuncios')

@section('content_header', 'Gestión de Anuncios')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Anuncios</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Jerárquía canónica de AdminLTE v3: .row > .col-md-4 (lista) + .col-md-8 (detalle) -->
            <div class="row">

                <!-- COLUMNA IZQUIERDA: Lista de Anuncios (col-md-4) -->
                <div class="col-md-4">

                    <!-- TARJETA 1: Filtros y Búsqueda -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-filter mr-2"></i>Filtros
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="filter-form">
                                <!-- Tipo de Anuncio -->
                                <div class="form-group">
                                    <label for="filter-type">Tipo</label>
                                    <select class="form-control select2" id="filter-type" name="type" style="width: 100%;">
                                        <option value="">Todos los tipos</option>
                                        <option value="MAINTENANCE">Mantenimiento</option>
                                        <option value="INCIDENT">Incidente</option>
                                        <option value="NEWS">Noticias</option>
                                        <option value="ALERT">Alerta</option>
                                    </select>
                                </div>

                                <!-- Estado -->
                                <div class="form-group">
                                    <label for="filter-status">Estado</label>
                                    <select class="form-control select2" id="filter-status" name="status" style="width: 100%;">
                                        <option value="">Todos los estados</option>
                                        <option value="DRAFT">Borrador</option>
                                        <option value="SCHEDULED">Programado</option>
                                        <option value="PUBLISHED">Publicado</option>
                                        <option value="ARCHIVED">Archivado</option>
                                    </select>
                                </div>

                                <!-- Urgencia -->
                                <div class="form-group">
                                    <label for="filter-urgency">Urgencia</label>
                                    <select class="form-control select2" id="filter-urgency" name="urgency" style="width: 100%;">
                                        <option value="">Todas las urgencias</option>
                                        <option value="LOW">Baja</option>
                                        <option value="MEDIUM">Media</option>
                                        <option value="HIGH">Alta</option>
                                        <option value="CRITICAL">Crítica</option>
                                    </select>
                                </div>

                                <!-- Búsqueda -->
                                <div class="form-group">
                                    <label for="filter-search">Búsqueda</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="filter-search" name="search"
                                               placeholder="Buscar en título y contenido..." maxlength="100">
                                        <div class="input-group-append">
                                            <button class="btn btn-default" type="button" id="clear-search">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones de Acción -->
                                <div class="form-group mb-0">
                                    <button type="button" class="btn btn-primary btn-block" id="apply-filters">
                                        <i class="fas fa-search mr-2"></i>Aplicar Filtros
                                    </button>
                                    <button type="button" class="btn btn-default btn-block" id="reset-filters">
                                        <i class="fas fa-undo mr-2"></i>Limpiar Filtros
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /.card -->

                    <!-- TARJETA 2: Lista de Anuncios -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bullhorn mr-2"></i>Anuncios
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-info" id="announcements-count">0</span>
                            </div>
                        </div>
                        <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                            <!-- Loading -->
                            <div id="list-loading" class="text-center p-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Cargando anuncios...</p>
                            </div>

                            <!-- Lista -->
                            <div id="announcements-list" style="display: none;">
                                <!-- Populated by JavaScript -->
                            </div>

                            <!-- Empty State -->
                            <div id="list-empty" style="display: none;" class="text-center p-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No se encontraron anuncios</p>
                                <button class="btn btn-primary btn-sm" id="create-first-announcement">
                                    <i class="fas fa-plus mr-2"></i>Crear Primer Anuncio
                                </button>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-success btn-block" id="create-new-announcement">
                                <i class="fas fa-plus mr-2"></i>Crear Nuevo Anuncio
                            </button>
                        </div>
                    </div>
                    <!-- /.card -->

                </div>
                <!-- /.col-md-4 -->

                <!-- COLUMNA DERECHA: Detalles y Formularios (col-md-8) -->
                <div class="col-md-8">
                    <div class="card">

                        <!-- NAV PILLS en Card Header (Patrón Canónico AdminLTE v3) -->
                        <div class="card-header p-2">
                            <ul class="nav nav-pills" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="detailsTab" data-toggle="tab" href="#detailsPane"
                                       role="tab" aria-controls="detailsPane" aria-selected="true">
                                        <i class="fas fa-info-circle mr-2"></i>Detalles
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="editorTab" data-toggle="tab" href="#editorPane"
                                       role="tab" aria-controls="editorPane" aria-selected="false">
                                        <i class="fas fa-edit mr-2"></i>Editor
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="timelineTab" data-toggle="tab" href="#timelinePane"
                                       role="tab" aria-controls="timelinePane" aria-selected="false">
                                        <i class="fas fa-history mr-2"></i>Historial
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="statsTab" data-toggle="tab" href="#statsPane"
                                       role="tab" aria-controls="statsPane" aria-selected="false">
                                        <i class="fas fa-chart-bar mr-2"></i>Estadísticas
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- /.card-header -->

                        <!-- TAB CONTENT -->
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- ========== PESTAÑA 1: DETALLES ========== -->
                                <div class="tab-pane fade show active" id="detailsPane" role="tabpanel"
                                     aria-labelledby="detailsTab">

                                    <!-- Welcome State -->
                                    <div id="details-welcome" class="text-center p-5">
                                        <i class="fas fa-hand-pointer fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">Selecciona un anuncio</h4>
                                        <p class="text-muted">Selecciona un anuncio de la lista para ver sus detalles</p>
                                    </div>

                                    <!-- Details Content -->
                                    <div id="details-content" style="display: none;">

                                        <!-- Header con Título y Badges -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h3 id="detail-title" class="mb-2">Título del Anuncio</h3>
                                                    <div>
                                                        <span id="detail-type-badge" class="badge badge-info mr-2">TIPO</span>
                                                        <span id="detail-status-badge" class="badge badge-secondary mr-2">ESTADO</span>
                                                        <span id="detail-urgency-badge" class="badge badge-warning">URGENCIA</span>
                                                    </div>
                                                </div>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-default dropdown-toggle"
                                                            data-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-cog"></i> Acciones
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right" id="detail-actions-menu">
                                                        <!-- Populated by JavaScript -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Contenido del Anuncio -->
                                        <div class="mb-4">
                                            <h5><i class="fas fa-align-left mr-2"></i>Contenido</h5>
                                            <div id="detail-content" class="border rounded p-3 bg-light">
                                                Contenido del anuncio...
                                            </div>
                                        </div>

                                        <!-- Metadata Cards -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-box bg-light">
                                                    <span class="info-box-icon"><i class="fas fa-user"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Autor</span>
                                                        <span class="info-box-number" id="detail-author">--</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-box bg-light">
                                                    <span class="info-box-icon"><i class="fas fa-calendar"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Creado</span>
                                                        <span class="info-box-number" id="detail-created">--</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Metadata Específica por Tipo -->
                                        <div id="detail-specific-metadata">
                                            <!-- Populated by JavaScript based on announcement type -->
                                        </div>

                                    </div>

                                </div>
                                <!-- /.tab-pane #detailsPane -->

                                <!-- ========== PESTAÑA 2: EDITOR ========== -->
                                <div class="tab-pane fade" id="editorPane" role="tabpanel"
                                     aria-labelledby="editorTab">

                                    <!-- Editor Welcome State -->
                                    <div id="editor-welcome" class="text-center p-5">
                                        <i class="fas fa-edit fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">Modo de Edición</h4>
                                        <p class="text-muted">Crea un nuevo anuncio o selecciona uno existente para editar</p>
                                        <button class="btn btn-primary" id="start-create">
                                            <i class="fas fa-plus mr-2"></i>Crear Nuevo Anuncio
                                        </button>
                                    </div>

                                    <!-- Editor Form -->
                                    <div id="editor-form-container" style="display: none;">
                                        <form id="announcement-form">

                                            <!-- Información Básica -->
                                            <div class="card card-outline card-primary">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-info-circle mr-2"></i>Información Básica
                                                    </h3>
                                                </div>
                                                <div class="card-body">

                                                    <!-- Tipo de Anuncio -->
                                                    <div class="form-group">
                                                        <label for="form-type">Tipo de Anuncio <span class="text-danger">*</span></label>
                                                        <select class="form-control select2" id="form-type" name="type" required style="width: 100%;">
                                                            <option value="">-- Selecciona un tipo --</option>
                                                            <option value="MAINTENANCE">🔧 Mantenimiento</option>
                                                            <option value="INCIDENT">🚨 Incidente</option>
                                                            <option value="NEWS">📰 Noticias</option>
                                                            <option value="ALERT">⚠️ Alerta</option>
                                                        </select>
                                                        <small class="form-text text-muted">Selecciona el tipo de anuncio que deseas crear</small>
                                                    </div>

                                                    <!-- Título -->
                                                    <div class="form-group">
                                                        <label for="form-title">Título <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="form-title" name="title"
                                                               placeholder="Ej: Mantenimiento Programado del Sistema"
                                                               minlength="5" maxlength="200" required>
                                                        <small class="form-text text-muted">Mínimo 5 caracteres, máximo 200</small>
                                                    </div>

                                                    <!-- Contenido -->
                                                    <div class="form-group">
                                                        <label for="form-content">Contenido <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="form-content" name="content" rows="6"
                                                                  placeholder="Describe detalladamente el anuncio..." required></textarea>
                                                        <small class="form-text text-muted">Proporciona una descripción completa del anuncio</small>
                                                    </div>

                                                </div>
                                            </div>
                                            <!-- /.card -->

                                            <!-- MANTENIMIENTO - Campos Específicos -->
                                            <div id="maintenance-fields" class="card card-outline card-warning type-specific-card" style="display: none;">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-tools mr-2"></i>Detalles de Mantenimiento
                                                    </h3>
                                                </div>
                                                <div class="card-body">

                                                    <!-- Urgencia -->
                                                    <div class="form-group">
                                                        <label for="maint-urgency">Urgencia <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="maint-urgency" name="urgency">
                                                            <option value="LOW">🟢 Baja</option>
                                                            <option value="MEDIUM">🟡 Media</option>
                                                            <option value="HIGH">🔴 Alta</option>
                                                        </select>
                                                    </div>

                                                    <!-- Inicio Programado -->
                                                    <div class="form-group">
                                                        <label for="maint-scheduled-start">Inicio Programado <span class="text-danger">*</span></label>
                                                        <div class="input-group date" id="scheduled-start-picker" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input"
                                                                   id="maint-scheduled-start" name="scheduled_start"
                                                                   data-target="#scheduled-start-picker"/>
                                                            <div class="input-group-append" data-target="#scheduled-start-picker" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Fin Programado -->
                                                    <div class="form-group">
                                                        <label for="maint-scheduled-end">Fin Programado <span class="text-danger">*</span></label>
                                                        <div class="input-group date" id="scheduled-end-picker" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input"
                                                                   id="maint-scheduled-end" name="scheduled_end"
                                                                   data-target="#scheduled-end-picker"/>
                                                            <div class="input-group-append" data-target="#scheduled-end-picker" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Emergencia -->
                                                    <div class="form-group">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="maint-emergency" name="is_emergency">
                                                            <label class="custom-control-label" for="maint-emergency">
                                                                <strong>Es Mantenimiento de Emergencia</strong>
                                                            </label>
                                                        </div>
                                                        <small class="form-text text-muted">Si es emergencia, la urgencia se establecerá automáticamente como Alta</small>
                                                    </div>

                                                    <!-- Servicios Afectados -->
                                                    <div class="form-group">
                                                        <label for="maint-affected-services">Servicios Afectados <span class="text-danger">*</span></label>
                                                        <select class="form-control select2" id="maint-affected-services"
                                                                name="affected_services" multiple="multiple"
                                                                data-placeholder="Selecciona los servicios afectados" style="width: 100%;">
                                                            <option value="API">API</option>
                                                            <option value="Dashboard">Dashboard</option>
                                                            <option value="Database">Base de Datos</option>
                                                            <option value="Authentication">Autenticación</option>
                                                            <option value="Email">Email</option>
                                                            <option value="Storage">Almacenamiento</option>
                                                            <option value="Payment">Pagos</option>
                                                            <option value="Reporting">Reportes</option>
                                                        </select>
                                                        <small class="form-text text-muted">Selecciona al menos un servicio</small>
                                                    </div>

                                                </div>
                                            </div>
                                            <!-- /.card MAINTENANCE -->

                                            <!-- INCIDENTE - Campos Específicos -->
                                            <div id="incident-fields" class="card card-outline card-danger type-specific-card" style="display: none;">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-exclamation-triangle mr-2"></i>Detalles del Incidente
                                                    </h3>
                                                </div>
                                                <div class="card-body">

                                                    <!-- Urgencia -->
                                                    <div class="form-group">
                                                        <label for="incident-urgency">Urgencia <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="incident-urgency" name="urgency">
                                                            <option value="LOW">🟢 Baja</option>
                                                            <option value="MEDIUM">🟡 Media</option>
                                                            <option value="HIGH">🟠 Alta</option>
                                                            <option value="CRITICAL">🔴 Crítica</option>
                                                        </select>
                                                    </div>

                                                    <!-- Fecha de Inicio -->
                                                    <div class="form-group">
                                                        <label for="incident-started-at">Fecha de Inicio <span class="text-danger">*</span></label>
                                                        <div class="input-group date" id="incident-started-picker" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input"
                                                                   id="incident-started-at" name="started_at"
                                                                   data-target="#incident-started-picker"/>
                                                            <div class="input-group-append" data-target="#incident-started-picker" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Fecha de Fin (Opcional) -->
                                                    <div class="form-group">
                                                        <label for="incident-ended-at">Fecha de Fin (Opcional)</label>
                                                        <div class="input-group date" id="incident-ended-picker" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input"
                                                                   id="incident-ended-at" name="ended_at"
                                                                   data-target="#incident-ended-picker"/>
                                                            <div class="input-group-append" data-target="#incident-ended-picker" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Resuelto -->
                                                    <div class="form-group">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="incident-resolved" name="is_resolved">
                                                            <label class="custom-control-label" for="incident-resolved">
                                                                <strong>Incidente Resuelto</strong>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <!-- Contenido de Resolución -->
                                                    <div class="form-group" id="incident-resolution-group" style="display: none;">
                                                        <label for="incident-resolution">Contenido de Resolución</label>
                                                        <textarea class="form-control" id="incident-resolution" name="resolution_content" rows="4"
                                                                  placeholder="Describe cómo se resolvió el incidente..."></textarea>
                                                    </div>

                                                    <!-- Servicios Afectados -->
                                                    <div class="form-group">
                                                        <label for="incident-affected-services">Servicios Afectados</label>
                                                        <select class="form-control select2" id="incident-affected-services"
                                                                name="affected_services" multiple="multiple"
                                                                data-placeholder="Selecciona los servicios afectados" style="width: 100%;">
                                                            <option value="API">API</option>
                                                            <option value="Dashboard">Dashboard</option>
                                                            <option value="Database">Base de Datos</option>
                                                            <option value="Authentication">Autenticación</option>
                                                            <option value="Email">Email</option>
                                                            <option value="Storage">Almacenamiento</option>
                                                            <option value="Payment">Pagos</option>
                                                            <option value="Reporting">Reportes</option>
                                                        </select>
                                                    </div>

                                                </div>
                                            </div>
                                            <!-- /.card INCIDENT -->

                                            <!-- NOTICIAS - Campos Específicos -->
                                            <div id="news-fields" class="card card-outline card-info type-specific-card" style="display: none;">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-newspaper mr-2"></i>Detalles de Noticias
                                                    </h3>
                                                </div>
                                                <div class="card-body">

                                                    <!-- Tipo de Noticia -->
                                                    <div class="form-group">
                                                        <label for="news-type">Tipo de Noticia <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="news-type" name="news_type">
                                                            <option value="feature_release">🎉 Lanzamiento de Característica</option>
                                                            <option value="policy_update">📋 Actualización de Política</option>
                                                            <option value="general_update">ℹ️ Actualización General</option>
                                                        </select>
                                                    </div>

                                                    <!-- Resumen -->
                                                    <div class="form-group">
                                                        <label for="news-summary">Resumen <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="news-summary" name="summary" rows="3"
                                                                  placeholder="Resumen breve de la noticia (máx. 280 caracteres)..."
                                                                  maxlength="280"></textarea>
                                                        <small class="form-text text-muted">
                                                            <span id="summary-char-count">0</span> / 280 caracteres
                                                        </small>
                                                    </div>

                                                    <!-- Audiencia Objetivo -->
                                                    <div class="form-group">
                                                        <label for="news-audience">Audiencia Objetivo <span class="text-danger">*</span></label>
                                                        <select class="form-control select2" id="news-audience"
                                                                name="target_audience" multiple="multiple"
                                                                data-placeholder="Selecciona la audiencia" style="width: 100%;">
                                                            <option value="users">👥 Usuarios</option>
                                                            <option value="agents">🎧 Agentes</option>
                                                            <option value="admins">👔 Administradores</option>
                                                        </select>
                                                        <small class="form-text text-muted">Selecciona al menos una audiencia</small>
                                                    </div>

                                                    <!-- Call to Action -->
                                                    <div class="card card-outline card-secondary">
                                                        <div class="card-header">
                                                            <h3 class="card-title">Call to Action (Opcional)</h3>
                                                            <div class="card-tools">
                                                                <div class="custom-control custom-switch">
                                                                    <input type="checkbox" class="custom-control-input" id="news-cta-enabled">
                                                                    <label class="custom-control-label" for="news-cta-enabled">Habilitar</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body" id="news-cta-fields" style="display: none;">
                                                            <div class="form-group">
                                                                <label for="news-cta-text">Texto del Botón</label>
                                                                <input type="text" class="form-control" id="news-cta-text" name="cta_text"
                                                                       placeholder="Ej: Más Información" maxlength="50">
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="news-cta-url">URL</label>
                                                                <input type="url" class="form-control" id="news-cta-url" name="cta_url"
                                                                       placeholder="https://ejemplo.com/mas-info">
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                            <!-- /.card NEWS -->

                                            <!-- ALERTA - Campos Específicos -->
                                            <div id="alert-fields" class="card card-outline card-dark type-specific-card" style="display: none;">
                                                <div class="card-header bg-dark">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-bell mr-2"></i>Detalles de Alerta
                                                    </h3>
                                                </div>
                                                <div class="card-body">

                                                    <!-- Urgencia (Solo HIGH y CRITICAL) -->
                                                    <div class="form-group">
                                                        <label for="alert-urgency">Urgencia <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="alert-urgency" name="urgency">
                                                            <option value="HIGH">🟠 Alta</option>
                                                            <option value="CRITICAL">🔴 Crítica</option>
                                                        </select>
                                                        <small class="form-text text-muted">Las alertas solo pueden ser Alta o Crítica</small>
                                                    </div>

                                                    <!-- Tipo de Alerta -->
                                                    <div class="form-group">
                                                        <label for="alert-type">Tipo de Alerta <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="alert-type" name="alert_type">
                                                            <option value="security">🔒 Seguridad</option>
                                                            <option value="system">⚙️ Sistema</option>
                                                            <option value="service">🔌 Servicio</option>
                                                            <option value="compliance">📜 Cumplimiento</option>
                                                        </select>
                                                    </div>

                                                    <!-- Mensaje -->
                                                    <div class="form-group">
                                                        <label for="alert-message">Mensaje de Alerta <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="alert-message" name="message" rows="3"
                                                                  placeholder="Mensaje breve y claro de la alerta (máx. 500 caracteres)..."
                                                                  maxlength="500"></textarea>
                                                        <small class="form-text text-muted">
                                                            <span id="alert-char-count">0</span> / 500 caracteres
                                                        </small>
                                                    </div>

                                                    <!-- Fecha de Inicio -->
                                                    <div class="form-group">
                                                        <label for="alert-started-at">Fecha de Inicio <span class="text-danger">*</span></label>
                                                        <div class="input-group date" id="alert-started-picker" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input"
                                                                   id="alert-started-at" name="started_at"
                                                                   data-target="#alert-started-picker"/>
                                                            <div class="input-group-append" data-target="#alert-started-picker" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Fecha de Fin (Opcional) -->
                                                    <div class="form-group">
                                                        <label for="alert-ended-at">Fecha de Fin (Opcional)</label>
                                                        <div class="input-group date" id="alert-ended-picker" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input"
                                                                   id="alert-ended-at" name="ended_at"
                                                                   data-target="#alert-ended-picker"/>
                                                            <div class="input-group-append" data-target="#alert-ended-picker" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Acción Requerida -->
                                                    <div class="form-group">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="alert-action-required" name="action_required">
                                                            <label class="custom-control-label" for="alert-action-required">
                                                                <strong>Acción Requerida del Usuario</strong>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <!-- Descripción de la Acción -->
                                                    <div class="form-group" id="alert-action-description-group" style="display: none;">
                                                        <label for="alert-action-description">Descripción de la Acción <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="alert-action-description" name="action_description" rows="3"
                                                                  placeholder="Describe qué acción debe tomar el usuario..."></textarea>
                                                        <small class="form-text text-muted">Requerido si se marca "Acción Requerida"</small>
                                                    </div>

                                                    <!-- Servicios Afectados -->
                                                    <div class="form-group">
                                                        <label for="alert-affected-services">Servicios Afectados (Opcional)</label>
                                                        <select class="form-control select2" id="alert-affected-services"
                                                                name="affected_services" multiple="multiple"
                                                                data-placeholder="Selecciona los servicios afectados" style="width: 100%;">
                                                            <option value="API">API</option>
                                                            <option value="Dashboard">Dashboard</option>
                                                            <option value="Database">Base de Datos</option>
                                                            <option value="Authentication">Autenticación</option>
                                                            <option value="Email">Email</option>
                                                            <option value="Storage">Almacenamiento</option>
                                                            <option value="Payment">Pagos</option>
                                                            <option value="Reporting">Reportes</option>
                                                        </select>
                                                    </div>

                                                </div>
                                            </div>
                                            <!-- /.card ALERT -->

                                            <!-- Botones de Acción -->
                                            <div class="card">
                                                <div class="card-footer">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <button type="button" class="btn btn-default btn-block" id="cancel-form">
                                                                <i class="fas fa-times mr-2"></i>Cancelar
                                                            </button>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="btn-group btn-block">
                                                                <button type="submit" class="btn btn-secondary" id="save-draft">
                                                                    <i class="fas fa-save mr-2"></i>Guardar Borrador
                                                                </button>
                                                                <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split"
                                                                        data-toggle="dropdown" aria-expanded="false">
                                                                    <span class="sr-only">Toggle Dropdown</span>
                                                                </button>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    <a class="dropdown-item" href="#" id="save-and-schedule">
                                                                        <i class="fas fa-clock mr-2"></i>Guardar y Programar
                                                                    </a>
                                                                    <a class="dropdown-item" href="#" id="save-and-publish">
                                                                        <i class="fas fa-paper-plane mr-2"></i>Guardar y Publicar
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- /.card -->

                                        </form>
                                    </div>

                                </div>
                                <!-- /.tab-pane #editorPane -->

                                <!-- ========== PESTAÑA 3: HISTORIAL ========== -->
                                <div class="tab-pane fade" id="timelinePane" role="tabpanel"
                                     aria-labelledby="timelineTab">

                                    <!-- Timeline Welcome State -->
                                    <div id="timeline-welcome" class="text-center p-5">
                                        <i class="fas fa-history fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">Historial de Actividad</h4>
                                        <p class="text-muted">Selecciona un anuncio para ver su historial</p>
                                    </div>

                                    <!-- Timeline Content -->
                                    <div id="timeline-content" style="display: none;">
                                        <div class="timeline" id="announcement-timeline">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>

                                </div>
                                <!-- /.tab-pane #timelinePane -->

                                <!-- ========== PESTAÑA 4: ESTADÍSTICAS ========== -->
                                <div class="tab-pane fade" id="statsPane" role="tabpanel"
                                     aria-labelledby="statsTab">

                                    <!-- Stats Loading -->
                                    <div id="stats-loading" class="text-center p-5">
                                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                        <p class="text-muted mt-2">Cargando estadísticas...</p>
                                    </div>

                                    <!-- Stats Content -->
                                    <div id="stats-content" style="display: none;">

                                        <!-- Resumen General -->
                                        <div class="row">
                                            <div class="col-lg-3 col-6">
                                                <div class="small-box bg-info">
                                                    <div class="inner">
                                                        <h3 id="stat-total">0</h3>
                                                        <p>Total Anuncios</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-bullhorn"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-6">
                                                <div class="small-box bg-success">
                                                    <div class="inner">
                                                        <h3 id="stat-published">0</h3>
                                                        <p>Publicados</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-6">
                                                <div class="small-box bg-warning">
                                                    <div class="inner">
                                                        <h3 id="stat-scheduled">0</h3>
                                                        <p>Programados</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-clock"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-6">
                                                <div class="small-box bg-secondary">
                                                    <div class="inner">
                                                        <h3 id="stat-draft">0</h3>
                                                        <p>Borradores</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-file-alt"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Gráficos -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h3 class="card-title">Anuncios por Tipo</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="chart-by-type" style="height: 250px;"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h3 class="card-title">Anuncios por Estado</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="chart-by-status" style="height: 250px;"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tabla de Actividad Reciente -->
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-history mr-2"></i>Actividad Reciente
                                                </h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-striped table-sm" id="recent-activity-table">
                                                    <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Anuncio</th>
                                                        <th>Acción</th>
                                                        <th>Usuario</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <!-- Populated by JavaScript -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                                <!-- /.tab-pane #statsPane -->

                            </div>
                            <!-- /.tab-content -->
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col-md-8 -->

            </div>
            <!-- /.row -->
        </div>
    </section>

    <!-- MODAL: Programar Publicación -->
    <div class="modal fade" id="schedule-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h4 class="modal-title">
                        <i class="fas fa-clock mr-2"></i>Programar Publicación
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Selecciona la fecha y hora en que deseas publicar este anuncio:</p>
                    <div class="form-group">
                        <label for="schedule-datetime">Fecha y Hora de Publicación</label>
                        <div class="input-group date" id="schedule-datetime-picker" data-target-input="nearest">
                            <input type="text" class="form-control datetimepicker-input"
                                   id="schedule-datetime" data-target="#schedule-datetime-picker"/>
                            <div class="input-group-append" data-target="#schedule-datetime-picker" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                        <small class="form-text text-muted">Debe ser entre 5 minutos y 1 año en el futuro</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="confirm-schedule">
                        <i class="fas fa-check mr-2"></i>Programar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- /.modal -->

@endsection

@push('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
    <!-- Tempus Dominus Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tempusdominus-bootstrap-4@5.39.0/build/css/tempusdominus-bootstrap-4.min.css">
    <!-- Custom Styles -->
    <style>
        .announcement-item {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }
        .announcement-item:hover {
            background: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .announcement-item.active {
            background: #e3f2fd;
            border-left-color: #1976d2;
        }
        .announcement-item.type-MAINTENANCE {
            border-left-color: #ffc107;
        }
        .announcement-item.type-INCIDENT {
            border-left-color: #dc3545;
        }
        .announcement-item.type-NEWS {
            border-left-color: #17a2b8;
        }
        .announcement-item.type-ALERT {
            border-left-color: #343a40;
        }
        .timeline > div > .timeline-item > .timeline-header {
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <!-- Tempus Dominus Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/tempusdominus-bootstrap-4@5.39.0/build/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery Validation -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

    <!-- Main Script -->
    <script src="{{ asset('js/pages/announcements.js') }}"></script>
@endpush
