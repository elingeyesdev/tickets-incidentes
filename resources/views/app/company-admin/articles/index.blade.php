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
<!-- Row 1: Filters and Actions -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <!-- Filter by Category -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-category" class="mb-1">Categoría:</label>
                        <select id="filter-category" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="ACCOUNT_PROFILE">Cuenta y Perfil</option>
                            <option value="SECURITY_PRIVACY">Seguridad y Privacidad</option>
                            <option value="BILLING_PAYMENTS">Facturación y Pagos</option>
                            <option value="TECHNICAL_SUPPORT">Soporte Técnico</option>
                        </select>
                    </div>

                    <!-- Filter by Status -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-status" class="mb-1">Estado:</label>
                        <select id="filter-status" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="draft">Borrador</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                        <label for="filter-sort" class="mb-1">Ordenar por:</label>
                        <select id="filter-sort" class="form-control form-control-sm">
                            <option value="-created_at">Más recientes</option>
                            <option value="created_at">Más antiguos</option>
                            <option value="title">Título (A-Z)</option>
                            <option value="-title">Título (Z-A)</option>
                            <option value="-views">Más visto</option>
                            <option value="views">Menos visto</option>
                        </select>
                    </div>

                    <!-- Search Box -->
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-2">
                        <label for="search-articles" class="mb-1">Buscar:</label>
                        <input type="text" id="search-articles" class="form-control form-control-sm"
                               placeholder="Buscar en título y contenido...">
                    </div>

                    <!-- Refresh Button -->
                    <div class="col-lg-1 col-md-6 col-sm-12 mb-2">
                        <label class="mb-1">&nbsp;</label>
                        <button id="btn-refresh" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>

                    <!-- Create Button -->
                    <div class="col-lg-2 col-md-12 col-sm-12 mb-2">
                        <label class="mb-1">&nbsp;</label>
                        <button id="btn-create-article" class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-plus"></i> Crear Artículo
                        </button>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row mt-2">
                    <div class="col-12">
                        <small class="text-muted">
                            Total: <strong id="total-count">0</strong> |
                            Publicados: <strong id="published-count">0</strong> |
                            Borradores: <strong id="draft-count">0</strong> |
                            Vistas totales: <strong id="views-count">0</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Articles Table -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-book"></i> Artículos del Centro de Ayuda
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="articles-table" class="table table-striped table-hover">
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
                            <!-- Loaded dynamically via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right" id="pagination">
                    <!-- Loaded dynamically -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: View Article -->
<div class="modal fade" id="modal-view" tabindex="-1" role="dialog" aria-labelledby="modalViewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="modalViewLabel">
                    <i class="fas fa-eye"></i> Ver Artículo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <h3 id="view-title" class="mb-3"></h3>
                        <div class="mb-3">
                            <span id="view-category-badge-container"></span>
                            <span id="view-status-badge-container"></span>
                            <span class="badge badge-light"><i class="fas fa-eye"></i> <span id="view-views">0</span> vistas</span>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h5>Resumen:</h5>
                            <p id="view-excerpt" class="text-muted" style="word-wrap: break-word; white-space: pre-wrap;"></p>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h5>Contenido:</h5>
                            <div id="view-content" class="border rounded p-3 bg-light" style="word-wrap: break-word; white-space: pre-wrap; overflow-wrap: break-word;"></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">Creado: <strong id="view-created"></strong></small>
                            </div>
                            <div class="col-md-6 text-right">
                                <small class="text-muted">Publicado: <strong id="view-published"></strong></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" id="btn-modal-edit" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Create/Edit Article -->
<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modalFormLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white" id="modalFormLabel">
                    <i class="fas fa-plus-circle"></i> <span id="form-title">Nuevo Artículo</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="article-form">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="form-category">Categoría <span class="text-danger">*</span></label>
                                <select id="form-category" class="form-control" required disabled>
                                    <option value="">Cargando categorías...</option>
                                </select>
                                <small class="form-text text-muted">Selecciona la categoría del artículo</small>
                            </div>

                            <div class="form-group">
                                <label for="form-article-title">Título <span class="text-danger">*</span></label>
                                <input type="text" id="form-article-title" class="form-control"
                                       placeholder="Título del artículo"
                                       minlength="3" maxlength="255" required>
                                <small class="form-text text-muted">Mínimo 3 caracteres, máximo 255</small>
                            </div>

                            <div class="form-group">
                                <label for="form-excerpt">Extracto (Opcional)</label>
                                <textarea id="form-excerpt" class="form-control" rows="2"
                                          placeholder="Breve descripción del artículo..."
                                          maxlength="500"></textarea>
                                <small class="form-text text-muted">Máximo 500 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="form-content">Contenido <span class="text-danger">*</span></label>
                                <textarea id="form-content" class="form-control" rows="10"
                                          placeholder="Escribe el contenido completo del artículo..."
                                          minlength="50" required></textarea>
                                <small class="form-text text-muted">Mínimo 50 caracteres</small>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="form-article-id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-save-article" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Delete Article -->
