@extends('layouts.authenticated')

@section('title', 'Tickets')

@section('content_header', 'Sistema de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Tickets</li>
@endsection

@push('css')
<style>
    /* Hacer que Select2 tenga la misma altura que form-control-sm */
    .select2-container--bootstrap4 .select2-selection--single {
        height: 31px !important;
        min-height: 31px !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        padding-left: 0.5rem !important;
        padding-right: 0 !important;
        font-size: 0.875rem !important;
    }
    /* Placeholder "Categorias" en gris claro */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        color: #adb5bd !important;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        height: 29px !important;
        top: 1px !important;
        right: 3px !important;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
        margin-top: -2px !important;
    }

    /* Loading overlay */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
</style>
@endpush

@section('content')
<div x-data="ticketsList()" x-init="init()">
    <div class="row">
        <div class="col-md-3">
            @if($role === 'USER')
                <button class="btn btn-primary btn-block mb-3" @click="openCreateModal()">
                    <i class="fas fa-plus mr-2"></i>Crear Nuevo Ticket
                </button>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Folders</h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        @if($role === 'USER')
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'all' }"
                                   @click.prevent="applyFolderFilter('all', '')">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right" x-text="stats.total || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'awaiting' }"
                                   @click.prevent="applyFolderFilter('awaiting', 'last_response_author_type=user')">
                                    <i class="far fa-clock"></i> Awaiting Support
                                    <span class="badge bg-warning float-right" x-text="stats.awaiting_support || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'resolved' }"
                                   @click.prevent="applyFolderFilter('resolved', 'status=resolved')">
                                    <i class="far fa-check-circle"></i> Resolved
                                    <span class="badge bg-success float-right" x-text="stats.resolved || 0">0</span>
                                </a>
                            </li>
                        @elseif($role === 'AGENT')
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'all' }"
                                   @click.prevent="applyFolderFilter('all', '')">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right" x-text="stats.total || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'new' }"
                                   @click.prevent="applyFolderFilter('new', 'last_response_author_type=none')">
                                    <i class="fas fa-bell"></i> New Tickets
                                    <span class="badge bg-danger float-right" x-text="stats.new_tickets || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'unassigned' }"
                                   @click.prevent="applyFolderFilter('unassigned', 'owner_agent_id=null')">
                                    <i class="fas fa-user-slash"></i> Unassigned
                                    <span class="badge bg-warning float-right" x-text="stats.unassigned || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'assigned' }"
                                   @click.prevent="applyFolderFilter('assigned', 'owner_agent_id=me')">
                                    <i class="fas fa-user-check"></i> My Assigned
                                    <span class="badge bg-info float-right" x-text="stats.my_assigned || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'awaiting_response' }"
                                   @click.prevent="applyFolderFilter('awaiting_response', 'owner_agent_id=me&last_response_author_type=user')">
                                    <i class="far fa-comments"></i> Awaiting My Response
                                    <span class="badge bg-success float-right" x-text="stats.awaiting_my_response || 0">0</span>
                                </a>
                            </li>
                        @elseif($role === 'COMPANY_ADMIN')
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'all' }"
                                   @click.prevent="applyFolderFilter('all', '')">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right" x-text="stats.total || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'unassigned' }"
                                   @click.prevent="applyFolderFilter('unassigned', 'owner_agent_id=null')">
                                    <i class="fas fa-user-slash"></i> Unassigned
                                    <span class="badge bg-danger float-right" x-text="stats.unassigned || 0">0</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        @if($role === 'USER')
                            My Ticket Status
                        @else
                            Statuses
                        @endif
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
                            <a href="#"
                               class="nav-link"
                               :class="{ 'active': activeStatus === 'open' }"
                               @click.prevent="applyStatusFilter('open')">
                                <i class="far fa-circle text-danger"></i> Open
                                <span class="badge bg-danger float-right" x-text="stats.open || 0">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#"
                               class="nav-link"
                               :class="{ 'active': activeStatus === 'pending' }"
                               @click.prevent="applyStatusFilter('pending')">
                                <i class="far fa-circle text-warning"></i> Pending
                                <span class="badge bg-warning float-right" x-text="stats.pending || 0">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#"
                               class="nav-link"
                               :class="{ 'active': activeStatus === 'resolved' }"
                               @click.prevent="applyStatusFilter('resolved')">
                                <i class="far fa-circle text-success"></i> Resolved
                                <span class="badge bg-success float-right" x-text="stats.resolved || 0">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#"
                               class="nav-link"
                               :class="{ 'active': activeStatus === 'closed' }"
                               @click.prevent="applyStatusFilter('closed')">
                                <i class="far fa-circle text-secondary"></i> Closed
                                <span class="badge bg-secondary float-right" x-text="stats.closed || 0">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

        @include('app.shared.tickets.partials.tickets-list')
    </div>

    <!-- Modal: Crear Nuevo Ticket (USER) -->
    @if($role === 'USER')
        <div class="modal fade" id="createTicketModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h4 class="modal-title text-white">
                            <i class="fas fa-plus mr-2"></i>Crear Nuevo Ticket
                        </h4>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="createTicket()">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="ticketTitle">
                                    <i class="fas fa-heading mr-2"></i>Título <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="ticketTitle"
                                       x-model="newTicket.title"
                                       placeholder="Ej: Error al exportar reporte"
                                       minlength="5"
                                       maxlength="255"
                                       required>
                                <small class="form-text text-muted">Mínimo 5 caracteres, máximo 255</small>
                            </div>
                            <div class="form-group">
                                <label for="ticketCategory">
                                    <i class="fas fa-tag mr-2"></i>Categoría <span class="text-danger">*</span>
                                </label>
                                <select class="form-control select2"
                                        id="ticketCategory"
                                        x-model="newTicket.category_id"
                                        style="width: 100%;"
                                        required>
                                    <option value="">Selecciona una categoría...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ticketDescription">
                                    <i class="fas fa-align-left mr-2"></i>Descripción <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control"
                                          id="ticketDescription"
                                          x-model="newTicket.description"
                                          rows="5"
                                          placeholder="Describe tu problema en detalle..."
                                          minlength="10"
                                          required></textarea>
                                <small class="form-text text-muted">Mínimo 10 caracteres. Se lo más específico posible.</small>
                            </div>
                            <div class="form-group">
                                <label for="ticketAttachment">
                                    <i class="fas fa-paperclip mr-2"></i>Adjuntos (Opcional)
                                </label>
                                <div class="custom-file">
                                    <input type="file"
                                           class="custom-file-input"
                                           id="ticketAttachment"
                                           @change="handleTicketFiles"
                                           multiple>
                                    <label class="custom-file-label" for="ticketAttachment">Seleccionar archivos...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Máximo 5 archivos, 10 MB cada uno. Tipos: PDF, TXT, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG, GIF, MP4
                                </small>
                                <template x-if="newTicket.files.length > 0">
                                    <ul class="list-unstyled mt-2">
                                        <template x-for="(file, index) in newTicket.files" :key="index">
                                            <li class="text-sm">
                                                <i class="fas fa-file mr-2"></i>
                                                <span x-text="file.name"></span>
                                                (<span x-text="formatFileSize(file.size)"></span>)
                                                <a href="#"
                                                   class="text-danger ml-2"
                                                   @click.prevent="removeTicketFile(index)">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </button>
                            <button type="submit"
                                    class="btn btn-primary"
                                    :disabled="isCreating">
                                <template x-if="isCreating">
                                    <span>
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Creando...
                                    </span>
                                </template>
                                <template x-if="!isCreating">
                                    <span>
                                        <i class="fas fa-check mr-2"></i>Crear Ticket
                                    </span>
                                </template>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('js')
