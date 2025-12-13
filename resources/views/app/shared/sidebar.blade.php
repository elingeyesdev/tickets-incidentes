<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo - AdminLTE Style with Helpdesk Icon -->
    <a href="{{ route('dashboard') }}" class="brand-link">
        {{-- Custom SVG logo matching AdminLTE style: gray circle with bold headset icon --}}
        <span class="brand-image elevation-3 d-inline-flex align-items-center justify-content-center" 
              style="width: 33px; height: 33px; border-radius: 50%; background: linear-gradient(180deg, #e9ecef 0%, #ced4da 100%); opacity: 0.8;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                {{-- Bold Headset Icon - Thicker stroke --}}
                <path d="M12 3C7.03 3 3 7.03 3 12V18C3 19.1 3.9 20 5 20H7C7.55 20 8 19.55 8 19V14C8 13.45 7.55 13 7 13H5V12C5 8.13 8.13 5 12 5C15.87 5 19 8.13 19 12V13H17C16.45 13 16 13.45 16 14V19C16 19.55 16.45 20 17 20H19C20.1 20 21 19.1 21 18V12C21 7.03 16.97 3 12 3Z" 
                      fill="#343a40" stroke="#343a40" stroke-width="2.5"/>
            </svg>
        </span>
        <span class="brand-text font-weight-light">HELPDESK</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar" x-data="sidebarMenu()" x-init="init()">
        <!-- Sidebar user panel (optional) - AdminLTE v3 Official Structure -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img id="sidebar-user-avatar" src="https://ui-avatars.com/api/?name=User&background=6c757d&color=fff&size=160" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="{{ route('app.profile') }}" class="d-block" id="sidebar-user-name">User</a>
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
                        <li class="nav-item">
                            <a href="/app/admin/reports" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>Reportes</p>
                            </a>
                        </li>
                        <li class="nav-header">INTEGRACIONES</li>
                        <li class="nav-item">
                            <a href="/app/admin/api-keys" class="nav-link">
                                <i class="nav-icon fas fa-key"></i>
                                <p>API Keys</p>
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
                        <li class="nav-item">
                            <a href="/app/company/agents" class="nav-link">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p>Agentes</p>
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
                        <li class="nav-header">REPORTES</li>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>
                                    Reportes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/app/company/reports/tickets" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tickets</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/app/company/reports/agents" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Agentes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/app/company/reports/summary" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Resumen Operativo</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/app/company/reports/company" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Empresa y Equipo</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-header">CONFIGURACIÓN</li>
                        <li class="nav-item">
                            <a href="/app/company/settings" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Configuración</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('app.profile') }}" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Perfil</p>
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
                        <li class="nav-header">REPORTES</li>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>
                                    Mis Reportes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/app/agent/reports/tickets" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Mis Tickets</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/app/agent/reports/performance" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Mi Rendimiento</p>
                                    </a>
                                </li>
                            </ul>
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
                        <li class="nav-header">REPORTES</li>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-file-export"></i>
                                <p>
                                    Mis Reportes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/app/user/reports/tickets" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Historial de Tickets</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/app/user/reports/activity" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Resumen de Actividad</p>
                                    </a>
                                </li>
                            </ul>
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

