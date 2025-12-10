<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Historial de Tickets</title>
    <style>
        /* Reset and base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', 'Helvetica', sans-serif; 
            font-size: 11px; 
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #17a2b8;
        }
        .header h1 {
            color: #17a2b8;
            font-size: 22px;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #6c757d;
            font-size: 12px;
        }
        .header .meta {
            color: #999;
            font-size: 10px;
            margin-top: 8px;
        }
        
        /* Summary box */
        .summary-box {
            background-color: #e7f5f8;
            border: 1px solid #17a2b8;
            border-radius: 5px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .summary-box .title {
            font-weight: bold;
            color: #17a2b8;
            margin-bottom: 8px;
        }
        .summary-box .stats {
            display: inline-block;
        }
        .summary-box .stat-item {
            display: inline-block;
            margin-right: 25px;
        }
        .summary-box .stat-value {
            font-weight: bold;
            color: #000;
            font-size: 14px;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #17a2b8;
            color: white;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-success { background-color: #28a745; color: white; }
        .badge-secondary { background-color: #6c757d; color: white; }
        
        /* Code */
        code {
            background-color: #e9ecef;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 10px;
        }
        
        /* Footer */
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎫 Historial de Tickets</h1>
        <div class="subtitle">Reporte de {{ $userName }}</div>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }} | 
            Filtro: {{ $status ? ucfirst(strtolower($status)) : 'Todos' }} |
            Total: {{ count($tickets) }} tickets
        </div>
    </div>
    
    {{-- Summary --}}
    <div class="summary-box">
        <div class="title">📊 Resumen</div>
        <div class="stats">
            <span class="stat-item">
                <span class="stat-value">{{ $summary['total'] }}</span> Total
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $summary['open'] }}</span> Abiertos
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $summary['pending'] }}</span> Pendientes
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $summary['resolved'] }}</span> Resueltos
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $summary['closed'] }}</span> Cerrados
            </span>
        </div>
    </div>
    
    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 12%">Código</th>
                <th style="width: 28%">Asunto</th>
                <th style="width: 15%">Empresa</th>
                <th style="width: 12%">Categoría</th>
                <th style="width: 10%">Prioridad</th>
                <th style="width: 10%">Estado</th>
                <th style="width: 13%">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td><code>{{ $ticket->ticket_code ?? 'N/A' }}</code></td>
                <td>{{ Str::limit($ticket->title, 45) }}</td>
                <td>{{ $ticket->company?->name ?? 'N/A' }}</td>
                <td>{{ $ticket->category?->name ?? 'Sin categoría' }}</td>
                <td>
                    @php
                        $priority = $ticket->priority?->value ?? $ticket->priority;
                    @endphp
                    @if($priority === 'HIGH')
                        <span class="badge badge-danger">Alta</span>
                    @elseif($priority === 'MEDIUM')
                        <span class="badge badge-warning">Media</span>
                    @else
                        <span class="badge badge-success">Baja</span>
                    @endif
                </td>
                <td>
                    @php
                        $status = $ticket->status?->value ?? $ticket->status;
                    @endphp
                    @if($status === 'OPEN')
                        <span class="badge badge-danger">Abierto</span>
                    @elseif($status === 'PENDING')
                        <span class="badge badge-warning">Pendiente</span>
                    @elseif($status === 'RESOLVED')
                        <span class="badge badge-info">Resuelto</span>
                    @else
                        <span class="badge badge-success">Cerrado</span>
                    @endif
                </td>
                <td>{{ $ticket->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #6c757d;">
                    No se encontraron tickets con los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        <strong>Helpdesk Platform</strong> - Reporte generado automáticamente<br>
        Este documento es para uso personal del usuario.
    </div>
</body>
</html>
