<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Rendimiento - {{ $agentName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 25px; margin-bottom: 25px; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .section { margin-bottom: 25px; }
        .section-title { background: #f8f9fa; padding: 10px 15px; margin-bottom: 15px; border-left: 4px solid #28a745; font-size: 13px; font-weight: bold; }
        .kpi-grid { display: table; width: 100%; }
        .kpi-box { display: table-cell; width: 25%; text-align: center; padding: 15px; background: #fff; border: 1px solid #dee2e6; }
        .kpi-icon { font-size: 24px; margin-bottom: 8px; }
        .kpi-value { font-size: 28px; font-weight: bold; color: #28a745; }
        .kpi-label { font-size: 10px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 15px; }
        .card-title { font-size: 12px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .stat-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dotted #eee; }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { color: #666; }
        .stat-value { font-weight: bold; font-size: 14px; }
        .progress-item { margin-bottom: 12px; }
        .progress-label { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .progress-bar { height: 12px; background: #e9ecef; border-radius: 6px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 6px; }
        .fill-high { background: #dc3545; }
        .fill-medium { background: #ffc107; }
        .fill-low { background: #28a745; }
        .rate-box { text-align: center; padding: 20px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 10px; }
        .rate-value { font-size: 48px; font-weight: bold; }
        .rate-label { font-size: 12px; opacity: 0.9; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #28a745; font-size: 9px; color: #666; text-align: center; }
        .two-col { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding: 0 10px; }
        .col:first-child { padding-left: 0; }
        .col:last-child { padding-right: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Mi Rendimiento</h1>
        <p>{{ $agentName }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <div class="section">
        <div class="section-title">Indicadores Clave</div>
        <div class="kpi-grid">
            <div class="kpi-box">
                <div class="kpi-value">{{ $stats['total'] ?? 0 }}</div>
                <div class="kpi-label">Total Asignados</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-value" style="color: #dc3545;">{{ $stats['open'] ?? 0 }}</div>
                <div class="kpi-label">Abiertos</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-value" style="color: #ffc107;">{{ $stats['pending'] ?? 0 }}</div>
                <div class="kpi-label">Pendientes</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-value" style="color: #28a745;">{{ ($stats['resolved'] ?? 0) + ($stats['closed'] ?? 0) }}</div>
                <div class="kpi-label">Resueltos</div>
            </div>
        </div>
    </div>
    
    <div class="two-col">
        <div class="col">
            <div class="section">
                <div class="section-title">Tasa de Resolución</div>
                <div class="rate-box">
                    <div class="rate-value">{{ $stats['resolution_rate'] ?? 0 }}%</div>
                    <div class="rate-label">Tickets Resueltos / Total</div>
                </div>
                <div class="card" style="margin-top: 15px;">
                    <div class="stat-row">
                        <span class="stat-label">Resueltos Hoy</span>
                        <span class="stat-value" style="color: #28a745;">{{ $stats['resolved_today'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="section">
                <div class="section-title">Distribución por Prioridad</div>
                <div class="card">
                    @php
                        $totalP = max(($stats['priority']['high'] ?? 0) + ($stats['priority']['medium'] ?? 0) + ($stats['priority']['low'] ?? 0), 1);
                    @endphp
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Alta</span>
                            <span>{{ $stats['priority']['high'] ?? 0 }} ({{ round((($stats['priority']['high'] ?? 0) / $totalP) * 100) }}%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill fill-high" style="width: {{ (($stats['priority']['high'] ?? 0) / $totalP) * 100 }}%;"></div>
                        </div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Media</span>
                            <span>{{ $stats['priority']['medium'] ?? 0 }} ({{ round((($stats['priority']['medium'] ?? 0) / $totalP) * 100) }}%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill fill-medium" style="width: {{ (($stats['priority']['medium'] ?? 0) / $totalP) * 100 }}%;"></div>
                        </div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Baja</span>
                            <span>{{ $stats['priority']['low'] ?? 0 }} ({{ round((($stats['priority']['low'] ?? 0) / $totalP) * 100) }}%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill fill-low" style="width: {{ (($stats['priority']['low'] ?? 0) / $totalP) * 100 }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>Reporte de Rendimiento Personal</strong> | {{ $agentName }}</p>
        <p style="margin-top: 5px;">Generado automáticamente por el Sistema de Helpdesk | {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
