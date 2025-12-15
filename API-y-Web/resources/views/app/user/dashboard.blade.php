@extends('layouts.authenticated')

@section('title', 'Dashboard - Usuario')

@section('content_header', 'Mi Panel de Control')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<!-- Row 1: KPI Statistics -->
<div class="row" id="kpi-row">
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
            <div class="overlay dark" id="kpi-overlay-1">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
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
            <div class="overlay dark" id="kpi-overlay-2">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
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
            <div class="overlay dark" id="kpi-overlay-3">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
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
            <div class="overlay dark" id="kpi-overlay-4">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Profile Widget + Priority Distribution + Resolution Rate -->
<div class="row">
    <!-- User Profile Widget -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i> Mi Perfil
                </h3>
            </div>
            <div class="card-body text-center">
                <img class="img-circle elevation-2 mb-3" src="/img/default-avatar.png" alt="Avatar" id="user-avatar" style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="mb-1" id="user-name">Cargando...</h5>
                <p class="text-muted mb-3" id="user-since">Miembro desde --</p>
                <div class="row text-center">
                    <div class="col-4 border-right">
                        <h5 class="font-weight-bold mb-0" id="profile-total">0</h5>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-4 border-right">
                        <h5 class="font-weight-bold mb-0" id="profile-resolved">0</h5>
                        <small class="text-muted">Resueltos</small>
                    </div>
                    <div class="col-4">
                        <h5 class="font-weight-bold mb-0 text-success" id="profile-rate">0%</h5>
                        <small class="text-muted">Tasa</small>
                    </div>
                </div>
            </div>
            <div class="overlay" id="profile-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Priority Distribution -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-fire mr-2"></i> Distribución por Prioridad
                </h3>
            </div>
            <div class="card-body">
                <!-- High Priority -->
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-danger mr-1"></i> Alta</span>
                    <span class="float-right" id="priority-high-count"><b>0</b> (<span id="priority-high-pct">0</span>%)</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" id="priority-high-bar" style="width: 0%"></div>
                    </div>
                </div>
                <!-- Medium Priority -->
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-warning mr-1"></i> Media</span>
                    <span class="float-right" id="priority-medium-count"><b>0</b> (<span id="priority-medium-pct">0</span>%)</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" id="priority-medium-bar" style="width: 0%"></div>
                    </div>
                </div>
                <!-- Low Priority -->
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-success mr-1"></i> Baja</span>
                    <span class="float-right" id="priority-low-count"><b>0</b> (<span id="priority-low-pct">0</span>%)</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" id="priority-low-bar" style="width: 0%"></div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Total de Tickets:</span>
                    <strong id="priority-total">0</strong>
                </div>
            </div>
            <div class="overlay" id="priority-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Ticket Status Doughnut Chart -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-2"></i> Estado de Tickets
                </h3>
            </div>
            <div class="card-body">
                <canvas id="ticketStatusChart" style="min-height: 180px; height: 180px; max-height: 180px; max-width: 100%;"></canvas>
            </div>
            <div class="overlay" id="status-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Tickets Trend Chart + Tickets Table -->
<div class="row">
    <!-- Tickets Trend Chart (6 months) -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-2"></i> Tendencia de Tickets (Últimos 6 Meses)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="ticketsTrendChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
            <div class="overlay" id="trend-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Top 5 Most Followed Companies -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building mr-2"></i> Empresas Más Seguidas
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2" id="topCompaniesBody">
                    <li class="item text-center text-muted py-3">Cargando...</li>
                </ul>
            </div>
            <div class="overlay" id="companies-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Recent Tickets Table + Top Articles -->
