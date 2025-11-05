@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('header', 'Mi Perfil')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div id="profileAvatar" class="mb-3">
                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                         style="width: 120px; height: 120px; color: white; font-size: 3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <h4 id="profileName">Cargando...</h4>
                <p class="text-muted" id="profileEmail"></p>
                <hr>
                <p class="mb-0">
                    <strong>Código:</strong> <code id="profileCode"></code>
                </p>
                <p class="mb-0 mt-2">
                    <strong>Estado:</strong> <span id="profileStatus" class="badge"></span>
                </p>
                <p class="mb-0 mt-2">
                    <strong>Roles:</strong><br>
                    <div id="profileRoles"></div>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Editar Información</h5>
            </div>
            <div class="card-body">
                <div id="profileFormErrors"></div>
                <form id="profileForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" disabled>
                        <small class="text-muted">El email no puede ser modificado</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phoneNumber" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber">
                        </div>
                        <div class="col-md-6">
                            <label for="language" class="form-label">Idioma</label>
                            <select class="form-select" id="language" name="language">
                                <option value="es">Español</option>
                                <option value="en">Inglés</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="theme" class="form-label">Tema</label>
                            <select class="form-select" id="theme" name="theme">
                                <option value="light">Claro</option>
                                <option value="dark">Oscuro</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i> Descartar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Seguridad</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key me-2"></i> Cambiar Contraseña
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div id="passwordErrors"></div>

                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                    </div>

                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required minlength="8">
                        <small class="text-muted">Mínimo 8 caracteres</small>
                    </div>

                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Cambiar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    await loadProfileData();
    setupEventListeners();
});

async function loadProfileData() {
    try {
        const response = await apiRequest('/auth/status');
        const user = response.user;

        // Llenar datos del perfil
        document.getElementById('profileName').textContent = user.displayName;
        document.getElementById('profileEmail').textContent = user.email;
        document.getElementById('profileCode').textContent = user.userCode;
        document.getElementById('email').value = user.email;

        // Status badge
        const statusBadge = document.getElementById('profileStatus');
        statusBadge.className = `badge bg-${getStatusColor(user.status)}`;
        statusBadge.textContent = getStatusLabel(user.status);

        // Roles
        const rolesDiv = document.getElementById('profileRoles');
        rolesDiv.innerHTML = user.roleContexts
            .map(r => `<span class="badge bg-info">${r.roleName}</span>`)
            .join(' ');

        // Llenar formulario
        if (user.profile) {
            document.getElementById('firstName').value = user.profile.firstName || '';
            document.getElementById('lastName').value = user.profile.lastName || '';
            document.getElementById('phoneNumber').value = user.profile.phoneNumber || '';
        }

        document.getElementById('language').value = user.language || 'es';
        document.getElementById('theme').value = user.theme || 'light';

    } catch (error) {
        console.error('Error cargando perfil:', error);
        showError('Error al cargar los datos del perfil');
    }
}

function setupEventListeners() {
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = {
            firstName: document.getElementById('firstName').value,
            lastName: document.getElementById('lastName').value,
            phoneNumber: document.getElementById('phoneNumber').value || null,
            language: document.getElementById('language').value,
            theme: document.getElementById('theme').value,
        };

        try {
            await apiRequest('/users/me/profile', 'PATCH', formData);
            showSuccess('Perfil actualizado correctamente');
            await loadProfileData();
        } catch (error) {
            document.getElementById('profileFormErrors').innerHTML = `
                <div class="alert alert-danger">${error.message}</div>
            `;
        }
    });

    document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            document.getElementById('passwordErrors').innerHTML = `
                <div class="alert alert-danger">Las contraseñas no coinciden</div>
            `;
            return;
        }

        try {
            // Nota: Necesitarías un endpoint específico para cambiar contraseña
            // Por ahora es un placeholder
            document.getElementById('passwordErrors').innerHTML = `
                <div class="alert alert-warning">
                    El cambio de contraseña aún no está implementado en esta interfaz.
                </div>
            `;
        } catch (error) {
            document.getElementById('passwordErrors').innerHTML = `
                <div class="alert alert-danger">${error.message}</div>
            `;
        }
    });
}

function getStatusColor(status) {
    const colors = {
        'ACTIVE': 'success',
        'INACTIVE': 'secondary',
        'SUSPENDED': 'danger'
    };
    return colors[status] || 'secondary';
}

function getStatusLabel(status) {
    const labels = {
        'ACTIVE': 'Activo',
        'INACTIVE': 'Inactivo',
        'SUSPENDED': 'Suspendido'
    };
    return labels[status] || status;
}
</script>
@endsection
