@extends('layouts.public')

@section('title', 'Selecciona tu Rol - Helpdesk')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i> Selecciona tu Rol
                    </h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted text-center mb-4">
                        Tienes acceso a múltiples roles. Selecciona con cuál deseas comenzar.
                    </p>

                    <div id="rolesContainer">
                        <!-- Roles will be loaded by JavaScript -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="text-muted mt-2">Cargando roles disponibles...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .role-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        border-radius: 8px;
    }

    .role-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        transform: translateY(-2px);
    }

    .role-card.active {
        border-color: #007bff;
        background-color: #f0f7ff;
    }

    .role-icon {
        font-size: 2.5rem;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        margin: 0 auto 1rem;
    }

    .role-icon.admin {
        background-color: #cfe2ff;
        color: #0c63e4;
    }

    .role-icon.company {
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
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .role-description {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .btn-select-role {
        width: 100%;
        padding: 1rem;
        border: none;
        text-align: left;
        margin-bottom: 1rem;
    }

    .btn-select-role:focus {
        outline: none;
    }
</style>

@endsection

@section('scripts')
<script src="{{ asset('js/lib/auth/TokenManager.js') }}"></script>
<script>
/**
 * Decode JWT payload (helper function)
 */
function decodeJWT(token) {
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
}

/**
 * Format role code to display name
 */
function formatRoleName(roleCode) {
    const names = {
        'PLATFORM_ADMIN': 'Administrador Global',
        'COMPANY_ADMIN': 'Administrador de Empresa',
        'AGENT': 'Agente de Soporte',
        'USER': 'Usuario Final'
    };
    return names[roleCode] || roleCode;
}

/**
 * Get icon class for role
 */
function getRoleIconClass(roleCode) {
    const icons = {
        'PLATFORM_ADMIN': 'fas fa-crown admin',
        'COMPANY_ADMIN': 'fas fa-building company',
        'AGENT': 'fas fa-headset agent',
        'USER': 'fas fa-user user'
    };
    return icons[roleCode] || 'fas fa-user';
}

/**
 * Get description for role
 */
function getRoleDescription(roleCode) {
    const descriptions = {
        'PLATFORM_ADMIN': 'Acceso total al sistema. Gestiona usuarios, empresas y solicitudes.',
        'COMPANY_ADMIN': 'Administra tu empresa. Gestiona agentes, categorías y análisis.',
        'AGENT': 'Soporte al cliente. Gestiona tickets asignados a ti.',
        'USER': 'Cliente. Crea y gestiona tus propios tickets.'
    };
    return descriptions[roleCode] || 'Acceso a las funcionalidades de este rol.';
}

/**
 * Load available roles from JWT
 */
async function loadRoles() {
    try {
        // Get JWT from localStorage
        const accessToken = localStorage.getItem('access_token');

        if (!accessToken) {
            window.location.href = '/login';
            return;
        }

        // Decode JWT to get roles
        const payload = decodeJWT(accessToken);

        if (!payload || !payload.roles || payload.roles.length === 0) {
            console.error('No roles found in JWT');
            document.getElementById('rolesContainer').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error: No se encontraron roles. Por favor, intenta loguear nuevamente.
                </div>
            `;
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
            return;
        }

        // Render roles
        const rolesHtml = payload.roles.map((role, index) => `
            <div class="role-card btn btn-light" onclick="selectRole('${role.code}', '${role.company_id || 'null'}')">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <div class="role-icon ${role.code.toLowerCase()}">
                            <i class="${getRoleIconClass(role.code)}"></i>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="role-title">${formatRoleName(role.code)}</div>
                        <div class="role-description">${getRoleDescription(role.code)}</div>
                        ${role.company_name ? `<small class="text-muted"><i class="fas fa-building me-1"></i>${role.company_name}</small>` : ''}
                    </div>
                    <div class="col-md-3 text-end">
                        <i class="fas fa-arrow-right text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        `).join('');

        document.getElementById('rolesContainer').innerHTML = rolesHtml;
    } catch (error) {
        console.error('Error loading roles:', error);
        document.getElementById('rolesContainer').innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                Error al cargar los roles. Por favor, recarga la página.
            </div>
        `;
    }
}

/**
 * Map role code to dashboard URL
 */
function getDashboardUrl(roleCode) {
    const dashboardMap = {
        'PLATFORM_ADMIN': '/app/admin/dashboard',
        'COMPANY_ADMIN': '/app/company/dashboard',
        'AGENT': '/app/agent/dashboard',
        'USER': '/app/user/dashboard'
    };
    return dashboardMap[roleCode] || '/app/dashboard';
}

/**
 * Select a role and redirect to dashboard
 */
async function selectRole(roleCode, companyId) {
    try {
        const loadingSpinner = document.createElement('div');
        loadingSpinner.className = 'position-fixed top-50 start-50 translate-middle';
        loadingSpinner.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Procesando...</span>
            </div>
        `;
        document.body.appendChild(loadingSpinner);

        // Get current access token
        const accessToken = localStorage.getItem('access_token');

        // Decodificar JWT para obtener datos del rol
        const payload = decodeJWT(accessToken);
        const roles = payload.roles || [];

        // Buscar el rol seleccionado en los roles disponibles
        const selectedRole = roles.find(role =>
            role.code === roleCode &&
            (role.company_id === null && companyId === 'null' || role.company_id === companyId)
        );

        if (!selectedRole) {
            throw new Error('Role not found in your available roles');
        }

        // Guardar activeRole en localStorage
        const activeRole = {
            code: selectedRole.code,
            company_id: selectedRole.company_id || null,
            company_name: selectedRole.company_name || null
        };
        localStorage.setItem('active_role', JSON.stringify(activeRole));

        // Ir directo al dashboard del rol seleccionado
        const dashboardUrl = getDashboardUrl(roleCode);
        window.location.href = dashboardUrl;

    } catch (error) {
        console.error('Error selecting role:', error);
        alert('Error al seleccionar el rol: ' + error.message);
    }
}

// Load roles when page loads
document.addEventListener('DOMContentLoaded', loadRoles);
</script>
@endsection
