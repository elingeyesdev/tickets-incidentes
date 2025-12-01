@extends('layouts.authenticated')

@section('title', 'Dashboard - Agente')

@section('content_header', 'Panel de Agente')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<!-- Row 1: KPI Statistics -->
<div class="row">
    <!-- Assigned Total -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="assigned-total">0</h3>
                <p>Asignados Total</p>
            </div>
            <div class="icon">
                <i class="fas fa-inbox"></i>
            </div>
            <a href="/app/agent/tickets?filter=assigned" class="small-box-footer">
                Ver mis tickets <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Assigned Open -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="assigned-open">0</h3>
                <p>Abiertos (Míos)</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <a href="/app/agent/tickets?status=OPEN&filter=assigned" class="small-box-footer">
                Atender ahora <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Assigned Pending -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="assigned-pending">0</h3>
                <p>Pendientes (Míos)</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <a href="/app/agent/tickets?status=PENDING&filter=assigned" class="small-box-footer">
                Revisar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Resolved Today -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="resolved-today">0</h3>
                <p>Resueltos Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-double"></i>
            </div>
            <a href="/app/agent/tickets?status=RESOLVED" class="small-box-footer">
                Ver historial <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Assigned Tickets & Chart -->
    <div class="col-md-8">
        
        <!-- My Assigned Tickets Table -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Mis Tickets Prioritarios</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Asunto</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Creado</th>
                        </tr>
                    </thead>
                    <tbody id="assignedTicketsBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/agent/tickets?filter=assigned" class="btn btn-sm btn-primary float-right">Ver todos mis asignados</a>
            </div>
        </div>

        <!-- Unassigned Queue (Opportunity to pick up tickets) -->
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Cola de Tickets Sin Asignar</h3>
                <div class="card-tools">
                    <span class="badge badge-warning">Pendientes de asignación</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Asunto</th>
                            <th>Prioridad</th>
                            <th>Antigüedad</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="unassignedTicketsBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Cargando cola...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/agent/tickets?filter=unassigned" class="btn btn-sm btn-warning float-right">Ver cola completa</a>
            </div>
        </div>

    </div>

    <!-- Right Column: Stats & Tools -->
    <div class="col-md-4">
        
        <!-- Status Chart -->
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Estado de mi carga de trabajo</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="ticketStatusChart" style="min-height: 200px; height: 200px; max-height: 200px; max-width: 100%;"></canvas>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Herramientas</h3>
            </div>
            <div class="card-body">
                <a href="/app/agent/tickets/create" class="btn btn-block btn-success mb-2">
                    <i class="fas fa-plus mr-2"></i> Crear Ticket
                </a>
                <a href="/app/agent/help-center" class="btn btn-block btn-info">
                    <i class="fas fa-book mr-2"></i> Base de Conocimiento
                </a>
            </div>
        </div>

        <!-- Recent Articles -->
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Artículos Recientes</h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2" id="helpCenterBody">
                    <li class="item text-center text-muted py-3">Cargando...</li>
                </ul>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// =====================================================================
// UTILITY FUNCTIONS
// =====================================================================

function getAccessToken() {
    if (typeof window.tokenManager !== 'undefined') {
        return window.tokenManager.getAccessToken();
    }
    return localStorage.getItem('access_token');
}

function getStatusBadgeClass(status) {
    const statusMap = {
        'OPEN': 'badge-danger',
        'PENDING': 'badge-warning',
        'RESOLVED': 'badge-info',
        'CLOSED': 'badge-success'
    };
    return statusMap[status] || 'badge-secondary';
}

function getPriorityBadgeClass(priority) {
    const map = {
        'HIGH': 'badge-danger',
        'MEDIUM': 'badge-warning',
        'LOW': 'badge-success'
    };
    return map[priority] || 'badge-secondary';
}

function getStatusLabel(status) {
    const statusMap = {
        'OPEN': 'Abierto',
        'PENDING': 'Pendiente',
        'RESOLVED': 'Resuelto',
        'CLOSED': 'Cerrado'
    };
    return statusMap[status] || status;
}

