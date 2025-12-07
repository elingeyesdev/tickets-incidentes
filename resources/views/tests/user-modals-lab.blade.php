<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laboratorio UI - User Modals</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE v3.2.0 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        .lab-section { margin-bottom: 2rem; }
        .demo-card { min-height: 100px; }
        .avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
        }
        /* Avatar serio - sin foto */
        .avatar-serious {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dee2e6;
            font-size: 2.5rem;
            border: 3px solid #495057;
        }
        .avatar-serious-sm {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            border-width: 2px;
        }
        .company-logo-sm {
            width: 24px;
            height: 24px;
            object-fit: contain;
            border-radius: 4px;
        }
        .company-logo-lg {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        .role-card {
            background: #f8f9fa;
            border-left: 4px solid;
            padding: 1rem;
            margin-bottom: 0.5rem;
        }
        .role-card.role-agent { border-left-color: #17a2b8; }
        .role-card.role-user { border-left-color: #28a745; }
        .role-card.role-company-admin { border-left-color: #ffc107; }
        .role-card.role-platform-admin { border-left-color: #dc3545; }
        /* Serious role card */
        .role-card-serious {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .role-card-serious:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
        <div class="container">
            <a href="/tests/user-modals" class="navbar-brand">
                <i class="fas fa-flask text-primary mr-2"></i>
                <span class="brand-text font-weight-bold">Laboratorio UI</span>
            </a>
            <span class="badge badge-dark">User Modals - 5 Propuestas</span>
        </div>
    </nav>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container">
                <h1 class="m-0"><i class="fas fa-user-circle"></i> Diseños de Modal de Usuario</h1>
                <p class="text-muted">Compara 5 propuestas de diseño siguiendo AdminLTE v3 · <strong>Opciones 4 y 5 son las más profesionales</strong></p>
            </div>
        </div>
        
        <div class="content">
            <div class="container">
                <!-- Datos de Prueba -->
                <div class="callout callout-info">
                    <h5><i class="fas fa-info-circle"></i> Datos de Prueba</h5>
                    <p class="mb-0">
                        <strong>Usuario:</strong> Juan Pérez García (lukqs05@gmail.com) | 
                        <strong>Roles:</strong> AGENT @ Coca-Cola, USER | 
                        <strong>Estado:</strong> Activo
                    </p>
                </div>
                
                <!-- Buttons to trigger modals - Row 1 -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#idea1Modal">
                            <i class="fas fa-eye"></i> Idea 1: Widget User
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success btn-block" data-toggle="modal" data-target="#idea2Modal">
                            <i class="fas fa-eye"></i> Idea 2: Card + Tabs
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-warning btn-block" data-toggle="modal" data-target="#idea3Modal">
                            <i class="fas fa-eye"></i> Idea 3: Sidebar Layout
                        </button>
                    </div>
                </div>
                
                <!-- Buttons to trigger modals - Row 2 (New) -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <button class="btn btn-dark btn-lg btn-block" data-toggle="modal" data-target="#idea4Modal">
                            <i class="fas fa-eye"></i> <strong>Idea 4: Sidebar SERIO (Dark)</strong>
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-secondary btn-lg btn-block" data-toggle="modal" data-target="#idea5Modal">
                            <i class="fas fa-eye"></i> <strong>Idea 5: Tabs + Timeline</strong>
                        </button>
                    </div>
                </div>
                
                <!-- Preview Cards -->
                <div class="row">
                    <!-- Idea 1 Preview -->
                    <div class="col-md-4 mb-3">
                        <div class="card card-primary card-outline demo-card">
                            <div class="card-header py-2">
                                <h3 class="card-title"><i class="fas fa-user"></i> Idea 1</h3>
                            </div>
                            <div class="card-body py-2">
                                <small>Widget User style - AdminLTE profile.html</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Idea 2 Preview -->
                    <div class="col-md-4 mb-3">
                        <div class="card card-success card-outline demo-card">
                            <div class="card-header py-2">
                                <h3 class="card-title"><i class="fas fa-folder-open"></i> Idea 2</h3>
                            </div>
                            <div class="card-body py-2">
                                <small>Card + Tabs - Material style</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Idea 3 Preview -->
                    <div class="col-md-4 mb-3">
                        <div class="card card-warning card-outline demo-card">
                            <div class="card-header py-2">
                                <h3 class="card-title"><i class="fas fa-columns"></i> Idea 3</h3>
                            </div>
                            <div class="card-body py-2">
                                <small>Sidebar Layout - Split view</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Idea 4 Preview -->
                    <div class="col-md-6 mb-3">
                        <div class="card card-dark demo-card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-tie"></i> Idea 4: SERIO (Dark)</h3>
                            </div>
                            <div class="card-body">
                                <ul class="text-sm mb-0">
                                    <li>Header negro profesional</li>
                                    <li>Colores mínimos y sobrios</li>
                                    <li>Avatar placeholder serio</li>
                                    <li>Sin ratings, logos grandes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Idea 5 Preview -->
                    <div class="col-md-6 mb-3">
                        <div class="card card-outline card-secondary demo-card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-stream"></i> Idea 5: Tabs + Timeline</h3>
                            </div>
                            <div class="card-body">
                                <ul class="text-sm mb-0">
                                    <li>Header compacto serio</li>
                                    <li>Tab Actividad con Timeline</li>
                                    <li>Logos de empresa más grandes</li>
                                    <li>Sin ratings, diseño limpio</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================================
     IDEA 1: Widget User Style (Basado en AdminLTE profile.html)
     ============================================================================ --}}
<div class="modal fade" id="idea1Modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> Detalles del Usuario</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <!-- Card Profile Widget -->
                <div class="card card-widget widget-user-2 mb-0">
                    <div class="widget-user-header bg-gradient-primary">
                        <div class="widget-user-image">
                            <div class="avatar-placeholder">JP</div>
                        </div>
                        <h3 class="widget-user-username ml-3">Juan Pérez García</h3>
                        <h5 class="widget-user-desc ml-3">
                            <code class="text-white">USR-2024-001</code> · lukqs05@gmail.com
                            <span class="badge badge-success ml-2">Activo</span>
                        </h5>
                    </div>
                    <div class="card-footer p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <span class="nav-link">
                                    <i class="fas fa-ticket-alt text-primary"></i> Tickets Creados
                                    <span class="float-right badge badge-primary">42</span>
                                </span>
                            </li>
                            <li class="nav-item">
                                <span class="nav-link">
                                    <i class="fas fa-check-circle text-success"></i> Tickets Resueltos
                                    <span class="float-right badge badge-success">38</span>
                                </span>
                            </li>
                            <li class="nav-item">
                                <span class="nav-link">
                                    <i class="fas fa-star text-warning"></i> Rating Promedio
                                    <span class="float-right text-warning">4.5 ⭐</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="row m-3">
                    <!-- About Me Box -->
                    <div class="col-md-6">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-info-circle"></i> Información</h3>
                            </div>
                            <div class="card-body">
                                <strong><i class="fas fa-phone mr-1"></i> Teléfono</strong>
                                <p class="text-muted">+591 12345678</p>
                                <hr>
                                <strong><i class="fas fa-envelope mr-1"></i> Email Verificado</strong>
                                <p class="text-muted"><span class="badge badge-success">Sí</span></p>
                                <hr>
                                <strong><i class="fas fa-sign-in-alt mr-1"></i> Último Login</strong>
                                <p class="text-muted">07/12/2024 09:30</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Roles Box -->
                    <div class="col-md-6">
                        <div class="card card-info card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-shield-alt"></i> Roles Asignados</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/ce/Coca-Cola_logo.svg/200px-Coca-Cola_logo.svg.png" 
                                         class="company-logo-sm mr-2" alt="Logo">
                                    <div>
                                        <strong>Agente de Soporte</strong><br>
                                        <small class="text-muted">Coca-Cola Bolivia</small>
                                    </div>
                                    <button class="btn btn-xs btn-outline-danger ml-auto"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                    <i class="fas fa-user-circle fa-2x text-success mr-2"></i>
                                    <div>
                                        <strong>Cliente</strong><br>
                                        <small class="text-muted">Sin empresa asociada</small>
                                    </div>
                                    <button class="btn btn-xs btn-outline-danger ml-auto"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Creado: 01/12/2024 · Actualizado: 07/12/2024</small>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning"><i class="fas fa-ban"></i> Cambiar Estado</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================================
     IDEA 2: Card + Tabs (Material/Clean style)
     ============================================================================ --}}
<div class="modal fade" id="idea2Modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Compact Header -->
            <div class="modal-header">
                <div class="d-flex align-items-center w-100">
                    <div class="avatar-placeholder mr-3" style="width: 60px; height: 60px; font-size: 1.5rem;">JP</div>
                    <div class="flex-grow-1">
                        <h4 class="mb-0">Juan Pérez García <span class="badge badge-success">Activo</span></h4>
                        <small class="text-muted">
                            <code>USR-2024-001</code> · lukqs05@gmail.com · 
                            <i class="fas fa-check-circle text-success"></i> Email verificado
                        </small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="card-header border-bottom">
                <ul class="nav nav-pills" id="idea2Tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#tab2-general">
                            <i class="fas fa-info-circle"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab2-roles">
                            <i class="fas fa-shield-alt"></i> Roles <span class="badge badge-info">2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab2-activity">
                            <i class="fas fa-history"></i> Actividad
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Tab Content -->
            <div class="modal-body">
                <div class="tab-content">
                    <!-- Tab: General -->
                    <div class="tab-pane fade show active" id="tab2-general">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted mb-3">Contacto</h6>
                                <dl class="row">
                                    <dt class="col-sm-4">Teléfono</dt>
                                    <dd class="col-sm-8">+591 12345678</dd>
                                    <dt class="col-sm-4">Email</dt>
                                    <dd class="col-sm-8">lukqs05@gmail.com</dd>
                                    <dt class="col-sm-4">Auth Provider</dt>
                                    <dd class="col-sm-8">Sistema</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted mb-3">Estadísticas</h6>
                                <dl class="row">
                                    <dt class="col-sm-5">Tickets</dt>
                                    <dd class="col-sm-7"><strong class="text-primary">42</strong></dd>
                                    <dt class="col-sm-5">Resueltos</dt>
                                    <dd class="col-sm-7"><strong class="text-success">38</strong></dd>
                                </dl>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted mb-3">Preferencias</h6>
                                <dl class="row">
                                    <dt class="col-sm-4">Tema</dt>
                                    <dd class="col-sm-8">dark</dd>
                                    <dt class="col-sm-4">Idioma</dt>
                                    <dd class="col-sm-8">Español (es)</dd>
                                    <dt class="col-sm-4">Zona Horaria</dt>
                                    <dd class="col-sm-8">America/La_Paz</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted mb-3">Fechas</h6>
                                <dl class="row">
                                    <dt class="col-sm-5">Creado</dt>
                                    <dd class="col-sm-7">01/12/2024</dd>
                                    <dt class="col-sm-5">Actualizado</dt>
                                    <dd class="col-sm-7">07/12/2024</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Roles (Updated with large logos from Idea 5) -->
                    <div class="tab-pane fade" id="tab2-roles">
                        <!-- Role 1: Agent -->
                        <div class="role-card-serious">
                            <div class="d-flex">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/ce/Coca-Cola_logo.svg/200px-Coca-Cola_logo.svg.png" 
                                     class="company-logo-lg mr-4" alt="Logo">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">Agente de Soporte</h5>
                                            <h6 class="text-secondary mb-2">Coca-Cola Bolivia</h6>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Quitar
                                        </button>
                                    </div>
                                    <div class="text-sm text-muted">
                                        <i class="fas fa-industry"></i> Alimentos y Bebidas · 
                                        <i class="fas fa-hashtag"></i> CMP-001 · 
                                        <i class="fas fa-calendar"></i> 15/01/2024
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Role 2: User -->
                        <div class="role-card-serious">
                            <div class="d-flex">
                                <div class="company-logo-lg mr-4 bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user fa-2x text-secondary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">Cliente</h5>
                                            <h6 class="text-muted mb-2">Sin empresa asociada</h6>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Quitar
                                        </button>
                                    </div>
                                    <div class="text-sm text-muted">
                                        <i class="fas fa-calendar"></i> 01/12/2024
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Add Role -->
                        <div class="row">
                            <div class="col-md-5">
                                <select class="form-control form-control-sm">
                                    <option value="">Rol...</option>
                                    <option>AGENT</option>
                                    <option>COMPANY_ADMIN</option>
                                    <option>PLATFORM_ADMIN</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <select class="form-control form-control-sm">
                                    <option value="">Empresa...</option>
                                    <option>Coca-Cola Bolivia</option>
                                    <option>Banco Nacional</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-success btn-sm btn-block">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Activity (Updated with Timeline from Idea 5) -->
                    <div class="tab-pane fade" id="tab2-activity">
                        <!-- AdminLTE Timeline -->
                        <div class="timeline">
                            <!-- Timeline time label -->
                            <div class="time-label">
                                <span class="bg-secondary">Hoy</span>
                            </div>
                            
                            <!-- Timeline Item 1 -->
                            <div>
                                <i class="fas fa-sign-in-alt bg-info"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 09:30</span>
                                    <h3 class="timeline-header">Inicio de sesión</h3>
                                    <div class="timeline-body">
                                        El usuario inició sesión desde IP 192.168.1.100
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline Item 2 -->
                            <div>
                                <i class="fas fa-ticket-alt bg-primary"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 09:45</span>
                                    <h3 class="timeline-header">Ticket asignado</h3>
                                    <div class="timeline-body">
                                        Se asignó el ticket <code>TKT-2024-0042</code>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline time label -->
                            <div class="time-label">
                                <span class="bg-secondary">Ayer</span>
                            </div>
                            
                            <!-- Timeline Item 3 -->
                            <div>
                                <i class="fas fa-check bg-success"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 17:20</span>
                                    <h3 class="timeline-header">Ticket resuelto</h3>
                                    <div class="timeline-body">
                                        Ticket <code>TKT-2024-0041</code> marcado como resuelto
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline Item 4 -->
                            <div>
                                <i class="fas fa-sign-out-alt bg-secondary"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 18:00</span>
                                    <h3 class="timeline-header">Cierre de sesión</h3>
                                </div>
                            </div>
                            
                            <!-- End timeline -->
                            <div>
                                <i class="fas fa-ellipsis-h bg-gray"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning"><i class="fas fa-ban"></i> Cambiar Estado</button>
                <button type="button" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================================
     IDEA 3: Sidebar Layout (Split View)
     ============================================================================ --}}
