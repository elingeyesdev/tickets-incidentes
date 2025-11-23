@extends('adminlte::page')

@section('title', 'Anuncios - Feed')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Feed de Actividad</h1>
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
                <div class="col-md-6 text-center mt-5">
                    <img src="https://placehold.co/200x200?text=No+Follows" class="img-circle mb-4" alt="No Follows">
                    <h3>¡Aún no sigues a ninguna empresa!</h3>
                    <p class="lead text-muted">Sigue a tus proveedores de servicios para recibir notificaciones sobre mantenimientos, incidentes y novedades.</p>
                    <a href="#" class="btn btn-primary btn-lg mt-3"><i class="fas fa-plus"></i> Explorar Empresas</a>
                </div>
            </div>
        </div>

        <!-- FOLLOWING CONTENT -->
        <div id="following-content">
            <div class="row">
                <!-- Main Feed Column -->
                <div class="col-md-8">
                    
                    <!-- ALERT TYPE -->
                    <div class="card card-widget">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Acme+Corp&background=dc3545&color=fff&size=128" alt="User Image" style="width: 40px; height: 40px;">
                                <span class="username"><a href="#">Acme Corp</a> <span class="badge badge-secondary ml-1">Infraestructura</span></span>
                                <span class="description">Publicado: 22 Nov, 12:05 PM</span>
                            </div>
                            <div class="card-tools">
                                <span class="badge badge-danger" style="font-size: 1rem;">CRITICAL ALERT</span>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4 class="text-danger"><i class="fas fa-shield-alt"></i> Intento de Acceso No Autorizado</h4>
                            <p class="lead">Detectamos un intento de acceso no autorizado masivo en la región US-East.</p>
                            
                            <div class="callout callout-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Action Required</h5>
                                <p><strong>Message:</strong> Se requiere que todos los administradores roten sus claves API inmediatamente.</p>
                                <p><strong>Description:</strong> Diríjase al panel de seguridad y seleccione "Rotar todas las llaves".</p>
                            </div>

                            <div class="card card-outline card-secondary collapsed-card">
                                <div class="card-header">
                                    <h3 class="card-title">Metadata Completa</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped">
                                        <tbody>
                                            <tr><th>Urgency</th><td><span class="badge badge-danger">CRITICAL</span></td></tr>
                                            <tr><th>Alert Type</th><td>Security</td></tr>
                                            <tr><th>Action Required</th><td><span class="badge badge-warning">YES</span></td></tr>
                                            <tr><th>Started At</th><td>2025-11-22 12:00:00</td></tr>
                                            <tr><th>Ended At</th><td>-</td></tr>
                                            <tr><th>Affected Services</th><td>
                                                <span class="badge badge-info">API Gateway</span>
                                                <span class="badge badge-info">Auth Service</span>
                                            </td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MAINTENANCE TYPE -->
                    <div class="card card-widget">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Cloud+Net&background=ffc107&color=000&size=128" alt="User Image" style="width: 40px; height: 40px;">
                                <span class="username"><a href="#">Cloud Net</a> <span class="badge badge-secondary ml-1">Redes</span></span>
                                <span class="description">Publicado: Ayer, 15:30 PM</span>
                            </div>
                            <div class="card-tools">
                                <span class="badge badge-warning" style="font-size: 1rem;">MAINTENANCE</span>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4><i class="fas fa-tools"></i> Actualización de Firmware Core</h4>
                            <p>Mantenimiento programado para actualizar los routers principales y mejorar la latencia.</p>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <span class="info-box-icon"><i class="far fa-calendar-alt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Inicio Programado</span>
                                            <span class="info-box-number">2025-11-25 02:00:00</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <span class="info-box-icon"><i class="far fa-clock"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Fin Programado</span>
                                            <span class="info-box-number">2025-11-25 06:00:00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-secondary collapsed-card">
                                <div class="card-header">
                                    <h3 class="card-title">Metadata Completa</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped">
                                        <tbody>
                                            <tr><th>Urgency</th><td><span class="badge badge-warning">HIGH</span></td></tr>
                                            <tr><th>Is Emergency</th><td><span class="badge badge-success">NO</span></td></tr>
                                            <tr><th>Actual Start</th><td>-</td></tr>
                                            <tr><th>Actual End</th><td>-</td></tr>
                                            <tr><th>Affected Services</th><td>
                                                <span class="badge badge-info">Core Routers</span>
                                                <span class="badge badge-info">VPN Access</span>
                                            </td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INCIDENT TYPE -->
                    <div class="card card-widget">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Pay+Flow&background=28a745&color=fff&size=128" alt="User Image" style="width: 40px; height: 40px;">
                                <span class="username"><a href="#">Pay Flow</a> <span class="badge badge-secondary ml-1">Pagos</span></span>
                                <span class="description">Publicado: Hoy, 10:35 AM</span>
                            </div>
                            <div class="card-tools">
                                <span class="badge badge-success" style="font-size: 1rem;">RESOLVED INCIDENT</span>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4 class="text-success"><i class="fas fa-check-circle"></i> Latencia en Procesamiento de Pagos</h4>
                            <p>Se experimentó una alta latencia en las transacciones durante 1.5 horas.</p>
                            
                            <div class="alert alert-success">
                                <h5><i class="icon fas fa-check"></i> Resolución</h5>
                                Se identificó un bloqueo en la base de datos de transacciones debido a una consulta mal optimizada. Se aplicó un hotfix y se reiniciaron los servicios.
                            </div>

                            <div class="card card-outline card-secondary collapsed-card">
                                <div class="card-header">
                                    <h3 class="card-title">Metadata Completa</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped">
                                        <tbody>
                                            <tr><th>Urgency</th><td><span class="badge badge-warning">MEDIUM</span></td></tr>
                                            <tr><th>Is Resolved</th><td><span class="badge badge-success">YES</span></td></tr>
                                            <tr><th>Started At</th><td>2025-11-22 09:00:00</td></tr>
                                            <tr><th>Ended At</th><td>2025-11-22 10:30:00</td></tr>
                                            <tr><th>Affected Services</th><td>
                                                <span class="badge badge-info">Transaction Processing Unit</span>
                                            </td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NEWS TYPE -->
                    <div class="card card-widget">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Tech+Solutions&background=17a2b8&color=fff&size=128" alt="User Image" style="width: 40px; height: 40px;">
                                <span class="username"><a href="#">Tech Solutions</a> <span class="badge badge-secondary ml-1">SaaS</span></span>
                                <span class="description">Publicado: Hoy, 08:00 AM</span>
                            </div>
                            <div class="card-tools">
                                <span class="badge badge-info" style="font-size: 1rem;">NEWS</span>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4 class="text-info"><i class="fas fa-rocket"></i> Nuevo Dashboard de Analíticas v2.0</h4>
                            <p>Estamos emocionados de anunciar el lanzamiento de nuestro nuevo dashboard.</p>
                            <p><strong>Resumen:</strong> Incluye gráficos en tiempo real, exportación a PDF/Excel y soporte nativo para modo oscuro.</p>
                            
                            <div class="mt-3 mb-3">
                                <button class="btn btn-primary">Probar Ahora (Call to Action)</button>
                            </div>

                            <div class="card card-outline card-secondary collapsed-card">
                                <div class="card-header">
                                    <h3 class="card-title">Metadata Completa</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped">
                                        <tbody>
                                            <tr><th>News Type</th><td>feature_release</td></tr>
                                            <tr><th>Target Audience</th><td>
                                                <span class="badge badge-secondary">users</span>
                                                <span class="badge badge-secondary">admins</span>
                                            </td></tr>
                                            <tr><th>Call To Action</th><td><code>{"text": "Probar Ahora", "url": "/dashboard/v2"}</code></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sidebar Column -->
                <div class="col-md-4">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Empresas que Sigues</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="products-list product-list-in-card pl-2 pr-2">
                                <li class="item">
                                    <div class="product-img">
                                        <img src="https://ui-avatars.com/api/?name=Acme+Corp&background=dc3545&color=fff" alt="Product Image" class="img-size-50">
                                    </div>
                                    <div class="product-info">
                                        <a href="javascript:void(0)" class="product-title">Acme Corp</a>
                                        <span class="product-description">Infraestructura</span>
                                    </div>
                                </li>
                                <li class="item">
                                    <div class="product-img">
                                        <img src="https://ui-avatars.com/api/?name=Tech+Solutions&background=17a2b8&color=fff" alt="Product Image" class="img-size-50">
                                    </div>
                                    <div class="product-info">
                                        <a href="javascript:void(0)" class="product-title">Tech Solutions</a>
                                        <span class="product-description">SaaS</span>
                                    </div>
                                </li>
                            </ul>
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
