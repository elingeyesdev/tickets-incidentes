@extends('layouts.pdf-report')

@section('title', 'Reporte de Tickets - ' . ($company->name ?? 'Empresa'))
@section('report-title', 'Reporte de Tickets')

@section('report-meta')
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ $company->name ?? 'Empresa' }} &bull; {{ count($tickets) }} Tickets
@endsection

@section('content')
    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $summary['total'] }}</span>
                <span class="stat-label">Total</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #dc2626;">{{ $summary['open'] }}</span>
                <span class="stat-label">Abiertos</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #d97706;">{{ $summary['pending'] }}</span>
                <span class="stat-label">Pendientes</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #059669;">{{ $summary['resolved'] + $summary['closed'] }}</span>
                <span class="stat-label">Resueltos</span>
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
                    <th style="width: 10%;">CÓDIGO</th>
                    <th style="width: 25%;">ASUNTO</th>
                    <th style="width: 15%;">USUARIO</th>
                    <th style="width: 15%;">AGENTE</th>
                    <th style="width: 8%;">PRIORIDAD</th>
                    <th style="width: 8%;">ESTADO</th>
                    <th style="width: 5%; text-align: center;">RESP</th>
                    <th style="width: 7%; text-align: right;">CREADO</th>
                    <th style="width: 7%; text-align: right;">ACTUALIZADO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>
                            <code
                                style="font-size: 9px; background: #f3f4f6; padding: 2px 4px; border-radius: 3px;">{{ $ticket->ticket_code }}</code>
                        </td>
                        <td>
                            <strong style="color: #111827;">{{ Str::limit($ticket->subject, 35) }}</strong>
                            @if($ticket->category)
                                <div style="font-size: 9px; color: #6b7280; margin-top: 2px;">{{ $ticket->category->name }}</div>
                            @endif
                        </td>
                        <td style="font-size: 10px; color: #374151;">
                            {{ $ticket->creator?->profile?->display_name ?? $ticket->creator?->email ?? '-' }}
                        </td>
                        <td style="font-size: 10px; color: #374151;">
                            {{ $ticket->ownerAgent?->profile?->display_name ?? $ticket->ownerAgent?->email ?? '-' }}
                        </td>
                        <td>
                            @if($ticket->priority === 'high')
                                <span style="color: #dc2626; font-weight: bold; font-size: 10px;">ALTA</span>
                            @elseif($ticket->priority === 'medium')
                                <span style="color: #d97706; font-weight: bold; font-size: 10px;">MEDIA</span>
                            @else
                                <span style="color: #0284c7; font-weight: bold; font-size: 10px;">BAJA</span>
                            @endif
                        </td>
                        <td>
                            @if($ticket->status === 'open')
                                <span class="status-suspended">ABIERTO</span>
                            @elseif($ticket->status === 'pending')
                                <span
                                    style="color: #d97706; font-weight: bold; font-size: 10px; text-transform: uppercase;">PENDIENTE</span>
                            @elseif($ticket->status === 'resolved')
                                <span class="status-active">RESUELTO</span>
                            @else
                                <span class="status-default">CERRADO</span>
                            @endif
                        </td>
                        <td class="number-cell">
                            @if($ticket->responses_count == 0)
                                <span style="color: #d1d5db; font-weight: normal;">0</span>
                            @else
                                {{ $ticket->responses_count }}
                            @endif
                        </td>
                        <td class="date-cell">{{ $ticket->created_at?->format('d/m/y') ?? '-' }}</td>
                        <td class="date-cell">{{ $ticket->updated_at?->format('d/m/y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #6b7280;">
                            No hay tickets con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection