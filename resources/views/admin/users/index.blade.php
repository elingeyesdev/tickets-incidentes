@extends('layouts.app')

@section('title', 'Gestión de Usuarios')
@section('header', 'Gestión de Usuarios')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Lista de Usuarios</h5>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-plus me-2"></i> Nuevo Usuario
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="usersTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Roles</th>
                        <th>Verificado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin me-2"></i> Cargando usuarios...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUserForm">
                <div class="modal-body">
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-info-circle me-2"></i> Los usuarios se crean mediante registro. Este formulario es solo para administradores.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <div class="mb-3">
                        <label for="firstName" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                    </div>

                    <div class="mb-3">
                        <label for="lastName" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div id="formErrors"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" id="editUserId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="editStatus" required>
                            <option value="ACTIVE">Activo</option>
                            <option value="INACTIVE">Inactivo</option>
                            <option value="SUSPENDED">Suspendido</option>
                        </select>
                    </div>

                    <div id="editFormErrors"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let usersData = [];

document.addEventListener('DOMContentLoaded', async function() {
    await loadUsers();
    setupEventListeners();
});

async function loadUsers() {
    try {
        const response = await apiRequest('/users');
        usersData = response.data || response;

        const tbody = document.querySelector('#usersTable tbody');
        if (!Array.isArray(usersData) || usersData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox me-2"></i> No hay usuarios registrados
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = usersData.map(user => `
            <tr>
                <td><code>${user.userCode}</code></td>
                <td>
                    <strong>${user.displayName || (user.profile?.firstName + ' ' + user.profile?.lastName)}</strong>
                </td>
                <td>${user.email}</td>
                <td>
                    <span class="badge bg-${getStatusColor(user.status)}">
                        ${getStatusLabel(user.status)}
                    </span>
                </td>
                <td>
                    <small>
                        ${user.userRoles?.map(r => `<span class="badge bg-secondary">${r.role?.name || 'Sin rol'}</span>`).join(' ') || 'Sin roles'}
                    </small>
                </td>
                <td>
                    <i class="fas fa-${user.emailVerified ? 'check-circle text-success' : 'times-circle text-danger'}"></i>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="openEditModal('${user.id}', '${user.status}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser('${user.id}', '${user.displayName}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error('Error cargando usuarios:', error);
        showError('Error al cargar los usuarios: ' + error.message);
    }
}

function setupEventListeners() {
    document.getElementById('editUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const userId = document.getElementById('editUserId').value;
        const status = document.getElementById('editStatus').value;

        try {
            await apiRequest(`/users/${userId}/status`, 'PUT', { status });
            showSuccess('Usuario actualizado correctamente');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            await loadUsers();
        } catch (error) {
            document.getElementById('editFormErrors').innerHTML = `
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

function openEditModal(userId, status) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editStatus').value = status;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

async function deleteUser(userId, userName) {
    if (!confirm(`¿Está seguro que desea eliminar a ${userName}?`)) {
        return;
    }

    try {
        await apiRequest(`/users/${userId}`, 'DELETE');
        showSuccess('Usuario eliminado correctamente');
        await loadUsers();
    } catch (error) {
        showError('Error al eliminar usuario: ' + error.message);
    }
}
</script>
@endsection
