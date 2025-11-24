# Vista de Knowledge Base - Help Center para Rol USER
## Especificación AdminLTE v3 + Search + Cards por Categorías

## 📋 FASE 1: Análisis de Modelos y Estructura Base

### ✅ Modelo HelpCenterArticle - Encontrado
**Ubicación**: `app/Features/ContentManagement/Models/HelpCenterArticle.php`

**Tabla BD**: `business.help_center_articles`

**Atributos Principales**:
- `id` (UUID)
- `company_id` (FK a companies)
- `author_id` (FK a users - quien escribe el artículo)
- `category_id` (FK a article_categories)
- `title` - Título del artículo
- `excerpt` - Resumen corto (max 500 caracteres)
- `content` - Contenido principal (markdown o HTML)
- `status` - Estado (enum: DRAFT, PUBLISHED)
- `views_count` - Contador de vistas
- `published_at` - Fecha de publicación
- `created_at`, `updated_at`
- `deleted_at` - Soft delete

**Relaciones Modelo**:
- `belongsTo(Company)` - Empresa propietaria del artículo
- `belongsTo(ArticleCategory)` - Categoría global del artículo
- `belongsTo(User)` - Autor del artículo

**Métodos Útiles**:
- `scopePublished()` - Filtra artículos publicados
- `incrementViews()` - Incrementa el contador de vistas
- `formattedPublishedDate()` - Fecha formateada legible
- `scopeByCategory($categoryCode)` - Filtra por código de categoría
- `scopeSearch($term)` - Búsqueda case-insensitive en título y contenido

---

### ✅ Modelo ArticleCategory - Encontrado
**Ubicación**: `app/Features/ContentManagement/Models/ArticleCategory.php`

**Tabla BD**: `article_categories`

**Categorías Globales Fijas** (4 en total):
1. **ACCOUNT_PROFILE** - Gestión de cuenta y perfil
2. **SECURITY_PRIVACY** - Seguridad y privacidad
3. **BILLING_PAYMENTS** - Facturación y pagos
4. **TECHNICAL_SUPPORT** - Soporte técnico

**Atributos**:
- `id` (UUID)
- `code` (VARCHAR 50, UNIQUE) - Código identificador
- `name` - Nombre legible en inglés/español
- `description` - Descripción detallada
- `created_at`, `updated_at`

**Relación Modelo**:
- `hasMany(HelpCenterArticle)` - Artículos de esta categoría

---

## 📋 FASE 2: Estados de Artículos

### ✅ PublicationStatus Enum (Help Center)
**Estados Posibles**:
- `DRAFT` - Borrador (solo visible para COMPANY_ADMIN)
- `PUBLISHED` - Publicado (visible para END_USER de empresas seguidas)

**Diferencias con Announcements**:
- Help Center solo usa 2 estados (vs 4 en Announcements)
- No hay SCHEDULED ni ARCHIVED
- PUBLISHED articles son inmutables en estructura

---

## 📋 FASE 3: Controladores y Endpoints de API

### ✅ ArticleController - Métodos Disponibles
**Ubicación**: `app/Features/ContentManagement/Http/Controllers/ArticleController.php`

**Base URL**: `/api/help-center/articles`

**Endpoints** (Role-based visibility):

1. **GET /api/help-center/articles** - Listar artículos
   - Parámetros: `page`, `per_page` (max 100), `search`, `category`, `status`, `sort`, `company_id`
   - Ordenamiento: `title`, `-title`, `views`, `-views`, `created_at`, `-created_at`
   - END_USER: ve solo PUBLISHED de empresas que sigue
   - COMPANY_ADMIN: ve DRAFT + PUBLISHED de su empresa
   - PLATFORM_ADMIN: ve todos de todas las empresas
   - Retorna: Paginated list con metadata

2. **GET /api/help-center/articles/{id}** - Obtener artículo específico
   - Mismas reglas de visibilidad que index
   - Incrementa views_count automáticamente si END_USER ve PUBLISHED
   - Retorna: Single article object

