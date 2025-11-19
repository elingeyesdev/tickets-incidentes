@extends('layouts.laboratory')

@section('title', 'Anuncios - Vista Dividida')

@section('content_header')
    Gestión de Anuncios
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="#">Empresa</a></li>
    <li class="breadcrumb-item active">Anuncios</li>
@endsection

@section('content')
<div class="row">
    {{-- ===== SECCIÓN 1: FEED DE PUBLICADOS ===== --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-broadcast-tower mr-2"></i>
                    Anuncios Publicados
                </h3>
                <div class="card-tools">
                    <span class="badge badge-secondary">12 publicados</span>
                </div>
            </div>

            {{-- Filtros del Feed --}}
            <div class="card-body border-bottom pb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <select class="form-control form-control-sm">
                                <option value="">Todos los tipos</option>
                                <option value="NEWS">Noticias</option>
                                <option value="MAINTENANCE">Mantenimiento</option>
                                <option value="INCIDENT">Incidentes</option>
                                <option value="ALERT">Alertas</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <select class="form-control form-control-sm">
                                <option value="">Cualquier urgencia</option>
                                <option value="low">Baja</option>
                                <option value="medium">Media</option>
                                <option value="high">Alta</option>
                                <option value="critical">Crítica</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" placeholder="Buscar...">
                            <div class="input-group-append">
                                <button class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Feed/Timeline de Anuncios --}}
            <div class="card-body p-0">
                <div class="timeline timeline-inverse p-3">

                    {{-- Incidente Activo --}}
                    <div class="time-label">
                        <span class="bg-secondary">Hoy</span>
                    </div>
                    <div>
                        <i class="fas fa-exclamation-triangle bg-danger"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 10:30</span>
                            <h3 class="timeline-header">
                                <span class="badge badge-danger mr-2">Incidente</span>
                                Problemas de Conectividad en Red Principal
                            </h3>
                            <div class="timeline-body">
                                Se han detectado problemas de conectividad que afectan a múltiples servicios.
                                El equipo técnico está trabajando en la resolución.
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-server mr-1"></i> Servicios: Email, VPN, Intranet
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-bolt mr-1"></i> Urgencia: Alta
                                    </small>
                                </div>
                            </div>
                            <div class="timeline-footer">
                                <span class="badge badge-warning">En Investigación</span>
                            </div>
                        </div>
                    </div>

                    {{-- Mantenimiento Programado --}}
                    <div>
                        <i class="fas fa-tools bg-warning"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 08:00</span>
                            <h3 class="timeline-header">
                                <span class="badge badge-warning mr-2">Mantenimiento</span>
                                Actualización del Sistema de Tickets
                            </h3>
                            <div class="timeline-body">
                                Mantenimiento programado para actualizar el sistema de gestión de tickets.
                                Se esperan mejoras en rendimiento y nuevas funcionalidades.
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar mr-1"></i> 22 Nov, 02:00 - 06:00
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-server mr-1"></i> Sistema de Tickets
                                    </small>
                                </div>
                            </div>
                            <div class="timeline-footer">
                                <span class="badge badge-info">Programado</span>
                            </div>
                        </div>
                    </div>

                    {{-- Noticia --}}
                    <div class="time-label">
                        <span class="bg-secondary">18 Nov 2024</span>
                    </div>
                    <div>
                        <i class="fas fa-newspaper bg-info"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 14:00</span>
                            <h3 class="timeline-header">
                                <span class="badge badge-info mr-2">Noticia</span>
                                Nueva Política de Seguridad Implementada
                            </h3>
                            <div class="timeline-body">
                                Se ha implementado una nueva política de seguridad que requiere
                                autenticación de dos factores para todos los usuarios.
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Acción requerida
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Alerta --}}
                    <div>
                        <i class="fas fa-bell bg-secondary"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 09:00</span>
                            <h3 class="timeline-header">
                                <span class="badge badge-secondary mr-2">Alerta</span>
                                Recordatorio: Cambio de Contraseñas
                            </h3>
                            <div class="timeline-body">
                                Se recuerda a todos los usuarios que deben cambiar sus contraseñas
                                antes del fin de mes como parte del protocolo de seguridad.
                            </div>
                        </div>
                    </div>

                    {{-- Incidente Resuelto --}}
                    <div class="time-label">
                        <span class="bg-secondary">15 Nov 2024</span>
                    </div>
                    <div>
                        <i class="fas fa-check-circle bg-success"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 16:45</span>
                            <h3 class="timeline-header">
                                <span class="badge badge-success mr-2">Resuelto</span>
                                Interrupción del Servicio de Email - SOLUCIONADO
                            </h3>
                            <div class="timeline-body">
                                El problema con el servicio de email ha sido resuelto.
                                Todos los servicios funcionan con normalidad.
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-history mr-1"></i> Duración: 2h 30m
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <i class="fas fa-clock bg-light"></i>
                    </div>
                </div>
            </div>

            {{-- Paginación --}}
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <li class="page-item"><a class="page-link" href="#">&laquo;</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ===== SECCIÓN 2: GESTIÓN ===== --}}
    <div class="col-lg-5">
        {{-- Botón Crear Nuevo --}}
        <div class="card card-outline card-primary">
            <div class="card-body text-center py-4">
                <button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#modal-crear">
                    <i class="fas fa-plus mr-2"></i>
                    Crear Nuevo Anuncio
                </button>
            </div>
        </div>

        {{-- Pestañas de Gestión --}}
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="management-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-borradores">
                            Borradores <span class="badge badge-light ml-1">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-programados">
                            Programados <span class="badge badge-light ml-1">2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-archivados">
                            Archivados <span class="badge badge-light ml-1">8</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content">
                    {{-- Tab: Borradores --}}
                    <div class="tab-pane fade show active" id="tab-borradores">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Actualización de Horarios</h6>
                                        <small class="text-muted">
                                            <span class="badge badge-light">Noticia</span>
                                            Modificado hace 2 horas
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" title="Publicar">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Programar">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Mantenimiento Base de Datos</h6>
                                        <small class="text-muted">
                                            <span class="badge badge-light">Mantenimiento</span>
                                            Modificado hace 1 día
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" title="Publicar">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Programar">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Nuevo Protocolo de Emergencias</h6>
                                        <small class="text-muted">
                                            <span class="badge badge-light">Alerta</span>
                                            Modificado hace 3 días
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" title="Publicar">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Programar">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    {{-- Tab: Programados --}}
                    <div class="tab-pane fade" id="tab-programados">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Migración a Nuevo Servidor</h6>
                                        <small class="text-muted d-block">
                                            <span class="badge badge-light">Mantenimiento</span>
                                        </small>
                                        <small class="text-info">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            Publica: 25 Nov 2024, 00:00
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" title="Desprogramar">
                                            <i class="fas fa-calendar-times"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" title="Publicar Ahora">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Cierre por Festividad</h6>
                                        <small class="text-muted d-block">
                                            <span class="badge badge-light">Noticia</span>
                                        </small>
                                        <small class="text-info">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            Publica: 1 Dic 2024, 08:00
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" title="Desprogramar">
                                            <i class="fas fa-calendar-times"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" title="Publicar Ahora">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    {{-- Tab: Archivados --}}
                    <div class="tab-pane fade" id="tab-archivados">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-muted">Actualización Sistema v2.1</h6>
                                        <small class="text-muted">
                                            <span class="badge badge-light">Noticia</span>
                                            Archivado hace 1 semana
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-muted">Incidente Resuelto - API</h6>
                                        <small class="text-muted">
                                            <span class="badge badge-light">Incidente</span>
                                            Archivado hace 2 semanas
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-muted">Mantenimiento Octubre</h6>
                                        <small class="text-muted">
                                            <span class="badge badge-light">Mantenimiento</span>
                                            Archivado hace 1 mes
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        {{-- Ver más archivados --}}
                        <div class="text-center py-2 border-top">
                            <a href="#" class="text-muted small">
                                Ver todos los archivados (8)
                                <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estadísticas Compactas --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Resumen
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td>Total Publicados</td>
                            <td class="text-right"><strong>12</strong></td>
                        </tr>
                        <tr>
                            <td>Incidentes Activos</td>
                            <td class="text-right"><strong class="text-danger">1</strong></td>
                        </tr>
                        <tr>
                            <td>Mantenimientos Próximos</td>
                            <td class="text-right"><strong class="text-warning">2</strong></td>
                        </tr>
                        <tr>
                            <td>Publicados este mes</td>
                            <td class="text-right"><strong>5</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    /* Timeline más compacto y menos colorido */
    .timeline {
        margin: 0;
        padding: 0;
    }

    .timeline > div > .timeline-item {
        box-shadow: none;
        border: 1px solid #dee2e6;
        border-radius: .25rem;
        margin-left: 60px;
        margin-right: 0;
        margin-bottom: 15px;
        padding: 0;
    }

    .timeline > div > .timeline-item > .timeline-header {
        padding: 10px 15px;
        border-bottom: 1px solid #dee2e6;
        font-size: .9rem;
        font-weight: 600;
    }

    .timeline > div > .timeline-item > .timeline-body {
        padding: 10px 15px;
        font-size: .85rem;
    }

    .timeline > div > .timeline-item > .timeline-footer {
        padding: 8px 15px;
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }

    .timeline > div > .timeline-item > .time {
        font-size: .75rem;
        padding: 5px 10px;
    }

    /* Iconos del timeline más sutiles */
    .timeline > div > i {
        width: 30px;
        height: 30px;
        line-height: 30px;
        font-size: .8rem;
    }

    /* Time labels */
    .time-label > span {
        padding: 3px 10px;
        font-size: .75rem;
        font-weight: 600;
    }

    /* Badges más sutiles */
    .badge-light {
        background-color: #e9ecef;
        color: #495057;
    }

    /* Lista de gestión */
    .list-group-item h6 {
        font-size: .9rem;
        margin-bottom: .25rem;
    }

    /* Tabs de gestión */
    #management-tabs .nav-link {
        padding: .5rem 1rem;
        font-size: .85rem;
    }

    /* Botones de acción más compactos */
    .btn-group-sm .btn {
        padding: .2rem .4rem;
    }
</style>
@endsection
