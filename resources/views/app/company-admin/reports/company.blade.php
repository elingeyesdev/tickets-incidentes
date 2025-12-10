@extends('layouts.authenticated')

@section('title', 'Empresa y Equipo - Company Admin')
@section('content_header', 'Reporte de Empresa y Equipo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Empresa y Equipo</li>
@endsection

@section('content')

<div class="callout callout-info">
    <h5><i class="fas fa-building"></i> Reporte de Empresa y Equipo</h5>
    <p class="mb-0">Descarga un documento con la información de tu empresa y un listado completo de tu equipo de trabajo (administradores y agentes).</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-pdf mr-2"></i> Generar Reporte</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-building text-primary"></i> <strong>Información de la Empresa</strong></h6>
                        <ul class="pl-3 text-muted">
                            <li>Nombre comercial</li>
                            <li>Razón social</li>
                            <li>Industria</li>
                            <li>Información de contacto</li>
                            <li>Dirección</li>
                            <li>Fecha de registro</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-users text-success"></i> <strong>Equipo de Trabajo</strong></h6>
                        <ul class="pl-3 text-muted">
                            <li>Administradores de empresa</li>
                            <li>Agentes de soporte</li>
                            <li>Correos electrónicos</li>
                            <li>Tickets asignados por agente</li>
                            <li>Estado de cada miembro</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light text-center">
                <a href="/app/company/reports/company/pdf" class="btn btn-danger btn-lg btn-block">
                    <i class="fas fa-file-pdf mr-2"></i> Descargar Reporte (PDF)
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-id-card mr-2"></i> Tu Empresa</h3>
            </div>
            <div class="card-body text-center">
                <div id="companyLogo" class="mb-3">
                    <i class="fas fa-building fa-4x text-muted"></i>
                </div>
                <h5 id="companyName"><i class="fas fa-spinner fa-spin"></i></h5>
                <p class="text-muted mb-0" id="companyIndustry">-</p>
            </div>
        </div>
        
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i> Equipo</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-shield text-warning mr-2"></i> Administradores</span>
                        <span class="badge badge-warning badge-pill" id="statAdmins">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-tie text-info mr-2"></i> Agentes</span>
                        <span class="badge badge-info badge-pill" id="statAgents">-</span>
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
    
    async function loadCompanyInfo() {
        try {
            const response = await fetch('/api/analytics/company-dashboard', {
                headers: { 'Authorization': `Bearer ${getToken()}`, 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Failed');
            const data = await response.json();
            
            // Company info from JWT/session
            const companyInfo = data.company_info || {};
            document.getElementById('companyName').textContent = companyInfo.name || 'Mi Empresa';
            document.getElementById('companyIndustry').textContent = companyInfo.industry || '-';
            
            if (companyInfo.logo_url) {
                document.getElementById('companyLogo').innerHTML = `<img src="${companyInfo.logo_url}" alt="Logo" class="img-fluid" style="max-height: 80px;">`;
            }
            
            // Team stats
            const agents = data.agents_performance || [];
            document.getElementById('statAgents').textContent = agents.length;
            document.getElementById('statAdmins').textContent = '1'; // At least the current admin
        } catch (e) {
            console.error('Company info error:', e);
            document.getElementById('companyName').textContent = 'Mi Empresa';
        }
    }
    
    setTimeout(loadCompanyInfo, 500);
})();
</script>
@endpush
