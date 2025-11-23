@extends('adminlte::page')

@section('title', 'Anuncios - Cards')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Explorar Anuncios</h1>
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
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Sin Contenido</h5>
                        Actualmente no sigues a ninguna empresa. ¡Explora el directorio para encontrar servicios de interés!
                    </div>
                </div>
            </div>
        </div>

        <!-- FOLLOWING CONTENT -->
        <div id="following-content">
            <div class="row">
                
                <!-- Card 1: ALERT -->
                <div class="col-md-4">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Acme+Corp&background=dc3545&color=fff" alt="User Image">
                                <span class="username"><a href="#">Acme Corp</a></span>
                                <span class="description">Infraestructura</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="text-danger"><i class="fas fa-shield-alt"></i> Security Alert</h5>
                            <p class="card-text">Intento de acceso no autorizado. Rotación de claves requerida.</p>
                            <span class="badge badge-danger">CRITICAL</span>
                            <span class="badge badge-warning">Action Required</span>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-danger btn-sm btn-block" data-toggle="modal" data-target="#modal-alert">Ver Metadata Completa</button>
                        </div>
                    </div>
                </div>

                <!-- Card 2: MAINTENANCE -->
                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Cloud+Net&background=ffc107&color=000" alt="User Image">
                                <span class="username"><a href="#">Cloud Net</a></span>
                                <span class="description">Redes</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="text-warning"><i class="fas fa-tools"></i> Mantenimiento</h5>
                            <p class="card-text">Actualización de firmware en routers core.</p>
                            <span class="badge badge-warning">HIGH URGENCY</span>
                            <span class="badge badge-info">25 Nov</span>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-warning btn-sm btn-block" data-toggle="modal" data-target="#modal-maintenance">Ver Metadata Completa</button>
                        </div>
                    </div>
                </div>

                <!-- Card 3: NEWS -->
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <div class="user-block">
                                <img class="img-circle" src="https://ui-avatars.com/api/?name=Tech+Solutions&background=17a2b8&color=fff" alt="User Image">
                                <span class="username"><a href="#">Tech Solutions</a></span>
                                <span class="description">SaaS</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="text-info"><i class="fas fa-newspaper"></i> Novedad</h5>
                            <p class="card-text">Lanzamiento del nuevo módulo de reportes v2.0.</p>
                            <span class="badge badge-info">Feature Release</span>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-info btn-sm btn-block" data-toggle="modal" data-target="#modal-news">Ver Metadata Completa</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODALS FOR METADATA -->
    
    <!-- Alert Modal -->
    <div class="modal fade" id="modal-alert">
        <div class="modal-dialog">
            <div class="modal-content bg-danger">
                <div class="modal-header">
                    <h4 class="modal-title">Acme Corp: Security Alert</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-white text-dark">
                    <table class="table table-bordered">
                        <tbody>
                            <tr><th>Urgency</th><td>CRITICAL</td></tr>
                            <tr><th>Alert Type</th><td>Security</td></tr>
                            <tr><th>Message</th><td>Intento de acceso no autorizado.</td></tr>
                            <tr><th>Action Required</th><td>YES</td></tr>
                            <tr><th>Action Description</th><td>Rotar claves API.</td></tr>
                            <tr><th>Started At</th><td>2025-11-22 12:00:00</td></tr>
                            <tr><th>Ended At</th><td>-</td></tr>
                            <tr><th>Affected Services</th><td>API Gateway, Auth Service</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Modal -->
    <div class="modal fade" id="modal-maintenance">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h4 class="modal-title">Cloud Net: Mantenimiento</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr><th>Urgency</th><td>HIGH</td></tr>
                            <tr><th>Scheduled Start</th><td>2025-11-25 02:00:00</td></tr>
                            <tr><th>Scheduled End</th><td>2025-11-25 06:00:00</td></tr>
                            <tr><th>Is Emergency</th><td>NO</td></tr>
                            <tr><th>Actual Start</th><td>-</td></tr>
                            <tr><th>Actual End</th><td>-</td></tr>
                            <tr><th>Affected Services</th><td>Core Routers, VPN</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- News Modal -->
    <div class="modal fade" id="modal-news">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h4 class="modal-title">Tech Solutions: Novedad</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr><th>News Type</th><td>feature_release</td></tr>
                            <tr><th>Target Audience</th><td>users, admins</td></tr>
                            <tr><th>Summary</th><td>Lanzamiento del nuevo módulo de reportes v2.0.</td></tr>
                            <tr><th>Call To Action</th><td>Probar Ahora</td></tr>
                        </tbody>
                    </table>
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
