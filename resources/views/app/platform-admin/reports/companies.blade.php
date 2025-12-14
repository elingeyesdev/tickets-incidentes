@extends('layouts.authenticated')

@section('title', 'Reporte de Empresas - Platform Admin')
@section('content_header', 'Reporte de Empresas')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Empresas</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-building"></i> Reporte de Empresas Registradas</h5>
        <p class="mb-0">Listado completo de todas las empresas registradas en la plataforma con estadísticas de agentes,
            tickets y artículos.</p>
    </div>

    {{-- Summary Cards Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total Empresas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
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
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['suspended'] }}</h3>
                    <p>Suspendidas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-pause-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['this_month'] }}</h3>
                    <p>Nuevas este mes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Chart Section --}}
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Distribución por Industria</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="industriesChart" style="min-height: 300px;"></canvas>
                </div>
            </div>

            {{-- Companies by Status Chart --}}
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i> Empresas Creadas por Mes</h3>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" style="min-height: 250px;"></canvas>
                </div>
            </div>
        </div>

        {{-- Export Section --}}
        <div class="col-lg-4">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Reporte</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><i class="fas fa-filter"></i> Filtrar por Estado</label>
                        <select class="form-control" id="filterStatus">
                            <option value="">Todas las empresas</option>
                            <option value="active">Solo Activas</option>
                            <option value="suspended">Solo Suspendidas</option>
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
                        <i class="fas fa-table"></i> <strong>Incluye:</strong> Código, Nombre, Email, Industria, Estado,
                        Agentes, Tickets
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
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Estado de Empresas</h3>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" style="min-height: 200px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Companies Table --}}
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-trophy mr-2"></i> Top 10 Empresas por Tickets</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empresa</th>
                        <th>Industria</th>
                        <th>Estado</th>
                        <th class="text-center">Agentes</th>
                        <th class="text-center">Tickets</th>
                        <th>Registrada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCompanies as $index => $company)
                        <tr>
                            <td><span class="badge badge-primary">{{ $index + 1 }}</span></td>
                            <td>
                                <strong>{{ $company->name }}</strong>
                                <br><small class="text-muted">{{ $company->company_code }}</small>
                            </td>
                            <td>{{ $company->industry->name ?? '-' }}</td>
                            <td>
                                @if($company->status === 'active')
                                    <span class="badge badge-success">Activa</span>
                                @else
                                    <span class="badge badge-warning">Suspendida</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $company->agents_count }}</td>
                            <td class="text-center"><strong>{{ $company->tickets_count }}</strong></td>
                            <td>{{ $company->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No hay empresas registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
            const industriesData = @json($chartData['industries']);
            const monthlyData = @json($chartData['monthly']);
            const statusData = @json($chartData['status']);

            // =========================================================================
            // INDUSTRIES BAR CHART
            // =========================================================================
            new Chart(document.getElementById('industriesChart'), {
                type: 'bar',
                data: {
                    labels: industriesData.labels,
                    datasets: [{
                        label: 'Empresas',
                        data: industriesData.values,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(199, 199, 199, 1)'
                        ],
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
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });

            // =========================================================================
            // MONTHLY LINE CHART
            // =========================================================================
            new Chart(document.getElementById('monthlyChart'), {
                type: 'line',
                data: {
                    labels: monthlyData.labels,
                    datasets: [{
                        label: 'Empresas Creadas',
                        data: monthlyData.values,
                        fill: true,
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)'
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
                    labels: ['Activas', 'Suspendidas'],
                    datasets: [{
                        data: [statusData.active, statusData.suspended],
                        backgroundColor: ['rgba(40, 167, 69, 0.8)', 'rgba(255, 193, 7, 0.8)'],
                        borderColor: ['rgba(40, 167, 69, 1)', 'rgba(255, 193, 7, 1)'],
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

            document.getElementById('btnExcel').onclick = function () {
                window.location.href = `${REPORTS_BASE}/companies/excel${buildQuery()}`;
            };

            document.getElementById('btnPdf').onclick = function () {
                window.location.href = `${REPORTS_BASE}/companies/pdf${buildQuery()}`;
            };

        })();
    </script>
@endpush