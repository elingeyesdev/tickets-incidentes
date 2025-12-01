@extends('layouts.authenticated')

@section('title', 'Tickets')
@section('content_header', 'Sistema de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Tickets</li>
@endsection

@section('content')
    <div class="row" id="tickets-app">
        {{-- ============================================================== --}}
        {{-- LEFT SIDEBAR (FILTERS & NAVIGATION) --}}
        {{-- ============================================================== --}}
        <div class="col-md-3">

            {{-- CREATE BUTTON (USER ONLY) --}}
            @if($role === 'USER')
                <a href="#" id="btn-compose"
                    class="btn btn-primary btn-block mb-3 text-center d-flex justify-content-center align-items-center"><i
                        class="fas fa-plus mr-2"></i>Crear Ticket</a>
            @else
                {{-- REFRESH BUTTON (AGENT/ADMIN) --}}
                <button id="btn-refresh-sidebar"
                    class="btn btn-primary btn-block mb-3 text-center d-flex justify-content-center align-items-center">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar Buzón
                </button>
            @endif

            {{-- FOLDERS CARD --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Carpetas</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column" id="folder-filters">

                        {{-- ROL: USER --}}
                        @if($role === 'USER')
                            <li class="nav-item">
                                <a href="#" class="nav-link active" data-filter="all">
                                    <i class="fas fa-inbox"></i> Todos los Tickets
                                    <span class="badge bg-primary float-right count-total"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="pending_my_reply">
                                    <i class="fas fa-user-clock"></i> Pendiente de mi respuesta
                                    <span class="badge bg-warning float-right count-pending-my-reply"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="awaiting_support">
                                    <i class="far fa-clock"></i> Esperando Soporte
                                    <span class="badge bg-secondary float-right count-awaiting"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="resolved">
                                    <i class="far fa-check-circle"></i> Resueltos
                                    <span class="badge bg-success float-right count-resolved"></span>
                                </a>
                            </li>

                            {{-- ROL: AGENT --}}
                        @elseif($role === 'AGENT')
                            <li class="nav-item">
                                <a href="#" class="nav-link active" data-filter="all">
                                    <i class="fas fa-inbox"></i> Todos
                                    <span class="badge bg-primary float-right count-total"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="requires_attention">
                                    <i class="fas fa-exclamation-circle text-danger"></i> Requiere Atención
                                    <span class="badge bg-danger float-right count-attention"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="new">
                                    <i class="fas fa-star"></i> Nuevos (Sin Asignar)
                                    <span class="badge bg-info float-right count-new"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="assigned">
                                    <i class="fas fa-user-check"></i> Mis Asignados
                                    <span class="badge bg-success float-right count-assigned"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="awaiting_response">
                                    <i class="far fa-comments"></i> Esperando mi respuesta
                                    <span class="badge bg-warning float-right count-awaiting-response"></span>
                                </a>
                            </li>

                            {{-- ROL: COMPANY ADMIN --}}
                        @elseif($role === 'COMPANY_ADMIN')
                            <li class="nav-item">
                                <a href="#" class="nav-link active" data-filter="all">
                                    <i class="fas fa-inbox"></i> Todos
                                    <span class="badge bg-primary float-right count-total"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="requires_attention">
                                    <i class="fas fa-exclamation-circle text-danger"></i> Requiere Atención
                                    <span class="badge bg-danger float-right count-attention"></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-filter="new">
                                    <i class="fas fa-star"></i> Nuevos (Sin Asignar)
                                    <span class="badge bg-info float-right count-new"></span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- STATUS CARD --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $role === 'USER' ? 'Estado de mis Tickets' : 'Estados' }}
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column" id="status-filters">
                        {{-- USER no ve "New" en la lista de estados --}}
                        @if($role !== 'USER')
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-status="new">
                                    <i class="far fa-circle text-info"></i> Nuevo
                                    <span class="badge bg-info float-right count-status-new"></span>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a href="#" class="nav-link" data-status="open">
                                <i class="far fa-circle text-danger"></i> Abierto
                                <span class="badge bg-danger float-right count-status-open"></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-status="pending">
                                <i class="far fa-circle text-warning"></i> Pendiente
                                <span class="badge bg-warning float-right count-status-pending"></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-status="resolved">
                                <i class="far fa-circle text-success"></i> Resuelto
                                <span class="badge bg-success float-right count-status-resolved"></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-status="closed">
                                <i class="far fa-circle text-secondary"></i> Cerrado
                                <span class="badge bg-secondary float-right count-status-closed"></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- RIGHT CONTENT AREA --}}
        {{-- ============================================================== --}}
        <div class="col-md-9">

            {{-- VIEW CONTAINER --}}
            <div id="tickets-main-container">
                @include('app.shared.tickets.partials.tickets-list')
            </div>

            {{-- HIDDEN CONTAINERS FOR OTHER VIEWS --}}
            <div id="view-create-ticket" class="d-none">
                @include('app.shared.tickets.partials.create-ticket')
            </div>

            <div id="view-ticket-details" class="d-none">
                @include('app.shared.tickets.partials.ticket-detail')
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Configuración Global
        const TicketConfig = {
            role: '{{ $role }}',
            companyId: '{{ $companyId ?? "" }}',
            userId: '{{ auth()->id() }}',
            endpoints: {
                list: '/api/tickets',
                stats: '/api/tickets' // Se usa el mismo endpoint con filtros para contar
            }
        };

        (function () {
            function initTicketSystem() {
                console.log('[Tickets Index] Initializing navigation logic...');

                // ==============================================================
                // ROUTING LOGIC (History API)
                // ==============================================================

                /**
                 * Navigate to a new URL path using History API
                 * @param {string} path - The path to navigate to (e.g., '/tickets', '/tickets/create', '/tickets/123')
                 */
                function navigateTo(path) {
                    // Get the current role from URL (user, agent, or company)
                    const currentPath = window.location.pathname;
                    const roleMatch = currentPath.match(/\/app\/(user|agent|company)\//);
                    const rolePrefix = roleMatch ? roleMatch[1] : getRolePrefix();

                    // Build full path with role
                    const fullPath = path.startsWith('/app/') ? path : `/app/${rolePrefix}${path}`;
                    history.pushState({ path: path }, '', fullPath);
                    handleRouteChange(path);
                }

                /**
                 * Get role prefix based on TicketConfig.role
                 */
                function getRolePrefix() {
                    const roleMap = {
                        'USER': 'user',
                        'AGENT': 'agent',
                        'COMPANY_ADMIN': 'company'
                    };
                    return roleMap[TicketConfig.role] || 'user';
                }

                /**
                 * Handle route changes and display the appropriate view
                 * @param {string} path - The path to handle (optional, reads from URL if not provided)
                 */
                function handleRouteChange(path) {
                    if (!path) {
                        // Read from current URL
                        path = window.location.pathname;
                        // Remove the /app/{role}/ prefix to get the relative path
                        const match = path.match(/\/app\/(?:user|agent|company)(\/.+)?$/);
                        path = match && match[1] ? match[1] : '/tickets';
                    }

                    console.log('[Routing] Handling route:', path);

                    // Parse the path
                    if (path === '/tickets' || path === '/tickets/') {
                        // Show list view
                        showListView();
                    } else if (path === '/tickets/create') {
                        // Show create view
                        showCreateView();
                    } else if (path.match(/^\/tickets\/(.+)$/)) {
                        // Show details view
                        const ticketId = path.match(/^\/tickets\/(.+)$/)[1];
                        showDetailsView(ticketId);
                    } else {
                        // Unknown path, default to list
                        console.warn('[Routing] Unknown path, defaulting to list:', path);
                        showListView();
                    }
                }

                /**
                 * Show the list view
                 */
                function showListView() {
                    $detailsContainer.addClass('d-none');
                    $createContainer.addClass('d-none');
                    $mainContainer.removeClass('d-none');

                    if ($btnCompose.length) {
                        if ($btnCompose.attr('id') === 'btn-compose') {
                            $btnCompose.html('<i class="fas fa-plus mr-2"></i>Crear Ticket');
                        } else {
                            $btnCompose.html('<i class="fas fa-sync-alt mr-2"></i>Actualizar Buzón');
                        }
                    }
                }

                /**
                 * Show the create view
                 */
                function showCreateView() {
                    $mainContainer.addClass('d-none');
                    $detailsContainer.addClass('d-none');
                    $createContainer.removeClass('d-none');

                    if ($btnCompose.attr('id') === 'btn-compose') {
                        $btnCompose.html('<i class="fas fa-arrow-left mr-2"></i>Volver a Inbox');
                    }
                }

                /**
                 * Show the details view for a specific ticket
                 * @param {number|string} ticketId - The ticket ID to display
                 */
                function showDetailsView(ticketId) {
                    console.log(`[Routing] Showing details for ticket ${ticketId}`);
                    $mainContainer.addClass('d-none');
                    $createContainer.addClass('d-none');
                    $detailsContainer.removeClass('d-none');

                    // Trigger event to load ticket details
                    $(document).trigger('tickets:view-details', [ticketId]);

                    if ($btnCompose.length) {
                        $btnCompose.html('<i class="fas fa-arrow-left mr-2"></i>Volver a Inbox');
                    }
                }

                // Listen for browser back/forward buttons
                window.addEventListener('popstate', function (event) {
                    console.log('[Routing] Popstate event triggered');
                    handleRouteChange();
                });

                // ==============================================================
                // NAVIGATION LOGIC
                // ==============================================================
                const $mainContainer = $('#tickets-main-container');
                const $createContainer = $('#view-create-ticket');
                const $detailsContainer = $('#view-ticket-details');

                const $btnCompose = $('#btn-compose, #btn-refresh-sidebar');

                // Show Create View
                // Show Create View (Or Refresh if Agent)
                $btnCompose.on('click', function (e) {
                    e.preventDefault();

                    if ($mainContainer.hasClass('d-none')) {
                        // Currently in create/detail view, go back to list
                        navigateTo('/tickets');
                    } else {
                        // Currently in list view
                        if ($(this).attr('id') === 'btn-compose') {
                            // User: Go to Create
                            navigateTo('/tickets/create');
                        } else {
                            // Agent: Refresh List
                            $(document).trigger('tickets:refresh-list');
                            $(document).trigger('tickets:stats-update-required');
                        }
                    }
                });

                // Event: View Ticket Details
                $(document).on('tickets:view-details', function (e, ticketId) {
                    console.log(`[Index] Viewing details for ticket ${ticketId}`);

                    // Only navigate if we're not already handling a route change
                    // (to avoid infinite loops when navigateTo triggers this event)
                    const currentPath = window.location.pathname;
                    if (!currentPath.endsWith('/tickets/' + ticketId)) {
                        navigateTo('/tickets/' + ticketId);
                    } else {
                        // We're already at the right URL, just show the view
                        $mainContainer.addClass('d-none');
                        $createContainer.addClass('d-none');
                        $detailsContainer.removeClass('d-none');

                        // Update Compose Button to "Back" style if needed
                        if ($btnCompose.length) {
                            $btnCompose.html('<i class="fas fa-arrow-left mr-2"></i>Volver a Inbox');
                        }
                    }
                });

                // Event: Show List (Back from details)
                $(document).on('tickets:show-list', function () {
                    console.log('[Index] Returning to list');
                    navigateTo('/tickets');
                });

                // Handle "Discard" or "Created" events from Create Component
                $(document).on('tickets:discarded tickets:created', function (event) {
                    // Return to list view
                    navigateTo('/tickets');

                    // If ticket was created, refresh list and stats
                    if (event.type === 'tickets:created') {
                        $(document).trigger('tickets:refresh-list');
                        $(document).trigger('tickets:stats-update-required');
                    }
                });


                // ==============================================================
                // SIDEBAR LOGIC (Folders & Status)
                // ==============================================================

                // 1. Handle Filter Clicks (Folders & Status)
                $('.nav-link[data-filter], .nav-link[data-status]').on('click', function (e) {
                    e.preventDefault();

                    // If we're in another view, return to list
                    if ($mainContainer.hasClass('d-none')) {
                        navigateTo('/tickets');
                    }

                    // Visual Update (Active State)
                    $('.nav-link').removeClass('active');
                    $(this).addClass('active');

                    // Get Filter Data
                    const filterType = $(this).data('filter') ? 'folder' : 'status';
                    const value = $(this).data('filter') || $(this).data('status');

                    console.log(`[Sidebar] Filter clicked: ${filterType} = ${value}`);

                    // Trigger Event for the List Component to handle
                    $(document).trigger('tickets:filter-changed', {
                        type: filterType,
                        value: value
                    });
                });

                // 2. Load Stats (Counters)
                loadSidebarStats();

                // Escuchar evento para recargar stats cuando sea necesario (ej: ticket creado/actualizado)
                $(document).on('tickets:stats-update-required', function () {
                    loadSidebarStats();
                });

                // ==============================================================
                // INITIALIZE ROUTING ON PAGE LOAD
                // ==============================================================

                // Variable to track if detail component is ready
                let isDetailComponentReady = false;
                $(document).on('tickets:detail-ready', function () {
                    console.log('[Routing] Detail component reported ready');
                    isDetailComponentReady = true;
                });

                // Handle the current URL to show the appropriate view
                function initRouting() {
                    const currentPath = window.location.pathname;
                    // If we need to show details, we MUST wait for the component
                    if (currentPath.match(/\/tickets\/(.+)$/) && !currentPath.endsWith('/create')) {
                        if (isDetailComponentReady) {
                            handleRouteChange();
                        } else {
                            console.log('[Routing] Waiting for detail component...');
                            $(document).one('tickets:detail-ready', function () {
                                console.log('[Routing] Detail component ready, proceeding with route...');
                                handleRouteChange();
                            });
                        }
                    } else {
                        // List or Create views don't depend on detail component listeners
                        handleRouteChange();
                    }
                }

                initRouting();
            }

            // Wait for jQuery
            if (typeof jQuery !== 'undefined') {
                $(document).ready(initTicketSystem);
            } else {
                var checkJQuery = setInterval(function () {
                    if (typeof jQuery !== 'undefined') {
                        clearInterval(checkJQuery);
                        $(document).ready(initTicketSystem);
                    }
                }, 100);
                setTimeout(function () {
                    clearInterval(checkJQuery);
                }, 10000);
            }
        })();

        /**
         * Load Stats for Sidebar Badges
         * Logic adapted from original Alpine.js implementation
         */
        async function loadSidebarStats() {
            try {
                const token = window.tokenManager.getAccessToken();
                if (!token) return;

                // Helper for fetching count
                const getCount = async (params) => {
                    const query = new URLSearchParams({ ...params, per_page: 1 }).toString();
                    const response = await fetch(`${TicketConfig.endpoints.list}?${query}`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    return data.meta?.total || 0;
                };

                // 1. Common Status Counts (Open, Pending, Resolved, Closed)
                // Note: USER role doesn't see "New" status in the list, but others do.
                const statuses = ['open', 'pending', 'resolved', 'closed'];
                if (TicketConfig.role !== 'USER') statuses.push('new');

                for (const status of statuses) {
                    // Para USER, filtrar por sus propios tickets si es necesario,
                    // pero la API /api/tickets ya filtra por el usuario autenticado por defecto.
                    const count = await getCount({ status: status });
                    $(`.count-status-${status}`).text(count > 0 ? count : '');
                }

                // 2. Folder Counts (Role Specific)

                // --- USER ---
                if (TicketConfig.role === 'USER') {
                    // All
                    const total = await getCount({});
                    $('.count-total').text(total > 0 ? total : '');

                    // Pending My Reply (last_response_author_type = agent, status != closed/resolved)
                    // We sum open + pending where agent replied last
                    const pmrOpen = await getCount({ last_response_author_type: 'agent', status: 'open' });
                    const pmrPending = await getCount({ last_response_author_type: 'agent', status: 'pending' });
                    const pmrTotal = pmrOpen + pmrPending;
                    $('.count-pending-my-reply').text(pmrTotal > 0 ? pmrTotal : '');

                    // Awaiting Support (last_response_author_type = user or none, status != closed/resolved)
                    // Note: 'none' is for new tickets
                    const asUserOpen = await getCount({ last_response_author_type: 'user', status: 'open' });
                    const asUserPending = await getCount({ last_response_author_type: 'user', status: 'pending' });
                    const asNoneOpen = await getCount({ last_response_author_type: 'none', status: 'open' });
                    const asTotal = asUserOpen + asUserPending + asNoneOpen;
                    $('.count-awaiting').text(asTotal > 0 ? asTotal : '');

                    // Resolved
                    const resolved = await getCount({ status: 'resolved' });
                    $('.count-resolved').text(resolved > 0 ? resolved : '');
                }

                // --- AGENT ---
                else if (TicketConfig.role === 'AGENT') {
                    // All
                    const total = await getCount({});
                    $('.count-total').text(total > 0 ? total : '');

                    // Requires Attention (Priority High + Open/Pending)
                    const raOpen = await getCount({ priority: 'high', status: 'open' });
                    const raPending = await getCount({ priority: 'high', status: 'pending' });
                    const raTotal = raOpen + raPending;
                    $('.count-attention').text(raTotal > 0 ? raTotal : '');

                    // New (Unassigned)
                    const newTickets = await getCount({ owner_agent_id: 'null' });
                    $('.count-new').text(newTickets > 0 ? newTickets : '');

                    // My Assigned (Active only: Open/Pending)
                    const assignedOpen = await getCount({ owner_agent_id: 'me', status: 'open' });
                    const assignedPending = await getCount({ owner_agent_id: 'me', status: 'pending' });
                    const assignedTotal = assignedOpen + assignedPending;
                    $('.count-assigned').text(assignedTotal > 0 ? assignedTotal : '');

                    // Awaiting My Response (Assigned to me + User replied last + Active)
                    const awaitingOpen = await getCount({
                        owner_agent_id: 'me',
                        last_response_author_type: 'user',
                        status: 'open'
                    });
                    const awaitingPending = await getCount({
                        owner_agent_id: 'me',
                        last_response_author_type: 'user',
                        status: 'pending'
                    });
                    const totalAwaiting = awaitingOpen + awaitingPending;
                    $('.count-awaiting-response').text(totalAwaiting > 0 ? totalAwaiting : '');
                }

                // --- COMPANY ADMIN ---
                else if (TicketConfig.role === 'COMPANY_ADMIN') {
                    // All
                    const total = await getCount({});
                    $('.count-total').text(total > 0 ? total : '');

                    // Requires Attention (Priority High + Open/Pending)
                    const raOpen = await getCount({ priority: 'high', status: 'open' });
                    const raPending = await getCount({ priority: 'high', status: 'pending' });
                    const raTotal = raOpen + raPending;
                    $('.count-attention').text(raTotal > 0 ? raTotal : '');

                    // New (Unassigned)
                    const newTickets = await getCount({ owner_agent_id: 'null' });
                    $('.count-new').text(newTickets > 0 ? newTickets : '');
                }

            } catch (error) {
                console.error('[Sidebar] Error loading stats:', error);
            }
        }
    </script>
    {{-- Scripts will be added here --}}
@endpush