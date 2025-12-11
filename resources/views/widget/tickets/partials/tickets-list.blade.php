<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            @if($role === 'USER')
                Mis Tickets
            @else
                Bandeja de Entrada
            @endif
        </h3>

        <div class="card-tools d-flex align-items-center">
            {{-- FILTERS (Category, Area, Priority) --}}
            <div class="mr-1" style="width: 180px;">
                <select class="form-control form-control-sm" id="filter-category">
                    <option value="">Filtrar por Categoría</option>
                    {{-- Loaded via JS --}}
                </select>
            </div>
            <div class="mr-1" style="width: 180px;">
                <select class="form-control form-control-sm" id="filter-area">
                    <option value="">Filtrar por Área</option>
                    {{-- Loaded via JS --}}
                </select>
            </div>
            <div class="mr-1" style="width: 160px;">
                <select class="form-control form-control-sm" id="filter-priority">
                    <option value="">Filtrar por Prioridad</option>
                    <option value="low">Baja</option>
                    <option value="medium">Media</option>
                    <option value="high">Alta</option>
                </select>
            </div>

            <div class="input-group input-group-sm" style="width: 200px;">
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
            <table class="table table-hover" id="tickets-table">
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
        <td class="mailbox-name" style="padding-right: 20px;">
            <!-- Status Badge will go here -->
        </td>
        <td class="mailbox-subject">
            <!-- Code - Title will go here -->
        </td>
        <td class="mailbox-priority text-center" style="width: 70px; white-space: nowrap;">
            <!-- Priority dot will go here -->
        </td>
        <td class="mailbox-date"></td>
        <td class="mailbox-info text-center" style="width: 40px;">
            <button class="btn btn-xs btn-default btn-info-toggle" type="button" title="Ver detalles">
                <i class="fas fa-chevron-down"></i>
            </button>
        </td>
    </tr>
    <tr class="ticket-info-row" style="display: none; background-color: #f8f9fa;">
        <td colspan="5" class="p-3">
            <div class="ticket-info-details">
                <!-- Info content will be inserted here -->
            </div>
        </td>
    </tr>
</template>


