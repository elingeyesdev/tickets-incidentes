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
    <h5><i class="fas fa-chart-line"></i> Reporte de Actividad</h5>
    <p class="mb-0">Genera un resumen de tu actividad en el sistema durante los últimos meses. Incluye estadísticas de tickets, tendencias y distribución por prioridad.</p>
</div>

<div class="row">
    {{-- Report Config Card --}}
    <div class="col-lg-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-clock mr-2"></i> Exportar Mi Actividad</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle"></i> 
                    Este reporte resume tu actividad en la plataforma con estadísticas detalladas.
                </p>
                
                {{-- Período --}}
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Período de Análisis</label>
                    <select class="form-control" id="activityPeriod">
                        <option value="6">Últimos 6 meses</option>
                        <option value="3">Últimos 3 meses</option>
                        <option value="12">Último año (12 meses)</option>
                    </select>
                </div>
                
                <div class="text-muted small mb-3">
                    <i class="fas fa-chart-bar"></i> <strong>Incluye:</strong> Tickets por mes, Distribución de prioridades, Tasa de resolución
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-success btn-lg" id="btnActivityExcel">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="btnActivityPdf">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- My Stats Preview --}}
    <div class="col-lg-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-check mr-2"></i> Mi Rendimiento</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 border-right">
                        <h2 class="mb-0 text-primary" id="myTotal">
                            <i class="fas fa-spinner fa-spin"></i>
                        </h2>
                        <small class="text-muted">Total Tickets</small>
                    </div>
                    <div class="col-6">
                        <h2 class="mb-0 text-success" id="myRate">
                            <i class="fas fa-spinner fa-spin"></i>
                        </h2>
                        <small class="text-muted">Tasa Resolución</small>
                    </div>
                </div>
                
                <hr>
                
                {{-- Priority Distribution --}}
                <h6 class="text-muted mb-3"><i class="fas fa-fire"></i> Por Prioridad</h6>
                
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-danger mr-1"></i> Alta</span>
                    <span class="float-right" id="priorityHighCount"><b>0</b></span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-danger" id="priorityHighBar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-warning mr-1"></i> Media</span>
                    <span class="float-right" id="priorityMediumCount"><b>0</b></span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-warning" id="priorityMediumBar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="progress-group">
                    <span class="progress-text"><i class="fas fa-circle text-success mr-1"></i> Baja</span>
                    <span class="float-right" id="priorityLowCount"><b>0</b></span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" id="priorityLowBar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    
    const CONFIG = {
        REPORTS_BASE: '/app/user/reports'
    };
    
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
    
    // =========================================================================
    // DOWNLOAD HANDLERS
    // =========================================================================
    
    document.getElementById('btnActivityExcel').onclick = function() {
        const months = document.getElementById('activityPeriod').value || '6';
        const url = `${CONFIG.REPORTS_BASE}/activity/excel?months=${months}`;
        showDownloadToast('excel');
        window.location.href = url;
    };
    
    document.getElementById('btnActivityPdf').onclick = function() {
        const months = document.getElementById('activityPeriod').value || '6';
        const url = `${CONFIG.REPORTS_BASE}/activity/pdf?months=${months}`;
        showDownloadToast('pdf');
        window.location.href = url;
    };
    
    // =========================================================================
    // LOAD STATS
    // =========================================================================
    async function loadStats() {
        try {
            const response = await fetch('/api/analytics/user-dashboard', {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to load stats');
            
            const data = await response.json();
            
            // Total and rate
            const kpi = data.kpi || {};
            const profile = data.profile || {};
            
            document.getElementById('myTotal').textContent = kpi.total_tickets || 0;
            document.getElementById('myRate').textContent = (profile.resolution_rate || 0) + '%';
            
            // Priority distribution
            const priority = data.priority_distribution || {};
            
            document.getElementById('priorityHighCount').innerHTML = '<b>' + (priority.high?.count || 0) + '</b>';
            document.getElementById('priorityMediumCount').innerHTML = '<b>' + (priority.medium?.count || 0) + '</b>';
            document.getElementById('priorityLowCount').innerHTML = '<b>' + (priority.low?.count || 0) + '</b>';
            
            document.getElementById('priorityHighBar').style.width = (priority.high?.percentage || 0) + '%';
            document.getElementById('priorityMediumBar').style.width = (priority.medium?.percentage || 0) + '%';
            document.getElementById('priorityLowBar').style.width = (priority.low?.percentage || 0) + '%';
            
        } catch (error) {
            console.error('[Reports] Stats load error:', error);
            document.getElementById('myTotal').textContent = 'N/A';
            document.getElementById('myRate').textContent = 'N/A';
        }
    }
    
    // Initialize
    setTimeout(loadStats, 500);
})();
</script>
@endpush
