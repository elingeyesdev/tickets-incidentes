@extends('layouts.authenticated')

@section('title', 'Resumen Operativo - Company Admin')
@section('content_header', 'Resumen Operativo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Resumen Operativo</li>
@endsection

@section('content')

<div class="callout callout-info">
    <h5><i class="fas fa-chart-line"></i> Resumen Operativo Ejecutivo</h5>
    <p class="mb-0">Obtén una vista consolidada de todas las operaciones de soporte de tu empresa. Ideal para presentaciones ejecutivas y toma de decisiones.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-pdf mr-2"></i> Generar Reporte Ejecutivo</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-primary"><i class="fas fa-ticket-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">KPIs de Tickets</span>
                                <span class="info-box-number text-muted">Abiertos, Pendientes, Resueltos</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Equipo de Soporte</span>
                                <span class="info-box-number text-muted">Agentes Activos</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info"><i class="fas fa-book"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Help Center</span>
                                <span class="info-box-number text-muted">Artículos Publicados</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-warning"><i class="fas fa-bullhorn"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Comunicaciones</span>
                                <span class="info-box-number text-muted">Anuncios Publicados</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h6><i class="fas fa-list-alt text-primary"></i> <strong>Contenido del Reporte:</strong></h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="pl-3">
                            <li>Distribución de tickets por estado</li>
                            <li>Distribución por prioridad</li>
                            <li>Top 5 categorías con más tickets</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="pl-3">
                            <li>Resumen de configuración (áreas, categorías)</li>
                            <li>Tendencia de tickets (últimos 6 meses)</li>
                            <li>Métricas de rendimiento general</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light text-center">
                <a href="/app/company/reports/summary/pdf" class="btn btn-danger btn-lg btn-block">
                    <i class="fas fa-file-pdf mr-2"></i> Descargar Resumen Ejecutivo (PDF)
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Vista Previa</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-inbox text-primary mr-2"></i> Total Tickets</span>
                        <span class="badge badge-primary badge-pill" id="statTotal"><i class="fas fa-spinner fa-spin"></i></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-tie text-info mr-2"></i> Agentes</span>
                        <span class="badge badge-info badge-pill" id="statAgents">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-book text-success mr-2"></i> Artículos</span>
                        <span class="badge badge-success badge-pill" id="statArticles">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-bullhorn text-warning mr-2"></i> Anuncios</span>
                        <span class="badge badge-warning badge-pill" id="statAnnouncements">-</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i> ¿Para qué sirve?</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    <small>Este reporte consolida toda la información operativa en un solo documento PDF, ideal para reuniones de seguimiento o presentaciones a gerencia.</small>
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
            
            const ts = data.ticket_status || {};
            document.getElementById('statTotal').textContent = (ts.open || 0) + (ts.pending || 0) + (ts.resolved || 0) + (ts.closed || 0);
            document.getElementById('statAgents').textContent = (data.agents_performance || []).length;
            document.getElementById('statArticles').textContent = data.help_center?.total_articles || 0;
            document.getElementById('statAnnouncements').textContent = data.announcements?.total || 0;
        } catch (e) {
            console.error('Stats error:', e);
        }
    }
    
    setTimeout(loadStats, 500);
})();
</script>
@endpush
