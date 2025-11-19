@extends('layouts.authenticated')

@section('title')
Ticket {{ request()->get('ticket', 'TKT-XXXX-XXXXX') }}
@endsection

@section('content_header')
Detalle de Ticket
@endsection

@section('breadcrumbs')
<li class="breadcrumb-item">
    <a href="{{ route($role === 'USER' ? 'user.tickets.index' : ($role === 'AGENT' ? 'agent.tickets.index' : 'company.tickets.index')) }}">
        Tickets
    </a>
</li>
<li class="breadcrumb-item active" id="breadcrumb-ticket-code">{{ request()->get('ticket', 'TKT-XXXX-XXXXX') }}</li>
@endsection

@section('content')
<div x-data="ticketManage()" x-init="loadTicket()">

    <!-- Card Principal: Información del Ticket -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-ticket-alt mr-2"></i>
                <span x-text="ticket.ticket_code">TKT-XXXX-XXXXX</span>
            </h3>

            <div class="card-tools">
                <a href="{{ route($role === 'USER' ? 'user.tickets.index' : ($role === 'AGENT' ? 'agent.tickets.index' : 'company.tickets.index')) }}"
                   class="btn btn-tool" title="Volver a la lista">
                    <i class="fas fa-list"></i>
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Mailbox Read Info -->
            <div class="mailbox-read-info">
                <h5 x-text="ticket.title">Cargando...</h5>
                <h6>
                    <i class="fas fa-user mr-2"></i>Creado por:
                    <a href="#" x-text="ticket.created_by_user?.name || 'N/A'">N/A</a>
                    <span class="mailbox-read-time float-right">
                        <i class="fas fa-clock mr-2"></i>
                        <span x-text="formatDate(ticket.created_at)">N/A</span>
                    </span>
                </h6>
            </div>

            <!-- Metadata Section -->
            <div class="p-3 border-bottom">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <strong><i class="fas fa-info-circle mr-2"></i>Estado:</strong>
                            <span class="badge ml-2"
                                  :class="{
                                      'badge-danger': ticket.status === 'open',
                                      'badge-warning': ticket.status === 'pending',
                                      'badge-success': ticket.status === 'resolved',
                                      'badge-secondary': ticket.status === 'closed'
                                  }"
                                  x-text="statusText(ticket.status)">
                            </span>
                        </p>
                        <p class="mb-2">
                            <strong><i class="fas fa-tag mr-2"></i>Categoría:</strong>
                            <span class="text-muted ml-2" x-text="ticket.category?.name || 'N/A'">N/A</span>
                        </p>
                        @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                        <p class="mb-2">
                            <strong><i class="fas fa-building mr-2"></i>Empresa:</strong>
                            <span class="text-muted ml-2" x-text="ticket.company?.name || 'N/A'">N/A</span>
                        </p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                        <p class="mb-2">
                            <strong><i class="fas fa-user-tie mr-2"></i>Asignado a:</strong>
                            <span class="text-muted ml-2" x-text="ticket.owner_agent?.name || 'Sin asignar'">N/A</span>
                        </p>
                        @endif
                        <p class="mb-2">
                            <strong><i class="fas fa-comments mr-2"></i>Respuestas:</strong>
                            <span class="badge badge-info ml-2" x-text="ticket.responses_count || 0">0</span>
                        </p>
                        <p class="mb-2">
                            <strong><i class="fas fa-paperclip mr-2"></i>Adjuntos:</strong>
                            <span class="badge badge-secondary ml-2" x-text="ticket.attachments_count || 0">0</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mailbox-controls with-border text-center p-3">
                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                    <!-- Resolver (AGENT only, if open/pending) -->
                    <button type="button"
                            class="btn btn-success btn-sm"
                            x-show="(ticket.status === 'open' || ticket.status === 'pending')"
                            @click="openActionModal('resolve')"
                            x-cloak>
                        <i class="fas fa-check mr-2"></i>Resolver
                    </button>

                    <!-- Asignar (AGENT only) -->
                    <button type="button"
                            class="btn btn-warning btn-sm"
                            @click="openAssignModal()"
                            x-cloak>
                        <i class="fas fa-user-plus mr-2"></i>Asignar
                    </button>

                    <!-- Cerrar (AGENT, any status) -->
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            x-show="ticket.status !== 'closed'"
                            @click="openActionModal('close')"
                            x-cloak>
                        <i class="fas fa-times mr-2"></i>Cerrar
                    </button>

                    <!-- Editar (AGENT/COMPANY_ADMIN) -->
                    <button type="button"
                            class="btn btn-default btn-sm ml-2"
                            @click="openEditModal()"
                            x-cloak>
                        <i class="fas fa-edit mr-2"></i>Editar
                    </button>
                @endif

                @if($role === 'USER')
                    <!-- Reabrir (USER, if resolved/closed within 30 days) -->
                    <button type="button"
                            class="btn btn-info btn-sm"
                            x-show="(ticket.status === 'resolved' || ticket.status === 'closed') && canReopen()"
                            @click="openActionModal('reopen')"
                            x-cloak>
                        <i class="fas fa-redo mr-2"></i>Reabrir Ticket
                    </button>

                    <!-- Cerrar (USER, only if resolved within 30 days) -->
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            x-show="ticket.status === 'resolved' && canReopen()"
                            @click="openActionModal('close')"
                            x-cloak>
                        <i class="fas fa-times mr-2"></i>Cerrar
                    </button>
                @endif

                <!-- Imprimir (Todos) -->
                <button type="button"
                        class="btn btn-default btn-sm ml-2"
                        @click="window.print()">
                    <i class="fas fa-print mr-2"></i>Imprimir
                </button>

                @if($role === 'COMPANY_ADMIN')
                    <!-- Eliminar (COMPANY_ADMIN only, if closed) -->
                    <button type="button"
                            class="btn btn-danger btn-sm ml-2"
                            x-show="ticket.status === 'closed'"
                            @click="deleteTicket()"
                            x-cloak>
                        <i class="fas fa-trash mr-2"></i>Eliminar
                    </button>
                @endif
            </div>

            <!-- Descripción -->
            <div class="mailbox-read-message p-4">
                <h5><i class="fas fa-align-left mr-2"></i>Descripción</h5>
                <p x-html="ticket.description ? ticket.description.replace(/\n/g, '<br>') : 'Sin descripción'">
                    Cargando...
                </p>
            </div>
        </div>

        <!-- Attachments del Ticket Inicial -->
        <div class="card-footer bg-white" x-show="initialAttachments.length > 0" x-cloak>
            <h5><i class="fas fa-paperclip mr-2"></i>Adjuntos del Ticket (<span x-text="initialAttachments.length">0</span>)</h5>
            <ul class="mailbox-attachments d-flex align-items-stretch clearfix">
                <template x-for="attachment in initialAttachments" :key="attachment.id">
                    <li>
                        <span class="mailbox-attachment-icon"
                              :class="{'has-img': isImage(attachment.file_type)}">
                            <template x-if="isImage(attachment.file_type)">
                                <img :src="'/storage/' + attachment.file_url" :alt="attachment.file_name">
                            </template>
                            <template x-if="!isImage(attachment.file_type)">
                                <i :class="getFileIcon(attachment.file_type)"></i>
                            </template>
                        </span>
                        <div class="mailbox-attachment-info">
                            <a href="#"
                               class="mailbox-attachment-name"
                               @click.prevent="downloadAttachment(attachment.id)"
                               x-text="attachment.file_name">
                            </a>
                            <span class="mailbox-attachment-size clearfix mt-1">
                                <span x-text="formatFileSize(attachment.file_size_bytes)"></span>
                                <a href="#"
                                   class="btn btn-default btn-sm float-right"
                                   @click.prevent="downloadAttachment(attachment.id)">
                                    <i class="fas fa-cloud-download-alt"></i>
                                </a>
                            </span>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <!-- Card: Timeline de Respuestas -->
    <div class="card" x-show="responses.length > 0" x-cloak>
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-comments mr-2"></i>Conversación (<span x-text="responses.length">0</span> respuestas)
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="timeline">
                <template x-for="(response, index) in responses" :key="response.id">
                    <div>
                        <!-- Time Label (agrupación por día) -->
                        <template x-if="index === 0 || !isSameDay(responses[index-1].created_at, response.created_at)">
                            <div class="time-label">
                                <span :class="{
                                    'bg-primary': response.author_type === 'user',
                                    'bg-success': response.author_type === 'agent'
                                }" x-text="formatDateLabel(response.created_at)"></span>
                            </div>
                        </template>

                        <!-- Timeline Item -->
                        <div>
                            <i :class="{
                                'fas fa-user bg-blue': response.author_type === 'user',
                                'fas fa-user-tie bg-green': response.author_type === 'agent'
                            }"></i>

                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i>
                                    <span x-text="formatTime(response.created_at)"></span>
                                </span>

                                <h3 class="timeline-header">
                                    <a href="#" x-text="response.author?.name || 'N/A'">N/A</a>
                                    <template x-if="response.author_type === 'user'">
                                        <span class="badge badge-primary badge-sm ml-2">USER</span>
                                    </template>
                                    <template x-if="response.author_type === 'agent'">
                                        <span class="badge badge-success badge-sm ml-2">AGENT</span>
                                    </template>
                                    respondió
                                </h3>

                                <div class="timeline-body" x-html="response.content.replace(/\n/g, '<br>')"></div>

                                <!-- Attachments de esta respuesta -->
                                <template x-if="response.attachments && response.attachments.length > 0">
                                    <div class="timeline-footer">
                                        <span class="badge badge-primary">
                                            <i class="fas fa-paperclip mr-1"></i>
                                            <span x-text="response.attachments.length"></span> adjunto(s)
                                        </span>
                                        <template x-for="att in response.attachments" :key="att.id">
                                            <a href="#"
                                               class="btn btn-sm btn-default ml-2"
                                               @click.prevent="downloadAttachment(att.id)">
                                                <i class="fas fa-download mr-1"></i>
                                                <span x-text="att.file_name"></span>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Timeline End -->
                <div>
                    <i class="fas fa-clock bg-gray"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Formulario de Nueva Respuesta -->
    <div class="card card-primary" x-show="ticket.status !== 'closed'" x-cloak>
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-reply mr-2"></i>Agregar Respuesta
            </h3>
        </div>
        <form @submit.prevent="submitResponse()">
            <div class="card-body">
                <div class="form-group">
                    <label for="response-content">
                        <i class="fas fa-comment-dots mr-2"></i>Tu respuesta
                    </label>
                    <textarea class="form-control"
                              id="response-content"
                              rows="5"
                              x-model="newResponse.content"
                              placeholder="Escribe tu respuesta aquí..."
                              required></textarea>
                    <small class="form-text text-muted">
                        Máximo 5000 caracteres
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-paperclip mr-2"></i>Adjuntar archivos (Opcional)
                    </label>
                    <div class="custom-file">
                        <input type="file"
                               class="custom-file-input"
                               id="response-files"
                               @change="handleFileSelection"
                               multiple>
                        <label class="custom-file-label" for="response-files">
                            Seleccionar archivos...
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Máximo 5 archivos, 10 MB cada uno.
                        Tipos permitidos: PDF, TXT, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG, GIF, MP4
                    </small>

                    <!-- Lista de archivos seleccionados -->
                    <template x-if="newResponse.files.length > 0">
                        <ul class="list-unstyled mt-2">
                            <template x-for="(file, index) in newResponse.files" :key="index">
                                <li class="text-sm">
                                    <i class="fas fa-file mr-2"></i>
                                    <span x-text="file.name"></span>
                                    (<span x-text="formatFileSize(file.size)"></span>)
                                    <a href="#"
                                       class="text-danger ml-2"
                                       @click.prevent="removeFile(index)">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit"
                        class="btn btn-primary"
                        :disabled="isSubmitting || !newResponse.content.trim()">
                    <template x-if="isSubmitting">
                        <span>
                            <i class="fas fa-spinner fa-spin mr-2"></i>Enviando...
                        </span>
                    </template>
                    <template x-if="!isSubmitting">
                        <span>
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Respuesta
                        </span>
                    </template>
                </button>
                <button type="button"
                        class="btn btn-default ml-2"
                        @click="resetResponseForm()">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
            </div>
        </form>
    </div>

    <!-- Mensaje si ticket está cerrado -->
    <div class="card" x-show="ticket.status === 'closed'" x-cloak>
        <div class="card-body bg-light text-center p-4">
            <i class="fas fa-lock fa-3x text-muted mb-3"></i>
            <h5>Este ticket está cerrado</h5>
            <p class="text-muted">
                No se pueden agregar más respuestas a tickets cerrados.
                @if($role === 'USER')
                Si necesitas reabrir este ticket, puedes hacerlo dentro de los 30 días posteriores al cierre.
                @endif
            </p>
        </div>
    </div>

    @include('app.shared.tickets.modals.assign-agent')
    @include('app.shared.tickets.modals.edit-ticket')
    @include('app.shared.tickets.modals.confirm-action')
