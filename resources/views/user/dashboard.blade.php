@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Tickets Totales</h6>
                        <h3 class="mb-0">
                            <span id="totalTickets">-</span>
                        </h3>
                    </div>
                    <i class="fas fa-ticket-alt text-primary" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">En Progreso</h6>
                        <h3 class="mb-0">
                            <span id="inProgressTickets">-</span>
                        </h3>
                    </div>
                    <i class="fas fa-hourglass-half text-warning" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Resueltos</h6>
                        <h3 class="mb-0">
                            <span id="resolvedTickets">-</span>
                        </h3>
                    </div>
                    <i class="fas fa-check-circle text-success" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Tiempo Promedio</h6>
                        <h3 class="mb-0">
                            <span id="avgTime">-</span>h
                        </h3>
                    </div>
                    <i class="fas fa-clock text-info" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i> Mis Últimos Tickets
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-hover" id="ticketsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin me-2"></i> Cargando...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i> Información de Perfil
                </h5>
            </div>
            <div class="card-body" id="profileInfo">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
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

        // Llenar información de perfil
        const profileDiv = document.getElementById('profileInfo');
        profileDiv.innerHTML = `
            <div class="text-center">
                <div class="mb-3">
                    ${user.avatarUrl ?
                        `<img src="${user.avatarUrl}" alt="Avatar" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">` :
                        `<div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; color: white; font-size: 2rem;">
                            <i class="fas fa-user"></i>
                        </div>`
                    }
                </div>
                <h5>${user.displayName}</h5>
                <p class="text-muted mb-1">${user.email}</p>
                <p class="text-muted small">Código: ${user.userCode}</p>
                <hr>
                <p class="text-muted small mb-0">
                    <strong>Roles:</strong><br>
                    ${user.roleContexts.map(r => r.roleName).join(', ')}
                </p>
            </div>
        `;

    } catch (error) {
        console.error('Error al cargar datos:', error);
        window.location.href = '{{ route('login') }}';
    }
});
</script>
@endsection
