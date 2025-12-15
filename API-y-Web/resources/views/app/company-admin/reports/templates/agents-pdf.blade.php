@extends('layouts.pdf-report')

@section('title', 'Rendimiento de Agentes - ' . ($company->name ?? 'Empresa'))
@section('report-title', 'Rendimiento de Agentes')

@section('report-meta')
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ $company->name ?? 'Empresa' }} &bull; {{ count($agents) }} Agentes
@endsection

@section('content')
    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell">
                <span class="stat-number">{{ $summary['total_agents'] ?? count($agents) }}</span>
                <span class="stat-label">Total Agentes</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #2563eb;">{{ $summary['avg_tickets_per_agent'] ?? 0 }}</span>
                <span class="stat-label">Promedio Tickets</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #059669;">{{ $summary['best_agent'] ?? '-' }}</span>
                <span class="stat-label">Mejor Desempeño</span>
            </td>
        </tr>
    </table>

    <!-- TABLE CONTENT -->
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Detalle por Agente</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">AGENTE</th>
                    <th style="width: 20%;">EMAIL</th>
                    <th style="width: 10%; text-align: center;">ASIGNADOS</th>
                    <th style="width: 10%; text-align: center;">ACTIVOS</th>
                    <th style="width: 10%; text-align: center;">RESUELTOS</th>
                    <th style="width: 10%; text-align: center;">HOY</th>
                    <th style="width: 8%; text-align: center;">TASA</th>
                    <th style="width: 7%; text-align: right;">DESDE</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                    @php
                        $rate = ($agent['assigned_tickets'] ?? 0) > 0
                            ? round((($agent['resolved_tickets'] ?? 0) / ($agent['assigned_tickets'] ?? 1)) * 100)
                            : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong style="color: #111827;">{{ $agent['name'] ?? '-' }}</strong>
                        </td>
                        <td style="font-size: 10px; color: #374151;">{{ $agent['email'] ?? '-' }}</td>
                        <td class="number-cell">
                            @if(($agent['assigned_tickets'] ?? 0) == 0)
                                <span style="color: #d1d5db; font-weight: normal;">0</span>
                            @else
                                {{ $agent['assigned_tickets'] }}
                            @endif
                        </td>
                        <td class="number-cell">
                            @if(($agent['active_tickets'] ?? 0) == 0)
                                <span style="color: #d1d5db; font-weight: normal;">0</span>
                            @else
                                <span style="color: #d97706;">{{ $agent['active_tickets'] }}</span>
                            @endif
                        </td>
                        <td class="number-cell">
                            @if(($agent['resolved_tickets'] ?? 0) == 0)
                                <span style="color: #d1d5db; font-weight: normal;">0</span>
                            @else
                                <span style="color: #059669;">{{ $agent['resolved_tickets'] }}</span>
                            @endif
                        </td>
                        <td class="number-cell">
                            @if(($agent['resolved_today'] ?? 0) == 0)
                                <span style="color: #d1d5db; font-weight: normal;">0</span>
                            @else
                                {{ $agent['resolved_today'] }}
                            @endif
                        </td>
                        <td class="number-cell">
                            @if($rate >= 70)
                                <span style="color: #059669; font-weight: bold;">{{ $rate }}%</span>
                            @elseif($rate >= 40)
                                <span style="color: #d97706; font-weight: bold;">{{ $rate }}%</span>
                            @else
                                <span style="color: #dc2626; font-weight: bold;">{{ $rate }}%</span>
                            @endif
                        </td>
                        <td class="date-cell">{{ $agent['member_since'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">
                            No hay agentes registrados en esta empresa.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection