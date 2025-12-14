@extends('layouts.pdf-report')

@section('title', 'Reporte de Historial de Tickets')
@section('report-title', 'Historial de Tickets')

@section('report-meta')
    Usuario: {{ $userName }}<br>
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ count($tickets) }} registros &bull;
    Filtros:
    @if($filters['status']) Estado: {{ $filters['status'] }} @endif
    @if($filters['company']) | Empresa: {{ $filters['company'] }} @endif
    @if($filters['category']) | CatID: {{ $filters['category'] }} @endif
    @if($filters['priority']) | Prioridad: {{ $filters['priority'] }} @endif
    @if(!$filters['status'] && !$filters['priority'] && !$filters['company'] && !$filters['category']) Todos @endif
@endsection

@section('content')
    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell" style="border-bottom-color: #2563eb;">
                <span class="stat-number">{{ $summary['total'] }}</span>
                <span class="stat-label">Total</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #dc2626;">
                <span class="stat-number" style="color: #dc2626;">{{ $summary['open'] }}</span>
                <span class="stat-label">Abiertos</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #d97706;">
                <span class="stat-number" style="color: #d97706;">{{ $summary['pending'] }}</span>
                <span class="stat-label">Pendientes</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #059669;">
                <span class="stat-number" style="color: #059669;">{{ $summary['resolved'] }}</span>
                <span class="stat-label">Resueltos</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #4b5563;">
                <span class="stat-number" style="color: #4b5563;">{{ $summary['closed'] }}</span>
                <span class="stat-label">Cerrados</span>
            </td>
        </tr>
    </table>

    <!-- TABLE CONTENT -->
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Listado de Tickets</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%">CÓDIGO</th>
                    <th style="width: 25%">ASUNTO</th>
                    <th style="width: 15%">EMPRESA</th>
                    <th style="width: 12%">CATEGORÍA</th>
                    <th style="width: 10%">PRIORIDAD</th>
                    <th style="width: 10%">ESTADO</th>
                    <th style="width: 16%; text-align: right;">FECHA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>
                            <span style="font-family: monospace; color: #555;">{{ $ticket->ticket_code ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <strong style="color: #111827;">{{ Str::limit($ticket->title, 40) }}</strong>
                        </td>
                        <td>{{ $ticket->company?->name ?? 'N/A' }}</td>
                        <td>{{ $ticket->category?->name ?? 'Sin categoría' }}</td>
                        <td>
                            @php $p = strtoupper($ticket->priority?->value ?? $ticket->priority ?? ''); @endphp
                            @if($p === 'HIGH')
                                <span style="color: #dc2626; font-weight: bold; font-size: 10px;">ALTA</span>
                            @elseif($p === 'MEDIUM')
                                <span style="color: #d97706; font-weight: bold; font-size: 10px;">MEDIA</span>
                            @else
                                <span style="color: #0284c7; font-weight: bold; font-size: 10px;">BAJA</span>
                            @endif
                        </td>
                        <td>
                            @php $s = strtoupper($ticket->status?->value ?? $ticket->status ?? ''); @endphp
                            @if($s === 'OPEN')
                                <span class="status-suspended">ABIERTO</span>
                            @elseif($s === 'PENDING')
                                <span class="status-default" style="color: #d97706;">PENDIENTE</span>
                            @elseif($s === 'RESOLVED')
                                <span class="status-active">RESUELTO</span>
                            @else
                                <span class="status-default">CERRADO</span>
                            @endif
                        </td>
                        <td class="date-cell">{{ $ticket->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                            No se encontraron tickets con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection