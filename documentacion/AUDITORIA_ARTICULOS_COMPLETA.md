# Auditoría Completa: Sistema de Artículos Help Center

## 1. Estructura de Código

### Modelos
- **HelpCenterArticle** (`app/Features/ContentManagement/Models/HelpCenterArticle.php`)
  - Traits: `HasFactory`, `HasUuid`, `SoftDeletes`
  - Relaciones: `belongsTo(Company)`, `belongsTo(ArticleCategory)`, `belongsTo(User, 'author_id')`
  - Scopes: `published()`, `byCategory()`, `search()`
  - Métodos: `incrementViews()`, `formattedPublishedDate()`

- **ArticleCategory** (`app/Features/ContentManagement/Models/ArticleCategory.php`)
  - Traits: `HasFactory`, `HasUuid`
  - Relación: `hasMany(HelpCenterArticle)`
  - 4 categorías fijas globales (no company-specific)

### Servicio Principal
- **ArticleService** (`app/Features/ContentManagement/Services/ArticleService.php`)
  - `createArticle()` - Crea siempre en DRAFT
  - `updateArticle()` - Partial update (campos inmutables: company_id, author_id, published_at, views_count, status)
  - `publishArticle()` - DRAFT → PUBLISHED, dispara evento
  - `unpublishArticle()` - PUBLISHED → DRAFT, preserva views
  - `deleteArticle()` - Solo DRAFT, soft delete
  - `viewArticle()` - Incrementa views si USER + PUBLISHED
  - `listArticles()` - Con filtros, búsqueda, visibilidad por rol

- **ArticleCategoryService** (`app/Features/ContentManagement/Services/ArticleCategoryService.php`)
  - `getAllCategories()` - Retorna 4 categorías ordenadas

### Controlador
- **ArticleController** (`app/Features/ContentManagement/Http/Controllers/ArticleController.php`)
  - Métodos: `index`, `show`, `store`, `update`, `publish`, `unpublish`, `destroy`
  - Delega todo a ArticleService
  - Manejo de excepciones con códigos HTTP

---

## 2. Base de Datos

### Tabla: `business.help_center_articles`

| Campo | Tipo | Nullable | Restricciones |
|-------|------|----------|---------------|
| id | uuid | NO | PK |
| company_id | uuid | NO | FK → business.companies (CASCADE) |
| author_id | uuid | YES | FK → auth.users (SET NULL) |
| category_id | uuid | NO | FK → article_categories (RESTRICT) |
| title | varchar(255) | NO | |
| excerpt | varchar(500) | YES | Auto-generado si nulo |
| content | text | NO | |
| status | enum | NO | `DRAFT`, `PUBLISHED` |
| views_count | integer | NO | Default: 0 |
| published_at | timestamp | YES | |
| created_at | timestamp | NO | |
| updated_at | timestamp | NO | |
| deleted_at | timestamp | YES | Soft delete |

**Índices:** `company_id`, `(company_id, status)`, `category_id`, `status`, `views_count`, `published_at`

### Tabla: `article_categories` (pública)

| Campo | Tipo | Nullable | Restricciones |
|-------|------|----------|---------------|
| id | uuid | NO | PK |
| code | varchar(50) | NO | UNIQUE |
| name | varchar(255) | NO | |
| description | text | YES | |
| created_at | timestamp | NO | |
| updated_at | timestamp | NO | |

**Categorías Seeded:**
1. `ACCOUNT_PROFILE` - Account & Profile
2. `SECURITY_PRIVACY` - Security & Privacy
3. `BILLING_PAYMENTS` - Billing & Payments
4. `TECHNICAL_SUPPORT` - Technical Support

---

## 3. Validaciones

### StoreArticleRequest
```
category_id:  required, uuid, exists:article_categories,id
title:        required, string, min:3, max:255
excerpt:      nullable, string, max:500
content:      required, string, min:50, max:20000
action:       nullable, in:draft,publish (IGNORADO - siempre crea DRAFT)
```

### UpdateArticleRequest
```
title:        sometimes, string, min:3, max:255, unique(help_center_articles,title) donde company_id
content:      sometimes, string, min:50, max:20000
category_id:  sometimes, uuid, exists:article_categories,id
excerpt:      nullable, string, max:500
```

### ListArticleRequest (query params)
```
company_id:   sometimes, uuid, exists:companies,id
category:     sometimes, string, in:ACCOUNT_PROFILE,SECURITY_PRIVACY,BILLING_PAYMENTS,TECHNICAL_SUPPORT
status:       sometimes, string, in:draft,published
search:       sometimes, string, max:255
sort:         sometimes, string, in:-views,views,-created_at,created_at,title,-title
page:         sometimes, integer, min:1
per_page:     sometimes, integer, min:1, max:100 (default: 20)
```

---

## 4. Transformación de Respuestas (Resources)

