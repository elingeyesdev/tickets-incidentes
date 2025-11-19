@extends('layouts.laboratory')

@section('title', 'Anuncios - Vista Tabla')

@section('content_header', 'Propuesta 1: Gestión de Anuncios - Vista Tabla')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tests.index') }}">Laboratorio Visual</a></li>
    <li class="breadcrumb-item active">Anuncios - Vista Tabla</li>
@endsection

@section('content')

{{-- Info Callout --}}
<x-adminlte-callout theme="success" icon="fas fa-flask">
    <strong>Propuesta 1: Vista de Tabla Clásica</strong><br>
    Vista tradicional estilo tabla con filtros avanzados y acciones contextuales según el estado. Todos los datos son ejemplos hardcodeados.
</x-adminlte-callout>

{{-- Statistics Small Boxes --}}
<div class="row">
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="12" text="Total Anuncios" icon="fas fa-bullhorn" theme="info"/>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="5" text="Publicados" icon="fas fa-check-circle" theme="success"/>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="4" text="Borradores" icon="fas fa-pencil-alt" theme="secondary"/>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="2" text="Programados" icon="fas fa-clock" theme="warning"/>
    </div>
</div>

{{-- Announcements Table Card --}}
<x-adminlte-card title="Gestión de Anuncios" icon="fas fa-bullhorn" theme="primary" maximizable collapsible>
    <x-slot name="toolsSlot">
        <button type="button" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Crear Anuncio
        </button>
    </x-slot>

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
                        <input type="text" class="form-control" placeholder="Título o contenido...">
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group mb-2">
                    <label class="text-sm mb-1">Tipo</label>
                    <select class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="MAINTENANCE">Mantenimiento</option>
                        <option value="INCIDENT">Incidente</option>
                        <option value="NEWS">Noticias</option>
                        <option value="ALERT">Alerta</option>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group mb-2">
                    <label class="text-sm mb-1">Estado</label>
                    <select class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="DRAFT">Borrador</option>
                        <option value="SCHEDULED">Programado</option>
                        <option value="PUBLISHED">Publicado</option>
                        <option value="ARCHIVED">Archivado</option>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group mb-2">
                    <label class="text-sm mb-1">Urgencia</label>
                    <select class="form-control form-control-sm">
                        <option value="">Todas</option>
                        <option value="LOW">Baja</option>
                        <option value="MEDIUM">Media</option>
                        <option value="HIGH">Alta</option>
                        <option value="CRITICAL">Crítica</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <label class="text-sm mb-1 d-block">&nbsp;</label>
                <button type="button" class="btn btn-default btn-sm">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
                <button type="button" class="btn btn-primary btn-sm">
                    <i class="fas fa-sync-alt"></i> Refrescar
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 25%;">Título</th>
                    <th style="width: 10%;">Tipo</th>
                    <th style="width: 8%;">Urgencia</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 13%;">Publicado/Programado</th>
                    <th style="width: 10%;">Autor</th>
                    <th style="width: 20%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                {{-- Example 1: MAINTENANCE - PUBLISHED --}}
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <strong>Mantenimiento Programado del Servidor</strong><br>
                        <small class="text-muted">El servidor estará en mantenimiento el 25/11/2025...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-info">
                            <i class="fas fa-tools"></i> Mantenimiento
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-warning">Alta</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Publicado
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-muted">18/11/2025<br>14:30</small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> Juan Pérez
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-primary mx-1 shadow" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-secondary mx-1 shadow" title="Archivar">
                            <i class="fas fa-archive"></i>
                        </button>
                    </td>
                </tr>

                {{-- Example 2: INCIDENT - PUBLISHED --}}
                <tr>
                    <td class="text-center">2</td>
                    <td>
                        <strong>Caída del Servicio de Autenticación</strong><br>
                        <small class="text-muted">Se reporta falla en el sistema de autenticación...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-danger">
                            <i class="fas fa-exclamation-triangle"></i> Incidente
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-danger">Crítica</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Publicado
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-muted">19/11/2025<br>09:15</small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> María García
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-primary mx-1 shadow" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-success mx-1 shadow" title="Resolver">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-secondary mx-1 shadow" title="Archivar">
                            <i class="fas fa-archive"></i>
                        </button>
                    </td>
                </tr>

                {{-- Example 3: NEWS - SCHEDULED --}}
                <tr class="table-warning">
                    <td class="text-center">3</td>
                    <td>
                        <strong>Nueva Funcionalidad: Dark Mode</strong><br>
                        <small class="text-muted">Estamos emocionados de anunciar el lanzamiento de Dark Mode...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-primary">
                            <i class="fas fa-newspaper"></i> Noticias
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-secondary">Media</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-warning">
                            <i class="fas fa-clock"></i> Programado
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-warning">
                            <i class="fas fa-calendar-alt"></i> 20/11/2025<br>10:00
                        </small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> Carlos López
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-primary mx-1 shadow" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-success mx-1 shadow" title="Publicar Ahora">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-warning mx-1 shadow" title="Desprogramar">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </td>
                </tr>

                {{-- Example 4: ALERT - PUBLISHED --}}
                <tr>
                    <td class="text-center">4</td>
                    <td>
                        <strong>Alerta de Seguridad: Cambio de Contraseña Requerido</strong><br>
                        <small class="text-muted">Se detectó actividad sospechosa. Por favor cambiar contraseña...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-danger">
                            <i class="fas fa-shield-alt"></i> Alerta
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-danger">Crítica</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Publicado
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-muted">19/11/2025<br>11:45</small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> Admin Sistema
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-primary mx-1 shadow" title="Editar ended_at">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-secondary mx-1 shadow" title="Archivar">
                            <i class="fas fa-archive"></i>
                        </button>
                    </td>
                </tr>

                {{-- Example 5: MAINTENANCE - DRAFT --}}
                <tr>
                    <td class="text-center">5</td>
                    <td>
                        <strong>Actualización de Base de Datos</strong><br>
                        <small class="text-muted">Migración de base de datos a nueva versión...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-info">
                            <i class="fas fa-tools"></i> Mantenimiento
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-warning">Alta</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-secondary">
                            <i class="fas fa-pencil-alt"></i> Borrador
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-muted">-</small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> Juan Pérez
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-primary mx-1 shadow" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-success mx-1 shadow" title="Publicar">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-warning mx-1 shadow" title="Programar">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-danger mx-1 shadow" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>

                {{-- Example 6: NEWS - DRAFT --}}
                <tr>
                    <td class="text-center">6</td>
                    <td>
                        <strong>Política de Privacidad Actualizada</strong><br>
                        <small class="text-muted">Hemos actualizado nuestra política de privacidad...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-primary">
                            <i class="fas fa-newspaper"></i> Noticias
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">Baja</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-secondary">
                            <i class="fas fa-pencil-alt"></i> Borrador
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-muted">-</small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> Ana Martínez
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-primary mx-1 shadow" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-success mx-1 shadow" title="Publicar">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-warning mx-1 shadow" title="Programar">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-danger mx-1 shadow" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>

                {{-- Example 7: INCIDENT - SCHEDULED --}}
                <tr class="table-warning">
                    <td class="text-center">7</td>
                    <td>
                        <strong>Mantenimiento de Red Programado</strong><br>
                        <small class="text-muted">Corte programado de conectividad...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-danger">
                            <i class="fas fa-exclamation-triangle"></i> Incidente
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-warning">Alta</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-warning">
                            <i class="fas fa-clock"></i> Programado
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-warning">
                            <i class="fas fa-calendar-alt"></i> 22/11/2025<br>02:00
                        </small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> Carlos López
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-primary mx-1 shadow" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-success mx-1 shadow" title="Publicar Ahora">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-warning mx-1 shadow" title="Desprogramar">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </td>
                </tr>

                {{-- Example 8: MAINTENANCE - ARCHIVED --}}
                <tr class="table-secondary">
                    <td class="text-center">8</td>
                    <td>
                        <strong>Actualización Completada de Seguridad</strong><br>
                        <small class="text-muted">Se completó la actualización de seguridad v2.3.1...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-info">
                            <i class="fas fa-tools"></i> Mantenimiento
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-secondary">Media</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-secondary">
                            <i class="fas fa-archive"></i> Archivado
                        </span>
                    </td>
                    <td class="text-center">
                        <small class="text-muted">15/11/2025<br>18:00</small>
                    </td>
                    <td class="text-nowrap">
                        <small>
                            <i class="fas fa-user"></i> Juan Pérez
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default text-teal mx-1 shadow" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-default text-success mx-1 shadow" title="Restaurar a Borrador">
                            <i class="fas fa-undo"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <x-slot name="footerSlot">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Mostrando 1 a 8 de 12</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                    <li class="page-item active"><span class="page-link">1</span></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
    </x-slot>
