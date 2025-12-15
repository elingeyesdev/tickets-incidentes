<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Crear Nuevo Ticket</h3>
    </div>
    <!-- /.card-header -->

    <form id="form-create-ticket" novalidate>
        <div class="card-body">

            {{-- Row 1: Company Search (Full Width) - AdminLTE v3 Official Search Component --}}
            <div class="form-group">
                <label for="companySearch">Compañía <span class="text-danger">*</span></label>

                {{-- Wrapper with position: relative for absolute positioning --}}
                <div id="company-search-container" style="position: relative;">
                    <div class="input-group">
                        <input type="search" id="companySearch" class="form-control form-control-lg"
                            placeholder="Buscar compañía..." autocomplete="off">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-lg btn-default" id="btnSearchCompany">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Dropdown Results (AdminLTE v3 Style) - Positioned Absolutely --}}
                    <div id="companySearchResults" class="company-search-results" style="display: none;">
                        <div class="list-group">
                            {{-- Results will be dynamically loaded here --}}
                        </div>
                    </div>
                </div>

                {{-- Hidden input for form submission --}}
                <input type="hidden" id="createCompany" name="company_id" required>

                {{-- Selected Company Card (AdminLTE v3 Compact Design - No outline) --}}
                <div id="selectedCompanyCard" class="card card-primary mt-3" style="display: none;">
                    <div class="card-body" style="padding: 1rem;">
                        <div class="d-flex align-items-center">
                            {{-- Company Logo - Left --}}
                            <div class="mr-3 flex-shrink-0">
                                <img id="selectedCompanyLogo" src="" alt="Company Logo"
                                    style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px;">
                            </div>

                            {{-- Company Info - Right --}}
                            <div class="flex-grow-1">
                                <h5 class="mb-1" id="selectedCompanyName" style="font-weight: 600;"></h5>
                                <p class="mb-0 text-muted" style="font-size: 0.875rem;">
                                    <span id="selectedCompanyCode"></span> • <span id="selectedCompanyIndustry"></span>
                                </p>
                            </div>

                            {{-- Clear Button - Right --}}
                            <div class="ml-2 flex-shrink-0">
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btnClearCompany"
                                    title="Cambiar Compañía">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Area UI: reemplazamos el select por un placeholder que usará IA cuando la empresa tenga
                        áreas habilitadas --}}
                        <div id="area-ai-row" class="mt-3" style="display: none;">
                            <label class="mb-1" style="font-size: 0.875rem; font-weight: 600;">Área /
                                Departamento</label>
                            <div id="area-ai-content" class="p-3 border rounded" style="background:#fafafa;">
                                <div id="area-ai-placeholder" class="text-muted"><strong>(N/A)</strong> — Selecciona una
                                    categoría para que <span style="color: #0066cc; font-weight: 600;">HELPDESK
                                        IA</span> seleccione automáticamente el mejor área para tu ticket.</div>
                            </div>
                            <small class="form-text text-muted">Utilizamos IA para sugerir el área más adecuada.</small>
                        </div>

                        {{-- Hidden input que almacenará el area_id sugerido por la IA para el envío del formulario --}}
                        <input type="hidden" id="createAreaHidden" name="area_id">
                    </div>
                </div>

                <small id="companyHelpText" class="form-text text-muted">Busca y selecciona la compañía relacionada con
                    este ticket</small>
            </div>

            {{-- Row 2: Category (col-md-6) + Priority (col-md-6) --}}
            <div class="row">
                {{-- Category Select --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="createCategory">Categoría <span class="text-danger">*</span></label>
                        <select id="createCategory" name="category_id" class="form-control select2" style="width: 100%;"
                            disabled required>
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
                            <button type="button" class="btn btn-outline-success btn-priority flex-fill"
                                data-priority="low">
                                <i class="fas fa-bolt"></i> Baja - Normal
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-priority flex-fill"
                                data-priority="medium">
                                <i class="fas fa-exclamation-triangle"></i> Media - Importante
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-priority flex-fill"
                                data-priority="high">
                                <i class="fas fa-exclamation-circle"></i> Alta - Crítico
                            </button>
                        </div>
                        <input type="hidden" id="createPriority" name="priority" required>
                        <small class="form-text text-muted">Indica el nivel de urgencia del ticket</small>
                    </div>
                </div>
            </div>

            {{-- Row 3: Area moved inside company card - This div is now hidden/removed --}}

            {{-- Row 4: Title --}}
            <div class="form-group">
                <label for="createTitle">Asunto <span class="text-danger">*</span></label>
                <input type="text" id="createTitle" name="title" class="form-control" placeholder="Asunto:" required
                    minlength="5" maxlength="255">
                <small class="form-text text-muted">Resumen breve del problema (mínimo 5 caracteres)</small>
                <small class="text-muted float-right" id="title-counter">0/255</small>
            </div>

            {{-- Row 5: Description --}}
            <div class="form-group">
                <label for="createDescription">Descripción <span class="text-danger">*</span></label>
                <textarea id="createDescription" name="description" class="form-control" style="height: 300px"
                    placeholder="Escribe aquí los detalles del problema..." required minlength="10"
                    maxlength="5000"></textarea>
                <small class="form-text text-muted">Describe el problema con el mayor detalle posible (mínimo 10
                    caracteres)</small>
                <small class="text-muted float-right" id="description-counter">0/5000</small>
            </div>

            {{-- Row 6: File Input --}}
            <div class="form-group">
                <label for="createAttachment">Adjuntar Archivos</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="createAttachment" name="attachment" multiple
                        accept=".pdf,.txt,.log,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif,.bmp,.webp,.svg,.mp4">
                    <label class="custom-file-label" for="createAttachment">Seleccionar archivos...</label>
                </div>
                <small class="form-text text-muted">Máximo 10MB por archivo. Límite de 5 archivos. Formatos permitidos:
                    PDF, imágenes, documentos Office, videos.</small>

                {{-- File List Container - AdminLTE v3 Official Mailbox Attachments Style --}}
                <ul class="mailbox-attachments d-flex align-items-stretch clearfix" id="file-list-container">
                    {{-- Files will be appended here via jQuery --}}
                </ul>
            </div>
        </div>
        <!-- /.card-body -->

        <div class="card-footer">
            <div class="float-right">
                <button type="button" class="btn btn-outline-dark" id="btn-discard-ticket"><i class="fas fa-times"></i>
                    Descartar</button>
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
                <button type="button" class="btn btn-default btn-sm float-right btn-remove-file"><i
                        class="fas fa-times"></i></button>
            </span>
        </div>
    </li>
</template>

{{-- Push CSS to layout head --}}
@push('css')
    <style>
        /* ========================================
       PRIORITY BUTTONS (Horizontal Button Group)
       ======================================== */
        .btn-priority {
            transition: all 0.2s ease;
            color: #000 !important;
            font-weight: 500;
        }

        .btn-priority i {
            margin-right: 4px;
        }

        .btn-priority.active {
            color: #000 !important;
        }

        /* Baja - Verde Acaramelado */
        .btn-priority[data-priority="low"] {
            border-color: #7cb342 !important;
            color: #000 !important;
        }

        .btn-priority[data-priority="low"] i {
            color: #7cb342 !important;
        }

        .btn-priority[data-priority="low"]:hover:not(.active) {
            background-color: rgba(124, 179, 66, 0.08) !important;
        }

        .btn-priority[data-priority="low"].active {
            background-color: #7cb342 !important;
            border-color: #7cb342 !important;
            color: #000 !important;
        }

        .btn-priority[data-priority="low"].active i {
            color: #fff !important;
        }

        /* Media - Amarillo AdminLTE */
        .btn-priority[data-priority="medium"] {
            border-color: #ffc107 !important;
            color: #000 !important;
        }

        .btn-priority[data-priority="medium"] i {
            color: #ffc107 !important;
        }

        .btn-priority[data-priority="medium"]:hover:not(.active) {
            background-color: rgba(255, 193, 7, 0.08) !important;
        }

        .btn-priority[data-priority="medium"].active {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #000 !important;
        }

        .btn-priority[data-priority="medium"].active i {
            color: #fff !important;
        }

        /* Alta - Rojo Acaramelado */
        .btn-priority[data-priority="high"] {
            border-color: #ef5350 !important;
            color: #000 !important;
        }

        .btn-priority[data-priority="high"] i {
            color: #ef5350 !important;
        }

        .btn-priority[data-priority="high"]:hover:not(.active) {
            background-color: rgba(239, 83, 80, 0.08) !important;
        }

        .btn-priority[data-priority="high"].active {
            background-color: #ef5350 !important;
            border-color: #ef5350 !important;
            color: #fff !important;
        }

        .btn-priority[data-priority="high"].active i {
            color: #fff !important;
        }

        /* ========================================
       COMPANY SEARCH DROPDOWN (AdminLTE v3 Official Style)
       ======================================== */

        /* Search Results Dropdown - Positioned absolutely below search input */
        .company-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 56px;
            /* Exclude button width (btn-lg is ~56px) */
            z-index: 1050;
            margin-top: 2px;
            max-height: 400px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* List Group Items (Company Results) */
        .company-search-results .list-group-item {
            cursor: pointer;
            border-left: 0;
            border-right: 0;
            border-top: 0;
            border-bottom: 1px solid rgba(0, 0, 0, .125);
            padding: 0.875rem 1rem;
            transition: all 0.2s ease;
        }

        .company-search-results .list-group-item:first-child {
            border-top: 0;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
        }

        .company-search-results .list-group-item:last-child {
            border-bottom: 0;
            border-bottom-left-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }

        /* Hover Effect - Marca todo el item */
        .company-search-results .list-group-item:hover {
            background-color: #f4f6f9;
            border-color: rgba(0, 0, 0, .125);
        }

        /* Company Item Layout */
        .company-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* Company Logo - Sin fondo gris, tamaño aumentado */
        .company-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .company-details {
            flex: 1;
            min-width: 0;
        }

        /* Company Name - Tamaño aumentado */
        .company-name {
            font-weight: 600;
            color: #212529;
            margin: 0 0 2px 0;
            line-height: 1.3;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Company Code - A la derecha del nombre */
        .company-code {
            font-weight: 400;
            color: #6c757d;
            font-size: 0.813rem;
        }

        /* Company Industry - Debajo del nombre */
        .company-info {
            font-size: 0.813rem;
            color: #6c757d;
            margin: 0;
            line-height: 1.2;
        }

        /* Empty State */
        .company-search-results .text-muted {
            padding: 1rem;
            text-align: center;
        }

        /* Loading State */
        .company-search-results .loading {
            padding: 1rem;
            text-align: center;
            color: #6c757d;
        }

        /* Selected Company Card Styling (Compact Design) */
        /* No additional styles needed - using Bootstrap flex utilities */

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
            max-width: 100%;
            overflow: hidden;
        }

        .file-name-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
            display: inline-block;
        }

        /* Ensure mailbox-attachment-info doesn't overflow */
        .mailbox-attachment-info {
            max-width: 200px;
            overflow: hidden;
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

        .mailbox-attachment-icon.has-img>a>img,
        .mailbox-attachment-icon.has-img>img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center !important;
            max-width: none !important;
        }

        /* ========================================
       SELECT2 CUSTOMIZATION
       ======================================== */
        /* Fix for strong blue background on hover making small text unreadable */
        .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
            background-color: #e8f0fe !important;
            /* Very light blue */
            color: #333 !important;
        }

        .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] .text-muted {
            color: #6c757d !important;
            /* Keep description gray */
        }
    </style>
