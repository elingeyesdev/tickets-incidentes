@extends('layouts.laboratory')

@section('title', 'Anuncios - Vista Cards')

@section('content_header', 'Propuesta 2: Gestión de Anuncios - Vista Cards')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tests.index') }}">Laboratorio Visual</a></li>
    <li class="breadcrumb-item active">Anuncios - Vista Cards</li>
@endsection

@section('css')
<style>
    .announcement-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .announcement-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .card-scheduled {
        border-left: 5px solid #ffc107 !important;
    }
    .card-published {
        border-left: 5px solid #28a745 !important;
    }
    .card-draft {
        border-left: 5px solid #6c757d !important;
    }
    .card-archived {
        border-left: 5px solid #adb5bd !important;
        opacity: 0.7;
    }
</style>
@endsection

@section('content')

{{-- Info Callout --}}
<x-adminlte-callout theme="warning" icon="fas fa-flask">
    <strong>Propuesta 2: Vista de Cards Visual</strong><br>
    Vista moderna con tarjetas que muestran más información de forma visual. Ideal para pantallas grandes y enfoque en contenido. Todos los datos son ejemplos hardcodeados.
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

{{-- Filters and Actions Bar --}}
<x-adminlte-card theme="primary" icon="fas fa-filter" title="Filtros y Acciones" collapsible collapsed>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label>Buscar</label>
                <input type="text" class="form-control" placeholder="Título o contenido...">
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>Tipo</label>
                <select class="form-control">
                    <option value="">Todos</option>
                    <option value="MAINTENANCE">Mantenimiento</option>
                    <option value="INCIDENT">Incidente</option>
                    <option value="NEWS">Noticias</option>
                    <option value="ALERT">Alerta</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>Estado</label>
                <select class="form-control">
                    <option value="">Todos</option>
                    <option value="DRAFT">Borrador</option>
                    <option value="SCHEDULED">Programado</option>
                    <option value="PUBLISHED">Publicado</option>
                    <option value="ARCHIVED">Archivado</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>Urgencia</label>
                <select class="form-control">
                    <option value="">Todas</option>
                    <option value="LOW">Baja</option>
                    <option value="MEDIUM">Media</option>
                    <option value="HIGH">Alta</option>
                    <option value="CRITICAL">Crítica</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <label>&nbsp;</label>
            <div>
                <button class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i> Crear Anuncio
                </button>
            </div>
        </div>
    </div>
</x-adminlte-card>

