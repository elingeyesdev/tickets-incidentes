@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Selecciona tu Rol')

@section('auth_body')
<div x-data="roleSelector()" x-init="init()">
    <p class="text-muted text-center mb-4">
        Tienes acceso a múltiples roles. Selecciona con cuál deseas comenzar.
    </p>

    <!-- Loading State -->
    <div x-show="loading" class="text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="text-muted mt-2">Cargando roles disponibles...</p>
    </div>

    <!-- Error State -->
    <div x-show="error && !loading" class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <span x-text="errorMessage"></span>
        <button type="button" class="close" @click="error = false" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <!-- Roles Container -->
    <div x-show="!loading && !error && roles.length > 0" id="rolesContainer" style="display: none;">
        <template x-for="role in roles" :key="role.code">
            <button
                type="button"
                class="role-card btn btn-light w-100 text-start p-4 mb-3"
                @click="selectRole(role.code, role.company_id)"
            >
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <div class="role-icon" :class="role.code.toLowerCase()">
                            <i :class="getRoleIconClass(role.code)"></i>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="role-title" x-text="formatRoleName(role.code)"></div>
                        <div class="role-description" x-text="getRoleDescription(role.code)"></div>
                        <template x-if="role.company_name">
                            <small class="text-muted d-block">
                                <i class="fas fa-building me-1"></i>
                                <span x-text="role.company_name"></span>
                            </small>
                        </template>
                    </div>
                    <div class="col-md-3 text-end">
                        <i class="fas fa-arrow-right text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </button>
        </template>
    </div>
</div>

<style>
    /* Ensanchar el card del auth */
    body .card {
        max-width: 900px !important;
        width: 90vw !important;
    }

    body .card-body {
        padding: 2.5rem !important;
    }

    body .login-page {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    body .login-box {
        width: 100%;
        max-width: 900px !important;
    }

    .role-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        min-height: 140px;
    }

    .role-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        transform: translateY(-2px);
    }

    .role-card.active {
        border-color: #007bff;
        background-color: #f0f7ff !important;
    }

    .role-icon {
        font-size: 3rem;
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        margin: 0 auto;
    }

    .role-icon.platform_admin {
        background-color: #cfe2ff;
        color: #0c63e4;
    }

    .role-icon.company_admin {
        background-color: #f8d7da;
        color: #842029;
    }

    .role-icon.agent {
        background-color: #d1e7dd;
        color: #0f5132;
    }

    .role-icon.user {
        background-color: #fff3cd;
        color: #664d03;
    }

    .role-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .role-description {
        font-size: 1rem;
        color: #6c757d;
        line-height: 1.5;
    }
</style>
@stop

@section('adminlte_js')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function roleSelector() {
        return {
            loading: true,
            error: false,
            errorMessage: '',
            roles: [],

            /**
             * Initialize component
             */
            async init() {
                await this.loadRoles();
            },

            /**
             * Decode JWT payload
             */
            decodeJWT(token) {
                try {
                    const base64Url = token.split('.')[1];
                    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                    const jsonPayload = decodeURIComponent(atob(base64).split('').map(function (c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));
                    return JSON.parse(jsonPayload);
                } catch (error) {
                    console.error('Failed to decode JWT:', error);
                    return null;
                }
            },

            /**
             * Format role code to display name
             */
            formatRoleName(roleCode) {
                const names = {
                    'PLATFORM_ADMIN': 'Administrador Global',
                    'COMPANY_ADMIN': 'Administrador de Empresa',
                    'AGENT': 'Agente de Soporte',
                    'USER': 'Usuario Final'
                };
                return names[roleCode] || roleCode;
            },

            /**
             * Get icon class for role
             */
            getRoleIconClass(roleCode) {
                const icons = {
                    'PLATFORM_ADMIN': 'fas fa-crown',
                    'COMPANY_ADMIN': 'fas fa-building',
                    'AGENT': 'fas fa-headset',
                    'USER': 'fas fa-user'
                };
                return icons[roleCode] || 'fas fa-user';
            },

            /**
             * Get description for role
             */
            getRoleDescription(roleCode) {
                const descriptions = {
                    'PLATFORM_ADMIN': 'Acceso total al sistema. Gestiona usuarios, empresas y solicitudes.',
                    'COMPANY_ADMIN': 'Administra tu empresa. Gestiona agentes, categorías y análisis.',
                    'AGENT': 'Soporte al cliente. Gestiona tickets asignados a ti.',
                    'USER': 'Cliente. Crea y gestiona tus propios tickets.'
                };
                return descriptions[roleCode] || 'Acceso a las funcionalidades de este rol.';
            },

            /**
             * Load available roles from JWT
             */
            async loadRoles() {
                try {
                    this.loading = true;
                    this.error = false;

                    // Get JWT from localStorage
                    const accessToken = localStorage.getItem('access_token');

                    if (!accessToken) {
                        window.location.href = '/login';
                        return;
                    }

                    // Decode JWT to get roles
                    const payload = this.decodeJWT(accessToken);

                    if (!payload || !payload.roles || payload.roles.length === 0) {
                        this.errorMessage = 'No se encontraron roles. Por favor, intenta loguear nuevamente.';
                        this.error = true;
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);
                        return;
                    }

                    this.roles = payload.roles;
                    this.loading = false;

                } catch (error) {
                    console.error('Error loading roles:', error);
                    this.errorMessage = 'Error al cargar los roles. Por favor, recarga la página.';
                    this.error = true;
                    this.loading = false;
                }
            },

            /**
             * Map role code to dashboard URL
             */
            getDashboardUrl(roleCode) {
                const dashboardMap = {
                    'PLATFORM_ADMIN': '/app/admin/dashboard',
                    'COMPANY_ADMIN': '/app/company/dashboard',
                    'AGENT': '/app/agent/dashboard',
                    'USER': '/app/user/dashboard'
                };
                return dashboardMap[roleCode] || '/app/dashboard';
            },

            /**
             * Select a role and redirect to dashboard
             */
            async selectRole(roleCode, companyId) {
                try {
                    // Get current access token
                    const accessToken = localStorage.getItem('access_token');

                    // Decode JWT to get role data
                    const payload = this.decodeJWT(accessToken);
                    const roles = payload.roles || [];

                    // Find the selected role in available roles
                    const selectedRole = roles.find(role => {
                        const sameCode = role.code === roleCode;
                        const sameCompany = (role.company_id === null && companyId === null) ||
                                           (role.company_id === companyId);
                        return sameCode && sameCompany;
                    });

                    if (!selectedRole) {
                        throw new Error('Role not found in your available roles');
                    }

                    // Save active role to localStorage
                    const activeRole = {
                        code: selectedRole.code,
                        company_id: selectedRole.company_id || null,
                        company_name: selectedRole.company_name || null
                    };
                    localStorage.setItem('active_role', JSON.stringify(activeRole));

                    // Redirect to role dashboard (NOT to prepare-web since token is already in localStorage)
                    const dashboardUrl = this.getDashboardUrl(roleCode);
                    window.location.href = dashboardUrl;

                } catch (error) {
                    console.error('Error selecting role:', error);
                    this.errorMessage = 'Error al seleccionar el rol: ' + error.message;
                    this.error = true;
                }
            }
        };
    }
</script>
@stop
