@extends('layouts.authenticated')

@section('title', 'Historial de Tickets - Reportes')
@section('content_header', 'Historial de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/user/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Historial de Tickets</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-file-alt"></i> Reporte de Historial de Tickets</h5>
        <p class="mb-0">Visualiza estadísticas, gráficos y descarga reportes detallados de tus tickets.</p>
    </div>

    {{-- KPI Small Boxes Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary" id="kpi-total" style="cursor: pointer;">
                <div class="inner">
                    <h3 id="stat-total"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Total Tickets</p>
                </div>
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger" id="kpi-open" style="cursor: pointer;" data-status="OPEN">
                <div class="inner">
                    <h3 id="stat-open">0</h3>
                    <p>Abiertos</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning" id="kpi-pending" style="cursor: pointer;" data-status="PENDING">
                <div class="inner">
                    <h3 id="stat-pending">0</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success" id="kpi-resolved" style="cursor: pointer;" data-status="RESOLVED">
                <div class="inner">
                    <h3 id="stat-resolved">0</h3>
                    <p>Resueltos / Cerrados</p>
                </div>
                <div class="icon"><i class="fas fa-check-double"></i></div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        {{-- Status/Trend Distribution Chart --}}
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i> Tickets Creados (Últimos 6 meses)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="monthlyChart"></canvas>
                        <div id="chart-loader-monthly"
                            style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8);">
                            <i class="fas fa-2x fa-sync-alt fa-spin text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Priority Distribution Chart --}}
        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Por Prioridad</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="priorityChart"></canvas>
                        <div id="chart-loader-priority"
                            style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8);">
                            <i class="fas fa-2x fa-sync-alt fa-spin text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Report Config Card (Filters) --}}
        <div class="col-lg-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Reporte Personalizado</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-filter"></i> Estado</label>
                                <select class="form-control" id="filterStatus">
                                    <option value="">Todos los tickets</option>
                                    <option value="OPEN">Solo Abiertos</option>
                                    <option value="PENDING">Solo Pendientes</option>
                                    <option value="RESOLVED">Solo Resueltos</option>
                                    <option value="CLOSED">Solo Cerrados</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-layer-group"></i> Prioridad</label>
                                <select class="form-control" id="filterPriority">
                                    <option value="">Todas las prioridades</option>
                                    <option value="HIGH">Alta</option>
                                    <option value="MEDIUM">Media</option>
                                    <option value="LOW">Baja</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                             <div class="form-group">
                                <label><i class="fas fa-building"></i> Empresa</label>
                                <select class="form-control" id="filterCompany">
                                    <option value="">Todas las empresas</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                             <div class="form-group">
                                <label><i class="fas fa-tags"></i> Categoría</label>
                                <select class="form-control" id="filterCategory">
                                    <option value="">Todas las categorías</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" data-company="{{ $category->company_id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Fecha de Creación</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="filterDateFrom" placeholder="Desde">
                                    <div class="input-group-append">
                                        <span class="input-group-text">a</span>
                                    </div>
                                    <input type="date" class="form-control" id="filterDateTo" placeholder="Hasta">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-success" id="btnTicketsExcel" title="Descargar Excel">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                                <button type="button" class="btn btn-danger" id="btnTicketsPdf" title="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-muted small mt-2">
                        <i class="fas fa-info-circle"></i> Los filtros seleccionados se aplicarán al archivo descargado.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Tickets Table --}}
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history mr-2"></i> Últimos Tickets Registrados</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Código</th>
                        <th>Asunto</th>
                        <th>Categoría</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                    </tr>
                </thead>
                <tbody id="recentTicketsTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Cargando tickets...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-center">
            <a href="/app/user/tickets" class="btn btn-sm btn-outline-dark">Ver Historial Completo en Gestión de Tickets</a>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            'use strict';

            const CONFIG = {
                API_BASE: '/api',
                REPORTS_BASE: '/app/user/reports'
            };

            // =========================================================================
            // UTILITIES
            // =========================================================================
            function getToken() {
                return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                return new Date(dateString).toLocaleDateString('es-ES', {
                    year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'
                });
            }

            function showDownloadToast(type) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: type === 'excel' ? 'Generando Excel...' : 'Generando PDF...',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                }
            }

            // =========================================================================
            // DATA LOADING
            // =========================================================================
            async function loadDashboardData() {
                try {
                    const response = await fetch(`${CONFIG.API_BASE}/analytics/user-dashboard`, {
                        headers: {
                            'Authorization': `Bearer ${getToken()}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to load dashboard data');

                    const data = await response.json();

                    // 1. Update KPIs
                    updateKPIs(data);

                    // 2. Render Charts
                    renderMonthlyChart(data.tickets_trend);
                    renderPriorityChart(data.priority_distribution);

                    // 3. Render Recent Tickets
                    renderRecentTickets(data.recent_tickets);

                } catch (error) {
                    console.error('[Reports] Data load error:', error);
                    document.getElementById('recentTicketsTableBody').innerHTML =
                        '<tr><td colspan="6" class="text-center text-danger">Error al cargar datos. Por favor recarga la página.</td></tr>';
                }
            }

            function updateKPIs(data) {
                const kpi = data.kpi || {};
                const total = kpi.total_tickets || 0;
                const resolved = kpi.resolved_tickets || 0;
                const closed = kpi.closed_tickets || 0;

                document.getElementById('stat-total').textContent = total;
                document.getElementById('stat-open').textContent = kpi.open_tickets || 0;
                document.getElementById('stat-pending').textContent = kpi.pending_tickets || 0;
                document.getElementById('stat-resolved').textContent = resolved + closed;
            }

            function renderMonthlyChart(trendData) {
                const ctx = document.getElementById('monthlyChart');
                document.getElementById('chart-loader-monthly').style.display = 'none';

                if (!ctx || !trendData) return;

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: trendData.labels || [],
                        datasets: [{
                            label: 'Tickets Creados',
                            data: trendData.data || [],
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            function renderPriorityChart(priorityData) {
                const ctx = document.getElementById('priorityChart');
                document.getElementById('chart-loader-priority').style.display = 'none';

                if (!ctx || !priorityData) return;

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Alta', 'Media', 'Baja'],
                        datasets: [{
                            data: [
                                priorityData.high?.count || 0,
                                priorityData.medium?.count || 0,
                                priorityData.low?.count || 0
                            ],
                            backgroundColor: [
                                '#dc3545', // Red
                                '#ffc107', // Yellow
                                '#28a745'  // Green
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, padding: 20 }
                            }
                        }
                    }
                });
            }

            function renderRecentTickets(tickets) {
                const tbody = document.getElementById('recentTicketsTableBody');
                if (!tickets || tickets.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No has registrado tickets recientemente.</td></tr>';
                    return;
                }

                tbody.innerHTML = tickets.map(t => {
                    const statusLabels = {
                        'OPEN': '<span class="badge badge-danger">Abierto</span>',
                        'PENDING': '<span class="badge badge-warning">Pendiente</span>',
                        'RESOLVED': '<span class="badge badge-success">Resuelto</span>',
                        'CLOSED': '<span class="badge badge-secondary">Cerrado</span>'
                    };

                    const priorityLabels = {
                        'HIGH': '<span class="text-danger font-weight-bold"><i class="fas fa-arrow-up"></i> Alta</span>',
                        'MEDIUM': '<span class="text-warning font-weight-bold"><i class="fas fa-minus"></i> Media</span>',
                        'LOW': '<span class="text-success font-weight-bold"><i class="fas fa-arrow-down"></i> Baja</span>'
                    };

                    const status = (t.status || '').toUpperCase();
                    const priority = (t.priority || '').toUpperCase();

                    return `
                    <tr>
                        <td><code>${escapeHtml(t.ticket_code)}</code></td>
                        <td>${escapeHtml(t.title)}</td>
                        <td>${escapeHtml(t.category)}</td>
                        <td>${priorityLabels[priority] || priority}</td>
                        <td>${statusLabels[status] || status}</td>
                        <td>${formatDate(t.created_at)}</td>
                    </tr>
                `;
                }).join('');
            }

            // =========================================================================
            // EXPORT LOGIC
            // =========================================================================
            function getExportUrl(type) {
                const params = new URLSearchParams();
                const status = document.getElementById('filterStatus').value;
                const priority = document.getElementById('filterPriority').value;
                const company = document.getElementById('filterCompany').value;
                const category = document.getElementById('filterCategory').value;
                const dateFrom = document.getElementById('filterDateFrom').value;
                const dateTo = document.getElementById('filterDateTo').value;

                if (status) params.append('status', status);
                if (priority) params.append('priority', priority);
                if (company) params.append('company_id', company);
                if (category) params.append('category_id', category);
                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);

                return `${CONFIG.REPORTS_BASE}/tickets/${type}?${params.toString()}`;
            }

            // Dependent Dropdown for Categories
            document.getElementById('filterCompany').addEventListener('change', function() {
                const selectedCompany = this.value;
                const categorySelect = document.getElementById('filterCategory');
                const options = categorySelect.getElementsByTagName('option');
                
                // Reset category selection
                categorySelect.value = "";
                
                for (let i = 0; i < options.length; i++) {
                    const option = options[i];
                    const companyId = option.getAttribute('data-company');
                    
                    if (!selectedCompany || !companyId || companyId === selectedCompany) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                }
            });

            document.getElementById('btnTicketsExcel').onclick = function () {
                showDownloadToast('excel');
                window.location.href = getExportUrl('excel');
            };

            document.getElementById('btnTicketsPdf').onclick = function () {
                showDownloadToast('pdf');
                window.location.href = getExportUrl('pdf');
            };

            // KPI Interactivity
            document.querySelectorAll('.small-box[data-status]').forEach(box => {
                box.addEventListener('click', function () {
                    const status = this.dataset.status;
                    document.getElementById('filterStatus').value = status;
                    // Optional: smooth scroll to filters
                    document.querySelector('.card-outline.card-secondary').scrollIntoView({ behavior: 'smooth' });
                });
            });

            // Initialize
            if (window.tokenManager) {
                loadDashboardData();
            } else {
                setTimeout(loadDashboardData, 500);
            }

        })();
    </script>
@endpush