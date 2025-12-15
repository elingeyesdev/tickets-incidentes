{{--
    Activity Timeline Component (AdminLTE v3 + jQuery)
    
    Reusable timeline component for displaying user activity logs.
    Uses the official AdminLTE timeline structure with "Load More" pagination.
    Follows blade-components-jquery.mdc rules for jQuery availability.
    
    Usage:
    @include('components.activity-timeline', [
        'containerId' => 'profileActivityTimeline',
        'userId' => null, // null for current user, UUID for specific user
        'initialLimit' => 15,
        'showLoadMore' => true,
        'showFilters' => true,
        'autoInit' => false // Set to true for auto-init on DOM ready
    ])
    
    Then call: initActivityTimeline('profileActivityTimeline') when ready
--}}

@php
    $containerId = $containerId ?? 'activityTimeline';
    $userId = $userId ?? null;
    $initialLimit = $initialLimit ?? 15;
    $showLoadMore = $showLoadMore ?? true;
    $showFilters = $showFilters ?? true;
    $autoInit = $autoInit ?? false;
@endphp

<div id="{{ $containerId }}" class="activity-timeline-container" 
     data-user-id="{{ $userId }}" 
     data-per-page="{{ $initialLimit }}"
     data-auto-init="{{ $autoInit ? 'true' : 'false' }}">
    
    <!-- Date Filter -->
    @if($showFilters)
    <div class="timeline-filters mb-3">
        <div class="row align-items-end">
            <div class="col-md-4 col-sm-6 mb-2">
                <label class="form-label small text-muted mb-1">Período</label>
                <select class="form-control form-control-sm filter-period">
                    <option value="">Todo el historial</option>
                    <option value="today">Hoy</option>
                    <option value="week" selected>Última semana</option>
                    <option value="month">Último mes</option>
                    <option value="3months">Últimos 3 meses</option>
                    <option value="custom">Rango personalizado...</option>
                </select>
            </div>
            <div class="col-md-6 col-sm-6 mb-2 custom-date-range" style="display: none;">
                <div class="row">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Desde</label>
                        <input type="date" class="form-control form-control-sm filter-date-from">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Hasta</label>
                        <input type="date" class="form-control form-control-sm filter-date-to">
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-12 mb-2">
                <button type="button" class="btn btn-sm btn-outline-primary btn-block btn-apply-filter">
                    <i class="fas fa-filter mr-1"></i>Filtrar
                </button>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Loading State -->
    <div class="timeline-loading text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
        <p class="text-muted mt-2 mb-0">Cargando actividad...</p>
    </div>
    
    <!-- Empty State -->
    <div class="timeline-empty text-center py-5" style="display: none;">
        <i class="fas fa-history fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Sin actividad registrada</h5>
        <p class="text-muted small">No hay registros de actividad en este período.</p>
    </div>
    
    <!-- Error State -->
    <div class="timeline-error" style="display: none;">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span class="error-message">Error al cargar la actividad</span>
        </div>
    </div>
    
    <!-- Timeline Container with scroll - contains timeline + load more + end marker -->
    <div class="timeline-scroll-container" style="max-height: 650px; overflow-y: auto; display: none;">
        <!-- Timeline Content (AdminLTE v3 official structure) -->
        <div class="timeline timeline-content">
            <!-- Timeline items will be inserted here by jQuery -->
        </div>
        
        <!-- Load More Button (inside scroll container) -->
        @if($showLoadMore)
        <div class="timeline-load-more text-center py-3" style="display: none;">
            <button type="button" class="btn btn-outline-secondary btn-load-more">
                <i class="fas fa-chevron-down mr-2"></i>Cargar más actividad
            </button>
            <p class="text-muted small mt-2 mb-0 load-more-info">
                Mostrando <span class="shown-count">0</span> de <span class="total-count">0</span> registros
            </p>
        </div>
        @endif
        
        <!-- End of timeline marker (inside scroll container) -->
        <div class="timeline-end text-center py-3" style="display: none;">
            <span class="badge badge-secondary">
                <i class="fas fa-check mr-1"></i>Has visto toda la actividad
            </span>
        </div>
    </div>