### ArticleResource
```json
{
  "id": "uuid",
  "company_id": "uuid",
  "company": { "id", "name", "logo_url" },
  "author_id": "uuid|null",
  "author_name": "string|null",
  "category_id": "uuid",
  "category": { "id", "code", "name" },
  "title": "string",
  "excerpt": "string",
  "content": "string",
  "status": "DRAFT|PUBLISHED",
  "views_count": "integer",
  "published_at": "ISO8601|null",
  "created_at": "ISO8601",
  "updated_at": "ISO8601"
}
```

### ArticleCategoryResource
```json
{
  "id": "uuid",
  "code": "string",
  "name": "string",
  "description": "string|null"
}
```

---

## 5. Control de Acceso y Visibilidad por Rol

### ViewArticle (GET /api/help-center/articles/{id})

| Rol | DRAFT | PUBLISHED | Views +1 |
|-----|-------|-----------|----------|
| PLATFORM_ADMIN | ✓ TODO | ✓ TODO | ✗ |
| COMPANY_ADMIN | ✓ Su empresa | ✓ Su empresa | ✗ |
| AGENT | ✗ | ✓ Su empresa | ✗ |
| USER | ✗ | ✓ Empresas seguidas | ✓ |

### ListArticles (GET /api/help-center/articles)

| Rol | Query Base | Status Default | Filtro company_id |
|-----|-----------|-----------------|-------------------|
| PLATFORM_ADMIN | - | - (ve ambos) | ✓ |
| COMPANY_ADMIN | WHERE company_id=? | DRAFT+PUBLISHED | ✗ (su empresa) |
| AGENT | WHERE company_id=? | PUBLISHED solo | ✗ (su empresa) |
| USER | WHERE company_id IN (followed) | PUBLISHED solo | ✗ (sus empresas) |

**Búsqueda:** Case-insensitive en `title` y `content` (compatible ñ/acentos)

---

## 6. Operaciones y Reglas de Negocio

### CREATE: `POST /api/help-center/articles`
- **Requiere:** COMPANY_ADMIN
- **Validación:** StoreArticleRequest
- **Lógica:**
  - Status: SIEMPRE `DRAFT` (ignora campo 'action')
  - company_id: Del JWT (inmutable)
  - author_id: Usuario autenticado
  - excerpt: Auto-generado si nulo (primeros 150 chars de content)
  - views_count: 0
  - published_at: null
- **Retorna:** ArticleResource (201)

### UPDATE: `PUT /api/help-center/articles/{id}`
- **Requiere:** COMPANY_ADMIN de su empresa
- **Validación:** UpdateArticleRequest
- **Campos Inmutables:** company_id, author_id, published_at, views_count, status
- **Lógica:** Partial update, solo campos enviados
- **Retorna:** ArticleResource

### PUBLISH: `POST /api/help-center/articles/{id}/publish`
- **Requiere:** COMPANY_ADMIN de su empresa
- **Pre-requisito:** Status = DRAFT
- **Lógica:** DRAFT → PUBLISHED, published_at = now()
- **Efecto:** Dispara evento ArticlePublished
- **Errores:** 400 si ya PUBLISHED, 404 si no existe
- **Retorna:** ArticleResource

### UNPUBLISH: `POST /api/help-center/articles/{id}/unpublish`
- **Requiere:** COMPANY_ADMIN de su empresa
- **Pre-requisito:** Status = PUBLISHED
- **Lógica:** PUBLISHED → DRAFT, published_at = null
- **Importante:** views_count se preserva (no resetea)
- **Errores:** 400 si está DRAFT
- **Retorna:** ArticleResource

### DELETE: `DELETE /api/help-center/articles/{id}`
- **Requiere:** COMPANY_ADMIN de su empresa
- **Pre-requisito:** Status = DRAFT
- **Lógica:** Soft delete (deleted_at)
- **Errores:** 403 si PUBLISHED, 404 si no existe
- **Retorna:** `{success: true, message: "..."}`

### VIEW: `GET /api/help-center/articles/{id}`
- **Visibilidad:** Según rol (ver tabla arriba)
- **Side Effect:** views_count+1 si:
  - Status = PUBLISHED
  - Rol = USER
  - Usuario sigue la empresa
- **Retorna:** ArticleResource

### LIST: `GET /api/help-center/articles`
- **Paginación:** Default 20, max 100 por página
- **Ordenamiento:** Default `-created_at` (más recientes)
- **Filtros:**
  - `category`: Por code (ACCOUNT_PROFILE, etc.)
  - `status`: draft o published
  - `search`: En title y content
  - `sort`: -views, views, -created_at, created_at, title, -title
  - `company_id`: Solo PLATFORM_ADMIN
- **Visibilidad:** Según rol (ver tabla arriba)
- **Retorna:** ArticleResource[] + paginación

---

## 7. Datos de Ejemplo

### HelpCenterArticleFactory
```php
company_id:   Company::factory()
author_id:    User::factory()
category_id:  ArticleCategory::factory()
title:        $faker->sentence()
excerpt:      $faker->sentence()
content:      $faker->paragraphs(5, true)
status:       'DRAFT'
views_count:  0
published_at: null
```

**Estados especiales:**
- `->published()` - status='PUBLISHED', published_at=now()
- `->withViews(n)` - views_count=n