</x-adminlte-card>

{{-- Analysis Card --}}
<x-adminlte-card title="Análisis de la Propuesta 1: Vista Tabla" theme="info" icon="fas fa-lightbulb" collapsible collapsed>
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-check-circle text-success"></i> Ventajas</h6>
            <ul class="small">
                <li>Vista compacta que permite ver muchos anuncios a la vez</li>
                <li>Filtros avanzados en la parte superior</li>
                <li>Acciones contextuales según el estado del anuncio</li>
                <li>Fácil de ordenar y buscar</li>
                <li>Familiaridad - usuarios están acostumbrados a este formato</li>
                <li>Resaltado visual de filas programadas (amarillo) y archivadas (gris)</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-exclamation-triangle text-warning"></i> Consideraciones</h6>
            <ul class="small">
                <li>Puede ser difícil leer si hay mucho texto en el contenido</li>
                <li>Menos espacio para metadata específica de cada tipo</li>
                <li>Múltiples botones pueden abrumar en pantallas pequeñas</li>
                <li>Menos visual que una vista de cards</li>
            </ul>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <h6><i class="fas fa-key text-primary"></i> Características Clave</h6>
            <ul class="small mb-0">
                <li><strong>Estados visuales:</strong> DRAFT (gris), SCHEDULED (amarillo), PUBLISHED (verde), ARCHIVED (gris oscuro)</li>
                <li><strong>Tipos de anuncios:</strong> MAINTENANCE (azul), INCIDENT (rojo), NEWS (azul oscuro), ALERT (rojo)</li>
                <li><strong>Acciones contextuales:</strong> Las acciones disponibles cambian según el estado del anuncio</li>
                <li><strong>Urgencia visible:</strong> LOW (info), MEDIUM (secondary), HIGH (warning), CRITICAL (danger)</li>
            </ul>
        </div>
    </div>
