@extends('layouts.authenticated')

@section('title', 'User Profile')

@section('content_header', 'User Profile')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Profile</li>
@endsection

@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- Jerárquía canónica de AdminLTE v3: .row > .col-md-3 (sidebar) + .col-md-9 (main) -->
        <div class="row">

            <!-- COLUMNA IZQUIERDA: Perfil del Usuario + About Me (col-md-3) -->
            <div class="col-md-3">

                <!-- TARJETA 1: Widget de Perfil Principal -->
                <!-- Clases canónicas: .card.card-primary.card-outline + .card-body.box-profile -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <!-- Avatar (centrado) -->
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle"
                                 id="profileAvatar"
                                 src="https://ui-avatars.com/api/?name=User&size=128&color=white&background=0D8ABC"
                                 alt="Avatar del usuario"
                                 title="Avatar del usuario">
                        </div>

                        <!-- Nombre y Email -->
                        <h3 class="profile-username text-center" id="profileName">
                            <span class="spinner-border spinner-border-sm mr-2" id="profileNameSpinner"></span>
                            Cargando...
                        </h3>
                        <p class="text-muted text-center mb-3" id="profileEmail">Cargando...</p>

                        <!-- Estadísticas (Utilidad Oficial de Bootstrap: .list-group) -->
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Estado</b>
                                <span class="float-right">
                                    <span id="statusBadge" class="badge badge-secondary">--</span>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Email Verificado</b>
                                <span class="float-right">
                                    <span id="emailVerifiedBadge" class="badge badge-secondary">--</span>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Miembro desde</b>
                                <span class="float-right text-muted" id="memberSince">--</span>
                            </li>
                        </ul>

                        <!-- Botón para acceder a edición -->
                        <button class="btn btn-primary btn-block" type="button" id="editProfileBtn">
                            <i class="fas fa-edit mr-2"></i> Editar Perfil
                        </button>
                    </div>
                </div>
                <!-- /.card -->

                <!-- TARJETA 2: About Me (Utilidad Official: .card.card-primary) -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book mr-2"></i>Información Personal
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Email (Utilidad Official: .text-muted) -->
                        <strong><i class="fas fa-envelope mr-2"></i>Email</strong>
                        <p class="text-muted mb-3" id="aboutEmail">--</p>

                        <!-- Teléfono -->
                        <strong><i class="fas fa-phone mr-2"></i>Teléfono</strong>
                        <p class="text-muted mb-3" id="aboutPhone">No proporcionado</p>

                        <!-- Tema -->
                        <strong><i class="fas fa-palette mr-2"></i>Tema</strong>
                        <p class="text-muted mb-3" id="aboutTheme">--</p>

                        <!-- Idioma -->
                        <strong><i class="fas fa-language mr-2"></i>Idioma</strong>
                        <p class="text-muted mb-3" id="aboutLanguage">--</p>

                        <!-- Zona Horaria -->
                        <strong><i class="fas fa-globe mr-2"></i>Zona Horaria</strong>
                        <p class="text-muted" id="aboutTimezone">--</p>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-3 -->

            <!-- COLUMNA DERECHA: Tarjeta Tabbed (col-md-9) -->
            <div class="col-md-9">
                <div class="card">

                    <!-- NAV TABS en Card Header (Utilidad Official: .card-header-tabs) -->
                    <!-- IMPORTANTE: p-0 + border-bottom-0 para que las pestañas queden al ras -->
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="profileTab" data-toggle="tab" href="#profilePane"
                                   role="tab" aria-controls="profilePane" aria-selected="true">
                                    <i class="fas fa-user mr-2"></i>Perfil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="preferencesTab" data-toggle="tab" href="#preferencesPane"
                                   role="tab" aria-controls="preferencesPane" aria-selected="false">
                                    <i class="fas fa-cog mr-2"></i>Preferencias
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="sessionsTab" data-toggle="tab" href="#sessionsPane"
                                   role="tab" aria-controls="sessionsPane" aria-selected="false">
                                    <i class="fas fa-laptop mr-2"></i>Sesiones
                                </a>
                            </li>
                        </ul>
                    </div>
                    <!-- /.card-header -->

                    <!-- TAB CONTENT -->
                    <div class="card-body">
                        <div class="tab-content">

                            <!-- ========== PESTAÑA 1: PROFILE ========== -->
                            <div class="tab-pane fade show active" id="profilePane" role="tabpanel"
                                 aria-labelledby="profileTab">

                                <!-- Loading State -->
                                <div id="profileLoading" class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Información</h5>
                                    Cargando datos del perfil...
                                </div>

                                <!-- Profile Form (Estructura Canónica) -->
                                <form id="profileForm" style="display: none;">
                                    <div id="profileFormAlert"></div>

                                    <!-- Nombre (Primera Parte) -->
                                    <!-- Utilidad Official: .form-group.row > .col-sm-2.col-form-label + .col-sm-10 -->
                                    <div class="form-group row">
                                        <label for="firstName" class="col-sm-2 col-form-label">
                                            <strong>Nombre</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="firstName"
                                                   name="firstName" placeholder="Ej: Juan"
                                                   minlength="2" maxlength="100" required>
                                            <small class="form-text text-muted d-block mt-1">
                                                Mínimo 2 caracteres, máximo 100
                                            </small>
                                            <div class="invalid-feedback" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- Apellido -->
                                    <div class="form-group row">
                                        <label for="lastName" class="col-sm-2 col-form-label">
                                            <strong>Apellido</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="lastName"
                                                   name="lastName" placeholder="Ej: Pérez"
                                                   minlength="2" maxlength="100" required>
                                            <small class="form-text text-muted d-block mt-1">
                                                Mínimo 2 caracteres, máximo 100
                                            </small>
                                            <div class="invalid-feedback" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- Teléfono -->
                                    <div class="form-group row">
                                        <label for="phoneNumber" class="col-sm-2 col-form-label">
                                            <strong>Teléfono</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <!-- Input Group con Country Code -->
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <select class="custom-select" id="phoneCountryCode"
                                                            title="Código de país" style="max-width: 120px;">
                                                        <option value="+1">🇺🇸 +1 (EE.UU.)</option>
                                                        <option value="+52">🇲🇽 +52 (México)</option>
                                                        <option value="+56">🇨🇱 +56 (Chile)</option>
                                                        <option value="+54">🇦🇷 +54 (Argentina)</option>
                                                        <option value="+57">🇨🇴 +57 (Colombia)</option>
                                                        <option value="+51">🇵🇪 +51 (Perú)</option>
                                                        <option value="+55">🇧🇷 +55 (Brasil)</option>
                                                        <option value="+34">🇪🇸 +34 (España)</option>
                                                        <option value="+44">🇬🇧 +44 (UK)</option>
                                                        <option value="+33">🇫🇷 +33 (Francia)</option>
                                                        <option value="+49">🇩🇪 +49 (Alemania)</option>
                                                        <option value="+39">🇮🇹 +39 (Italia)</option>
                                                        <option value="+64">🇳🇿 +64 (Nueva Zelanda)</option>
                                                        <option value="+61">🇦🇺 +61 (Australia)</option>
                                                        <option value="+81">🇯🇵 +81 (Japón)</option>
                                                        <option value="+86">🇨🇳 +86 (China)</option>
                                                        <option value="+91">🇮🇳 +91 (India)</option>
                                                    </select>
                                                </div>
                                                <input type="tel" class="form-control" id="phoneNumber"
                                                       name="phoneNumber" placeholder="555123456"
                                                       minlength="10" maxlength="20">
                                            </div>
                                            <small class="form-text text-muted d-block mt-1">
                                                10-20 dígitos: números, espacios, +, -, ()
                                            </small>
                                            <div class="invalid-feedback" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- Avatar URL -->
                                    <div class="form-group row">
                                        <label for="avatarUrl" class="col-sm-2 col-form-label">
                                            <strong>Avatar</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <input type="url" class="form-control" id="avatarUrl"
                                                   name="avatarUrl"
                                                   placeholder="https://ejemplo.com/avatar.jpg"
                                                   maxlength="2048">
                                            <small class="form-text text-muted d-block mt-1">
                                                URL válida (HTTP/HTTPS). Máximo 2048 caracteres.
                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2"
                                                        id="previewAvatarBtn">
                                                    <i class="fas fa-eye mr-1"></i>Previsualizar
                                                </button>
                                            </small>

                                            <!-- Avatar Preview -->
                                            <div id="avatarPreview" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                                <div class="text-center">
                                                    <img id="avatarPreviewImg" src="" alt="Preview"
                                                         style="max-width: 150px; max-height: 150px; border-radius: 8px; object-fit: cover;">
                                                    <div class="mt-2">
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check mr-1"></i>Imagen cargada correctamente
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="invalid-feedback" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- Botones de Acción -->
                                    <!-- Utilidad Official: .offset-sm-2 para alineación -->
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <button type="submit" class="btn btn-primary" id="profileSubmitBtn">
                                                <i class="fas fa-save mr-2"></i>Guardar Cambios
                                            </button>
                                            <button type="reset" class="btn btn-secondary ml-2">
                                                <i class="fas fa-undo mr-2"></i>Deshacer
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <!-- /.tab-pane -->

                            <!-- ========== PESTAÑA 2: PREFERENCES ========== -->
                            <div class="tab-pane fade" id="preferencesPane" role="tabpanel"
                                 aria-labelledby="preferencesTab">

                                <!-- Loading State -->
                                <div id="preferencesLoading" class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Información</h5>
                                    Cargando preferencias...
                                </div>

                                <!-- Preferences Form -->
                                <form id="preferencesForm" style="display: none;">
                                    <div id="preferencesFormAlert"></div>

                                    <!-- Tema -->
                                    <div class="form-group row">
                                        <label for="theme" class="col-sm-2 col-form-label">
                                            <strong>Tema</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <select class="form-control" id="theme" name="theme" required>
                                                <option value="light">Claro</option>
                                                <option value="dark">Oscuro</option>
                                            </select>
                                            <small class="form-text text-muted d-block mt-1">
                                                Selecciona el tema de la interfaz
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Idioma -->
                                    <div class="form-group row">
                                        <label for="language" class="col-sm-2 col-form-label">
                                            <strong>Idioma</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <select class="form-control" id="language" name="language" required>
                                                <option value="en">English</option>
                                                <option value="es">Español</option>
                                            </select>
                                            <small class="form-text text-muted d-block mt-1">
                                                Selecciona tu idioma preferido
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Zona Horaria -->
                                    <div class="form-group row">
                                        <label for="timezone" class="col-sm-2 col-form-label">
                                            <strong>Zona Horaria</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <select class="form-control" id="timezone" name="timezone" required>
                                                <option value="UTC">UTC</option>
                                                <option value="America/New_York">America/New_York</option>
                                                <option value="America/Chicago">America/Chicago</option>
                                                <option value="America/Denver">America/Denver</option>
                                                <option value="America/Los_Angeles">America/Los_Angeles</option>
                                                <option value="America/Toronto">America/Toronto</option>
                                                <option value="America/Mexico_City">America/Mexico_City</option>
                                                <option value="America/Bogota">America/Bogota</option>
                                                <option value="America/Lima">America/Lima</option>
                                                <option value="America/Santiago">America/Santiago</option>
                                                <option value="America/Buenos_Aires">America/Buenos_Aires</option>
                                                <option value="America/Sao_Paulo">America/Sao_Paulo</option>
                                                <option value="Europe/London">Europe/London</option>
                                                <option value="Europe/Paris">Europe/Paris</option>
                                                <option value="Europe/Berlin">Europe/Berlin</option>
                                                <option value="Europe/Madrid">Europe/Madrid</option>
                                                <option value="Europe/Rome">Europe/Rome</option>
                                                <option value="Europe/Amsterdam">Europe/Amsterdam</option>
                                                <option value="Europe/Moscow">Europe/Moscow</option>
                                                <option value="Africa/Cairo">Africa/Cairo</option>
                                                <option value="Africa/Johannesburg">Africa/Johannesburg</option>
                                                <option value="Asia/Dubai">Asia/Dubai</option>
                                                <option value="Asia/Kolkata">Asia/Kolkata</option>
                                                <option value="Asia/Bangkok">Asia/Bangkok</option>
                                                <option value="Asia/Hong_Kong">Asia/Hong_Kong</option>
                                                <option value="Asia/Shanghai">Asia/Shanghai</option>
                                                <option value="Asia/Tokyo">Asia/Tokyo</option>
                                                <option value="Asia/Seoul">Asia/Seoul</option>
                                                <option value="Asia/Singapore">Asia/Singapore</option>
                                                <option value="Australia/Sydney">Australia/Sydney</option>
                                                <option value="Australia/Melbourne">Australia/Melbourne</option>
                                            </select>
                                            <small class="form-text text-muted d-block mt-1">
                                                Selecciona tu zona horaria IANA
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Notificaciones Push -->
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="pushWebNotifications" name="pushWebNotifications">
                                                <label class="custom-control-label" for="pushWebNotifications">
                                                    <strong>Habilitar Notificaciones Push</strong>
                                                    <small class="d-block text-muted">Recibe notificaciones en tu navegador</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notificaciones de Tickets -->
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="notificationsTickets" name="notificationsTickets">
                                                <label class="custom-control-label" for="notificationsTickets">
                                                    <strong>Habilitar Notificaciones de Tickets</strong>
                                                    <small class="d-block text-muted">Recibe alertas sobre cambios en tus tickets</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botones de Acción -->
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <button type="submit" class="btn btn-primary" id="preferencesSubmitBtn">
                                                <i class="fas fa-save mr-2"></i>Guardar Preferencias
                                            </button>
                                            <button type="reset" class="btn btn-secondary ml-2">
                                                <i class="fas fa-undo mr-2"></i>Deshacer
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <!-- /.tab-pane -->

                            <!-- ========== PESTAÑA 3: SESSIONS ========== -->
                            <div class="tab-pane fade" id="sessionsPane" role="tabpanel"
                                 aria-labelledby="sessionsTab">

                                <!-- Loading State -->
                                <div id="sessionsLoading" class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Información</h5>
                                    Cargando sesiones activas...
                                </div>

                                <!-- Sessions Content -->
                                <div id="sessionsContent" style="display: none;">
                                    <!-- Botón para cerrar todas las sesiones -->
                                    <div class="mb-3">
                                        <button class="btn btn-danger btn-sm" id="logoutAllBtn" type="button">
                                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar todas las sesiones
                                        </button>
                                    </div>

                                    <!-- Tabla Responsiva (Utilidad Official: .table-responsive) -->
                                    <!-- IMPORTANTE: El contenedor .table-responsive habilita scroll horizontal en mobile -->
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped table-sm">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 30%;">Dispositivo</th>
                                                    <th style="width: 25%;">Dirección IP</th>
                                                    <th style="width: 25%;">Último Uso</th>
                                                    <th style="width: 10%;">Estado</th>
                                                    <th style="width: 10%;">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="sessionsList">
                                                <!-- Populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane -->

                        </div>
                        <!-- /.tab-content -->
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-9 -->

        </div>
        <!-- /.row -->
    </div>
</section>
@endsection

@section('js')
<script>
// =====================================
// CONSTANTS & CONFIGURATION
// =====================================

const API_ENDPOINTS = {
    profile: '/api/users/me',
    updateProfile: '/api/users/me/profile',
    updatePreferences: '/api/users/me/preferences',
    sessions: '/api/auth/sessions',
    deleteSession: (id) => `/api/auth/sessions/${id}`,
    logout: '/api/auth/logout'
};

const VALIDATION_MESSAGES = {
    firstName: {
        required: 'El nombre es obligatorio',
        minlength: 'El nombre debe tener al menos 2 caracteres',
        maxlength: 'El nombre no puede exceder 100 caracteres'
    },
    lastName: {
        required: 'El apellido es obligatorio',
        minlength: 'El apellido debe tener al menos 2 caracteres',
        maxlength: 'El apellido no puede exceder 100 caracteres'
    },
    phoneNumber: {
        minlength: 'El teléfono debe tener al menos 10 caracteres',
        maxlength: 'El teléfono no puede exceder 20 caracteres'
    },
    theme: { required: 'Debes seleccionar un tema' },
    language: { required: 'Debes seleccionar un idioma' },
    timezone: { required: 'Debes seleccionar una zona horaria' }
};

// =====================================
// UTILITY FUNCTIONS
// =====================================