{{-- jQuery: Load User Profile Data for Sidebar --}}
<script>
(function() {
    'use strict';

    /**
     * Sidebar User Panel Manager
     * Loads user profile data from API and updates sidebar avatar/name
     * Uses sessionStorage for caching to avoid repeated API calls
     */
    const SidebarUserPanel = {
        CACHE_KEY: 'helpdesk_user_profile',
        CACHE_TTL: 5 * 60 * 1000, // 5 minutes in milliseconds

        init: function() {
            // Wait for jQuery to be available
            if (typeof $ === 'undefined') {
                document.addEventListener('DOMContentLoaded', () => this.loadUserProfile());
            } else {
                $(document).ready(() => this.loadUserProfile());
            }
        },

        loadUserProfile: function() {
            const cached = this.getCachedProfile();
            if (cached) {
                this.updateUI(cached);
                return;
            }

            // No cache, fetch from API
            this.fetchFromAPI();
        },

        getCachedProfile: function() {
            try {
                const stored = sessionStorage.getItem(this.CACHE_KEY);
                if (!stored) return null;

                const data = JSON.parse(stored);
                const now = Date.now();

                // Check if cache is expired
                if (now - data.timestamp > this.CACHE_TTL) {
                    sessionStorage.removeItem(this.CACHE_KEY);
                    return null;
                }

                return data.profile;
            } catch (e) {
                console.warn('[SidebarUserPanel] Error reading cache:', e);
                return null;
            }
        },

        cacheProfile: function(profile) {
            try {
                sessionStorage.setItem(this.CACHE_KEY, JSON.stringify({
                    profile: profile,
                    timestamp: Date.now()
                }));
            } catch (e) {
                console.warn('[SidebarUserPanel] Error caching profile:', e);
            }
        },

        fetchFromAPI: function() {
            const token = localStorage.getItem('access_token');
            if (!token) {
                console.warn('[SidebarUserPanel] No access token available');
                this.updateUI({ name: 'Usuario', avatarUrl: null });
                return;
            }

            $.ajax({
                url: '/api/users/me',
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                },
                success: (response) => {
                    // Handle both { data: {...} } and direct object response
                    const userData = response.data || response;
                    
                    console.log('[SidebarUserPanel] API Response:', userData);
                    console.log('[SidebarUserPanel] Avatar URL:', userData.avatarUrl);
                    
                    const profile = {
                        name: this.extractName(userData),
                        avatarUrl: userData.avatarUrl || userData.profile?.avatarUrl || null
                    };

                    console.log('[SidebarUserPanel] Profile to cache:', profile);
                    
                    this.cacheProfile(profile);
                    this.updateUI(profile);
                },
                error: (xhr, status, error) => {
                    console.error('[SidebarUserPanel] Error fetching profile:', error);
                    // Try to get name from JWT as fallback
                    this.updateFromJWT();
                }
            });
        },

        extractName: function(userData) {
            // Try different name formats (API returns displayName at root level)
            if (userData.displayName) return userData.displayName;
            if (userData.profile?.displayName) return userData.profile.displayName;
            if (userData.profile?.firstName && userData.profile?.lastName) {
                return userData.profile.firstName + ' ' + userData.profile.lastName;
            }
            if (userData.profile?.firstName) return userData.profile.firstName;
            if (userData.name) return userData.name;
            if (userData.email) return userData.email.split('@')[0];
            return 'Usuario';
        },

        updateFromJWT: function() {
            if (typeof getUserFromJWT === 'function') {
                const jwtData = getUserFromJWT();
                if (jwtData) {
                    this.updateUI({
                        name: jwtData.name || jwtData.email?.split('@')[0] || 'Usuario',
                        avatarUrl: null
                    });
                    return;
                }
            }
            this.updateUI({ name: 'Usuario', avatarUrl: null });
        },

        updateUI: function(profile) {
            const $avatar = $('#sidebar-user-avatar');
            const $name = $('#sidebar-user-name');

            // Update name
            if ($name.length) {
                $name.text(profile.name);
            }

            // Update avatar - only if we have a URL, otherwise keep default
            if (profile.avatarUrl && $avatar.length) {
                $avatar.attr('src', profile.avatarUrl);
            }
        },

        // Public method to force refresh (useful after profile update)
        refresh: function() {
            sessionStorage.removeItem(this.CACHE_KEY);
            this.fetchFromAPI();
        }
    };

    // Initialize
    SidebarUserPanel.init();

    // Expose for external use
    window.SidebarUserPanel = SidebarUserPanel;
})();
</script>
