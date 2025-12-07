{{-- 
    View Company Modal Component - Enhanced Version
    
    Features:
    - Fetches complete company data via API
    - Loads company statistics (users, tickets, announcements, etc.)
    - Shows areas if enabled
    - Card + Tabs design (General, Contacto, Estadísticas, Áreas)
    - AdminLTE v3 styling with info-boxes
    
    Usage: @include('app.platform-admin.companies.partials.view-company-modal')
--}}

{{-- CSS Styles for this component --}}
<style>
    /* Company logo */
    .company-logo-modal {
        width: 90px;
        height: 90px;
        min-width: 90px;
        border-radius: 16px;
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 2.5rem;
        border: 3px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .company-logo-modal img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 14px;
    }
    /* Header company info */
    .company-header-info {
        min-width: 0;
        overflow: hidden;
    }
    /* Stat card mini */
    .stat-card-mini {
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 12px;
        padding: 1.25rem;
        text-align: center;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
    }
    .stat-card-mini:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .stat-card-mini .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-card-mini .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    /* Area card */
    .area-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.2s ease;
    }
    .area-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .area-card.inactive {
        opacity: 0.6;
        background: #f8f9fa;
    }
    /* Features badge */
    .feature-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .feature-badge.enabled {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    .feature-badge.disabled {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    /* Loading overlay */
    .modal-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 0.3rem;
    }
    /* Tab with loading */
    .tab-loading {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
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
            
            {{-- Header with company name prominent --}}
            <div class="modal-header bg-gradient-primary text-white py-3">
                <div class="d-flex align-items-center w-100">
                    {{-- Logo --}}
                    <div id="viewCompanyLogo" class="company-logo-modal mr-3">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="flex-grow-1 company-header-info">
                        <h4 class="mb-1 text-white" id="viewCompanyName">-</h4>
                        <div class="d-flex align-items-center flex-wrap">
                            <code class="mr-2 bg-light text-dark px-2 py-1 rounded" id="viewCompanyCode">-</code>
                            <span class="text-white-50 mr-2">·</span>
                            <span class="text-white-50" id="viewCompanyEmail">-</span>
                            <span id="viewCompanyStatusBadge" class="badge badge-light ml-2">-</span>
                        </div>
                        <div class="mt-2">
                            <span id="viewAreasFeatureBadge" class="feature-badge disabled">
                                <i class="fas fa-layer-group mr-1"></i> Áreas: Deshabilitadas
                            </span>
                        </div>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            
            {{-- Tabs Navigation (AdminLTE v3 Pills) --}}
            <div class="card-header border-bottom bg-light">
                <ul class="nav nav-pills" id="viewCompanyTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general" data-toggle="pill" href="#viewCompanyGeneral">
                            <i class="fas fa-info-circle"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-contact" data-toggle="pill" href="#viewCompanyContact">
                            <i class="fas fa-address-book"></i> Contacto
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-stats" data-toggle="pill" href="#viewCompanyStats">
                            <i class="fas fa-chart-bar"></i> Estadísticas
                        </a>
                    </li>
                    <li class="nav-item" id="tabAreasNav" style="display: none;">
                        <a class="nav-link" id="tab-areas" data-toggle="pill" href="#viewCompanyAreas">
                            <i class="fas fa-layer-group"></i> Áreas <span id="areasCount" class="badge badge-info ml-1">0</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            {{-- Tab Content --}}
            <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
                <div class="tab-content" id="viewCompanyTabContent">
                    
                    {{-- Tab: General --}}
                    <div class="tab-pane fade show active" id="viewCompanyGeneral" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-primary mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-building text-primary"></i> Información de la Empresa</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-5 text-muted">Nombre Comercial</dt>
                                            <dd class="col-sm-7" id="viewName">-</dd>
                                            <dt class="col-sm-5 text-muted">Nombre Legal</dt>
                                            <dd class="col-sm-7" id="viewLegalName">-</dd>
                                            <dt class="col-sm-5 text-muted">Email Soporte</dt>
                                            <dd class="col-sm-7" id="viewSupportEmail">-</dd>
                                            <dt class="col-sm-5 text-muted">Teléfono</dt>
                                            <dd class="col-sm-7" id="viewPhone">-</dd>
                                            <dt class="col-sm-5 text-muted">Sitio Web</dt>
                                            <dd class="col-sm-7" id="viewWebsite">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-outline card-info mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-cogs text-info"></i> Configuración</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-5 text-muted">Industria</dt>
                                            <dd class="col-sm-7" id="viewIndustry">-</dd>
                                            <dt class="col-sm-5 text-muted">Zona Horaria</dt>
                                            <dd class="col-sm-7" id="viewTimezone">-</dd>
                                            <dt class="col-sm-5 text-muted">Estado</dt>
                                            <dd class="col-sm-7" id="viewStatusText">-</dd>
                                            <dt class="col-sm-5 text-muted">Áreas Habilitadas</dt>
                                            <dd class="col-sm-7" id="viewAreasEnabled">-</dd>
                                        </dl>
                                    </div>
                                </div>
                                <div class="card card-outline card-warning">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-user-shield text-warning"></i> Administrador</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 text-muted">Nombre</dt>
                                            <dd class="col-sm-8" id="viewAdminName">-</dd>
                                            <dt class="col-sm-4 text-muted">Email</dt>
                                            <dd class="col-sm-8" id="viewAdminEmail">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Dates Row --}}
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="callout callout-info py-2 mb-0">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted"><i class="fas fa-calendar-plus mr-1"></i> Creada:</small>
                                            <span id="viewCreatedAt">-</span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted"><i class="fas fa-calendar-check mr-1"></i> Actualizada:</small>
                                            <span id="viewUpdatedAt">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Contact --}}
                    <div class="tab-pane fade" id="viewCompanyContact" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-success">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-map-marker-alt text-success"></i> Dirección</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 text-muted">Dirección</dt>
                                            <dd class="col-sm-8" id="viewAddress">-</dd>
                                            <dt class="col-sm-4 text-muted">Ciudad</dt>
                                            <dd class="col-sm-8" id="viewCity">-</dd>
                                            <dt class="col-sm-4 text-muted">Estado/Región</dt>
                                            <dd class="col-sm-8" id="viewState">-</dd>
                                            <dt class="col-sm-4 text-muted">País</dt>
                                            <dd class="col-sm-8" id="viewCountry">-</dd>
                                            <dt class="col-sm-4 text-muted">Código Postal</dt>
                                            <dd class="col-sm-8" id="viewPostalCode">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-outline card-secondary">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-file-contract text-secondary"></i> Información Legal</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-5 text-muted">Tax ID (RUT/NIT)</dt>
                                            <dd class="col-sm-7" id="viewTaxId">-</dd>
                                            <dt class="col-sm-5 text-muted">Representante Legal</dt>
                                            <dd class="col-sm-7" id="viewLegalRep">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Stats --}}
                    <div class="tab-pane fade" id="viewCompanyStats" role="tabpanel">
                        <div id="statsLoading" class="tab-loading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Cargando estadísticas...</p>
                        </div>
                        <div id="statsContent" style="display: none;">
                            {{-- Quick Stats Row --}}
                            <div class="row mb-4">
                                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                    <div class="stat-card-mini">
                                        <div class="stat-number text-primary" id="statTotalUsers">0</div>
                                        <div class="stat-label"><i class="fas fa-users mr-1"></i> Usuarios Totales</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                    <div class="stat-card-mini">
                                        <div class="stat-number text-success" id="statActiveAgents">0</div>
                                        <div class="stat-label"><i class="fas fa-headset mr-1"></i> Agentes Activos</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                    <div class="stat-card-mini">
                                        <div class="stat-number text-info" id="statTotalTickets">0</div>
                                        <div class="stat-label"><i class="fas fa-ticket-alt mr-1"></i> Tickets Totales</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                    <div class="stat-card-mini">
                                        <div class="stat-number text-warning" id="statOpenTickets">0</div>
                                        <div class="stat-label"><i class="fas fa-folder-open mr-1"></i> Tickets Abiertos</div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Detailed Stats --}}
                            <div class="row">
                                {{-- Tickets Section --}}
                                <div class="col-md-6 mb-3">
                                    <div class="card card-outline card-info h-100">
                                        <div class="card-header py-2">
                                            <h6 class="card-title mb-0"><i class="fas fa-ticket-alt text-info"></i> Tickets</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="description-block border-right">
                                                        <span class="description-text text-success">ABIERTOS</span>
                                                        <h5 class="description-header" id="statTicketsOpen">0</h5>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="description-block border-right">
                                                        <span class="description-text text-warning">PENDIENTES</span>
                                                        <h5 class="description-header" id="statTicketsPending">0</h5>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="description-block">
                                                        <span class="description-text text-info">RESUELTOS</span>
                                                        <h5 class="description-header" id="statTicketsResolved">0</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr class="my-2">
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted">Cerrados</small>
                                                    <h6 id="statTicketsClosed">0</h6>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Total</small>
                                                    <h6 id="statTicketsTotal">0</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Users Section --}}
                                <div class="col-md-6 mb-3">
                                    <div class="card card-outline card-primary h-100">
                                        <div class="card-header py-2">
                                            <h6 class="card-title mb-0"><i class="fas fa-users text-primary"></i> Usuarios</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="description-block border-right">
                                                        <span class="description-text text-info">AGENTES</span>
                                                        <h5 class="description-header" id="statUsersAgents">0</h5>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="description-block border-right">
                                                        <span class="description-text text-warning">ADMINS</span>
                                                        <h5 class="description-header" id="statUsersAdmins">0</h5>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="description-block">
                                                        <span class="description-text text-success">CLIENTES</span>
                                                        <h5 class="description-header" id="statUsersClients">0</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr class="my-2">
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <small class="text-muted">Seguidores</small>
                                                    <h6 id="statFollowers">0</h6>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Total Usuarios</small>
                                                    <h6 id="statUsersTotal">0</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Content Stats Row --}}
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="info-box bg-gradient-success mb-0">
                                        <span class="info-box-icon"><i class="fas fa-bullhorn"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Anuncios</span>
                                            <span class="info-box-number" id="statAnnouncements">0</span>
                                            <small id="statAnnouncementsPublished">0 publicados</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-box bg-gradient-info mb-0">
                                        <span class="info-box-icon"><i class="fas fa-book"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Artículos</span>
                                            <span class="info-box-number" id="statArticles">0</span>
                                            <small id="statArticlesPublished">0 publicados</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-box bg-gradient-warning mb-0">
                                        <span class="info-box-icon"><i class="fas fa-tags"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Categorías</span>
                                            <span class="info-box-number" id="statCategories">0</span>
                                            <small id="statCategoriesActive">0 activas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tab: Areas --}}
                    <div class="tab-pane fade" id="viewCompanyAreas" role="tabpanel">
                        <div id="areasLoading" class="tab-loading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Cargando áreas...</p>
                        </div>
                        <div id="areasContent" style="display: none;">
                            {{-- Areas Summary --}}
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3 id="areasTotalCount">0</h3>
                                            <p>Áreas Totales</p>
                                        </div>
                                        <div class="icon"><i class="fas fa-layer-group"></i></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3 id="areasActiveCount">0</h3>
                                            <p>Áreas Activas</p>
                                        </div>
                                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3 id="areasTicketsCount">0</h3>
                                            <p>Tickets en Áreas</p>
                                        </div>
                                        <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Areas List --}}
                            <div class="card card-outline card-primary">
                                <div class="card-header py-2">
                                    <h6 class="card-title mb-0"><i class="fas fa-list"></i> Lista de Áreas</h6>
                                </div>
                                <div class="card-body p-3" id="areasListContainer">
                                    <p class="text-muted text-center">No hay áreas configuradas.</p>
                                </div>
                            </div>
                        </div>
                        <div id="areasDisabled" style="display: none;">
                            <div class="text-center py-5">
                                <i class="fas fa-layer-group fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Áreas no habilitadas</h5>
                                <p class="text-muted">Esta empresa no tiene el módulo de áreas activado.</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btnModalEdit" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button type="button" id="btnModalChangeStatus" class="btn btn-warning">
                    <i class="fas fa-ban"></i> Cambiar Estado
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
    let areasLoaded = false;
    let statsLoaded = false;
    
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
        
        // Load stats when stats tab is shown
        $('#tab-stats').on('shown.bs.tab', function() {
            if (currentCompanyId && !statsLoaded) {
                loadCompanyStats(currentCompanyId);
            }
        });
        
        // Load areas when areas tab is shown
        $('#tab-areas').on('shown.bs.tab', function() {
            if (currentCompanyId && !areasLoaded) {
                loadCompanyAreas(currentCompanyId);
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
        areasLoaded = false;
        statsLoaded = false;
        
        // Reset tabs to general
        $('#viewCompanyTabs .nav-link').removeClass('active');
        $('#viewCompanyTabContent .tab-pane').removeClass('show active');
        $('#tab-general').addClass('active');
        $('#viewCompanyGeneral').addClass('show active');
        
        // Hide areas tab
        $('#tabAreasNav').hide();
        
        // Reset loading states
        $('#statsContent, #areasContent').hide();
        $('#statsLoading, #areasLoading').show();
    }
    
    // Format date helper
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });
    }
    
    function getToken() {
        return window.tokenManager?.getAccessToken() || localStorage.getItem('access_token');
    }
    
    // Fetch full company data from API
    async function fetchCompanyData(companyId) {
        const response = await fetch(`/api/companies/${companyId}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar datos de la empresa');
        }
        
        return await response.json();
    }
    
    // Fetch areas enabled status
    async function fetchAreasEnabled(companyId) {
        try {
            const response = await fetch(`/api/companies/${companyId}/settings/areas-enabled`, {
                headers: { 'Accept': 'application/json' }
            });
            
            if (!response.ok) return false;
            
            const data = await response.json();
            return data.data?.areas_enabled || false;
        } catch (error) {
            console.error('[ViewCompanyModal] Error fetching areas status:', error);
            return false;
        }
    }
    
    // Load company statistics
    async function loadCompanyStats(companyId) {
        $('#statsLoading').show();
        $('#statsContent').hide();
        
        try {
            const response = await fetch(`/api/analytics/companies/${companyId}/stats`, {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Error al cargar estadísticas');
            }
            
            const result = await response.json();
            const data = result.data || {};
            
            // Populate stats
            const users = data.users || {};
            const tickets = data.tickets || {};
            const announcements = data.announcements || {};
            const articles = data.articles || {};
            const categories = data.categories || {};
            
            // Quick stats
            $('#statTotalUsers').text(users.total || 0);
            $('#statActiveAgents').text(users.agents || 0);
            $('#statTotalTickets').text(tickets.total || 0);
            $('#statOpenTickets').text(tickets.open || 0);
            
            // Tickets breakdown
            $('#statTicketsOpen').text(tickets.open || 0);
            $('#statTicketsPending').text(tickets.pending || 0);
            $('#statTicketsResolved').text(tickets.resolved || 0);
            $('#statTicketsClosed').text(tickets.closed || 0);
            $('#statTicketsTotal').text(tickets.total || 0);
            
            // Users breakdown
            $('#statUsersAgents').text(users.agents || 0);
            $('#statUsersAdmins').text(users.admins || 0);
            $('#statUsersClients').text(users.clients || users.followers || 0);
            $('#statFollowers').text(currentCompanyData?.followersCount || users.followers || 0);
            $('#statUsersTotal').text(users.total || 0);
            
            // Content stats
            $('#statAnnouncements').text(announcements.total || 0);
            $('#statAnnouncementsPublished').text(`${announcements.published || 0} publicados`);
            
            $('#statArticles').text(articles.total || 0);
            $('#statArticlesPublished').text(`${articles.published || 0} publicados`);
            
            $('#statCategories').text(categories.total || 0);
            $('#statCategoriesActive').text(`${categories.active || 0} activas`);
            
            statsLoaded = true;
            
        } catch (error) {
            console.error('[ViewCompanyModal] Stats error:', error);
            // Show basic stats from company data
            $('#statTotalUsers').text(currentCompanyData?.totalUsersCount || 0);
            $('#statActiveAgents').text(currentCompanyData?.activeAgentsCount || 0);
            $('#statFollowers').text(currentCompanyData?.followersCount || 0);
        }
        
        $('#statsLoading').hide();
        $('#statsContent').show();
    }
    
    // Load company areas
    async function loadCompanyAreas(companyId) {
        $('#areasLoading').show();
        $('#areasContent, #areasDisabled').hide();
        
        try {
            const response = await fetch(`/api/areas?company_id=${companyId}&per_page=50`, {
                headers: {
                    'Authorization': `Bearer ${getToken()}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Error al cargar áreas');
            }
            
            const result = await response.json();
            const areas = result.data || [];
            
            // Calculate stats
            const totalAreas = areas.length;
            const activeAreas = areas.filter(a => a.is_active).length;
            const totalTickets = areas.reduce((sum, a) => sum + (a.active_tickets_count || 0), 0);
            
            $('#areasTotalCount').text(totalAreas);
            $('#areasActiveCount').text(activeAreas);
            $('#areasTicketsCount').text(totalTickets);
            $('#areasCount').text(totalAreas);
            
            // Render areas list
            const $container = $('#areasListContainer');
            
            if (areas.length === 0) {
                $container.html('<p class="text-muted text-center mb-0">No hay áreas configuradas.</p>');
            } else {
                $container.html(areas.map(area => `
                    <div class="area-card ${area.is_active ? '' : 'inactive'}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    ${area.is_active 
                                        ? '<i class="fas fa-check-circle text-success mr-2"></i>' 
                                        : '<i class="fas fa-pause-circle text-warning mr-2"></i>'}
                                    ${escapeHtml(area.name)}
                                </h6>
                                <small class="text-muted">${escapeHtml(area.description || 'Sin descripción')}</small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-info">${area.active_tickets_count || 0} tickets activos</span>
                                ${!area.is_active ? '<span class="badge badge-warning ml-1">Inactiva</span>' : ''}
                            </div>
                        </div>
                    </div>
                `).join(''));
            }
            
            areasLoaded = true;
            $('#areasContent').show();
            
        } catch (error) {
            console.error('[ViewCompanyModal] Areas error:', error);
            $('#areasListContainer').html('<p class="text-danger text-center mb-0"><i class="fas fa-exclamation-circle"></i> Error al cargar áreas</p>');
            $('#areasContent').show();
        }
        
        $('#areasLoading').hide();
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Populate modal with company data
    function populateModal(companyData, areasEnabled) {
        currentCompanyData = companyData;
        
        // Header
        $('#viewCompanyName').text(companyData.name || '-');
        $('#viewCompanyCode').text(companyData.companyCode || companyData.company_code || '-');
        $('#viewCompanyEmail').text(companyData.supportEmail || companyData.support_email || '-');
        
        // Logo
        const logoUrl = companyData.logoUrl || companyData.logo_url;
        if (logoUrl) {
            $('#viewCompanyLogo').html(`<img src="${logoUrl}" alt="Logo">`);
        } else {
            $('#viewCompanyLogo').html('<i class="fas fa-building"></i>');
        }
        
        // Status badge
        const status = (companyData.status || '').toLowerCase();
        const statusClasses = { active: 'badge-success', suspended: 'badge-warning', inactive: 'badge-secondary' };
        const statusLabels = { active: 'Activa', suspended: 'Suspendida', inactive: 'Inactiva' };
        $('#viewCompanyStatusBadge')
            .removeClass('badge-success badge-warning badge-secondary badge-danger')
            .addClass(statusClasses[status] || 'badge-secondary')
            .text(statusLabels[status] || companyData.status || '-');
        
        // Areas enabled badge
        if (areasEnabled) {
            $('#viewAreasFeatureBadge')
                .removeClass('disabled').addClass('enabled')
                .html('<i class="fas fa-layer-group mr-1"></i> Áreas: Habilitadas');
            $('#tabAreasNav').show();
            $('#viewAreasEnabled').html('<span class="badge badge-success"><i class="fas fa-check"></i> Sí</span>');
        } else {
            $('#viewAreasFeatureBadge')
                .removeClass('enabled').addClass('disabled')
                .html('<i class="fas fa-layer-group mr-1"></i> Áreas: Deshabilitadas');
            $('#tabAreasNav').hide();
            $('#viewAreasEnabled').html('<span class="badge badge-secondary"><i class="fas fa-times"></i> No</span>');
        }
        
        // General tab
        $('#viewName').text(companyData.name || '-');
        $('#viewLegalName').text(companyData.legalName || companyData.legal_name || '-');
        $('#viewSupportEmail').text(companyData.supportEmail || companyData.support_email || '-');
        $('#viewPhone').text(companyData.phone || '-');
        
        // Website with link
        const website = companyData.website;
        if (website) {
            $('#viewWebsite').html(`<a href="${website}" target="_blank">${website} <i class="fas fa-external-link-alt fa-xs"></i></a>`);
        } else {
            $('#viewWebsite').text('-');
        }
        
        // Configuration
        const industryName = companyData.industry?.name || '-';
        $('#viewIndustry').html(`<span class="badge badge-info">${escapeHtml(industryName)}</span>`);
        $('#viewTimezone').text(companyData.timezone || 'UTC');
        $('#viewStatusText').html($('#viewCompanyStatusBadge').clone());
        
        // Admin
        const admin = companyData.admin || {};
        const adminName = admin.name || admin.fullName || (admin.profile ? `${admin.profile.firstName || ''} ${admin.profile.lastName || ''}`.trim() : null) || admin.email || '-';
        $('#viewAdminName').text(adminName);
        $('#viewAdminEmail').text(admin.email || '-');
        
        // Contact tab
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
            areasLoaded = false;
            statsLoaded = false;
            
            // Reset tabs
            $('#viewCompanyTabs .nav-link').removeClass('active');
            $('#viewCompanyTabContent .tab-pane').removeClass('show active');
            $('#tab-general').addClass('active');
            $('#viewCompanyGeneral').addClass('show active');
            $('#tabAreasNav').hide();
            
            // Show modal with loading
            $('#viewCompanyLoading').show();
            $('#viewCompanyModal').modal('show');
            
            try {
                // Fetch company data and areas status in parallel
                const [companyResponse, areasEnabled] = await Promise.all([
                    fetchCompanyData(companyId),
                    fetchAreasEnabled(companyId)
                ]);
                
                const companyData = companyResponse.data || companyResponse;
                
                // Populate modal
                populateModal(companyData, areasEnabled);
                
                // Store for other operations
                $('#viewCompanyModal').data('company-id', companyId);
                $('#viewCompanyModal').data('company-data', companyData);
                
            } catch (error) {
                console.error('[ViewCompanyModal] Error loading company:', error);
                
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
