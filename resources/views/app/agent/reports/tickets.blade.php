@extends('layouts.authenticated')

@section('title', 'Mis Tickets - Reporte')
@section('content_header', 'Reporte de Mis Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/agent/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Mis Tickets</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-ticket-alt"></i> Reporte de Tickets Asignados</h5>
        <p class="mb-0">Visualiza estadísticas, gráficos y genera reportes de todos los tickets asignados a ti con filtros
            avanzados.</p>
    </div>

    {{-- KPI Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($kpis['total']) }}</h3>
                    <p>Total Asignados</p>
                </div>
                <div class="icon"><i class="fas fa-inbox"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($kpis['open']) }}</h3>
                    <p>Abiertos</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($kpis['pending']) }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($kpis['resolved']) }}</h3>
                    <p>Resueltos/Cerrados</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        {{-- Monthly Trend Chart --}}
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Tickets Asignados (Últimos 6 meses)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" style="min-height: 280px;"></canvas>
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
                    <canvas id="priorityChart" style="min-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Tickets Table --}}
        <div class="col-lg-8">
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i> Tickets Recientes (Últimos 50)</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Código</th>
                                <th>Título</th>
                                <th>Creador</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                        <tr>
                                            <td>
                                                <a href="/app/agent/tickets/{{ $ticket->ticket_code }}" class="font-weight-bold">
                                                    {{ $ticket->ticket_code }}
                                                </a>
                                            </td>
                                            <td>{{ Str::limit($ticket->title, 35) }}</td>
                                            <td><small>{{ $ticket->creator?->profile?->display_name ?? $ticket->creator?->email ?? '-' }}</small>
                                            </td>
                                            <td>
                                   @php
                                    $priorityColors = ['HIGH' => 'danger', 'MEDIUM' => 'warning', 'LOW' => 'success'];
                                    $priorityLabels = ['HIGH' => 'Alta', 'MEDIUM' => 'Media', 'LOW' => 'Baja'];
                                    $priority = $ticket->priority->value ?? $ticket->priority;
                                @endphp
                                                <span class="badge badge-{{ $priorityColors[$priority] ?? 'secondary' }}">
                                                    {{ $priorityLabels[$priority] ?? $priority }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $statusColors = ['OPEN' => 'danger', 'PENDING' => 'warning', 'RESOLVED' => 'info', 'CLOSED' => 'success'];
                                                    $statusLabels = ['OPEN' => 'Abierto', 'PENDING' => 'Pendiente', 'RESOLVED' => 'Resuelto', 'CLOSED' => 'Cerrado'];
                                                    $status = $ticket->status->value ?? $ticket->status;
                                                @endphp
                                                <span class="badge badge-{{ $statusColors[$status] ?? 'secondary' }}">
                                                    {{ $statusLabels[$status] ?? $status }}
                                                </span>
                                            </td>
                                            <td><small class="text-muted">{{ $ticket->created_at->format('d/m/Y H:i') }}</small></td>
                                        </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        No tienes tickets asignados
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="/app/agent/tickets" class="btn btn-sm btn-outline-dark">Ver todos mis tickets</a>
                </div>
            </div>

            {{-- Export Section --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Reporte</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-filter"></i> Estado</label>
                                <select class="form-control form-control-sm" id="filterStatus">
                                    <option value="">Todos</option>
                                    <option value="OPEN">Abiertos</option>
                                    <option value="PENDING">Pendientes</option>
                                    <option value="RESOLVED">Resueltos</option>
                                    <option value="CLOSED">Cerrados</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-layer-group"></i> Prioridad</label>
                                <select class="form-control form-control-sm" id="filterPriority">
                                    <option value="">Todas</option>
                                    <option value="HIGH">Alta</option>
                                    <option value="MEDIUM">Media</option>
                                    <option value="LOW">Baja</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-tags"></i> Categoría</label>
                                <select class="form-control form-control-sm" id="filterCategory">
                                    <option value="">Todas</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Rango de Fechas</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" class="form-control" id="filterDateFrom">
                                    <input type="date" class="form-control" id="filterDateTo">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <a href="#" class="btn btn-success" id="btnExcel">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </a>
                    <a href="#" class="btn btn-danger" id="btnPdf">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Status Distribution --}}
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Distribución por Estado</h3>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" style="min-height: 200px;"></canvas>
                </div>
            </div>

            {{-- Top Categories --}}
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i> Top Categorías</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topCategories as $cat)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-tag text-success mr-2"></i> {{ $cat['name'] }}</span>
                                <span class="badge badge-success badge-pill">{{ $cat['count'] }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">Sin datos</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Info Card --}}
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Información</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2 small">
                        <strong>Filtros disponibles:</strong> Estado, Prioridad, Categoría y Rango de Fechas.
                    </p>
                    <p class="text-muted mb-0 small">
                        Los filtros se aplican al exportar Excel o PDF.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            'use strict';

            const REPORTS_BASE = '/app/agent/reports';

            // Data from controller
            const monthlyData = @json($monthlyData);
            const priorityStats = @json($priorityStats);
            const statusData = @json($statusStats);

            // Monthly Chart
            const ctxMonthly = document.getElementById('monthlyChart');
            if (ctxMonthly && monthlyData.length > 0) {
                new Chart(ctxMonthly, {
                    type: 'bar',
                    data: {
                        labels: monthlyData.map(m => m.label),
                        datasets: [{
                            label: 'Tickets Asignados',
                            data: monthlyData.map(m => m.count),
                            backgroundColor: 'rgba(23, 162, 184, 0.7)',
                            borderColor: 'rgba(23, 162, 184, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }

            // Priority Chart
            const ctxPriority = document.getElementById('priorityChart');
            if (ctxPriority) {
                new Chart(ctxPriority, {
                    type: 'doughnut',
                    data: {
                        labels: ['Alta', 'Media', 'Baja'],
                        datasets: [{
                            data: [priorityStats.high || 0, priorityStats.medium || 0, priorityStats.low || 0],
                            backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }

            // Status Chart (Consistent colors with KPIs)
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Abiertos', 'Pendientes', 'Resueltos'],
                    datasets: [{
                        data: [
                            statusData['OPEN'] || 0,
                            statusData['PENDING'] || 0,
                            (statusData['RESOLVED'] || 0) + (statusData['CLOSED'] || 0)
                        ],
                        backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                        borderColor: ['#dc3545', '#ffc107', '#28a745'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // Export handlers
            function buildQuery() {
                const params = new URLSearchParams();
                const status = document.getElementById('filterStatus').value;
                const priority = document.getElementById('filterPriority').value;
                const category = document.getElementById('filterCategory').value;
                const dateFrom = document.getElementById('filterDateFrom').value;
                const dateTo = document.getElementById('filterDateTo').value;

                if (status) params.append('status', status);
                if (priority) params.append('priority', priority);
                if (category) params.append('category_id', category);
                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);

                return params.toString() ? '?' + params.toString() : '';
            }

            document.getElementById('btnExcel').onclick = function (e) {
                e.preventDefault();
                window.location.href = REPORTS_BASE + '/tickets/excel' + buildQuery();
            };

            document.getElementById('btnPdf').onclick = function (e) {
                e.preventDefault();
                window.location.href = REPORTS_BASE + '/tickets/pdf' + buildQuery();
            };

        })();
    </script>
@endpush