<div class="row">
    <!-- Recent Tickets Table -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-ticket-alt mr-2"></i> Tickets Recientes
                </h3>
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
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Actualizado</th>
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
            <div class="overlay" id="tickets-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Top 5 Most Viewed Articles -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-fire-alt mr-2"></i> Artículos Más Vistos
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2" id="topArticlesBody">
                    <li class="item text-center text-muted py-3">Cargando...</li>
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="/app/user/help-center" class="uppercase">Ver todos los artículos</a>
            </div>
            <div class="overlay" id="articles-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 5: Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-2"></i> Acciones Rápidas
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <a href="/app/user/tickets/create" class="btn btn-block btn-success btn-lg mb-2">
                            <i class="fas fa-plus mr-2"></i> Crear Nuevo Ticket
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/app/user/tickets" class="btn btn-block btn-primary btn-lg mb-2">
                            <i class="fas fa-list mr-2"></i> Ver Mis Tickets
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/app/user/help-center" class="btn btn-block btn-info btn-lg mb-2">
                            <i class="fas fa-question-circle mr-2"></i> Centro de Ayuda
                        </a>
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
(function() {
    'use strict';

    // =========================================================================
    // CONFIGURATION
    // =========================================================================
    const CONFIG = {
        API_URL: '/api',
        ENDPOINTS: {
            USER_DASHBOARD: '/analytics/user-dashboard'
        }
    };

    // =========================================================================
    // UTILITY FUNCTIONS
    // =========================================================================

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

    function getPriorityLabel(priority) {
        const map = {
            'HIGH': 'Alta',
            'MEDIUM': 'Media',
            'LOW': 'Baja'
        };
        return map[priority] || priority;
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

    function hideOverlay(id) {
        const overlay = document.getElementById(id);
        if (overlay) overlay.style.display = 'none';
    }

    function hideAllOverlays() {
        const overlays = [
            'kpi-overlay-1', 'kpi-overlay-2', 'kpi-overlay-3', 'kpi-overlay-4',
            'profile-overlay', 'priority-overlay', 'status-overlay', 
            'trend-overlay', 'companies-overlay', 'tickets-overlay', 'articles-overlay'
        ];
        overlays.forEach(hideOverlay);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // =========================================================================
    // CHART INSTANCES
    // =========================================================================
    let ticketStatusChartInstance = null;
    let ticketsTrendChartInstance = null;

    // =========================================================================
    // RENDER FUNCTIONS
    // =========================================================================

    function renderUserProfile(profile) {
        if (!profile) return;

        const userName = document.getElementById('user-name');
        const userSince = document.getElementById('user-since');
        const profileTotal = document.getElementById('profile-total');
        const profileResolved = document.getElementById('profile-resolved');
        const profileRate = document.getElementById('profile-rate');
        const userAvatar = document.getElementById('user-avatar');

        if (userName) userName.textContent = profile.name || 'Usuario';
        if (userSince) userSince.textContent = profile.member_since ? `Miembro desde ${profile.member_since}` : '';
        if (profileTotal) profileTotal.textContent = profile.total_tickets || 0;
        if (profileResolved) profileResolved.textContent = profile.resolved_tickets || 0;
        if (profileRate) profileRate.textContent = (profile.resolution_rate || 0) + '%';

        if (userAvatar && profile.avatar_url) {
            userAvatar.src = profile.avatar_url;
        }
    }

    function renderPriorityDistribution(priority) {
        if (!priority) return;

        const highCount = document.getElementById('priority-high-count');
        const highPct = document.getElementById('priority-high-pct');
        const mediumCount = document.getElementById('priority-medium-count');
        const mediumPct = document.getElementById('priority-medium-pct');
        const lowCount = document.getElementById('priority-low-count');
        const lowPct = document.getElementById('priority-low-pct');
        const total = document.getElementById('priority-total');
        const highBar = document.getElementById('priority-high-bar');
        const mediumBar = document.getElementById('priority-medium-bar');
        const lowBar = document.getElementById('priority-low-bar');

        if (highCount) highCount.innerHTML = '<b>' + (priority.high?.count || 0) + '</b>';
        if (highPct) highPct.textContent = priority.high?.percentage || 0;
        if (mediumCount) mediumCount.innerHTML = '<b>' + (priority.medium?.count || 0) + '</b>';
        if (mediumPct) mediumPct.textContent = priority.medium?.percentage || 0;
        if (lowCount) lowCount.innerHTML = '<b>' + (priority.low?.count || 0) + '</b>';
        if (lowPct) lowPct.textContent = priority.low?.percentage || 0;
        if (total) total.textContent = priority.total || 0;

        if (highBar) highBar.style.width = (priority.high?.percentage || 0) + '%';
        if (mediumBar) mediumBar.style.width = (priority.medium?.percentage || 0) + '%';
        if (lowBar) lowBar.style.width = (priority.low?.percentage || 0) + '%';
    }

    function renderTicketStatusChart(data) {
        if (!data) return;

        const ctx = document.getElementById('ticketStatusChart');
        if (!ctx) return;

        if (ticketStatusChartInstance) {
            ticketStatusChartInstance.destroy();
        }

        ticketStatusChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Abiertos', 'Pendientes', 'Resueltos', 'Cerrados'],
                datasets: [{
                    data: [data.OPEN || 0, data.PENDING || 0, data.RESOLVED || 0, data.CLOSED || 0],
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
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                }
            }
        });
    }

    function renderTicketsTrendChart(data) {
        if (!data) return;

        const ctx = document.getElementById('ticketsTrendChart');
        if (!ctx) return;

        if (ticketsTrendChartInstance) {
            ticketsTrendChartInstance.destroy();
        }

        ticketsTrendChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Tickets Creados',
                    data: data.data || [],
                    backgroundColor: 'rgba(23, 162, 184, 0.3)',
                    borderColor: '#17a2b8',
                    pointRadius: 5,
                    pointBackgroundColor: '#17a2b8',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function renderTopCompanies(companies) {
        const list = document.getElementById('topCompaniesBody');
        list.innerHTML = '';

        if (!companies || companies.length === 0) {
            list.innerHTML = '<li class="item text-center text-muted py-3"><i class="fas fa-building mr-2"></i>No hay empresas disponibles</li>';
            return;
        }

        companies.forEach((company, index) => {
            const li = document.createElement('li');
            li.className = 'item';
            li.innerHTML = `
                <div class="product-img">
                    ${company.logo_url 
                        ? `<img src="${escapeHtml(company.logo_url)}" alt="${escapeHtml(company.name)}" class="img-size-50" style="object-fit: contain;">` 
                        : `<span class="badge badge-secondary" style="font-size: 1.2rem; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">${index + 1}</span>`
                    }
                </div>
                <div class="product-info">
                    <span class="product-title">
                        ${escapeHtml(company.name)}
                        <span class="badge badge-info float-right"><i class="fas fa-users mr-1"></i>${company.followers_count}</span>
                    </span>
                    <span class="product-description">
                        ${escapeHtml(company.industry || 'General')}
                    </span>
                </div>
            `;
            list.appendChild(li);
        });
    }

    function renderTopArticles(articles) {
        const list = document.getElementById('topArticlesBody');
        list.innerHTML = '';

        if (!articles || articles.length === 0) {
            list.innerHTML = '<li class="item text-center text-muted py-3"><i class="fas fa-file-alt mr-2"></i>No hay artículos disponibles</li>';
            return;
        }

        articles.forEach((article) => {
            const li = document.createElement('li');
            li.className = 'item';
            li.innerHTML = `
                <div class="product-img">
                    <i class="fas fa-file-alt fa-2x text-info"></i>
                </div>
                <div class="product-info">
                    <a href="/app/user/help-center/articles/${article.id}" class="product-title">
                        ${escapeHtml(article.title)}
                        <span class="badge badge-success float-right"><i class="fas fa-eye mr-1"></i>${article.views_count}</span>
                    </a>
                    <span class="product-description">
                        ${escapeHtml(article.category || 'General')} · ${escapeHtml(article.published_at || '')}
                    </span>
                </div>
            `;
            list.appendChild(li);
        });
    }

    function renderRecentTickets(tickets) {
        const tbody = document.getElementById('recentTicketsBody');
        tbody.innerHTML = '';

        if (!tickets || tickets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-inbox mr-2"></i>No tienes tickets recientes</td></tr>';
            return;
        }

        tickets.forEach(ticket => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><a href="/app/user/tickets/${escapeHtml(ticket.ticket_code)}" class="font-weight-bold">${escapeHtml(ticket.ticket_code)}</a></td>
                <td>${escapeHtml(ticket.title.substring(0, 40))}${ticket.title.length > 40 ? '...' : ''}</td>
                <td><span class="badge ${getPriorityBadgeClass(ticket.priority)}">${getPriorityLabel(ticket.priority)}</span></td>
                <td>
                    <span class="badge ${getStatusBadgeClass(ticket.status)}">
                        ${getStatusLabel(ticket.status)}
                    </span>
                </td>
                <td><small class="text-muted">${escapeHtml(ticket.updated_at)}</small></td>
            `;
            tbody.appendChild(row);
        });
    }

    // =========================================================================
    // MAIN INITIALIZATION
    // =========================================================================

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const token = getAccessToken();

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

            fetch(`${CONFIG.API_URL}${CONFIG.ENDPOINTS.USER_DASHBOARD}`, {
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

                // Hide all overlays
                hideAllOverlays();

                // Update KPI cards
                document.getElementById('total-tickets').textContent = data.kpi?.total_tickets || 0;
                document.getElementById('open-tickets').textContent = data.kpi?.open_tickets || 0;
                document.getElementById('pending-tickets').textContent = data.kpi?.pending_tickets || 0;
                document.getElementById('resolved-tickets').textContent = (data.kpi?.resolved_tickets || 0) + (data.kpi?.closed_tickets || 0);

                // Render Profile Widget
                renderUserProfile(data.profile);

                // Render Priority Distribution
                renderPriorityDistribution(data.priority_distribution);

                // Render Status Chart
                renderTicketStatusChart(data.ticket_status);

                // Render Tickets Trend Chart
                renderTicketsTrendChart(data.tickets_trend);

                // Render Top Companies
                renderTopCompanies(data.top_companies);

                // Render Top Articles
                renderTopArticles(data.top_articles);

                // Render Recent Tickets
                renderRecentTickets(data.recent_tickets);
            })
            .catch(error => {
                console.error('[User Dashboard] Error loading dashboard:', error);
                hideAllOverlays();
            });

        }, 500);
    });

})();
</script>
@endpush