<div class="modal fade" id="idea3Modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning">
                <h5 class="modal-title"><i class="fas fa-user-cog"></i> Gestión de Usuario</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="row no-gutters">
                    <!-- Left Sidebar: User Card -->
                    <div class="col-md-4 bg-light border-right">
                        <div class="p-4 text-center">
                            <div class="avatar-placeholder mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">JP</div>
                            <h4 class="mb-1">Juan Pérez García</h4>
                            <p class="text-muted mb-2">
                                <code>USR-2024-001</code>
                            </p>
                            <span class="badge badge-success badge-lg">
                                <i class="fas fa-check-circle"></i> Activo
                            </span>
                        </div>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span><i class="fas fa-envelope text-primary"></i> Email</span>
                                <span class="text-muted text-truncate ml-2" style="max-width: 150px;">lukqs05@gmail.com</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><i class="fas fa-phone text-success"></i> Teléfono</span>
                                <span class="text-muted">+591 12345678</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><i class="fas fa-check-circle text-info"></i> Email</span>
                                <span class="badge badge-success">Verificado</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><i class="fas fa-key text-warning"></i> Auth</span>
                                <span class="text-muted">Sistema</span>
                            </li>
                        </ul>
                        
                        <div class="p-3">
                            <div class="info-box bg-gradient-info mb-2">
                                <span class="info-box-icon"><i class="fas fa-ticket-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tickets</span>
                                    <span class="info-box-number">42</span>
                                </div>
                            </div>
                            <div class="info-box bg-gradient-success mb-2">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Resueltos</span>
                                    <span class="info-box-number">38</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 border-top bg-white">
                            <small class="text-muted d-block">
                                <i class="fas fa-calendar"></i> Creado: 01/12/2024
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-clock"></i> Último login: 07/12/2024 09:30
                            </small>
                        </div>
                    </div>
                    
                    <!-- Right Content: Roles & Actions -->
                    <div class="col-md-8">
                        <div class="p-4">
                            <h5 class="mb-4">
                                <i class="fas fa-shield-alt text-info"></i> Roles Asignados
                                <span class="badge badge-secondary">2</span>
                            </h5>
                            
                            <!-- Role Cards -->
                            <div class="card mb-3 border-left border-info" style="border-left-width: 4px !important;">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/ce/Coca-Cola_logo.svg/200px-Coca-Cola_logo.svg.png" 
                                             style="width: 60px; height: 60px; object-fit: contain;" 
                                             class="rounded border p-1 mr-3" alt="Logo">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="mb-1">Agente de Soporte</h5>
                                                    <h6 class="text-info mb-2">Coca-Cola Bolivia</h6>
                                                </div>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row text-sm text-muted">
                                                <div class="col-md-4">
                                                    <i class="fas fa-industry"></i> Alimentos y Bebidas
                                                </div>
                                                <div class="col-md-4">
                                                    <i class="fas fa-code"></i> CMP-001
                                                </div>
                                                <div class="col-md-4">
                                                    <i class="fas fa-calendar-plus"></i> 15/01/2024
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-3 border-left border-success" style="border-left-width: 4px !important;">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="rounded border p-2 mr-3 bg-light text-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-user fa-2x text-success mt-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="mb-1">Cliente</h5>
                                                    <h6 class="text-muted mb-2">Sin empresa asociada</h6>
                                                </div>
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="row text-sm text-muted">
                                                <div class="col-md-6">
                                                    <i class="fas fa-info-circle"></i> Rol básico de sistema
                                                </div>
                                                <div class="col-md-6">
                                                    <i class="fas fa-calendar-plus"></i> 01/12/2024
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Add New Role -->
                            <h5 class="mb-3"><i class="fas fa-plus-circle text-success"></i> Asignar Nuevo Rol</h5>
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Rol</label>
                                        <select class="form-control">
                                            <option value="">-- Seleccionar --</option>
                                            <option>AGENT - Agente de Soporte</option>
                                            <option>COMPANY_ADMIN - Admin de Empresa</option>
                                            <option>PLATFORM_ADMIN - Admin de Plataforma</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Empresa</label>
                                        <select class="form-control">
                                            <option value="">-- Seleccionar --</option>
                                            <option>Coca-Cola Bolivia</option>
                                            <option>Banco Nacional</option>
                                            <option>Telefónica</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-success btn-block mb-3">
                                        <i class="fas fa-plus"></i> Asignar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning"><i class="fas fa-ban"></i> Suspender</button>
                <button type="button" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================================
     IDEA 4: Sidebar SERIO (Dark/Professional)
     - Header negro, colores mínimos, avatar serio, sin ratings, logos grandes
     ============================================================================ --}}