{{-- Announcements Cards Grid --}}
<div class="row">
    {{-- Card 1: MAINTENANCE - PUBLISHED --}}
    <div class="col-md-6">
        <div class="card card-outline card-info announcement-card card-published">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-info">
                            <i class="fas fa-tools"></i> Mantenimiento
                        </span>
                        <span class="badge badge-warning ml-1">Alta</span>
                    </div>
                    <span class="badge badge-success">
                        <i class="fas fa-check-circle"></i> Publicado
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Mantenimiento Programado del Servidor</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    El servidor estará en mantenimiento el 25/11/2025 de 02:00 a 06:00. Durante este tiempo el sistema no estará disponible. Por favor planifique en consecuencia.
                </p>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> Juan Pérez
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-calendar"></i> 18/11/2025 14:30
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-primary shadow" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-default text-secondary shadow" title="Archivar">
                        <i class="fas fa-archive"></i> Archivar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: INCIDENT - PUBLISHED --}}
    <div class="col-md-6">
        <div class="card card-outline card-danger announcement-card card-published">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-danger">
                            <i class="fas fa-exclamation-triangle"></i> Incidente
                        </span>
                        <span class="badge badge-danger ml-1">Crítica</span>
                    </div>
                    <span class="badge badge-success">
                        <i class="fas fa-check-circle"></i> Publicado
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Caída del Servicio de Autenticación</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    Se reporta falla en el sistema de autenticación. Los usuarios pueden experimentar problemas al iniciar sesión. El equipo técnico está trabajando en una solución.
                </p>
                <div class="alert alert-warning mb-2 py-1 px-2 small">
                    <i class="fas fa-wrench"></i> <strong>Servicios Afectados:</strong> Autenticación, Login
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> María García
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-calendar"></i> 19/11/2025 09:15
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-primary shadow" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-default text-success shadow" title="Resolver">
                        <i class="fas fa-check"></i> Resolver
                    </button>
                    <button class="btn btn-default text-secondary shadow" title="Archivar">
                        <i class="fas fa-archive"></i> Archivar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 3: NEWS - SCHEDULED --}}
    <div class="col-md-6">
        <div class="card card-outline card-primary announcement-card card-scheduled">
            <div class="card-header bg-warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-primary">
                            <i class="fas fa-newspaper"></i> Noticias
                        </span>
                        <span class="badge badge-secondary ml-1">Media</span>
                    </div>
                    <span class="badge badge-warning">
                        <i class="fas fa-clock"></i> Programado
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Nueva Funcionalidad: Dark Mode</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    Estamos emocionados de anunciar el lanzamiento de Dark Mode en nuestra plataforma. Los usuarios ahora pueden cambiar entre modo claro y oscuro desde la configuración.
                </p>
                <div class="alert alert-info mb-2 py-1 px-2 small">
                    <i class="fas fa-clock"></i> <strong>Publicación Programada:</strong> 20/11/2025 a las 10:00
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> Carlos López
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-calendar-alt text-warning"></i> Pendiente
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-primary shadow" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-default text-success shadow" title="Publicar Ahora">
                        <i class="fas fa-paper-plane"></i> Publicar
                    </button>
                    <button class="btn btn-default text-warning shadow" title="Desprogramar">
                        <i class="fas fa-times-circle"></i> Desprogramar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 4: ALERT - PUBLISHED --}}
    <div class="col-md-6">
        <div class="card card-outline card-danger announcement-card card-published">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-danger">
                            <i class="fas fa-shield-alt"></i> Alerta
                        </span>
                        <span class="badge badge-danger ml-1">Crítica</span>
                    </div>
                    <span class="badge badge-success">
                        <i class="fas fa-check-circle"></i> Publicado
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Alerta de Seguridad: Cambio de Contraseña Requerido</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    Se detectó actividad sospechosa en algunos cuentas. Por favor cambie su contraseña inmediatamente y active la autenticación de dos factores.
                </p>
                <div class="alert alert-danger mb-2 py-1 px-2 small">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Acción Requerida:</strong> Cambiar contraseña en Configuración > Seguridad
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> Admin Sistema
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-calendar"></i> 19/11/2025 11:45
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-primary shadow" title="Editar ended_at">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-default text-secondary shadow" title="Archivar">
                        <i class="fas fa-archive"></i> Archivar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 5: MAINTENANCE - DRAFT --}}
    <div class="col-md-6">
        <div class="card card-outline card-info announcement-card card-draft">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-info">
                            <i class="fas fa-tools"></i> Mantenimiento
                        </span>
                        <span class="badge badge-warning ml-1">Alta</span>
                    </div>
                    <span class="badge badge-secondary">
                        <i class="fas fa-pencil-alt"></i> Borrador
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Actualización de Base de Datos</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    Migración de base de datos a nueva versión PostgreSQL 15. Se espera una mejora del 30% en rendimiento de consultas complejas.
                </p>
                <div class="alert alert-secondary mb-2 py-1 px-2 small">
                    <i class="fas fa-info-circle"></i> <strong>Estado:</strong> En edición, no visible para usuarios
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> Juan Pérez
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-edit text-secondary"></i> Sin publicar
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-primary shadow" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-default text-success shadow" title="Publicar">
                        <i class="fas fa-paper-plane"></i> Publicar
                    </button>
                    <button class="btn btn-default text-warning shadow" title="Programar">
                        <i class="fas fa-clock"></i> Programar
                    </button>
                    <button class="btn btn-default text-danger shadow" title="Eliminar">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 6: NEWS - DRAFT --}}
    <div class="col-md-6">
        <div class="card card-outline card-primary announcement-card card-draft">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-primary">
                            <i class="fas fa-newspaper"></i> Noticias
                        </span>
                        <span class="badge badge-info ml-1">Baja</span>
                    </div>
                    <span class="badge badge-secondary">
                        <i class="fas fa-pencil-alt"></i> Borrador
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Política de Privacidad Actualizada</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    Hemos actualizado nuestra política de privacidad para cumplir con las nuevas regulaciones de protección de datos. Por favor revise los cambios.
                </p>
                <div class="alert alert-info mb-2 py-1 px-2 small">
                    <i class="fas fa-users"></i> <strong>Audiencia:</strong> Todos los usuarios
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> Ana Martínez
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-edit text-secondary"></i> Sin publicar
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-primary shadow" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-default text-success shadow" title="Publicar">
                        <i class="fas fa-paper-plane"></i> Publicar
                    </button>
                    <button class="btn btn-default text-warning shadow" title="Programar">
                        <i class="fas fa-clock"></i> Programar
                    </button>
                    <button class="btn btn-default text-danger shadow" title="Eliminar">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 7: INCIDENT - SCHEDULED --}}
    <div class="col-md-6">
        <div class="card card-outline card-danger announcement-card card-scheduled">
            <div class="card-header bg-warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-danger">
                            <i class="fas fa-exclamation-triangle"></i> Incidente
                        </span>
                        <span class="badge badge-warning ml-1">Alta</span>
                    </div>
                    <span class="badge badge-warning">
                        <i class="fas fa-clock"></i> Programado
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Mantenimiento de Red Programado</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    Corte programado de conectividad para actualización de infraestructura de red. El servicio estará interrumpido temporalmente.
                </p>
                <div class="alert alert-info mb-2 py-1 px-2 small">
                    <i class="fas fa-clock"></i> <strong>Publicación Programada:</strong> 22/11/2025 a las 02:00
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> Carlos López
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-calendar-alt text-warning"></i> Pendiente
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-primary shadow" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-default text-success shadow" title="Publicar Ahora">
                        <i class="fas fa-paper-plane"></i> Publicar
                    </button>
                    <button class="btn btn-default text-warning shadow" title="Desprogramar">
                        <i class="fas fa-times-circle"></i> Desprogramar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 8: MAINTENANCE - ARCHIVED --}}
    <div class="col-md-6">
        <div class="card card-outline card-secondary announcement-card card-archived">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge badge-info">
                            <i class="fas fa-tools"></i> Mantenimiento
                        </span>
                        <span class="badge badge-secondary ml-1">Media</span>
                    </div>
                    <span class="badge badge-secondary">
                        <i class="fas fa-archive"></i> Archivado
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-2">
                    <strong>Actualización Completada de Seguridad</strong>
                </h5>
                <p class="card-text text-muted small mb-3">
                    Se completó exitosamente la actualización de seguridad v2.3.1. Todos los parches críticos han sido aplicados correctamente.
                </p>
                <div class="alert alert-secondary mb-2 py-1 px-2 small">
                    <i class="fas fa-check-circle"></i> <strong>Estado:</strong> Archivado - Ya no visible para usuarios
                </div>
                <div class="row small text-muted">
                    <div class="col-6">
                        <i class="fas fa-user"></i> Juan Pérez
                    </div>
                    <div class="col-6 text-right">
                        <i class="fas fa-calendar"></i> 15/11/2025 18:00
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-default text-teal shadow" title="Ver">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-default text-success shadow" title="Restaurar">
                        <i class="fas fa-undo"></i> Restaurar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pagination --}}
