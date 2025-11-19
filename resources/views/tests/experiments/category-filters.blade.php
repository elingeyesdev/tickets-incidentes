<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio - Filtros de Categorías para Tickets</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback" rel="stylesheet">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

    <style>
        body {
            background-color: #f4f6f9;
            padding: 20px;
        }
        .option-section {
            margin-bottom: 50px;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        .option-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }
        .option-description {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .pros-cons {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .pros-cons h5 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .pros-cons ul {
            margin-bottom: 10px;
        }
        .demo-area {
            border: 2px dashed #dee2e6;
            padding: 20px;
            border-radius: 5px;
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">Laboratorio: Filtros de Categorías para Tickets</h1>
        <p class="text-muted mb-4">3 opciones para manejar filtrado por categorías (escalable para muchas categorías)</p>

        <!-- OPCIÓN 1: Select2 en Sidebar (Recomendada) -->
        <div class="option-section">
            <div class="option-title">Opción 1: Select2 en Sidebar (Recomendada)</div>
            <div class="option-description">
                Reemplaza el card completo de categorías por un Select2 compacto con búsqueda integrada. Perfecto cuando hay muchas categorías.
            </div>

            <div class="demo-area">
                <div class="row">
                    <!-- Simulación de Sidebar -->
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Folders</h3>
                            </div>
                            <div class="card-body p-0">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item active">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-inbox"></i> All Tickets
                                            <span class="badge bg-primary float-right">208</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-bell"></i> New Tickets
                                            <span class="badge bg-danger float-right">18</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Select2 para categorías -->
                        <div class="form-group">
                            <label><i class="fas fa-tags mr-2"></i>Filtrar por Categoría</label>
                            <select class="form-control select2" id="categoryFilter1" style="width: 100%;">
                                <option value="">Todas las Categorías</option>
                                <option value="1">Soporte Técnico (28)</option>
                                <option value="2">Facturación (12)</option>
                                <option value="3">Consulta General (18)</option>
                                <option value="4">Hardware (15)</option>
                                <option value="5">Software (22)</option>
                                <option value="6">Red y Conectividad (9)</option>
                                <option value="7">Base de Datos (7)</option>
                                <option value="8">Seguridad (11)</option>
                                <option value="9">Accesos y Permisos (13)</option>
                                <option value="10">Correo Electrónico (6)</option>
                                <option value="11">Impresoras (8)</option>
                                <option value="12">Telefonía (5)</option>
                                <option value="13">VPN (4)</option>
                                <option value="14">Respaldos (3)</option>
                                <option value="15">Capacitación (10)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> El select mantiene el sidebar limpio y permite búsqueda rápida de categorías
                        </div>
                    </div>
                </div>
            </div>

            <div class="pros-cons">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-success"><i class="fas fa-check-circle"></i> Ventajas</h5>
                        <ul>
                            <li>Muy compacto, no ocupa mucho espacio</li>
                            <li>Búsqueda integrada (escribe para filtrar)</li>
                            <li>Escala perfectamente (soporta cientos de categorías)</li>
                            <li>UI profesional y familiar para usuarios</li>
                            <li>Select2 ya está instalado en tu proyecto</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-warning"><i class="fas fa-exclamation-triangle"></i> Desventajas</h5>
                        <ul>
                            <li>No muestra los contadores de forma tan visual</li>
                            <li>Requiere un click extra para ver opciones</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPCIÓN 2: Select2 en el Header del Card Principal -->
        <div class="option-section">
            <div class="option-title">Opción 2: Select2 en el Header del Card Principal</div>
            <div class="option-description">
                Agrega el filtro de categorías como parte del header del card principal (junto al buscador), dejando el sidebar solo para Folders y Statuses.
            </div>

            <div class="demo-area">
                <div class="row">
                    <!-- Simulación de Sidebar -->
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Folders</h3>
                            </div>
                            <div class="card-body p-0">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item active">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-inbox"></i> All Tickets
                                            <span class="badge bg-primary float-right">208</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-bell"></i> New Tickets
                                            <span class="badge bg-danger float-right">18</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Statuses</h3>
                            </div>
                            <div class="card-body p-0">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle text-danger"></i> Open
                                            <span class="badge bg-danger float-right">15</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-circle text-warning"></i> Pending
                                            <span class="badge bg-warning float-right">23</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Card Principal con Select2 en Header -->
                    <div class="col-md-9">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Todos los Tickets</h3>
                                <div class="card-tools" style="width: 60%;">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" placeholder="Search Ticket">
                                                <div class="input-group-append">
                                                    <div class="btn btn-primary">
                                                        <i class="fas fa-search"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-control select2 select2-sm" id="categoryFilter2" style="width: 100%;">
                                                <option value="">Categoría</option>
                                                <option value="1">Soporte Técnico (28)</option>
                                                <option value="2">Facturación (12)</option>
                                                <option value="3">Consulta General (18)</option>
                                                <option value="4">Hardware (15)</option>
                                                <option value="5">Software (22)</option>
                                                <option value="6">Red y Conectividad (9)</option>
                                                <option value="7">Base de Datos (7)</option>
                                                <option value="8">Seguridad (11)</option>
                                                <option value="9">Accesos y Permisos (13)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> Los filtros principales están en el header, dejando el sidebar más limpio
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pros-cons">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-success"><i class="fas fa-check-circle"></i> Ventajas</h5>
                        <ul>
                            <li>Sidebar más limpio y organizado</li>
                            <li>Filtros activos juntos en un solo lugar</li>
                            <li>Flujo más lógico: buscar y filtrar están juntos</li>
                            <li>Escala bien con muchas categorías</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-warning"><i class="fas fa-exclamation-triangle"></i> Desventajas</h5>
                        <ul>
                            <li>El header puede verse un poco saturado</li>
                            <li>En móvil puede necesitar ajustes de layout</li>
                            <li>Menos visible que estar en el sidebar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPCIÓN 3: Dropdown Button en Toolbar -->
        <div class="option-section">
            <div class="option-title">Opción 3: Dropdown Button en Toolbar</div>
            <div class="option-description">
                Agregar un botón de categorías en el mailbox-controls (toolbar) que abre un dropdown con las categorías.
            </div>

            <div class="demo-area">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Folders</h3>
                            </div>
                            <div class="card-body p-0">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item active">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-inbox"></i> All Tickets
                                            <span class="badge bg-primary float-right">208</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-bell"></i> New Tickets
                                            <span class="badge bg-danger float-right">18</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Todos los Tickets</h3>
                            </div>
                            <div class="card-body p-0">
                                <!-- Toolbar con Dropdown de Categorías -->
                                <div class="mailbox-controls">
                                    <button type="button" class="btn btn-default btn-sm checkbox-toggle">
                                        <i class="far fa-square"></i>
                                    </button>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm">
                                            <i class="far fa-trash-alt"></i>
                                        </button>
                                    </div>

                                    <!-- Dropdown de Categorías -->
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-tags"></i> Categorías
                                        </button>
                                        <div class="dropdown-menu" style="max-height: 400px; overflow-y: auto;">
                                            <a class="dropdown-item active" href="#"><i class="fas fa-check mr-2"></i>Todas las categorías</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#"><i class="fas fa-cog text-primary mr-2"></i> Soporte Técnico <span class="badge badge-primary float-right">28</span></a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-dollar-sign text-success mr-2"></i> Facturación <span class="badge badge-success float-right">12</span></a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-question-circle text-info mr-2"></i> Consulta General <span class="badge badge-info float-right">18</span></a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-laptop text-secondary mr-2"></i> Hardware <span class="badge badge-secondary float-right">15</span></a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-code text-warning mr-2"></i> Software <span class="badge badge-warning float-right">22</span></a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-network-wired text-danger mr-2"></i> Red y Conectividad <span class="badge badge-danger float-right">9</span></a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-database text-primary mr-2"></i> Base de Datos <span class="badge badge-primary float-right">7</span></a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-shield-alt text-success mr-2"></i> Seguridad <span class="badge badge-success float-right">11</span></a>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-default btn-sm">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="alert alert-info m-3">
                                    <i class="fas fa-info-circle"></i> El filtro está integrado en el toolbar junto a las acciones
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pros-cons">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-success"><i class="fas fa-check-circle"></i> Ventajas</h5>
                        <ul>
                            <li>Integrado con las acciones del toolbar</li>
                            <li>Sidebar completamente limpio</li>
                            <li>Dropdown permite scroll para muchas categorías</li>
                            <li>Muestra contadores con badges</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-warning"><i class="fas fa-exclamation-triangle"></i> Desventajas</h5>
                        <ul>
                            <li>No tiene búsqueda integrada (si hay 50+ categorías es difícil navegar)</li>
                            <li>Menos visible que otras opciones</li>
                            <li>Requiere más clicks (abrir dropdown, scroll, seleccionar)</li>
                            <li>Puede saturar el toolbar si hay muchas acciones</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen Final -->
        <div class="option-section" style="background-color: #e3f2fd;">
            <div class="option-title" style="color: #1976d2;">
                <i class="fas fa-lightbulb"></i> Recomendación Final
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h5><strong>Opción 1: Select2 en Sidebar</strong></h5>
                    <p class="mb-2">Es la mejor opción porque:</p>
                    <ul>
                        <li><strong>Escalabilidad:</strong> Búsqueda integrada permite manejar 50+ categorías sin problema</li>
                        <li><strong>UX profesional:</strong> Select2 es un estándar en aplicaciones empresariales</li>
                        <li><strong>Compacto:</strong> Ocupa poco espacio en el sidebar</li>
                        <li><strong>Ya instalado:</strong> Select2 ya está en tu proyecto (authenticated.blade.php línea 152)</li>
                        <li><strong>Consistente:</strong> Mantiene todos los filtros en el sidebar (Folders, Statuses, Categorías)</li>
                    </ul>

                    <div class="alert alert-warning mt-3">
                        <strong>Si tienes pocas categorías (menos de 10):</strong> Podrías mantener el card de nav-pills actual.<br>
                        <strong>Si tienes muchas categorías (10+):</strong> Definitivamente usa Select2.
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2 en todos los selects
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: 'Seleccionar categoría',
                allowClear: true
            });

            // Demo: Mostrar valor seleccionado
            $('.select2').on('select2:select', function (e) {
                var data = e.params.data;
                console.log('Categoría seleccionada:', data.text);
            });
        });
    </script>
</body>
</html>
