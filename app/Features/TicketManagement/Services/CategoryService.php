<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Category;
use Illuminate\Database\Eloquent\Collection;

/**
 * CategoryService - Lógica de negocio para categorías de tickets
 *
 * Responsabilidades:
 * - Crear categorías con validaciones de negocio
 * - Actualizar categorías preservando campos no modificados
 * - Eliminar categorías (solo si no tienen tickets activos)
 * - Listar categorías con filtros y conteo de tickets activos
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
     * Lista categorías de una empresa con filtrado opcional
     *
     * @param string $companyId ID de la empresa
     * @param bool|null $isActive Filtrar por estado activo/inactivo (null = todos)
     * @return Collection
     */
    public function list(string $companyId, ?bool $isActive = null): Collection
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

        return $query->get();
    }
}
