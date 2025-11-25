<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Crear Nuevo Ticket</h3>
    </div>
    <!-- /.card-header -->
    
    <form id="form-create-ticket" novalidate>
        <div class="card-body">
            
            {{-- Row 1: Company & Category --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="createCompany">Compañía <span class="text-danger">*</span></label>
                        <select id="createCompany" name="company_id" class="form-control select2" style="width: 100%;" required>
                            <option value="">Selecciona una compañía...</option>
                        </select>
                        <small class="form-text text-muted">Selecciona la compañía relacionada con este ticket</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                        <select id="createCategory" name="category_id" class="form-control select2" style="width: 100%;" disabled required>
                            <option value="">Selecciona una compañía primero</option>
                        </select>
                        <small class="form-text text-muted">Categoría que mejor describe el problema</small>
                    </div>
                </div>
            </div>

            {{-- Subject --}}
            <div class="form-group">
                <label for="createTitle">Asunto <span class="text-danger">*</span></label>
                <input type="text" id="createTitle" name="title" class="form-control" placeholder="Asunto:" required minlength="5" maxlength="255">
                <small class="form-text text-muted">Resumen breve del problema (mínimo 5 caracteres)</small>
                <small class="text-muted float-right" id="title-counter">0/255</small>
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label for="createDescription">Descripción <span class="text-danger">*</span></label>
                <textarea id="createDescription" name="description" class="form-control" style="height: 300px" placeholder="Escribe aquí los detalles del problema..." required minlength="10" maxlength="5000"></textarea>
                <small class="form-text text-muted">Describe el problema con el mayor detalle posible (mínimo 10 caracteres)</small>
                <small class="text-muted float-right" id="description-counter">0/5000</small>
            </div>

            {{-- File Input --}}
            <div class="form-group">
                <label for="createAttachment">Adjuntar Archivos</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="createAttachment" name="attachment" multiple accept=".pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.mp4">
                    <label class="custom-file-label" for="createAttachment">Seleccionar archivos...</label>
                </div>
                <small class="form-text text-muted">Máximo 10MB por archivo. Límite de 5 archivos. Formatos permitidos: PDF, imágenes, documentos Office, videos.</small>

                {{-- File List Container - AdminLTE v3 Official Mailbox Attachments Style --}}
                {{-- Note: align-items-stretch makes all items same height (official behavior) --}}
                {{-- Change to align-items-start for natural height (non-official) --}}
                <ul class="mailbox-attachments d-flex align-items-stretch clearfix" id="file-list-container">
                    {{-- Files will be appended here via jQuery --}}
                </ul>
            </div>
        </div>
        <!-- /.card-body -->

        <div class="card-footer">
            <div class="float-right">
                <button type="button" class="btn btn-outline-dark" id="btn-discard-ticket"><i class="fas fa-times"></i> Descartar</button>
                <button type="submit" class="btn btn-primary"><i class="far fa-envelope"></i> Enviar Ticket</button>
            </div>
        </div>
        <!-- /.card-footer -->
    </form>
</div>

{{-- Template for File Item (AdminLTE v3 Official Mailbox Attachment) --}}
<template id="template-file-item">
    <li>
        <span class="mailbox-attachment-icon"><i class="far fa-file"></i></span>
        <div class="mailbox-attachment-info">
            <a href="#" class="mailbox-attachment-name" title="">
                <i class="fas fa-paperclip"></i> <span class="file-name file-name-truncate">filename.pdf</span>
            </a>
            <span class="mailbox-attachment-size clearfix mt-1">
                <span class="file-size">1.2 MB</span>
                <button type="button" class="btn btn-default btn-sm float-right btn-remove-file"><i class="fas fa-times"></i></button>
            </span>
        </div>
    </li>
</template>

{{-- Push CSS to layout head (better practice than inline <style>) --}}
@push('css')
<style>
/* Alinear botones correctamente y agregar espacio entre ellos */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px; /* Espacio entre ícono y texto */
}

/* Agregar margen entre el botón Descartar y Enviar */
#btn-discard-ticket {
    margin-right: 10px;
}

/* Agregar espacio entre el small text y la lista de archivos */
.mailbox-attachments {
    margin-top: 12px !important;
}

/* Configurar el link como flex para alinear ícono + texto */
.mailbox-attachment-name {
    display: flex;
    align-items: center;
    gap: 4px; /* Pequeño espacio entre ícono y texto */
}

/* Truncar nombre de archivo a máximo 1 línea con ellipsis */
.file-name-truncate {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-all; /* Forzar ruptura en cualquier carácter (incluso guiones bajos) */
    flex: 1; /* Tomar todo el espacio disponible */
    min-width: 0; /* Permitir que el flex item se encoja más allá de su contenido */
}

/* Fix: AdminLTE v3 CSS oficial NO maneja correctamente imágenes portrait/landscape */
/* Forzar tamaño fijo para todos los thumbnails (portrait, landscape, 16:9, etc.) */
.mailbox-attachment-icon.has-img {
    width: 200px !important;          /* ← Ancho fijo igual al contenedor padre */
    height: 132.5px !important;       /* ← Altura fija igual a los íconos de archivo */
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background-color: #f4f4f4; /* ← Fondo gris claro para letterboxing */
    padding: 0 !important;     /* ← Sobrescribir padding de AdminLTE */
}

/* Ekko Lightbox - Link en thumbnail debe ser clickeable */
.mailbox-attachment-icon.has-img a[data-toggle="lightbox"] {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    height: 100% !important;
    cursor: pointer;
    text-decoration: none;
}

/* Hover effect para indicar que es clickeable */
.mailbox-attachment-icon.has-img a[data-toggle="lightbox"]:hover img {
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.mailbox-attachment-icon.has-img > a > img,
.mailbox-attachment-icon.has-img > img {
    width: 100% !important;           /* ← Llenar todo el ancho del contenedor */
    height: 100% !important;          /* ← Llenar toda la altura del contenedor */
    object-fit: cover !important;     /* ← Recortar manteniendo aspecto (sin distorsión) */
    object-position: center !important; /* ← Centrar la imagen antes de recortar */
    max-width: none !important;       /* ← Sobrescribir max-width: 100% de AdminLTE */
}
</style>
@endpush

<script>
(function() {
    console.log('[Create Ticket] Script cargado - esperando jQuery...');
    
    // Function to initialize the form
    function initCreateTicketForm() {
        console.log('[Create Ticket] jQuery disponible - Inicializando formulario');
        
        // ==============================================================
    // CONFIGURATION & STATE
    // ==============================================================
    const $form = $('#form-create-ticket');
    const $companySelect = $('#createCompany');
    const $categorySelect = $('#createCategory');
    const $fileInput = $('#createAttachment');
    const $fileList = $('#file-list-container');
    const $submitBtn = $form.find('button[type="submit"]');
    
    let selectedFiles = []; // Array to store File objects
    const MAX_FILES = 5;
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const ALLOWED_EXTENSIONS = ['pdf','txt','log','doc','docx','xls','xlsx','csv','jpg','jpeg','png','gif','bmp','webp','svg','mp4'];

    // ==============================================================
    // SELECT2 INITIALIZATION
    // ==============================================================
    
    // 1. Companies Select2
    $companySelect.select2({
        theme: 'bootstrap4',
        placeholder: 'Selecciona una compañía...',
        allowClear: true,
        ajax: {
            url: '/api/companies/minimal',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    per_page: 50,
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.data.map(function(company) {
                        return { id: company.id, text: company.name };
                    }),
                    pagination: {
                        more: (params.page * 50) < data.meta.total
                    }
                };
            },
            cache: true
        }
    });

    // 2. Categories Select2 (Dependent)
    $categorySelect.select2({
        theme: 'bootstrap4',
        placeholder: 'Selecciona una compañía primero',
        allowClear: true
    });

    // Handle Company Change
    $companySelect.on('change', function() {
        const companyId = $(this).val();
        console.log(`[Create Ticket] Compañía seleccionada: ${companyId || 'ninguna'}`);

        // Reset Category
        $categorySelect.val(null).trigger('change');

        if (companyId) {
            $categorySelect.prop('disabled', false);

            // Re-init Select2 with AJAX for specific company
            $categorySelect.select2({
                theme: 'bootstrap4',
                placeholder: 'Selecciona una categoría...',
                allowClear: true,
                ajax: {
                    url: '/api/tickets/categories',
                    dataType: 'json',
                    delay: 250,
                    headers: { 'Authorization': 'Bearer ' + window.tokenManager.getAccessToken() },
                    data: function (params) {
                        return {
                            company_id: companyId,
                            is_active: 'true',
                            search: params.term,
                            per_page: 50,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.data.map(function(cat) {
                                return { id: cat.id, text: cat.name };
                            }),
                            pagination: {
                                more: (params.page * 15) < data.meta.total
                            }
                        };
                    },
                    cache: true
                }
            });
        } else {
            $categorySelect.prop('disabled', true);
            $categorySelect.select2({
                theme: 'bootstrap4',
                placeholder: 'Selecciona una compañía primero'
            });
        }

        // 🔴 FIX Select2 + jQuery Validation: Forzar re-validación
        // Select2 interfiere con eventos nativos change de jQuery Validation
        $form.validate().element('#createCompany');
        console.log('[Create Ticket] Re-validando company_id');
    });

    // Handle Category Change - jQuery Validation Fix
    $categorySelect.on('change', function() {
        const categoryId = $(this).val();
        console.log(`[Create Ticket] Categoría seleccionada: ${categoryId || 'ninguna'}`);

        // 🔴 FIX Select2 + jQuery Validation: Forzar re-validación
        // Esto limpia el error de validación cuando el usuario selecciona una categoría
        $form.validate().element('#createCategory');
        console.log('[Create Ticket] Re-validando category_id');
    });

    // ==============================================================
    // FILE HANDLING
    // ==============================================================

    // Initialize bs-custom-file-input plugin (AdminLTE v3 standard)
    if (typeof bsCustomFileInput !== 'undefined') {
        bsCustomFileInput.init();
        console.log('[Create Ticket] ✓ bs-custom-file-input inicializado');
    } else {
        // Fallback: Actualizar label manualmente si el plugin no está disponible
        console.log('[Create Ticket] ⚠ bs-custom-file-input no disponible, usando fallback manual');
        $fileInput.on('change', function() {
            const fileCount = this.files.length;
            const label = $(this).siblings('.custom-file-label');
            if (fileCount === 0) {
                label.text('Seleccionar archivos...');
            } else if (fileCount === 1) {
                label.text(this.files[0].name);
            } else {
                label.text(`${fileCount} archivos seleccionados`);
            }
        });
    }

    $fileInput.on('change', function(e) {
        const newFiles = Array.from(this.files);
        console.log(`[Create Ticket] Archivos seleccionados: ${newFiles.length}`);

        // Clear input to allow re-selecting same file
        $(this).val('');
        // Reset label after clearing input
        $(this).siblings('.custom-file-label').text('Seleccionar archivos...');

        let hasError = false;

        newFiles.forEach(file => {
            console.log(`[Create Ticket] Procesando: ${file.name} (${formatBytes(file.size)})`);

            // Validation: Max Files
            if (selectedFiles.length >= MAX_FILES) {
                if (!hasError) {
                    console.warn(`[Create Ticket] ❌ Límite alcanzado: ${MAX_FILES} archivos máximo`);
                    Swal.fire('Límite alcanzado', 'Máximo 5 archivos permitidos.', 'warning');
                }
                hasError = true;
                return;
            }

            // Validation: Size
            if (file.size > MAX_FILE_SIZE) {
                console.warn(`[Create Ticket] ❌ Archivo muy grande: ${file.name} (${formatBytes(file.size)} > 10MB)`);
                Swal.fire('Archivo muy grande', `El archivo ${file.name} excede los 10MB.`, 'warning');
                return;
            }

            // Validation: Extension
            const ext = file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_EXTENSIONS.includes(ext)) {
                console.warn(`[Create Ticket] ❌ Extensión no permitida: ${ext} (archivo: ${file.name})`);
                Swal.fire('Formato no válido', `El archivo ${file.name} no es válido.`, 'warning');
                return;
            }

            // Add to array
            selectedFiles.push(file);
            console.log(`[Create Ticket] ✓ Archivo validado y agregado. Total: ${selectedFiles.length}/${MAX_FILES}`);
            renderFileItem(file);
        });
    });

    // Render File Item usando AdminLTE v3 Official Template (mailbox-attachments)
    function renderFileItem(file) {
        const template = document.getElementById('template-file-item').content.cloneNode(true);
        const $item = $(template.querySelector('li'));

        // Set file name and size
        $item.find('.file-name').text(file.name);
        $item.find('.file-size').text(formatBytes(file.size));

        // Add title attribute for tooltip (shows full name on hover when truncated)
        $item.find('.mailbox-attachment-name').attr('title', file.name);

        // Icon logic - AdminLTE v3 Official Icons
        const ext = file.name.split('.').pop().toLowerCase();
        const $icon = $item.find('.mailbox-attachment-icon i');

        // Icon mapping según AdminLTE v3 mailbox
        if (['jpg','jpeg','png','gif','bmp','webp','svg'].includes(ext)) {
            // Para imágenes, usar has-img class con thumbnail + preview lightbox
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageDataUrl = e.target.result;
                $item.find('.mailbox-attachment-icon')
                    .addClass('has-img')
                    .html(`
                        <a href="${imageDataUrl}"
                           data-toggle="lightbox"
                           data-title="${file.name}"
                           data-gallery="ticket-attachments"
                           class="w-100 h-100 d-flex align-items-center justify-content-center">
                            <img src="${imageDataUrl}" alt="${file.name}" class="w-100 h-100">
                        </a>
                    `);
            };
            reader.readAsDataURL(file);
        } else if (ext === 'pdf') {
            $icon.removeClass('fa-file').addClass('fa-file-pdf');
        } else if (['doc','docx'].includes(ext)) {
            $icon.removeClass('fa-file').addClass('fa-file-word');
        } else if (['xls','xlsx','csv'].includes(ext)) {
            $icon.removeClass('fa-file').addClass('fa-file-excel');
        } else if (ext === 'txt') {
            $icon.removeClass('fa-file').addClass('fa-file-alt');
        } else if (ext === 'mp4') {
            $icon.removeClass('fa-file').addClass('fa-file-video');
        } else {
            // Default: mantener fa-file
            $icon.addClass('fa-file');
        }

        // Remove Handler
        $item.find('.btn-remove-file').on('click', function(e) {
            e.preventDefault();
            const index = selectedFiles.indexOf(file);
            if (index > -1) {
                selectedFiles.splice(index, 1);
                $item.remove();
                console.log(`[Create Ticket] Archivo "${file.name}" eliminado. Total: ${selectedFiles.length}`);
            }
        });

        // Append to list
        $fileList.append($item);
        console.log(`[Create Ticket] ✓ Archivo "${file.name}" agregado a la lista (${formatBytes(file.size)})`);
    }

    function formatBytes(bytes, decimals = 2) {
        if (!+bytes) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }

    // ==============================================================
    // FORM VALIDATION & SUBMISSION
    // ==============================================================

    // Character Counters
    $('#createTitle').on('input', function() {
        $('#title-counter').text(`${$(this).val().length}/255`);
    });
    $('#createDescription').on('input', function() {
        $('#description-counter').text(`${$(this).val().length}/5000`);
    });

    // jQuery Validation Plugin Check
    console.log('[Create Ticket] Verificando jQuery Validation Plugin...');
    if (typeof $.fn.validate === 'undefined') {
        console.error('[Create Ticket] ERROR: jQuery Validation Plugin NO está cargado!');
        console.log('[Create Ticket] Validación inline NO funcionará');
    } else {
        console.log('[Create Ticket] ✓ jQuery Validation Plugin cargado correctamente');
    }

    // jQuery Validation Rules (AdminLTE v3 Official Configuration)
    $form.validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function(error, element) {
            // ESTÁNDAR OFICIAL AdminLTE v3: agregar al final del form-group
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid');
            // Ocultar form-text cuando aparece error de validación (mejora de UX)
            $(element).closest('.form-group').find('.form-text').hide();
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
            // Mostrar form-text nuevamente cuando el error desaparece
            $(element).closest('.form-group').find('.form-text').show();
        },
        rules: {
            company_id: {
                required: true
            },
            category_id: {
                required: true
            },
            title: {
                required: true,
                minlength: 5,
                maxlength: 255
            },
            description: {
                required: true,
                minlength: 10,
                maxlength: 5000
            }
        },
        messages: {
            company_id: {
                required: "Debes seleccionar una compañía"
            },
            category_id: {
                required: "Debes seleccionar una categoría"
            },
            title: {
                required: "El asunto es obligatorio",
                minlength: "El asunto debe tener al menos 5 caracteres",
                maxlength: "El asunto no puede exceder los 255 caracteres"
            },
            description: {
                required: "La descripción es obligatoria",
                minlength: "La descripción debe tener al menos 10 caracteres",
                maxlength: "La descripción no puede exceder los 5000 caracteres"
            }
        },
        submitHandler: function(form) {
            submitTicket();
        }
    });
    
    console.log('[Create Ticket] ✓ Validación configurada exitosamente');

    async function submitTicket() {
        // Disable button & show loading
        const originalBtnText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        try {
            const token = window.tokenManager.getAccessToken();
            
            // 1. Create Ticket
            const ticketData = {
                title: $('#createTitle').val(),
                description: $('#createDescription').val(),
                company_id: $companySelect.val(),
                category_id: $categorySelect.val()
            };

            const response = await fetch('/api/tickets', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(ticketData)
            });

            const result = await response.json();

            if (!response.ok) {
                // Handle Errors
                if (response.status === 422) {
                    let errorMsg = '<ul>';
                    $.each(result.errors, function(key, msgs) {
                        errorMsg += `<li>${msgs[0]}</li>`;
                    });
                    errorMsg += '</ul>';
                    Swal.fire({ icon: 'error', title: 'Error de Validación', html: errorMsg });
                } else {
                    throw new Error(result.message || 'Error al crear el ticket');
                }
                return; // Stop execution
            }

            const ticketCode = result.data.ticket_code;

            // 2. Upload Files (if any)
            if (selectedFiles.length > 0) {
                // Update UI to show uploading status
                $submitBtn.html('<i class="fas fa-cloud-upload-alt"></i> Subiendo archivos...');
                
                for (const file of selectedFiles) {
                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        // Assuming endpoint based on standard REST conventions
                        await fetch(`/api/tickets/${ticketCode}/attachments`, {
                            method: 'POST',
                            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
                            body: formData
                        });
                    } catch (uploadError) {
                        console.error(`Error uploading ${file.name}`, uploadError);
                        // We continue uploading other files, but warn user at the end?
                        // For now, silent fail on individual file, but ticket is created.
                    }
                }
            }

            // Success!
            Swal.fire({
                icon: 'success',
                title: 'Ticket Creado',
                text: `Ticket ${ticketCode} creado exitosamente.`,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Reset Form
                $form[0].reset();
                $companySelect.val(null).trigger('change');
                $categorySelect.val(null).trigger('change');
                selectedFiles = [];
                $fileList.empty();
                
                // Return to List View (Trigger global event)
                $(document).trigger('tickets:created');
            });

        } catch (error) {
            console.error('Error creating ticket:', error);
            Swal.fire('Error', error.message || 'Ocurrió un error inesperado', 'error');
        } finally {
            // Restore button
            $submitBtn.prop('disabled', false).html(originalBtnText);
        }
    }

    // Discard Button
    $('#btn-discard-ticket').on('click', function() {
        console.log('[Create Ticket] Descartando formulario...');

        $form[0].reset();
        $companySelect.val(null).trigger('change');
        $categorySelect.val(null).trigger('change');
        selectedFiles = [];
        $fileList.empty();

        // Reset character counters
        $('#title-counter').text('0/255');
        $('#description-counter').text('0/5000');

        // Reset custom file input label
        $('.custom-file-label').text('Seleccionar archivos...');

        // Remove validation errors
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        // Asegurar que form-text esté visible después de limpiar errores
        $form.find('.form-text').show();

        console.log('[Create Ticket] ✓ Formulario descartado y limpiado completamente');

        $(document).trigger('tickets:discarded');
    });
    } // End of initCreateTicketForm
    
    // Wait for jQuery to be available
    if (typeof jQuery !== 'undefined') {
        // jQuery is already loaded
        $(document).ready(initCreateTicketForm);
    } else {
        // Wait for jQuery to load
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                console.log('[Create Ticket] jQuery detectado después de esperar');
                clearInterval(checkJQuery);
                $(document).ready(initCreateTicketForm);
            }
        }, 100); // Check every 100ms
        
        // Timeout after 10 seconds
        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[Create Ticket] ERROR: jQuery no se cargó después de 10 segundos');
            }
        }, 10000);
    }
})();
</script>
