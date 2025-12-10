<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Crecimiento de Plataforma</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', 'Helvetica', sans-serif; 
            font-size: 11px; 
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #000;
        }
        .header h1 {
            color: #000;
            font-size: 22px;
            margin-bottom: 5px;
            text-transform: uppercase;
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
        
        /* Summary Cards */
        .summary-grid {
            margin-bottom: 25px;
        }
        .summary-card {
            display: inline-block;
            width: 23%;
            margin-right: 2%;
            background: #f8f9fa;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            vertical-align: top;
            min-height: 80px;
        }
        .summary-card:last-child {
            margin-right: 0;
        }
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }
        .summary-card .label {
            font-size: 10px;
            color: #333;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        /* Section */
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #000;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 10px 12px;
            text-align: center;
        }
        th {
            background-color: #000;
            color: white;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .highlight {
            background-color: #dee2e6 !important;
            font-weight: bold;
        }
        
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
        <h1>📈 Reporte de Crecimiento</h1>
        <div class="subtitle">Sistema Helpdesk - Análisis de Plataforma</div>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }} | 
            Periodo: Últimos {{ $months }} meses
        </div>
    </div>
    
    {{-- Summary Cards --}}
    <div class="summary-grid">
        <div class="summary-card">
            <div class="value">{{ number_format($data['summary']['total_companies']) }}</div>
            <div class="label">Total Empresas</div>
        </div>
        <div class="summary-card">
            <div class="value">{{ number_format($data['summary']['total_users']) }}</div>
            <div class="label">Total Usuarios</div>
        </div>
        <div class="summary-card">
            <div class="value">{{ number_format($data['summary']['total_tickets']) }}</div>
            <div class="label">Total Tickets</div>
        </div>
        <div class="summary-card">
            <div class="value">{{ number_format($data['summary']['pending_requests']) }}</div>
            <div class="label">Solicitudes Pendientes</div>
        </div>
    </div>
    
    {{-- Growth in Period --}}
    <div class="section">
        <div class="section-title">🚀 Crecimiento en el Periodo ({{ $months }} meses)</div>
        <div class="summary-grid">
            <div class="summary-card" style="width: 30%;">
                <div class="value">+{{ number_format($data['summary']['new_companies_period']) }}</div>
                <div class="label">Nuevas Empresas</div>
            </div>
            <div class="summary-card" style="width: 30%;">
                <div class="value">+{{ number_format($data['summary']['new_users_period']) }}</div>
                <div class="label">Nuevos Usuarios</div>
            </div>
            <div class="summary-card" style="width: 30%;">
                <div class="value">+{{ number_format($data['summary']['new_tickets_period']) }}</div>
                <div class="label">Nuevos Tickets</div>
            </div>
        </div>
    </div>
    
    {{-- Monthly Data Table --}}
    <div class="section">
        <div class="section-title">📊 Detalle Mensual</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 35%">Mes</th>
                    <th style="width: 20%">Nuevas Empresas</th>
                    <th style="width: 20%">Nuevos Usuarios</th>
                    <th style="width: 25%">Nuevos Tickets</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['monthly'] as $month)
                <tr>
                    <td style="text-align: left; font-weight: bold;">{{ ucfirst($month['month_name']) }}</td>
                    <td>{{ number_format($month['companies']) }}</td>
                    <td>{{ number_format($month['users']) }}</td>
                    <td>{{ number_format($month['tickets']) }}</td>
                </tr>
                @endforeach
                {{-- Totals Row --}}
                <tr class="highlight">
                    <td style="text-align: left;"><strong>TOTAL PERIODO</strong></td>
                    <td><strong>{{ number_format($data['summary']['new_companies_period']) }}</strong></td>
                    <td><strong>{{ number_format($data['summary']['new_users_period']) }}</strong></td>
                    <td><strong>{{ number_format($data['summary']['new_tickets_period']) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <strong>Helpdesk Platform</strong> - Reporte de Crecimiento generado automáticamente<br>
        Este documento es confidencial y de uso interno.
    </div>
</body>
</html>
