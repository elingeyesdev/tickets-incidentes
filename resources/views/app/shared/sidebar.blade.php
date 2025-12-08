<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="brand-link">
        <img src="{{ asset('vendor/adminlte/dist/img/AdminLTELogo.png') }}" alt="HELPDESK Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">HELPDESK</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar" x-data="sidebarMenu()" x-init="init()">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="/profile" class="d-block" x-text="userName"></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard (All roles) -->
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Platform Admin Menu -->
                <template x-if="activeRole === 'PLATFORM_ADMIN'">
                    <div>
                        <li class="nav-header">SYSTEM MANAGEMENT</li>
                        <li class="nav-item">
                            <a href="/app/admin/users" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Users</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/admin/companies" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>Companies</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/admin/company-requests" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>
                                    Company Requests
                                    <span class="badge badge-warning right" id="companyRequestsBadge">0</span>
                                </p>
                            </a>
                        </li>
                        <li class="nav-header">CUENTA</li>
                        <li class="nav-item">
                            <a href="{{ route('app.profile') }}" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Perfil</p>
                            </a>
                        </li>
                    </div>
                </template>

                <!-- Company Admin Menu -->
                <template x-if="activeRole === 'COMPANY_ADMIN'">
                    <div>
                        <li class="nav-header">TICKETS</li>
                        <li class="nav-item">
                            <a href="/app/company/tickets" class="nav-link">
                                <i class="nav-icon fas fa-ticket-alt"></i>
                                <p>Tickets</p>
                            </a>
                        </li>
                        <li class="nav-header">GESTIÓN</li>
                        <li class="nav-item">
                            <a href="/app/company/categories" class="nav-link">
                                <i class="nav-icon fas fa-tags"></i>
                                <p>Categorías</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/company/areas" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>Áreas</p>
                            </a>
                        </li>
                        <li class="nav-header">CONTENIDO</li>
                        <li class="nav-item">
                            <a href="/app/company/announcements" class="nav-link">
                                <i class="nav-icon fas fa-bullhorn"></i>
                                <p>Anuncios</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/company/articles" class="nav-link">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>Artículos</p>
                            </a>
                        </li>
                        <li class="nav-header">CONFIGURACIÓN</li>
                        <li class="nav-item">
                            <a href="/app/company/settings" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Configuración</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/profile" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Perfil</p>
                            </a>
                        </li>
                        <li class="nav-header text-warning">DEBUG</li>
                        <li class="nav-item">
                            <a href="#" @click.prevent="testRefresh()" class="nav-link text-warning">
                                <i class="nav-icon fas fa-sync-alt"></i>
                                <p>Test Refresh Token</p>
                            </a>
                        </li>
                    </div>
                </template>

                <!-- Agent Menu -->
                <template x-if="activeRole === 'AGENT'">
                    <div>
                        <li class="nav-header">TICKETS</li>
                        <li class="nav-item">
                            <a href="/app/agent/tickets" class="nav-link">
                                <i class="nav-icon fas fa-ticket-alt"></i>
                                <p>Tickets</p>
                            </a>
                        </li>
                        <li class="nav-header">CUENTA</li>
                        <li class="nav-item">
                            <a href="{{ route('app.profile') }}" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Perfil</p>
                            </a>
                        </li>
                    </div>
                </template>

                <!-- User Menu -->
                <template x-if="activeRole === 'USER'">
                    <div>
                        <li class="nav-header">SOPORTE</li>
                        <li class="nav-item">
                            <a href="/app/user/tickets" class="nav-link">
                                <i class="nav-icon fas fa-ticket-alt"></i>
                                <p>Mis Tickets</p>
                            </a>
                        </li>
                        <li class="nav-header">INFORMACIÓN</li>
                        <li class="nav-item">
                            <a href="/app/user/announcements" class="nav-link">
                                <i class="nav-icon fas fa-bullhorn"></i>
                                <p>Anuncios</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('user.companies.index') }}" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>Empresas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/user/help-center" class="nav-link">
                                <i class="nav-icon fas fa-question-circle"></i>
                                <p>Centro de Ayuda</p>
                            </a>
                        </li>
                        <li class="nav-header">CUENTA</li>
                        <li class="nav-item">
                            <a href="{{ route('app.profile') }}" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Perfil</p>
                            </a>
                        </li>
                    </div>
                </template>

                <!-- Switch Role (Only if multiple roles) - Above Logout -->
                <template x-if="hasMultipleRoles">
                    <li class="nav-item">
                        <a href="/auth-flow/role-selector" class="nav-link">
                            <i class="nav-icon fas fa-exchange-alt"></i>
                            <p>Cambiar Rol</p>
                        </a>
                    </li>
                </template>

                <!-- Logout (All roles) -->
                <li class="nav-item">
                    <a href="#" @click.prevent="logout()" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<script>
    /**
     * Sidebar Menu Component
     * Dynamically shows menu items based on active role from JWT
     */
    function sidebarMenu() {
        return {
            activeRole: null,
            userName: 'User',
            hasMultipleRoles: false,

            init() {
                this.loadUserData();
                
                // Wait for Alpine and DOM to be fully ready before detecting role
                // This prevents race conditions where getUserFromJWT might not be defined yet
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => {
                        setTimeout(() => this.waitForTokenAndDetectRole(), 100);
                    });
                } else {
                    setTimeout(() => this.waitForTokenAndDetectRole(), 100);
                }
            },

            loadUserData() {
                if (typeof getUserFromJWT === 'function') {
                    const userData = getUserFromJWT();
                    if (userData) {
                        this.userName = userData.name || 'User';
                    }
                }
            },

            waitForTokenAndDetectRole(attempts = 0) {
                if (typeof getUserFromJWT === 'function') {
                    const userData = getUserFromJWT();
                    
                    if (userData && userData.activeRole) {
                        this.activeRole = userData.activeRole.code;
                        console.log('Sidebar: Active role detected:', this.activeRole);
                        this.loadCompanyRequestsCount(); // Load counts only after role is detected
                    }
                }

                // Check if user has multiple roles
                this.checkMultipleRoles();

                // If no role found and we haven't retried too many times, wait and retry
                // This handles the race condition where sidebar inits before token injection script finishes
                if (!this.activeRole && attempts < 5) {
                    setTimeout(() => {
                        this.waitForTokenAndDetectRole(attempts + 1);
                    }, 100);
                } else if (!this.activeRole) {
                    console.warn('Sidebar: No active role found in JWT after retries');
                }
            },

            checkMultipleRoles() {
                const token = localStorage.getItem('access_token');
                if (!token) return;

                try {
                    const payload = JSON.parse(atob(token.split('.')[1]));
                    this.hasMultipleRoles = payload.roles && payload.roles.length > 1;
                    console.log('[Sidebar] Has multiple roles:', this.hasMultipleRoles);
                } catch (e) {
                    console.error('[Sidebar] Error checking multiple roles:', e);
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
            },

            loadCompanyRequestsCount() {
                // Only load for PLATFORM_ADMIN
                if (this.activeRole !== 'PLATFORM_ADMIN') return;

                // Load company requests count from API - only PENDING requests
                const token = localStorage.getItem('access_token');
                if (!token) return;

                fetch('/api/company-requests', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch');
                    return response.json();
                })
                .then(data => {
                    // Handle both { data: [...] } and { items: [...] } formats
                    const requests = data.data || data.items || [];

                    // Count only PENDING requests (case-insensitive)
                    const pendingCount = Array.isArray(requests)
                        ? requests.filter(req => (req.status || '').toUpperCase() === 'PENDING').length
                        : 0;

                    const badge = document.getElementById('companyRequestsBadge');
                    if (badge) {
                        badge.textContent = pendingCount;
                    }
                    console.log('Pending company requests count updated:', pendingCount);
                })
                .catch(error => {
                    console.error('Error loading company requests count:', error);
                    // Keep default 0 value on error
                });
            },

            async testRefresh() {
                try {
                    Swal.fire({
                        title: 'Refreshing Token...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Force refresh
                    await window.tokenManager.refresh();

                    // Success
                    await Swal.fire({
                        icon: 'success',
                        title: 'Refresh Successful!',
                        text: 'Token refreshed and cookie updated. Reloading page to verify...',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reload to verify auth persistence
                    window.location.reload();

                } catch (error) {
                    console.error('Test Refresh Failed:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Refresh Failed',
                        text: error.message || 'Unknown error occurred'
                    });
                }
            }
        };
    }
</script>