/**
 * Muestra un toast (notificación no intrusiva) usando AdminLTE oficial
 * Utilidad Official de AdminLTE: $(document).Toasts('create', {...})
 */
function showToast(title, message, type = 'info') {
    $(document).Toasts('create', {
        class: `bg-${type}`,
        title: title,
        body: message,
        autohide: true,
        delay: 5000
    });
}

/**
 * Valida si una URL es válida (sintaxis básica)
 */
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (_) {
        return false;
    }
}

/**
 * Valida que una URL apunte a una imagen real cargable
 * Esta es la validación crítica que falta en muchas implementaciones
 */
async function validateImageUrl(url) {
    if (!url) return { valid: false, reason: 'La URL es obligatoria' };
    if (!isValidUrl(url)) return { valid: false, reason: 'Formato de URL inválido' };

    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';

        const timeout = setTimeout(() => {
            resolve({
                valid: false,
                reason: 'La imagen tardó demasiado en cargar. El servidor puede no ser accesible.'
            });
        }, 5000);

        img.onload = () => {
            clearTimeout(timeout);
            resolve({ valid: true, url: url });
        };

        img.onerror = () => {
            clearTimeout(timeout);
            const domain = new URL(url).hostname;

            // Detección específica de dominios problemáticos
            if (domain.includes('wikia') || domain.includes('fandom')) {
                resolve({
                    valid: false,
                    reason: 'Los servidores de Wikia/Fandom bloquean acceso externo (CORS). Usa Imgur u otro CDN.'
                });
            } else {
                resolve({
                    valid: false,
                    reason: `No se puede cargar la imagen desde ${domain}. El servidor puede bloquear acceso externo.`
                });
            }
        };

        img.src = url;
    });
}

