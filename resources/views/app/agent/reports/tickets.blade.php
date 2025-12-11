@extends('layouts.authenticated')

@section('title', 'Mis Tickets - Reporte')
@section('content_header', 'Reporte de Mis Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/agent/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Mis Tickets</li>
@endsection

@section('content')

<div class="callout callout-info">
    <h5><i class="fas fa-ticket-alt"></i> Reporte de Mis Tickets Asignados</h5>
    <p class="mb-0">Descarga un listado de todos los tickets que tienes asignados.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Tickets</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><i class="fas fa-filter"></i> Filtrar por Estado</label>
                    <select class="form-control" id="filterStatus">
                        <option value="">Todos los estados</option>
                        <option value="OPEN">Abiertos</option>
                        <option value="PENDING">Pendientes</option>
                        <option value="RESOLVED">Resueltos</option>
                        <option value="CLOSED">Cerrados</option>
                    </select>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-success btn-lg" id="btnExcel">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="btnPdf">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Resumen</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-inbox text-primary mr-2"></i> Total Asignados</span>
                        <span class="badge badge-primary badge-pill" id="statTotal"><i class="fas fa-spinner fa-spin"></i></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-exclamation-circle text-danger mr-2"></i> Abiertos</span>
                        <span class="badge badge-danger badge-pill" id="statOpen">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-clock text-warning mr-2"></i> Pendientes</span>
                        <span class="badge badge-warning badge-pill" id="statPending">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-check-double text-success mr-2"></i> Resueltos</span>
                        <span class="badge badge-success badge-pill" id="statResolved">-</span>
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
    
    function buildQuery() {
        const status = document.getElementById('filterStatus').value;
        return status ? '?status=' + status : '';
    }
    
    document.getElementById('btnExcel').onclick = function() {
        window.location.href = '/app/agent/reports/tickets/excel' + buildQuery();
    };
    
    document.getElementById('btnPdf').onclick = function() {
        window.location.href = '/app/agent/reports/tickets/pdf' + buildQuery();
    };
    
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
            document.getElementById('statTotal').textContent = (ts.open || 0) + (ts.pending || 0) + (ts.resolved || 0) + (ts.closed || 0);
            document.getElementById('statOpen').textContent = ts.open || 0;
            document.getElementById('statPending').textContent = ts.pending || 0;
            document.getElementById('statResolved').textContent = (ts.resolved || 0) + (ts.closed || 0);
        } catch (e) {
            console.error('Stats error:', e);
        }
    }
    
    setTimeout(loadStats, 500);
})();
</script>
@endpush
