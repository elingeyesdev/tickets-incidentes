@extends('layouts.authenticated')

@section('title', 'Dashboard - Administrador de Plataforma')

@section('content_header', 'Panel de Control Global')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<!-- Row 1: KPI Statistics (AdminLTE Official Colors) -->
<div class="row">
    <!-- Total Companies (Azul Custom) -->
    <div class="col-lg-3 col-6">
        <div class="small-box" style="background-color: #0d6efd !important; color: white;">
            <div class="inner">
                <h3 id="total-companies" style="color: white;">0</h3>
                <p style="color: white;">Empresas Registradas</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <a href="/app/admin/companies" class="small-box-footer" style="color: white;">
                Gestionar empresas <i class="fas fa-arrow-circle-right"></i>
            </a>
            <div class="overlay dark" id="overlay-kpi-companies">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Total Users (Verde Success) -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="total-users">0</h3>
                <p>Usuarios Totales</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="/app/admin/users" class="small-box-footer">
                Gestionar usuarios <i class="fas fa-arrow-circle-right"></i>
            </a>
            <div class="overlay dark" id="overlay-kpi-users">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Total Tickets (Amarillo Warning) -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="total-tickets">0</h3>
                <p>Tickets Globales</p>
            </div>
            <div class="icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <a href="#" class="small-box-footer">
                Ver métricas <i class="fas fa-chart-bar"></i>
            </a>
            <div class="overlay dark" id="overlay-kpi-tickets">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Pending Requests (Rojo Danger) -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger" id="pending-requests-box">
            <div class="inner">
                <h3 id="pending-requests">0</h3>
                <p>Solicitudes Pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-contract"></i>
            </div>
            <a href="/app/admin/company-requests" class="small-box-footer">
                Revisar solicitudes <i class="fas fa-arrow-circle-right"></i>
            </a>
            <div class="overlay dark" id="overlay-kpi-requests">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Real-time API Traffic Chart (AdminLTE Flot Interactive Style) -->
<div class="row">
    <!-- Chart Column (8 cols) -->
    <div class="col-md-9">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="far fa-chart-bar"></i>
                    Tráfico de API en Tiempo Real
                </h3>

                <div class="card-tools">
                    Real time
                    <div class="btn-group" id="realtime-toggle" data-toggle="btn-toggle">
                        <button type="button" class="btn btn-default btn-sm active" data-toggle="on">On</button>
                        <button type="button" class="btn btn-default btn-sm" data-toggle="off">Off</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="realtime-traffic-chart" style="height: 300px;"></div>
            </div>
            <!-- /.card-body-->
            <div class="overlay" id="overlay-realtime-traffic">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
        <!-- /.card -->
    </div>
    
    <!-- Stats Column (3 cols) - jQuery Knob Inline Style -->
    <div class="col-md-3">
        <div class="card card-outline card-secondary" style="min-height: 395px;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="far fa-chart-bar"></i>
                    Estadísticas API
                </h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="row">
                    <!-- RPS Actual -->
                    <div class="col-6 text-center mb-3">
                        <input type="text" class="knob" id="knob-current-rps" value="0" data-min="0" data-max="20"
                               data-width="100" data-height="100" data-fgColor="#3c8dbc" data-readonly="true">
                        <div class="knob-label">Requests/s (ahora)</div>
                    </div>
                    <!-- Promedio -->
                    <div class="col-6 text-center mb-3">
                        <input type="text" class="knob" id="knob-avg-rps" value="0" data-min="0" data-max="20"
                               data-width="100" data-height="100" data-fgColor="#00a65a" data-readonly="true">
                        <div class="knob-label">Promedio req/s</div>
                    </div>
                </div>
                <!-- /.row -->
                <div class="row">
                    <!-- Máximo -->
                    <div class="col-6 text-center">
                        <input type="text" class="knob" id="knob-max-rps" value="0" data-min="0" data-max="20"
                               data-width="100" data-height="100" data-fgColor="#dc3545" data-readonly="true">
                        <div class="knob-label">Pico máximo</div>
                    </div>
                    <!-- Total -->
                    <div class="col-6 text-center">
                        <input type="text" class="knob" id="knob-total" value="0" data-min="0" data-max="100"
                               data-width="100" data-height="100" data-fgColor="#00c0ef" data-readonly="true">
                        <div class="knob-label">Total (últimos 60s)</div>
                    </div>
                </div>
                <!-- /.row -->
            </div>
            <!-- /.card-body -->
            <div class="overlay" id="overlay-api-stats">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Company Requests Status & Pending Requests List -->
