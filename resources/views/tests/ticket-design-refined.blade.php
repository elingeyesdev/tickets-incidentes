@extends('adminlte::page')

@section('title', 'Ticket #2025-001 - Refined Design')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1>Error en la facturación del mes de Noviembre</h1>
                <h5 class="text-muted mt-1">Ticket #2025-001 <small>• Creado por Kylie De la quintana</small></h5>
            </div>
            <div class="col-sm-4 text-right">
                <span class="badge badge-warning p-2" style="font-size: 1rem;">Pending</span>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column: Details & Actions (30%) -->
            <div class="col-md-3">
                
                <!-- Details Card -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Detalles</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped">
                            <tbody>
                                <tr>
                                    <td><strong>Categoría</strong></td>
                                    <td>Facturación</td>
                                </tr>
                                <tr>
                                    <td><strong>Prioridad</strong></td>
                                    <td><span class="badge badge-danger">Alta</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Asignado a</strong></td>
                                    <td>Juan Support</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha</strong></td>
                                    <td>15 Nov, 2025</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Acciones</h3>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-check mr-1"></i> Resolver Ticket
                        </button>
                        <div class="row">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-secondary btn-block" title="Cerrar">
                                    <i class="fas fa-times-circle"></i> Cerrar
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-warning btn-block" title="Reasignar">
                                    <i class="fas fa-user-friends"></i> Asignar
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-block mt-2">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>
                    </div>
                </div>

                <!-- Requester Mini Profile -->
                <div class="card card-widget widget-user-2">
                    <div class="widget-user-header bg-light">
                        <div class="widget-user-image">
                            <img class="img-circle elevation-2" src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&background=007bff&color=fff" alt="User Avatar">
                        </div>
                        <h3 class="widget-user-username text-sm">Kylie De la quintana</h3>
                        <h5 class="widget-user-desc text-xs text-muted">Cliente VIP</h5>
                    </div>
                </div>

            </div>
            <!-- /.col -->

            <!-- Right Column: Chat & Description (70%) -->
            <div class="col-md-9">
                
                <!-- Collapsible Description -->
                <div class="card collapsed-card mb-3">
                    <div class="card-header bg-light">
                        <h3 class="card-title text-muted" style="font-size: 1rem;">
                            <i class="fas fa-align-left mr-2"></i> Descripción Original del Problema
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i> Mostrar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p>Hola equipo de soporte,</p>
                        <p>Estoy escribiendo porque he notado un error en la factura generada para este mes. El monto total no coincide con el plan que tengo contratado.</p>
                        <p>Según mi contrato, debería estar pagando $50/mes, pero la factura muestra $75. ¿Podrían revisar esto por favor?</p>
                        <div class="attachment-block clearfix">
                            <img class="attachment-img" src="https://placehold.co/100x100?text=PDF" alt="Attachment Image">
                            <div class="attachment-pushed">
                                <h4 class="attachment-heading"><a href="#">Factura_Nov.pdf</a></h4>
                                <div class="attachment-text">
                                    1,245 KB - PDF File
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Component -->
                <x-ticket-chat />

            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
@stop
