@extends('layouts.authenticated')

@section('title', 'Gestión de Empresas - Dashboard')

@section('content_header', 'Gestión de Empresas')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="/app/admin/dashboard">Admin</a></li>
    <li class="breadcrumb-item active">Empresas</li>
@endsection

@section('content')
<!-- Row 1: Filters and Actions -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <!-- Filter by Status -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-status" class="mb-1">Estado:</label>
                        <select id="filter-status" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="active">Activas</option>
                            <option value="suspended">Suspendidas</option>
                        </select>
                    </div>

                    <!-- Filter by Industry -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-industry" class="mb-1">Industria:</label>
                        <select id="filter-industry" class="form-control form-control-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <!-- Search Box -->
                    <div class="col-lg-4 col-md-4 col-sm-12 mb-2">
                        <label for="search-companies" class="mb-1">Buscar:</label>
                        <input type="text" id="search-companies" class="form-control form-control-sm"
                               placeholder="Buscar por nombre o email...">
                    </div>

                    <!-- Refresh Button -->
                    <div class="col-lg-2 col-md-12 col-sm-12 mb-2">
                        <label class="mb-1">&nbsp;</label>
                        <button id="btn-refresh" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-sync-alt"></i> Refrescar
                        </button>
                    </div>

                    <!-- Create Button -->
                    <div class="col-lg-2 col-md-12 col-sm-12 mb-2">
                        <label class="mb-1">&nbsp;</label>
                        <button id="btn-create-company" class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-plus"></i> Nueva Empresa
                        </button>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row mt-2">
                    <div class="col-12">
                        <small class="text-muted">
                            Total: <strong id="total-count">0</strong> |
                            Activas: <strong id="active-count">0</strong> |
                            Suspendidas: <strong id="suspended-count">0</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Companies Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building"></i> Empresas Registradas
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="companies-table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Código</th>
                                <th style="width: 20%;">Nombre</th>
                                <th style="width: 15%;">Email Soporte</th>
                                <th style="width: 12%;">Industria</th>
                                <th style="width: 10%;">Admin</th>
                                <th style="width: 8%;">Estado</th>
                                <th style="width: 10%;">Agentes</th>
                                <th style="width: 15%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loaded dynamically via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Los datos se cargan dinámicamente desde la API
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: View/Edit Details -->
<div class="modal fade" id="modal-details" tabindex="-1" role="dialog" aria-labelledby="modalDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modalDetailsLabel">
                    <i class="fas fa-building"></i> Detalles de Empresa
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Column: Basic Info -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Información General</h6>

                        <div class="form-group">
                            <label class="text-muted mb-1">Código de Empresa:</label>
                            <p class="mb-2"><strong id="detail-company-code">-</strong></p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Nombre Comercial:</label>
                            <p class="mb-2"><strong id="detail-company-name">-</strong></p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Nombre Legal:</label>
                            <p class="mb-2" id="detail-legal-name">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Email de Soporte:</label>
                            <p class="mb-2" id="detail-support-email">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Teléfono:</label>
                            <p class="mb-2" id="detail-phone">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Sitio Web:</label>
                            <p class="mb-2" id="detail-website">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Industria:</label>
                            <p class="mb-2" id="detail-industry">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Estado:</label>
                            <p class="mb-2" id="detail-status-badge">-</p>
                        </div>
                    </div>

                    <!-- Right Column: Contact & Admin -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Información de Contacto</h6>

                        <div class="form-group">
                            <label class="text-muted mb-1">Dirección:</label>
                            <p class="mb-2" id="detail-address">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Ciudad:</label>
                            <p class="mb-2" id="detail-city">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Estado/Región:</label>
                            <p class="mb-2" id="detail-state">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">País:</label>
                            <p class="mb-2" id="detail-country">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Código Postal:</label>
                            <p class="mb-2" id="detail-postal">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Tax ID (RUT/NIT):</label>
                            <p class="mb-2" id="detail-tax-id">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Representante Legal:</label>
                            <p class="mb-2" id="detail-legal-rep">-</p>
                        </div>

                        <div class="form-group">
                            <label class="text-muted mb-1">Zona Horaria:</label>
                            <p class="mb-2" id="detail-timezone">-</p>
                        </div>
                    </div>
                </div>

                <!-- Admin & Statistics Section -->
                <div class="row mt-3">
                    <div class="col-12">
                        <hr>
                        <h6 class="border-bottom pb-2 mb-3">Administrador y Estadísticas</h6>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted mb-1">Admin:</label>
                        <p class="mb-2" id="detail-admin-name">-</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted mb-1">Email Admin:</label>
                        <p class="mb-2" id="detail-admin-email">-</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted mb-1">Agentes Activos:</label>
                        <p class="mb-2"><strong id="detail-active-agents">0</strong></p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted mb-1">Seguidores (Users):</label>
                        <p class="mb-2"><strong id="detail-followers">0</strong></p>
                    </div>
                </div>

                <!-- Dates -->
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="text-muted mb-1">Creada:</label>
                        <p class="mb-2" id="detail-created-at">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted mb-1">Última Actualización:</label>
                        <p class="mb-2" id="detail-updated-at">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btn-modal-edit" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Create/Edit Company -->
<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modalFormLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white" id="modalFormLabel">
                    <i class="fas fa-plus-circle"></i> <span id="form-title">Nueva Empresa</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="company-form">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Información General</h6>

                            <div class="form-group">
                                <label for="form-company-name">Nombre Comercial <span class="text-danger">*</span></label>
                                <input type="text" id="form-company-name" class="form-control" placeholder="Nombre de la empresa" required>
                                <small class="form-text text-muted">2-255 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-legal-name">Nombre Legal</label>
                                <input type="text" id="form-legal-name" class="form-control" placeholder="Nombre legal">
                                <small class="form-text text-muted">2-255 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-support-email">Email de Soporte <span class="text-danger">*</span></label>
                                <input type="email" id="form-support-email" class="form-control" placeholder="support@empresa.com" required>
                            </div>

                            <div class="form-group">
                                <label for="form-phone">Teléfono</label>
                                <input type="text" id="form-phone" class="form-control" placeholder="+56912345678">
                                <small class="form-text text-muted">Máximo 20 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-website">Sitio Web</label>
                                <input type="url" id="form-website" class="form-control" placeholder="https://empresa.com">
                            </div>

                            <div class="form-group">
                                <label for="form-industry-id">Industria <span class="text-danger">*</span></label>
                                <select id="form-industry-id" class="form-control" required disabled>
                                    <option value="">Cargando industrias...</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Requerida en creación. Editable en actualización.
                                </small>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Información de Contacto</h6>

                            <div class="form-group">
                                <label for="form-address">Dirección</label>
                                <input type="text" id="form-address" class="form-control" placeholder="Calle y número">
                                <small class="form-text text-muted">Máximo 255 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-city">Ciudad</label>
                                <input type="text" id="form-city" class="form-control" placeholder="Santiago">
                                <small class="form-text text-muted">Máximo 100 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-state">Estado/Región</label>
                                <input type="text" id="form-state" class="form-control" placeholder="Metropolitana">
                                <small class="form-text text-muted">Máximo 100 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-country">País</label>
                                <input type="text" id="form-country" class="form-control" placeholder="Chile">
                                <small class="form-text text-muted">Máximo 100 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-postal">Código Postal</label>
                                <input type="text" id="form-postal" class="form-control" placeholder="8340000">
                                <small class="form-text text-muted">Máximo 20 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-tax-id">Tax ID (RUT/NIT)</label>
                                <input type="text" id="form-tax-id" class="form-control" placeholder="12.345.678-9">
                                <small class="form-text text-muted">Máximo 50 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-legal-rep">Representante Legal</label>
                                <input type="text" id="form-legal-rep" class="form-control" placeholder="Nombre completo">
                                <small class="form-text text-muted">Máximo 255 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-timezone">Zona Horaria <span class="text-danger">*</span></label>
                                <select id="form-timezone" class="form-control" required>
                                    <option value="">Seleccionar zona horaria...</option>
                                    <option value="America/Santiago">America/Santiago</option>
                                    <option value="America/Bogota">America/Bogota</option>
                                    <option value="America/La_Paz">America/La_Paz</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Selection -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <hr>
                            <h6 class="border-bottom pb-2 mb-3">Administrador</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="form-admin-id">Usuario Admin <span class="text-danger">*</span></label>
                                <select id="form-admin-id" class="form-control" required disabled>
                                    <option value="">Cargando usuarios...</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Requerido en creación. No editable en actualización.
                                </small>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="form-company-id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-save-company" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Delete Company -->