<div class="row">
    <!-- Company Requests Status Donut Chart -->
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Estados de Solicitudes de Empresa</h3>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="requestsStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
            <div class="overlay" id="overlay-requests-status">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- Pending Requests List -->
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Solicitudes de Registro Pendientes</h3>
                <div class="card-tools">
                    <span class="badge badge-danger" id="pending-badge">0 Pendientes</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Email Admin</th>
                            <th>Recibido</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="pendingRequestsBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/admin/company-requests" class="btn btn-sm btn-danger float-right">Ver todas las solicitudes</a>
            </div>
            <div class="overlay" id="overlay-pending-requests">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Top Companies List -->
<div class="row">
    <!-- Top Companies List -->
    <div class="col-md-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Empresas con Mayor Actividad</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Estado</th>
                            <th>Tickets Totales</th>
                            <th>Tendencia</th>
                        </tr>
                    </thead>
                    <tbody id="topCompaniesBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <a href="/app/admin/companies" class="btn btn-sm btn-info float-right">Ver todas las empresas</a>
            </div>
            <div class="overlay" id="overlay-top-companies">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 5: System Status -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Estado del Sistema</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-server"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">API Server</span>
                                <span class="info-box-number">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-database"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Database</span>
                                <span class="info-box-number">Connected</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-envelope"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Email Service</span>
                                <span class="info-box-number">Active</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box shadow-none">
                            <span class="info-box-icon bg-success"><i class="fas fa-shield-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Security</span>
                                <span class="info-box-number">Secure</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
{{-- Flot Charts (AdminLTE v3 native plugin) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.time.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.resize.min.js"></script>
{{-- jQuery Knob (AdminLTE v3 native plugin for circular gauges) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-Knob/1.2.13/jquery.knob.min.js"></script>

<script>
// =====================================================================
// UTILITY FUNCTIONS
// =====================================================================

function getAccessToken() {
    // Try tokenManager first (preferred)
    if (typeof window.tokenManager !== 'undefined' && window.tokenManager) {
        var token = window.tokenManager.getAccessToken();
        if (token) return token;
    }
    // Fallback to localStorage
    return localStorage.getItem('access_token');
}

// Wait for tokenManager to be available
function waitForTokenManager(callback, maxWait) {
    maxWait = maxWait || 5000;
    var startTime = Date.now();
    
    function check() {
        if (typeof window.tokenManager !== 'undefined' && window.tokenManager) {
            callback();
        } else if (Date.now() - startTime < maxWait) {
            setTimeout(check, 100);
        } else {
            callback(); // Fallback
        }
    }
    check();
}

// =====================================================================
// REAL-TIME TRAFFIC CHART (FLOT)
// =====================================================================

var realtimeTrafficChart = (function() {
    // Chart configuration
    var CONFIG = {
        updateInterval: 1000,  // 1 second polling
        displaySeconds: 60,    // Show 60 seconds of data
        chartSelector: '#realtime-traffic-chart'
    };

    // State
    var state = {
        data: [],
        plot: null,
        realtime: true,
        updateTimer: null,
        isInitialized: false,
        consecutiveErrors: 0,
        animationFrameId: null  // Track animation frame for cleanup
    };

    // Chart options (AdminLTE v3 Flot style)
    var chartOptions = {
        grid: {
            borderColor: '#f3f3f3',
            borderWidth: 1,
            tickColor: '#f3f3f3',
            hoverable: true
        },
        series: {
            color: '#5a8fbf',
            lines: {
                lineWidth: 2,
                show: true,
                fill: true,
                fillColor: {
                    colors: [
                        { opacity: 0.1 },
                        { opacity: 0.3 }
                    ]
                }
            },
            points: {
                show: false
            },
            shadowSize: 0
        },
        yaxis: {
            min: 0,
            max: null,                // Will be set dynamically
            minTickSize: 1,           // Minimum tick increment of 1
            tickDecimals: 0,          // No decimals on Y axis
            show: true,
            tickFormatter: function(val) {
                return Math.round(val);  // Always show integers
            },
            // Ensure minimum scale of 0-5
            transform: function(v) { return v; },
            inverseTransform: function(v) { return v; }
        },
        xaxis: {
            mode: 'time',
            timezone: 'browser',
            timeformat: '%H:%M:%S',
            show: true
        }
    };

    // Initialize the chart
    function init() {
        if (state.isInitialized) return;
        
        console.log('[RealtimeTraffic] Initializing chart...');
        
        // Initial empty plot
        if ($(CONFIG.chartSelector).length) {
            state.plot = $.plot($(CONFIG.chartSelector), [{ data: [] }], chartOptions);
            
            // Bind toggle buttons (AdminLTE btn-toggle style)
            $('#realtime-toggle button').on('click', function() {
                // Update active state
                $('#realtime-toggle button').removeClass('active');
                $(this).addClass('active');
                
                var toggle = $(this).data('toggle');
                if (toggle === 'on') {
                    state.realtime = true;
                    updateStatusUI('En Vivo', true);
                    startPolling();
                } else {
                    state.realtime = false;
                    updateStatusUI('Pausado', false);
                    stopPolling();
                }
            });

            // Add tooltip on hover
            $('<div id="traffic-tooltip"></div>').css({
                position: 'absolute',
                display: 'none',
                border: '1px solid #5a8fbf',
                padding: '4px 8px',
                'background-color': '#343a40',
                color: '#fff',
                'border-radius': '4px',
                'font-size': '12px',
                'z-index': 9999
            }).appendTo('body');

            $(CONFIG.chartSelector).bind('plothover', function(event, pos, item) {
                if (item) {
                    var date = new Date(item.datapoint[0]);
                    var timeStr = date.toLocaleTimeString();
                    var rps = item.datapoint[1];
                    $('#traffic-tooltip')
                        .html('<strong>' + rps + '</strong> req/s<br><small>' + timeStr + '</small>')
                        .css({ top: item.pageY - 40, left: item.pageX + 10 })
                        .fadeIn(100);
                } else {
                    $('#traffic-tooltip').hide();
                }
            });
            
            // Initialize jQuery Knob circular gauges
            $('.knob').knob({
                'readOnly': true,
                'draw': function() {
                    // Custom draw function for better appearance
                }
            });
            
            state.isInitialized = true;
            
            // Load initial data
            loadInitialData();
        }
    }

    // Load full history on first load
    function loadInitialData() {
        console.log('[RealtimeTraffic] Loading initial data...');
        updateStatusUI('Cargando...', true);

        // Reset to empty state while loading
        resetChart();
        resetKnobs();

        var token = getAccessToken();
        if (!token) {
            console.error('[RealtimeTraffic] No access token available');
            updateStatusUI('Sin auth', false);
            return;
        }

        $.ajax({
            url: '/api/analytics/realtime-traffic',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Set initial data from history
                    state.data = response.data.history || [];

                    // Store stats for animation (don't update yet, wait for overlay to hide)
                    window.pendingStats = response.data.stats;

                    // Flag to animate chart after overlay hides
                    window.pendingChartAnimation = true;

                    // DON'T update chart yet - keep it empty while overlay is visible
                    // Chart will be animated after overlay disappears

                    updateStatusUI('En Vivo', true);

                    // Start polling for updates
                    if (state.realtime) {
                        startPolling();
                    }

                    console.log('[RealtimeTraffic] Loaded ' + state.data.length + ' data points');
                }
            },
            error: function(xhr, status, error) {
                console.error('[RealtimeTraffic] Failed to load initial data:', error);
                updateStatusUI('Error', false);

                // Retry after 5 seconds
                setTimeout(loadInitialData, 5000);
            }
        });
    }

    // Poll for latest data
    function pollLatest() {
        if (!state.realtime) return;

        var token = getAccessToken();
        if (!token) {
            console.warn('[RealtimeTraffic] No token for polling, stopping...');
            updateStatusUI('Sin auth', false);
            stopPolling();
            return;
        }

        $.ajax({
            url: '/api/analytics/realtime-traffic',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            success: function(response) {
                state.consecutiveErrors = 0; // Reset error counter

                if (response.success && response.data) {
                    // Replace all data with fresh history
                    state.data = response.data.history || [];

                    // Update stats directly (no animation, no reset)
                    updateStats(response.data.stats);

                    // Update chart without animation (already loaded)
                    updateChartData();

                    updateStatusUI('En Vivo', true);
                }
            },
            error: function(xhr, status, error) {
                state.consecutiveErrors++;
                console.warn('[RealtimeTraffic] Poll failed (attempt ' + state.consecutiveErrors + '):', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    tokenLength: token ? token.length : 0
                });
                
                // If we get 401, try to refresh token once
                if (xhr.status === 401) {
                    console.log('[RealtimeTraffic] Got 401, checking tokenManager...');
                    
                    if (window.tokenManager && typeof window.tokenManager.refreshToken === 'function') {
                        console.log('[RealtimeTraffic] Attempting token refresh...');
                        window.tokenManager.refreshToken()
                            .then(function() {
                                console.log('[RealtimeTraffic] Token refreshed successfully');
                                state.consecutiveErrors = 0;
                            })
                            .catch(function(err) {
                                console.error('[RealtimeTraffic] Token refresh failed:', err);
                            });
                    }
                }
                
                // After 5 consecutive errors, pause polling
                if (state.consecutiveErrors >= 5) {
                    console.error('[RealtimeTraffic] Too many errors, pausing polling');
                    updateStatusUI('Error', false);
                    stopPolling();
                }
            }
        });
    }

    // Start polling timer
    function startPolling() {
        if (state.updateTimer) {
            clearInterval(state.updateTimer);
        }
        state.updateTimer = setInterval(pollLatest, CONFIG.updateInterval);
        console.log('[RealtimeTraffic] Polling started');
    }

    // Stop polling timer
    function stopPolling() {
        if (state.updateTimer) {
            clearInterval(state.updateTimer);
            state.updateTimer = null;
        }
        console.log('[RealtimeTraffic] Polling stopped');
    }

    // Reset chart to empty state (while loading)
    function resetChart() {
        if (!state.plot) return;
        state.data = [];
        state.plot.setData([{ data: [] }]);
        state.plot.setupGrid();
        state.plot.draw();
        console.log('[RealtimeTraffic] Chart reset to empty');
    }

    // Reset all jQuery Knobs to 0 (while loading)
    function resetKnobs() {
        $('#knob-current-rps').val(0).trigger('change');
        $('#knob-avg-rps').val(0).trigger('change');
        $('#knob-max-rps').val(0).trigger('change');
        $('#knob-total').val(0).trigger('change');
        console.log('[RealtimeTraffic] Knobs reset to 0');
    }

    // Animate chart data progressively using requestAnimationFrame (time-based, not frame-based)
    function animateChartData() {
        if (!state.plot || !state.data || state.data.length === 0) return;

        // Cancel any existing animation
        if (state.animationFrameId) {
            cancelAnimationFrame(state.animationFrameId);
            state.animationFrameId = null;
        }

        var totalPoints = state.data.length;
        var animationDuration = 960; // 0.96 seconds total animation (20% faster)
        var startTime = Date.now();

        // Calculate max Y value
        var maxY = 5;
        var dataMax = Math.max.apply(null, state.data.map(function(d) { return d[1]; }));
        if (dataMax > maxY) {
            maxY = Math.ceil(dataMax * 1.2);
        }

        // Update Y axis max
        state.plot.getOptions().yaxes[0].max = maxY;

        function animate() {
            var elapsed = Date.now() - startTime;
            var progress = Math.min(elapsed / animationDuration, 1); // 0 to 1

            // Calculate how many points to show based on progress (0 to totalPoints)
            var pointsToShow = Math.ceil(progress * totalPoints);
            var animatedData = state.data.slice(0, pointsToShow);

            state.plot.setData([{ data: animatedData }]);
            state.plot.setupGrid();
            state.plot.draw();

            // Continue animation until complete
            if (progress < 1) {
                state.animationFrameId = requestAnimationFrame(animate);
            } else {
                // Animation complete, show full data
                state.plot.setData([{ data: state.data }]);
                state.plot.setupGrid();
                state.plot.draw();
                state.animationFrameId = null;
                console.log('[RealtimeTraffic] Animation complete');
            }
        }

        // Start animation using requestAnimationFrame (syncs with display refresh rate)
        state.animationFrameId = requestAnimationFrame(animate);
    }

    // Render the chart with animation (ONLY on initial load)
    function renderChart() {
        if (!state.plot) return;

        // Calculate max Y value, with minimum of 5 for better visibility
        var maxY = 5;
        if (state.data && state.data.length > 0) {
            var dataMax = Math.max.apply(null, state.data.map(function(d) { return d[1]; }));
            if (dataMax > maxY) {
                maxY = Math.ceil(dataMax * 1.2); // Add 20% padding
            }
        }

        // Update Y axis max
        state.plot.getOptions().yaxes[0].max = maxY;

        // Animate only on initial load
        animateChartData();
    }

    // Update chart without animation (for polling updates)
    function updateChartData() {
        if (!state.plot) return;

        // Calculate max Y value, with minimum of 5 for better visibility
        var maxY = 5;
        if (state.data && state.data.length > 0) {
            var dataMax = Math.max.apply(null, state.data.map(function(d) { return d[1]; }));
            if (dataMax > maxY) {
                maxY = Math.ceil(dataMax * 1.2); // Add 20% padding
            }
        }

        // Update Y axis max
        state.plot.getOptions().yaxes[0].max = maxY;

        // Direct update without animation
        state.plot.setData([{ data: state.data }]);
        state.plot.setupGrid();
        state.plot.draw();
    }

    // Animate jQuery Knob value from 0 to target value
    function animateKnob($knobElement, targetValue, duration) {
        duration = duration || 800; // 800ms animation duration

        // Get current value or start from 0
        var currentValue = parseInt($knobElement.val()) || 0;

        // Only animate if target is different from current
        if (targetValue === currentValue) return;

        $({value: currentValue}).animate({value: targetValue}, {
            duration: duration,
            easing: 'swing',
            step: function() {
                $knobElement.val(Math.ceil(this.value)).trigger('change');
            },
            complete: function() {
                $knobElement.val(targetValue).trigger('change');
            }
        });
    }

    // Update statistics display using jQuery Knob WITHOUT animation (for polling updates)
    function updateStats(stats) {
        if (!stats) return;

        var currentRps = stats.current_rps || 0;
        var avgRps = stats.avg_rps || 0;
        var maxRps = stats.max_rps || 0;
        var totalRequests = stats.total_requests || 0;

        // Update jQuery Knob values directly WITHOUT animation
        $('#knob-current-rps').val(currentRps).trigger('change');
        $('#knob-avg-rps').val(avgRps).trigger('change');
        $('#knob-max-rps').val(maxRps).trigger('change');
        $('#knob-total').val(totalRequests).trigger('change');
    }

    // Animate statistics display using jQuery Knob WITH animation (only on initial load)
    function animateStats(stats) {
        if (!stats) return;

        var currentRps = stats.current_rps || 0;
        var avgRps = stats.avg_rps || 0;
        var maxRps = stats.max_rps || 0;
        var totalRequests = stats.total_requests || 0;

        // Animate jQuery Knob values with staggered timing
        animateKnob($('#knob-current-rps'), currentRps, 600);
        animateKnob($('#knob-avg-rps'), avgRps, 700);
        animateKnob($('#knob-max-rps'), maxRps, 800);
        animateKnob($('#knob-total'), totalRequests, 900);
    }

    // Update status indicator
    function updateStatusUI(text, isActive) {
        var $status = $('#traffic-status');
        var $box = $('#traffic-status-box');
        
        // Update text with icon
        if (isActive) {
            $status.html('<i class="fas fa-circle fa-xs mr-1"></i> ' + text);
            $box.css('background', '#28a745');
        } else {
            $status.html('<i class="fas fa-circle fa-xs mr-1"></i> ' + text);
            $box.css('background', '#6c757d');
        }
    }

    // Cleanup on page unload
    function destroy() {
        stopPolling();

        // Cancel any running animation
        if (state.animationFrameId) {
            cancelAnimationFrame(state.animationFrameId);
            state.animationFrameId = null;
        }

        state.isInitialized = false;
    }

    // Public API
    return {
        init: init,
        destroy: destroy,
        updateStats: updateStats,      // For polling updates (no animation)
        animateStats: animateStats,    // For initial load stats animations
        animateChartData: animateChartData  // For initial load chart animations
    };
})();

// =====================================================================
// LOAD DASHBOARD DATA
// =====================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize real-time traffic chart AFTER tokenManager is available
    waitForTokenManager(function() {
        realtimeTrafficChart.init();
    });

    setTimeout(() => {
        const token = getAccessToken();
        const apiUrl = '/api';

        if (!token) {
            Swal.fire({
                icon: 'error',
                title: 'Error de autenticación',
                text: 'Por favor inicia sesión de nuevo'
            }).then(() => {
                window.location.href = '/login';
            });
            return;
        }

        fetch(`${apiUrl}/analytics/platform-dashboard`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) {
                window.location.href = '/login?reason=session_expired';
                return null;
            }
            if (!response.ok) throw new Error('Failed to load dashboard data');
            return response.json();
        })
        .then(data => {
            if (!data) return;

            // Update KPI cards
            document.getElementById('total-companies').textContent = data.kpi.total_companies;
            document.getElementById('total-users').textContent = data.kpi.total_users;
            document.getElementById('total-tickets').textContent = data.kpi.total_tickets;
            document.getElementById('pending-requests').textContent = data.kpi.pending_requests;
            document.getElementById('pending-badge').textContent = data.kpi.pending_requests + ' Pendientes';

            // Update Pending Box Color if requests > 0
            // Keep pending-requests-box always red (bg-danger)
            document.getElementById('pending-requests-box').classList.add('bg-danger');
            document.getElementById('pending-requests-box').classList.remove('bg-success');

            // Render Lists
            renderPendingRequests(data.pending_requests);
            renderTopCompanies(data.top_companies);

            // Initialize Charts
            initializeRequestsStatusChart(data.company_requests_stats);
        })
        .catch(error => {
            console.error('[Platform Dashboard] Error loading dashboard:', error);
        })
        .finally(() => {
            // Hide all overlays regardless of success or failure
            document.querySelectorAll('.overlay').forEach(el => el.style.display = 'none');

            // Small delay to ensure overlay is fully hidden before animating
            setTimeout(() => {
                // Animate chart if data was loaded
                if (window.pendingChartAnimation) {
                    console.log('[Dashboard] Animating chart after overlay hide');
                    realtimeTrafficChart.animateChartData();
                    window.pendingChartAnimation = false;
                }

                // Animate stats after chart animation starts
                if (window.pendingStats) {
                    console.log('[Dashboard] Animating stats after overlay hide');
                    realtimeTrafficChart.animateStats(window.pendingStats);
                    window.pendingStats = null;
                }
            }, 100); // 100ms delay ensures overlay CSS transition is complete
        });

    }, 500);
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    realtimeTrafficChart.destroy();
});

