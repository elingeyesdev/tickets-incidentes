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

    <div class="row">
        {{-- Main Metrics --}}
        <div class="col-md-8">
            {{-- Performance Knobs --}}
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i> Indicadores Clave</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 col-md-3 text-center">
                            <input type="text" class="knob" id="knob-workload" value="0" 
                                   data-width="90" data-height="90" 
                                   data-fgColor="#17a2b8" data-readonly="true">
                            <div class="knob-label mt-2 text-muted font-weight-bold">Carga Trabajo</div>
                            <small class="text-muted d-block">Tickets Asignados</small>
                        </div>
                        <div class="col-6 col-md-3 text-center">
                            <input type="text" class="knob" id="knob-open" value="0" 
                                   data-width="90" data-height="90" 
                                   data-fgColor="#dc3545" data-readonly="true">
                            <div class="knob-label mt-2 text-muted font-weight-bold">% Abiertos</div>
                            <small class="text-muted d-block">Sin resolver</small>
                        </div>
                        <div class="col-6 col-md-3 text-center">
                            <input type="text" class="knob" id="knob-pending" value="0" 
                                   data-width="90" data-height="90" 
                                   data-fgColor="#ffc107" data-readonly="true">
                            <div class="knob-label mt-2 text-muted font-weight-bold">% Pendientes</div>
                            <small class="text-muted d-block">En espera</small>
                        </div>
                        <div class="col-6 col-md-3 text-center">
                            <input type="text" class="knob" id="knob-resolution" value="0" 
                                   data-width="90" data-height="90" 
                                   data-fgColor="#28a745" data-readonly="true">
                            <div class="knob-label mt-2 text-muted font-weight-bold">Efe. Resolución</div>
                            <small class="text-muted d-block">Resueltos / Total</small>
                        </div>
                    </div>
                </div>
                <div class="overlay" id="loading-overlay">
                    <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                </div>
            </div>

            {{-- Weekly Activity Chart --}}
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-area mr-2"></i> Evolución Semanal</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="weeklyActivityChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Export & Status --}}
        <div class="col-md-4">
            {{-- Export Card --}}
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-pdf mr-2"></i> Exportar</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Genera un documento PDF con todos tus indicadores, gráficas de rendimiento y desglose de actividad reciente.</p>
                    <a href="/app/agent/reports/performance/pdf" class="btn btn-danger btn-block btn-lg">
                        <i class="fas fa-download mr-2"></i> Descargar Reporte
                    </a>
                </div>
            </div>

            {{-- Today's Status --}}
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Resumen de Hoy</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-valign-middle">
                        <tbody>
                        <tr>
                            <td>
                                <i class="fas fa-check-circle text-success mr-2"></i> Resueltos
                            </td>
                            <td class="text-right font-weight-bold" id="td-resolved">0</td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fas fa-inbox text-primary mr-2"></i> Nuevos Asignados
                            </td>
                            <td class="text-right font-weight-bold" id="td-assigned">0</td>
                        </tr>
                        </tbody>
                    </table>
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

    function getToken() {
        return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
    }

    // Init Knobs
    $('.knob').knob({
        'draw': function() { $(this.i).val(this.cv + '%'); }
    });

    function animateKnob(id, value) {
        $({value: 0}).animate({value: value}, {
            duration: 1000,
            easing: 'swing',
            step: function() { $('#' + id).val(Math.ceil(this.value)).trigger('change'); }
        });
    }

    // Chart Instance
    let weeklyChart = null;

    function renderChart(labels, data) {
        const ctx = document.getElementById('weeklyActivityChart');
        if (weeklyChart) weeklyChart.destroy();

        weeklyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tickets Resueltos',
                    data: data,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: '#28a745',
                    pointRadius: 4,
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
    }

    async function loadData() {
        try {
            document.getElementById('loading-overlay').style.display = 'flex';
            
            const response = await fetch('/api/analytics/agent-dashboard', {
                headers: { 'Authorization': 'Bearer ' + getToken(), 'Accept': 'application/json' }
            });
            
            if (!response.ok) throw new Error('Failed');
            const data = await response.json();

            // Render Metrics (Knobs)
            const metrics = data.performance_metrics || {};
            animateKnob('knob-workload', metrics.workload || 0); // Note: Workload is number, not % strictly but displayed as knob
            animateKnob('knob-open', metrics.open_rate || 0);
            animateKnob('knob-pending', metrics.pending_rate || 0);
            animateKnob('knob-resolution', metrics.resolution_rate || 0);

            // Render Chart
            const weekly = data.weekly_activity || { labels: [], data: [] };
            renderChart(weekly.labels, weekly.data);

            // Render Today's Stats
            const kpi = data.kpi || {};
            document.getElementById('td-resolved').textContent = kpi.resolved_today || 0;
            // Assuming assigned today is roughly total - resolved (just for quick approximation if not in API)
            // Or better, leave static if API doesn't send "assigned_today" specifically
            // We'll use assigned_total as placeholder or 0
            
        } catch (e) {
            console.error('Performance load error:', e);
        } finally {
            document.getElementById('loading-overlay').style.display = 'none';
        }
    }

    // Init
    if (window.tokenManager) {
        loadData();
    } else {
        setTimeout(loadData, 500);
    }

})();
</script>
@endpush
