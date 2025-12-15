@extends('layouts.pdf-report')

@section('title', 'Reporte de Anuncios - ' . ($company->name ?? 'Empresa'))
@section('report-title', 'Reporte de Anuncios')

@section('report-meta')
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ $company->name ?? 'Empresa' }} &bull; {{ count($announcements) }} Anuncios
@endsection

@section('content')
    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell">
                <span class="stat-number">{{ count($announcements) }}</span>
                <span class="stat-label">Total</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #059669;">{{ $announcements->where('status', 'PUBLISHED')->count() }}</span>
                <span class="stat-label">Publicados</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #d97706;">{{ $announcements->where('status', 'DRAFT')->count() }}</span>
                <span class="stat-label">Borradores</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #6b7280;">{{ $announcements->where('status', 'ARCHIVED')->count() }}</span>
                <span class="stat-label">Archivados</span>
            </td>
        </tr>
    </table>

    <!-- TABLE CONTENT -->
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Listado de Anuncios</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">TÍTULO</th>
                    <th style="width: 12%;">TIPO</th>
                    <th style="width: 12%;">ESTADO</th>
                    <th style="width: 20%;">AUTOR</th>
                    <th style="width: 13%; text-align: right;">CREADO</th>
                    <th style="width: 13%; text-align: right;">PUBLICADO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $announcement)
                <tr>
                    <td>
                        <strong style="color: #111827;">{{ Str::limit($announcement->title, 40) }}</strong>
                    </td>
                    <td>
                        @switch($announcement->type?->value ?? $announcement->type)
                            @case('MAINTENANCE')
                                <span style="color: #d97706; font-weight: bold; font-size: 10px;">MANTENIMIENTO</span>
                                @break
                            @case('INCIDENT')
                                <span style="color: #dc2626; font-weight: bold; font-size: 10px;">INCIDENTE</span>
                                @break
                            @case('NEWS')
                                <span style="color: #0284c7; font-weight: bold; font-size: 10px;">NOTICIA</span>
                                @break
                            @case('ALERT')
                                <span style="color: #7c3aed; font-weight: bold; font-size: 10px;">ALERTA</span>
                                @break
                            @default
                                <span class="status-default">{{ $announcement->type }}</span>
                        @endswitch
                    </td>
                    <td>
                        @switch($announcement->status?->value ?? $announcement->status)
                            @case('PUBLISHED')
                                <span class="status-active">PUBLICADO</span>
                                @break
                            @case('DRAFT')
                                <span style="color: #6b7280; font-weight: bold; font-size: 10px;">BORRADOR</span>
                                @break
                            @case('SCHEDULED')
                                <span style="color: #0284c7; font-weight: bold; font-size: 10px;">PROGRAMADO</span>
                                @break
                            @case('ARCHIVED')
                                <span class="status-default">ARCHIVADO</span>
                                @break
                            @default
                                <span class="status-default">{{ $announcement->status }}</span>
                        @endswitch
                    </td>
                    <td style="font-size: 10px; color: #374151;">
                        {{ $announcement->author?->profile?->display_name ?? $announcement->author?->email ?? '-' }}
                    </td>
                    <td class="date-cell">{{ $announcement->created_at?->format('d/m/y') ?? '-' }}</td>
                    <td class="date-cell">{{ $announcement->published_at?->format('d/m/y') ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                        No hay anuncios con los filtros seleccionados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
