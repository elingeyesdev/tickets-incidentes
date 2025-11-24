@extends('layouts.authenticated')

@section('title', 'Centro de Ayuda')
@section('content_header', 'Centro de Ayuda')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

            <!-- ========== HERO SECTION ========== -->
            <div class="row mb-5">
                <div class="col-md-12 text-center">
                    <h1 class="display-4 font-weight-light mb-3">
                        <i class="fas fa-question-circle text-primary mr-2"></i>Centro de Ayuda
                    </h1>
                    <p class="text-muted lead">Encuentra respuestas a tus preguntas</p>
                </div>
            </div>

            <!-- ========== SEARCH SECTION ========== -->
            <div class="row mb-5">
                <div class="col-md-8 mx-auto">
                    <div class="input-group input-group-lg">
                        <input type="text"
                               id="help-search-input"
                               class="form-control"
                               placeholder="¿Qué necesitas encontrar?">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="help-search-btn">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== CATEGORIES SECTION ========== -->
            <div class="row mb-5">
                <div class="col-md-12">
                    <h4 class="mb-3">
                        <i class="fas fa-tags mr-2 text-muted"></i>Busca por categoría
                    </h4>
                    <div style="overflow-x: auto; overflow-y: hidden; padding-bottom: 10px;">
                        <div id="categories-container" style="display: flex; gap: 15px; min-width: max-content;">
                            <!-- Categories will be loaded here via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== ARTICLES SECTION ========== -->
            <div class="row">
                <div class="col-md-12">
                    <!-- Loading Spinner -->
                    <div id="loading-spinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando artículos...</p>
                    </div>

                    <!-- Articles Container -->
                    <div id="articles-container" style="display: none;"></div>

                    <!-- Empty State -->
                    <div id="empty-state" style="display: none;"></div>

                    <!-- No Followers State -->
                    <div id="no-followers-state" style="display: none;"></div>
                </div>
            </div>

            <!-- ========== CTA SECTION ========== -->
            <div class="row mt-5" id="cta-section" style="display: none;">
                <div class="col-md-8 mx-auto">
                    <div class="callout callout-warning">
                        <h5>
                            <i class="fas fa-headset mr-2"></i>¿No encontraste lo que buscas?
                        </h5>
                        <p class="mb-0">
                            Si no encontraste la respuesta a tu pregunta en nuestro Centro de Ayuda, nuestro equipo de soporte está aquí para ayudarte.
                        </p>
                        <div class="mt-3">
                            <a href="/app/user/tickets" class="btn btn-warning btn-lg" id="create-ticket-cta">
                                <i class="fas fa-plus-circle mr-2"></i>Crear un ticket de soporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ARTICLE DETAIL MODAL -->
<div class="modal fade" id="articleModal" tabindex="-1" role="dialog" aria-labelledby="articleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="articleModalLabel">Detalles del Artículo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Company Info -->
                <div class="mb-4">
                    <div class="d-flex align-items-center">
                        <img id="modal-company-logo"
                             src=""
                             alt="Company Logo"
                             class="rounded-circle mr-3"
                             style="width: 60px; height: 60px; object-fit: cover;"
                             onerror="this.src='/vendor/adminlte/dist/img/AdminLTELogo.png'">
                        <div>
                            <h6 class="mb-1" id="modal-company-name"></h6>
                            <small class="text-muted" id="modal-company-code"></small>
                        </div>
                    </div>
                </div>

                <!-- Article Header -->
                <div class="mb-4">
                    <div class="mb-2">
                        <span class="badge" id="modal-category-badge"></span>
                    </div>
                    <h3 id="modal-article-title" class="text-dark mb-3"></h3>
                    <div class="text-muted small mb-3">
                        <span class="mr-3">
                            <i class="fas fa-eye mr-1"></i><span id="modal-views-count">0</span> vistas
                        </span>
                        <span class="mr-3">
                            <i class="fas fa-user mr-1"></i><span id="modal-author-name">Anónimo</span>
                        </span>
                        <span>
                            <i class="fas fa-calendar mr-1"></i><span id="modal-publish-date"></span>
                        </span>
                    </div>
                </div>

                <!-- Article Content -->
                <div id="modal-article-content" class="article-content">
                    <!-- Loading spinner -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando contenido...</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
