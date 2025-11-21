@extends('layouts.authenticated')

@section('title')
Ticket {{ request()->get('ticket', 'TKT-XXXX-XXXXX') }}
@endsection

@section('content_header')
Detalle de Ticket
@endsection

@section('breadcrumbs')
<li class="breadcrumb-item">
    <a href="{{ route($role === 'AGENT' ? 'agent.tickets.index' : 'company.tickets.index') }}">Tickets</a>
</li>
<li class="breadcrumb-item active" id="breadcrumb-ticket-code">{{ request()->get('ticket', 'TKT-XXXX-XXXXX') }}</li>
@endsection

@section('content')
<div x-data="ticketManage()" x-init="loadTicket()">

    @include('app.shared.tickets.partials.ticket-header')

    <!-- Action Buttons - AGENT & COMPANY_ADMIN -->
    <div class="mailbox-controls with-border text-center p-3">
        <!-- Resolver (if open/pending) -->
        <button type="button"
                class="btn btn-success btn-sm"
                x-show="(ticket.status === 'open' || ticket.status === 'pending')"
                @click="openActionModal('resolve')"
                x-cloak>
            <i class="fas fa-check mr-2"></i>Resolver
        </button>

        <!-- Asignar -->
        <button type="button"
                class="btn btn-warning btn-sm"
                @click="openAssignModal()"
                x-cloak>
            <i class="fas fa-user-plus mr-2"></i>Asignar
        </button>

        <!-- Cerrar (if not closed) -->
        <button type="button"
                class="btn btn-danger btn-sm"
                x-show="ticket.status !== 'closed'"
                @click="openActionModal('close')"
                x-cloak>
            <i class="fas fa-times mr-2"></i>Cerrar
        </button>

        <!-- Editar -->
        <button type="button"
                class="btn btn-default btn-sm ml-2"
                @click="openEditModal()"
                x-cloak>
            <i class="fas fa-edit mr-2"></i>Editar
        </button>

        <!-- Imprimir -->
        <button type="button"
                class="btn btn-default btn-sm ml-2"
                @click="window.print()">
            <i class="fas fa-print mr-2"></i>Imprimir
        </button>

        <!-- Eliminar (COMPANY_ADMIN only, if closed) -->
        @if($role === 'COMPANY_ADMIN')
        <button type="button"
                class="btn btn-danger btn-sm ml-2"
                x-show="ticket.status === 'closed'"
                @click="deleteTicket()"
                x-cloak>
            <i class="fas fa-trash mr-2"></i>Eliminar
        </button>
        @endif
    </div>

    @include('app.shared.tickets.partials.ticket-responses')

    @include('app.shared.tickets.partials.ticket-response-form')

    @include('app.shared.tickets.partials.ticket-closed-message')

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

        // Wait for tokenManager to be ready
        async waitForTokenManager() {
            let attempts = 0;
            const maxAttempts = 50; // 5 seconds max (50 * 100ms)

            while (!window.tokenManager && attempts < maxAttempts) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }

            if (!window.tokenManager) {
                console.error('[Ticket Manage] TokenManager not available after 5 seconds');
                this.showError('Error de autenticación. Por favor, recarga la página.');
                throw new Error('TokenManager not available');
            }

            console.log('[Ticket Manage] TokenManager ready');
        },

        // Load Ticket Data
        async loadTicket() {
            // Wait for tokenManager to be available
            await this.waitForTokenManager();

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
                    const indexRoute = this.role === 'AGENT' ? '{{ route("agent.tickets.index") }}' :
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