</x-adminlte-card>

{{-- Actions Legend --}}
<x-adminlte-card title="Leyenda de Acciones por Estado" theme="secondary" icon="fas fa-info-circle" collapsible collapsed>
    <div class="row">
        <div class="col-md-6">
            <h6><span class="badge badge-secondary">DRAFT (Borrador)</span></h6>
            <ul class="small">
                <li><i class="fas fa-eye text-teal"></i> Ver</li>
                <li><i class="fas fa-edit text-primary"></i> Editar</li>
                <li><i class="fas fa-paper-plane text-success"></i> Publicar</li>
                <li><i class="fas fa-clock text-warning"></i> Programar</li>
                <li><i class="fas fa-trash text-danger"></i> Eliminar</li>
            </ul>

            <h6><span class="badge badge-warning">SCHEDULED (Programado)</span></h6>
            <ul class="small">
                <li><i class="fas fa-eye text-teal"></i> Ver</li>
                <li><i class="fas fa-edit text-primary"></i> Editar</li>
                <li><i class="fas fa-paper-plane text-success"></i> Publicar Ahora</li>
                <li><i class="fas fa-times-circle text-warning"></i> Desprogramar</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6><span class="badge badge-success">PUBLISHED (Publicado)</span></h6>
            <ul class="small">
                <li><i class="fas fa-eye text-teal"></i> Ver</li>
                <li><i class="fas fa-edit text-primary"></i> Editar (solo ended_at para ALERT)</li>
                <li><i class="fas fa-check text-success"></i> Resolver (solo INCIDENT)</li>
                <li><i class="fas fa-archive text-secondary"></i> Archivar</li>
            </ul>

            <h6><span class="badge badge-secondary">ARCHIVED (Archivado)</span></h6>
            <ul class="small">
                <li><i class="fas fa-eye text-teal"></i> Ver</li>
                <li><i class="fas fa-undo text-success"></i> Restaurar a Borrador</li>
            </ul>
        </div>
    </div>
</x-adminlte-card>

{{-- Navigation removed - single route mode --}}

@endsection

@section('js')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();

        // Demo: highlight row on hover
        $('tbody tr').hover(
            function() {
                $(this).addClass('bg-light');
            },
            function() {
                $(this).removeClass('bg-light');
            }
        );
    })
</script>
@endsection
