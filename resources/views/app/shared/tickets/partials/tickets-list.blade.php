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

                // Filter out closed tickets for 'awaiting_response' folder
                if (this.activeFolder === 'awaiting_response') {
                    this.tickets = this.tickets.filter(ticket => ticket.status !== 'closed');
                }

                // Filter out resolved and closed tickets for 'assigned' folder
                if (this.activeFolder === 'assigned') {
                    this.tickets = this.tickets.filter(ticket => ticket.status !== 'resolved' && ticket.status !== 'closed');
                }

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
                    // New tickets (unassigned - no agent)
                    const newResponse = await fetch(`/api/tickets?owner_agent_id=null&per_page=1`, {
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

                    // My assigned (excluding resolved and closed)
                    const myFullResponse = await fetch(`/api/tickets?owner_agent_id=me&per_page=100`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const myFullData = await myFullResponse.json();
                    const myAssignedActive = (myFullData.data || []).filter(t => t.status !== 'resolved' && t.status !== 'closed').length;
                    this.stats.my_assigned = myAssignedActive;

                    // Awaiting my response (excluding closed)
                    const awaitingFullResponse = await fetch(`/api/tickets?owner_agent_id=me&last_response_author_type=user&per_page=100`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const awaitingFullData = await awaitingFullResponse.json();
                    const awaitingNotClosed = (awaitingFullData.data || []).filter(t => t.status !== 'closed').length;
                    this.stats.awaiting_my_response = awaitingNotClosed;
                } else if (this.role === 'COMPANY_ADMIN') {
                    // New tickets (unassigned - no agent)
                    const newResponse = await fetch(`/api/tickets?owner_agent_id=null&per_page=1`, {
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

                // Sincronizar Select2 con Alpine.js
                $('#categoryFilter').on('change', function() {
                    self.filters.category_id = $(this).val();
                    self.applyFilters();
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

            // Special handling for 'new' folder (which filters by owner_agent_id)
            if (folder === 'new' && !queryString) {
                this.filters.owner_agent_id = 'null';
            }

            this.applyFilters();
        },

        applyStatusFilter(status, queryString = '') {
            this.activeFolder = '';
            this.activeStatus = status;
            // Special handling for 'new' status (which is not a real status, just a filter)
            this.filters.status = (status === 'new') ? '' : status;
            this.filters.owner_agent_id = '';
            this.filters.last_response_author_type = '';

            // Handle additional query parameters for special statuses like 'new'
            if (queryString) {
                const params = new URLSearchParams(queryString);
                if (params.has('last_response_author_type')) {
                    this.filters.last_response_author_type = params.get('last_response_author_type');
                }
                if (params.has('owner_agent_id')) {
                    this.filters.owner_agent_id = params.get('owner_agent_id');
                }
            }

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

<div class="col-md-9">
    <div class="card card-primary card-outline" style="position: relative;">
        <!-- Loading overlay -->
        <div x-show="loading" class="loading-overlay" x-cloak>
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
        </div>

        <div class="card-header">
            <h3 class="card-title">
                @if($role === 'USER')
                    Mis Tickets
                @elseif($role === 'AGENT')
                    Todos los Tickets
                @else
                    Gestión de Tickets
                @endif
            </h3>

            <div class="card-tools" style="display: flex; align-items: center;">
                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                    <div class="input-group input-group-sm" style="width: 180px; margin-right: 5px;">
                        <select class="form-control form-control-sm select2"
                                id="categoryFilter"
                                x-model="filters.category_id"
                                @change="applyFilters()"
                                data-placeholder="Categorias">
                            <option value=""></option>
                        </select>
                    </div>
                @endif
                <div class="input-group input-group-sm" style="width: 180px;">
                    <input type="text"
                           class="form-control"
                           placeholder="Search Ticket"
                           x-model="filters.search"
                           @keyup.debounce.500ms="applyFilters()">
                    <div class="input-group-append">
                        <div class="btn btn-primary" @click="applyFilters()">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="mailbox-controls">
                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                    <button type="button" class="btn btn-default btn-sm checkbox-toggle"><i class="far fa-square"></i></button>
                @endif
                <button type="button" class="btn btn-default btn-sm" @click="loadTickets()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <div class="float-right">
                    <span x-text="paginationText()">0-0/0</span>
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-default btn-sm"
                                @click="previousPage()"
                                :disabled="!meta.prev">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button"
                                class="btn btn-default btn-sm"
                                @click="nextPage()"
                                :disabled="!meta.next">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive mailbox-messages">
                <table class="table table-hover">
                    <tbody>
                    @if($role === 'USER')
                        {{-- USER VIEW: Sin avatar, sin nombre de creador --}}
                        <template x-if="tickets.length === 0 && !loading">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No hay tickets para mostrar</p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="ticket in tickets" :key="ticket.id">
                            <tr style="cursor: pointer;" @click="goToTicket(ticket.ticket_code)">
                                <td style="width: 40px;" @click.stop>
                                    <a href="#" @click.prevent="toggleStar(ticket)">
                                        <i :class="ticket.is_starred ? 'fas fa-star text-warning' : 'far fa-star'"></i>
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge mr-2"
                                              :class="{
                                                  'badge-danger': ticket.status === 'open',
                                                  'badge-warning': ticket.status === 'pending',
                                                  'badge-success': ticket.status === 'resolved',
                                                  'badge-secondary': ticket.status === 'closed'
                                              }"
                                              x-text="statusText(ticket.status)"></span>
                                        <strong x-text="ticket.ticket_code"></strong> - <span x-text="ticket.title"></span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> <span x-text="ticket.category?.name || 'N/A'"></span>
                                        <i class="fas fa-comments ml-3"></i> <span x-text="ticket.responses_count"></span> respuestas
                                        <template x-if="ticket.attachments_count > 0">
                                            <span>
                                                <i class="fas fa-paperclip ml-2"></i> <span x-text="ticket.attachments_count"></span> adjunto(s)
                                            </span>
                                        </template>
                                    </small>
                                </td>
                                <td style="width: 120px; text-align: right;">
                                    <small class="text-muted" x-text="formatTimeAgo(ticket.created_at)"></small>
                                </td>
                            </tr>
                        </template>
                    @else
                        {{-- AGENT/COMPANY_ADMIN VIEW: Con checkbox, avatar y nombre de creador --}}
                        <template x-if="tickets.length === 0 && !loading">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No hay tickets para mostrar</p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="ticket in tickets" :key="ticket.id">
                            <tr style="cursor: pointer;" @click="goToTicket(ticket.ticket_code)">
                                <td style="width: 40px;" @click.stop>
                                    <div class="icheck-primary">
                                        <input type="checkbox" :value="ticket.id" :id="'check-' + ticket.id">
                                        <label :for="'check-' + ticket.id"></label>
                                    </div>
                                </td>
                                <td style="width: 40px;" @click.stop>
                                    <a href="#" @click.prevent="toggleStar(ticket)">
                                        <i :class="ticket.is_starred ? 'fas fa-star text-warning' : 'far fa-star'"></i>
                                    </a>
                                </td>
                                <td style="width: 50px;">
                                    <img :src="getAvatarUrl(ticket.created_by_user?.name || 'Unknown')"
                                         class="img-circle"
                                         alt="User Image">
                                </td>
                                <td>
                                    <div>
                                        <span class="badge mr-2"
                                              :class="{
                                                  'badge-danger': ticket.status === 'open',
                                                  'badge-warning': ticket.status === 'pending',
                                                  'badge-success': ticket.status === 'resolved',
                                                  'badge-secondary': ticket.status === 'closed'
                                              }"
                                              x-text="statusText(ticket.status)"></span>
                                        <template x-if="!ticket.last_response_author_type">
                                            <span class="badge badge-info mr-2">
                                                <i class="fas fa-bell"></i> New
                                            </span>
                                        </template>
                                        <strong x-text="ticket.ticket_code"></strong> - <span x-text="ticket.title"></span>
                                    </div>
                                    <small class="text-muted">
                                        <strong x-text="ticket.created_by_user?.name || 'N/A'"></strong>
                                        <i class="fas fa-tag ml-3"></i> <span x-text="ticket.category?.name || 'N/A'"></span>
                                        <template x-if="ticket.owner_agent">
                                            <span>
                                                <i class="fas fa-user-check ml-3"></i> Asignado: <span x-text="ticket.owner_agent.name"></span>
                                            </span>
                                        </template>
                                        <template x-if="!ticket.owner_agent">
                                            <span>
                                                <i class="fas fa-user-slash ml-3 text-danger"></i> Sin asignar
                                            </span>
                                        </template>
                                        <i class="fas fa-comments ml-3"></i> <span x-text="ticket.responses_count"></span>
                                        <template x-if="ticket.attachments_count > 0">
                                            <span>
                                                <i class="fas fa-paperclip ml-2"></i> <span x-text="ticket.attachments_count"></span>
                                            </span>
                                        </template>
                                    </small>
                                </td>
                                <td style="width: 120px; text-align: right;">
                                    <small class="text-muted" x-text="formatTimeAgo(ticket.created_at)"></small>
                                </td>
                            </tr>
                        </template>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer p-0">
            <div class="mailbox-controls">
                @if($role === 'AGENT' || $role === 'COMPANY_ADMIN')
                    <button type="button" class="btn btn-default btn-sm checkbox-toggle">
                        <i class="far fa-square"></i>
                    </button>
                @endif
                <button type="button" class="btn btn-default btn-sm" @click="loadTickets()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <div class="float-right">
                    <span x-text="paginationText()">0-0/0</span>
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-default btn-sm"
                                @click="previousPage()"
                                :disabled="!meta.prev">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button"
                                class="btn btn-default btn-sm"
                                @click="nextPage()"
                                :disabled="!meta.next">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
