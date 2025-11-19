@extends('layouts.authenticated')

@section('title', 'Anuncios')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Anuncios Publicados</h1>
        <a href="{{ route('company.announcements.manage') }}" class="btn btn-outline-primary">
            <i class="fas fa-cogs mr-1"></i>
            Gestionar Anuncios
        </a>
    </div>
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
                <h3 id="stat-total">-</h3>
                <p>Total Publicados</p>
            </div>
            <div class="icon">
                <i class="fas fa-broadcast-tower"></i>
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
                <h3 id="stat-month">-</h3>
                <p>Este Mes</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-alt"></i>
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
    /* Status badge position in timeline - before time */
    .timeline-item > .badge {
        float: right;
        margin-top: 8px;
        margin-left: 10px;
    }

    /* Filter active state */
    .filter-type.active {
        background-color: #007bff;
        color: white;
    }

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

document.addEventListener('DOMContentLoaded', function() {
    const companyId = '{{ $companyId }}';
    const token = window.tokenManager?.getAccessToken();
    announcementsToken = token; // Make available globally
    let currentPage = 1;
    let currentType = '';
    let currentSearch = '';
    let dateColorIndex = 0; // For alternating date colors

    // Load initial data
    loadAnnouncements();
    loadStatistics();

    // Filter by type
    document.querySelectorAll('.filter-type').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.filter-type').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            currentType = this.dataset.type;
            currentPage = 1;
            loadAnnouncements();
        });
    });

    // Search
    document.getElementById('btn-search').addEventListener('click', function() {
        currentSearch = document.getElementById('search-input').value;
        currentPage = 1;
        loadAnnouncements();
    });

    document.getElementById('search-input').addEventListener('keypress', function(e) {
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
                        <h3 class="timeline-header">
                            <span class="badge ${typeConfig.badgeColor} mr-2">${typeConfig.label}</span>
                            ${announcement.title}
                        </h3>
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

        if (announcement.type === 'INCIDENT') {
            if (metadata.is_resolved) {
                return `<span class="badge badge-success"><i class="fas fa-check mr-1"></i> Resuelto</span>`;
            } else {
                return `<span class="badge badge-warning"><i class="fas fa-spinner fa-spin mr-1"></i> En Investigación</span>`;
            }
        }

        if (announcement.type === 'MAINTENANCE') {
            if (metadata.actual_end) {
                return `<span class="badge badge-success"><i class="fas fa-check mr-1"></i> Completado</span>`;
            } else if (metadata.actual_start) {
                return `<span class="badge badge-warning"><i class="fas fa-cog fa-spin mr-1"></i> En Progreso</span>`;
            } else {
                return `<span class="badge badge-info"><i class="fas fa-clock mr-1"></i> Programado</span>`;
            }
        }

        if (announcement.type === 'ALERT') {
            if (metadata.ended_at) {
                return `<span class="badge badge-success"><i class="fas fa-check mr-1"></i> Finalizada</span>`;
            }
        }

        return '';
    }

    function renderMetadata(announcement) {
        const metadata = announcement.metadata || {};
        let html = '<div class="announcement-metadata mt-2">';

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

        // Affected services
        if (metadata.affected_services && metadata.affected_services.length > 0) {
            html += `<span class="mr-3">
                <i class="fas fa-server"></i> ${metadata.affected_services.join(', ')}
            </span>`;
        }

        // Scheduled dates for maintenance
        if (metadata.scheduled_start) {
            const start = new Date(metadata.scheduled_start);
            const end = metadata.scheduled_end ? new Date(metadata.scheduled_end) : null;
            html += `<span class="mr-3">
                <i class="fas fa-calendar"></i>
                ${start.toLocaleDateString('es-ES')} ${start.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'})}
                ${end ? ' - ' + end.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : ''}
            </span>`;
        }

        // Action required for alerts
        if (metadata.action_required) {
            html += `<span class="text-danger">
                <i class="fas fa-exclamation-circle"></i> Acción requerida
            </span>`;
        }

        html += '</div>';
        return html;
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
        buttons.push(`<a class="btn btn-danger btn-sm" href="#" onclick="archiveAnnouncement('${announcement.id}'); return false;"><i class="fas fa-archive"></i></a>`);

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
            link.addEventListener('click', function(e) {
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
    const resolution = prompt('Descripción de la resolución:');
    if (resolution === null) return;

    fetch(`/api/v1/announcements/incidents/${id}/resolve`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${announcementsToken}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            resolution_content: resolution || 'Incidente resuelto',
            resolved_at: new Date().toISOString()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Incidente resuelto exitosamente');
            if (loadAnnouncementsFn) loadAnnouncementsFn();
            if (loadStatisticsFn) loadStatisticsFn();
        } else {
            alert('Error: ' + (data.message || 'No se pudo resolver el incidente'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al resolver el incidente');
    });
}

function startMaintenance(id) {
    if (!confirm('¿Iniciar este mantenimiento ahora?')) return;

    fetch(`/api/announcements/maintenance/${id}/start`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${announcementsToken}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Mantenimiento iniciado');
            if (loadAnnouncementsFn) loadAnnouncementsFn();
        } else {
            alert('Error: ' + (data.message || 'No se pudo iniciar el mantenimiento'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al iniciar el mantenimiento');
    });
}

function completeMaintenance(id) {
    if (!confirm('¿Marcar este mantenimiento como completado?')) return;

    fetch(`/api/announcements/maintenance/${id}/complete`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${announcementsToken}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Mantenimiento completado');
            if (loadAnnouncementsFn) loadAnnouncementsFn();
            if (loadStatisticsFn) loadStatisticsFn();
        } else {
            alert('Error: ' + (data.message || 'No se pudo completar el mantenimiento'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al completar el mantenimiento');
    });
}

function endAlert(id) {
    if (!confirm('¿Finalizar esta alerta?')) return;

    fetch(`/api/announcements/${id}`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${announcementsToken}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            metadata: {
                ended_at: new Date().toISOString()
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Alerta finalizada');
            if (loadAnnouncementsFn) loadAnnouncementsFn();
        } else {
            alert('Error: ' + (data.message || 'No se pudo finalizar la alerta'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al finalizar la alerta');
    });
}

function archiveAnnouncement(id) {
    if (!confirm('¿Archivar este anuncio?')) return;

    fetch(`/api/announcements/${id}/archive`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${announcementsToken}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Anuncio archivado');
            if (loadAnnouncementsFn) loadAnnouncementsFn();
            if (loadStatisticsFn) loadStatisticsFn();
        } else {
            alert('Error: ' + (data.message || 'No se pudo archivar el anuncio'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al archivar el anuncio');
    });
}
</script>
@endsection
