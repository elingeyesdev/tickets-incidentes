@extends('layouts.laboratory')

@section('title', 'Laboratorio de Comunicación Visual')

@section('content_header', 'Laboratorio de Comunicación Visual')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Laboratorio Visual</li>
@endsection

@section('content')

{{-- Info Alert --}}
<x-adminlte-callout theme="info" icon="fas fa-flask">
    <strong>Bienvenido al Laboratorio Visual</strong><br>
    Este es un espacio donde evaluamos ejemplos visuales de componentes, diseños y opciones antes de implementarlos en producción.
    Selecciona un experimento para ver las diferentes propuestas.
</x-adminlte-callout>

{{-- Experiments Catalog --}}
<div class="row">
    {{-- Experiment 1: Category Styles --}}
    <div class="col-md-6">
        <x-adminlte-card theme="primary" icon="fas fa-tags" title="Estilos de Categorías" collapsible>
            <p class="text-muted">
                Diferentes opciones visuales para mostrar categorías en tablas (iconos, badges, pills).
            </p>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Creado: Nov 2025
                </small>
                <a href="{{ route('tests.category-styles') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> Ver Experimento
                </a>
            </div>
        </x-adminlte-card>
    </div>

    {{-- Experiment 2: Announcements Table View --}}
    <div class="col-md-6">
        <x-adminlte-card theme="success" icon="fas fa-bullhorn" title="Anuncios - Vista Tabla" collapsible>
            <p class="text-muted">
                Vista de gestión de anuncios estilo tabla clásica con filtros y acciones por estado.
            </p>
            <div class="mb-2">
                <span class="badge badge-info">Tipos: MAINTENANCE, INCIDENT, NEWS, ALERT</span>
            </div>
            <div class="mb-2">
                <span class="badge badge-secondary">Estados: DRAFT, SCHEDULED, PUBLISHED, ARCHIVED</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Creado: Nov 2025
                </small>
                <a href="{{ route('tests.announcements-table') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-eye"></i> Ver Experimento
                </a>
            </div>
        </x-adminlte-card>
    </div>

    {{-- Experiment 3: Announcements Cards View --}}
    <div class="col-md-6">
        <x-adminlte-card theme="warning" icon="fas fa-th-large" title="Anuncios - Vista Cards" collapsible>
            <p class="text-muted">
                Vista de gestión de anuncios estilo cards/tarjetas con diseño visual más moderno y espacioso.
            </p>
            <div class="mb-2">
                <span class="badge badge-info">Tipos: MAINTENANCE, INCIDENT, NEWS, ALERT</span>
            </div>
            <div class="mb-2">
                <span class="badge badge-secondary">Estados: DRAFT, SCHEDULED, PUBLISHED, ARCHIVED</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Creado: Nov 2025
                </small>
                <a href="{{ route('tests.announcements-cards') }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-eye"></i> Ver Experimento
                </a>
            </div>
        </x-adminlte-card>
    </div>

    {{-- Experiment 4: Help Center - Search First --}}
    <div class="col-md-6">
        <x-adminlte-card theme="info" icon="fas fa-search" title="Help Center - Búsqueda Primero" collapsible>
            <p class="text-muted">
                Diseño centrado en búsqueda con hero section, categorías en cards y artículos populares.
            </p>
            <div class="mb-2">
                <span class="badge badge-primary">Barra de búsqueda prominente</span>
            </div>
            <div class="mb-2">
                <span class="badge badge-success">4 categorías principales</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Creado: Nov 2025
                </small>
                <a href="{{ url('/tests/helpcenter-search-first') }}" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> Ver Diseño
                </a>
            </div>
        </x-adminlte-card>
    </div>

    {{-- Experiment 5: Help Center - FAQ Accordion --}}
    <div class="col-md-6">
        <x-adminlte-card theme="purple" icon="fas fa-question-circle" title="Help Center - FAQ Accordion" collapsible>
            <p class="text-muted">
                Diseño estilo FAQ con acordeones colapsables y tabs por categoría.
            </p>
            <div class="mb-2">
                <span class="badge badge-info">Preguntas frecuentes</span>
            </div>
            <div class="mb-2">
                <span class="badge badge-warning">Acordeones animados</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Creado: Nov 2025
                </small>
                <a href="{{ url('/tests/helpcenter-faq-accordion') }}" class="btn btn-sm btn-purple" style="background-color: #6f42c1; border-color: #6f42c1; color: white;">
                    <i class="fas fa-eye"></i> Ver Diseño
                </a>
            </div>
        </x-adminlte-card>
    </div>

    {{-- Experiment 6: Help Center - Knowledge Base --}}
    <div class="col-md-6">
        <x-adminlte-card theme="dark" icon="fas fa-book-open" title="Help Center - Knowledge Base" collapsible>
            <p class="text-muted">
                Diseño tipo knowledge base con artículos destacados y grilla de categorías.
            </p>
            <div class="mb-2">
                <span class="badge badge-warning">Artículos destacados</span>
            </div>
            <div class="mb-2">
                <span class="badge badge-info">Vista completa por categoría</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Creado: Nov 2025
                </small>
                <a href="{{ url('/tests/helpcenter-knowledge-base') }}" class="btn btn-sm btn-dark">
                    <i class="fas fa-eye"></i> Ver Diseño
                </a>
            </div>
        </x-adminlte-card>
    </div>

    {{-- Future Experiments Placeholder --}}
    <div class="col-md-6">

        <x-adminlte-card theme="secondary" icon="fas fa-plus-circle" title="Agregar Nuevo Experimento" collapsible collapsed>
            <p class="text-muted">
                Para agregar un nuevo experimento al laboratorio:
            </p>
            <ol class="small">
                <li>Crea una nueva vista en <code>resources/views/tests/experiments/</code></li>
                <li>Agrega la ruta en <code>routes/web.php</code></li>
                <li>Añade la tarjeta del experimento en este índice</li>
            </ol>
            <hr>
            <p class="mb-0 text-center">
                <i class="fas fa-lightbulb text-warning fa-2x"></i>
            </p>
        </x-adminlte-card>
    </div>
</div>

{{-- Quick Reference --}}
<x-adminlte-card title="Guía Rápida del Laboratorio" theme="info" icon="fas fa-info-circle" collapsible collapsed>
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-question-circle text-info"></i> ¿Qué es el Laboratorio Visual?</h6>
            <p class="small text-muted">
                Es un espacio donde probamos diferentes opciones de diseño antes de implementarlas en producción.
                Todos los ejemplos usan datos hardcodeados para facilitar la comparación.
            </p>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-check-circle text-success"></i> ¿Cómo usarlo?</h6>
            <ol class="small text-muted mb-0">
                <li>Selecciona un experimento de arriba</li>
                <li>Revisa las diferentes opciones visuales</li>
                <li>Evalúa cuál te gusta más</li>
                <li>Comunica tu decisión al equipo de desarrollo</li>
            </ol>
        </div>
    </div>
</x-adminlte-card>

@endsection

@section('js')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
@endsection