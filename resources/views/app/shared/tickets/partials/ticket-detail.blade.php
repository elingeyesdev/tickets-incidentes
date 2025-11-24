{{-- Ticket Detail Container --}}
<div id="ticket-detail-container">
    {{-- Header Component --}}
    @include('app.shared.tickets.components.header')

    <div class="row">
        {{-- Left Column: Info, Actions, Attachments --}}
        <div class="col-md-5">
            @include('app.shared.tickets.components.info-card')
            @include('app.shared.tickets.components.actions')
            @include('app.shared.tickets.components.attachments')
        </div>

        {{-- Right Column: Chat --}}
        <div class="col-md-7">
            {{-- Chat Component (Existing) --}}
            <x-ticket-chat />
        </div>
    </div>
</div>

<script>
(function() {
    console.log('[Ticket Detail] Script loaded - waiting for jQuery...');

    function initTicketDetail() {
        console.log('[Ticket Detail] jQuery available - Initializing');

        // ==============================================================
        // CONFIGURATION
        // ==============================================================
        const endpoints = {
            get: '/api/tickets/', // + id
            resolve: '/api/tickets/', // + code + /resolve
            close: '/api/tickets/', // + code + /close
            reopen: '/api/tickets/', // + code + /reopen
            assign: '/api/tickets/' // + code + /assign
        };

        let currentTicket = null;

        // ==============================================================
        // EVENT LISTENERS
        // ==============================================================

        // 1. View Details Event (Triggered from List)
        $(document).on('tickets:view-details', function(e, ticketId) {
            loadTicketDetails(ticketId);
        });

        // 2. Close Detail View (Back to List)
        $('#btn-close-ticket-detail').on('click', function() {
            $(document).trigger('tickets:show-list');
        });

        // 3. Action Buttons
        $('#btn-action-resolve').on('click', function() {
            if (!currentTicket) return;
            performAction('resolve', currentTicket.ticket_code);
        });

        $('#btn-action-close').on('click', function() {
            if (!currentTicket) return;
            performAction('close', currentTicket.ticket_code);
        });

        $('#btn-action-reopen').on('click', function() {
            if (!currentTicket) return;
            performAction('reopen', currentTicket.ticket_code);
        });

        // 4. Message Sent Event (Payload Response Strategy)
        $(document).on('tickets:message-sent', function(e, data) {
            console.log('[Ticket Detail] Message sent event received', data);
            
            // Update Response Count
            const $count = $('#t-info-responses');
            let current = parseInt($count.text()) || 0;
            $count.text(current + 1);

            // Update Attachments List
            if (data.attachments && data.attachments.length > 0) {
                const $list = $('#t-attachments-list');
                const $countAtt = $('#t-attachments-count');
                const $empty = $('#t-attachments-empty');
                
                let currentAttCount = parseInt($countAtt.text()) || 0;
                $countAtt.text(currentAttCount + data.attachments.length);
                $empty.addClass('d-none');
                
                data.attachments.forEach(att => {
                    let iconClass = 'far fa-file text-muted';
                    let iconColorClass = 'text-muted';
                    
                    if (att.file_type) {
                        if (att.file_type.includes('pdf')) { iconClass = 'far fa-file-pdf'; iconColorClass = 'text-danger'; }
                        else if (att.file_type.includes('word') || att.file_type.includes('doc')) { iconClass = 'far fa-file-word'; iconColorClass = 'text-primary'; }
                        else if (att.file_type.includes('excel') || att.file_type.includes('sheet')) { iconClass = 'far fa-file-excel'; iconColorClass = 'text-success'; }
                        else if (att.file_type.includes('image')) { iconClass = 'far fa-file-image'; iconColorClass = 'text-primary'; }
                    }

                    const html = `
                        <li class="mb-2 pb-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 10px;">
                                    <a href="${att.file_url}" target="_blank" class="text-dark" title="${att.file_name}">
                                        <i class="${iconClass} ${iconColorClass} mr-1"></i> 
                                        ${att.file_name}
                                    </a>
                                </div>
                                <div style="flex-shrink: 0;">
                                    <span class="text-muted small mr-2">${formatBytes(att.file_size_bytes)}</span>
                                    <a href="${att.file_url}" download class="text-muted"><i class="fas fa-cloud-download-alt"></i></a>
                                </div>
                            </div>
                        </li>
                    `;
                    $list.append(html);
                });
            }
        });

        // ==============================================================
        // CORE LOGIC
        // ==============================================================

        async function loadTicketDetails(ticketId) {
            // Show Loading State (Overlay)
            console.log(`[Ticket Detail] Loading ticket ${ticketId}...`);
            
            // Include Chat Card in Overlay
            const $cards = $('#card-ticket-info, #card-ticket-actions, #card-ticket-attachments, #ticket-chat-card');
            $cards.append('<div class="overlay"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>');

            // CLEANUP UI to avoid showing stale data
            $('#t-header-code').text('...');
            $('#t-header-title').text('Cargando...');
            $('#t-header-creator-name').text('...');
            $('#t-header-created-at').text('');
            $('#t-header-description').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
            $('#t-header-status-badge').empty();
            
            $('#t-info-code').text('...');
            $('#t-info-status').empty();
            $('#t-info-category').text('...');
            $('#t-info-created').text('...');
            $('#t-info-updated').text('...');
            $('#t-info-responses').text('...');
            $('#t-info-agent').text('...');
            $('#t-info-company-container').hide();
            
            $('#t-attachments-list').empty();
            $('#t-attachments-count').text('0');

            // CLEANUP CHAT (Prevent stale chat)
            $('#chat-messages-list').empty();
            $('#chat-msg-count').text('0');

            try {
                const token = window.tokenManager.getAccessToken();
                if (!token) throw new Error('No access token');

                const response = await $.ajax({
                    url: `${endpoints.get}${ticketId}`,
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                console.log('[Ticket Detail] API Response:', response); // DEBUG

                if (response.success || response.data) { // Handle both standard formats
                    currentTicket = response.data || response; // Fallback if data is root
                    console.log('[Ticket Detail] Ticket Data:', currentTicket); // DEBUG
                    
                    renderTicketData(currentTicket);
                    console.log('[Ticket Detail] Rendered Data'); // DEBUG
                    
                    updateActionButtons(currentTicket);
                    console.log('[Ticket Detail] Updated Buttons'); // DEBUG
                    
                    // Notify Chat Component
                    console.log('[Ticket Detail] Triggering chat load...'); // DEBUG
                    $(document).trigger('tickets:details-loaded', [currentTicket]);
                } else {
                    console.error('[Ticket Detail] API returned success: false', response);
                    throw new Error(response.message || 'Error desconocido');
                }

            } catch (error) {
                console.error('[Ticket Detail] Error loading ticket:', error);
                // Show Error Toast
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: 'No se pudo cargar el ticket. Intente nuevamente.',
                    autohide: true,
                    delay: 3000
                });
            } finally {
                // Remove Overlay
                $cards.find('.overlay').remove();
            }
        }

        function renderTicketData(ticket) {
            // --- Header ---
            $('#t-header-code').text(ticket.ticket_code);
            $('#t-header-title').text(ticket.title);
            $('#t-header-creator-name').text(ticket.created_by_user?.name || 'Desconocido');
            $('#t-header-created-at').text(formatDate(ticket.created_at));
            $('#t-header-description').html(ticket.description || '<i>Sin descripción</i>');
            
            // Status Badge
            const statusConfig = getStatusConfig(ticket.status);
            $('#t-header-status-badge')
                .removeClass()
                .addClass(`badge ${statusConfig.badgeClass} p-2 mr-2`)
                .text(statusConfig.label.toUpperCase());

            // --- Info Card ---
            $('#t-info-code').text(ticket.ticket_code);
            $('#t-info-status').html(`<span class="badge ${statusConfig.badgeClass}">${statusConfig.label}</span>`);
            $('#t-info-category').text(ticket.category?.name || 'Sin Categoría');
            $('#t-info-created').text(formatDate(ticket.created_at));
            $('#t-info-updated').text(formatRelativeTime(ticket.updated_at));
            $('#t-info-responses').text(ticket.responses_count || 0);

            // Agent
            const agentName = ticket.owner_agent ? ticket.owner_agent.name : 'Sin Asignar';
            $('#t-info-agent').text(agentName);

            // Company (Show if Agent/Admin)
            if (TicketConfig.role !== 'USER' && ticket.company) {
                $('#t-info-company').text(ticket.company.name);
                $('#t-info-company-container').show();
                $('#t-info-company-divider').show();
            } else {
                $('#t-info-company-container').hide();
                $('#t-info-company-divider').hide();
            }

            // --- Attachments ---
            // Fetch attachments separately to ensure we get all of them
            loadAttachments(ticket.ticket_code);
        }

        async function loadAttachments(ticketCode) {
            try {
                const token = window.tokenManager.getAccessToken();
                const response = await $.ajax({
                    url: `/api/tickets/${ticketCode}/attachments`,
                    method: 'GET',
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                renderAttachments(response.data || []);
            } catch (e) {
                console.error('[Ticket Detail] Error loading attachments:', e);
                $('#t-attachments-count').text(0);
                $('#t-attachments-empty').removeClass('d-none');
            }
        }

        function renderAttachments(attachments) {
            const $list = $('#t-attachments-list');
            const $count = $('#t-attachments-count');
            const $empty = $('#t-attachments-empty');

            // Reset classes for compact list
            $list.removeClass('mailbox-attachments d-flex align-items-stretch flex-wrap clearfix').addClass('list-unstyled');

            // Clear previous (except empty placeholder)
            $list.find('li:not(#t-attachments-empty)').remove();

            if (!attachments || attachments.length === 0) {
                $count.text(0);
                $empty.removeClass('d-none');
                return;
            }

            $count.text(attachments.length);
            $empty.addClass('d-none');

            attachments.forEach(att => {
                let iconClass = 'far fa-file text-muted';
                let iconColorClass = 'text-muted';
                
                if (att.file_type) {
                    if (att.file_type.includes('pdf')) { iconClass = 'far fa-file-pdf'; iconColorClass = 'text-danger'; }
                    else if (att.file_type.includes('word') || att.file_type.includes('doc')) { iconClass = 'far fa-file-word'; iconColorClass = 'text-primary'; }
                    else if (att.file_type.includes('excel') || att.file_type.includes('sheet')) { iconClass = 'far fa-file-excel'; iconColorClass = 'text-success'; }
                    else if (att.file_type.includes('image')) { iconClass = 'far fa-file-image'; iconColorClass = 'text-primary'; }
                }

                const html = `
                    <li class="mb-2 pb-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 10px;">
                                <a href="${att.file_url}" target="_blank" class="text-dark" title="${att.file_name}">
                                    <i class="${iconClass} ${iconColorClass} mr-1"></i> 
                                    ${att.file_name}
                                </a>
                            </div>
                            <div style="flex-shrink: 0;">
                                <span class="text-muted small mr-2">${formatBytes(att.file_size_bytes)}</span>
                                <a href="${att.file_url}" download class="text-muted"><i class="fas fa-cloud-download-alt"></i></a>
                            </div>
                        </div>
                    </li>
                `;
                $list.append(html);
            });
        }

        function updateActionButtons(ticket) {
            const role = TicketConfig.role; // Global from index.blade.php
            const status = ticket.status;

            // Reset visibility
            $('#btn-action-resolve, #btn-action-close, #btn-action-reopen, #action-section-assign').addClass('d-none');

            // --- USER Logic ---
            if (role === 'USER') {
                // Close: Only if Resolved
                if (status === 'resolved') $('#btn-action-close').removeClass('d-none');
                
                // Reopen: If Resolved or Closed (check date logic in backend, here just show)
                if (status === 'resolved' || status === 'closed') $('#btn-action-reopen').removeClass('d-none');
            }
            
            // --- AGENT / ADMIN Logic ---
            else {
                // Resolve: Open or Pending
                if (status === 'open' || status === 'pending') $('#btn-action-resolve').removeClass('d-none');
                
                // Close: Always unless already closed
                if (status !== 'closed') $('#btn-action-close').removeClass('d-none');
                
                // Reopen: If Resolved or Closed
                if (status === 'resolved' || status === 'closed') $('#btn-action-reopen').removeClass('d-none');

                // Assign: Always visible
                $('#action-section-assign').removeClass('d-none');
            }
        }

        async function performAction(action, ticketCode) {
            if (!confirm(`¿Estás seguro de querer realizar esta acción: ${action}?`)) return;

            try {
                const token = window.tokenManager.getAccessToken();
                const url = `${endpoints[action]}${ticketCode}/${action}`; // e.g. /api/tickets/TKT-123/resolve

                // Note: Some actions might need body data (notes), for now simple POST
                const response = await $.ajax({
                    url: url,
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                // Success
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Éxito',
                    body: `Acción ${action} completada correctamente.`,
                    autohide: true,
                    delay: 3000
                });

                // Reload Ticket
                loadTicketDetails(currentTicket.id);
                // Also refresh list in background
                $(document).trigger('tickets:refresh-list');

            } catch (error) {
                console.error(`[Ticket Detail] Action ${action} failed:`, error);
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: error.responseJSON?.message || 'Error al procesar la acción.',
                    autohide: true,
                    delay: 3000
                });
            }
        }

        // ==============================================================
        // HELPERS
        // ==============================================================
        
        function getStatusConfig(status) {
            const map = {
                'open': { label: 'Abierto', badgeClass: 'badge-danger' },
                'pending': { label: 'Pendiente', badgeClass: 'badge-warning' },
                'resolved': { label: 'Resuelto', badgeClass: 'badge-success' },
                'closed': { label: 'Cerrado', badgeClass: 'badge-secondary' }
            };
            return map[status] || { label: status, badgeClass: 'badge-secondary' };
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('es-ES', { 
                day: 'numeric', month: 'short', year: 'numeric', 
                hour: '2-digit', minute: '2-digit' 
            });
        }

        function formatRelativeTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            if (diff < 60) return 'Hace un momento';
            if (diff < 3600) return `Hace ${Math.floor(diff/60)} mins`;
            if (diff < 86400) return `Hace ${Math.floor(diff/3600)} horas`;
            return formatDate(dateString);
        }

        function formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
        }
    }

    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initTicketDetail);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initTicketDetail);
            }
        }, 100);
        setTimeout(function() {
            clearInterval(checkJQuery);
        }, 10000);
    }
})();
</script>
