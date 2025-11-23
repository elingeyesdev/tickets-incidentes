@extends('adminlte::page')

@section('title', 'Ticket #2025-001 - Modern Design')

@section('content_header')
    <!-- Hidden header to maximize space -->
@stop

@section('content')
    <div class="container-fluid pt-3">
        <div class="row">
            
            <!-- Main Chat Stream (Center - 8 cols) -->
            <div class="col-md-8">
                <div class="card card-primary card-outline direct-chat direct-chat-primary" style="height: 85vh;">
                    <div class="card-header">
                        <h3 class="card-title">#2025-001: Error en la facturación</h3>
                        <div class="card-tools">
                            <span class="badge badge-warning">Pending</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="direct-chat-messages" style="height: 100%;">
                            
                            <!-- The Original Ticket as the First "Message" -->
                            <div class="direct-chat-msg">
                                <div class="direct-chat-infos clearfix">
                                    <span class="direct-chat-name float-left">Kylie De la quintana (Solicitante)</span>
                                    <span class="direct-chat-timestamp float-right">15 Nov 11:03 PM</span>
                                </div>
                                <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&background=007bff&color=fff" alt="message user image">
                                <div class="direct-chat-text bg-light text-dark border">
                                    <h5 class="mb-2">Asunto: Error en la facturación del mes de Noviembre</h5>
                                    <p>Hola equipo de soporte,</p>
                                    <p>Estoy escribiendo porque he notado un error en la factura generada para este mes. El monto total no coincide con el plan que tengo contratado.</p>
                                    <p>Según mi contrato, debería estar pagando $50/mes, pero la factura muestra $75. ¿Podrían revisar esto por favor?</p>
                                    <hr>
                                    <small class="text-muted"><i class="fas fa-paperclip"></i> 2 Adjuntos: Factura_Nov.pdf, Contrato.docx</small>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="text-center text-muted my-3">
                                <small>Ticket Created - 15 Nov 2025</small>
                            </div>

                            <!-- Regular Chat Messages (Hardcoded for demo, usually dynamic) -->
                            <div class="direct-chat-msg right">
                                <div class="direct-chat-infos clearfix">
                                    <span class="direct-chat-name float-right">Juan Support</span>
                                    <span class="direct-chat-timestamp float-left">22 Nov 10:45 am</span>
                                </div>
                                <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=Juan+Support&background=6c757d&color=fff" alt="message user image">
                                <div class="direct-chat-text">
                                    Hola Kylie! Entiendo tu preocupación. Déjame revisar tu caso en el sistema.
                                </div>
                            </div>

                            <!-- ... more messages ... -->
                        </div>
                    </div>
                    <div class="card-footer">
                        <form action="#" method="post">
                            <div class="input-group">
                                <input type="text" name="message" placeholder="Escribe una respuesta..." class="form-control">
                                <span class="input-group-append">
                                    <button type="button" class="btn btn-primary">Enviar</button>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Right - 4 cols) -->
            <div class="col-md-4">
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-body">
                        <button class="btn btn-success btn-block btn-lg mb-2">Resolver Ticket</button>
                        <div class="btn-group w-100">
                            <button class="btn btn-default">Cerrar</button>
                            <button class="btn btn-default">Asignar</button>
                            <button class="btn btn-default">Editar</button>
                        </div>
                    </div>
                </div>

                <!-- Ticket Properties -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Propiedades</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <tr>
                                <td>Estado</td>
                                <td class="text-right"><span class="badge badge-warning">Pending</span></td>
                            </tr>
                            <tr>
                                <td>Prioridad</td>
                                <td class="text-right"><span class="badge badge-danger">Alta</span></td>
                            </tr>
                            <tr>
                                <td>Categoría</td>
                                <td class="text-right">Facturación</td>
                            </tr>
                            <tr>
                                <td>Agente</td>
                                <td class="text-right">Juan Support</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Requester Info -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Solicitante</h3>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle" src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&background=007bff&color=fff" alt="User profile picture">
                        </div>
                        <h3 class="profile-username text-center">Kylie De la quintana</h3>
                        <p class="text-muted text-center">Cliente VIP</p>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Email</b> <a class="float-right">kylie@example.com</a>
                            </li>
                            <li class="list-group-item">
                                <b>Tickets Totales</b> <a class="float-right">12</a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
@stop
