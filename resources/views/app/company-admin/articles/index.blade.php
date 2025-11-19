@extends('layouts.authenticated')

@section('title', 'Gestión de Artículos')

@section('content_header', 'Gestión de Artículos del Centro de Ayuda')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Artículos</li>
@endsection

@section('css')
<style>
    /* Markdown content styling */
    #view-content h1,
    #view-content h2,
    #view-content h3,
    #view-content h4,
    #view-content h5,
    #view-content h6 {
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    #view-content h1 { font-size: 1.75rem; }
    #view-content h2 { font-size: 1.5rem; }
    #view-content h3 { font-size: 1.25rem; }
    #view-content h4 { font-size: 1.1rem; }

    #view-content p {
        margin-bottom: 0.50rem;
        line-height: 1;
    }

    #view-content ul,
    #view-content ol {
        margin-bottom: 0.75rem;
        padding-left: 1.5rem;
    }

    #view-content li {
        margin-bottom: 0.25rem;
    }

    #view-content pre {
        background-color: #f4f4f4;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        overflow-x: auto;
    }

    #view-content code {
        background-color: #f4f4f4;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        font-family: monospace;
        font-size: 0.9em;
    }

    #view-content pre code {
        background-color: transparent;
        padding: 0;
    }

    #view-content blockquote {
        border-left: 4px solid #ddd;
        padding-left: 1rem;
        margin-left: 0;
        margin-bottom: 0.75rem;
        color: #666;
    }

    #view-content hr {
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    #view-content a {
        color: #007bff;
        text-decoration: none;
    }

    #view-content a:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')

{{-- Statistics Small Boxes --}}
<div class="row">
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="0" text="Total Artículos" icon="fas fa-book" theme="info" id="stat-total"/>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="0" text="Publicados" icon="fas fa-check-circle" theme="success" id="stat-published"/>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="0" text="Borradores" icon="fas fa-pencil-alt" theme="secondary" id="stat-draft"/>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-12">
        <x-adminlte-small-box title="0" text="Vistas Totales" icon="fas fa-eye" theme="warning" id="stat-views"/>
    </div>
</div>

{{-- Articles Table Card --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book"></i> Artículos del Centro de Ayuda</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" id="btn-create-article">
                <i class="fas fa-plus"></i> Crear Artículo
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="maximize">
                <i class="fas fa-expand"></i>
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Sección de Filtros --}}
        <div class="p-3 border-bottom bg-light">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Buscar</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="search-articles" placeholder="Título o contenido...">
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Categoría</label>
                        <select class="form-control form-control-sm" id="filter-category">
                            <option value="">Todas</option>
                            <option value="ACCOUNT_PROFILE">Cuenta y Perfil</option>
                            <option value="SECURITY_PRIVACY">Seguridad y Privacidad</option>
                            <option value="BILLING_PAYMENTS">Facturación y Pagos</option>
                            <option value="TECHNICAL_SUPPORT">Soporte Técnico</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Estado</label>
                        <select class="form-control form-control-sm" id="filter-status">
                            <option value="">Todos</option>
                            <option value="draft">Borrador</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="text-sm mb-1">Ordenar por</label>
                        <select class="form-control form-control-sm" id="filter-sort">
                            <option value="-created_at">Más recientes</option>
                            <option value="created_at">Más antiguos</option>
                            <option value="title">Título (A-Z)</option>
                            <option value="-title">Título (Z-A)</option>
                            <option value="-views">Más visitados</option>
                            <option value="views">Menos visitados</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="text-sm mb-1 d-block">&nbsp;</label>
                    <button type="button" class="btn btn-default btn-sm" id="btn-clear-filters">
                        <i class="fas fa-eraser"></i> Limpiar Filtros
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-refresh">
                        <i class="fas fa-sync-alt"></i> Refrescar
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
        <table id="articles-table" class="table table-bordered table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 35%;">Título</th>
                    <th style="width: 15%;">Categoría</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 8%;">Vistas</th>
                    <th style="width: 12%;">Publicado</th>
                    <th style="width: 20%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Cargando artículos...
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    {{-- Footer: Paginación --}}
    <div class="card-footer border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" id="pagination-info">Mostrando 0 de 0</small>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>