</div>

<script>
/**
 * Activity Timeline Component (AdminLTE v3 + jQuery)
 * Following blade-components-jquery.mdc rules
 */
(function() {
    'use strict';
    
    console.log('[ActivityTimeline] Script loaded - waiting for jQuery...');
    
    // =========================================================================
    // TIMELINE STATE MANAGEMENT (per container)
    // =========================================================================
    var timelineInstances = {};
    
    function createTimelineInstance(containerId) {
        return {
            containerId: containerId,
            $container: null,
            userId: null,
            perPage: 15,
            currentPage: 1,
            totalItems: 0,
            hasMore: false,
            isLoading: false,
            dateFrom: null,
            dateTo: null
        };
    }
    
    // =========================================================================
    // MAIN INITIALIZATION FUNCTION
    // =========================================================================
    function initActivityTimeline(containerId) {
        console.log('[ActivityTimeline] initActivityTimeline called for:', containerId);
        
        var $container = $('#' + containerId);
        if ($container.length === 0) {
            console.error('[ActivityTimeline] Container not found:', containerId);
            return null;
        }
        
        // Check for token
        var token = localStorage.getItem('access_token');
        if (!token) {
            console.error('[ActivityTimeline] No access_token found in localStorage');
            showError($container, 'No se encontró token de autenticación. Por favor, inicie sesión nuevamente.');
            return null;
        }
        
        console.log('[ActivityTimeline] Token found, initializing timeline');
        
        // Create or get instance
        var instance = timelineInstances[containerId] || createTimelineInstance(containerId);
        instance.$container = $container;
        instance.userId = $container.data('user-id') || null;
        instance.perPage = parseInt($container.data('per-page')) || 15;
        
        // Set default date filter (last week)
        setDefaultDateFilter(instance);
        
        // Store instance
        timelineInstances[containerId] = instance;
        
        // Bind events
        bindEvents(instance);
        
        // Load initial data
        loadActivity(instance, false);
        
        return instance;
    }
    
    // =========================================================================
    // EVENT BINDING
    // =========================================================================
    function bindEvents(instance) {
        var $container = instance.$container;
        
        // Period filter change
        $container.find('.filter-period').off('change').on('change', function() {
            var value = $(this).val();
            if (value === 'custom') {
                $container.find('.custom-date-range').slideDown(200);
            } else {
                $container.find('.custom-date-range').slideUp(200);
            }
        });
        
        // Apply filter button
        $container.find('.btn-apply-filter').off('click').on('click', function() {
            applyDateFilter(instance);
        });
        
        // Load more button
        $container.find('.btn-load-more').off('click').on('click', function() {
            if (!instance.isLoading && instance.hasMore) {
                instance.currentPage++;
                loadActivity(instance, true);
            }
        });
    }
    
    // =========================================================================
    // DATE FILTER HELPERS
    // =========================================================================
    function setDefaultDateFilter(instance) {
        var now = new Date();
        var weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        
        instance.dateFrom = weekAgo.toISOString().split('T')[0];
        instance.dateTo = now.toISOString().split('T')[0];
    }
    
    function applyDateFilter(instance) {
        var $container = instance.$container;
        var period = $container.find('.filter-period').val();
        var now = new Date();
        var from = null;
        var to = now.toISOString().split('T')[0];
        
        switch (period) {
            case 'today':
                from = to;
                break;
            case 'week':
                from = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                break;
            case 'month':
                from = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                break;
            case '3months':
                from = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                break;
            case 'custom':
                from = $container.find('.filter-date-from').val() || null;
                to = $container.find('.filter-date-to').val() || to;
                break;
            default:
                // All history - no date filter
                from = null;
                to = null;
        }
        
        instance.dateFrom = from;
        instance.dateTo = to;
        instance.currentPage = 1;
        
        loadActivity(instance, false);
    }
    
    // =========================================================================
    // DATA LOADING (jQuery AJAX)
    // =========================================================================
    function loadActivity(instance, append) {
        if (instance.isLoading) return;
        instance.isLoading = true;
        
        var $container = instance.$container;
        var token = localStorage.getItem('access_token');
        
        if (!token) {
            showError($container, 'Sesión expirada. Por favor, inicie sesión nuevamente.');
            instance.isLoading = false;
            return;
        }
        
        // Show loading state
        if (!append) {
            $container.find('.timeline-loading').show();
            $container.find('.timeline-scroll-container').hide();
            $container.find('.timeline-empty').hide();
            $container.find('.timeline-error').hide();
            $container.find('.timeline-load-more').hide();
            $container.find('.timeline-end').hide();
        } else {
            // Show loading in button
            $container.find('.btn-load-more')
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin mr-2"></i>Cargando...');
        }
        
        // Build URL
        var baseUrl = instance.userId 
            ? '/api/activity-logs'
            : '/api/activity-logs/my';
        
        var params = {
            page: instance.currentPage,
            per_page: instance.perPage
        };
        
        if (instance.userId) {
            params.user_id = instance.userId;
        }
        if (instance.dateFrom) {
            params.from = instance.dateFrom;
        }
        if (instance.dateTo) {
            params.to = instance.dateTo;
        }
        
        console.log('[ActivityTimeline] Loading activity:', baseUrl, params);
        
        // jQuery AJAX call
        $.ajax({
            url: baseUrl,
            method: 'GET',
            data: params,
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('[ActivityTimeline] Data received:', response);
                
                var logs = response.data || [];
                var meta = response.meta || {};
                
                instance.totalItems = meta.total || 0;
                instance.hasMore = (meta.current_page || 1) < (meta.last_page || 1);
                
                if (!append) {
                    $container.find('.timeline-content').empty();
                }
                
                if (logs.length === 0 && !append) {
                    showEmpty($container);
                } else {
                    renderTimeline($container, logs, append);
                    updateLoadMore(instance);
                }
                
                instance.isLoading = false;
            },
            error: function(xhr, status, error) {
                console.error('[ActivityTimeline] AJAX Error:', status, error);
                
                var errorMsg = 'Error al cargar la actividad';
                if (xhr.status === 401) {
                    errorMsg = 'Sesión expirada. Por favor, inicie sesión nuevamente.';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                showError($container, errorMsg);
                instance.isLoading = false;
            }
        });
    }
    
    // =========================================================================
    // TIMELINE RENDERING (AdminLTE v3 structure)
    // =========================================================================
    function renderTimeline($container, logs, append) {
        var $timeline = $container.find('.timeline-content');
        var $scrollContainer = $container.find('.timeline-scroll-container');
        
        // Hide loading, show scroll container with timeline
        $container.find('.timeline-loading').hide();
        $container.find('.timeline-empty').hide();
        $container.find('.timeline-error').hide();
        $scrollContainer.show();
        
        // Group logs by date
        var grouped = groupLogsByDate(logs);
        
        // Render each group
        $.each(grouped, function(dateLabel, items) {
            // Add date label if not exists
            var existingLabel = $timeline.find('[data-date-label="' + dateLabel + '"]');
            if (existingLabel.length === 0) {
                var labelHtml = '<div class="time-label" data-date-label="' + dateLabel + '">' +
                    '<span class="bg-secondary">' + dateLabel + '</span>' +
                    '</div>';
                $timeline.append(labelHtml);
            }
            
            // Add each log item
            $.each(items, function(index, log) {
                var itemHtml = renderTimelineItem(log);
                $timeline.append(itemHtml);
            });
        });
        
        // Add end marker if no more items
        var existingEndMarker = $timeline.find('.timeline-end-marker');
        if (existingEndMarker.length > 0) {
            existingEndMarker.remove();
        }
        $timeline.append('<div class="timeline-end-marker"><i class="fas fa-ellipsis-h bg-gray"></i></div>');
    }
    
    function renderTimelineItem(log) {
        var style = getActionStyle(log.action);
        var time = formatTime(log.createdAt);
        var description = getActionDescription(log.action, log);
        var details = getActionDetails(log);
        
        var html = '<div data-activity-id="' + log.id + '">' +
            '<i class="' + style.icon + ' bg-' + style.color + '"></i>' +
            '<div class="timeline-item">' +
            '<span class="time"><i class="fas fa-clock"></i> ' + time + '</span>' +
            '<h3 class="timeline-header">' + description + '</h3>';
        
        if (details) {
            html += '<div class="timeline-body text-muted small">' + details + '</div>';
        }
        
        html += '</div></div>';
        
        return html;
    }
    
    function groupLogsByDate(logs) {
        var groups = {};
        var today = new Date().toDateString();
        var yesterday = new Date(Date.now() - 86400000).toDateString();
        
        $.each(logs, function(index, log) {
            var logDate = new Date(log.createdAt);
            var dateStr = logDate.toDateString();
            var label;
            
            if (dateStr === today) {
                label = 'Hoy';
            } else if (dateStr === yesterday) {
                label = 'Ayer';
            } else {
                label = logDate.toLocaleDateString('es-ES', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                });
            }
            
            if (!groups[label]) {
                groups[label] = [];
            }
            groups[label].push(log);
        });
        
        return groups;
    }
    
    // =========================================================================
    // ACTION STYLING & DESCRIPTIONS
    // =========================================================================
    function getActionStyle(action) {
        var styles = {
            // Authentication
            'login': { icon: 'fas fa-sign-in-alt', color: 'info' },
            'logout': { icon: 'fas fa-sign-out-alt', color: 'secondary' },
            'login_failed': { icon: 'fas fa-exclamation-triangle', color: 'danger' },
            'register': { icon: 'fas fa-user-plus', color: 'success' },
            'email_verified': { icon: 'fas fa-envelope-open-text', color: 'success' },
            'password_reset_requested': { icon: 'fas fa-unlock', color: 'warning' },
            'password_changed': { icon: 'fas fa-key', color: 'warning' },
            'profile_updated': { icon: 'fas fa-user-edit', color: 'primary' },
            
            // Tickets
            'ticket_created': { icon: 'fas fa-ticket-alt', color: 'success' },
            'ticket_updated': { icon: 'fas fa-edit', color: 'info' },
            'ticket_deleted': { icon: 'fas fa-trash', color: 'danger' },
            'ticket_resolved': { icon: 'fas fa-check-double', color: 'success' },
            'ticket_closed': { icon: 'fas fa-check-circle', color: 'success' },
            'ticket_reopened': { icon: 'fas fa-undo', color: 'warning' },
            'ticket_assigned': { icon: 'fas fa-user-plus', color: 'primary' },
            'ticket_response_added': { icon: 'fas fa-comment', color: 'info' },
            'ticket_attachment_added': { icon: 'fas fa-paperclip', color: 'info' },
            
            // Roles & Users
            'role_assigned': { icon: 'fas fa-user-tag', color: 'success' },
            'role_removed': { icon: 'fas fa-user-minus', color: 'danger' },
            'user_status_changed': { icon: 'fas fa-toggle-on', color: 'warning' },
            
            // Companies
            'company_created': { icon: 'fas fa-building', color: 'success' },
            'company_request_approved': { icon: 'fas fa-building', color: 'success' },
            'company_request_rejected': { icon: 'fas fa-building', color: 'danger' }
        };
        
        return styles[action] || { icon: 'fas fa-circle', color: 'secondary' };
    }
    
    function getActionDescription(action, log) {
        // Use backend-provided description if available
        if (log && log.actionDescription) {
            return log.actionDescription;
        }
        
        // Fallback descriptions
        var descriptions = {
            // Authentication
            'login': 'Inicio de sesión',
            'logout': 'Cierre de sesión',
            'login_failed': 'Intento de inicio de sesión fallido',
            'register': 'Registro de cuenta',
            'email_verified': 'Email verificado',
            'password_reset_requested': 'Solicitud de recuperación de contraseña',
            'password_changed': 'Contraseña cambiada',
            'profile_updated': 'Perfil actualizado',
            
            // Tickets
            'ticket_created': 'Ticket creado',
            'ticket_updated': 'Ticket actualizado',
            'ticket_deleted': 'Ticket eliminado',
            'ticket_resolved': 'Ticket resuelto',
            'ticket_closed': 'Ticket cerrado',
            'ticket_reopened': 'Ticket reabierto',
            'ticket_assigned': 'Ticket asignado',
            'ticket_response_added': 'Respuesta agregada al ticket',
            'ticket_attachment_added': 'Adjunto agregado al ticket',
            
            // Roles & Users
            'role_assigned': 'Rol asignado',
            'role_removed': 'Rol removido',
            'user_status_changed': 'Estado de usuario cambiado',
            
            // Companies
            'company_created': 'Empresa creada',
            'company_request_approved': 'Solicitud de empresa aprobada',
            'company_request_rejected': 'Solicitud de empresa rechazada'
        };
        
        return descriptions[action] || action;
    }
    
    function getActionDetails(log) {
        var parts = [];
        
        // Entity info
        if (log.entityType && log.entityId) {
            var shortId = log.entityId.substring(0, 8);
            var entityLabel = {
                'ticket': 'Ticket',
                'user': 'Usuario',
                'company': 'Empresa',
                'company_request': 'Solicitud'
            }[log.entityType] || log.entityType;
            parts.push('<span class="badge badge-light">' + entityLabel + ': ' + shortId + '...</span>');
        }
        
        // Show old → new values for status changes
        if (log.oldValues && log.newValues) {
            if (log.oldValues.status && log.newValues.status) {
                parts.push('<span class="text-muted">' + log.oldValues.status + '</span> → <span class="text-primary">' + log.newValues.status + '</span>');
            }
        }
        
        // Show specific details based on action type
        if (log.action === 'ticket_created' && log.newValues) {
            if (log.newValues.ticket_code) {
                parts.push('<strong>' + log.newValues.ticket_code + '</strong>');
            }
            if (log.newValues.title) {
                parts.push('"' + truncate(log.newValues.title, 40) + '"');
            }
            if (log.newValues.priority) {
                var priorityColors = { low: 'success', medium: 'warning', high: 'danger' };
                var priorityColor = priorityColors[log.newValues.priority.toLowerCase()] || 'secondary';
                parts.push('<span class="badge badge-' + priorityColor + '">' + log.newValues.priority + '</span>');
            }
        }
        
        if (log.action === 'ticket_deleted' && log.oldValues) {
            if (log.oldValues.ticket_code) {
                parts.push('<strong>' + log.oldValues.ticket_code + '</strong>');
            }
        }
        
        if (log.action === 'ticket_assigned' && log.newValues) {
            if (log.newValues.assigned_to_name) {
                parts.push('Asignado a: <strong>' + log.newValues.assigned_to_name + '</strong>');
            } else if (log.metadata && log.metadata.assigned_to) {
                parts.push('Asignado a: ' + log.metadata.assigned_to.substring(0, 8) + '...');
            }
        }
        
        if (log.action === 'ticket_resolved' && log.newValues) {
            if (log.newValues.resolution_time) {
                parts.push('Tiempo: <strong>' + log.newValues.resolution_time + '</strong>');
            }
        }
        
        if (log.action === 'company_request_approved' && log.newValues) {
            if (log.newValues.company_name) {
                parts.push('Empresa: <strong>' + log.newValues.company_name + '</strong>');
            }
            if (log.newValues.admin_email) {
                parts.push('<small class="text-muted">' + log.newValues.admin_email + '</small>');
            }
        }
        
        if (log.action === 'company_request_rejected' && log.newValues) {
            if (log.newValues.company_name) {
                parts.push('Empresa: <strong>' + log.newValues.company_name + '</strong>');
            }
            if (log.newValues.reason) {
                parts.push('Razón: "' + truncate(log.newValues.reason, 50) + '"');
            }
        }
        
        if (log.action === 'role_assigned' && log.newValues) {
            // User info
            if (log.newValues.user_name) {
                parts.push('Usuario: <strong>' + log.newValues.user_name + '</strong>');
            }
            if (log.newValues.user_email && !log.newValues.user_name) {
                parts.push('Usuario: <code>' + log.newValues.user_email + '</code>');
            }
            // Role info
            if (log.newValues.role) {
                parts.push('Rol: <span class="badge badge-info">' + log.newValues.role + '</span>');
            }
            if (log.newValues.company_name) {
                parts.push('Empresa: ' + log.newValues.company_name);
            }
        }
        
        if (log.action === 'role_removed' && log.oldValues) {
            // User info
            if (log.oldValues.user_name) {
                parts.push('Usuario: <strong>' + log.oldValues.user_name + '</strong>');
            }
            if (log.oldValues.user_email && !log.oldValues.user_name) {
                parts.push('Usuario: <code>' + log.oldValues.user_email + '</code>');
            }
            // Role info
            if (log.oldValues.role) {
                parts.push('Rol: <span class="badge badge-secondary">' + log.oldValues.role + '</span>');
            }
            if (log.oldValues.company_name) {
                parts.push('Empresa: ' + log.oldValues.company_name);
            }
            if (log.oldValues.reason) {
                parts.push('Razón: "' + truncate(log.oldValues.reason, 40) + '"');
            }
        }
        
        if (log.action === 'user_status_changed' && log.newValues) {
            // User info first
            if (log.newValues.user_name || (log.oldValues && log.oldValues.user_name)) {
                parts.push('Usuario: <strong>' + (log.newValues.user_name || log.oldValues.user_name) + '</strong>');
            }
            if ((log.newValues.user_email || (log.oldValues && log.oldValues.user_email)) && !log.newValues.user_name && !(log.oldValues && log.oldValues.user_name)) {
                parts.push('Usuario: <code>' + (log.newValues.user_email || log.oldValues.user_email) + '</code>');
            }
            // Status change
            if (log.oldValues && log.oldValues.status) {
                parts.push('<span class="text-muted">' + log.oldValues.status + '</span> → <span class="badge badge-warning">' + log.newValues.status + '</span>');
            }
            if (log.newValues.reason) {
                parts.push('Razón: "' + truncate(log.newValues.reason, 40) + '"');
            }
        }
        
        if (log.action === 'profile_updated' && log.newValues) {
            var changedFields = [];
            if (log.newValues.first_name) changedFields.push('nombre');
            if (log.newValues.last_name) changedFields.push('apellido');
            if (log.newValues.phone_number) changedFields.push('teléfono');
            if (log.newValues.bio) changedFields.push('bio');
            if (changedFields.length > 0) {
                parts.push('Campos: <em>' + changedFields.join(', ') + '</em>');
            }
        }
        
        if (log.action === 'ticket_response_added' && log.newValues) {
            if (log.newValues.content) {
                parts.push('"' + truncate(log.newValues.content, 60) + '"');
            }
            if (log.newValues.is_internal) {
                parts.push('<span class="badge badge-dark">Nota interna</span>');
            }
        }
        
        // Attachment with preview - AdminLTE mailbox-attachment style with ekko-lightbox
        if (log.action === 'ticket_attachment_added' && log.newValues) {
            var fileName = log.newValues.file_name || log.newValues.filename || '';
            var fileUrl = log.newValues.file_url || log.newValues.url || '';
            var fileType = log.newValues.mime_type || log.newValues.file_type || '';
            var fileSize = log.newValues.file_size || '';
            var fileExt = fileName.split('.').pop().toLowerCase();
            
            // Check if it's an image
            var isImage = fileType.indexOf('image') !== -1 || ['jpg','jpeg','png','gif','bmp','webp','svg'].indexOf(fileExt) !== -1;
            
            // Determine file icon based on extension
            var fileIcon = 'far fa-file';
            if (isImage) fileIcon = 'far fa-file-image';
            else if (fileExt === 'pdf') fileIcon = 'far fa-file-pdf';
            else if (['doc','docx'].indexOf(fileExt) !== -1) fileIcon = 'far fa-file-word';
            else if (['xls','xlsx','csv'].indexOf(fileExt) !== -1) fileIcon = 'far fa-file-excel';
            else if (fileExt === 'txt') fileIcon = 'far fa-file-alt';
            else if (fileExt === 'mp4') fileIcon = 'far fa-file-video';
            else if (['zip','rar','7z'].indexOf(fileExt) !== -1) fileIcon = 'far fa-file-archive';
            
            // Build AdminLTE mailbox-attachment style HTML
            var attachmentHtml = '<ul class="mailbox-attachments d-flex align-items-stretch clearfix" style="margin: 0; padding: 0;">';
            attachmentHtml += '<li style="max-width: 200px;">';
            
            if (isImage && fileUrl) {
                // Image with thumbnail and lightbox
                attachmentHtml += '<span class="mailbox-attachment-icon has-img" style="width: 200px; height: 132px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f4f4f4;">';
                attachmentHtml += '<a href="' + fileUrl + '" data-toggle="lightbox" data-title="' + fileName + '" data-gallery="activity-attachments" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; cursor: pointer;">';
                attachmentHtml += '<img src="' + fileUrl + '" alt="' + fileName + '" style="width: 100%; height: 100%; object-fit: cover;">';
                attachmentHtml += '</a></span>';
            } else {
                // Non-image file with icon
                attachmentHtml += '<span class="mailbox-attachment-icon"><i class="' + fileIcon + '"></i></span>';
            }
            
            attachmentHtml += '<div class="mailbox-attachment-info">';
            if (fileUrl) {
                attachmentHtml += '<a href="' + fileUrl + '" target="_blank" class="mailbox-attachment-name" title="' + fileName + '">';
                attachmentHtml += '<i class="fas fa-paperclip"></i> ' + truncate(fileName, 20);
                attachmentHtml += '</a>';
            } else {
                attachmentHtml += '<span class="mailbox-attachment-name" title="' + fileName + '">';
                attachmentHtml += '<i class="fas fa-paperclip"></i> ' + truncate(fileName, 20);
                attachmentHtml += '</span>';
            }
            attachmentHtml += '<span class="mailbox-attachment-size clearfix mt-1">';
            attachmentHtml += formatFileSize(fileSize);
            if (fileUrl) {
                attachmentHtml += '<a href="' + fileUrl + '" target="_blank" class="btn btn-default btn-sm float-right" title="Descargar"><i class="fas fa-download"></i></a>';
            }
            attachmentHtml += '</span>';
            attachmentHtml += '</div></li></ul>';
            
            parts.push(attachmentHtml);
        }
        
        // Login failed details
        if (log.action === 'login_failed' && log.newValues) {
            if (log.newValues.reason) {
                var reasonLabels = {
                    'user_not_found': 'Usuario no encontrado',
                    'invalid_password': 'Contraseña incorrecta',
                    'account_suspended': 'Cuenta suspendida',
                    'account_inactive': 'Cuenta inactiva'
                };
                parts.push('<span class="badge badge-danger">' + (reasonLabels[log.newValues.reason] || log.newValues.reason) + '</span>');
            }
            if (log.newValues.email) {
                parts.push('Email: <code>' + log.newValues.email + '</code>');
            }
        }
        
        // IP Address
        if (log.ipAddress) {
            parts.push('<small class="text-muted"><i class="fas fa-globe"></i> ' + log.ipAddress + '</small>');
        }
        
        return parts.length > 0 ? parts.join(' · ') : null;
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '...' : str;
    }
    
    function formatTime(dateStr) {
        var date = new Date(dateStr);
        return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }
    
    // =========================================================================
    // UI STATE HELPERS
    // =========================================================================
    function showEmpty($container) {
        $container.find('.timeline-loading').hide();
        $container.find('.timeline-scroll-container').hide();
        $container.find('.timeline-error').hide();
        $container.find('.timeline-load-more').hide();
        $container.find('.timeline-end').hide();
        $container.find('.timeline-empty').show();
    }
    
    function showError($container, message) {
        $container.find('.timeline-loading').hide();
        $container.find('.timeline-scroll-container').hide();
        $container.find('.timeline-empty').hide();
        $container.find('.timeline-load-more').hide();
        $container.find('.timeline-end').hide();
        $container.find('.timeline-error').show();
        $container.find('.error-message').text(message);
    }
    
    function updateLoadMore(instance) {
        var $container = instance.$container;
        var $loadMore = $container.find('.timeline-load-more');
        var $end = $container.find('.timeline-end');
        var $timeline = $container.find('.timeline-content');
        
        var currentCount = $timeline.find('[data-activity-id]').length;
        
        $loadMore.find('.shown-count').text(currentCount);
        $loadMore.find('.total-count').text(instance.totalItems);
        
        // Reset button state
        $loadMore.find('.btn-load-more')
            .prop('disabled', false)
            .html('<i class="fas fa-chevron-down mr-2"></i>Cargar más actividad');
        
        if (instance.hasMore) {
            $loadMore.show();
            $end.hide();
        } else {
            $loadMore.hide();
            if (currentCount > 0) {
                $end.show();
            }
        }
    }
    
    // =========================================================================
    // PUBLIC API - Expose to window
    // =========================================================================
    window.initActivityTimeline = initActivityTimeline;
    
    // Reload function for external use
    window.reloadActivityTimeline = function(containerId) {
        var instance = timelineInstances[containerId];
        if (instance) {
            instance.currentPage = 1;
            loadActivity(instance, false);
        }
    };
    
    // =========================================================================
    // jQuery Ready - Wait for jQuery following blade-components-jquery.mdc
    // =========================================================================
    function onJQueryReady() {
        console.log('[ActivityTimeline] jQuery available - Ready for initialization');
        
        // Initialize ekko-lightbox for dynamically added images
        $(document).on('click', '[data-toggle="lightbox"]', function(event) {
            event.preventDefault();
            $(this).ekkoLightbox({
                alwaysShowClose: true,
                showArrows: true
            });
        });
        
        // Auto-initialize containers with data-auto-init="true"
        $(document).ready(function() {
            $('.activity-timeline-container[data-auto-init="true"]').each(function() {
                var containerId = $(this).attr('id');
                console.log('[ActivityTimeline] Auto-initializing:', containerId);
                initActivityTimeline(containerId);
            });
        });
    }
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        onJQueryReady();
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                console.log('[ActivityTimeline] jQuery detected after waiting');
                clearInterval(checkJQuery);
                onJQueryReady();
            }
        }, 100);
        
        // Timeout after 10 seconds
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[ActivityTimeline] ERROR: jQuery did not load after 10 seconds');
            }
        }, 10000);
    }
})();
</script>
