@extends('layouts.authenticated')

@section('title', 'Reporte de Agentes - Company Admin')
@section('content_header', 'Reporte de Agentes')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Agentes</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-user-tie"></i> Reporte de Rendimiento de Agentes</h5>
        <p class="mb-0">Analiza el rendimiento, carga de trabajo y tasas de resolución de tus agentes de soporte.</p>
    </div>

    {{-- KPI Small Boxes Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $kpis['total'] }}</h3>
                    <p>Total Agentes</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $kpis['avgTickets'] }}</h3>
                    <p>Promedio Tickets/Agente</p>
                </div>
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $kpis['avgRate'] }}%</h3>
                    <p>Tasa de Resolución Prom.</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 class="text-truncate" style="font-size: 1.5rem;">{{ Str::limit($kpis['bestAgent'], 15) }}</h3>
                    <p>Mejor Rendimiento</p>
                </div>
                <div class="icon"><i class="fas fa-trophy"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Performance Chart --}}
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Rendimiento por Agente</h3>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        {{-- Load Distribution --}}
        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Distribución de Carga</h3>
                </div>
                <div class="card-body">
                    <canvas id="loadChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Agents Table --}}
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-table mr-2"></i> Detalle de Agentes</h3>
            <div class="card-tools">
                <a href="/app/company/reports/agents/excel" class="btn btn-success btn-sm mr-1">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="/app/company/reports/agents/pdf" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Agente</th>
                        <th class="text-center">Asignados</th>
                        <th class="text-center">Activos</th>
                        <th class="text-center">Resueltos</th>
                        <th class="text-center">Tasa Resolución</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                        <tr>
                            <td>
                                <strong>{{ $agent['name'] }}</strong>
                                <br><small class="text-muted">{{ $agent['email'] }}</small>
                            </td>
                            <td class="text-center"><span class="badge badge-primary">{{ $agent['assigned'] }}</span></td>
                            <td class="text-center"><span
                                    class="badge badge-warning">{{ $agent['active'] > 0 ? $agent['active'] : 0 }}</span></td>
                            <td class="text-center"><span class="badge badge-success">{{ $agent['resolved'] }}</span></td>
                            <td class="text-center">
                                @php $rate = $agent['rate']; @endphp
                                <span
                                    class="badge badge-{{ $rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'danger') }}">{{ $rate }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No hay agentes registrados</td>
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

            const agents = @json($agents->values());

            if (agents.length === 0) return;

            const labels = agents.map(a => a.name.split(' ')[0]);

            // Performance Chart
            const ctxPerf = document.getElementById('performanceChart');
            if (ctxPerf) {
                new Chart(ctxPerf, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Asignados',
                                data: agents.map(a => a.assigned),
                                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                                borderColor: 'rgba(0, 123, 255, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Resueltos',
                                data: agents.map(a => a.resolved),
                                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // Load Distribution Chart
            const ctxLoad = document.getElementById('loadChart');
            if (ctxLoad) {
                const colors = [
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ];

                new Chart(ctxLoad, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: agents.map(a => a.assigned),
                            backgroundColor: colors.slice(0, agents.length),
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
                    }
                });
            }
        })();
    </script>
@endpush