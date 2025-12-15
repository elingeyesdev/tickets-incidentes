@extends('layouts.authenticated')

@section('title', 'Dashboard - Agente')

@section('content_header', 'Panel de Agente')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<!-- Row 1: KPI Statistics (Consistent with Platform/Company Admin) -->
<div class="row" id="kpi-row">
    <!-- Assigned Total -->
    <div class="col-lg-3 col-6">
        <div class="small-box" style="background-color: #0d6efd !important; color: white;">
            <div class="inner">
                <h3 id="assigned-total" style="color: white;">0</h3>
                <p style="color: white;">Asignados Total</p>
            </div>
            <div class="icon">
                <i class="fas fa-inbox"></i>
            </div>
            <a href="/app/agent/tickets?filter=assigned" class="small-box-footer" style="color: white;">
                Ver mis tickets <i class="fas fa-arrow-circle-right"></i>
            </a>
            <div class="overlay dark" id="kpi-overlay-1">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
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
            <div class="overlay dark" id="kpi-overlay-2">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
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
            <div class="overlay dark" id="kpi-overlay-3">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
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
            <div class="overlay dark" id="kpi-overlay-4">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Agent Profile Widget + Performance Knobs -->
<div class="row">
    <!-- Agent Profile Widget -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i> Mi Perfil
                </h3>
            </div>
            <div class="card-body text-center">
                <img class="img-circle elevation-2 mb-3" src="/img/default-avatar.png" alt="Avatar" id="agent-avatar" style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="mb-1" id="agent-name">Cargando...</h5>
                <p class="text-muted mb-3" id="agent-role">Agente de Soporte</p>
                <div class="row text-center">
                    <div class="col-4 border-right">
                        <h5 class="font-weight-bold mb-0" id="profile-active">0</h5>
                        <small class="text-muted">Activos</small>
                    </div>
                    <div class="col-4 border-right">
                        <h5 class="font-weight-bold mb-0" id="profile-resolved">0</h5>
                        <small class="text-muted">Resueltos</small>
                    </div>
                    <div class="col-4">
                        <small class="text-muted" id="profile-since">--</small>
                        <br><small class="text-muted">Miembro</small>
                    </div>
                </div>
            </div>
            <div class="overlay" id="profile-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Performance Dashboard with jQuery Knobs -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-2"></i> Panel de Rendimiento
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-md-3 text-center">
                        <input type="text" class="knob" id="knob-workload" value="0" 
                               data-width="90" data-height="90" 
                               data-fgColor="#17a2b8" data-readonly="true">
                        <div class="knob-label mt-2 text-muted">Carga de Trabajo</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <input type="text" class="knob" id="knob-open" value="0" 
                               data-width="90" data-height="90" 
                               data-fgColor="#dc3545" data-readonly="true">
                        <div class="knob-label mt-2 text-muted">% Abiertos</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <input type="text" class="knob" id="knob-pending" value="0" 
                               data-width="90" data-height="90" 
                               data-fgColor="#ffc107" data-readonly="true">
                        <div class="knob-label mt-2 text-muted">% Pendientes</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <input type="text" class="knob" id="knob-resolution" value="0" 
                               data-width="90" data-height="90" 
                               data-fgColor="#28a745" data-readonly="true">
                        <div class="knob-label mt-2 text-muted">Tasa Resolución</div>
                    </div>
                </div>
            </div>
            <div class="overlay" id="performance-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Weekly Activity Line Chart + Workload Donut -->
<div class="row">
    <!-- Weekly Activity Chart -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-area mr-2"></i> Mi Actividad Semanal
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="weeklyActivityChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
            <div class="overlay" id="activity-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Ticket Status Donut -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-2"></i> Estado de Tickets
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="ticketStatusChart" style="min-height: 200px; height: 200px; max-height: 200px; max-width: 100%;"></canvas>
            </div>
            <div class="overlay" id="donut-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Priority Distribution + Assigned Tickets Table -->
