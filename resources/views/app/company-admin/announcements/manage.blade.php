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
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3 id="stat-drafts">-</h3>
                <p>Borradores</p>
            </div>
            <div class="icon">
                <i class="fas fa-edit"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="stat-scheduled">-</h3>
                <p>Programados</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="stat-published">-</h3>
                <p>Publicados</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-dark">
            <div class="inner">
                <h3 id="stat-archived">-</h3>
                <p>Archivados</p>
            </div>
            <div class="icon">
                <i class="fas fa-archive"></i>
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
                <a class="nav-link" id="tab-published-tab" data-toggle="tab" href="#tab-published" role="tab">
                    <i class="fas fa-check-circle mr-1"></i>
                    Publicados
                    <span class="badge badge-success ml-1" id="badge-published">0</span>
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

            {{-- Published Tab --}}
            <div class="tab-pane fade" id="tab-published" role="tabpanel">
                <div id="published-list">
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
                        <select class="form-control" id="create-type" name="type" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="NEWS">Noticia</option>
                            <option value="MAINTENANCE">Mantenimiento</option>
                            <option value="INCIDENT">Incidente</option>
                            <option value="ALERT">Alerta</option>
                        </select>
                        <small class="form-text text-muted">El tipo determina los campos adicionales requeridos</small>
                    </div>
                    <div class="form-group">
                        <label for="create-title">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create-title" name="title" required minlength="5" maxlength="255" placeholder="Título del anuncio...">
                        <small class="form-text text-muted">Entre 5 y 255 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label for="create-content">Contenido <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="create-content" name="content" rows="4" required minlength="10" placeholder="Describe el anuncio..."></textarea>
                        <small class="form-text text-muted">Mínimo 10 caracteres</small>
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

{{-- Edit Modal --}}
<div class="modal fade" id="modal-edit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Anuncio
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-edit">
                    <input type="hidden" id="edit-id">
                    <input type="hidden" id="edit-type">

                    <div class="form-group">
                        <label>Tipo de Anuncio</label>
                        <input type="text" class="form-control" id="edit-type-display" readonly>
                    </div>

                    <div class="form-group">
                        <label for="edit-title">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-title" required minlength="5" maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="edit-content">Contenido <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit-content" rows="4" required minlength="10"></textarea>
                    </div>

                    {{-- Dynamic metadata fields will be inserted here --}}
                    <div id="edit-metadata-fields"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-update">
                    <i class="fas fa-save mr-1"></i>
                    Actualizar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- View Modal --}}
