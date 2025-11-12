@extends('layouts.authenticated')

@section('title', 'Panel de Administrador - Dashboard')

@section('content_header', 'Panel de Control - Administrador')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<!-- Row 1: KPI Statistics -->
<div class="row">
    <!-- Total Users KPI -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="total-users">0</h3>
                <p>Usuarios Activos</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="/app/admin/users" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Companies KPI -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="total-companies">0</h3>
                <p>Empresas Registradas</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <a href="/app/admin/companies" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Pending Requests KPI (Alert color if > 0) -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-warning" id="pending-requests-box">
            <div class="inner">
                <h3 id="pending-requests">0</h3>
                <p>Solicitudes Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <a href="/app/admin/requests" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Tickets KPI (Disabled: No API Endpoint) -->
    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>-</h3>
                <p>Total Tickets</p>
                <small style="font-size: 10px;">(No disponible)</small>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="#" class="small-box-footer" style="cursor: not-allowed;">
                En desarrollo <i class="fas fa-lock"></i>
            </a>
        </div>
    </div>
</div>

<!-- Row 2: Charts and Recent Requests -->
<div class="row">
    <!-- Chart: Companies by Status -->
    <div class="col-md-6">
        <div class="card card-primary" id="companiesCard">
            <div class="card-header">
                <h3 class="card-title">Estado de Empresas</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 250px;">
                    <canvas id="companiesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Company Requests Table -->
    <div class="col-md-6">
        <div class="card card-warning" id="requestsTableCard">
            <div class="card-header">
                <h3 class="card-title">Solicitudes Recientes</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Empresa</th>
                            <th style="width: 35%;">Email</th>
                            <th style="width: 25%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cargado dinámicamente vía AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="/app/admin/requests" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-right"></i> Ver todas las solicitudes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: System Health Info Boxes -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Estado del Sistema</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- API Status -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-success">
                            <span class="info-box-icon">
                                <i class="fas fa-server"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">API</span>
                                <span class="info-box-number" id="api-status">En línea</span>
                            </div>
                        </div>
                    </div>

                    <!-- Database Status -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-success">
                            <span class="info-box-icon">
                                <i class="fas fa-database"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Base de Datos</span>
                                <span class="info-box-number" id="db-status">Conectada</span>
                            </div>
                        </div>
                    </div>

                    <!-- Email Service Status -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-success">
                            <span class="info-box-icon">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Email</span>
                                <span class="info-box-number" id="email-status">Activo</span>
                            </div>
                        </div>
                    </div>

                    <!-- Docker Status -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box bg-success">
                            <span class="info-box-icon">
                                <i class="fas fa-docker"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Docker</span>
                                <span class="info-box-number" id="docker-status">Corriendo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    // Wait a bit for TokenManager to be ready, or get token directly from localStorage
    function getAccessToken() {
        // Try to use TokenManager first (if loaded)
        if (typeof window.tokenManager !== 'undefined' && window.tokenManager.getAccessToken) {
            return window.tokenManager.getAccessToken();
        }
        // Fallback to localStorage directly
        return localStorage.getItem('access_token');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Use small delay to ensure TokenManager is initialized
        setTimeout(() => {
            const token = getAccessToken();
            const apiUrl = '/api';

            console.log('[Dashboard] Token available:', !!token);
            if (!token) {
                console.error('[Dashboard] No token available, cannot load dashboard data');
                return;
            }

        // =====================================================================
        // LOAD DASHBOARD DATA VIA AJAX
        // =====================================================================

        // Store references to cards for later overlay removal
        const cardReferences = {};

        // Add overlay loading state to KPI cards using AdminLTE v3 official pattern
        // ONLY for cards that will load data (exclude "Total Tickets" which is "En desarrollo")
        const kpiCards = document.querySelectorAll('.small-box');
        kpiCards.forEach((card, index) => {
            // Skip "Total Tickets" card (usually the 4th one, index 3)
            if (index === 3) return;

            const overlay = document.createElement('div');
            overlay.className = 'overlay';
            overlay.innerHTML = '<i class="fas fa-3x fa-sync-alt fa-spin"></i>';
            card.style.position = 'relative';
            card.appendChild(overlay);

            // Store card reference by index
            cardReferences[`kpi-${index}`] = card;
        });

        // Create alias for users card from KPI cards (already has overlay from above loop)
        cardReferences['usersCard'] = cardReferences['kpi-0'];

        // Add overlay to company status chart - store reference
        const chartCard = document.getElementById('companiesCard');
        if (chartCard) {
            const overlay = document.createElement('div');
            overlay.className = 'overlay';
            overlay.innerHTML = '<i class="fas fa-3x fa-sync-alt fa-spin"></i>';
            chartCard.style.position = 'relative';
            chartCard.appendChild(overlay);
            cardReferences['chartCard'] = chartCard;
        }

        // Add overlay to recent requests card - store reference
        const requestsCard = document.getElementById('requestsTableCard');
        if (requestsCard) {
            const overlay = document.createElement('div');
            overlay.className = 'overlay';
            overlay.innerHTML = '<i class="fas fa-3x fa-sync-alt fa-spin"></i>';
            requestsCard.style.position = 'relative';
            requestsCard.appendChild(overlay);
            cardReferences['requestsCard'] = requestsCard;
        }

        // 1. Load Users Count
        if (token) {
            console.log('[Dashboard] Fetching /api/users with token:', token.substring(0, 20) + '...');
            fetch(`${apiUrl}/users`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('[Dashboard] /api/users response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('[Dashboard] /api/users data:', data);
                // Handle paginated response structure (data, meta, links)
                if (data.data && Array.isArray(data.data)) {
                    const count = data.meta?.total || data.data.length || 0;
                    console.log('[Dashboard] Users count:', count);
                    document.getElementById('total-users').textContent = count;
                } else {
                    console.warn('[Dashboard] Unexpected response structure for users:', data);
                }
                // Always remove overlay on success
                if (cardReferences['usersCard']) {
                    const overlay = cardReferences['usersCard'].querySelector('.overlay');
                    if (overlay) {
                        console.log('[Dashboard] Removing users overlay');
                        overlay.remove();
                    }
                }
            })
            .catch(error => {
                console.error('[Dashboard] Error loading users:', error);
                // Remove overlay on error
                if (cardReferences['usersCard']) {
                    const overlay = cardReferences['usersCard'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
            });
        }

        // 2. Load Companies Count and Data for Chart
        let companiesData = { active: 0, suspended: 0 };
        if (token) {
            console.log('[Dashboard] Fetching /api/companies');
            fetch(`${apiUrl}/companies`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('[Dashboard] /api/companies response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('[Dashboard] /api/companies data:', data);
                // Handle paginated response structure (data, meta, links)
                if (data.data && Array.isArray(data.data)) {
                    const companies = data.data;
                    const count = data.meta?.total || companies.length || 0;
                    console.log('[Dashboard] Companies count:', count);
                    document.getElementById('total-companies').textContent = count;

                    // Calculate companies by status for chart
                    companiesData = { active: 0, suspended: 0 };
                    companies.forEach(company => {
                        const status = company.status || 'ACTIVE';
                        const statusLower = status.toLowerCase();
                        if (companiesData.hasOwnProperty(statusLower)) {
                            companiesData[statusLower]++;
                        }
                    });

                    console.log('[Dashboard] Companies by status:', companiesData);
                    // Update chart
                    updateCompaniesChart(companiesData);
                } else {
                    console.warn('[Dashboard] Unexpected response structure for companies:', data);
                }
                // Remove overlay from companies KPI and chart card
                if (cardReferences['kpi-1']) {
                    const overlay = cardReferences['kpi-1'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
                if (cardReferences['chartCard']) {
                    const overlay = cardReferences['chartCard'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
            })
            .catch(error => {
                console.error('[Dashboard] Error loading companies:', error);
                // Remove overlay on error
                if (cardReferences['kpi-1']) {
                    const overlay = cardReferences['kpi-1'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
                if (cardReferences['chartCard']) {
                    const overlay = cardReferences['chartCard'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
            });
        }

        // 3. Load Pending Requests Count
        let recentRequests = [];
        if (token) {
            console.log('[Dashboard] Fetching /api/company-requests');
            fetch(`${apiUrl}/company-requests`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('[Dashboard] /api/company-requests response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('[Dashboard] /api/company-requests data:', data);
                // Handle paginated response structure (data, meta, links)
                if (data.data && Array.isArray(data.data)) {
                    const requests = data.data;

                    // Log all statuses to see what values we have
                    console.log('[Dashboard] Request statuses:', requests.map(r => ({ code: r.requestCode, status: r.status })));

                    // Filter pending requests (uppercase PENDING)
                    recentRequests = requests.filter(req => req.status === 'PENDING').slice(0, 5);

                    console.log('[Dashboard] Pending requests count:', recentRequests.length);
                    document.getElementById('pending-requests').textContent = recentRequests.length;

                    // Update pending requests box color
                    const pendingBox = document.getElementById('pending-requests-box');
                    if (recentRequests.length > 0) {
                        pendingBox.classList.remove('bg-warning');
                        pendingBox.classList.add('bg-danger');
                    } else {
                        pendingBox.classList.remove('bg-danger');
                        pendingBox.classList.add('bg-warning');
                    }

                    // Update recent requests table
                    updateRecentRequestsTable(recentRequests);
                } else {
                    console.warn('[Dashboard] Unexpected response structure for requests:', data);
                }
                // Remove overlay from requests KPI and table card
                if (cardReferences['kpi-2']) {
                    const overlay = cardReferences['kpi-2'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
                if (cardReferences['requestsCard']) {
                    const overlay = cardReferences['requestsCard'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
            })
            .catch(error => {
                console.error('[Dashboard] Error loading requests:', error);
                // Remove overlay on error
                if (cardReferences['kpi-2']) {
                    const overlay = cardReferences['kpi-2'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
                if (cardReferences['requestsCard']) {
                    const overlay = cardReferences['requestsCard'].querySelector('.overlay');
                    if (overlay) overlay.remove();
                }
            });
        }

        // =====================================================================
        // CHART INITIALIZATION
        // =====================================================================

        function updateCompaniesChart(data) {
            const ctx = document.getElementById('companiesChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Activas', 'Suspendidas'],
                        datasets: [{
                            data: [
                                data.active || 0,
                                data.suspended || 0
                            ],
                            backgroundColor: [
                                '#28a745',  // Green for active
                                '#dc3545'   // Red for suspended
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: { size: 12 }
                                }
                            }
                        }
                    }
                });
            }
        }

        // =====================================================================
        // UPDATE RECENT REQUESTS TABLE
        // =====================================================================

        function updateRecentRequestsTable(requests) {
            const tbody = document.querySelector('.table tbody');
            if (!tbody) return;

            if (requests.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-muted">
                            <i class="fas fa-check-circle text-success mr-2"></i> No hay solicitudes pendientes
                        </td>
                    </tr>
                `;
                return;
            }

            console.log('[Dashboard] Updating table with requests:', requests);
            tbody.innerHTML = requests.map(req => `
                <tr>
                    <td>
                        <strong>${req.companyName || 'N/A'}</strong><br>
                        <small class="text-muted">${req.industryType || req.legalName || 'N/A'}</small>
                    </td>
                    <td>${req.adminEmail || 'N/A'}</td>
                    <td>
                        <a href="/app/admin/requests" class="btn btn-xs btn-primary">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
            `).join('');
        }


        // =====================================================================
        // API HEALTH CHECK
        // =====================================================================

        if (token) {
            fetch(`${apiUrl}/health`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('api-status').textContent = 'En línea';
                    const apiBox = document.getElementById('api-status').parentElement.parentElement;
                    apiBox.classList.remove('bg-danger');
                    apiBox.classList.add('bg-success');
                }
            })
            .catch(error => {
                console.error('Health check failed:', error);
                document.getElementById('api-status').textContent = 'Offline';
                const apiBox = document.getElementById('api-status').parentElement.parentElement;
                apiBox.classList.remove('bg-success');
                apiBox.classList.add('bg-danger');
            });
        }
        }, 100); // Close setTimeout
    });
</script>
@endsection
