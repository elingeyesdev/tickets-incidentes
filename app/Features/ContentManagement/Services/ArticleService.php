<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Services;

use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;

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
}