# Vista Knowledge Base - Help Center para Rol USER
## Especificación AdminLTE v3 al 100% + Search Cards + Categorías

---

## 🔍 FASE PREVIA: Investigación de AdminLTE v3

### ✅ Ubicación de Componentes en Vendor
**Ruta base**: `@resources/views/vendor/adminlte/views/`

**Componentes disponibles a usar**:
1. **Card Component** → `components/widget/card.blade.php`
   - Soporte para header, body, footer, tools
   - Collapsible y removable
   - Versiones: `card`, `card-outline`, `card-primary`, etc.

2. **Accordion/Collapse** → Nativo Bootstrap 4 (AdminLTE v3)
   - ENCONTRADO en: `pages/examples/faq.html`
   - Patrón: `<div id="accordion">` + Bootstrap collapse
   - Estructura:
     ```html
     <div id="accordion">
       <div class="card card-primary card-outline">
         <a class="d-block w-100" data-toggle="collapse" href="#collapseOne">
           <div class="card-header">
             <h4 class="card-title w-100">Pregunta</h4>
           </div>
         </a>
         <div id="collapseOne" class="collapse" data-parent="#accordion">
           <div class="card-body">Respuesta aquí</div>
         </div>
       </div>
     </div>
     ```

3. **Search/Filter** → Nativo Bootstrap + Custom
   - Navbar search: `partials/navbar/menu-item-search-form.blade.php`
   - Ejemplo: `pages/search/enhanced.html`
   - Patrón: Input + Button (form-inline)

4. **Small Box / Info Box** → `components/widget/small-box.blade.php` + `info-box.blade.php`
   - Para mostrar contador de categorías/artículos

5. **Alert / Callout** → `components/widget/alert.blade.php` + `callout.blade.php`
   - Para mensajes de "No encontrado"

---

## 📋 FASE 1: Layout General de la Vista

### ✅ Estructura de Página (Modelo)
**Ubicación**: `@resources/views/layouts/authenticated.blade.php` (layout base)

**Estructura esperada**:
```blade
@extends('layouts.authenticated')
@section('title', 'Centro de Ayuda')
@section('content_header', 'Knowledge Base')
@section('content')

<!-- CONTENIDO AQUÍ -->

@push('scripts')
<!-- JavaScript aquí -->
@endpush
```

---

## 📋 FASE 2: Sección de Búsqueda - Search Cards

### ✅ Búsqueda Principal (Cards)
**Patrón AdminLTE**: Usar card + search input + botones de categorías

**Referencia**: `@resources/views/vendor/adminlte/views/pages/search/enhanced.html`

**Estructura HTML a implementar**:
```html
<!-- Search Card -->
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card card-outline card-info">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-search mr-2"></i>
          Buscar en Centro de Ayuda
        </h3>
      </div>
      <div class="card-body">
        <!-- Input de búsqueda -->
        <div class="row mb-3">
          <div class="col-md-9">
            <div class="input-group input-group-lg">
              <input
                type="text"
                id="search-input"
                class="form-control"
                placeholder="¿Qué necesitas encontrar?"
              >
              <div class="input-group-append">
                <button id="search-btn" class="btn btn-info" type="button">
                  <i class="fas fa-search"></i> Buscar
                </button>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <button id="reset-btn" class="btn btn-secondary btn-block">
              <i class="fas fa-redo mr-1"></i> Limpiar
            </button>
          </div>
        </div>

        <!-- Botones de Categorías -->
        <div class="row">
          <div class="col-md-12">
            <p class="text-muted mb-2">Filtrar por categoría:</p>
            <div class="btn-group d-flex flex-wrap" role="group">
              <button type="button" class="btn btn-outline-primary category-filter active mr-2 mb-2"
                      data-category="">
                <i class="fas fa-list mr-1"></i> Todas
              </button>
              <button type="button" class="btn btn-outline-info category-filter mr-2 mb-2"
                      data-category="ACCOUNT_PROFILE">
                <i class="fas fa-user mr-1"></i> Cuenta y Perfil
              </button>
              <button type="button" class="btn btn-outline-danger category-filter mr-2 mb-2"
                      data-category="SECURITY_PRIVACY">
                <i class="fas fa-shield-alt mr-1"></i> Seguridad y Privacidad
              </button>
              <button type="button" class="btn btn-outline-warning category-filter mr-2 mb-2"
                      data-category="BILLING_PAYMENTS">
                <i class="fas fa-credit-card mr-1"></i> Facturación y Pagos
              </button>
              <button type="button" class="btn btn-outline-success category-filter mr-2 mb-2"
                      data-category="TECHNICAL_SUPPORT">
                <i class="fas fa-tools mr-1"></i> Soporte Técnico
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
```