</div>

{{-- Modal: View Article --}}
<x-adminlte-modal id="modal-view" title="Ver Artículo" theme="warning" icon="fas fa-eye" size="xl" scrollable>
    <div class="mb-3">
        <h3 id="view-title" class="mb-3"></h3>
        <div class="mb-3">
            <span id="view-category-badge-container"></span>
            <span id="view-status-badge-container" class="ml-2"></span>
            <span class="badge badge-light ml-2">
                <i class="fas fa-eye"></i> <span id="view-views">0</span> vistas
            </span>
        </div>
    </div>

    <hr>

    <div class="mb-3">
        <h5><i class="fas fa-align-left"></i> Resumen:</h5>
        <p id="view-excerpt" class="text-muted pl-3" style="word-wrap: break-word; white-space: pre-wrap;"></p>
    </div>

    <hr>

    <div class="mb-3">
        <h5><i class="fas fa-file-alt"></i> Contenido:</h5>
        <div id="view-content" class="border rounded p-3 bg-light"
             style="word-wrap: break-word; white-space: pre-wrap; overflow-wrap: break-word;"></div>
    </div>

    <hr>

    <div class="row">
        <div class="col-md-6">
            <small class="text-muted">
                <i class="fas fa-calendar-plus"></i> Creado: <strong id="view-created"></strong>
            </small>
        </div>
        <div class="col-md-6 text-right">
            <small class="text-muted">
                <i class="fas fa-calendar-check"></i> Publicado: <strong id="view-published"></strong>
            </small>
        </div>
    </div>

    <x-slot name="footerSlot">
        <x-adminlte-button label="Cerrar" icon="fas fa-times" theme="secondary" data-dismiss="modal"/>
        <x-adminlte-button label="Editar" icon="fas fa-edit" theme="primary" id="btn-modal-edit"/>
    </x-slot>
</x-adminlte-modal>

{{-- Modal: Create/Edit Article --}}
<x-adminlte-modal id="modal-form" title="Nuevo Artículo" theme="primary" icon="fas fa-plus-circle" size="xl" scrollable>
    <form id="article-form">
        <x-adminlte-select id="form-category" name="category_id" label="Categoría" label-class="text-danger"
                           enable-old-support disabled>
            <option value="">Cargando categorías...</option>
        </x-adminlte-select>

        <x-adminlte-input id="form-article-title" name="title" label="Título" placeholder="Título del artículo"
                          enable-old-support>
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-heading text-primary"></i>
                </div>
            </x-slot>
            <x-slot name="bottomSlot">
                <small class="text-muted">Mínimo 3 caracteres, máximo 255</small>
            </x-slot>
        </x-adminlte-input>

        <x-adminlte-textarea id="form-excerpt" name="excerpt" label="Resumen (Opcional)" rows="2"
                             placeholder="Breve descripción del artículo..." enable-old-support>
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-align-left text-info"></i>
                </div>
            </x-slot>
            <x-slot name="bottomSlot">
                <small class="text-muted">Máximo 500 caracteres</small>
            </x-slot>
        </x-adminlte-textarea>

        <x-adminlte-textarea id="form-content" name="content" label="Contenido" rows="10"
                             placeholder="Escribe el contenido completo del artículo (soporta Markdown)..."
                             enable-old-support>
            <x-slot name="prependSlot">
                <div class="input-group-text">
                    <i class="fas fa-file-alt text-success"></i>
                </div>
            </x-slot>
            <x-slot name="bottomSlot">
                <small class="text-muted">
                    <i class="fab fa-markdown"></i> Soporta formato Markdown. Mínimo 50 caracteres.
                </small>
            </x-slot>
        </x-adminlte-textarea>

        <input type="hidden" id="form-article-id">
    </form>

    <x-slot name="footerSlot">
        <x-adminlte-button label="Cancelar" icon="fas fa-times" theme="secondary" data-dismiss="modal"/>
        <x-adminlte-button label="Guardar" icon="fas fa-save" theme="primary" id="btn-save-article"/>
    </x-slot>
