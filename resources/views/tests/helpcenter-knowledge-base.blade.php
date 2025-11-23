@extends('adminlte::page')

@section('title', 'Centro de Ayuda - Knowledge Base')

@section('css')
<style>
    /* Header */
    .kb-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 50px 0;
        margin: -15px -15px 30px -15px;
        color: white;
    }
    
    .kb-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: white;
        margin-bottom: 10px;
    }
    
    .kb-header p {
        font-size: 1.1rem;
        opacity: 0.95;
    }

    /* Search Box */
    .kb-search {
        max-width: 800px;
        margin: 30px auto 0;
    }
    
    .kb-search .input-group {
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .kb-search .form-control {
        height: 60px;
        font-size: 1.1rem;
        border: none;
        padding-left: 25px;
    }
    
    .kb-search .input-group-append button {
        height: 60px;
        padding: 0 35px;
        background: #28a745;
        border: none;
        font-weight: 600;
    }
    
    .kb-search .input-group-append button:hover {
        background: #218838;
    }

    /* Featured Articles */
    .featured-article {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
        border: 2px solid transparent;
    }
    
    .featured-article:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border-color: #667eea;
    }
    
    .featured-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 10;
    }
    
    .article-icon {
        width: 80px;
        height: 80px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin: 30px auto 20px;
    }
    
    .featured-article h4 {
        font-weight: 700;
        margin-bottom: 15px;
        color: #343a40;
    }
    
    .featured-article p {
        color: #6c757d;
        margin-bottom: 20px;
    }
    
    .featured-article .card-footer {
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 15px 20px;
    }

    /* Category Grid */
    .category-grid-item {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .category-grid-item:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transform: translateY(-3px);
    }
    
    .category-grid-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .category-grid-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 15px;
    }
    
    .category-grid-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
        color: #343a40;
    }
    
    .article-link-item {
        padding: 10px 0;
        border-bottom: 1px dashed #e9ecef;
    }
    
    .article-link-item:last-child {
        border-bottom: none;
    }
    
    .article-link-item a {
        color: #495057;
        font-weight: 500;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .article-link-item a:hover {
        color: #667eea;
        padding-left: 10px;
    }
    
    .article-link-item .article-views {
        font-size: 0.85rem;
        color: #adb5bd;
    }

    /* Browse All Button */
    .browse-all-btn {
        padding: 8px 15px;
        font-size: 0.9rem;
        border-radius: 20px;
        font-weight: 600;
    }

    /* Support CTA */
    .support-cta-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        margin-top: 40px;
    }
    
    .support-cta-card .card-body {
        padding: 40px;
        text-align: center;
    }
    
    .support-cta-card h3 {
        color: white;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .support-cta-card p {
        opacity: 0.95;
        margin-bottom: 10px;
    }

    /* Sidebar */
    .sidebar-widget {
        margin-bottom: 25px;
    }
    
    .trending-badge {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Stats */
    .stats-row {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 30px;
    }
    
    .stat-item {
        text-align: center;
        color: white;
    }
    
    .stat-item h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: white;
    }
    
    .stat-item p {
        margin: 0;
        opacity: 0.9;
    }
</style>
@stop

@section('content')
    <div class="container-fluid">
        
        <!-- Knowledge Base Header -->
        <div class="kb-header">
            <div class="container">
                <div class="text-center">
                    <h1><i class="fas fa-book-open mr-3"></i>Centro de Ayuda</h1>
                    <p>Todo lo que necesitas saber para aprovechar al máximo nuestra plataforma</p>
                    
                    <!-- Search -->
                    <div class="kb-search">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="¿Qué necesitas saber hoy?" id="kb-search-input">
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button">
                                    <i class="fas fa-search mr-2"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="stats-row">
                        <div class="stat-item">
                            <h2>88</h2>
                            <p>Artículos</p>
                        </div>
                        <div class="stat-item">
                            <h2>4</h2>
                            <p>Categorías</p>
                        </div>
                        <div class="stat-item">
                            <h2>2.5k+</h2>
                            <p>Lecturas/Mes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            
            <!-- Main Content -->
            <div class="col-lg-9">
                
                <!-- Featured Articles -->
                <div class="mb-4">
                    <h3 class="mb-3">
                        <i class="fas fa-star text-warning mr-2"></i>Artículos Destacados
                    </h3>
                    
                    <div class="row">
                        <!-- Featured 1 -->
                        <div class="col-md-4 mb-4">
                            <div class="card featured-article">
                                <span class="badge badge-warning featured-badge">
                                    <i class="fas fa-star mr-1"></i>Popular
                                </span>
                                <div class="card-body text-center">
                                    <div class="article-icon bg-danger">
                                        <i class="fas fa-shield-alt text-white"></i>
                                    </div>
                                    <h4>Activar 2FA</h4>
                                    <p>Protege tu cuenta con autenticación de dos factores. Configuración paso a paso.</p>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-eye mr-1"></i>1.2k vistas
                                        </small>
                                        <a href="#" class="btn btn-sm btn-outline-danger">
                                            Leer más <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Featured 2 -->
                        <div class="col-md-4 mb-4">
                            <div class="card featured-article">
                                <span class="badge badge-info featured-badge">
                                    <i class="fas fa-lightbulb mr-1"></i>Nuevo
                                </span>
                                <div class="card-body text-center">
                                    <div class="article-icon bg-primary">
                                        <i class="fas fa-user-edit text-white"></i>
                                    </div>
                                    <h4>Gestión de Perfil</h4>
                                    <p>Aprende a personalizar y mantener actualizada tu información de perfil.</p>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-eye mr-1"></i>673 vistas
                                        </small>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            Leer más <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Featured 3 -->
                        <div class="col-md-4 mb-4">
                            <div class="card featured-article">
                                <span class="badge badge-success featured-badge">
                                    <i class="fas fa-check mr-1"></i>Esencial
                                </span>
                                <div class="card-body text-center">
                                    <div class="article-icon bg-success">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                    <h4>Facturación</h4>
                                    <p>Todo sobre facturación mensual, métodos de pago y descarga de facturas.</p>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-eye mr-1"></i>551 vistas
                                        </small>
                                        <a href="#" class="btn btn-sm btn-outline-success">
                                            Leer más <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Browse by Category -->
                <div class="mt-5">
                    <h3 class="mb-4">
                        <i class="fas fa-th-large text-primary mr-2"></i>Explorar por Categoría
                    </h3>

                    <!-- Account & Profile Category -->
                    <div class="category-grid-item">
                        <div class="category-grid-header">
                            <div class="category-grid-icon bg-primary">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="category-grid-title">Account & Profile</h4>
                                <p class="text-muted mb-0 small">Gestión de cuenta y configuraciones personales</p>
                            </div>
                            <div>
                                <span class="badge badge-primary badge-pill">24 artículos</span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-primary"></i>Actualizar información de perfil</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 673</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-primary"></i>Gestionar preferencias de notificaciones</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 445</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-primary"></i>Configurar foto de perfil</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 312</span>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-primary"></i>Cambiar idioma de la interfaz</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 289</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-primary"></i>Zona horaria y formato de fecha</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 201</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-primary"></i>Eliminar mi cuenta</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 156</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-primary browse-all-btn">
                                Ver todos los artículos <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Security & Privacy Category -->
                    <div class="category-grid-item">
                        <div class="category-grid-header">
                            <div class="category-grid-icon bg-danger">
                                <i class="fas fa-shield-alt text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="category-grid-title">Security & Privacy</h4>
                                <p class="text-muted mb-0 small">Protege tu cuenta y tus datos personales</p>
                            </div>
                            <div>
                                <span class="badge badge-danger badge-pill">18 artículos</span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-danger"></i>Autenticación de dos factores (2FA)</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 1.2k</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-danger"></i>Cambiar contraseña de forma segura</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 892</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-danger"></i>Recuperar contraseña olvidada</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 487</span>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-danger"></i>Gestionar sesiones activas</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 334</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-danger"></i>Configuración de privacidad</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 278</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-danger"></i>Reportar actividad sospechosa</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 145</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-danger browse-all-btn">
                                Ver todos los artículos <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Billing & Payments Category -->
                    <div class="category-grid-item">
                        <div class="category-grid-header">
                            <div class="category-grid-icon bg-success">
                                <i class="fas fa-credit-card text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="category-grid-title">Billing & Payments</h4>
                                <p class="text-muted mb-0 small">Facturación, pagos y suscripciones</p>
                            </div>
                            <div>
                                <span class="badge badge-success badge-pill">15 artículos</span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-success"></i>Cómo funciona la facturación mensual</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 551</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-success"></i>Actualizar método de pago</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 423</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-success"></i>Descargar facturas</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 389</span>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-success"></i>Cambiar plan de suscripción</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 312</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-success"></i>Reembolsos y cancelaciones</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 267</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-success"></i>Impuestos y facturación internacional</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 198</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-success browse-all-btn">
                                Ver todos los artículos <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Technical Support Category -->
                    <div class="category-grid-item">
                        <div class="category-grid-header">
                            <div class="category-grid-icon bg-warning">
                                <i class="fas fa-tools text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="category-grid-title">Technical Support</h4>
                                <p class="text-muted mb-0 small">Solución de problemas técnicos y guías</p>
                            </div>
                            <div>
                                <span class="badge badge-warning badge-pill">31 artículos</span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-warning"></i>Troubleshooting de conexión</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 412</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-warning"></i>Configuración de API</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 389</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-warning"></i>Problemas con adjuntos de archivos</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 301</span>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-warning"></i>Requisitos del sistema</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 278</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-warning"></i>Exportar datos</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 245</span>
                                    </a>
                                </div>
                                <div class="article-link-item">
                                    <a href="#">
                                        <span><i class="far fa-file-alt mr-2 text-warning"></i>Integraciones disponibles</span>
                                        <span class="article-views"><i class="fas fa-eye"></i> 223</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-warning browse-all-btn">
                                Ver todos los artículos <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Support CTA -->
                <div class="card support-cta-card">
                    <div class="card-body">
                        <h3><i class="fas fa-question-circle mr-2"></i>¿No encontraste una solución?</h3>
                        <p>No te preocupes, estamos aquí para ayudarte personalmente.</p>
                        <p class="small mb-4">
                            Nuestro equipo de soporte responde en un promedio de <strong>2-4 horas</strong>
                        </p>
                        <a href="#" class="btn btn-light btn-lg">
                            <i class="fas fa-ticket-alt mr-2"></i>Crear Ticket de Soporte
                        </a>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                
                <!-- Quick Actions -->
                <div class="card card-primary card-outline sidebar-widget">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt text-warning mr-2"></i>Acciones Rápidas
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-ticket-alt text-primary mr-2"></i>
                                Crear Ticket
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-comments text-success mr-2"></i>
                                Chat en Vivo
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-video text-danger mr-2"></i>
                                Video Tutoriales
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-download text-info mr-2"></i>
                                Recursos Descargables
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Trending This Week -->
                <div class="card card-warning card-outline sidebar-widget">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-fire text-warning mr-2 trending-badge"></i>Trending
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-weight-bold">Activar 2FA</div>
                                    <small class="text-muted">Security</small>
                                </div>
                                <span class="badge badge-warning badge-pill">1.2k</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-weight-bold">Cambiar contraseña</div>
                                    <small class="text-muted">Security</small>
                                </div>
                                <span class="badge badge-secondary badge-pill">892</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-weight-bold">Actualizar perfil</div>
                                    <small class="text-muted">Account</small>
                                </div>
                                <span class="badge badge-secondary badge-pill">673</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-weight-bold">Facturación mensual</div>
                                    <small class="text-muted">Billing</small>
                                </div>
                                <span class="badge badge-secondary badge-pill">551</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Need More Help? -->
                <div class="card bg-gradient-primary sidebar-widget">
                    <div class="card-body">
                        <h5 class="text-white mb-3">
                            <i class="fas fa-headset mr-2"></i>¿Necesitas más ayuda?
                        </h5>
                        <p class="text-white-50 small mb-3">
                            Nuestro equipo de soporte está disponible 24/7 para asistirte.
                        </p>
                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-light btn-sm mb-2">
                                <i class="fas fa-envelope mr-1"></i>Email
                            </a>
                            <a href="#" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-phone mr-1"></i>Llamar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats Widget -->
                <div class="small-box bg-info sidebar-widget">
                    <div class="inner">
                        <h3>88</h3>
                        <p>Artículos Disponibles</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>

                <div class="small-box bg-success sidebar-widget">
                    <div class="inner">
                        <h3>97<sup style="font-size: 20px">%</sup></h3>
                        <p>Satisfacción de Usuarios</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-smile"></i>
                    </div>
                </div>

            </div>
        </div>

    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Search functionality
            $('#kb-search-input').on('keypress', function(e) {
                if(e.which == 13) {
                    const query = $(this).val();
                    alert('Buscando: ' + query);
                }
            });
            
            // Article link hover effect
            $('.article-link-item a').on('mouseenter', function() {
                $(this).find('.far.fa-file-alt').removeClass('far').addClass('fas');
            }).on('mouseleave', function() {
                $(this).find('.fas.fa-file-alt').removeClass('fas').addClass('far');
            });
            
            // Featured article click
            $('.featured-article').on('click', function(e) {
                if (!$(e.target).is('a, button')) {
                    const title = $(this).find('h4').text();
                    console.log('Opening article:', title);
                }
            });
        });
    </script>
@stop
