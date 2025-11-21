ua <div class="col-md-9">
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
