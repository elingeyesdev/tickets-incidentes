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
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">3</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">3 Notifications</span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-envelope mr-2"></i> 4 new messages
                    <span class="float-right text-muted text-sm">3 mins</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-users mr-2"></i> 8 friend requests
                    <span class="float-right text-muted text-sm">12 hours</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-file mr-2"></i> 3 new reports
                    <span class="float-right text-muted text-sm">2 days</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
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
                    <i class="fas fa-user mr-2"></i> My Profile
                </a>
                <a href="/settings" class="dropdown-item">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" @click.prevent="logout()" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
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

            init() {
                this.updateRoleDisplay();
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

            init() {
                this.loadUserData();
            },

            loadUserData() {
                if (typeof getUserFromJWT === 'function') {
                    const userData = getUserFromJWT();
                    if (userData) {
                        this.userName = userData.name || 'User';
                        this.userEmail = userData.email || '';
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
