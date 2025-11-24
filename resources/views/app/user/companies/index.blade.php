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
    <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
        <div class="card bg-light d-flex flex-fill">
            <div class="card-header text-muted border-bottom-0 company-industry">
                Industry Name
            </div>
            <div class="card-body pt-0">
                <div class="row">
                    <div class="col-7">
                        <h2 class="lead"><b class="company-name">Company Name</b></h2>
                        <p class="text-muted text-sm company-description"><b>About: </b> Description... </p>
                        <ul class="ml-4 mb-0 fa-ul text-muted">
                            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-map-marker-alt"></i></span> <span class="company-location">Location</span></li>
                            <li class="small mt-1"><span class="fa-li"><i class="fas fa-lg fa-users"></i></span> <span class="company-followers">0</span> seguidores</li>
                        </ul>
                    </div>
                    <div class="col-5 text-center">
                        <img src="" alt="logo" class="img-circle img-fluid company-logo" style="width: 100px; height: 100px; object-fit: cover;" onerror="this.onerror=null; this.src='/vendor/adminlte/dist/img/AdminLTELogo.png'">
                    </div>
                </div>
            </div>
            <div class="card-footer">
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
            explore: { page: 1, search: '', loaded: false },
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

        // Tab Switching
        $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('href'); // #following or #explore
            state.activeTab = target.replace('#', '');
            
            // Clear search when switching tabs (optional, but cleaner)
            $('#search-input').val(state[state.activeTab].search);

            if (state.activeTab === 'explore' && !state.explore.loaded) {
                loadExplore();
            } else if (state.activeTab === 'following' && !state.following.loaded) {
                loadFollowing();
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

            let url = `/api/companies/explore?page=${state.explore.page}&per_page=9&sort_by=followers_count&sort_direction=desc`;
            if (state.explore.search) {
                url += `&search=${state.explore.search}`;
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

        // Render Companies
        function renderCompanies(items, containerId, isFollowedList) {
            const container = $(containerId);
            
            items.forEach(item => {
                // Normalize data structure
                // Followed list returns: { company: { ... }, ... }
                // Explore list returns: { ... } (company details directly)
                let company = isFollowedList ? item.company : item;
                
                // If it's the followed list, we know we follow them.
                // If it's explore list, we check isFollowedByMe (camelCase from resource)
                let isFollowing = isFollowedList ? true : (company.isFollowedByMe || false);

                // Clone template
                const $card = $('#company-card-template').children().first().clone();
                
                // Populate data
                $card.find('.company-name').text(company.name);
                $card.find('.company-industry').text(company.industry ? company.industry.name : 'General');
                $card.find('.company-description').text(company.description || 'Sin descripción disponible.');
                $card.find('.company-location').text([company.city, company.country].filter(Boolean).join(', ') || 'Ubicación no disponible');
                $card.find('.company-followers').text(company.followersCount || 0); // camelCase from resource
                
                const logoUrl = company.logoUrl || '/vendor/adminlte/dist/img/AdminLTELogo.png';
                $card.find('.company-logo').attr('src', logoUrl);

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
