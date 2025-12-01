@extends('layouts.authenticated')

@section('title', 'Dashboard - Administrador de Empresa')

@section('content_header', 'Panel de Control - Administrador de Empresa')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<!-- Row 1: KPI Statistics -->
<div class="row">
    <!-- Total Agents KPI -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 id="total-agents">0</h3>
                <p>Agentes</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <a href="/app/company/agents" class="small-box-footer">
                Gestionar <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Articles KPI -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="total-articles">0</h3>
                <p>Artículos</p>
            </div>
            <div class="icon">
                <i class="fas fa-book"></i>
            </div>
            <a href="/app/company/help-center" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Announcements KPI -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="total-announcements">0</h3>
                <p>Anuncios</p>
            </div>
            <div class="icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <a href="/app/company/announcements" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Tickets KPI -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="total-tickets-kpi">0</h3>
                <p>Tickets Totales</p>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="/app/company/tickets" class="small-box-footer">
                Ver todos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Row 2: Charts -->
<div class="row">
    <!-- Ticket Status Chart -->
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Estado de Tickets</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 250px;">
                    <canvas id="ticketStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets Over Time Chart -->
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Evolución de Tickets (Últimos 6 meses)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 250px;">
                    <canvas id="ticketsOverTimeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Recent Tickets and Team -->
<div class="row">
    <!-- Recent Tickets Table -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Tickets Recientes</h3>
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
                            <th style="width: 20%;">Código</th>
                            <th style="width: 40%;">Título</th>
                            <th style="width: 20%;">Creador</th>
                            <th style="width: 20%;">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="recentTicketsBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/company/tickets" class="btn btn-sm btn-primary float-right">
                    Ver todos los tickets
                </a>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Equipo de Soporte</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="users-list clearfix" id="teamBody">
                    <!-- Loaded dynamically -->
                    <li class="text-center text-muted py-3" style="width: 100%">Cargando...</li>
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="/app/company/agents">Ver todos los agentes</a>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Categories and Statistics -->
<div class="row">
    <!-- Categories Card -->
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Categorías de Tickets</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Categoría</th>
                            <th>Progreso</th>
                            <th style="width: 40px">Cant.</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Stats (Info Boxes) -->
    <div class="col-md-6">
        <div class="info-box mb-3 bg-info">
            <span class="info-box-icon"><i class="fas fa-stopwatch"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tiempo Promedio de Respuesta</span>
                <span class="info-box-number" id="avg-response-time">--</span>
            </div>
        </div>

        <div class="info-box mb-3 bg-warning">
            <span class="info-box-icon"><i class="fas fa-hourglass-half text-white"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-white">Tickets Pendientes</span>
                <span class="info-box-number text-white" id="pending-tickets">0</span>
            </div>
        </div>

        <div class="info-box mb-3 bg-success">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tasa de Resolución</span>
                <span class="info-box-number" id="resolution-rate">0%</span>
            </div>
        </div>

        <div class="info-box mb-3 bg-danger">
            <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tickets Abiertos</span>
                <span class="info-box-number" id="open-tickets">0</span>
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

        fetch(`${apiUrl}/analytics/company-dashboard`, {
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
            document.getElementById('total-agents').textContent = data.kpi.total_agents;
            document.getElementById('total-articles').textContent = data.kpi.total_articles;
            document.getElementById('total-announcements').textContent = data.kpi.total_announcements;
            document.getElementById('total-tickets-kpi').textContent = data.kpi.total_tickets;

            // Update Info Boxes (Quick Stats)
            document.getElementById('avg-response-time').textContent = data.performance.avg_response_time;
            document.getElementById('pending-tickets').textContent = data.ticket_status.PENDING;
            document.getElementById('open-tickets').textContent = data.ticket_status.OPEN;

            const totalTickets = data.kpi.total_tickets;
            const resolvedCount = data.ticket_status.RESOLVED + data.ticket_status.CLOSED;
            const resolutionRate = totalTickets > 0 ? Math.round((resolvedCount / totalTickets) * 100) : 0;
            document.getElementById('resolution-rate').textContent = resolutionRate + '%';

            // Render Tables and Lists
            renderRecentTickets(data.recent_tickets);
            renderTeamMembers(data.team_members);
            renderCategories(data.categories);

            // Initialize Charts
            initializeTicketStatusChart(
                data.ticket_status.OPEN,
                data.ticket_status.PENDING,
                data.ticket_status.RESOLVED,
                data.ticket_status.CLOSED
            );

            initializeTicketsOverTimeChart(data.tickets_over_time);
        })
        .catch(error => {
            console.error('[Company Admin Dashboard] Error loading dashboard:', error);
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
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sin tickets recientes</td></tr>';
        return;
    }

    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><a href="/app/company/tickets/${ticket.ticket_code}">${ticket.ticket_code}</a></td>
            <td>${ticket.title}</td>
            <td>${ticket.creator_name}</td>
            <td>
                <span class="badge ${getStatusBadgeClass(ticket.status)}">
                    ${getStatusLabel(ticket.status)}
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function renderTeamMembers(agents) {
    const list = document.getElementById('teamBody');
    list.innerHTML = '';

    if (agents.length === 0) {
        list.innerHTML = '<li class="text-center text-muted py-3" style="width: 100%">Sin agentes</li>';
        return;
    }

    agents.forEach(agent => {
        const name = agent.name || agent.email;
        const avatar = agent.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=random';
        const date = new Date().toLocaleDateString(); // Placeholder for "Member Since" if needed, or just remove

        const li = document.createElement('li');
        li.innerHTML = `
            <img src="${avatar}" alt="User Image" style="width: 50px; height: 50px; object-fit: cover;">
            <a class="users-list-name" href="#">${name}</a>
            <span class="users-list-date">${agent.status === 'ONLINE' ? '<span class="text-success">Online</span>' : 'Offline'}</span>
        `;
        list.appendChild(li);
    });
}

function renderCategories(categories) {
    const tbody = document.getElementById('categoriesBody');
    tbody.innerHTML = '';

    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sin categorías</td></tr>';
        return;
    }

    categories.forEach((category, index) => {
        const percentage = category.percentage || 0;
        let colorClass = 'bg-primary';
        if (percentage > 75) colorClass = 'bg-danger';
        else if (percentage > 50) colorClass = 'bg-warning';
        else if (percentage > 25) colorClass = 'bg-info';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${index + 1}.</td>
            <td>${category.name}</td>
            <td>
                <div class="progress progress-xs">
                    <div class="progress-bar ${colorClass}" style="width: ${percentage}%"></div>
                </div>
            </td>
            <td><span class="badge ${colorClass}">${category.active_tickets_count}</span></td>
        `;
        tbody.appendChild(row);
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
                    position: 'bottom'
                }
            }
        }
    });
}

function initializeTicketsOverTimeChart(data) {
    const ctx = document.getElementById('ticketsOverTimeChart');
    if (!ctx) return;

    if (window.ticketsOverTimeChartInstance) {
        window.ticketsOverTimeChartInstance.destroy();
    }

    window.ticketsOverTimeChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Tickets Creados',
                data: data.data,
                backgroundColor: 'rgba(60, 141, 188, 0.2)',
                borderColor: 'rgba(60, 141, 188, 1)',
                pointRadius: 4,
                pointBackgroundColor: '#3b8bba',
                pointBorderColor: 'rgba(60, 141, 188, 1)',
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4 // Smooth curve
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

</script>
@endpush
