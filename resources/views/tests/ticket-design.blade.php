@extends('adminlte::page')

@section('title', 'Ticket #2025-001 - Mockup')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    Ticket #2025-001: Error en la facturación mensual
                    <span class="badge badge-warning ml-2">Pending</span>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
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
            <!-- Left Column: Description & Chat (70%) -->
            <div class="col-md-9">
                
                <!-- Ticket Description (Read Mail Style) -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Detalles del Problema</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <div class="mailbox-read-info">
                            <h5>Asunto: Error en la facturación del mes de Noviembre</h5>
                            <h6>De: Kylie De la quintana (kylie@example.com)
                                <span class="mailbox-read-time float-right">15 Nov. 2025 11:03 PM</span>
                            </h6>
                        </div>
                        <!-- /.mailbox-read-info -->
                        <div class="mailbox-controls with-border text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm" data-container="body" title="Reply">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm" data-container="body" title="Forward">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-default btn-sm" title="Print">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                        <!-- /.mailbox-controls -->
                        <div class="mailbox-read-message">
                            <p>Hola equipo de soporte,</p>

                            <p>Estoy escribiendo porque he notado un error en la factura generada para este mes. El monto total no coincide con el plan que tengo contratado.</p>

                            <p>Según mi contrato, debería estar pagando $50/mes, pero la factura muestra $75. ¿Podrían revisar esto por favor?</p>

                            <p>Adjunto encontrarán la factura en cuestión y mi contrato.</p>

                            <p>Gracias,<br>Kylie</p>
                        </div>
                        <!-- /.mailbox-read-message -->
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer bg-white">
                        <ul class="mailbox-attachments d-flex align-items-stretch clearfix">
                            <li>
                                <span class="mailbox-attachment-icon"><i class="far fa-file-pdf"></i></span>

                                <div class="mailbox-attachment-info">
                                    <a href="#" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> Factura_Nov.pdf</a>
                                        <span class="mailbox-attachment-size clearfix mt-1">
                                            <span>1,245 KB</span>
                                            <a href="#" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
                                        </span>
                                </div>
                            </li>
                            <li>
                                <span class="mailbox-attachment-icon"><i class="far fa-file-word"></i></span>

                                <div class="mailbox-attachment-info">
                                    <a href="#" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> Contrato.docx</a>
                                        <span class="mailbox-attachment-size clearfix mt-1">
                                            <span>1.5 MB</span>
                                            <a href="#" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
                                        </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!-- /.card-footer -->
                </div>
                <!-- /.card -->

                <!-- Chat Component -->
                <div class="mt-4">
                    <x-ticket-chat />
                </div>

            </div>
            <!-- /.col -->

            <!-- Right Column: Metadata & Actions (30%) -->
            <div class="col-md-3">
                
                <!-- Ticket Info -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Información del Ticket</h3>
                    </div>
                    <div class="card-body">
                        <strong><i class="fas fa-book mr-1"></i> Categoría</strong>
                        <p class="text-muted">Facturación</p>
                        <hr>

                        <strong><i class="fas fa-exclamation-circle mr-1"></i> Prioridad</strong>
                        <p class="text-danger">Alta</p>
                        <hr>

                        <strong><i class="far fa-calendar-alt mr-1"></i> Creado</strong>
                        <p class="text-muted">15 Nov, 2025</p>
                        <hr>

                        <strong><i class="far fa-clock mr-1"></i> Última Actividad</strong>
                        <p class="text-muted">Hace 5 minutos</p>
                    </div>
                </div>

                <!-- People -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">Personas</h3>
                    </div>
                    <div class="card-body">
                        <strong><i class="fas fa-user mr-1"></i> Solicitante</strong>
                        <div class="media mt-2">
                            <img src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&background=007bff&color=fff" alt="User Avatar" class="img-size-32 mr-3 img-circle">
                            <div class="media-body">
                                <h6 class="dropdown-item-title">Kylie De la quintana</h6>
                                <p class="text-sm text-muted">Cliente VIP</p>
                            </div>
                        </div>
                        <hr>

                        <strong><i class="fas fa-user-shield mr-1"></i> Agente Asignado</strong>
                        <div class="media mt-2">
                            <img src="https://ui-avatars.com/api/?name=Juan+Support&background=6c757d&color=fff" alt="User Avatar" class="img-size-32 mr-3 img-circle">
                            <div class="media-body">
                                <h6 class="dropdown-item-title">Juan Support</h6>
                                <p class="text-sm text-muted">Soporte Nivel 2</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Acciones</h3>
                    </div>
                    <div class="card-body p-2">
                        <button type="button" class="btn btn-app bg-success btn-block m-0 mb-2">
                            <i class="fas fa-check"></i> Resolver
                        </button>
                        <button type="button" class="btn btn-app bg-secondary btn-block m-0 mb-2">
                            <i class="fas fa-times-circle"></i> Cerrar
                        </button>
                        <button type="button" class="btn btn-app bg-warning btn-block m-0 mb-2">
                            <i class="fas fa-user-plus"></i> Reasignar
                        </button>
                        <button type="button" class="btn btn-app bg-danger btn-block m-0">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>

            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
@stop

@section('css')
    <style>
        /* Custom styles if needed */
        .btn-app {
            height: auto;
            padding: 10px;
            min-width: 0;
            margin: 0 0 10px 0;
        }
    </style>
@stop

@section('js')
    <script>
        console.log('Ticket Design Mockup Loaded');
    </script>
@stop
