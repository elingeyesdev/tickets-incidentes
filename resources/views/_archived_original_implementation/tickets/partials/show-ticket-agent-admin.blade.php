{{-- ==========================================================================
    SHOW TICKET - AGENT/COMPANY_ADMIN PARTIAL
    Self-contained Alpine.js component for viewing ticket details

    Props expected from parent:
    - ticketCode: string (the ticket code to load)
    - role: string ('AGENT' or 'COMPANY_ADMIN')

    Actions allowed:
    - Respond: Always (if not closed)
    - Upload attachment: Always (if not closed)
    - Resolve ticket: Change status to RESOLVED (any status)
    - Close ticket: Close from any status
    - Reopen ticket: Without time restrictions
    - Assign to agent: Select from company agents (AGENT only)
    - Edit title/category: Without status restrictions
    - Delete ticket: If status = CLOSED only (COMPANY_ADMIN only)
========================================================================== --}}

@push('css')
<style>
    /* Fix table layout for details */
    .details-table {
        table-layout: fixed;
        width: 100%;
    }
    .details-table td {
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        padding: 0.5rem !important;
    }
    .details-table td:first-child {
        width: 35%;
        font-weight: 600;
    }
    .details-table td:last-child {
        width: 65%;
    }

    /* Attachment Styling */
    .attachment-item-admin {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
        background-color: #f8f9fa;
    }

    .attachment-item-admin:hover {
        background-color: #e9ecef;
    }
</style>
@endpush

