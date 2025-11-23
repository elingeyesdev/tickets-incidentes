@extends('adminlte::page')

@section('title', 'Ticket #2025-001 - Tabs Design')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Ticket #2025-001 <span class="badge badge-warning ml-2" style="font-size: 0.6em; vertical-align: middle;">Pending</span></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Tickets</a></li>
                    <li class="breadcrumb-item active">#2025-001</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-tabs">
                    <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="custom-tabs-one-chat-tab" data-toggle="pill" href="#custom-tabs-one-chat" role="tab" aria-controls="custom-tabs-one-chat" aria-selected="true">Conversación</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="custom-tabs-one-details-tab" data-toggle="pill" href="#custom-tabs-one-details" role="tab" aria-controls="custom-tabs-one-details" aria-selected="false">Detalles</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="custom-tabs-one-actions-tab" data-toggle="pill" href="#custom-tabs-one-actions" role="tab" aria-controls="custom-tabs-one-actions" aria-selected="false">Acciones</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="custom-tabs-one-tabContent">
                            
                            <!-- TAB 1: CHAT -->
                            <div class="tab-pane fade show active" id="custom-tabs-one-chat" role="tabpanel" aria-labelledby="custom-tabs-one-chat-tab">
                                <!-- Original Description as a Collapsible Card above Chat -->
                                <div class="card card-outline card-secondary collapsed-card mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title text-muted"><i class="fas fa-info-circle mr-1"></i> Ver Descripción Original</h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5>Error en la facturación del mes de Noviembre</h5>
                                        <p>Hola equipo de soporte, estoy escribiendo porque he notado un error en la factura...</p>
                                    </div>
                                </div>

                                <x-ticket-chat />
                            </div>

                            <!-- TAB 2: DETAILS -->
                            <div class="tab-pane fade" id="custom-tabs-one-details" role="tabpanel" aria-labelledby="custom-tabs-one-details-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card card-outline card-info">
                                            <div class="card-header">
                                                <h3 class="card-title">Metadatos</h3>
                                            </div>
                                            <div class="card-body">
                                                <dl class="row">
                                                    <dt class="col-sm-4">Categoría</dt>
                                                    <dd class="col-sm-8">Facturación</dd>
                                                    <dt class="col-sm-4">Prioridad</dt>
                                                    <dd class="col-sm-8"><span class="badge badge-danger">Alta</span></dd>
                                                    <dt class="col-sm-4">Estado</dt>
                                                    <dd class="col-sm-8">Pending</dd>
                                                    <dt class="col-sm-4">Creado</dt>
                                                    <dd class="col-sm-8">15 Nov, 2025</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card card-outline card-success">
                                            <div class="card-header">
                                                <h3 class="card-title">Personas</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="user-block">
                                                    <img class="img-circle img-bordered-sm" src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&background=007bff&color=fff" alt="user image">
                                                    <span class="username"><a href="#">Kylie De la quintana</a></span>
                                                    <span class="description">Solicitante - Cliente VIP</span>
                                                </div>
                                                <hr>
                                                <div class="user-block">
                                                    <img class="img-circle img-bordered-sm" src="https://ui-avatars.com/api/?name=Juan+Support&background=6c757d&color=fff" alt="user image">
                                                    <span class="username"><a href="#">Juan Support</a></span>
                                                    <span class="description">Agente Asignado - Soporte Nivel 2</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: ACTIONS -->
                            <div class="tab-pane fade" id="custom-tabs-one-actions" role="tabpanel" aria-labelledby="custom-tabs-one-actions-tab">
                                <div class="row">
                                    <div class="col-md-12 text-center mb-4">
                                        <h4>Acciones Disponibles</h4>
                                        <p class="text-muted">Seleccione una acción para cambiar el estado del ticket.</p>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box bg-success">
                                            <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Resolver Ticket</span>
                                                <span class="info-box-number">Marcar como resuelto</span>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 100%"></div>
                                                </div>
                                                <span class="progress-description">Soluciona el problema</span>
                                            </div>
                                            <a href="#" class="small-box-footer text-white text-center p-1" style="background: rgba(0,0,0,0.1); display:block;">Ejecutar <i class="fas fa-arrow-circle-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box bg-secondary">
                                            <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Cerrar Ticket</span>
                                                <span class="info-box-number">Finalizar proceso</span>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 100%"></div>
                                                </div>
                                                <span class="progress-description">Sin reapertura</span>
                                            </div>
                                            <a href="#" class="small-box-footer text-white text-center p-1" style="background: rgba(0,0,0,0.1); display:block;">Ejecutar <i class="fas fa-arrow-circle-right"></i></a>
                                        </div>
                                    </div>
                                    <!-- More actions... -->
                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </div>
@stop