<div class="card">
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Mostrando 8 de 12 anuncios</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                    <li class="page-item active"><span class="page-link">1</span></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

{{-- Analysis Card --}}
<x-adminlte-card title="Análisis de la Propuesta 2: Vista Cards" theme="info" icon="fas fa-lightbulb" collapsible collapsed>
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-check-circle text-success"></i> Ventajas</h6>
            <ul class="small">
                <li>Más espacio para mostrar contenido y metadata</li>
                <li>Visualmente atractivo y moderno</li>
                <li>Borde izquierdo de color según estado (DRAFT=gris, SCHEDULED=amarillo, PUBLISHED=verde, ARCHIVED=gris claro)</li>
                <li>Fácil de escanear visualmente</li>
                <li>Excelente para mostrar alertas e información adicional</li>
                <li>Efecto hover hace la interacción más dinámica</li>
                <li>Headers con colores especiales para estados SCHEDULED</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-exclamation-triangle text-warning"></i> Consideraciones</h6>
            <ul class="small">
                <li>Ocupa más espacio vertical - menos anuncios visibles por página</li>
                <li>Puede requerir más scroll en listas largas</li>
                <li>Menos eficiente en pantallas pequeñas</li>
                <li>Requiere más ancho de pantalla para verse bien</li>
            </ul>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <h6><i class="fas fa-key text-primary"></i> Características Clave</h6>
            <ul class="small mb-0">
                <li><strong>Código de colores por borde:</strong> Borde izquierdo indica el estado del anuncio (verde=publicado, amarillo=programado, gris=borrador/archivado)</li>
                <li><strong>Metadata visible:</strong> Cada card muestra información adicional según el tipo (servicios afectados, audiencia, etc.)</li>
                <li><strong>Efecto hover:</strong> Cards se elevan al pasar el mouse para mejor interactividad</li>
                <li><strong>Alertas integradas:</strong> Información importante se destaca con alertas de Bootstrap dentro del card</li>
                <li><strong>Header destacado para SCHEDULED:</strong> Fondo amarillo en el header para anuncios programados</li>
            </ul>
        </div>
    </div>