/**
 * Obtiene el color de badge según el estado
 */
function getStatusColor(status) {
    const statusLower = (status || '').toLowerCase();
    if (statusLower === 'active') return 'success';
    if (statusLower === 'suspended') return 'warning';
    if (statusLower === 'deleted') return 'danger';
    return 'secondary';
}

/**
 * Obtiene el icono Font Awesome según el tipo de dispositivo
 */
function getDeviceIcon(deviceName) {
    const name = (deviceName || '').toLowerCase();
    if (name.includes('mobile')) return 'mobile-alt';
    if (name.includes('tablet')) return 'tablet-alt';
    if (name.includes('windows')) return 'windows';
    if (name.includes('mac')) return 'apple';
    if (name.includes('linux')) return 'linux';
    return 'desktop';
}

// =====================================
// JQUERY VALIDATION INITIALIZATION
// =====================================

/**
 * Inicialización de jQuery Validation Plugin para el formulario de perfil
 * Integración oficial con Bootstrap 4
 */
$(function() {
    $('#profileForm').validate({
        rules: {
            firstName: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            lastName: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            phoneNumber: {
                minlength: 10,
                maxlength: 20
            },
            avatarUrl: {
                maxlength: 2048,
                url: true
            }
        },
        messages: VALIDATION_MESSAGES,
        // Integración con Bootstrap 4 - Utilidades Oficiales
        errorElement: 'span',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback d-block');
            element.closest('.form-group').append(error);
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        }
    });

    // Inicialización de jQuery Validation para formulario de preferencias
    $('#preferencesForm').validate({
        rules: {
            theme: { required: true },
            language: { required: true },
            timezone: { required: true }
        },
        messages: VALIDATION_MESSAGES,
        errorElement: 'span',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback d-block');
            element.closest('.form-group').append(error);
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        }
    });
});

