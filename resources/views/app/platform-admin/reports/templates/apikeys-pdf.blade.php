@extends('layouts.pdf-report')

@section('title', 'Reporte de API Keys')
@section('report-title', 'Reporte de API Keys')

@section('report-meta')
    Generado el: {{ $generatedAt->timezone('America/La_Paz')->format('d/m/Y H:i') }}<br>
    {{ count($apiKeys) }} Credenciales &bull; Integraciones y Seguridad
@endsection

@section('content')


    <!-- TABLE CONTENT -->
    <div class="content-wrapper" style="margin-top: 30px;">
        <div class="section-header">
            <h3 class="section-title">Listado de Credenciales</h3>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">NOMBRE</th>
                    <th style="width: 15%;">KEY (MASKED)</th>
                    <th style="width: 18%;">EMPRESA</th>
                    <th style="width: 10%;">TIPO</th>
                    <th style="width: 10%;">ESTADO</th>
                    <th style="width: 8%; text-align: center;">USO</th>
                    <th style="width: 12%; text-align: right;">ÚLTIMO USO</th>
                    <th style="width: 12%; text-align: right;">CREACIÓN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($apiKeys as $apiKey)
                <tr>
                    <td>
                        <strong style="color: #111827;">{{ $apiKey->name }}</strong>
                    </td>
                    <td>
                        <span style="font-family: monospace; color: #4b5563; font-size: 9px; background: #f3f4f6; padding: 2px 4px; border-radius: 3px;">
                            {{ $apiKey->masked_key }}
                        </span>
                    </td>
                    <td>{{ $apiKey->company->name ?? '-' }}</td>
                    <td>
                        @if($apiKey->type === 'production')
                            <span style="color: #059669; font-weight: bold; font-size: 10px;">PROD</span>
                        @elseif($apiKey->type === 'development')
                            <span style="color: #3b82f6; font-weight: bold; font-size: 10px;">DEV</span>
                        @else
                            <span style="font-weight: bold; font-size: 10px;">{{ strtoupper($apiKey->type) }}</span>
                        @endif
                    </td>
                    <td>
                        @if(!$apiKey->is_active)
                            <span class="status-suspended">REVOCADA</span>
                        @elseif($apiKey->isExpired())
                            <span style="color: #d97706; font-weight: bold; font-size: 10px; text-transform: uppercase;">EXPIRADA</span>
                        @else
                            <span class="status-active">ACTIVA</span>
                        @endif
                    </td>
                    <td class="number-cell">
                        @if($apiKey->usage_count == 0)
                            <span style="color: #d1d5db; font-weight: normal;">0</span>
                        @else
                            {{ number_format($apiKey->usage_count) }}
                        @endif
                    </td>
                    <td class="date-cell">{{ $apiKey->last_used_at?->format('d/m/y H:i') ?? '-' }}</td>
                    <td class="date-cell">{{ $apiKey->created_at->format('d/m/y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">
                        No hay API Keys registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