<div class="modal fade" id="idea4Modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Dark Header -->
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title"><i class="fas fa-user-tie mr-2"></i> Gestión de Usuario</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="row no-gutters">
                    <!-- Left Sidebar: User Card (Dark theme) -->
                    <div class="col-md-4 bg-dark text-white">
                        <div class="p-4 text-center border-bottom border-secondary">
                            <!-- Avatar Serio -->
                            <div class="avatar-serious mx-auto mb-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4 class="mb-1 text-white">Juan Pérez García</h4>
                            <p class="text-light mb-2">
                                <code class="text-info">USR-2024-001</code>
                            </p>
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i> Activo
                            </span>
                        </div>
                        
                        <ul class="list-group list-group-flush bg-dark">
                            <li class="list-group-item bg-dark text-white d-flex justify-content-between">
                                <span><i class="fas fa-envelope mr-2 text-muted"></i> Email</span>
                                <span class="text-light text-truncate ml-2" style="max-width: 150px;">lukqs05@gmail.com</span>
                            </li>
                            <li class="list-group-item bg-dark text-white d-flex justify-content-between">
                                <span><i class="fas fa-phone mr-2 text-muted"></i> Teléfono</span>
                                <span class="text-light">+591 12345678</span>
                            </li>
                            <li class="list-group-item bg-dark text-white d-flex justify-content-between">
                                <span><i class="fas fa-check-circle mr-2 text-muted"></i> Email</span>
                                <span class="badge badge-success">Verificado</span>
                            </li>
                            <li class="list-group-item bg-dark text-white d-flex justify-content-between">
                                <span><i class="fas fa-key mr-2 text-muted"></i> Auth</span>
                                <span class="text-light">Sistema</span>
                            </li>
                        </ul>
                        
                        <!-- Stats sobrios -->
                        <div class="p-3 border-top border-secondary">
                            <div class="row text-center">
                                <div class="col-6 border-right border-secondary">
                                    <h4 class="mb-0 text-info">42</h4>
                                    <small class="text-muted">Tickets</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-success">38</h4>
                                    <small class="text-muted">Resueltos</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 border-top border-secondary">
                            <small class="text-muted d-block">
                                <i class="far fa-calendar-alt mr-1"></i> Creado: 01/12/2024
                            </small>
                            <small class="text-muted d-block">
                                <i class="far fa-clock mr-1"></i> Último login: 07/12/2024 09:30
                            </small>
                        </div>
                    </div>
                    
                    <!-- Right Content: Roles -->
                    <div class="col-md-8">
                        <div class="p-4">
                            <h5 class="mb-4 text-dark">
                                <i class="fas fa-shield-alt text-secondary"></i> Roles Asignados
                                <span class="badge badge-secondary ml-1">2</span>
                            </h5>
                            
                            <!-- Role 1: Agent (Logo grande) -->
                            <div class="role-card-serious">
                                <div class="d-flex">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/ce/Coca-Cola_logo.svg/200px-Coca-Cola_logo.svg.png" 
                                         class="company-logo-lg mr-4" alt="Logo">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1 text-dark">Agente de Soporte</h5>
                                                <h6 class="text-secondary mb-2">Coca-Cola Bolivia</h6>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Quitar
                                            </button>
                                        </div>
                                        <div class="row text-sm text-muted mt-2">
                                            <div class="col-md-4">
                                                <i class="fas fa-industry"></i> Alimentos y Bebidas
                                            </div>
                                            <div class="col-md-4">
                                                <i class="fas fa-hashtag"></i> CMP-001
                                            </div>
                                            <div class="col-md-4">
                                                <i class="fas fa-calendar-plus"></i> 15/01/2024
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Role 2: User -->
                            <div class="role-card-serious">
                                <div class="d-flex">
                                    <div class="company-logo-lg mr-4 bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user fa-2x text-secondary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1 text-dark">Cliente</h5>
                                                <h6 class="text-muted mb-2">Sin empresa asociada</h6>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Quitar
                                            </button>
                                        </div>
                                        <div class="text-sm text-muted mt-2">
                                            <i class="fas fa-info-circle"></i> Rol básico de sistema · 
                                            <i class="fas fa-calendar-plus"></i> 01/12/2024
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Add New Role -->
                            <h6 class="text-muted mb-3"><i class="fas fa-plus-circle"></i> Asignar Nuevo Rol</h6>
                            <div class="row">
                                <div class="col-md-5">
                                    <select class="form-control form-control-sm">
                                        <option value="">Rol...</option>
                                        <option>AGENT</option>
                                        <option>COMPANY_ADMIN</option>
                                        <option>PLATFORM_ADMIN</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <select class="form-control form-control-sm">
                                        <option value="">Empresa...</option>
                                        <option>Coca-Cola Bolivia</option>
                                        <option>Banco Nacional</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-dark btn-sm btn-block">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning"><i class="fas fa-ban"></i> Suspender</button>
                <button type="button" class="btn btn-dark"><i class="fas fa-trash"></i> Eliminar</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================================
     IDEA 5: Tabs + Timeline 
     - Header compacto serio, Actividad con Timeline, logos grandes, sin ratings
     ============================================================================ --}}