// =====================================================================
// RENDER FUNCTIONS
// =====================================================================

function renderPendingRequests(requests) {
    const tbody = document.getElementById('pendingRequestsBody');
    tbody.innerHTML = '';

    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No hay solicitudes pendientes</td></tr>';
        return;
    }

    requests.forEach(req => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${req.company_name}</td>
            <td>${req.admin_email}</td>
            <td><small class="text-muted">${req.created_at}</small></td>
            <td>
                <a href="/app/admin/company-requests" class="btn btn-xs btn-primary">
                    <i class="fas fa-eye"></i> Revisar
                </a>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function renderTopCompanies(companies) {
    const tbody = document.getElementById('topCompaniesBody');
    tbody.innerHTML = '';

    if (companies.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sin datos de empresas</td></tr>';
        return;
    }

    companies.forEach(company => {
        const statusClass = company.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary';
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${company.name}</td>
            <td><span class="badge ${statusClass}">${company.status}</span></td>
            <td><strong>${company.tickets_count}</strong></td>
            <td><span class="text-success"><i class="fas fa-caret-up"></i> Activo</span></td>
        `;
        tbody.appendChild(row);
    });
}

// =====================================================================
// CHART FUNCTIONS
// =====================================================================


function initializeRequestsStatusChart(data) {
    const ctx = document.getElementById('requestsStatusChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Solicitudes',
                data: data.data,
                backgroundColor: data.backgroundColor,
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
                        boxWidth: 12,
                        padding: 10,
                        font: { size: 13 }
                    }
                }
            }
        }
    });
}

</script>
@endpush
