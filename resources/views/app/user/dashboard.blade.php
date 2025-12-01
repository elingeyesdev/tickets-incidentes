@extends('layouts.authenticated')

@section('title', 'Dashboard - Usuario')

@section('content_header', 'Mi Panel de Control')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<!-- Row 1: KPI Statistics -->
<div class="row">
    <!-- Total Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="total-tickets">0</h3>
                <p>Mis Tickets</p>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="/app/user/tickets" class="small-box-footer">
                Ver todos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Open Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="open-tickets">0</h3>
                <p>Abiertos</p>
            </div>
            <div class="icon">
                <i class="fas fa-folder-open"></i>
            </div>
            <a href="/app/user/tickets?status=OPEN" class="small-box-footer">
                Ver abiertos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Pending Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="pending-tickets">0</h3>
                <p>Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <a href="/app/user/tickets?status=PENDING" class="small-box-footer">
                Ver pendientes <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Resolved/Closed Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="resolved-tickets">0</h3>
                <p>Resueltos/Cerrados</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="/app/user/tickets?status=RESOLVED" class="small-box-footer">
                Ver historial <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Recent Tickets & Chart -->
    <div class="col-md-8">
        <!-- Ticket Status Chart -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Estado de mis Solicitudes</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-responsive">
                            <canvas id="ticketStatusChart" height="150"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <ul class="chart-legend clearfix">
                            <li><i class="far fa-circle text-danger"></i> Abiertos</li>
                            <li><i class="far fa-circle text-warning"></i> Pendientes</li>
                            <li><i class="far fa-circle text-info"></i> Resueltos</li>
                            <li><i class="far fa-circle text-success"></i> Cerrados</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tickets Table -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Tickets Recientes</h3>
                <div class="card-tools">
                    <a href="/app/user/tickets/create" class="btn btn-sm btn-success">
                        <i class="fas fa-plus mr-1"></i> Nuevo Ticket
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Actualizado</th>
                            <th style="width: 40px">Ver</th>
                        </tr>
                    </thead>
                    <tbody id="recentTicketsBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/user/tickets" class="btn btn-sm btn-default float-right">Ver todos mis tickets</a>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Actions & Help Center -->
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Acciones Rápidas</h3>
            </div>
            <div class="card-body">
                <a href="/app/user/tickets/create" class="btn btn-block btn-success mb-3">
                    <i class="fas fa-plus mr-2"></i> Crear Nuevo Ticket
                </a>
                <a href="/app/user/tickets" class="btn btn-block btn-primary mb-3">
                    <i class="fas fa-list mr-2"></i> Mis Tickets
                </a>
                <a href="/app/user/profile" class="btn btn-block btn-info">
                    <i class="fas fa-user mr-2"></i> Mi Perfil
                </a>
            </div>
        </div>

        <!-- Help Center Suggestions -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Centro de Ayuda</h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2" id="helpCenterBody">
                    <li class="item text-center text-muted py-3">Cargando artículos...</li>
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="/app/user/help-center" class="uppercase">Ver todos los artículos</a>
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

        fetch(`${apiUrl}/analytics/user-dashboard`, {
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
            document.getElementById('total-tickets').textContent = data.kpi.total_tickets;
            document.getElementById('open-tickets').textContent = data.kpi.open_tickets;
            document.getElementById('pending-tickets').textContent = data.kpi.pending_tickets;
            document.getElementById('resolved-tickets').textContent = data.kpi.resolved_tickets + data.kpi.closed_tickets;

            // Render Tables and Lists
            renderRecentTickets(data.recent_tickets);
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
            console.error('[User Dashboard] Error loading dashboard:', error);
        });

    }, 500);
});

// =====================================================================
// RENDER FUNCTIONS
// =====================================================================

function renderRecentTickets(tickets) {
    const tbody = document.getElementById('recentTicketsBody');
    tbody.innerHTML = '';

    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No tienes tickets recientes</td></tr>';
        return;
    }

    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><a href="/app/user/tickets/${ticket.ticket_code}">${ticket.ticket_code}</a></td>
            <td>${ticket.title}</td>
            <td>
                <span class="badge ${getStatusBadgeClass(ticket.status)}">
                    ${getStatusLabel(ticket.status)}
                </span>
            </td>
            <td><small class="text-muted">${ticket.updated_at}</small></td>
            <td>
                <a href="/app/user/tickets/${ticket.ticket_code}" class="text-muted">
                    <i class="fas fa-search"></i>
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
                <i class="fas fa-file-alt fa-2x text-primary"></i>
            </div>
            <div class="product-info">
                <a href="/app/user/help-center/articles/${article.slug}" class="product-title">${article.title}</a>
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
                    display: false // Using custom legend
                }
            }
        }
    });
}

</script>
@endpush