3. **POST /api/help-center/articles** - Crear artículo
   - Solo COMPANY_ADMIN
   - Parámetros: `category_id`, `title`, `content`, `excerpt` (opcional)
   - Siempre crea en estado DRAFT
   - Retorna: Article object

4. **PUT /api/help-center/articles/{id}** - Actualizar artículo
   - Solo COMPANY_ADMIN
   - Campos editables: `title`, `content`, `excerpt`, `category_id`
   - Campos inmutables: `company_id`, `author_id`, `published_at`, `views_count`, `status`
   - Partial updates permitidos
   - Retorna: Updated article object

5. **POST /api/help-center/articles/{id}/publish** - Publicar artículo
   - Solo COMPANY_ADMIN
   - Solo artículos en DRAFT
   - Establece `published_at` a timestamp actual
   - Dispara evento ArticlePublished
   - Retorna: Published article object

6. **POST /api/help-center/articles/{id}/unpublish** - Despublicar artículo
   - Solo COMPANY_ADMIN
   - Solo artículos en PUBLISHED
   - Establece `published_at` a null
   - Preserva views_count
   - Retorna: Unpublished article object

7. **DELETE /api/help-center/articles/{id}** - Eliminar artículo
   - Solo COMPANY_ADMIN
   - Solo DRAFT articles
   - No se pueden eliminar PUBLISHED (error 403)
   - Soft delete
   - Retorna: Success message

### ✅ HelpCenterCategoryController - Métodos Disponibles
**Ubicación**: `app/Features/ContentManagement/Http/Controllers/HelpCenterCategoryController.php`

**Base URL**: `/api/help-center/categories`

**Endpoints**:

1. **GET /api/help-center/categories** - Listar categorías
   - NO requiere autenticación (público)
   - Retorna: Array de 4 categorías globales
   - Cada categoría incluye: `id`, `code`, `name`, `description`

---

## 📋 FASE 4: Servicios de Lógica de Negocio

### ✅ ArticleService - Lógica centralizada
**Ubicación**: `app/Features/ContentManagement/Services/ArticleService.php`

**Métodos principales**:
- `createArticle($data, $companyId, $authorId)` - Crear artículo DRAFT
- `updateArticle($articleId, $data, $companyId)` - Actualizar artículo (partial updates)
- `publishArticle($articleId, $companyId)` - Publicar artículo
- `unpublishArticle($articleId, $companyId)` - Despublicar artículo
- `deleteArticle($articleId, $companyId)` - Eliminar artículo (soft delete)
- `viewArticle($user, $articleId)` - Ver artículo con visibilidad y increment views
- `listArticles($user, $filters)` - Listar artículos con filtros y visibilidad

### ✅ ArticleCategoryService - Gestión de categorías
**Ubicación**: `app/Features/ContentManagement/Services/ArticleCategoryService.php`

**Métodos principales**:
- `getAllCategories()` - Obtener 4 categorías globales

---

## 📋 FASE 5: Recursos de API y Estructura de Respuestas

### ✅ ArticleResource - Transformación de datos
**Ubicación**: `app/Features/ContentManagement/Http/Resources/ArticleResource.php`

**JSON Response Structure**:
```json
{
  "id": "uuid",
  "company_id": "uuid",
  "author_id": "uuid",
  "category_id": "uuid",
  "category_name": "Account & Profile",
  "title": "Cómo cambiar mi contraseña",
  "excerpt": "Pasos para actualizar tu contraseña de forma segura",
  "content": "Contenido detallado en markdown o HTML",
  "status": "DRAFT|PUBLISHED",
  "views_count": 42,
  "published_at": "2025-11-24T10:30:00Z",
  "created_at": "2025-11-24T08:00:00Z",
  "updated_at": "2025-11-24T10:30:00Z"
}
```

### ✅ ArticleCategoryResource - Categorías
**Ubicación**: `app/Features/ContentManagement/Http/Resources/ArticleCategoryResource.php`

**JSON Response Structure**:
```json
{
  "id": "uuid",
  "code": "ACCOUNT_PROFILE",
  "name": "Account & Profile",
  "description": "Manage your account settings, profile information, and personal preferences"
}
```

