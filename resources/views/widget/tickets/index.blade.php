{{-- 
    WIDGET VERSION - Vista de Tickets Embebible
    Copia de: app/shared/tickets/index.blade.php
    
    Modificaciones:
    - Usa layouts.widget en lugar de layouts.authenticated
    - Sin breadcrumbs (no hay navbar en widget)
    - Usa partials de widget/tickets/
--}}
@extends('layouts.widget')

@section('title', 'Centro de Soporte')

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

            {{-- ============================================================== --}}
            {{-- USER PROFILE CARD --}}
            {{-- ============================================================== --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle mr-1"></i>Mi Perfil
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body text-center">
                    {{-- Avatar con overlay de edición --}}
                    <div class="profile-avatar-container mb-3" id="avatar-container">
                        <img src="{{ $user->avatar_url ?? asset('img/default-avatar.png') }}" 
                             alt="Avatar" 
                             class="profile-avatar img-circle elevation-2"
                             id="profile-avatar">
                        <div class="avatar-overlay" id="avatar-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Cambiar</span>
                        </div>
                        <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                    </div>
                    
                    {{-- Nombre y email --}}
                    <h6 class="font-weight-bold mb-1" id="profile-name">
                        {{ $user->display_name ?? 'Usuario' }}
                    </h6>
                    <small class="text-muted" id="profile-email">
                        {{ $user->email }}
                    </small>
                </div>
                <div class="card-footer p-2">
                    {{-- Botón Salir --}}
                    <button type="button" 
                            class="btn btn-outline-secondary btn-sm btn-block mb-2" 
                            id="btn-widget-logout">
                        <i class="fas fa-sign-out-alt mr-1"></i>Salir del Centro de Soporte
                    </button>
                    
                    {{-- Botón Visitar Sitio (solo si SSO está habilitado) --}}
                    <a href="#" 
                       class="btn btn-outline-primary btn-sm btn-block"
                       id="btn-visit-official"
                       target="_blank">
                        <i class="fas fa-external-link-alt mr-1"></i>Visitar Sitio Oficial
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- RIGHT CONTENT AREA --}}
        {{-- ============================================================== --}}
        <div class="col-md-9">

            {{-- VIEW CONTAINER --}}
            <div id="tickets-main-container">
                @include('widget.tickets.partials.tickets-list')
            </div>

            {{-- HIDDEN CONTAINERS FOR OTHER VIEWS --}}
            <div id="view-create-ticket" class="d-none">
                @include('widget.tickets.partials.create-ticket')
            </div>

            <div id="view-ticket-details" class="d-none">
                @include('widget.tickets.partials.ticket-detail')
            </div>

            {{-- MODALS - Mantenemos los originales ya que son componentes globales --}}
            @include('components.tickets.assign-agent-modal')
            @include('components.tickets.confirm-action-modal')

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Configuración Global - WIDGET VERSION
        const TicketConfig = {
            role: '{{ $role ?? "USER" }}',
            companyId: '{{ $companyId ?? "" }}',
            userId: null, // Se obtiene del JWT en el widget
            isWidget: true, // Flag para identificar que estamos en el widget
            endpoints: {
                list: '/api/tickets',
                stats: '/api/tickets'
            }
        };

        (function () {
            function initTicketSystem() {
                console.log('[Widget Tickets] Initializing navigation logic...');

                // ==============================================================
                // ROUTING LOGIC (WIDGET VERSION - Sin History API)
                // En el widget NO usamos History API para evitar conflictos
                // con el sistema externo. Usamos navegación interna.
                // ==============================================================

                /**
                 * Navigate to a view (widget version - no URL changes)
                 * @param {string} view - The view to show ('list', 'create', 'detail')
                 * @param {string} ticketId - Optional ticket ID for detail view
                 */
                function navigateTo(view, ticketId = null) {
                    console.log('[Widget Routing] Navigating to:', view, ticketId);
                    
                    switch(view) {
                        case 'list':
                        case '/tickets':
                        case '/tickets/':
                            showListView();
                            break;
                        case 'create':
                        case '/tickets/create':
                            showCreateView();
                            break;
                        case 'detail':
                            showDetailsView(ticketId);
                            break;
                        default:
                            // Check if it's a ticket path like /tickets/123
                            const match = view.match(/\/tickets\/(.+)/);
                            if (match) {
                                showDetailsView(match[1]);
                            } else {
                                showListView();
                            }
                    }
                }

                /**
                 * Get role prefix (not used in widget, kept for compatibility)
                 */
                function getRolePrefix() {
                    return 'widget';
                }

                /**
                 * Handle route changes (WIDGET VERSION - simplified)
                 * In widget, we don't read from URL since we're in iframe
                 */
                function handleRouteChange(path) {
                    console.log('[Widget Routing] Handling:', path);
                    // Widget always starts with list view
                    showListView();
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
                    console.log(`[Widget] Showing details for ticket ${ticketId}`);
                    $mainContainer.addClass('d-none');
                    $createContainer.addClass('d-none');
                    $detailsContainer.removeClass('d-none');

                    // Trigger event to load ticket details
                    $(document).trigger('tickets:view-details', [ticketId]);

                    if ($btnCompose.length) {
                        $btnCompose.html('<i class="fas fa-arrow-left mr-2"></i>Volver a Inbox');
                    }
                }

                // WIDGET: No usamos popstate porque estamos en iframe
                // Los eventos de navegación del navegador padre no nos afectan

                // ==============================================================
                // NAVIGATION LOGIC
                // ==============================================================
                const $mainContainer = $('#tickets-main-container');
                const $createContainer = $('#view-create-ticket');
                const $detailsContainer = $('#view-ticket-details');

                const $btnCompose = $('#btn-compose, #btn-refresh-sidebar');

                // Show Create View (Or Refresh if Agent)
                $btnCompose.on('click', function (e) {
                    e.preventDefault();

                    if ($mainContainer.hasClass('d-none')) {
                        // Currently in create/detail view, go back to list
                        showListView();
                    } else {
                        // Currently in list view
                        if ($(this).attr('id') === 'btn-compose') {
                            // User: Go to Create
                            showCreateView();
                        } else {
                            // Agent: Refresh List
                            $(document).trigger('tickets:refresh-list');
                            $(document).trigger('tickets:stats-update-required');
                        }
                    }
                });

                // Event: View Ticket Details (WIDGET VERSION - simplified)
                $(document).on('tickets:view-details', function (e, ticketId) {
                    console.log(`[Widget] Viewing details for ticket ${ticketId}`);
                    // Just show the view, no URL manipulation
                    $mainContainer.addClass('d-none');
                    $createContainer.addClass('d-none');
                    $detailsContainer.removeClass('d-none');

                    if ($btnCompose.length) {
                        $btnCompose.html('<i class="fas fa-arrow-left mr-2"></i>Volver a Inbox');
                    }
                });

                // Event: Show List (Back from details)
                $(document).on('tickets:show-list', function () {
                    console.log('[Widget] Returning to list');
                    showListView();
                });

                // Handle "Discard" or "Created" events from Create Component
                $(document).on('tickets:discarded tickets:created', function (event) {
                    // Return to list view
                    showListView();

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
                        showListView();
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
                // INITIALIZE WIDGET ON PAGE LOAD
                // ==============================================================

                // Widget siempre inicia en la vista de lista
                $(document).on('tickets:detail-ready', function () {
                    console.log('[Widget] Detail component reported ready');
                });

                // Initialize: Show list view
                console.log('[Widget] Initializing - showing list view');
                showListView();
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
    
    {{-- ================================================================== --}}
    {{-- PROFILE CARD STYLES --}}
    {{-- ================================================================== --}}
    <style>
        .profile-avatar-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 3px solid #dee2e6;
            transition: border-color 0.3s ease;
        }

        .profile-avatar-container:hover .profile-avatar {
            border-color: #007bff;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            font-size: 0.75rem;
        }

        .avatar-overlay i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .profile-avatar-container:hover .avatar-overlay {
            opacity: 1;
        }

        .profile-avatar.uploading {
            opacity: 0.5;
        }
    </style>
    
    {{-- ================================================================== --}}
    {{-- PROFILE CARD SCRIPTS --}}
    {{-- ================================================================== --}}
    <script>
        // Profile Card functionality
        (function() {
            'use strict';

            // ================================================================
            // AVATAR UPLOAD
            // ================================================================
            const avatarContainer = document.getElementById('avatar-container');
            const avatarInput = document.getElementById('avatar-input');
            const avatarImg = document.getElementById('profile-avatar');

            if (avatarContainer && avatarInput) {
                // Click en el avatar abre el selector de archivo
                avatarContainer.addEventListener('click', function() {
                    avatarInput.click();
                });

                // Cuando se selecciona un archivo
                avatarInput.addEventListener('change', async function() {
                    const file = this.files[0];
                    if (!file) return;

                    // Validar tamaño (max 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        Swal.fire('Error', 'La imagen no puede superar 2MB', 'error');
                        return;
                    }

                    // Validar tipo
                    if (!file.type.startsWith('image/')) {
                        Swal.fire('Error', 'Solo se permiten imágenes', 'error');
                        return;
                    }

                    // Mostrar loading
                    avatarImg.classList.add('uploading');

                    try {
                        const formData = new FormData();
                        formData.append('avatar', file);

                        const response = await fetch('/api/users/me/avatar', {
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + window.widgetTokenManager.getAccessToken(),
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (response.ok && data.avatar_url) {
                            avatarImg.src = data.avatar_url;
                            Swal.fire({
                                icon: 'success',
                                title: '¡Avatar actualizado!',
                                timer: 1500,
                                showConfirmButton: false,
                            });
                        } else {
                            throw new Error(data.message || 'Error al subir avatar');
                        }
                    } catch (error) {
                        console.error('[Profile] Avatar upload error:', error);
                        Swal.fire('Error', 'No se pudo actualizar el avatar', 'error');
                    } finally {
                        avatarImg.classList.remove('uploading');
                    }
                });
            }

            // ================================================================
            // LOGOUT BUTTON
            // ================================================================
            const btnLogout = document.getElementById('btn-widget-logout');
            
            if (btnLogout) {
                btnLogout.addEventListener('click', function() {
                    Swal.fire({
                        title: '¿Salir del Centro de Soporte?',
                        text: 'Tendrás que volver a conectarte para acceder.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#6c757d',
                        cancelButtonColor: '#007bff',
                        confirmButtonText: 'Sí, salir',
                        cancelButtonText: 'Cancelar',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.widgetTokenManager.logout();
                        }
                    });
                });
            }

            // ================================================================
            // VISIT OFFICIAL SITE (SSO)
            // ================================================================
            const btnVisit = document.getElementById('btn-visit-official');
            
            if (btnVisit) {
                btnVisit.addEventListener('click', async function(e) {
                    e.preventDefault();
                    
                    // Mostrar loading
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Conectando...';
                    this.classList.add('disabled');

                    try {
                        // Por ahora redirigir directamente (SSO se implementará después)
                        // TODO: Implementar endpoint /api/external/create-sso-token
                        window.open('{{ config("app.url") }}', '_blank');
                    } catch (error) {
                        console.error('[Profile] SSO error:', error);
                        Swal.fire('Error', 'No se pudo iniciar sesión en el sitio oficial', 'error');
                    } finally {
                        this.innerHTML = originalText;
                        this.classList.remove('disabled');
                    }
                });
            }
        })();
    </script>
@endpush