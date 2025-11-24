<!-- Ticket Header Card -->
<div class="card card-primary card-outline mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fas fa-ticket-alt mr-2"></i>#2025-001
            </h3>
            <div>
                <span class="badge badge-warning p-2 mr-2" style="font-size: 0.9rem;">
                    PENDING
                </span>
                <div class="card-tools" style="display: inline-block;">
                    <button type="button" class="btn btn-tool" id="btn-minimize-ticket-detail" title="Minimizar pestaña" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="mailbox-read-info">
            <h5>Asunto: Error en la facturación del mes de Noviembre</h5>
            <h6>De: Kylie De la quintana (kylie@example.com)
                <span class="mailbox-read-time float-right">15 Nov, 2025 11:03 PM</span>
            </h6>
        </div>
        <!-- /.mailbox-read-info -->
        <div class="mailbox-read-message">
            <p>Hola equipo de soporte,</p>

            <p>Estoy escribiendo porque he notado un error en la factura generada para este mes. El monto total no coincide con el plan que tengo contratado.</p>

            <p>Según mi contrato, debería estar pagando <strong class="text-primary">$50/mes</strong>, pero la factura muestra <strong class="text-danger">$75</strong>. ¿Podrían revisar esto por favor?</p>

            <p>Adjunto encontrarán la factura en cuestión y mi contrato.</p>

            <p>Gracias,<br>Kylie</p>
        </div>
        <!-- /.mailbox-read-message -->
    </div>
    <!-- /.card-body -->
</div>