// =====================================
// DOCUMENT READY - INITIALIZATION
// =====================================

document.addEventListener('DOMContentLoaded', async function() {
    const token = localStorage.getItem('access_token');

    if (!token) {
        window.location.href = '/login';
        return;
    }

    // Cargar datos iniciales
    await loadUserProfile(token);
    await loadSessions(token);

    // Event Listeners para forms
    document.getElementById('profileForm').addEventListener('submit', (e) => {
        e.preventDefault();
        if ($('#profileForm').valid()) {
            saveProfile(token);
        }
    });

    document.getElementById('preferencesForm').addEventListener('submit', (e) => {
        e.preventDefault();
        if ($('#preferencesForm').valid()) {
            savePreferences(token);
        }
    });

    // Botón para editar perfil
    document.getElementById('editProfileBtn').addEventListener('click', () => {
        document.getElementById('profileTab').click();
    });

    // Botón de preview para avatar
    document.getElementById('previewAvatarBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        const url = document.getElementById('avatarUrl').value.trim();
        if (url) {
            const validation = await validateImageUrl(url);
            if (validation.valid) {
                document.getElementById('avatarPreviewImg').src = validation.url;
                document.getElementById('avatarPreview').style.display = 'block';
                showToast('Éxito', 'Imagen cargada correctamente', 'success');
            } else {
                document.getElementById('avatarPreview').style.display = 'none';
                showToast('Error', validation.reason, 'danger');
            }
        } else {
            showToast('Error', 'Ingresa una URL antes de previsualizar', 'warning');
        }
    });

    // Logout All Sessions button
    document.getElementById('logoutAllBtn').addEventListener('click', () => {
        logoutAllSessions(token);
    });
});

