@extends('layouts.authenticated')

@section('title', 'Empresa y Equipo - Company Admin')
@section('content_header', 'Empresa y Equipo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Empresa y Equipo</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-building"></i> Información de la Empresa y Equipo</h5>
        <p class="mb-0">Vista general de tu empresa, equipo de trabajo y estadísticas de configuración.</p>
    </div>

    {{-- Company Info Header --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-widget widget-user-2">
                <div class="widget-user-header bg-gradient-primary">
                    <div class="d-flex align-items-center">
                        @if($company?->logo_url)
                            <img class="img-circle elevation-2 mr-3" src="{{ $company->logo_url }}" alt="Logo"
                                style="width: 80px; height: 80px; background: white; padding: 5px;">
                        @else
                            <div class="img-circle elevation-2 mr-3 bg-white d-flex align-items-center justify-content-center"
                                style="width: 80px; height: 80px;">
                                <i class="fas fa-building fa-2x text-primary"></i>
                            </div>
                        @endif
                        <div>
                            <h3 class="widget-user-username mb-0">{{ $company?->name ?? 'Mi Empresa' }}</h3>
                            <h6 class="widget-user-desc">{{ $company?->legal_name ?? '' }}</h6>
                            @if($company?->industry)
                                <span class="badge badge-light"><i class="fas fa-industry"></i> {{ $company->industry->name ?? '' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer p-0">
                    <ul class="nav flex-row">
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-user-shield text-warning"></i> {{ $stats['totalAdmins'] }} Admins
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-user-tie text-info"></i> {{ $stats['totalAgents'] }} Agentes
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-ticket-alt text-primary"></i> {{ number_format($stats['totalTickets']) }}
                                Tickets
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-sitemap text-success"></i> {{ $stats['totalAreas'] }} Áreas
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-tags text-secondary"></i> {{ $stats['totalCategories'] }} Categorías
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Stats --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['totalAdmins'] }}</h3>
                    <p>Administradores</p>
                </div>
                <div class="icon"><i class="fas fa-user-shield"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['totalAgents'] }}</h3>
                    <p>Agentes de Soporte</p>
                </div>
                <div class="icon"><i class="fas fa-user-tie"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['totalAreas'] }}</h3>
                    <p>Áreas Activas</p>
                </div>
                <div class="icon"><i class="fas fa-sitemap"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['totalCategories'] }}</h3>
                    <p>Categorías</p>
                </div>
                <div class="icon"><i class="fas fa-tags"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Administrators --}}
        <div class="col-lg-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i> Administradores</h3>
                    <span class="badge badge-warning float-right">{{ $admins->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($admins as $admin)
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    @if($admin['avatar'])
                                        <img src="{{ $admin['avatar'] }}" class="img-circle mr-3"
                                            style="width: 40px; height: 40px;">
                                    @else
                                        <div class="img-circle bg-warning text-white d-flex align-items-center justify-content-center mr-3"
                                            style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <strong>{{ $admin['name'] }}</strong>
                                        <br><small class="text-muted">{{ $admin['email'] }}</small>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">No hay administradores registrados</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Agents --}}
        <div class="col-lg-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-tie mr-2"></i> Agentes de Soporte</h3>
                    <span class="badge badge-info float-right">{{ $agents->count() }}</span>
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 350px;">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Agente</th>
                                <th class="text-center">Asignados</th>
                                <th class="text-center">Resueltos</th>
                                <th class="text-center">Tasa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agents as $agent)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($agent['avatar'])
                                                <img src="{{ $agent['avatar'] }}" class="img-circle mr-2"
                                                    style="width: 30px; height: 30px;">
                                            @else
                                                <div class="img-circle bg-info text-white d-flex align-items-center justify-content-center mr-2"
                                                    style="width: 30px; height: 30px;">
                                                    <i class="fas fa-user" style="font-size: 12px;"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <strong>{{ Str::limit($agent['name'], 20) }}</strong>
                                                <br><small class="text-muted">{{ Str::limit($agent['email'], 25) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge badge-primary">{{ $agent['assigned'] }}</span>
                                    </td>
                                    <td class="text-center"><span class="badge badge-success">{{ $agent['resolved'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        @php $rate = $agent['rate']; @endphp
                                        <span
                                            class="badge badge-{{ $rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'secondary') }}">{{ $rate }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay agentes registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Areas --}}
        <div class="col-lg-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i> Áreas de la Empresa</h3>
                    <span class="badge badge-success float-right">{{ $areas->count() }}</span>
                </div>
                <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($areas as $area)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-folder text-success mr-2"></i> {{ $area->name }}</span>
                                @if($area->is_active)
                                    <span class="badge badge-success">Activa</span>
                                @else
                                    <span class="badge badge-secondary">Inactiva</span>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">No hay áreas configuradas</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Categories --}}
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tags mr-2"></i> Categorías de Tickets</h3>
                    <span class="badge badge-primary float-right">{{ $categories->count() }}</span>
                </div>
                <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($categories as $category)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-tag text-primary mr-2"></i> {{ $category->name }}</span>
                                @if($category->is_active ?? true)
                                    <span class="badge badge-primary">Activa</span>
                                @else
                                    <span class="badge badge-secondary">Inactiva</span>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">No hay categorías configuradas</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Section --}}
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-download mr-2"></i> Exportar Reporte de Empresa</h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle"></i> Descarga un documento PDF con toda la información de tu empresa: datos
                generales, equipo de trabajo, áreas y categorías configuradas.
            </p>
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-building text-primary"></i> Información Incluida:</h6>
                    <ul class="text-muted small">
                        <li>Datos de la empresa (nombre, razón social, industria)</li>
                        <li>Información de contacto</li>
                        <li>Logo de la empresa</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-users text-success"></i> Equipo de Trabajo:</h6>
                    <ul class="text-muted small">
                        <li>Lista de administradores</li>
                        <li>Lista de agentes con estadísticas</li>
                        <li>Áreas y categorías configuradas</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light">
            <a href="/app/company/reports/company/pdf" class="btn btn-danger btn-lg">
                <i class="fas fa-file-pdf mr-2"></i> Descargar Reporte PDF
            </a>
        </div>
    </div>

@endsection