<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Services;

use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Events\ArticlePublished;
use App\Features\UserManagement\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleService
{
    /**
     * Crear un nuevo artículo del Help Center
     *
     * IMPORTANTE: Los artículos SIEMPRE se crean en estado DRAFT.
     * El campo 'action' se acepta pero se ignora en la creación.
     * Para publicar un artículo, se debe usar el endpoint de publicación después de la creación.
     *
     * @param array $data - Datos validados del request (category_id, title, excerpt, content, action)
     * @param string $companyId - Extraído del JWT (inmutable)
     * @param string $authorId - ID del usuario autenticado
     * @return HelpCenterArticle - Artículo creado
     * @throws Exception si la categoría no existe
     */
    public function createArticle(array $data, string $companyId, string $authorId): HelpCenterArticle
    {
        // Validar que la categoría existe
        $category = ArticleCategory::find($data['category_id']);
        if (!$category) {
            throw new Exception('Categoría no encontrada', 404);
        }

        // Generar excerpt si no se proporciona
        $excerpt = $data['excerpt'] ?? null;
        if (!$excerpt && isset($data['content'])) {
            $excerpt = substr($data['content'], 0, 150);
        }

        // IMPORTANTE: Los artículos SIEMPRE se crean como DRAFT
        // El campo 'action' es ignorado en la creación según test #10
        $status = 'DRAFT';
        $publishedAt = null;

        // Crear artículo
        $article = HelpCenterArticle::create([
            'company_id' => $companyId,
            'author_id' => $authorId,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'excerpt' => $excerpt,
            'content' => $data['content'],
            'status' => $status,
            'published_at' => $publishedAt,
            'views_count' => 0,
        ]);

        return $article;
    }

    /**
     * Actualizar un artículo existente del Help Center
     *
     * REGLAS IMPORTANTES:
     * 1. Solo se pueden actualizar campos proporcionados (partial update)
     * 2. company_id es INMUTABLE (validado por JWT)
     * 3. author_id es INMUTABLE (no cambia)
     * 4. published_at es INMUTABLE (preservado siempre)
     * 5. views_count es INMUTABLE (no se resetea)
     * 6. Title único POR EMPRESA (validado en Request)
     * 7. Solo admin de la misma empresa puede editar
     *
     * @param string $articleId - ID del artículo a actualizar
     * @param array $data - Datos validados del request (title?, content?, excerpt?, category_id?)
     * @param string $companyId - Extraído del JWT (inmutable)
     * @return HelpCenterArticle - Artículo actualizado
     * @throws Exception si el artículo no existe
     * @throws AuthorizationException si no es de su empresa
     */
    public function updateArticle(string $articleId, array $data, string $companyId): HelpCenterArticle
    {
        // Validar que el artículo existe
        $article = HelpCenterArticle::find($articleId);
        if (!$article) {
            throw new Exception('Artículo no encontrado', 404);
        }

        // Validar que el artículo pertenece a la empresa del usuario (cross-company check)
        if ($article->company_id !== $companyId) {
            throw new AuthorizationException('No tienes permiso para editar artículos de otra empresa');
        }

        // Validar que la categoría existe (si se está actualizando)
        if (isset($data['category_id'])) {
            $category = ArticleCategory::find($data['category_id']);
            if (!$category) {
                throw new Exception('Categoría no encontrada', 404);
            }
        }

        // Filtrar solo campos permitidos para actualizar
        // CAMPOS INMUTABLES que NO se pueden actualizar:
        // - company_id (inmutable, validado por JWT)
        // - author_id (inmutable, no cambia nunca)
        // - published_at (inmutable, preservado siempre)
        // - views_count (inmutable, no se resetea)
        // - status (no se actualiza directamente, requiere endpoints específicos)
        $allowedFields = ['title', 'content', 'excerpt', 'category_id'];
        $filteredData = collect($data)
            ->only($allowedFields)
            ->filter(fn ($value) => $value !== null)
            ->toArray();

        // Actualizar solo los campos proporcionados (partial update)
        $article->update($filteredData);

        // Refrescar para obtener datos actualizados
        $article->refresh();

        return $article;
    }

    /**
     * Publicar un artículo del Help Center
     *
     * REGLAS IMPORTANTES:
     * 1. Solo artículos en estado DRAFT pueden ser publicados
     * 2. published_at se establece a now()
     * 3. status cambia a PUBLISHED
     * 4. Dispara evento ArticlePublished
     * 5. Solo admin de la misma empresa puede publicar
     *
     * @param string $articleId - ID del artículo a publicar
     * @param string $companyId - Extraído del JWT (inmutable)
     * @return HelpCenterArticle - Artículo publicado
     * @throws Exception si el artículo no existe, no pertenece a la empresa, o ya está publicado
     */
    public function publishArticle(string $articleId, string $companyId): HelpCenterArticle
    {
        // Buscar artículo
        $article = HelpCenterArticle::find($articleId);
        if (!$article) {
            throw new Exception('Artículo no encontrado', 404);
        }

        // Validar que pertenece a la empresa del usuario
        if ($article->company_id !== $companyId) {
            throw new Exception('No tienes permiso para editar este artículo', 403);
        }

        // Validar que está en DRAFT
        if ($article->status === 'PUBLISHED') {
            throw new Exception('El artículo ya está publicado', 400);
        }

        // Actualizar a PUBLISHED
        $article->update([
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Disparar evento
        event(new ArticlePublished($article));

        return $article->refresh();
    }

    /**
     * Despublicar un artículo del Help Center
     *
     * REGLAS IMPORTANTES:
     * 1. Solo artículos en estado PUBLISHED pueden ser despublicados
     * 2. published_at se establece a null
     * 3. status cambia a DRAFT
     * 4. views_count se preserva (NO se resetea)
     * 5. Solo admin de la misma empresa puede despublicar
     *
     * @param string $articleId - ID del artículo a despublicar
     * @param string $companyId - Extraído del JWT (inmutable)
     * @return HelpCenterArticle - Artículo despublicado
     * @throws Exception si el artículo no existe, no pertenece a la empresa, o no está publicado
     */
    public function unpublishArticle(string $articleId, string $companyId): HelpCenterArticle
    {
        // Buscar artículo
        $article = HelpCenterArticle::find($articleId);
        if (!$article) {
            throw new Exception('Artículo no encontrado', 404);
        }

        // Validar que pertenece a la empresa del usuario
        if ($article->company_id !== $companyId) {
            throw new Exception('No tienes permiso para editar este artículo', 403);
        }

        // Validar que está en PUBLISHED
        if ($article->status === 'DRAFT') {
            throw new Exception('El artículo no está publicado', 400);
        }

        // Actualizar a DRAFT
        $article->update([
            'status' => 'DRAFT',
            'published_at' => null,
        ]);

        return $article->refresh();
    }

    /**
     * Eliminar un artículo del Help Center (soft delete)
     *
     * REGLAS IMPORTANTES:
     * 1. Solo artículos en estado DRAFT pueden ser eliminados
     * 2. Soft delete (no borrar permanentemente, marcar deleted_at)
     * 3. Solo admin de la misma empresa puede eliminar
     * 4. Lanza excepción si está PUBLISHED
     * 5. Lanza excepción si no pertenece a la empresa
     *
     * @param string $articleId - ID del artículo a eliminar
     * @param string $companyId - Extraído del JWT (inmutable)
     * @return void
     * @throws Exception si no puede eliminar
     */
    public function deleteArticle(string $articleId, string $companyId): void
    {
        // 1. Buscar artículo
        $article = HelpCenterArticle::find($articleId);
        if (!$article) {
            throw new Exception('Artículo no encontrado', 404);
        }

        // 2. Validar que pertenece a la empresa del usuario
        if ($article->company_id !== $companyId) {
            throw new Exception('No tienes permiso para eliminar este artículo', 403);
        }

        // 3. Validar que está en DRAFT (no se puede eliminar PUBLISHED)
        if ($article->status === 'PUBLISHED') {
            throw new Exception('No se puede eliminar un artículo publicado', 403);
        }

        // 4. Soft delete (Laravel automáticamente marca deleted_at)
        $article->delete();
    }

    /**
     * Listar artículos del Help Center con filtros, búsqueda y paginación
     *
     * REGLAS DE VISIBILIDAD:
     * - END_USER (USER role): Ve SOLO artículos PUBLISHED de empresas que sigue
     * - COMPANY_ADMIN: Ve TODOS los artículos de su empresa (DRAFT + PUBLISHED)
     * - PLATFORM_ADMIN: Ve TODOS los artículos de TODAS las empresas
     *
     * @param User $user - Usuario autenticado
     * @param array $filters - Parámetros de query validados
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws Exception si hay validaciones fallidas
     */
    public function listArticles(User $user, array $filters): LengthAwarePaginator
    {
        // 1. DETERMINAR QUERY BASE SEGÚN ROL
        $query = HelpCenterArticle::query();

        // Obtener company_id solicitado (si existe)
        $requestedCompanyId = $filters['company_id'] ?? null;

        // 2. VALIDACIÓN CROSS-COMPANY Y DETERMINACIÓN DE EMPRESAS
        if ($user->hasRole('PLATFORM_ADMIN')) {
            // PLATFORM_ADMIN: Ve TODO sin restricción
            // Si especifica company_id, filtra por esa empresa
            if ($requestedCompanyId) {
                $query->where('company_id', $requestedCompanyId);
            }
            // Sin restricción de status (ve todos por defecto)

        } elseif ($user->hasRole('COMPANY_ADMIN')) {
            // COMPANY_ADMIN: Solo su empresa
            $adminRole = $user->userRoles()
                ->where('role_code', 'COMPANY_ADMIN')
                ->first();

            if (!$adminRole || !$adminRole->company_id) {
                throw new Exception('Usuario no tiene empresa asignada', 500);
            }

            $adminCompanyId = $adminRole->company_id;

            // Si especifica ?company_id, validar que sea su empresa
            if ($requestedCompanyId && $requestedCompanyId !== $adminCompanyId) {
                throw new Exception('No tienes permiso para acceder a artículos de otra empresa', 403);
            }

            $query->where('company_id', $adminCompanyId);

            // Status default ADMIN: todos (DRAFT + PUBLISHED)
            if (!isset($filters['status'])) {
                // No agregar WHERE para status (ve todos)
            } else {
                // Si especifica status, filtrar por eso
                $status = strtoupper($filters['status']);
                $query->where('status', $status);
            }

        } else {
            // END_USER (USER role): Solo empresas que sigue + PUBLISHED
            // Usar select para especificar exactamente qué columna queremos
            $followedCompanyIds = $user->followedCompanies()
                ->select('business.companies.id')
                ->pluck('business.companies.id')
                ->toArray();

            // Si especifica ?company_id, validar que la siga
            if ($requestedCompanyId) {
                if (!in_array($requestedCompanyId, $followedCompanyIds)) {
                    throw new Exception('No tienes permiso para acceder a esta empresa', 403);
                }
                $query->where('company_id', $requestedCompanyId);
            } else {
                // Sin company_id especificado, ver todas las que sigue
                if (!empty($followedCompanyIds)) {
                    $query->whereIn('company_id', $followedCompanyIds);
                } else {
                    // Usuario no sigue ninguna empresa, retornar vacío
                    $query->where('company_id', null);
                }
            }

            // Status default END_USER: PUBLISHED (hardcoded)
            // END_USER NUNCA puede ver DRAFT
            $query->where('status', 'PUBLISHED');
        }

        // 3. FILTRO POR CATEGORÍA
        if (isset($filters['category'])) {
            $categoryId = ArticleCategory::where('code', $filters['category'])
                ->first()
                ?->id;

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
        }

        // 4. BÚSQUEDA (title + content)
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('content', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // 5. ORDENAMIENTO
        if (isset($filters['sort'])) {
            $sort = $filters['sort'];

            if ($sort === '-views') {
                $query->orderBy('views_count', 'DESC');
            } elseif ($sort === 'views') {
                $query->orderBy('views_count', 'ASC');
            } elseif ($sort === 'title') {
                $query->orderBy('title', 'ASC');
            } elseif ($sort === '-title') {
                $query->orderBy('title', 'DESC');
            } elseif ($sort === '-created_at') {
                $query->orderBy('created_at', 'DESC');
            } elseif ($sort === 'created_at') {
                $query->orderBy('created_at', 'ASC');
            }
        } else {
            // Default: created_at DESC
            $query->orderBy('created_at', 'DESC');
        }

        // 6. PAGINACIÓN
        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100); // Max 100

        return $query->paginate($perPage);
    }
}