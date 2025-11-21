@extends('layouts.authenticated')

@section('title', 'Tickets')

@section('content_header', 'Sistema de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Tickets</li>
@endsection

@push('scripts')
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

        // Create Ticket Form
        showCreateForm: false,
        companies: [],
        categoriesCache: {}, // Caché de categorías por company_id
        newTicket: {
            company_id: '',
            title: '',
            category_id: '',
            description: '',
            files: []
        },
        isCreating: false,

        // Initialize
        async init() {
            await this.waitForTokenManager();
            await this.loadCompanies();
            await this.loadTickets();
            await this.loadStats();
            this.initializeSelect2();
        },

        // Wait for tokenManager to be ready
        async waitForTokenManager() {
            let attempts = 0;
            const maxAttempts = 50;

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

        // Load Companies for Create Form
        async loadCompanies() {
            try {
                const response = await fetch('/api/companies/minimal?per_page=100', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Error loading companies');

                const result = await response.json();
                this.companies = result.data || [];
                console.log('[Tickets] Companies loaded:', this.companies.length);
            } catch (error) {
                console.error('[Tickets] Error loading companies:', error);
                this.showError('Error al cargar las compañías');
                this.companies = [];
            }
        },

        // Load Tickets
        async loadTickets() {
            this.loading = true;

            try {
                const token = window.tokenManager.getAccessToken();
                const params = new URLSearchParams();

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

                if (this.activeFolder === 'awaiting_response') {
                    this.tickets = this.tickets.filter(ticket => ticket.status !== 'closed');
                }

                if (this.activeFolder === 'assigned') {
                    this.tickets = this.tickets.filter(ticket => ticket.status !== 'resolved' && ticket.status !== 'closed');
                }

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

                const totalResponse = await fetch(`/api/tickets?per_page=1`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const totalData = await totalResponse.json();
                this.stats.total = totalData.meta?.total || 0;

                if (this.role === 'USER') {
                    const awaitingResponse = await fetch(`/api/tickets?last_response_author_type=user&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const awaitingData = await awaitingResponse.json();
                    this.stats.awaiting_support = awaitingData.meta?.total || 0;
                } else if (this.role === 'AGENT') {
                    const newResponse = await fetch(`/api/tickets?owner_agent_id=null&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const newData = await newResponse.json();
                    this.stats.new_tickets = newData.meta?.total || 0;

                    const unassignedResponse = await fetch(`/api/tickets?owner_agent_id=null&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const unassignedData = await unassignedResponse.json();
                    this.stats.unassigned = unassignedData.meta?.total || 0;

                    const myFullResponse = await fetch(`/api/tickets?owner_agent_id=me&per_page=100`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const myFullData = await myFullResponse.json();
                    const myAssignedActive = (myFullData.data || []).filter(t => t.status !== 'resolved' && t.status !== 'closed').length;
                    this.stats.my_assigned = myAssignedActive;

                    const awaitingFullResponse = await fetch(`/api/tickets?owner_agent_id=me&last_response_author_type=user&per_page=100`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const awaitingFullData = await awaitingFullResponse.json();
                    const awaitingNotClosed = (awaitingFullData.data || []).filter(t => t.status !== 'closed').length;
                    this.stats.awaiting_my_response = awaitingNotClosed;
                } else if (this.role === 'COMPANY_ADMIN') {
                    const newResponse = await fetch(`/api/tickets?owner_agent_id=null&per_page=1`, {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    const newData = await newResponse.json();
                    this.stats.new_tickets = newData.meta?.total || 0;

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

                $('#categoryFilter').on('change', function() {
                    self.filters.category_id = $(this).val();
                    self.applyFilters();
                });
            }

            // Note: createCategory Select2 is initialized separately in openCreateModal()
            // to ensure company_id is selected first
        },

        // Apply Filters
        applyFilters() {
            this.filters.page = 1;
            this.loadTickets();
        },

        applyFolderFilter(folder, queryString) {
            this.showCreateForm = false;
            this.activeFolder = folder;
            this.activeStatus = '';

            this.filters.status = '';
            this.filters.owner_agent_id = '';
            this.filters.last_response_author_type = '';

            if (queryString) {
                const pairs = queryString.split('&');
                pairs.forEach(pair => {
                    const [key, value] = pair.split('=');
                    if (this.filters.hasOwnProperty(key)) {
                        this.filters[key] = value;
                    }
                });
            }

            if (folder === 'new' && !queryString) {
                this.filters.owner_agent_id = 'null';
            }

            this.applyFilters();
        },

        applyStatusFilter(status, queryString = '') {
            this.showCreateForm = false;
            this.activeFolder = '';
            this.activeStatus = status;
            this.filters.status = (status === 'new') ? '' : status;
            this.filters.owner_agent_id = '';
            this.filters.last_response_author_type = '';

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

        // Toggle star
        toggleStar(ticket) {
            ticket.is_starred = !ticket.is_starred;
        },

        // Create Ticket Methods
        openCreateModal() {
            this.showCreateForm = true;
            this.resetNewTicket();
        },

        initializeCategorySelect2() {
            // Solo inicializar si hay company_id seleccionado
            if (!this.newTicket.company_id) {
                return;
            }

            // Limpiar categoría anterior
            this.newTicket.category_id = '';

            setTimeout(() => {
                if ($('#createCategory').length) {
                    const self = this;
                    const token = window.tokenManager.getAccessToken();
                    const companyId = self.newTicket.company_id;

                    // Destruir Select2 si ya existe (para limpiar su caché interno)
                    if ($('#createCategory').data('select2')) {
                        $('#createCategory').select2('destroy');
                    }

                    // Limpiar HTML
                    $('#createCategory').html('<option></option>').val('');

                    // Inicializar Select2 AJAX fresco
                    $('#createCategory').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Selecciona una categoría...',
                        allowClear: true,
                        ajax: {
                            url: '/api/tickets/categories',
                            headers: {
                                'Authorization': `Bearer ${token}`
                            },
                            dataType: 'json',
                            delay: 100,
                            cache: false,
                            data: (params) => {
                                return {
                                    company_id: self.newTicket.company_id,
                                    is_active: 'true',
                                    per_page: 100,
                                    search: params.term
                                };
                            },
                            processResults: function (data) {
                                const results = (data.data || []).map(function(category) {
                                    return { id: category.id, text: category.name };
                                });

                                if (results.length === 0) {
                                    self.showError('La compañía seleccionada no tiene categorías disponibles');
                                }

                                return { results: results };
                            }
                        }
                    });

                    $('#createCategory').on('change', function() {
                        self.newTicket.category_id = $(this).val();
                    });

                    // Limpiar caché de esta compañía cuando cambias
                    self.categoriesCache[companyId] = null;

                    // Cargar categorías en background
                    fetch(`/api/tickets/categories?company_id=${companyId}&is_active=true&per_page=100`, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Guardar en caché para que Select2 AJAX lo use
                        self.categoriesCache[companyId] = (data.data || []);
                    })
                    .catch(error => {
                        console.error('Error cargando categorías:', error);
                    });
                }
            }, 100);
        },

        async createTicket() {
            // Validar campos requeridos
            if (!this.newTicket.company_id || !this.newTicket.title || !this.newTicket.category_id || !this.newTicket.description) {
                this.showError('Por favor completa todos los campos requeridos');
                return;
            }

            // Validar compañía
            if (!this.newTicket.company_id || this.newTicket.company_id === '') {
                this.showError('Por favor selecciona una compañía');
                return;
            }

            // Validar título (5-255 caracteres)
            const titleLength = this.newTicket.title.trim().length;
            if (titleLength < 5) {
                this.showError('El asunto debe tener al menos 5 caracteres');
                return;
            }
            if (titleLength > 255) {
                this.showError('El asunto no debe exceder 255 caracteres');
                return;
            }

            // Validar descripción (10-5000 caracteres)
            const descriptionLength = this.newTicket.description.trim().length;
            if (descriptionLength < 10) {
                this.showError('La descripción debe tener al menos 10 caracteres');
                return;
            }
            if (descriptionLength > 5000) {
                this.showError('La descripción no debe exceder 5000 caracteres');
                return;
            }

            // Validar que category_id sea válido
            if (!this.newTicket.category_id || this.newTicket.category_id === '') {
                this.showError('Por favor selecciona una categoría válida');
                return;
            }

            this.isCreating = true;

            try {
                const token = window.tokenManager.getAccessToken();

                // Preparar body
                const ticketData = {
                    title: this.newTicket.title.trim(),
                    description: this.newTicket.description.trim(),
                    company_id: this.newTicket.company_id,
                    category_id: this.newTicket.category_id
                };

                const response = await fetch('/api/tickets', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(ticketData)
                });

                if (!response.ok) {
                    const errorData = await response.json();

                    // Manejar errores de validación 422
                    if (response.status === 422 && errorData.errors) {
                        const errorMessages = [];
                        Object.keys(errorData.errors).forEach(field => {
                            errorMessages.push(...errorData.errors[field]);
                        });
                        throw new Error(errorMessages.join('\n'));
                    }

                    // Otros errores
                    throw new Error(errorData.message || 'Error al crear ticket');
                }

                const result = await response.json();
                const newTicketCode = result.data.ticket_code;

                // Subir archivos si existen
                if (this.newTicket.files.length > 0) {
                    for (const file of this.newTicket.files) {
                        const formData = new FormData();
                        formData.append('file', file);

                        const attachmentResponse = await fetch(`/api/tickets/${newTicketCode}/attachments`, {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        if (!attachmentResponse.ok) {
                            const attachmentError = await attachmentResponse.json();
                            console.warn(`Error al subir archivo ${file.name}:`, attachmentError);
                            // No lanzar error, continuar con otros archivos
                        }
                    }
                }

                this.showSuccess('Ticket creado exitosamente. Código: ' + newTicketCode);
                this.showCreateForm = false;
                this.resetNewTicket();
                await this.loadTickets();
                await this.loadStats();

            } catch (error) {
                console.error('Error al crear ticket:', error);
                this.showError(error.message || 'Error al crear el ticket');
            } finally {
                this.isCreating = false;
            }
        },

        handleTicketFiles(event) {
            const files = Array.from(event.target.files);

            // Validar cantidad de archivos
            if (files.length > 5) {
                this.showError('Máximo 5 archivos permitidos');
                event.target.value = '';
                return;
            }

            // Formatos permitidos según API
            const allowedExtensions = ['pdf', 'txt', 'log', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'mp4'];

            for (const file of files) {
                // Validar tamaño (max 10 MB)
                if (file.size > 10 * 1024 * 1024) {
                    this.showError(`El archivo ${file.name} excede el tamaño máximo de 10 MB`);
                    event.target.value = '';
                    return;
                }

                // Validar tipo/extensión de archivo
                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(fileExtension)) {
                    this.showError(`El archivo ${file.name} no está permitido. Formatos válidos: ${allowedExtensions.join(', ')}`);
                    event.target.value = '';
                    return;
                }
            }

            this.newTicket.files = files;
        },

        removeTicketFile(index) {
            this.newTicket.files.splice(index, 1);
            document.getElementById('createAttachment').value = '';
        },

        resetNewTicket() {
            this.newTicket = {
                company_id: '',
                title: '',
                category_id: '',
                description: '',
                files: []
            };
            document.getElementById('createAttachment').value = '';
            $('#createCompany').val('').trigger('change');
            $('#createCategory').val('').trigger('change');
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

$(function () {
    $('.checkbox-toggle').click(function () {
        var clicks = $(this).data('clicks')
        if (clicks) {
            $('.mailbox-messages input[type=\'checkbox\']').prop('checked', false)
            $('.checkbox-toggle .far.fa-check-square').removeClass('fa-check-square').addClass('fa-square')
        } else {
            $('.mailbox-messages input[type=\'checkbox\']').prop('checked', true)
            $('.checkbox-toggle .far.fa-square').removeClass('fa-square').addClass('fa-check-square')
        }
        $(this).data('clicks', !clicks)
    })
})
</script>
@endpush

@section('content')
<div x-data="ticketsList()" x-init="init()">
    <div class="row">
        <div class="col-md-3">
            @if($role === 'USER')
                <button class="btn btn-primary btn-block mb-3" @click="!showCreateForm ? openCreateModal() : (showCreateForm = false)">
                    <template x-if="!showCreateForm">
                        <span><i class="fas fa-plus mr-2"></i>Crear Nuevo Ticket</span>
                    </template>
                    <template x-if="showCreateForm">
                        <span><i class="fas fa-arrow-left mr-2"></i>Volver a Inbox</span>
                    </template>
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
                                    <span class="badge bg-primary float-right" x-show="stats.total > 0" x-text="stats.total || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'awaiting' }"
                                   @click.prevent="applyFolderFilter('awaiting', 'last_response_author_type=user')">
                                    <i class="far fa-clock"></i> Awaiting Support
                                    <span class="badge bg-warning float-right" x-show="stats.awaiting_support > 0" x-text="stats.awaiting_support || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'resolved' }"
                                   @click.prevent="applyFolderFilter('resolved', 'status=resolved')">
                                    <i class="far fa-check-circle"></i> Resolved
                                    <span class="badge bg-success float-right" x-show="stats.resolved > 0" x-text="stats.resolved || 0">0</span>
                                </a>
                            </li>
                        @elseif($role === 'AGENT')
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'all' }"
                                   @click.prevent="applyFolderFilter('all', '')">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right" x-show="stats.total > 0" x-text="stats.total || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'new' }"
                                   @click.prevent="applyFolderFilter('new', 'owner_agent_id=null')">
                                    <i class="fas fa-star"></i> New Tickets
                                    <span class="badge bg-info float-right" x-show="stats.new_tickets > 0" x-text="stats.new_tickets || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'assigned' }"
                                   @click.prevent="applyFolderFilter('assigned', 'owner_agent_id=me')">
                                    <i class="fas fa-user-check"></i> My Assigned
                                    <span class="badge bg-success float-right" x-show="stats.my_assigned > 0" x-text="stats.my_assigned || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'awaiting_response' }"
                                   @click.prevent="applyFolderFilter('awaiting_response', 'owner_agent_id=me&last_response_author_type=user')">
                                    <i class="far fa-comments"></i> Awaiting My Response
                                    <span class="badge bg-danger float-right" x-show="stats.awaiting_my_response > 0" x-text="stats.awaiting_my_response || 0">0</span>
                                </a>
                            </li>
                        @elseif($role === 'COMPANY_ADMIN')
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'all' }"
                                   @click.prevent="applyFolderFilter('all', '')">
                                    <i class="fas fa-inbox"></i> All Tickets
                                    <span class="badge bg-primary float-right" x-show="stats.total > 0" x-text="stats.total || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'new' }"
                                   @click.prevent="applyFolderFilter('new', 'owner_agent_id=null')">
                                    <i class="fas fa-star"></i> New Tickets
                                    <span class="badge bg-info float-right" x-show="stats.new_tickets > 0" x-text="stats.new_tickets || 0">0</span>
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
                        @if($role !== 'USER')
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeStatus === 'new' }"
                                   @click.prevent="applyStatusFilter('new', 'owner_agent_id=null')">
                                    <i class="far fa-circle text-info"></i> New
                                    <span class="badge bg-info float-right" x-text="stats.new_tickets || 0">0</span>
                                </a>
                            </li>
                        @endif
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

        <div class="col-md-9">
            <div x-show="!showCreateForm">
                @include('app.shared.tickets.partials.tickets-list')
            </div>

            <div x-show="showCreateForm">
                @include('app.shared.tickets.partials.create-ticket')
            </div>
        </div>
    </div>
</div>
@endsection