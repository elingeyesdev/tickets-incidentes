<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resumen de Actividad</title>
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
            border-bottom: 3px solid #28a745;
        }
        .header h1 {
            color: #28a745;
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
            background-color: #e8f5e9;
            border: 1px solid #28a745;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-box .title {
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        /* Stats grid */
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-grid td {
            padding: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
            width: 25%;
        }
        .stats-grid .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .stats-grid .stat-label {
            font-size: 10px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        /* Section title */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #495057;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #28a745;
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
            background-color: #28a745;
            color: white;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        /* Progress bar (simplified for PDF) */
        .progress-container {
            margin: 10px 0;
        }
        .progress-label {
            display: inline-block;
            width: 100px;
            font-size: 11px;
        }
        .progress-bar-bg {
            display: inline-block;
            width: 200px;
            height: 15px;
            background-color: #e9ecef;
            border-radius: 3px;
            vertical-align: middle;
        }
        .progress-bar-fill {
            height: 15px;
            border-radius: 3px;
        }
        .progress-value {
            display: inline-block;
            width: 50px;
            text-align: right;
            font-weight: bold;
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
        <h1>📈 Resumen de Actividad</h1>
        <div class="subtitle">Reporte de {{ $userName }}</div>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }} | 
            Período: Últimos {{ $months }} meses
        </div>
    </div>
    
    {{-- Summary Stats --}}
    <div class="summary-box">
        <div class="title">📊 Estadísticas Generales</div>
        <table class="stats-grid">
            <tr>
                <td>
                    <div class="stat-value">{{ $data['summary']['total_tickets'] }}</div>
                    <div class="stat-label">Total Tickets</div>
                </td>
                <td>
                    <div class="stat-value">{{ $data['summary']['resolution_rate'] }}%</div>
                    <div class="stat-label">Tasa Resolución</div>
                </td>
                <td>
                    <div class="stat-value">{{ $data['summary']['open'] + $data['summary']['pending'] }}</div>
                    <div class="stat-label">Activos</div>
                </td>
                <td>
                    <div class="stat-value">{{ $data['summary']['tickets_this_period'] }}</div>
                    <div class="stat-label">Este Período</div>
                </td>
            </tr>
        </table>
    </div>
    
    {{-- Status Distribution --}}
    <div class="section-title">📋 Distribución por Estado</div>
    <table>
        <thead>
            <tr>
                <th>Estado</th>
                <th>Cantidad</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total = $data['summary']['total_tickets'] ?: 1;
            @endphp
            <tr>
                <td>🔴 Abiertos</td>
                <td>{{ $data['summary']['open'] }}</td>
                <td>{{ round(($data['summary']['open'] / $total) * 100) }}%</td>
            </tr>
            <tr>
                <td>🟡 Pendientes</td>
                <td>{{ $data['summary']['pending'] }}</td>
                <td>{{ round(($data['summary']['pending'] / $total) * 100) }}%</td>
            </tr>
            <tr>
                <td>🔵 Resueltos</td>
                <td>{{ $data['summary']['resolved'] }}</td>
                <td>{{ round(($data['summary']['resolved'] / $total) * 100) }}%</td>
            </tr>
            <tr>
                <td>🟢 Cerrados</td>
                <td>{{ $data['summary']['closed'] }}</td>
                <td>{{ round(($data['summary']['closed'] / $total) * 100) }}%</td>
            </tr>
        </tbody>
    </table>
    
    {{-- Priority Distribution --}}
    <div class="section-title">🔥 Distribución por Prioridad</div>
    <table>
        <thead>
            <tr>
                <th>Prioridad</th>
                <th>Cantidad</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>⚠️ Alta</td>
                <td>{{ $data['summary']['priority_high'] }}</td>
                <td>{{ $total > 0 ? round(($data['summary']['priority_high'] / $total) * 100) : 0 }}%</td>
            </tr>
            <tr>
                <td>🔶 Media</td>
                <td>{{ $data['summary']['priority_medium'] }}</td>
                <td>{{ $total > 0 ? round(($data['summary']['priority_medium'] / $total) * 100) : 0 }}%</td>
            </tr>
            <tr>
                <td>✅ Baja</td>
                <td>{{ $data['summary']['priority_low'] }}</td>
                <td>{{ $total > 0 ? round(($data['summary']['priority_low'] / $total) * 100) : 0 }}%</td>
            </tr>
        </tbody>
    </table>
    
    {{-- Monthly Trend --}}
    <div class="section-title">📅 Actividad Mensual</div>
    <table>
        <thead>
            <tr>
                <th>Mes</th>
                <th>Tickets Creados</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['monthly'] as $month)
            <tr>
                <td>{{ $month['month_name'] }}</td>
                <td>{{ $month['tickets'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <strong>Helpdesk Platform</strong> - Reporte generado automáticamente<br>
        Este documento es para uso personal del usuario.
    </div>
</body>
</html>
