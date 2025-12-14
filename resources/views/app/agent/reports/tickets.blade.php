@extends('layouts.authenticated')

@section('title', 'Mis Tickets - Reporte')
@section('content_header', 'Reporte de Tickets Asignados')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/agent/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Mis Tickets</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-ticket-alt"></i> Reporte de Carga de Trabajo</h5>
        <p class="mb-0">Visualiza y exporta el estado de todos los tickets que tienes asignados actualmente.</p>
    </div>

    {{-- KPI Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="statTotal"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Total Asignados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-inbox"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="statOpen"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Abiertos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="statPending"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="statResolved"><i class="fas fa-spinner fa-spin"></i></h3>
                    <p>Resueltos/Cerrados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Card --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-export mr-2"></i> Exportar Reporte</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><i class="fas fa-filter"></i> Filtrar por Estado</label>
                                <select class="form-control" id="filterStatus">
                                    <option value="">Todos los estados</option>
                                    <option value="OPEN">Abiertos (Open)</option>
                                    <option value="PENDING">Pendientes (Pending)</option>
                                    <option value="RESOLVED">Resueltos (Resolved)</option>
                                    <option value="CLOSED">Cerrados (Closed)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="btn-group">
                                <button type="button" class="btn btn-success" id="btnExcel">
                                    <i class="fas fa-file-excel mr-1"></i> Descargar Excel
                                </button>
                                <button type="button" class="btn btn-danger" id="btnPdf">
                                    <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
                                </button>
                            </div>
                            <small class="text-muted ml-3 align-middle d-inline-block mt-2 mt-md-0">
                                <i class="fas fa-info-circle"></i> El reporte incluye detalles como prioridad, categoría y fechas.
                            </small>
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
    
    // Config
    const CONFIG = {
        API_BASE: '/api/analytics/agent-dashboard',
        EXCEL_URL: '/app/agent/reports/tickets/excel',
        PDF_URL: '/app/agent/reports/tickets/pdf'
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

    // Build query params
    function buildQuery() {
        const status = document.getElementById('filterStatus').value;
        return status ? '?status=' + status : '';
    }

    // Export Handlers
    document.getElementById('btnExcel').onclick = function() {
        showDownloadToast('excel');
        window.location.href = CONFIG.EXCEL_URL + buildQuery();
    };
    
    document.getElementById('btnPdf').onclick = function() {
        showDownloadToast('pdf');
        window.location.href = CONFIG.PDF_URL + buildQuery();
    };
    
    // Load Stats
    async function loadStats() {
        try {
            const response = await fetch(CONFIG.API_BASE, {
                headers: { 'Authorization': 'Bearer ' + getToken(), 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Failed to load stats');
            
            const data = await response.json();
            const ts = data.ticket_status || {};
            
            document.getElementById('statTotal').textContent = (ts.OPEN || 0) + (ts.PENDING || 0) + (ts.RESOLVED || 0) + (ts.CLOSED || 0);
            document.getElementById('statOpen').textContent = ts.OPEN || 0;
            document.getElementById('statPending').textContent = ts.PENDING || 0;
            document.getElementById('statResolved').textContent = (ts.RESOLVED || 0) + (ts.CLOSED || 0);
            
        } catch (e) {
            console.error('Stats error:', e);
            ['statTotal', 'statOpen', 'statPending', 'statResolved'].forEach(id => {
                document.getElementById(id).textContent = '-';
            });
        }
    }
    
    // Init
    if (window.tokenManager) {
        loadStats();
    } else {
        setTimeout(loadStats, 500);
    }
})();
</script>
@endpush