<div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-labelledby="modalDeleteLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="modalDeleteLabel">
                    <i class="fas fa-trash"></i> Eliminar Artículo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¿Deseas eliminar el artículo "<span id="delete-article-title">-</span>"?</strong>
                </div>
                <p class="text-muted">Solo se pueden eliminar artículos en estado BORRADOR.</p>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
                <input type="hidden" id="delete-article-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-confirm-delete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
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
            const colors = {
                'ACCOUNT_PROFILE': 'primary',
                'SECURITY_PRIVACY': 'warning',
                'BILLING_PAYMENTS': 'success',
                'TECHNICAL_SUPPORT': 'info'
            };
            const color = colors[code] || 'secondary';
            return `<span class="badge badge-${color}">${name || code}</span>`;
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
            params.append('per_page', 15);

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

                    // Update stats - Only update when no filters are applied
                    const total = data.meta?.total || 0;
                    document.getElementById('total-count').textContent = total;

                    // Calculate stats from current page data
                    const publishedCount = allArticles.filter(a => a.status === 'PUBLISHED').length;
                    const draftCount = allArticles.filter(a => a.status === 'DRAFT').length;
                    const totalViews = allArticles.reduce((sum, a) => sum + (a.views_count || 0), 0);

                    // Only show page-specific stats if filtering
                    if (filters.category || filters.status || filters.search) {
                        document.getElementById('published-count').textContent = publishedCount + ' (filtrados)';
                        document.getElementById('draft-count').textContent = draftCount + ' (filtrados)';
                        document.getElementById('views-count').textContent = totalViews + ' (filtrados)';
                    } else {
                        document.getElementById('published-count').textContent = publishedCount;
                        document.getElementById('draft-count').textContent = draftCount;
                        document.getElementById('views-count').textContent = totalViews;
                    }
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
                            <small class="text-muted">${(article.excerpt || '').substring(0, 80)}${(article.excerpt || '').length > 80 ? '...' : ''}</small>
                        </td>
                        <td>${categoryBadge}</td>
                        <td>${statusBadge}</td>
                        <td><span class="badge badge-light"><i class="fas fa-eye"></i> ${article.views_count || 0}</span></td>
                        <td><small>${formatDate(article.published_at)}</small></td>
                        <td>
                            <button class="btn btn-sm btn-info btn-view" data-id="${article.id}" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-edit" data-id="${article.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${article.status === 'DRAFT' ? `
                                <button class="btn btn-sm btn-success btn-publish" data-id="${article.id}" title="Publicar">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete" data-id="${article.id}" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : `
                                <button class="btn btn-sm btn-secondary btn-unpublish" data-id="${article.id}" title="Despublicar">
                                    <i class="fas fa-undo"></i>
                                </button>
                            `}
                        </td>
                    </tr>
                `;
            }).join('');

            attachActionListeners();
        }

        // =====================================================================
        // RENDER PAGINATION
        // =====================================================================

        function renderPagination(meta) {
            const pagination = document.getElementById('pagination');

            if (!meta || meta.last_page <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';

            // Previous button
            if (meta.current_page > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${meta.current_page - 1}">&laquo;</a></li>`;
            }

            // Page numbers
            for (let i = 1; i <= meta.last_page; i++) {
                if (i === meta.current_page) {
                    html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
            }

            // Next button
            if (meta.current_page < meta.last_page) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${meta.current_page + 1}">&raquo;</a></li>`;
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
            document.getElementById('view-excerpt').textContent = currentArticle.excerpt || 'Sin extracto';

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

            if (mode === 'create') {
                document.getElementById('form-title').textContent = 'Nuevo Artículo';
                document.getElementById('modalFormLabel').closest('.modal-header').classList.remove('bg-info');
                document.getElementById('modalFormLabel').closest('.modal-header').classList.add('bg-success');
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

                document.getElementById('form-title').textContent = 'Editar Artículo';
                document.getElementById('modalFormLabel').closest('.modal-header').classList.remove('bg-success');
                document.getElementById('modalFormLabel').closest('.modal-header').classList.add('bg-info');

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
                btnSave.innerHTML = '<i class="fas fa-save"></i> Guardar';
            });
        }

        // =====================================================================
        // PUBLISH ARTICLE
        // =====================================================================

        function publishArticle(articleId) {
            if (!confirm('¿Deseas publicar este artículo? Será visible para los usuarios.')) {
                return;
            }

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

        // =====================================================================
        // UNPUBLISH ARTICLE
        // =====================================================================

        function unpublishArticle(articleId) {
            if (!confirm('¿Deseas despublicar este artículo? Volverá al estado de borrador.')) {
                return;
            }

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
                btnDelete.innerHTML = '<i class="fas fa-trash"></i> Eliminar';
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
