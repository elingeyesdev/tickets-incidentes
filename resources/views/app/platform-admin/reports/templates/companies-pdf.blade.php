<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Empresas</title>
    <style>
        /* Reset and base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', 'Helvetica', sans-serif; 
            font-size: 11px; 
            color: #333;
            line-height: 1.4;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #007bff;
        }
        .header h1 {
            color: #007bff;
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
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .summary-box .title {
            font-weight: bold;
            color: #495057;
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
            color: #007bff;
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
            background-color: #007bff;
            color: white;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #e9ecef;
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
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        /* Code style */
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
        
        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Reporte de Empresas</h1>
        <div class="subtitle">Sistema Helpdesk - Platform Admin</div>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }} | 
            Filtro: {{ $status ? ucfirst($status) : 'Todas' }} |
            Total: {{ count($companies) }} empresas
        </div>
    </div>
    
    {{-- Summary --}}
    <div class="summary-box">
        <div class="title">📈 Resumen</div>
        <div class="stats">
            <span class="stat-item">
                <span class="stat-value">{{ count($companies) }}</span> Total
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $companies->where('status', 'active')->count() }}</span> Activas
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $companies->where('status', 'suspended')->count() }}</span> Suspendidas
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $companies->sum('agents_count') }}</span> Total Agentes
            </span>
            <span class="stat-item">
                <span class="stat-value">{{ $companies->sum('tickets_count') }}</span> Total Tickets
            </span>
        </div>
    </div>
    
    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Código</th>
                <th style="width: 22%">Empresa</th>
                <th style="width: 18%">Email Soporte</th>
                <th style="width: 15%">Industria</th>
                <th style="width: 10%">Estado</th>
                <th style="width: 8%">Agentes</th>
                <th style="width: 8%">Tickets</th>
                <th style="width: 9%">Creación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
            <tr>
                <td><code>{{ $company->company_code ?? 'N/A' }}</code></td>
                <td>
                    <strong>{{ $company->name }}</strong>
                    @if($company->legal_name)
                    <br><small style="color: #6c757d;">{{ $company->legal_name }}</small>
                    @endif
                </td>
                <td>{{ $company->support_email ?? 'N/A' }}</td>
                <td>{{ $company->industry?->name ?? 'N/A' }}</td>
                <td>
                    @if($company->status === 'active')
                        <span class="badge badge-success">Activa</span>
                    @elseif($company->status === 'suspended')
                        <span class="badge badge-warning">Suspendida</span>
                    @else
                        <span class="badge badge-secondary">{{ $company->status ?? 'N/A' }}</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $company->agents_count ?? 0 }}</td>
                <td style="text-align: center;">{{ $company->tickets_count ?? 0 }}</td>
                <td>{{ $company->created_at?->format('d/m/Y') ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px; color: #6c757d;">
                    No se encontraron empresas con los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        <strong>Helpdesk Platform</strong> - Reporte generado automáticamente<br>
        Este documento es confidencial y de uso interno.
    </div>
</body>
</html>
