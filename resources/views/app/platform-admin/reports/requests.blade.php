@extends('layouts.authenticated')

@section('title', 'Reporte de Solicitudes - Platform Admin')
@section('content_header', 'Solicitudes de Empresa')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Solicitudes</li>
@endsection

@section('content')

{{-- Page Description --}}
<div class="callout callout-info">
    <h5><i class="fas fa-inbox"></i> Historial de Solicitudes de Empresa</h5>
    <p class="mb-0">Registro completo de todas las solicitudes de registro de empresas con su estado de revisión.</p>
</div>

{{-- Summary Cards Row --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['total'] }}</h3>
                <p>Total Solicitudes</p>
            </div>
            <div class="icon">
                <i class="fas fa-inbox"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['pending'] }}</h3>
                <p>Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['approved'] }}</h3>
                <p>Aprobadas</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['rejected'] }}</h3>
                <p>Rechazadas</p>
            </div>
            <div class="icon">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Chart Section --}}
    <div class="col-lg-8">
        {{-- Status Distribution Chart --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Solicitudes por Mes</h3>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" style="min-height: 300px;"></canvas>
            </div>
        </div>
        
        {{-- Recent Requests Table --}}
        <div class="card card-outline card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i> Últimas Solicitudes</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Empresa</th>

                            <th>Industria</th>
                            <th>Estado</th>
                            <th>Revisor</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRequests as $request)
                            <td>
                                <strong>{{ $request->company_name }}</strong>
                                <br><small class="text-muted">{{ $request->admin_email }}</small>
                            </td>
                            <td>{{ $request->industry->name ?? '-' }}</td>
                            <td>
                                @switch($request->status)
                                    @case('pending')
                                        <span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>
                                        @break
                                    @case('approved')
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Aprobada</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Rechazada</span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                @if($request->reviewer)
                                    {{ $request->reviewer->profile->display_name ?? $request->reviewer->email }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No hay solicitudes registradas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Export Section --}}
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Reporte</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><i class="fas fa-filter"></i> Filtrar por Estado</label>
                    <select class="form-control" id="filterStatus">
                        <option value="">Todas las solicitudes</option>
                        <option value="pending">Pendientes</option>
                        <option value="approved">Aprobadas</option>
                        <option value="rejected">Rechazadas</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-industry"></i> Filtrar por Industria</label>
                    <select class="form-control" id="filterIndustry">
                        <option value="">Todas las industrias</option>
                        @foreach($industries as $industry)
                            <option value="{{ $industry->id }}">{{ $industry->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Rango de Fechas</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="filterDateFrom" placeholder="Desde">
                        <input type="date" class="form-control" id="filterDateTo" placeholder="Hasta">
                    </div>
                </div>
                
                <div class="text-muted small mb-3">
                    <i class="fas fa-list"></i> <strong>Incluye:</strong> Empresa, Admin, Industria, Estado, Revisor, Fechas
                </div>
            </div>
            <div class="card-footer bg-light">
                <button type="button" class="btn btn-success btn-block mb-2" id="btnExcel">
                    <i class="fas fa-file-excel"></i> Descargar Excel
                </button>
                <button type="button" class="btn btn-danger btn-block" id="btnPdf">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
            </div>
        </div>
        
        {{-- Status Pie Chart --}}
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Distribución por Estado</h3>
            </div>
            <div class="card-body">
                <canvas id="statusChart" style="min-height: 200px;"></canvas>
            </div>
        </div>
        
        {{-- Processing Time --}}
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-stopwatch mr-2"></i> Tiempo de Procesamiento</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-hourglass-half text-info mr-2"></i> Promedio</span>
                        <span class="badge badge-info badge-pill">{{ $stats['avg_processing_time'] ?? '< 24h' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-percentage text-success mr-2"></i> Tasa Aprobación</span>
                        <span class="badge badge-success badge-pill">
                            @php
                                $processed = $stats['approved'] + $stats['rejected'];
                                $rate = $processed > 0 ? round(($stats['approved'] / $processed) * 100, 1) : 0;
                            @endphp
                            {{ $rate }}%
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-check text-warning mr-2"></i> Este Mes</span>
                        <span class="badge badge-warning badge-pill">{{ $stats['this_month'] ?? 0 }}</span>
                    </li>
                </ul>
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
    
    const REPORTS_BASE = '/app/admin/reports';
    
    // =========================================================================
    // CHARTS DATA FROM CONTROLLER
    // =========================================================================
    const monthlyData = @json($chartData['monthly']);
    const statusData = @json($chartData['status']);
    
    // =========================================================================
    // MONTHLY STACKED BAR CHART
    // =========================================================================
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyData.labels,
            datasets: [
                {
                    label: 'Aprobadas',
                    data: monthlyData.approved,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pendientes',
                    data: monthlyData.pending,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Rechazadas',
                    data: monthlyData.rejected,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                x: { stacked: true },
                y: { 
                    stacked: true,
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
    
    // =========================================================================
    // STATUS PIE CHART
    // =========================================================================
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pendientes', 'Aprobadas', 'Rechazadas'],
            datasets: [{
                data: [statusData.pending, statusData.approved, statusData.rejected],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
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
    
    // =========================================================================
    // DOWNLOAD HANDLERS
    // =========================================================================
    function buildQuery() {
        const status = document.getElementById('filterStatus').value;
        const industry = document.getElementById('filterIndustry').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (industry) params.append('industry_id', industry);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        return params.toString() ? '?' + params.toString() : '';
    }
    
    document.getElementById('btnExcel').onclick = function() {
        window.location.href = `${REPORTS_BASE}/requests/excel${buildQuery()}`;
    };
    
    document.getElementById('btnPdf').onclick = function() {
        window.location.href = `${REPORTS_BASE}/requests/pdf${buildQuery()}`;
    };
    
})();
</script>
@endpush
