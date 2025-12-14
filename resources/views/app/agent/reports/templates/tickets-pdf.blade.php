{{-- resources/views/app/agent/reports/templates/tickets-pdf.blade.php --}}
@extends('layouts.pdf-report')

@section('title', 'Mis Tickets Asignados - ' . $agentName)

@section('report-title', 'Mis Tickets Asignados')

@section('report-meta')
    Agente: {{ $agentName }}<br>
    Generado: {{ now()->timezone('America/La_Paz')->format('d/m/Y H:i') }}
    @if($filter)<br>Filtro: Estado {{ $filter }}@endif
@endsection

@section('styles')
    .status-open { color: #dc2626; font-weight: bold; font-size: 10px; text-transform: uppercase; }
    .status-pending { color: #d97706; font-weight: bold; font-size: 10px; text-transform: uppercase; }
    .status-resolved { color: #059669; font-weight: bold; font-size: 10px; text-transform: uppercase; }
    .status-closed { color: #4b5563; font-weight: bold; font-size: 10px; text-transform: uppercase; }
    .priority-high { color: #dc2626; }
    .priority-medium { color: #d97706; }
    .priority-low { color: #059669; }
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell" style="border-bottom-color: #2563eb;">
                <span class="stat-number">{{ $summary['total'] ?? 0 }}</span>
                <span class="stat-label">TOTAL</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #dc2626;">
                <span class="stat-number">{{ $summary['open'] ?? 0 }}</span>
                <span class="stat-label">ABIERTOS</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #d97706;">
                <span class="stat-number">{{ $summary['pending'] ?? 0 }}</span>
                <span class="stat-label">PENDIENTES</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #059669;">
                <span class="stat-number">{{ ($summary['resolved'] ?? 0) + ($summary['closed'] ?? 0) }}</span>
                <span class="stat-label">RESUELTOS</span>
            </td>
        </tr>
    </table>

    {{-- Data Table --}}
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Detalle de Tickets</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">CÓDIGO</th>
                    <th style="width: 28%;">TÍTULO</th>
                    <th style="width: 15%;">CREADO POR</th>
                    <th style="width: 14%;">CATEGORÍA</th>
                    <th style="width: 8%; text-align: center;">PRIORIDAD</th>
                    <th style="width: 10%; text-align: center;">ESTADO</th>
                    <th style="width: 15%; text-align: right;">FECHA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td><strong>{{ $ticket->ticket_code ?? 'N/A' }}</strong></td>
                        <td>{{ Str::limit($ticket->title ?? '', 40) }}</td>
                        <td>{{ Str::limit($ticket->creator?->profile?->display_name ?? $ticket->creator?->email ?? 'N/A', 20) }}
                        </td>
                        <td>{{ Str::limit($ticket->category?->name ?? 'N/A', 18) }}</td>
                        <td class="number-cell">
                            @php $priority = $ticket->priority?->value ?? 'LOW'; @endphp
                            <span class="priority-{{ strtolower($priority) }}">
                                {{ ['HIGH' => 'Alta', 'MEDIUM' => 'Media', 'LOW' => 'Baja'][$priority] ?? $priority }}
                            </span>
                        </td>
                        <td class="number-cell">
                            @php $status = $ticket->status?->value ?? 'OPEN'; @endphp
                            <span class="status-{{ strtolower($status) }}">
                                {{ ['OPEN' => 'Abierto', 'PENDING' => 'Pendiente', 'RESOLVED' => 'Resuelto', 'CLOSED' => 'Cerrado'][$status] ?? $status }}
                            </span>
                        </td>
                        <td class="date-cell">{{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #9ca3af;">
                            No tienes tickets asignados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection