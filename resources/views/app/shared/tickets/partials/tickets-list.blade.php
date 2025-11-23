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
        // SIMULATED DATA
        // ==============================================================
        const mockTickets = [
            {
                id: 1,
                code: 'TKT-2025-001',
                title: 'Problema con la impresora de RRHH',
                status: 'open',
                status_label: 'Abierto',
                created_at: 'Hace 5 mins',
                creator_name: 'Juan Perez',
                owner_agent_name: null,
                category_name: 'Hardware',
                responses_count: 2,
                attachments_count: 1,
                is_new: true
            },
            {
                id: 2,
                code: 'TKT-2025-002',
                title: 'Error al acceder al ERP',
                status: 'pending',
                status_label: 'Pendiente',
                created_at: 'Hace 2 horas',
                creator_name: 'Maria Garcia',
                owner_agent_name: 'Carlos Admin',
                category_name: 'Software',
                responses_count: 5,
                attachments_count: 0,
                is_new: false
            },
            {
                id: 3,
                code: 'TKT-2025-003',
                title: 'Solicitud de acceso a VPN',
                status: 'resolved',
                status_label: 'Resuelto',
                created_at: 'Ayer',
                creator_name: 'Pedro Lopez',
                owner_agent_name: 'Carlos Admin',
                category_name: 'Redes',
                responses_count: 8,
                attachments_count: 2,
                is_new: false
            },
            {
                id: 4,
                code: 'TKT-2025-004',
                title: 'Pantalla azul en laptop',
                status: 'closed',
                status_label: 'Cerrado',
                created_at: 'Hace 2 dias',
                creator_name: 'Ana Martinez',
                owner_agent_name: 'Carlos Admin',
                category_name: 'Hardware',
                responses_count: 12,
                attachments_count: 3,
                is_new: false
            },
            {
                id: 5,
                code: 'TKT-2025-005',
                title: 'Actualización de Licencia Office',
                status: 'open',
                status_label: 'Abierto',
                created_at: 'Hace 3 dias',
                creator_name: 'Luis Rodriguez',
                owner_agent_name: null,
                category_name: 'Software',
                responses_count: 1,
                attachments_count: 0,
                is_new: true
            }
        ];

        // RENDER LOGIC
        // ==============================================================
        const $tableBody = $('#tickets-table-body');
        const $template = $('#template-ticket-row');
        const userRole = '{{ $role }}'; // Blade injection

        function renderTickets(tickets) {
            $tableBody.empty();

            if (tickets.length === 0) {
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
                
                // 1. Mailbox Name -> NOW STATUS (Icon + Text)
                // Define Icon and Color based on status
                let statusIcon = 'fa-circle';
                let statusColor = 'text-secondary';
                
                if (ticket.status === 'open') {
                    statusIcon = 'fa-circle';
                    statusColor = 'text-danger';
                } else if (ticket.status === 'pending') {
                    statusIcon = 'fa-clock';
                    statusColor = 'text-warning';
                } else if (ticket.status === 'resolved') {
                    statusIcon = 'fa-check-circle';
                    statusColor = 'text-success';
                } else if (ticket.status === 'closed') {
                    statusIcon = 'fa-times-circle';
                    statusColor = 'text-secondary';
                }
                
                // Clean Status HTML: Icon + Text (Capitalized)
                const statusHtml = `<i class="fas ${statusIcon} ${statusColor} mr-2"></i> <span class="text-dark">${ticket.status_label}</span>`;
                $clone.find('.mailbox-name').html(statusHtml);

                // 2. Mailbox Subject -> Code + Title + Response Count
                // Construct Subject HTML: <b>Code</b> - Title
                let subjectHtml = `<b>${ticket.code}</b> - ${ticket.title}`;
                
                // Add Response Count to Subject (Float Right or Inline)
                if (ticket.responses_count > 0) {
                    // Fixed width container ensures icons align vertically perfectly
                    subjectHtml += `<span class="float-right text-dark text-sm" style="width: 50px; text-align: right;">
                        <small>${ticket.responses_count}</small> <i class="far fa-comments ml-1 text-dark"></i>
                    </span>`;
                }
                
                $clone.find('.mailbox-subject').html(subjectHtml);

                // 3. Attachments (STRICTLY ICON ONLY)
                if (ticket.attachments_count > 0) {
                    $clone.find('.mailbox-attachment').html('<i class="fas fa-paperclip"></i>');
                } else {
                    $clone.find('.mailbox-attachment').empty();
                }

                // 4. Date
                $clone.find('.mailbox-date').text(ticket.created_at);

                // Click Event
                $clone.find('tr.ticket-row').on('click', function(e) {
                    if ($(e.target).is('a')) return;
                    console.log(`[Tickets List] Opening ticket ${ticket.code}`);
                    // $(document).trigger('tickets:view-details', [ticket.id]);
                });

                $tableBody.append($clone);
            });
        }

        // Initial Render
        setTimeout(() => {
            renderTickets(mockTickets);
        }, 500); // Simulate network delay

        // ==============================================================
        // EVENTS
        // ==============================================================
        
        // Refresh Button
        $('#btn-refresh-list').click(function() {
            $tableBody.html(`
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Actualizando...</p>
                    </td>
                </tr>
            `);
            setTimeout(() => {
                renderTickets(mockTickets);
            }, 800);
        });

        // Filter Change (Simulation)
        $('#search-tickets').on('keyup', function() {
            // In a real app, this would trigger an API call
            console.log('[Tickets List] Search changed');
        });
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
