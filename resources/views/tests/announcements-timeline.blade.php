@extends('adminlte::page')

@section('title', 'Anuncios - Timeline')

@section('css')
<style>
    /* Custom Colors matching Admin View */
    .bg-purple { background-color: #6f42c1 !important; color: #fff; }
    .text-purple { color: #6f42c1 !important; }
    .badge-purple { background-color: #6f42c1; color: #fff; }
    
    /* Sticky Sidebar */
    .sticky-top-offset {
        top: 20px;
        z-index: 100;
    }

    /* Clean Metadata Grid */
    .metadata-grid {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 15px;
    }
    .metadata-grid i {
        width: 20px;
        text-align: center;
        margin-right: 5px;
    }
    .metadata-divider {
        border-top: 1px solid #e9ecef;
        margin: 15px 0;
    }
    
    /* Timeline Item Tweaks */
    .timeline-item {
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        border-radius: 0.25rem;
        background: #fff;
        margin-top: 0;
    }
    .timeline-header {
        border-bottom: 1px solid rgba(0,0,0,.125);
        padding: 10px;
        font-size: 16px;
        line-height: 1.1;
    }
    .timeline-header .user-block {
        margin-bottom: 0;
    }
    .timeline-body {
        padding: 15px;
    }
    .timeline-footer {
        padding: 10px 15px;
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
        border-bottom-left-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
    }
</style>
@stop

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Linea de Tiempo de Anuncios</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right form-inline">
                    <label for="simulation-toggle" class="mr-2">Simulación:</label>
                    <select id="simulation-toggle" class="form-control form-control-sm" onchange="toggleSimulation()">
                        <option value="following">Siguiendo Empresas</option>
                        <option value="empty">No Siguiendo (Empty State)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        
        <!-- EMPTY STATE -->
        <div id="empty-state" style="display: none;">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="error-page">
                        <h2 class="headline text-warning"> <i class="fas fa-search"></i></h2>
                        <div class="error-content">
                            <h3><i class="fas fa-exclamation-triangle text-warning"></i> No hay actividad reciente.</h3>
                            <p>
                                No estás siguiendo a ninguna empresa o no han publicado nada recientemente.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Suggested Companies Widget -->
                    <div class="card card-primary card-outline mt-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-lightbulb text-warning mr-2"></i> Empresas Sugeridas</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="products-list product-list-in-card pl-2 pr-2">
                                <li class="item">
                                    <div class="product-img">
                                        <img src="https://ui-avatars.com/api/?name=Acme+Corp&background=dc3545&color=fff" alt="Product Image" class="img-size-50">
                                    </div>
                                    <div class="product-info">
                                        <a href="javascript:void(0)" class="product-title">Acme Corp
                                            <span class="badge badge-info float-right">Infraestructura</span></a>
                                        <span class="product-description">
                                            Proveedores de servicios cloud y servidores dedicados.
                                        </span>
                                        <button class="btn btn-xs btn-outline-primary mt-1"><i class="fas fa-plus"></i> Seguir</button>
                                    </div>
                                </li>
                                <li class="item">
                                    <div class="product-img">
                                        <img src="https://ui-avatars.com/api/?name=Tech+Solutions&background=17a2b8&color=fff" alt="Product Image" class="img-size-50">
                                    </div>
                                    <div class="product-info">
                                        <a href="javascript:void(0)" class="product-title">Tech Solutions
                                            <span class="badge badge-success float-right">SaaS</span></a>
                                        <span class="product-description">
                                            Plataforma de gestión empresarial y CRM.
                                        </span>
                                        <button class="btn btn-xs btn-outline-primary mt-1"><i class="fas fa-plus"></i> Seguir</button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOLLOWING CONTENT -->
        <div id="following-content">
            <div class="row">
                
                <!-- FILTERS SIDEBAR -->
                <div class="col-md-3 order-md-2">
                    <div class="sticky-top sticky-top-offset">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
                            </div>
                            <div class="card-body">
                                <!-- Search -->
                                <div class="form-group">
                                    <label>Buscar</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Palabra clave...">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Type Filter -->
                                <div class="form-group">
                                    <label>Tipo de Anuncio</label>
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="filterNews" checked>
                                        <label for="filterNews" class="custom-control-label text-info"><i class="fas fa-newspaper mr-1"></i> Noticias</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="filterMaintenance" checked>
                                        <label for="filterMaintenance" class="custom-control-label text-purple"><i class="fas fa-tools mr-1"></i> Mantenimiento</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="filterIncident" checked>
                                        <label for="filterIncident" class="custom-control-label text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Incidentes</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="filterAlert" checked>
                                        <label for="filterAlert" class="custom-control-label text-warning"><i class="fas fa-bell mr-1"></i> Alertas</label>
                                    </div>
                                </div>

                                <!-- Company Filter -->
                                <div class="form-group">
                                    <label>Empresa</label>
                                    <select class="form-control">
                                        <option>Todas</option>
                                        <option>Acme Corp</option>
                                        <option>Cloud Net</option>
                                        <option>Tech Solutions</option>
                                        <option>Pay Flow</option>
                                    </select>
                                </div>
                                
                                <button class="btn btn-primary btn-block">Aplicar Filtros</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TIMELINE COLUMN -->
                <div class="col-md-9 order-md-1">
                    <div class="timeline">
                        
                        <!-- Timeline Time Label -->
                        <div class="time-label">
                            <span class="bg-red">Hoy, 22 Nov 2025</span>
                        </div>

                        <!-- ALERT ITEM -->
                        <div>
                            <i class="fas fa-bell bg-yellow"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> 12:05</span>
                                
                                <div class="timeline-header">
                                    <div class="user-block">
                                        <img class="img-circle" src="https://ui-avatars.com/api/?name=Acme+Corp&background=dc3545&color=fff" alt="User Image">
                                        <span class="username"><a href="#">Acme Corp</a></span>
                                        <span class="description">Infraestructura - Security Alert</span>
                                    </div>
                                </div>

                                <div class="timeline-body">
                                    <div class="alert alert-danger mb-0">
                                        <h5><i class="icon fas fa-exclamation-triangle"></i> Intento de Acceso No Autorizado</h5>
                                        Detectamos un intento de acceso no autorizado masivo en la región US-East.
                                    </div>
                                    
                                    <div class="metadata-divider"></div>
                                    
                                    <div class="row metadata-grid">
                                        <div class="col-md-6">
                                            <p class="mb-1"><i class="fas fa-bolt text-warning"></i> <strong>Urgencia:</strong> <span class="badge badge-danger">CRITICAL</span></p>
                                            <p class="mb-1"><i class="fas fa-server text-muted"></i> <strong>Servicios:</strong> API Gateway, Auth Service</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><i class="fas fa-exclamation-circle text-danger"></i> <strong>Acción Requerida:</strong> Rotar claves API inmediatamente.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="timeline-footer">
                                    <a class="btn btn-warning btn-sm">Ver Instrucciones</a>
                                </div>
                            </div>
                        </div>

                        <!-- NEWS ITEM -->
                        <div>
                            <i class="fas fa-newspaper bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> 10:00</span>
                                
                                <div class="timeline-header">
                                    <div class="user-block">
                                        <img class="img-circle" src="https://ui-avatars.com/api/?name=Tech+Solutions&background=17a2b8&color=fff" alt="User Image">
                                        <span class="username"><a href="#">Tech Solutions</a></span>
                                        <span class="description">SaaS - Feature Release</span>
                                    </div>
                                </div>

                                <div class="timeline-body">
                                    <h4 class="text-primary mt-0">Nuevo Dashboard de Analíticas v2.0</h4>
                                    <p>Estamos emocionados de anunciar el lanzamiento de nuestro nuevo dashboard con métricas en tiempo real.</p>
                                    
                                    <div class="metadata-divider"></div>

                                    <div class="row metadata-grid">
                                        <div class="col-md-6">
                                            <p class="mb-1"><i class="fas fa-users text-info"></i> <strong>Audiencia:</strong> <span class="badge badge-secondary">Users</span> <span class="badge badge-secondary">Admins</span></p>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <a href="#" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt"></i> Probar Ahora</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- INCIDENT ITEM -->
                        <div>
                            <i class="fas fa-exclamation-triangle bg-red"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> 09:00</span>
                                
                                <div class="timeline-header">
                                    <div class="user-block">
                                        <img class="img-circle" src="https://ui-avatars.com/api/?name=Pay+Flow&background=28a745&color=fff" alt="User Image">
                                        <span class="username"><a href="#">Pay Flow</a></span>
                                        <span class="description">Pagos - Incidente Resuelto</span>
                                    </div>
                                </div>

                                <div class="timeline-body">
                                    <h5 class="text-danger font-weight-bold mt-0">Latencia en Procesamiento de Pagos</h5>
                                    <p>Se identificó un bloqueo en la base de datos que causaba lentitud en las transacciones.</p>
                                    
                                    <div class="callout callout-success mt-3">
                                        <h5>Resolución</h5>
                                        <p>Se aplicó un hotfix y el servicio opera con normalidad.</p>
                                    </div>

                                    <div class="metadata-divider"></div>

                                    <div class="row metadata-grid">
                                        <div class="col-md-4">
                                            <p class="mb-1"><i class="fas fa-bolt text-warning"></i> <strong>Urgencia:</strong> MEDIUM</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-1"><i class="fas fa-hourglass-half text-muted"></i> <strong>Duración:</strong> 1h 30m</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-1"><i class="fas fa-check-circle text-success"></i> <strong>Estado:</strong> Resuelto</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline Time Label -->
                        <div class="time-label">
                            <span class="bg-green">Futuro</span>
                        </div>

                        <!-- MAINTENANCE ITEM -->
                        <div>
                            <i class="fas fa-tools bg-purple"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-calendar"></i> 25 Nov</span>
                                
                                <div class="timeline-header">
                                    <div class="user-block">
                                        <img class="img-circle" src="https://ui-avatars.com/api/?name=Cloud+Net&background=ffc107&color=000" alt="User Image">
                                        <span class="username"><a href="#">Cloud Net</a></span>
                                        <span class="description">Redes - Mantenimiento Programado</span>
                                    </div>
                                </div>

                                <div class="timeline-body">
                                    <h5 class="font-weight-bold mt-0">Actualización de Firmware Core</h5>
                                    <p>Mantenimiento para actualizar routers principales.</p>

                                    <div class="metadata-divider"></div>

                                    <div class="row metadata-grid">
                                        <div class="col-md-6">
                                            <p class="mb-1"><i class="far fa-calendar-alt text-purple"></i> <strong>Inicio:</strong> 25 Nov, 02:00 AM</p>
                                            <p class="mb-1"><i class="far fa-clock text-purple"></i> <strong>Fin:</strong> 25 Nov, 06:00 AM</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><i class="fas fa-server text-muted"></i> <strong>Servicios:</strong> <span class="badge badge-purple">Core Routers</span> <span class="badge badge-purple">VPN Access</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <i class="fas fa-clock bg-gray"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSimulation() {
            var mode = document.getElementById('simulation-toggle').value;
            if (mode === 'empty') {
                document.getElementById('following-content').style.display = 'none';
                document.getElementById('empty-state').style.display = 'block';
            } else {
                document.getElementById('following-content').style.display = 'block';
                document.getElementById('empty-state').style.display = 'none';
            }
        }
    </script>
@stop
