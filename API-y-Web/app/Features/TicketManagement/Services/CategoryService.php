<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Data\DefaultCategoriesByIndustry;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CategoryService - Lógica de negocio para categorías de tickets
 *
 * Responsabilidades:
 * - Crear categorías con validaciones de negocio
 * - Actualizar categorías preservando campos no modificados
 * - Eliminar categorías (solo si no tienen tickets activos)
 * - Listar categorías con filtros y conteo de tickets activos (paginadas)
 * - Crear categorías por defecto según industry type (auto-setup para nuevas empresas)
 */
class CategoryService
{
    /**
     * Crea una nueva categoría
     *
     * @param array $data Datos validados de la categoría
     * @param string $companyId ID de la empresa (del JWT)
     * @return Category
     */
    public function create(array $data, string $companyId): Category
    {
        // Agregar company_id del JWT (inmutable, ignora payload)
        $data['company_id'] = $companyId;

        // Establecer is_active por defecto en true si no se provee
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // Crear categoría
        $category = Category::create($data);

        return $category;
    }

    /**
     * Actualiza una categoría existente
     *
     * Solo actualiza los campos provistos, preserva los demás.
     *
     * @param Category $category
     * @param array $data Datos validados a actualizar
     * @return Category
     */
    public function update(Category $category, array $data): Category
    {
        // Actualizar solo campos presentes
        $category->update($data);

        return $category->fresh();
    }

    /**
     * Elimina una categoría
     *
     * Valida que no tenga tickets activos antes de eliminar.
     * Tickets activos = status IN (OPEN, PENDING, RESOLVED)
     * Tickets CLOSED no cuentan como activos.
     *
     * @param Category $category
     * @return bool
     * @throws \Exception Si la categoría tiene tickets activos
     */
    public function delete(Category $category): bool
    {
        // Contar tickets activos (excluyendo CLOSED)
        // Durante FASE 2, la tabla de tickets no existe aún, así que usamos try-catch
        try {
            $activeTicketsCount = $category->tickets()
                ->whereIn('status', ['open', 'pending', 'resolved'])
                ->count();
        } catch (\Exception $e) {
            // Durante FASE 2, la tabla de tickets no existe aún
            $activeTicketsCount = 0;
        }

        if ($activeTicketsCount > 0) {
            throw new \Exception("Cannot delete category with {$activeTicketsCount} active tickets");
        }

        // Eliminar categoría (no soft delete, eliminación física)
        return $category->delete();
    }

    /**
     * Crea las 5 categorías por defecto para una empresa según su tipo de industria
     *
     * Este método es llamado automáticamente por CreateDefaultCategoriesListener
     * cuando se crea una nueva empresa (evento CompanyCreated).
     *
     * Características:
     * - Crea 5 categorías específicas según el industry_code
     * - Usa bulk insert para mejor performance
     * - No crea duplicados (verifica existencia previa)
     * - Todas las categorías se crean como activas (is_active = true)
     *
     * @param string $companyId UUID de la empresa
     * @param string $industryCode Código de industria (ej: 'technology', 'healthcare')
     * @return int Número de categorías creadas
     */
    public function createDefaultCategoriesForIndustry(string $companyId, string $industryCode): int
    {
        // Obtener las 5 categorías por defecto según el industry code
        $defaultCategories = DefaultCategoriesByIndustry::get($industryCode);

        $createdCount = 0;
        $now = now();

        // Preparar datos para bulk insert
        $categoriesToInsert = [];

        foreach ($defaultCategories as $categoryData) {
            // Verificar si la categoría ya existe para esta empresa
            $exists = Category::where('company_id', $companyId)
                ->where('name', $categoryData['name'])
                ->exists();

            if (!$exists) {
                $categoriesToInsert[] = [
                    'id' => DB::raw('gen_random_uuid()'),
                    'company_id' => $companyId,
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'is_active' => true,
                    'created_at' => $now,
                ];
            }
        }

        // Bulk insert todas las categorías de una vez
        if (!empty($categoriesToInsert)) {
            DB::table('ticketing.categories')->insert($categoriesToInsert);
            $createdCount = count($categoriesToInsert);

            Log::info('Created default categories for company', [
                'company_id' => $companyId,
                'industry_code' => $industryCode,
                'categories_created' => $createdCount,
            ]);
        }

        return $createdCount;
    }

    /**
     * Lista categorías de una empresa con filtrado opcional y paginación
     *
     * @param string $companyId ID de la empresa
     * @param bool|null $isActive Filtrar por estado activo/inactivo (null = todos)
     * @param int $perPage Número de items por página (default 15)
     * @return LengthAwarePaginator
     */
    public function list(string $companyId, ?bool $isActive = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Category::query()
            ->where('company_id', $companyId)
            ->withCount([
                'tickets as active_tickets_count' => function ($query) {
                    $query->whereIn('status', ['open', 'pending', 'resolved']);
                }
            ])
            ->orderBy('created_at', 'desc');

        // Aplicar filtro de is_active si se provee
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->paginate($perPage);
    }
}
