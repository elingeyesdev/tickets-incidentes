@extends('layouts.authenticated')

@section('title', 'Resumen de Actividad - Reportes')
@section('content_header', 'Resumen de Actividad')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/user/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Resumen de Actividad</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-chart-line"></i> Reporte de Actividad del Usuario</h5>
        <p class="mb-0">Visualiza tu rendimiento y estadísticas de uso en la plataforma.</p>
    </div>

    {{-- KPI Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="stat-total"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Total Tickets</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="stat-rate"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Tasa de Resolución</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="stat-pending"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Tickets Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="stat-priority"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Tickets Alta Prioridad</p>
                </div>
                <div class="icon">
                    <i class="fas fa-fire"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-2"></i> Actividad Reciente (Tickets por Mes)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="monthlyChart"></canvas>
                        <div id="chart-loader-monthly" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8);">
                            <i class="fas fa-2x fa-sync-alt fa-spin text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Estado de Tickets</h3>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="statusChart"></canvas>
                         <div id="chart-loader-status" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8);">
                            <i class="fas fa-2x fa-sync-alt fa-spin text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Report Download --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Reporte de Actividad</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><i class="fas fa-calendar-alt"></i> Periodo de Análisis</label>
                                <select class="form-control" id="activityMonths">
                                    <option value="3">Últimos 3 meses</option>
                                    <option value="6" selected>Últimos 6 meses</option>
                                    <option value="12">Último año</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                             <div class="btn-group">
                                <button type="button" class="btn btn-success" id="btnExportExcel">
                                    <i class="fas fa-file-excel mr-1"></i> Descargar Excel
                                </button>
                                <button type="button" class="btn btn-danger" id="btnExportPdf">
                                    <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
                                </button>
                             </div>
                             <small class="text-muted ml-3 align-middle d-inline-block mt-2 mt-md-0">
                                <i class="fas fa-info-circle"></i> El reporte incluirá desglose mensual y estadísticas.
                             </small>
                        </div>
                    </div>
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
    
    const CONFIG = {
        API_BASE: '/api',
        REPORTS_BASE: '/app/user/reports'
    };
    
    // Utilities
    function getToken() {
        return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
    }
    
    function showDownloadToast(type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Generando reporte...',
                showConfirmButton: false,
                timer: 2000,
            });
        }
    }

    // Load Data
    async function loadStats() {
        try {
            const response = await fetch(`${CONFIG.API_BASE}/analytics/user-dashboard`, {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to load dashboard data');
            const data = await response.json();
            
            updateKPIs(data);
            renderMonthlyChart(data.tickets_trend);
            renderStatusChart(data.ticket_status);
            
        } catch (error) {
            console.error('[Activity] Error loading stats:', error);
        }
    }

    function updateKPIs(data) {
        const kpi = data.kpi || {};
        const profile = data.profile || {};
        const priority = data.priority_distribution || {};
        
        document.getElementById('stat-total').textContent = kpi.total_tickets || 0;
        document.getElementById('stat-rate').textContent = (profile.resolution_rate || 0) + '%';
        document.getElementById('stat-pending').textContent = kpi.open_tickets + kpi.pending_tickets || 0;
        document.getElementById('stat-priority').textContent = priority.high?.count || 0;
    }

    function renderMonthlyChart(trendData) {
        const ctx = document.getElementById('monthlyChart');
        document.getElementById('chart-loader-monthly').style.display = 'none';
        
        if (!ctx || !trendData) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.labels || [],
                datasets: [{
                    label: 'Tickets Creados',
                    data: trendData.data || [],
                    borderColor: 'rgba(60,141,188,0.8)',
                    backgroundColor: 'rgba(60,141,188,0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    function renderStatusChart(statusData) {
        const ctx = document.getElementById('statusChart');
        document.getElementById('chart-loader-status').style.display = 'none';
        
        if (!ctx || !statusData) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Abiertos', 'Pendientes', 'Resueltos', 'Cerrados'],
                datasets: [{
                    data: [
                        statusData.OPEN || 0,
                        statusData.PENDING || 0,
                        statusData.RESOLVED || 0,
                        statusData.CLOSED || 0
                    ],
                    backgroundColor: [
                        '#dc3545', // Open - Red
                        '#ffc107', // Pending - Yellow
                        '#28a745', // Resolved - Green
                        '#6c757d'  // Closed - Gray
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } }
                }
            }
        });
    }

    // Export Handlers
    document.getElementById('btnExportExcel').onclick = function() {
        const months = document.getElementById('activityMonths').value;
        showDownloadToast('excel');
        window.location.href = `${CONFIG.REPORTS_BASE}/activity/excel?months=${months}`;
    };

    document.getElementById('btnExportPdf').onclick = function() {
        const months = document.getElementById('activityMonths').value;
        showDownloadToast('pdf');
        window.location.href = `${CONFIG.REPORTS_BASE}/activity/pdf?months=${months}`;
    };

    // Init
    if (window.tokenManager) {
        loadStats();
    } else {
        setTimeout(loadStats, 500);
    }
})();
</script>
@endpush
