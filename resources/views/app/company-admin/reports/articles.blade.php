@extends('layouts.authenticated')

@section('title', 'Reporte de Artículos - Company Admin')
@section('content_header', 'Reporte Centro de Ayuda')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/company/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Centro de Ayuda</li>
@endsection

@section('content')

    {{-- Page Description --}}
    <div class="callout callout-info">
        <h5><i class="fas fa-book"></i> Reporte de Artículos del Centro de Ayuda</h5>
        <p class="mb-0">Visualiza estadísticas y genera reportes de los artículos de ayuda publicados.</p>
    </div>

    {{-- KPI Small Boxes Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $kpis['total'] }}</h3>
                    <p>Total Artículos</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
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
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($kpis['totalViews']) }}</h3>
                    <p>Vistas Totales</p>
                </div>
                <div class="icon"><i class="fas fa-eye"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Category Distribution Chart --}}
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Por Categoría</h3>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" style="min-height: 250px;"></canvas>
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

    {{-- Top Articles Table --}}
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-trophy mr-2"></i> Artículos Más Vistos</h3>
            <div class="card-tools">
                <a href="/app/company/reports/articles/pdf" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th class="text-center">Vistas</th>
                        <th>Autor</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td><strong>{{ Str::limit($article->title, 40) }}</strong></td>
                            <td><span class="badge badge-info">{{ $article->category?->name ?? 'Sin categoría' }}</span></td>
                            <td>
                                @php $status = $article->status ?? 'draft'; @endphp
                                @if($status === 'published')
                                    <span class="badge badge-success">Publicado</span>
                                @else
                                    <span class="badge badge-warning">Borrador</span>
                                @endif
                            </td>
                            <td class="text-center"><span
                                    class="badge badge-primary">{{ number_format($article->views_count ?? 0) }}</span></td>
                            <td><small>{{ $article->author?->profile?->display_name ?? $article->author?->email ?? '-' }}</small>
                            </td>
                            <td><small>{{ $article->created_at?->format('d/m/y') }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No hay artículos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="/app/company/articles" class="btn btn-sm btn-outline-dark">Ver todos los artículos</a>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            'use strict';

            const categoryStats = @json($categoryStats);
            const statusStats = @json($statusStats);

            // Category Chart
            const ctxCat = document.getElementById('categoryChart');
            if (ctxCat && categoryStats.length > 0) {
                const colors = [
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ];

                new Chart(ctxCat, {
                    type: 'doughnut',
                    data: {
                        labels: categoryStats.map(c => c.name),
                        datasets: [{
                            data: categoryStats.map(c => c.count),
                            backgroundColor: colors.slice(0, categoryStats.length),
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
                    'archived': 'Archivados'
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
                                'rgba(108, 117, 125, 0.8)'
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