<div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-labelledby="modalDeleteLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="modalDeleteLabel">
                    <i class="fas fa-trash"></i> Eliminar Empresa
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¿Deseas eliminar la empresa <span id="delete-company-name">-</span>?</strong>
                </div>

                <p>Esta acción es <strong>irreversible</strong> y eliminará:</p>
                <ul>
                    <li>Todos los datos de la empresa</li>
                    <li>Todos los usuarios asociados</li>
                    <li>Todos los tickets y datos relacionados</li>
                </ul>

                <div class="alert alert-danger">
                    <strong>CONFIRMACIÓN:</strong> Escribe el nombre de la empresa para confirmar
                </div>

                <input type="text" id="delete-confirmation" class="form-control" placeholder="Nombre de la empresa">
                <small class="form-text text-muted">Debes escribir el nombre exacto para confirmar</small>

                <input type="hidden" id="delete-company-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-confirm-delete" class="btn btn-danger" disabled>
                    <i class="fas fa-trash"></i> Eliminar Permanentemente
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // =====================================================================
        // CONFIGURATION & STATE
        // =====================================================================

        const token = window.tokenManager?.getAccessToken();
        const apiUrl = '/api';

        let allCompanies = [];
        let currentCompany = null;
        let currentMode = 'view'; // view, create, edit
        let industries = [];
        let adminUsers = [];
        let industriesLoaded = false;
        let adminUsersLoaded = false;

        // =====================================================================
        // UTILITY: Format Status Badge
        // =====================================================================

        function getStatusBadge(status) {
            const statusLower = status ? status.toLowerCase() : '';
            const badges = {
                'active': '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Activa</span>',
                'suspended': '<span class="badge badge-warning"><i class="fas fa-pause-circle"></i> Suspendida</span>'
            };
            return badges[statusLower] || '<span class="badge badge-secondary">Desconocido</span>';
        }

        // =====================================================================
        // UTILITY: Format Date
        // =====================================================================

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('es-ES', options);
        }

        // =====================================================================
        // LOAD INDUSTRIES FOR FORM
        // =====================================================================

        function loadIndustries() {
            fetch(`${apiUrl}/company-industries`)
                .then(response => response.json())
                .then(data => {
                    if (data.data && Array.isArray(data.data)) {
                        industries = data.data;
                        industriesLoaded = true;
                        populateIndustrySelects();
                    }
                })
                .catch(error => {
                    console.error('Error loading industries:', error);
                });
        }

        function populateIndustrySelects() {
            const filterSelect = document.getElementById('filter-industry');
            const formSelect = document.getElementById('form-industry-id');

            // Clear existing options (except the first one)
            filterSelect.querySelectorAll('option:not(:first-child)').forEach(opt => opt.remove());
            formSelect.querySelectorAll('option:not(:first-child)').forEach(opt => opt.remove());

            industries.forEach(industry => {
                // Filter select
                const filterOption = document.createElement('option');
                filterOption.value = industry.id;
                filterOption.textContent = industry.name;
                filterSelect.appendChild(filterOption);

                // Form select
                const formOption = document.createElement('option');
                formOption.value = industry.id;
                formOption.textContent = industry.name;
                formSelect.appendChild(formOption);
            });

            // Enable form select
            formSelect.disabled = false;
        }

        // =====================================================================
        // LOAD ADMIN USERS FOR FORM
        // =====================================================================

        function loadAdminUsers() {
            // Load active users for admin selector
            fetch(`${apiUrl}/users?status=ACTIVE&per_page=100`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.data && Array.isArray(data.data)) {
                    adminUsers = data.data;
                    adminUsersLoaded = true;
                    populateAdminUserSelects();
                }
            })
            .catch(error => {
                console.error('Error loading admin users:', error);
            });
        }

        function populateAdminUserSelects() {
            const formSelect = document.getElementById('form-admin-id');

            // Clear existing options (except the first one)
            formSelect.querySelectorAll('option:not(:first-child)').forEach(opt => opt.remove());

            adminUsers.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                // Display user code and email
                option.textContent = `${user.userCode} - ${user.email}`;
                formSelect.appendChild(option);
            });

            // Enable form select
            formSelect.disabled = false;
        }

        // =====================================================================
        // FUNCTION 1: Load Companies
        // =====================================================================

        function loadCompanies(filters = {}) {
            if (!token) {
                showAlert('error', 'No se encontró token de autenticación');
                return;
            }

            const tbody = document.querySelector('#companies-table tbody');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando empresas...</td></tr>';

            // Build URL with filters
            let url = `${apiUrl}/companies`;
            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.industry_id) params.append('industry_id', filters.industry_id);
            if (filters.search) params.append('search', filters.search);
            params.append('per_page', 50);

            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.data && Array.isArray(data.data)) {
                    allCompanies = Array.isArray(data.data) ? data.data : [data.data];
                    renderCompaniesTable(allCompanies);

                    // Update stats
                    document.getElementById('total-count').textContent = data.meta?.total || allCompanies.length;
                    const activeCount = allCompanies.filter(c => c.status === 'ACTIVE' || c.status === 'active').length;
                    const suspendedCount = allCompanies.filter(c => c.status === 'SUSPENDED' || c.status === 'suspended').length;
                    document.getElementById('active-count').textContent = activeCount;
                    document.getElementById('suspended-count').textContent = suspendedCount;
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error al cargar empresas</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading companies:', error);
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error de conexión: ' + error.message + '</td></tr>';
            });
        }

        // =====================================================================
        // FUNCTION: Render Companies Table
        // =====================================================================

        function renderCompaniesTable(companies) {
            const tbody = document.querySelector('#companies-table tbody');

            if (!companies || companies.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-inbox"></i> No hay empresas disponibles</td></tr>';
                return;
            }

            tbody.innerHTML = companies.map(company => {
                const statusBadge = getStatusBadge(company.status);
                const industryName = (company.industry && company.industry.name) || 'N/A';

                return `
                    <tr data-id="${company.id}">
                        <td><code>${company.companyCode || 'N/A'}</code></td>
                        <td>
                            <strong>${company.name || 'N/A'}</strong><br>
                            <small class="text-muted">${company.legalName || ''}</small>
                        </td>
                        <td>${company.supportEmail || 'N/A'}</td>
                        <td><span class="badge badge-info">${industryName}</span></td>
                        <td><small>${company.adminName || 'N/A'}</small></td>
                        <td>${statusBadge}</td>
                        <td>
                            <span class="badge badge-primary" title="Agentes activos">${company.activeAgentsCount || 0}</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-view-details" data-id="${company.id}" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-edit" data-id="${company.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${company.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            attachActionListeners();
        }

        // =====================================================================
        // FUNCTION 2: Open Details Modal
        // =====================================================================

        function openDetailsModal(companyId) {
            currentCompany = allCompanies.find(c => c.id === companyId);

            if (!currentCompany) {
                showAlert('error', 'No se encontró la empresa');
                return;
            }

            currentMode = 'view';

            // Populate basic info
            document.getElementById('detail-company-code').textContent = currentCompany.companyCode || 'N/A';
            document.getElementById('detail-company-name').textContent = currentCompany.name || 'N/A';
            document.getElementById('detail-legal-name').textContent = currentCompany.legalName || 'N/A';
            document.getElementById('detail-support-email').textContent = currentCompany.supportEmail || 'N/A';
            document.getElementById('detail-phone').textContent = currentCompany.phone || 'N/A';

            // Website with link
            const website = currentCompany.website || 'N/A';
            document.getElementById('detail-website').innerHTML = website !== 'N/A'
                ? `<a href="${website}" target="_blank">${website} <i class="fas fa-external-link-alt"></i></a>`
                : website;

            document.getElementById('detail-industry').textContent = (currentCompany.industry && currentCompany.industry.name) || 'N/A';
            document.getElementById('detail-status-badge').innerHTML = getStatusBadge(currentCompany.status);

            // Contact info
            document.getElementById('detail-address').textContent = currentCompany.contactAddress || 'N/A';
            document.getElementById('detail-city').textContent = currentCompany.contactCity || 'N/A';
            document.getElementById('detail-state').textContent = currentCompany.contactState || 'N/A';
            document.getElementById('detail-country').textContent = currentCompany.contactCountry || 'N/A';
            document.getElementById('detail-postal').textContent = currentCompany.contactPostalCode || 'N/A';
            document.getElementById('detail-tax-id').textContent = currentCompany.taxId || 'N/A';
            document.getElementById('detail-legal-rep').textContent = currentCompany.legalRepresentative || 'N/A';
            document.getElementById('detail-timezone').textContent = currentCompany.timezone || 'UTC';

            // Admin & Stats
            document.getElementById('detail-admin-name').textContent = currentCompany.adminName || 'N/A';
            document.getElementById('detail-admin-email').textContent = currentCompany.adminEmail || 'N/A';
            document.getElementById('detail-active-agents').textContent = currentCompany.activeAgentsCount || 0;
            document.getElementById('detail-followers').textContent = currentCompany.followersCount || 0;

            // Dates
            document.getElementById('detail-created-at').textContent = formatDate(currentCompany.createdAt);
            document.getElementById('detail-updated-at').textContent = formatDate(currentCompany.updatedAt);

            $('#modal-details').modal('show');
        }

        // =====================================================================
        // FUNCTION 3: Open Form Modal (Create/Edit)
        // =====================================================================

        function openFormModal(mode, companyId = null) {
            currentMode = mode;
            const form = document.getElementById('company-form');
            form.reset();

            if (mode === 'create') {
                document.getElementById('form-title').textContent = 'Nueva Empresa';
                document.getElementById('modalFormLabel').closest('.modal-header').classList.remove('bg-info');
                document.getElementById('modalFormLabel').closest('.modal-header').classList.add('bg-success');
                document.getElementById('form-company-id').value = '';

                // ENABLE selects for create mode (requerido en creación)
                const formIndustrySelect = document.getElementById('form-industry-id');
                const formAdminSelect = document.getElementById('form-admin-id');

                // Industries
                if (industriesLoaded) {
                    formIndustrySelect.disabled = false;
                    // Update placeholder text
                    const firstOption = formIndustrySelect.querySelector('option');
                    if (firstOption) {
                        firstOption.textContent = 'Seleccionar industria...';
                    }
                }

                // Admin users
                if (adminUsersLoaded) {
                    formAdminSelect.disabled = false;
                    // Update placeholder text
                    const firstOption = formAdminSelect.querySelector('option');
                    if (firstOption) {
                        firstOption.textContent = 'Seleccionar usuario...';
                    }
                }

                // Solo mostrar alerta si AMBOS aún no han cargado
                if (!industriesLoaded && !adminUsersLoaded) {
                    showAlert('warning', 'Cargando industrias y usuarios...');
                } else if (!industriesLoaded) {
                    showAlert('warning', 'Cargando industrias...');
                } else if (!adminUsersLoaded) {
                    showAlert('warning', 'Cargando usuarios...');
                }
            } else if (mode === 'edit') {
                currentCompany = allCompanies.find(c => c.id === companyId);
                if (!currentCompany) {
                    showAlert('error', 'No se encontró la empresa');
                    return;
                }

                document.getElementById('form-title').textContent = 'Editar Empresa';
                document.getElementById('modalFormLabel').closest('.modal-header').classList.remove('bg-success');
                document.getElementById('modalFormLabel').closest('.modal-header').classList.add('bg-info');

                // Populate form
                document.getElementById('form-company-name').value = currentCompany.name || '';
                document.getElementById('form-legal-name').value = currentCompany.legalName || '';
                document.getElementById('form-support-email').value = currentCompany.supportEmail || '';
                document.getElementById('form-phone').value = currentCompany.phone || '';
                document.getElementById('form-website').value = currentCompany.website || '';
                document.getElementById('form-address').value = currentCompany.contactAddress || '';
                document.getElementById('form-city').value = currentCompany.contactCity || '';
                document.getElementById('form-state').value = currentCompany.contactState || '';
                document.getElementById('form-country').value = currentCompany.contactCountry || '';
                document.getElementById('form-postal').value = currentCompany.contactPostalCode || '';
                document.getElementById('form-tax-id').value = currentCompany.taxId || '';
                document.getElementById('form-legal-rep').value = currentCompany.legalRepresentative || '';
                document.getElementById('form-timezone').value = currentCompany.timezone || 'UTC';
                document.getElementById('form-company-id').value = currentCompany.id;

                // Set industry value (EDITABLE)
                if (industriesLoaded && currentCompany.industryId) {
                    document.getElementById('form-industry-id').value = currentCompany.industryId;
                    document.getElementById('form-industry-id').disabled = false;
                }

                // Set admin value (READ-ONLY - no se puede editar)
                if (adminUsersLoaded && currentCompany.adminId) {
                    document.getElementById('form-admin-id').value = currentCompany.adminId;
                }

                // IMPORTANTE: admin_user_id NO es editable en PATCH
                // Pero industry_id SÍ es editable (es opcional en PATCH)
                document.getElementById('form-admin-id').disabled = true;
            }

            $('#modal-details').modal('hide');
            $('#modal-form').modal('show');
        }

        // =====================================================================
        // FUNCTION 4: Save Company
        // =====================================================================

        function saveCompany() {
            const companyId = document.getElementById('form-company-id').value;
            const isCreate = !companyId;

            const payload = {
                name: document.getElementById('form-company-name').value,
                legal_name: document.getElementById('form-legal-name').value,
                support_email: document.getElementById('form-support-email').value,
                phone: document.getElementById('form-phone').value || null,
                website: document.getElementById('form-website').value || null,
                contact_address: document.getElementById('form-address').value || null,
                contact_city: document.getElementById('form-city').value || null,
                contact_state: document.getElementById('form-state').value || null,
                contact_country: document.getElementById('form-country').value || null,
                contact_postal_code: document.getElementById('form-postal').value || null,
                tax_id: document.getElementById('form-tax-id').value || null,
                legal_representative: document.getElementById('form-legal-rep').value || null,
                timezone: document.getElementById('form-timezone').value,
            };

            // For create: industry_id y admin_user_id son requeridos
            if (isCreate) {
                payload.industry_id = document.getElementById('form-industry-id').value;
                payload.admin_user_id = document.getElementById('form-admin-id').value;
            } else {
                // For edit: industry_id se puede cambiar (es opcional en PATCH)
                const industryId = document.getElementById('form-industry-id').value;
                if (industryId) {
                    payload.industry_id = industryId;
                }
                // admin_user_id NO se envía en PATCH (no se puede editar)
            }

            const method = isCreate ? 'POST' : 'PATCH';
            const url = isCreate ? `${apiUrl}/companies` : `${apiUrl}/companies/${companyId}`;

            const btnSave = document.getElementById('btn-save-company');
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.data || data.success) {
                    showAlert('success', isCreate ? 'Empresa creada exitosamente' : 'Empresa actualizada exitosamente');
                    $('#modal-form').modal('hide');
                    loadCompanies();
                } else {
                    showAlert('error', data.message || 'Error al guardar la empresa');
                }
            })
            .catch(error => {
                console.error('Error saving company:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save"></i> Guardar';
            });
        }

        // =====================================================================
        // FUNCTION 5: Delete Company
        // =====================================================================

        function openDeleteModal(companyId) {
            currentCompany = allCompanies.find(c => c.id === companyId);
            if (!currentCompany) {
                showAlert('error', 'No se encontró la empresa');
                return;
            }

            document.getElementById('delete-company-name').textContent = currentCompany.name;
            document.getElementById('delete-company-id').value = companyId;
            document.getElementById('delete-confirmation').value = '';
            document.getElementById('btn-confirm-delete').disabled = true;

            $('#modal-details').modal('hide');
            $('#modal-delete').modal('show');
        }

        function deleteCompany() {
            const companyId = document.getElementById('delete-company-id').value;
            const confirmationText = document.getElementById('delete-confirmation').value.trim();
            const expectedText = currentCompany.name;

            if (confirmationText !== expectedText) {
                showAlert('error', 'El nombre de confirmación no coincide');
                return;
            }

            const btnDelete = document.getElementById('btn-confirm-delete');
            btnDelete.disabled = true;
            btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

            // TODO: Esta función se implementará cuando exista el endpoint DELETE
            fetch(`${apiUrl}/companies/${companyId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.data?.success) {
                    showAlert('success', 'Empresa eliminada exitosamente');
                    $('#modal-delete').modal('hide');
                    loadCompanies();
                } else {
                    showAlert('error', data.message || 'Error al eliminar la empresa');
                    btnDelete.disabled = false;
                    btnDelete.innerHTML = '<i class="fas fa-trash"></i> Eliminar Permanentemente';
                }
            })
            .catch(error => {
                console.error('Error deleting company:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
                btnDelete.disabled = false;
                btnDelete.innerHTML = '<i class="fas fa-trash"></i> Eliminar Permanentemente';
            });
        }

        // =====================================================================
        // FUNCTION: Show Alert
        // =====================================================================

        function showAlert(type, message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: type,
                title: message
            });
        }

        // =====================================================================
        // ATTACH ACTION LISTENERS
        // =====================================================================

        function attachActionListeners() {
            document.querySelectorAll('.btn-view-details').forEach(btn => {
                btn.addEventListener('click', function() {
                    openDetailsModal(this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function() {
                    openFormModal('edit', this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    openDeleteModal(this.dataset.id);
                });
            });
        }

        // =====================================================================
        // EVENT LISTENERS
        // =====================================================================

        document.getElementById('filter-status').addEventListener('change', function() {
            loadCompanies({ status: this.value });
        });

        document.getElementById('filter-industry').addEventListener('change', function() {
            loadCompanies({ industry_id: this.value });
        });

        document.getElementById('search-companies').addEventListener('input', function() {
            loadCompanies({ search: this.value });
        });

        document.getElementById('btn-refresh').addEventListener('click', function() {
            loadCompanies();
        });

        document.getElementById('btn-create-company').addEventListener('click', function() {
            openFormModal('create');
        });

        document.getElementById('btn-modal-edit').addEventListener('click', function() {
            if (currentCompany) {
                openFormModal('edit', currentCompany.id);
            }
        });

        document.getElementById('btn-save-company').addEventListener('click', saveCompany);

        document.getElementById('btn-confirm-delete').addEventListener('click', deleteCompany);

        // Delete confirmation text validation
        document.getElementById('delete-confirmation').addEventListener('input', function() {
            const btnDelete = document.getElementById('btn-confirm-delete');
            btnDelete.disabled = this.value.trim() !== currentCompany.name;
        });

        // =====================================================================
        // INITIALIZE
        // =====================================================================

        loadIndustries();
        loadAdminUsers();
        loadCompanies();
    });
</script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