<script>
function ticketsList() {
    return {
        // Role & Company
        role: '{{ $role }}',
        companyId: '{{ $companyId ?? "" }}',

        // Data
        tickets: [],
        meta: {},
        stats: {},
        loading: false,

        // Filters
        filters: {
            search: '',
            category_id: '',
            status: '',
            owner_agent_id: '',
            last_response_author_type: '',
            sort_by: 'created_at',
            sort_order: 'desc',
            per_page: 15,
            page: 1
        },

        // Active filters tracking
        activeFolder: 'all',
        activeStatus: '',

        // Create Ticket (USER)
        newTicket: {
            title: '',
            category_id: '',
            description: '',
            files: []
        },
        isCreating: false,

        // Initialize
        async init() {
            // Wait for tokenManager to be available
            await this.waitForTokenManager();
            await this.loadTickets();
            await this.loadStats();
            this.initializeSelect2();
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
                console.error('[Tickets] TokenManager not available after 5 seconds');
                this.showError('Error de autenticación. Por favor, recarga la página.');
                throw new Error('TokenManager not available');
            }

            console.log('[Tickets] TokenManager ready');
        },

        // Load Tickets
        async loadTickets() {
            this.loading = true;

            try {
                const token = window.tokenManager.getAccessToken();
                const params = new URLSearchParams();

                // Build query params
                Object.keys(this.filters).forEach(key => {
                    if (this.filters[key] !== '' && this.filters[key] !== null) {
                        params.append(key, this.filters[key]);
                    }
                });

                const response = await fetch(`/api/tickets?${params.toString()}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error loading tickets');

                const data = await response.json();
                this.tickets = data.data || [];
                this.meta = data.meta || {};

                // Initialize starred state (would need backend support)
                this.tickets.forEach(ticket => ticket.is_starred = false);

            } catch (error) {
                console.error('Error:', error);
                this.showError('Error al cargar los tickets');
            } finally {
                this.loading = false;
            }
        },

        // Load Stats for badges
        async loadStats() {
            try {
                const token = window.tokenManager.getAccessToken();

                // Load stats for each status
                const statusPromises = ['open', 'pending', 'resolved', 'closed'].map(async status => {
                    const params = new URLSearchParams({ status, per_page: 1 });
                    const response = await fetch(`/api/tickets?${params.toString()}`, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    return { status, count: data.meta?.total || 0 };
                });

                const statusCounts = await Promise.all(statusPromises);
                statusCounts.forEach(({ status, count }) => {
                    this.stats[status] = count;
                });

                // Total
                const totalResponse = await fetch(`/api/tickets?per_page=1`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const totalData = await totalResponse.json();
                this.stats.total = totalData.meta?.total || 0;

                // Role-specific stats
                if (this.role === 'USER') {
                    const awaitingResponse = await fetch(`/api/tickets?last_response_author_type=user&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const awaitingData = await awaitingResponse.json();
                    this.stats.awaiting_support = awaitingData.meta?.total || 0;
                } else if (this.role === 'AGENT') {
                    // New tickets (no responses)
                    const newResponse = await fetch(`/api/tickets?last_response_author_type=none&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const newData = await newResponse.json();
                    this.stats.new_tickets = newData.meta?.total || 0;

                    // Unassigned
                    const unassignedResponse = await fetch(`/api/tickets?owner_agent_id=null&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const unassignedData = await unassignedResponse.json();
                    this.stats.unassigned = unassignedData.meta?.total || 0;

                    // My assigned
                    const myResponse = await fetch(`/api/tickets?owner_agent_id=me&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const myData = await myResponse.json();
                    this.stats.my_assigned = myData.meta?.total || 0;

                    // Awaiting my response
                    const awaitingResponse = await fetch(`/api/tickets?owner_agent_id=me&last_response_author_type=user&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const awaitingData = await awaitingResponse.json();
                    this.stats.awaiting_my_response = awaitingData.meta?.total || 0;
                } else if (this.role === 'COMPANY_ADMIN') {
                    // Unassigned
                    const unassignedResponse = await fetch(`/api/tickets?owner_agent_id=null&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const unassignedData = await unassignedResponse.json();
                    this.stats.unassigned = unassignedData.meta?.total || 0;
                }

            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        // Initialize Select2 for category filters
        initializeSelect2() {
            const self = this;

            // Category filter in header (AGENT/COMPANY_ADMIN)
            if (this.role === 'AGENT' || this.role === 'COMPANY_ADMIN') {
                $('#categoryFilter').select2({
                    theme: 'bootstrap4',
                    placeholder: 'Categorias',
                    allowClear: true,
                    ajax: {
                        url: '/api/tickets/categories',
                        headers: {
                            'Authorization': 'Bearer ' + window.tokenManager.getAccessToken()
                        },
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                company_id: self.companyId,
                                is_active: 'true',
                                per_page: 100,
                                search: params.term
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: (data.data || []).map(function(category) {
                                    return {
                                        id: category.id,
                                        text: category.name + ' (' + (category.active_tickets_count || 0) + ')'
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                });
            }

            // Category select in create modal (USER)
            if (this.role === 'USER') {
                $('#ticketCategory').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#createTicketModal'),
                    placeholder: 'Selecciona una categoría...',
                    ajax: {
                        url: '/api/tickets/categories',
                        headers: {
                            'Authorization': 'Bearer ' + window.tokenManager.getAccessToken()
                        },
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                company_id: self.companyId,
                                is_active: 'true',
                                per_page: 100
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: (data.data || []).map(function(category) {
                                    return {
                                        id: category.id,
                                        text: category.name
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                });
            }
        },

        // Apply Filters
        applyFilters() {
            this.filters.page = 1;
            this.loadTickets();
        },

        applyFolderFilter(folder, queryString) {
            this.activeFolder = folder;
            this.activeStatus = '';

            // Reset filters
            this.filters.status = '';
            this.filters.owner_agent_id = '';
            this.filters.last_response_author_type = '';

            // Parse query string
            if (queryString) {
                const pairs = queryString.split('&');
                pairs.forEach(pair => {
                    const [key, value] = pair.split('=');
                    if (this.filters.hasOwnProperty(key)) {
                        this.filters[key] = value;
                    }
                });
            }

            this.applyFilters();
        },

        applyStatusFilter(status) {
            this.activeFolder = '';
            this.activeStatus = status;
            this.filters.status = status;
            this.filters.owner_agent_id = '';
            this.filters.last_response_author_type = '';
            this.applyFilters();
        },

        // Pagination
        nextPage() {
            if (this.meta.next) {
                this.filters.page++;
                this.loadTickets();
            }
        },

        previousPage() {
            if (this.meta.prev) {
                this.filters.page--;
                this.loadTickets();
            }
        },

        paginationText() {
            if (!this.meta.total) return '0-0/0';
            const from = this.meta.from || 0;
            const to = this.meta.to || 0;
            const total = this.meta.total || 0;
            return `${from}-${to}/${total}`;
        },

        // Navigate to ticket
        goToTicket(ticketCode) {
            const routeMap = {
                'USER': '{{ route("user.tickets.manage") }}',
                'AGENT': '{{ route("agent.tickets.manage") }}',
                'COMPANY_ADMIN': '{{ route("company.tickets.manage") }}'
            };
            window.location.href = `${routeMap[this.role]}?ticket=${ticketCode}`;
        },

        // Toggle star (placeholder - would need backend support)
        toggleStar(ticket) {
            ticket.is_starred = !ticket.is_starred;
            // TODO: Implement backend API for starring tickets
        },

        // Create Ticket (USER)
        openCreateModal() {
            this.resetNewTicket();
            $('#createTicketModal').modal('show');
        },

        async createTicket() {
            if (!this.newTicket.title || !this.newTicket.category_id || !this.newTicket.description) {
                this.showError('Por favor completa todos los campos requeridos');
                return;
            }

            this.isCreating = true;

            try {
                const token = window.tokenManager.getAccessToken();

                // 1. Create ticket
                const response = await fetch('/api/tickets', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: this.newTicket.title,
                        description: this.newTicket.description,
                        company_id: this.companyId,
                        category_id: this.newTicket.category_id
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Error al crear ticket');
                }

                const result = await response.json();
                const newTicketCode = result.data.ticket_code;

                // 2. Upload attachments if any
                if (this.newTicket.files.length > 0) {
                    for (const file of this.newTicket.files) {
                        const formData = new FormData();
                        formData.append('file', file);

                        await fetch(`/api/tickets/${newTicketCode}/attachments`, {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                    }
                }

                // Success
                $('#createTicketModal').modal('hide');
                this.showSuccess('Ticket creado exitosamente');

                // Reload tickets and stats
                await this.loadTickets();
                await this.loadStats();

                // Optionally navigate to the new ticket
                // this.goToTicket(newTicketCode);

            } catch (error) {
                console.error('Error:', error);
                this.showError(error.message || 'Error al crear el ticket');
            } finally {
                this.isCreating = false;
            }
        },

        handleTicketFiles(event) {
            const files = Array.from(event.target.files);

            if (files.length > 5) {
                this.showError('Máximo 5 archivos permitidos');
                event.target.value = '';
                return;
            }

            for (const file of files) {
                if (file.size > 10 * 1024 * 1024) {
                    this.showError(`El archivo ${file.name} excede el tamaño máximo de 10 MB`);
                    event.target.value = '';
                    return;
                }
            }

            this.newTicket.files = files;
        },

        removeTicketFile(index) {
            this.newTicket.files.splice(index, 1);
            document.getElementById('ticketAttachment').value = '';
        },

        resetNewTicket() {
            this.newTicket = {
                title: '',
                category_id: '',
                description: '',
                files: []
            };
            document.getElementById('ticketAttachment').value = '';
            $('#ticketCategory').val('').trigger('change');
        },

        // Utilities
        statusText(status) {
            const map = {
                open: 'Open',
                pending: 'Pending',
                resolved: 'Resolved',
                closed: 'Closed'
            };
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

            return date.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short',
                year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
            });
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        getAvatarUrl(name) {
            const colors = ['007bff', '28a745', 'dc3545', 'ffc107', '17a2b8', '6c757d'];
            const randomColor = colors[Math.floor(Math.random() * colors.length)];
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&size=40&background=${randomColor}&color=fff`;
        },

        showSuccess(message) {
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

// jQuery for checkbox toggle and star functionality
$(function () {
    //Enable check and uncheck all functionality
    $('.checkbox-toggle').click(function () {
        var clicks = $(this).data('clicks')
        if (clicks) {
            //Uncheck all checkboxes
            $('.mailbox-messages input[type=\'checkbox\']').prop('checked', false)
            $('.checkbox-toggle .far.fa-check-square').removeClass('fa-check-square').addClass('fa-square')
        } else {
            //Check all checkboxes
            $('.mailbox-messages input[type=\'checkbox\']').prop('checked', true)
            $('.checkbox-toggle .far.fa-square').removeClass('fa-square').addClass('fa-check-square')
        }
        $(this).data('clicks', !clicks)
    })
})
</script>
@endsection