// =====================================================================
// LOAD DASHBOARD DATA
// =====================================================================

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const token = getAccessToken();
        const apiUrl = '/api';

        if (!token) {
            Swal.fire({
                icon: 'error',
                title: 'Error de autenticación',
                text: 'Por favor inicia sesión de nuevo'
            }).then(() => {
                window.location.href = '/login';
            });
            return;
        }

        fetch(`${apiUrl}/analytics/agent-dashboard`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) {
                window.location.href = '/login?reason=session_expired';
                return null;
            }
            if (!response.ok) throw new Error('Failed to load dashboard data');
            return response.json();
        })
        .then(data => {
            if (!data) return;

            // Update KPI cards
            document.getElementById('assigned-total').textContent = data.kpi.assigned_total;
            document.getElementById('assigned-open').textContent = data.kpi.assigned_open;
            document.getElementById('assigned-pending').textContent = data.kpi.assigned_pending;
            document.getElementById('resolved-today').textContent = data.kpi.resolved_today;

            // Render Tables and Lists
            renderAssignedTickets(data.assigned_tickets);
            renderUnassignedTickets(data.unassigned_tickets);
            renderRecentArticles(data.recent_articles);

            // Initialize Chart
            initializeTicketStatusChart(
                data.ticket_status.OPEN,
                data.ticket_status.PENDING,
                data.ticket_status.RESOLVED,
                data.ticket_status.CLOSED
            );
        })
        .catch(error => {
            console.error('[Agent Dashboard] Error loading dashboard:', error);
        });

    }, 500);
});

// =====================================================================
// RENDER FUNCTIONS
// =====================================================================

function renderAssignedTickets(tickets) {
    const tbody = document.getElementById('assignedTicketsBody');
    tbody.innerHTML = '';

    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No tienes tickets asignados activos</td></tr>';
        return;
    }

    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><a href="/app/agent/tickets/${ticket.ticket_code}">${ticket.ticket_code}</a></td>
            <td>${ticket.title}</td>
            <td><span class="badge ${getPriorityBadgeClass(ticket.priority)}">${ticket.priority}</span></td>
            <td>
                <span class="badge ${getStatusBadgeClass(ticket.status)}">
                    ${getStatusLabel(ticket.status)}
                </span>
            </td>
            <td><small class="text-muted">${ticket.created_at}</small></td>
        `;
        tbody.appendChild(row);
    });
}

function renderUnassignedTickets(tickets) {
    const tbody = document.getElementById('unassignedTicketsBody');
    tbody.innerHTML = '';

    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No hay tickets sin asignar</td></tr>';
        return;
    }

    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><a href="/app/agent/tickets/${ticket.ticket_code}">${ticket.ticket_code}</a></td>
            <td>${ticket.title}</td>
            <td><span class="badge ${getPriorityBadgeClass(ticket.priority)}">${ticket.priority}</span></td>
            <td><small>${ticket.created_at}</small></td>
            <td>
                <a href="/app/agent/tickets/${ticket.ticket_code}" class="btn btn-xs btn-info">
                    <i class="fas fa-eye"></i> Ver
                </a>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function renderRecentArticles(articles) {
    const list = document.getElementById('helpCenterBody');
    list.innerHTML = '';

    if (articles.length === 0) {
        list.innerHTML = '<li class="item text-center text-muted py-3">No hay artículos recientes</li>';
        return;
    }

    articles.forEach(article => {
        const li = document.createElement('li');
        li.className = 'item';
        li.innerHTML = `
            <div class="product-img">
                <i class="fas fa-file-alt fa-2x text-secondary"></i>
            </div>
            <div class="product-info">
                <a href="/app/agent/help-center/articles/${article.slug}" class="product-title">${article.title}</a>
                <span class="product-description">
                    ${article.views || 0} vistas
                </span>
            </div>
        `;
        list.appendChild(li);
    });
}

// =====================================================================
// CHART FUNCTIONS
// =====================================================================

function initializeTicketStatusChart(open, pending, resolved, closed) {
    const ctx = document.getElementById('ticketStatusChart');
    if (!ctx) return;

    if (window.ticketStatusChartInstance) {
        window.ticketStatusChartInstance.destroy();
    }

    window.ticketStatusChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Abiertos', 'Pendientes', 'Resueltos', 'Cerrados'],
            datasets: [{
                data: [open, pending, resolved, closed],
                backgroundColor: ['#dc3545', '#ffc107', '#17a2b8', '#28a745'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'left'
                }
            }
        }
    });
}

</script>
@endpush