<script>
    (function () {
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

            // Filters
            const $filterCategory = $('#filter-category');
            const $filterArea = $('#filter-area');
            const $filterPriority = $('#filter-priority');

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
                'pending': { label: 'Pendiente', icon: 'fa-clock', color: '#d39e00' }, // Darker warning
                'resolved': { label: 'Resuelto', icon: 'fa-check-circle', color: '#1e7e34' }, // Darker success
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
                        <td colspan="5" class="text-center py-5 text-danger">
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
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay tickets para mostrar</p>
                        </td>
                    </tr>
                `);
                    return;
                }

                tickets.forEach((ticket, index) => {
                    const $clone = $($template.html());
                    const $mainRow = $clone.filter('.ticket-row');
                    const $infoRow = $clone.filter('.ticket-info-row');
                    
                    // Apply striping - both rows use the SAME background color
                    const bgColor = index % 2 === 0 ? '#f9f9f9' : '#ffffff';
                    $mainRow.css('background-color', bgColor);
                    $infoRow.css('background-color', bgColor);

                    // 1. Status (Text only - UPPERCASE, Bold, Colored)
                    const statusConfig = getStatusConfig(ticket.status);
                    const colorStyle = statusConfig.color.startsWith('#') ? `color: ${statusConfig.color};` : '';
                    const colorClass = statusConfig.color.startsWith('#') ? '' : statusConfig.color;
                    const statusHtml = `<span class="${colorClass}" style="font-weight: bold; text-transform: uppercase; ${colorStyle}">${statusConfig.label}</span>`;
                    $mainRow.find('.mailbox-name').html(statusHtml);

                    // 2. Subject (Code + Title + Response Count)
                    let subjectHtml = `<b>${ticket.code || ticket.ticket_code}</b> - ${ticket.title}`;

                    if (ticket.responses_count > 0) {
                        subjectHtml += `<span class="float-right text-dark text-sm" style="width: 50px; text-align: right;">
                        <small>${ticket.responses_count}</small> <i class="far fa-comments ml-1 text-dark"></i>
                    </span>`;
                    }

                    $mainRow.find('.mailbox-subject').html(subjectHtml);

                    // 3. Priority Indicator (Colored dot + Text label)
                    let priorityDot = '';
                    let priorityText = '';
                    if (ticket.priority === 'high') {
                        priorityDot = '<i class="fas fa-circle text-danger" style="font-size: 8px;"></i>';
                        priorityText = 'Alta';
                    } else if (ticket.priority === 'medium') {
                        priorityDot = '<i class="fas fa-circle text-warning" style="font-size: 8px;"></i>';
                        priorityText = 'Media';
                    } else {
                        priorityDot = '<i class="fas fa-circle text-success" style="font-size: 8px;"></i>';
                        priorityText = 'Baja';
                    }
                    $mainRow.find('.mailbox-priority').html(`${priorityDot} <span class="text-dark" style="font-size: 11px;">${priorityText}</span>`);

                    // 4. Date (Relative Format)
                    $mainRow.find('.mailbox-date').text(formatRelativeTime(ticket.created_at));

                    // 5. Prepare Info Details
                    const infoPriorityText = ticket.priority === 'high' ? 'Alta' : (ticket.priority === 'medium' ? 'Media' : 'Baja');
                    const priorityClass = ticket.priority === 'high' ? 'text-danger' : (ticket.priority === 'medium' ? 'text-warning' : 'text-success');
                    
                    let infoHtml = `
                        <div class="row ml-5">
                            <div class="col-md-3">
                                <strong>Categoría:</strong> <span class="text-muted">${ticket.category?.name || ticket.category_name || 'N/A'}</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Prioridad:</strong> <span class="${priorityClass} font-weight-bold">${infoPriorityText}</span>
                            </div>`;
                    
                    if (ticket.area && ticket.area.name) {
                        infoHtml += `
                            <div class="col-md-3">
                                <strong>Área:</strong> <span class="text-muted">${ticket.area.name}</span>
                            </div>`;
                    }
                    
                    // Fix agent name - use owner_agent_name first, fallback to owner_agent.name, then 'Sin asignar'
                    let agentName = 'Sin asignar';
                    if (ticket.owner_agent_name) {
                        agentName = ticket.owner_agent_name;
                    } else if (ticket.owner_agent && ticket.owner_agent.name) {
                        agentName = ticket.owner_agent.name;
                    }
                    
                    infoHtml += `
                            <div class="col-md-3">
                                <strong>Agente:</strong> <span class="text-muted">${agentName}</span>
                            </div>
                        </div>`;
                    
                    $infoRow.find('.ticket-info-details').html(infoHtml);

                    // Click Event -> View Details (excluding info button)
                    $mainRow.on('click', function (e) {
                        if ($(e.target).closest('.btn-info-toggle').length > 0) return;
                        if ($(e.target).is('a')) return;
                        const code = ticket.ticket_code || ticket.code;
                        console.log(`[Tickets List] Opening ticket ${code}`);
                        $(document).trigger('tickets:view-details', [code]);
                    });

                    // Info Toggle Button
                    $mainRow.find('.btn-info-toggle').on('click', function (e) {
                        e.stopPropagation();
                        const $icon = $(this).find('i');
                        const $nextRow = $(this).closest('tr').next('.ticket-info-row');
                        
                        if ($nextRow.is(':visible')) {
                            $nextRow.slideUp(200);
                            $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                        } else {
                            $nextRow.slideDown(200);
                            $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                        }
                    });

                    $tableBody.append($mainRow);
                    $tableBody.append($infoRow);
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

            /**
             * Load Categories and Areas for Selects
             */
            async function loadFilterOptions() {
                const token = window.tokenManager.getAccessToken();
                if (!token) {
                    console.warn('[Tickets List] No access token available');
                    return;
                }

                if (!TicketConfig.companyId) {
                    console.warn('[Tickets List] No company ID available');
                    return;
                }

                // Fetch Categories from /api/tickets/categories
                $.ajax({
                    url: `/api/tickets/categories`,
                    method: 'GET',
                    data: {
                        company_id: TicketConfig.companyId,
                        is_active: true,
                        per_page: 100
                    },
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        console.log('[Tickets List] Categories loaded:', response);
                        const categories = response.data || [];
                        if (categories.length > 0) {
                            categories.forEach(cat => {
                                $filterCategory.append(new Option(cat.name, cat.id));
                            });
                            console.log(`[Tickets List] Added ${categories.length} categories to filter`);
                        } else {
                            console.warn('[Tickets List] No categories returned from API');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('[Tickets List] Error loading categories:', error, xhr.responseText);
                    }
                });

                // Fetch Areas from /api/areas
                $.ajax({
                    url: `/api/areas`,
                    method: 'GET',
                    data: {
                        company_id: TicketConfig.companyId,
                        is_active: true,
                        per_page: 100
                    },
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        console.log('[Tickets List] Areas loaded:', response);
                        const areas = response.data || [];
                        if (areas.length > 0) {
                            areas.forEach(area => {
                                $filterArea.append(new Option(area.name, area.id));
                            });
                            console.log(`[Tickets List] Added ${areas.length} areas to filter`);
                        } else {
                            console.warn('[Tickets List] No areas returned from API');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('[Tickets List] Error loading areas:', error, xhr.responseText);
                    }
                });
            }

            // ==============================================================
            // EVENTS
            // ==============================================================

            // Refresh
            $btnRefresh.click(function () {
                loadTickets();
            });

            // Pagination: Prev
            $btnPrev.click(function () {
                if (currentState.page > 1) {
                    currentState.page--;
                    loadTickets();
                }
            });

            // Pagination: Next
            $btnNext.click(function () {
                if (currentState.meta && currentState.page < currentState.meta.last_page) {
                    currentState.page++;
                    loadTickets();
                }
            });

            // Search (Debounced)
            let searchTimeout;
            $searchInput.on('keyup', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const val = $(this).val();
                    currentState.filters.search = val;
                    currentState.page = 1; // Reset to page 1 on search
                    loadTickets();
                }, 500);
            });

            // Filters Changed (Selects)
            $filterCategory.on('change', function () {
                currentState.filters.category_id = $(this).val();
                currentState.page = 1;
                loadTickets();
            });

            $filterArea.on('change', function () {
                currentState.filters.area_id = $(this).val();
                currentState.page = 1;
                loadTickets();
            });

            $filterPriority.on('change', function () {
                currentState.filters.priority = $(this).val();
                currentState.page = 1;
                loadTickets();
            });

            // Filter Changed (from Sidebar)
            $(document).on('tickets:filter-changed', function (e, data) {
                // Reset filters but keep search if needed? Usually sidebar click clears search or combines.
                // Let's reset other filters and apply new one.
                currentState.filters = {};

                // Reset Selects UI
                $filterCategory.val('');
                $filterArea.val('');
                $filterPriority.val('');
                $searchInput.val('');

                currentState.page = 1;

                if (data.type === 'status') {
                    currentState.filters.status = data.value;
                } else if (data.type === 'folder') {
                    // Map folder names to API params
                    if (data.value === 'new') {
                        currentState.filters.owner_agent_id = 'null'; // Unassigned
                    } else if (data.value === 'assigned') {
                        currentState.filters.owner_agent_id = 'me';
                        // Active only (Open/Pending) - Exclude Closed/Resolved
                        // Since API doesn't support "status != closed", we might need to filter by status array if API supported it.
                        // But our API supports single status. 
                        // Workaround: We can't easily say "not closed" in one query if API doesn't support it.
                        // However, usually "My Assigned" implies active work.
                        // Let's try to filter by 'open' OR 'pending'. 
                        // Limitation: If API only accepts one status, we might show all assigned.
                        // Wait, the user specifically asked to remove closed/resolved.
                        // If the API doesn't support multiple statuses (e.g. ?status=open,pending), we have a backend limitation.
                        // Assuming standard Laravel implementation often supports arrays if coded right, but our Service code:
                        // if (!empty($filters['status'])) { $query->where('status', $filters['status']); }
                        // It seems strict equality.
                        // FIX: We will rely on the user manually filtering status if they want, OR we accept that "My Assigned" shows all.
                        // BUT, for "Requires Attention" we need Open/Pending.
                        // Let's assume for now we show all assigned, but maybe sort by status?
                        // Actually, let's look at the Service again. It uses `where('status', $filters['status'])`.
                        // If we pass an array, Eloquent handles `whereIn`. Let's try passing array in JS? 
                        // jQuery $.ajax data handles arrays like status[]=open&status[]=pending.
                        // Let's try that for folders that need it.

                        // For "My Assigned" (Active):
                        // currentState.filters.status = ['open', 'pending']; 

                    } else if (data.value === 'awaiting_response') {
                        currentState.filters.owner_agent_id = 'me';
                        currentState.filters.last_response_author_type = 'user';
                        // Also Active only
                        // currentState.filters.status = ['open', 'pending'];
                    } else if (data.value === 'awaiting_support') {
                        currentState.filters.last_response_author_type = 'user';
                        // Active only
                        // currentState.filters.status = ['open', 'pending'];
                    } else if (data.value === 'pending_my_reply') {
                        currentState.filters.last_response_author_type = 'agent';
                        // Active only
                        // currentState.filters.status = ['open', 'pending'];
                    } else if (data.value === 'requires_attention') {
                        currentState.filters.priority = 'high';
                        // Active only
                        // currentState.filters.status = ['open', 'pending'];
                    } else if (data.value === 'resolved') {
                        currentState.filters.status = 'resolved';
                    }
                    // 'all' doesn't need params
                }

                loadTickets();
            });

            // Refresh List Event (External)
            $(document).on('tickets:refresh-list', function () {
                loadTickets();
            });

            // Initial Load
            loadFilterOptions();
            loadTickets();
        }

        // Wait for jQuery
        if (typeof jQuery !== 'undefined') {
            $(document).ready(initTicketsList);
        } else {
            var checkJQuery = setInterval(function () {
                if (typeof jQuery !== 'undefined') {
                    clearInterval(checkJQuery);
                    $(document).ready(initTicketsList);
                }
            }, 100);
            setTimeout(function () {
                clearInterval(checkJQuery);
            }, 10000);
        }
    })();
</script>