(function() {
    'use strict';
    console.log('[Help Center] Script initialized');

    // ========== STATE VARIABLES ==========
    let currentPage = 1;
    let currentCategory = '';
    let currentSearch = '';
    let token = null;
    let userFollowsCompanies = false;

    // ========== CATEGORY CONFIGURATION ==========
    const categoryConfig = {
        'ACCOUNT_PROFILE': {
            id: 'cat-account',
            icon: 'fas fa-user',
            color: 'info',
            badgeColor: 'badge-info',
            label: 'Cuenta y Perfil',
            description: 'Gestión de tu perfil y cuenta'
        },
        'SECURITY_PRIVACY': {
            id: 'cat-security',
            icon: 'fas fa-shield-alt',
            color: 'danger',
            badgeColor: 'badge-danger',
            label: 'Seguridad y Privacidad',
            description: 'Protege tu información y datos'
        },
        'BILLING_PAYMENTS': {
            id: 'cat-billing',
            icon: 'fas fa-credit-card',
            color: 'warning',
            badgeColor: 'badge-warning',
            label: 'Facturación y Pagos',
            description: 'Gestión de facturas y pagos'
        },
        'TECHNICAL_SUPPORT': {
            id: 'cat-support',
            icon: 'fas fa-tools',
            color: 'success',
            badgeColor: 'badge-success',
            label: 'Soporte Técnico',
            description: 'Soluciona problemas técnicos'
        }
    };

    // ========== INITIALIZATION ==========
    function init() {
        console.log('[Help Center] Initializing');

        // Get token
        token = window.tokenManager ? window.tokenManager.getAccessToken() : localStorage.getItem('access_token');
        if (!token) {
            console.error('[Help Center] No token found');
            toastr.error('No se encontró sesión activa');
            return;
        }

        // Render categories
        renderCategories();

        // Check if user follows companies
        checkIfUserFollowsCompanies();

        // Attach event listeners
        attachEventListeners();
    }

    // ========== RENDER CATEGORIES ==========
    function renderCategories() {
        console.log('[Help Center] Rendering categories');
        const container = $('#categories-container');

        Object.entries(categoryConfig).forEach(function([code, cat]) {
            const categoryHtml = `
                <div class="card card-outline card-${cat.color} category-card cursor-pointer"
                     data-category-code="${code}"
                     style="transition: all 0.3s ease; cursor: pointer; min-width: 250px; flex-shrink: 0;">
                    <div class="card-body text-center py-4 px-3">
                        <i class="${cat.icon} fa-2x text-${cat.color} mb-2"></i>
                        <h6 class="card-title mb-1">${cat.label}</h6>
                        <p class="card-text text-muted small mb-0">${cat.description}</p>
                    </div>
                </div>
            `;
            container.append(categoryHtml);
        });

        // Attach click listeners to categories
        $('.category-card').on('click', function() {
            const categoryCode = $(this).data('category-code');
            console.log('[Help Center] Category clicked:', categoryCode);

            // Remove active state from all categories
            $('.category-card').removeClass('border-left-4');

            // Add active state to clicked category
            $(this).addClass('border-left-4');

            // Set current category and load articles
            currentCategory = categoryCode;
            currentPage = 1;
            currentSearch = '';
            $('#help-search-input').val('');

            loadArticles();
        });

        // Add hover effect
        $('.category-card').on('mouseenter', function() {
            $(this).css('box-shadow', '0 0 10px rgba(0, 0, 0, 0.15)');
        }).on('mouseleave', function() {
            $(this).css('box-shadow', '');
        });
    }

    // ========== CHECK IF USER FOLLOWS COMPANIES ==========
    function checkIfUserFollowsCompanies() {
        console.log('[Help Center] Checking if user follows companies');

        $.ajax({
            url: '/api/companies/followed',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            },
            success: function(response) {
                const companies = response.data || response.items || [];
                userFollowsCompanies = Array.isArray(companies) && companies.length > 0;

                console.log('[Help Center] User follows companies:', userFollowsCompanies);

                if (userFollowsCompanies) {
                    loadArticles();
                } else {
                    showNoFollowersState();
                }
            },
            error: function(xhr) {
                console.error('[Help Center] Error checking companies:', xhr);
                toastr.error('Error al cargar datos');
                userFollowsCompanies = false;
                showNoFollowersState();
            }
        });
    }

    // ========== SHOW NO FOLLOWERS STATE ==========
    function showNoFollowersState() {
        console.log('[Help Center] Showing no followers state');
        $('#loading-spinner').hide();
        $('#articles-container').hide();
        $('#empty-state').hide();

        const noFollowersHtml = `
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building mr-2"></i>
                                No sigues a ninguna empresa
                            </h3>
                        </div>
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted lead">
                                Para acceder al Centro de Ayuda, debes seguir al menos una empresa.
                            </p>
                            <p class="text-muted small mb-4">
                                Sigue a las empresas para recibir sus artículos de soporte, guías y preguntas frecuentes.
                            </p>

                            <div class="mt-4">
                                <a href="/app/user/companies" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus mr-1"></i> Seguir empresas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#no-followers-state').html(noFollowersHtml).fadeIn();
    }

    // ========== LOAD ARTICLES ==========
    function loadArticles() {
        console.log('[Help Center] Loading articles - Page:', currentPage, 'Category:', currentCategory, 'Search:', currentSearch);

        $('#loading-spinner').show();
        $('#articles-container').hide().empty();
        $('#empty-state').hide();
        $('#no-followers-state').hide();

        let url = '/api/help-center/articles?page=' + currentPage + '&per_page=12&status=published';
        if (currentCategory) {
            url += '&category=' + encodeURIComponent(currentCategory);
        }
        if (currentSearch) {
            url += '&search=' + encodeURIComponent(currentSearch);
        }

        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('[Help Center] Articles loaded:', response.data.length);
                $('#loading-spinner').hide();

                if (response.data && response.data.length > 0) {
                    renderArticles(response.data);
                    $('#articles-container').fadeIn();
                } else {
                    showEmptyState();
                }
            },
            error: function(xhr) {
                console.error('[Help Center] Error loading articles:', xhr);
                $('#loading-spinner').hide();
                toastr.error('Error al cargar artículos');
            }
        });
    }

    // ========== RENDER ARTICLES ==========
    function renderArticles(articles) {
        console.log('[Help Center] Rendering', articles.length, 'articles');
        const container = $('#articles-container');
        container.empty();

        // Create a grid row for articles
        const articlesHtml = `<div class="row" id="articles-grid"></div>`;
        container.append(articlesHtml);
        const articlesGrid = $('#articles-grid');

        articles.forEach(function(article) {
            const config = categoryConfig[article.category_id] || categoryConfig['ACCOUNT_PROFILE'];
            const publishDate = new Date(article.published_at).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            // Truncate excerpt to 150 chars
            const excerptText = article.excerpt
                ? article.excerpt.substring(0, 150) + (article.excerpt.length > 150 ? '...' : '')
                : article.content.substring(0, 150) + '...';

            // Get company logo with fallback
            const companyLogo = article.company_logo
                ? article.company_logo
                : '/vendor/adminlte/dist/img/AdminLTELogo.png';

            const cardHtml = `
                <div class="col-12 col-sm-6 col-md-4 mb-4">
                    <div class="card h-100 article-card" data-article-id="${article.id}">
                        <!-- Company Info Header -->
                        <div class="card-header bg-light border-bottom">
                            <div class="d-flex align-items-center">
                                <img src="${companyLogo}"
                                     alt="${article.company_name}"
                                     class="rounded-circle mr-2"
                                     style="width: 40px; height: 40px; object-fit: cover;"
                                     onerror="this.src='/vendor/adminlte/dist/img/AdminLTELogo.png'">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 small text-dark font-weight-bold">${article.company_name || 'Empresa'}</h6>
                                    <small class="text-muted">${article.company_code || ''}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Article Content -->
                        <div class="card-body d-flex flex-column">
                            <!-- Category Badge -->
                            <div class="mb-2">
                                <span class="badge ${config.badgeColor}">
                                    <i class="${config.icon} mr-1"></i>${config.label}
                                </span>
                            </div>

                            <!-- Article Title -->
                            <h5 class="card-title text-dark mb-3">
                                ${article.title}
                            </h5>

                            <!-- Article Excerpt -->
                            <p class="card-text text-muted small flex-grow-1">
                                ${excerptText}
                            </p>
                        </div>

                        <!-- Article Footer -->
                        <div class="card-footer bg-white border-top">
                            <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                <span>
                                    <i class="fas fa-eye mr-1"></i>${article.views_count || 0} vistas
                                </span>
                                <span>${publishDate}</span>
                            </div>
                            <button class="btn btn-sm btn-primary btn-block view-article-btn"
                                    data-article-id="${article.id}">
                                <i class="fas fa-arrow-right mr-1"></i>Ver detalles
                            </button>
                        </div>
                    </div>
                </div>
            `;

            articlesGrid.append(cardHtml);
        });

        // Attach event listeners to "Ver detalles" buttons
        $('.view-article-btn').on('click', function() {
            const articleId = $(this).data('article-id');
            console.log('[Help Center] Viewing article:', articleId);
            loadAndShowArticleModal(articleId);
        });

        // Show CTA section
        $('#cta-section').fadeIn();
    }

    // ========== SHOW EMPTY STATE ==========
    function showEmptyState() {
        console.log('[Help Center] Showing empty state');
        $('#loading-spinner').hide();
        $('#articles-container').hide();
        $('#no-followers-state').hide();
        $('#cta-section').fadeIn();

        const message = currentSearch
            ? 'No hay artículos que coincidan con tu búsqueda. Intenta con otro término.'
            : 'No hay artículos disponibles en esta categoría.';

        const emptyHtml = `
            <div class="callout callout-info">
                <h5><i class="fas fa-inbox mr-2"></i>No se encontraron artículos</h5>
                <p>${message}</p>
            </div>
        `;
        $('#empty-state').html(emptyHtml).fadeIn();
    }

    // ========== LOAD AND SHOW ARTICLE MODAL ==========
    function loadAndShowArticleModal(articleId) {
        console.log('[Help Center] Loading article modal for ID:', articleId);

        $.ajax({
            url: '/api/help-center/articles/' + articleId,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('[Help Center] Article loaded successfully');

                if (response.data) {
                    const article = response.data;
                    const config = categoryConfig[article.category_id] || categoryConfig['ACCOUNT_PROFILE'];
                    const publishDate = new Date(article.published_at).toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    // Populate modal fields
                    $('#modal-company-logo').attr('src', article.company_logo || '/vendor/adminlte/dist/img/AdminLTELogo.png');
                    $('#modal-company-name').text(article.company_name || 'Empresa');
                    $('#modal-company-code').text(article.company_code || '');

                    // Category badge
                    const badgeHtml = `<i class="${config.icon} mr-1"></i>${config.label}`;
                    $('#modal-category-badge').html(badgeHtml).addClass(`badge-${config.color}`);

                    // Article info
                    $('#modal-article-title').text(article.title);
                    $('#modal-views-count').text(article.views_count || 0);
                    $('#modal-author-name').text(article.author_name || 'Anónimo');
                    $('#modal-publish-date').text(publishDate);

                    // Article content
                    $('#modal-article-content').html(`
                        <div class="article-body">
                            ${article.content}
                        </div>
                    `);

                    // Show modal
                    $('#articleModal').modal('show');
                }
            },
            error: function(xhr) {
                console.error('[Help Center] Error loading article:', xhr);
                toastr.error('Error al cargar el artículo');
            }
        });
    }

    // ========== ATTACH EVENT LISTENERS ==========
    function attachEventListeners() {
        console.log('[Help Center] Attaching event listeners');

        // Search button
        $('#help-search-btn').on('click', function() {
            currentSearch = $('#help-search-input').val();
            currentPage = 1;
            console.log('[Help Center] Search triggered:', currentSearch);
            loadArticles();
        });

        // Search input - Enter key
        $('#help-search-input').on('keypress', function(e) {
            if (e.which === 13) {
                currentSearch = $(this).val();
                currentPage = 1;
                console.log('[Help Center] Search via Enter:', currentSearch);
                loadArticles();
            }
        });
    }

    // ========== WAIT FOR JQUERY ==========
    if (typeof jQuery !== 'undefined') {
        console.log('[Help Center] jQuery available - Initializing');
        $(document).ready(init);
    } else {
        console.log('[Help Center] Waiting for jQuery...');
        const checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                console.log('[Help Center] jQuery detected');
                $(document).ready(init);
            }
        }, 100);

        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[Help Center] jQuery not loaded after 10 seconds');
            }
        }, 10000);
    }
})();
</script>
@endpush
