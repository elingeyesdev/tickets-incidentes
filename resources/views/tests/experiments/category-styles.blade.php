@extends('layouts.laboratory')

@section('title', 'Estilos de Categorías - Laboratorio')

@section('content_header', 'Experimento: Estilos de Categorías')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tests.index') }}">Laboratorio Visual</a></li>
    <li class="breadcrumb-item active">Estilos de Categorías</li>
@endsection

@section('content')

{{-- Info Alert --}}
<x-adminlte-callout theme="info" icon="fas fa-flask">
    <strong>Experimento: Estilos de Categorías</strong><br>
    Comparación de diferentes opciones visuales para mostrar categorías en tablas.
</x-adminlte-callout>

{{-- Current Topic: Category Badge Options --}}
<x-adminlte-card title="Simulación Real: Estilos de Categorías en Tabla de Artículos" theme="primary" icon="fas fa-tags" collapsible>
    <p class="text-muted mb-4">
        <i class="fas fa-info-circle"></i> Nuevas opciones que combinan <strong>icono + texto</strong> para mejorar UX.
        Cada sección muestra la simulación real con tus 3 artículos de ejemplo.
    </p>

    {{-- Opción 1: Icono + Texto Inline --}}
    <h5 class="mt-4 mb-3">
        <span class="badge badge-primary">Opción 1</span> Icono + Texto Inline
        <small class="text-muted">- Icono al lado del texto, muy claro</small>
    </h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 35%;">Título</th>
                    <th style="width: 15%;">Categoría</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 8%;">Vistas</th>
                    <th style="width: 12%;">Publicado</th>
                    <th style="width: 20%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Información nutricional de productos PIL</strong><br>
                        <small class="text-muted">Información nutricional de productos PIL</small>
                    </td>
                    <td class="text-nowrap">
                        <i class="fas fa-tools text-info"></i>
                        <small>Technical Support</small>
                    </td>
                    <td><span class="badge badge-success"><i class="fas fa-check-circle"></i> Publicado</span></td>
                    <td><span class="badge badge-light"><i class="fas fa-eye"></i> 0</span></td>
                    <td><small class="text-muted">18/11/2025, 20:06</small></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-warning"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i> Despublicar</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Preguntas frecuentes sobre productos PIL</strong><br>
                        <small class="text-muted"># Preguntas frecuentes sobre productos PIL ## ¿Qué son los productos PIL? Los ...</small>
                    </td>
                    <td class="text-nowrap">
                        <i class="fas fa-user-circle text-primary"></i>
                        <small>Account & Profile</small>
                    </td>
                    <td><span class="badge badge-secondary"><i class="fas fa-pencil-alt"></i> Borrador</span></td>
                    <td><span class="badge badge-light"><i class="fas fa-eye"></i> 0</span></td>
                    <td><small class="text-muted">N/A</small></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-warning"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-success"><i class="fas fa-paper-plane"></i> Publicar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Cómo reportar problemas con productos PIL</strong><br>
                        <small class="text-muted">Guía paso a paso para reportar problemas de calidad o defectos en nuestros produ...</small>
                    </td>
                    <td class="text-nowrap">
                        <i class="fas fa-shield-alt text-warning"></i>
                        <small>Security & Privacy</small>
                    </td>
                    <td><span class="badge badge-secondary"><i class="fas fa-pencil-alt"></i> Borrador</span></td>
                    <td><span class="badge badge-light"><i class="fas fa-eye"></i> 12</span></td>
                    <td><small class="text-muted">N/A</small></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-warning"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-success"><i class="fas fa-paper-plane"></i> Publicar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr class="my-4">

    {{-- Opción 2: Badge Pill --}}
    <h5 class="mt-4 mb-3">
        <span class="badge badge-primary">Opción 2</span> Badge Pill + Icono + Texto
        <small class="text-muted">- Redondeado, elegante y completo</small>
    </h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 35%;">Título</th>
                    <th style="width: 15%;">Categoría</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 8%;">Vistas</th>
                    <th style="width: 12%;">Publicado</th>
                    <th style="width: 20%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Información nutricional de productos PIL</strong><br>
                        <small class="text-muted">Información nutricional de productos PIL</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-pill badge-info">
                            <i class="fas fa-tools"></i> Soporte
                        </span>
                    </td>
                    <td><span class="badge badge-success"><i class="fas fa-check-circle"></i> Publicado</span></td>
                    <td><span class="badge badge-light"><i class="fas fa-eye"></i> 0</span></td>
                    <td><small class="text-muted">18/11/2025, 20:06</small></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-warning"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i> Despublicar</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Preguntas frecuentes sobre productos PIL</strong><br>
                        <small class="text-muted"># Preguntas frecuentes sobre productos PIL ## ¿Qué son los productos PIL? Los ...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-pill badge-primary">
                            <i class="fas fa-user-circle"></i> Cuenta
                        </span>
                    </td>
                    <td><span class="badge badge-secondary"><i class="fas fa-pencil-alt"></i> Borrador</span></td>
                    <td><span class="badge badge-light"><i class="fas fa-eye"></i> 0</span></td>
                    <td><small class="text-muted">N/A</small></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-warning"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-success"><i class="fas fa-paper-plane"></i> Publicar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Cómo reportar problemas con productos PIL</strong><br>
                        <small class="text-muted">Guía paso a paso para reportar problemas de calidad o defectos en nuestros produ...</small>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge badge-pill badge-warning">
                            <i class="fas fa-shield-alt"></i> Seguridad
                        </span>
                    </td>
                    <td><span class="badge badge-secondary"><i class="fas fa-pencil-alt"></i> Borrador</span></td>
                    <td><span class="badge badge-light"><i class="fas fa-eye"></i> 12</span></td>
                    <td><small class="text-muted">N/A</small></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-warning"><i class="fas fa-eye"></i> Ver</button>
                        <button class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar</button>
                        <button class="btn btn-sm btn-success"><i class="fas fa-paper-plane"></i> Publicar</button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <x-slot name="footerSlot">
        <a href="{{ route('tests.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Laboratorio
        </a>
    </x-slot>
</x-adminlte-card>

@endsection

@section('js')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
@endsection