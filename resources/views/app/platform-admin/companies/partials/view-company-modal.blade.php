{{-- 
    View Company Modal Component - Enhanced Version v2
    
    Features:
    - Fetches complete company data via API
    - Loads company statistics (users, tickets, announcements, etc.)
    - Shows team members (Company Admins and Agents)
    - Shows areas if enabled
    - Shows ticket categories
    - Tabs: Estadísticas, Información, Equipo, Categorías, Áreas
    - AdminLTE v3 styling
    
    Usage: @include('app.platform-admin.companies.partials.view-company-modal')
--}}

{{-- CSS Styles for this component --}}
<style>
    /* Company logo */
    .company-logo-view {
        width: 85px;
        height: 85px;
        min-width: 85px;
        border-radius: 14px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 2.5rem;
        border: 2px solid #dee2e6;
    }
    .company-logo-view img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 10px;
    }
    /* Modal header light */
    .modal-header-company {
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-bottom: 1px solid #dee2e6;
    }
    /* Stat card */
    .stat-card-compact {
        background: #fff;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
        height: 100%;
    }
    .stat-card-compact:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .stat-card-compact .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
    }
    .stat-card-compact .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }
    .stat-card-compact .stat-label {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    /* Team member card */
    .team-member-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        transition: all 0.2s ease;
    }
    .team-member-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .team-member-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
        font-size: 1.5rem;
        color: #adb5bd;
    }
    .team-member-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    /* Category/Area item */
    .list-item-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .list-item-card.inactive {
        opacity: 0.6;
        background: #f8f9fa;
    }
    /* Loading overlay */
    .modal-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 0.3rem;
    }
    /* Tab loading */
    .tab-loading {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }
    /* Feature badge */
    .feature-badge-sm {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
    }
    .feature-badge-sm.enabled {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    .feature-badge-sm.disabled {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    /* Info list */
    .info-list dt {
        color: #6c757d;
        font-weight: 500;
    }
    .info-list dd {
        margin-bottom: 0.5rem;
    }
</style>

{{-- Modal: View Company Details --}}
<div class="modal fade" id="viewCompanyModal" tabindex="-1" aria-labelledby="viewCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="position: relative;">
            {{-- Loading Overlay --}}
            <div id="viewCompanyLoading" class="modal-loading-overlay">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted mb-0">Cargando datos de empresa...</p>
                </div>
            </div>
            
            {{-- Header - Light theme for better readability --}}
            <div class="modal-header modal-header-company py-3">
                <div class="d-flex align-items-center w-100">
                    {{-- Logo --}}
                    <div id="viewCompanyLogo" class="company-logo-view mr-3">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <h4 class="mb-0 text-dark" id="viewCompanyName">-</h4>
                            <span id="viewCompanyStatusBadge" class="badge badge-secondary ml-2">-</span>
                        </div>
                        <div class="d-flex align-items-center flex-wrap">
                            <code class="mr-2 text-primary" id="viewCompanyCode">-</code>
                            <span class="text-muted mr-2">·</span>
                            <span class="text-muted" id="viewCompanyIndustry">-</span>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            
            {{-- Tabs Navigation --}}
            <div class="card-header border-bottom bg-white py-2">
                <ul class="nav nav-pills nav-pills-sm" id="viewCompanyTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-stats" data-toggle="pill" href="#viewCompanyStats">
                            <i class="fas fa-chart-pie mr-1"></i> Estadísticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-info" data-toggle="pill" href="#viewCompanyInfo">
                            <i class="fas fa-info-circle mr-1"></i> Información
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-team" data-toggle="pill" href="#viewCompanyTeam">
                            <i class="fas fa-users mr-1"></i> Equipo <span id="teamCount" class="badge badge-light ml-1">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-categories" data-toggle="pill" href="#viewCompanyCategories">
                            <i class="fas fa-tags mr-1"></i> Categorías <span id="categoriesCount" class="badge badge-light ml-1">0</span>
                        </a>
                    </li>
                    <li class="nav-item" id="tabAreasNav" style="display: none;">
                        <a class="nav-link" id="tab-areas" data-toggle="pill" href="#viewCompanyAreas">
                            <i class="fas fa-layer-group mr-1"></i> Áreas <span id="areasCount" class="badge badge-light ml-1">0</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            {{-- Tab Content --}}
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <div class="tab-content" id="viewCompanyTabContent">
                    
                    {{-- Tab: Estadísticas (First) --}}
                    <div class="tab-pane fade show active" id="viewCompanyStats" role="tabpanel">
                        <div id="statsLoading" class="tab-loading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0">Cargando estadísticas...</p>
                        </div>
                        <div id="statsContent" style="display: none;">
                            {{-- Main Stats Grid --}}
                            <div class="row mb-4">
                                <div class="col-lg-3 col-md-6 col-6 mb-3">
                                    <div class="stat-card-compact">
                                        <div class="stat-icon bg-primary text-white">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="stat-number text-primary" id="statTotalUsers">0</div>
                                        <div class="stat-label">Usuarios</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-6 mb-3">
                                    <div class="stat-card-compact">
                                        <div class="stat-icon bg-success text-white">
                                            <i class="fas fa-headset"></i>
                                        </div>
                                        <div class="stat-number text-success" id="statActiveAgents">0</div>
                                        <div class="stat-label">Agentes</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-6 mb-3">
                                    <div class="stat-card-compact">
                                        <div class="stat-icon bg-info text-white">
                                            <i class="fas fa-ticket-alt"></i>
                                        </div>
                                        <div class="stat-number text-info" id="statTotalTickets">0</div>
                                        <div class="stat-label">Tickets</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-6 mb-3">
                                    <div class="stat-card-compact">
                                        <div class="stat-icon bg-warning text-white">
                                            <i class="fas fa-folder-open"></i>
                                        </div>
                                        <div class="stat-number text-warning" id="statOpenTickets">0</div>
                                        <div class="stat-label">Abiertos</div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Tickets Breakdown --}}
                            <div class="card card-outline card-info mb-3">
                                <div class="card-header py-2">
                                    <h6 class="card-title mb-0"><i class="fas fa-ticket-alt text-info"></i> Desglose de Tickets</h6>
                                </div>
                                <div class="card-body py-3">
                                    <div class="row text-center">
                                        <div class="col">
                                            <div class="text-success font-weight-bold" id="statTicketsOpen">0</div>
                                            <small class="text-muted">Abiertos</small>
                                        </div>
                                        <div class="col border-left">
                                            <div class="text-warning font-weight-bold" id="statTicketsPending">0</div>
                                            <small class="text-muted">Pendientes</small>
                                        </div>
                                        <div class="col border-left">
                                            <div class="text-info font-weight-bold" id="statTicketsResolved">0</div>
                                            <small class="text-muted">Resueltos</small>
                                        </div>
                                        <div class="col border-left">
                                            <div class="text-secondary font-weight-bold" id="statTicketsClosed">0</div>
                                            <small class="text-muted">Cerrados</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Content Stats Row --}}
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="info-box bg-gradient-success mb-0 shadow-sm">
                                        <span class="info-box-icon"><i class="fas fa-bullhorn"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Anuncios</span>
                                            <span class="info-box-number" id="statAnnouncements">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-box bg-gradient-info mb-0 shadow-sm">
                                        <span class="info-box-icon"><i class="fas fa-book"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Artículos</span>
                                            <span class="info-box-number" id="statArticles">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-box bg-gradient-warning mb-0 shadow-sm">
                                        <span class="info-box-icon"><i class="fas fa-heart"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Seguidores</span>
                                            <span class="info-box-number" id="statFollowers">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Información (Combined General + Contact) --}}
                    <div class="tab-pane fade" id="viewCompanyInfo" role="tabpanel">
                        {{-- Description Card - Full width at top --}}
                        <div class="card card-outline card-info mb-3" id="descriptionCard" style="display: none;">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0"><i class="fas fa-align-left text-info"></i> Descripción</h6>
                            </div>
                            <div class="card-body py-3">
                                <p class="mb-0 text-muted" id="viewDescription">-</p>
                            </div>
                        </div>
                        
                        <div class="row">
                            {{-- Left Column: General --}}
                            <div class="col-lg-6">
                                <div class="card card-outline card-primary mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-building text-primary"></i> Datos Generales</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <dl class="row info-list mb-0">
                                            <dt class="col-sm-5">Nombre</dt>
                                            <dd class="col-sm-7" id="viewName">-</dd>
                                            <dt class="col-sm-5">Nombre Legal</dt>
                                            <dd class="col-sm-7" id="viewLegalName">-</dd>
                                            <dt class="col-sm-5">Email Soporte</dt>
                                            <dd class="col-sm-7" id="viewSupportEmail">-</dd>
                                            <dt class="col-sm-5">Teléfono</dt>
                                            <dd class="col-sm-7" id="viewPhone">-</dd>
                                            <dt class="col-sm-5">Sitio Web</dt>
                                            <dd class="col-sm-7" id="viewWebsite">-</dd>
                                            <dt class="col-sm-5">Zona Horaria</dt>
                                            <dd class="col-sm-7" id="viewTimezone">-</dd>
                                            <dt class="col-sm-5">Áreas</dt>
                                            <dd class="col-sm-7" id="viewAreasEnabled">-</dd>
                                        </dl>
                                    </div>
                                </div>
                                
                                {{-- Admin Card --}}
                                <div class="card card-outline card-warning mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-user-shield text-warning"></i> Administrador Principal</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="team-member-avatar mr-3" id="viewAdminAvatar" style="width:50px;height:50px;margin:0">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0" id="viewAdminName">-</h6>
                                                <small class="text-muted" id="viewAdminEmail">-</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Right Column --}}
                            <div class="col-lg-6">
                                {{-- Dirección --}}
                                <div class="card card-outline card-success mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-map-marker-alt text-success"></i> Dirección</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <dl class="row info-list mb-0">
                                            <dt class="col-sm-5">Dirección</dt>
                                            <dd class="col-sm-7" id="viewAddress">-</dd>
                                            <dt class="col-sm-5">Ciudad</dt>
                                            <dd class="col-sm-7" id="viewCity">-</dd>
                                            <dt class="col-sm-5">Estado/Región</dt>
                                            <dd class="col-sm-7" id="viewState">-</dd>
                                            <dt class="col-sm-5">País</dt>
                                            <dd class="col-sm-7" id="viewCountry">-</dd>
                                            <dt class="col-sm-5">Código Postal</dt>
                                            <dd class="col-sm-7" id="viewPostalCode">-</dd>
                                        </dl>
                                    </div>
                                </div>
                                
                                {{-- Legal --}}
                                <div class="card card-outline card-secondary mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-file-contract text-secondary"></i> Legal</h6>
                                    </div>
                                    <div class="card-body py-3">
                                        <dl class="row info-list mb-0">
                                            <dt class="col-sm-5">Tax ID</dt>
                                            <dd class="col-sm-7" id="viewTaxId">-</dd>
                                            <dt class="col-sm-5">Representante</dt>
                                            <dd class="col-sm-7" id="viewLegalRep">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Business Hours - Full width --}}
                        <div class="card card-outline card-purple mb-3" id="businessHoursCard" style="display: none;">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0"><i class="fas fa-clock text-purple"></i> Horario de Atención</h6>
                            </div>
                            <div class="card-body py-3">
                                <div id="viewBusinessHours" class="row">
                                    <p class="col-12 text-muted mb-0">No configurado</p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Dates --}}
                        <div class="callout callout-info py-2 mb-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <small><i class="fas fa-calendar-plus mr-1 text-muted"></i> Creada: <span id="viewCreatedAt">-</span></small>
                                </div>
                                <div class="col-md-6">
                                    <small><i class="fas fa-calendar-check mr-1 text-muted"></i> Actualizada: <span id="viewUpdatedAt">-</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Equipo --}}
                    <div class="tab-pane fade" id="viewCompanyTeam" role="tabpanel">
                        <div id="teamLoading" class="tab-loading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0">Cargando equipo...</p>
                        </div>
                        <div id="teamContent" style="display: none;">
                            {{-- Company Admins --}}
                            <h6 class="text-muted mb-3"><i class="fas fa-user-tie"></i> Administradores de Empresa</h6>
                            <div class="row mb-4" id="teamAdminsContainer">
                                <div class="col-12 text-center text-muted py-3">
                                    Sin administradores encontrados
                                </div>
                            </div>
                            
                            {{-- Agents --}}
                            <h6 class="text-muted mb-3"><i class="fas fa-headset"></i> Agentes de Soporte</h6>
                            <div class="row" id="teamAgentsContainer">
                                <div class="col-12 text-center text-muted py-3">
                                    Sin agentes encontrados
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Categorías --}}
                    <div class="tab-pane fade" id="viewCompanyCategories" role="tabpanel">
                        <div id="categoriesLoading" class="tab-loading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0">Cargando categorías...</p>
                        </div>
                        <div id="categoriesContent" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="info-box bg-light mb-0 shadow-sm">
                                        <span class="info-box-icon bg-info"><i class="fas fa-tags"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Categorías</span>
                                            <span class="info-box-number" id="categoriesTotalCount">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-light mb-0 shadow-sm">
                                        <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Activas</span>
                                            <span class="info-box-number" id="categoriesActiveCount">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="categoriesListContainer">
                                <p class="text-muted text-center">No hay categorías configuradas.</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Áreas --}}
                    <div class="tab-pane fade" id="viewCompanyAreas" role="tabpanel">
                        <div id="areasLoading" class="tab-loading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0">Cargando áreas...</p>
                        </div>
                        <div id="areasContent" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="info-box bg-light mb-0 shadow-sm">
                                        <span class="info-box-icon bg-primary"><i class="fas fa-layer-group"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Áreas</span>
                                            <span class="info-box-number" id="areasTotalCount">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-light mb-0 shadow-sm">
                                        <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Activas</span>
                                            <span class="info-box-number" id="areasActiveCount">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="areasListContainer">
                                <p class="text-muted text-center">No hay áreas configuradas.</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btnModalEdit" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button type="button" id="btnModalChangeStatus" class="btn btn-warning">
                    <i class="fas fa-exchange-alt"></i> Estado
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Script for View Company Modal --}}
<script>
(function() {
    console.log('[ViewCompanyModal] Script loaded - waiting for jQuery...');
    
    let currentCompanyId = null;
    let currentCompanyData = null;
    let areasEnabled = false;
    let statsLoaded = false;
    let teamLoaded = false;
    let categoriesLoaded = false;
    let areasLoaded = false;
    
    function initViewCompanyModal() {
        console.log('[ViewCompanyModal] Initializing...');
        
        const $modal = $('#viewCompanyModal');
        
        // Modal button handlers
        $('#btnModalEdit').on('click', function() {
            if (currentCompanyId) {
                $modal.modal('hide');
                $(document).trigger('openEditCompanyModal', [currentCompanyId]);
            }
        });
        
        $('#btnModalChangeStatus').on('click', function() {
            if (currentCompanyId && currentCompanyData) {
                $modal.modal('hide');
                $(document).trigger('openStatusCompanyModal', [currentCompanyId]);
            }
        });
        
        // Tab change handlers - lazy load
        $('#tab-team').on('shown.bs.tab', function() {
            if (currentCompanyId && !teamLoaded) {
                loadTeamMembers(currentCompanyId);
            }
        });
        
        $('#tab-categories').on('shown.bs.tab', function() {
            if (currentCompanyId && !categoriesLoaded) {
                loadCategories(currentCompanyId);
            }
        });
        
        $('#tab-areas').on('shown.bs.tab', function() {
            if (currentCompanyId && !areasLoaded && areasEnabled) {
                loadAreas(currentCompanyId);
            }
        });
        
        // Reset on modal close
        $modal.on('hidden.bs.modal', function() {
            resetModal();
        });
        
        console.log('[ViewCompanyModal] Initialization complete');
    }
    
    function resetModal() {
        currentCompanyId = null;
        currentCompanyData = null;
        areasEnabled = false;
        statsLoaded = false;
        teamLoaded = false;
        categoriesLoaded = false;
        areasLoaded = false;
        
        // Reset tabs to stats (first tab)
        $('#viewCompanyTabs .nav-link').removeClass('active');
        $('#viewCompanyTabContent .tab-pane').removeClass('show active');
        $('#tab-stats').addClass('active');
        $('#viewCompanyStats').addClass('show active');
        
        // Hide areas tab
        $('#tabAreasNav').hide();
        
        // Reset loading states
        $('#statsContent, #teamContent, #categoriesContent, #areasContent').hide();
        $('#statsLoading, #teamLoading, #categoriesLoading, #areasLoading').show();
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
    
    function getToken() {
        return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Fetch complete company data
    async function fetchCompanyData(companyId) {
        const response = await fetch(`/api/companies/${companyId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`,
                'Accept': 'application/json'
            }
        });
        if (!response.ok) throw new Error('Error al cargar empresa');
        return await response.json();
    }
    
    // Fetch areas enabled status
    async function fetchAreasEnabled(companyId) {
        try {
            const response = await fetch(`/api/companies/${companyId}/settings/areas-enabled`);
            if (!response.ok) return false;
            const data = await response.json();
            return data.data?.areas_enabled || false;
        } catch (error) {
            return false;
        }
    }
    
    // Load statistics
    async function loadStats(companyId) {
        try {
            const response = await fetch(`/api/analytics/companies/${companyId}/stats`, {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Error loading stats');
            
            const result = await response.json();
            const data = result.data || {};
            const users = data.users || {};
            const tickets = data.tickets || {};
            const announcements = data.announcements || {};
            const articles = data.articles || {};
            
            $('#statTotalUsers').text(users.total || 0);
            $('#statActiveAgents').text(users.agents || 0);
            $('#statTotalTickets').text(tickets.total || 0);
            $('#statOpenTickets').text(tickets.open || 0);
            
            $('#statTicketsOpen').text(tickets.open || 0);
            $('#statTicketsPending').text(tickets.pending || 0);
            $('#statTicketsResolved').text(tickets.resolved || 0);
            $('#statTicketsClosed').text(tickets.closed || 0);
            
            $('#statAnnouncements').text(announcements.total || 0);
            $('#statArticles').text(articles.total || 0);
            $('#statFollowers').text(currentCompanyData?.followersCount || currentCompanyData?.followers_count || 0);
            
        } catch (error) {
            console.error('[ViewCompanyModal] Stats error:', error);
            // Fallback to company data
            $('#statTotalUsers').text(currentCompanyData?.totalUsersCount || 0);
            $('#statActiveAgents').text(currentCompanyData?.activeAgentsCount || 0);
            $('#statFollowers').text(currentCompanyData?.followersCount || 0);
        }
        
        statsLoaded = true;
        $('#statsLoading').hide();
        $('#statsContent').show();
    }
    
    // Load team members
    async function loadTeamMembers(companyId) {
        try {
            // Fetch users with roles for this company
            // Use companyId param (camelCase) as expected by API
            const response = await fetch(`/api/users?companyId=${companyId}&per_page=100`, {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Error loading team');
            
            const result = await response.json();
            const users = result.data || [];
            
            console.log('[ViewCompanyModal] Users loaded:', users.length, users);
            
            // Filter by role - API already filters by company, just check roleCode
            const admins = users.filter(u => {
                const roles = u.roleContexts || [];
                return roles.some(r => r.roleCode === 'COMPANY_ADMIN');
            });
            
            const agents = users.filter(u => {
                const roles = u.roleContexts || [];
                return roles.some(r => r.roleCode === 'AGENT');
            });
            
            console.log('[ViewCompanyModal] Admins:', admins.length, 'Agents:', agents.length);
            
            $('#teamCount').text(admins.length + agents.length);
            
            // Render admins
            if (admins.length > 0) {
                $('#teamAdminsContainer').html(admins.map(user => renderTeamCard(user, 'COMPANY_ADMIN')).join(''));
            } else {
                $('#teamAdminsContainer').html('<div class="col-12 text-center text-muted py-2">Sin administradores</div>');
            }
            
            // Render agents
            if (agents.length > 0) {
                $('#teamAgentsContainer').html(agents.map(user => renderTeamCard(user, 'AGENT')).join(''));
            } else {
                $('#teamAgentsContainer').html('<div class="col-12 text-center text-muted py-2">Sin agentes</div>');
            }
            
        } catch (error) {
            console.error('[ViewCompanyModal] Team error:', error);
            $('#teamAdminsContainer, #teamAgentsContainer').html(
                '<div class="col-12 text-center text-danger py-2"><i class="fas fa-exclamation-circle"></i> Error al cargar</div>'
            );
        }
        
        teamLoaded = true;
        $('#teamLoading').hide();
        $('#teamContent').show();
    }
    
    function renderTeamCard(user, role) {
        const profile = user.profile || {};
        const fullName = profile.firstName 
            ? `${profile.firstName} ${profile.lastName || ''}`.trim() 
            : user.email.split('@')[0];
        const avatar = profile.avatarUrl;
        const status = (user.status || '').toLowerCase();
        const statusBadge = status === 'active' 
            ? '<span class="badge badge-success badge-sm">Activo</span>' 
            : '<span class="badge badge-secondary badge-sm">Inactivo</span>';
        const roleIcon = role === 'COMPANY_ADMIN' ? 'fa-user-tie text-warning' : 'fa-headset text-info';
        
        return `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="team-member-card">
                    <div class="team-member-avatar">
                        ${avatar 
                            ? `<img src="${avatar}" alt="${escapeHtml(fullName)}">` 
                            : `<i class="fas ${roleIcon}"></i>`}
                    </div>
                    <h6 class="mb-1">${escapeHtml(fullName)}</h6>
                    <small class="text-muted d-block mb-2">${escapeHtml(user.email)}</small>
                    ${statusBadge}
                </div>
            </div>
        `;
    }
    
    // Load categories
    async function loadCategories(companyId) {
        try {
            const response = await fetch(`/api/tickets/categories?company_id=${companyId}&per_page=50`, {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Error loading categories');
            
            const result = await response.json();
            const categories = result.data || [];
            
            const total = categories.length;
            const active = categories.filter(c => c.is_active !== false).length;
            
            $('#categoriesTotalCount').text(total);
            $('#categoriesActiveCount').text(active);
            $('#categoriesCount').text(total);
            
            if (categories.length === 0) {
                $('#categoriesListContainer').html('<p class="text-muted text-center mb-0">No hay categorías configuradas.</p>');
            } else {
                $('#categoriesListContainer').html(categories.map(cat => `
                    <div class="list-item-card ${cat.is_active === false ? 'inactive' : ''}">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-${cat.color || 'secondary'} mr-2" style="width:12px;height:12px;padding:0;border-radius:50%;${cat.color ? 'background-color:'+cat.color : ''}"></span>
                            <div>
                                <strong>${escapeHtml(cat.name)}</strong>
                                ${cat.description ? `<br><small class="text-muted">${escapeHtml(cat.description)}</small>` : ''}
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-light border">${cat.active_tickets_count || 0} tickets</span>
                            ${cat.is_active === false 
                                ? '<span class="badge badge-warning ml-1">Inactiva</span>' 
                                : '<span class="badge badge-success ml-1">Activa</span>'}
                        </div>
                    </div>
                `).join(''));
            }
            
        } catch (error) {
            console.error('[ViewCompanyModal] Categories error:', error);
            $('#categoriesListContainer').html('<p class="text-danger text-center mb-0"><i class="fas fa-exclamation-circle"></i> Error al cargar</p>');
        }
        
        categoriesLoaded = true;
        $('#categoriesLoading').hide();
        $('#categoriesContent').show();
    }
    
    // Load areas
    async function loadAreas(companyId) {
        try {
            const response = await fetch(`/api/areas?company_id=${companyId}&per_page=50`, {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Error loading areas');
            
            const result = await response.json();
            const areas = result.data || [];
            
            const total = areas.length;
            const active = areas.filter(a => a.is_active).length;
            const tickets = areas.reduce((sum, a) => sum + (a.active_tickets_count || 0), 0);
            
            $('#areasTotalCount').text(total);
            $('#areasActiveCount').text(active);
            $('#areasTicketsCount').text(tickets);
            $('#areasCount').text(total);
            
            if (areas.length === 0) {
                $('#areasListContainer').html('<p class="text-muted text-center mb-0">No hay áreas configuradas.</p>');
            } else {
                $('#areasListContainer').html(areas.map(area => `
                    <div class="list-item-card ${area.is_active ? '' : 'inactive'}">
                        <div>
                            <strong>${escapeHtml(area.name)}</strong>
                            ${area.description ? `<br><small class="text-muted">${escapeHtml(area.description)}</small>` : ''}
                        </div>
                        <div>
                            <span class="badge badge-light border">${area.active_tickets_count || 0} tickets</span>
                            ${!area.is_active ? '<span class="badge badge-warning ml-1">Inactiva</span>' : '<span class="badge badge-success ml-1">Activa</span>'}
                        </div>
                    </div>
                `).join(''));
            }
            
        } catch (error) {
            console.error('[ViewCompanyModal] Areas error:', error);
            $('#areasListContainer').html('<p class="text-danger text-center mb-0"><i class="fas fa-exclamation-circle"></i> Error al cargar</p>');
        }
        
        areasLoaded = true;
        $('#areasLoading').hide();
        $('#areasContent').show();
    }
    
    // Populate modal
    function populateModal(companyData, hasAreas) {
        currentCompanyData = companyData;
        areasEnabled = hasAreas;
        
        // Header
        $('#viewCompanyName').text(companyData.name || '-');
        $('#viewCompanyCode').text(companyData.companyCode || companyData.company_code || '-');
        $('#viewCompanyIndustry').text(companyData.industry?.name || '-');
        
        // Logo
        const logoUrl = companyData.logoUrl || companyData.logo_url;
        if (logoUrl) {
            $('#viewCompanyLogo').html(`<img src="${logoUrl}" alt="Logo">`);
        } else {
            $('#viewCompanyLogo').html('<i class="fas fa-building"></i>');
        }
        
        // Status
        const status = (companyData.status || '').toLowerCase();
        const statusClasses = { active: 'badge-success', suspended: 'badge-warning', inactive: 'badge-secondary' };
        const statusLabels = { active: 'Activa', suspended: 'Suspendida', inactive: 'Inactiva' };
        $('#viewCompanyStatusBadge')
            .removeClass('badge-success badge-warning badge-secondary badge-danger')
            .addClass(statusClasses[status] || 'badge-secondary')
            .text(statusLabels[status] || companyData.status || '-');
        
        // Areas badge
        if (hasAreas) {
            $('#viewAreasFeatureBadge').removeClass('disabled').addClass('enabled').html('<i class="fas fa-layer-group mr-1"></i> Áreas');
            $('#tabAreasNav').show();
        } else {
            $('#viewAreasFeatureBadge').removeClass('enabled').addClass('disabled').html('<i class="fas fa-layer-group mr-1"></i> Áreas');
            $('#tabAreasNav').hide();
        }
        
        // Info tab
        $('#viewName').text(companyData.name || '-');
        $('#viewLegalName').text(companyData.legalName || companyData.legal_name || '-');
        $('#viewSupportEmail').text(companyData.supportEmail || companyData.support_email || '-');
        $('#viewPhone').text(companyData.phone || '-');
        $('#viewTimezone').text(companyData.timezone || 'UTC');
        
        // Description - show card only if exists
        const description = companyData.description;
        if (description && description.trim()) {
            $('#viewDescription').text(description);
            $('#descriptionCard').show();
        } else {
            $('#descriptionCard').hide();
        }
        
        // Business Hours - show card only if configured
        const businessHours = companyData.businessHours || companyData.business_hours;
        if (businessHours && typeof businessHours === 'object' && Object.keys(businessHours).length > 0) {
            const daysMap = {
                monday: 'Lunes', tuesday: 'Martes', wednesday: 'Miércoles',
                thursday: 'Jueves', friday: 'Viernes', saturday: 'Sábado', sunday: 'Domingo'
            };
            let hoursHtml = '';
            for (const [day, hours] of Object.entries(businessHours)) {
                const dayName = daysMap[day.toLowerCase()] || day;
                if (hours && hours.open && hours.close) {
                    hoursHtml += `<div class="col-md-3 col-sm-4 col-6 mb-2">
                        <strong>${dayName}</strong><br>
                        <small class="text-muted">${hours.open} - ${hours.close}</small>
                    </div>`;
                } else if (hours === 'closed' || hours?.closed) {
                    hoursHtml += `<div class="col-md-3 col-sm-4 col-6 mb-2">
                        <strong>${dayName}</strong><br>
                        <small class="text-muted">Cerrado</small>
                    </div>`;
                }
            }
            if (hoursHtml) {
                $('#viewBusinessHours').html(hoursHtml);
                $('#businessHoursCard').show();
            } else {
                $('#businessHoursCard').hide();
            }
        } else {
            $('#businessHoursCard').hide();
        }
        
        const website = companyData.website;
        if (website) {
            $('#viewWebsite').html(`<a href="${website}" target="_blank">${escapeHtml(website)} <i class="fas fa-external-link-alt fa-xs"></i></a>`);
        } else {
            $('#viewWebsite').text('-');
        }
        
        // Areas enabled status
        if (hasAreas) {
            $('#viewAreasEnabled').html('<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Habilitadas</span>');
        } else {
            $('#viewAreasEnabled').html('<span class="badge badge-secondary"><i class="fas fa-times mr-1"></i>Deshabilitadas</span>');
        }
        
        // Admin - API returns flat fields: adminName, adminEmail, adminAvatar
        const adminName = companyData.adminName || '-';
        const adminEmail = companyData.adminEmail || '-';
        const adminAvatar = companyData.adminAvatar || null;
        
        $('#viewAdminName').text(adminName);
        $('#viewAdminEmail').text(adminEmail);
        if (adminAvatar) {
            $('#viewAdminAvatar').html(`<img src="${adminAvatar}" alt="">`);
        } else {
            $('#viewAdminAvatar').html('<i class="fas fa-user"></i>');
        }
        
        // Contact
        $('#viewAddress').text(companyData.contactAddress || companyData.contact_address || '-');
        $('#viewCity').text(companyData.contactCity || companyData.contact_city || '-');
        $('#viewState').text(companyData.contactState || companyData.contact_state || '-');
        $('#viewCountry').text(companyData.contactCountry || companyData.contact_country || '-');
        $('#viewPostalCode').text(companyData.contactPostalCode || companyData.contact_postal_code || '-');
        $('#viewTaxId').text(companyData.taxId || companyData.tax_id || '-');
        $('#viewLegalRep').text(companyData.legalRepresentative || companyData.legal_representative || '-');
        
        // Dates
        $('#viewCreatedAt').text(formatDate(companyData.createdAt || companyData.created_at));
        $('#viewUpdatedAt').text(formatDate(companyData.updatedAt || companyData.updated_at));
    }
    
    // Open modal
    window.ViewCompanyModal = {
        open: async function(companyId) {
            currentCompanyId = companyId;
            statsLoaded = false;
            teamLoaded = false;
            categoriesLoaded = false;
            areasLoaded = false;
            
            // Reset tabs
            $('#viewCompanyTabs .nav-link').removeClass('active');
            $('#viewCompanyTabContent .tab-pane').removeClass('show active');
            $('#tab-stats').addClass('active');
            $('#viewCompanyStats').addClass('show active');
            $('#tabAreasNav').hide();
            
            // Show loading
            $('#viewCompanyLoading').show();
            $('#viewCompanyModal').modal('show');
            
            try {
                const [companyResponse, hasAreas] = await Promise.all([
                    fetchCompanyData(companyId),
                    fetchAreasEnabled(companyId)
                ]);
                
                const companyData = companyResponse.data || companyResponse;
                populateModal(companyData, hasAreas);
                
                // Auto-load stats since it's the first tab
                await loadStats(companyId);
                
                $('#viewCompanyModal').data('company-id', companyId);
                
            } catch (error) {
                console.error('[ViewCompanyModal] Error:', error);
                if (typeof window.showToast === 'function') {
                    window.showToast('error', 'Error al cargar datos de la empresa');
                }
            }
            
            $('#viewCompanyLoading').hide();
        }
    };
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initViewCompanyModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initViewCompanyModal);
            }
        }, 100);
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[ViewCompanyModal] jQuery did not load');
            }
        }, 10000);
    }
})();
</script>
