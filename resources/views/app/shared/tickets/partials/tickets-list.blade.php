<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            @if($role === 'USER')
                Mis Tickets
            @else
                Bandeja de Entrada
            @endif
        </h3>

        <div class="card-tools">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" id="search-tickets" placeholder="Buscar Ticket...">
                <div class="input-group-append">
                    <div class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-tools -->
    </div>
    <!-- /.card-header -->
    <div class="card-body p-0">
        <div class="mailbox-controls">
            <!-- Refresh Button -->
            <button type="button" class="btn btn-default btn-sm" id="btn-refresh-list" title="Actualizar">
                <i class="fas fa-sync-alt"></i>
            </button>
            <div class="float-right">
                <span id="pagination-info">1-50/200</span>
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm" id="btn-prev-page">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" class="btn btn-default btn-sm" id="btn-next-page">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <!-- /.btn-group -->
            </div>
            <!-- /.float-right -->
        </div>
        <div class="table-responsive mailbox-messages">
            <table class="table table-hover table-striped">
                <tbody id="tickets-table-body">
                    {{-- Content will be loaded via jQuery --}}
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Cargando tickets...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <!-- /.table -->
        </div>
        <!-- /.mail-box-messages -->
    </div>
    <!-- /.card-body -->
    <div class="card-footer p-0">
        <div class="mailbox-controls">
            <div class="float-right">
                <span id="pagination-info-footer">1-50/200</span>
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="btn btn-default btn-sm"><i class="fas fa-chevron-right"></i></button>
                </div>
                <!-- /.btn-group -->
            </div>
            <!-- /.float-right -->
        </div>
    </div>
</div>
<!-- /.card -->

{{-- Template for Ticket Row --}}
<template id="template-ticket-row">
    <tr class="ticket-row" style="cursor: pointer;">
        <td class="mailbox-name">
            <!-- Status Badge will go here -->
        </td>
        <td class="mailbox-subject">
            <!-- Code - Title will go here -->
        </td>
        <td class="mailbox-attachment"></td>
        <td class="mailbox-date"></td>
    </tr>
</template>


