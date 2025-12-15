@extends('layouts.pdf-report')

@section('title', 'Reporte de Artículos - ' . ($company->name ?? 'Empresa'))
@section('report-title', 'Centro de Ayuda')

@section('report-meta')
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ $company->name ?? 'Empresa' }} &bull; {{ count($articles) }} Artículos
@endsection

@section('content')
    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell">
                <span class="stat-number">{{ count($articles) }}</span>
                <span class="stat-label">Total</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number"
                    style="color: #059669;">{{ $articles->where('status', 'PUBLISHED')->count() }}</span>
                <span class="stat-label">Publicados</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #d97706;">{{ $articles->where('status', 'DRAFT')->count() }}</span>
                <span class="stat-label">Borradores</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #2563eb;">{{ $articles->sum('views_count') }}</span>
                <span class="stat-label">Vistas Totales</span>
            </td>
        </tr>
    </table>

    <!-- TABLE CONTENT -->
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Listado de Artículos</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">TÍTULO</th>
                    <th style="width: 18%;">CATEGORÍA</th>
                    <th style="width: 10%;">ESTADO</th>
                    <th style="width: 8%; text-align: center;">VISTAS</th>
                    <th style="width: 17%;">AUTOR</th>
                    <th style="width: 8%; text-align: right;">CREADO</th>
                    <th style="width: 9%; text-align: right;">PUBLICADO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td>
                            <strong style="color: #111827;">{{ Str::limit($article->title, 35) }}</strong>
                            @if($article->excerpt)
                                <div style="font-size: 9px; color: #6b7280; margin-top: 2px;">
                                    {{ Str::limit($article->excerpt, 50) }}</div>
                            @endif
                        </td>
                        <td style="font-size: 10px; color: #374151;">
                            {{ $article->category?->name ?? '-' }}
                        </td>
                        <td>
                            @if($article->status === 'PUBLISHED')
                                <span class="status-active">PUBLICADO</span>
                            @elseif($article->status === 'DRAFT')
                                <span style="color: #d97706; font-weight: bold; font-size: 10px;">BORRADOR</span>
                            @else
                                <span class="status-default">{{ strtoupper($article->status) }}</span>
                            @endif
                        </td>
                        <td class="number-cell">
                            @if(($article->views_count ?? 0) == 0)
                                <span style="color: #d1d5db; font-weight: normal;">0</span>
                            @else
                                {{ $article->views_count }}
                            @endif
                        </td>
                        <td style="font-size: 10px; color: #374151;">
                            {{ $article->author?->profile?->display_name ?? $article->author?->email ?? '-' }}
                        </td>
                        <td class="date-cell">{{ $article->created_at?->format('d/m/y') ?? '-' }}</td>
                        <td class="date-cell">{{ $article->published_at?->format('d/m/y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                            No hay artículos con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection