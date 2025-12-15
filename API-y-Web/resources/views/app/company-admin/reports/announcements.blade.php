@extends('layouts.authenticated')

@section('title', 'Reporte de Anuncios - Company Admin')
@section('content_header', 'Reporte de Anuncios')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Anuncios</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-bullhorn"></i> Reporte de Anuncios</h5>
        <p class="mb-0">Visualiza estadísticas y genera reportes de los anuncios publicados en tu empresa.</p>
    </div>

    {{-- KPI Small Boxes Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $kpis['total'] }}</h3>
                    <p>Total Anuncios</p>
                </div>
                <div class="icon"><i class="fas fa-bullhorn"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $kpis['published'] }}</h3>
                    <p>Publicados</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $kpis['draft'] }}</h3>
                    <p>Borradores</p>
                </div>
                <div class="icon"><i class="fas fa-edit"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $kpis['archived'] }}</h3>
                    <p>Archivados</p>
                </div>
                <div class="icon"><i class="fas fa-archive"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Type Distribution Chart --}}
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Por Tipo</h3>
                </div>
                <div class="card-body">
                    <canvas id="typeChart" style="min-height: 250px;"></canvas>
                </div>
            </div>
        </div>

        {{-- Status Distribution Chart --}}
        <div class="col-lg-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Por Estado</h3>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" style="min-height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Announcements Table --}}
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-2"></i> Últimos Anuncios</h3>
            <div class="card-tools">
                <a href="/app/company/reports/announcements/pdf" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Autor</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        <tr>
                            <td><strong>{{ Str::limit($announcement->title, 50) }}</strong></td>
                            <td>
                                @php $type = $announcement->type ?? 'news'; @endphp
                                @if($type === 'maintenance')
                                    <span class="badge badge-warning"><i class="fas fa-tools"></i> Mantenimiento</span>
                                @elseif($type === 'incident')
                                    <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Incidente</span>
                                @elseif($type === 'alert')
                                    <span class="badge badge-info"><i class="fas fa-bell"></i> Alerta</span>
                                @else
                                    <span class="badge badge-primary"><i class="fas fa-newspaper"></i> Noticia</span>
                                @endif
                            </td>
                            <td>
                                @php $status = $announcement->status ?? 'draft'; @endphp
                                @if($status === 'published')
                                    <span class="badge badge-success">Publicado</span>
                                @elseif($status === 'archived')
                                    <span class="badge badge-secondary">Archivado</span>
                                @else
                                    <span class="badge badge-warning">Borrador</span>
                                @endif
                            </td>
                            <td><small>{{ $announcement->author?->profile?->display_name ?? $announcement->author?->email ?? '-' }}</small>
                            </td>
                            <td><small>{{ $announcement->created_at?->format('d/m/y H:i') }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No hay anuncios registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="/app/company/announcements" class="btn btn-sm btn-outline-dark">Ver todos los anuncios</a>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            'use strict';

            const typeStats = @json($typeStats);
            const statusStats = @json($statusStats);

            // Type Chart
            const ctxType = document.getElementById('typeChart');
            if (ctxType) {
                const typeLabels = {
                    'maintenance': 'Mantenimiento',
                    'incident': 'Incidente',
                    'news': 'Noticia',
                    'alert': 'Alerta'
                };

                new Chart(ctxType, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(typeStats).map(k => typeLabels[k] || k),
                        datasets: [{
                            data: Object.values(typeStats),
                            backgroundColor: [
                                'rgba(255, 193, 7, 0.8)',
                                'rgba(220, 53, 69, 0.8)',
                                'rgba(0, 123, 255, 0.8)',
                                'rgba(23, 162, 184, 0.8)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }

            // Status Chart
            const ctxStatus = document.getElementById('statusChart');
            if (ctxStatus) {
                const statusLabels = {
                    'published': 'Publicados',
                    'draft': 'Borradores',
                    'archived': 'Archivados',
                    'scheduled': 'Programados'
                };

                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(statusStats).map(k => statusLabels[k] || k),
                        datasets: [{
                            data: Object.values(statusStats),
                            backgroundColor: [
                                'rgba(40, 167, 69, 0.8)',
                                'rgba(255, 193, 7, 0.8)',
                                'rgba(108, 117, 125, 0.8)',
                                'rgba(23, 162, 184, 0.8)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        })();
    </script>
@endpush