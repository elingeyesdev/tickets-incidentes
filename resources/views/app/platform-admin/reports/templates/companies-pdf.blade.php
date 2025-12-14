@extends('layouts.pdf-report')

@section('title', 'Reporte de Empresas')

@section('report-title', 'Reporte de Empresas')

@section('report-meta')
    @php
        $statusMap = [
            'active' => 'Activas',
            'suspended' => 'Suspendidas',
            '' => 'Todos'
        ];
        $filterLabel = $statusMap[$status] ?? ucfirst($status ?? 'Todos');
    @endphp
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ count($companies) }} registros &bull; Filtros: {{ $filterLabel }}
@endsection

@section('content')
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
                    <th style="text-align: center;">AGENTES</th>
                    <th style="text-align: center;">TICKETS</th>
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
                    <td class="number-cell">
                        @if($company->agents_count == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            <span style="color: #111827;">{{ $company->agents_count }}</span>
                        @endif
                    </td>
                    <td class="number-cell">
                        @if($company->tickets_count == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            <span style="color: #111827;">{{ $company->tickets_count }}</span>
                        @endif
                    </td>
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
@endsection