@extends('layouts.authenticated')

@section('title', 'Ticket TKT-2025-00001')

@section('content_header', 'Detalle de Ticket')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/tickets">Tickets</a></li>
    <li class="breadcrumb-item active">TKT-2025-00001</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">

                    <!-- Card Principal del Ticket -->
                    <div class="card card-primary card-outline">

                        <!-- Card Header con Navegación -->
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                <span id="ticket-code">TKT-2025-00001</span>
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" title="Ticket Anterior">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-tool" title="Ticket Siguiente">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <button type="button" class="btn btn-tool" title="Volver a la Lista">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-0">

                            <!-- Información Principal del Ticket -->
                            <div class="mailbox-read-info">
                                <h3 id="ticket-title">Error crítico en módulo de reportes</h3>
                                <h6>
                                    <i class="fas fa-user mr-2"></i>Creado por:
                                    <a href="#" id="creator-name">Juan Pérez</a>
                                    <span class="mailbox-read-time float-right">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span id="created-at">16 Nov. 2025 10:30 AM</span>
                                </span>
                                </h6>
                            </div>

                            <!-- Badges de Estado y Metadata -->
                            <div class="p-3 border-bottom">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong><i class="fas fa-info-circle mr-2"></i>Estado:</strong>
                                            <span class="badge badge-danger ml-2" id="ticket-status">Open</span>
                                        </p>
                                        <p class="mb-2">
                                            <strong><i class="fas fa-tag mr-2"></i>Categoría:</strong>
                                            <span class="text-muted ml-2" id="ticket-category">Soporte Técnico</span>
                                        </p>
                                        <p class="mb-2">
                                            <strong><i class="fas fa-building mr-2"></i>Empresa:</strong>
                                            <span class="text-muted ml-2" id="ticket-company">Acme Corporation</span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2" data-role="AGENT,COMPANY_ADMIN">
                                            <strong><i class="fas fa-user-tie mr-2"></i>Asignado a:</strong>
                                            <span class="text-muted ml-2" id="assigned-agent">María García</span>
                                        </p>
                                        <p class="mb-2">
                                            <strong><i class="fas fa-comments mr-2"></i>Respuestas:</strong>
                                            <span class="badge badge-info ml-2" id="responses-count">3</span>
                                        </p>
                                        <p class="mb-2">
                                            <strong><i class="fas fa-paperclip mr-2"></i>Adjuntos:</strong>
                                            <span class="badge badge-secondary ml-2" id="attachments-count">2</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción según Rol y Estado -->
                            <div class="mailbox-controls with-border text-center p-3" id="action-buttons">

                                <!-- Botones para AGENT -->
                                <div class="btn-group" data-role="AGENT" data-status="open,pending">
                                    <button class="btn btn-success btn-sm" id="btn-resolve">
                                        <i class="fas fa-check mr-2"></i>Resolver
                                    </button>
                                    <button class="btn btn-warning btn-sm" id="btn-assign">
                                        <i class="fas fa-user-plus mr-2"></i>Asignar
                                    </button>
                                    <button class="btn btn-danger btn-sm" id="btn-close">
                                        <i class="fas fa-times mr-2"></i>Cerrar
                                    </button>
                                </div>

                                <!-- Botón para USER (Solo si está resolved) -->
                                <div class="btn-group" data-role="USER" data-status="resolved">
                                    <button class="btn btn-info btn-sm" id="btn-reopen">
                                        <i class="fas fa-redo mr-2"></i>Reabrir Ticket
                                    </button>
                                </div>

                                <!-- Botón Imprimir (Todos) -->
                                <button class="btn btn-default btn-sm ml-2" id="btn-print">
                                    <i class="fas fa-print mr-2"></i>Imprimir
                                </button>

                                <!-- Botón Actualizar (AGENT, COMPANY_ADMIN) -->
                                <button class="btn btn-default btn-sm ml-2" data-role="AGENT,COMPANY_ADMIN" data-toggle="modal" data-target="#editTicketModal">
                                    <i class="fas fa-edit mr-2"></i>Editar
                                </button>

                                <!-- Botón Eliminar (Solo COMPANY_ADMIN y si está cerrado) -->
                                <button class="btn btn-danger btn-sm ml-2" data-role="COMPANY_ADMIN" data-status="closed" id="btn-delete">
                                    <i class="fas fa-trash mr-2"></i>Eliminar
                                </button>
                            </div>

                            <!-- Descripción del Ticket -->
                            <div class="mailbox-read-message p-4">
                                <h5><i class="fas fa-align-left mr-2"></i>Descripción</h5>
                                <p id="ticket-description">
                                    Cuando intento exportar el reporte mensual de ventas desde el dashboard,
                                    el sistema muestra un error 500 (Internal Server Error). He intentado desde
                                    diferentes navegadores (Chrome, Firefox, Edge) con el mismo resultado.
                                    El problema comenzó esta mañana alrededor de las 9:00 AM.
                                    <br><br>
                                    Pasos para reproducir:
                                    <br>1. Ir a Dashboard > Reportes
                                    <br>2. Seleccionar "Reporte Mensual de Ventas"
                                    <br>3. Click en "Exportar a PDF"
                                    <br>4. El sistema muestra error 500
                                    <br><br>
                                    Adjunto capturas de pantalla del error y de la consola del navegador.
                                </p>
                            </div>

                        </div>
                        <!-- /.card-body -->

                        <!-- Attachments Section -->
                        <div class="card-footer bg-white">
                            <h5><i class="fas fa-paperclip mr-2"></i>Adjuntos (2)</h5>
                            <ul class="mailbox-attachments d-flex align-items-stretch clearfix">

                                <!-- Attachment 1: Imagen -->
                                <li>
                                <span class="mailbox-attachment-icon has-img">
                                    <img src="https://via.placeholder.com/150x150/007bff/ffffff?text=Error+Screenshot"
                                         alt="Captura de error">
                                </span>
                                    <div class="mailbox-attachment-info">
                                        <a href="#" class="mailbox-attachment-name">
                                            <i class="fas fa-camera"></i> error_screenshot.png
                                        </a>
                                        <span class="mailbox-attachment-size clearfix mt-1">
                                        <span>245 KB</span>
                                        <a href="#" class="btn btn-default btn-sm float-right">
                                            <i class="fas fa-cloud-download-alt"></i>
                                        </a>
                                    </span>
                                    </div>
                                </li>

                                <!-- Attachment 2: PDF -->
                                <li>
                                <span class="mailbox-attachment-icon">
                                    <i class="far fa-file-pdf"></i>
                                </span>
                                    <div class="mailbox-attachment-info">
                                        <a href="#" class="mailbox-attachment-name">
                                            <i class="fas fa-paperclip"></i> console_log.txt
                                        </a>
                                        <span class="mailbox-attachment-size clearfix mt-1">
                                        <span>12 KB</span>
                                        <a href="#" class="btn btn-default btn-sm float-right">
                                            <i class="fas fa-cloud-download-alt"></i>
                                        </a>
                                    </span>
                                    </div>
                                </li>

                            </ul>
                        </div>
                        <!-- /.card-footer -->

                    </div>
                    <!-- /.card (Información Principal) -->

                    <!-- Card: Timeline de Actividad -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>Historial de Actividad
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">

                                <!-- Timeline: Ticket Creado -->
                                <div class="time-label">
                                    <span class="bg-red">16 Nov. 2025</span>
                                </div>
                                <div>
                                    <i class="fas fa-plus-circle bg-blue"></i>
                                    <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 10:30 AM
                                    </span>
                                        <h3 class="timeline-header">
                                            <a href="#">Juan Pérez</a> creó el ticket
                                        </h3>
                                        <div class="timeline-body">
                                            Ticket creado con prioridad normal en categoría "Soporte Técnico"
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline: Agente Asignado -->
                                <div>
                                    <i class="fas fa-user-plus bg-yellow"></i>
                                    <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 10:35 AM
                                    </span>
                                        <h3 class="timeline-header">
                                            <a href="#">Sistema</a> asignó el ticket automáticamente
                                        </h3>
                                        <div class="timeline-body">
                                            Ticket asignado a <strong>María García</strong> (AGENT)
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline: Primera Respuesta del Agente -->
                                <div>
                                    <i class="fas fa-comment bg-green"></i>
                                    <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 10:45 AM
                                    </span>
                                        <h3 class="timeline-header">
                                            <a href="#">María García (AGENT)</a> respondió al ticket
                                        </h3>
                                        <div class="timeline-body">
                                            Gracias por reportar el problema. He revisado los logs del servidor y
                                            efectivamente hay un error en el módulo de exportación. Estoy trabajando
                                            en la solución.
                                        </div>
                                        <div class="timeline-footer">
                                            <span class="badge badge-info">Estado cambiado a: Pending</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline: Respuesta del Usuario -->
                                <div>
                                    <i class="fas fa-comment bg-blue"></i>
                                    <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 11:15 AM
                                    </span>
                                        <h3 class="timeline-header">
                                            <a href="#">Juan Pérez (USER)</a> respondió
                                        </h3>
                                        <div class="timeline-body">
                                            Muchas gracias por la pronta respuesta. ¿Tienen una estimación de cuándo
                                            estará solucionado? Necesito entregar estos reportes hoy antes de las 5 PM.
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline: Actualización del Agente -->
                                <div>
                                    <i class="fas fa-tools bg-purple"></i>
                                    <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 12:30 PM
                                    </span>
                                        <h3 class="timeline-header">
                                            <a href="#">María García (AGENT)</a> actualizó el ticket
                                        </h3>
                                        <div class="timeline-body">
                                            He identificado la causa raíz del problema. Era una incompatibilidad con
                                            la última actualización de la librería de exportación PDF. Ya apliqué el
                                            fix y desplegué a producción. Por favor, intenta nuevamente.
                                        </div>
                                        <div class="timeline-footer">
                                        <span class="badge badge-primary">
                                            <i class="fas fa-paperclip mr-1"></i>1 adjunto
                                        </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline: Confirmación del Usuario -->
                                <div>
                                    <i class="fas fa-check-circle bg-success"></i>
                                    <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 1:00 PM
                                    </span>
                                        <h3 class="timeline-header">
                                            <a href="#">Juan Pérez (USER)</a> confirmó la solución
                                        </h3>
                                        <div class="timeline-body">
                                            ¡Perfecto! Ya pude exportar los reportes sin problemas. Muchas gracias
                                            por la solución rápida. Todo funciona correctamente ahora.
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline: Ticket Resuelto -->
                                <div>
                                    <i class="fas fa-star bg-warning"></i>
                                    <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 1:05 PM
                                    </span>
                                        <h3 class="timeline-header">
                                            <a href="#">María García (AGENT)</a> marcó como resuelto
                                        </h3>
                                        <div class="timeline-body">
                                            Me alegra que el problema esté solucionado. Marcando el ticket como resuelto.
                                        </div>
                                        <div class="timeline-footer">
                                            <span class="badge badge-success">Estado cambiado a: Resolved</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timeline End -->
                                <div>
                                    <i class="fas fa-clock bg-gray"></i>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- /.card (Timeline) -->

                    <!-- Card: Conversación (Direct Chat Style) -->
                    <div class="card direct-chat direct-chat-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-comments mr-2"></i>Conversación (3 mensajes)
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="direct-chat-messages" style="height: 400px;">

                                <!-- Mensaje 1: Usuario -->
                                <div class="direct-chat-msg">
                                    <div class="direct-chat-infos clearfix">
                                        <span class="direct-chat-name float-left">Juan Pérez</span>
                                        <span class="direct-chat-timestamp float-right">16 Nov 10:30 AM</span>
                                    </div>
                                    <img class="direct-chat-img"
                                         src="https://ui-avatars.com/api/?name=Juan+Perez&size=40&background=0D8ABC&color=fff"
                                         alt="Juan Pérez">
                                    <div class="direct-chat-text">
                                        Cuando intento exportar el reporte mensual de ventas, el sistema muestra un error 500.
                                        He intentado desde diferentes navegadores con el mismo resultado.
                                    </div>
                                </div>

                                <!-- Mensaje 2: Agente (derecha) -->
                                <div class="direct-chat-msg right">
                                    <div class="direct-chat-infos clearfix">
                                        <span class="direct-chat-name float-right">María García</span>
                                        <span class="direct-chat-timestamp float-left">16 Nov 10:45 AM</span>
                                    </div>
                                    <img class="direct-chat-img"
                                         src="https://ui-avatars.com/api/?name=Maria+Garcia&size=40&background=28a745&color=fff"
                                         alt="María García">
                                    <div class="direct-chat-text">
                                        Gracias por reportar el problema. He revisado los logs del servidor y efectivamente
                                        hay un error en el módulo de exportación. Estoy trabajando en la solución.
                                    </div>
                                </div>

                                <!-- Mensaje 3: Usuario -->
                                <div class="direct-chat-msg">
                                    <div class="direct-chat-infos clearfix">
                                        <span class="direct-chat-name float-left">Juan Pérez</span>
                                        <span class="direct-chat-timestamp float-right">16 Nov 11:15 AM</span>
                                    </div>
                                    <img class="direct-chat-img"
                                         src="https://ui-avatars.com/api/?name=Juan+Perez&size=40&background=0D8ABC&color=fff"
                                         alt="Juan Pérez">
                                    <div class="direct-chat-text">
                                        Muchas gracias por la pronta respuesta. ¿Tienen una estimación de cuándo estará
                                        solucionado? Necesito entregar estos reportes hoy antes de las 5 PM.
                                    </div>
                                </div>

                                <!-- Mensaje 4: Agente -->
                                <div class="direct-chat-msg right">
                                    <div class="direct-chat-infos clearfix">
                                        <span class="direct-chat-name float-right">María García</span>
                                        <span class="direct-chat-timestamp float-left">16 Nov 12:30 PM</span>
                                    </div>
                                    <img class="direct-chat-img"
                                         src="https://ui-avatars.com/api/?name=Maria+Garcia&size=40&background=28a745&color=fff"
                                         alt="María García">
                                    <div class="direct-chat-text">
                                        He identificado la causa raíz del problema. Era una incompatibilidad con la última
                                        actualización de la librería de exportación PDF. Ya apliqué el fix y desplegué a
                                        producción. Por favor, intenta nuevamente.
                                        <br><br>
                                        <div class="attachment-block clearfix">
                                            <img class="attachment-img"
                                                 src="https://via.placeholder.com/100x100/28a745/ffffff?text=Fix+Applied"
                                                 alt="Attachment">
                                            <div class="attachment-info">
                                                <a href="#" class="attachment-title">
                                                    <i class="fas fa-paperclip"></i> fix_deployment_log.txt
                                                </a>
                                                <span class="attachment-meta">8 KB</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mensaje 5: Usuario (Confirmación) -->
                                <div class="direct-chat-msg">
                                    <div class="direct-chat-infos clearfix">
                                        <span class="direct-chat-name float-left">Juan Pérez</span>
                                        <span class="direct-chat-timestamp float-right">16 Nov 1:00 PM</span>
                                    </div>
                                    <img class="direct-chat-img"
                                         src="https://ui-avatars.com/api/?name=Juan+Perez&size=40&background=0D8ABC&color=fff"
                                         alt="Juan Pérez">
                                    <div class="direct-chat-text">
                                        ¡Perfecto! Ya pude exportar los reportes sin problemas. Muchas gracias por la
                                        solución rápida. Todo funciona correctamente ahora. 👍
                                    </div>
                                </div>

                                <!-- Mensaje 6: Agente (Cierre) -->
                                <div class="direct-chat-msg right">
                                    <div class="direct-chat-infos clearfix">
                                        <span class="direct-chat-name float-right">María García</span>
                                        <span class="direct-chat-timestamp float-left">16 Nov 1:05 PM</span>
                                    </div>
                                    <img class="direct-chat-img"
                                         src="https://ui-avatars.com/api/?name=Maria+Garcia&size=40&background=28a745&color=fff"
                                         alt="María García">
                                    <div class="direct-chat-text">
                                        Me alegra que el problema esté solucionado. Si vuelves a experimentar algún
                                        inconveniente, no dudes en contactarnos. ¡Que tengas un excelente día!
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Footer: Formulario de Respuesta (Solo si no está cerrado) -->
                        <div class="card-footer" id="response-form-container" data-status="open,pending,resolved">
                            <form id="responseForm">
                                <div class="input-group">
                                    <input type="text" name="message" placeholder="Escribe tu respuesta..."
                                           class="form-control" id="response-input">
                                    <span class="input-group-append">
                                    <button type="button" class="btn btn-default" id="attach-file-btn" title="Adjuntar archivo">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="send-response-btn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </span>
                                </div>
                                <input type="file" id="response-attachment" style="display: none;" multiple>
                            </form>
                        </div>

                    </div>
                    <!-- /.card (Conversación) -->

                </div>
                <!-- /.col-md-12 -->
            </div>
            <!-- /.row -->
        </div>
    </section>

    <!-- Modal: Asignar Agente (Solo AGENT) -->
    <div class="modal fade" id="assignAgentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus mr-2"></i>Asignar Ticket a Agente
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="assignAgentForm">
                        <div class="form-group">
                            <label for="agent-select">
                                <i class="fas fa-user-tie mr-2"></i>Seleccionar Agente
                            </label>
                            <select class="form-control select2" id="agent-select" style="width: 100%;">
                                <option value="">Selecciona un agente...</option>
                                <option value="agent1">María García</option>
                                <option value="agent2">Carlos Rodríguez</option>
                                <option value="agent3">Ana Martínez</option>
                                <option value="agent4">Luis Fernández</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="assignment-note">
                                <i class="fas fa-sticky-note mr-2"></i>Nota (Opcional)
                            </label>
                            <textarea class="form-control" id="assignment-note" rows="3"
                                      placeholder="Ej: Asignando a María por su experiencia en módulos de reportes..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="confirm-assign-btn">
                        <i class="fas fa-check mr-2"></i>Asignar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Ticket (AGENT, COMPANY_ADMIN) -->
    <div class="modal fade" id="editTicketModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-edit mr-2"></i>Editar Ticket
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editTicketForm">
                        <div class="form-group">
                            <label for="edit-title">
                                <i class="fas fa-heading mr-2"></i>Título
                            </label>
                            <input type="text" class="form-control" id="edit-title"
                                   value="Error crítico en módulo de reportes">
                        </div>
                        <div class="form-group">
                            <label for="edit-category">
                                <i class="fas fa-tag mr-2"></i>Categoría
                            </label>
                            <select class="form-control select2" id="edit-category" style="width: 100%;">
                                <option value="tech" selected>Soporte Técnico</option>
                                <option value="billing">Facturación</option>
                                <option value="general">Consulta General</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirm-edit-btn">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Confirmar Acción (Resolver/Cerrar/Reabrir) -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" id="confirm-modal-header">
                    <h5 class="modal-title" id="confirm-modal-title">
                        <i class="fas fa-question-circle mr-2"></i>Confirmar Acción
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="confirm-modal-message">¿Estás seguro de realizar esta acción?</p>
                    <div class="form-group">
                        <label for="action-note">
                            <i class="fas fa-sticky-note mr-2"></i>Nota (Opcional)
                        </label>
                        <textarea class="form-control" id="action-note" rows="3"
                                  placeholder="Añade una nota sobre esta acción..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirm-action-btn">
                        <i class="fas fa-check mr-2"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        // =====================================
        // SIMULACIÓN DE ROLES Y ESTADO
        // =====================================
        // Cambiar estos valores para probar diferentes escenarios
        const CURRENT_ROLE = 'AGENT'; // 'USER', 'AGENT', 'COMPANY_ADMIN'
        const TICKET_STATUS = 'open'; // 'open', 'pending', 'resolved', 'closed'

        $(function() {
            // Aplicar visibilidad según rol y estado
            applyRoleAndStatusVisibility(CURRENT_ROLE, TICKET_STATUS);

            // Inicializar Select2
            $('.select2').select2({
                theme: 'bootstrap4'
            });

            // =====================================
            // BOTONES DE ACCIÓN
            // =====================================

            // Resolver Ticket
            $('#btn-resolve').on('click', function() {
                showConfirmModal(
                    'Resolver Ticket',
                    '¿Estás seguro de marcar este ticket como resuelto?',
                    'success',
                    function() {
                        simulateAction('resolve');
                    }
                );
            });

            // Cerrar Ticket
            $('#btn-close').on('click', function() {
                showConfirmModal(
                    'Cerrar Ticket',
                    '¿Estás seguro de cerrar este ticket?',
                    'danger',
                    function() {
                        simulateAction('close');
                    }
                );
            });

            // Reabrir Ticket
            $('#btn-reopen').on('click', function() {
                showConfirmModal(
                    'Reabrir Ticket',
                    '¿Por qué deseas reabrir este ticket?',
                    'info',
                    function() {
                        simulateAction('reopen');
                    }
                );
            });

            // Asignar Agente
            $('#btn-assign').on('click', function() {
                $('#assignAgentModal').modal('show');
            });

            $('#confirm-assign-btn').on('click', function() {
                const agent = $('#agent-select').val();
                if (!agent) {
                    alert('Por favor selecciona un agente');
                    return;
                }
                $('#assignAgentModal').modal('hide');
                alert('Ticket asignado exitosamente (Simulación)');
            });

            // Editar Ticket
            $('#confirm-edit-btn').on('click', function() {
                $('#editTicketModal').modal('hide');
                alert('Ticket actualizado exitosamente (Simulación)');
            });

            // Eliminar Ticket (Solo COMPANY_ADMIN)
            $('#btn-delete').on('click', function() {
                if (confirm('¿ESTÁS SEGURO de eliminar permanentemente este ticket?')) {
                    alert('Ticket eliminado exitosamente (Simulación)');
                    window.location.href = '/tickets';
                }
            });

            // Imprimir
            $('#btn-print').on('click', function() {
                window.print();
            });

            // =====================================
            // FORMULARIO DE RESPUESTA
            // =====================================

            $('#responseForm').on('submit', function(e) {
                e.preventDefault();
                const message = $('#response-input').val().trim();
                if (!message) {
                    alert('Por favor escribe un mensaje');
                    return;
                }

                // Simular envío
                alert('Respuesta enviada exitosamente (Simulación)');
                $('#response-input').val('');
            });

            // Adjuntar archivo en respuesta
            $('#attach-file-btn').on('click', function() {
                $('#response-attachment').click();
            });

            $('#response-attachment').on('change', function() {
                const fileCount = $(this)[0].files.length;
                if (fileCount > 0) {
                    alert(`${fileCount} archivo(s) seleccionado(s) (Simulación)`);
                }
            });

            // =====================================
            // SCROLL SUAVE AL FORMULARIO DE RESPUESTA
            // =====================================
            $('.direct-chat-messages').animate({ scrollTop: $('.direct-chat-messages')[0].scrollHeight }, 1000);
        });

        /**
         * Muestra modal de confirmación personalizado
         */
        function showConfirmModal(title, message, type, callback) {
            const headerClass = type === 'success' ? 'bg-success' :
                type === 'danger' ? 'bg-danger' :
                    type === 'info' ? 'bg-info' : 'bg-warning';

            $('#confirm-modal-header').removeClass().addClass('modal-header ' + headerClass);
            $('#confirm-modal-title').html('<i class="fas fa-question-circle mr-2"></i>' + title);
            $('#confirm-modal-message').text(message);

            // Remover listeners anteriores y agregar nuevo
            $('#confirm-action-btn').off('click').on('click', function() {
                $('#confirmActionModal').modal('hide');
                if (callback) callback();
            });

            $('#confirmActionModal').modal('show');
        }

        /**
         * Simula ejecución de acción
         */
        function simulateAction(action) {
            const messages = {
                resolve: 'Ticket marcado como resuelto exitosamente',
                close: 'Ticket cerrado exitosamente',
                reopen: 'Ticket reabierto exitosamente'
            };

            alert(messages[action] + ' (Simulación)');

            // Simular cambio de badge
            if (action === 'resolve') {
                $('#ticket-status').removeClass().addClass('badge badge-success').text('Resolved');
            } else if (action === 'close') {
                $('#ticket-status').removeClass().addClass('badge badge-secondary').text('Closed');
            } else if (action === 'reopen') {
                $('#ticket-status').removeClass().addClass('badge badge-warning').text('Pending');
            }
        }

        /**
         * Aplica visibilidad según rol y estado del ticket
         */
        function applyRoleAndStatusVisibility(role, status) {
            // Ocultar todos los elementos con data-role
            $('[data-role]').hide();

            // Mostrar solo elementos del rol actual
            $('[data-role*="' + role + '"]').show();

            // Ocultar/mostrar botones según estado del ticket
            $('[data-status]').each(function() {
                const allowedStatuses = $(this).data('status').toString().split(',');
                if (allowedStatuses.includes(status)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            // Mostrar/ocultar formulario de respuesta según estado
            if (status === 'closed') {
                $('#response-form-container').hide();
                $('#response-form-container').after(
                    '<div class="card-footer bg-light text-center">' +
                    '<i class="fas fa-lock mr-2"></i>' +
                    '<strong>Este ticket está cerrado. No se pueden agregar más respuestas.</strong>' +
                    '</div>'
                );
            }

            // Actualizar badge de estado
            const statusBadges = {
                open: { class: 'badge-danger', text: 'Open' },
                pending: { class: 'badge-warning', text: 'Pending' },
                resolved: { class: 'badge-success', text: 'Resolved' },
                closed: { class: 'badge-secondary', text: 'Closed' }
            };

            const badge = statusBadges[status];
            $('#ticket-status').removeClass().addClass('badge ' + badge.class).text(badge.text);

            console.log('Vista aplicada para Rol:', role, '| Estado:', status);
        }
    </script>
@endsection