<div class="row">
    <!-- Priority Distribution -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-fire mr-2"></i> Por Prioridad
                </h3>
            </div>
            <div class="card-body">
                <!-- High Priority -->
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-danger mr-1"></i> Alta</span>
                    <span class="float-right" id="priority-high-count"><b>0</b></span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" id="priority-high-bar" style="width: 0%"></div>
                    </div>
                </div>
                <!-- Medium Priority -->
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-warning mr-1"></i> Media</span>
                    <span class="float-right" id="priority-medium-count"><b>0</b></span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" id="priority-medium-bar" style="width: 0%"></div>
                    </div>
                </div>
                <!-- Low Priority -->
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-success mr-1"></i> Baja</span>
                    <span class="float-right" id="priority-low-count"><b>0</b></span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" id="priority-low-bar" style="width: 0%"></div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Total Activos:</span>
                    <strong id="priority-total">0</strong>
                </div>
            </div>
            <div class="overlay" id="priority-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- My Assigned Tickets Table -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks mr-2"></i> Mis Tickets Prioritarios
                </h3>
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
                <a href="/app/agent/tickets?filter=assigned" class="btn btn-sm btn-primary float-right">
                    Ver todos mis asignados <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="overlay" id="assigned-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 5: Queue Table + Quick Tools & Articles -->
<div class="row">
    <!-- Unassigned Queue -->
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-layer-group mr-2"></i> Cola de Tickets Sin Asignar
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning" id="queue-count">0</span>
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
                <a href="/app/agent/tickets?filter=unassigned" class="btn btn-sm btn-warning float-right">
                    Ver cola completa <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="overlay" id="queue-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Quick Tools + Articles -->
    <div class="col-md-4">
        <!-- Recent Articles -->
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-book-open mr-2"></i> Artículos Recientes
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2" id="helpCenterBody">
                    <li class="item text-center text-muted py-3">Cargando...</li>
                </ul>
            </div>
            <div class="overlay" id="articles-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 6: Activity Timeline -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i> Actividad Reciente
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="timeline timeline-inverse" id="activityTimeline">
                    <!-- Timeline items will be rendered here -->
                    <div class="text-center text-muted py-3">Cargando actividad...</div>
                </div>
            </div>
            <div class="overlay" id="timeline-overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-Knob/1.2.13/jquery.knob.min.js"></script>