<div x-data="showTicketAgentAdmin('{{ $role ?? 'AGENT' }}')">
    {{-- Loading State --}}
    <template x-if="loading">
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <p class="mt-3 text-muted">Cargando ticket...</p>
            </div>
        </div>
    </template>

    {{-- Error State --}}
    <template x-if="error && !loading">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <p class="text-danger" x-text="error"></p>
                <button class="btn btn-primary" @click="loadTicket()">
                    <i class="fas fa-sync-alt mr-2"></i>Reintentar
                </button>
            </div>
        </div>
    </template>

    {{-- Ticket Content --}}
    <template x-if="ticket && !loading && !error">
        <div>
            {{-- Header Card --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-ticket-alt mr-2"></i>
                        <span x-text="ticket.ticket_code"></span>
                    </h3>
                    <div class="card-tools">
                        <span class="badge"
                              :class="{
                                  'badge-danger': ticket.status === 'open',
                                  'badge-warning': ticket.status === 'pending',
                                  'badge-success': ticket.status === 'resolved',
                                  'badge-secondary': ticket.status === 'closed'
                              }"
                              x-text="statusText(ticket.status)"></span>
                        <button class="btn btn-sm btn-default ml-2" @click="loadTicket()" title="Refrescar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <h5 x-text="ticket.title"></h5>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user mr-1"></i>
                        Creado por <strong x-text="ticket.created_by_user?.name || 'N/A'"></strong>
                        <span class="mx-2">|</span>
                        <i class="fas fa-clock mr-1"></i>
                        <span x-text="formatTimeAgo(ticket.created_at)"></span>
                    </p>
                </div>
            </div>

            {{-- Main Content - 2 Column Layout --}}
            <div class="row">
                {{-- LEFT Column: Details --}}
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Detalles</h3>
                            <div class="card-tools">
                                <button class="btn btn-sm btn-tool" @click="showEditModal = true" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0 details-table">
                                <tr>
                                    <td>Creador</td>
                                    <td x-text="ticket.created_by_user?.name || 'N/A'"></td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td x-text="ticket.created_by_user?.email || 'N/A'"></td>
                                </tr>
                                <tr>
                                    <td>Compañía</td>
                                    <td x-text="ticket.company?.name || 'N/A'"></td>
                                </tr>
                                <tr>
                                    <td>Categoría</td>
                                    <td x-text="ticket.category?.name || 'N/A'"></td>
                                </tr>
                                <tr>
                                    <td>Estado</td>
                                    <td>
                                        <span class="badge"
                                              :class="{
                                                  'badge-danger': ticket.status === 'open',
                                                  'badge-warning': ticket.status === 'pending',
                                                  'badge-success': ticket.status === 'resolved',
                                                  'badge-secondary': ticket.status === 'closed'
                                              }"
                                              x-text="statusText(ticket.status)"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Agente</td>
                                    <td>
                                        <span x-text="ticket.owner_agent?.name || 'Sin asignar'"
                                              :class="{ 'text-danger': !ticket.owner_agent }"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Creado</td>
                                    <td x-text="formatTimeAgo(ticket.created_at)"></td>
                                </tr>
                                <tr>
                                    <td>Actualizado</td>
                                    <td x-text="formatTimeAgo(ticket.updated_at)"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Description Card --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-align-left mr-2"></i>Descripción</h3>
                        </div>
                        <div class="card-body">
                            <p x-text="ticket.description" style="white-space: pre-wrap;"></p>

                            {{-- Initial Attachments (from ticket creation) --}}
                            <template x-if="initialAttachments.length > 0">
                                <div class="mt-3">
                                    <strong class="d-block mb-2"><i class="fas fa-paperclip mr-1"></i>Archivos adjuntos del ticket:</strong>
                                    <template x-for="attachment in initialAttachments" :key="attachment.id">
                                        <div class="attachment-item-admin mb-2">
                                            <i class="fas fa-file mr-2 text-primary" style="flex-shrink: 0;"></i>
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <a :href="'/api/tickets/attachments/' + attachment.id + '/download'"
                                                   target="_blank"
                                                   :title="attachment.file_name"
                                                   class="font-weight-bold text-truncate d-block"
                                                   x-text="attachment.file_name"></a>
                                                <small class="d-block text-muted">
                                                    <span x-text="formatFileSize(attachment.file_size_bytes)"></span>
                                                    <span class="mx-1">•</span>
                                                    <span x-text="attachment.file_type"></span>
                                                </small>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    {{-- Actions Card --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Acciones</h3>
                        </div>
                        <div class="card-body">
                            {{-- Assign Agent (AGENT only) --}}
                            <template x-if="role === 'AGENT' && ticket.status !== 'closed'">
                                <div class="form-group">
                                    <label><i class="fas fa-user-check mr-1"></i>Asignar Agente</label>
                                    <select class="form-control form-control-sm" x-model="selectedAgentId" @change="assignTicket()">
                                        <option value="">Seleccionar agente...</option>
                                        <template x-for="agent in agents" :key="agent.id">
                                            <option :value="agent.id" x-text="agent.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>

                            {{-- Resolve Button --}}
                            <template x-if="ticket.status !== 'resolved' && ticket.status !== 'closed'">
                                <button class="btn btn-success btn-sm btn-block mb-2" @click="resolveTicket()">
                                    <i class="fas fa-check-circle mr-2"></i>Resolver Ticket
                                </button>
                            </template>

                            {{-- Close Button --}}
                            <template x-if="ticket.status !== 'closed'">
                                <button class="btn btn-secondary btn-sm btn-block mb-2" @click="closeTicket()">
                                    <i class="fas fa-times-circle mr-2"></i>Cerrar Ticket
                                </button>
                            </template>

                            {{-- Reopen Button --}}
                            <template x-if="ticket.status === 'resolved' || ticket.status === 'closed'">
                                <button class="btn btn-warning btn-sm btn-block mb-2" @click="reopenTicket()">
                                    <i class="fas fa-redo mr-2"></i>Reabrir Ticket
                                </button>
                            </template>

                            {{-- Delete Button (COMPANY_ADMIN only, if closed) --}}
                            <template x-if="role === 'COMPANY_ADMIN' && ticket.status === 'closed'">
                                <button class="btn btn-danger btn-sm btn-block mb-2" @click="deleteTicket()">
                                    <i class="fas fa-trash mr-2"></i>Eliminar Ticket
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Attachments Card --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-paperclip mr-2"></i>Adjuntos
                                <span class="badge badge-info ml-2" x-text="attachments.length"></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <template x-if="attachments.length === 0">
                                <p class="text-muted mb-0">No hay archivos adjuntos</p>
                            </template>
                            <template x-for="attachment in attachments" :key="attachment.id">
                                <div class="attachment-item-admin">
                                    <i class="fas fa-file mr-2 text-primary" style="flex-shrink: 0;"></i>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <a :href="'/api/tickets/attachments/' + attachment.id + '/download'"
                                           target="_blank"
                                           :title="attachment.file_name"
                                           class="font-weight-bold text-truncate d-block"
                                           x-text="attachment.file_name"></a>
                                        <small class="d-block text-muted">
                                            <span x-text="formatFileSize(attachment.file_size_bytes)"></span>
                                            <span class="mx-1">•</span>
                                            <span x-text="attachment.file_type"></span>
                                        </small>
                                        <small class="d-block text-muted text-truncate">
                                            <i class="fas fa-user mr-1"></i>
                                            <span x-text="attachment.uploaded_by_name || 'Desconocido'"></span>
                                            <span class="mx-1">•</span>
                                            <span x-text="formatTimeAgo(attachment.created_at)"></span>
                                        </small>
                                    </div>
                                    <button class="btn btn-sm btn-danger ml-2" @click="deleteAttachment(attachment.id)" title="Eliminar (solo si eres el autor dentro de 30 min)" style="flex-shrink: 0;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </template>

                            {{-- Upload new attachment (if not closed) --}}
                            <template x-if="ticket.status !== 'closed'">
                                <div class="mt-3">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="adminAttachment" @change="uploadAttachment($event)">
                                        <label class="custom-file-label" for="adminAttachment">Agregar archivo...</label>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- RIGHT Column: Chat Component --}}
                <div class="col-md-8">
                    <x-ticket-chat :role="$role" />
                </div>
            </div>

            {{-- Edit Modal --}}
            <div class="modal fade" :class="{ 'show d-block': showEditModal }" tabindex="-1" x-show="showEditModal" @click.self="showEditModal = false">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Ticket</h5>
                            <button type="button" class="close" @click="showEditModal = false">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form @submit.prevent="updateTicket()">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Título</label>
                                    <input type="text" class="form-control" x-model="editTitle" required>
                                </div>
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select class="form-control" x-model="editCategoryId">
                                        <template x-for="cat in categories" :key="cat.id">
                                            <option :value="cat.id" x-text="cat.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" @click="showEditModal = false">Cancelar</button>
                                <button type="submit" class="btn btn-primary" :disabled="submitting">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show" x-show="showEditModal" @click="showEditModal = false"></div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function showTicketAgentAdmin(role) {
    return {
        ticketCode: '',
        role: role,
        ticket: null,
        attachments: [],
        initialAttachments: [],  // Archivos del ticket inicial (response_id === null)
        agents: [],
        categories: [],
        loading: true,
        error: null,
        submitting: false,
        showEditModal: false,
        editTitle: '',
        editCategoryId: '',
        selectedAgentId: '',

        async init() {
            await this.waitForTokenManager();
            // Escuchar evento para cargar ticket
            document.addEventListener('loadTicketDetail', async (e) => {
                this.ticketCode = e.detail;
                if (this.ticketCode) {
                    await this.loadTicket();
                    if (this.role === 'AGENT') {
                        await this.loadAgents();
                    }
                    await this.loadCategories();
                }
            });
        },

        async waitForTokenManager() {
            let attempts = 0;
            const maxAttempts = 50;
            while (!window.tokenManager && attempts < maxAttempts) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            if (!window.tokenManager) {
                this.error = 'Error de autenticación. Por favor, recarga la página.';
            }
        },

        async handleApiError(response) {
            let data;
            try {
                data = await response.json();
            } catch (e) {
                return 'Error de comunicación con el servidor';
            }

            switch(response.status) {
                case 400:
                    return data.message || 'Solicitud inválida';
                case 401:
                    return 'Sesión expirada. Por favor, inicia sesión nuevamente';
                case 403:
                    return data.message || 'No tienes permisos para realizar esta acción';
                case 404:
                    return data.message || 'Recurso no encontrado';
                case 413:
                    return 'Archivo demasiado grande (máximo 10 MB)';
                case 422:
                    if (data.errors) {
                        const errorMessages = [];
                        Object.keys(data.errors).forEach(field => {
                            errorMessages.push(...data.errors[field]);
                        });
                        return errorMessages.join('\n');
                    }
                    return data.message || 'Error de validación';
                case 500:
                    return 'Error del servidor. Por favor, intenta más tarde';
                default:
                    return data.message || 'Error desconocido';
            }
        },

        async loadTicket() {
            this.loading = true;
            this.error = null;
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${this.ticketCode}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Error al cargar el ticket');
                const data = await response.json();
                this.ticket = data.data;
                this.editTitle = this.ticket.title;
                this.editCategoryId = this.ticket.category_id;
                this.selectedAgentId = this.ticket.owner_agent_id || '';
                await Promise.all([this.loadResponses(), this.loadAttachments()]);
            } catch (error) {
                console.error('Error:', error);
                this.error = error.message || 'Error al cargar el ticket';
            } finally {
                this.loading = false;
            }
        },


        async loadResponses() {
            // Responses are now handled by the ticket-chat component
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
                this.attachments = data.data || [];

                // Separar archivos iniciales (response_id === null) de archivos de respuestas
                this.initialAttachments = this.attachments.filter(att => !att.response_id);
            } catch (error) {
                console.error('Error loading attachments:', error);
            }
        },

        async loadAgents() {
            try {
                const token = window.tokenManager.getAccessToken();
                const companyId = this.ticket?.company_id || '';
                const response = await fetch(`/api/companies/${companyId}/agents?per_page=100`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) return;
                const data = await response.json();
                this.agents = data.data || [];
            } catch (error) {
                console.error('Error loading agents:', error);
            }
        },

        async loadCategories() {
            try {
                const token = window.tokenManager.getAccessToken();
                const companyId = this.ticket?.company_id || '';
                const response = await fetch(`/api/tickets/categories?company_id=${companyId}&is_active=true&per_page=100`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) return;
                const data = await response.json();
                this.categories = data.data || [];
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        },



        async uploadAttachment(event) {
            const file = event.target.files[0];
            if (!file) return;
            try {
                const token = window.tokenManager.getAccessToken();
                const formData = new FormData();
                formData.append('file', file);
                const response = await fetch(`/api/tickets/${this.ticketCode}/attachments`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                if (!response.ok) throw new Error('Error al subir archivo');
                await this.loadAttachments();
                event.target.value = '';
                Swal.fire({ icon: 'success', title: 'Archivo subido', timer: 2000, showConfirmButton: false });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo subir el archivo' });
            }
        },

        async deleteAttachment(attachmentId) {
            const result = await Swal.fire({
                title: '¿Eliminar adjunto?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            });
            if (!result.isConfirmed) return;
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${this.ticketCode}/attachments/${attachmentId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Error al eliminar');
                await this.loadAttachments();
                Swal.fire({ icon: 'success', title: 'Eliminado', timer: 2000, showConfirmButton: false });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el archivo' });
            }
        },

        async resolveTicket() {
            const result = await Swal.fire({
                title: '¿Resolver ticket?',
                text: 'El ticket será marcado como resuelto',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, resolver'
            });
            if (!result.isConfirmed) return;
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${this.ticketCode}/resolve`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Error al resolver');
                await this.loadTicket();
                Swal.fire({ icon: 'success', title: 'Ticket resuelto', timer: 2000, showConfirmButton: false });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo resolver el ticket' });
            }
        },

        async closeTicket() {
            const result = await Swal.fire({
                title: '¿Cerrar ticket?',
                text: 'El ticket será marcado como cerrado',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar'
            });
            if (!result.isConfirmed) return;
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${this.ticketCode}/close`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Error al cerrar');
                await this.loadTicket();
                Swal.fire({ icon: 'success', title: 'Ticket cerrado', timer: 2000, showConfirmButton: false });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cerrar el ticket' });
            }
        },

        async reopenTicket() {
            const result = await Swal.fire({
                title: '¿Reabrir ticket?',
                text: 'El ticket será reabierto',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, reabrir'
            });
            if (!result.isConfirmed) return;
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${this.ticketCode}/reopen`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Error al reabrir');
                await this.loadTicket();
                Swal.fire({ icon: 'success', title: 'Ticket reabierto', timer: 2000, showConfirmButton: false });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo reabrir el ticket' });
            }
        },

        async assignTicket() {
            if (!this.selectedAgentId) return;
            this.submitting = true;
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${this.ticketCode}/assign`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ new_agent_id: this.selectedAgentId })
                });
                if (!response.ok) {
                    const errorMsg = await this.handleApiError(response);
                    throw new Error(errorMsg);
                }
                await this.loadTicket();
                Swal.fire({ icon: 'success', title: 'Agente asignado', timer: 2000, showConfirmButton: false });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'No se pudo asignar el agente' });
            } finally {
                this.submitting = false;
            }
        },

        async deleteTicket() {
            const result = await Swal.fire({
                title: '¿Eliminar ticket?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            });
            if (!result.isConfirmed) return;
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/${this.ticketCode}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Error al eliminar');
                Swal.fire({ icon: 'success', title: 'Ticket eliminado', timer: 2000, showConfirmButton: false });
                // Redirect back to list
                window.location.href = window.location.pathname;
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el ticket' });
            }
        },

        async updateTicket() {
            this.submitting = true;
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
                        title: this.editTitle,
                        category_id: this.editCategoryId
                    })
                });
                if (!response.ok) throw new Error('Error al actualizar');
                this.showEditModal = false;
                await this.loadTicket();
                Swal.fire({ icon: 'success', title: 'Ticket actualizado', timer: 2000, showConfirmButton: false });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el ticket' });
            } finally {
                this.submitting = false;
            }
        },

        statusText(status) {
            const map = { open: 'Abierto', pending: 'Pendiente', resolved: 'Resuelto', closed: 'Cerrado' };
            return map[status] || status;
        },

        formatTimeAgo(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            if (diffMins < 1) return 'Justo ahora';
            if (diffMins < 60) return `Hace ${diffMins} min`;
            if (diffHours < 24) return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
            if (diffDays < 7) return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
            return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },

        formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const now = new Date();
            const isToday = date.toDateString() === now.toDateString();

            const timeStr = date.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });

            if (isToday) {
                return `Hoy ${timeStr}`;
            }

            const dateStr = date.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short'
            });

            return `${dateStr} ${timeStr}`;
        },

        getAvatarUrl(name) {
            if (!name) return 'https://ui-avatars.com/api/?name=U&size=128&background=6c757d&color=fff';
            const colors = ['007bff', '28a745', 'dc3545', 'ffc107', '17a2b8', '6610f2', 'e83e8c', 'fd7e14'];
            const charCode = name.charCodeAt(0) || 0;
            const colorIndex = charCode % colors.length;
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&size=128&background=${colors[colorIndex]}&color=fff&bold=true`;
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

    }
}
</script>
@endpush
