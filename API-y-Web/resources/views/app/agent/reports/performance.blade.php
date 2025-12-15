@extends('layouts.authenticated')

@section('title', 'Mi Rendimiento - Reporte')
@section('content_header', 'Panel de Rendimiento')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/agent/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Mi Rendimiento</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-success">
        <h5><i class="fas fa-chart-line"></i> Métricas de Desempeño</h5>
        <p class="mb-0">Analiza tus indicadores clave de rendimiento, identifica áreas de mejora y descarga tu reporte detallado.</p>
    </div>

    {{-- KPI Cards (Server-side data) --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-info">
                <span class="info-box-icon"><i class="fas fa-inbox"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Asignados</span>
                    <span class="info-box-number">{{ number_format($kpis['total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-danger">
                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Abiertos</span>
                    <span class="info-box-number">{{ number_format($kpis['open']) }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $metrics['open_rate'] }}%"></div>
                    </div>
                    <span class="progress-description">{{ $metrics['open_rate'] }}% del total</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-warning">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pendientes</span>
                    <span class="info-box-number">{{ number_format($kpis['pending']) }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $metrics['pending_rate'] }}%"></div>
                    </div>
                    <span class="progress-description">{{ $metrics['pending_rate'] }}% del total</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-success">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Resueltos</span>
                    <span class="info-box-number">{{ number_format($kpis['resolved']) }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $metrics['resolution_rate'] }}%"></div>
                    </div>
                    <span class="progress-description">{{ $metrics['resolution_rate'] }}% efectividad</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Weekly Activity Chart --}}
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-area mr-2"></i> Actividad Semanal (Tickets Resueltos)</h3>
                </div>
                <div class="card-body">
                    <canvas id="weeklyChart" style="min-height: 300px;"></canvas>
                </div>
            </div>

            {{-- Priority Distribution --}}
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-fire mr-2"></i> Tickets Activos por Prioridad</h3>
                </div>
                <div class="card-body">
                    @php
                        $totalPriority = max($priority['high'] + $priority['medium'] + $priority['low'], 1);
                    @endphp
                    {{-- High --}}
                    <div class="progress-group">
                        <span class="progress-text"><i class="fas fa-circle text-danger mr-1"></i> Alta Prioridad</span>
                        <span class="float-right"><b>{{ $priority['high'] }}</b> ({{ round(($priority['high'] / $totalPriority) * 100) }}%)</span>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-danger" style="width: {{ ($priority['high'] / $totalPriority) * 100 }}%"></div>
                        </div>
                    </div>
                    {{-- Medium --}}
                    <div class="progress-group">
                        <span class="progress-text"><i class="fas fa-circle text-warning mr-1"></i> Media Prioridad</span>
                        <span class="float-right"><b>{{ $priority['medium'] }}</b> ({{ round(($priority['medium'] / $totalPriority) * 100) }}%)</span>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-warning" style="width: {{ ($priority['medium'] / $totalPriority) * 100 }}%"></div>
                        </div>
                    </div>
                    {{-- Low --}}
                    <div class="progress-group">
                        <span class="progress-text"><i class="fas fa-circle text-success mr-1"></i> Baja Prioridad</span>
                        <span class="float-right"><b>{{ $priority['low'] }}</b> ({{ round(($priority['low'] / $totalPriority) * 100) }}%)</span>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-success" style="width: {{ ($priority['low'] / $totalPriority) * 100 }}%"></div>
                        </div>
                    </div>
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
                    <p class="text-muted small">Genera un documento PDF con todos tus indicadores, gráficas de rendimiento y desglose de actividad.</p>
                </div>
                <div class="card-footer bg-light">
                    <a href="/app/agent/reports/performance/pdf" class="btn btn-danger btn-block btn-lg">
                        <i class="fas fa-file-pdf mr-2"></i> Descargar PDF
                    </a>
                </div>
            </div>

            {{-- Today's Stats --}}
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-day mr-2"></i> Resumen de Hoy</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check-circle text-success mr-2"></i> Resueltos Hoy</span>
                            <span class="badge badge-success badge-pill">{{ $kpis['resolved_today'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-percentage text-info mr-2"></i> Tasa de Resolución</span>
                            <span class="badge badge-info badge-pill">{{ $metrics['resolution_rate'] }}%</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Status Distribution Chart --}}
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Distribución por Estado</h3>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" style="min-height: 200px;"></canvas>
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

    // Chart data from controller (server-side)
    const chartData = @json($chartData);
    const statusData = @json($statusStats);

    // Weekly Activity Chart
    new Chart(document.getElementById('weeklyChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Tickets Resueltos',
                data: chartData.data,
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: '#28a745',
                pointRadius: 5,
                pointBackgroundColor: '#28a745',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Status Distribution Chart (Consistent colors with KPIs)
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

})();
</script>
@endpush