</div>
@endsection

@section('js')
<script>
function ticketManage() {
    return {
        // State
        role: '{{ $role }}',
        ticketCode: '{{ request()->get("ticket") }}',
        companyId: '{{ $companyId ?? "" }}',
        ticket: {},
        responses: [],
        initialAttachments: [],
        isSubmitting: false,

        // New Response Form
        newResponse: {
            content: '',
            files: []
        },

        // Modal State
        actionModal: {
            action: '',
            title: '',
            message: '',
            note: ''
        },

        editModal: {
            title: '',
            category_id: ''
        },

        assignModal: {
            agent_id: '',
            note: ''
        },

        // Load Ticket Data
        async loadTicket() {
            try {
                const token = window.tokenManager.getAccessToken();

                // Load ticket details
                const ticketResponse = await fetch(`/api/tickets/${this.ticketCode}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!ticketResponse.ok) {
                    throw new Error('Error al cargar el ticket');
                }

                const ticketData = await ticketResponse.json();
                this.ticket = ticketData.data;

                // Update breadcrumb
                document.getElementById('breadcrumb-ticket-code').textContent = this.ticket.ticket_code;

                // Load responses
                await this.loadResponses();

                // Load attachments
                await this.loadAttachments();

            } catch (error) {
                console.error('Error:', error);
                this.showError('Error al cargar el ticket');
            }
        },

        async loadResponses() {
            try {
                const token = window.tokenManager.getAccessToken();

                const response = await fetch(`/api/tickets/${this.ticketCode}/responses`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error al cargar respuestas');

                const data = await response.json();
                this.responses = data.data || [];

            } catch (error) {
                console.error('Error:', error);
            }
        },

        async loadAttachments() {
            try {
                const token = window.tokenManager.getAccessToken();

                const response = await fetch(`/api/tickets/${this.ticketCode}/attachments`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error al cargar adjuntos');

                const data = await response.json();
                // Separate initial attachments (no response_id) from response attachments
                this.initialAttachments = (data.data || []).filter(att => !att.response_id);

            } catch (error) {
                console.error('Error:', error);
            }
        },

        // Submit Response
        async submitResponse() {
            if (!this.newResponse.content.trim()) {
                this.showError('Por favor escribe una respuesta');
                return;
            }

            this.isSubmitting = true;

            try {
                const token = window.tokenManager.getAccessToken();

                // 1. Create response
                const responseData = await fetch(`/api/tickets/${this.ticketCode}/responses`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        content: this.newResponse.content
                    })
                });

                if (!responseData.ok) {
                    const error = await responseData.json();
                    throw new Error(error.message || 'Error al enviar respuesta');
                }

                const responseResult = await responseData.json();
                const newResponseId = responseResult.data.id;

                // 2. Upload files if any
                if (this.newResponse.files.length > 0) {
                    for (const file of this.newResponse.files) {
                        const formData = new FormData();
                        formData.append('file', file);

                        await fetch(`/api/tickets/${this.ticketCode}/responses/${newResponseId}/attachments`, {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                    }
                }

                // 3. Reload data
                await this.loadTicket();

                // 4. Reset form
                this.resetResponseForm();

                this.showSuccess('Respuesta enviada exitosamente');

            } catch (error) {
                console.error('Error:', error);
                this.showError(error.message || 'Error al enviar respuesta');
            } finally {
                this.isSubmitting = false;
            }
        },

        // File Handling
        handleFileSelection(event) {
            const files = Array.from(event.target.files);

            // Validate max 5 files
            if (files.length > 5) {
                this.showError('Máximo 5 archivos permitidos');
                event.target.value = '';
                return;
            }

            // Validate file size (10MB each)
            for (const file of files) {
                if (file.size > 10 * 1024 * 1024) {
                    this.showError(`El archivo ${file.name} excede el tamaño máximo de 10 MB`);
                    event.target.value = '';
                    return;
                }
            }

            this.newResponse.files = files;
        },

        removeFile(index) {
            this.newResponse.files.splice(index, 1);
            document.getElementById('response-files').value = '';
        },

        resetResponseForm() {
            this.newResponse = {
                content: '',
                files: []
            };
            document.getElementById('response-files').value = '';
        },

        // Actions
        async openActionModal(action) {
            this.actionModal.action = action;
            this.actionModal.note = '';

            const config = {
                resolve: {
                    title: 'Resolver Ticket',
                    message: '¿Estás seguro de marcar este ticket como resuelto?',
                    color: 'success'
                },
                close: {
                    title: 'Cerrar Ticket',
                    message: '¿Estás seguro de cerrar este ticket?',
                    color: 'danger'
                },
                reopen: {
                    title: 'Reabrir Ticket',
                    message: '¿Por qué deseas reabrir este ticket?',
                    color: 'info'
                }
            };

            const cfg = config[action];
            this.actionModal.title = cfg.title;
            this.actionModal.message = cfg.message;

            $('#confirmActionModal').modal('show');
        },

        async executeAction() {
            try {
                const token = window.tokenManager.getAccessToken();
                const endpoint = `/api/tickets/${this.ticketCode}/${this.actionModal.action}`;

                const bodyData = {};
                if (this.actionModal.note) {
                    const noteKey = this.actionModal.action === 'resolve' ? 'resolution_note' :
                                  this.actionModal.action === 'close' ? 'close_note' :
                                  'reopen_reason';
                    bodyData[noteKey] = this.actionModal.note;
                }

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(bodyData)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error al ejecutar acción');
                }

                $('#confirmActionModal').modal('hide');
                await this.loadTicket();
                this.showSuccess('Acción ejecutada exitosamente');

            } catch (error) {
                console.error('Error:', error);
                this.showError(error.message);
            }
        },

        openAssignModal() {
            this.assignModal.agent_id = '';
            this.assignModal.note = '';
            $('#assignAgentModal').modal('show');
        },

        async executeAssign() {
            if (!this.assignModal.agent_id) {
                this.showError('Por favor selecciona un agente');
                return;
            }

            try {
                const token = window.tokenManager.getAccessToken();

                const response = await fetch(`/api/tickets/${this.ticketCode}/assign`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        new_agent_id: this.assignModal.agent_id,
                        assignment_note: this.assignModal.note
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error al asignar ticket');
                }

                $('#assignAgentModal').modal('hide');
                await this.loadTicket();
                this.showSuccess('Ticket asignado exitosamente');

            } catch (error) {
                console.error('Error:', error);
                this.showError(error.message);
            }
        },

        openEditModal() {
            this.editModal.title = this.ticket.title;
            this.editModal.category_id = this.ticket.category_id;
            $('#editTicketModal').modal('show');
        },

        async executeEdit() {
            try {
                const token = window.tokenManager.getAccessToken();

                const response = await fetch(`/api/tickets/${this.ticketCode}`, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: this.editModal.title,
                        category_id: this.editModal.category_id
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error al editar ticket');
                }

                $('#editTicketModal').modal('hide');
                await this.loadTicket();
                this.showSuccess('Ticket actualizado exitosamente');

            } catch (error) {
                console.error('Error:', error);
                this.showError(error.message);
            }
        },

        async deleteTicket() {
            if (!confirm('¿ESTÁS SEGURO de eliminar permanentemente este ticket? Esta acción no se puede deshacer.')) {
                return;
            }

            try {
                const token = window.tokenManager.getAccessToken();

                const response = await fetch(`/api/tickets/${this.ticketCode}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error al eliminar ticket');
                }

                this.showSuccess('Ticket eliminado exitosamente');

                // Redirect to tickets list
                setTimeout(() => {
                    const indexRoute = this.role === 'USER' ? '{{ route("user.tickets.index") }}' :
                                     this.role === 'AGENT' ? '{{ route("agent.tickets.index") }}' :
                                     '{{ route("company.tickets.index") }}';
                    window.location.href = indexRoute;
                }, 1500);

            } catch (error) {
                console.error('Error:', error);
                this.showError(error.message);
            }
        },

        async downloadAttachment(attachmentId) {
            try {
                const token = window.tokenManager.getAccessToken();

                const response = await fetch(`/api/tickets/attachments/${attachmentId}/download`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!response.ok) throw new Error('Error al descargar archivo');

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;

                // Get filename from Content-Disposition header
                const contentDisposition = response.headers.get('Content-Disposition');
                const filenameMatch = contentDisposition && contentDisposition.match(/filename="(.+)"/);
                a.download = filenameMatch ? filenameMatch[1] : 'download';

                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

            } catch (error) {
                console.error('Error:', error);
                this.showError('Error al descargar archivo');
            }
        },

        // Utilities
        canReopen() {
            if (!this.ticket.closed_at) return true;

            const closedDate = new Date(this.ticket.closed_at);
            const now = new Date();
            const diffDays = (now - closedDate) / (1000 * 60 * 60 * 24);

            return diffDays <= 30;
        },

        statusText(status) {
            const map = {
                open: 'Abierto',
                pending: 'Pendiente',
                resolved: 'Resuelto',
                closed: 'Cerrado'
            };
            return map[status] || status;
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatDateLabel(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        },

        formatTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        isSameDay(date1, date2) {
            if (!date1 || !date2) return false;
            const d1 = new Date(date1);
            const d2 = new Date(date2);
            return d1.toDateString() === d2.toDateString();
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        isImage(fileType) {
            return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileType?.toLowerCase());
        },

        getFileIcon(fileType) {
            const iconMap = {
                pdf: 'far fa-file-pdf',
                doc: 'far fa-file-word',
                docx: 'far fa-file-word',
                xls: 'far fa-file-excel',
                xlsx: 'far fa-file-excel',
                txt: 'far fa-file-alt',
                csv: 'far fa-file-csv',
                mp4: 'far fa-file-video'
            };
            return iconMap[fileType?.toLowerCase()] || 'far fa-file';
        },

        showSuccess(message) {
            // Using SweetAlert2 (already loaded in authenticated.blade.php)
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        },

        showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }
    }
}
</script>
@endsection
