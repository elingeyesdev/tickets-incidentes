@extends('layouts.authenticated')

@section('title', 'Mi Rendimiento - Reporte')
@section('content_header', 'Reporte de Mi Rendimiento')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/agent/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Mi Rendimiento</li>
@endsection

@section('content')

<div class="callout callout-info">
    <h5><i class="fas fa-chart-line"></i> Reporte de Rendimiento Personal</h5>
    <p class="mb-0">Descarga un resumen de tu desempeño como agente de soporte.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-pdf mr-2"></i> Generar Reporte</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-light border">
                    <h6 class="mb-2"><i class="fas fa-list-alt text-primary"></i> <strong>Contenido del Reporte:</strong></h6>
                    <ul class="mb-0 pl-3">
                        <li>Total de tickets asignados</li>
                        <li>Tickets por estado (Abiertos, Pendientes, Resueltos, Cerrados)</li>
                        <li>Tickets resueltos hoy</li>
                        <li>Tasa de resolución</li>
                        <li>Distribución por prioridad</li>
                    </ul>
                </div>
            </div>
            <div class="card-footer bg-light text-center">
                <a href="/app/agent/reports/performance/pdf" class="btn btn-danger btn-lg btn-block">
                    <i class="fas fa-file-pdf mr-2"></i> Descargar Reporte (PDF)
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i> Vista Previa</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-ticket-alt text-primary mr-2"></i> Total</span>
                        <span class="badge badge-primary badge-pill" id="statTotal"><i class="fas fa-spinner fa-spin"></i></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-check text-success mr-2"></i> Resueltos Hoy</span>
                        <span class="badge badge-success badge-pill" id="statToday">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-percentage text-info mr-2"></i> Tasa Resolución</span>
                        <span class="badge badge-info badge-pill" id="statRate">-</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    
    function getToken() {
        return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
    }
    
    async function loadStats() {
        try {
            const response = await fetch('/api/analytics/agent-dashboard', {
                headers: { 'Authorization': 'Bearer ' + getToken(), 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Failed');
            const data = await response.json();
            
            const ts = data.ticket_status || {};
            const total = (ts.open || 0) + (ts.pending || 0) + (ts.resolved || 0) + (ts.closed || 0);
            const resolved = (ts.resolved || 0) + (ts.closed || 0);
            
            document.getElementById('statTotal').textContent = total;
            document.getElementById('statToday').textContent = data.agent_performance?.resolved_today || 0;
            document.getElementById('statRate').textContent = total > 0 ? Math.round((resolved / total) * 100) + '%' : '0%';
        } catch (e) {
            console.error('Stats error:', e);
        }
    }
    
    setTimeout(loadStats, 500);
})();
</script>
@endpush
