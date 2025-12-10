<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Rendimiento de Agentes - {{ $company->name ?? 'Empresa' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .summary-container { text-align: center; margin-bottom: 20px; }
        .summary-box { display: inline-block; width: 30%; text-align: center; padding: 15px; margin: 0 1%; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; }
        .summary-box .icon { font-size: 24px; margin-bottom: 5px; }
        .summary-box .number { font-size: 24px; font-weight: bold; color: #28a745; }
        .summary-box .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .summary-agents .number { color: #007bff; }
        .summary-avg .number { color: #17a2b8; }
        .summary-best .number { color: #ffc107; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #343a40; color: white; padding: 10px 8px; text-align: left; font-weight: 600; font-size: 10px; }
        td { padding: 10px 8px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        tr:nth-child(even) { background: #f8f9fa; }
        .agent-name { font-weight: bold; color: #333; }
        .agent-email { font-size: 9px; color: #666; }
        .metric { text-align: center; }
        .metric-highlight { font-weight: bold; color: #28a745; }
        .rate-bar { background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 3px; }
        .rate-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 4px; }
        .section-title { background: #f8f9fa; padding: 10px; margin: 20px 0 10px 0; border-left: 4px solid #28a745; font-size: 12px; font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #dee2e6; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>👥 Rendimiento de Agentes</h1>
        <p>{{ $company->name ?? 'Empresa' }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <div class="summary-container">
        <div class="summary-box summary-agents">
            <div class="icon">👤</div>
            <div class="number">{{ count($agents) }}</div>
            <div class="label">Agentes Activos</div>
        </div>
        <div class="summary-box summary-avg">
            <div class="icon">📊</div>
            <div class="number">{{ $summary['avg_tickets_per_agent'] ?? 0 }}</div>
            <div class="label">Promedio Tickets/Agente</div>
        </div>
        <div class="summary-box summary-best">
            <div class="icon">🏆</div>
            <div class="number">{{ $summary['best_agent'] ?? '-' }}</div>
            <div class="label">Mejor Agente del Mes</div>
        </div>
    </div>
    
    <div class="section-title">📋 Detalle por Agente</div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Agente</th>
                <th style="width: 12%;" class="metric">Asignados</th>
                <th style="width: 12%;" class="metric">Activos</th>
                <th style="width: 12%;" class="metric">Resueltos</th>
                <th style="width: 10%;" class="metric">Hoy</th>
                <th style="width: 15%;">Tasa Resolución</th>
                <th style="width: 14%;">Miembro Desde</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agents as $agent)
            @php
                $rate = $agent['resolution_rate'] ?? 0;
            @endphp
            <tr>
                <td>
                    <div class="agent-name">{{ $agent['name'] ?? 'N/A' }}</div>
                    <div class="agent-email">{{ $agent['email'] ?? '' }}</div>
                </td>
                <td class="metric">{{ $agent['assigned_tickets'] ?? 0 }}</td>
                <td class="metric">{{ $agent['active_tickets'] ?? 0 }}</td>
                <td class="metric metric-highlight">{{ $agent['resolved_tickets'] ?? 0 }}</td>
                <td class="metric">{{ $agent['resolved_today'] ?? 0 }}</td>
                <td>
                    <div style="display: flex; align-items: center;">
                        <span style="width: 35px; font-weight: bold;">{{ number_format($rate, 0) }}%</span>
                        <div class="rate-bar" style="flex: 1; margin-left: 5px;">
                            <div class="rate-fill" style="width: {{ min($rate, 100) }}%;"></div>
                        </div>
                    </div>
                </td>
                <td>{{ $agent['member_since'] ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 30px; color: #666;">No hay agentes registrados en esta empresa.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        <p>Reporte de Rendimiento de Agentes | {{ $company->name ?? 'Empresa' }} | Sistema de Helpdesk</p>
        <p style="margin-top: 5px; font-size: 8px;">Los datos mostrados corresponden al estado actual del sistema al momento de la generación.</p>
    </div>
</body>
</html>