<!-- Content Row: Info/People/Actions (Left) & Chat (Right) -->
<div class="row">
    <!-- Left Column: Ticket Info, People, Actions -->
    <div class="col-md-5">

        <!-- Ticket Info -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Información del Ticket</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="mb-2">
                    <strong><i class="fas fa-code mr-1"></i> Código</strong>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">TKT-2025-00001</p>
                </div>
                <hr class="my-1">

                <div class="mb-2">
                    <strong><i class="fas fa-hourglass-half mr-1"></i> Estado</strong>
                    <p class="mb-0"><span class="badge badge-warning" style="font-size: 0.75rem;">PENDING</span></p>
                </div>
                <hr class="my-1">

                <div class="mb-2">
                    <strong><i class="fas fa-building mr-1"></i> Empresa</strong>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Acme Corporation</p>
                </div>
                <hr class="my-1">

                <div class="mb-2">
                    <strong><i class="fas fa-list mr-1"></i> Categoría</strong>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Problemas Técnicos</p>
                </div>
                <hr class="my-1">

                <div class="mb-2">
                    <strong><i class="fas fa-user-shield mr-1"></i> Agente Asignado</strong>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Juan Support</p>
                </div>
                <hr class="my-1">

                <div class="mb-2">
                    <strong><i class="far fa-calendar-alt mr-1"></i> Creado</strong>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">16 Nov, 2025 10:30</p>
                </div>
                <hr class="my-1">

                <div class="mb-2">
                    <strong><i class="far fa-clock mr-1"></i> Última Actividad</strong>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">16 Nov, 2025 12:45</p>
                </div>
                <hr class="my-1">

                <div class="mb-0">
                    <strong><i class="fas fa-comments mr-1"></i> Respuestas</strong>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">3</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Acciones</h3>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-control form-control-sm" style="width: 140px;">
                            <option value="open">OPEN</option>
                            <option value="pending" selected>PENDING</option>
                            <option value="resolved">RESOLVED</option>
                            <option value="closed">CLOSED</option>
                        </select>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <!-- Cambiar Estado -->
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-exchange-alt mr-1"></i> Cambiar Estado
                    </small>
                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                        <!-- Resolver: Solo OPEN o PENDING -->
                        <button type="button" class="btn btn-success"
                                title="Solo disponible en OPEN o PENDING">
                            <i class="fas fa-check-circle mr-1"></i> Resolver
                        </button>

                        <!-- Reabrir: Solo RESOLVED o CLOSED -->
                        <button type="button" class="btn btn-warning" disabled
                                title="Solo disponible en RESOLVED o CLOSED">
                            <i class="fas fa-redo mr-1"></i> Reabrir
                        </button>

                        <!-- Cerrar: Todos menos CLOSED -->
                        <button type="button" class="btn btn-secondary"
                                title="No disponible en CLOSED">
                            <i class="fas fa-times-circle mr-1"></i> Cerrar
                        </button>
                    </div>
                </div>

                <!-- Asignación -->
                <div class="border-top pt-3">
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-user-tie mr-1"></i> Asignación
                    </small>
                    <!-- Reasignar: Siempre disponible -->
                    <button type="button" class="btn btn-info">
                        <i class="fas fa-user-plus mr-1"></i> Reasignar
                    </button>
                </div>
            </div>
        </div>

        <!-- Attachments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Adjuntos
                    <span class="badge badge-info ml-2">2</span>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
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
        </div>

    </div>
    <!-- /.col -->

    <!-- Right Column: Chat -->
    <div class="col-md-7">
        <!-- DIRECT CHAT PRIMARY (MOCK DESIGN) -->
        <div class="card card-primary card-outline direct-chat direct-chat-primary">
            <div class="card-header">
                <h3 class="card-title">Direct Chat</h3>

                <div class="card-tools">
                    <span title="3 New Messages" class="badge bg-primary">3</span>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <!-- Conversations are loaded here -->
                <div class="direct-chat-messages" style="height: 500px;">
                    <!-- Message. Default to the left (Support) -->
                    <div class="direct-chat-msg">
                        <div class="direct-chat-infos clearfix">
                            <span class="direct-chat-name float-left">Juan Support</span>
                            <span class="direct-chat-timestamp float-right">22 Nov 10:45 am</span>
                        </div>
                        <!-- /.direct-chat-infos -->
                        <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=Juan+Support&size=128&background=6c757d&color=fff&bold=true" alt="Juan">
                        <!-- /.direct-chat-img -->
                        <div class="direct-chat-text" style="background-color: #d2d6de; color: #444;">
                            Hola Kylie! Entiendo tu preocupación. Déjame revisar tu caso en el sistema.
                        </div>
                        <!-- /.direct-chat-text -->
                    </div>
                    <!-- /.direct-chat-msg -->

                    <!-- Message to the right (User) -->
                    <div class="direct-chat-msg right">
                        <div class="direct-chat-infos clearfix">
                            <span class="direct-chat-name float-right">Kylie De la quintana quisbert</span>
                            <span class="direct-chat-timestamp float-left">22 Nov 10:50 am</span>
                        </div>
                        <!-- /.direct-chat-infos -->
                        <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&size=128&background=007bff&color=fff&bold=true" alt="Kylie">
                        <!-- /.direct-chat-img -->
                        <div class="direct-chat-text" style="background-color: #007bff; color: #fff;">
                            Perfecto, te agradezco que revises mi caso. Te envío también mi contrato actualizado.
                        </div>
                        <!-- /.direct-chat-text -->
                        <div style="margin: 8px 50px 0 0; padding: 8px; background-color: rgba(0,123,255,0.1); border-radius: 4px; border-left: 3px solid #007bff; position: relative;">
                            <div style="margin-bottom: 10px;">
                                <a href="#" style="text-decoration: none; font-size: 0.9rem;" class="text-primary">
                                    <i class="fas fa-file-pdf mr-2"></i>
                                    <strong>contrato_actualizado.pdf</strong>
                                </a>
                            </div>
                            <div style="font-size: 0.85rem; color: #999; margin-bottom: 8px;">
                                <span>Tamaño: 850 KB</span>
                                <span class="mx-2">•</span>
                                <span>Tipo: application/pdf</span>
                            </div>
                            <button type="button" style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); width: 35px; height: 35px; border: 2px solid rgba(0, 123, 255, 0.4); background: transparent; cursor: pointer; border-radius: 2px; display: flex; align-items: center; justify-content: center; color: rgba(0, 123, 255, 0.6); padding: 0; transition: all 0.2s ease;" onmouseover="this.style.borderColor='rgba(0, 123, 255, 1)'; this.style.color='rgba(0, 123, 255, 1)';" onmouseout="this.style.borderColor='rgba(0, 123, 255, 0.4)'; this.style.color='rgba(0, 123, 255, 0.6)';">
                                <i class="fas fa-download" style="font-size: 0.9rem;"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.direct-chat-msg -->

                    <!-- Message. Default to the left (Support) with attachment -->
                    <div class="direct-chat-msg">
                        <div class="direct-chat-infos clearfix">
                            <span class="direct-chat-name float-left">Juan Support</span>
                            <span class="direct-chat-timestamp float-right">22 Nov 11:00 am</span>
                        </div>
                        <!-- /.direct-chat-infos -->
                        <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=Juan+Support&size=128&background=6c757d&color=fff&bold=true" alt="Juan">
                        <!-- /.direct-chat-img -->
                        <div class="direct-chat-text" style="background-color: #d2d6de; color: #444;">
                            He encontrado el problema. Te envío la documentación actualizada y las instrucciones para resolver esto.
                        </div>
                        <!-- /.direct-chat-text -->
                        <div style="margin: 8px 0 0 50px; padding: 8px; background-color: #f8f9fa; border-radius: 4px; border-left: 3px solid #d2d6de; position: relative;">
                            <div style="margin-bottom: 10px;">
                                <a href="#" style="text-decoration: none; font-size: 0.9rem; color: #444;">
                                    <i class="fas fa-file-pdf mr-2"></i>
                                    <strong>solicitud_actualizada.pdf</strong>
                                </a>
                            </div>
                            <div style="font-size: 0.85rem; color: #999; margin-bottom: 8px;">
                                <span>Tamaño: 1.2 MB</span>
                                <span class="mx-2">•</span>
                                <span>Tipo: application/pdf</span>
                            </div>
                            <button type="button" style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); width: 35px; height: 35px; border: 2px solid rgba(68, 68, 68, 0.4); background: transparent; cursor: pointer; border-radius: 2px; display: flex; align-items: center; justify-content: center; color: rgba(68, 68, 68, 0.6); padding: 0; transition: all 0.2s ease;" onmouseover="this.style.borderColor='rgba(68, 68, 68, 1)'; this.style.color='rgba(68, 68, 68, 1)';" onmouseout="this.style.borderColor='rgba(68, 68, 68, 0.4)'; this.style.color='rgba(68, 68, 68, 0.6)';">
                                <i class="fas fa-download" style="font-size: 0.9rem;"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.direct-chat-msg -->

                    <!-- Message to the right (User) -->
                    <div class="direct-chat-msg right">
                        <div class="direct-chat-infos clearfix">
                            <span class="direct-chat-name float-right">Kylie De la quintana quisbert</span>
                            <span class="direct-chat-timestamp float-left">22 Nov 11:15 am</span>
                        </div>
                        <!-- /.direct-chat-infos -->
                        <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=Kylie+De+la+quintana&size=128&background=007bff&color=fff&bold=true" alt="Kylie">
                        <!-- /.direct-chat-img -->
                        <div class="direct-chat-text" style="background-color: #007bff; color: #fff;">
                            Perfecto! Muchas gracias Juan. Voy a revisar la documentación.
                        </div>
                        <!-- /.direct-chat-text -->
                    </div>
                    <!-- /.direct-chat-msg -->
                </div>
                <!--/.direct-chat-messages-->
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
                <form action="#" method="post">
                    <div class="input-group">
                        <span class="input-group-prepend">
                            <button class="btn btn-light border" type="button" data-toggle="tooltip" title="Attach File" style="border-color: #ced4da; background-color: #e9ecef; color: #495057;">
                                <i class="fas fa-paperclip"></i>
                            </button>
                        </span>
                        <input type="text" name="message" placeholder="Type Message ..." class="form-control">
                        <span class="input-group-append">
                            <button type="submit" class="btn btn-primary">Send</button>
                        </span>
                    </div>
                </form>
            </div>
            <!-- /.card-footer-->
        </div>
        <!--/.direct-chat -->

    </div>
    <!-- /.col -->

</div>
<!-- /.row -->

<script>
    // Simple script to handle close button in this mock view
    $('#btn-close-ticket-detail').on('click', function() {
        // Trigger event to go back to list
        $(document).trigger('tickets:show-list');
    });
</script>
