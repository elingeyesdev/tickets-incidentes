@extends('layouts.authenticated')

@section('title', 'Reporte de Crecimiento - Platform Admin')
@section('content_header', 'Crecimiento de Plataforma')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Crecimiento</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-chart-line"></i> Estadísticas de Crecimiento de la Plataforma</h5>
        <p class="mb-0">Analiza las tendencias de crecimiento: nuevas empresas, usuarios registrados y tickets creados por
            periodo.</p>
    </div>

    {{-- Summary Cards Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-info">
                <span class="info-box-icon"><i class="fas fa-building"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Empresas</span>
                    <span class="info-box-number">{{ number_format($summary['total_companies']) }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">
                        +{{ $summary['new_companies_period'] }} en el periodo
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-success">
                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Usuarios</span>
                    <span class="info-box-number">{{ number_format($summary['total_users']) }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">
                        +{{ $summary['new_users_period'] }} en el periodo
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-warning">
                <span class="info-box-icon"><i class="fas fa-ticket-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Tickets</span>
                    <span class="info-box-number">{{ number_format($summary['total_tickets']) }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">
                        +{{ $summary['new_tickets_period'] }} en el periodo
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box bg-gradient-purple">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Solicitudes Pendientes</span>
                    <span class="info-box-number">{{ $summary['pending_requests'] }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">
                        {{ $summary['active_companies'] }} empresas activas
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Main Growth Chart --}}
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-area mr-2"></i> Tendencias de Crecimiento</h3>
                    <div class="card-tools">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary period-btn active" data-months="6">6
                                meses</button>
                            <button type="button" class="btn btn-outline-primary period-btn" data-months="12">12
                                meses</button>
                            <button type="button" class="btn btn-outline-primary period-btn" data-months="3">3
                                meses</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="growthChart" style="min-height: 350px;"></canvas>
                </div>
            </div>

            {{-- Monthly Comparison Table --}}
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-table mr-2"></i> Detalle Mensual</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Mes</th>
                                <th class="text-center"><i class="fas fa-building"></i> Empresas</th>
                                <th class="text-center"><i class="fas fa-users"></i> Usuarios</th>
                                <th class="text-center"><i class="fas fa-ticket-alt"></i> Tickets</th>
                                <th class="text-center">Tendencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthlyData as $index => $month)
                                <tr>
                                    <td><strong>{{ $month['month_name'] }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $month['companies'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ $month['users'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-warning">{{ $month['tickets'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $total = $month['companies'] + $month['users'] + $month['tickets'];
                                            $prevTotal = isset($monthlyData[$index - 1])
                                                ? $monthlyData[$index - 1]['companies'] + $monthlyData[$index - 1]['users'] + $monthlyData[$index - 1]['tickets']
                                                : $total;
                                            $diff = $total - $prevTotal;
                                        @endphp
                                        @if($diff > 0)
                                            <span class="text-success"><i class="fas fa-arrow-up"></i></span>
                                        @elseif($diff < 0)
                                            <span class="text-danger"><i class="fas fa-arrow-down"></i></span>
                                        @else
                                            <span class="text-muted"><i class="fas fa-minus"></i></span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
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
                        <label><i class="fas fa-calendar-alt"></i> Periodo de Análisis</label>
                        <select class="form-control" id="filterMonths">
                            <option value="3">Últimos 3 meses</option>
                            <option value="6" selected>Últimos 6 meses</option>
                            <option value="12">Último año (12 meses)</option>
                            <option value="24">Últimos 2 años</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-chart-bar"></i> Tipo de Datos</label>
                        <select class="form-control" id="filterDataType">
                            <option value="all">Todos (Empresas, Usuarios, Tickets)</option>
                            <option value="companies">Solo Empresas</option>
                            <option value="users">Solo Usuarios</option>
                            <option value="tickets">Solo Tickets</option>
                        </select>
                    </div>

                    <div class="text-muted small mb-3">
                        <i class="fas fa-chart-line"></i> <strong>Incluye:</strong> Datos mensuales, tendencias y resumen
                        general
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

            {{-- Entities Distribution --}}
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Distribución por Tipo</h3>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" style="min-height: 200px;"></canvas>
                </div>
            </div>

            {{-- Growth Rate --}}
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-percentage mr-2"></i> Tasa de Crecimiento</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-building text-info mr-2"></i> Empresas</span>
                            <span class="badge badge-info badge-pill">
                                @php
                                    $rate = $summary['total_companies'] > 0
                                        ? round(($summary['new_companies_period'] / $summary['total_companies']) * 100, 1)
                                        : 0;
                                @endphp
                                +{{ $rate }}%
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-users text-success mr-2"></i> Usuarios</span>
                            <span class="badge badge-success badge-pill">
                                @php
                                    $rate = $summary['total_users'] > 0
                                        ? round(($summary['new_users_period'] / $summary['total_users']) * 100, 1)
                                        : 0;
                                @endphp
                                +{{ $rate }}%
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-ticket-alt text-warning mr-2"></i> Tickets</span>
                            <span class="badge badge-warning badge-pill">
                                @php
                                    $rate = $summary['total_tickets'] > 0
                                        ? round(($summary['new_tickets_period'] / $summary['total_tickets']) * 100, 1)
                                        : 0;
                                @endphp
                                +{{ $rate }}%
                            </span>
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
        (function () {
            'use strict';

            const REPORTS_BASE = '/app/admin/reports';

            // =========================================================================
            // CHARTS DATA FROM CONTROLLER
            // =========================================================================
            const monthlyChartData = @json($chartData);

            // =========================================================================
            // MAIN GROWTH CHART
            // =========================================================================
            const growthChart = new Chart(document.getElementById('growthChart'), {
                type: 'line',
                data: {
                    labels: monthlyChartData.labels,
                    datasets: [
                        {
                            label: 'Empresas',
                            data: monthlyChartData.companies,
                            borderColor: 'rgba(23, 162, 184, 1)',
                            backgroundColor: 'rgba(23, 162, 184, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Usuarios',
                            data: monthlyChartData.users,
                            borderColor: 'rgba(40, 167, 69, 1)',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Tickets',
                            data: monthlyChartData.tickets,
                            borderColor: 'rgba(255, 193, 7, 1)',
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });

            // =========================================================================
            // DISTRIBUTION PIE CHART
            // =========================================================================
            new Chart(document.getElementById('distributionChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Empresas', 'Usuarios', 'Tickets'],
                    datasets: [{
                        data: [
                        {{ $summary['new_companies_period'] }},
                        {{ $summary['new_users_period'] }},
                            {{ $summary['new_tickets_period'] }}
                        ],
                        backgroundColor: [
                            'rgba(23, 162, 184, 0.8)',
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)'
                        ],
                        borderColor: [
                            'rgba(23, 162, 184, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)'
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
            // PERIOD BUTTONS
            // =========================================================================
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.onclick = function () {
                    const months = this.dataset.months;
                    window.location.href = `${REPORTS_BASE}/growth?months=${months}`;
                };
            });

            // =========================================================================
            // DOWNLOAD HANDLERS
            // =========================================================================
            document.getElementById('btnExcel').onclick = function () {
                const months = document.getElementById('filterMonths').value;
                const dataType = document.getElementById('filterDataType').value;
                window.location.href = `${REPORTS_BASE}/growth/excel?months=${months}&type=${dataType}`;
            };

            document.getElementById('btnPdf').onclick = function () {
                const months = document.getElementById('filterMonths').value;
                const dataType = document.getElementById('filterDataType').value;
                window.location.href = `${REPORTS_BASE}/growth/pdf?months=${months}&type=${dataType}`;
            };

        })();
    </script>
@endpush