---

## 📋 FASE 6: Vistas Existentes para COMPANY_ADMIN

### ✅ Vista Company-Admin: Artículos del Help Center
**Ubicación**: `resources/views/app/company-admin/articles/index.blade.php`

**Estructura Implementada**:
1. **Estadísticas** - Small boxes con contadores:
   - Total de artículos
   - Artículos publicados
   - Artículos en borrador
   - Total de vistas acumuladas

2. **Tabla Principal** con:
   - Header con Filtros: Búsqueda, Categoría, Estado (DRAFT/PUBLISHED)
   - Ordenamiento: por título, por vistas, por fecha
   - Tabla con columnas: Título, Categoría, Estado, Vistas, Publicado, Acciones

3. **Modales**:
   - Modal de vista (view) - lectura del contenido
   - Modal de formulario (form) - crear/editar artículos

4. **Componentes del Formulario**:
   - Campo de categoría (select dropdown)
   - Campo de título (input text)
   - Campo de resumen/excerpt (textarea)
   - Editor de contenido (rich text editor)

---

## 📋 FASE 7: Rutas Web y Estructura del Sidebar

### ✅ Rutas Actuales (web.php)
**Ubicación**: `routes/web.php` (líneas 302-340)

**Rutas COMPANY_ADMIN (middleware role:COMPANY_ADMIN, prefix: company)**:
```
GET  /app/company/articles              → view articles.index       (name: company.articles.index)  [Línea 224]
```

**Rutas USER EXISTENTES (middleware role:USER, prefix: user)**:
```
GET  /app/user/dashboard       → UserController@dashboard  (name: dashboard.user)           [Línea 303]
GET  /app/user/tickets         → view tickets.index       (name: user.tickets.index)      [Línea 307]
GET  /app/user/announcements   → view announcements.index (name: user.announcements.index) [Línea 317]
GET  /app/user/companies       → view companies.index     (name: user.companies.index)    [Línea 324]
```

**Rutas USER FALTANTES** (NO EXISTEN AÚN):
```
GET  /app/user/help-center     → view help-center.index   (name: user.help-center.index)  ← DEBE CREARSE
```

### ✅ Sidebar - Estructura del Menú de USER
**Ubicación**: `resources/views/app/shared/sidebar.blade.php` (líneas 147-183)

