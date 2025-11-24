@extends('layouts.authenticated')

@section('title', 'Empresas')
@section('content_header', 'Directorio de Empresas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline card-outline-tabs">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="companies-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="following-tab" data-toggle="pill" href="#following" role="tab" aria-controls="following" aria-selected="true">Siguiendo</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="explore-tab" data-toggle="pill" href="#explore" role="tab" aria-controls="explore" aria-selected="false">Explorar</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <!-- Search Bar -->
                    <div class="row mb-4">
                        <div class="col-md-6 offset-md-3">
                            <div class="input-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Buscar empresas...">
                                <div class="input-group-append">
                                    <button class="btn btn-default" type="button" id="search-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters (visible only in Explore tab) -->
                    <div id="explore-filters" class="row mb-4" style="display: none;">
                        <div class="col-md-3 offset-md-1">
                            <label class="small text-muted mb-1">Filtrar por industria</label>
                            <select id="industry-filter" class="form-control">
                                <option value="">Todas las industrias</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted mb-1">Ordenar por</label>
                            <select id="sort-filter" class="form-control">
                                <option value="name|asc">Nombre (A-Z)</option>
                                <option value="name|desc">Nombre (Z-A)</option>
                                <option value="followers_count|desc" selected>Más seguidores</option>
                                <option value="followers_count|asc">Menos seguidores</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted mb-1">&nbsp;</label>
                            <button id="clear-filters-btn" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="tab-content" id="companies-tabs-content">
                        <!-- Following Tab -->
                        <div class="tab-pane fade show active" id="following" role="tabpanel" aria-labelledby="following-tab">
                            <div id="following-container" class="row d-flex align-items-stretch">
                                <!-- Content loaded via JS -->
                            </div>
                            <div id="following-loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                            </div>
                            <div id="following-empty" class="text-center py-5" style="display: none;">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No sigues a ninguna empresa aún.</p>
                                <button class="btn btn-primary btn-sm" onclick="$('#explore-tab').tab('show')">Explorar Empresas</button>
                            </div>
                            <div class="d-flex justify-content-center mt-4" id="following-pagination"></div>
                        </div>

                        <!-- Explore Tab -->
                        <div class="tab-pane fade" id="explore" role="tabpanel" aria-labelledby="explore-tab">
                            <div id="explore-container" class="row d-flex align-items-stretch">
                                <!-- Content loaded via JS -->
                            </div>
                            <div id="explore-loading" class="text-center py-5" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                            </div>
                            <div id="explore-empty" class="text-center py-5" style="display: none;">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No se encontraron empresas.</p>
                            </div>
                            <div class="d-flex justify-content-center mt-4" id="explore-pagination"></div>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
        </div>
    </div>
</div>

<!-- Template for Company Card -->
<div id="company-card-template" style="display: none;">
    <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column mb-4">
        <div class="card d-flex flex-fill company-card-item">
            <!-- Industry Badge Header -->
            <div class="card-header border-bottom-0 company-industry-header" style="background-color: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge badge-info company-industry-badge">
                        <i class="fas fa-industry mr-1"></i>
                        <span class="company-industry">Industry</span>
                    </span>
                    <span class="badge badge-secondary company-code-badge">
                        <span class="company-code">CODE</span>
                    </span>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <div class="row">
                    <!-- Company Info -->
                    <div class="col-8">
                        <h5 class="mb-2">
                            <b class="company-name">Company Name</b>
                        </h5>
                        <p class="text-muted small company-description mb-3">
                            Description...
                        </p>

                        <!-- Company Details -->
                        <ul class="list-unstyled mb-0">
                            <!-- Location -->
                            <li class="small mb-2">
                                <i class="fas fa-map-marker-alt text-primary fa-fw mr-1"></i>
                                <span class="company-location text-muted">Location</span>
                            </li>

                            <!-- Followers -->
                            <li class="small mb-2">
                                <i class="fas fa-users text-success fa-fw mr-1"></i>
                                <span class="company-followers">0</span>
                                <span class="text-muted">seguidores</span>
                            </li>

                            <!-- Status Badge -->
                            <li class="small mb-2 company-status-item">
                                <i class="fas fa-check-circle text-success fa-fw mr-1"></i>
                                <span class="badge badge-success badge-sm company-status">ACTIVE</span>
                            </li>

                            <!-- Following Since (only for followed tab) -->
                            <li class="small mb-0 company-following-since" style="display: none;">
                                <i class="fas fa-calendar-check text-info fa-fw mr-1"></i>
                                <span class="text-muted">Siguiendo desde</span>
                                <span class="following-date">01 Ene 2025</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Logo -->
                    <div class="col-4 text-center d-flex align-items-center justify-content-center">
                        <img src=""
                             alt="logo"
                             class="img-fluid rounded company-logo"
                             style="width: 90px; height: 90px; object-fit: contain; border: 2px solid #dee2e6;"
                             onerror="this.onerror=null; this.src='/vendor/adminlte/dist/img/AdminLTELogo.png'">
                    </div>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="card-footer bg-white border-top">
                <div class="text-right">
                    <button class="btn btn-sm btn-primary btn-action">
                        <i class="fas fa-user-plus"></i> Seguir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    $(document).ready(function() {
        // State
        const state = {
            following: { page: 1, search: '', loaded: false },
            explore: { page: 1, search: '', loaded: false, industryId: '', sortBy: 'followers_count', sortDirection: 'desc' },
            activeTab: 'following'
        };

        // Token
        const token = window.tokenManager ? window.tokenManager.getAccessToken() : localStorage.getItem('access_token');
        if (!token) {
            toastr.error('Sesión no válida. Recargue la página.');
            return;
        }

        // Initial Load
        loadFollowing();
        loadIndustries();

        // Tab Switching
        $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('href'); // #following or #explore
            state.activeTab = target.replace('#', '');

            // Clear search when switching tabs (optional, but cleaner)
            $('#search-input').val(state[state.activeTab].search);

            // Show/hide filters
            if (state.activeTab === 'explore') {
                $('#explore-filters').slideDown();
                if (!state.explore.loaded) {
                    loadExplore();
                }
            } else {
                $('#explore-filters').slideUp();
                if (!state.following.loaded) {
                    loadFollowing();
                }
            }
        });

        // Search
        $('#search-btn').on('click', function() {
            performSearch();
        });

        $('#search-input').on('keypress', function(e) {
            if (e.which === 13) performSearch();
        });

        function performSearch() {
            const query = $('#search-input').val();
            state[state.activeTab].search = query;
            state[state.activeTab].page = 1;

            if (state.activeTab === 'following') {
                loadFollowing();
            } else {
                loadExplore();
            }
        }

        // Filter Handlers
        $('#industry-filter').on('change', function() {
            state.explore.industryId = $(this).val();
            state.explore.page = 1;
            loadExplore();
        });

        $('#sort-filter').on('change', function() {
            const sortValue = $(this).val().split('|');
            state.explore.sortBy = sortValue[0];
            state.explore.sortDirection = sortValue[1];
            state.explore.page = 1;
            loadExplore();
        });

        $('#clear-filters-btn').on('click', function() {
            // Reset filters
            state.explore.search = '';
            state.explore.industryId = '';
            state.explore.sortBy = 'followers_count';
            state.explore.sortDirection = 'desc';
            state.explore.page = 1;

            // Reset UI
            $('#search-input').val('');
            $('#industry-filter').val('');
            $('#sort-filter').val('followers_count|desc');

            // Reload
            loadExplore();
        });

        // Load Following
        function loadFollowing() {
            $('#following-loading').show();
            $('#following-container').hide().empty();
            $('#following-empty').hide();
            $('#following-pagination').empty();

            // Note: The /api/companies/followed endpoint might not support search/pagination in the same way as explore
            // Based on user docs: GET /api/companies/followed supports page, per_page
            let url = `/api/companies/followed?page=${state.following.page}&per_page=9`;
            
            // If search is implemented in backend for followed, add it. If not, we might need client-side filtering or just ignore.
            // Assuming it might not support search yet based on docs provided.
            
            $.ajax({
                url: url,
                method: 'GET',
                headers: { 'Authorization': 'Bearer ' + token },
                success: function(response) {
                    $('#following-loading').hide();
                    state.following.loaded = true;

                    // The structure of response.data for followed might be different (wrapper object?)
                    // Docs say: { data: [ { id, company: {...}, ... } ] }
                    
                    if (response.data && response.data.length > 0) {
                        renderCompanies(response.data, '#following-container', true);
                        // Pagination logic if meta exists
                        if (response.meta) renderPagination(response.meta, '#following-pagination', 'following');
                        $('#following-container').fadeIn();
                    } else {
                        $('#following-empty').show();
                    }
                },
                error: function(xhr) {
                    $('#following-loading').hide();
                    console.error(xhr);
                    toastr.error('Error al cargar empresas seguidas');
                }
            });
        }

        // Load Explore
        function loadExplore() {
            $('#explore-loading').show();
            $('#explore-container').hide().empty();
            $('#explore-empty').hide();
            $('#explore-pagination').empty();

            let url = `/api/companies/explore?page=${state.explore.page}&per_page=9&sort_by=${state.explore.sortBy}&sort_direction=${state.explore.sortDirection}`;

            if (state.explore.search) {
                url += `&search=${encodeURIComponent(state.explore.search)}`;
            }

            if (state.explore.industryId) {
                url += `&industry_id=${state.explore.industryId}`;
            }

            $.ajax({
                url: url,
                method: 'GET',
                headers: { 'Authorization': 'Bearer ' + token },
                success: function(response) {
                    $('#explore-loading').hide();
                    state.explore.loaded = true;

                    if (response.data && response.data.length > 0) {
                        renderCompanies(response.data, '#explore-container', false);
                        if (response.meta) renderPagination(response.meta, '#explore-pagination', 'explore');
                        $('#explore-container').fadeIn();
                    } else {
                        $('#explore-empty').show();
                    }
                },
                error: function(xhr) {
                    $('#explore-loading').hide();
                    console.error(xhr);
                    toastr.error('Error al cargar directorio');
                }
            });
        }

        // Load Industries for Filter
        function loadIndustries() {
            $.ajax({
                url: '/api/industries',
                method: 'GET',
                headers: { 'Authorization': 'Bearer ' + token },
                success: function(response) {
                    const industries = response.data || response.items || [];
                    const $select = $('#industry-filter');

                    industries.forEach(function(industry) {
                        const option = $('<option></option>')
                            .val(industry.id)
                            .text(industry.name);
                        $select.append(option);
                    });
                },
                error: function(xhr) {
                    console.error('Error loading industries:', xhr);
                }
            });
        }

        // Render Companies
        function renderCompanies(items, containerId, isFollowedList) {
            const container = $(containerId);

            items.forEach(item => {
                // Normalize data structure
                // Followed list returns: { company: { ... }, followedAt: '...', ... }
                // Explore list returns: { ... } (company details directly)
                let company = isFollowedList ? item.company : item;
                let followedAt = isFollowedList ? item.followedAt : null;

                // If it's the followed list, we know we follow them.
                // If it's explore list, we check isFollowedByMe (camelCase from resource)
                let isFollowing = isFollowedList ? true : (company.isFollowedByMe || false);

                // Clone template
                const $card = $('#company-card-template').children().first().clone();

                // Populate Basic Data
                $card.find('.company-name').text(company.name || 'Sin nombre');
                $card.find('.company-description').text(company.description || 'Sin descripción disponible.');

                // Industry
                const industryName = company.industry ? company.industry.name : 'General';
                $card.find('.company-industry').text(industryName);

                // Company Code
                $card.find('.company-code').text(company.companyCode || 'N/A');

                // Location
                const location = [company.city, company.country].filter(Boolean).join(', ') || 'Ubicación no especificada';
                $card.find('.company-location').text(location);

                // Followers Count
                $card.find('.company-followers').text(company.followersCount || 0);

                // Status Badge
                const status = company.status || 'ACTIVE';
                const statusClass = status === 'ACTIVE' ? 'success' : 'secondary';
                const statusIcon = status === 'ACTIVE' ? 'check-circle' : 'info-circle';
                $card.find('.company-status').text(status).removeClass('badge-success badge-secondary').addClass('badge-' + statusClass);
                $card.find('.company-status-item i').removeClass('text-success text-secondary').addClass('text-' + statusClass);
                $card.find('.company-status-item i').removeClass('fa-check-circle fa-info-circle').addClass('fa-' + statusIcon);

                // Logo
                const logoUrl = company.logoUrl || '/vendor/adminlte/dist/img/AdminLTELogo.png';
                $card.find('.company-logo').attr('src', logoUrl);

                // Primary Color (customize card border if available)
                if (company.primaryColor) {
                    $card.find('.company-card-item').css('border-left', '4px solid ' + company.primaryColor);
                    $card.find('.company-industry-badge').css('background-color', company.primaryColor);
                }

                // Following Since (only for followed tab)
                if (isFollowedList && followedAt) {
                    const followedDate = new Date(followedAt);
                    const formattedDate = followedDate.toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    $card.find('.following-date').text(formattedDate);
                    $card.find('.company-following-since').show();
                }

                // Action Button
                const $btn = $card.find('.btn-action');
                $btn.attr('data-id', company.id);

                updateButtonState($btn, isFollowing);

                // Bind Click
                $btn.on('click', function() {
                    toggleFollow(company.id, $btn, isFollowing);
                });

                container.append($card);
            });
        }

        function updateButtonState($btn, isFollowing) {
            if (isFollowing) {
                $btn.removeClass('btn-primary').addClass('btn-danger');
                $btn.html('<i class="fas fa-user-minus"></i> Dejar de Seguir');
            } else {
                $btn.removeClass('btn-danger').addClass('btn-primary');
                $btn.html('<i class="fas fa-user-plus"></i> Seguir');
            }
        }

        // Toggle Follow
        function toggleFollow(companyId, $btn, currentStatus) {
            const endpoint = currentStatus ? 'unfollow' : 'follow';
            const method = currentStatus ? 'DELETE' : 'POST';
            
            // Disable button
            $btn.prop('disabled', true);

            $.ajax({
                url: `/api/companies/${companyId}/${endpoint}`,
                method: method,
                headers: { 'Authorization': 'Bearer ' + token },
                success: function(response) {
                    const newStatus = !currentStatus;
                    updateButtonState($btn, newStatus);
                    toastr.success(newStatus ? 'Ahora sigues a la empresa' : 'Has dejado de seguir a la empresa');
                    
                    // Invalidate other tab to force reload
                    if (state.activeTab === 'following') {
                        state.explore.loaded = false;
                        // If we unfollowed in "Following" tab, maybe remove the card?
                        if (!newStatus) {
                            $btn.closest('.col-12').fadeOut(function() { 
                                $(this).remove(); 
                                if ($('#following-container').children().length === 0) {
                                    $('#following-empty').show();
                                }
                            });
                        }
                    } else {
                        state.following.loaded = false;
                        // Update button state immediately
                        // We need to re-bind the click event with the new status
                        $btn.off('click').on('click', function() {
                            toggleFollow(companyId, $btn, newStatus);
                        });
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    let msg = 'Error al procesar la solicitud';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        }

        // Pagination
        function renderPagination(meta, containerSelector, type) {
            if (meta.last_page <= 1) return;

            let html = '<ul class="pagination">';
            
            // Prev
            html += `<li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${meta.current_page - 1}">&laquo;</a>
                     </li>`;
            
            // Numbers (simplified)
            for (let i = 1; i <= meta.last_page; i++) {
                html += `<li class="page-item ${meta.current_page === i ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                         </li>`;
            }

            // Next
            html += `<li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${meta.current_page + 1}">&raquo;</a>
                     </li>`;
            
            html += '</ul>';
            
            const $container = $(containerSelector);
            $container.html(html);

            $container.find('.page-link').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && page !== meta.current_page) {
                    state[type].page = page;
                    if (type === 'following') loadFollowing();
                    else loadExplore();
                }
            });
        }
    });
</script>
@endpush