**Componentes AdminLTE usados**:
- ✅ `card` class (bootstrap4 standard)
- ✅ `card-header` (bootstrap4 standard)
- ✅ `card-body` (bootstrap4 standard)
- ✅ `input-group` + `form-control` (bootstrap4 standard)
- ✅ `btn btn-info`, `btn-secondary`, `btn-outline-*` (bootstrap4 standard)

---

## 📋 FASE 3: Sección de Artículos - Accordion/Cards Knowledge Base

### ✅ Presentación: Accordion con Collapse (AdminLTE FAQ Pattern)
**Referencia oficial**: `@resources/views/vendor/adminlte/views/pages/examples/faq.html`

**Estructura HTML a implementar**:
```html
<!-- Articles Container -->
<div class="row">
  <div class="col-md-12">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Cargando...</span>
      </div>
      <p class="mt-2 text-muted">Cargando artículos...</p>
    </div>

    <!-- Accordion Content -->
    <div id="articles-accordion" style="display: none;">
      <!-- Articles rendered here via JavaScript -->
    </div>

    <!-- Empty State -->
    <div id="empty-state" style="display: none;">
      <div class="callout callout-info">
        <h5>No se encontraron artículos</h5>
        <p>Intenta con otra búsqueda o categoría.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4" id="pagination-container"></div>
  </div>
</div>
```

**Componente Card-Accordion (Template)** - A renderizar vía JavaScript:
```html
<div class="card card-primary card-outline article-card">
  <a class="d-block w-100" data-toggle="collapse" href="#article-{ID}">
    <div class="card-header">
      <div class="row align-items-center">
        <div class="col-md-10">
          <h4 class="card-title w-100 mb-0 article-title">
            Título del artículo
          </h4>
        </div>
        <div class="col-md-2 text-right">
          <span class="badge article-category-badge">Categoría</span>
        </div>
      </div>
      <p class="text-muted small mb-0 mt-2 article-excerpt">
        Resumen del artículo...
      </p>
    </div>
  </a>
  <div id="article-{ID}" class="collapse" data-parent="#articles-accordion">
    <div class="card-body article-content">
      <!-- Contenido completo aquí -->
    </div>
    <div class="card-footer text-muted small">
      <i class="fas fa-eye"></i> <span class="article-views"></span> vistas
      • <i class="fas fa-user"></i> <span class="article-author"></span>
      • <i class="fas fa-calendar"></i> <span class="article-date"></span>
    </div>
  </div>
</div>
```

**Componentes AdminLTE usados**:
- ✅ `card card-primary card-outline` (de FAQ example)
- ✅ `d-block w-100` (Bootstrap 4)
- ✅ `data-toggle="collapse"` + `href="#id"` (Bootstrap 4 collapse)
- ✅ `collapse` class (Bootstrap 4)
- ✅ `data-parent="#accordion"` (Bootstrap 4 accordion group)
- ✅ `card-header`, `card-body`, `card-footer` (Bootstrap 4)
- ✅ `badge` class (Bootstrap 4)

---

## 📋 FASE 4: Callout para Estado Vacío

### ✅ Componente Empty State
**Referencia**: `@resources/views/vendor/adminlte/views/components/widget/callout.blade.php`

**Estructura**:
```html
<div class="callout callout-info">
  <h5>
    <i class="fas fa-inbox mr-2"></i>
    No hay artículos disponibles
  </h5>
  <p>
    No se encontraron artículos que coincidan con tu búsqueda.
    Intenta con otra búsqueda o categoría.
  </p>
</div>
```

**Variantes de callout en AdminLTE**:
- `callout callout-info` (azul)
- `callout callout-success` (verde)
- `callout callout-warning` (amarillo)
- `callout callout-danger` (rojo)

---

## 📋 FASE 5: Info Boxes para Estadísticas (Opcional)

### ✅ Cards de Estadísticas
**Referencia**: `@resources/views/vendor/adminlte/views/components/widget/info-box.blade.php`

**Si se requiere mostrar contadores** (opcional):
```html
<div class="row mb-4">
  <div class="col-md-3">
    <div class="info-box bg-info">
      <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Total de Artículos</span>
        <span class="info-box-number" id="total-articles">0</span>
      </div>
    </div>
  </div>
  <!-- Repetir para otras estadísticas -->
</div>
```

---

## 📋 FASE 6: Mapeo de Categorías a Colores AdminLTE

### ✅ Esquema de Colores (de AdminLTE v3)
```javascript
const categoryConfig = {
  'ACCOUNT_PROFILE': {
    icon: 'fas fa-user',
    color: 'primary',        // bg-primary, text-primary
    badgeColor: 'badge-info',
    label: 'Cuenta y Perfil'
  },
  'SECURITY_PRIVACY': {
    icon: 'fas fa-shield-alt',
    color: 'danger',         // bg-danger, text-danger
    badgeColor: 'badge-danger',
    label: 'Seguridad y Privacidad'
  },
  'BILLING_PAYMENTS': {
    icon: 'fas fa-credit-card',
    color: 'warning',        // bg-warning, text-warning
    badgeColor: 'badge-warning',
    label: 'Facturación y Pagos'
  },
  'TECHNICAL_SUPPORT': {
    icon: 'fas fa-tools',
    color: 'success',        // bg-success, text-success
    badgeColor: 'badge-success',
    label: 'Soporte Técnico'
  }
};
```

**Clases de color en AdminLTE v3**:
- `bg-primary`, `text-primary` (azul)
- `bg-danger`, `text-danger` (rojo)
- `bg-warning`, `text-warning` (amarillo)
- `bg-success`, `text-success` (verde)
- `bg-info`, `text-info` (celeste)
- `badge-primary`, `badge-danger`, `badge-warning`, `badge-success`, `badge-info`

---

## 📋 FASE 7: JavaScript - Funciones Principales

### ✅ Variables de Estado
```javascript
$(document).ready(function() {
  let currentPage = 1;
  let currentCategory = '';
  let currentSearch = '';
  const token = window.tokenManager ? window.tokenManager.getAccessToken() : localStorage.getItem('access_token');

  // Inicializar
  loadArticles();

  // ... resto del código
});
```

### ✅ Funciones Clave

#### 1. `loadArticles()` - Cargar desde API
```javascript
function loadArticles() {
  $('#loading-spinner').show();
  $('#articles-accordion').hide().empty();
  $('#empty-state').hide();
  $('#pagination-container').empty();

  let url = `/api/help-center/articles?page=${currentPage}&per_page=10&status=published`;
  if (currentCategory) url += `&category=${currentCategory}`;
  if (currentSearch) url += `&search=${currentSearch}`;

  $.ajax({
    url: url,
    method: 'GET',
    headers: { 'Authorization': 'Bearer ' + token },
    success: function(response) {
      $('#loading-spinner').hide();

      if (response.data && response.data.length > 0) {
        renderAccordion(response.data);
        renderPagination(response.meta);
        $('#articles-accordion').fadeIn();
      } else {
        showEmptyState();
      }
    },
    error: function(xhr) {
      $('#loading-spinner').hide();
      toastr.error('Error al cargar los artículos');
    }
  });
}
```

#### 2. `renderAccordion()` - Renderizar Accordion
```javascript
function renderAccordion(articles) {
  const accordion = $('#articles-accordion');

  articles.forEach((article, index) => {
    const config = categoryConfig[article.category_id] || categoryConfig['ACCOUNT_PROFILE'];
    const cardHtml = `
      <div class="card card-outline card-${config.color} article-card">
        <a class="d-block w-100" data-toggle="collapse" href="#article-${article.id}">
          <div class="card-header">
            <div class="row align-items-center">
              <div class="col-md-10">
                <h4 class="card-title w-100 mb-0">
                  <i class="${config.icon} mr-2 text-${config.color}"></i>
                  ${article.title}
                </h4>
              </div>
              <div class="col-md-2 text-right">
                <span class="${config.badgeColor}">${config.label}</span>
              </div>
            </div>
            <p class="text-muted small mb-0 mt-2">
              ${article.excerpt || article.content.substring(0, 100)}...
            </p>
          </div>
        </a>
        <div id="article-${article.id}" class="collapse" data-parent="#articles-accordion">
          <div class="card-body">
            ${article.content}
          </div>
          <div class="card-footer text-muted small">
            <i class="fas fa-eye"></i> ${article.views_count} vistas
            • <i class="fas fa-user"></i> ${article.author_name || 'Anónimo'}
            • <i class="fas fa-calendar"></i> ${new Date(article.published_at).toLocaleDateString()}
          </div>
        </div>
      </div>
    `;
    accordion.append(cardHtml);
  });
}
```

#### 3. Event Listeners
```javascript
// Búsqueda
$('#search-btn').on('click', function() {
  currentSearch = $('#search-input').val();
  currentPage = 1;
  loadArticles();
});

$('#search-input').on('keypress', function(e) {
  if (e.which === 13) {
    currentSearch = $(this).val();
    currentPage = 1;
    loadArticles();
  }
});

// Filtros por categoría
$('.category-filter').on('click', function() {
  $('.category-filter').removeClass('active');
  $(this).addClass('active');
  currentCategory = $(this).data('category');
  currentPage = 1;
  loadArticles();
});

// Reset
$('#reset-btn').on('click', function() {
  $('#search-input').val('');
  currentSearch = '';
  currentCategory = '';
  $('.category-filter').removeClass('active').first().addClass('active');
  currentPage = 1;
  loadArticles();
});
```

