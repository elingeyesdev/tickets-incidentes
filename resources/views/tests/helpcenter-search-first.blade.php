@extends('adminlte::page')

@section('title', 'Centro de Ayuda')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Centro de Ayuda</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Centro de Ayuda</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        
        {{-- Search Card --}}
        <div class="card card-primary card-outline">
            <div class="card-body">
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" placeholder="¿Qué necesitas saber hoy?" id="help-search">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Main Content --}}
            <div class="col-md-9">
                
                {{-- Categories --}}
                <div class="row">
                    <div class="col-12 mb-2">
                        <h4>Explora por Categoría</h4>
                    </div>
                    
                    {{-- Account & Profile --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>24</h3>
                                <p>Account & Profile</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <a href="#" class="small-box-footer">
                                Ver artículos <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    {{-- Security & Privacy --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>18</h3>
                                <p>Security & Privacy</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <a href="#" class="small-box-footer">
                                Ver artículos <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    {{-- Billing & Payments --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>15</h3>
                                <p>Billing & Payments</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <a href="#" class="small-box-footer">
                                Ver artículos <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    {{-- Technical Support --}}
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>31</h3>
                                <p>Technical Support</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <a href="#" class="small-box-footer">
                                Ver artículos <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Popular Articles --}}
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-fire text-warning mr-2"></i>Artículos Populares</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <li class="item">
                                <div class="product-info">
                                    <a href="#" class="product-title">Cómo activar autenticación de dos factores (2FA)
                                        <span class="badge badge-danger float-right">1,248</span>
                                    </a>
                                    <span class="product-description">
                                        Protege tu cuenta con una capa adicional de seguridad...
                                    </span>
                                </div>
                            </li>
                            <li class="item">
                                <div class="product-info">
                                    <a href="#" class="product-title">Cómo cambiar tu contraseña
                                        <span class="badge badge-warning float-right">892</span>
                                    </a>
                                    <span class="product-description">
                                        Guía paso a paso para actualizar tu contraseña de forma segura...
                                    </span>
                                </div>
                            </li>
                            <li class="item">
                                <div class="product-info">
                                    <a href="#" class="product-title">Actualizar información de perfil
                                        <span class="badge badge-info float-right">673</span>
                                    </a>
                                    <span class="product-description">
                                        Mantén tu información actualizada: nombre, email, teléfono...
                                    </span>
                                </div>
                            </li>
                            <li class="item">
                                <div class="product-info">
                                    <a href="#" class="product-title">Facturación mensual
                                        <span class="badge badge-success float-right">551</span>
                                    </a>
                                    <span class="product-description">
                                        Todo sobre facturación mensual, métodos de pago...</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Recent by Category --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Artículos Recientes</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        {{-- Security Articles --}}
                        <h5 class="text-danger"><i class="fas fa-shield-alt mr-2"></i>Security & Privacy</h5>
                        <div class="list-group mb-4">
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Cómo activar 2FA</h6>
                                    <small class="text-muted"><i class="fas fa-eye"></i> 1.2k</small>
                                </div>
                                <small>Protege tu cuenta con autenticación de dos factores...</small>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Restablecer contraseña</h6>
                                    <small class="text-muted"><i class="fas fa-eye"></i> 487</small>
                                </div>
                                <small>¿Olvidaste tu contraseña? Aprende a restablecerla...</small>
                            </a>
                        </div>

                        {{-- Account Articles --}}
                        <h5 class="text-primary"><i class="fas fa-user mr-2"></i>Account & Profile</h5>
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Actualizar perfil</h6>
                                    <small class="text-muted"><i class="fas fa-eye"></i> 673</small>
                                </div>
                                <small>Mantén actualizada tu información personal...</small>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Gestionar notificaciones</h6>
                                    <small class="text-muted"><i class="fas fa-eye"></i> 445</small>
                                </div>
                                <small>Configura cómo y cuándo recibir notificaciones...</small>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Support CTA --}}
                <div class="callout callout-info">
                    <h5>¿No encontraste una solución?</h5>
                    <p>Nuestro equipo de soporte está aquí para ayudarte. Crea un ticket y te responderemos lo antes posible.</p>
                    <p class="mb-0">
                        <small class="text-muted"><i class="far fa-clock mr-1"></i>Tiempo de respuesta promedio: 2-4 horas</small>
                    </p>
                    <a href="#" class="btn btn-primary mt-2">
                        <i class="fas fa-ticket-alt mr-1"></i>Crear Ticket de Soporte
                    </a>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="col-md-3">
                
                {{-- Top Articles --}}
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Top 5 Artículos</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item active">
                                <a href="#" class="nav-link">
                                    Activar 2FA
                                    <span class="badge bg-warning float-right">1.2k</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    Cambiar contraseña
                                    <span class="badge bg-secondary float-right">892</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    Actualizar perfil
                                    <span class="badge bg-secondary float-right">673</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    Facturación
                                    <span class="badge bg-secondary float-right">551</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    Reset password
                                    <span class="badge bg-secondary float-right">487</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Enlaces Rápidos</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-book text-primary mr-2"></i>
                                    Documentación API
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-video text-danger mr-2"></i>
                                    Video Tutoriales
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-comments text-success mr-2"></i>
                                    Comunidad
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-headset text-warning mr-2"></i>
                                    Contactar Soporte
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="info-box bg-gradient-info">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Artículos Totales</span>
                        <span class="info-box-number">88</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">
                            12 nuevos este mes
                        </span>
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
        $('#help-search').on('keypress', function(e) {
            if(e.which == 13) {
                alert('Búsqueda por: ' + $(this).val());
            }
        });
    });