</x-adminlte-card>

{{-- Comparison Card --}}
<x-adminlte-card title="Comparación: Tabla vs Cards" theme="warning" icon="fas fa-balance-scale" collapsible collapsed>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="thead-light">
                <tr>
                    <th>Característica</th>
                    <th>Vista Tabla</th>
                    <th>Vista Cards</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Densidad de información</td>
                    <td><span class="badge badge-success">Alta - Muchos en pantalla</span></td>
                    <td><span class="badge badge-warning">Media - Menos por pantalla</span></td>
                </tr>
                <tr>
                    <td>Espacio para contenido</td>
                    <td><span class="badge badge-warning">Limitado</span></td>
                    <td><span class="badge badge-success">Amplio</span></td>
                </tr>
                <tr>
                    <td>Atractivo visual</td>
                    <td><span class="badge badge-info">Funcional</span></td>
                    <td><span class="badge badge-success">Moderno y atractivo</span></td>
                </tr>
                <tr>
                    <td>Facilidad de escaneo</td>
                    <td><span class="badge badge-success">Fácil escaneo horizontal</span></td>
                    <td><span class="badge badge-success">Fácil escaneo por cards</span></td>
                </tr>
                <tr>
                    <td>Responsive (móvil)</td>
                    <td><span class="badge badge-warning">Requiere scroll horizontal</span></td>
                    <td><span class="badge badge-success">Cards apilan verticalmente</span></td>
                </tr>
                <tr>
                    <td>Mejor para</td>
                    <td>Gestión rápida de muchos anuncios</td>
                    <td>Revisión detallada de anuncios</td>
                </tr>
            </tbody>
        </table>
    </div>
</x-adminlte-card>

{{-- Navigation removed - single route mode --}}

@endsection

@section('js')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    })
</script>
@endsection
