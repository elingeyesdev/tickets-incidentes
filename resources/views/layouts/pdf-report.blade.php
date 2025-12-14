<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Reporte Helpdesk')</title>
    <style>
        @page {
            /* Márgenes ajustados: Footer baja al borde (30px) */
            margin: 85px 0px 30px 0px;
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
            font-weight: bold;
            /* TÍTULO REPORTE EN NEGRITA */
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

        /* FOOTER FIJO ABAJO Y CENTRADO */
        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            padding: 20px 60px 5px 60px;
            /* 20px arriba para bajarlo al centro visual */
            border-top: 1px solid #e5e7eb;
            background: #fafafa;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        .footer-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .footer-left {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            vertical-align: middle;
            /* Centrado vertical real */
        }

        .footer-right {
            font-size: 10px;
            color: #6b7280;
            text-align: right;
            vertical-align: middle;
            /* Centrado vertical real */
        }

        .page-number:after {
            content: counter(page);
        }

        /* STATISTICS & CONTENT HELPERS */
        .statistics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 30px 0;
            margin: 10px 60px 25px 60px; /* Margen superior reducido de 25px a 10px */
            width: calc(100% - 120px);
        }

        .stat-card-cell {
            padding: 15px 0;
            border-bottom-width: 2px;
            border-bottom-style: solid;
            width: 20%;
            vertical-align: top;
        }

        /* Colores por defecto para stats (pueden sobreescribirse si se desea) */
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
            padding: 10px 10px 15px 10px; /* Un poco más de aire abajo */
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
            background: transparent; /* Se va el fondo gris */
            border-bottom: 2px solid #111827; /* Línea negra sólida */
        }

        .data-table td {
            font-family: 'Helvetica', 'Arial', sans-serif;
            padding: 14px 10px;
            color: #374151;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }

        /* Helpers comunes */
        .number-cell { text-align: center; font-weight: bold; color: #111827; }
        .date-cell { color: #4b5563; font-size: 11px; text-align: right; }
        
        /* Estados en mayúsculas */
        .status-active { color: #059669; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .status-suspended { color: #dc2626; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .status-default { color: #4b5563; font-weight: bold; font-size: 10px; text-transform: uppercase; }

        /* Estilos extra definidos por las vistas hijas */
        @yield('styles')
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
                        <h2>@yield('report-title', 'Reporte del Sistema')</h2>
                        <div class="report-meta">
                            @yield('report-meta')
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

    <!-- CONTENIDO PRINCIPAL -->
    @yield('content')

</body>

</html>