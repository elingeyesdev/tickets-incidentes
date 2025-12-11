<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Tickets - {{ $agentName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .summary-box { display: inline-block; width: 23%; text-align: center; padding: 10px; margin: 0 0.5%; background: #fff; border: 1px solid #dee2e6; border-radius: 5px; }
        .summary-box .number { font-size: 18px; font-weight: bold; }
        .summary-box .label { font-size: 9px; color: #666; }
        .summary-open .number { color: #dc3545; }
        .summary-pending .number { color: #ffc107; }
        .summary-resolved .number { color: #28a745; }
        .summary-total .number { color: #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9px; }
        th { background: #343a40; color: white; padding: 8px 5px; text-align: left; font-weight: 600; }
        td { padding: 6px 5px; border-bottom: 1px solid #dee2e6; }
        tr:nth-child(even) { background: #f8f9fa; }
        .status-badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; color: white; }
        .status-open { background: #dc3545; }
        .status-pending { background: #ffc107; color: #333; }
        .status-resolved { background: #28a745; }
        .status-closed { background: #6c757d; }
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #ffc107; }
        .priority-low { color: #28a745; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #dee2e6; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Mis Tickets Asignados</h1>
        <p>{{ $agentName }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <div style="text-align: center; margin-bottom: 15px;">
        <div class="summary-box summary-total">
            <div class="number">{{ $summary['total'] ?? 0 }}</div>
            <div class="label">TOTAL</div>
        </div>
        <div class="summary-box summary-open">
            <div class="number">{{ $summary['open'] ?? 0 }}</div>
            <div class="label">ABIERTOS</div>
        </div>
        <div class="summary-box summary-pending">
            <div class="number">{{ $summary['pending'] ?? 0 }}</div>
            <div class="label">PENDIENTES</div>
        </div>
        <div class="summary-box summary-resolved">
            <div class="number">{{ ($summary['resolved'] ?? 0) + ($summary['closed'] ?? 0) }}</div>
            <div class="label">RESUELTOS</div>
        </div>
    </div>
    
    @if($filter)
    <div style="background: #e7f3ff; padding: 8px; border-radius: 5px; margin-bottom: 15px; font-size: 9px;">
        <strong>Filtro aplicado:</strong> Estado: {{ $filter }}
    </div>
    @endif
    
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Código</th>
                <th style="width: 25%;">Título</th>
                <th style="width: 15%;">Creado Por</th>
                <th style="width: 12%;">Categoría</th>
                <th style="width: 8%;">Prioridad</th>
                <th style="width: 10%;">Estado</th>
                <th style="width: 5%;">Resp.</th>
                <th style="width: 15%;">Creado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td><strong>{{ $ticket->ticket_code ?? 'N/A' }}</strong></td>
                <td>{{ Str::limit($ticket->title ?? '', 35) }}</td>
                <td>{{ Str::limit($ticket->creator?->profile?->display_name ?? $ticket->creator?->email ?? 'N/A', 18) }}</td>
                <td>{{ Str::limit($ticket->category?->name ?? 'N/A', 15) }}</td>
                <td class="priority-{{ strtolower($ticket->priority?->value ?? 'low') }}">
                    {{ ['HIGH' => 'Alta', 'MEDIUM' => 'Media', 'LOW' => 'Baja'][$ticket->priority?->value ?? ''] ?? $ticket->priority }}
                </td>
                <td>
                    <span class="status-badge status-{{ strtolower($ticket->status?->value ?? 'open') }}">
                        {{ ['OPEN' => 'Abierto', 'PENDING' => 'Pendiente', 'RESOLVED' => 'Resuelto', 'CLOSED' => 'Cerrado'][$ticket->status?->value ?? ''] ?? $ticket->status }}
                    </span>
                </td>
                <td style="text-align: center;">{{ $ticket->responses_count ?? 0 }}</td>
                <td>{{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px; color: #666;">No tienes tickets asignados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        <p>Reporte generado automáticamente | {{ $agentName }}</p>
    </div>
</body>
</html>
