<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('dashboard') }}" class="nav-link">Home</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Active Role Display -->
        <li class="nav-item">
            <span class="nav-link" x-data="roleDisplay()" x-init="init()">
                <i class="fas fa-user-tag mr-1"></i>
                <span x-text="roleDisplay"></span>
            </span>
        </li>

        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown" x-data="notificationsDropdown()" x-init="init()">
            <a class="nav-link" data-toggle="dropdown" href="#" @click="loadNotifications()">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge" x-show="unreadCount > 0" x-text="unreadCount"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    <span x-text="unreadCount"></span> Notificaciones
                </span>

                <!-- Loading State -->
                <template x-if="loading">
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </div>
                </template>

                <!-- No Notifications -->
                <template x-if="!loading && invitations.length === 0">
                    <div class="dropdown-item text-center text-muted py-3">
                        <i class="fas fa-check-circle text-success"></i> Sin notificaciones pendientes
                    </div>
                </template>

                <!-- Invitations List -->
                <template x-for="invitation in invitations" :key="invitation.id">
                    <div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-item">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-user-plus text-primary mr-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="text-sm">
                                        <strong x-text="invitation.company?.name"></strong> te invita a ser
                                        <strong>Agente</strong>
                                    </div>
                                    <small class="text-muted" x-text="formatDate(invitation.created_at)"></small>
                                    <div class="mt-2">
                                        <button class="btn btn-xs btn-success mr-1"
                                            @click.stop="acceptInvitation(invitation.id)"
                                            :disabled="processingId === invitation.id">
                                            <i class="fas fa-check"></i> Aceptar
                                        </button>
                                        <button class="btn btn-xs btn-outline-danger"
                                            @click.stop="rejectInvitation(invitation.id)"
                                            :disabled="processingId === invitation.id">
                                            <i class="fas fa-times"></i> Rechazar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer" @click.prevent="loadNotifications()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </a>
            </div>
        </li>

        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown" x-data="userMenu()" x-init="init()">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user"></i>
                <span class="d-none d-md-inline ml-1" x-text="userName"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <a href="#" class="dropdown-item dropdown-header">
                    <span x-text="userEmail"></span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="/profile" class="dropdown-item">
                    <i class="fas fa-user mr-2"></i> Mi Perfil
                </a>
                <a href="/settings" class="dropdown-item">
                    <i class="fas fa-cog mr-2"></i> Configuración
                </a>
                <template x-if="hasMultipleRoles">
                    <div>
                        <div class="dropdown-divider"></div>
                        <a href="/auth-flow/role-selector" class="dropdown-item text-info">
                            <i class="fas fa-exchange-alt mr-2"></i> Cambiar Rol
                        </a>
                    </div>
                </template>
                <div class="dropdown-divider"></div>
                <a href="#" @click.prevent="logout()" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                </a>
            </div>
        </li>

        <!-- Fullscreen Toggle -->
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
    </ul>
</nav>