</script>
@stop
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 60px 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        color: white;
        text-align: center;
    }
    
    .help-center-hero h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: white;
    }
    
    .help-center-hero p {
        font-size: 1.1rem;
        margin-bottom: 30px;
        opacity: 0.95;
    }
    
    .hero-search-box {
        max-width: 700px;
        margin: 0 auto;
    }
    
    .hero-search-box .input-group {
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .hero-search-box .form-control {
        height: 60px;
        font-size: 1.1rem;
        border: none;
        border-radius: 30px 0 0 30px !important;
    }
    
    .hero-search-box .input-group-append .btn {
        height: 60px;
        padding: 0 30px;
        border-radius: 0 30px 30px 0 !important;
        background: #28a745;
        border-color: #28a745;
        font-weight: 600;
    }
    
    .hero-search-box .input-group-append .btn:hover {
        background: #218838;
        border-color: #218838;
    }

    /* Category Cards */
    .category-card {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
        cursor: pointer;
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .category-icon {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }
    
    .category-card:hover .category-icon {
        transform: scale(1.1);
    }
    
    /* Article Items */
    .article-item {
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
        padding: 15px;
        margin-bottom: 10px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    
    .article-item:hover {
        border-left-color: #007bff;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .article-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #343a40;
        margin-bottom: 5px;
    }
    
    .article-excerpt {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .article-meta {
        font-size: 0.85rem;
        color: #adb5bd;
    }
    
    /* Support CTA */
    .support-cta {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 40px;
        border-radius: 10px;
        text-align: center;
        margin-top: 50px;
    }
    
    .support-cta h3 {
        color: white;
        margin-bottom: 15px;
    }
    
    .support-cta p {
        opacity: 0.95;
        margin-bottom: 25px;
    }
    
    /* Sticky Sidebar */
    .sticky-sidebar {
        position: sticky;
        top: 80px;
    }
</style>
@stop

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active">Centro de Ayuda</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        
        <!-- Hero Search Section -->
        <div class="help-center-hero">
            <h1><i class="fas fa-question-circle mr-2"></i>¿Cómo podemos ayudarte?</h1>
            <p>Busca artículos, guías y respuestas a tus preguntas más frecuentes</p>
            
            <div class="hero-search-box">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Buscar por palabra clave, tema o pregunta..." id="main-search">
                    <div class="input-group-append">
                        <button class="btn btn-success" type="button">
                            <i class="fas fa-search mr-2"></i>Buscar
                        </button>
                    </div>
                </div>
                <div class="text-left mt-2 ml-3">
                    <small class="text-white-50">
                        <i class="fas fa-lightbulb mr-1"></i>Sugerencias: 
                        <a href="#" class="text-white"><u>restablecer contraseña</u></a>, 
                        <a href="#" class="text-white"><u>facturación</u></a>, 
                        <a href="#" class="text-white"><u>2FA</u></a>
                    </small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-9">
                
                <!-- Category Cards -->
                <div class="row">
                    <div class="col-12 mb-3">
                        <h4><i class="fas fa-folder-open mr-2 text-primary"></i>Explora por Categoría</h4>
                    </div>
                    
                    <!-- Account & Profile -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card category-card h-100">
                            <div class="card-body text-center">
                                <div class="category-icon bg-primary mx-auto">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <h5 class="card-title">Account & Profile</h5>
                                <p class="text-muted small">Gestión de cuenta y perfil</p>
                                <span class="badge badge-secondary">24 artículos</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security & Privacy -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card category-card h-100">
                            <div class="card-body text-center">
                                <div class="category-icon bg-danger mx-auto">
                                    <i class="fas fa-shield-alt text-white"></i>
                                </div>
                                <h5 class="card-title">Security & Privacy</h5>
                                <p class="text-muted small">Seguridad y privacidad</p>
                                <span class="badge badge-secondary">18 artículos</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing & Payments -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card category-card h-100">
                            <div class="card-body text-center">
                                <div class="category-icon bg-success mx-auto">
                                    <i class="fas fa-credit-card text-white"></i>
                                </div>
                                <h5 class="card-title">Billing & Payments</h5>
                                <p class="text-muted small">Facturación y pagos</p>
                                <span class="badge badge-secondary">15 artículos</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Technical Support -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card category-card h-100">
                            <div class="card-body text-center">
                                <div class="category-icon bg-warning mx-auto">
                                    <i class="fas fa-tools text-white"></i>
                                </div>
                                <h5 class="card-title">Technical Support</h5>
                                <p class="text-muted small">Soporte técnico</p>
                                <span class="badge badge-secondary">31 artículos</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Articles by Category -->
                <div class="card card-primary card-outline mt-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Artículos Recientes</h3>
                        <div class="card-tools">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-tool active"><i class="fas fa-th-large"></i></button>
                                <button type="button" class="btn btn-tool"><i class="fas fa-list"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        
                        <!-- Security Articles -->
                        <div class="mb-4">
                            <h5 class="text-danger"><i class="fas fa-shield-alt mr-2"></i>Security & Privacy</h5>
                            <hr class="mt-1 mb-3">
                            
                            <div class="article-item">
                                <div class="article-title">
                                    <a href="#">Cómo activar autenticación de dos factores (2FA)</a>
                                </div>
                                <div class="article-excerpt">
                                    Protege tu cuenta con una capa adicional de seguridad. Aprende a configurar 2FA en menos de 5 minutos.
                                </div>
                                <div class="article-meta">
                                    <i class="fas fa-eye mr-1"></i>1,248 vistas
                                    <span class="mx-2">•</span>
                                    <i class="far fa-clock mr-1"></i>Actualizado hace 2 días
                                </div>
                            </div>
                            
                            <div class="article-item">
                                <div class="article-title">
                                    <a href="#">Cómo cambiar tu contraseña de forma segura</a>
                                </div>
                                <div class="article-excerpt">
                                    Guía paso a paso para actualizar tu contraseña siguiendo las mejores prácticas de seguridad.
                                </div>
                                <div class="article-meta">
                                    <i class="fas fa-eye mr-1"></i>892 vistas
                                    <span class="mx-2">•</span>
                                    <i class="far fa-clock mr-1"></i>Actualizado hace 5 días
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-sm btn-outline-danger">
                                    Ver todos los artículos de Security <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Account Articles -->
                        <div class="mb-4">
                            <h5 class="text-primary"><i class="fas fa-user mr-2"></i>Account & Profile</h5>
                            <hr class="mt-1 mb-3">
                            
                            <div class="article-item">
                                <div class="article-title">
                                    <a href="#">Cómo actualizar tu información de perfil</a>
                                </div>
                                <div class="article-excerpt">
                                    Mantén tu información actualizada: nombre, email, teléfono y más.
                                </div>
                                <div class="article-meta">
                                    <i class="fas fa-eye mr-1"></i>673 vistas
                                    <span class="mx-2">•</span>
                                    <i class="far fa-clock mr-1"></i>Actualizado hace 1 semana
                                </div>
                            </div>
                            
                            <div class="article-item">
                                <div class="article-title">
                                    <a href="#">Gestionar preferencias de notificaciones</a>
                                </div>
                                <div class="article-excerpt">
                                    Configura cómo y cuándo quieres recibir notificaciones de tu cuenta.
                                </div>
                                <div class="article-meta">
                                    <i class="fas fa-eye mr-1"></i>445 vistas
                                    <span class="mx-2">•</span>
                                    <i class="far fa-clock mr-1"></i>Actualizado hace 3 días
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-sm btn-outline-primary">
                                    Ver todos los artículos de Account <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Support CTA -->
                <div class="support-cta">
                    <h3><i class="fas fa-question-circle mr-2"></i>¿No encontraste una solución?</h3>
                    <p class="mb-0">
                        Nuestro equipo de soporte está aquí para ayudarte. Crea un ticket y te responderemos lo antes posible.
                    </p>
                    <p class="small mb-4">
                        <i class="fas fa-clock mr-1"></i>Tiempo de respuesta promedio: 2-4 horas
                    </p>
                    <a href="#" class="btn btn-light btn-lg">
                        <i class="fas fa-ticket-alt mr-2"></i>Crear Ticket de Soporte
                    </a>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sticky-sidebar">
                    
                    <!-- Popular Articles -->
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-fire text-warning mr-2"></i>Más Populares
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-warning mr-2 mt-1">1</span>
                                        <div class="flex-grow-1">
                                            <a href="#" class="text-dark font-weight-bold small">Activar 2FA</a>
                                            <div class="text-muted small">
                                                <i class="fas fa-eye"></i> 1.2k
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-secondary mr-2 mt-1">2</span>
                                        <div class="flex-grow-1">
                                            <a href="#" class="text-dark font-weight-bold small">Cambiar contraseña</a>
                                            <div class="text-muted small">
                                                <i class="fas fa-eye"></i> 892
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-secondary mr-2 mt-1">3</span>
                                        <div class="flex-grow-1">
                                            <a href="#" class="text-dark font-weight-bold small">Actualizar perfil</a>
                                            <div class="text-muted small">
                                                <i class="fas fa-eye"></i> 673
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-secondary mr-2 mt-1">4</span>
                                        <div class="flex-grow-1">
                                            <a href="#" class="text-dark font-weight-bold small">Facturación mensual</a>
                                            <div class="text-muted small">
                                                <i class="fas fa-eye"></i> 551
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-secondary mr-2 mt-1">5</span>
                                        <div class="flex-grow-1">
                                            <a href="#" class="text-dark font-weight-bold small">Reset de password</a>
                                            <div class="text-muted small">
                                                <i class="fas fa-eye"></i> 487
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card card-info card-outline mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-link mr-2"></i>Enlaces Rápidos
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        <i class="fas fa-book text-primary mr-2"></i>
                                        Documentación API
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        <i class="fas fa-video text-danger mr-2"></i>
                                        Video Tutoriales
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        <i class="fas fa-comments text-success mr-2"></i>
                                        Comunidad
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        <i class="fas fa-headset text-warning mr-2"></i>
                                        Contactar Soporte
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="info-box bg-gradient-info mt-3">
                        <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Artículos Totales</span>
                            <span class="info-box-number">88</span>
                            <div class="progress">
                                <div class="progress-bar" style="width: 70%"></div>
                            </div>
                            <span class="progress-description">
                                12 nuevos este mes
                            </span>
                        </div>
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
            $('#main-search').on('keypress', function(e) {
                if(e.which == 13) {
                    alert('Búsqueda por: ' + $(this).val());
                }
            });
            
            // Category card click
            $('.category-card').on('click', function() {
                $(this).addClass('border-primary');
                setTimeout(() => {
                    $(this).removeClass('border-primary');
                }, 300);
            });
            
            // Article item click
            $('.article-item').on('click', function() {
                const title = $(this).find('.article-title a').text();
                console.log('Viewing article:', title);
            });
        });
    </script>
@stop
