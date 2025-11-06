@extends('layouts.app')

@section('title', 'Usuarios')
@section('header', 'Gestión de Usuarios')
@section('breadcrumb', 'Usuarios')

@section('content')
<!-- Header Section -->
<div class="row mb-3">
    <div class="col-md-6">
        <h5 class="mb-0">Lista de Usuarios</h5>
    </div>
    <div class="col-md-6 text-right">
        <button class="btn btn-primary" data-toggle="modal" data-target="#createUserModal">
            <i class="fas fa-plus mr-2"></i> Nuevo Usuario
        </button>
    </div>
</div>

<!-- Main Table Card -->
<div class="card card-primary card-outline">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped" id="usersTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Roles</th>
                        <th>Verificado</th>
                        <th width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Cargando usuarios...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="card card-primary mb-0">
                <div class="card-header">
                    <h3 class="card-title">Crear Nuevo Usuario</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="createUserForm">
                    <div class="card-body">
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-info-circle"></i> Información</h5>
                            Los usuarios se crean mediante registro. Este formulario es solo para administradores.
                        </div>

                        <div class="form-group">
                            <label for="firstName">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Ingrese el nombre" required>
                        </div>

                        <div class="form-group">
                            <label for="lastName">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Ingrese el apellido" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="correo@ejemplo.com" required>
                        </div>

                        <div id="formErrors"></div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fas fa-times mr-2"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save mr-2"></i> Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="card card-primary mb-0">
                <div class="card-header">
                    <h3 class="card-title">Editar Usuario</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editUserForm">
                    <input type="hidden" id="editUserId">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="editStatus">Estado <span class="text-danger">*</span></label>
                            <select class="form-control" id="editStatus" required>
                                <option value="">Seleccione un estado</option>
                                <option value="ACTIVE">Activo</option>
                                <option value="INACTIVE">Inactivo</option>
                                <option value="SUSPENDED">Suspendido</option>
                            </select>
                        </div>

                        <div id="editFormErrors"></div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fas fa-times mr-2"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save mr-2"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let usersData = [];
let usersTable;

$(document).ready(async function() {
    await loadUsers();
    setupEventListeners();
});

async function loadUsers() {
    try {
        const response = await apiRequest('/users');
        usersData = response.data || response;

        const tbody = $('#usersTable tbody');

        if (!Array.isArray(usersData) || usersData.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox mr-2"></i> No hay usuarios registrados
                    </td>
                </tr>
            `);
            return;
        }

        tbody.html(usersData.map(user => `
            <tr>
                <td><code>${user.userCode}</code></td>
                <td>
                    <strong>${user.displayName || (user.profile?.firstName + ' ' + user.profile?.lastName)}</strong>
                </td>
                <td>${user.email}</td>
                <td>
                    <span class="badge badge-${getStatusColor(user.status)}">
                        ${getStatusLabel(user.status)}
                    </span>
                </td>
                <td>
                    ${user.userRoles?.map(r => `<span class="badge badge-secondary">${r.role?.name || 'Sin rol'}</span>`).join(' ') || '<span class="badge badge-light">Sin roles</span>'}
                </td>
                <td class="text-center">
                    <i class="fas fa-${user.emailVerified ? 'check-circle text-success' : 'times-circle text-danger'}"></i>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info" onclick="openEditModal('${user.id}', '${user.status}')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser('${user.id}', '${escapeHtml(user.displayName || user.email)}')" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join(''));

        // Initialize DataTables
        if (usersTable) {
            usersTable.destroy();
        }
        usersTable = $('#usersTable').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print"],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
            }
        }).buttons().container().appendTo('#usersTable_wrapper .col-md-6:eq(0)');

    } catch (error) {
        console.error('Error cargando usuarios:', error);
        showError('Error al cargar los usuarios: ' + error.message);
    }
}

function setupEventListeners() {
    $('#editUserForm').on('submit', async function(e) {
        e.preventDefault();
        const userId = $('#editUserId').val();
        const status = $('#editStatus').val();

        if (!status) {
            $('#editFormErrors').html(`
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="icon fas fa-exclamation-triangle"></i> Debe seleccionar un estado
                </div>
            `);
            return;
        }

        try {
            await apiRequest(`/users/${userId}/status`, 'PUT', { status });
            showSuccess('Usuario actualizado correctamente');
            $('#editUserModal').modal('hide');
            await loadUsers();
        } catch (error) {
            $('#editFormErrors').html(`
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="icon fas fa-ban"></i> ${error.message}
                </div>
            `);
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
    $('#editUserId').val(userId);
    $('#editStatus').val(status);
    $('#editFormErrors').html('');
    $('#editUserModal').modal('show');
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

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>
@endsection
