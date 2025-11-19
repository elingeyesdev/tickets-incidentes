@extends('layouts.laboratory')

@section('title', 'Anuncios - Vista Mailbox')

@section('content_header', 'Propuesta 4: Gestión de Anuncios - Estilo Mailbox')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('tests.index') }}">Laboratorio Visual</a></li>
    <li class="breadcrumb-item active">Anuncios - Vista Mailbox</li>
@endsection

@section('css')
<style>
    .mailbox-controls {
        padding: 10px;
    }
    .mailbox-messages > table {
        margin: 0;
    }
    .mailbox-messages > table > tbody > tr > td {
        padding: 12px 10px;
        vertical-align: middle;
    }
    .mailbox-star {
        cursor: pointer;
    }
    .mailbox-star .fa-star {
        color: #ffc107;
    }
    .mailbox-attachment {
        padding-right: 10px;
    }
    .mailbox-name {
        font-weight: 600;
        white-space: nowrap;
    }
    .mailbox-subject {
        padding-left: 10px;
    }
    .mailbox-subject b {
        font-weight: 600;
    }
    .mailbox-date {
        white-space: nowrap;
    }

    /* Type indicators */
    .type-indicator {
        width: 4px;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
    }
    .type-maintenance { border-left: 4px solid #17a2b8 !important; }
    .type-incident { border-left: 4px solid #dc3545 !important; }
    .type-news { border-left: 4px solid #007bff !important; }
    .type-alert { border-left: 4px solid #ffc107 !important; }

    /* Row states */
    .row-draft { background-color: #f8f9fa; }
    .row-scheduled { background-color: #fff3cd; }
    .row-archived { background-color: #e9ecef; opacity: 0.8; }

    /* Unread style */
    .mailbox-read-message { font-weight: normal; }
    .mailbox-unread-message { font-weight: bold; }

    /* Folder badges */
    .folder-badge {
        float: right;
        margin-top: 3px;
    }

    /* Quick actions */
    .quick-actions {
        white-space: nowrap;
    }
    .quick-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    /* Metadata preview */
    .metadata-preview {
        font-size: 0.75rem;
        color: #6c757d;
    }
</style>
@endsection

@section('content')

<div class="row">
    {{-- LEFT SIDEBAR --}}
    <div class="col-md-3">
        <a href="#" class="btn btn-primary btn-block mb-3">
            <i class="fas fa-plus"></i> Nuevo Anuncio
        </a>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Carpetas</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item active">
                        <a href="#" class="nav-link">
                            <i class="fas fa-inbox"></i> Todos
                            <span class="badge bg-primary float-right">12</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-pencil-alt"></i> Borradores
                            <span class="badge bg-secondary float-right">4</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-clock"></i> Programados
                            <span class="badge bg-warning float-right">2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-check-circle"></i> Publicados
                            <span class="badge bg-success float-right">5</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-archive"></i> Archivados
                            <span class="badge bg-secondary float-right">1</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tipos</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-tools text-info"></i> Mantenimiento
                            <span class="badge bg-info float-right">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-exclamation-triangle text-danger"></i> Incidentes
                            <span class="badge bg-danger float-right">2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-newspaper text-primary"></i> Noticias
                            <span class="badge bg-primary float-right">4</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-shield-alt text-warning"></i> Alertas
                            <span class="badge bg-warning float-right">3</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Urgencia</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-circle text-info"></i> Baja
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-circle text-secondary"></i> Media
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-circle text-warning"></i> Alta
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-circle text-danger"></i> Crítica
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="col-md-9">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Anuncios</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" name="table_search" class="form-control float-right" placeholder="Buscar">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mailbox Controls --}}
            <div class="card-body p-0">
                <div class="mailbox-controls">
                    <button type="button" class="btn btn-default btn-sm checkbox-toggle">
                        <i class="far fa-square"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm" title="Refrescar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-sm" title="Archivar Seleccionados">
                            <i class="fas fa-archive"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-sm" title="Eliminar Seleccionados">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                        Acciones <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#"><i class="fas fa-paper-plane"></i> Publicar Seleccionados</a>
                        <a class="dropdown-item" href="#"><i class="fas fa-clock"></i> Programar Seleccionados</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#"><i class="fas fa-archive"></i> Archivar Seleccionados</a>
                    </div>
                    <span class="float-right">
                        1-12/12
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-default btn-sm">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </span>
                </div>

                {{-- Announcements List --}}
                <div class="table-responsive mailbox-messages">
                    <table class="table table-hover">
                        <tbody>
                            {{-- Row 1: MAINTENANCE - PUBLISHED --}}
                            <tr class="type-maintenance">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check1">
                                        <label for="check1"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="fas fa-star text-warning"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-info"><i class="fas fa-tools"></i></span>
                                    <span class="badge badge-success">Publicado</span>
                                </td>
                                <td class="mailbox-subject">
                                    <b>Mantenimiento Programado del Servidor</b>
                                    <span class="metadata-preview">
                                        - Inicio: 25/11 02:00 | Fin: 25/11 06:00 | Servicios: Database, API
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-warning">Alta</span>
                                </td>
                                <td class="mailbox-date">18/11</td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-default" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-xs btn-default" title="Archivar"><i class="fas fa-archive"></i></button>
                                </td>
                            </tr>

                            {{-- Row 2: INCIDENT - PUBLISHED - UNRESOLVED --}}
                            <tr class="type-incident mailbox-unread-message">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check2">
                                        <label for="check2"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="fas fa-star text-warning"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                    <span class="badge badge-success">Publicado</span>
                                </td>
                                <td class="mailbox-subject">
                                    <b>Caída del Servicio de Autenticación</b>
                                    <span class="badge badge-warning badge-sm ml-2">Sin Resolver</span>
                                    <span class="metadata-preview">
                                        - Inicio: 19/11 09:00 | Servicios: authentication, login
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-danger">Crítica</span>
                                </td>
                                <td class="mailbox-date">19/11</td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-default" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-xs btn-success" title="Resolver"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-xs btn-default" title="Archivar"><i class="fas fa-archive"></i></button>
                                </td>
                            </tr>

                            {{-- Row 3: NEWS - SCHEDULED --}}
                            <tr class="type-news row-scheduled">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check3">
                                        <label for="check3"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="far fa-star"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-primary"><i class="fas fa-newspaper"></i></span>
                                    <span class="badge badge-warning">Programado</span>
                                </td>
                                <td class="mailbox-subject">
                                    <b>Nueva Funcionalidad: Dark Mode</b>
                                    <span class="metadata-preview">
                                        - Publica: 20/11 10:00 | Tipo: feature_release | Audiencia: users, agents
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-secondary">Media</span>
                                </td>
                                <td class="mailbox-date">
                                    <i class="fas fa-clock text-warning"></i> 20/11
                                </td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-default" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-xs btn-success" title="Publicar Ahora"><i class="fas fa-paper-plane"></i></button>
                                    <button class="btn btn-xs btn-warning" title="Desprogramar"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>

                            {{-- Row 4: ALERT - PUBLISHED --}}
                            <tr class="type-alert">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check4">
                                        <label for="check4"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="fas fa-star text-warning"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-warning"><i class="fas fa-shield-alt"></i></span>
                                    <span class="badge badge-success">Publicado</span>
                                </td>
                                <td class="mailbox-subject">
                                    <b>Alerta de Seguridad: Cambio de Contraseña</b>
                                    <span class="badge badge-danger badge-sm ml-2">Acción Requerida</span>
                                    <span class="metadata-preview">
                                        - Tipo: security | Inicio: 19/11 09:00 | Fin: 19/11 18:00
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-danger">Crítica</span>
                                </td>
                                <td class="mailbox-date">19/11</td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-default" title="Editar Fin"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-xs btn-default" title="Archivar"><i class="fas fa-archive"></i></button>
                                </td>
                            </tr>

                            {{-- Row 5: MAINTENANCE - DRAFT --}}
                            <tr class="type-maintenance row-draft">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check5">
                                        <label for="check5"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="far fa-star"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-info"><i class="fas fa-tools"></i></span>
                                    <span class="badge badge-secondary">Borrador</span>
                                </td>
                                <td class="mailbox-subject">
                                    <b>Actualización de Base de Datos</b>
                                    <span class="metadata-preview">
                                        - Inicio: 25/11 02:00 | Fin: 25/11 06:00 | Servicios: Database
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-warning">Alta</span>
                                </td>
                                <td class="mailbox-date">18/11</td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-default" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-xs btn-success" title="Publicar"><i class="fas fa-paper-plane"></i></button>
                                    <button class="btn btn-xs btn-warning" title="Programar"><i class="fas fa-clock"></i></button>
                                    <button class="btn btn-xs btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>

                            {{-- Row 6: NEWS - DRAFT --}}
                            <tr class="type-news row-draft">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check6">
                                        <label for="check6"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="far fa-star"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-primary"><i class="fas fa-newspaper"></i></span>
                                    <span class="badge badge-secondary">Borrador</span>
                                </td>
                                <td class="mailbox-subject">
                                    <b>Política de Privacidad Actualizada</b>
                                    <span class="metadata-preview">
                                        - Tipo: policy_update | Audiencia: todos
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-info">Baja</span>
                                </td>
                                <td class="mailbox-date">17/11</td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-default" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-xs btn-success" title="Publicar"><i class="fas fa-paper-plane"></i></button>
                                    <button class="btn btn-xs btn-warning" title="Programar"><i class="fas fa-clock"></i></button>
                                    <button class="btn btn-xs btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>

                            {{-- Row 7: INCIDENT - SCHEDULED --}}
                            <tr class="type-incident row-scheduled">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check7">
                                        <label for="check7"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="far fa-star"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                    <span class="badge badge-warning">Programado</span>
                                </td>
                                <td class="mailbox-subject">
                                    <b>Mantenimiento de Red Programado</b>
                                    <span class="metadata-preview">
                                        - Publica: 22/11 02:00 | Servicios: Network
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-warning">Alta</span>
                                </td>
                                <td class="mailbox-date">
                                    <i class="fas fa-clock text-warning"></i> 22/11
                                </td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-default" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-xs btn-success" title="Publicar Ahora"><i class="fas fa-paper-plane"></i></button>
                                    <button class="btn btn-xs btn-warning" title="Desprogramar"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>

                            {{-- Row 8: MAINTENANCE - ARCHIVED --}}
                            <tr class="type-maintenance row-archived">
                                <td>
                                    <div class="icheck-primary">
                                        <input type="checkbox" value="" id="check8">
                                        <label for="check8"></label>
                                    </div>
                                </td>
                                <td class="mailbox-star">
                                    <a href="#"><i class="far fa-star"></i></a>
                                </td>
                                <td class="mailbox-name">
                                    <span class="badge badge-info"><i class="fas fa-tools"></i></span>
                                    <span class="badge badge-secondary">Archivado</span>
                                </td>
                                <td class="mailbox-subject">
                                    <span class="text-muted">Actualización Completada de Seguridad</span>
                                    <span class="metadata-preview">
                                        - Completado: 15/11
                                    </span>
                                </td>
                                <td class="mailbox-date">
                                    <span class="badge badge-secondary">Media</span>
                                </td>
                                <td class="mailbox-date">15/11</td>
                                <td class="quick-actions">
                                    <button class="btn btn-xs btn-default" title="Ver"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-xs btn-success" title="Restaurar"><i class="fas fa-undo"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer p-0">
                <div class="mailbox-controls">
                    <button type="button" class="btn btn-default btn-sm checkbox-toggle">
                        <i class="far fa-square"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-sm">
                            <i class="fas fa-archive"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-sm">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    </div>
                    <span class="float-right">
                        1-12/12
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-default btn-sm">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Analysis Card --}}
<x-adminlte-card title="Análisis de la Propuesta 4: Vista Mailbox" theme="info" icon="fas fa-lightbulb" collapsible collapsed>
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-check-circle text-success"></i> Ventajas</h6>
            <ul class="small">
                <li><strong>Patrón familiar</strong> - Los usuarios conocen el paradigma de email/inbox</li>
                <li><strong>Sidebar de filtros</strong> - Navegación rápida por Estado, Tipo y Urgencia</li>
                <li><strong>Acciones bulk</strong> - Selección múltiple para publicar/archivar/eliminar varios</li>
                <li><strong>Metadata en línea</strong> - Preview de información clave sin abrir el anuncio</li>
                <li><strong>Densidad alta</strong> - Muchos anuncios visibles a la vez</li>
                <li><strong>Indicadores visuales</strong> - Borde de color por tipo, fondo por estado</li>
                <li><strong>Acciones contextuales</strong> - Botones cambian según estado y tipo</li>
                <li><strong>Favoritos</strong> - Sistema de estrellas para marcar importantes</li>
                <li><strong>Búsqueda integrada</strong> - Campo de búsqueda en header</li>
                <li><strong>Paginación</strong> - Controles de navegación en header y footer</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-star text-warning"></i> Características Clave</h6>
            <ul class="small">
                <li><strong>Layout 3 columnas:</strong>
                    <ul>
                        <li>Sidebar (3 cols): Carpetas, Tipos, Urgencia</li>
                        <li>Contenido (9 cols): Lista de anuncios</li>
                    </ul>
                </li>
                <li><strong>Información por fila:</strong>
                    <ul>
                        <li>Checkbox para selección</li>
                        <li>Estrella favorito</li>
                        <li>Badge de tipo + Badge de estado</li>
                        <li>Título + Metadata preview</li>
                        <li>Badge de urgencia</li>
                        <li>Fecha</li>
                        <li>Botones de acción rápida</li>
                    </ul>
                </li>
                <li><strong>Estados visuales:</strong>
                    <ul>
                        <li>Fondo gris claro = Borrador</li>
                        <li>Fondo amarillo claro = Programado</li>
                        <li>Fondo gris oscuro + opacidad = Archivado</li>
                    </ul>
                </li>
                <li><strong>Dropdown de acciones bulk:</strong> Publicar, Programar, Archivar múltiples</li>
            </ul>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <h6><i class="fas fa-lightbulb text-primary"></i> ¿Por qué este diseño es ideal?</h6>
            <ul class="small mb-0">
                <li>El patrón de mailbox es uno de los más intuitivos para gestionar listas de items con diferentes estados</li>
                <li>Permite ver toda la metadata importante sin necesidad de abrir cada anuncio</li>
                <li>Las acciones bulk reducen dramáticamente el tiempo de gestión</li>
                <li>El sidebar permite filtrar instantáneamente sin perder contexto</li>
                <li>Los badges de estado y tipo son inmediatamente reconocibles</li>
                <li>Es un patrón probado usado por Gmail, Outlook, y sistemas enterprise</li>
            </ul>
        </div>
    </div>
</x-adminlte-card>

@endsection

@section('js')
<script>
$(function () {
    // Toggle all checkboxes
    $('.checkbox-toggle').click(function () {
        var clicks = $(this).data('clicks');
        if (clicks) {
            $('.mailbox-messages input[type=\'checkbox\']').prop('checked', false);
            $('.checkbox-toggle .fa-check-square').removeClass('fa-check-square').addClass('fa-square');
        } else {
            $('.mailbox-messages input[type=\'checkbox\']').prop('checked', true);
            $('.checkbox-toggle .fa-square').removeClass('fa-square').addClass('fa-check-square');
        }
        $(this).data('clicks', !clicks);
    });

    // Toggle star
    $('.mailbox-star').click(function (e) {
        e.preventDefault();
        var $this = $(this).find('i');
        if ($this.hasClass('fa-star')) {
            $this.removeClass('fa-star').addClass('far fa-star');
        } else {
            $this.removeClass('far fa-star').addClass('fas fa-star');
        }
    });

    // Enable tooltips
    $('[title]').tooltip();
});
</script>
@endsection
