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
                                    <span class="badge badge-warning right">8</span>
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/admin/settings" class="nav-link">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>System Settings</p>
                            </a>
                        </li>
                    </div>
                </template>

                <!-- Company Admin Menu -->
                <template x-if="activeRole === 'COMPANY_ADMIN'">
                    <div>
                        <li class="nav-header">COMPANY MANAGEMENT</li>
                        <li class="nav-item">
                            <a href="/app/company/settings" class="nav-link">
                                <i class="nav-icon fas fa-building"></i>
                                <p>Company Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/company/agents" class="nav-link">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p>Agents</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/company/categories" class="nav-link">
                                <i class="nav-icon fas fa-tags"></i>
                                <p>Categories</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/company/macros" class="nav-link">
                                <i class="nav-icon fas fa-magic"></i>
                                <p>Macros</p>
                            </a>
                        </li>
                        <li class="nav-header">CONTENT</li>
                        <li class="nav-item">
                            <a href="/app/company/help-center" class="nav-link">
                                <i class="nav-icon fas fa-question-circle"></i>
                                <p>Help Center</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/company/analytics" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Analytics</p>
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
                                <p>
                                    My Tickets
                                    <span class="badge badge-info right">15</span>
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/agent/notes" class="nav-link">
                                <i class="nav-icon fas fa-sticky-note"></i>
                                <p>Internal Notes</p>
                            </a>
                        </li>
                        <li class="nav-header">RESOURCES</li>
                        <li class="nav-item">
                            <a href="/app/agent/help-center" class="nav-link">
                                <i class="nav-icon fas fa-question-circle"></i>
                                <p>Help Center</p>
                            </a>
                        </li>
                    </div>
                </template>

                <!-- User Menu -->
                <template x-if="activeRole === 'USER'">
                    <div>
                        <li class="nav-header">SUPPORT</li>
                        <li class="nav-item">
                            <a href="/app/user/tickets" class="nav-link">
                                <i class="nav-icon fas fa-ticket-alt"></i>
                                <p>
                                    My Tickets
                                    <span class="badge badge-primary right">3</span>
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/user/profile" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>My Profile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/app/user/help-center" class="nav-link">
                                <i class="nav-icon fas fa-question-circle"></i>
                                <p>Help Center</p>
                            </a>
                        </li>
                    </div>
                </template>

                <!-- Logout (All roles) -->
                <li class="nav-header">ACCOUNT</li>
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

            init() {
                this.loadUserData();
                this.detectActiveRole();
            },

            loadUserData() {
                if (typeof getUserFromJWT === 'function') {
                    const userData = getUserFromJWT();
                    if (userData) {
                        this.userName = userData.name || 'User';
                    }
                }
            },

            detectActiveRole() {
                if (typeof getUserFromJWT === 'function') {
                    const userData = getUserFromJWT();
                    if (userData && userData.activeRole) {
                        this.activeRole = userData.activeRole.code;
                        console.log('Sidebar: Active role detected:', this.activeRole);
                    } else {
                        console.warn('Sidebar: No active role found in JWT');
                        this.activeRole = null;
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
</script>
