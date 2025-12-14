<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Empresas</title>
    <style>
        @page {
            /* Márgenes ajustados para subir el header y la línea divisora */
            margin: 85px 0px 60px 0px;
        }

        body {
            /* FORZAR HELVETICA */
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: #fff;
            color: #1a1a1a;
            font-size: 12px;
        }

        /* HEADER FIJO COMPACTO */
        header {
            position: fixed;
            top: -85px;
            left: 0;
            right: 0;
            height: 60px;
            /* Reducido de 75px a 60px -> LA LÍNEA SUBE */
            padding: 10px 60px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
            z-index: 1000;
        }

        /* TABLA HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-section-cell {
            vertical-align: middle;
        }

        .report-info-cell {
            text-align: right;
            vertical-align: middle;
        }

        .company-info h1 {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 2px 0;
            letter-spacing: -0.3px;
        }

        .company-info p {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #6b7280;
            margin: 0;
            font-weight: 400;
        }

        .report-info h2 {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 20px;
            font-weight: normal;
            color: #111827;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }

        .report-meta {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #6b7280;
            line-height: 1.4;
        }

        /* FOOTER FIJO */
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 40px;
            padding: 20px 60px;
            border-top: 1px solid #e5e7eb;
            background: #fafafa;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        .footer-table {
            width: 100%;
        }

        .footer-left {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .footer-right {
            font-size: 10px;
            color: #6b7280;
            text-align: right;
        }

        .page-number:after {
            content: counter(page);
        }

        /* STATISTICS */
        .statistics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 30px 0;
            margin: 25px 60px;
            width: calc(100% - 120px);
        }

        .stat-card-cell {
            padding: 15px 0;
            border-bottom-width: 2px;
            border-bottom-style: solid;
            width: 20%;
            vertical-align: top;
        }

        .stat-card-cell:nth-child(1) {
            border-bottom-color: #2563eb;
        }

        .stat-card-cell:nth-child(2) {
            border-bottom-color: #059669;
        }

        .stat-card-cell:nth-child(3) {
            border-bottom-color: #dc2626;
        }

        .stat-card-cell:nth-child(4) {
            border-bottom-color: #7c3aed;
        }

        .stat-card-cell:nth-child(5) {
            border-bottom-color: #ea580c;
        }

        .stat-number {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 30px;
            font-weight: normal;
            color: #111827;
            display: block;
            margin-bottom: 5px;
            letter-spacing: -1px;
        }

        .stat-label {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: bold;
            display: block;
        }

        /* TABLE SECTION */
        .content-wrapper {
            padding: 0 60px;
        }

        .section-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .section-title {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 15px;
            font-weight: bold;
            color: #111827;
            letter-spacing: -0.2px;
            margin: 0;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .data-table th {
            font-family: 'Helvetica', 'Arial', sans-serif;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
            background: #f9fafb;
            border-bottom: 1px solid #d1d5db;
        }

        .data-table td {
            font-family: 'Helvetica', 'Arial', sans-serif;
            padding: 14px 10px;
            color: #374151;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }

        .company-cell {
            font-weight: bold;
            color: #111827;
            font-size: 12.5px;
            display: block;
        }

        .subtitle {
            font-size: 11px;
            color: #6b7280;
            font-weight: 400;
            margin-top: 3px;
            display: block;
        }

        .status-active {
            color: #059669;
            font-weight: bold;
            font-size: 11px;
        }

        .status-suspended {
            color: #dc2626;
            font-weight: bold;
            font-size: 11px;
        }

        .status-default {
            color: #4b5563;
            font-weight: bold;
            font-size: 11px;
        }

        .number-cell {
            text-align: center;
            font-weight: bold;
            color: #111827;
        }

        .date-cell {
            color: #4b5563;
            font-size: 11px;
            text-align: right;
        }
    </style>
</head>

<body>

    <!-- HEADER FIJO -->
    <header>
        <table class="header-table">
            <tr>
                <td class="logo-section-cell">
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="padding-right: 15px;">
                                <img src="{{ public_path('img/helpdesklogo.png') }}" style="width: 50px; height: auto;">
                            </td>
                            <td>
                                <div class="company-info">
                                    <h1>Helpdesk Centro de Soporte</h1>
                                    <p>Sistema de Registro de incidentes</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="report-info-cell">
                    <div class="report-info">
                        <h2>Reporte de Empresas</h2>
                        <div class="report-meta">
                            Generado: {{ $generatedAt->format('d/m/Y H:i') }}<br>
                            {{ count($companies) }} registros &bull; {{ $status ? ucfirst($status) : 'Global' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <!-- FOOTER FIJO -->
    <footer>
        <table class="footer-table">
            <tr>
                <td class="footer-left">Documento Confidencial</td>
                <td class="footer-right">Página <span class="page-number"></span> &bull; Helpdesk Platform</td>
            </tr>
        </table>
    </footer>

    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell">
                <span class="stat-number">{{ count($companies) }}</span>
                <span class="stat-label">Total</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $companies->where('status', 'active')->count() }}</span>
                <span class="stat-label">Activas</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $companies->where('status', 'suspended')->count() }}</span>
                <span class="stat-label">Suspendidas</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $companies->sum('agents_count') }}</span>
                <span class="stat-label">Agentes</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $companies->sum('tickets_count') }}</span>
                <span class="stat-label">Tickets</span>
            </td>
        </tr>
    </table>

    <!-- TABLE CONTENT -->
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Listado Detallado de Empresas</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Empresa</th>
                    <th>Email de Contacto</th>
                    <th>Industria</th>
                    <th>Estado</th>
                    <th style="text-align: center;">AGS</th>
                    <th style="text-align: center;">TKS</th>
                    <th style="text-align: right;">Fecha Registro</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td style="font-family: monospace; color: #555;">{{ $company->company_code ?? '-' }}</td>
                        <td>
                            <div class="company-cell">{{ $company->name }}</div>
                            @if($company->legal_name)
                                <div class="subtitle">{{ $company->legal_name }}</div>
                            @endif
                        </td>
                        <td style="color: #4b5563;">{{ $company->support_email ?? '-' }}</td>
                        <td>{{ $company->industry?->name ?? 'General' }}</td>
                        <td>
                            @if($company->status === 'active')
                                <span class="status-active">Activa</span>
                            @elseif($company->status === 'suspended')
                                <span class="status-suspended">Suspendida</span>
                            @else
                                <span class="status-default">{{ ucfirst($company->status) }}</span>
                            @endif
                        </td>
                        <td class="number-cell">{{ $company->agents_count }}</td>
                        <td class="number-cell">{{ $company->tickets_count }}</td>
                        <td class="date-cell">{{ $company->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">
                            No hay empresas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>

</html>