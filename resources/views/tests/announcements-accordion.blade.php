@extends('adminlte::page')

@section('title', 'Anuncios - Accordion')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Anuncios Importantes</h1>
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
            <div class="jumbotron text-center bg-white">
                <h1 class="display-4">¡Hola!</h1>
                <p class="lead">Parece que tu feed de noticias está vacío.</p>
            </div>
        </div>

        <!-- FOLLOWING CONTENT -->
        <div id="following-content">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    
                    <div id="accordion">
                        
                        <!-- Item 1: ALERT -->
                        <div class="card card-danger">
                            <div class="card-header">
                                <h4 class="card-title w-100">
                                    <a class="d-block w-100 text-white" data-toggle="collapse" href="#collapseOne">
                                        <img class="img-circle mr-2" src="https://ui-avatars.com/api/?name=Acme+Corp&background=fff&color=dc3545" style="width: 30px;">
                                        <strong>Acme Corp</strong>: Security Alert
                                        <span class="float-right text-sm font-weight-normal"><i class="far fa-clock"></i> 22 Nov</span>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseOne" class="collapse show" data-parent="#accordion">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <h5 class="text-danger">Intento de Acceso No Autorizado</h5>
                                            <p>Detectamos un intento de acceso no autorizado. Se requiere acción inmediata.</p>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="card card-outline card-secondary">
                                                <div class="card-header">
                                                    <h3 class="card-title">Metadata Completa</h3>
                                                </div>
                                                <div class="card-body p-0">
                                                    <table class="table table-sm table-bordered">
                                                        <tbody>
                                                            <tr><th style="width: 200px">Urgency</th><td>CRITICAL</td></tr>
                                                            <tr><th>Alert Type</th><td>Security</td></tr>
                                                            <tr><th>Message</th><td>Intento de acceso no autorizado.</td></tr>
                                                            <tr><th>Action Required</th><td><span class="badge badge-danger">YES</span></td></tr>
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
                                </div>
                            </div>
                        </div>

                        <!-- Item 2: MAINTENANCE -->
                        <div class="card card-warning">
                            <div class="card-header">
                                <h4 class="card-title w-100">
                                    <a class="d-block w-100 text-dark" data-toggle="collapse" href="#collapseTwo">
                                        <img class="img-circle mr-2" src="https://ui-avatars.com/api/?name=Cloud+Net&background=fff&color=000" style="width: 30px;">
                                        <strong>Cloud Net</strong>: Mantenimiento
                                        <span class="float-right text-sm font-weight-normal"><i class="far fa-calendar"></i> 25 Nov</span>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseTwo" class="collapse" data-parent="#accordion">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <h5>Actualización de Firmware</h5>
                                        </div>
                                        <div class="col-md-12">
                                            <table class="table table-sm table-bordered">
                                                <tbody>
                                                    <tr><th style="width: 200px">Urgency</th><td>HIGH</td></tr>
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
                        </div>

                        <!-- Item 3: NEWS -->
                        <div class="card card-info">
                            <div class="card-header">
                                <h4 class="card-title w-100">
                                    <a class="d-block w-100 text-white" data-toggle="collapse" href="#collapseThree">
                                        <img class="img-circle mr-2" src="https://ui-avatars.com/api/?name=Tech+Solutions&background=fff&color=17a2b8" style="width: 30px;">
                                        <strong>Tech Solutions</strong>: Novedad
                                        <span class="float-right text-sm font-weight-normal"><i class="far fa-clock"></i> Today</span>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapseThree" class="collapse" data-parent="#accordion">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <h5>Nuevo Dashboard v2.0</h5>
                                        </div>
                                        <div class="col-md-12">
                                            <table class="table table-sm table-bordered">
                                                <tbody>
                                                    <tr><th style="width: 200px">News Type</th><td>feature_release</td></tr>
                                                    <tr><th>Target Audience</th><td>users, admins</td></tr>
                                                    <tr><th>Summary</th><td>Lanzamiento del nuevo módulo de reportes v2.0.</td></tr>
                                                    <tr><th>Call To Action</th><td>Probar Ahora</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