<div class="modal fade" id="idea5Modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Compact Serious Header -->
            <div class="modal-header bg-light border-bottom">
                <div class="d-flex align-items-center w-100">
                    <!-- Avatar Serio Small -->
                    <div class="avatar-serious avatar-serious-sm mr-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-0 text-dark">Juan Pérez García <span class="badge badge-success">Activo</span></h5>
                        <small class="text-muted">
                            <code>USR-2024-001</code> · lukqs05@gmail.com · 
                            <i class="fas fa-check-circle text-success"></i> Verificado
                        </small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="card-header border-bottom p-0">
                <ul class="nav nav-tabs" id="idea5Tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab5-general">
                            <i class="fas fa-info-circle"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab5-roles">
                            <i class="fas fa-shield-alt"></i> Roles <span class="badge badge-dark">2</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab5-activity">
                            <i class="fas fa-history"></i> Actividad
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Tab Content -->
            <div class="modal-body">
                <div class="tab-content">
                    <!-- Tab: General -->
                    <div class="tab-pane fade show active" id="tab5-general">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Contacto</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4 text-muted">Teléfono</dt>
                                    <dd class="col-sm-8">+591 12345678</dd>
                                    <dt class="col-sm-4 text-muted">Email</dt>
                                    <dd class="col-sm-8">lukqs05@gmail.com</dd>
                                    <dt class="col-sm-4 text-muted">Auth</dt>
                                    <dd class="col-sm-8">Sistema</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Estadísticas</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted">Tickets</dt>
                                    <dd class="col-sm-7"><strong>42</strong></dd>
                                    <dt class="col-sm-5 text-muted">Resueltos</dt>
                                    <dd class="col-sm-7"><strong>38</strong></dd>
                                </dl>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Preferencias</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4 text-muted">Tema</dt>
                                    <dd class="col-sm-8">dark</dd>
                                    <dt class="col-sm-4 text-muted">Idioma</dt>
                                    <dd class="col-sm-8">es</dd>
                                    <dt class="col-sm-4 text-muted">Zona</dt>
                                    <dd class="col-sm-8">America/La_Paz</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small mb-3">Fechas</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted">Creado</dt>
                                    <dd class="col-sm-7">01/12/2024</dd>
                                    <dt class="col-sm-5 text-muted">Actualizado</dt>
                                    <dd class="col-sm-7">07/12/2024</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Roles (Logos más grandes) -->
                    <div class="tab-pane fade" id="tab5-roles">
                        <!-- Role 1: Agent -->
                        <div class="role-card-serious">
                            <div class="d-flex">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/ce/Coca-Cola_logo.svg/200px-Coca-Cola_logo.svg.png" 
                                     class="company-logo-lg mr-4" alt="Logo">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">Agente de Soporte</h5>
                                            <h6 class="text-secondary mb-2">Coca-Cola Bolivia</h6>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="text-sm text-muted">
                                        <i class="fas fa-industry"></i> Alimentos y Bebidas · 
                                        <i class="fas fa-hashtag"></i> CMP-001 · 
                                        <i class="fas fa-calendar"></i> 15/01/2024
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Role 2: User -->
                        <div class="role-card-serious">
                            <div class="d-flex">
                                <div class="company-logo-lg mr-4 bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user fa-2x text-secondary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">Cliente</h5>
                                            <h6 class="text-muted mb-2">Sin empresa asociada</h6>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="text-sm text-muted">
                                        <i class="fas fa-calendar"></i> 01/12/2024
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Add Role -->
                        <div class="row">
                            <div class="col-md-5">
                                <select class="form-control form-control-sm">
                                    <option value="">Rol...</option>
                                    <option>AGENT</option>
                                    <option>COMPANY_ADMIN</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <select class="form-control form-control-sm">
                                    <option value="">Empresa...</option>
                                    <option>Coca-Cola Bolivia</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-dark btn-sm btn-block">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Activity (Timeline) -->
                    <div class="tab-pane fade" id="tab5-activity">
                        <!-- AdminLTE Timeline -->
                        <div class="timeline">
                            <!-- Timeline time label -->
                            <div class="time-label">
                                <span class="bg-secondary">Hoy</span>
                            </div>
                            
                            <!-- Timeline Item 1 -->
                            <div>
                                <i class="fas fa-sign-in-alt bg-info"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 09:30</span>
                                    <h3 class="timeline-header">Inicio de sesión</h3>
                                    <div class="timeline-body">
                                        El usuario inició sesión desde IP 192.168.1.100
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline Item 2 -->
                            <div>
                                <i class="fas fa-ticket-alt bg-primary"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 09:45</span>
                                    <h3 class="timeline-header">Ticket asignado</h3>
                                    <div class="timeline-body">
                                        Se asignó el ticket <code>TKT-2024-0042</code>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline time label -->
                            <div class="time-label">
                                <span class="bg-secondary">Ayer</span>
                            </div>
                            
                            <!-- Timeline Item 3 -->
                            <div>
                                <i class="fas fa-check bg-success"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 17:20</span>
                                    <h3 class="timeline-header">Ticket resuelto</h3>
                                    <div class="timeline-body">
                                        Ticket <code>TKT-2024-0041</code> marcado como resuelto
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline Item 4 -->
                            <div>
                                <i class="fas fa-sign-out-alt bg-secondary"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"></i> 18:00</span>
                                    <h3 class="timeline-header">Cierre de sesión</h3>
                                </div>
                            </div>
                            
                            <!-- End timeline -->
                            <div>
                                <i class="fas fa-ellipsis-h bg-gray"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-dark" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning"><i class="fas fa-ban"></i> Suspender</button>
                <button type="button" class="btn btn-dark"><i class="fas fa-trash"></i> Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

</body>
</html>
