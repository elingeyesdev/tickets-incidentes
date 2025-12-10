@extends('layouts.authenticated')

@section('title', 'Reporte de Agentes - Company Admin')
@section('content_header', 'Rendimiento de Agentes')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Agentes</li>
@endsection

@section('content')

<div class="callout callout-info">
    <h5><i class="fas fa-users-cog"></i> Reporte de Rendimiento de Agentes</h5>
    <p class="mb-0">Analiza el desempeño de tu equipo de soporte. Incluye métricas de tickets asignados, resueltos y tasas de resolución por agente.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Rendimiento</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-light border">
                    <h6 class="mb-2"><i class="fas fa-table text-primary"></i> <strong>Información Incluida:</strong></h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="mb-0 pl-3">
                                <li>Nombre completo del agente</li>
                                <li>Correo electrónico</li>
                                <li>Total de tickets asignados</li>
                                <li>Tickets activos (abiertos/pendientes)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0 pl-3">
                                <li>Tickets resueltos</li>
                                <li>Tickets resueltos hoy</li>
                                <li>Tasa de resolución (%)</li>
                                <li>Fecha de ingreso</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="btn-group w-100" role="group">
                    <a href="/app/company/reports/agents/excel" class="btn btn-success btn-lg">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </a>
                    <a href="/app/company/reports/agents/pdf" class="btn btn-danger btn-lg">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Estadísticas del Equipo</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-tie text-primary mr-2"></i> Total Agentes</span>
                        <span class="badge badge-primary badge-pill" id="statAgents"><i class="fas fa-spinner fa-spin"></i></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-ticket-alt text-info mr-2"></i> Promedio Tickets/Agente</span>
                        <span class="badge badge-info badge-pill" id="statAvg">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-trophy text-warning mr-2"></i> Mejor Agente</span>
                        <span class="badge badge-warning badge-pill" id="statBest">-</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i> Consejo</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    <small>Usa este reporte para identificar agentes con alta carga de trabajo o reconocer a quienes tienen mejor desempeño.</small>
                </p>
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
            const response = await fetch('/api/analytics/company-dashboard', {
                headers: { 'Authorization': `Bearer ${getToken()}`, 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Failed');
            const data = await response.json();
            
            const agents = data.agents_performance || [];
            document.getElementById('statAgents').textContent = agents.length;
            
            if (agents.length > 0) {
                const totalTickets = agents.reduce((sum, a) => sum + (a.assigned_tickets || 0), 0);
                document.getElementById('statAvg').textContent = Math.round(totalTickets / agents.length);
                
                const best = agents.reduce((prev, curr) => 
                    (curr.resolved_tickets || 0) > (prev.resolved_tickets || 0) ? curr : prev
                );
                document.getElementById('statBest').textContent = best.name?.split(' ')[0] || '-';
            }
        } catch (e) {
            console.error('Stats error:', e);
        }
    }
    
    setTimeout(loadStats, 500);
})();
</script>
@endpush
