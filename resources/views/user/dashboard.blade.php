@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('breadcrumb', 'Inicio')

@section('content')
{{-- ESTADÍSTICAS: Row de 4 info-boxes --}}
<div class="row">
    {{-- Total Tickets --}}
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-primary elevation-1">
                <i class="fas fa-ticket-alt"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Tickets</span>
                <span class="info-box-number" id="totalTickets">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </div>
        </div>
    </div>

    {{-- En Progreso --}}
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1">
                <i class="fas fa-hourglass-half"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">En Progreso</span>
                <span class="info-box-number" id="inProgressTickets">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </div>
        </div>
    </div>

    {{-- Resueltos --}}
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1">
                <i class="fas fa-check-circle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Resueltos</span>
                <span class="info-box-number" id="resolvedTickets">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </div>
        </div>
    </div>

    {{-- Tiempo Promedio --}}
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1">
                <i class="fas fa-clock"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Tiempo Promedio</span>
                <span class="info-box-number" id="avgTime">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </div>
        </div>
    </div>
</div>

{{-- CONTENIDO PRINCIPAL: Gráfico/Tabla + Perfil --}}
<div class="row">
    {{-- Actividad Reciente --}}
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Actividad Reciente
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="ticketsTable">
                        <thead>
                            <tr>
                                <th style="width: 80px;">#</th>
                                <th>Asunto</th>
                                <th style="width: 120px;">Estado</th>
                                <th style="width: 120px;">Prioridad</th>
                                <th style="width: 140px;">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Cargando tickets...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    Mostrando tus últimos tickets creados
                </small>
            </div>
        </div>
    </div>

    {{-- Información de Perfil --}}
    <div class="col-lg-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i>
                    Mi Perfil
                </h3>
            </div>
            <div class="card-body box-profile" id="profileInfo">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="text-muted mt-2">Cargando información...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Verificar autenticación
    const token = localStorage.getItem('accessToken');
    if (!token) {
        window.location.href = '{{ route('login') }}';
        return;
    }

    try {
        // Obtener estado de autenticación
        const authStatus = await apiRequest('/auth/status');
        const user = authStatus.user;

        // LLENAR ESTADÍSTICAS (valores de ejemplo - reemplazar con datos reales)
        document.getElementById('totalTickets').textContent = '24';
        document.getElementById('inProgressTickets').textContent = '8';
        document.getElementById('resolvedTickets').textContent = '16';
        document.getElementById('avgTime').innerHTML = '4.5<small> horas</small>';

        // LLENAR TABLA DE TICKETS (datos de ejemplo - reemplazar con datos reales)
        const ticketsTableBody = document.querySelector('#ticketsTable tbody');
        ticketsTableBody.innerHTML = `
            <tr>
                <td><strong>#1234</strong></td>
                <td>No puedo acceder al sistema</td>
                <td><span class="badge badge-warning">En Progreso</span></td>
                <td><span class="badge badge-danger">Alta</span></td>
                <td><small class="text-muted">Hace 2 horas</small></td>
            </tr>
            <tr>
                <td><strong>#1233</strong></td>
                <td>Error al generar reporte</td>
                <td><span class="badge badge-success">Resuelto</span></td>
                <td><span class="badge badge-warning">Media</span></td>
                <td><small class="text-muted">Hace 5 horas</small></td>
            </tr>
            <tr>
                <td><strong>#1232</strong></td>
                <td>Solicitud de nuevo usuario</td>
                <td><span class="badge badge-info">Pendiente</span></td>
                <td><span class="badge badge-info">Baja</span></td>
                <td><small class="text-muted">Hace 1 día</small></td>
            </tr>
            <tr>
                <td><strong>#1231</strong></td>
                <td>Problema con impresora</td>
                <td><span class="badge badge-success">Resuelto</span></td>
                <td><span class="badge badge-warning">Media</span></td>
                <td><small class="text-muted">Hace 2 días</small></td>
            </tr>
            <tr>
                <td><strong>#1230</strong></td>
                <td>Actualización de software</td>
                <td><span class="badge badge-success">Resuelto</span></td>
                <td><span class="badge badge-info">Baja</span></td>
                <td><small class="text-muted">Hace 3 días</small></td>
            </tr>
        `;

        // LLENAR INFORMACIÓN DE PERFIL (AdminLTE v3 box-profile)
        const profileDiv = document.getElementById('profileInfo');
        profileDiv.innerHTML = `
            <div class="text-center">
                ${user.avatarUrl ?
                    `<img class="profile-user-img img-fluid img-circle"
                         src="${user.avatarUrl}"
                         alt="User profile picture"
                         style="width: 100px; height: 100px; object-fit: cover;">` :
                    `<div class="profile-user-img img-fluid img-circle d-inline-flex align-items-center justify-content-center bg-primary"
                         style="width: 100px; height: 100px;">
                        <i class="fas fa-user fa-3x text-white"></i>
                    </div>`
                }
            </div>

            <h3 class="profile-username text-center">${user.displayName}</h3>

            <p class="text-muted text-center">
                <i class="fas fa-envelope mr-1"></i>
                <small>${user.email}</small>
            </p>

            <ul class="list-group list-group-unbordered mb-3">
                <li class="list-group-item">
                    <b><i class="fas fa-id-badge mr-2 text-primary"></i>Código</b>
                    <span class="float-right">${user.userCode}</span>
                </li>
                <li class="list-group-item">
                    <b><i class="fas fa-user-tag mr-2 text-info"></i>Rol Principal</b>
                    <span class="float-right">
                        <span class="badge badge-primary">${user.roleContexts[0]?.roleName || 'Usuario'}</span>
                    </span>
                </li>
                ${user.roleContexts.length > 1 ? `
                <li class="list-group-item">
                    <b><i class="fas fa-users mr-2 text-success"></i>Roles Adicionales</b>
                    <div class="float-right">
                        ${user.roleContexts.slice(1).map(r =>
                            `<span class="badge badge-secondary mr-1">${r.roleName}</span>`
                        ).join('')}
                    </div>
                </li>
                ` : ''}
            </ul>

            <a href="#" class="btn btn-primary btn-block">
                <i class="fas fa-user-edit mr-2"></i><b>Editar Perfil</b>
            </a>
        `;

    } catch (error) {
        console.error('Error al cargar datos:', error);

        // Mostrar error en estadísticas
        document.getElementById('totalTickets').innerHTML = '<i class="fas fa-times text-danger"></i>';
        document.getElementById('inProgressTickets').innerHTML = '<i class="fas fa-times text-danger"></i>';
        document.getElementById('resolvedTickets').innerHTML = '<i class="fas fa-times text-danger"></i>';
        document.getElementById('avgTime').innerHTML = '<i class="fas fa-times text-danger"></i>';

        // Mostrar error en tabla
        document.querySelector('#ticketsTable tbody').innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Error al cargar tickets
                </td>
            </tr>
        `;

        // Mostrar error en perfil
        document.getElementById('profileInfo').innerHTML = `
            <div class="text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <p class="mt-2">Error al cargar perfil</p>
            </div>
        `;

        // Redirigir al login si no está autenticado
        setTimeout(() => {
            window.location.href = '{{ route('login') }}';
        }, 2000);
    }
});
</script>
@endsection
