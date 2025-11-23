@extends('adminlte::page')

@section('title', 'Anuncios - Lista')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Bandeja de Anuncios</h1>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- List Column -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Recientes</h3>
                        <div class="card-tools">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" placeholder="Buscar...">
                                <div class="input-group-append">
                                    <div class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action active">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Interrupción de Servicio</h5>
                                    <small>Hace 2h</small>
                                </div>
                                <p class="mb-1">Acme Corp</p>
                                <small><span class="badge badge-danger">Incidente</span> Problemas con pagos...</small>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Nueva Funcionalidad</h5>
                                    <small>Hace 5h</small>
                                </div>
                                <p class="mb-1">Tech Solutions</p>
                                <small><span class="badge badge-info">Noticia</span> Módulo de reportes...</small>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">Mantenimiento Programado</h5>
                                    <small>Ayer</small>
                                </div>
                                <p class="mb-1">Acme Corp</p>
                                <small><span class="badge badge-warning">Mantenimiento</span> Actualización de...</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Column -->
            <div class="col-md-8">
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Detalles del Anuncio</h3>
                        <div class="card-tools">
                            <span class="badge badge-danger">Incidente Crítico</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="media mb-4">
                            <img class="mr-3 img-circle" src="https://ui-avatars.com/api/?name=Acme+Corp&background=dc3545&color=fff" alt="Generic placeholder image" style="width: 64px;">
                            <div class="media-body">
                                <h5 class="mt-0">Acme Corp</h5>
                                <span class="text-muted">Publicado el 22 de Noviembre, 2025 a las 12:05 PM</span>
                            </div>
                        </div>
                        
                        <h4>Interrupción de Servicio en Pasarela de Pagos</h4>
                        <hr>
                        <div class="announcement-content">
                            <p>Estimados usuarios,</p>
                            <p>Actualmente estamos experimentando una interrupción parcial en nuestro servicio de procesamiento de pagos. Esto está afectando a las transacciones con tarjetas de crédito Visa y Mastercard.</p>
                            
                            <div class="callout callout-danger">
                                <h5>Estado Actual</h5>
                                <p>Nuestros ingenieros han identificado la causa raíz y están trabajando en implementar una solución. Estimamos un tiempo de resolución de 2 horas.</p>
                            </div>

                            <p>Lamentamos los inconvenientes que esto pueda causar a su operación.</p>
                            <p>Atentamente,<br>El equipo de Acme Corp</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="button" class="btn btn-default"><i class="fas fa-share"></i> Compartir</button>
                        <button type="button" class="btn btn-default float-right"><i class="far fa-thumbs-up"></i> Me sirve (12)</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
