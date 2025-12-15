<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Area;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * AreaService - Lógica de negocio para áreas/departamentos
 *
 * Responsabilidades:
 * - Crear áreas con validaciones de negocio
 * - Actualizar áreas preservando campos no modificados
 * - Eliminar áreas (solo si no tienen tickets activos)
 * - Listar áreas con filtros y conteo de tickets activos (paginadas)
 */
class AreaService
{
    /**
     * Crea una nueva área
     *
     * @param array $data Datos validados del área
     * @param string $companyId ID de la empresa (del JWT)
     * @return Area
     */
    public function create(array $data, string $companyId): Area
    {
        // Agregar company_id del JWT (inmutable, ignora payload)
        $data['company_id'] = $companyId;

        // Establecer is_active por defecto en true si no se provee
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // Crear área
        $area = Area::create($data);

        return $area;
    }

    /**
     * Actualiza un área existente
     *
     * Solo actualiza los campos provistos, preserva los demás.
     *
     * @param Area $area
     * @param array $data Datos validados a actualizar
     * @return Area
     */
    public function update(Area $area, array $data): Area
    {
        // Actualizar solo campos presentes
        $area->update($data);

        return $area->fresh();
    }

    /**
     * Elimina un área
     *
     * Valida que no tenga tickets activos antes de eliminar.
     * Tickets activos = status IN (OPEN, PENDING, RESOLVED)
     * Tickets CLOSED no cuentan como activos.
     *
     * @param Area $area
     * @return bool
     * @throws \Exception Si el área tiene tickets activos
     */
    public function delete(Area $area): bool
    {
        // Contar tickets activos (excluyendo CLOSED)
        $activeTicketsCount = $area->tickets()
            ->whereIn('status', ['open', 'pending', 'resolved'])
            ->count();

        if ($activeTicketsCount > 0) {
            throw new \Exception("Cannot delete area with {$activeTicketsCount} active tickets");
        }

        // Eliminar área (no soft delete, eliminación física)
        return $area->delete();
    }

    /**
     * Lista áreas de una empresa con filtrado opcional y paginación
     *
     * @param string $companyId ID de la empresa
     * @param bool|null $isActive Filtrar por estado activo/inactivo (null = todos)
     * @param int $perPage Número de items por página (default 15)
     * @return LengthAwarePaginator
     */
    public function list(string $companyId, ?bool $isActive = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Area::query()
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
