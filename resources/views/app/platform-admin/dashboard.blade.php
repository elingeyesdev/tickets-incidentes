@extends('layouts.authenticated')

@section('title', 'Dashboard - Administrador de Plataforma')

@section('content_header', 'Panel de Control Global')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<!-- Row 1: KPI Statistics -->
<div class="row">
    <!-- Total Companies -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="total-companies">0</h3>
                <p>Empresas Registradas</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <a href="/app/admin/companies" class="small-box-footer">
                Gestionar empresas <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Users -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="total-users">0</h3>
                <p>Usuarios Totales</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="/app/admin/users" class="small-box-footer">
                Gestionar usuarios <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Tickets -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="total-tickets">0</h3>
                <p>Tickets Globales</p>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="#" class="small-box-footer">
                Ver métricas <i class="fas fa-chart-bar"></i>
            </a>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger" id="pending-requests-box">
            <div class="inner">
                <h3 id="pending-requests">0</h3>
                <p>Solicitudes Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-contract"></i>
            </div>
            <a href="/app/admin/company-requests" class="small-box-footer">
                Revisar solicitudes <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Row 2: Charts -->
<div class="row">
    <!-- Companies Growth Chart -->
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Crecimiento de Empresas (Últimos 6 meses)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="companiesGrowthChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Ticket Volume Chart -->
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Volumen Global de Tickets</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="ticketVolumeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Lists -->
<div class="row">
    <!-- Pending Requests List -->
    <div class="col-md-6">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title">Solicitudes de Registro Pendientes</h3>
                <div class="card-tools">
                    <span class="badge badge-danger" id="pending-badge">0 Pendientes</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Email Admin</th>
                            <th>Recibido</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="pendingRequestsBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/admin/company-requests" class="btn btn-sm btn-danger float-right">Ver todas las solicitudes</a>
            </div>
        </div>
    </div>

    <!-- Top Companies List -->
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Empresas con Mayor Actividad</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Estado</th>
                            <th>Tickets Totales</th>
                            <th>Tendencia</th>
                        </tr>
                    </thead>
                    <tbody id="topCompaniesBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/admin/companies" class="btn btn-sm btn-info float-right">Ver todas las empresas</a>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: System Status -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Estado del Sistema</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-server"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">API Server</span>
                                <span class="info-box-number">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-database"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Database</span>
                                <span class="info-box-number">Connected</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-envelope"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Email Service</span>
                                <span class="info-box-number">Active</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-shield-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Security</span>
                                <span class="info-box-number">Secure</span>
                            </div>
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

        fetch(`${apiUrl}/analytics/platform-dashboard`, {
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
            document.getElementById('total-companies').textContent = data.kpi.total_companies;
            document.getElementById('total-users').textContent = data.kpi.total_users;
            document.getElementById('total-tickets').textContent = data.kpi.total_tickets;
            document.getElementById('pending-requests').textContent = data.kpi.pending_requests;
            document.getElementById('pending-badge').textContent = data.kpi.pending_requests + ' Pendientes';

            // Update Pending Box Color if requests > 0
            if (data.kpi.pending_requests > 0) {
                document.getElementById('pending-requests-box').classList.add('bg-danger');
                document.getElementById('pending-requests-box').classList.remove('bg-success');
            } else {
                document.getElementById('pending-requests-box').classList.remove('bg-danger');
                document.getElementById('pending-requests-box').classList.add('bg-success');
            }

            // Render Lists
            renderPendingRequests(data.pending_requests);
            renderTopCompanies(data.top_companies);

            // Initialize Charts
            initializeCompaniesGrowthChart(data.companies_growth);
            initializeTicketVolumeChart(data.ticket_volume);
        })
        .catch(error => {
            console.error('[Platform Dashboard] Error loading dashboard:', error);
        });

    }, 500);
});

// =====================================================================
// RENDER FUNCTIONS
// =====================================================================

function renderPendingRequests(requests) {
    const tbody = document.getElementById('pendingRequestsBody');
    tbody.innerHTML = '';

    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No hay solicitudes pendientes</td></tr>';
        return;
    }

    requests.forEach(req => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${req.company_name}</td>
            <td>${req.admin_email}</td>
            <td><small class="text-muted">${req.created_at}</small></td>
            <td>
                <a href="/app/admin/company-requests" class="btn btn-xs btn-primary">
                    <i class="fas fa-eye"></i> Revisar
                </a>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function renderTopCompanies(companies) {
    const tbody = document.getElementById('topCompaniesBody');
    tbody.innerHTML = '';

    if (companies.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sin datos de empresas</td></tr>';
        return;
    }

    companies.forEach(company => {
        const statusClass = company.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary';
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${company.name}</td>
            <td><span class="badge ${statusClass}">${company.status}</span></td>
            <td><strong>${company.tickets_count}</strong></td>
            <td><span class="text-success"><i class="fas fa-caret-up"></i> Activo</span></td>
        `;
        tbody.appendChild(row);
    });
}

// =====================================================================
// CHART FUNCTIONS
// =====================================================================

function initializeCompaniesGrowthChart(data) {
    const ctx = document.getElementById('companiesGrowthChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Nuevas Empresas',
                data: data.data,
                backgroundColor: 'rgba(60, 141, 188, 0.2)',
                borderColor: 'rgba(60, 141, 188, 1)',
                pointRadius: 4,
                pointBackgroundColor: '#3b8bba',
                pointBorderColor: 'rgba(60, 141, 188, 1)',
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function initializeTicketVolumeChart(data) {
    const ctx = document.getElementById('ticketVolumeChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Tickets Creados',
                data: data.data,
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

</script>
@endpush
