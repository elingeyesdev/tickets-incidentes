@extends('layouts.authenticated')

@section('title', 'Historial de Tickets - Reportes')
@section('content_header', 'Historial de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/user/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Historial de Tickets</li>
@endsection

@section('content')

{{-- Page Description --}}
<div class="callout callout-info">
    <h5><i class="fas fa-file-alt"></i> Reporte de Historial de Tickets</h5>
    <p class="mb-0">Descarga un listado completo de todos tus tickets en formato PDF o Excel. Puedes filtrar por estado para obtener reportes más específicos.</p>
</div>

<div class="row">
    {{-- Report Config Card --}}
    <div class="col-lg-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i> Exportar Mis Tickets</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle"></i> 
                    Este reporte incluye todos los tickets que has creado con su información detallada.
                </p>
                
                {{-- Filtro de Estado --}}
                <div class="form-group">
                    <label><i class="fas fa-filter"></i> Filtrar por Estado</label>
                    <select class="form-control" id="ticketsStatus">
                        <option value="">Todos los tickets</option>
                        <option value="OPEN">Solo Abiertos</option>
                        <option value="PENDING">Solo Pendientes</option>
                        <option value="RESOLVED">Solo Resueltos</option>
                        <option value="CLOSED">Solo Cerrados</option>
                    </select>
                </div>
                
                <div class="text-muted small mb-3">
                    <i class="fas fa-table"></i> <strong>Incluye:</strong> Código, Asunto, Empresa, Categoría, Prioridad, Estado, Fechas
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-success btn-lg" id="btnTicketsExcel">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="btnTicketsPdf">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Quick Stats --}}
    <div class="col-lg-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Resumen de Tickets</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-inbox text-primary mr-2"></i> Total de Tickets</span>
                        <span class="badge badge-primary badge-pill" id="statTotal">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
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
                        <span><i class="fas fa-check-circle text-info mr-2"></i> Resueltos</span>
                        <span class="badge badge-info badge-pill" id="statResolved">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-check-double text-success mr-2"></i> Cerrados</span>
                        <span class="badge badge-success badge-pill" id="statClosed">-</span>
                    </li>
                </ul>
            </div>
        </div>
        
        {{-- Help Callout --}}
        <div class="callout callout-warning">
            <h5><i class="fas fa-lightbulb"></i> Consejo</h5>
            <p class="mb-0 small">El archivo Excel es ideal para análisis en hojas de cálculo. El PDF es mejor para impresión o compartir.</p>
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
    
    // =========================================================================
    // UTILITIES
    // =========================================================================
    function getToken() {
        return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
    }
    
    function showDownloadToast(type) {
        const icon = type === 'excel' ? 'fa-file-excel' : 'fa-file-pdf';
        const colorClass = type === 'excel' ? 'bg-success' : 'bg-danger';
        
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
    
    document.getElementById('btnTicketsExcel').onclick = function() {
        const status = document.getElementById('ticketsStatus').value;
        const url = `${CONFIG.REPORTS_BASE}/tickets/excel${status ? '?status=' + status : ''}`;
        showDownloadToast('excel');
        window.location.href = url;
    };
    
    document.getElementById('btnTicketsPdf').onclick = function() {
        const status = document.getElementById('ticketsStatus').value;
        const url = `${CONFIG.REPORTS_BASE}/tickets/pdf${status ? '?status=' + status : ''}`;
        showDownloadToast('pdf');
        window.location.href = url;
    };
    
    // =========================================================================
    // LOAD QUICK STATS
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
            const kpi = data.kpi || {};
            
            document.getElementById('statTotal').textContent = kpi.total_tickets || 0;
            document.getElementById('statOpen').textContent = kpi.open_tickets || 0;
            document.getElementById('statPending').textContent = kpi.pending_tickets || 0;
            document.getElementById('statResolved').textContent = kpi.resolved_tickets || 0;
            document.getElementById('statClosed').textContent = kpi.closed_tickets || 0;
            
        } catch (error) {
            console.error('[Reports] Stats load error:', error);
            document.getElementById('statTotal').textContent = 'N/A';
        }
    }
    
    // Initialize
    setTimeout(loadStats, 500);
})();
</script>
@endpush
