@extends('layouts.pdf-report')

@section('title', 'Reporte de Crecimiento')
@section('report-title', 'Reporte de Crecimiento')

@section('report-meta')
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    Análisis Mensual &bull; Últimos {{ $months }} meses
@endsection

@section('content')
    <!-- STATISTICS -->
    <table class="statistics-table">
        <tr>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #059669;">+{{ number_format($data['summary']['new_companies_period']) }}</span>
                <span class="stat-label">Nuevas Empresas</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #2563eb;">+{{ number_format($data['summary']['new_users_period']) }}</span>
                <span class="stat-label">Nuevos Usuarios</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number" style="color: #ea580c;">+{{ number_format($data['summary']['new_tickets_period']) }}</span>
                <span class="stat-label">Nuevos Tickets</span>
            </td>
            <td class="stat-card-cell">
                <span class="stat-number">{{ number_format($data['summary']['total_users']) }}</span>
                <span class="stat-label">Usuarios Totales</span>
            </td>
        </tr>
    </table>

    <!-- TABLE CONTENT -->
    <div class="content-wrapper">
        <div class="section-header">
            <h3 class="section-title">Detalle Mensual</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">MES</th>
                    <th style="width: 20%; text-align: center;">EMPRESAS</th>
                    <th style="width: 20%; text-align: center;">USUARIOS</th>
                    <th style="width: 20%; text-align: center;">TICKETS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['monthly'] as $month)
                <tr>
                    <td><strong>{{ ucfirst($month['month_name']) }}</strong></td>
                    <td class="number-cell">
                        @if($month['companies'] == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            {{ number_format($month['companies']) }}
                        @endif
                    </td>
                    <td class="number-cell">
                        @if($month['users'] == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            {{ number_format($month['users']) }}
                        @endif
                    </td>
                    <td class="number-cell">
                        @if($month['tickets'] == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            {{ number_format($month['tickets']) }}
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr style="background-color: #f3f4f6; font-weight: bold; border-top: 2px solid #e5e7eb;">
                    <td style="padding-left: 10px;">TOTAL PERIODO</td>
                    <td class="number-cell">{{ number_format($data['summary']['new_companies_period']) }}</td>
                    <td class="number-cell">{{ number_format($data['summary']['new_users_period']) }}</td>
                    <td class="number-cell">{{ number_format($data['summary']['new_tickets_period']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection