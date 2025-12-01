<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Crear Nuevo Ticket</h3>
    </div>
    <!-- /.card-header -->

    <form id="form-create-ticket" novalidate>
        <div class="card-body">

            {{-- Row 1: Company Search (Full Width) - AdminLTE v3 Official Search --}}
            <div class="form-group">
                <label for="createCompany">Compañía <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="search"
                           id="createCompanySearch"
                           class="form-control form-control-lg"
                           placeholder="Buscar compañía por nombre..."
                           autocomplete="off">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-lg btn-default" id="btn-search-company">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" id="createCompany" name="company_id" required>
                <small class="form-text text-muted">Busca y selecciona la compañía relacionada con este ticket</small>

                {{-- Company Search Results Dropdown --}}
                <div id="company-search-results" class="list-group" style="display: none; max-height: 300px; overflow-y: auto; margin-top: 10px;">
                    {{-- Results will be appended here via jQuery --}}
                </div>
            </div>

            {{-- Row 2: Category (col-md-6) + Priority (col-md-6) --}}
            <div class="row">
                {{-- Category Select --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                        <select id="createCategory" name="category_id" class="form-control select2" style="width: 100%;" disabled required>
                            <option value="">Selecciona una compañía primero</option>
                        </select>
                        <small class="form-text text-muted">Categoría que mejor describe el problema</small>
                    </div>
                </div>

                {{-- Priority Buttons (Horizontal Button Group) --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Prioridad <span class="text-danger">*</span></label>
                        <div class="btn-group d-flex" role="group" id="priority-btn-group">
                            <button type="button" class="btn btn-outline-info btn-priority flex-fill" data-priority="low">
                                <i class="fas fa-angle-down"></i> Baja
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-priority flex-fill" data-priority="medium">
                                <i class="fas fa-minus"></i> Media
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-priority flex-fill" data-priority="high">
                                <i class="fas fa-angle-up"></i> Alta
                            </button>
                        </div>
                        <input type="hidden" id="createPriority" name="priority" required>
                        <small class="form-text text-muted">Indica el nivel de urgencia del ticket</small>
                    </div>
                </div>
            </div>

            {{-- Row 3: Area (Conditional - Only if company has areas enabled) --}}
            <div class="row" id="area-row" style="display: none;">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="createArea">Área</label>
                        <select id="createArea" name="area_id" class="form-control select2" style="width: 100%;" disabled>
                            <option value="">Selecciona un área (opcional)</option>
                        </select>
                        <small class="form-text text-muted">Área o departamento al que pertenece este ticket</small>
                    </div>
                </div>
            </div>

            {{-- Row 4: Title --}}
            <div class="form-group">
                <label for="createTitle">Asunto <span class="text-danger">*</span></label>
                <input type="text" id="createTitle" name="title" class="form-control" placeholder="Asunto:" required minlength="5" maxlength="255">
                <small class="form-text text-muted">Resumen breve del problema (mínimo 5 caracteres)</small>
                <small class="text-muted float-right" id="title-counter">0/255</small>
            </div>

            {{-- Row 5: Description --}}
            <div class="form-group">
                <label for="createDescription">Descripción <span class="text-danger">*</span></label>
                <textarea id="createDescription" name="description" class="form-control" style="height: 300px" placeholder="Escribe aquí los detalles del problema..." required minlength="10" maxlength="5000"></textarea>
                <small class="form-text text-muted">Describe el problema con el mayor detalle posible (mínimo 10 caracteres)</small>
                <small class="text-muted float-right" id="description-counter">0/5000</small>
            </div>

            {{-- Row 6: File Input --}}
            <div class="form-group">
                <label for="createAttachment">Adjuntar Archivos</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="createAttachment" name="attachment" multiple accept=".pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.mp4">
                    <label class="custom-file-label" for="createAttachment">Seleccionar archivos...</label>
                </div>
                <small class="form-text text-muted">Máximo 10MB por archivo. Límite de 5 archivos. Formatos permitidos: PDF, imágenes, documentos Office, videos.</small>

                {{-- File List Container - AdminLTE v3 Official Mailbox Attachments Style --}}
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

{{-- Template for Company Search Result Item --}}
<template id="template-company-item">
    <a href="#" class="list-group-item list-group-item-action company-result-item" data-company-id="">
        <div class="d-flex align-items-center">
            <img src="" alt="Logo" class="company-logo mr-3" style="width: 40px; height: 40px; object-fit: contain; border-radius: 4px;">
            <div class="flex-fill">
                <h6 class="mb-0 company-name"></h6>
                <small class="text-muted company-info"></small>
            </div>
        </div>
    </a>
</template>

{{-- Push CSS to layout head --}}
@push('css')
<style>
/* ========================================
   PRIORITY BUTTONS (Horizontal Button Group)
   ======================================== */
.btn-priority {
    transition: all 0.2s ease;
}

.btn-priority.active {
    /* Cuando está activo, usar colores sólidos según AdminLTE v3 */
}

.btn-priority[data-priority="low"].active {
    background-color: #17a2b8 !important; /* btn-info */
    border-color: #17a2b8 !important;
    color: white !important;
}

.btn-priority[data-priority="medium"].active {
    background-color: #ffc107 !important; /* btn-warning */
    border-color: #ffc107 !important;
    color: #1f2d3d !important; /* Texto oscuro para contraste */
}

.btn-priority[data-priority="high"].active {
    background-color: #dc3545 !important; /* btn-danger */
    border-color: #dc3545 !important;
    color: white !important;
}

/* ========================================
   COMPANY SEARCH RESULTS
   ======================================== */
#company-search-results {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#company-search-results .list-group-item {
    border-left: 0;
    border-right: 0;
}

#company-search-results .list-group-item:first-child {
    border-top: 0;
}

#company-search-results .list-group-item:last-child {
    border-bottom: 0;
}

.company-logo {
    background-color: #f4f4f4;
}

.company-result-item:hover {
    background-color: #f8f9fa;
}

/* ========================================
   GENERAL FORM STYLES
   ======================================== */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

#btn-discard-ticket {
    margin-right: 10px;
}

