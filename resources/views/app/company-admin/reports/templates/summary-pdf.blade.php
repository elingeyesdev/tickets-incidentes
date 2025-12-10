<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Operativo - {{ $company->name ?? 'Empresa' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; padding: 25px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .section { margin-bottom: 20px; }
        .section-title { background: #f8f9fa; padding: 10px 15px; margin-bottom: 15px; border-left: 4px solid #6f42c1; font-size: 13px; font-weight: bold; color: #333; }
        .kpi-grid { display: table; width: 100%; margin-bottom: 15px; }
        .kpi-row { display: table-row; }
        .kpi-box { display: table-cell; width: 25%; text-align: center; padding: 15px 10px; background: #fff; border: 1px solid #dee2e6; }
        .kpi-box:first-child { border-radius: 8px 0 0 8px; }
        .kpi-box:last-child { border-radius: 0 8px 8px 0; }
        .kpi-icon { font-size: 20px; margin-bottom: 8px; }
        .kpi-value { font-size: 24px; font-weight: bold; color: #6f42c1; }
        .kpi-label { font-size: 9px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .two-column { display: table; width: 100%; }
        .column { display: table-cell; width: 50%; vertical-align: top; padding: 0 10px; }
        .column:first-child { padding-left: 0; }
        .column:last-child { padding-right: 0; }
        .card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .card-title { font-size: 11px; font-weight: bold; color: #333; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .stat-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dotted #eee; }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { color: #666; }
        .stat-value { font-weight: bold; }
        .progress-item { margin-bottom: 10px; }
        .progress-label { display: flex; justify-content: space-between; margin-bottom: 3px; font-size: 9px; }
        .progress-bar { height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 5px; }
        .fill-open { background: #dc3545; }
        .fill-pending { background: #ffc107; }
        .fill-resolved { background: #28a745; }
        .fill-closed { background: #6c757d; }
        .fill-high { background: #dc3545; }
        .fill-medium { background: #ffc107; }
        .fill-low { background: #28a745; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #343a40; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #dee2e6; }
        tr:nth-child(even) { background: #f8f9fa; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #6f42c1; font-size: 9px; color: #666; text-align: center; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; }
        .badge-primary { background: #007bff; color: white; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📈 Resumen Operativo Ejecutivo</h1>
        <p>{{ $company->name ?? 'Empresa' }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <!-- KPIs Principales -->
    <div class="section">
        <div class="section-title">🎯 Indicadores Clave de Rendimiento (KPIs)</div>
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-box">
                    <div class="kpi-icon">📋</div>
                    <div class="kpi-value">{{ $kpis['total_tickets'] ?? 0 }}</div>
                    <div class="kpi-label">Total Tickets</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-icon">👥</div>
                    <div class="kpi-value">{{ $kpis['total_agents'] ?? 0 }}</div>
                    <div class="kpi-label">Agentes Activos</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-icon">📚</div>
                    <div class="kpi-value">{{ $kpis['total_articles'] ?? 0 }}</div>
                    <div class="kpi-label">Artículos Help Center</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-icon">📢</div>
                    <div class="kpi-value">{{ $kpis['total_announcements'] ?? 0 }}</div>
                    <div class="kpi-label">Anuncios</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Distribuciones -->
    <div class="section">
        <div class="section-title">📊 Distribución de Tickets</div>
        <div class="two-column">
            <div class="column">
                <div class="card">
                    <div class="card-title">Por Estado</div>
                    @php
                        $total = max(($distributions['by_status']['open'] ?? 0) + ($distributions['by_status']['pending'] ?? 0) + ($distributions['by_status']['resolved'] ?? 0) + ($distributions['by_status']['closed'] ?? 0), 1);
                    @endphp
                    <div class="progress-item">
                        <div class="progress-label"><span>Abiertos</span><span>{{ $distributions['by_status']['open'] ?? 0 }} ({{ round((($distributions['by_status']['open'] ?? 0) / $total) * 100) }}%)</span></div>
                        <div class="progress-bar"><div class="progress-fill fill-open" style="width: {{ (($distributions['by_status']['open'] ?? 0) / $total) * 100 }}%;"></div></div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label"><span>Pendientes</span><span>{{ $distributions['by_status']['pending'] ?? 0 }} ({{ round((($distributions['by_status']['pending'] ?? 0) / $total) * 100) }}%)</span></div>
                        <div class="progress-bar"><div class="progress-fill fill-pending" style="width: {{ (($distributions['by_status']['pending'] ?? 0) / $total) * 100 }}%;"></div></div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label"><span>Resueltos</span><span>{{ $distributions['by_status']['resolved'] ?? 0 }} ({{ round((($distributions['by_status']['resolved'] ?? 0) / $total) * 100) }}%)</span></div>
                        <div class="progress-bar"><div class="progress-fill fill-resolved" style="width: {{ (($distributions['by_status']['resolved'] ?? 0) / $total) * 100 }}%;"></div></div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label"><span>Cerrados</span><span>{{ $distributions['by_status']['closed'] ?? 0 }} ({{ round((($distributions['by_status']['closed'] ?? 0) / $total) * 100) }}%)</span></div>
                        <div class="progress-bar"><div class="progress-fill fill-closed" style="width: {{ (($distributions['by_status']['closed'] ?? 0) / $total) * 100 }}%;"></div></div>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="card">
                    <div class="card-title">Por Prioridad</div>
                    @php
                        $totalP = max(($distributions['by_priority']['high'] ?? 0) + ($distributions['by_priority']['medium'] ?? 0) + ($distributions['by_priority']['low'] ?? 0), 1);
                    @endphp
                    <div class="progress-item">
                        <div class="progress-label"><span>🔴 Alta</span><span>{{ $distributions['by_priority']['high'] ?? 0 }} ({{ round((($distributions['by_priority']['high'] ?? 0) / $totalP) * 100) }}%)</span></div>
                        <div class="progress-bar"><div class="progress-fill fill-high" style="width: {{ (($distributions['by_priority']['high'] ?? 0) / $totalP) * 100 }}%;"></div></div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label"><span>🟡 Media</span><span>{{ $distributions['by_priority']['medium'] ?? 0 }} ({{ round((($distributions['by_priority']['medium'] ?? 0) / $totalP) * 100) }}%)</span></div>
                        <div class="progress-bar"><div class="progress-fill fill-medium" style="width: {{ (($distributions['by_priority']['medium'] ?? 0) / $totalP) * 100 }}%;"></div></div>
                    </div>
                    <div class="progress-item">
                        <div class="progress-label"><span>🟢 Baja</span><span>{{ $distributions['by_priority']['low'] ?? 0 }} ({{ round((($distributions['by_priority']['low'] ?? 0) / $totalP) * 100) }}%)</span></div>
                        <div class="progress-bar"><div class="progress-fill fill-low" style="width: {{ (($distributions['by_priority']['low'] ?? 0) / $totalP) * 100 }}%;"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Categorías -->
    <div class="section">
        <div class="section-title">🏷️ Top 5 Categorías con Más Tickets</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 50%;">Categoría</th>
                    <th style="width: 20%;">Tickets</th>
                    <th style="width: 20%;">% del Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($distributions['top_categories'] ?? [] as $index => $category)
                <tr>
                    <td><span class="badge badge-primary">{{ $index + 1 }}</span></td>
                    <td>{{ $category['name'] ?? 'Sin categoría' }}</td>
                    <td>{{ $category['count'] ?? 0 }}</td>
                    <td>{{ number_format($category['percentage'] ?? 0, 1) }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #666;">No hay datos de categorías disponibles.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Configuración -->
    <div class="section">
        <div class="section-title">⚙️ Configuración del Sistema</div>
        <div class="two-column">
            <div class="column">
                <div class="card">
                    <div class="card-title">Categorías y Áreas</div>
                    <div class="stat-row">
                        <span class="stat-label">Total Categorías</span>
                        <span class="stat-value">{{ $config['total_categories'] ?? 0 }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Total Áreas</span>
                        <span class="stat-value">{{ $config['total_areas'] ?? 0 }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Áreas Activas</span>
                        <span class="stat-value" style="color: #28a745;">{{ $config['active_areas'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="card">
                    <div class="card-title">Help Center</div>
                    <div class="stat-row">
                        <span class="stat-label">Artículos Publicados</span>
                        <span class="stat-value">{{ $kpis['total_articles'] ?? 0 }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Categorías de Artículos</span>
                        <span class="stat-value">{{ $config['article_categories'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>Resumen Operativo Ejecutivo</strong> | {{ $company->name ?? 'Empresa' }}</p>
        <p style="margin-top: 5px;">Generado automáticamente por el Sistema de Helpdesk | {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
