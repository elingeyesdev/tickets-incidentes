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

    <!-- Average Response Time -->
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Métrica de Rendimiento</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 text-center">
                        <div class="text-lg font-weight-bold mb-2">
                            <span id="avg-response-time">--</span>
                        </div>
                        <small class="text-muted">Tiempo Promedio Respuesta</small>
                    </div>
                    <div class="col-md-6 text-center">
                        <div class="text-lg font-weight-bold mb-2">
                            <span id="customer-satisfaction">--</span>%
                        </div>
                        <small class="text-muted">Satisfacción Cliente</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Recent Tickets and Team -->
<div class="row">
    <!-- Recent Tickets Table -->
    <div class="col-md-6">
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
                            <th style="width: 30%;">Código</th>
                            <th style="width: 40%;">Título</th>
                            <th style="width: 30%;">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="recentTicketsBody">
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/company/tickets" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-right"></i> Ver todos
                </a>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="col-md-6">
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
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Nombre</th>
                            <th style="width: 35%;">Email</th>
                            <th style="width: 25%;">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="teamBody">
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/company/agents" class="btn btn-sm btn-info">
                    <i class="fas fa-arrow-right"></i> Gestionar
                </a>
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
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Nombre</th>
                            <th style="width: 50%;">Tickets Activos</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesBody">
                        <tr>
                            <td colspan="2" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/company/categories" class="btn btn-sm btn-danger">
                    <i class="fas fa-arrow-right"></i> Gestionar
                </a>
            </div>
        </div>
    </div>

    <!-- Announcements Card -->
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Estadísticas Rápidas</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 border-right">
                        <div class="text-center">
                            <h4 id="total-tickets-stat" class="text-primary">0</h4>
                            <p class="text-muted">Tickets Totales</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 id="resolution-rate" class="text-success">0%</h4>
                            <p class="text-muted">Tasa Resolución</p>
                        </div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row">
                    <div class="col-6 border-right">
                        <div class="text-center">
                            <h5 id="pending-tickets" class="text-warning">0</h5>
                            <p class="text-muted">Pendientes</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h5 id="closed-tickets" class="text-info">0</h5>
                            <p class="text-muted">Cerrados</p>
                        </div>
                    </div>
                </div>
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

        console.log('[Company Admin Dashboard] Token available:', !!token);

        if (!token) {
            console.error('[Company Admin Dashboard] No token available');
            Swal.fire({
                icon: 'error',
                title: 'Error de autenticación',
                text: 'Por favor inicia sesión de nuevo'
            }).then(() => {
                window.location.href = '/login';
            });
            return;
        }

        // =====================================================================
        // 1. LOAD AGENTS DATA
        // =====================================================================

        fetch(`${apiUrl}/users?role=AGENT&per_page=100`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) return null;
            return response.json();
        })
        .then(data => {
            if (!data) return;

            const totalAgents = data.meta?.total || 0;
            const agents = data.data || [];

            document.getElementById('total-agents').textContent = totalAgents;
            renderTeamMembers(agents.slice(0, 5));
        })
        .catch(error => {
            console.error('[Company Admin Dashboard] Error loading agents:', error);
        });

        // =====================================================================
        // 2. LOAD ARTICLES DATA
        // =====================================================================

        fetch(`${apiUrl}/help-center/articles?per_page=1000`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) return null;
            return response.json();
        })
        .then(data => {
            if (!data) return;

            const totalArticles = data.meta?.total || (data.data ? data.data.length : 0);
            document.getElementById('total-articles').textContent = totalArticles;
        })
        .catch(error => {
            console.error('[Company Admin Dashboard] Error loading articles:', error);
        });

        // =====================================================================
        // 3. LOAD ANNOUNCEMENTS DATA
        // =====================================================================

        fetch(`${apiUrl}/announcements?per_page=1000`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) return null;
            return response.json();
        })
        .then(data => {
            if (!data) return;

            const totalAnnouncements = data.meta?.total || (data.data ? data.data.length : 0);
            document.getElementById('total-announcements').textContent = totalAnnouncements;
        })
        .catch(error => {
            console.error('[Company Admin Dashboard] Error loading announcements:', error);
        });

        // =====================================================================
        // 4. LOAD TICKETS DATA
        // =====================================================================

        fetch(`${apiUrl}/tickets?page=1&per_page=100`, {
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
            return response.json();
        })
        .then(data => {
            if (!data) return;

            const tickets = data.data || [];
            const totalTickets = data.meta?.total || 0;

            // Count by status
            let openCount = 0, pendingCount = 0, resolvedCount = 0, closedCount = 0;

            tickets.forEach(ticket => {
                if (ticket.status === 'OPEN') openCount++;
                else if (ticket.status === 'PENDING') pendingCount++;
                else if (ticket.status === 'RESOLVED') resolvedCount++;
                else if (ticket.status === 'CLOSED') closedCount++;
            });

            // Update KPI cards
            document.getElementById('total-tickets-kpi').textContent = totalTickets;

            // Update stats
            document.getElementById('total-tickets-stat').textContent = totalTickets;
            document.getElementById('pending-tickets').textContent = pendingCount;
            document.getElementById('closed-tickets').textContent = closedCount;

            // Calculate resolution rate
            const resolutionRate = totalTickets > 0 ? Math.round((resolvedCount + closedCount) / totalTickets * 100) : 0;
            document.getElementById('resolution-rate').textContent = resolutionRate;

            // Render recent tickets table
            renderRecentTickets(tickets.slice(0, 5));

            // Initialize chart
            initializeTicketStatusChart(openCount, pendingCount, resolvedCount, closedCount);
        })
        .catch(error => {
            console.error('[Company Admin Dashboard] Error loading tickets:', error);
        });

        // =====================================================================
        // 5. LOAD CATEGORIES DATA
        // =====================================================================

        fetch(`${apiUrl}/tickets/categories`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) return null;
            return response.json();
        })
        .then(data => {
            if (!data) return;

            const categories = data.data || [];
            renderCategories(categories);
        })
        .catch(error => {
            console.error('[Company Admin Dashboard] Error loading categories:', error);
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
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Sin tickets</td></tr>';
        return;
    }

    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${ticket.ticket_code}</strong></td>
            <td>${ticket.title}</td>
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
    const tbody = document.getElementById('teamBody');
    tbody.innerHTML = '';

    if (agents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Sin agentes</td></tr>';
        return;
    }

    agents.forEach(agent => {
        const firstName = agent.profile?.first_name || '';
        const lastName = agent.profile?.last_name || '';
        const name = firstName ? `${firstName} ${lastName}`.trim() : agent.email;
        const isOnline = Math.random() > 0.3;
        const statusBadge = isOnline ? '<span class="badge badge-success">En línea</span>' : '<span class="badge badge-secondary">Offline</span>';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${name}</td>
            <td>${agent.email}</td>
            <td>${statusBadge}</td>
        `;
        tbody.appendChild(row);
    });
}

function renderCategories(categories) {
    const tbody = document.getElementById('categoriesBody');
    tbody.innerHTML = '';

    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">Sin categorías</td></tr>';
        return;
    }

    categories.forEach(category => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${category.name}</td>
            <td><span class="badge badge-info">${category.active_tickets_count || 0}</span></td>
        `;
        tbody.appendChild(row);
    });
}

function initializeTicketStatusChart(open, pending, resolved, closed) {
    const ctx = document.getElementById('ticketStatusChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Abiertos', 'Pendientes', 'Resueltos', 'Cerrados'],
            datasets: [{
                data: [open, pending, resolved, closed],
                backgroundColor: [
                    '#dc3545',
                    '#ffc107',
                    '#17a2b8',
                    '#28a745'
                ],
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

</script>
@endpush