<script>
(function() {
    console.log('[Tickets List] Script loaded - waiting for jQuery...');

    function initTicketsList() {
        console.log('[Tickets List] jQuery available - Initializing');

        // ==============================================================
        // CONFIGURATION & STATE
        // ==============================================================
        const $tableBody = $('#tickets-table-body');
        const $template = $('#template-ticket-row');
        const $paginationInfo = $('#pagination-info, #pagination-info-footer');
        const $btnPrev = $('#btn-prev-page');
        const $btnNext = $('#btn-next-page');
        const $btnRefresh = $('#btn-refresh-list');
        const $searchInput = $('#search-tickets');

        let currentState = {
            page: 1,
            per_page: 50, // User requested 50 per page
            filters: {}, 
            meta: null
        };

        // ==============================================================
        // HELPER FUNCTIONS
        // ==============================================================

        const statusMap = {
            'open': { label: 'Abierto', icon: 'fa-circle', color: 'text-danger' },
            'pending': { label: 'Pendiente', icon: 'fa-clock', color: 'text-warning' },
            'resolved': { label: 'Resuelto', icon: 'fa-check-circle', color: 'text-success' },
            'closed': { label: 'Cerrado', icon: 'fa-times-circle', color: 'text-secondary' }
        };

        function getStatusConfig(status) {
            return statusMap[status] || { label: status, icon: 'fa-circle', color: 'text-secondary' };
        }

        function formatRelativeTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) return 'Hace un momento';
            if (diffInSeconds < 3600) return `Hace ${Math.floor(diffInSeconds / 60)} mins`;
            if (diffInSeconds < 86400) return `Hace ${Math.floor(diffInSeconds / 3600)} horas`;
            if (diffInSeconds < 172800) return 'Ayer';
            
            // Format: DD MMM (e.g., 23 Nov)
            const options = { day: 'numeric', month: 'short' };
            return date.toLocaleDateString('es-ES', options);
        }

        // ==============================================================
        // CORE FUNCTIONS
        // ==============================================================

        /**
         * Load Tickets from API
         */
        async function loadTickets() {
            // 1. Show Loading State
            $tableBody.html(`
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Cargando tickets...</p>
                    </td>
                </tr>
            `);

            try {
                // 2. Prepare Params
                const token = window.tokenManager.getAccessToken();
                if (!token) {
                    throw new Error('No access token found');
                }

                const params = {
                    page: currentState.page,
                    per_page: currentState.per_page,
                    ...currentState.filters
                };

                // 3. Fetch Data
                const response = await $.ajax({
                    url: TicketConfig.endpoints.list,
                    method: 'GET',
                    data: params,
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                // 4. Render Data
                renderTickets(response.data);
                updatePagination(response.meta);
                currentState.meta = response.meta;

            } catch (error) {
                console.error('[Tickets List] Error loading tickets:', error);
                $tableBody.html(`
                    <tr>
                        <td colspan="4" class="text-center py-5 text-danger">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <p>Error al cargar los tickets. Por favor intente nuevamente.</p>
                        </td>
                    </tr>
                `);
            }
        }

        /**
         * Render Ticket Rows
         */
        function renderTickets(tickets) {
            $tableBody.empty();

            if (!tickets || tickets.length === 0) {
                $tableBody.html(`
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay tickets para mostrar</p>
                        </td>
                    </tr>
                `);
                return;
            }

            tickets.forEach(ticket => {
                const $clone = $($template.html());
                
                // 1. Status (Icon + Text)
                const statusConfig = getStatusConfig(ticket.status);
                const statusHtml = `<i class="fas ${statusConfig.icon} ${statusConfig.color} mr-2"></i> <span class="text-dark">${statusConfig.label}</span>`;
                $clone.find('.mailbox-name').html(statusHtml);

                // 2. Subject (Code + Title + Response Count)
                let subjectHtml = `<b>${ticket.code || ticket.ticket_code}</b> - ${ticket.title}`;
                
                if (ticket.responses_count > 0) {
                    subjectHtml += `<span class="float-right text-dark text-sm" style="width: 50px; text-align: right;">
                        <small>${ticket.responses_count}</small> <i class="far fa-comments ml-1 text-dark"></i>
                    </span>`;
                }
                
                $clone.find('.mailbox-subject').html(subjectHtml);

                // 3. Attachments (Icon Only)
                if (ticket.attachments_count > 0) {
                    $clone.find('.mailbox-attachment').html('<i class="fas fa-paperclip"></i>');
                } else {
                    $clone.find('.mailbox-attachment').empty();
                }

                // 4. Date (Relative Format)
                $clone.find('.mailbox-date').text(formatRelativeTime(ticket.created_at));

                // Click Event -> View Details
                $clone.on('click', function(e) {
                    if ($(e.target).is('a')) return; // Don't trigger if clicking a link
                    const code = ticket.ticket_code || ticket.code;
                    console.log(`[Tickets List] Opening ticket ${code}`);
                    $(document).trigger('tickets:view-details', [code]);
                });

                $tableBody.append($clone);
            });
        }

        /**
         * Update Pagination UI
         */
        function updatePagination(meta) {
            if (!meta) return;

            const total = meta.total || 0;
            let from = meta.from;
            let to = meta.to;

            // Fallback calculation if API returns null for from/to but we have items
            if (total > 0 && (!from || !to)) {
                const currentPage = meta.current_page || 1;
                const perPage = parseInt(meta.per_page) || 50;
                
                from = (currentPage - 1) * perPage + 1;
                to = Math.min(from + perPage - 1, total);
            }

            // If still 0/null and total is 0, then it is 0-0
            if (total === 0) {
                from = 0;
                to = 0;
            }

            $paginationInfo.text(`${from}-${to}/${total}`);

            // Buttons State
            $btnPrev.prop('disabled', meta.current_page <= 1);
            $btnNext.prop('disabled', meta.current_page >= meta.last_page);
        }

        // ==============================================================
        // EVENTS
        // ==============================================================
        
        // Refresh
        $btnRefresh.click(function() {
            loadTickets();
        });

        // Pagination: Prev
        $btnPrev.click(function() {
            if (currentState.page > 1) {
                currentState.page--;
                loadTickets();
            }
        });

        // Pagination: Next
        $btnNext.click(function() {
            if (currentState.meta && currentState.page < currentState.meta.last_page) {
                currentState.page++;
                loadTickets();
            }
        });

        // Search (Debounced)
        let searchTimeout;
        $searchInput.on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const val = $(this).val();
                currentState.filters.search = val;
                currentState.page = 1; // Reset to page 1 on search
                loadTickets();
            }, 500);
        });

        // Filter Changed (from Sidebar)
        $(document).on('tickets:filter-changed', function(e, data) {
            // Reset filters but keep search if needed? Usually sidebar click clears search or combines.
            // Let's reset other filters and apply new one.
            currentState.filters = {}; 
            currentState.page = 1;

            if (data.type === 'status') {
                currentState.filters.status = data.value;
            } else if (data.type === 'folder') {
                // Map folder names to API params
                if (data.value === 'new') {
                    currentState.filters.owner_agent_id = 'null'; // Unassigned
                } else if (data.value === 'assigned') {
                    currentState.filters.owner_agent_id = 'me';
                } else if (data.value === 'awaiting_response') {
                    currentState.filters.owner_agent_id = 'me';
                    currentState.filters.last_response_author_type = 'user';
                } else if (data.value === 'awaiting_support') {
                    currentState.filters.last_response_author_type = 'user'; // Or none?
                } else if (data.value === 'resolved') {
                    currentState.filters.status = 'resolved';
                }
                // 'all' doesn't need params
            }

            loadTickets();
        });

        // Refresh List Event (External)
        $(document).on('tickets:refresh-list', function() {
            loadTickets();
        });

        // Initial Load
        loadTickets();
    }

    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initTicketsList);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initTicketsList);
            }
        }, 100);
        setTimeout(function() {
            clearInterval(checkJQuery);
        }, 10000);
    }
})();
</script>
