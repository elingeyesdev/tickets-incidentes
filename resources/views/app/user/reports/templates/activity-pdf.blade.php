@extends('layouts.pdf-report')

@section('title', 'Resumen de Actividad')
@section('report-title', 'Resumen de Actividad del Usuario')

@section('report-meta')
    Usuario: {{ $userName }}<br>
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    Período de Análisis: Últimos {{ $months }} meses
@endsection

@section('content')

    <!-- KPIS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell" style="border-bottom-color: #17a2b8;">
                <span class="stat-number">{{ $data['summary']['total_tickets'] }}</span>
                <span class="stat-label">Total Tickets</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #28a745;">
                <span class="stat-number" style="color: #28a745;">{{ $data['summary']['resolution_rate'] }}%</span>
                <span class="stat-label">Tasa Resolución</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #ffc107;">
                <span class="stat-number"
                    style="color: #ffc107;">{{ $data['summary']['pending'] + $data['summary']['open'] }}</span>
                <span class="stat-label">Activos</span>
            </td>
            <td class="stat-card-cell" style="border-bottom-color: #dc3545;">
                <span class="stat-number" style="color: #dc3545;">{{ $data['summary']['priority_high'] }}</span>
                <span class="stat-label">Alta Prioridad</span>
            </td>
        </tr>
    </table>

    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Evolución Mensual</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 70%">MES</th>
                    <th style="width: 30%; text-align: center;">TICKETS CREADOS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['monthly'] as $month)
                    <tr>
                        <td>{{ ucfirst($month['month_name']) }}</td>
                        <td style="text-align: center;"><strong>{{ $month['tickets'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="content-wrapper" style="margin-top: 30px;">
        <div class="section-header">
            <h3 class="section-title">Distribución por Estado</h3>
        </div>

        @php $total = $data['summary']['total_tickets'] ?: 1; @endphp

        <table class="data-table">
            <thead>
                <tr>
                    <th>ESTADO</th>
                    <th style="text-align: center;">CANTIDAD</th>
                    <th style="text-align: center;">PORCENTAJE</th>
                    <th style="width: 40%">BARRA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="status-suspended">ABIERTO</span></td>
                    <td style="text-align: center;">{{ $data['summary']['open'] }}</td>
                    <td style="text-align: center;">{{ round(($data['summary']['open'] / $total) * 100) }}%</td>
                    <td>
                        <div style="background: #e9ecef; height: 10px; width: 100%; border-radius: 5px;">
                            <div
                                style="background: #dc3545; height: 10px; width: {{ round(($data['summary']['open'] / $total) * 100) }}%; border-radius: 5px;">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><span class="status-default" style="color: #ffc107;">PENDIENTE</span></td>
                    <td style="text-align: center;">{{ $data['summary']['pending'] }}</td>
                    <td style="text-align: center;">{{ round(($data['summary']['pending'] / $total) * 100) }}%</td>
                    <td>
                        <div style="background: #e9ecef; height: 10px; width: 100%; border-radius: 5px;">
                            <div
                                style="background: #ffc107; height: 10px; width: {{ round(($data['summary']['pending'] / $total) * 100) }}%; border-radius: 5px;">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><span class="status-active">RESUELTO</span></td>
                    <td style="text-align: center;">{{ $data['summary']['resolved'] }}</td>
                    <td style="text-align: center;">{{ round(($data['summary']['resolved'] / $total) * 100) }}%</td>
                    <td>
                        <div style="background: #e9ecef; height: 10px; width: 100%; border-radius: 5px;">
                            <div
                                style="background: #28a745; height: 10px; width: {{ round(($data['summary']['resolved'] / $total) * 100) }}%; border-radius: 5px;">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><span class="status-default">CERRADO</span></td>
                    <td style="text-align: center;">{{ $data['summary']['closed'] }}</td>
                    <td style="text-align: center;">{{ round(($data['summary']['closed'] / $total) * 100) }}%</td>
                    <td>
                        <div style="background: #e9ecef; height: 10px; width: 100%; border-radius: 5px;">
                            <div
                                style="background: #6c757d; height: 10px; width: {{ round(($data['summary']['closed'] / $total) * 100) }}%; border-radius: 5px;">
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

@endsection