### Seeders (5 empresas)
- PilAndinaHelpCenterArticlesSeeder
- BancoFassilHelpCenterArticlesSeeder
- YPFBHelpCenterArticlesSeeder
- TigoHelpCenterArticlesSeeder
- CBNHelpCenterArticlesSeeder

**Patrón:** Buscan empresa por nombre, crean artículos con author_id=company.admin_user_id

---

## 8. Eventos

### ArticlePublished
- **Disparado:** Cuando se publica un artículo (publishArticle)
- **Contenedor:** Datos completos del artículo
- **Ubicación:** `app/Features/ContentManagement/Events/ArticlePublished.php`

---

## 9. Enums y Tipos

### ArticleStatus (ENUM DB)
```
DRAFT
PUBLISHED
```

### ArticleCategory Codes
```
ACCOUNT_PROFILE
SECURITY_PRIVACY
BILLING_PAYMENTS
TECHNICAL_SUPPORT
```

---

## 10. Rutas REST

| Método | Ruta | Action | Requiere |
|--------|------|--------|----------|
| GET | `/api/help-center/articles` | index | JWT |
| GET | `/api/help-center/articles/{id}` | show | JWT |
| POST | `/api/help-center/articles` | store | JWT + COMPANY_ADMIN |
| PUT | `/api/help-center/articles/{id}` | update | JWT + COMPANY_ADMIN |
| POST | `/api/help-center/articles/{id}/publish` | publish | JWT + COMPANY_ADMIN |
| POST | `/api/help-center/articles/{id}/unpublish` | unpublish | JWT + COMPANY_ADMIN |
| DELETE | `/api/help-center/articles/{id}` | destroy | JWT + COMPANY_ADMIN |

**Base:** `routes/api.php`

---

## 11. Ciclo de Vida de un Artículo

```
CREATE (DRAFT)
    ↓
UPDATE (aún DRAFT)
    ↓
PUBLISH (→ PUBLISHED)
    ↓
VIEW (views incrementa si USER)
    ↓
UNPUBLISH (→ DRAFT)
    ↓
UPDATE (aún DRAFT)
    ↓
DELETE (soft delete)
```

**Restricciones:**
- Solo se publica desde DRAFT
- Solo se despublica desde PUBLISHED
- Solo se elimina en estado DRAFT
- author_id y company_id no cambian

---

## 12. Validaciones Especiales

- **Title:** Única por empresa (no puede repetir dentro de la misma company)
- **Content:** 50-20000 caracteres (protección contra contenido vacío o excesivo)
- **Excerpt:** Auto-generado si nulo, máx 500 caracteres
- **Categoría:** Debe existir en las 4 globales (no se pueden crear nuevas)
- **Status:** Solo cambia vía publicar/despublicar
- **Views:** No decrece, no resettea al despublicar

---

## 13. Resumen de Campos CRUD

### CREATE
✓ category_id, title, excerpt (opt), content
✗ Ignorar: id, company_id (auto), author_id (auto), status (siempre DRAFT), views_count (0), published_at (null)

### UPDATE
✓ title, content, category_id, excerpt
✗ Inmutables: company_id, author_id, published_at, views_count, status

### PUBLISH
- No requiere payload
- Pre-requisito: status=DRAFT

### UNPUBLISH
- No requiere payload
- Pre-requisito: status=PUBLISHED

### DELETE
- No requiere payload
- Pre-requisito: status=DRAFT

---

## 14. Notas de Implementación

1. **Excerpt auto-generado:** Si no se proporciona, toma primeros 150 caracteres de content
2. **Búsqueda:** Case-insensitive, compatible con ñ y acentos
3. **Soft Deletes:** Los artículos eliminados no aparecen en listados
4. **Views:** Solo incrementa para rol USER en artículos PUBLISHED de empresas seguidas
5. **Factory:** Todos los artículos creados con factory inician en estado DRAFT
6. **Transacciones:** Las operaciones de publish/unpublish usan transacciones DB
7. **Eventos:** ArticlePublished se dispara solo al publicar desde DRAFT

---

## Archivos Clave

| Archivo | Propósito |
|---------|-----------|
| `app/Features/ContentManagement/Models/HelpCenterArticle.php` | Modelo principal |
| `app/Features/ContentManagement/Models/ArticleCategory.php` | Modelo categorías |
| `app/Features/ContentManagement/Services/ArticleService.php` | Lógica de negocio |
| `app/Features/ContentManagement/Http/Controllers/ArticleController.php` | Endpoints REST |
| `app/Features/ContentManagement/Http/Requests/Articles/StoreArticleRequest.php` | Validación CREATE |
| `app/Features/ContentManagement/Http/Requests/Articles/UpdateArticleRequest.php` | Validación UPDATE |
| `app/Features/ContentManagement/Http/Requests/Articles/ListArticleRequest.php` | Validación LIST |
| `app/Features/ContentManagement/Http/Resources/ArticleResource.php` | Transformación JSON |
| `app/Features/ContentManagement/Database/Factories/HelpCenterArticleFactory.php` | Factory test |
| `app/Features/ContentManagement/Events/ArticlePublished.php` | Evento publicación |
