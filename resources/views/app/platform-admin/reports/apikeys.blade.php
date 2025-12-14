@extends('layouts.authenticated')

@section('title', 'Reporte de API Keys - Platform Admin')
@section('content_header', 'Reporte de API Keys')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">API Keys</li>
@endsection

@section('content')

{{-- Page Description --}}
<div class="callout callout-info">
    <h5><i class="fas fa-key"></i> Reporte de Integraciones y API Keys</h5>
    <p class="mb-0">Monitorea el uso de las API Keys de integración externa. Analiza patrones de uso, empresas más activas y estado de las credenciales.</p>
</div>

{{-- Summary Cards Row --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['total'] }}</h3>
                <p>Total API Keys</p>
            </div>
            <div class="icon">
                <i class="fas fa-key"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['active'] }}</h3>
                <p>Activas</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['inactive'] }}</h3>
                <p>Revocadas/Inactivas</p>
            </div>
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($stats['total_usage']) }}</h3>
                <p>Uso Total (requests)</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Chart Section --}}
    <div class="col-lg-8">
        {{-- Usage Over Time --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-area mr-2"></i> Uso de API Keys por Día (últimos 30 días)</h3>
            </div>
            <div class="card-body">
                <canvas id="usageChart" style="min-height: 300px;"></canvas>
            </div>
        </div>
        
        {{-- Top API Keys Table --}}
        <div class="card card-outline card-dark">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trophy mr-2"></i> Top 10 API Keys por Uso</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Empresa</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th class="text-center">Uso Total</th>
                            <th>Último Uso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topApiKeys as $index => $apiKey)
                        <tr>
                            <td><span class="badge badge-primary">{{ $index + 1 }}</span></td>
                            <td>
                                <strong>{{ $apiKey->name }}</strong>
                                <br><small class="text-muted font-monospace">{{ $apiKey->masked_key }}</small>
                            </td>
                            <td>{{ $apiKey->company->name ?? '-' }}</td>
                            <td>
                                @switch($apiKey->type)
                                    @case('production')
                                        <span class="badge badge-success">Producción</span>
                                        @break
                                    @case('development')
                                        <span class="badge badge-info">Desarrollo</span>
                                        @break
                                    @case('testing')
                                        <span class="badge badge-secondary">Testing</span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                @if($apiKey->is_active)
                                    @if($apiKey->isExpired())
                                        <span class="badge badge-warning"><i class="fas fa-clock"></i> Expirada</span>
                                    @else
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Activa</span>
                                    @endif
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-ban"></i> Revocada</span>
                                @endif
                            </td>
                            <td class="text-center"><strong>{{ number_format($apiKey->usage_count) }}</strong></td>
                            <td>
                                @if($apiKey->last_used_at)
                                    {{ $apiKey->last_used_at->diffForHumans() }}
                                @else
                                    <span class="text-muted">Nunca</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No hay API Keys registradas</td>
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
                        <option value="">Todas las API Keys</option>
                        <option value="active">Solo Activas</option>
                        <option value="inactive">Revocadas/Inactivas</option>
                        <option value="expired">Expiradas</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-server"></i> Filtrar por Tipo</label>
                    <select class="form-control" id="filterType">
                        <option value="">Todos los tipos</option>
                        <option value="production">Producción</option>
                        <option value="development">Desarrollo</option>
                        <option value="testing">Testing</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Filtrar por Empresa</label>
                    <select class="form-control" id="filterCompany">
                        <option value="">Todas las empresas</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-sort-amount-down"></i> Ordenar por</label>
                    <select class="form-control" id="filterSort">
                        <option value="usage_count">Mayor uso</option>
                        <option value="last_used_at">Último uso</option>
                        <option value="created_at">Fecha creación</option>
                    </select>
                </div>
                
                <div class="text-muted small mb-3">
                    <i class="fas fa-list"></i> <strong>Incluye:</strong> Nombre, Empresa, Tipo, Estado, Uso, Fechas
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
        
        {{-- Type Distribution --}}
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Distribución por Tipo</h3>
            </div>
            <div class="card-body">
                <canvas id="typeChart" style="min-height: 200px;"></canvas>
            </div>
        </div>
        
        {{-- Quick Stats --}}
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i> Estadísticas de Uso</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-building text-primary mr-2"></i> Empresas con API Keys</span>
                        <span class="badge badge-primary badge-pill">{{ $stats['companies_with_keys'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chart-bar text-info mr-2"></i> Promedio uso/key</span>
                        <span class="badge badge-info badge-pill">
                            {{ $stats['total'] > 0 ? number_format($stats['total_usage'] / $stats['total'], 0) : 0 }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-fire text-danger mr-2"></i> Uso últimas 24h</span>
                        <span class="badge badge-danger badge-pill">{{ number_format($stats['usage_24h']) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-week text-warning mr-2"></i> Uso última semana</span>
                        <span class="badge badge-warning badge-pill">{{ number_format($stats['usage_7d']) }}</span>
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
    const usageData = @json($chartData['usage']);
    const typeData = @json($chartData['types']);
    
    // =========================================================================
    // USAGE LINE CHART
    // =========================================================================
    new Chart(document.getElementById('usageChart'), {
        type: 'line',
        data: {
            labels: usageData.labels,
            datasets: [{
                label: 'Requests',
                data: usageData.values,
                fill: true,
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderColor: 'rgba(0, 123, 255, 1)',
                tension: 0.4,
                pointBackgroundColor: 'rgba(0, 123, 255, 1)',
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // =========================================================================
    // TYPE PIE CHART
    // =========================================================================
    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Producción', 'Desarrollo', 'Testing'],
            datasets: [{
                data: [typeData.production, typeData.development, typeData.testing],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(23, 162, 184, 1)',
                    'rgba(108, 117, 125, 1)'
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
        const type = document.getElementById('filterType').value;
        const company = document.getElementById('filterCompany').value;
        const sort = document.getElementById('filterSort').value;
        
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (type) params.append('type', type);
        if (company) params.append('company_id', company);
        if (sort) params.append('sort', sort);
        
        return params.toString() ? '?' + params.toString() : '';
    }
    
    document.getElementById('btnExcel').onclick = function() {
        window.location.href = `${REPORTS_BASE}/apikeys/excel${buildQuery()}`;
    };
    
    document.getElementById('btnPdf').onclick = function() {
        window.location.href = `${REPORTS_BASE}/apikeys/pdf${buildQuery()}`;
    };
    
})();
</script>
@endpush
