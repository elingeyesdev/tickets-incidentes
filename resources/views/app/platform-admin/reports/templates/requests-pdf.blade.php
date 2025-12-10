<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Solicitudes de Empresa</title>
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
        .summary-box .stat-item {
            display: inline-block;
            margin-right: 25px;
        }
        .summary-box .stat-value {
            font-weight: bold;
            font-size: 14px;
            color: #000;
        }
        
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
            background-color: #000;
            color: white;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 10px;
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
        <h1>📋 Reporte de Solicitudes de Empresa</h1>
        <div class="subtitle">Sistema Helpdesk - Gestión de Solicitudes</div>
        <div class="meta">
            Generado: {{ $generatedAt->format('d/m/Y H:i:s') }} | 
            Filtro: {{ $status ? ucfirst($status) : 'Todas' }} |
            Total: {{ count($requests) }} solicitudes
        </div>
    </div>
    
    {{-- Summary --}}
    <div class="summary-box">
        <div class="title">📊 Resumen de Solicitudes</div>
        <span class="stat-item">
            <span class="stat-value">{{ count($requests) }}</span> Total
        </span>
        <span class="stat-item">
            <span class="stat-value">{{ $requests->where('status', 'pending')->count() }}</span> Pendientes
        </span>
        <span class="stat-item">
            <span class="stat-value">{{ $requests->where('status', 'approved')->count() }}</span> Aprobadas
        </span>
        <span class="stat-item">
            <span class="stat-value">{{ $requests->where('status', 'rejected')->count() }}</span> Rechazadas
        </span>
    </div>
    
    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 18%">Empresa</th>
                <th style="width: 15%">Email Admin</th>
                <th style="width: 12%">Nombre Admin</th>
                <th style="width: 10%">Industria</th>
                <th style="width: 10%">Estado</th>
                <th style="width: 10%">Solicitud</th>
                <th style="width: 10%">Revisión</th>
                <th style="width: 15%">Revisor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $request)
            <tr>
                <td>
                    <strong>{{ $request->company_name }}</strong>
                    @if($request->company_legal_name)
                    <br><span class="text-muted">{{ $request->company_legal_name }}</span>
                    @endif
                </td>
                <td>{{ $request->admin_email }}</td>
                <td>{{ $request->admin_name ?? 'N/A' }}</td>
                <td>{{ $request->industry_name ?? 'N/A' }}</td>
                <td>
                    @if($request->status === 'pending')
                        <span class="badge badge-warning">Pendiente</span>
                    @elseif($request->status === 'approved')
                        <span class="badge badge-success">Aprobada</span>
                    @elseif($request->status === 'rejected')
                        <span class="badge badge-danger">Rechazada</span>
                    @else
                        <span class="badge">{{ $request->status }}</span>
                    @endif
                </td>
                <td>{{ $request->created_at?->format('d/m/Y') ?? 'N/A' }}</td>
                <td>{{ $request->reviewed_at?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $request->reviewer?->email ?? '-' }}</td>
            </tr>
            @if($request->status === 'rejected' && $request->rejection_reason)
            <tr>
                <td colspan="8" style="background-color: #fff3cd; font-size: 10px;">
                    <strong>Motivo de rechazo:</strong> {{ $request->rejection_reason }}
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px; color: #6c757d;">
                    No se encontraron solicitudes con los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        <strong>Helpdesk Platform</strong> - Reporte de Solicitudes generado automáticamente<br>
        Este documento es confidencial y de uso interno.
    </div>
</body>
</html>