@endpush

<script>
    (function () {
        console.log('[Create Ticket] Script cargado - esperando jQuery...');

        function initCreateTicketForm() {
            console.log('[Create Ticket] jQuery disponible - Inicializando formulario');

            // ==============================================================
            // CONFIGURATION & STATE
            // ==============================================================
            const $form = $('#form-create-ticket');
            const $companySelect = $('#createCompany');
            const $categorySelect = $('#createCategory');
            const $areaSelect = $('#createArea');
            const $areaRow = $('#area-row-inside-card'); // Changed to new location inside card
            const $priorityButtons = $('.btn-priority');
            const $priorityHiddenInput = $('#createPriority');
            const $fileInput = $('#createAttachment');
            const $fileList = $('#file-list-container');
            const $submitBtn = $form.find('button[type="submit"]');

            let selectedFiles = [];
            let selectedCompanyId = null;
            let companyHasAreas = false;

            const MAX_FILES = 5;
            const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
            const ALLOWED_EXTENSIONS = ['pdf', 'txt', 'log', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'mp4'];

            // ==============================================================
            // COMPANY SEARCH - AdminLTE v3 Official Search Component
            // ==============================================================

            const $companySearchInput = $('#companySearch');
            const $companySearchBtn = $('#btnSearchCompany');
            const $companySearchResults = $('#companySearchResults');
            const $companyHiddenInput = $('#createCompany'); // Hidden input for form submission
            const $selectedCompanyCard = $('#selectedCompanyCard');
            const $btnClearCompany = $('#btnClearCompany');

            let searchTimeout = null;
            let currentSearchQuery = '';
            let loadedCompanies = [];

            // Load companies instantly when clicking search input or button
            function loadCompanies(searchQuery = '') {
                console.log(`[Company Search] Cargando compañías con búsqueda: "${searchQuery}"`);
                currentSearchQuery = searchQuery;

                // Show loading state
                $companySearchResults.html(`
                <div class="list-group-item loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando compañías...
                </div>
            `).show();

                // Fetch companies from API
                fetch(`/api/companies/minimal?search=${encodeURIComponent(searchQuery)}&per_page=50`)
                    .then(response => response.json())
                    .then(data => {
                        loadedCompanies = data.data;
                        console.log(`[Company Search] Compañías cargadas: ${loadedCompanies.length}`);

                        if (loadedCompanies.length === 0) {
                            $companySearchResults.html(`
                            <div class="list-group-item text-muted">
                                <i class="fas fa-info-circle"></i> No se encontraron compañías
                            </div>
                        `);
                        } else {
                            renderCompanies(loadedCompanies);
                        }
                    })
                    .catch(error => {
                        console.error('[Company Search] Error:', error);
                        $companySearchResults.html(`
                        <div class="list-group-item text-danger">
                            <i class="fas fa-exclamation-triangle"></i> Error al cargar compañías
                        </div>
                    `);
                    });
            }

            // Render companies in dropdown
            function renderCompanies(companies) {
                const $listGroup = $('<div class="list-group"></div>');

                companies.forEach(company => {
                    const $item = $(`
                    <a href="#" class="list-group-item list-group-item-action" data-company-id="${company.id}">
                        <div class="company-item">
                            <img class="company-logo" src="${company.logoUrl || '/img/default-company.png'}" alt="${company.name}">
                            <div class="company-details">
                                <div class="company-name">
                                    ${company.name}
                                    <span class="company-code">${company.companyCode || ''}</span>
                                </div>
                                <div class="company-info">${company.industryName || 'Sin categoría'}</div>
                            </div>
                        </div>
                    </a>
                `);

                    $item.on('click', function (e) {
                        e.preventDefault();
                        selectCompany(company);
                    });

                    $listGroup.append($item);
                });

                $companySearchResults.html($listGroup);
            }

            // Helper to build company info string
            function buildCompanyInfo(company) {
                let info = company.companyCode || '';
                if (company.industryName) {
                    info += (info ? ' • ' : '') + company.industryName;
                }
                return info || 'Sin información adicional';
            }

            // Select a company and display card
            function selectCompany(company) {
                console.log(`[Company Search] Compañía seleccionada: ${company.name} (ID: ${company.id})`);

                selectedCompanyId = company.id;

                // Update hidden input
                $companyHiddenInput.val(company.id);

                // Hide search results
                $companySearchResults.hide();

                // Clear search input
                $companySearchInput.val('');

                // Display selected company card
                $('#selectedCompanyLogo').attr('src', company.logoUrl || '/img/default-company.png');
                $('#selectedCompanyName').text(company.name);
                $('#selectedCompanyCode').text(company.companyCode || 'N/A');
                $('#selectedCompanyIndustry').text(company.industryName || 'N/A');
                $selectedCompanyCard.slideDown(300);

                // Hide help text when company is selected
                $('#companyHelpText').hide();

                // Remove any validation error styling from search input
                $companySearchInput.removeClass('is-invalid');

                // 🔴 FIX: Trigger jQuery Validation re-check
                $form.validate().element('#createCompany');
                console.log('[Validation] Re-validando company_id');

                // Reset dependent fields
                $categorySelect.val(null).trigger('change');
                $categorySelect.prop('disabled', true);
                $areaSelect.val(null).trigger('change');
                $areaSelect.prop('disabled', true);
                $areaRow.hide();

                // Load categories for this company
                loadCategories(company.id);

                // Check if company has areas enabled
                checkCompanyAreas(company.id);
            }

            // Clear selected company
            $btnClearCompany.on('click', function () {
                console.log('[Company Search] Limpiando selección de compañía');

                selectedCompanyId = null;
                $companyHiddenInput.val('');
                $selectedCompanyCard.slideUp(300);
                $companySearchInput.val('').focus();

                // Show help text again
                $('#companyHelpText').show();

                // Reset dependent fields
                $categorySelect.val(null).trigger('change');
                $categorySelect.prop('disabled', true);
                $areaSelect.val(null).trigger('change');
                $areaSelect.prop('disabled', true);
                $areaRow.hide();
            });

            // Event: Click on search input - Load companies instantly
            $companySearchInput.on('focus click', function () {
                if (!selectedCompanyId) {
                    loadCompanies('');
                }
            });

            // Event: Click on search button
            $companySearchBtn.on('click', function () {
                const query = $companySearchInput.val().trim();
                loadCompanies(query);
            });

            // Event: Type in search input (debounced)
            $companySearchInput.on('input', function () {
                const query = $(this).val().trim();

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadCompanies(query);
                }, 300);
            });

            // Event: Press Enter in search input
            $companySearchInput.on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const query = $(this).val().trim();
                    loadCompanies(query);
                }
            });

            // Event: Click outside to close dropdown
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#company-search-container, #companySearchResults').length) {
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
                        // Store description in data attribute for Select2 template
                        // Escape quotes in description to avoid HTML issues
                        const description = (category.description || '').replace(/"/g, '&quot;');
                        $categorySelect.append(`<option value="${category.id}" data-description="${description}">${category.name}</option>`);
                    });

                    // Custom formatting function for Select2 results
                    function formatCategory(state) {
                        if (!state.id) {
                            return state.text;
                        }

                        const description = $(state.element).data('description');

                        const $state = $(
                            '<div class="d-flex flex-column">' +
                            '<span class="font-weight-bold">' + state.text + '</span>' +
                            '<span class="small text-muted" style="line-height: 1.2; margin-top: 2px;">' + (description || '') + '</span>' +
                            '</div>'
                        );

                        return $state;
                    }

                    // Re-init Select2 with custom template
                    $categorySelect.select2({
                        theme: 'bootstrap4',
                        placeholder: 'Selecciona una categoría...',
                        allowClear: true,
                        templateResult: formatCategory,
                        // Keep selection simple (just the name)
                        templateSelection: function (state) {
                            return state.text;
                        }
                    });

                    $categorySelect.prop('disabled', false);

                } catch (error) {
                    console.error('[Categories] Error:', error);
                    Swal.fire('Error', `No se pudieron cargar las categorías: ${error.message}`, 'error');
                }
            }

            // Category change handler
            $categorySelect.on('change', function () {
                const categoryId = $(this).val();
                console.log(`[Categories] Categoría seleccionada: ${categoryId || 'ninguna'}`);

                // If a category is selected, validate it to remove any "required" error message.
                // This prevents showing an error when the field is cleared programmatically.
                if (categoryId) {
                    $form.validate().element('#createCategory');
                }
                // If company has areas enabled, use IA to predict area when a category is selected
                if (companyHasAreas && categoryId) {
                    const $opt = $categorySelect.find(`option[value="${categoryId}"]`);
                    const categoryName = $opt.text();
                    const categoryDescription = $opt.data('description') || '';
                    predictAreaForCategory(selectedCompanyId, categoryName, categoryDescription);
                }
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
                        // Usaremos IA para determinar el área automáticamente.
                        // Ocultamos el select tradicional y mostramos el placeholder IA.
                        $areaRow.hide();
                        $('#area-ai-row').show();
                        // Inicial placeholder
                        $('#area-ai-content').html('<div id="area-ai-placeholder" class="text-muted">(N/A) — Selecciona una categoría para que HELPDESK IA seleccione automáticamente el mejor área para tu ticket.</div>');
                        // Do not call loadAreas() to avoid rendering the select2
                    } else {
                        $areaRow.hide();
                        $('#area-ai-row').hide();
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
                        // Store description in data attribute
                        const description = (area.description || '').replace(/"/g, '&quot;');
                        $areaSelect.append(`<option value="${area.id}" data-description="${description}">${area.name}${ticketCount}</option>`);
                    });

                    // Custom formatting function for Select2 results
                    function formatArea(state) {
                        if (!state.id) {
                            return state.text;
                        }

                        const description = $(state.element).data('description');

                        const $state = $(
                            '<div class="d-flex flex-column">' +
                            '<span class="font-weight-bold">' + state.text + '</span>' +
                            '<span class="small text-muted" style="line-height: 1.2; margin-top: 2px;">' + (description || '') + '</span>' +
                            '</div>'
                        );

                        return $state;
                    }

                    // Re-init Select2
                    $areaSelect.select2({
                        theme: 'bootstrap4',
                        placeholder: 'Selecciona un área (opcional)',
                        allowClear: true,
                        templateResult: formatArea,
                        templateSelection: function (state) {
                            return state.text;
                        }
                    });

                    $areaSelect.prop('disabled', false);
                    // Ensure AI hidden input is cleared when manual areas are available
                    $('#createAreaHidden').val('');

                } catch (error) {
                    console.error('[Areas] Error:', error);
                    Swal.fire('Error', `No se pudieron cargar las áreas: ${error.message}`, 'error');
                }
            }

            // Area change handler
            $areaSelect.on('change', function () {
                const areaId = $(this).val();
                console.log(`[Areas] Área seleccionada: ${areaId || 'ninguna'}`);

                // Hide help text on selection, show it when cleared.
                const $helpText = $(this).closest('#area-row-inside-card').find('.form-text');
                if (areaId) {
                    $helpText.hide();
                } else {
                    $helpText.show();
                }
            });

            // ==============================================================
            // PRIORITY BUTTONS - Horizontal Button Group (AdminLTE v3)
            // ==============================================================

            $priorityButtons.on('click', function () {
                const $clickedBtn = $(this);
                const priority = $clickedBtn.data('priority');
                const isActive = $clickedBtn.hasClass('active');

                if (isActive) {
                    // Deselect: remove active class and clear value
                    $clickedBtn.removeClass('active');
                    $priorityHiddenInput.val('');
                    console.log('[Priority] Prioridad deseleccionada');
                } else {
                    // Select: remove active from all, add to clicked
                    $priorityButtons.removeClass('active');
                    $clickedBtn.addClass('active');
                    $priorityHiddenInput.val(priority);
                    console.log(`[Priority] Prioridad seleccionada: ${priority}`);
                }
            });

            // ==============================================================
            // FILE HANDLING
            // ==============================================================

            if (typeof bsCustomFileInput !== 'undefined') {
                bsCustomFileInput.init();
                console.log('[Create Ticket] ✓ bs-custom-file-input inicializado');
            } else {
                console.log('[Create Ticket] ⚠ bs-custom-file-input no disponible, usando fallback');
                $fileInput.on('change', function () {
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

            $fileInput.on('change', function (e) {
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

                if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
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
                } else if (['doc', 'docx'].includes(ext)) {
                    $icon.removeClass('fa-file').addClass('fa-file-word');
                } else if (['xls', 'xlsx', 'csv'].includes(ext)) {
                    $icon.removeClass('fa-file').addClass('fa-file-excel');
                } else if (ext === 'txt') {
                    $icon.removeClass('fa-file').addClass('fa-file-alt');
                } else if (ext === 'mp4') {
                    $icon.removeClass('fa-file').addClass('fa-file-video');
                }

                $item.find('.btn-remove-file').on('click', function (e) {
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
            $('#createTitle').on('input', function () {
                $('#title-counter').text(`${$(this).val().length}/255`);
            });
            $('#createDescription').on('input', function () {
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
                ignore: [], // CRITICAL: Validate hidden inputs (priority field)
                errorElement: 'span',
                errorClass: 'invalid-feedback',
                errorPlacement: function (error, element) {
                    // For hidden inputs, append error to its form-group
                    if (element.attr('type') === 'hidden') {
                        error.addClass('invalid-feedback');
                        element.closest('.form-group').append(error);
                    } else {
                        error.addClass('invalid-feedback');
                        element.closest('.form-group').append(error);
                    }
                },
                highlight: function (element, errorClass, validClass) {
                    $(element).addClass('is-invalid');

                    // Special case: if validating company_id (hidden), mark the search input
                    if ($(element).attr('id') === 'createCompany' && $(element).attr('type') === 'hidden') {
                        $('#companySearch').addClass('is-invalid');
                    }

                    $(element).closest('.form-group').find('.form-text').hide();
                },
                unhighlight: function (element, errorClass, validClass) {
                    $(element).removeClass('is-invalid');

                    // Special case: if validating company_id (hidden), unmark the search input
                    if ($(element).attr('id') === 'createCompany' && $(element).attr('type') === 'hidden') {
                        $('#companySearch').removeClass('is-invalid');
                    }

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
                submitHandler: function (form) {
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
                        company_id: $companySelect.val(),
                        category_id: $categorySelect.val(),
                        priority: $priorityHiddenInput.val()
                    };

                    // Add area_id if visible and selected
                    // If we have an AI-predicted area, prefer it
                    const aiArea = $('#createAreaHidden').val();
                    if (aiArea) {
                        ticketData.area_id = aiArea;
                    } else if ($areaRow.is(':visible') && $areaSelect.val()) {
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
                            $.each(result.errors, function (key, msgs) {
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

            // ==============================================================
            // AI Prediction for Area
            // ==============================================================

            async function predictAreaForCategory(companyId, categoryName, categoryDescription) {
                console.log(`[AI] Solicitando predicción de área para company=${companyId} category="${categoryName}"`);

                // Show loading UI
                $('#area-ai-content').html(`<div class="d-flex align-items-center">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                <div>
                    <div style="font-weight:600">Utilizando HELPDESK IA...</div>
                    <div class="small text-muted">Estamos buscando la mejor área para tu ticket. Esto puede tardar unos segundos.</div>
                </div>
            </div>`);

                try {
                    const token = window.tokenManager.getAccessToken();
                    const payload = {
                        company_id: companyId,
                        category_name: categoryName,
                        category_description: categoryDescription
                    };

                    const resp = await fetch(`/api/tickets/predict-area`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const body = await resp.json();

                    if (!resp.ok) {
                        console.warn('[AI] Respuesta no OK', body);
                        $('#area-ai-content').html(`<div class="text-danger">No se pudo determinar automáticamente el área. Intenta nuevamente o selecciona manualmente.</div>`);
                        $('#createAreaHidden').val('');
                        return;
                    }

                    if (body.success && body.data && body.data.predicted_area_id) {
                        const areaId = body.data.predicted_area_id;
                        const areaName = body.data.area_name || 'Área sugerida';
                        const areaDesc = body.data.area_description || '';
                        const confidence = body.data.confidence || '';

                        $('#area-ai-content').html(`
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-weight:600">${escapeHtml(areaName)}</div>
                                <div class="small text-muted">${escapeHtml(areaDesc)}</div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-info">Sugerido</span>
                                ${confidence ? `<div class="small text-muted">${escapeHtml(confidence)}</div>` : ''}
                            </div>
                        </div>
                    `);

                        // Set hidden input for submission
                        $('#createAreaHidden').val(areaId);
                        console.log(`[AI] Área sugerida: ${areaName} (${areaId})`);
                    } else {
                        console.warn('[AI] No se recibió área válida', body);
                        $('#area-ai-content').html(`<div class="text-warning">No se pudo determinar automáticamente el área. Por favor selecciona manualmente si lo deseas.</div>`);
                        $('#createAreaHidden').val('');
                    }

                } catch (err) {
                    console.error('[AI] Error al predecir área', err);
                    $('#area-ai-content').html(`<div class="text-danger">Error al conectar con HELPDESK IA. Intenta nuevamente más tarde.</div>`);
                    $('#createAreaHidden').val('');
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                return $('<div/>').text(text).html();
            }

            // Reset Form Function
            function resetForm() {
                console.log('[Create Ticket] Reseteando formulario...');

                $form[0].reset();
                $companySelect.val(null).trigger('change');
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
            $('#btn-discard-ticket').on('click', function () {
                resetForm();
                $(document).trigger('tickets:discarded');
            });

            console.log('[Create Ticket] ✓ Formulario inicializado completamente');

        } // End of initCreateTicketForm

        // Wait for jQuery to be available
        if (typeof jQuery !== 'undefined') {
            $(document).ready(initCreateTicketForm);
        } else {
            var checkJQuery = setInterval(function () {
                if (typeof jQuery !== 'undefined') {
                    console.log('[Create Ticket] jQuery detectado después de esperar');
                    clearInterval(checkJQuery);
                    $(document).ready(initCreateTicketForm);
                }
            }, 100);

            setTimeout(function () {
                if (typeof jQuery === 'undefined') {
                    clearInterval(checkJQuery);
                    console.error('[Create Ticket] ERROR: jQuery no se cargó después de 10 segundos');
                }
            }, 10000);
        }
    })();
</script>