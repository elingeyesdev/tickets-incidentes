{{-- 
    View Agent Modal Component
    
    Features:
    - Shows complete agent information
    - Ticket statistics (assigned, resolved, resolution rate)
    - Agent info (email, phone, assigned date)
    - AdminLTE v3 styling
    - Inspired by view-company-modal.blade.php
    
    Usage: @include('app.company-admin.agents.partials.view-agent-modal')
--}}

{{-- CSS Styles for this component --}}
<style>
    /* Agent avatar */
    .agent-avatar-view {
        width: 80px;
        height: 80px;
        min-width: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 2rem;
        border: 2px solid #dee2e6;
        overflow: hidden;
    }
    .agent-avatar-view img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Modal header light */
    .modal-header-agent {
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-bottom: 1px solid #dee2e6;
    }
    /* Info list */
    .agent-info-list dt {
        color: #6c757d;
        font-weight: 500;
    }
    .agent-info-list dd {
        margin-bottom: 0.5rem;
    }
</style>

{{-- Modal: View Agent Profile --}}
<div class="modal fade" id="viewAgentModal" tabindex="-1" aria-labelledby="viewAgentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="position: relative;">
            {{-- Loading Overlay - AdminLTE v3 Official Pattern --}}
            <div id="viewAgentLoading" class="overlay" style="display:none;">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
            
            {{-- Header - Light theme for better readability --}}
            <div class="modal-header modal-header-agent py-3">
                <div class="d-flex align-items-center w-100">
                    {{-- Avatar --}}
                    <div id="viewAgentAvatar" class="agent-avatar-view mr-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <h4 class="mb-0 text-dark" id="viewAgentName">-</h4>
                            <span class="badge badge-primary ml-2"><i class="fas fa-user-tie"></i> Agente</span>
                        </div>
                        <div class="text-muted">
                            <i class="fas fa-envelope mr-1"></i> <span id="viewAgentEmail">-</span>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            
            {{-- Tabs Navigation --}}
            <div class="card-header border-bottom bg-white py-2">
                <ul class="nav nav-pills nav-pills-sm" id="viewAgentTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-agent-stats" data-toggle="pill" href="#viewAgentStats">
                            <i class="fas fa-chart-pie mr-1"></i> Estadísticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-agent-info" data-toggle="pill" href="#viewAgentInfo">
                            <i class="fas fa-info-circle mr-1"></i> Información
                        </a>
                    </li>
                </ul>
            </div>
            
            {{-- Tab Content --}}
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <div class="tab-content" id="viewAgentTabContent">
                    
                    {{-- Tab: Estadísticas --}}
                    <div class="tab-pane fade show active" id="viewAgentStats" role="tabpanel">
                        {{-- Main Stats - Tickets --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box shadow-none border">
                                    <span class="info-box-icon bg-info"><i class="fas fa-ticket-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Tickets Asignados</span>
                                        <span class="info-box-number" id="statAgentTicketsAssigned">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box shadow-none border">
                                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Tickets Resueltos</span>
                                        <span class="info-box-number" id="statAgentTicketsResolved">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Performance Card --}}
                        <div class="card shadow-none border mb-3">
                            <div class="card-header bg-light py-2 border-bottom">
                                <h6 class="card-title mb-0 text-secondary">
                                    <i class="fas fa-chart-line"></i> Rendimiento
                                </h6>
                            </div>
                            <div class="card-body py-3">
                                <div class="row text-center">
                                    <div class="col-6 border-right">
                                        <h4 class="mb-0 text-success" id="statAgentResolutionRate">0%</h4>
                                        <small class="text-muted">Tasa de Resolución</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="mb-0 text-primary" id="statAgentActiveTickets">0</h4>
                                        <small class="text-muted">Tickets Activos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Activity Info --}}
                        <div class="callout callout-info py-2 mb-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <small><i class="fas fa-calendar-check mr-1 text-muted"></i> Asignado: <span id="statAgentAssignedAt">-</span></small>
                                </div>
                                <div class="col-md-6">
                                    <small><i class="fas fa-folder-open mr-1 text-muted"></i> Pendientes: <span id="statAgentPendingTickets">0</span> tickets</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Información --}}
                    <div class="tab-pane fade" id="viewAgentInfo" role="tabpanel">
                        <div class="card card-outline card-primary mb-3">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0"><i class="fas fa-user text-primary"></i> Datos del Agente</h6>
                            </div>
                            <div class="card-body py-3">
                                <dl class="row agent-info-list mb-0">
                                    <dt class="col-sm-4">Nombre Completo</dt>
                                    <dd class="col-sm-8" id="infoAgentFullName">-</dd>
                                    <dt class="col-sm-4">Email</dt>
                                    <dd class="col-sm-8" id="infoAgentEmail">-</dd>
                                    <dt class="col-sm-4">Teléfono</dt>
                                    <dd class="col-sm-8" id="infoAgentPhone">-</dd>
                                    <dt class="col-sm-4">ID de Usuario</dt>
                                    <dd class="col-sm-8"><code id="infoAgentUserId">-</code></dd>
                                </dl>
                            </div>
                        </div>
                        
                        <div class="card card-outline card-success mb-3">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0"><i class="fas fa-briefcase text-success"></i> Estado en la Empresa</h6>
                            </div>
                            <div class="card-body py-3">
                                <dl class="row agent-info-list mb-0">
                                    <dt class="col-sm-4">Rol</dt>
                                    <dd class="col-sm-8"><span class="badge badge-info">Agente de Soporte</span></dd>
                                    <dt class="col-sm-4">Estado</dt>
                                    <dd class="col-sm-8" id="infoAgentStatus">-</dd>
                                    <dt class="col-sm-4">Fecha de Asignación</dt>
                                    <dd class="col-sm-8" id="infoAgentAssignedDate">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btnRemoveAgentFromProfile" class="btn btn-outline-danger">
                    <i class="fas fa-user-minus"></i> Remover del Equipo
                </button>
            </div>
            
            {{-- Hidden fields for state --}}
            <input type="hidden" id="viewAgentRoleId">
            <input type="hidden" id="viewAgentDisplayName">
        </div>
    </div>