.mailbox-attachments {
    margin-top: 12px !important;
}

.mailbox-attachment-name {
    display: flex;
    align-items: center;
    gap: 4px;
}

.file-name-truncate {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-all;
    flex: 1;
    min-width: 0;
}

/* Image Thumbnails - AdminLTE v3 Fix */
.mailbox-attachment-icon.has-img {
    width: 200px !important;
    height: 132.5px !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background-color: #f4f4f4;
    padding: 0 !important;
}

.mailbox-attachment-icon.has-img a[data-toggle="lightbox"] {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    height: 100% !important;
    cursor: pointer;
    text-decoration: none;
}

.mailbox-attachment-icon.has-img a[data-toggle="lightbox"]:hover img {
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.mailbox-attachment-icon.has-img > a > img,
.mailbox-attachment-icon.has-img > img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center !important;
    max-width: none !important;
}
</style>
@endpush

<script>
(function() {
    console.log('[Create Ticket] Script cargado - esperando jQuery...');

    function initCreateTicketForm() {
        console.log('[Create Ticket] jQuery disponible - Inicializando formulario');

        // ==============================================================
        // CONFIGURATION & STATE
        // ==============================================================
        const $form = $('#form-create-ticket');
        const $companySearchInput = $('#createCompanySearch');
        const $companySearchResults = $('#company-search-results');
        const $companyHiddenInput = $('#createCompany');
        const $categorySelect = $('#createCategory');
        const $areaSelect = $('#createArea');
        const $areaRow = $('#area-row');
        const $priorityButtons = $('.btn-priority');
        const $priorityHiddenInput = $('#createPriority');
        const $fileInput = $('#createAttachment');
        const $fileList = $('#file-list-container');
        const $submitBtn = $form.find('button[type="submit"]');

        let selectedFiles = [];
        let selectedCompanyId = null;
        let companyHasAreas = false;
        let searchTimeout = null;

        const MAX_FILES = 5;
        const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
        const ALLOWED_EXTENSIONS = ['pdf','txt','log','doc','docx','xls','xlsx','csv','jpg','jpeg','png','gif','bmp','webp','svg','mp4'];

        // ==============================================================
        // COMPANY SEARCH - AdminLTE v3 Official Pattern
        // ==============================================================

        // Focus on search input
        $companySearchInput.on('focus', function() {
            if ($(this).val().length >= 2) {
                performCompanySearch($(this).val());
            } else {
                $companySearchResults.show();
                $companySearchResults.html('<div class="list-group-item text-muted">Escribe al menos 2 caracteres para buscar...</div>');
            }
        });

        // Search on input (debounced)
        $companySearchInput.on('input', function() {
            const query = $(this).val().trim();

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                $companySearchResults.html('<div class="list-group-item text-muted">Escribe al menos 2 caracteres para buscar...</div>');
                $companySearchResults.show();
                return;
            }

            // Debounce search (wait 300ms after user stops typing)
            searchTimeout = setTimeout(function() {
                performCompanySearch(query);
            }, 300);
        });

        // Search button click
        $('#btn-search-company').on('click', function() {
            const query = $companySearchInput.val().trim();
            if (query.length >= 2) {
                performCompanySearch(query);
            } else {
                Swal.fire('Búsqueda vacía', 'Escribe al menos 2 caracteres para buscar', 'info');
            }
        });

        // Perform company search
        async function performCompanySearch(query) {
            console.log(`[Company Search] Buscando: "${query}"`);

            $companySearchResults.html('<div class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>');
            $companySearchResults.show();

            try {
                const response = await fetch(`/api/companies/minimal?search=${encodeURIComponent(query)}&per_page=20`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al buscar compañías');
                }

                console.log(`[Company Search] Resultados encontrados: ${result.data.length}`);

                if (result.data.length === 0) {
                    $companySearchResults.html('<div class="list-group-item text-muted">No se encontraron resultados</div>');
                    return;
                }

                // Render results
                $companySearchResults.empty();
                result.data.forEach(company => {
                    renderCompanyItem(company);
                });

            } catch (error) {
                console.error('[Company Search] Error:', error);
                $companySearchResults.html(`<div class="list-group-item text-danger"><i class="fas fa-exclamation-triangle"></i> ${error.message}</div>`);
            }
        }

        // Render company result item
        function renderCompanyItem(company) {
            const template = document.getElementById('template-company-item').content.cloneNode(true);
            const $item = $(template.querySelector('.company-result-item'));

            // Set data
            $item.attr('data-company-id', company.id);
            $item.find('.company-logo').attr('src', company.logoUrl || '/img/default-company.png');
            $item.find('.company-logo').attr('alt', company.name);
            $item.find('.company-name').text(company.name);

            // Build info string (code + industry)
            let infoText = company.companyCode || '';
            if (company.industryName) {
                infoText += (infoText ? ' • ' : '') + company.industryName;
            }
            $item.find('.company-info').text(infoText || 'Sin información adicional');

            // Click handler
            $item.on('click', function(e) {
                e.preventDefault();
                selectCompany(company);
            });

            $companySearchResults.append($item);
        }

        // Select company
        async function selectCompany(company) {
            console.log(`[Company Search] Compañía seleccionada: ${company.name} (${company.id})`);

            selectedCompanyId = company.id;
            $companyHiddenInput.val(company.id);
            $companySearchInput.val(company.name);
            $companySearchResults.hide();

            // Reset dependent fields
            $categorySelect.val(null).trigger('change');
            $categorySelect.prop('disabled', false);
            $areaSelect.val(null).trigger('change');
            $areaSelect.prop('disabled', true);
            $areaRow.hide();

            // 🔴 FIX: Trigger jQuery Validation re-check
            $form.validate().element('#createCompany');
            console.log('[Validation] Re-validando company_id');

            // Load categories for this company
            await loadCategories(company.id);

            // Check if company has areas enabled
            await checkCompanyAreas(company.id);
        }

        // Hide search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#createCompanySearch, #company-search-results, #btn-search-company').length) {
                $companySearchResults.hide();
            }
        });

        // ==============================================================
        // CATEGORIES - Load based on selected company
        // ==============================================================

        async function loadCategories(companyId) {
            console.log(`[Categories] Cargando categorías para company: ${companyId}`);

            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/tickets/categories?company_id=${companyId}&is_active=true&per_page=100`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al cargar categorías');
                }

                console.log(`[Categories] Categorías cargadas: ${result.data.length}`);

                // Clear and populate select
                $categorySelect.empty();
                $categorySelect.append('<option value="">Selecciona una categoría...</option>');

                result.data.forEach(category => {
                    $categorySelect.append(`<option value="${category.id}">${category.name}</option>`);
                });

                // Re-init Select2
                $categorySelect.select2({
                    theme: 'bootstrap4',
                    placeholder: 'Selecciona una categoría...',
                    allowClear: true
                });

            } catch (error) {
                console.error('[Categories] Error:', error);
                Swal.fire('Error', `No se pudieron cargar las categorías: ${error.message}`, 'error');
            }
        }

        // Category change handler - jQuery Validation Fix
        $categorySelect.on('change', function() {
            const categoryId = $(this).val();
            console.log(`[Categories] Categoría seleccionada: ${categoryId || 'ninguna'}`);

            // 🔴 FIX: Trigger jQuery Validation re-check
            $form.validate().element('#createCategory');
            console.log('[Validation] Re-validando category_id');
        });

        // ==============================================================
        // AREAS - Conditional based on company settings
        // ==============================================================

        async function checkCompanyAreas(companyId) {
            console.log(`[Areas] Verificando si company ${companyId} tiene áreas habilitadas...`);

            try {
                const response = await fetch(`/api/companies/${companyId}/settings/areas-enabled`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al verificar áreas');
                }

                companyHasAreas = result.data.areas_enabled === true;
                console.log(`[Areas] Áreas habilitadas: ${companyHasAreas}`);

                if (companyHasAreas) {
                    $areaRow.show();
                    await loadAreas(companyId);
                } else {
                    $areaRow.hide();
                    $areaSelect.val(null).trigger('change');
                }

            } catch (error) {
                console.error('[Areas] Error:', error);
                // No mostrar error al usuario, simplemente ocultar áreas
                $areaRow.hide();
                companyHasAreas = false;
            }
        }

        async function loadAreas(companyId) {
            console.log(`[Areas] Cargando áreas para company: ${companyId}`);

            try {
                const token = window.tokenManager.getAccessToken();
                const response = await fetch(`/api/areas?company_id=${companyId}&is_active=true&per_page=100`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Error al cargar áreas');
                }

                console.log(`[Areas] Áreas cargadas: ${result.data.length}`);

                // Clear and populate select
                $areaSelect.empty();
                $areaSelect.append('<option value="">Selecciona un área (opcional)</option>');

                result.data.forEach(area => {
                    // Show active tickets count if available
                    const ticketCount = area.active_tickets_count > 0 ? ` (${area.active_tickets_count} tickets)` : '';
                    $areaSelect.append(`<option value="${area.id}">${area.name}${ticketCount}</option>`);
                });

                // Re-init Select2
                $areaSelect.select2({
                    theme: 'bootstrap4',
                    placeholder: 'Selecciona un área (opcional)',
                    allowClear: true
                });

                $areaSelect.prop('disabled', false);

            } catch (error) {
                console.error('[Areas] Error:', error);
                Swal.fire('Error', `No se pudieron cargar las áreas: ${error.message}`, 'error');
            }
        }

        // Area change handler - jQuery Validation Fix
        $areaSelect.on('change', function() {
            const areaId = $(this).val();
            console.log(`[Areas] Área seleccionada: ${areaId || 'ninguna'}`);

            // 🔴 FIX: Trigger jQuery Validation re-check (only if visible)
            if ($areaRow.is(':visible')) {
                $form.validate().element('#createArea');
                console.log('[Validation] Re-validando area_id');
            }
        });

        // ==============================================================
        // PRIORITY BUTTONS - Horizontal Button Group (AdminLTE v3)
        // ==============================================================

        $priorityButtons.on('click', function() {
            const priority = $(this).data('priority');
            console.log(`[Priority] Prioridad seleccionada: ${priority}`);

            // Remove active from all, add to clicked
            $priorityButtons.removeClass('active');
            $(this).addClass('active');

            // Set hidden input value
            $priorityHiddenInput.val(priority);

            // 🔴 FIX: Trigger jQuery Validation re-check
            $form.validate().element('#createPriority');
            console.log('[Validation] Re-validando priority');
        });

        // ==============================================================
        // FILE HANDLING
        // ==============================================================

        if (typeof bsCustomFileInput !== 'undefined') {
            bsCustomFileInput.init();
            console.log('[Create Ticket] ✓ bs-custom-file-input inicializado');
        } else {
            console.log('[Create Ticket] ⚠ bs-custom-file-input no disponible, usando fallback');
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
            console.log(`[Files] Archivos seleccionados: ${newFiles.length}`);

            $(this).val('');
            $(this).siblings('.custom-file-label').text('Seleccionar archivos...');

            let hasError = false;

            newFiles.forEach(file => {
                console.log(`[Files] Procesando: ${file.name} (${formatBytes(file.size)})`);

                if (selectedFiles.length >= MAX_FILES) {
                    if (!hasError) {
                        console.warn(`[Files] ❌ Límite alcanzado: ${MAX_FILES} archivos`);
                        Swal.fire('Límite alcanzado', 'Máximo 5 archivos permitidos.', 'warning');
                    }
                    hasError = true;
                    return;
                }

                if (file.size > MAX_FILE_SIZE) {
                    console.warn(`[Files] ❌ Archivo muy grande: ${file.name}`);
                    Swal.fire('Archivo muy grande', `El archivo ${file.name} excede los 10MB.`, 'warning');
                    return;
                }

                const ext = file.name.split('.').pop().toLowerCase();
                if (!ALLOWED_EXTENSIONS.includes(ext)) {
                    console.warn(`[Files] ❌ Extensión no permitida: ${ext}`);
                    Swal.fire('Formato no válido', `El archivo ${file.name} no es válido.`, 'warning');
                    return;
                }

                selectedFiles.push(file);
                console.log(`[Files] ✓ Archivo agregado. Total: ${selectedFiles.length}/${MAX_FILES}`);
                renderFileItem(file);
            });
        });

        function renderFileItem(file) {
            const template = document.getElementById('template-file-item').content.cloneNode(true);
            const $item = $(template.querySelector('li'));

            $item.find('.file-name').text(file.name);
            $item.find('.file-size').text(formatBytes(file.size));
            $item.find('.mailbox-attachment-name').attr('title', file.name);

            const ext = file.name.split('.').pop().toLowerCase();
            const $icon = $item.find('.mailbox-attachment-icon i');

            if (['jpg','jpeg','png','gif','bmp','webp','svg'].includes(ext)) {
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
            }

            $item.find('.btn-remove-file').on('click', function(e) {
                e.preventDefault();
                const index = selectedFiles.indexOf(file);
                if (index > -1) {
                    selectedFiles.splice(index, 1);
                    $item.remove();
                    console.log(`[Files] Archivo eliminado: ${file.name}. Total: ${selectedFiles.length}`);
                }
            });

            $fileList.append($item);
            console.log(`[Files] ✓ Archivo renderizado: ${file.name}`);
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
        } else {
            console.log('[Create Ticket] ✓ jQuery Validation Plugin cargado correctamente');
        }

        // jQuery Validation Rules (AdminLTE v3 Official Pattern)
        $form.validate({
            errorElement: 'span',
            errorClass: 'invalid-feedback',
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
                $(element).closest('.form-group').find('.form-text').hide();
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).closest('.form-group').find('.form-text').show();
            },
            rules: {
                company_id: {
                    required: true
                },
                category_id: {
                    required: true
                },
                priority: {
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
                priority: {
                    required: "Debes seleccionar una prioridad"
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
            const originalBtnText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

            try {
                const token = window.tokenManager.getAccessToken();

                // Build ticket data
                const ticketData = {
                    title: $('#createTitle').val(),
                    description: $('#createDescription').val(),
                    company_id: $companyHiddenInput.val(),
                    category_id: $categorySelect.val(),
                    priority: $priorityHiddenInput.val()
                };

                // Add area_id if visible and selected
                if ($areaRow.is(':visible') && $areaSelect.val()) {
                    ticketData.area_id = $areaSelect.val();
                }

                console.log('[Submit] Enviando ticket:', ticketData);

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
                    return;
                }

                const ticketCode = result.data.ticket_code;
                console.log(`[Submit] ✓ Ticket creado: ${ticketCode}`);

                // Upload files if any
                if (selectedFiles.length > 0) {
                    $submitBtn.html('<i class="fas fa-cloud-upload-alt"></i> Subiendo archivos...');

                    for (const file of selectedFiles) {
                        const formData = new FormData();
                        formData.append('file', file);

                        try {
                            await fetch(`/api/tickets/${ticketCode}/attachments`, {
                                method: 'POST',
                                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
                                body: formData
                            });
                            console.log(`[Submit] ✓ Archivo subido: ${file.name}`);
                        } catch (uploadError) {
                            console.error(`[Submit] ❌ Error subiendo ${file.name}`, uploadError);
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
                    resetForm();
                    $(document).trigger('tickets:created');
                });

            } catch (error) {
                console.error('[Submit] Error:', error);
                Swal.fire('Error', error.message || 'Ocurrió un error inesperado', 'error');
            } finally {
                $submitBtn.prop('disabled', false).html(originalBtnText);
            }
        }

        // Reset Form Function
        function resetForm() {
            console.log('[Create Ticket] Reseteando formulario...');

            $form[0].reset();
            $companySearchInput.val('');
            $companyHiddenInput.val('');
            $companySearchResults.hide();
            $categorySelect.val(null).trigger('change');
            $categorySelect.prop('disabled', true);
            $areaSelect.val(null).trigger('change');
            $areaSelect.prop('disabled', true);
            $areaRow.hide();
            $priorityButtons.removeClass('active');
            $priorityHiddenInput.val('');
            selectedFiles = [];
            $fileList.empty();
            selectedCompanyId = null;
            companyHasAreas = false;

            $('#title-counter').text('0/255');
            $('#description-counter').text('0/5000');
            $('.custom-file-label').text('Seleccionar archivos...');

            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $form.find('.form-text').show();

            console.log('[Create Ticket] ✓ Formulario reseteado');
        }

        // Discard Button
        $('#btn-discard-ticket').on('click', function() {
            resetForm();
            $(document).trigger('tickets:discarded');
        });

        console.log('[Create Ticket] ✓ Formulario inicializado completamente');

    } // End of initCreateTicketForm

    // Wait for jQuery to be available
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initCreateTicketForm);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                console.log('[Create Ticket] jQuery detectado después de esperar');
                clearInterval(checkJQuery);
                $(document).ready(initCreateTicketForm);
            }
        }, 100);

        setTimeout(function() {
            if (typeof jQuery === 'undefined') {
                clearInterval(checkJQuery);
                console.error('[Create Ticket] ERROR: jQuery no se cargó después de 10 segundos');
            }
        }, 10000);
    }
})();
</script>
