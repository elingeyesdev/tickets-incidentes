@extends('layouts.authenticated')

@section('title', 'Gestionar Anuncios')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestionar Anuncios</h1>
        <div>
            <a href="{{ route('company.announcements.index') }}" class="btn btn-outline-secondary mr-2">
                <i class="fas fa-stream mr-1"></i>
                Ver Feed
            </a>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modal-create">
                <i class="fas fa-plus mr-1"></i>
                Crear Anuncio
            </button>
        </div>
    </div>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.company-admin') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('company.announcements.index') }}">Anuncios</a></li>
    <li class="breadcrumb-item active">Gestionar</li>
@endsection

@section('content')
{{-- Statistics Row --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-light"><i class="fas fa-edit"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Borradores</span>
                <span class="info-box-number" id="stat-drafts">-</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-light"><i class="fas fa-calendar-alt text-info"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Programados</span>
                <span class="info-box-number" id="stat-scheduled">-</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-light"><i class="fas fa-check-circle text-success"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Publicados</span>
                <span class="info-box-number" id="stat-published">-</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-light"><i class="fas fa-archive text-secondary"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Archivados</span>
                <span class="info-box-number" id="stat-archived">-</span>
            </div>
        </div>
    </div>
</div>

{{-- Main Content with Tabs --}}
<div class="card card-primary card-outline card-outline-tabs">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" id="management-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-drafts-tab" data-toggle="tab" href="#tab-drafts" role="tab">
                    <i class="fas fa-edit mr-1"></i>
                    Borradores
                    <span class="badge badge-secondary ml-1" id="badge-drafts">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-scheduled-tab" data-toggle="tab" href="#tab-scheduled" role="tab">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Programados
                    <span class="badge badge-info ml-1" id="badge-scheduled">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-archived-tab" data-toggle="tab" href="#tab-archived" role="tab">
                    <i class="fas fa-archive mr-1"></i>
                    Archivados
                    <span class="badge badge-secondary ml-1" id="badge-archived">0</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="management-tabs-content">
            {{-- Drafts Tab --}}
            <div class="tab-pane fade show active" id="tab-drafts" role="tabpanel">
                <div id="drafts-list">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin text-muted"></i>
                    </div>
                </div>
            </div>

            {{-- Scheduled Tab --}}
            <div class="tab-pane fade" id="tab-scheduled" role="tabpanel">
                <div id="scheduled-list">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin text-muted"></i>
                    </div>
                </div>
            </div>

            {{-- Archived Tab --}}
            <div class="tab-pane fade" id="tab-archived" role="tabpanel">
                <div id="archived-list">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="modal-create" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus mr-2"></i>
                    Crear Nuevo Anuncio
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-create">
                    <div class="form-group">
                        <label for="create-type">Tipo de Anuncio <span class="text-danger">*</span></label>
                        <select class="form-control" id="create-type" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="NEWS">Noticia</option>
                            <option value="MAINTENANCE">Mantenimiento</option>
                            <option value="INCIDENT">Incidente</option>
                            <option value="ALERT">Alerta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="create-title">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create-title" required minlength="5" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label for="create-content">Contenido <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="create-content" rows="4" required minlength="10"></textarea>
                    </div>

                    {{-- Dynamic metadata fields will be inserted here --}}
                    <div id="metadata-fields"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-create-draft">
                    <i class="fas fa-save mr-1"></i>
                    Guardar como Borrador
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Schedule Modal --}}
<div class="modal fade" id="modal-schedule" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Programar Publicación
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="schedule-id">
                <div class="form-group">
                    <label for="schedule-datetime">Fecha y Hora de Publicación</label>
                    <input type="datetime-local" class="form-control" id="schedule-datetime" required>
                    <small class="text-muted">La publicación debe ser al menos 5 minutos en el futuro</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btn-confirm-schedule">
                    <i class="fas fa-calendar-check mr-1"></i>
                    Programar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .announcement-item {
        border: 1px solid #dee2e6;
        border-radius: .25rem;
        padding: 1rem;
        margin-bottom: .75rem;
        background: #fff;
    }

    .announcement-item:hover {
        background: #f8f9fa;
    }

    .announcement-item h6 {
        margin-bottom: .25rem;
        font-weight: 600;
    }

    .announcement-item .meta {
        font-size: .8rem;
        color: #6c757d;
    }

    .announcement-item .actions {
        white-space: nowrap;
    }

    .badge-type {
        font-size: .7rem;
    }

    .empty-state {
        padding: 3rem;
        text-align: center;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Info box adjustments */
    .info-box {
        min-height: auto;
        padding: .5rem;
    }

    .info-box .info-box-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
        line-height: 50px;
    }

    .info-box-number {
        font-size: 1.2rem;
    }
