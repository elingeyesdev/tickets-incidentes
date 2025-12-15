@php
    $isAuthenticated = request()->hasCookie('jwt_token') || request()->hasCookie('refresh_token');
    $layout = $isAuthenticated ? 'layouts.authenticated' : 'layouts.guest';
    $homeRoute = $isAuthenticated ? '/app/dashboard' : '/';
    $homeText = $isAuthenticated ? 'volver al panel' : 'volver al inicio';
@endphp

@extends($layout)

{{-- Para el layout autenticado, usamos la sección content_header estándar de AdminLTE --}}
@if($isAuthenticated)
    @section('content_header', 'Página de Error 404')
@endif

@section('content')

    {{-- Para el layout guest, necesitamos envolver el contenido y poner el header manualmente --}}
    @if(!$isAuthenticated)
    <div class="content-wrapper" style="margin-left: 0;">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Página de Error 404</h1>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Main content -->
    <section class="content">
        <div class="error-page">
            <h2 class="headline text-warning"> 404</h2>

            <div class="error-content">
                <h3><i class="fas fa-exclamation-triangle text-warning"></i> ¡Ups! Página no encontrada.</h3>

                <p>
                    No pudimos encontrar la página que estabas buscando.
                    Mientras tanto, puedes <a href="{{ $homeRoute }}">{{ $homeText }}</a> o intentar usar el formulario de búsqueda.
                </p>

                <form class="search-form">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar">

                        <div class="input-group-append">
                            <button type="submit" name="submit" class="btn btn-warning"><i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.input-group -->
                </form>
            </div>
            <!-- /.error-content -->
        </div>
        <!-- /.error-page -->
    </section>

    @if(!$isAuthenticated)
    </div> {{-- Cierre de content-wrapper --}}
    @endif

@endsection