<script>
    /**
     * Role Display Component
     * Shows the active role from JWT in the navbar
     */
    function roleDisplay() {
        return {
            roleDisplay: 'Loading...',
            _initialized: false,

            init() {
                // Only initialize once to avoid duplicate updates
                if (this._initialized) return;
                this._initialized = true;

                // Wait for Alpine and DOM to be fully ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => {
                        setTimeout(() => this.updateRoleDisplay(), 100);
                    });
                } else {
                    setTimeout(() => this.updateRoleDisplay(), 100);
                }
            },

            updateRoleDisplay() {
                if (typeof getUserFromJWT === 'function') {
                    const userData = getUserFromJWT();

                    if (userData && userData.activeRole) {
                        const role = userData.activeRole;
                        const roleNames = {
                            'PLATFORM_ADMIN': 'Platform Admin',
                            'COMPANY_ADMIN': 'Company Admin',
                            'AGENT': 'Agent',
                            'USER': 'User'
                        };

                        let display = roleNames[role.code] || role.code;

                        // Add company name if available
                        if (role.company_name) {
                            display += ' - ' + role.company_name;
                        }

                        this.roleDisplay = display;
                    } else {
                        this.roleDisplay = 'No role selected';
                    }
                } else {
                    this.roleDisplay = 'Role unavailable';
                }
            }
        };
    }

    /**
     * User Menu Component
     * Shows user info from JWT in the navbar dropdown
     */
    function userMenu() {
        return {
            userName: 'User',
            userEmail: '',
            hasMultipleRoles: false,
            _initialized: false,

            init() {
                // Only initialize once to avoid duplicate updates
                if (this._initialized) return;
                this._initialized = true;

                // Wait for Alpine and DOM to be fully ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => {
                        setTimeout(() => this.loadUserData(), 100);
                    });
                } else {
                    setTimeout(() => this.loadUserData(), 100);
                }
            },

            loadUserData() {
                if (typeof getUserFromJWT === 'function') {
                    const userData = getUserFromJWT();
                    if (userData) {
                        this.userName = userData.name || 'User';
                        this.userEmail = userData.email || '';
                        this.hasMultipleRoles = userData.hasMultipleRoles || false;
                    }
                }
            },

            logout() {
                if (typeof window.logout === 'function') {
                    window.logout();
                } else {
                    // Fallback logout
                    localStorage.clear();
                    window.location.href = '/login';
                }
            }
        };
    }

    /**
     * Notifications Dropdown Component
     * Shows pending invitations from API in the navbar
     */
    function notificationsDropdown() {
        return {
            loading: false,
            invitations: [],
            unreadCount: 0,
            processingId: null,
            _initialized: false,
            _lastLoad: 0,

            init() {
                if (this._initialized) return;
                this._initialized = true;

                // Load initial count after short delay
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => {
                        setTimeout(() => this.loadNotificationsCount(), 500);
                    });
                } else {
                    setTimeout(() => this.loadNotificationsCount(), 500);
                }
            },

            getToken() {
                return localStorage.getItem('access_token');
            },

            formatDate(dateString) {
                if (!dateString) return '';
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);

                if (diffMins < 1) return 'Ahora';
                if (diffMins < 60) return `Hace ${diffMins} min`;
                if (diffHours < 24) return `Hace ${diffHours} horas`;
                return `Hace ${diffDays} días`;
            },

            async loadNotificationsCount() {
                const token = this.getToken();
                if (!token) return;

                try {
                    const response = await fetch('/api/me/invitations/pending-count', {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.unreadCount = data.data?.count || 0;
                    }
                } catch (error) {
                    console.error('[Notifications] Error loading count:', error);
                }
            },

            async loadNotifications() {
                // Prevent rapid reloads
                const now = Date.now();
                if (now - this._lastLoad < 1000) return;
                this._lastLoad = now;

                const token = this.getToken();
                if (!token) return;

                this.loading = true;
                
                try {
                    const response = await fetch('/api/me/invitations?pending_only=true', {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.invitations = data.data || [];
                        this.unreadCount = data.meta?.pending_count || this.invitations.length;
                    }
                } catch (error) {
                    console.error('[Notifications] Error loading invitations:', error);
                } finally {
                    this.loading = false;
                }
            },

            async acceptInvitation(id) {
                this.processingId = id;
                const token = this.getToken();

                try {
                    const response = await fetch(`/api/me/invitations/${id}/accept`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        // Show success toast
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: data.message || '¡Invitación aceptada!',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                        
                        // Reload invitations
                        this._lastLoad = 0;
                        await this.loadNotifications();
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: data.message || 'Error al aceptar',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    }
                } catch (error) {
                    console.error('[Notifications] Error accepting:', error);
                } finally {
                    this.processingId = null;
                }
            },

            async rejectInvitation(id) {
                this.processingId = id;
                const token = this.getToken();

                try {
                    const response = await fetch(`/api/me/invitations/${id}/reject`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                title: 'Invitación rechazada',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                        
                        this._lastLoad = 0;
                        await this.loadNotifications();
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: data.message || 'Error al rechazar',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    }
                } catch (error) {
                    console.error('[Notifications] Error rejecting:', error);
                } finally {
                    this.processingId = null;
                }
            }
        };
    }

</script>