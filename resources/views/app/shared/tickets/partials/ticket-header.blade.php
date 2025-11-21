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

        @yield('ticket-actions')

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