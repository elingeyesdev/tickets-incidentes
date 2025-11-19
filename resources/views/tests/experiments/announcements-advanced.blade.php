@extends('layouts.laboratory')

@section('title', 'Anuncios - Vista Avanzada')

@section('content_header', 'Propuesta 3: Gestión Avanzada de Anuncios con Tabs')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('tests.index') }}">Laboratorio Visual</a></li>
    <li class="breadcrumb-item active">Anuncios - Vista Avanzada</li>
@endsection

@section('css')
<style>
    .announcement-type-maintenance { border-left: 4px solid #17a2b8; }
    .announcement-type-incident { border-left: 4px solid #dc3545; }
    .announcement-type-news { border-left: 4px solid #007bff; }
    .announcement-type-alert { border-left: 4px solid #ffc107; }

    .metadata-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
        display: inline-block;
    }

    .timeline-item {
        border-left: 3px solid #dee2e6;
        padding-left: 1.5rem;
        position: relative;
        padding-bottom: 1rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -7px;
        top: 5px;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        background: #6c757d;
        border: 2px solid #fff;
    }

    .timeline-item.timeline-published::before { background: #28a745; }
    .timeline-item.timeline-scheduled::before { background: #ffc107; }
    .timeline-item.timeline-archived::before { background: #6c757d; }

    .action-buttons .btn {
        margin: 0.15rem;
    }

    .quick-stats {
        font-size: 1.1rem;
        font-weight: bold;
    }
</style>
@endsection

@section('content')

{{-- Info Callout --}}
<x-adminlte-callout theme="primary" icon="fas fa-flask">
    <strong>Propuesta 3: Vista Avanzada con Tabs y Organización por Estado</strong><br>
    Sistema de tabs que organiza los anuncios por estado, con filtros por tipo y metadata completa visible. Diseñada para gestión profesional de gran volumen de anuncios.
</x-adminlte-callout>

{{-- Statistics Row --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>4</h3>
                <p>Borradores</p>
            </div>
            <div class="icon">
                <i class="fas fa-pencil-alt"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>2</h3>
                <p>Programados</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>5</h3>
                <p>Publicados</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>1</h3>
                <p>Archivados</p>
            </div>
            <div class="icon">
                <i class="fas fa-archive"></i>
            </div>
        </div>
    </div>
</div>

{{-- Main Card with Tabs --}}
<x-adminlte-card title="Gestión de Anuncios" icon="fas fa-bullhorn" theme="primary" maximizable>
    <x-slot name="toolsSlot">
        <button type="button" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nuevo Anuncio
        </button>
    </x-slot>

    {{-- Tabs Navigation --}}
    <ul class="nav nav-tabs" id="announcementTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="draft-tab" data-toggle="tab" href="#draft" role="tab">
                <i class="fas fa-pencil-alt"></i> Borradores <span class="badge badge-secondary">4</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="scheduled-tab" data-toggle="tab" href="#scheduled" role="tab">
                <i class="fas fa-clock"></i> Programados <span class="badge badge-warning">2</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="published-tab" data-toggle="tab" href="#published" role="tab">
                <i class="fas fa-check-circle"></i> Publicados <span class="badge badge-success">5</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="archived-tab" data-toggle="tab" href="#archived" role="tab">
                <i class="fas fa-archive"></i> Archivados <span class="badge badge-secondary">1</span>
            </a>
        </li>
    </ul>

    {{-- Tabs Content --}}
    <div class="tab-content" id="announcementTabsContent">
        {{-- DRAFT TAB --}}
        <div class="tab-pane fade show active" id="draft" role="tabpanel">
            <div class="p-3">
                {{-- Filter Bar --}}
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control form-control-sm">
                            <option>Todos los tipos</option>
                            <option>Mantenimiento</option>
                            <option>Incidente</option>
                            <option>Noticias</option>
                            <option>Alerta</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm">
                            <option>Todas las urgencias</option>
                            <option>Baja</option>
                            <option>Media</option>
                            <option>Alta</option>
                            <option>Crítica</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" placeholder="Buscar...">
                    </div>
                </div>

                {{-- Draft Announcement 1: MAINTENANCE --}}
                <div class="card announcement-type-maintenance mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <span class="badge badge-info"><i class="fas fa-tools"></i> MAINTENANCE</span>
                                    <span class="badge badge-warning">Alta</span>
                                </h5>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> Juan Pérez |
                                <i class="fas fa-calendar"></i> Creado: 18/11/2025 14:30
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title">Actualización de Base de Datos</h4>
                        <p class="text-muted">Migración de base de datos a nueva versión PostgreSQL 15. Se espera una mejora del 30% en rendimiento...</p>

                        {{-- Metadata Section --}}
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2"><strong>Metadata:</strong></small>
                            <span class="metadata-badge badge badge-light">
                                <i class="far fa-calendar-alt"></i> Inicio: 25/11/2025 02:00
                            </span>
                            <span class="metadata-badge badge badge-light">
                                <i class="far fa-calendar-check"></i> Fin: 25/11/2025 06:00
                            </span>
                            <span class="metadata-badge badge badge-light">
                                <i class="fas fa-server"></i> Servicios: Database, API
                            </span>
                            <span class="metadata-badge badge badge-danger">
                                <i class="fas fa-exclamation-triangle"></i> No es emergencia
                            </span>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-default text-teal shadow">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-sm btn-default text-primary shadow">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-default text-success shadow">
                                <i class="fas fa-paper-plane"></i> Publicar Ahora
                            </button>
                            <button class="btn btn-sm btn-default text-warning shadow">
                                <i class="fas fa-clock"></i> Programar
                            </button>
                            <button class="btn btn-sm btn-default text-danger shadow">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Draft Announcement 2: NEWS --}}
                <div class="card announcement-type-news mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <span class="badge badge-primary"><i class="fas fa-newspaper"></i> NEWS</span>
                                    <span class="badge badge-info">Baja</span>
                                </h5>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> Ana Martínez |
                                <i class="fas fa-calendar"></i> Creado: 17/11/2025 10:15
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title">Política de Privacidad Actualizada</h4>
                        <p class="text-muted">Hemos actualizado nuestra política de privacidad para cumplir con las nuevas regulaciones...</p>

                        {{-- Metadata Section --}}
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2"><strong>Metadata:</strong></small>
                            <span class="metadata-badge badge badge-light">
                                <i class="fas fa-tag"></i> Tipo: policy_update
                            </span>
                            <span class="metadata-badge badge badge-light">
                                <i class="fas fa-users"></i> Audiencia: users, agents, admins
                            </span>
                            <span class="metadata-badge badge badge-info">
                                <i class="fas fa-external-link-alt"></i> CTA: Leer más
                            </span>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-default text-teal shadow">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-sm btn-default text-primary shadow">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-default text-success shadow">
                                <i class="fas fa-paper-plane"></i> Publicar Ahora
                            </button>
                            <button class="btn btn-sm btn-default text-warning shadow">
                                <i class="fas fa-clock"></i> Programar
                            </button>
                            <button class="btn btn-sm btn-default text-danger shadow">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-center text-muted py-3">
                    <p>Mostrando 2 de 4 borradores <a href="#" class="text-primary">Ver todos</a></p>
                </div>
            </div>
        </div>

        {{-- SCHEDULED TAB --}}
        <div class="tab-pane fade" id="scheduled" role="tabpanel">
            <div class="p-3">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> Los anuncios programados se publicarán automáticamente en la fecha/hora especificada.
                </div>

                {{-- Scheduled Announcement 1: NEWS --}}
                <div class="card announcement-type-news mb-3 shadow-sm">
                    <div class="card-header bg-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <span class="badge badge-primary"><i class="fas fa-newspaper"></i> NEWS</span>
                                    <span class="badge badge-light">Media</span>
                                </h5>
                            </div>
                            <small>
                                <i class="fas fa-user"></i> Carlos López
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title">Nueva Funcionalidad: Dark Mode</h4>
                        <p class="text-muted">Estamos emocionados de anunciar el lanzamiento de Dark Mode en nuestra plataforma...</p>

                        <div class="alert alert-info mb-3">
                            <i class="fas fa-clock"></i> <strong>Publicación Programada:</strong>
                            20/11/2025 a las 10:00 AM (en 2 días)
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block mb-2"><strong>Metadata:</strong></small>
                            <span class="metadata-badge badge badge-light">
                                <i class="fas fa-tag"></i> feature_release
                            </span>
                            <span class="metadata-badge badge badge-light">
                                <i class="fas fa-users"></i> users, agents
                            </span>
                        </div>

                        <div class="action-buttons">
                            <button class="btn btn-sm btn-default text-teal shadow">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-sm btn-default text-primary shadow">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-default text-success shadow">
                                <i class="fas fa-paper-plane"></i> Publicar Ahora
                            </button>
                            <button class="btn btn-sm btn-default text-warning shadow">
                                <i class="fas fa-times-circle"></i> Desprogramar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-center text-muted py-3">
                    <p>Mostrando 1 de 2 programados</p>
                </div>
            </div>
        </div>

        {{-- PUBLISHED TAB --}}
        <div class="tab-pane fade" id="published" role="tabpanel">
            <div class="p-3">
                {{-- Sub-tabs for types --}}
                <ul class="nav nav-pills mb-3" id="publishedTypeTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#pub-all">Todos (5)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#pub-maintenance">
                            <i class="fas fa-tools text-info"></i> Mantenimiento (2)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#pub-incident">
                            <i class="fas fa-exclamation-triangle text-danger"></i> Incidentes (1)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#pub-news">
                            <i class="fas fa-newspaper text-primary"></i> Noticias (1)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#pub-alert">
                            <i class="fas fa-shield-alt text-warning"></i> Alertas (1)
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pub-all">
                        {{-- Published ALERT --}}
                        <div class="card announcement-type-alert mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">
                                            <span class="badge badge-danger"><i class="fas fa-shield-alt"></i> ALERT</span>
                                            <span class="badge badge-danger">Crítica</span>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Publicado</span>
                                        </h5>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> Publicado: 19/11/2025 11:45
                                    </small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title">Alerta de Seguridad: Cambio de Contraseña Requerido</h4>
                                <p class="text-muted">Se detectó actividad sospechosa. Por favor cambiar contraseña inmediatamente...</p>

                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2"><strong>Metadata de Alerta:</strong></small>
                                    <span class="metadata-badge badge badge-danger">
                                        <i class="fas fa-tag"></i> Tipo: security
                                    </span>
                                    <span class="metadata-badge badge badge-danger">
                                        <i class="fas fa-exclamation-circle"></i> Acción Requerida: Sí
                                    </span>
                                    <span class="metadata-badge badge badge-light">
                                        <i class="far fa-calendar"></i> Inicio: 19/11/2025 09:00
                                    </span>
                                    <span class="metadata-badge badge badge-light">
                                        <i class="far fa-calendar-check"></i> Fin: 19/11/2025 18:00
                                    </span>
                                    <span class="metadata-badge badge badge-warning">
                                        <i class="fas fa-server"></i> authentication, user_management
                                    </span>
                                </div>

                                <div class="alert alert-danger small">
                                    <strong><i class="fas fa-hand-point-right"></i> Acción Requerida:</strong>
                                    Navigate to Settings > Security and update your password
                                </div>

                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-default text-teal shadow">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                    <button class="btn btn-sm btn-default text-primary shadow" title="Solo se puede editar ended_at">
                                        <i class="fas fa-edit"></i> Editar Fin
                                    </button>
                                    <button class="btn btn-sm btn-default text-secondary shadow">
                                        <i class="fas fa-archive"></i> Archivar
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Published INCIDENT --}}
                        <div class="card announcement-type-incident mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">
                                            <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> INCIDENT</span>
                                            <span class="badge badge-danger">Crítica</span>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Publicado</span>
                                            <span class="badge badge-warning"><i class="fas fa-wrench"></i> Sin Resolver</span>
                                        </h5>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> Publicado: 19/11/2025 09:15
                                    </small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title">Caída del Servicio de Autenticación</h4>
                                <p class="text-muted">Se reporta falla en el sistema de autenticación. Los usuarios pueden experimentar problemas...</p>

                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2"><strong>Metadata de Incidente:</strong></small>
                                    <span class="metadata-badge badge badge-light">
                                        <i class="far fa-calendar"></i> Inicio: 19/11/2025 09:00
                                    </span>
                                    <span class="metadata-badge badge badge-light">
                                        <i class="fas fa-server"></i> Servicios: authentication, login
                                    </span>
                                    <span class="metadata-badge badge badge-warning">
                                        <i class="fas fa-times-circle"></i> No Resuelto
                                    </span>
                                </div>

                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-default text-teal shadow">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                    <button class="btn btn-sm btn-default text-primary shadow">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-default text-success shadow">
                                        <i class="fas fa-check-double"></i> Marcar Resuelto
                                    </button>
                                    <button class="btn btn-sm btn-default text-secondary shadow">
                                        <i class="fas fa-archive"></i> Archivar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-center text-muted py-3">
                            <p>Mostrando 2 de 5 publicados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ARCHIVED TAB --}}
        <div class="tab-pane fade" id="archived" role="tabpanel">
            <div class="p-3">
                <div class="alert alert-secondary">
                    <i class="fas fa-info-circle"></i> Los anuncios archivados pueden ser restaurados a borrador para editarlos nuevamente.
                </div>

                <div class="card announcement-type-maintenance mb-3" style="opacity: 0.7;">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <span class="badge badge-info"><i class="fas fa-tools"></i> MAINTENANCE</span>
                                    <span class="badge badge-secondary"><i class="fas fa-archive"></i> Archivado</span>
                                </h5>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> Archivado: 16/11/2025
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title">Actualización Completada de Seguridad</h4>
                        <p class="text-muted">Se completó exitosamente la actualización de seguridad v2.3.1...</p>

                        <div class="action-buttons">
                            <button class="btn btn-sm btn-default text-teal shadow">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-sm btn-default text-success shadow">
                                <i class="fas fa-undo"></i> Restaurar a Borrador
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-adminlte-card>

{{-- Analysis Card --}}
<x-adminlte-card title="Análisis de la Propuesta 3: Vista Avanzada con Tabs" theme="info" icon="fas fa-lightbulb" collapsible collapsed>
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-check-circle text-success"></i> Ventajas</h6>
            <ul class="small">
                <li><strong>Organización clara por estado</strong> - Tabs principales separan Draft, Scheduled, Published, Archived</li>
                <li><strong>Sub-organización por tipo</strong> - En Published, sub-tabs filtran por tipo de anuncio</li>
                <li><strong>Metadata completa visible</strong> - Toda la información específica del tipo se muestra con badges</li>
                <li><strong>Acciones contextuales claras</strong> - Botones cambian según el estado y tipo</li>
                <li><strong>Indicadores visuales</strong> - Borde de color por tipo, badges de urgencia y estado</li>
                <li><strong>Estadísticas en tiempo real</strong> - Small-boxes muestran totales por estado</li>
                <li><strong>Escalable</strong> - Fácil agregar más filtros o acciones</li>
                <li><strong>Profesional</strong> - Diseño limpio ideal para administradores</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-star text-warning"></i> Características Clave</h6>
            <ul class="small">
                <li><strong>Sistema de Tabs de 2 niveles:</strong> Estado principal + Tipo secundario</li>
                <li><strong>Metadata por tipo visible:</strong>
                    <ul>
                        <li>MAINTENANCE: scheduled_start/end, actual_start/end, is_emergency, affected_services</li>
                        <li>INCIDENT: started_at, ended_at, is_resolved, resolution_content, affected_services</li>
                        <li>NEWS: news_type, target_audience, summary, call_to_action</li>
                        <li>ALERT: alert_type, urgency, action_required, action_description, started_at, ended_at</li>
                    </ul>
                </li>
                <li><strong>Alertas contextuales:</strong> Info sobre programación, acciones requeridas, etc.</li>
                <li><strong>Filtros por tab:</strong> Tipo, urgencia, búsqueda</li>
                <li><strong>Acciones específicas:</strong>
                    <ul>
                        <li>INCIDENT: Marcar Resuelto</li>
                        <li>ALERT: Solo editar ended_at cuando publicado</li>
                        <li>SCHEDULED: Desprogramar</li>
                        <li>ARCHIVED: Restaurar</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <h6><i class="fas fa-lightbulb text-primary"></i> ¿Por qué esta propuesta es mejor para tu caso?</h6>
            <ul class="small mb-0">
                <li>La complejidad de tu sistema (4 tipos, 4 estados, múltiples acciones) requiere una organización clara</li>
                <li>Los tabs permiten enfocarse en un estado a la vez sin sobrecarga visual</li>
                <li>La metadata específica por tipo es crítica - esta vista la hace completamente visible</li>
                <li>Las acciones contextuales previenen errores (ej: no puedes desprogramar un borrador)</li>
                <li>Escalable para agregar más tipos o estados en el futuro</li>
                <li>Diseño profesional apropiado para un sistema enterprise</li>
            </ul>
        </div>
    </div>
</x-adminlte-card>

@endsection

@section('js')
<script>
    $(function () {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Tab activation
        $('#announcementTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    })
</script>
@endsection
