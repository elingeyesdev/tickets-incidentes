@extends('adminlte::page')

@section('title', 'Centro de Ayuda - FAQ')

@section('css')
<style>
    /* Header Section */
    .help-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px;
        border-radius: 10px;
        margin-bottom: 30px;
        color: white;
    }
    
    .help-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: white;
    }
    
    /* Search Bar */
    .search-card {
        margin-top: -30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .search-card .input-group {
        height: 50px;
    }
    
    .search-card .form-control {
        border: 2px solid #e9ecef;
        height: 100%;
    }
    
    .search-card .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    /* Category Tabs */
    .category-tabs .nav-link {
        border-radius: 10px 10px 0 0;
        font-weight: 600;
        padding: 12px 20px;
        margin-right: 5px;
        transition: all 0.3s ease;
    }
    
    .category-tabs .nav-link:hover {
        background: #f8f9fa;
    }
    
    .category-tabs .nav-link.active {
        background: white;
        border-bottom-color: white;
    }
    
    /* Accordion Styling */
    .faq-accordion .card {
        border: none;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .faq-accordion .card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.12);
    }
    
    .faq-accordion .card-header {
        background: white;
        border: none;
        padding: 0;
    }
    
    .faq-accordion .btn-link {
        width: 100%;
        text-align: left;
        padding: 18px 20px;
        color: #343a40;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }
    
    .faq-accordion .btn-link:hover {
        color: #667eea;
        background: #f8f9fa;
    }
    
    .faq-accordion .btn-link.collapsed {
        color: #495057;
    }
    
    .faq-accordion .btn-link::after {
        content: '\f078';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        transition: transform 0.3s ease;
    }
    
    .faq-accordion .btn-link:not(.collapsed)::after {
        transform: rotate(180deg);
    }
    
    .faq-accordion .card-body {
        padding: 20px;
        background: #f8f9fa;
        border-top: 2px solid #e9ecef;
    }
    
    .faq-meta {
        display: flex;
        gap: 15px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .faq-helpful {
        margin-top: 10px;
        padding: 15px;
        background: white;
        border-radius: 5px;
        text-align: center;
    }
    
    /* Support Section */
    .support-section {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        padding: 40px;
        border-radius: 10px;
        color: white;
        text-align: center;
        margin-top: 40px;
    }
    
    .support-section h3 {
        color: white;
        margin-bottom: 15px;
    }
    
    .support-section p {
        opacity: 0.95;
        margin-bottom: 20px;
    }
    
    /* Stats Cards */
    .stat-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
</style>
@stop

@section('content_header')
    <div class="container-fluid">
        <div class="help-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-question-circle mr-2"></i>Centro de Ayuda</h1>
                    <p class="mb-0">Encuentra respuestas rápidas a las preguntas más frecuentes</p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="d-flex justify-content-end gap-2">
                        <div class="text-center">
                            <h3 class="mb-0">88</h3>
                            <small>Artículos</small>
                        </div>
                        <div class="mx-3"></div>
                        <div class="text-center">
                            <h3 class="mb-0">4</h3>
                            <small>Categorías</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        
        <!-- Search Card -->
        <div class="row">
            <div class="col-12">
                <div class="card search-card">
                    <div class="card-body">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" placeholder="Buscar en artículos de ayuda..." id="search-input">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">Buscar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            
            <!-- Main Content -->
            <div class="col-lg-9">
                
                <!-- Category Tabs -->
                <div class="card card-primary card-tabs">
                    <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs category-tabs" id="category-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="all-tab" data-toggle="pill" href="#all" role="tab">
                                    <i class="fas fa-th-large mr-2"></i>Todos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="account-tab" data-toggle="pill" href="#account" role="tab">
                                    <i class="fas fa-user mr-2"></i>Account
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="security-tab" data-toggle="pill" href="#security" role="tab">
                                    <i class="fas fa-shield-alt mr-2"></i>Security
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="billing-tab" data-toggle="pill" href="#billing" role="tab">
                                    <i class="fas fa-credit-card mr-2"></i>Billing
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="technical-tab" data-toggle="pill" href="#technical" role="tab">
                                    <i class="fas fa-tools mr-2"></i>Technical
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="category-tabContent">
                            
                            <!-- All Tab -->
                            <div class="tab-pane fade show active" id="all" role="tabpanel">
                                <div class="faq-accordion" id="accordion-all">
                                    
                                    <!-- FAQ Item 1 -->
                                    <div class="card">
                                        <div class="card-header" id="heading1">
                                            <button class="btn btn-link" data-toggle="collapse" data-target="#collapse1">
                                                <span>¿Cómo activo la autenticación de dos factores (2FA)?</span>
                                            </button>
                                        </div>
                                        <div id="collapse1" class="collapse" data-parent="#accordion-all">
                                            <div class="card-body">
                                                <p>Para activar la autenticación de dos factores (2FA) en tu cuenta:</p>
                                                <ol>
                                                    <li>Ve a <strong>Configuración > Seguridad</strong></li>
                                                    <li>Busca la sección "Autenticación de Dos Factores"</li>
                                                    <li>Haz clic en <strong>"Activar 2FA"</strong></li>
                                                    <li>Escanea el código QR con tu aplicación autenticadora (Google Authenticator, Microsoft Authenticator, o Authy)</li>
                                                    <li>Ingresa el código de 6 dígitos generado por la aplicación</li>
                                                    <li>Guarda los códigos de respaldo en un lugar seguro</li>
                                                </ol>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <strong>Importante:</strong> Guarda tus códigos de respaldo. Los necesitarás si pierdes acceso a tu dispositivo.
                                                </div>
                                                
                                                <div class="faq-meta">
                                                    <span><i class="fas fa-folder mr-1 text-danger"></i>Security & Privacy</span>
                                                    <span><i class="fas fa-eye mr-1"></i>1,248 vistas</span>
                                                    <span><i class="far fa-clock mr-1"></i>Actualizado hace 2 días</span>
                                                </div>
                                                
                                                <div class="faq-helpful">
                                                    <span class="mr-2">¿Te fue útil este artículo?</span>
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fas fa-thumbs-up mr-1"></i>Sí
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary ml-2">
                                                        <i class="fas fa-thumbs-down mr-1"></i>No
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FAQ Item 2 -->
                                    <div class="card">
                                        <div class="card-header" id="heading2">
                                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse2">
                                                <span>¿Cómo cambio mi contraseña?</span>
                                            </button>
                                        </div>
                                        <div id="collapse2" class="collapse" data-parent="#accordion-all">
                                            <div class="card-body">
                                                <p>Cambiar tu contraseña es fácil y rápido:</p>
                                                <ol>
                                                    <li>Navega a <strong>Configuración > Seguridad</strong></li>
                                                    <li>Haz clic en <strong>"Cambiar Contraseña"</strong></li>
                                                    <li>Ingresa tu contraseña actual</li>
                                                    <li>Ingresa tu nueva contraseña (mínimo 8 caracteres)</li>
                                                    <li>Confirma la nueva contraseña</li>
                                                    <li>Haz clic en <strong>"Guardar Cambios"</strong></li>
                                                </ol>
                                                
                                                <div class="callout callout-info">
                                                    <h5><i class="fas fa-info-circle mr-2"></i>Recomendaciones de Seguridad</h5>
                                                    <ul class="mb-0">
                                                        <li>Usa una contraseña única que no uses en otros sitios</li>
                                                        <li>Combina letras mayúsculas, minúsculas, números y símbolos</li>
                                                        <li>Evita información personal obvia</li>
                                                        <li>Considera usar un gestor de contraseñas</li>
                                                    </ul>
                                                </div>
                                                
                                                <div class="faq-meta">
                                                    <span><i class="fas fa-folder mr-1 text-danger"></i>Security & Privacy</span>
                                                    <span><i class="fas fa-eye mr-1"></i>892 vistas</span>
                                                    <span><i class="far fa-clock mr-1"></i>Actualizado hace 5 días</span>
                                                </div>
                                                
                                                <div class="faq-helpful">
                                                    <span class="mr-2">¿Te fue útil este artículo?</span>
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fas fa-thumbs-up mr-1"></i>Sí
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary ml-2">
                                                        <i class="fas fa-thumbs-down mr-1"></i>No
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FAQ Item 3 -->
                                    <div class="card">
                                        <div class="card-header" id="heading3">
                                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse3">
                                                <span>¿Cómo actualizo mi información de perfil?</span>
                                            </button>
                                        </div>
                                        <div id="collapse3" class="collapse" data-parent="#accordion-all">
                                            <div class="card-body">
                                                <p>Mantener tu información actualizada es importante:</p>
                                                <ol>
                                                    <li>Ve a <strong>Configuración > Mi Perfil</strong></li>
                                                    <li>Actualiza los campos que necesites: nombre, email, teléfono, etc.</li>
                                                    <li>Haz clic en <strong>"Guardar Cambios"</strong></li>
                                                </ol>
                                                <p class="mb-0">Si cambias tu email, recibirás un correo de verificación en la nueva dirección.</p>
                                                
                                                <div class="faq-meta">
                                                    <span><i class="fas fa-folder mr-1 text-primary"></i>Account & Profile</span>
                                                    <span><i class="fas fa-eye mr-1"></i>673 vistas</span>
                                                    <span><i class="far fa-clock mr-1"></i>Actualizado hace 1 semana</span>
                                                </div>
                                                
                                                <div class="faq-helpful">
                                                    <span class="mr-2">¿Te fue útil este artículo?</span>
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fas fa-thumbs-up mr-1"></i>Sí
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary ml-2">
                                                        <i class="fas fa-thumbs-down mr-1"></i>No
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FAQ Item 4 -->
                                    <div class="card">
                                        <div class="card-header" id="heading4">
                                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse4">
                                                <span>¿Cómo funciona la facturación mensual?</span>
                                            </button>
                                        </div>
                                        <div id="collapse4" class="collapse" data-parent="#accordion-all">
                                            <div class="card-body">
                                                <p>Nuestro sistema de facturación mensual funciona de la siguiente manera:</p>
                                                <ul>
                                                    <li><strong>Fecha de Corte:</strong> El último día del mes</li>
                                                    <li><strong>Emisión de Factura:</strong> Los primeros 3 días del mes siguiente</li>
                                                    <li><strong>Método de Pago:</strong> Se carga automáticamente a tu método de pago registrado</li>
                                                    <li><strong>Notificaciones:</strong> Recibirás un email cuando la factura esté lista</li>
                                                </ul>
                                                <p>Puedes descargar todas tus facturas desde <strong>Configuración > Facturación</strong>.</p>
                                                
                                                <div class="faq-meta">
                                                    <span><i class="fas fa-folder mr-1 text-success"></i>Billing & Payments</span>
                                                    <span><i class="fas fa-eye mr-1"></i>551 vistas</span>
                                                    <span><i class="far fa-clock mr-1"></i>Actualizado hace 3 días</span>
                                                </div>
                                                
                                                <div class="faq-helpful">
                                                    <span class="mr-2">¿Te fue útil este artículo?</span>
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fas fa-thumbs-up mr-1"></i>Sí
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary ml-2">
                                                        <i class="fas fa-thumbs-down mr-1"></i>No
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FAQ Item 5 -->
                                    <div class="card">
                                        <div class="card-header" id="heading5">
                                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse5">
                                                <span>¿Qué hago si olvidé mi contraseña?</span>
                                            </button>
                                        </div>
                                        <div id="collapse5" class="collapse" data-parent="#accordion-all">
                                            <div class="card-body">
                                                <p>Si olvidaste tu contraseña, puedes restablecerla fácilmente:</p>
                                                <ol>
                                                    <li>Ve a la página de inicio de sesión</li>
                                                    <li>Haz clic en <strong>"¿Olvidaste tu contraseña?"</strong></li>
                                                    <li>Ingresa tu email registrado</li>
                                                    <li>Revisa tu correo electrónico</li>
                                                    <li>Haz clic en el enlace de recuperación (válido por 60 minutos)</li>
                                                    <li>Crea una nueva contraseña</li>
                                                </ol>
                                                
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Si no recibes el email en 5 minutos, revisa tu carpeta de spam o solicita un nuevo enlace.
                                                </div>
                                                
                                                <div class="faq-meta">
                                                    <span><i class="fas fa-folder mr-1 text-danger"></i>Security & Privacy</span>
                                                    <span><i class="fas fa-eye mr-1"></i>487 vistas</span>
                                                    <span><i class="far fa-clock mr-1"></i>Actualizado hace 1 semana</span>
                                                </div>
                                                
                                                <div class="faq-helpful">
                                                    <span class="mr-2">¿Te fue útil este artículo?</span>
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fas fa-thumbs-up mr-1"></i>Sí
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary ml-2">
                                                        <i class="fas fa-thumbs-down mr-1"></i>No
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <p class="text-muted">Artículos filtrados de <strong>Security & Privacy</strong></p>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Esta sección mostraría solo los artículos de seguridad cuando esté conectada a la API.
                                </div>
                            </div>

                            <!-- Other tabs would be similar -->
                            
                        </div>
                    </div>
                </div>

                <!-- Support CTA -->
                <div class="support-section">
                    <h3><i class="fas fa-life-ring mr-2"></i>¿No encontraste una solución?</h3>
                    <p>
                        No te preocupes, nuestro equipo de soporte está listo para ayudarte.
                        Crea un ticket y recibirás asistencia personalizada.
                    </p>
                    <p class="small mb-4">
                        <i class="fas fa-clock mr-1"></i>Tiempo de respuesta promedio: <strong>2-4 horas</strong>
                    </p>
                    <a href="#" class="btn btn-light btn-lg">
                        <i class="fas fa-ticket-alt mr-2"></i>Crear Ticket de Soporte
                    </a>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                
                <!-- Quick Stats -->
                <div class="card stat-card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="text-primary mb-0">88</h3>
                                <p class="text-muted mb-0">Artículos Totales</p>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card stat-card border-success mt-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="text-success mb-0">12</h3>
                                <p class="text-muted mb-0">Nuevos Este Mes</p>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-plus-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="card card-outline card-primary mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-folder-open mr-2"></i>Categorías
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-user text-primary mr-2"></i>
                                    Account & Profile
                                    <span class="badge badge-primary float-right">24</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-shield-alt text-danger mr-2"></i>
                                    Security & Privacy
                                    <span class="badge badge-danger float-right">18</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-credit-card text-success mr-2"></i>
                                    Billing & Payments
                                    <span class="badge badge-success float-right">15</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tools text-warning mr-2"></i>
                                    Technical Support
                                    <span class="badge badge-warning float-right">31</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Popular Articles -->
                <div class="card card-outline card-warning mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-fire text-warning mr-2"></i>Más Populares
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <li class="item">
                                <div class="product-info">
                                    <a href="#" class="product-title">Activar 2FA
                                        <span class="badge badge-warning float-right">1.2k</span>
                                    </a>
                                    <span class="product-description text-sm">
                                        Security & Privacy
                                    </span>
                                </div>
                            </li>
                            <li class="item">
                                <div class="product-info">
                                    <a href="#" class="product-title">Cambiar contraseña
                                        <span class="badge badge-warning float-right">892</span>
                                    </a>
                                    <span class="product-description text-sm">
                                        Security & Privacy
                                    </span>
                                </div>
                            </li>
                            <li class="item">
                                <div class="product-info">
                                    <a href="#" class="product-title">Actualizar perfil
                                        <span class="badge badge-warning float-right">673</span>
                                    </a>
                                    <span class="product-description text-sm">
                                        Account & Profile
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="card bg-gradient-info mt-3">
                    <div class="card-body">
                        <h5 class="text-white"><i class="fas fa-headset mr-2"></i>¿Necesitas Ayuda?</h5>
                        <p class="text-white-50 small mb-3">
                            Contáctanos directamente para asistencia personalizada
                        </p>
                        <a href="#" class="btn btn-light btn-sm btn-block">
                            <i class="fas fa-envelope mr-1"></i>Contactar Soporte
                        </a>
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
            $('#search-input').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.faq-accordion .card').each(function() {
                    const questionText = $(this).find('.btn-link span').text().toLowerCase();
                    const answerText = $(this).find('.card-body').text().toLowerCase();
                    
                    if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Helpful buttons
            $('.faq-helpful .btn-success').on('click', function() {
                $(this).removeClass('btn-success').addClass('btn-outline-success').prop('disabled', true);
                alert('¡Gracias por tu feedback!');
            });
            
            $('.faq-helpful .btn-outline-secondary').on('click', function() {
                $(this).removeClass('btn-outline-secondary').addClass('btn-outline-danger').prop('disabled', true);
                alert('Lamentamos que no te haya sido útil. ¿Quieres crear un ticket de soporte?');
            });
        });
    </script>
@stop