</style>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const companyId = '{{ $companyId }}';
    const token = window.tokenManager?.getAccessToken();

    // Load initial data
    loadDrafts();
    loadScheduled();
    loadArchived();
    loadStatistics();

    // Tab change handlers
    document.querySelectorAll('#management-tabs a').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('href');
            if (target === '#tab-drafts') loadDrafts();
            else if (target === '#tab-scheduled') loadScheduled();
            else if (target === '#tab-archived') loadArchived();
        });
    });

    // Create form - type change handler for dynamic fields
    document.getElementById('create-type').addEventListener('change', function() {
        updateMetadataFields(this.value);
    });

    // Create draft button
    document.getElementById('btn-create-draft').addEventListener('click', createDraft);

    // Schedule button
    document.getElementById('btn-confirm-schedule').addEventListener('click', confirmSchedule);

    function loadDrafts() {
        const container = document.getElementById('drafts-list');
        loadAnnouncements('draft', container, [
            { action: 'edit', icon: 'fa-edit', class: 'btn-outline-secondary', title: 'Editar' },
            { action: 'publish', icon: 'fa-paper-plane', class: 'btn-outline-primary', title: 'Publicar' },
            { action: 'schedule', icon: 'fa-calendar', class: 'btn-outline-info', title: 'Programar' },
            { action: 'delete', icon: 'fa-trash', class: 'btn-outline-danger', title: 'Eliminar' }
        ]);
    }

    function loadScheduled() {
        const container = document.getElementById('scheduled-list');
        loadAnnouncements('scheduled', container, [
            { action: 'edit', icon: 'fa-edit', class: 'btn-outline-secondary', title: 'Editar' },
            { action: 'unschedule', icon: 'fa-calendar-times', class: 'btn-outline-warning', title: 'Desprogramar' },
            { action: 'publish', icon: 'fa-paper-plane', class: 'btn-outline-primary', title: 'Publicar Ahora' }
        ]);
    }

    function loadArchived() {
        const container = document.getElementById('archived-list');
        loadAnnouncements('archived', container, [
            { action: 'view', icon: 'fa-eye', class: 'btn-outline-secondary', title: 'Ver' },
            { action: 'restore', icon: 'fa-undo', class: 'btn-outline-success', title: 'Restaurar' },
            { action: 'delete', icon: 'fa-trash', class: 'btn-outline-danger', title: 'Eliminar' }
        ]);
    }

    function loadAnnouncements(status, container, actions) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin text-muted"></i>
            </div>
        `;

        fetch(`/api/announcements?status=${status}&per_page=50`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                renderAnnouncementList(container, data.data, actions);
                updateBadge(status, data.data.length);
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No hay anuncios ${getStatusLabel(status)}</p>
                    </div>
                `;
                updateBadge(status, 0);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div class="empty-state text-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error al cargar los anuncios</p>
                </div>
            `;
        });
    }

    function renderAnnouncementList(container, announcements, actions) {
        let html = '';

        announcements.forEach(announcement => {
            const typeConfig = getTypeConfig(announcement.type);
            const dateStr = formatDate(announcement);

            html += `
                <div class="announcement-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6>
                            <span class="badge ${typeConfig.badgeColor} badge-type mr-1">${typeConfig.label}</span>
                            ${announcement.title}
                        </h6>
                        <div class="meta">
                            ${dateStr}
                            ${announcement.metadata?.urgency ? `<span class="mx-1">|</span> Urgencia: ${announcement.metadata.urgency}` : ''}
                        </div>
                    </div>
                    <div class="actions btn-group btn-group-sm">
                        ${renderActions(announcement.id, actions)}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Add action handlers
        container.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', function() {
                handleAction(this.dataset.action, this.dataset.id);
            });
        });
    }

    function renderActions(id, actions) {
        return actions.map(action => `
            <button class="btn ${action.class}" data-action="${action.action}" data-id="${id}" title="${action.title}">
                <i class="fas ${action.icon}"></i>
            </button>
        `).join('');
    }

    function handleAction(action, id) {
        switch(action) {
            case 'publish':
                if (confirm('¿Publicar este anuncio ahora?')) {
                    publishAnnouncement(id);
                }
                break;
            case 'schedule':
                document.getElementById('schedule-id').value = id;
                // Set minimum datetime to 5 minutes from now
                const min = new Date(Date.now() + 5 * 60000);
                document.getElementById('schedule-datetime').min = min.toISOString().slice(0, 16);
                $('#modal-schedule').modal('show');
                break;
            case 'unschedule':
                if (confirm('¿Desprogramar este anuncio?')) {
                    unscheduleAnnouncement(id);
                }
                break;
            case 'archive':
                if (confirm('¿Archivar este anuncio?')) {
                    archiveAnnouncement(id);
                }
                break;
            case 'restore':
                if (confirm('¿Restaurar este anuncio a borrador?')) {
                    restoreAnnouncement(id);
                }
                break;
            case 'delete':
                if (confirm('¿Eliminar este anuncio permanentemente?')) {
                    deleteAnnouncement(id);
                }
                break;
            case 'edit':
                // TODO: Implement edit modal
                alert('Funcionalidad de edición en desarrollo');
                break;
            case 'view':
                // TODO: Implement view modal
                alert('Funcionalidad de vista en desarrollo');
                break;
        }
    }

    function publishAnnouncement(id) {
        fetch(`/api/announcements/${id}/publish`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Anuncio publicado exitosamente');
                loadDrafts();
                loadScheduled();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al publicar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al publicar el anuncio');
        });
    }

    function confirmSchedule() {
        const id = document.getElementById('schedule-id').value;
        const datetime = document.getElementById('schedule-datetime').value;

        if (!datetime) {
            showToast('error', 'Seleccione una fecha y hora');
            return;
        }

        fetch(`/api/announcements/${id}/schedule`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                scheduled_for: new Date(datetime).toISOString()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modal-schedule').modal('hide');
                showToast('success', 'Anuncio programado exitosamente');
                loadDrafts();
                loadScheduled();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al programar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al programar el anuncio');
        });
    }

    function unscheduleAnnouncement(id) {
        fetch(`/api/announcements/${id}/unschedule`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Anuncio desprogramado');
                loadDrafts();
                loadScheduled();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al desprogramar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al desprogramar el anuncio');
        });
    }

    function restoreAnnouncement(id) {
        fetch(`/api/announcements/${id}/restore`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Anuncio restaurado a borrador');
                loadDrafts();
                loadArchived();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al restaurar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al restaurar el anuncio');
        });
    }

    function deleteAnnouncement(id) {
        fetch(`/api/announcements/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Anuncio eliminado');
                loadDrafts();
                loadArchived();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al eliminar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al eliminar el anuncio');
        });
    }

    function createDraft() {
        const type = document.getElementById('create-type').value;
        const title = document.getElementById('create-title').value;
        const content = document.getElementById('create-content').value;

        if (!type || !title || !content) {
            showToast('error', 'Complete todos los campos requeridos');
            return;
        }

        // Build metadata based on type
        const metadata = buildMetadata(type);

        // Determine endpoint based on type
        let endpoint = '/api/announcements/news';
        let body = { title, body: content, metadata, action: 'draft' };

        if (type === 'MAINTENANCE') {
            endpoint = '/api/announcements/maintenance';
            body = {
                title,
                content,
                urgency: metadata.urgency || 'MEDIUM',
                scheduled_start: metadata.scheduled_start,
                scheduled_end: metadata.scheduled_end,
                is_emergency: metadata.is_emergency || false,
                affected_services: metadata.affected_services || [],
                action: 'draft'
            };
        } else if (type === 'INCIDENT') {
            endpoint = '/api/v1/announcements/incidents';
            body = {
                title,
                content,
                urgency: metadata.urgency || 'MEDIUM',
                is_resolved: false,
                started_at: new Date().toISOString(),
                affected_services: metadata.affected_services || [],
                action: 'draft'
            };
        } else if (type === 'ALERT') {
            endpoint = '/api/announcements/alerts';
            body = {
                title,
                content,
                metadata: {
                    urgency: metadata.urgency || 'HIGH',
                    alert_type: metadata.alert_type || 'system',
                    message: metadata.message || title,
                    action_required: metadata.action_required || false,
                    started_at: new Date().toISOString()
                },
                action: 'draft'
            };
        }

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modal-create').modal('hide');
                document.getElementById('form-create').reset();
                document.getElementById('metadata-fields').innerHTML = '';
                showToast('success', 'Borrador creado exitosamente');
                loadDrafts();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al crear el anuncio');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al crear el anuncio');
        });
    }

    function updateMetadataFields(type) {
        const container = document.getElementById('metadata-fields');

        if (!type) {
            container.innerHTML = '';
            return;
        }

        let html = '';

        if (type === 'NEWS') {
            html = `
                <div class="form-group">
                    <label>Tipo de Noticia</label>
                    <select class="form-control" id="meta-news-type">
                        <option value="general_update">Actualización General</option>
                        <option value="feature_release">Nuevo Lanzamiento</option>
                        <option value="policy_update">Actualización de Políticas</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Resumen</label>
                    <input type="text" class="form-control" id="meta-summary" maxlength="200">
                </div>
            `;
        } else if (type === 'MAINTENANCE') {
            html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Inicio Programado <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="meta-scheduled-start" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fin Programado <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="meta-scheduled-end" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Urgencia</label>
                    <select class="form-control" id="meta-urgency">
                        <option value="LOW">Baja</option>
                        <option value="MEDIUM" selected>Media</option>
                        <option value="HIGH">Alta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Servicios Afectados</label>
                    <input type="text" class="form-control" id="meta-services" placeholder="Separados por coma">
                </div>
            `;
        } else if (type === 'INCIDENT') {
            html = `
                <div class="form-group">
                    <label>Urgencia</label>
                    <select class="form-control" id="meta-urgency">
                        <option value="LOW">Baja</option>
                        <option value="MEDIUM">Media</option>
                        <option value="HIGH" selected>Alta</option>
                        <option value="CRITICAL">Crítica</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Servicios Afectados</label>
                    <input type="text" class="form-control" id="meta-services" placeholder="Separados por coma">
                </div>
            `;
        } else if (type === 'ALERT') {
            html = `
                <div class="form-group">
                    <label>Urgencia</label>
                    <select class="form-control" id="meta-urgency">
                        <option value="HIGH" selected>Alta</option>
                        <option value="CRITICAL">Crítica</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de Alerta</label>
                    <select class="form-control" id="meta-alert-type">
                        <option value="security">Seguridad</option>
                        <option value="system">Sistema</option>
                        <option value="service">Servicio</option>
                        <option value="compliance">Cumplimiento</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mensaje Corto</label>
                    <input type="text" class="form-control" id="meta-message" maxlength="200">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="meta-action-required">
                    <label class="form-check-label" for="meta-action-required">Acción requerida por el usuario</label>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    function buildMetadata(type) {
        const metadata = {};

        if (type === 'NEWS') {
            metadata.news_type = document.getElementById('meta-news-type')?.value || 'general_update';
            metadata.target_audience = ['users', 'agents'];
            metadata.summary = document.getElementById('meta-summary')?.value || '';
        } else if (type === 'MAINTENANCE') {
            metadata.urgency = document.getElementById('meta-urgency')?.value || 'MEDIUM';
            metadata.scheduled_start = document.getElementById('meta-scheduled-start')?.value ?
                new Date(document.getElementById('meta-scheduled-start').value).toISOString() : null;
            metadata.scheduled_end = document.getElementById('meta-scheduled-end')?.value ?
                new Date(document.getElementById('meta-scheduled-end').value).toISOString() : null;
            metadata.is_emergency = false;
            metadata.affected_services = (document.getElementById('meta-services')?.value || '')
                .split(',').map(s => s.trim()).filter(s => s);
        } else if (type === 'INCIDENT') {
            metadata.urgency = document.getElementById('meta-urgency')?.value || 'HIGH';
            metadata.affected_services = (document.getElementById('meta-services')?.value || '')
                .split(',').map(s => s.trim()).filter(s => s);
        } else if (type === 'ALERT') {
            metadata.urgency = document.getElementById('meta-urgency')?.value || 'HIGH';
            metadata.alert_type = document.getElementById('meta-alert-type')?.value || 'system';
            metadata.message = document.getElementById('meta-message')?.value || '';
            metadata.action_required = document.getElementById('meta-action-required')?.checked || false;
        }

        return metadata;
    }

    function loadStatistics() {
        ['draft', 'scheduled', 'published', 'archived'].forEach(status => {
            fetch(`/api/announcements?status=${status}&per_page=1`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const total = data.meta?.total || 0;
                document.getElementById(`stat-${status === 'draft' ? 'drafts' : status}`).textContent = total;
            });
        });
    }

    function updateBadge(status, count) {
        const badgeId = status === 'draft' ? 'badge-drafts' : `badge-${status}`;
        const badge = document.getElementById(badgeId);
        if (badge) badge.textContent = count;
    }

    function getTypeConfig(type) {
        const configs = {
            'NEWS': { badgeColor: 'badge-info', label: 'Noticia' },
            'MAINTENANCE': { badgeColor: 'badge-warning', label: 'Mantenimiento' },
            'INCIDENT': { badgeColor: 'badge-danger', label: 'Incidente' },
            'ALERT': { badgeColor: 'badge-secondary', label: 'Alerta' }
        };
        return configs[type] || configs['NEWS'];
    }

    function getStatusLabel(status) {
        const labels = {
            'draft': 'en borrador',
            'scheduled': 'programados',
            'archived': 'archivados'
        };
        return labels[status] || status;
    }

    function formatDate(announcement) {
        if (announcement.status === 'SCHEDULED' && announcement.metadata?.scheduled_for) {
            const date = new Date(announcement.metadata.scheduled_for);
            return `<i class="fas fa-calendar-alt mr-1"></i> Publica: ${date.toLocaleDateString('es-ES')} ${date.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'})}`;
        }
        const date = new Date(announcement.updated_at);
        return `Modificado: ${date.toLocaleDateString('es-ES')}`;
    }

    function showToast(type, message) {
        // Simple toast using AdminLTE toastr if available
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

});
</script>
@endsection
