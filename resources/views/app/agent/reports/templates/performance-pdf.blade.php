{{-- resources/views/app/agent/reports/templates/performance-pdf.blade.php --}}
@extends('layouts.pdf-report')

@section('title', 'Mi Rendimiento - ' . $agentName)

@section('report-title', 'Reporte de Rendimiento')

@section('report-meta')
    Agente: {{ $agentName }}<br>
    Generado: {{ now()->timezone('America/La_Paz')->format('d/m/Y H:i') }}
@endsection

@section('styles')
    .metric-table {
    width: calc(100% - 120px);
    margin: 0 60px 30px 60px;
    border-collapse: collapse;
    }
    .metric-row {
    border-bottom: 1px solid #e5e7eb;
    }
    .metric-row td {
    padding: 15px 10px;
    vertical-align: middle;
    }
    .metric-label {
    font-size: 12px;
    color: #374151;
    }
    .metric-value {
    font-size: 24px;
    font-weight: bold;
    color: #111827;
    text-align: right;
    }
    .metric-bar-container {
    width: 200px;
    height: 10px;
    background: #e5e7eb;
    border-radius: 5px;
    overflow: hidden;
    }
    .metric-bar {
    height: 100%;
    border-radius: 5px;
    }
    .bar-high { background: #dc2626; }
    .bar-medium { background: #d97706; }
    .bar-low { background: #059669; }
    .bar-success { background: #059669; }
    .highlight-box {
    background: #f0fdf4;
    border: 2px solid #059669;
    padding: 20px;
    text-align: center;
    margin: 0 60px 30px 60px;
    }
    .highlight-number {
    font-size: 48px;
    font-weight: bold;
    color: #059669;
    }
    .highlight-label {
    font-size: 14px;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 1px;
    }
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell" style="border-bottom-color: #2563eb;">
                <span class="stat-number">{{ $stats['total'] ?? 0 }}</span>
                <span class="stat-label">TOTAL ASIGNADOS</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #dc2626;">
                <span class="stat-number">
                    @if(($stats['open'] ?? 0) == 0)
                        <span style="color: #d1d5db; font-weight: normal;">0</span>
                    @else
                        {{ $stats['open'] }}
                    @endif
                </span>
                <span class="stat-label">ABIERTOS</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #d97706;">
                <span class="stat-number">
                    @if(($stats['pending'] ?? 0) == 0)
                        <span style="color: #d1d5db; font-weight: normal;">0</span>
                    @else
                        {{ $stats['pending'] }}
                    @endif
                </span>
                <span class="stat-label">PENDIENTES</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #059669;">
                <span class="stat-number">{{ ($stats['resolved'] ?? 0) + ($stats['closed'] ?? 0) }}</span>
                <span class="stat-label">RESUELTOS</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #7c3aed;">
                <span class="stat-number">{{ $stats['resolved_today'] ?? 0 }}</span>
                <span class="stat-label">HOY</span>
            </td>
        </tr>
    </table>

    {{-- Resolution Rate Highlight --}}
    <div class="highlight-box">
        <div class="highlight-number">{{ $stats['resolution_rate'] ?? 0 }}%</div>
        <div class="highlight-label">Tasa de Resolución</div>
    </div>

    {{-- Priority Distribution --}}
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Distribución por Prioridad (Tickets Activos)</h3>
        </div>

        @php
            $high = $stats['priority']['high'] ?? 0;
            $medium = $stats['priority']['medium'] ?? 0;
            $low = $stats['priority']['low'] ?? 0;
            $totalP = max($high + $medium + $low, 1);
        @endphp

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">PRIORIDAD</th>
                    <th style="width: 40%;">DISTRIBUCIÓN</th>
                    <th style="width: 15%; text-align: center;">CANTIDAD</th>
                    <th style="width: 15%; text-align: right;">PORCENTAJE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong style="color: #dc2626;">Alta</strong></td>
                    <td>
                        <div class="metric-bar-container">
                            <div class="metric-bar bar-high" style="width: {{ ($high / $totalP) * 100 }}%;"></div>
                        </div>
                    </td>
                    <td class="number-cell">
                        @if($high == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            {{ $high }}
                        @endif
                    </td>
                    <td class="date-cell">{{ round(($high / $totalP) * 100) }}%</td>
                </tr>
                <tr>
                    <td><strong style="color: #d97706;">Media</strong></td>
                    <td>
                        <div class="metric-bar-container">
                            <div class="metric-bar bar-medium" style="width: {{ ($medium / $totalP) * 100 }}%;"></div>
                        </div>
                    </td>
                    <td class="number-cell">
                        @if($medium == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            {{ $medium }}
                        @endif
                    </td>
                    <td class="date-cell">{{ round(($medium / $totalP) * 100) }}%</td>
                </tr>
                <tr>
                    <td><strong style="color: #059669;">Baja</strong></td>
                    <td>
                        <div class="metric-bar-container">
                            <div class="metric-bar bar-low" style="width: {{ ($low / $totalP) * 100 }}%;"></div>
                        </div>
                    </td>
                    <td class="number-cell">
                        @if($low == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            {{ $low }}
                        @endif
                    </td>
                    <td class="date-cell">{{ round(($low / $totalP) * 100) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection