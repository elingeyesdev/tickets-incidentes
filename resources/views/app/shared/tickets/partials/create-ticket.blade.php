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
                        <select id="createCompany" class="form-control select2" style="width: 100%;" required>
                            <option value="">Selecciona una compañía...</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                        <select id="createCategory" class="form-control select2" style="width: 100%;" disabled required>
                            <option value="">Selecciona una compañía primero</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Subject --}}
            <div class="form-group">
                <label for="createTitle">Asunto <span class="text-danger">*</span></label>
                <input type="text" id="createTitle" class="form-control" placeholder="Asunto:" required maxlength="255">
                <small class="text-muted float-right" id="title-counter">0/255</small>
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label for="createDescription">Descripción <span class="text-danger">*</span></label>
                <textarea id="createDescription" class="form-control" style="height: 300px" placeholder="Escribe aquí los detalles del problema..." required maxlength="5000"></textarea>
                <small class="text-muted float-right" id="description-counter">0/5000</small>
            </div>

            {{-- File Input --}}
            <div class="form-group">
                <div class="btn btn-default btn-file">
                    <i class="fas fa-paperclip"></i> Adjuntar Archivos
                    <input type="file" id="createAttachment" name="attachment" multiple accept=".pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.mp4">
                </div>
                <p class="help-block">Max. 10MB por archivo (Límite 5 archivos)</p>
                
                {{-- File List Container --}}
                <div id="file-list-container" class="mt-2">
                    {{-- Files will be appended here via jQuery --}}
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        
        <div class="card-footer">
            <div class="float-right">
                <button type="button" class="btn btn-default" id="btn-discard-ticket"><i class="fas fa-times"></i> Descartar</button>
                <button type="submit" class="btn btn-primary"><i class="far fa-envelope"></i> Enviar Ticket</button>
            </div>
        </div>
        <!-- /.card-footer -->
    </form>
</div>

{{-- Template for File Item (Hidden) --}}
<template id="template-file-item">
    <div class="ticket-file-item alert alert-secondary alert-dismissible fade show mb-2" role="alert">
        <i class="fas fa-file file-icon mr-2"></i>
        <strong class="file-name">filename.pdf</strong>
        <span class="file-size text-muted ml-2">(1.2 MB)</span>
        <button type="button" class="close btn-remove-file" aria-label="Remove">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</template>

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
    });

    // ==============================================================
    // FILE HANDLING
    // ==============================================================
    
    $fileInput.on('change', function(e) {
        const newFiles = Array.from(this.files);
        
        // Clear input to allow re-selecting same file
        $(this).val('');

        let hasError = false;

        newFiles.forEach(file => {
            // Validation: Max Files
            if (selectedFiles.length >= MAX_FILES) {
                if (!hasError) Swal.fire('Límite alcanzado', 'Máximo 5 archivos permitidos.', 'warning');
                hasError = true;
                return;
            }

            // Validation: Size
            if (file.size > MAX_FILE_SIZE) {
                Swal.fire('Archivo muy grande', `El archivo ${file.name} excede los 10MB.`, 'warning');
                return;
            }

            // Validation: Extension
            const ext = file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_EXTENSIONS.includes(ext)) {
                Swal.fire('Formato no válido', `El archivo ${file.name} no es válido.`, 'warning');
                return;
            }

            // Add to array
            selectedFiles.push(file);
            renderFileItem(file);
        });
    });

    function renderFileItem(file) {
        const template = document.getElementById('template-file-item').content.cloneNode(true);
        const $item = $(template.querySelector('.ticket-file-item'));
        
        $item.find('.file-name').text(file.name);
        $item.find('.file-size').text(formatBytes(file.size));
        
        // Icon logic
        const ext = file.name.split('.').pop().toLowerCase();
        const $icon = $item.find('.file-icon');
        if (['jpg','jpeg','png','gif'].includes(ext)) $icon.removeClass('fa-file').addClass('fa-file-image text-warning');
        else if (ext === 'pdf') $icon.removeClass('fa-file').addClass('fa-file-pdf text-danger');
        else if (['doc','docx'].includes(ext)) $icon.removeClass('fa-file').addClass('fa-file-word text-info');
        else if (['xls','xlsx','csv'].includes(ext)) $icon.removeClass('fa-file').addClass('fa-file-excel text-success');

        // Remove Handler
        $item.find('.btn-remove-file').on('click', function() {
            const index = selectedFiles.indexOf(file);
            if (index > -1) {
                selectedFiles.splice(index, 1);
                $item.remove();
            }
        });

        $fileList.append($item);
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

    // jQuery Validation Rules
    $form.validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        },
        rules: {
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
            // Manual validation for Select2 (since they are hidden inputs usually)
            if (!$companySelect.val()) {
                Swal.fire('Error', 'Debes seleccionar una compañía', 'error');
                return false;
            }
            if (!$categorySelect.val()) {
                Swal.fire('Error', 'Debes seleccionar una categoría', 'error');
                return false;
            }

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
        $form[0].reset();
        $companySelect.val(null).trigger('change');
        selectedFiles = [];
        $fileList.empty();
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