</div>

{{-- Script for View Agent Modal --}}
<script>
(function() {
    console.log('[ViewAgentModal] Script loaded - waiting for jQuery...');
    
    let currentAgentData = null;
    
    function initViewAgentModal() {
        console.log('[ViewAgentModal] Initializing...');
        
        const $modal = $('#viewAgentModal');
        
        // Remove from profile button handler
        $('#btnRemoveAgentFromProfile').on('click', function() {
            if (currentAgentData) {
                $modal.modal('hide');
                // Trigger the remove modal
                $('#removeAgentRoleId').val(currentAgentData.id);
                $('#removeAgentName').text(currentAgentData.display_name || currentAgentData.email);
                $('#removeAgentModal').modal('show');
            }
        });
        
        // Reset on modal close
        $modal.on('hidden.bs.modal', function() {
            resetAgentModal();
        });
        
        console.log('[ViewAgentModal] Initialization complete');
    }
    
    function resetAgentModal() {
        currentAgentData = null;
        
        // Reset tabs to stats (first tab)
        $('#viewAgentTabs .nav-link').removeClass('active');
        $('#viewAgentTabContent .tab-pane').removeClass('show active');
        $('#tab-agent-stats').addClass('active');
        $('#viewAgentStats').addClass('show active');
        
        // Reset all fields
        $('#viewAgentName').text('-');
        $('#viewAgentEmail').text('-');
        $('#viewAgentAvatar').html('<i class="fas fa-user"></i>');
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
    }
    
    function formatRelativeTime(dateString) {
        if (!dateString) return null;
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Ahora mismo';
        if (diffMins < 60) return `Hace ${diffMins} min`;
        if (diffHours < 24) return `Hace ${diffHours} horas`;
        if (diffDays < 7) return `Hace ${diffDays} días`;
        return formatDate(dateString);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function populateModal(agent) {
        currentAgentData = agent;
        
        // Header
        const displayName = agent.display_name || agent.email?.split('@')[0] || 'Usuario';
        $('#viewAgentName').text(displayName);
        $('#viewAgentEmail').text(agent.email || '-');
        $('#viewAgentDisplayName').val(displayName);
        $('#viewAgentRoleId').val(agent.id);
        
        // Avatar
        if (agent.avatar_url) {
            $('#viewAgentAvatar').html(`<img src="${escapeHtml(agent.avatar_url)}" alt="Avatar">`);
        } else {
            const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=6c757d&color=fff&size=160`;
            $('#viewAgentAvatar').html(`<img src="${avatarUrl}" alt="Avatar">`);
        }
        
        // Statistics
        const ticketsAssigned = agent.tickets_assigned || 0;
        const ticketsResolved = agent.tickets_resolved || 0;
        const activeTickets = ticketsAssigned - ticketsResolved;
        const resolutionRate = ticketsAssigned > 0 ? Math.round((ticketsResolved / ticketsAssigned) * 100) : 0;
        
        $('#statAgentTicketsAssigned').text(ticketsAssigned);
        $('#statAgentTicketsResolved').text(ticketsResolved);
        $('#statAgentResolutionRate').text(resolutionRate + '%');
        $('#statAgentActiveTickets').text(Math.max(0, activeTickets));
        $('#statAgentAssignedAt').text(formatDate(agent.assigned_at));
        $('#statAgentPendingTickets').text(Math.max(0, activeTickets));
        
        // Information tab
        $('#infoAgentFullName').text(displayName);
        $('#infoAgentEmail').text(agent.email || '-');
        $('#infoAgentPhone').text(agent.phone_number || 'No registrado');
        $('#infoAgentUserId').text(agent.user_id || '-');
        $('#infoAgentStatus').html(agent.is_active ? 
            '<span class="badge badge-success">Activo</span>' : 
            '<span class="badge badge-secondary">Inactivo</span>'
        );
        $('#infoAgentAssignedDate').text(formatDate(agent.assigned_at));
    }
    
    // Expose global function to open modal
    window.ViewAgentModal = {
        open: function(agentData) {
            console.log('[ViewAgentModal] Opening for agent:', agentData);
            
            if (!agentData) {
                console.error('[ViewAgentModal] No agent data provided');
                return;
            }
            
            // Populate and show modal
            populateModal(agentData);
            $('#viewAgentModal').modal('show');
        }
    };
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initViewAgentModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initViewAgentModal);
            }
        }, 100);
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[ViewAgentModal] jQuery did not load');
            }
        }, 10000);
    }
})();
</script>