#### 4. `renderPagination()` - Paginación
```javascript
function renderPagination(meta) {
  if (meta.last_page > 1) {
    let html = '<ul class="pagination">';

    // Prev button
    html += `<li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
              <a class="page-link pagination-link" href="#" data-page="${meta.current_page - 1}">&laquo;</a>
            </li>`;

    // Page numbers
    for (let i = 1; i <= meta.last_page; i++) {
      html += `<li class="page-item ${meta.current_page === i ? 'active' : ''}">
                <a class="page-link pagination-link" href="#" data-page="${i}">${i}</a>
              </li>`;
    }

    // Next button
    html += `<li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
              <a class="page-link pagination-link" href="#" data-page="${meta.current_page + 1}">&raquo;</a>
            </li>`;

    html += '</ul>';
    $('#pagination-container').html(html);

    $('.pagination-link').on('click', function(e) {
      e.preventDefault();
      const page = $(this).data('page');
      if (page && page !== currentPage) {
        currentPage = page;
        loadArticles();
        window.scrollTo(0, 0);
      }
    });
  }
}
```

#### 5. `showEmptyState()` - Estado Vacío
```javascript
function showEmptyState() {
  const emptyHtml = `
    <div class="callout callout-info">
      <h5>
        <i class="fas fa-inbox mr-2"></i>
        No se encontraron artículos
      </h5>
      <p>
        No hay artículos que coincidan con tu búsqueda.
        ${currentSearch ? 'Intenta con otro término.' : 'Intenta con otra categoría o búsqueda.'}
      </p>
    </div>
  `;
  $('#empty-state').html(emptyHtml).fadeIn();
}
```

---

## 🎯 CHECKLIST DE IMPLEMENTACIÓN

### Paso 1: Crear Ruta Web
- [ ] Agregar en `routes/web.php` (grupo USER):
  ```php
  Route::get('/help-center', function () {
      $user = JWTHelper::getAuthenticatedUser();
      return view('app.user.help-center.index', ['user' => $user]);
  })->name('user.help-center.index');
  ```

### Paso 2: Crear Directorios
- [ ] `mkdir -p resources/views/app/user/help-center`

### Paso 3: Crear Vista Principal
- [ ] Archivo: `resources/views/app/user/help-center/index.blade.php`
- [ ] Estructura:
  - @extends('layouts.authenticated')
  - Search Card (Fase 2)
  - Articles Accordion (Fase 3)
  - JavaScript (Fase 7)

### Paso 4: Referencias de AdminLTE
- [ ] Search input → Bootstrap 4 standard (`input-group`, `form-control`)
- [ ] Buttons → Bootstrap 4 standard (`btn`, `btn-outline-*`, `btn-block`)
- [ ] Cards → Bootstrap 4 standard (`card`, `card-header`, `card-body`, `card-footer`)
- [ ] Collapse → Bootstrap 4 standard (`collapse`, `data-toggle="collapse"`)
- [ ] Callout → AdminLTE component (`callout callout-info`)
- [ ] Pagination → Bootstrap 4 standard (`pagination`)

### Paso 5: Limpiar Cache de Rutas
- [ ] `docker exec helpdesk php artisan route:clear`

### Paso 6: Pruebas
- [ ] Página carga sin errores
- [ ] Búsqueda funciona
- [ ] Filtros por categoría funcionan
- [ ] Accordion abre/cierra correctamente
- [ ] Paginación funciona
- [ ] Estado vacío muestra correctly
- [ ] Responsive en mobile

---

## 🔗 REFERENCIAS EXACTAS DE ADMINLTE V3

**Ver en vendor los ejemplos**:
1. FAQ Accordion → `@resources/views/vendor/adminlte/views/pages/examples/faq.html`
2. Search Page → `@resources/views/vendor/adminlte/views/pages/search/enhanced.html`
3. Card Component → `@resources/views/vendor/adminlte/views/components/widget/card.blade.php`
4. Callout → `@resources/views/vendor/adminlte/views/components/widget/callout.blade.php`

---

## ⚠️ SI FALTA ALGO

Si necesitas un componente que NO existe en AdminLTE v3 vendor:
1. **Crear TICKET** en lugar de inventar
2. **Especificar exactamente** qué falta
3. **Detallar** componente AdminLTE alternativo a usar
4. NO hacer custom CSS sin justificación

---

**Estado**: ✅ ESPECIFICACIÓN COMPLETA - AdminLTE v3 al 100%
**Última actualización**: Investigación final completada
