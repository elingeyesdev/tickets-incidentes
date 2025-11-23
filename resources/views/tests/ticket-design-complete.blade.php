@extends('layouts.authenticated')

@section('title', 'Ticket #2025-001 - Complete Design Example')

@section('content_header', 'Error en la facturación del mes de Noviembre')

@section('breadcrumbs')
    <li class="breadcrumb-item"><i class="fas fa-ticket-alt mr-2"></i>Ticket #2025-001</li>
    <li class="breadcrumb-item active">Ejemplo Completo</li>
@endsection

@section('content')

<div class="row" x-data="ticketDemo()">
    <!-- LEFT COLUMN: Sidebar -->
    <div class="col-md-3">

        <!-- Create Ticket Button (Top) -->
        <button type="button" class="btn btn-primary btn-block mb-3" @click="showCreateForm = !showCreateForm">
            <i class="fas fa-plus mr-2"></i>Crear Ticket
        </button>

        <!-- Folders Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-folder mr-2"></i>Folders
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="#" class="nav-link active">
                            <i class="fas fa-inbox mr-2"></i> All Tickets
                            <span class="badge badge-primary float-right">12</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-star mr-2"></i> New Tickets
                            <span class="badge badge-info float-right">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-user-check mr-2"></i> My Assigned
                            <span class="badge badge-success float-right">5</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-comments mr-2"></i> Awaiting Response
                            <span class="badge badge-danger float-right">2</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Statuses Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-2"></i>Statuses
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="far fa-circle text-info mr-2"></i> New
                            <span class="badge badge-info float-right">1</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link active">
                            <i class="far fa-circle text-danger mr-2"></i> Open
                            <span class="badge badge-danger float-right">5</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="far fa-circle text-warning mr-2"></i> Pending
                            <span class="badge badge-warning float-right">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="far fa-circle text-success mr-2"></i> Resolved
                            <span class="badge badge-success float-right">2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="far fa-circle text-secondary mr-2"></i> Closed
                            <span class="badge badge-secondary float-right">1</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>


    </div>
    <!-- /.col -->

    <!-- RIGHT COLUMN: Main Content Area -->
    <div class="col-md-9">

        <!-- VISTA DE CREAR TICKET -->
        <template x-if="showCreateForm">
            @push('css')
            <style>
                /* Select2 height consistency with form-control */
                .select2-container--bootstrap4 .select2-selection--single {
                    height: 38px !important;
                    min-height: 38px !important;
                    display: flex !important;
                    align-items: center !important;
                    padding: 0 !important;
                }

                .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
                    line-height: 38px !important;
                    padding-left: 0.75rem !important;
                    padding-right: 0 !important;
                    font-size: 1rem !important;
                }

                .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
                    color: #6c757d !important;
                }

                .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
                    height: 36px !important;
                    top: 1px !important;
                    right: 0.75rem !important;
                }

                .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
                    margin-top: 0 !important;
                }


                /* Archivo list styling */
                .ticket-file-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 0.75rem;
                    border: 1px solid #dee2e6;
                    border-radius: 0.25rem;
                    margin-bottom: 0.5rem;
                    background-color: #f8f9fa;
                }

                .ticket-file-item:hover {
                    background-color: #e9ecef;
                }

                .ticket-file-info {
                    display: flex;
                    align-items: center;
                    flex: 1;
                }

                .ticket-file-icon {
                    font-size: 1.25rem;
                    margin-right: 0.75rem;
                    min-width: 1.5rem;
                    text-align: center;
                }

                .ticket-file-details {
                    flex: 1;
                }

                .ticket-file-name {
                    display: block;
                    font-weight: 500;
                    color: #212529;
                    word-break: break-word;
                }

                .ticket-file-size {
                    display: block;
                    font-size: 0.875rem;
                    color: #6c757d;
                    margin-top: 0.25rem;
                }
            </style>
            @endpush

            <div class="card card-primary card-outline">
                <!-- Card Header -->
                <div class="card-header with-border">
                    <h3 class="card-title">Crear Nuevo Ticket</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" @click="showCreateForm = false" title="Volver a lista">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Card Body -->
                <form @submit.prevent="createTicket()" novalidate>
                    <div class="card-body">

                        <!-- Row 1: Company & Category -->
                        <div class="row">
                            <!-- Compañía -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="createCompany">Compañía <span class="text-danger">*</span></label>
                                    <select id="createCompany"
                                            class="form-control select2"
                                            x-model="newTicket.company_id"
                                            data-placeholder="Selecciona una compañía..."
                                            required>
                                        <option value="">Selecciona una compañía...</option>
                                        <template x-for="company in companies" :key="company.id">
                                            <option :value="company.id" x-text="company.name"></option>
                                        </template>
                                    </select>
                                    <small class="form-text text-muted d-block mt-1">
                                        Selecciona la compañía para la que deseas crear el ticket
                                    </small>
                                </div>
                            </div>

                            <!-- Categoría -->
                            <div class="col-md-6">
                                <template x-if="!newTicket.company_id">
                                    <div class="form-group">
                                        <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                                        <div class="form-control" style="background-color: #e9ecef; color: #6c757d; cursor: not-allowed;">
                                            Selecciona una compañía primero
                                        </div>
                                        <small class="form-text text-muted d-block mt-1">
                                            Debes seleccionar una compañía antes de elegir categoría
                                        </small>
                                    </div>
                                </template>

                                <template x-if="newTicket.company_id">
                                    <div class="form-group">
                                        <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                                        <select id="createCategory"
                                                class="form-control select2"
                                                x-model="newTicket.category_id"
                                                data-placeholder="Selecciona una categoría..."
                                                required>
                                            <option></option>
                                        </select>
                                        <small class="form-text text-muted d-block mt-1">
                                            Selecciona la categoría que mejor describe tu problema
                                        </small>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Asunto -->
                        <div class="form-group">
                            <label for="createTitle">
                                Asunto <span class="text-danger">*</span>
                                <span class="float-right">
                                    <small x-text="newTicket.title.length + '/255'" class="text-muted"></small>
                                </span>
                            </label>
                            <input type="text"
                                   id="createTitle"
                                   class="form-control"
                                   x-model="newTicket.title"
                                   placeholder="Describe brevemente el problema"
                                   maxlength="255"
                                   autocomplete="off"
                                   required>
                            <small class="form-text text-muted d-block mt-1">
                                Mínimo 5, máximo 255 caracteres
                            </small>
                            <template x-if="newTicket.title.trim().length > 0 && newTicket.title.trim().length < 5">
                                <small class="form-text text-danger d-block mt-1">
                                    El asunto debe tener al menos 5 caracteres
                                </small>
                            </template>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group">
                            <label for="createDescription">
                                Descripción <span class="text-danger">*</span>
                                <span class="float-right">
                                    <small x-text="newTicket.description.length + '/5000'" :class="newTicket.description.length < 10 ? 'text-danger' : 'text-muted'"></small>
                                </span>
                            </label>
                            <textarea id="createDescription"
                                      class="form-control"
                                      x-model="newTicket.description"
                                      placeholder="Proporciona todos los detalles necesarios..."
                                      rows="6"
                                      maxlength="5000"
                                      required
                                      style="resize: vertical;"></textarea>
                            <small class="form-text text-muted d-block mt-1">
                                Mínimo 10, máximo 5000 caracteres
                            </small>
                            <template x-if="newTicket.description.trim().length > 0 && newTicket.description.trim().length < 10">
                                <small class="form-text text-danger d-block mt-1">
                                    La descripción debe tener al menos 10 caracteres (tienes <span x-text="newTicket.description.trim().length"></span>)
                                </small>
                            </template>
                        </div>

                        <!-- Archivos Adjuntos -->
                        <div class="form-group">
                            <label for="createAttachment">
                                Archivos Adjuntos
                                <span class="badge" :class="newTicket.files.length >= 5 ? 'badge-danger' : 'badge-secondary'" x-text="newTicket.files.length + '/5'"></span>
                            </label>
                            <div class="custom-file">
                                <input type="file"
                                       id="createAttachment"
                                       class="custom-file-input"
                                       @change="handleTicketFiles($event)"
                                       multiple
                                       :disabled="newTicket.files.length >= 5"
                                       accept=".pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.mp4">
                                <label class="custom-file-label text-truncate" for="createAttachment">
                                    <span x-show="newTicket.files.length === 0">Selecciona archivos (máx 5, 10MB c/u)</span>
                                    <span x-show="newTicket.files.length > 0" x-text="newTicket.files.length + ' archivo(s) seleccionado(s)'"></span>
                                </label>
                            </div>
                            <small class="form-text text-muted d-block mt-1">
                                Máximo 5 archivos de 10MB cada uno. Formatos: PDF, TXT, LOG, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF, BMP, WEBP, SVG, MP4
                            </small>
                            <template x-if="newTicket.files.length >= 5">
                                <small class="form-text text-warning d-block mt-1">
                                    Límite máximo de archivos alcanzado
                                </small>
                            </template>
                        </div>

                        <!-- Lista de Archivos -->
                        <template x-if="newTicket.files.length > 0">
                            <div class="form-group">
                                <label class="d-block mb-2">Archivos Seleccionados</label>
                                <template x-for="(file, index) in newTicket.files" :key="index">
                                    <div class="ticket-file-item">
                                        <div class="ticket-file-info">
                                            <div class="ticket-file-icon" :class="{
                                                'text-danger': file.name.endsWith('.pdf'),
                                                'text-info': file.name.endsWith('.doc') || file.name.endsWith('.docx'),
                                                'text-success': file.name.endsWith('.xlsx') || file.name.endsWith('.xls'),
                                                'text-warning': file.name.endsWith('.jpg') || file.name.endsWith('.jpeg') || file.name.endsWith('.png') || file.name.endsWith('.gif'),
                                                'text-primary': file.name.endsWith('.txt') || file.name.endsWith('.log'),
                                                'text-secondary': !file.name.endsWith('.pdf') && !file.name.endsWith('.doc') && !file.name.endsWith('.docx') && !file.name.endsWith('.xlsx') && !file.name.endsWith('.xls') && !file.name.endsWith('.jpg') && !file.name.endsWith('.jpeg') && !file.name.endsWith('.png') && !file.name.endsWith('.gif') && !file.name.endsWith('.txt') && !file.name.endsWith('.log')
                                            }">
                                                <i class="fas fa-file"></i>
                                            </div>
                                            <div class="ticket-file-details">
                                                <span class="ticket-file-name" x-text="file.name"></span>
                                                <span class="ticket-file-size" x-text="formatFileSize(file.size)"></span>
                                            </div>
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm btn-danger ml-2"
                                                @click="removeTicketFile(index)"
                                                title="Eliminar archivo">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button"
                                    class="btn btn-secondary"
                                    @click="showCreateForm = false">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </button>
                            <button type="submit"
                                    class="btn btn-primary"
                                    :disabled="isCreating || !newTicket.company_id || newTicket.title.trim().length < 5 || newTicket.description.trim().length < 10 || !newTicket.category_id"
                                    x-cloak>
                                <template x-if="!isCreating">
                                    <span><i class="fas fa-paper-plane mr-2"></i>Enviar Ticket</span>
                                </template>
                                <template x-if="isCreating">
                                    <span>
                                        <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                                        Enviando...
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </template>

        <!-- VISTA DE TICKET NORMAL -->
        <template x-if="!showCreateForm">

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
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
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
                <div class="card card-outline card-success" x-data="ticketActions()">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Acciones</h3>
                            <div class="d-flex gap-2 align-items-center">
                                <select x-model="ticket.status" class="form-control form-control-sm" style="width: 140px;">
                                    <option value="open">OPEN</option>
                                    <option value="pending">PENDING</option>
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
                                        :disabled="!(['open', 'pending'].includes(ticket.status))"
                                        title="Solo disponible en OPEN o PENDING">
                                    <i class="fas fa-check-circle mr-1"></i> Resolver
                                </button>

                                <!-- Reabrir: Solo RESOLVED o CLOSED -->
                                <button type="button" class="btn btn-warning"
                                        :disabled="!(['resolved', 'closed'].includes(ticket.status))"
                                        title="Solo disponible en RESOLVED o CLOSED">
                                    <i class="fas fa-redo mr-1"></i> Reabrir
                                </button>

                                <!-- Cerrar: Todos menos CLOSED -->
                                <button type="button" class="btn btn-secondary"
                                        :disabled="ticket.status === 'closed'"
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

    </div>
    <!-- /.col -->

        </template>
        <!-- /.VISTA DE TICKET NORMAL -->

    </div>
    <!-- /.col -->

</div>
<!-- /.row -->

@endsection

@section('js')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function ticketDemo() {
        return {
            showCreateForm: false,
            newTicket: {
                company_id: '',
                category_id: '',
                title: '',
                description: '',
                files: []
            },
            companies: [
                { id: 1, name: 'Acme Corporation' },
                { id: 2, name: 'Tech Solutions Inc.' },
                { id: 3, name: 'Global Enterprises' }
            ],
            isCreating: false,
            createTicket() {
                // Placeholder for ticket creation
                console.log('Creating ticket:', this.newTicket);
            },
            handleTicketFiles(event) {
                // Placeholder for file handling
                console.log('Files selected:', event.target.files);
            },
            removeTicketFile(index) {
                this.newTicket.files.splice(index, 1);
            },
            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }
        }
    }

    function ticketActions() {
        return {
            ticket: {
                status: 'open',
            },
            confirmDelete() {
                if (this.ticket.status === 'closed') {
                    alert('¿Está seguro que desea eliminar este ticket? Esta acción es irreversible.');
                }
            }
        }
    }
</script>
@endsection
