@extends('layouts.app')

@section('title', 'Empresas')
@section('header', 'Gestión de Empresas')
@section('breadcrumb', 'Empresas')

@section('content')
{{-- Header con botón --}}
<div class="row mb-3">
    <div class="col-12">
        <button class="btn btn-primary float-right" data-toggle="modal" data-target="#createCompanyModal">
            <i class="fas fa-plus"></i> Nueva Empresa
        </button>
    </div>
</div>

{{-- Tabla de empresas --}}
<div class="card card-info card-outline">
    <div class="card-header">
        <h3 class="card-title">Lista de Empresas</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="companiesTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Administrador</th>
                        <th>Estado</th>
                        <th>Usuarios</th>
                        <th>Fecha Creación</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Cargando empresas...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Crear Empresa --}}
<div class="modal fade" id="createCompanyModal" tabindex="-1" role="dialog" aria-labelledby="createCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="card card-primary mb-0">
                <div class="card-header">
                    <h3 class="card-title">Crear Nueva Empresa</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="createCompanyForm">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="companyName">Nombre de la Empresa</label>
                            <input type="text" class="form-control" id="companyName" name="name" placeholder="Ingrese el nombre de la empresa" required minlength="3">
                        </div>

                        <div class="form-group">
                            <label for="adminUserId">Administrador</label>
                            <select class="form-control select2" id="adminUserId" name="adminUserId" style="width: 100%;" required>
                                <option value="">-- Selecciona un administrador --</option>
                            </select>
                            <small class="form-text text-muted">El administrador será el responsable de gestionar la empresa</small>
                        </div>

                        <div id="formErrors"></div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save"></i> Crear Empresa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Empresa --}}
<div class="modal fade" id="editCompanyModal" tabindex="-1" role="dialog" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="card card-primary mb-0">
                <div class="card-header">
                    <h3 class="card-title">Editar Empresa</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editCompanyForm">
                    <input type="hidden" id="editCompanyId">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="editCompanyName">Nombre</label>
                            <input type="text" class="form-control" id="editCompanyName" name="name" placeholder="Ingrese el nombre de la empresa" required>
                        </div>

                        <div class="form-group">
                            <label for="editStatus">Estado</label>
                            <select class="form-control" id="editStatus" name="status" required>
                                <option value="ACTIVE">Activo</option>
                                <option value="INACTIVE">Inactivo</option>
                                <option value="SUSPENDED">Suspendido</option>
                            </select>
                        </div>

                        <div id="editFormErrors"></div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fas fa-save"></i> Guardar Cambios
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
let companiesData = [];
let usersData = [];

$(document).ready(async function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#createCompanyModal')
    });

    await loadUsers();
    await loadCompanies();
    setupEventListeners();
});

async function loadUsers() {
    try {
        const response = await apiRequest('/users');
        usersData = response.data || response;

        const $select = $('#adminUserId');
        $select.empty().append('<option value="">-- Selecciona un administrador --</option>');

        usersData.forEach(user => {
            const displayName = user.displayName || (user.profile?.firstName + ' ' + user.profile?.lastName);
            $select.append(`<option value="${user.id}">${displayName} (${user.email})</option>`);
        });
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        Swal.fire('Error', 'No se pudieron cargar los usuarios', 'error');
    }
}

async function loadCompanies() {
    try {
        const response = await apiRequest('/companies');
        companiesData = response.data || response;

        const $tbody = $('#companiesTable tbody');

        if (!Array.isArray(companiesData) || companiesData.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-inbox mr-2"></i> No hay empresas registradas
                    </td>
                </tr>
            `);
            return;
        }

        const rows = companiesData.map(company => `
            <tr>
                <td><code>${company.companyCode}</code></td>
                <td><strong>${company.name}</strong></td>
                <td>${company.adminUser?.displayName || 'Sin asignar'}</td>
                <td>
                    <span class="badge badge-${getStatusColor(company.status)}">
                        ${getStatusLabel(company.status)}
                    </span>
                </td>
                <td>
                    <span class="badge badge-info">${company.usersCount || 0}</span>
                </td>
                <td>
                    <small>${new Date(company.createdAt).toLocaleDateString('es-ES')}</small>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info" onclick="openEditModal('${company.id}', '${escapeHtml(company.name)}', '${company.status}')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCompany('${company.id}', '${escapeHtml(company.name)}')" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        $tbody.html(rows);

    } catch (error) {
        console.error('Error cargando empresas:', error);
        Swal.fire('Error', 'Error al cargar las empresas: ' + error.message, 'error');
    }
}

function setupEventListeners() {
    $('#createCompanyForm').on('submit', async function(e) {
        e.preventDefault();

        const name = $('#companyName').val();
        const adminUserId = $('#adminUserId').val();

        // Validación
        if (!name || !adminUserId) {
            Swal.fire('Advertencia', 'Por favor complete todos los campos', 'warning');
            return;
        }

        try {
            await apiRequest('/companies', 'POST', {
                name: name,
                adminUserId: adminUserId
            });

            Swal.fire('Éxito', 'Empresa creada correctamente', 'success');
            this.reset();
            $('#adminUserId').val(null).trigger('change'); // Reset Select2
            $('#createCompanyModal').modal('hide');
            await loadCompanies();
        } catch (error) {
            $('#formErrors').html(`
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    ${error.message}
                </div>
            `);
        }
    });

    $('#editCompanyForm').on('submit', async function(e) {
        e.preventDefault();

        const companyId = $('#editCompanyId').val();
        const name = $('#editCompanyName').val();
        const status = $('#editStatus').val();

        try {
            await apiRequest(`/companies/${companyId}`, 'PATCH', {
                name: name,
                status: status
            });

            Swal.fire('Éxito', 'Empresa actualizada correctamente', 'success');
            $('#editCompanyModal').modal('hide');
            await loadCompanies();
        } catch (error) {
            $('#editFormErrors').html(`
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    ${error.message}
                </div>
            `);
        }
    });

    // Limpiar errores al abrir modales
    $('#createCompanyModal').on('show.bs.modal', function() {
        $('#formErrors').empty();
        $('#createCompanyForm')[0].reset();
        $('#adminUserId').val(null).trigger('change');
    });

    $('#editCompanyModal').on('show.bs.modal', function() {
        $('#editFormErrors').empty();
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
    $('#editCompanyId').val(companyId);
    $('#editCompanyName').val(name);
    $('#editStatus').val(status);
    $('#editCompanyModal').modal('show');
}

async function deleteCompany(companyId, companyName) {
    const result = await Swal.fire({
        title: '¿Eliminar empresa?',
        text: `¿Está seguro que desea eliminar la empresa "${companyName}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await apiRequest(`/companies/${companyId}`, 'DELETE');
        Swal.fire('Eliminada', 'Empresa eliminada correctamente', 'success');
        await loadCompanies();
    } catch (error) {
        Swal.fire('Error', 'Error al eliminar empresa: ' + error.message, 'error');
    }
}

// Helper para escapar HTML (prevenir XSS)
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