// =====================================
// DATA LOADING FUNCTIONS
// =====================================

/**
 * Carga el perfil del usuario desde la API y actualiza la UI
 */
async function loadUserProfile(token) {
    try {
        const response = await fetch(API_ENDPOINTS.profile, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error('No se pudo cargar el perfil');

        const data = await response.json();
        const user = data.data || data;
        const profile = user.profile || {};

        // ===== LEFT COLUMN: Profile Card =====

        // Avatar
        const displayName = profile.displayName ||
            `${profile.firstName || ''} ${profile.lastName || ''}`.trim() ||
            user.email || 'Usuario';

        const avatarUrl = profile.avatarUrl && profile.avatarUrl.trim() ? profile.avatarUrl : null;
        const avatarImg = document.getElementById('profileAvatar');
        const fallbackUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&size=128&color=white&background=0D8ABC`;

        if (avatarUrl) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => { avatarImg.src = avatarUrl; };
            img.onerror = () => { avatarImg.src = fallbackUrl; };
            setTimeout(() => { if (avatarImg.src.includes('data:') === false) avatarImg.src = fallbackUrl; }, 5000);
            img.src = avatarUrl;
        } else {
            avatarImg.src = fallbackUrl;
        }

        // Profile name & email
        const profileNameSpinner = document.getElementById('profileNameSpinner');
        if (profileNameSpinner) profileNameSpinner.remove();
        document.getElementById('profileName').textContent = displayName;
        document.getElementById('profileEmail').textContent = user.email || '--';

        // Status badge
        document.getElementById('statusBadge').textContent = user.status || '--';
        document.getElementById('statusBadge').className = 'badge badge-' + getStatusColor(user.status);

        // Email verified badge
        document.getElementById('emailVerifiedBadge').textContent = user.emailVerified ? 'Sí' : 'No';
        document.getElementById('emailVerifiedBadge').className =
            'badge badge-' + (user.emailVerified ? 'success' : 'warning');

        // Member since
        if (user.createdAt) {
            document.getElementById('memberSince').textContent = new Date(user.createdAt).toLocaleDateString();
        }

        // ===== ABOUT ME CARD =====
        document.getElementById('aboutEmail').textContent = user.email || '--';
        document.getElementById('aboutPhone').textContent = profile.phoneNumber || 'No proporcionado';
        document.getElementById('aboutTheme').textContent = (profile.theme || 'light').charAt(0).toUpperCase() + (profile.theme || 'light').slice(1);
        document.getElementById('aboutLanguage').textContent = (profile.language === 'es' ? 'Español' : 'English');
        document.getElementById('aboutTimezone').textContent = profile.timezone || 'UTC';

        // ===== PROFILE FORM POPULATION =====
        document.getElementById('firstName').value = profile.firstName || '';
        document.getElementById('lastName').value = profile.lastName || '';
        document.getElementById('avatarUrl').value = profile.avatarUrl || '';
        document.getElementById('profileLoading').style.display = 'none';
        document.getElementById('profileForm').style.display = 'block';

        // Phone number handling
        if (profile.phoneNumber) {
            const phoneMatch = profile.phoneNumber.match(/^\+?(\d{1,3})?[\s-]?(\d+)$/);
            if (phoneMatch) {
                document.getElementById('phoneCountryCode').value = '+' + (phoneMatch[1] || '1');
                document.getElementById('phoneNumber').value = phoneMatch[2];
            } else {
                document.getElementById('phoneNumber').value = profile.phoneNumber;
            }
        }

        // ===== PREFERENCES FORM POPULATION =====
        document.getElementById('theme').value = profile.theme || 'light';
        document.getElementById('language').value = profile.language || 'en';
        document.getElementById('timezone').value = profile.timezone || 'UTC';
        document.getElementById('pushWebNotifications').checked = profile.pushWebNotifications || false;
        document.getElementById('notificationsTickets').checked = profile.notificationsTickets || false;
        document.getElementById('preferencesLoading').style.display = 'none';
        document.getElementById('preferencesForm').style.display = 'block';

    } catch (error) {
        console.error('Error loading profile:', error);
        showToast('Error', 'No se pudo cargar la información del perfil', 'danger');
    }
}

/**
 * Carga las sesiones activas del usuario
 */
async function loadSessions(token) {
    try {
        const response = await fetch(API_ENDPOINTS.sessions, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error('No se pudieron cargar las sesiones');

        const data = await response.json();
        const sessions = data.sessions || [];

        const tbody = document.getElementById('sessionsList');
        tbody.innerHTML = '';

        if (sessions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay sesiones activas</td></tr>';
        } else {
            sessions.forEach(session => {
                const tr = document.createElement('tr');
                const lastUsed = new Date(session.lastUsedAt).toLocaleString();
                const currentBadge = session.isCurrent
                    ? '<span class="badge badge-primary"><i class="fas fa-check mr-1"></i>Actual</span>'
                    : '';
                const revokeBtn = session.isCurrent
                    ? ''
                    : `<button class="btn btn-xs btn-danger" onclick="revokeSession('${session.sessionId}', '${token}')" type="button">
                         <i class="fas fa-times"></i>
                       </button>`;

                tr.innerHTML = `
                    <td><i class="fas fa-${getDeviceIcon(session.deviceName)} mr-2"></i>${session.deviceName}</td>
                    <td><code class="text-monospace">${session.ipAddress}</code></td>
                    <td>${lastUsed}</td>
                    <td>${currentBadge}</td>
                    <td>${revokeBtn}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('sessionsLoading').style.display = 'none';
        document.getElementById('sessionsContent').style.display = 'block';

    } catch (error) {
        console.error('Error loading sessions:', error);
        document.getElementById('sessionsLoading').innerHTML =
            '<div class="alert alert-danger">No se pudieron cargar las sesiones</div>';
    }
}

// =====================================
// FORM SUBMISSION FUNCTIONS
// =====================================

/**
 * Guarda cambios del perfil
 */
async function saveProfile(token) {
    try {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const avatarUrl = document.getElementById('avatarUrl').value.trim();

        // Validar avatar URL si se proporciona
        if (avatarUrl) {
            const validation = await validateImageUrl(avatarUrl);
            if (!validation.valid) {
                showToast('Error de Imagen', validation.reason, 'danger');
                return;
            }
        }

        const countryCode = document.getElementById('phoneCountryCode').value;
        const phoneNumberOnly = document.getElementById('phoneNumber').value.trim().replace(/\D/g, '');
        const phoneNumber = phoneNumberOnly ? countryCode + phoneNumberOnly : '';

        const data = {};
        if (firstName) data.firstName = firstName;
        if (lastName) data.lastName = lastName;
        if (phoneNumber) data.phoneNumber = phoneNumber;
        if (avatarUrl) data.avatarUrl = avatarUrl;

        const btn = document.getElementById('profileSubmitBtn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

        const response = await fetch(API_ENDPOINTS.updateProfile, {
            method: 'PATCH',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'No se pudo guardar el perfil');
        }

        showToast('Éxito', 'Perfil actualizado correctamente', 'success');
        await loadUserProfile(token);

    } catch (error) {
        console.error('Error saving profile:', error);
        showToast('Error', error.message || 'No se pudo guardar el perfil', 'danger');
    }
}

/**
 * Guarda cambios de preferencias
 */
async function savePreferences(token) {
    try {
        const data = {
            theme: document.getElementById('theme').value || 'light',
            language: document.getElementById('language').value || 'en',
            timezone: document.getElementById('timezone').value || 'UTC',
            pushWebNotifications: document.getElementById('pushWebNotifications').checked,
            notificationsTickets: document.getElementById('notificationsTickets').checked
        };

        const btn = document.getElementById('preferencesSubmitBtn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

        const response = await fetch(API_ENDPOINTS.updatePreferences, {
            method: 'PATCH',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'No se pudieron guardar las preferencias');
        }

        showToast('Éxito', 'Preferencias actualizadas correctamente', 'success');
        await loadUserProfile(token);

    } catch (error) {
        console.error('Error saving preferences:', error);
        showToast('Error', error.message || 'No se pudieron guardar las preferencias', 'danger');
    }
}

/**
 * Revoca una sesión específica
 * Utiliza SweetAlert2 para confirmación (Utilidad Official de AdminLTE)
 */
async function revokeSession(sessionId, token) {
    const confirmed = await Swal.fire({
        title: '¿Revocar sesión?',
        text: 'Esta acción cerrará la sesión en ese dispositivo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, revocar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirmed.isConfirmed) return;

    try {
        const response = await fetch(API_ENDPOINTS.deleteSession(sessionId), {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (response.status === 409) {
            showToast('Advertencia', 'No puedes revocar tu sesión actual', 'warning');
            return;
        }

        if (!response.ok) throw new Error('No se pudo revocar la sesión');

        showToast('Éxito', 'Sesión revocada correctamente', 'success');
        await loadSessions(token);

    } catch (error) {
        console.error('Error revoking session:', error);
        showToast('Error', error.message || 'No se pudo revocar la sesión', 'danger');
    }
}

/**
 * Cierra todas las sesiones
 * Utiliza SweetAlert2 para confirmación
 */
async function logoutAllSessions(token) {
    const confirmed = await Swal.fire({
        title: '¿Cerrar todas las sesiones?',
        text: 'Serás desconectado de todos los dispositivos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cerrar todas',
        cancelButtonText: 'Cancelar'
    });

    if (!confirmed.isConfirmed) return;

    try {
        const response = await fetch(API_ENDPOINTS.logout, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ everywhere: true })
        });

        if (!response.ok) throw new Error('No se pudo cerrar las sesiones');

        showToast('Éxito', 'Has sido desconectado de todas las sesiones', 'success');

        setTimeout(() => {
            localStorage.removeItem('access_token');
            localStorage.removeItem('helpdesk_token_expiry');
            localStorage.removeItem('helpdesk_token_issued_at');
            window.location.href = '/login';
        }, 1500);

    } catch (error) {
        console.error('Error logging out:', error);
        showToast('Error', error.message || 'No se pudieron cerrar las sesiones', 'danger');
    }
}
</script>
@endsection
