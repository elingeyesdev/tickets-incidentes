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
                    </div>
                </div>
                <!-- /.card -->

                <!-- TARJETA 2: About Me (Información Descriptiva) -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book mr-2"></i>Acerca de Mí
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Datos de contacto e información básica (ESTADÍSTICA, NO EDITABLE) -->
                        <strong><i class="fas fa-envelope mr-2"></i>Email</strong>
                        <p class="text-muted mb-3" id="aboutEmail">--</p>

                        <strong><i class="fas fa-phone mr-2"></i>Teléfono</strong>
                        <p class="text-muted mb-3" id="aboutPhone">No proporcionado</p>

                        <hr>

                        <strong><i class="fas fa-globe mr-2"></i>Zona Horaria</strong>
                        <p class="text-muted mb-3" id="aboutTimezone">--</p>

                        <strong><i class="fas fa-language mr-2"></i>Idioma</strong>
                        <p class="text-muted mb-3" id="aboutLanguage">--</p>

                        <strong><i class="fas fa-palette mr-2"></i>Tema</strong>
                        <p class="text-muted" id="aboutTheme">--</p>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-3 -->

            <!-- COLUMNA DERECHA: Tarjeta Tabbed con .nav-pills (col-md-9) -->
            <div class="col-md-9">
                <div class="card">

                    <!-- NAV PILLS en Card Header (Patrón Canónico AdminLTE v3) -->
                    <!-- IMPORTANTE: .nav-pills (no .nav-tabs) para estilo de botones -->
                    <div class="card-header p-2">
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="activityTab" data-toggle="tab" href="#activityPane"
                                   role="tab" aria-controls="activityPane" aria-selected="true">
                                    <i class="fas fa-history mr-2"></i>Actividad
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="preferencesTab" data-toggle="tab" href="#preferencesPane"
                                   role="tab" aria-controls="preferencesPane" aria-selected="false">
                                    <i class="fas fa-sliders-h mr-2"></i>Preferencias
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="profileDataTab" data-toggle="tab" href="#profileDataPane"
                                   role="tab" aria-controls="profileDataPane" aria-selected="false">
                                    <i class="fas fa-user-circle mr-2"></i>Datos de Perfil
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

                            <!-- ========== PESTAÑA 1: ACTIVITY ========== -->
                            <div class="tab-pane fade show active" id="activityPane" role="tabpanel"
                                 aria-labelledby="activityTab">

                                <div id="activityLoading" class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Información</h5>
                                    Cargando historial de actividad...
                                </div>

                                <!-- Tabla de Auditoría (Responsiva) -->
                                <div id="activityContent" style="display: none;" class="table-responsive">
                                    <table class="table table-hover table-striped table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 15%;">Evento ID</th>
                                                <th style="width: 25%;">Acción</th>
                                                <th style="width: 35%;">Detalles</th>
                                                <th style="width: 25%;">Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityList">
                                            <!-- Populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /.tab-pane #activityPane -->

                            <!-- ========== PESTAÑA 2: PREFERENCIAS (Auto-Guardado) ========== -->
                            <div class="tab-pane fade" id="preferencesPane" role="tabpanel"
                                 aria-labelledby="preferencesTab">

                                <div id="preferencesLoading" class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Información</h5>
                                    Cargando preferencias...
                                </div>

                                <!-- Formulario INDEPENDIENTE para auto-guardado -->
                                <form class="form-horizontal" id="form-preferencias" style="display: none;">

                                    <!-- Tema -->
                                    <div class="form-group row">
                                        <label for="pref-tema" class="col-sm-2 col-form-label">
                                            <strong>Tema</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <select class="form-control form-control-auto-save select2"
                                                    name="pref_tema"
                                                    id="pref-tema"
                                                    style="width: 100%;">
                                                <option value="light">Claro</option>
                                                <option value="dark">Oscuro</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Idioma -->
                                    <div class="form-group row">
                                        <label for="pref-idioma" class="col-sm-2 col-form-label">
                                            <strong>Idioma</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <select class="form-control form-control-auto-save select2"
                                                    name="pref_idioma"
                                                    id="pref-idioma"
                                                    style="width: 100%;">
                                                <option value="en">English</option>
                                                <option value="es">Español</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Zona Horaria -->
                                    <div class="form-group row">
                                        <label for="pref-timezone" class="col-sm-2 col-form-label">
                                            <strong>Zona Horaria</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <select class="form-control form-control-auto-save select2"
                                                    name="pref_timezone"
                                                    id="pref-timezone"
                                                    style="width: 100%;">
                                                <option value="UTC">UTC</option>
                                                <option value="America/New_York">America/New_York</option>
                                                <option value="America/Chicago">America/Chicago</option>
                                                <option value="America/Denver">America/Denver</option>
                                                <option value="America/Los_Angeles">America/Los_Angeles</option>
                                                <option value="America/Toronto">America/Toronto</option>
                                                <option value="America/Mexico_City">America/Mexico_City</option>
                                                <option value="America/Bogota">America/Bogota</option>
                                                <option value="America/La_Paz">America/La_Paz</option>
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
                                        </div>
                                    </div>

                                    <!-- Notificaciones Push -->
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input form-control-auto-save"
                                                       id="pref-push-notifications"
                                                       name="pref_push_notifications">
                                                <label class="custom-control-label" for="pref-push-notifications">
                                                    <strong>Habilitar Notificaciones Push</strong>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notificaciones de Tickets -->
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input form-control-auto-save"
                                                       id="pref-ticket-notifications"
                                                       name="pref_ticket_notifications">
                                                <label class="custom-control-label" for="pref-ticket-notifications">
                                                    <strong>Habilitar Notificaciones de Tickets</strong>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                </form>

                                <!-- Estado de Auto-Guardado (Máquina de Estados) -->
                                <div id="preferencias-save-status" class="mt-3" style="display: none;"></div>
                            </div>
                            <!-- /.tab-pane #preferencesPane -->

                            <!-- ========== PESTAÑA 3: DATOS DE PERFIL (Guardado Manual) ========== -->
                            <div class="tab-pane fade" id="profileDataPane" role="tabpanel"
                                 aria-labelledby="profileDataTab">

                                <div id="profileDataLoading" class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Información</h5>
                                    Cargando datos del perfil...
                                </div>

                                <!-- Formulario INDEPENDIENTE para guardado manual -->
                                <form class="form-horizontal" id="form-profile-data" style="display: none;">

                                    <!-- Nombre -->
                                    <div class="form-group row">
                                        <label for="profile-nombre" class="col-sm-2 col-form-label">
                                            <strong>Nombre</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="profile-nombre"
                                                   name="profile_nombre" placeholder="Ej: Juan"
                                                   minlength="2" maxlength="100" required>
                                            <small class="form-text text-muted d-block mt-1">Mínimo 2 caracteres</small>
                                        </div>
                                    </div>

                                    <!-- Apellido -->
                                    <div class="form-group row">
                                        <label for="profile-apellido" class="col-sm-2 col-form-label">
                                            <strong>Apellido</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="profile-apellido"
                                                   name="profile_apellido" placeholder="Ej: Pérez"
                                                   minlength="2" maxlength="100" required>
                                            <small class="form-text text-muted d-block mt-1">Mínimo 2 caracteres</small>
                                        </div>
                                    </div>

                                    <!-- Teléfono -->
                                    <div class="form-group row">
                                        <label for="profile-telefono" class="col-sm-2 col-form-label">
                                            <strong>Teléfono</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <select class="custom-select" id="profile-country-code" style="max-width: 120px;">
                                                        <option value="+1">🇺🇸 +1</option>
                                                        <option value="+52">🇲🇽 +52</option>
                                                        <option value="+56">🇨🇱 +56</option>
                                                        <option value="+54">🇦🇷 +54</option>
                                                        <option value="+57">🇨🇴 +57</option>
                                                        <option value="+591">🇧🇴 +591</option>
                                                    </select>
                                                </div>
                                                <input type="tel" class="form-control" id="profile-telefono"
                                                       name="profile_telefono" placeholder="555123456"
                                                       minlength="8" maxlength="20">
                                            </div>
                                            <small class="form-text text-muted d-block mt-1">8-20 dígitos</small>
                                        </div>
                                    </div>

                                    <!-- Avatar URL -->
                                    <div class="form-group row">
                                        <label for="profile-avatar" class="col-sm-2 col-form-label">
                                            <strong>Avatar</strong>
                                        </label>
                                        <div class="col-sm-10">
                                            <input type="url" class="form-control" id="profile-avatar"
                                                   name="profile_avatar" placeholder="https://ejemplo.com/avatar.jpg"
                                                   maxlength="2048">
                                            <small class="form-text text-muted d-block mt-1">
                                                URL válida (HTTP/HTTPS)
                                                <button type="button" class="btn btn-link btn-sm p-0 ml-2" id="preview-avatar-btn">
                                                    <i class="fas fa-eye mr-1"></i>Previsualizar
                                                </button>
                                            </small>
                                            <div id="avatar-preview" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                                <div class="text-center">
                                                    <img id="avatar-preview-img" src="" alt="Preview"
                                                         style="max-width: 150px; max-height: 150px; border-radius: 8px; object-fit: cover;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botones de Acción -->
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <button type="submit" class="btn btn-primary" id="profile-submit-btn">
                                                <i class="fas fa-save mr-2"></i>Guardar Cambios
                                            </button>
                                            <button type="button" class="btn btn-secondary ml-2" id="profile-undo-btn">
                                                <i class="fas fa-undo mr-2"></i>Deshacer
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                            <!-- /.tab-pane #profileDataPane -->

                            <!-- ========== PESTAÑA 4: SESIONES ========== -->
                            <div class="tab-pane fade" id="sessionsPane" role="tabpanel"
                                 aria-labelledby="sessionsTab">

                                <div id="sessionsLoading" class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Información</h5>
                                    Cargando sesiones activas...
                                </div>

                                <div id="sessionsContent" style="display: none;">
                                    <div class="mb-3">
                                        <button class="btn btn-danger btn-sm" id="logout-all-btn" type="button">
                                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar todas las sesiones
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped table-sm">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 30%;">Dispositivo</th>
                                                    <th style="width: 25%;">IP</th>
                                                    <th style="width: 25%;">Último Uso</th>
                                                    <th style="width: 10%;">Estado</th>
                                                    <th style="width: 10%;">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="sessions-list">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane #sessionsPane -->

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
    profile_nombre: {
        required: 'El nombre es obligatorio',
        minlength: 'El nombre debe tener al menos 2 caracteres',
        maxlength: 'El nombre no puede exceder 100 caracteres'
    },
    profile_apellido: {
        required: 'El apellido es obligatorio',
        minlength: 'El apellido debe tener al menos 2 caracteres',
        maxlength: 'El apellido no puede exceder 100 caracteres'
    },
    profile_telefono: {
        minlength: 'El teléfono debe tener al menos 8 caracteres',
        maxlength: 'El teléfono no puede exceder 20 caracteres'
    }
};

// =====================================
// UTILITY FUNCTIONS
// =====================================

/**
 * Función Debounce de Grado Producción
 * Retrasa la ejecución de 'func' hasta que 'wait' milisegundos
 * hayan pasado desde la última vez que esta función fue invocada.
 */
function debounce(func, wait) {
    let timeoutId = null;

    return function(...args) {
        const context = this;
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

/**
 * Muestra un toast (notificación no intrusiva)
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
 * Valida si una URL es válida
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
                reason: 'La imagen tardó demasiado en cargar.'
            });
        }, 5000);

        img.onload = () => {
            clearTimeout(timeout);
            resolve({ valid: true, url: url });
        };

        img.onerror = () => {
            clearTimeout(timeout);
            const domain = new URL(url).hostname;
            if (domain.includes('wikia') || domain.includes('fandom')) {
                resolve({
                    valid: false,
                    reason: 'Los servidores de Wikia/Fandom bloquean acceso externo. Usa Imgur u otro CDN.'
                });
            } else {
                resolve({
                    valid: false,
                    reason: `No se puede cargar la imagen desde ${domain}.`
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
// JQUERY VALIDATION + PLUGINS INITIALIZATION
// =====================================

$(function() {
    // Inicializar jQuery Validation para Datos de Perfil
    $('#form-profile-data').validate({
        rules: {
            profile_nombre: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            profile_apellido: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            profile_telefono: {
                minlength: 10,
                maxlength: 20
            },
            profile_avatar: {
                maxlength: 2048,
                url: true
            }
        },
        messages: VALIDATION_MESSAGES,
        errorElement: 'span',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback d-block');
            element.closest('.form-group').append(error);
        },
        highlight: function(element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        }
    });

    // Inicializar Select2 en Preferencias
    $('#pref-tema').select2({ theme: 'bootstrap4' });
    $('#pref-idioma').select2({ theme: 'bootstrap4' });
    $('#pref-timezone').select2({ theme: 'bootstrap4', placeholder: 'Selecciona una zona horaria' });
});

// =====================================
// LÓGICA DE AUTO-GUARDADO INTELIGENTE (PREFERENCIAS)
// =====================================

// Flag para rastrear si estamos cargando datos iniciales
let isLoadingInitialPreferences = true;

// Almacenar estado anterior de preferencias para comparacion inteligente
let previousPreferencesState = {
    theme: 'light',
    language: 'en',
    timezone: 'UTC',
    pushWebNotifications: false,
    notificationsTickets: false
};

document.addEventListener('DOMContentLoaded', function() {
    const formPreferencias = document.getElementById('form-preferencias');
    const statusDisplay = document.getElementById('preferencias-save-status');

    if (formPreferencias && statusDisplay) {
        const inputs = formPreferencias.querySelectorAll('.form-control-auto-save');
        const WAIT_TIME = 3000; // 3 segundos

        // Funciones de Estado de UX
        function setStatusSaving() {
            statusDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando cambios...';
            statusDisplay.className = 'alert alert-info mt-3';
            statusDisplay.style.display = 'block';
        }

        function setStatusSaved() {
            statusDisplay.innerHTML = '<i class="fas fa-check"></i> ¡Cambios guardados!';
            statusDisplay.className = 'alert alert-success mt-3';
            statusDisplay.style.display = 'block';
            setTimeout(() => {
                statusDisplay.style.display = 'none';
            }, 2000);
        }

        function setStatusError(message = 'Error al guardar. Intente de nuevo.') {
            statusDisplay.innerHTML = `<i class="fas fa-times"></i> ${message}`;
            statusDisplay.className = 'alert alert-danger mt-3';
            statusDisplay.style.display = 'block';
        }

        // Función de Guardado Inteligente
        async function guardarPreferencias() {
            const token = localStorage.getItem('access_token');

            // Obtener estado actual
            const currentState = {
                theme: document.getElementById('pref-tema').value || 'light',
                language: document.getElementById('pref-idioma').value || 'en',
                timezone: document.getElementById('pref-timezone').value || 'UTC',
                pushWebNotifications: document.getElementById('pref-push-notifications').checked,
                notificationsTickets: document.getElementById('pref-ticket-notifications').checked
            };

            // Comparar con estado anterior
            const hasChanges = Object.keys(currentState).some(key => {
                return currentState[key] !== previousPreferencesState[key];
            });

            // Si no hay cambios reales, no hacer nada
            if (!hasChanges) {
                console.log('[Preferences] Sin cambios detectados. No se envía PATCH.');
                return;
            }

            console.log('[Preferences] Cambios detectados. Guardando...', {
                anterior: previousPreferencesState,
                actual: currentState
            });

            setStatusSaving();

            try {
                const response = await fetch(API_ENDPOINTS.updatePreferences, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(currentState)
                });

                if (!response.ok) {
                    throw new Error(`Error: ${response.status}`);
                }

                // Actualizar estado anterior con el estado guardado
                previousPreferencesState = { ...currentState };
                setStatusSaved();
                console.log('[Preferences] Guardado exitoso.');
            } catch (error) {
                console.error('Error en auto-guardado:', error);
                setStatusError(error.message);
            }
        }

        // Instanciar debounced
        const debouncedSave = debounce(guardarPreferencias, WAIT_TIME);

        // Adjuntar listeners
        inputs.forEach(input => {
            const nodeName = input.nodeName.toLowerCase();

            if (nodeName === 'input' && (input.type === 'text' || input.type === 'email')) {
                input.addEventListener('keyup', debouncedSave);
            } else {
                input.addEventListener('change', debouncedSave);
            }
        });

        // Para Select2, necesitamos usar su evento específico
        // IMPORTANTE: Solo guardar si NO estamos en la carga inicial
        $('#pref-tema').on('change.select2', function() {
            if (!isLoadingInitialPreferences) {
                debouncedSave();
            }
        });
        $('#pref-idioma').on('change.select2', function() {
            if (!isLoadingInitialPreferences) {
                debouncedSave();
            }
        });
        $('#pref-timezone').on('change.select2', function() {
            if (!isLoadingInitialPreferences) {
                debouncedSave();
            }
        });
    }
});

// =====================================
// INICIALIZACIÓN GENERAL
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

    // Manejador para Datos de Perfil (Guardado Manual)
    const formProfileData = document.getElementById('form-profile-data');
    if (formProfileData) {
        formProfileData.addEventListener('submit', (e) => {
            e.preventDefault();
            if ($('#form-profile-data').valid()) {
                saveProfileData(token);
            }
        });
    }

    // Botón Deshacer - Restaura al estado original guardado
    const undoBtn = document.getElementById('profile-undo-btn');
    if (undoBtn) {
        undoBtn.addEventListener('click', (e) => {
            e.preventDefault();
            restoreProfileDataToOriginal();
        });
    }

    // Botón Preview Avatar
    const previewBtn = document.getElementById('preview-avatar-btn');
    if (previewBtn) {
        previewBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const url = document.getElementById('profile-avatar').value.trim();
            if (url) {
                const validation = await validateImageUrl(url);
                if (validation.valid) {
                    document.getElementById('avatar-preview-img').src = validation.url;
                    document.getElementById('avatar-preview').style.display = 'block';
                    showToast('Éxito', 'Imagen cargada correctamente', 'success');
                } else {
                    document.getElementById('avatar-preview').style.display = 'none';
                    showToast('Error', validation.reason, 'danger');
                }
            } else {
                showToast('Error', 'Ingresa una URL antes de previsualizar', 'warning');
            }
        });
    }

    // Botón Logout All
    const logoutAllBtn = document.getElementById('logout-all-btn');
    if (logoutAllBtn) {
        logoutAllBtn.addEventListener('click', () => {
            logoutAllSessions(token);
        });
    }
});

// =====================================
// DATA LOADING FUNCTIONS
// =====================================

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

        // Actualizar tarjeta de perfil
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
            setTimeout(() => { if (!avatarImg.src.includes(avatarUrl)) avatarImg.src = fallbackUrl; }, 5000);
            img.src = avatarUrl;
        } else {
            avatarImg.src = fallbackUrl;
        }

        const spinner = document.getElementById('profileNameSpinner');
        if (spinner) spinner.remove();
        document.getElementById('profileName').textContent = displayName;
        document.getElementById('profileEmail').textContent = user.email || '--';

        document.getElementById('statusBadge').textContent = user.status || '--';
        document.getElementById('statusBadge').className = 'badge badge-' + getStatusColor(user.status);
        document.getElementById('emailVerifiedBadge').textContent = user.emailVerified ? 'Sí' : 'No';
        document.getElementById('emailVerifiedBadge').className = 'badge badge-' + (user.emailVerified ? 'success' : 'warning');

        if (user.createdAt) {
            document.getElementById('memberSince').textContent = new Date(user.createdAt).toLocaleDateString();
        }

        // Actualizar About Me
        document.getElementById('aboutEmail').textContent = user.email || '--';
        document.getElementById('aboutPhone').textContent = profile.phoneNumber || 'No proporcionado';
        document.getElementById('aboutTheme').textContent = (profile.theme || 'light').charAt(0).toUpperCase() + (profile.theme || 'light').slice(1);
        document.getElementById('aboutLanguage').textContent = (profile.language === 'es' ? 'Español' : 'English');
        document.getElementById('aboutTimezone').textContent = profile.timezone || 'UTC';

        // Llenar formulario de Datos de Perfil
        document.getElementById('profile-nombre').value = profile.firstName || '';
        document.getElementById('profile-apellido').value = profile.lastName || '';
        document.getElementById('profile-avatar').value = profile.avatarUrl || '';

        if (profile.phoneNumber) {
            const phoneMatch = profile.phoneNumber.match(/^\+?(\d{1,3})?[\s-]?(\d+)$/);
            if (phoneMatch) {
                document.getElementById('profile-country-code').value = '+' + (phoneMatch[1] || '1');
                document.getElementById('profile-telefono').value = phoneMatch[2];
            } else {
                document.getElementById('profile-telefono').value = profile.phoneNumber;
            }
        }

        // Llenar Preferencias
        document.getElementById('pref-tema').value = profile.theme || 'light';
        document.getElementById('pref-idioma').value = profile.language || 'en';
        document.getElementById('pref-timezone').value = profile.timezone || 'UTC';
        document.getElementById('pref-push-notifications').checked = profile.pushWebNotifications || false;
        document.getElementById('pref-ticket-notifications').checked = profile.notificationsTickets || false;

        // Actualizar estado anterior de preferencias con los valores cargados
        // Esto se usa para la comparacion inteligente de cambios
        previousPreferencesState = {
            theme: profile.theme || 'light',
            language: profile.language || 'en',
            timezone: profile.timezone || 'UTC',
            pushWebNotifications: profile.pushWebNotifications || false,
            notificationsTickets: profile.notificationsTickets || false
        };

        // Refrescar Select2
        $('#pref-tema').trigger('change');
        $('#pref-idioma').trigger('change');
        $('#pref-timezone').trigger('change');

        // Mostrar formularios
        document.getElementById('profileDataLoading').style.display = 'none';
        document.getElementById('form-profile-data').style.display = 'block';
        document.getElementById('preferencesLoading').style.display = 'none';
        document.getElementById('form-preferencias').style.display = 'block';

        // Guardar estado original para el botón Deshacer
        saveProfileDataAsOriginal();

        // MARCAR FIN DE CARGA INICIAL - Ahora los cambios se guardarán automáticamente
        isLoadingInitialPreferences = false;
        console.log('[Preferences] Carga inicial completada. Auto-guardado habilitado.');

        // Cargar actividad simulada (demo)
        loadActivityData();

    } catch (error) {
        console.error('Error loading profile:', error);
        showToast('Error', 'No se pudo cargar el perfil', 'danger');
    }
}

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

        const tbody = document.getElementById('sessions-list');
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
                    <td><code>${session.ipAddress}</code></td>
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
        document.getElementById('sessionsLoading').innerHTML = '<div class="alert alert-danger">No se pudieron cargar las sesiones</div>';
    }
}

/**
 * Carga datos de actividad simulados (demo)
 * En producción, estos vendrían de una API
 */
function loadActivityData() {
    const activityList = document.getElementById('activityList');
    const activities = [
        { id: 1001, action: 'Perfil Actualizado', details: 'Nombre, Apellido', date: new Date().toLocaleString() },
        { id: 1002, action: 'Sesión Iniciada', details: 'IP: 192.168.1.1', date: new Date(Date.now() - 3600000).toLocaleString() },
        { id: 1003, action: 'Preferencias Guardadas', details: 'Tema, Idioma', date: new Date(Date.now() - 7200000).toLocaleString() }
    ];

    activityList.innerHTML = '';
    activities.forEach(activity => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${activity.id}</td>
            <td>${activity.action}</td>
            <td>${activity.details}</td>
            <td>${activity.date}</td>
        `;
        activityList.appendChild(tr);
    });

    document.getElementById('activityLoading').style.display = 'none';
    document.getElementById('activityContent').style.display = 'block';
}

// =====================================
// FORM SUBMISSION FUNCTIONS
// =====================================

/**
 * Guarda los datos del perfil (Guardado Manual)
 */
async function saveProfileData(token) {
    try {
        const firstName = document.getElementById('profile-nombre').value.trim();
        const lastName = document.getElementById('profile-apellido').value.trim();
        const avatarUrl = document.getElementById('profile-avatar').value.trim();

        // Validar avatar si se proporciona
        if (avatarUrl) {
            const validation = await validateImageUrl(avatarUrl);
            if (!validation.valid) {
                showToast('Error de Imagen', validation.reason, 'danger');
                return;
            }
        }

        const countryCode = document.getElementById('profile-country-code').value;
        const phoneNumberOnly = document.getElementById('profile-telefono').value.trim().replace(/\D/g, '');
        const phoneNumber = phoneNumberOnly ? countryCode + phoneNumberOnly : '';

        const data = {};
        if (firstName) data.firstName = firstName;
        if (lastName) data.lastName = lastName;
        if (phoneNumber) data.phoneNumber = phoneNumber;
        if (avatarUrl) data.avatarUrl = avatarUrl;

        const btn = document.getElementById('profile-submit-btn');
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
 * Revoca una sesión
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

/**
 * Variable global para almacenar el estado original del formulario de perfil
 */
let originalProfileData = {
    nombre: '',
    apellido: '',
    telefono: '',
    countryCode: '+1',
    avatar: ''
};

/**
 * Guarda el estado actual del formulario de perfil como el estado original
 * Se llama automáticamente después de cargar los datos del usuario
 */
function saveProfileDataAsOriginal() {
    originalProfileData = {
        nombre: document.getElementById('profile-nombre').value,
        apellido: document.getElementById('profile-apellido').value,
        telefono: document.getElementById('profile-telefono').value,
        countryCode: document.getElementById('profile-country-code').value,
        avatar: document.getElementById('profile-avatar').value
    };
    console.log('[Profile] Estado original guardado:', originalProfileData);
}

/**
 * Restaura el formulario de perfil al estado original guardado
 */
function restoreProfileDataToOriginal() {
    document.getElementById('profile-nombre').value = originalProfileData.nombre;
    document.getElementById('profile-apellido').value = originalProfileData.apellido;
    document.getElementById('profile-telefono').value = originalProfileData.telefono;
    document.getElementById('profile-country-code').value = originalProfileData.countryCode;
    document.getElementById('profile-avatar').value = originalProfileData.avatar;

    // Limpiar preview de avatar
    document.getElementById('avatar-preview').style.display = 'none';

    // Limpiar errores de validación
    $('#form-profile-data').validate().resetForm();

    console.log('[Profile] Datos restaurados al estado original');
    showToast('Información', 'Cambios descartados', 'info');
}
</script>
@endsection
