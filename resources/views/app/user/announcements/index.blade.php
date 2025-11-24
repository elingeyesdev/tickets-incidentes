@extends('layouts.authenticated')

@section('title', 'Anuncios')
@section('content_header', 'Anuncios de Empresas que Sigo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body p-2">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="btn-group" role="group" aria-label="Filtros de tipo">
                                <button type="button" class="btn btn-default active filter-btn" data-type="">Todos</button>
                                <button type="button" class="btn btn-default filter-btn" data-type="NEWS">
                                    <i class="fas fa-newspaper text-info mr-1"></i> Noticias
                                </button>
                                <button type="button" class="btn btn-default filter-btn" data-type="MAINTENANCE">
                                    <i class="fas fa-tools text-purple mr-1"></i> Mantenimiento
                                </button>
                                <button type="button" class="btn btn-default filter-btn" data-type="INCIDENT">
                                    <i class="fas fa-exclamation-triangle text-danger mr-1"></i> Incidentes
                                </button>
                                <button type="button" class="btn btn-default filter-btn" data-type="ALERT">
                                    <i class="fas fa-bell text-warning mr-1"></i> Alertas
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Buscar anuncios...">
                                <div class="input-group-append">
                                    <button class="btn btn-default" type="button" id="search-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Container -->
            <div id="announcements-container">
                <!-- Loading State -->
                <div class="text-center py-5" id="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando anuncios...</p>
                </div>

                <!-- Timeline Content (Injected via JS) -->
                <div class="timeline" id="timeline-content" style="display: none;"></div>

                <!-- Empty State (Injected via JS) -->
                <div id="empty-state" style="display: none;"></div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4" id="pagination-container"></div>
        </div>
    </div>
</div>

<!-- Templates for Components (Hidden) -->
<div id="templates" style="display: none;">
    @include('components.anuncios.card-news')
    @include('components.anuncios.card-maintenance')
    @include('components.anuncios.card-incident')
    @include('components.anuncios.card-alert')
    @include('components.anuncios.no-followers')
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // State
        let currentPage = 1;
        let currentType = '';
        let currentSearch = '';
        const token = localStorage.getItem('jwt_token'); // Assuming token is stored here

        // Initial Load
        checkFollowedCompanies();

        // Event Listeners
        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            currentType = $(this).data('type');
            currentPage = 1;
            loadAnnouncements();
        });

        $('#search-btn').on('click', function() {
            currentSearch = $('#search-input').val();
            currentPage = 1;
            loadAnnouncements();
        });

        $('#search-input').on('keypress', function(e) {
            if (e.which === 13) {
                currentSearch = $(this).val();
                currentPage = 1;
                loadAnnouncements();
            }
        });

        // Functions
        function checkFollowedCompanies() {
            $.ajax({
                url: '/api/companies/followed',
                method: 'GET',
                headers: { 'Authorization': 'Bearer ' + token },
                success: function(response) {
                    if (response.data && response.data.length > 0) {
                        loadAnnouncements();
                    } else {
                        showNoFollowersState();
                    }
                },
                error: function(xhr) {
                    console.error('Error checking followed companies:', xhr);
                    // Fallback to trying to load announcements anyway, API will handle empty
                    loadAnnouncements();
                }
            });
        }

        function loadAnnouncements() {
            $('#loading-spinner').show();
            $('#timeline-content').hide().empty();
            $('#empty-state').hide();
            $('#pagination-container').empty();

            let url = `/api/announcements?page=${currentPage}&per_page=10&status=PUBLISHED`;
            if (currentType) url += `&type=${currentType}`;
            if (currentSearch) url += `&search=${currentSearch}`;

            $.ajax({
                url: url,
                method: 'GET',
                headers: { 'Authorization': 'Bearer ' + token },
                success: function(response) {
                    $('#loading-spinner').hide();
                    
                    if (response.data && response.data.length > 0) {
                        renderTimeline(response.data);
                        renderPagination(response.meta);
                        $('#timeline-content').fadeIn();
                    } else {
                        renderEmptyState();
                    }
                },
                error: function(xhr) {
                    $('#loading-spinner').hide();
                    toastr.error('Error al cargar los anuncios');
                    console.error(xhr);
                }
            });
        }

        function renderTimeline(announcements) {
            const timeline = $('#timeline-content');
            let lastDate = '';
            const dateColors = ['bg-red', 'bg-green', 'bg-blue', 'bg-yellow'];
            let colorIndex = 0;

            announcements.forEach(announcement => {
                const date = new Date(announcement.published_at).toLocaleDateString();
                
                if (date !== lastDate) {
                    const color = dateColors[colorIndex % dateColors.length];
                    timeline.append(`
                        <div class="time-label">
                            <span class="${color}">${date}</span>
                        </div>
                    `);
                    lastDate = date;
                    colorIndex++;
                }

                const itemHtml = getAnnouncementHtml(announcement);
                timeline.append(itemHtml);
            });

            timeline.append(`
                <div>
                    <i class="fas fa-clock bg-gray"></i>
                </div>
            `);
        }

        function getAnnouncementHtml(announcement) {
            let templateId = '';
            switch(announcement.type) {
                case 'NEWS': templateId = 'template-card-news'; break;
                case 'MAINTENANCE': templateId = 'template-card-maintenance'; break;
                case 'INCIDENT': templateId = 'template-card-incident'; break;
                case 'ALERT': templateId = 'template-card-alert'; break;
                default: templateId = 'template-card-news';
            }

            // Clone template
            let $template = $(`#${templateId}`).clone().removeAttr('id').show();
            
            // Populate common fields
            const time = new Date(announcement.published_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            $template.find('.announcement-time').text(time);
            $template.find('.announcement-title').text(announcement.title);
            $template.find('.announcement-content').html(announcement.content);
            $template.find('.company-name').text(announcement.company_name || 'Empresa');

            // Populate specific fields based on type
            const metadata = announcement.metadata || {};
            
            if (announcement.type === 'MAINTENANCE') {
                $template.find('.maintenance-urgency').text(metadata.urgency || 'N/A');
                // Add logic for badges colors based on urgency
                $template.find('.maintenance-scheduled').text(`${metadata.scheduled_start} - ${metadata.scheduled_end}`);
                $template.find('.maintenance-services').text(metadata.affected_services ? metadata.affected_services.join(', ') : 'N/A');
            } else if (announcement.type === 'INCIDENT') {
                $template.find('.incident-status').text(metadata.is_resolved ? 'Resuelto' : 'En Investigación');
                $template.find('.incident-duration').text(metadata.duration || 'N/A');
            } else if (announcement.type === 'NEWS') {
                $template.find('.news-summary').text(metadata.summary || '');
                if (metadata.call_to_action) {
                    $template.find('.news-cta').attr('href', metadata.call_to_action.url).text(metadata.call_to_action.text).show();
                } else {
                    $template.find('.news-cta').hide();
                }
            } else if (announcement.type === 'ALERT') {
                $template.find('.alert-type').text(metadata.alert_type || 'General');
                $template.find('.alert-message').text(metadata.message || '');
            }

            return $template;
        }

        function renderEmptyState() {
            $('#empty-state').html(`
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay anuncios publicados que coincidan con tus filtros.</p>
                </div>
            `).show();
        }

        function showNoFollowersState() {
            $('#loading-spinner').hide();
            // Clone no-followers template
            const $noFollowers = $('#template-no-followers').clone().removeAttr('id').show();
            $('#empty-state').html($noFollowers).show();
            
            // Load suggestions
            loadSuggestions();
        }

        function loadSuggestions() {
            $.ajax({
                url: '/api/companies/explore?sort_by=followers_count&sort_direction=desc&per_page=3',
                method: 'GET',
                headers: { 'Authorization': 'Bearer ' + token },
                success: function(response) {
                    const container = $('#suggested-companies-list');
                    container.empty();
                    
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(company => {
                            container.append(`
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div class="d-flex align-items-center">
                                        <img src="${company.logo_url || '/img/default-company.png'}" class="img-circle mr-2" style="width: 40px; height: 40px;">
                                        <div>
                                            <h6 class="mb-0 font-weight-bold">${company.name}</h6>
                                            <small class="text-muted">${company.followers_count} seguidores</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-primary btn-follow" data-id="${company.id}">
                                        <i class="fas fa-plus"></i> Seguir
                                    </button>
                                </div>
                            `);
                        });
                    }
                }
            });
        }

        function renderPagination(meta) {
            // Simple pagination implementation
            if (meta.last_page > 1) {
                let html = '<ul class="pagination">';
                // Prev
                html += `<li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" data-page="${meta.current_page - 1}">&laquo;</a>
                         </li>`;
                
                // Numbers
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
                $('#pagination-container').html(html);

                $('.page-link').on('click', function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    if (page && page !== currentPage) {
                        currentPage = page;
                        loadAnnouncements();
                    }
                });
            }
        }

        // Handle Follow Button Click (Delegated)
        $(document).on('click', '.btn-follow', function() {
            const btn = $(this);
            const companyId = btn.data('id');
            
            $.ajax({
                url: `/api/companies/${companyId}/follow`,
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token },
                success: function() {
                    toastr.success('Ahora sigues a esta empresa');
                    btn.replaceWith('<span class="badge badge-success">Siguiendo</span>');
                    // Optionally reload announcements after a delay
                    setTimeout(() => {
                        checkFollowedCompanies();
                    }, 1000);
                },
                error: function() {
                    toastr.error('Error al seguir la empresa');
                }
            });
        });
    });
</script>
@endpush
