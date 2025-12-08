@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_header', 'Selecciona tu Rol')

@section('auth_body')
<div x-data="roleSelector()" x-init="init()">
    <p class="text-muted text-center mb-4">
        Tienes acceso a múltiples roles. Selecciona con cuál deseas comenzar.
    </p>

    <!-- Loading State -->
    <div x-show="loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
        <p class="text-muted mt-3 mb-0">Cargando roles disponibles...</p>
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
    <div x-show="!loading && !error && roles.length > 0" id="rolesContainer">
        <template x-for="role in roles" :key="role.code + '-' + (role.company_id || 'null')">
            <button
                type="button"
                class="role-card btn btn-light w-100 text-start"
                :disabled="loading"
                @click="selectRole(role.code, role.company_id)"
            >
                <!-- Role Icon -->
                <div class="role-icon" :class="role.code.toLowerCase()">
                    <i :class="getRoleIconClass(role.code)"></i>
                </div>
                
                <!-- Role Info -->
                <div class="role-info">
                    <div class="role-title" x-text="formatRoleName(role.code)"></div>
                    <div class="role-description" x-text="getRoleDescription(role.code)"></div>
                    <template x-if="role.company_name">
                        <div class="role-company">
                            <i class="fas fa-building"></i>
                            <span x-text="role.company_name"></span>
                        </div>
                    </template>
                </div>
                
                <!-- Arrow -->
                <div class="role-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </button>
        </template>
    </div>
    
    <!-- Back to Login Link -->
    <div x-show="!loading" class="text-center mt-4">
        <a href="/login" class="text-muted small" @click="localStorage.clear()">
            <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
        </a>
    </div>
</div>

<style>
    /* 
     * Role Selector specific styles - OVERRIDE login/register card size
     * Using body class selector to ensure specificity without affecting other pages
     */
    
    /* Make the role selector card wider */
    body.login-page .login-box,
    body.login-page .card {
        max-width: 550px !important;
        width: 100% !important;
    }

    body.login-page .card-body {
        padding: 2rem !important;
    }

    body.login-page {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    #rolesContainer {
        display: flex !important;
        flex-direction: column;
        gap: 0.75rem;
    }

    .role-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        min-height: auto;
        padding: 1rem 1.25rem !important;
        display: flex !important;
        align-items: center;
        background: #fff;
    }

    .role-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.25);
        transform: translateY(-2px);
        background: #f8f9ff;
    }

    .role-card:disabled {
        opacity: 0.7;
        cursor: wait;
    }

    .role-card .row {
        width: 100%;
        align-items: center;
        margin: 0;
    }

    .role-icon {
        font-size: 1.5rem;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        flex-shrink: 0;
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

    .role-info {
        flex: 1;
        min-width: 0;
        padding: 0 1rem;
    }

    .role-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.15rem;
        color: #212529;
    }

    .role-description {
        font-size: 0.8rem;
        color: #6c757d;
        line-height: 1.3;
        margin-bottom: 0;
    }

    .role-company {
        font-size: 0.75rem;
        color: #0d6efd;
        margin-top: 0.25rem;
    }

    .role-company i {
        margin-right: 0.25rem;
    }

    .role-arrow {
        color: #007bff;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        body.login-page .login-box,
        body.login-page .card {
            max-width: 95% !important;
            margin: 0 auto;
        }
        
        .role-icon {
            width: 40px;
            height: 40px;
            font-size: 1.25rem;
        }
        
        .role-info {
            padding: 0 0.75rem;
        }
        
        .role-title {
            font-size: 0.9rem;
        }
        
        .role-description {
            font-size: 0.75rem;
        }
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
             * 
             * IMPORTANT: This calls the API to get a NEW JWT with the active_role claim.
             * The backend needs the active_role in the JWT to enforce policies correctly.
             */
            async selectRole(roleCode, companyId) {
                try {
                    this.loading = true;
                    this.error = false;

                    // Get current access token
                    const accessToken = localStorage.getItem('access_token');

                    if (!accessToken) {
                        window.location.href = '/login';
                        return;
                    }

                    // Call API to select role and get NEW JWT with active_role claim
                    const response = await fetch('/api/auth/select-role', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${accessToken}`
                        },
                        body: JSON.stringify({
                            role_code: roleCode,
                            company_id: companyId || null
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Error al seleccionar el rol');
                    }

                    // Save NEW JWT with active_role claim to localStorage
                    const newAccessToken = data.data.access_token;
                    localStorage.setItem('access_token', newAccessToken);

                    // Save active role to localStorage for quick access
                    const activeRole = data.data.active_role;
                    localStorage.setItem('active_role', JSON.stringify(activeRole));

                    console.log('[RoleSelector] Role selected successfully:', activeRole);

                    // Redirect through prepare-web to set cookie with NEW token
                    const dashboardUrl = this.getDashboardUrl(roleCode);
                    window.location.href = `/auth/prepare-web?token=${newAccessToken}&redirect=${encodeURIComponent(dashboardUrl)}`;

                } catch (error) {
                    console.error('Error selecting role:', error);
                    this.errorMessage = 'Error al seleccionar el rol: ' + error.message;
                    this.error = true;
                    this.loading = false;
                }
            }
        };
    }
</script>
@stop
