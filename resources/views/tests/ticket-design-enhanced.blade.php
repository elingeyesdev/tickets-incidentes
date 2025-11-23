@extends('adminlte::page')

@section('title', 'Ticket #2025-001 - Enhanced Design')

@section('content_header')
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-8">
                <h1 class="m-0">Error en la facturación del mes de Noviembre</h1>
                <small class="text-muted">Creado por Kylie De la quintana</small>
            </div>
            <div class="col-sm-4 text-right">
                <span class="badge badge-warning p-2" style="font-size: 0.9rem;">
                    <i class="fas fa-circle mr-1"></i> Pending
                </span>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row" style="margin-top: -20px;">

            <!-- LEFT COLUMN: Description & Actions (40%) -->
            <div class="col-md-5">

                <!-- Main Ticket Card - With Code, Title & Description -->
                <div class="card card-primary card-outline mb-3">
                    <div class="card-header bg-gradient-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title m-0 text-white">
                                    <i class="fas fa-ticket-alt mr-2"></i> Ticket #2025-001
                                </h5>
                                <small class="text-white-50">Creado: 15 Nov 2025</small>
                            </div>
                            <div>
                                <span class="badge badge-light text-primary p-2">
                                    Facturación
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Ticket Title -->
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted" style="letter-spacing: 0.5px; font-size: 0.75rem; font-weight: 600;">
                                <i class="fas fa-heading mr-2"></i> Asunto del Ticket
                            </h6>
                            <h4 class="card-title mt-2 mb-0">
                                Error en la facturación del mes de Noviembre
                            </h4>
                        </div>

                        <hr>

                        <!-- Ticket Description -->
                        <div>
                            <h6 class="text-uppercase text-muted mb-3" style="letter-spacing: 0.5px; font-size: 0.75rem; font-weight: 600;">
                                <i class="fas fa-align-left mr-2"></i> Descripción del Problema
                            </h6>
                            <div class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">
                                <p>
                                    Hola equipo de soporte, estoy escribiendo porque he notado un error en la factura generada para este mes. El monto total no coincide con el plan que tengo contratado.
                                </p>
                                <p class="mb-0">
                                    Según mi contrato, debería estar pagando <strong class="text-dark">$50/mes</strong>, pero la factura muestra <strong class="text-danger">$75</strong>. ¿Podrían revisar esto por favor?
                                </p>
                            </div>

                            <!-- Attachments -->
                            <div class="mt-3">
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-paperclip mr-1"></i> 2 Adjuntos
                                </small>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="#" class="attachment-badge" style="display: inline-flex; align-items: center; padding: 6px 10px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; font-size: 0.85rem; text-decoration: none; color: #495057; transition: all 0.2s ease;">
                                        <i class="fas fa-file-pdf mr-1" style="color: #dc3545;"></i>
                                        <span>Factura_Nov.pdf</span>
                                    </a>
                                    <a href="#" class="attachment-badge" style="display: inline-flex; align-items: center; padding: 6px 10px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; font-size: 0.85rem; text-decoration: none; color: #495057; transition: all 0.2s ease;">
                                        <i class="fas fa-file-word mr-1" style="color: #007bff;"></i>
                                        <span>Contrato.docx</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs mr-2"></i> Acciones
                        </h3>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success btn-block btn-lg mb-2">
                            <i class="fas fa-check mr-2"></i> Resolver Ticket
                        </button>

                        <div class="row">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-secondary btn-block" title="Cerrar Ticket">
                                    <i class="fas fa-times-circle mr-1"></i> Cerrar
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-warning btn-block" title="Reasignar">
                                    <i class="fas fa-user-plus mr-1"></i> Asignar
                                </button>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-danger btn-block mt-2">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>

                        <hr>

                        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing: 0.5px; font-size: 0.75rem; font-weight: 600;">
                            <i class="fas fa-info-circle mr-2"></i> Detalles Rápidos
                        </h6>

                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="font-size: 0.85rem;">Estado:</td>
                                    <td class="text-right"><span class="badge badge-warning">Pendiente</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size: 0.85rem;">Prioridad:</td>
                                    <td class="text-right"><span class="badge badge-danger">Alta</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size: 0.85rem;">Categoría:</td>
                                    <td class="text-right"><small>Facturación</small></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size: 0.85rem;">Asignado:</td>
                                    <td class="text-right"><small>Juan Support</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Requester Info Card -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user mr-2"></i> Solicitante
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        <img class="img-circle elevation-2 mb-2" src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&background=007bff&color=fff&size=80" alt="Kylie" style="width: 80px; height: 80px;">
                        <h5 class="mb-1">Kylie De la quintana</h5>
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">
                            <span class="badge badge-primary">Cliente VIP</span>
                        </p>
                        <small class="text-muted d-block">kylie@example.com</small>
                        <small class="text-muted">12 tickets totales</small>
                    </div>
                </div>

            </div>
            <!-- /.col -->

            <!-- RIGHT COLUMN: Chat (60%) -->
            <div class="col-md-7">
                <x-ticket-chat />
            </div>
            <!-- /.col -->

        </div>
        <!-- /.row -->
    </div>
@stop
