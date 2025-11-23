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
        <x-ticket-chat />
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
