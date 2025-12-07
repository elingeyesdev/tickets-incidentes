{{-- 
    View User Modal Component (Idea 2 Refined)
    
    Features:
    - Card + Tabs design (General, Roles, Actividad)
    - Large company logos in roles
    - AdminLTE Timeline for activity
    - jQuery Validation for role assignment
    - Prevents removing last role
    
    Usage: @include('app.platform-admin.users.partials.view-user-modal')
--}}

{{-- CSS Styles for this component --}}
<style>
    /* Avatar placeholder - light theme */
    .avatar-modal {
        width: 70px;
        height: 70px;
        min-width: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 1.8rem;
        border: 3px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    /* Role icon box */
    .role-icon-box {
        width: 64px;
        height: 64px;
        min-width: 64px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
    /* Role card styling */
    .role-card-serious {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .role-card-serious:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    /* Remove button disabled state */
    .btn-remove-role:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
    /* Header user info */
    .user-header-info {
        min-width: 0;
        overflow: hidden;
    }
    /* Email responsive */
    #viewEmailDuplicate {
        word-break: break-all;
    }
</style>

{{-- Modal: View User Details (Idea 2 Refined) --}}
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Header with name prominent -->
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center w-100">
                    <!-- Avatar -->
                    <div id="viewUserAvatar" class="avatar-modal mr-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-grow-1 user-header-info">
                        <h4 class="mb-1 text-dark" id="viewUserFullName">-</h4>
                        <div class="d-flex align-items-center flex-wrap">
                            <code id="viewUserCode" class="mr-2">-</code>
                            <span class="text-muted mr-2">·</span>
                            <span class="text-muted" id="viewUserEmail">-</span>
                            <span id="viewUserStatusBadge" class="badge badge-secondary ml-2">-</span>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            
            <!-- Tabs Navigation (AdminLTE v3 Pills) -->
            <div class="card-header border-bottom">
                <ul class="nav nav-pills" id="viewUserTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general" data-toggle="pill" href="#viewUserGeneral">
                            <i class="fas fa-info-circle"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-roles" data-toggle="pill" href="#viewUserRoles">
                            <i class="fas fa-shield-alt"></i> Roles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-activity" data-toggle="pill" href="#viewUserActivity">
                            <i class="fas fa-history"></i> Actividad
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Tab Content -->
            <div class="modal-body">
                <div class="tab-content" id="viewUserTabContent">
                    <!-- Tab: General -->
                    <div class="tab-pane fade show active" id="viewUserGeneral" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Información</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4 text-muted">Email</dt>
                                    <dd class="col-sm-8" id="viewEmailDuplicate">-</dd>
                                    <dt class="col-sm-4 text-muted">Verificado</dt>
                                    <dd class="col-sm-8" id="viewEmailVerifiedBadge"><i class="fas fa-times-circle text-danger"></i> No</dd>
                                    <dt class="col-sm-4 text-muted">Teléfono</dt>
                                    <dd class="col-sm-8" id="viewPhone">-</dd>
                                    <dt class="col-sm-4 text-muted">Auth</dt>
                                    <dd class="col-sm-8" id="viewAuthProvider">-</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Estadísticas</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted">Tickets</dt>
                                    <dd class="col-sm-7"><strong id="viewTicketsCount">0</strong></dd>
                                    <dt class="col-sm-5 text-muted">Resueltos</dt>
                                    <dd class="col-sm-7"><strong id="viewResolvedTickets">0</strong></dd>
                                </dl>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Preferencias</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4 text-muted">Tema</dt>
                                    <dd class="col-sm-8" id="viewTheme">-</dd>
                                    <dt class="col-sm-4 text-muted">Idioma</dt>
                                    <dd class="col-sm-8" id="viewLanguage">-</dd>
                                    <dt class="col-sm-4 text-muted">Zona</dt>
                                    <dd class="col-sm-8" id="viewTimezone">-</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Fechas</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted">Creado</dt>
                                    <dd class="col-sm-7" id="viewCreatedAt">-</dd>
                                    <dt class="col-sm-5 text-muted">Actualizado</dt>
                                    <dd class="col-sm-7" id="viewUpdatedAt">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Roles -->
                    <div class="tab-pane fade" id="viewUserRoles" role="tabpanel">
                        <!-- Roles Container (dynamically populated) -->
                        <div id="rolesContainer">
                            <p class="text-muted text-center py-3">
                                <i class="fas fa-spinner fa-spin"></i> Cargando roles...
                            </p>
                        </div>
                        
                        <hr>
                        
                        <!-- Add Role Form -->
                        <h6 class="text-muted mb-3"><i class="fas fa-plus-circle"></i> Asignar Nuevo Rol</h6>
                        <form id="assignRoleForm">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group mb-md-0">
                                        <select id="newRoleSelect" name="role_code" class="form-control form-control-sm" required>
                                            <option value="">Seleccionar rol...</option>
                                            <option value="USER">USER - Cliente</option>
                                            <option value="AGENT">AGENT - Agente</option>
                                            <option value="COMPANY_ADMIN">COMPANY_ADMIN - Admin Empresa</option>
                                            <option value="PLATFORM_ADMIN">PLATFORM_ADMIN - Admin Plataforma</option>
                                        </select>
                                        <small class="form-text text-muted">Rol a asignar</small>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group mb-md-0" id="companySelectWrapper" style="display: none;">
                                        <select id="newCompanySelect" name="company_id" class="form-control form-control-sm">
                                            <option value="">Seleccionar empresa...</option>
                                        </select>
                                        <small class="form-text text-muted">Empresa (si aplica)</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" id="btnAssignRole" class="btn btn-success btn-sm btn-block" disabled>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" id="assignRoleUserId" name="user_id">
                        </form>
                    </div>
                    
                    <!-- Tab: Activity (Timeline) -->
                    <div class="tab-pane fade" id="viewUserActivity" role="tabpanel">
                        <div id="activityTimeline">
                            <!-- Timeline will be populated dynamically -->
                            <div class="timeline">
                                <div class="time-label">
                                    <span class="bg-secondary">Última Sesión</span>
                                </div>
                                <div>
                                    <i class="fas fa-sign-in-alt bg-info"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> <span id="activityLastLogin">-</span></span>
                                        <h3 class="timeline-header">Último inicio de sesión</h3>
                                        <div class="timeline-body" id="activityLastLoginDetails">
                                            Sin información disponible
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fas fa-clock bg-secondary"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> <span id="activityLastActive">-</span></span>
                                        <h3 class="timeline-header">Última actividad registrada</h3>
                                    </div>
                                </div>
                                <div>
                                    <i class="fas fa-ellipsis-h bg-gray"></i>
                                </div>
                            </div>
                            <div class="callout callout-info mt-3">
                                <p class="mb-0"><i class="fas fa-info-circle"></i> El historial detallado de actividad estará disponible próximamente.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btnModalDelete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
                <button type="button" id="btnModalChangeStatus" class="btn btn-warning">
                    <i class="fas fa-ban"></i> Cambiar Estado
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Script for View User Modal --}}
<script>
(function() {
    console.log('[ViewUserModal] Script loaded - waiting for jQuery...');
    
    // Current user data
    let currentUserId = null;
    let currentUserData = null;
    let companiesCache = [];
    
    function initViewUserModal() {
        console.log('[ViewUserModal] Initializing...');
        
        const $modal = $('#viewUserModal');
        const $assignForm = $('#assignRoleForm');
        const $roleSelect = $('#newRoleSelect');
        const $companySelect = $('#newCompanySelect');
        const $companyWrapper = $('#companySelectWrapper');
        const $btnAssign = $('#btnAssignRole');
        
        // Validate jQuery Validation plugin
        if (typeof $.fn.validate === 'undefined') {
            console.warn('[ViewUserModal] jQuery Validation not loaded, using basic validation');
        } else {
            // Configure form validation
            $assignForm.validate({
                errorElement: 'span',
                errorClass: 'invalid-feedback',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                    $(element).closest('.form-group').find('.form-text').hide();
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                    $(element).closest('.form-group').find('.form-text').show();
                },
                rules: {
                    role_code: { required: true },
                    company_id: {
                        required: {
                            depends: function() {
                                const role = $roleSelect.val();
                                return role === 'AGENT' || role === 'COMPANY_ADMIN';
                            }
                        }
                    }
                },
                messages: {
                    role_code: { required: "Selecciona un rol" },
                    company_id: { required: "Selecciona una empresa" }
                }
            });
            console.log('[ViewUserModal] Form validation configured');
        }
        
        // Role select change - show/hide company
        $roleSelect.on('change', function() {
            const role = $(this).val();
            const requiresCompany = role === 'AGENT' || role === 'COMPANY_ADMIN';
            
            if (requiresCompany) {
                $companyWrapper.slideDown(200);
                $companySelect.prop('required', true);
                loadCompanies();
            } else {
                $companyWrapper.slideUp(200);
                $companySelect.prop('required', false).val('');
            }
            
            // Enable/disable submit button
            $btnAssign.prop('disabled', !role);
            
            // Re-validate if validation plugin exists
            if (typeof $.fn.validate !== 'undefined') {
                $assignForm.validate().element('#newRoleSelect');
            }
        });
        
        // Company select change - re-validate
        $companySelect.on('change', function() {
            if (typeof $.fn.validate !== 'undefined') {
                $assignForm.validate().element('#newCompanySelect');
            }
        });
        
        // Assign role form submit
        $assignForm.on('submit', function(e) {
            e.preventDefault();
            
            if (typeof $.fn.validate !== 'undefined' && !$assignForm.valid()) {
                return;
            }
            
            const roleCode = $roleSelect.val();
            const companyId = $companySelect.val() || null;
            
            if (!currentUserId || !roleCode) {
                showToast('error', 'Datos incompletos');
                return;
            }
            
            assignRole(currentUserId, roleCode, companyId);
        });
        
        // Modal change status button
        $('#btnModalChangeStatus').on('click', function() {
            if (currentUserId && currentUserData) {
                $('#viewUserModal').modal('hide');
                // Trigger status modal (handled by parent) - pass flag to enable "back" button
                $(document).trigger('openStatusModal', [currentUserId, currentUserData, true]);
            }
        });
        
        // Modal delete button
        $('#btnModalDelete').on('click', function() {
            if (currentUserId && currentUserData) {
                $('#viewUserModal').modal('hide');
                // Trigger delete modal (handled by parent) - pass flag to enable "back" button
                $(document).trigger('openDeleteModal', [currentUserId, currentUserData, true]);
            }
        });
        
        // Reset on modal close
        $modal.on('hidden.bs.modal', function() {
            resetModal();
        });
        
        console.log('[ViewUserModal] Initialization complete');
    }
    
    // Load companies for dropdown
    function loadCompanies() {
        const $select = $('#newCompanySelect');
        
        if (companiesCache.length > 0) {
            populateCompanies(companiesCache);
            return;
        }
        
        $select.html('<option value="">Cargando...</option>').prop('disabled', true);
        
        $.ajax({
            url: '/api/companies/minimal',
            method: 'GET',
            data: { per_page: 100 },
            success: function(response) {
                companiesCache = response.data || [];
                populateCompanies(companiesCache);
            },
            error: function() {
                $select.html('<option value="">Error al cargar</option>');
                console.error('[ViewUserModal] Error loading companies');
            }
        });
    }
    
    function populateCompanies(companies) {
        const $select = $('#newCompanySelect');
        let html = '<option value="">Seleccionar empresa...</option>';
        companies.forEach(c => {
            html += `<option value="${c.id}">${c.name}</option>`;
        });
        $select.html(html).prop('disabled', false);
    }
    
    // Assign role to user
    function assignRole(userId, roleCode, companyId) {
        const $btn = $('#btnAssignRole');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        const token = localStorage.getItem('access_token');
        
        // Build request body - use camelCase as per API spec
        const requestBody = { roleCode: roleCode };
        if (companyId) requestBody.companyId = companyId;
        
        $.ajax({
            url: `/api/users/${userId}/roles`,
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` },
            contentType: 'application/json',
            data: JSON.stringify(requestBody),
            success: function(response) {
                showToast('success', response.message || 'Rol asignado correctamente');
                // Reset form
                $('#assignRoleForm')[0].reset();
                $('#companySelectWrapper').hide();
                $btn.prop('disabled', true).html('<i class="fas fa-plus"></i>');
                // Reload roles
                loadUserRoles(userId);
                // Trigger refresh event for parent
                $(document).trigger('userRolesChanged', [userId]);
            },
            error: function(xhr) {
                handleApiError(xhr, 'Error al asignar rol');
                $btn.prop('disabled', false).html('<i class="fas fa-plus"></i>');
            }
        });
    }
    
    // Remove role from user - CORRECT URL: /api/users/roles/{roleId}
    function removeRole(userId, roleId, rolesCount) {
        // Prevent removing last role
        if (rolesCount <= 1) {
            showToast('warning', 'No se puede quitar el único rol del usuario');
            return;
        }
        
        if (!confirm('¿Estás seguro de quitar este rol?')) {
            return;
        }
        
        const token = localStorage.getItem('access_token');
        
        $.ajax({
            url: `/api/users/roles/${roleId}`,
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` },
            success: function(response) {
                showToast('success', response.message || 'Rol eliminado correctamente');
                loadUserRoles(userId);
                $(document).trigger('userRolesChanged', [userId]);
            },
            error: function(xhr) {
                handleApiError(xhr, 'Error al eliminar rol');
            }
        });
    }
    
    // Load user roles and render
    function loadUserRoles(userId) {
        const $container = $('#rolesContainer');
        $container.html('<p class="text-muted text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando roles...</p>');
        
        const token = localStorage.getItem('access_token');
        
        $.ajax({
            url: `/api/users/${userId}`,
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` },
            success: function(response) {
                const user = response.data || response;
                renderRoles(user.roleContexts || [], userId);
            },
            error: function() {
                $container.html('<p class="text-danger text-center py-3"><i class="fas fa-exclamation-circle"></i> Error al cargar roles</p>');
            }
        });
    }
    
    // Render roles cards
    function renderRoles(roles, userId) {
        const $container = $('#rolesContainer');
        const rolesCount = roles.length;
        
        if (rolesCount === 0) {
            $container.html('<p class="text-muted text-center py-3">Sin roles asignados</p>');
            return;
        }
        
        let html = '';
        roles.forEach(role => {
            const companyLogo = role.company?.logoUrl || null;
            const industry = role.company?.industry?.name || '';
            const companyCode = role.company?.companyCode || '';
            const assignedAt = role.assignedAt ? formatDate(role.assignedAt) : null;
            const canRemove = rolesCount > 1;
            
            // Roles that require company context
            const requiresCompany = ['AGENT', 'COMPANY_ADMIN'].includes(role.roleCode);
            const hasCompany = role.company && role.company.name;
            
            // Role icons - specific per role type
            const roleIcons = {
                'AGENT': 'headset',
                'USER': 'user',
                'COMPANY_ADMIN': 'user-tie',
                'PLATFORM_ADMIN': 'crown'
            };
            const roleIcon = roleIcons[role.roleCode] || 'user-shield';
            
            // Role icon colors - USER is blue (Helpdesk brand)
            const roleColors = {
                'AGENT': 'info',
                'USER': 'primary',
                'COMPANY_ADMIN': 'warning',
                'PLATFORM_ADMIN': 'danger'
            };
            const roleColor = roleColors[role.roleCode] || 'secondary';
            
            // Role name
            const roleNames = {
                'AGENT': 'Agente de Soporte',
                'USER': 'Cliente',
                'COMPANY_ADMIN': 'Admin de Empresa',
                'PLATFORM_ADMIN': 'Admin de Plataforma'
            };
            const roleName = roleNames[role.roleCode] || role.roleCode;
            
            // Build company section only for roles that need it
            let companySection = '';
            if (requiresCompany) {
                if (hasCompany) {
                    companySection = `<h6 class="text-secondary mb-2">${role.company.name}</h6>`;
                } else {
                    companySection = `<h6 class="text-danger mb-2"><i class="fas fa-exclamation-triangle"></i> Sin empresa asignada</h6>`;
                }
            }
            
            // Build meta info - include role code
            let metaInfo = [];
            metaInfo.push(`<code class="text-${roleColor}">${role.roleCode}</code>`);
            if (requiresCompany && industry) metaInfo.push(`<i class="fas fa-industry"></i> ${industry}`);
            if (requiresCompany && companyCode) metaInfo.push(`<i class="fas fa-hashtag"></i> ${companyCode}`);
            if (assignedAt) metaInfo.push(`<i class="fas fa-calendar"></i> Asignado: ${assignedAt}`);
            
            // Determine icon box - show company logo for company roles, or icon for others
            let iconBox = '';
            if (requiresCompany && companyLogo) {
                iconBox = `<img src="${companyLogo}" class="role-icon-box mr-3" style="object-fit: contain; background: #fff; border: 1px solid #dee2e6; padding: 8px;" alt="Logo">`;
            } else {
                iconBox = `<div class="role-icon-box bg-${roleColor}" style="opacity: 0.9;">
                    <i class="fas fa-${roleIcon} fa-lg text-white"></i>
                </div>`;
            }
            
            html += `
                <div class="role-card-serious">
                    <div class="d-flex align-items-center">
                        ${iconBox}
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><span class="text-muted font-weight-normal">ROL</span> — ${roleName}</h5>
                                    ${companySection}
                                </div>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger btn-remove-role" 
                                        data-role-id="${role.userRoleId}"
                                        ${!canRemove ? 'disabled title="No se puede quitar el único rol"' : ''}>
                                    <i class="fas fa-trash"></i>${canRemove ? ' Quitar' : ''}
                                </button>
                            </div>
                            <div class="text-sm text-muted">${metaInfo.join(' · ')}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $container.html(html);
        
        // Bind remove buttons
        $container.find('.btn-remove-role').on('click', function() {
            const roleId = $(this).data('role-id');
            removeRole(userId, roleId, rolesCount);
        });
    }
    
    // Open modal with user data
    window.ViewUserModal = {
        open: function(userData) {
            currentUserId = userData.id;
            currentUserData = userData;
            
            // Populate and set General tab
            this._populateData(userData, 'general');
            
            // Show modal
            $('#viewUserModal').modal('show');
        },
        
        // Open modal with Roles tab selected
        openRolesTab: function(userData) {
            currentUserId = userData.id;
            currentUserData = userData;
            
            // Populate and set Roles tab BEFORE showing modal (no flicker)
            this._populateData(userData, 'roles');
            
            // Show modal
            $('#viewUserModal').modal('show');
        },
        
        // Internal: Populate modal data with specified initial tab
        // initialTab: 'general' | 'roles' | 'activity'
        _populateData: function(userData, initialTab) {
            // Build full name from profile
            let fullName = userData.email;
            if (userData.profile) {
                const firstName = userData.profile.firstName || '';
                const lastName = userData.profile.lastName || '';
                const profileName = `${firstName} ${lastName}`.trim();
                if (profileName) fullName = profileName;
                else if (userData.profile.fullName) fullName = userData.profile.fullName;
            }
            
            // Populate header
            $('#viewUserFullName').text(fullName);
            $('#viewUserCode').text(userData.userCode || '-');
            $('#viewUserEmail').text(userData.email || '-');
            
            // Avatar
            if (userData.profile?.avatarUrl) {
                $('#viewUserAvatar').html(`<img src="${userData.profile.avatarUrl}" class="rounded-circle" style="width:100%;height:100%;object-fit:cover;">`);
            } else {
                $('#viewUserAvatar').html(`<i class="fas fa-user"></i>`);
            }
            
            // Status badge
            const status = (userData.status || '').toLowerCase();
            const statusClasses = { active: 'success', suspended: 'warning', inactive: 'secondary', deleted: 'danger' };
            const statusLabels = { active: 'Activo', suspended: 'Suspendido', inactive: 'Inactivo', deleted: 'Eliminado' };
            $('#viewUserStatusBadge')
                .removeClass('badge-success badge-warning badge-secondary badge-danger badge-info badge-primary')
                .addClass(`badge-${statusClasses[status] || 'secondary'}`)
                .text(statusLabels[status] || userData.status || '-');
            
            // Email verified
            const isVerified = userData.emailVerified || userData.emailVerifiedAt;
            if (isVerified) {
                $('#viewEmailVerifiedBadge').html('<i class="fas fa-check-circle text-success"></i> Sí');
            } else {
                $('#viewEmailVerifiedBadge').html('<i class="fas fa-times-circle text-danger"></i> No');
            }
            
            // General tab
            $('#viewPhone').text(userData.profile?.phoneNumber || '-');
            $('#viewEmailDuplicate').text(userData.email || '-');
            $('#viewAuthProvider').text(userData.authProvider || 'local');
            $('#viewTicketsCount').text(userData.stats?.ticketsCreated || 0);
            $('#viewResolvedTickets').text(userData.stats?.ticketsResolved || 0);
            $('#viewTheme').text(userData.preferences?.theme || 'light');
            $('#viewLanguage').text(userData.preferences?.language || 'es');
            $('#viewTimezone').text(userData.preferences?.timezone || '-');
            $('#viewCreatedAt').text(formatDate(userData.createdAt));
            $('#viewUpdatedAt').text(formatDate(userData.updatedAt));
            
            // Activity tab
            $('#activityLastLogin').text(formatDateTime(userData.lastLoginAt) || 'Nunca');
            $('#activityLastActive').text(formatDateTime(userData.lastActivityAt) || '-');
            
            // Hidden field for role assignment
            $('#assignRoleUserId').val(userData.id);
            
            // Load roles
            renderRoles(userData.roleContexts || [], userData.id);
            
            // Set active tab based on parameter (NO FLICKER - done before modal show)
            const tabMap = {
                'general': { tab: '#tab-general', pane: '#viewUserGeneral' },
                'roles': { tab: '#tab-roles', pane: '#viewUserRoles' },
                'activity': { tab: '#tab-activity', pane: '#viewUserActivity' }
            };
            const activeTab = tabMap[initialTab] || tabMap['general'];
            
            $('#viewUserTabs .nav-link').removeClass('active');
            $('#viewUserTabContent .tab-pane').removeClass('show active');
            $(activeTab.tab).addClass('active');
            $(activeTab.pane).addClass('show active');
        },
        
        close: function() {
            $('#viewUserModal').modal('hide');
        },
        
        refresh: function() {
            if (currentUserId) {
                loadUserRoles(currentUserId);
            }
        }
    };
    
    // Helper: Format date
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-BO');
    }
    
    function formatDateTime(dateStr) {
        if (!dateStr) return null;
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-BO') + ' ' + d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });
    }
    
    function getInitials(name) {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
    }
    
    // Reset modal state
    function resetModal() {
        currentUserId = null;
        currentUserData = null;
        $('#assignRoleForm')[0].reset();
        $('#companySelectWrapper').hide();
        $('#btnAssignRole').prop('disabled', true).html('<i class="fas fa-plus"></i>');
        $('#rolesContainer').html('');
        
        // Remove validation errors
        $('#assignRoleForm').find('.is-invalid').removeClass('is-invalid');
        $('#assignRoleForm').find('.invalid-feedback').remove();
        $('#assignRoleForm').find('.form-text').show();
    }
    
    // Toast helper (uses parent's toast or fallback)
    function showToast(type, message) {
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        } else if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            console.log(`[Toast ${type}]: ${message}`);
            alert(message);
        }
    }
    
    // Handle API errors with detailed messages
    function handleApiError(xhr, defaultMessage) {
        let errorMsg = defaultMessage;
        
        if (xhr.responseJSON) {
            const resp = xhr.responseJSON;
            // Try different error formats
            if (resp.message) {
                errorMsg = resp.message;
            }
            // Handle validation errors (Laravel format)
            if (resp.errors && typeof resp.errors === 'object') {
                const firstError = Object.values(resp.errors)[0];
                if (Array.isArray(firstError) && firstError.length > 0) {
                    errorMsg = firstError[0];
                } else if (typeof firstError === 'string') {
                    errorMsg = firstError;
                }
            }
            // Log full error for debugging
            console.error('[API Error]', resp);
        } else if (xhr.status === 401) {
            errorMsg = 'Sesión expirada. Por favor, inicia sesión nuevamente.';
        } else if (xhr.status === 403) {
            errorMsg = 'No tienes permisos para realizar esta acción.';
        } else if (xhr.status === 404) {
            errorMsg = 'Recurso no encontrado.';
        } else if (xhr.status === 422) {
            errorMsg = 'Error de validación. Revisa los datos ingresados.';
        } else if (xhr.status >= 500) {
            errorMsg = 'Error del servidor. Intenta más tarde.';
        }
        
        showToast('error', errorMsg);
    }
    
    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initViewUserModal);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initViewUserModal);
            }
        }, 100);
        
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[ViewUserModal] ERROR: jQuery did not load after 10 seconds');
            }
        }, 10000);
    }
})();
</script>