</x-adminlte-modal>

{{-- Modal: Delete Article --}}
<x-adminlte-modal id="modal-delete" title="Eliminar Artículo" theme="danger" icon="fas fa-trash">
    <x-adminlte-callout theme="warning" icon="fas fa-exclamation-triangle">
        <strong>¿Deseas eliminar el artículo "<span id="delete-article-title">-</span>"?</strong>
    </x-adminlte-callout>

    <p class="text-muted">
        <i class="fas fa-info-circle"></i> Solo se pueden eliminar artículos en estado BORRADOR.
    </p>
    <p class="text-danger">
        <i class="fas fa-exclamation-circle"></i> <strong>Esta acción no se puede deshacer.</strong>
    </p>

    <input type="hidden" id="delete-article-id">

    <x-slot name="footerSlot">
        <x-adminlte-button label="Cancelar" icon="fas fa-times" theme="secondary" data-dismiss="modal"/>
        <x-adminlte-button label="Eliminar" icon="fas fa-trash" theme="danger" id="btn-confirm-delete"/>
    </x-slot>
</x-adminlte-modal>

@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // =====================================================================
        // CONFIGURATION & STATE
        // =====================================================================

        const token = window.tokenManager?.getAccessToken();
        const apiUrl = '/api';

        let allArticles = [];
        let currentArticle = null;
        let currentMode = 'view';
        let categories = [];
        let categoriesLoaded = false;
        let currentPage = 1;
        let totalPages = 1;

        // =====================================================================
        // UTILITY: Format Status Badge
        // =====================================================================

        function getStatusBadge(status) {
            const badges = {
                'DRAFT': '<span class="badge badge-secondary"><i class="fas fa-pencil-alt"></i> Borrador</span>',
                'PUBLISHED': '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Publicado</span>'
            };
            return badges[status] || '<span class="badge badge-secondary">Desconocido</span>';
        }

        // =====================================================================
        // UTILITY: Format Category Badge
        // =====================================================================

        function getCategoryBadge(code, name) {
            const categoryStyles = {
                'ACCOUNT_PROFILE': {
                    icon: 'fas fa-user-circle',
                    color: 'text-primary'
                },
                'SECURITY_PRIVACY': {
                    icon: 'fas fa-shield-alt',
                    color: 'text-warning'
                },
                'BILLING_PAYMENTS': {
                    icon: 'fas fa-dollar-sign',
                    color: 'text-success'
                },
                'TECHNICAL_SUPPORT': {
                    icon: 'fas fa-tools',
                    color: 'text-info'
                }
            };

            const style = categoryStyles[code] || { icon: 'fas fa-tag', color: 'text-secondary' };
            return `<i class="${style.icon} ${style.color}"></i> <small>${name || code}</small>`;
        }

        // =====================================================================
        // UTILITY: Format Date
        // =====================================================================

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('es-ES', options);
        }

        // =====================================================================
        // LOAD CATEGORIES
        // =====================================================================

        function loadCategories() {
            fetch(`${apiUrl}/help-center/categories`)
                .then(response => response.json())
                .then(data => {
                    if (data.data && Array.isArray(data.data)) {
                        categories = data.data;
                        categoriesLoaded = true;
                        populateCategorySelect();
                    }
                })
                .catch(error => {
                    console.error('Error loading categories:', error);
                });
        }

        function populateCategorySelect() {
            const formSelect = document.getElementById('form-category');

            formSelect.querySelectorAll('option:not(:first-child)').forEach(opt => opt.remove());

            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                formSelect.appendChild(option);
            });

            formSelect.disabled = false;
            const firstOption = formSelect.querySelector('option');
            if (firstOption) {
                firstOption.textContent = 'Seleccionar categoría...';
            }
        }

        // =====================================================================
        // UPDATE STATISTICS
        // =====================================================================

        function updateStatistics(total, published, draft, views, hasFilters = false) {
            const suffix = hasFilters ? ' (filtrados)' : '';

            document.querySelector('#stat-total .inner h3').textContent = total + suffix;
            document.querySelector('#stat-published .inner h3').textContent = published + suffix;
            document.querySelector('#stat-draft .inner h3').textContent = draft + suffix;
            document.querySelector('#stat-views .inner h3').textContent = views + suffix;
        }

        // =====================================================================
        // LOAD ARTICLES
        // =====================================================================

        function loadArticles(filters = {}, page = 1) {
            if (!token) {
                showAlert('error', 'No se encontró token de autenticación');
                return;
            }

            const tbody = document.querySelector('#articles-table tbody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando artículos...</td></tr>';

            let url = `${apiUrl}/help-center/articles`;
            const params = new URLSearchParams();

            if (filters.category) params.append('category', filters.category);
            if (filters.status) params.append('status', filters.status);
            if (filters.search) params.append('search', filters.search);
            if (filters.sort) params.append('sort', filters.sort);
            params.append('page', page);
            params.append('per_page', 10);

            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('[Articles] Response status:', response.status);
                console.log('[Articles] Request URL:', url);
                return response.json();
            })
            .then(data => {
                console.log('[Articles] Response data:', data);
                if (data.data && Array.isArray(data.data)) {
                    allArticles = data.data;
                    renderArticlesTable(allArticles);

                    // Update pagination
                    currentPage = data.meta?.current_page || 1;
                    totalPages = data.meta?.last_page || 1;
                    renderPagination(data.meta);
                    updatePaginationInfo(data.meta);

                    // Update statistics
                    const total = data.meta?.total || 0;
                    const publishedCount = allArticles.filter(a => a.status === 'PUBLISHED').length;
                    const draftCount = allArticles.filter(a => a.status === 'DRAFT').length;
                    const totalViews = allArticles.reduce((sum, a) => sum + (a.views_count || 0), 0);

                    const hasFilters = filters.category || filters.status || filters.search;
                    updateStatistics(total, publishedCount, draftCount, totalViews, hasFilters);
                } else {
                    console.error('[Articles] Invalid response format:', data);
                    const errorMsg = data.message || 'Error al cargar artículos';
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> ${errorMsg}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error loading articles:', error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Error de conexión: ' + error.message + '</td></tr>';
            });
        }

        // =====================================================================
        // RENDER ARTICLES TABLE
        // =====================================================================

        function renderArticlesTable(articles) {
            const tbody = document.querySelector('#articles-table tbody');

            if (!articles || articles.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted"><i class="fas fa-inbox"></i> No hay artículos disponibles</td></tr>';
                return;
            }

            tbody.innerHTML = articles.map(article => {
                const statusBadge = getStatusBadge(article.status);
                const categoryName = categories.find(c => c.id === article.category_id)?.name || 'N/A';
                const categoryCode = categories.find(c => c.id === article.category_id)?.code || '';
                const categoryBadge = getCategoryBadge(categoryCode, categoryName);

                return `
                    <tr data-id="${article.id}">
                        <td>
                            <strong>${article.title || 'Sin título'}</strong><br>
                            <small class="text-muted d-block text-truncate" style="max-width: 400px;">${article.excerpt || 'Sin resumen'}</small>
                        </td>
                        <td class="text-nowrap">${categoryBadge}</td>
                        <td>${statusBadge}</td>
                        <td><span class="badge badge-light"><i class="fas fa-eye"></i> ${article.views_count || 0}</span></td>
                        <td><small class="text-muted">${formatDate(article.published_at)}</small></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-warning btn-view" data-id="${article.id}" title="Ver">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-sm btn-info btn-edit" data-id="${article.id}" title="Editar">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            ${article.status === 'DRAFT' ? `
                                <button class="btn btn-sm btn-success btn-publish" data-id="${article.id}" title="Publicar">
                                    <i class="fas fa-paper-plane"></i> Publicar
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete" data-id="${article.id}" title="Eliminar">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            ` : `
                                <button class="btn btn-sm btn-secondary btn-unpublish" data-id="${article.id}" title="Despublicar">
                                    <i class="fas fa-undo"></i> Despublicar
                                </button>
                            `}
                        </td>
                    </tr>
                `;
            }).join('');

            attachActionListeners();
        }

        // =====================================================================
        // RENDER PAGINATION (AdminLTE v3 Style)
        // =====================================================================

        function renderPagination(meta) {
            const pagination = document.getElementById('pagination');

            if (!meta || meta.last_page <= 1) {
                pagination.innerHTML = '';
                return;
            }

            const currentPage = meta.current_page;
            const totalPages = meta.last_page;
            let html = '';

            // Botón Previous
            if (currentPage > 1) {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
                    </li>
                `;
            } else {
                html += `
                    <li class="page-item disabled">
                        <span class="page-link">Previous</span>
                    </li>
                `;
            }

            // Números de página (máximo 5 botones visibles)
            const maxButtons = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxButtons - 1);

            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }

            // Primera página si no está visible
            if (startPage > 1) {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="1">1</a>
                    </li>
                `;
                if (startPage > 2) {
                    html += `
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    `;
                }
            }

            // Páginas visibles
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += `
                        <li class="page-item active">
                            <span class="page-link">${i}</span>
                        </li>
                    `;
                } else {
                    html += `
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }
            }

            // Última página si no está visible
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    `;
                }
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                    </li>
                `;
            }

            // Botón Next
            if (currentPage < totalPages) {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                    </li>
                `;
            } else {
                html += `
                    <li class="page-item disabled">
                        <span class="page-link">Next</span>
                    </li>
                `;
            }

            pagination.innerHTML = html;

            // Attach pagination listeners
            pagination.querySelectorAll('a.page-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.dataset.page);
                    loadArticles(getCurrentFilters(), page);
                });
            });
        }

        // =====================================================================
        // GET CURRENT FILTERS
        // =====================================================================

        function getCurrentFilters() {
            return {
                category: document.getElementById('filter-category').value,
                status: document.getElementById('filter-status').value,
                search: document.getElementById('search-articles').value,
                sort: document.getElementById('filter-sort').value
            };
        }

        // =====================================================================
        // OPEN VIEW MODAL
        // =====================================================================

        function openViewModal(articleId) {
            currentArticle = allArticles.find(a => a.id === articleId);

            if (!currentArticle) {
                showAlert('error', 'No se encontró el artículo');
                return;
            }

            const categoryName = categories.find(c => c.id === currentArticle.category_id)?.name || 'N/A';
            const categoryCode = categories.find(c => c.id === currentArticle.category_id)?.code || '';

            document.getElementById('view-title').textContent = currentArticle.title || 'Sin título';
            document.getElementById('view-category-badge-container').innerHTML = getCategoryBadge(categoryCode, categoryName);
            document.getElementById('view-status-badge-container').innerHTML = getStatusBadge(currentArticle.status);
            document.getElementById('view-views').textContent = currentArticle.views_count || 0;
            document.getElementById('view-excerpt').textContent = currentArticle.excerpt || 'Sin resumen';

            // Render Markdown content using marked.js
            const contentElement = document.getElementById('view-content');
            const rawContent = currentArticle.content || 'Sin contenido';

            // Check if marked is available and render Markdown
            if (typeof marked !== 'undefined') {
                try {
                    contentElement.innerHTML = marked.parse(rawContent);
                } catch (error) {
                    console.error('Error rendering Markdown:', error);
                    contentElement.textContent = rawContent;
                }
            } else {
                // Fallback to plain text if marked is not loaded
                contentElement.textContent = rawContent;
            }

            document.getElementById('view-created').textContent = formatDate(currentArticle.created_at);
            document.getElementById('view-published').textContent = formatDate(currentArticle.published_at);

            $('#modal-view').modal('show');
        }

        // =====================================================================
        // OPEN FORM MODAL
        // =====================================================================

        function openFormModal(mode, articleId = null) {
            currentMode = mode;
            const form = document.getElementById('article-form');
            form.reset();

            const modalTitle = document.querySelector('#modal-form .modal-title');

            if (mode === 'create') {
                modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Nuevo Artículo';
                document.getElementById('form-article-id').value = '';

                if (!categoriesLoaded) {
                    showAlert('warning', 'Cargando categorías...');
                }
            } else if (mode === 'edit') {
                currentArticle = allArticles.find(a => a.id === articleId);
                if (!currentArticle) {
                    showAlert('error', 'No se encontró el artículo');
                    return;
                }

                modalTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Artículo';

                document.getElementById('form-category').value = currentArticle.category_id || '';
                document.getElementById('form-article-title').value = currentArticle.title || '';
                document.getElementById('form-excerpt').value = currentArticle.excerpt || '';
                document.getElementById('form-content').value = currentArticle.content || '';
                document.getElementById('form-article-id').value = currentArticle.id;
            }

            $('#modal-view').modal('hide');
            $('#modal-form').modal('show');
        }

        // =====================================================================
        // SAVE ARTICLE
        // =====================================================================

        function saveArticle() {
            const articleId = document.getElementById('form-article-id').value;
            const isCreate = !articleId;

            const payload = {
                category_id: document.getElementById('form-category').value,
                title: document.getElementById('form-article-title').value,
                excerpt: document.getElementById('form-excerpt').value || null,
                content: document.getElementById('form-content').value
            };

            const method = isCreate ? 'POST' : 'PUT';
            const url = isCreate ? `${apiUrl}/help-center/articles` : `${apiUrl}/help-center/articles/${articleId}`;

            const btnSave = document.getElementById('btn-save-article');
            const originalHTML = btnSave.innerHTML;
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.data || data.success) {
                    showAlert('success', isCreate ? 'Artículo creado exitosamente' : 'Artículo actualizado exitosamente');
                    $('#modal-form').modal('hide');
                    loadArticles(getCurrentFilters(), currentPage);
                } else {
                    showAlert('error', data.message || 'Error al guardar el artículo');
                }
            })
            .catch(error => {
                console.error('Error saving article:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = originalHTML;
            });
        }

        // =====================================================================
        // PUBLISH ARTICLE
        // =====================================================================

        function publishArticle(articleId) {
            const article = allArticles.find(a => a.id === articleId);
            const articleTitle = article ? article.title : 'este artículo';

            Swal.fire({
                title: '¿Publicar artículo?',
                html: `¿Deseas publicar <strong>${articleTitle}</strong>?<br><small class="text-muted">Será visible para todos los usuarios.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-paper-plane"></i> Sí, publicar',
                cancelButtonText: 'Cancelar',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`${apiUrl}/help-center/articles/${articleId}/publish`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.data || data.success) {
                            showAlert('success', 'Artículo publicado exitosamente');
                            loadArticles(getCurrentFilters(), currentPage);
                        } else {
                            showAlert('error', data.message || 'Error al publicar el artículo');
                        }
                    })
                    .catch(error => {
                        console.error('Error publishing article:', error);
                        showAlert('error', 'Error de conexión: ' + error.message);
                    });
                }
            });
        }

        // =====================================================================
        // UNPUBLISH ARTICLE
        // =====================================================================

        function unpublishArticle(articleId) {
            const article = allArticles.find(a => a.id === articleId);
            const articleTitle = article ? article.title : 'este artículo';

            Swal.fire({
                title: '¿Despublicar artículo?',
                html: `¿Deseas despublicar <strong>${articleTitle}</strong>?<br><small class="text-muted">Volverá al estado de borrador y no será visible para los usuarios.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#adb5bd',
                confirmButtonText: '<i class="fas fa-undo"></i> Sí, despublicar',
                cancelButtonText: 'Cancelar',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`${apiUrl}/help-center/articles/${articleId}/unpublish`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.data || data.success) {
                            showAlert('success', 'Artículo despublicado exitosamente');
                            loadArticles(getCurrentFilters(), currentPage);
                        } else {
                            showAlert('error', data.message || 'Error al despublicar el artículo');
                        }
                    })
                    .catch(error => {
                        console.error('Error unpublishing article:', error);
                        showAlert('error', 'Error de conexión: ' + error.message);
                    });
                }
            });
        }

        // =====================================================================
        // DELETE ARTICLE
        // =====================================================================

        function openDeleteModal(articleId) {
            currentArticle = allArticles.find(a => a.id === articleId);
            if (!currentArticle) {
                showAlert('error', 'No se encontró el artículo');
                return;
            }

            if (currentArticle.status !== 'DRAFT') {
                showAlert('error', 'Solo se pueden eliminar artículos en estado BORRADOR');
                return;
            }

            document.getElementById('delete-article-title').textContent = currentArticle.title;
            document.getElementById('delete-article-id').value = articleId;

            $('#modal-delete').modal('show');
        }

        function deleteArticle() {
            const articleId = document.getElementById('delete-article-id').value;

            const btnDelete = document.getElementById('btn-confirm-delete');
            const originalHTML = btnDelete.innerHTML;
            btnDelete.disabled = true;
            btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

            fetch(`${apiUrl}/help-center/articles/${articleId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.data?.success) {
                    showAlert('success', 'Artículo eliminado exitosamente');
                    $('#modal-delete').modal('hide');
                    loadArticles(getCurrentFilters(), currentPage);
                } else {
                    showAlert('error', data.message || 'Error al eliminar el artículo');
                }
            })
            .catch(error => {
                console.error('Error deleting article:', error);
                showAlert('error', 'Error de conexión: ' + error.message);
            })
            .finally(() => {
                btnDelete.disabled = false;
                btnDelete.innerHTML = originalHTML;
            });
        }

        // =====================================================================
        // SHOW ALERT
        // =====================================================================

        function showAlert(type, message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: type,
                title: message
            });
        }

        // =====================================================================
        // ATTACH ACTION LISTENERS
        // =====================================================================

        function attachActionListeners() {
            document.querySelectorAll('.btn-view').forEach(btn => {
                btn.addEventListener('click', function() {
                    openViewModal(this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function() {
                    openFormModal('edit', this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-publish').forEach(btn => {
                btn.addEventListener('click', function() {
                    publishArticle(this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-unpublish').forEach(btn => {
                btn.addEventListener('click', function() {
                    unpublishArticle(this.dataset.id);
                });
            });

            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    openDeleteModal(this.dataset.id);
                });
            });
        }

        // =====================================================================
        // CLEAR FILTERS
        // =====================================================================

        function clearFilters() {
            document.getElementById('search-articles').value = '';
            document.getElementById('filter-category').value = '';
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-sort').value = '-created_at';
            loadArticles({}, 1);
        }

        // =====================================================================
        // UPDATE PAGINATION INFO
        // =====================================================================

        function updatePaginationInfo(meta) {
            const paginationInfo = document.getElementById('pagination-info');
            if (!paginationInfo) return;

            if (!meta || !meta.total) {
                paginationInfo.textContent = 'Mostrando 0 de 0';
                return;
            }

            const from = meta.from || 0;
            const to = meta.to || 0;
            const total = meta.total || 0;
            paginationInfo.textContent = `Mostrando ${from} a ${to} de ${total}`;
        }

        // =====================================================================
        // EVENT LISTENERS
        // =====================================================================

        document.getElementById('filter-category').addEventListener('change', function() {
            loadArticles(getCurrentFilters(), 1);
        });

        document.getElementById('filter-status').addEventListener('change', function() {
            loadArticles(getCurrentFilters(), 1);
        });

        document.getElementById('filter-sort').addEventListener('change', function() {
            loadArticles(getCurrentFilters(), 1);
        });

        document.getElementById('search-articles').addEventListener('input', function() {
            loadArticles(getCurrentFilters(), 1);
        });

        document.getElementById('btn-refresh').addEventListener('click', function() {
            loadArticles(getCurrentFilters(), currentPage);
        });

        document.getElementById('btn-clear-filters').addEventListener('click', clearFilters);

        document.getElementById('btn-create-article').addEventListener('click', function() {
            openFormModal('create');
        });

        document.getElementById('btn-modal-edit').addEventListener('click', function() {
            if (currentArticle) {
                openFormModal('edit', currentArticle.id);
            }
        });

        document.getElementById('btn-save-article').addEventListener('click', saveArticle);

        document.getElementById('btn-confirm-delete').addEventListener('click', deleteArticle);

        // =====================================================================
        // INITIALIZE
        // =====================================================================

        loadCategories();
        loadArticles();
    });
</script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Marked.js for Markdown rendering -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@endsection