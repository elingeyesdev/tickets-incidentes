<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empresa y Equipo - {{ $company->name ?? 'Empresa' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.5; }
        .header { background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 25px; margin-bottom: 25px; }
        .header h1 { font-size: 22px; margin-bottom: 5px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .section { margin-bottom: 25px; }
        .section-title { background: #f8f9fa; padding: 12px 15px; margin-bottom: 15px; border-left: 4px solid #17a2b8; font-size: 14px; font-weight: bold; color: #333; }
        .company-card { background: #fff; border: 2px solid #17a2b8; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .company-header { display: flex; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .company-logo { width: 80px; height: 80px; background: #f8f9fa; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 40px; }
        .company-info h2 { font-size: 18px; color: #333; margin-bottom: 5px; }
        .company-info p { font-size: 11px; color: #666; }
        .company-badge { display: inline-block; background: #17a2b8; color: white; padding: 3px 10px; border-radius: 15px; font-size: 9px; margin-top: 5px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; width: 50%; padding: 10px 15px; vertical-align: top; }
        .info-item { margin-bottom: 12px; }
        .info-label { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 3px; }
        .info-value { font-size: 11px; color: #333; font-weight: 500; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #343a40; color: white; padding: 10px 8px; text-align: left; font-weight: 600; }
        td { padding: 10px 8px; border-bottom: 1px solid #dee2e6; }
        tr:nth-child(even) { background: #f8f9fa; }
        .role-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: bold; }
        .role-admin { background: #ffc107; color: #333; }
        .role-agent { background: #17a2b8; color: white; }
        .member-name { font-weight: bold; color: #333; }
        .member-email { font-size: 9px; color: #666; }
        .stats-box { display: inline-block; background: #e7f5f7; padding: 8px 15px; border-radius: 8px; margin-right: 10px; text-align: center; }
        .stats-value { font-size: 18px; font-weight: bold; color: #17a2b8; }
        .stats-label { font-size: 9px; color: #666; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #17a2b8; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 Reporte de Empresa y Equipo</h1>
        <p>Información detallada de la organización | Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <!-- Información de la Empresa -->
    <div class="section">
        <div class="section-title">🏢 Información de la Empresa</div>
        <div class="company-card">
            <div class="company-header">
                <div class="company-logo">🏢</div>
                <div class="company-info">
                    <h2>{{ $company->name ?? 'Nombre de la Empresa' }}</h2>
                    <p>{{ $company->legal_name ?? $company->name ?? '' }}</p>
                    <span class="company-badge">{{ $company->industry ?? 'Industria' }}</span>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-cell">
                        <div class="info-item">
                            <div class="info-label">Nombre Comercial</div>
                            <div class="info-value">{{ $company->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Razón Social</div>
                            <div class="info-value">{{ $company->legal_name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Industria</div>
                            <div class="info-value">{{ $company->industry ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="info-cell">
                        <div class="info-item">
                            <div class="info-label">Email de Contacto</div>
                            <div class="info-value">{{ $company->contact_email ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Teléfono</div>
                            <div class="info-value">{{ $company->phone ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Fecha de Registro</div>
                            <div class="info-value">{{ $company->created_at ? $company->created_at->format('d/m/Y') : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            @if($company->address ?? null)
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                <div class="info-label">Dirección</div>
                <div class="info-value">{{ $company->address }}</div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Estadísticas del Equipo -->
    <div class="section">
        <div class="section-title">📊 Resumen del Equipo</div>
        <div style="text-align: center; margin-bottom: 20px;">
            <div class="stats-box">
                <div class="stats-value">{{ count($admins ?? []) }}</div>
                <div class="stats-label">Administradores</div>
            </div>
            <div class="stats-box">
                <div class="stats-value">{{ count($agents ?? []) }}</div>
                <div class="stats-label">Agentes</div>
            </div>
            <div class="stats-box">
                <div class="stats-value">{{ (count($admins ?? [])) + (count($agents ?? [])) }}</div>
                <div class="stats-label">Total Miembros</div>
            </div>
        </div>
    </div>
    
    <!-- Administradores -->
    @if(count($admins ?? []) > 0)
    <div class="section">
        <div class="section-title">👑 Administradores de Empresa</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Nombre</th>
                    <th style="width: 35%;">Email</th>
                    <th style="width: 15%;">Rol</th>
                    <th style="width: 10%;">Desde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $index => $admin)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="member-name">{{ $admin['name'] ?? 'N/A' }}</td>
                    <td class="member-email">{{ $admin['email'] ?? 'N/A' }}</td>
                    <td><span class="role-badge role-admin">Admin</span></td>
                    <td>{{ isset($admin['created_at']) ? \Carbon\Carbon::parse($admin['created_at'])->format('d/m/Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Agentes -->
    <div class="section">
        <div class="section-title">👥 Agentes de Soporte</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Nombre</th>
                    <th style="width: 30%;">Email</th>
                    <th style="width: 15%;">Tickets Asignados</th>
                    <th style="width: 10%;">Rol</th>
                    <th style="width: 10%;">Desde</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents ?? [] as $index => $agent)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="member-name">{{ $agent['name'] ?? 'N/A' }}</td>
                    <td class="member-email">{{ $agent['email'] ?? 'N/A' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $agent['assigned_tickets'] ?? 0 }}</td>
                    <td><span class="role-badge role-agent">Agente</span></td>
                    <td>{{ $agent['member_since'] ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">No hay agentes registrados en esta empresa.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p><strong>Reporte de Empresa y Equipo</strong> | {{ $company->name ?? 'Empresa' }}</p>
        <p style="margin-top: 5px;">Generado automáticamente por el Sistema de Helpdesk | {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
