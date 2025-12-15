@extends('layouts.authenticated')

@section('title', 'Anuncios')

@section('content_header')
    <h1>Anuncios Publicados</h1>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.company-admin') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Anuncios</li>
@endsection

@section('content')
    {{-- Statistics Row --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3 id="stat-total" class="text-info">-</h3>
                    <p>Total Publicados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-broadcast-tower text-info"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3 id="stat-incidents" class="text-danger">-</h3>
                    <p>Incidentes Activos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3 id="stat-maintenance" class="text-purple">-</h3>
                    <p>Mantenimientos Próximos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tools text-purple"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3 id="stat-month" class="text-success">-</h3>
                    <p>Este Mes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-alt text-success"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-stream mr-2"></i>
                Feed de Anuncios
            </h3>
            <div class="card-tools">
                {{-- Manage Button --}}
                <a href="{{ route('company.announcements.manage') }}" class="btn btn-primary btn-sm mr-2">
                    <i class="fas fa-cogs mr-1"></i>
                    Gestionar
                </a>
                {{-- Sort Order - COMENTADO PRÓXIMAMENTE --}}
                {{-- <div class="btn-group btn-group-sm mr-2">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-sort mr-1"></i> <span id="sort-label">Más recientes</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item sort-option active" href="#" data-sort="-published_at">
                            <i class="fas fa-arrow-down mr-1"></i> Más recientes
                        </a>
                        <a class="dropdown-item sort-option" href="#" data-sort="published_at">
                            <i class="fas fa-arrow-up mr-1"></i> Más antiguos
                        </a>
                    </div>
                </div> --}}
                {{-- Filters --}}
                <div class="btn-group btn-group-sm mr-2">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-filter mr-1"></i> Tipo
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item filter-type active" href="#" data-type="">Todos</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item filter-type" href="#" data-type="NEWS">
                            <i class="fas fa-newspaper text-info mr-1"></i> Noticias
                        </a>
                        <a class="dropdown-item filter-type" href="#" data-type="MAINTENANCE">
                            <i class="fas fa-tools text-purple mr-1"></i> Mantenimiento
                        </a>
                        <a class="dropdown-item filter-type" href="#" data-type="INCIDENT">
                            <i class="fas fa-exclamation-triangle text-danger mr-1"></i> Incidentes
                        </a>
                        <a class="dropdown-item filter-type" href="#" data-type="ALERT">
                            <i class="fas fa-bell text-warning mr-1"></i> Alertas
                        </a>
                    </div>
                </div>
                <div class="input-group input-group-sm" style="width: 200px; display: inline-flex;">
                    <input type="text" id="search-input" class="form-control" placeholder="Buscar...">
                    <div class="input-group-append">
                        <button class="btn btn-default" id="btn-search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body" style="background-color: #f4f6f9;">
            {{-- Timeline Container --}}
            <div id="announcements-timeline" class="timeline">
                {{-- Content loaded via JavaScript --}}
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">Cargando anuncios...</p>
                </div>
            </div>
        </div>

        <div class="card-footer clearfix">
            <div class="float-left">
                <span id="pagination-info" class="text-muted">-</span>
            </div>
            <ul class="pagination pagination-sm m-0 float-right" id="pagination-container">
                {{-- Pagination loaded via JavaScript --}}
            </ul>
        </div>
    </div>
@endsection

@section('css')
    <style>
        /* Small box icon improvements */
        .small-box .icon>i {
            font-size: 70px;
        }

        /* Status badge position in timeline - before time */
        .timeline-item>.badge {
            float: right;
            margin-top: 8px;
            margin-left: 10px;
        }

        /* Filter active state */
        .filter-type.active {
            background-color: #007bff;
            color: white;
        }

        /* Sort option active state - COMENTADO PRÓXIMAMENTE */
        /*
        .sort-option.active {
            background-color: #007bff;
            color: white;
        }
        */

        /* Purple color for maintenance */
        .bg-purple {
            background-color: #6f42c1 !important;
        }

        .badge-purple {
            background-color: #6f42c1;
            color: #fff;
        }

        .btn-purple {
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: #fff;
        }

        .btn-purple:hover {
            background-color: #5a32a3;
            border-color: #5a32a3;
            color: #fff;
        }

        .text-purple {
            color: #6f42c1 !important;
        }

        /* Metadata display */
        .announcement-metadata {
            font-size: .85rem;
            color: #6c757d;
            margin-top: 10px;
        }

        .announcement-metadata i {
            width: 16px;
        }
    </style>
@endsection

@section('js')
    <script>
        // Global variables for action handlers
        let announcementsToken = null;
        let loadAnnouncementsFn = null;
        let loadStatisticsFn = null;

        document.addEventListener('DOMContentLoaded', function () {
            const companyId = '{{ $companyId }}';
            const token = window.tokenManager?.getAccessToken();
            announcementsToken = token; // Make available globally
            
            // =========================================================================
            // UTILIDADES - Toast y Manejo de Errores de API
            // =========================================================================
            const Toast = {
                success: function(message) {
                    $(document).Toasts('create', {
                        class: 'bg-success',
                        title: '<i class="fas fa-check-circle mr-2"></i>Éxito',
                        body: message,
                        autohide: true,
                        delay: 4000
                    });
                },
                error: function(message) {
                    $(document).Toasts('create', {
                        class: 'bg-danger',
                        title: '<i class="fas fa-exclamation-circle mr-2"></i>Error',
                        body: message,
                        autohide: true,
                        delay: 6000
                    });
                },
                errorHtml: function(title, htmlContent) {
                    $(document).Toasts('create', {
                        class: 'bg-danger',
                        title: '<i class="fas fa-exclamation-triangle mr-2"></i>' + title,
                        body: htmlContent,
                        autohide: true,
                        delay: 8000
                    });
                },
                warning: function(message) {
                    $(document).Toasts('create', {
                        class: 'bg-warning',
                        title: '<i class="fas fa-exclamation-triangle mr-2"></i>Advertencia',
                        body: message,
                        autohide: true,
                        delay: 5000
                    });
                },
                info: function(message) {
                    $(document).Toasts('create', {
                        class: 'bg-info',
                        title: '<i class="fas fa-info-circle mr-2"></i>Información',
                        body: message,
                        autohide: true,
                        delay: 4000
                    });
                }
            };
            
            // Make Toast available globally
            window.AnnouncementToast = Toast;
            
            const Utils = {
                translateFieldName: function(field) {
                    const fieldMap = {
                        'title': 'Título',
                        'content': 'Contenido',
                        'body': 'Contenido',
                        'type': 'Tipo de anuncio',
                        'metadata': 'Metadatos',
                        'metadata.urgency': 'Urgencia',
                        'metadata.message': 'Mensaje',
                        'metadata.summary': 'Resumen',
                        'metadata.news_type': 'Tipo de noticia',
                        'metadata.target_audience': 'Audiencia objetivo',
                        'metadata.affected_services': 'Servicios afectados',
                        'urgency': 'Urgencia',
                        'scheduled_start': 'Inicio programado',
                        'scheduled_end': 'Fin programado',
                        'is_emergency': 'Es emergencia',
                        'resolution_content': 'Contenido de resolución',
                        'alert_type': 'Tipo de alerta',
                        'action_required': 'Acción requerida',
                        'action_description': 'Descripción de acción',
                        'started_at': 'Fecha de inicio',
                        'ended_at': 'Fecha de fin'
                    };
                    return fieldMap[field] || field;
                },
                
                translateErrorMessage: function(message) {
                    const patterns = [
                        { regex: /is required/i, replacement: 'es obligatorio' },
                        { regex: /must be at least (\d+) characters/i, replacement: 'debe tener al menos $1 caracteres' },
                        { regex: /may not be greater than (\d+) characters/i, replacement: 'no puede exceder los $1 caracteres' },
                        { regex: /must be a valid date/i, replacement: 'debe ser una fecha válida' },
                        { regex: /must be after/i, replacement: 'debe ser posterior a' },
                        { regex: /must be before/i, replacement: 'debe ser anterior a' },
                        { regex: /The given data was invalid/i, replacement: 'Los datos proporcionados no son válidos' },
                        { regex: /already exists/i, replacement: 'ya existe' },
                        { regex: /not found/i, replacement: 'no encontrado' },
                        { regex: /Unauthenticated/i, replacement: 'Sesión expirada. Por favor inicie sesión nuevamente.' },
                        { regex: /Insufficient permissions/i, replacement: 'Permisos insuficientes' }
                    ];
                    
                    let translated = message;
                    patterns.forEach(p => {
                        translated = translated.replace(p.regex, p.replacement);
                    });
                    
                    return translated;
                },
                
                handleApiError: function(response, defaultMessage = 'Error al procesar la solicitud') {
                    console.error('[API Error]', response);
                    
                    if (response.status === 401) {
                        Toast.error('Sesión expirada. Por favor inicie sesión nuevamente.');
                        return;
                    }
                    
                    if (response.status === 403) {
                        Toast.error('No tiene permisos para realizar esta acción.');
                        return;
                    }
                    
                    if (response.status === 404) {
                        Toast.error('El recurso solicitado no fue encontrado.');
                        return;
                    }
                    
                    // Error 422 - Validación con campos específicos
                    if (response.status === 422 && response.data?.errors) {
                        const errors = response.data.errors;
                        let errorHtml = '<ul class="mb-0 pl-3">';
                        
                        Object.entries(errors).forEach(([field, messages]) => {
                            const fieldName = this.translateFieldName(field);
                            messages.forEach(msg => {
                                const translatedMsg = this.translateErrorMessage(msg);
                                errorHtml += `<li><strong>${fieldName}:</strong> ${translatedMsg}</li>`;
                            });
                        });
                        
                        errorHtml += '</ul>';
                        Toast.errorHtml('Error de Validación', errorHtml);
                        return;
                    }
                    
                    if (response.data?.message) {
                        Toast.error(this.translateErrorMessage(response.data.message));
                        return;
                    }
                    
                    Toast.error(defaultMessage);
                }
            };
            
            // Make Utils available globally
            window.AnnouncementUtils = Utils;
            
            let currentPage = 1;
            let currentType = '';
            let currentSearch = '';
            // let currentSort = '-published_at'; // Default: más recientes - COMENTADO PRÓXIMAMENTE
            let dateColorIndex = 0; // For alternating date colors

            // Load initial data
            loadAnnouncements();
            loadStatistics();

            // Sort order - COMENTADO PRÓXIMAMENTE
            /*
            document.querySelectorAll('.sort-option').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.sort-option').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    currentSort = this.dataset.sort;
                    // Update label
                    document.getElementById('sort-label').textContent = this.textContent.trim();
                    currentPage = 1;
                    loadAnnouncements();
                });
            });
            */

            // Filter by type
            document.querySelectorAll('.filter-type').forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelectorAll('.filter-type').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    currentType = this.dataset.type;
                    currentPage = 1;
                    loadAnnouncements();
                });
            });

            // Search
            document.getElementById('btn-search').addEventListener('click', function () {
                currentSearch = document.getElementById('search-input').value;
                currentPage = 1;
                loadAnnouncements();
            });

            document.getElementById('search-input').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    currentSearch = this.value;
                    currentPage = 1;
                    loadAnnouncements();
                }
            });

            function loadAnnouncements() {
                const timeline = document.getElementById('announcements-timeline');
                timeline.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">Cargando anuncios...</p>
                </div>
            `;

                let url = `/api/announcements?status=published&per_page=10&page=${currentPage}`;
                if (currentType) url += `&type=${currentType}`;
                if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
                // sort parameter removed - PRÓXIMAMENTE

                fetch(url, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            renderTimeline(data.data);
                            renderPagination(data.meta);
                        } else {
                            timeline.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay anuncios publicados</p>
                        </div>
                    `;
                            document.getElementById('pagination-container').innerHTML = '';
                            document.getElementById('pagination-info').textContent = '0 anuncios';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        timeline.innerHTML = `
                    <div class="text-center py-5 text-danger">
                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                        <p>Error al cargar los anuncios</p>
                    </div>
                `;
                    });
            }

            function renderTimeline(announcements) {
                const timeline = document.getElementById('announcements-timeline');
                let html = '';
                let currentDate = '';
                let dateColorIndex = 0;
                const dateColors = ['bg-red', 'bg-green', 'bg-blue', 'bg-yellow'];

                announcements.forEach(announcement => {
                    const publishedDate = new Date(announcement.published_at);
                    const dateStr = publishedDate.toLocaleDateString('es-ES', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });

                    // Date separator with alternating colors (AdminLTE v3 time-label)
                    if (dateStr !== currentDate) {
                        currentDate = dateStr;
                        const colorClass = dateColors[dateColorIndex % dateColors.length];
                        dateColorIndex++;
                        html += `
                        <!-- timeline time label -->
                        <div class="time-label">
                            <span class="${colorClass}">${dateStr}</span>
                        </div>
                        <!-- /.timeline-label -->
                    `;
                    }

                    // Announcement item (AdminLTE v3 timeline item)
                    const typeConfig = getTypeConfig(announcement.type);
                    const timeStr = publishedDate.toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const statusBadge = getStatusBadge(announcement);

                    html += `
                    <!-- timeline item -->
                    <div>
                        <i class="${typeConfig.icon} ${typeConfig.bgColor}"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> ${timeStr}</span>
                            ${statusBadge}
                            <h3 class="timeline-header"><a href="#"><strong>${typeConfig.label}</strong></a> ${announcement.title}</h3>
                            <div class="timeline-body">
                                ${announcement.content}
                                ${renderMetadata(announcement)}
                            </div>
                            <div class="timeline-footer">
                                ${renderFooterButtons(announcement)}
                            </div>
                        </div>
                    </div>
                    <!-- END timeline item -->
                `;
                });

                // Timeline end marker (AdminLTE v3 style)
                html += `
                <div>
                    <i class="fas fa-clock bg-gray"></i>
                </div>
            `;

                timeline.innerHTML = html;
            }

            function getTypeConfig(type) {
                const configs = {
                    'NEWS': {
                        icon: 'fas fa-newspaper',
                        bgColor: 'bg-blue',
                        badgeColor: 'badge-info',
                        label: 'Noticia'
                    },
                    'MAINTENANCE': {
                        icon: 'fas fa-tools',
                        bgColor: 'bg-purple',
                        badgeColor: 'badge-purple',
                        label: 'Mantenimiento'
                    },
                    'INCIDENT': {
                        icon: 'fas fa-exclamation-triangle',
                        bgColor: 'bg-red',
                        badgeColor: 'badge-danger',
                        label: 'Incidente'
                    },
                    'ALERT': {
                        icon: 'fas fa-bell',
                        bgColor: 'bg-yellow',
                        badgeColor: 'badge-warning',
                        label: 'Alerta'
                    }
                };
                return configs[type] || configs['NEWS'];
            }

            function getStatusBadge(announcement) {
                const metadata = announcement.metadata || {};
                let badges = [];

                if (announcement.type === 'INCIDENT') {
                    if (metadata.is_resolved) {
                        badges.push(`<span class="badge badge-success"><i class="fas fa-check mr-1"></i> Resuelto</span>`);
                    } else {
                        badges.push(`<span class="badge badge-warning"><i class="fas fa-spinner fa-spin mr-1"></i> En Investigación</span>`);
                    }
                }

                if (announcement.type === 'MAINTENANCE') {
                    // Emergency badge
                    if (metadata.is_emergency) {
                        badges.push(`<span class="badge badge-danger"><i class="fas fa-exclamation-circle mr-1"></i> EMERGENCIA</span>`);
                    }

                    if (metadata.actual_end) {
                        badges.push(`<span class="badge badge-success"><i class="fas fa-check mr-1"></i> Completado</span>`);
                    } else if (metadata.actual_start) {
                        badges.push(`<span class="badge badge-warning"><i class="fas fa-cog fa-spin mr-1"></i> En Progreso</span>`);
                    } else {
                        badges.push(`<span class="badge badge-info"><i class="fas fa-clock mr-1"></i> Programado</span>`);
                    }
                }

                if (announcement.type === 'NEWS') {
                    // News type badge
                    const newsTypes = {
                        'feature_release': '<span class="badge badge-primary"><i class="fas fa-star mr-1"></i> Nuevo Feature</span>',
                        'policy_update': '<span class="badge badge-warning"><i class="fas fa-gavel mr-1"></i> Política</span>',
                        'general_update': '<span class="badge badge-info"><i class="fas fa-info-circle mr-1"></i> Actualización</span>'
                    };
                    if (metadata.news_type && newsTypes[metadata.news_type]) {
                        badges.push(newsTypes[metadata.news_type]);
                    }
                }

                if (announcement.type === 'ALERT') {
                    // Alert type badge
                    const alertTypes = {
                        'security': '<span class="badge badge-danger"><i class="fas fa-shield-alt mr-1"></i> Seguridad</span>',
                        'system': '<span class="badge badge-warning"><i class="fas fa-server mr-1"></i> Sistema</span>',
                        'service': '<span class="badge badge-info"><i class="fas fa-broadcast-tower mr-1"></i> Servicio</span>',
                        'compliance': '<span class="badge badge-secondary"><i class="fas fa-balance-scale mr-1"></i> Cumplimiento</span>'
                    };
                    if (metadata.alert_type && alertTypes[metadata.alert_type]) {
                        badges.push(alertTypes[metadata.alert_type]);
                    }

                    if (metadata.ended_at) {
                        badges.push(`<span class="badge badge-success"><i class="fas fa-check mr-1"></i> Finalizada</span>`);
                    } else {
                        badges.push(`<span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Activa</span>`);
                    }
                }

                return badges.join(' ');
            }

            function renderMetadata(announcement) {
                const metadata = announcement.metadata || {};
                let html = '<div class="announcement-metadata mt-2">';

                // === MAINTENANCE specific ===
                if (announcement.type === 'MAINTENANCE') {
                    // Urgency
                    if (metadata.urgency) {
                        const urgencyColors = {
                            'LOW': 'text-success',
                            'MEDIUM': 'text-info',
                            'HIGH': 'text-warning'
                        };
                        html += `<span class="${urgencyColors[metadata.urgency] || ''} mr-3">
                        <i class="fas fa-bolt"></i> ${metadata.urgency}
                    </span>`;
                    }

                    // Scheduled date/time window
                    if (metadata.scheduled_start && metadata.scheduled_end) {
                        const plannedStart = new Date(metadata.scheduled_start);
                        const plannedEnd = new Date(metadata.scheduled_end);
                        const plannedDuration = Math.round((plannedEnd - plannedStart) / 1000 / 60); // minutes

                        const startDate = plannedStart.toLocaleDateString('es-ES', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        });
                        const startTime = plannedStart.toLocaleTimeString('es-ES', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        const endTime = plannedEnd.toLocaleTimeString('es-ES', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        html += `<div class="mt-2">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <strong>Programado:</strong> ${startDate}, ${startTime} - ${endTime} (${formatDuration(plannedDuration)})
                    </div>`;

                        // Show actual times if maintenance has started/completed
                        if (metadata.actual_start) {
                            const actualStart = new Date(metadata.actual_start);
                            const actualStartTime = actualStart.toLocaleTimeString('es-ES', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            html += `<div class="mt-1">
                            <i class="fas fa-play mr-1 text-warning"></i>
                            <strong>Inicio real:</strong> ${actualStartTime}`;

                            if (metadata.actual_end) {
                                const actualEnd = new Date(metadata.actual_end);
                                const actualEndTime = actualEnd.toLocaleTimeString('es-ES', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                const actualDuration = Math.round((actualEnd - actualStart) / 1000 / 60);
                                html += ` - ${actualEndTime} (${formatDuration(actualDuration)})`;
                            }

                            html += `</div>`;
                        }
                    }

                    // Affected services
                    if (metadata.affected_services && metadata.affected_services.length > 0) {
                        html += `<div class="mt-1">
                        <i class="fas fa-server mr-1"></i> <strong>Servicios afectados:</strong> ${metadata.affected_services.join(', ')}
                    </div>`;
                    }
                }

                // === INCIDENT specific ===
                if (announcement.type === 'INCIDENT') {
                    // Urgency
                    if (metadata.urgency) {
                        const urgencyColors = {
                            'LOW': 'text-success',
                            'MEDIUM': 'text-info',
                            'HIGH': 'text-warning',
                            'CRITICAL': 'text-danger'
                        };
                        html += `<span class="${urgencyColors[metadata.urgency] || ''} mr-3">
                        <i class="fas fa-bolt"></i> ${metadata.urgency}
                    </span>`;
                    }

                    // Incident duration
                    if (metadata.started_at) {
                        const start = new Date(metadata.started_at);
                        const end = metadata.ended_at ? new Date(metadata.ended_at) : new Date();
                        const duration = Math.round((end - start) / 1000 / 60); // minutes

                        html += `<span class="mr-3">
                        <i class="fas fa-hourglass-half"></i> Duración: ${formatDuration(duration)}
                    </span>`;
                    }

                    // Resolution details (collapsible)
                    if (metadata.is_resolved && metadata.resolution_content) {
                        html += `<div class="mt-2">
                        <a data-toggle="collapse" href="#resolution-${announcement.id}" class="text-success">
                            <i class="fas fa-check-circle mr-1"></i> Ver resolución
                        </a>
                        <div class="collapse mt-2" id="resolution-${announcement.id}">
                            <div class="alert alert-success mb-0">
                                <strong>Resolución:</strong><br>
                                ${metadata.resolution_content}
                            </div>
                        </div>
                    </div>`;
                    }

                    // Affected services
                    if (metadata.affected_services && metadata.affected_services.length > 0) {
                        html += `<span class="mr-3">
                        <i class="fas fa-server"></i> ${metadata.affected_services.join(', ')}
                    </span>`;
                    }
                }

                // === NEWS specific ===
                if (announcement.type === 'NEWS') {
                    // Target audience with icons
                    if (metadata.target_audience && metadata.target_audience.length > 0) {
                        const audienceIcons = {
                            'users': '<i class="fas fa-user text-primary"></i>',
                            'agents': '<i class="fas fa-headset text-info"></i>',
                            'admins': '<i class="fas fa-user-shield text-warning"></i>'
                        };

                        html += `<span class="mr-3">
                        <i class="fas fa-users mr-1"></i>
                        ${metadata.target_audience.map(aud => audienceIcons[aud] || aud).join(' ')}
                    </span>`;
                    }

                    // Summary (instead of showing full content in body)
                    if (metadata.summary) {
                        html += `<div class="mt-2">
                        <em class="text-muted">${metadata.summary}</em>
                    </div>`;
                    }
                }

                // === ALERT specific ===
                if (announcement.type === 'ALERT') {
                    // Urgency
                    if (metadata.urgency) {
                        const urgencyColors = {
                            'HIGH': 'text-warning',
                            'CRITICAL': 'text-danger'
                        };
                        html += `<span class="${urgencyColors[metadata.urgency] || ''} mr-3">
                        <i class="fas fa-bolt"></i> ${metadata.urgency}
                    </span>`;
                    }

                    // Message (highlighted)
                    if (metadata.message) {
                        html += `<div class="alert alert-warning mt-2 mb-2">
                        <strong><i class="fas fa-megaphone mr-1"></i> ${metadata.message}</strong>
                    </div>`;
                    }

                    // Action required (callout)
                    if (metadata.action_required && metadata.action_description) {
                        html += `<div class="alert alert-danger mt-2 mb-2">
                        <strong><i class="fas fa-exclamation-triangle mr-1"></i> Acción Requerida:</strong><br>
                        ${metadata.action_description}
                    </div>`;
                    }

                    // Active duration
                    if (metadata.started_at && !metadata.ended_at) {
                        const start = new Date(metadata.started_at);
                        const now = new Date();
                        const duration = Math.round((now - start) / 1000 / 60);

                        html += `<span class="text-danger">
                        <i class="fas fa-clock"></i> Activa desde hace ${formatDuration(duration)}
                    </span>`;
                    }

                    // Affected services
                    if (metadata.affected_services && metadata.affected_services.length > 0) {
                        html += `<span class="mr-3">
                        <i class="fas fa-server"></i> ${metadata.affected_services.join(', ')}
                    </span>`;
                    }
                }

                html += '</div>';
                return html;
            }

            // Helper function to format duration
            function formatDuration(minutes) {
                if (minutes < 60) {
                    return `${minutes}min`;
                } else if (minutes < 1440) {
                    const hours = Math.floor(minutes / 60);
                    const mins = minutes % 60;
                    return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
                } else {
                    const days = Math.floor(minutes / 1440);
                    const hours = Math.floor((minutes % 1440) / 60);
                    return hours > 0 ? `${days}d ${hours}h` : `${days}d`;
                }
            }

            function renderFooterButtons(announcement) {
                const metadata = announcement.metadata || {};
                let buttons = [];

                // Action buttons only (no status badges - those go in header)
                if (announcement.type === 'INCIDENT') {
                    if (!metadata.is_resolved) {
                        buttons.push(`<a class="btn btn-success btn-sm" href="#" onclick="resolveIncident('${announcement.id}'); return false;"><i class="fas fa-check-circle mr-1"></i> Resolver</a>`);
                    }
                }

                if (announcement.type === 'MAINTENANCE') {
                    if (metadata.actual_end) {
                        // Completed - no action needed
                    } else if (metadata.actual_start) {
                        buttons.push(`<a class="btn btn-success btn-sm" href="#" onclick="completeMaintenance('${announcement.id}'); return false;"><i class="fas fa-check-circle mr-1"></i> Completar</a>`);
                    } else {
                        buttons.push(`<a class="btn btn-sm bg-purple" href="#" onclick="startMaintenance('${announcement.id}'); return false;"><i class="fas fa-play mr-1"></i> Iniciar</a>`);
                    }
                }

                if (announcement.type === 'NEWS') {
                    if (metadata.call_to_action) {
                        buttons.push(`<a href="${metadata.call_to_action.url}" class="btn btn-primary btn-sm" target="_blank">${metadata.call_to_action.text}</a>`);
                    }
                }

                if (announcement.type === 'ALERT') {
                    if (!metadata.ended_at) {
                        buttons.push(`<a class="btn btn-warning btn-sm" href="#" onclick="endAlert('${announcement.id}'); return false;"><i class="fas fa-stop-circle mr-1"></i> Finalizar</a>`);
                    }
                }

                // Archive button for all published announcements
                buttons.push(`<a class="btn btn-secondary btn-sm" href="#" onclick="archiveAnnouncement('${announcement.id}'); return false;"><i class="fas fa-archive mr-1"></i> Archivar</a>`);

                return buttons.join('\n                            ');
            }

            // Make functions available globally
            loadAnnouncementsFn = loadAnnouncements;
            loadStatisticsFn = loadStatistics;

            function renderPagination(meta) {
                const container = document.getElementById('pagination-container');
                const info = document.getElementById('pagination-info');

                info.textContent = `Mostrando ${meta.from || 0}-${meta.to || 0} de ${meta.total} anuncios`;

                if (meta.last_page <= 1) {
                    container.innerHTML = '';
                    return;
                }

                let html = '';

                // Previous
                html += `<li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${meta.current_page - 1}">&laquo;</a>
            </li>`;

                // Pages
                for (let i = 1; i <= meta.last_page; i++) {
                    if (i === 1 || i === meta.last_page || (i >= meta.current_page - 1 && i <= meta.current_page + 1)) {
                        html += `<li class="page-item ${i === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
                    } else if (i === meta.current_page - 2 || i === meta.current_page + 2) {
                        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
                    }
                }

                // Next
                html += `<li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${meta.current_page + 1}">&raquo;</a>
            </li>`;

                container.innerHTML = html;

                // Add click handlers
                container.querySelectorAll('a[data-page]').forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const page = parseInt(this.dataset.page);
                        if (page >= 1 && page <= meta.last_page) {
                            currentPage = page;
                            loadAnnouncements();
                        }
                    });
                });
            }

            function loadStatistics() {
                // Load total published
                fetch(`/api/announcements?status=published&per_page=1`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('stat-total').textContent = data.meta?.total || 0;
                    });

                // Load active incidents (not resolved)
                fetch(`/api/announcements?status=published&type=INCIDENT&per_page=100`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        const activeIncidents = data.data?.filter(a => !a.metadata?.is_resolved) || [];
                        document.getElementById('stat-incidents').textContent = activeIncidents.length;
                    });

                // Load upcoming maintenance
                fetch(`/api/announcements?status=published&type=MAINTENANCE&per_page=100`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        const upcoming = data.data?.filter(a => {
                            if (!a.metadata?.scheduled_start) return false;
                            return new Date(a.metadata.scheduled_start) > new Date() && !a.metadata.actual_end;
                        }) || [];
                        document.getElementById('stat-maintenance').textContent = upcoming.length;
                    });

                // Load this month's count
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                fetch(`/api/announcements?status=published&published_after=${firstDay}&per_page=1`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('stat-month').textContent = data.meta?.total || 0;
                    });
            }

        });

        // Global action handlers (must be outside DOMContentLoaded for onclick)
        function resolveIncident(id) {
            Swal.fire({
                title: 'Resolver Incidente',
                input: 'textarea',
                inputLabel: 'Descripción de la resolución',
                inputPlaceholder: 'Describe cómo se resolvió el incidente...',
                inputAttributes: {
                    'aria-label': 'Descripción de la resolución'
                },
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check-circle"></i> Resolver',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debes proporcionar una descripción de la resolución'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const resolution = result.value;

                    // Format: YYYY-MM-DDTHH:MM:SS+00:00
                    const now = new Date();
                    const year = now.getUTCFullYear();
                    const month = String(now.getUTCMonth() + 1).padStart(2, '0');
                    const day = String(now.getUTCDate()).padStart(2, '0');
                    const hours = String(now.getUTCHours()).padStart(2, '0');
                    const minutes = String(now.getUTCMinutes()).padStart(2, '0');
                    const seconds = String(now.getUTCSeconds()).padStart(2, '0');
                    const resolvedAt = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}+00:00`;

                    fetch(`/api/announcements/incidents/${id}/resolve`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${announcementsToken}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            resolution_content: resolution,
                            resolved_at: resolvedAt
                        })
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(({ status, data }) => {
                        if (data.success) {
                            window.AnnouncementToast?.success('Incidente resuelto exitosamente');
                            if (loadAnnouncementsFn) loadAnnouncementsFn();
                            if (loadStatisticsFn) loadStatisticsFn();
                        } else {
                            window.AnnouncementUtils?.handleApiError({ status, data }, 'No se pudo resolver el incidente');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.AnnouncementToast?.error('Error de conexión al resolver el incidente');
                    });
                }
            });
        }

        function startMaintenance(id) {
            Swal.fire({
                title: '¿Iniciar Mantenimiento?',
                text: 'Se marcará la hora actual como inicio real del mantenimiento.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6f42c1',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-play"></i> Iniciar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/api/announcements/maintenance/${id}/start`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${announcementsToken}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(({ status, data }) => {
                        if (data.success) {
                            $(document).Toasts('create', {
                                class: 'bg-purple',
                                title: '<i class="fas fa-play mr-2"></i>Mantenimiento Iniciado',
                                body: 'El mantenimiento ha comenzado',
                                autohide: true,
                                delay: 4000
                            });
                            if (loadAnnouncementsFn) loadAnnouncementsFn();
                            if (loadStatisticsFn) loadStatisticsFn();
                        } else {
                            window.AnnouncementUtils?.handleApiError({ status, data }, 'No se pudo iniciar el mantenimiento');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.AnnouncementToast?.error('Error de conexión al iniciar el mantenimiento');
                    });
                }
            });
        }

        function completeMaintenance(id) {
            Swal.fire({
                title: '¿Completar Mantenimiento?',
                text: 'Se marcará la hora actual como fin del mantenimiento.',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check-circle"></i> Completar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/api/announcements/maintenance/${id}/complete`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${announcementsToken}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(({ status, data }) => {
                        if (data.success) {
                            window.AnnouncementToast?.success('El mantenimiento ha finalizado exitosamente');
                            if (loadAnnouncementsFn) loadAnnouncementsFn();
                            if (loadStatisticsFn) loadStatisticsFn();
                        } else {
                            window.AnnouncementUtils?.handleApiError({ status, data }, 'No se pudo completar el mantenimiento');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.AnnouncementToast?.error('Error de conexión al completar el mantenimiento');
                    });
                }
            });
        }

        function endAlert(id) {
            Swal.fire({
                title: '¿Finalizar Alerta?',
                text: 'La alerta se marcará como finalizada y dejará de mostrarse como activa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-stop-circle"></i> Finalizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Format: YYYY-MM-DDTHH:MM:SS+00:00
                    const now = new Date();
                    const year = now.getUTCFullYear();
                    const month = String(now.getUTCMonth() + 1).padStart(2, '0');
                    const day = String(now.getUTCDate()).padStart(2, '0');
                    const hours = String(now.getUTCHours()).padStart(2, '0');
                    const minutes = String(now.getUTCMinutes()).padStart(2, '0');
                    const seconds = String(now.getUTCSeconds()).padStart(2, '0');
                    const endedAt = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}+00:00`;

                    fetch(`/api/announcements/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${announcementsToken}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            metadata: {
                                ended_at: endedAt
                            }
                        })
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(({ status, data }) => {
                        if (data.success) {
                            window.AnnouncementToast?.success('La alerta ha sido marcada como finalizada');
                            if (loadAnnouncementsFn) loadAnnouncementsFn();
                            if (loadStatisticsFn) loadStatisticsFn();
                        } else {
                            window.AnnouncementUtils?.handleApiError({ status, data }, 'No se pudo finalizar la alerta');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.AnnouncementToast?.error('Error de conexión al finalizar la alerta');
                    });
                }
            });
        }

        function archiveAnnouncement(id) {
            Swal.fire({
                title: '¿Archivar Anuncio?',
                text: 'El anuncio será movido al archivo y dejará de mostrarse.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#007bff',
                confirmButtonText: '<i class="fas fa-archive"></i> Archivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/api/announcements/${id}/archive`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${announcementsToken}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(({ status, data }) => {
                        if (data.success) {
                            $(document).Toasts('create', {
                                class: 'bg-secondary',
                                title: '<i class="fas fa-archive mr-2"></i>Anuncio Archivado',
                                body: 'El anuncio ha sido archivado exitosamente',
                                autohide: true,
                                delay: 4000
                            });
                            if (loadAnnouncementsFn) loadAnnouncementsFn();
                            if (loadStatisticsFn) loadStatisticsFn();
                        } else {
                            window.AnnouncementUtils?.handleApiError({ status, data }, 'No se pudo archivar el anuncio');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.AnnouncementToast?.error('Error de conexión al archivar el anuncio');
                    });
                }
            });
        }
    </script>
@endsection