**Menú USER (template x-if="activeRole === 'USER')**:
```blade
<li class="nav-header">SOPORTE</li>
<li class="nav-item">
    <a href="/app/user/tickets" class="nav-link">
        <i class="nav-icon fas fa-ticket-alt"></i>
        <p>Mis Tickets</p>
    </a>
</li>

<li class="nav-header">INFORMACIÓN</li>
<li class="nav-item">
    <a href="/app/user/announcements" class="nav-link">  ← EXISTENTE
        <i class="nav-icon fas fa-bullhorn"></i>
        <p>Anuncios</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('user.companies.index') }}" class="nav-link">
        <i class="nav-icon fas fa-building"></i>
        <p>Empresas</p>
    </a>
</li>
<li class="nav-item">
    <a href="/app/user/help-center" class="nav-link">  ← YA EXISTE EN SIDEBAR!
        <i class="nav-icon fas fa-question-circle"></i>
        <p>Centro de Ayuda</p>
    </a>
</li>

<li class="nav-header">CUENTA</li>
<li class="nav-item">
    <a href="{{ route('app.profile') }}" class="nav-link">
        <i class="nav-icon fas fa-user"></i>
        <p>Perfil</p>
    </a>
</li>
```

**Conclusión**: El enlace en el sidebar YA EXISTE (línea 170), pero la ruta en `web.php` NO existe aún. Necesitamos crearla.

---

## 🎯 RESUMEN GENERAL DEL CONTEXTO (FASE 1)

### Características Únicas del Help Center vs Announcements:
1. **2 Estados solamente** (DRAFT, PUBLISHED) vs 4 en Announcements
2. **4 Categorías globales fijas** (no editables) vs tipos específicos de anuncios
3. **Views count** - ayuda a saber qué contenido es más popular
4. **Excerpt** - resumen corto para previstas en listas
5. **No metadata JSON compleja** - estructura plana

### Archivos Relacionados (HALLADOS):
- **Modelo**: `app/Features/ContentManagement/Models/HelpCenterArticle.php`
- **Categorías**: `app/Features/ContentManagement/Models/ArticleCategory.php`
- **Controller**: `app/Features/ContentManagement/Http/Controllers/ArticleController.php`
- **Controller Categorías**: `app/Features/ContentManagement/Http/Controllers/HelpCenterCategoryController.php`
- **Services**: `ArticleService.php`, `ArticleCategoryService.php`
- **Resources**: `ArticleResource.php`, `ArticleCategoryResource.php`
- **Vista Ref**: `resources/views/app/company-admin/articles/index.blade.php`
- **Rutas API**: `/api/help-center/articles`, `/api/help-center/categories`
- **Rutas Web**: `routes/web.php` (USER routes)

---

## 📋 FASE 8: Estructura de Componentes Blade (Referencia)

### ✅ Componentes Existentes para Announcements (REFERENCIA)
**Ubicación**: `resources/views/components/anuncios/`

**Archivos encontrados**:
1. `card-news.blade.php` - Template para noticias
2. `card-maintenance.blade.php` - Template para mantenimientos
3. `card-incident.blade.php` - Template para incidentes
4. `card-alert.blade.php` - Template para alertas
5. `no-followers.blade.php` - Template para estado sin empresas

**Estructura de Componente (Ejemplo: card-news)**:
```blade
<div id="template-card-news" style="display: none;">
    <i class="fas fa-newspaper bg-blue"></i>
    <div class="timeline-item">
        <span class="time"><i class="fas fa-clock"></i> <span class="announcement-time"></span></span>
        <h3 class="timeline-header">
            <span class="text-primary font-weight-bold">Noticia</span> de
            <span class="company-name"></span>:
            <span class="announcement-title"></span>
        </h3>
        <div class="timeline-body">
            <p class="text-muted font-italic mb-2 news-summary"></p>
            <div class="announcement-content"></div>
        </div>
        <div class="timeline-footer">
            <a href="#" class="btn btn-primary btn-sm news-cta" target="_blank">Leer más</a>
        </div>
    </div>
</div>
```

**Patrón de uso en JavaScript**:
```javascript
// Clonar template
let $template = $('#template-card-news').clone().removeAttr('id').show();

// Poblar campos
$template.find('.announcement-time').text(time);
$template.find('.announcement-title').text(article.title);
// ... etc

// Insertar en timeline
$('#timeline-content').append($template);
```

### ✅ Componente No-Followers (REFERENCIA)
**Ubicación**: `resources/views/components/anuncios/no-followers.blade.php`

**Estructura**:
- Card con mensaje "No sigues a ninguna empresa"
- Icono de edificio 3x
- Párrafo explicativo
- Sección "Empresas Populares" con spinner inicial
- Lista dinámica de empresas sugeridas
- Botón "Explorar todas las empresas"

**Para Help Center**: Necesitaremos algo similar pero con mensaje adaptado

---

## 📋 FASE 9: Vista de Announcements del USER (REFERENCIA DIRECTA)

### ✅ Estructura Vista Announcements USER
**Ubicación**: `resources/views/app/user/announcements/index.blade.php`

**Componentes**:
1. **Filtros** - Botones por tipo (Todos, NEWS, MAINTENANCE, INCIDENT, ALERT)
2. **Búsqueda** - Input con botón buscar
3. **Contenedor de Timeline** - `<div id="announcements-container">`
4. **Loading State** - Spinner visible inicial
5. **Timeline Content** - `<div class="timeline" id="timeline-content">` (oculto)
6. **Empty State** - `<div id="empty-state">` (oculto)
7. **Paginación** - `<div id="pagination-container">` (generada dinámicamente)

**Variables JavaScript principales**:
```javascript
let currentPage = 1;
let currentType = '';
let currentSearch = '';
const dateColors = ['bg-red', 'bg-green', 'bg-blue', 'bg-yellow'];
```

**Funciones principales**:
- `checkFollowedCompanies()` - Verifica si usuario sigue empresas
- `loadAnnouncements()` - Hace fetch a API
- `renderTimeline(announcements)` - Renderiza timeline agrupado por fecha
- `getAnnouncementHtml(announcement)` - Clona template según tipo
- `renderEmptyState()` - Muestra estado vacío
- `showNoFollowersState()` - Muestra componente no-followers
- `loadSuggestions()` - Carga empresas sugeridas
- `renderPagination(meta)` - Renderiza paginación

**Event Listeners**:
- Click en botones de filtro
- Click en botón buscar
- Enter en input de búsqueda
- Click en números de paginación
- Click en botones "Seguir"

---

## 📋 FASE 10: Comparativa Announcements vs Help Center Articles

### ✅ Tabla Comparativa
| Aspecto | Announcements | Help Center Articles |
|---------|--------------|----------------------|
| **Tabla BD** | `company_announcements` | `business.help_center_articles` |
| **Estados** | 4 (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED) | 2 (DRAFT, PUBLISHED) |
| **Tipos** | 4 tipos complejos (NEWS, MAINTENANCE, INCIDENT, ALERT) | Categorías simples (4 fijas) |
| **Metadata** | JSON complejo por tipo (urgency, servicios, etc) | Plana (sin metadata JSON) |
| **Campos únicos** | Metadata, PublicationStatus enum | Excerpt, views_count |
| **Soft Delete** | No (eliminación física) | Sí (deleted_at) |
| **Vistas** | No registra visualizaciones | Sí (views_count) |
| **Editor Contenido** | Markdown/HTML | Markdown/HTML |
| **Resumen** | No existe | Sí (excerpt, max 500 chars) |
| **Tipo Layout** | Timeline con filtros por tipo | Grid/Tabla/Lista con filtros por categoría |

### ✅ Clave de Diferencias
1. **Help Center es más simple**: 2 estados vs 4, sin metadata compleja
2. **Help Center track vistas**: Útil para saber qué ayuda es popular
3. **Help Center tiene excerpt**: Para previsualizaciones
4. **Help Center usa categorías fijas**: No editables desde UI
5. **Help Center soft-deletes**: Articles can be recovered

---

## 📋 FASE 11: Estructura de Implementación para Help Center USER

### ✅ Diferencias en Implementación vs Announcements

**Filtros**:
- Announcements: Botones por TIPO (NEWS, MAINTENANCE, etc) - 4 opciones dinámicas
- Help Center: Botones por CATEGORÍA (ACCOUNT_PROFILE, SECURITY_PRIVACY, etc) - 4 opciones FIJAS

**Búsqueda**:
- Mismo patrón: Input + botón
- Help Center busca en `title` y `content`

**Componentes Blade**:
- Announcements: 4 componentes específicos por tipo (card-news, card-maintenance, etc)
- Help Center: 1-2 componentes genéricos (todos los artículos son iguales estructuralmente)

**API Endpoint**:
- Announcements: `/api/announcements?type=MAINTENANCE&status=published`
- Help Center: `/api/help-center/articles?category=ACCOUNT_PROFILE&status=published`

**Timeline vs Grid**:
- Announcements: Timeline con agrupación por fecha
- Help Center: Podría ser:
  - Opción A: Timeline igual a announcements
  - Opción B: Grid/Cards
  - Opción C: Lista con acordeón
  - **Recomendación**: Timeline para consistencia

**Variables de Estado JavaScript**:
- Announcements: `currentType`, `currentSearch`, `currentPage`
- Help Center: `currentCategory` (en lugar de `currentType`), `currentSearch`, `currentPage`

**Visualización de Artículo**:
- Announcements: Solo lectura en timeline
- Help Center: Probablemente mismo patrón
- API increment views automáticamente

---

## 📋 FASE 12: Especificación de Componentes para Help Center

### ✅ Componentes Blade a Crear

**Opción A: UN componente genérico**
```
resources/views/components/articles/
  article-card.blade.php           ← 1 componente para todos los artículos
  no-articles.blade.php            ← Estado alternativo
```

**Opción B: Por categoría** (menos probable)
```
resources/views/components/articles/
  article-account.blade.php
  article-security.blade.php
  article-billing.blade.php
  article-technical.blade.php
  no-articles.blade.php
```

**Recomendación**: **Opción A** porque todos los artículos tienen la misma estructura

### ✅ Estructura de Componente (article-card.blade.php)
```blade
<div id="template-article-card" style="display: none;">
    <!-- Icono con color según categoría -->
    <i class="fas fa-file-alt bg-info"></i>

    <div class="timeline-item">
        <!-- Timestamp y categoría badge -->
        <span class="time">
            <i class="fas fa-clock"></i>
            <span class="article-publish-date"></span>
        </span>
        <span class="badge badge-info article-category-badge"></span>

        <!-- Título principal -->
        <h3 class="timeline-header">
            <span class="article-title font-weight-bold"></span>
        </h3>

        <!-- Resumen (excerpt) -->
        <div class="timeline-body">
            <p class="text-muted article-excerpt"></p>

            <!-- Contenido principal (oculto por defecto, expandible) -->
            <div class="article-content mt-3" style="display: none;"></div>
        </div>

        <!-- Footer con metadata -->
        <div class="timeline-footer text-muted small">
            <i class="fas fa-eye"></i>
            <span class="article-views"></span> vistas
            <span class="ml-3">
                <i class="fas fa-user"></i>
                <span class="article-author"></span>
            </span>
        </div>

        <!-- Botón Leer más -->
        <div class="mt-2">
            <button class="btn btn-sm btn-primary btn-read-more" data-id="">
                <i class="fas fa-chevron-down"></i> Leer más
            </button>
        </div>
    </div>
</div>
```

### ✅ Componente No-Articles (Estado Vacío)
```blade
<div id="template-no-articles" style="display: none;">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-inbox mr-2"></i>
                No hay artículos disponibles
            </h3>
        </div>
        <div class="card-body text-center py-5">
            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
            <p class="text-muted">
                No se encontraron artículos en el Centro de Ayuda.
                Intenta con otra búsqueda o categoría.
            </p>
        </div>
    </div>
</div>
```

---

## 📋 FASE 13: Lógica JavaScript Específica para Help Center

### ✅ Variables de Estado
```javascript
let currentPage = 1;
let currentCategory = '';      // Diferencia: category en lugar de type
let currentSearch = '';
const categoryColors = {
    'ACCOUNT_PROFILE': 'bg-blue',
    'SECURITY_PRIVACY': 'bg-red',
    'BILLING_PAYMENTS': 'bg-yellow',
    'TECHNICAL_SUPPORT': 'bg-green'
};
```

### ✅ Mapeo de Categorías a Iconos
```javascript
function getCategoryConfig(categoryCode) {
    const configs = {
        'ACCOUNT_PROFILE': {
            icon: 'fas fa-user',
            bgColor: 'bg-blue',
            badgeColor: 'badge-info',
            label: 'Cuenta y Perfil'
        },
        'SECURITY_PRIVACY': {
            icon: 'fas fa-shield-alt',
            bgColor: 'bg-red',
            badgeColor: 'badge-danger',
            label: 'Seguridad y Privacidad'
        },
        'BILLING_PAYMENTS': {
            icon: 'fas fa-credit-card',
            bgColor: 'bg-yellow',
            badgeColor: 'badge-warning',
            label: 'Facturación y Pagos'
        },
        'TECHNICAL_SUPPORT': {
            icon: 'fas fa-tools',
            bgColor: 'bg-green',
            badgeColor: 'badge-success',
            label: 'Soporte Técnico'
        }
    };
    return configs[categoryCode] || configs['ACCOUNT_PROFILE'];
}
```

### ✅ Función Cargar Artículos
```javascript
function loadArticles() {
    $('#loading-spinner').show();
    $('#timeline-content').hide().empty();
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
                renderTimeline(response.data);
                renderPagination(response.meta);
                $('#timeline-content').fadeIn();
            } else {
                renderEmptyState();
            }
        },
        error: function(xhr) {
            $('#loading-spinner').hide();
            toastr.error('Error al cargar los artículos');
            console.error(xhr);
        }
    });
}
```

### ✅ Función Renderizar Artículo
```javascript
function getArticleHtml(article) {
    let $template = $('#template-article-card').clone().removeAttr('id').show();

    const date = new Date(article.published_at).toLocaleDateString();
    const config = getCategoryConfig(article.category_id);  // O usar category code

    $template.find('.article-publish-date').text(date);
    $template.find('.article-category-badge').text(config.label);
    $template.find('.article-title').text(article.title);
    $template.find('.article-excerpt').text(article.excerpt || article.content.substring(0, 150));
    $template.find('.article-content').html(article.content);
    $template.find('.article-views').text(article.views_count || 0);
    $template.find('.article-author').text(article.author_name || 'Anónimo');
    $template.find('.btn-read-more').data('id', article.id);

    // Cambiar color de icono según categoría
    $template.find('i.fa-file-alt').addClass(config.bgColor);

    return $template;
}
```

### ✅ Event Listener Expandir Artículo
```javascript
$(document).on('click', '.btn-read-more', function() {
    const $button = $(this);
    const $content = $button.closest('.timeline-item').find('.article-content');

    if ($content.is(':visible')) {
        $content.slideUp();
        $button.html('<i class="fas fa-chevron-down"></i> Leer más');
    } else {
        $content.slideDown();
        $button.html('<i class="fas fa-chevron-up"></i> Mostrar menos');

        // Incrementar vistas cuando se expande (opcional)
        const articleId = $button.data('id');
        // Llamar a API para registrar vista si es necesario
    }
});
```

---

## 📋 FASE 14: Guía de Implementación Final

### ✅ Checklist de Implementación (Paso a Paso)

#### PASO 1: Crear Ruta Web
**Archivo**: `routes/web.php` (línea ~330, dentro del grupo USER)

```php
Route::get('/help-center', function () {
    $user = JWTHelper::getAuthenticatedUser();
    return view('app.user.help-center.index', [
        'user' => $user,
    ]);
})->name('user.help-center.index');
```

**Nota**: Agregar después de la ruta de `announcements`

---

#### PASO 2: Crear Directorio de Vistas
```
mkdir -p resources/views/app/user/help-center/
mkdir -p resources/views/components/articles/
```

---

#### PASO 3: Crear Vista Principal
**Archivo**: `resources/views/app/user/help-center/index.blade.php`

**Estructura base**:
```blade
@extends('layouts.authenticated')

@section('title', 'Centro de Ayuda')
@section('content_header', 'Artículos de Ayuda')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Filters Card -->
            <div class="card mb-3">
                <div class="card-body p-2">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <!-- Botones de categoría aquí -->
                        </div>
                        <div class="col-md-4">
                            <!-- Search input aquí -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Container -->
            <div id="articles-container">
                <!-- Loading, Timeline, Empty states aquí -->
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4" id="pagination-container"></div>
        </div>
    </div>
</div>

<!-- Templates -->
<div id="templates" style="display: none;">
    @include('components.articles.article-card')
    @include('components.articles.no-articles')
</div>

@endsection

@push('scripts')
<!-- JavaScript principal aquí -->
@endpush
```

---

#### PASO 4: Crear Componente article-card.blade.php
**Archivo**: `resources/views/components/articles/article-card.blade.php`

```blade
<div id="template-article-card" style="display: none;">
    <i class="fas fa-file-alt bg-info"></i>
    <div class="timeline-item">
        <span class="time">
            <i class="fas fa-clock"></i>
            <span class="article-publish-date"></span>
        </span>
        <span class="badge article-category-badge"></span>

        <h3 class="timeline-header">
            <span class="article-title font-weight-bold"></span>
        </h3>

        <div class="timeline-body">
            <p class="text-muted article-excerpt"></p>
            <div class="article-content mt-3" style="display: none;"></div>
        </div>

        <div class="timeline-footer text-muted small">
            <i class="fas fa-eye"></i>
            <span class="article-views"></span> vistas
            <span class="ml-3">
                <i class="fas fa-user"></i>
                <span class="article-author"></span>
            </span>
        </div>

        <div class="mt-2">
            <button class="btn btn-sm btn-primary btn-read-more" data-id="">
                <i class="fas fa-chevron-down"></i> Leer más
            </button>
        </div>
    </div>
</div>
```

---

#### PASO 5: Crear Componente no-articles.blade.php
**Archivo**: `resources/views/components/articles/no-articles.blade.php`

```blade
<div id="template-no-articles" style="display: none;">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-inbox mr-2"></i>
                No hay artículos disponibles
            </h3>
        </div>
        <div class="card-body text-center py-5">
            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
            <p class="text-muted">
                No se encontraron artículos en el Centro de Ayuda.
                Intenta con otra búsqueda o categoría.
            </p>
        </div>
    </div>
</div>
```

---

#### PASO 6: Agregar JavaScript Completo
**En**: `resources/views/app/user/help-center/index.blade.php` (dentro de @push('scripts'))

Ver sección **"FASE 13: Lógica JavaScript Específica"** para el código completo.

**Funciones principales**:
- `loadArticles()` - Carga desde API
- `renderTimeline(articles)` - Renderiza timeline
- `getArticleHtml(article)` - Genera HTML de artículo
- `renderPagination(meta)` - Paginación
- `renderEmptyState()` - Estado vacío
- Event listeners para filtros, búsqueda y expandir

---

#### PASO 7: Limpiar Cache de Rutas
```bash
docker exec -it helpdesk php artisan route:cache --clear
# O
docker exec -it helpdesk php artisan route:clear
```

**Importante**: Por instrucciones en CLAUDE.md

---

### ✅ Pruebas de Validación

**Estado Normal** (hay artículos):
- [ ] Verificar timeline carga correctamente
- [ ] Filtrar por cada categoría funciona
- [ ] Búsqueda devuelve resultados
- [ ] Paginación funciona
- [ ] Botón "Leer más" expande/contrae

**Estado Vacío** (sin artículos):
- [ ] Se muestra componente no-articles
- [ ] Mensaje es claro

**Estados de Carga**:
- [ ] Spinner inicial visible
- [ ] Spinner desaparece al cargar

**Error**:
- [ ] Si API falla, mostrar toastr error

---

## ✨ CONSOLIDACIÓN FINAL

**Estado**: ✅ Investigación completada - Guía de implementación lista

**Fases completadas:**
- ✅ FASE 1-9: Investigación exhaustiva de estructura
- ✅ FASE 10: Comparativa Announcements vs Help Center
- ✅ FASE 11-13: Especificación técnica detallada
- ✅ FASE 14: Guía de implementación paso a paso

**Archivos a crear:**
1. `routes/web.php` - Agregar 1 ruta
2. `resources/views/app/user/help-center/index.blade.php` - Vista principal
3. `resources/views/components/articles/article-card.blade.php` - Componente artículo
4. `resources/views/components/articles/no-articles.blade.php` - Componente vacío

**Archivos ya existentes**:
- ✅ API endpoints en `/api/help-center/articles`
- ✅ Modelos, Controladores, Services
- ✅ Enlace en sidebar `/app/user/help-center`

**Diferencias clave vs Announcements**:
- **Categorías fijas** (ACCOUNT_PROFILE, SECURITY_PRIVACY, BILLING_PAYMENTS, TECHNICAL_SUPPORT)
- **Sin metadata compleja** (estructura plana)
- **Track vistas** (views_count)
- **1 componente genérico** (vs 4 específicos)

---

**🎯 LISTA FINAL DE IMPLEMENTACIÓN**:
1. [ ] Crear ruta en web.php
2. [ ] Crear directorio help-center
3. [ ] Crear index.blade.php
4. [ ] Crear article-card.blade.php
5. [ ] Crear no-articles.blade.php
6. [ ] Agregar JavaScript completo
7. [ ] Limpiar cache de rutas
8. [ ] Probar en navegador
9. [ ] Validar todos los casos (normal, vacío, error)
