@extends('layouts.app')

@section('title', 'Gestión de Empresas')
@section('header', 'Gestión de Empresas')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Lista de Empresas</h5>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
                <i class="fas fa-plus me-2"></i> Nueva Empresa
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="companiesTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Administrador</th>
                        <th>Estado</th>
                        <th>Usuarios</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin me-2"></i> Cargando empresas...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear Empresa -->
<div class="modal fade" id="createCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Nueva Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createCompanyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="companyName" class="form-label">Nombre de la Empresa</label>
                        <input type="text" class="form-control" id="companyName" name="name" required minlength="3">
                    </div>

                    <div class="mb-3">
                        <label for="adminUserId" class="form-label">Administrador</label>
                        <select class="form-select" id="adminUserId" name="adminUserId" required>
                            <option value="">-- Selecciona un administrador --</option>
                        </select>
                        <small class="text-muted d-block mt-2">El administrador será el responsable de gestionar la empresa</small>
                    </div>

                    <div id="formErrors"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Crear Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Empresa -->
<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCompanyForm">
                <input type="hidden" id="editCompanyId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editCompanyName" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editCompanyName" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Estado</label>
                        <select class="form-select" id="editStatus" name="status" required>
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
let companiesData = [];
let usersData = [];

document.addEventListener('DOMContentLoaded', async function() {
    await loadUsers();
    await loadCompanies();
    setupEventListeners();
});

async function loadUsers() {
    try {
        const response = await apiRequest('/users');
        usersData = response.data || response;

        const select = document.getElementById('adminUserId');
        select.innerHTML = '<option value="">-- Selecciona un administrador --</option>' +
            usersData.map(user => `
                <option value="${user.id}">
                    ${user.displayName || (user.profile?.firstName + ' ' + user.profile?.lastName)} (${user.email})
                </option>
            `).join('');
    } catch (error) {
        console.error('Error cargando usuarios:', error);
    }
}

async function loadCompanies() {
    try {
        const response = await apiRequest('/companies');
        companiesData = response.data || response;

        const tbody = document.querySelector('#companiesTable tbody');
        if (!Array.isArray(companiesData) || companiesData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox me-2"></i> No hay empresas registradas
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = companiesData.map(company => `
            <tr>
                <td><code>${company.companyCode}</code></td>
                <td><strong>${company.name}</strong></td>
                <td>
                    ${company.adminUser?.displayName || 'Sin asignar'}
                </td>
                <td>
                    <span class="badge bg-${getStatusColor(company.status)}">
                        ${getStatusLabel(company.status)}
                    </span>
                </td>
                <td>
                    <span class="badge bg-info">${company.usersCount || 0}</span>
                </td>
                <td>
                    <small>${new Date(company.createdAt).toLocaleDateString('es-ES')}</small>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="openEditModal('${company.id}', '${company.name}', '${company.status}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCompany('${company.id}', '${company.name}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error('Error cargando empresas:', error);
        showError('Error al cargar las empresas: ' + error.message);
    }
}

function setupEventListeners() {
    document.getElementById('createCompanyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = document.getElementById('companyName').value;
        const adminUserId = document.getElementById('adminUserId').value;

        try {
            await apiRequest('/companies', 'POST', {
                name: name,
                adminUserId: adminUserId
            });
            showSuccess('Empresa creada correctamente');
            this.reset();
            bootstrap.Modal.getInstance(document.getElementById('createCompanyModal')).hide();
            await loadCompanies();
        } catch (error) {
            document.getElementById('formErrors').innerHTML = `
                <div class="alert alert-danger">${error.message}</div>
            `;
        }
    });

    document.getElementById('editCompanyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const companyId = document.getElementById('editCompanyId').value;
        const name = document.getElementById('editCompanyName').value;
        const status = document.getElementById('editStatus').value;

        try {
            await apiRequest(`/companies/${companyId}`, 'PATCH', {
                name: name,
                status: status
            });
            showSuccess('Empresa actualizada correctamente');
            bootstrap.Modal.getInstance(document.getElementById('editCompanyModal')).hide();
            await loadCompanies();
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

function openEditModal(companyId, name, status) {
    document.getElementById('editCompanyId').value = companyId;
    document.getElementById('editCompanyName').value = name;
    document.getElementById('editStatus').value = status;
    new bootstrap.Modal(document.getElementById('editCompanyModal')).show();
}

async function deleteCompany(companyId, companyName) {
    if (!confirm(`¿Está seguro que desea eliminar la empresa ${companyName}?`)) {
        return;
    }

    try {
        // Nota: Eliminar empresa podría no estar implementado en tu API
        // Esta es una estructura lista para cuando lo esté
        await apiRequest(`/companies/${companyId}`, 'DELETE');
        showSuccess('Empresa eliminada correctamente');
        await loadCompanies();
    } catch (error) {
        showError('Error al eliminar empresa: ' + error.message);
    }
}
</script>
@endsection