<script>
(function() {
    'use strict';

    // =========================================================================
    // CONFIGURATION
    // =========================================================================
    const CONFIG = {
        API_URL: '/api',
        ENDPOINTS: {
            AGENT_DASHBOARD: '/analytics/agent-dashboard'
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
            'profile-overlay', 'performance-overlay', 'activity-overlay', 
            'donut-overlay', 'priority-overlay', 'assigned-overlay',
            'queue-overlay', 'articles-overlay', 'timeline-overlay'
        ];
        overlays.forEach(hideOverlay);
    }

    // =========================================================================
    // INITIALIZE JQUERY KNOBS
    // =========================================================================

    function initializeKnobs() {
        $('.knob').knob({
            'draw': function() {
                $(this.i).val(this.cv + '%');
            }
        });
    }

    function animateKnob(id, value) {
        const element = $('#' + id);
        $({value: 0}).animate({value: value}, {
            duration: 1000,
            easing: 'swing',
            step: function() {
                element.val(Math.ceil(this.value)).trigger('change');
            },
            complete: function() {
                element.val(value).trigger('change');
            }
        });
    }

    // =========================================================================
    // CHART INSTANCES
    // =========================================================================

    let weeklyActivityChartInstance = null;
    let ticketStatusChartInstance = null;

    // =========================================================================
    // RENDER FUNCTIONS
    // =========================================================================

    function renderAgentProfile(profile) {
        if (!profile) return;

        document.getElementById('agent-name').textContent = profile.name || 'Agent';
        document.getElementById('agent-role').textContent = profile.role || 'Agente de Soporte';
        document.getElementById('profile-active').textContent = profile.total_assigned || 0;
        document.getElementById('profile-resolved').textContent = profile.total_resolved || 0;
        document.getElementById('profile-since').textContent = profile.member_since || '--';

        if (profile.avatar_url) {
            document.getElementById('agent-avatar').src = profile.avatar_url;
        }
    }

    function renderPerformanceMetrics(metrics) {
        if (!metrics) return;

        // Animate Knobs
        animateKnob('knob-workload', metrics.workload || 0);
        animateKnob('knob-open', metrics.open_rate || 0);
        animateKnob('knob-pending', metrics.pending_rate || 0);
        animateKnob('knob-resolution', metrics.resolution_rate || 0);
    }

    function renderPriorityDistribution(priority) {
        if (!priority) return;

        document.getElementById('priority-high-count').innerHTML = '<b>' + (priority.high?.count || 0) + '</b>';
        document.getElementById('priority-medium-count').innerHTML = '<b>' + (priority.medium?.count || 0) + '</b>';
        document.getElementById('priority-low-count').innerHTML = '<b>' + (priority.low?.count || 0) + '</b>';
        document.getElementById('priority-total').textContent = priority.total || 0;

        document.getElementById('priority-high-bar').style.width = (priority.high?.percentage || 0) + '%';
        document.getElementById('priority-medium-bar').style.width = (priority.medium?.percentage || 0) + '%';
        document.getElementById('priority-low-bar').style.width = (priority.low?.percentage || 0) + '%';
    }

    function renderWeeklyActivity(data) {
        if (!data) return;

        const ctx = document.getElementById('weeklyActivityChart');
        if (!ctx) return;

        if (weeklyActivityChartInstance) {
            weeklyActivityChartInstance.destroy();
        }

        weeklyActivityChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Tickets Resueltos',
                    data: data.data || [],
                    backgroundColor: 'rgba(13, 110, 253, 0.3)',
                    borderColor: '#0d6efd',
                    pointRadius: 4,
                    pointBackgroundColor: '#0d6efd',
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
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function renderAssignedTickets(tickets) {
        const tbody = document.getElementById('assignedTicketsBody');
        tbody.innerHTML = '';

        if (!tickets || tickets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-check-circle text-success mr-2"></i>No tienes tickets asignados activos</td></tr>';
            return;
        }

        tickets.forEach(ticket => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><a href="/app/agent/tickets/${ticket.ticket_code}" class="font-weight-bold">${ticket.ticket_code}</a></td>
                <td>${ticket.title.substring(0, 40)}${ticket.title.length > 40 ? '...' : ''}</td>
                <td><span class="badge ${getPriorityBadgeClass(ticket.priority)}">${ticket.priority}</span></td>
                <td><span class="badge ${getStatusBadgeClass(ticket.status)}">${getStatusLabel(ticket.status)}</span></td>
                <td><small class="text-muted">${ticket.created_at}</small></td>
            `;
            tbody.appendChild(row);
        });
    }

    function renderUnassignedTickets(tickets) {
        const tbody = document.getElementById('unassignedTicketsBody');
        tbody.innerHTML = '';

        document.getElementById('queue-count').textContent = tickets?.length || 0;

        if (!tickets || tickets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-inbox text-info mr-2"></i>No hay tickets sin asignar</td></tr>';
            return;
        }

        tickets.forEach(ticket => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><a href="/app/agent/tickets/${ticket.ticket_code}">${ticket.ticket_code}</a></td>
                <td>${ticket.title.substring(0, 35)}${ticket.title.length > 35 ? '...' : ''}</td>
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

        if (!articles || articles.length === 0) {
            list.innerHTML = '<li class="item text-center text-muted py-3">No hay artículos recientes</li>';
            return;
        }

        articles.forEach(article => {
            const li = document.createElement('li');
            li.className = 'item';
            li.innerHTML = `
                <div class="product-img">
                    <i class="fas fa-file-alt fa-2x text-info"></i>
                </div>
                <div class="product-info">
                    <a href="/app/agent/help-center/articles/${article.slug}" class="product-title">${article.title}</a>
                    <span class="product-description">
                        <i class="fas fa-eye mr-1"></i>${article.views || 0} vistas
                    </span>
                </div>
            `;
            list.appendChild(li);
        });
    }

    function renderActivityTimeline(activities) {
        const timeline = document.getElementById('activityTimeline');
        timeline.innerHTML = '';

        if (!activities || activities.length === 0) {
            timeline.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-history fa-2x mb-2"></i><br>No hay actividad reciente</div>';
            return;
        }

        let currentDateGroup = null;

        activities.forEach(activity => {
            // Add date label if new group
            if (activity.date_group !== currentDateGroup) {
                currentDateGroup = activity.date_group;
                const timeLabel = document.createElement('div');
                timeLabel.className = 'time-label';
                timeLabel.innerHTML = `<span class="bg-secondary">${currentDateGroup}</span>`;
                timeline.appendChild(timeLabel);
            }

            // Add activity item
            const item = document.createElement('div');
            item.innerHTML = `
                <i class="${activity.icon} ${activity.color}"></i>
                <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock"></i> ${activity.time}</span>
                    <h3 class="timeline-header">${activity.message}</h3>
                    <div class="timeline-body">
                        ${activity.description}
                    </div>
                    <div class="timeline-footer">
                        <a href="/app/agent/tickets/${activity.ticket_code}" class="btn btn-sm btn-outline-primary">
                            Ver ticket
                        </a>
                    </div>
                </div>
            `;
            timeline.appendChild(item);
        });

        // Add end marker
        const endMarker = document.createElement('div');
        endMarker.innerHTML = '<i class="fas fa-clock bg-gray"></i>';
        timeline.appendChild(endMarker);
    }

    // =========================================================================
    // MAIN INITIALIZATION
    // =========================================================================

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize jQuery Knobs first
        initializeKnobs();

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

            fetch(`${CONFIG.API_URL}${CONFIG.ENDPOINTS.AGENT_DASHBOARD}`, {
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
                document.getElementById('assigned-total').textContent = data.kpi?.assigned_total || 0;
                document.getElementById('assigned-open').textContent = data.kpi?.assigned_open || 0;
                document.getElementById('assigned-pending').textContent = data.kpi?.assigned_pending || 0;
                document.getElementById('resolved-today').textContent = data.kpi?.resolved_today || 0;

                // Render all sections
                renderAgentProfile(data.agent_profile);
                renderPerformanceMetrics(data.performance_metrics);
                renderPriorityDistribution(data.priority_distribution);
                renderWeeklyActivity(data.weekly_activity);
                renderTicketStatusChart(data.ticket_status);
                renderAssignedTickets(data.assigned_tickets);
                renderUnassignedTickets(data.unassigned_tickets);
                renderRecentArticles(data.recent_articles);
                renderActivityTimeline(data.recent_activity);

                // Hide all overlays
                hideAllOverlays();
            })
            .catch(error => {
                console.error('[Agent Dashboard] Error loading dashboard:', error);
                hideAllOverlays();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar el dashboard. Por favor intenta de nuevo.'
                });
            });

        }, 500);
    });

})();
</script>

<style>
/* Additional styles for the agent dashboard */
.knob-label {
    font-size: 0.85rem;
    font-weight: 500;
}

.widget-user-2 .widget-user-username {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.widget-user-2 .widget-user-desc {
    font-size: 0.875rem;
    opacity: 0.9;
}

.timeline > div > .timeline-item {
    margin-left: 45px;
}

.timeline-inverse > div > i {
    width: 30px;
    height: 30px;
    line-height: 30px;
    font-size: 12px;
}

.progress-group {
    margin-bottom: 1rem;
}

.progress-group:last-child {
    margin-bottom: 0;
}
</style>
@endpush
