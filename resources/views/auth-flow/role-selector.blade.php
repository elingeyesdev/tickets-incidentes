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
        <p class="text-muted mt-3 mb-0" x-text="loadingMessage"></p>
    </div>

    <!-- Error State -->
    <div x-show="error && !loading" class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <span x-text="errorMessage"></span>
        <button type="button" class="close" @click="error = false" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <!-- Roles Container - Inspired by view-user-modal with original animations -->
    <div x-show="!loading && !error && roles.length > 0" id="rolesContainer">
        <template x-for="role in roles" :key="role.code + '-' + (role.company_id || 'null')">
            <div class="role-card"
                 @click="selectRole(role.code, role.company_id)"
                 :class="{'role-card-disabled': loading}">
                
                <!-- Icon Box (64x64) or Company Logo - AdminLTE colors -->
                <div class="role-icon-wrapper">
                    <template x-if="role.logo_url">
                        <img :src="role.logo_url" :alt="role.company_name + ' logo'" class="company-logo">
                    </template>
                    <template x-if="!role.logo_url">
                        <div class="role-icon" :class="'bg-' + getRoleColor(role.code)">
                            <i :class="getRoleIconClass(role.code) + ' text-white'"></i>
                        </div>
                    </template>
                </div>
                
                <!-- Role Info - Compact layout -->
                <div class="role-info">
                    <h5 class="role-title mb-0">
                        <span class="role-label">ROL</span> — 
                        <span x-text="formatRoleName(role.code)"></span>
                    </h5>
                    <div class="role-meta">
                        <code class="role-code" :class="'text-' + getRoleColor(role.code)" x-text="role.code"></code>
                        <template x-if="role.company_name">
                            <span class="role-company-inline">
                                <i class="fas fa-building"></i> 
                                <span x-text="role.company_name"></span>
                            </span>
                        </template>
                        <template x-if="role.industry_name">
                            <span class="role-industry">
                                <i class="fas fa-industry"></i> 
                                <span x-text="role.industry_name"></span>
                            </span>
                        </template>
                    </div>
                    <p class="role-description" x-text="getRoleDescription(role.code)"></p>
                </div>
                
                <!-- Arrow -->
                <div class="role-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
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
     * Role Selector - Combining view-user-modal layout with original animations
     * Professional design with smooth transitions
     */
    
    /* Make the role selector card wider */
    body.login-page .login-box,
    body.login-page .card {
        max-width: 580px !important;
        width: 100% !important;
    }

    body.login-page .card-body {
        padding: 2rem !important;
    }

    #rolesContainer {
        display: flex !important;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Role card - original animations + view-user-modal structure */
    .role-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        background: #fff;
    }

    .role-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.25);
        transform: translateY(-2px);
        background: #f8f9ff;
    }

    .role-card:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
    }

    .role-card.role-card-disabled {
        opacity: 0.7;
        cursor: wait;
        pointer-events: none;
    }

    /* Role icon - 64x64 like view-user-modal */
    .role-icon-wrapper {
        width: 64px;
        height: 64px;
        flex-shrink: 0;
    }

    .company-logo {
        width: 64px;
        height: 64px;
        object-fit: contain;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #dee2e6;
        padding: 6px;
    }

    .role-icon {
        font-size: 1.5rem;
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        transition: transform 0.2s ease;
    }

    .role-card:hover .role-icon {
        transform: scale(1.05);
    }

    /* Role info section */
    .role-info {
        flex: 1;
        min-width: 0;
        padding: 0 1rem;
    }

    .role-header {
        margin-bottom: 0.25rem;
    }

    .role-title {
        font-size: 1rem;
        font-weight: 600;
        color: #212529;
    }

    .role-label {
        font-weight: 400;
        color: #6c757d;
        font-size: 0.85rem;
    }

    .role-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.25rem;
    }

    .role-code {
        font-size: 0.75rem;
        font-weight: 500;
    }

    .role-company-inline {
        font-size: 0.75rem;
        color: #0d6efd;
        font-weight: 500;
    }

    .role-company-inline i {
        margin-right: 0.15rem;
    }

    .role-industry {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .role-description {
        font-size: 0.8rem;
        color: #6c757d;
        line-height: 1.3;
        margin: 0.35rem 0 0 0;
    }

    /* Arrow indicator */
    .role-arrow {
        color: #007bff;
        font-size: 1.25rem;
        flex-shrink: 0;
        opacity: 0.5;
        transition: all 0.3s ease;
    }

    .role-card:hover .role-arrow {
        opacity: 1;
        transform: translateX(3px);
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        body.login-page .login-box,
        body.login-page .card {
            max-width: 95% !important;
            margin: 0 auto;
        }
        
        .role-icon-wrapper,
        .company-logo,
        .role-icon {
            width: 50px;
            height: 50px;
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
            loadingMessage: 'Cargando roles disponibles...',
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
                    'COMPANY_ADMIN': 'fas fa-user-tie',
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
             * Get AdminLTE color class for role (matching view-user-modal)
             */
            getRoleColor(roleCode) {
                const colors = {
                    'PLATFORM_ADMIN': 'danger',
                    'COMPANY_ADMIN': 'warning',
                    'AGENT': 'info',
                    'USER': 'primary'
                };
                return colors[roleCode] || 'secondary';
            },

            /**
             * Load available roles from API (enriched with company data)
             * Falls back to JWT decoding if API fails
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

                    // Decode JWT as fallback (in case API fails)
                    const payload = this.decodeJWT(accessToken);

                    if (!payload || !payload.roles || payload.roles.length === 0) {
                        this.errorMessage = 'No se encontraron roles. Por favor, intenta loguear nuevamente.';
                        this.error = true;
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);
                        return;
                    }

                    // Set basic roles from JWT (fallback)
                    this.roles = payload.roles;

                    // Try to enrich with API data (logo, industry, etc.)
                    try {
                        const response = await fetch('/api/auth/available-roles', {
                            method: 'GET',
                            headers: {
                                'Authorization': `Bearer ${accessToken}`,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            if (data.success && data.data) {
                                // Replace with enriched data (includes logo_url, industry_name, etc.)
                                this.roles = data.data;
                                console.log('[Role Selector] Roles enriched with company data');
                            }
                        } else {
                            console.warn('[Role Selector] Could not enrich roles, using basic JWT data');
                        }
                    } catch (apiError) {
                        console.warn('[Role Selector] API enrichment failed, using basic JWT data:', apiError);
                        // Continue with JWT data (already set above)
                    }

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
                    this.loadingMessage = 'Enviando petición a Helpdesk API...';

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

                    this.loadingMessage = 'Cambiando de rol...';

                    // Save NEW JWT with active_role claim to localStorage
                    const newAccessToken = data.data.access_token;
                    localStorage.setItem('access_token', newAccessToken);

                    // Save active role to localStorage for quick access
                    const activeRole = data.data.active_role;
                    localStorage.setItem('active_role', JSON.stringify(activeRole));

                    console.log('[RoleSelector] Role selected successfully:', activeRole);

                    this.loadingMessage = 'Redirigiendo al dashboard...';

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