<div class="modal fade" id="modal-view" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-eye mr-2"></i>
                    Detalle del Anuncio
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="view-content">
                    <dl class="row">
                        <dt class="col-sm-3">Tipo:</dt>
                        <dd class="col-sm-9"><span id="view-type"></span></dd>

                        <dt class="col-sm-3">Título:</dt>
                        <dd class="col-sm-9" id="view-title"></dd>

                        <dt class="col-sm-3">Estado:</dt>
                        <dd class="col-sm-9"><span id="view-status"></span></dd>

                        <dt class="col-sm-3">Autor:</dt>
                        <dd class="col-sm-9" id="view-author"></dd>

                        <dt class="col-sm-3">Fecha Creación:</dt>
                        <dd class="col-sm-9" id="view-created"></dd>
                    </dl>

                    <hr>

                    <h5>Contenido</h5>
                    <p id="view-content-text"></p>

                    <hr>

                    <h5>Metadatos</h5>
                    <div id="view-metadata"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
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
</style>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // =========================================================================
    // CONFIGURACIÓN
    // =========================================================================
    const CONFIG = {
        companyId: '{{ $companyId }}',
        apiBase: '/api/announcements'
    };
    
    const token = window.tokenManager?.getAccessToken();
    
    // =========================================================================
    // UTILIDADES - Traducción de Errores de API
    // =========================================================================
    const Utils = {
        /**
         * Traduce nombres de campo de API a español amigable
         */
        translateFieldName: function(field) {
            const fieldMap = {
                // Campos principales
                'title': 'Título',
                'content': 'Contenido',
                'body': 'Contenido',
                'type': 'Tipo de anuncio',
                
                // Metadata general
                'metadata': 'Metadatos',
                'metadata.urgency': 'Urgencia',
                'metadata.message': 'Mensaje',
                'metadata.summary': 'Resumen',
                'metadata.news_type': 'Tipo de noticia',
                'metadata.target_audience': 'Audiencia objetivo',
                'metadata.affected_services': 'Servicios afectados',
                
                // Maintenance
                'urgency': 'Urgencia',
                'scheduled_start': 'Inicio programado',
                'scheduled_end': 'Fin programado',
                'is_emergency': 'Es emergencia',
                'actual_start': 'Inicio real',
                'actual_end': 'Fin real',
                
                // Incident
                'is_resolved': 'Está resuelto',
                'started_at': 'Fecha de inicio',
                'ended_at': 'Fecha de fin',
                'resolution_content': 'Contenido de resolución',
                
                // Alert
                'alert_type': 'Tipo de alerta',
                'action_required': 'Acción requerida',
                'action_description': 'Descripción de acción',
                
                // News
                'news_type': 'Tipo de noticia',
                'target_audience': 'Audiencia objetivo',
                'summary': 'Resumen',
                'call_to_action': 'Llamada a la acción',
                
                // Scheduling
                'scheduled_for': 'Programado para',
                'action': 'Acción'
            };
            return fieldMap[field] || field;
        },
        
        /**
         * Traduce mensajes de error de Laravel a español
         */
        translateErrorMessage: function(message, field) {
            // Patrones comunes de Laravel
            const patterns = [
                { regex: /is required/i, replacement: 'es obligatorio' },
                { regex: /must be at least (\d+) characters/i, replacement: 'debe tener al menos $1 caracteres' },
                { regex: /may not be greater than (\d+) characters/i, replacement: 'no puede exceder los $1 caracteres' },
                { regex: /must be a valid email/i, replacement: 'debe ser un correo válido' },
                { regex: /must be (HIGH|MEDIUM|LOW|CRITICAL)/i, replacement: 'debe ser ALTA, MEDIA, BAJA o CRÍTICA' },
                { regex: /must be a valid date/i, replacement: 'debe ser una fecha válida' },
                { regex: /must be after/i, replacement: 'debe ser posterior a' },
                { regex: /must be before/i, replacement: 'debe ser anterior a' },
                { regex: /field is required/i, replacement: 'es obligatorio' },
                { regex: /The (.+) field is required/i, replacement: '$1 es obligatorio' },
                { regex: /The given data was invalid/i, replacement: 'Los datos proporcionados no son válidos' },
                { regex: /already exists/i, replacement: 'ya existe' },
                { regex: /must be one of/i, replacement: 'debe ser uno de' },
                { regex: /invalid format/i, replacement: 'tiene un formato inválido' },
                { regex: /cannot be empty/i, replacement: 'no puede estar vacío' },
                { regex: /Unauthenticated/i, replacement: 'No autenticado. Por favor inicie sesión nuevamente.' },
                { regex: /Insufficient permissions/i, replacement: 'Permisos insuficientes' },
                { regex: /not found/i, replacement: 'no encontrado' },
                { regex: /validation failed/i, replacement: 'la validación falló' }
            ];
            
            let translated = message;
            patterns.forEach(p => {
                translated = translated.replace(p.regex, p.replacement);
            });
            
            return translated;
        },
        
        /**
         * Procesa errores de API y muestra Toast con detalles
         */
        handleApiError: function(response, defaultMessage = 'Error al procesar la solicitud') {
            console.error('[API Error]', response);
            
            // Error 401 - No autenticado
            if (response.status === 401) {
                Toast.error('Sesión expirada. Por favor inicie sesión nuevamente.');
                return;
            }
            
            // Error 403 - Sin permisos
            if (response.status === 403) {
                Toast.error('No tiene permisos para realizar esta acción.');
                return;
            }
            
            // Error 404 - No encontrado
            if (response.status === 404) {
                Toast.error('El recurso solicitado no fue encontrado.');
                return;
            }
            
            // Error 422 - Validación
            if (response.status === 422 && response.data?.errors) {
                const errors = response.data.errors;
                let errorHtml = '<ul class="mb-0 pl-3">';
                
                Object.entries(errors).forEach(([field, messages]) => {
                    const fieldName = this.translateFieldName(field);
                    messages.forEach(msg => {
                        const translatedMsg = this.translateErrorMessage(msg, field);
                        errorHtml += `<li><strong>${fieldName}:</strong> ${translatedMsg}</li>`;
                    });
                });
                
                errorHtml += '</ul>';
                Toast.errorHtml('Error de Validación', errorHtml);
                return;
            }
            
            // Error genérico con mensaje
            if (response.data?.message) {
                Toast.error(this.translateErrorMessage(response.data.message));
                return;
            }
            
            // Error de red/servidor
            Toast.error(defaultMessage);
        },
        
        /**
         * Escape HTML para prevenir XSS
         */
        escapeHtml: function(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text || '').replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    // =========================================================================
    // TOASTS - AdminLTE Oficial
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
    
    // =========================================================================
    // VALIDACIÓN - jQuery Validation con patrón AdminLTE oficial
    // =========================================================================
    const $formCreate = $('#form-create');
    
    if (typeof $.fn.validate !== 'undefined') {
        console.log('[Announcements] ✓ jQuery Validation Plugin cargado');
        
        $formCreate.validate({
            rules: {
                type: { required: true },
                title: { required: true, minlength: 5, maxlength: 255 },
                content: { required: true, minlength: 10 }
            },
            messages: {
                type: { required: 'Debes seleccionar un tipo de anuncio' },
                title: {
                    required: 'El título es obligatorio',
                    minlength: 'El título debe tener al menos 5 caracteres',
                    maxlength: 'El título no puede exceder los 255 caracteres'
                },
                content: {
                    required: 'El contenido es obligatorio',
                    minlength: 'El contenido debe tener al menos 10 caracteres'
                }
            },
            errorElement: 'span',
            errorClass: 'invalid-feedback',
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
                $(element).closest('.form-group').find('.form-text').hide();
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).closest('.form-group').find('.form-text').show();
            }
        });
        
        // Re-validar select cuando cambia
        $('#create-type').on('change', function() {
            $formCreate.validate().element('#create-type');
        });
    } else {
        console.warn('[Announcements] ⚠ jQuery Validation Plugin NO cargado');
    }
    
    // =========================================================================
    // INICIALIZACIÓN
    // =========================================================================
    
    // Load initial data
    loadDrafts();
    loadScheduled();
    loadPublished();
    loadArchived();
    loadStatistics();

    // Tab change handlers
    document.querySelectorAll('#management-tabs a').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('href');
            if (target === '#tab-drafts') loadDrafts();
            else if (target === '#tab-scheduled') loadScheduled();
            else if (target === '#tab-published') loadPublished();
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

    // Update button
    document.getElementById('btn-update').addEventListener('click', updateAnnouncement);

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

    function loadPublished() {
        const container = document.getElementById('published-list');
        loadAnnouncements('published', container, [
            { action: 'view', icon: 'fa-eye', class: 'btn-outline-info', title: 'Ver' },
            { action: 'archive', icon: 'fa-archive', class: 'btn-outline-secondary', title: 'Archivar' }
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
                editAnnouncement(id);
                break;
            case 'view':
                viewAnnouncement(id);
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
                loadPublished();
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
                scheduled_for: toISO8601(new Date(datetime))
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

    function archiveAnnouncement(id) {
        fetch(`/api/announcements/${id}/archive`, {
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
                showToast('success', 'Anuncio archivado');
                loadPublished();
                loadArchived();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al archivar');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al archivar el anuncio');
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

    function editAnnouncement(id) {
        fetch(`/api/announcements/${id}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const announcement = data.data;

                // Load basic fields
                document.getElementById('edit-id').value = announcement.id;
                document.getElementById('edit-type').value = announcement.type;
                document.getElementById('edit-type-display').value = getTypeLabel(announcement.type);
                document.getElementById('edit-title').value = announcement.title;
                document.getElementById('edit-content').value = announcement.content;

                // Load metadata fields dynamically
                loadEditMetadata(announcement.type, announcement.metadata || {});

                // Show modal
                $('#modal-edit').modal('show');
            } else {
                showToast('error', 'Error al cargar el anuncio');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al cargar el anuncio');
        });
    }

    function loadEditMetadata(type, metadata) {
        const container = document.getElementById('edit-metadata-fields');
        let html = '';

        if (type === 'NEWS') {
            html = `
                <div class="form-group">
                    <label>Tipo de Noticia</label>
                    <select class="form-control" id="edit-meta-news-type">
                        <option value="general_update" ${metadata.news_type === 'general_update' ? 'selected' : ''}>Actualización General</option>
                        <option value="feature_release" ${metadata.news_type === 'feature_release' ? 'selected' : ''}>Nuevo Lanzamiento</option>
                        <option value="policy_update" ${metadata.news_type === 'policy_update' ? 'selected' : ''}>Actualización de Políticas</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Resumen</label>
                    <input type="text" class="form-control" id="edit-meta-summary" maxlength="200" value="${metadata.summary || ''}">
                </div>
            `;
        } else if (type === 'MAINTENANCE') {
            const scheduledStart = metadata.scheduled_start ? new Date(metadata.scheduled_start).toISOString().slice(0, 16) : '';
            const scheduledEnd = metadata.scheduled_end ? new Date(metadata.scheduled_end).toISOString().slice(0, 16) : '';

            html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Inicio Programado <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit-meta-scheduled-start" value="${scheduledStart}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fin Programado <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit-meta-scheduled-end" value="${scheduledEnd}" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Urgencia</label>
                    <select class="form-control" id="edit-meta-urgency">
                        <option value="LOW" ${metadata.urgency === 'LOW' ? 'selected' : ''}>Baja</option>
                        <option value="MEDIUM" ${metadata.urgency === 'MEDIUM' ? 'selected' : ''}>Media</option>
                        <option value="HIGH" ${metadata.urgency === 'HIGH' ? 'selected' : ''}>Alta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Servicios Afectados</label>
                    <input type="text" class="form-control" id="edit-meta-services" placeholder="Separados por coma" value="${metadata.affected_services ? metadata.affected_services.join(', ') : ''}">
                </div>
            `;
        } else if (type === 'INCIDENT') {
            html = `
                <div class="form-group">
                    <label>Urgencia</label>
                    <select class="form-control" id="edit-meta-urgency">
                        <option value="LOW" ${metadata.urgency === 'LOW' ? 'selected' : ''}>Baja</option>
                        <option value="MEDIUM" ${metadata.urgency === 'MEDIUM' ? 'selected' : ''}>Media</option>
                        <option value="HIGH" ${metadata.urgency === 'HIGH' ? 'selected' : ''}>Alta</option>
                        <option value="CRITICAL" ${metadata.urgency === 'CRITICAL' ? 'selected' : ''}>Crítica</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Servicios Afectados</label>
                    <input type="text" class="form-control" id="edit-meta-services" placeholder="Separados por coma" value="${metadata.affected_services ? metadata.affected_services.join(', ') : ''}">
                </div>
            `;
        } else if (type === 'ALERT') {
            html = `
                <div class="form-group">
                    <label>Urgencia</label>
                    <select class="form-control" id="edit-meta-urgency">
                        <option value="HIGH" ${metadata.urgency === 'HIGH' ? 'selected' : ''}>Alta</option>
                        <option value="CRITICAL" ${metadata.urgency === 'CRITICAL' ? 'selected' : ''}>Crítica</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de Alerta</label>
                    <select class="form-control" id="edit-meta-alert-type">
                        <option value="security" ${metadata.alert_type === 'security' ? 'selected' : ''}>Seguridad</option>
                        <option value="system" ${metadata.alert_type === 'system' ? 'selected' : ''}>Sistema</option>
                        <option value="service" ${metadata.alert_type === 'service' ? 'selected' : ''}>Servicio</option>
                        <option value="compliance" ${metadata.alert_type === 'compliance' ? 'selected' : ''}>Cumplimiento</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mensaje Corto</label>
                    <input type="text" class="form-control" id="edit-meta-message" maxlength="200" value="${metadata.message || ''}">
                </div>
            `;
        }

        container.innerHTML = html;
    }

    function updateAnnouncement() {
        const id = document.getElementById('edit-id').value;
        const type = document.getElementById('edit-type').value;
        const title = document.getElementById('edit-title').value;
        const content = document.getElementById('edit-content').value;

        if (!title || !content) {
            showToast('error', 'Complete todos los campos requeridos');
            return;
        }

        // Build metadata from edit form
        const metadata = buildEditMetadata(type);

        fetch(`/api/announcements/${id}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title,
                content,
                metadata
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modal-edit').modal('hide');
                showToast('success', 'Anuncio actualizado exitosamente');
                loadDrafts();
                loadScheduled();
                loadPublished();
                loadArchived();
                loadStatistics();
            } else {
                showToast('error', data.message || 'Error al actualizar el anuncio');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al actualizar el anuncio');
        });
    }

    function buildEditMetadata(type) {
        const metadata = {};

        if (type === 'NEWS') {
            metadata.news_type = document.getElementById('edit-meta-news-type')?.value || 'general_update';
            metadata.target_audience = ['users', 'agents'];
            metadata.summary = document.getElementById('edit-meta-summary')?.value || '';
        } else if (type === 'MAINTENANCE') {
            metadata.urgency = document.getElementById('edit-meta-urgency')?.value || 'MEDIUM';
            metadata.scheduled_start = document.getElementById('edit-meta-scheduled-start')?.value ?
                toISO8601(new Date(document.getElementById('edit-meta-scheduled-start').value)) : null;
            metadata.scheduled_end = document.getElementById('edit-meta-scheduled-end')?.value ?
                toISO8601(new Date(document.getElementById('edit-meta-scheduled-end').value)) : null;
            metadata.affected_services = (document.getElementById('edit-meta-services')?.value || '')
                .split(',').map(s => s.trim()).filter(s => s);
        } else if (type === 'INCIDENT') {
            metadata.urgency = document.getElementById('edit-meta-urgency')?.value || 'HIGH';
            metadata.affected_services = (document.getElementById('edit-meta-services')?.value || '')
                .split(',').map(s => s.trim()).filter(s => s);
        } else if (type === 'ALERT') {
            metadata.urgency = document.getElementById('edit-meta-urgency')?.value || 'HIGH';
            metadata.alert_type = document.getElementById('edit-meta-alert-type')?.value || 'system';
            metadata.message = document.getElementById('edit-meta-message')?.value || '';
        }

        return metadata;
    }

    function viewAnnouncement(id) {
        fetch(`/api/announcements/${id}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const a = data.data;

                document.getElementById('view-type').innerHTML = getTypeBadge(a.type);
                document.getElementById('view-title').textContent = a.title;
                document.getElementById('view-status').innerHTML = getStatusBadge(a.status);
                document.getElementById('view-author').textContent = a.author_name;
                document.getElementById('view-created').textContent = formatDateTime(a.created_at);
                document.getElementById('view-content-text').textContent = a.content;

                // Render metadata
                renderViewMetadata(a.type, a.metadata || {});

                $('#modal-view').modal('show');
            } else {
                showToast('error', 'Error al cargar el anuncio');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error al cargar el anuncio');
        });
    }

    function renderViewMetadata(type, metadata) {
        const container = document.getElementById('view-metadata');
        let html = '<dl class="row">';

        if (type === 'MAINTENANCE') {
            html += `<dt class="col-sm-4">Urgencia:</dt><dd class="col-sm-8">${metadata.urgency || '-'}</dd>`;
            html += `<dt class="col-sm-4">Inicio Programado:</dt><dd class="col-sm-8">${metadata.scheduled_start ? formatDateTime(metadata.scheduled_start) : '-'}</dd>`;
            html += `<dt class="col-sm-4">Fin Programado:</dt><dd class="col-sm-8">${metadata.scheduled_end ? formatDateTime(metadata.scheduled_end) : '-'}</dd>`;
            html += `<dt class="col-sm-4">Emergencia:</dt><dd class="col-sm-8">${metadata.is_emergency ? 'Sí' : 'No'}</dd>`;
            if (metadata.affected_services?.length) {
                html += `<dt class="col-sm-4">Servicios Afectados:</dt><dd class="col-sm-8">${metadata.affected_services.join(', ')}</dd>`;
            }
        } else if (type === 'INCIDENT') {
            html += `<dt class="col-sm-4">Urgencia:</dt><dd class="col-sm-8">${metadata.urgency || '-'}</dd>`;
            html += `<dt class="col-sm-4">Estado:</dt><dd class="col-sm-8">${metadata.is_resolved ? 'Resuelto' : 'Activo'}</dd>`;
            html += `<dt class="col-sm-4">Fecha Inicio:</dt><dd class="col-sm-8">${metadata.started_at ? formatDateTime(metadata.started_at) : '-'}</dd>`;
            if (metadata.ended_at) {
                html += `<dt class="col-sm-4">Fecha Fin:</dt><dd class="col-sm-8">${formatDateTime(metadata.ended_at)}</dd>`;
            }
            if (metadata.affected_services?.length) {
                html += `<dt class="col-sm-4">Servicios Afectados:</dt><dd class="col-sm-8">${metadata.affected_services.join(', ')}</dd>`;
            }
            if (metadata.resolution_content) {
                html += `<dt class="col-sm-4">Resolución:</dt><dd class="col-sm-8">${metadata.resolution_content}</dd>`;
            }
        } else if (type === 'NEWS') {
            html += `<dt class="col-sm-4">Tipo:</dt><dd class="col-sm-8">${metadata.news_type || '-'}</dd>`;
            html += `<dt class="col-sm-4">Resumen:</dt><dd class="col-sm-8">${metadata.summary || '-'}</dd>`;
            if (metadata.target_audience?.length) {
                html += `<dt class="col-sm-4">Audiencia:</dt><dd class="col-sm-8">${metadata.target_audience.join(', ')}</dd>`;
            }
        } else if (type === 'ALERT') {
            html += `<dt class="col-sm-4">Urgencia:</dt><dd class="col-sm-8">${metadata.urgency || '-'}</dd>`;
            html += `<dt class="col-sm-4">Tipo:</dt><dd class="col-sm-8">${metadata.alert_type || '-'}</dd>`;
            html += `<dt class="col-sm-4">Mensaje:</dt><dd class="col-sm-8">${metadata.message || '-'}</dd>`;
            html += `<dt class="col-sm-4">Acción Requerida:</dt><dd class="col-sm-8">${metadata.action_required ? 'Sí' : 'No'}</dd>`;
            if (metadata.action_description) {
                html += `<dt class="col-sm-4">Descripción:</dt><dd class="col-sm-8">${metadata.action_description}</dd>`;
            }
            html += `<dt class="col-sm-4">Fecha Inicio:</dt><dd class="col-sm-8">${metadata.started_at ? formatDateTime(metadata.started_at) : '-'}</dd>`;
            if (metadata.ended_at) {
                html += `<dt class="col-sm-4">Fecha Fin:</dt><dd class="col-sm-8">${formatDateTime(metadata.ended_at)}</dd>`;
            }
        }

        html += '</dl>';
        container.innerHTML = html;
    }

    function createDraft() {
        // Validar formulario con jQuery Validation antes de enviar
        const $form = $('#form-create');
        
        if (typeof $.fn.validate !== 'undefined') {
            if (!$form.valid()) {
                console.log('[Announcement] Formulario inválido - validación frontend fallida');
                return;
            }
        }
        
        const type = document.getElementById('create-type').value;
        const title = document.getElementById('create-title').value.trim();
        const content = document.getElementById('create-content').value.trim();

        // Validación de respaldo (fallback)
        if (!type || !title || !content) {
            Toast.error('Por favor complete todos los campos requeridos');
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
            endpoint = '/api/announcements/incidents';
            body = {
                title,
                content,
                urgency: metadata.urgency || 'MEDIUM',
                is_resolved: false,
                started_at: toISO8601(new Date()),
                affected_services: metadata.affected_services || [],
                action: 'draft'
            };
        } else if (type === 'ALERT') {
            endpoint = '/api/announcements/alerts';
            const alertMetadata = {
                urgency: metadata.urgency || 'HIGH',
                alert_type: metadata.alert_type || 'system',
                message: metadata.message || title,
                action_required: metadata.action_required || false,
                started_at: toISO8601(new Date())
            };

            // Only add action_description if action_required is true
            if (alertMetadata.action_required && metadata.action_description) {
                alertMetadata.action_description = metadata.action_description;
            }

            body = {
                title,
                content,
                metadata: alertMetadata,
                action: 'draft'
            };
        }

        console.log('[Announcement] Creating:', { endpoint, type, body });

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        })
        .then(response => {
            console.log('[Announcement] Response status:', response.status);
            return response.json().then(data => ({
                status: response.status,
                data: data
            }));
        })
        .then(({status, data}) => {
            console.log('[Announcement] Response data:', data);

            if (data.success) {
                // Cerrar modal y limpiar formulario
                $('#modal-create').modal('hide');
                
                // Reset completo del formulario
                const $form = $('#form-create');
                $form[0].reset();
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').remove();
                $form.find('.form-text').show();
                document.getElementById('metadata-fields').innerHTML = '';
                
                Toast.success('Borrador creado exitosamente');
                loadDrafts();
                loadStatistics();
            } else {
                // Usar manejo robusto de errores
                Utils.handleApiError({ status, data }, 'Error al crear el anuncio');
            }
        })
        .catch(error => {
            console.error('[Announcement] Fetch error:', error);
            Toast.error('Error de conexión. Por favor verifique su red.');
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
                    <label>Tipo de Noticia <span class="text-danger">*</span></label>
                    <select class="form-control" id="meta-news-type" name="news_type">
                        <option value="general_update">Actualización General</option>
                        <option value="feature_release">Nuevo Lanzamiento</option>
                        <option value="policy_update">Actualización de Políticas</option>
                    </select>
                    <small class="form-text text-muted">Categoría de la noticia</small>
                </div>
                <div class="form-group">
                    <label>Resumen <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="meta-summary" name="summary" maxlength="200" placeholder="Resumen breve de la noticia...">
                    <small class="form-text text-muted">Máximo 200 caracteres</small>
                </div>
            `;
        } else if (type === 'MAINTENANCE') {
            html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Inicio Programado <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="meta-scheduled-start" name="scheduled_start" required>
                            <small class="form-text text-muted">Fecha/hora de inicio del mantenimiento</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fin Programado <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="meta-scheduled-end" name="scheduled_end" required>
                            <small class="form-text text-muted">Fecha/hora de fin del mantenimiento</small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Urgencia</label>
                    <select class="form-control" id="meta-urgency" name="urgency">
                        <option value="LOW">Baja</option>
                        <option value="MEDIUM" selected>Media</option>
                        <option value="HIGH">Alta</option>
                    </select>
                    <small class="form-text text-muted">Nivel de urgencia del mantenimiento</small>
                </div>
                <div class="form-group">
                    <label>Servicios Afectados</label>
                    <input type="text" class="form-control" id="meta-services" name="affected_services" placeholder="API, Dashboard, Email (separados por coma)">
                    <small class="form-text text-muted">Lista de servicios que serán afectados</small>
                </div>
            `;
        } else if (type === 'INCIDENT') {
            html = `
                <div class="form-group">
                    <label>Urgencia <span class="text-danger">*</span></label>
                    <select class="form-control" id="meta-urgency" name="urgency">
                        <option value="LOW">Baja</option>
                        <option value="MEDIUM">Media</option>
                        <option value="HIGH" selected>Alta</option>
                        <option value="CRITICAL">Crítica</option>
                    </select>
                    <small class="form-text text-muted">Nivel de impacto del incidente</small>
                </div>
                <div class="form-group">
                    <label>Servicios Afectados</label>
                    <input type="text" class="form-control" id="meta-services" name="affected_services" placeholder="API, Dashboard, Email (separados por coma)">
                    <small class="form-text text-muted">Lista de servicios afectados por el incidente</small>
                </div>
            `;
        } else if (type === 'ALERT') {
            html = `
                <div class="form-group">
                    <label>Urgencia <span class="text-danger">*</span></label>
                    <select class="form-control" id="meta-urgency" name="urgency">
                        <option value="HIGH" selected>Alta</option>
                        <option value="CRITICAL">Crítica</option>
                    </select>
                    <small class="form-text text-muted">Las alertas solo admiten urgencia Alta o Crítica</small>
                </div>
                <div class="form-group">
                    <label>Tipo de Alerta <span class="text-danger">*</span></label>
                    <select class="form-control" id="meta-alert-type" name="alert_type">
                        <option value="security">Seguridad</option>
                        <option value="system">Sistema</option>
                        <option value="service">Servicio</option>
                        <option value="compliance">Cumplimiento</option>
                    </select>
                    <small class="form-text text-muted">Categoría de la alerta</small>
                </div>
                <div class="form-group">
                    <label>Mensaje Corto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="meta-message" name="message" maxlength="200" placeholder="Mensaje de alerta conciso...">
                    <small class="form-text text-muted">Mensaje principal de la alerta (máx. 200 caracteres)</small>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="meta-action-required" name="action_required">
                    <label class="form-check-label" for="meta-action-required">Acción requerida por el usuario</label>
                </div>
                <div class="form-group" id="action-description-container" style="display: none;">
                    <label>Descripción de la Acción <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="meta-action-description" name="action_description" rows="2" placeholder="Describa qué debe hacer el usuario..."></textarea>
                    <small class="form-text text-muted">Instrucciones claras para el usuario</small>
                </div>
            `;

            // Add event listener after rendering
            setTimeout(() => {
                const checkbox = document.getElementById('meta-action-required');
                const container = document.getElementById('action-description-container');
                if (checkbox && container) {
                    checkbox.addEventListener('change', function() {
                        container.style.display = this.checked ? 'block' : 'none';
                        if (!this.checked) {
                            document.getElementById('meta-action-description').value = '';
                        }
                    });
                }
            }, 0);
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
                toISO8601(new Date(document.getElementById('meta-scheduled-start').value)) : null;
            metadata.scheduled_end = document.getElementById('meta-scheduled-end')?.value ?
                toISO8601(new Date(document.getElementById('meta-scheduled-end').value)) : null;
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

            // Only include action_description if action_required is true
            if (metadata.action_required) {
                metadata.action_description = document.getElementById('meta-action-description')?.value || '';
            }
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
            'published': 'publicados',
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
        // Usar Toasts oficiales de AdminLTE
        if (type === 'success') {
            Toast.success(message);
        } else if (type === 'error') {
            Toast.error(message);
        } else if (type === 'warning') {
            Toast.warning(message);
        } else {
            Toast.info(message);
        }
    }

    function formatDateTime(datetime) {
        if (!datetime) return '-';
        const d = new Date(datetime);
        return d.toLocaleDateString('es-ES') + ' ' + d.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Format date to ISO8601 with +00:00 (Laravel compatible)
    function toISO8601(date) {
        const d = date instanceof Date ? date : new Date(date);
        const year = d.getUTCFullYear();
        const month = String(d.getUTCMonth() + 1).padStart(2, '0');
        const day = String(d.getUTCDate()).padStart(2, '0');
        const hours = String(d.getUTCHours()).padStart(2, '0');
        const minutes = String(d.getUTCMinutes()).padStart(2, '0');
        const seconds = String(d.getUTCSeconds()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}+00:00`;
    }

    function getStatusBadge(status) {
        const badges = {
            'DRAFT': '<span class="badge badge-secondary">Borrador</span>',
            'SCHEDULED': '<span class="badge badge-info">Programado</span>',
            'PUBLISHED': '<span class="badge badge-success">Publicado</span>',
            'ARCHIVED': '<span class="badge badge-dark">Archivado</span>'
        };
        return badges[status] || '';
    }

    function getTypeLabel(type) {
        const labels = {
            'NEWS': 'Noticia',
            'MAINTENANCE': 'Mantenimiento',
            'INCIDENT': 'Incidente',
            'ALERT': 'Alerta'
        };
        return labels[type] || type;
    }

    function getTypeBadge(type) {
        const badges = {
            'NEWS': '<span class="badge badge-info">Noticia</span>',
            'MAINTENANCE': '<span class="badge badge-warning">Mantenimiento</span>',
            'INCIDENT': '<span class="badge badge-danger">Incidente</span>',
            'ALERT': '<span class="badge badge-secondary">Alerta</span>'
        };
        return badges[type] || '';
    }

});
</script>
@endsection
