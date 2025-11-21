@extends('layouts.authenticated')

@section('title', 'Tickets')

@section('content_header', 'Sistema de Tickets')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Tickets</li>
@endsection

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
                                    <span class="badge bg-danger float-right" x-show="stats.my_assigned > 0" x-text="stats.my_assigned || 0">0</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#"
                                   class="nav-link"
                                   :class="{ 'active': activeFolder === 'awaiting_response' }"
                                   @click.prevent="applyFolderFilter('awaiting_response', 'owner_agent_id=me&last_response_author_type=user&status=open,pending')">
                                    <i class="far fa-comments"></i> Awaiting My Response
                                    <span class="badge bg-success float-right" x-show="stats.awaiting_my_response > 0" x-text="stats.awaiting_my_response || 0">0</span>
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

        @include('app.shared.tickets.partials.tickets-list')
    </div>
</div>
@endsection