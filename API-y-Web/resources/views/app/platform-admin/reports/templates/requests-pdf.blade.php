@extends('layouts.pdf-report')

@section('title', 'Reporte de Solicitudes')
@section('report-title', 'Reporte de Solicitudes')

@section('report-meta')
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ count($requests) }} Solicitudes &bull; Gestión de Altas
@endsection

@section('content')
    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell">
                <span class="stat-number">{{ count($requests) }}</span>
                <span class="stat-label">Total</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $requests->where('status', 'pending')->count() }}</span>
                <span class="stat-label">Pendientes</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $requests->where('status', 'approved')->count() }}</span>
                <span class="stat-label">Aprobadas</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $requests->where('status', 'rejected')->count() }}</span>
                <span class="stat-label">Rechazadas</span>
            </td>
        </tr>
    </table>

    <!-- TABLE CONTENT -->
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Historial de Solicitudes</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">EMPRESA</th>
                    <th style="width: 20%;">EMAIL ADMIN</th>
                    <th style="width: 12%;">INDUSTRIA</th>
                    <th style="width: 10%;">ESTADO</th>
                    <th style="width: 10%;">SOLICITUD</th>
                    <th style="width: 10%;">REVISIÓN</th>
                    <th style="width: 18%;">REVISOR</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td>
                            <strong style="color: #111827;">{{ $request->company_name }}</strong>
                            @if($request->company_legal_name)
                                <div style="font-size: 9px; color: #6b7280; margin-top: 2px;">{{ $request->company_legal_name }}
                                </div>
                            @endif
                        </td>
                        <td style="color: #374151;">{{ $request->admin_email }}</td>
                        <td>{{ $request->industry->name ?? '-' }}</td>
                        <td>
                            @if($request->status === 'approved')
                                <span class="status-active">APROBADA</span>
                            @elseif($request->status === 'pending')
                                <span
                                    style="color: #d97706; font-weight: bold; font-size: 10px; text-transform: uppercase;">PENDIENTE</span>
                            @elseif($request->status === 'rejected')
                                <span class="status-suspended">RECHAZADA</span>
                            @else
                                <span class="status-default">{{ strtoupper($request->status) }}</span>
                            @endif
                        </td>
                        <td class="date-cell">{{ $request->created_at?->format('d/m/y') ?? '-' }}</td>
                        <td class="date-cell">{{ $request->reviewed_at?->format('d/m/y') ?? '-' }}</td>
                        <td style="font-size: 10px; color: #4b5563;">{{ $request->reviewer?->email ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                            No hay solicitudes registradas con estos filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection