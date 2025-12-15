@extends('layouts.authenticated')

@section('title', 'Centro de Reportes - Platform Admin')
@section('content_header', 'Centro de Reportes')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Reportes</li>
@endsection

@section('content')

{{-- Page Description --}}
<div class="callout callout-info">
    <h5><i class="fas fa-info-circle"></i> Centro de Reportes</h5>
    <p class="mb-0">Genera y descarga reportes en formato PDF o Excel. Los reportes se generan con la información actual del sistema.</p>
</div>

{{-- Report Cards Grid --}}
<div class="row">
    {{-- Reporte de Empresas --}}
    <div class="col-lg-4 col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building"></i> Reporte de Empresas</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Listado completo de empresas registradas con estadísticas de agentes, tickets y artículos.
                </p>
                
                {{-- Filtros opcionales --}}
                <div class="form-group">
                    <label class="text-sm"><i class="fas fa-filter"></i> Filtrar por Estado</label>
                    <select class="form-control form-control-sm" id="companiesStatus">
                        <option value="">Todas las empresas</option>
                        <option value="active">Solo Activas</option>
                        <option value="suspended">Solo Suspendidas</option>
                    </select>
                </div>
                
                <div class="text-muted small">
                    <i class="fas fa-table"></i> Incluye: Código, Nombre, Email, Industria, Estado, Agentes, Tickets
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-success" id="btnCompaniesExcel">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="btnCompaniesPdf">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reporte de Crecimiento --}}
    <div class="col-lg-4 col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> Crecimiento de Plataforma</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Estadísticas de crecimiento: nuevas empresas, usuarios y tickets por mes.
                </p>
                
                <div class="form-group">
                    <label class="text-sm"><i class="fas fa-calendar-alt"></i> Periodo de Análisis</label>
                    <select class="form-control form-control-sm" id="growthPeriod">
                        <option value="6">Últimos 6 meses</option>
                        <option value="12">Último año (12 meses)</option>
                        <option value="3">Últimos 3 meses</option>
                    </select>
                </div>
                
                <div class="text-muted small">
                    <i class="fas fa-chart-bar"></i> Incluye: Datos mensuales y resumen general
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-success" id="btnGrowthExcel">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="btnGrowthPdf">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reporte de Solicitudes --}}
    <div class="col-lg-4 col-md-6">
        <div class="card card-outline card-purple">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-inbox"></i> Solicitudes de Empresa</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Historial de solicitudes de registro de empresas con estado de revisión.
                </p>
                
                <div class="form-group">
                    <label class="text-sm"><i class="fas fa-filter"></i> Filtrar por Estado</label>
                    <select class="form-control form-control-sm" id="requestsStatus">
                        <option value="">Todas las solicitudes</option>
                        <option value="pending">Pendientes</option>
                        <option value="approved">Aprobadas</option>
                        <option value="rejected">Rechazadas</option>
                    </select>
                </div>
                
                <div class="text-muted small">
                    <i class="fas fa-list"></i> Incluye: Empresa, Admin, Fecha, Estado, Revisor
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-success" id="btnRequestsExcel">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="btnRequestsPdf">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Stats Row --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Estadísticas Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-building"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Empresas</span>
                                <span class="info-box-number" id="statTotalCompanies">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Usuarios</span>
                                <span class="info-box-number" id="statTotalUsers">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-ticket-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Tickets</span>
                                <span class="info-box-number" id="statTotalTickets">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-purple">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Solicitudes Pendientes</span>
                                <span class="info-box-number" id="statPendingRequests">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                        </div>
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
    
    console.log('[Reports] Initializing reports page...');
    
    // =========================================================================
    // CONFIGURATION
    // =========================================================================
    const CONFIG = {
        API_BASE: '/api',
        REPORTS_BASE: '/app/admin/reports'
    };
    
    // =========================================================================
    // UTILITIES
    // =========================================================================
    const Utils = {
        getToken() {
            return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
        },
        
        showDownloadToast(type) {
            const icon = type === 'excel' ? 'fa-file-excel' : 'fa-file-pdf';
            const colorClass = type === 'excel' ? 'bg-success' : 'bg-danger';
            
            $(document).Toasts('create', {
                class: colorClass,
                title: 'Generando Reporte',
                body: `<i class="fas ${icon}"></i> Tu reporte se está descargando...`,
                autohide: true,
                delay: 3000,
                icon: `fas ${icon}`
            });
        }
    };
    
    // =========================================================================
    // DOWNLOAD HANDLERS
    // =========================================================================
    
    // Companies Report
    document.getElementById('btnCompaniesExcel').onclick = function() {
        const status = document.getElementById('companiesStatus').value;
        const url = `${CONFIG.REPORTS_BASE}/companies/excel${status ? '?status=' + status : ''}`;
        Utils.showDownloadToast('excel');
        window.location.href = url;
    };
    
    document.getElementById('btnCompaniesPdf').onclick = function() {
        const status = document.getElementById('companiesStatus').value;
        const url = `${CONFIG.REPORTS_BASE}/companies/pdf${status ? '?status=' + status : ''}`;
        Utils.showDownloadToast('pdf');
        window.location.href = url;
    };
    
    // Growth Report
    document.getElementById('btnGrowthExcel').onclick = function() {
        const months = document.getElementById('growthPeriod').value || '6';
        const url = `${CONFIG.REPORTS_BASE}/growth/excel?months=${months}`;
        Utils.showDownloadToast('excel');
        window.location.href = url;
    };
    
    document.getElementById('btnGrowthPdf').onclick = function() {
        const months = document.getElementById('growthPeriod').value || '6';
        const url = `${CONFIG.REPORTS_BASE}/growth/pdf?months=${months}`;
        Utils.showDownloadToast('pdf');
        window.location.href = url;
    };
    
    // Requests Report
    document.getElementById('btnRequestsExcel').onclick = function() {
        const status = document.getElementById('requestsStatus').value;
        const url = `${CONFIG.REPORTS_BASE}/requests/excel${status ? '?status=' + status : ''}`;
        Utils.showDownloadToast('excel');
        window.location.href = url;
    };
    
    document.getElementById('btnRequestsPdf').onclick = function() {
        const status = document.getElementById('requestsStatus').value;
        const url = `${CONFIG.REPORTS_BASE}/requests/pdf${status ? '?status=' + status : ''}`;
        Utils.showDownloadToast('pdf');
        window.location.href = url;
    };
    
    // =========================================================================
    // LOAD QUICK STATS
    // =========================================================================
    async function loadQuickStats() {
        try {
            const response = await fetch(`${CONFIG.API_BASE}/analytics/platform-dashboard`, {
                headers: {
                    'Authorization': `Bearer ${Utils.getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to load stats');
            
            const data = await response.json();
            const kpi = data.kpi || {};
            
            document.getElementById('statTotalCompanies').textContent = kpi.total_companies || 0;
            document.getElementById('statTotalUsers').textContent = kpi.total_users || 0;
            document.getElementById('statTotalTickets').textContent = kpi.total_tickets || 0;
            document.getElementById('statPendingRequests').textContent = kpi.pending_requests || 0;
            
        } catch (error) {
            console.error('[Reports] Stats load error:', error);
            // Show N/A on error
            ['statTotalCompanies', 'statTotalUsers', 'statTotalTickets', 'statPendingRequests'].forEach(id => {
                document.getElementById(id).textContent = 'N/A';
            });
        }
    }
    
    // Initialize
    loadQuickStats();
    
    console.log('[Reports] Page initialized successfully');
})();
</script>
@endpush
