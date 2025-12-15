@extends('layouts.authenticated')

@section('title', 'Reporte de Tickets - Company Admin')
@section('content_header', 'Reporte de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Tickets</li>
@endsection

@section('content')

{{-- Page Description --}}
<div class="callout callout-info">
    <h5><i class="fas fa-ticket-alt"></i> Reporte de Tickets de la Empresa</h5>
    <p class="mb-0">Visualiza estadísticas, gráficos y genera reportes de todos los tickets de tu empresa con filtros avanzados.</p>
</div>

{{-- KPI Small Boxes Row --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $kpis['total'] }}</h3>
                <p>Total Tickets</p>
            </div>
            <div class="icon"><i class="fas fa-ticket-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $kpis['open'] }}</h3>
                <p>Abiertos</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $kpis['pending'] }}</h3>
                <p>Pendientes</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $kpis['resolved'] }}</h3>
                <p>Resueltos / Cerrados</p>
            </div>
            <div class="icon"><i class="fas fa-check-double"></i></div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row">
    {{-- Monthly Trend Chart --}}
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Tickets por Estado (Últimos 6 meses)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" style="min-height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    {{-- Priority Distribution Chart --}}
    <div class="col-lg-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Por Prioridad</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" style="min-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Recent Tickets Table --}}
    <div class="col-lg-8">
        <div class="card card-outline card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i> Últimos Tickets</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Código</th>
                            <th>Asunto</th>
                            <th>Usuario</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td><code>{{ $ticket->ticket_code }}</code></td>
                            <td>{{ Str::limit($ticket->subject, 40) }}</td>
                            <td><small>{{ $ticket->creator?->profile?->display_name ?? $ticket->creator?->email ?? '-' }}</small></td>
                            <td>
                                @if($ticket->priority === 'high')
                                    <span class="badge badge-danger">Alta</span>
                                @elseif($ticket->priority === 'medium')
                                    <span class="badge badge-warning">Media</span>
                                @else
                                    <span class="badge badge-info">Baja</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->status === 'open')
                                    <span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Abierto</span>
                                @elseif($ticket->status === 'pending')
                                    <span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>
                                @elseif($ticket->status === 'resolved')
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Resuelto</span>
                                @else
                                    <span class="badge badge-secondary"><i class="fas fa-lock"></i> Cerrado</span>
                                @endif
                            </td>
                            <td><small>{{ $ticket->created_at?->format('d/m/y H:i') }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No hay tickets registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/company/tickets" class="btn btn-sm btn-outline-dark">Ver todos los tickets</a>
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
                                <option value="open">Abiertos</option>
                                <option value="pending">Pendientes</option>
                                <option value="resolved">Resueltos</option>
                                <option value="closed">Cerrados</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-layer-group"></i> Prioridad</label>
                            <select class="form-control form-control-sm" id="filterPriority">
                                <option value="">Todas</option>
                                <option value="high">Alta</option>
                                <option value="medium">Media</option>
                                <option value="low">Baja</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Agente</label>
                            <select class="form-control form-control-sm" id="filterAgent">
                                <option value="">Todos</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->profile?->display_name ?? $agent->email }}</option>
                                @endforeach
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Área</label>
                            <select class="form-control form-control-sm" id="filterArea">
                                <option value="">Todas</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                    <strong>Filtros disponibles:</strong> Estado, Prioridad, Agente, Categoría, Área y Rango de Fechas.
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
(function() {
    'use strict';
    
    // Monthly Chart Data (from server)
    const monthlyData = @json($monthlyData);
    const priorityStats = @json($priorityStats);
    
    // Render Monthly Chart
    const ctxMonthly = document.getElementById('monthlyChart');
    if (ctxMonthly && monthlyData.length > 0) {
        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: monthlyData.map(m => m.label),
                datasets: [{
                    label: 'Tickets Creados',
                    data: monthlyData.map(m => m.count),
                    backgroundColor: 'rgba(0, 123, 255, 0.7)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 5 } } }
            }
        });
    }
    
    // Render Priority Chart
    const ctxPriority = document.getElementById('priorityChart');
    if (ctxPriority) {
        new Chart(ctxPriority, {
            type: 'doughnut',
            data: {
                labels: ['Alta', 'Media', 'Baja'],
                datasets: [{
                    data: [
                        priorityStats.high || 0,
                        priorityStats.medium || 0,
                        priorityStats.low || 0
                    ],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(23, 162, 184, 0.8)'
                    ],
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
    
    // Export handlers
    function buildQuery() {
        const params = new URLSearchParams();
        const status = document.getElementById('filterStatus').value;
        const priority = document.getElementById('filterPriority').value;
        const agent = document.getElementById('filterAgent').value;
        const category = document.getElementById('filterCategory').value;
        const area = document.getElementById('filterArea').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        
        if (status) params.append('status', status);
        if (priority) params.append('priority', priority);
        if (agent) params.append('agent_id', agent);
        if (category) params.append('category_id', category);
        if (area) params.append('area_id', area);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        return params.toString() ? '?' + params.toString() : '';
    }
    
    document.getElementById('btnExcel').onclick = function(e) {
        e.preventDefault();
        window.location.href = '/app/company/reports/tickets/excel' + buildQuery();
    };
    
    document.getElementById('btnPdf').onclick = function(e) {
        e.preventDefault();
        window.location.href = '/app/company/reports/tickets/pdf' + buildQuery();
    };
    
})();
</script>
@endpush