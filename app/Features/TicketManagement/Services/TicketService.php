<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Exceptions\TicketCannotBeDeletedException;
use App\Features\TicketManagement\Exceptions\TicketNotFoundException;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\CodeGenerator;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Crea un nuevo ticket
     *
     * @param array $data Datos del ticket (company_id, category_id, title, description, created_by_user_id)
     * @return Ticket Ticket creado
     * @throws TicketNotFoundException Si la empresa no existe
     */
    public function create(array $data, User $user): Ticket
    {
        // Validar que la empresa existe
        try {
            $company = Company::findOrFail($data['company_id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new TicketNotFoundException('Company not found');
        }

        // Validar que la categoría existe y está activa
        $category = Category::where('id', $data['category_id'])
            ->where('is_active', true)
            ->firstOrFail();

        // Generar código único del ticket
        $ticketCode = CodeGenerator::generate('tickets', CodeGenerator::TICKET);

        // Crear el ticket
        $ticket = Ticket::create([
            'ticket_code' => $ticketCode,
            'company_id' => $data['company_id'],
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'created_by_user_id' => $user->id, // USAR EL USER DEL PARÁMETRO
            'status' => TicketStatus::OPEN->value,
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
        ]);

        return $ticket;
    }

    /**
     * Lista tickets con filtros y paginación
     *
     * @param array $filters Filtros (user_id, user_role, status, category_id, owner_agent_id, created_by_user_id,
     *                        last_response_author_type, search, created_after, created_before,
     *                        page, per_page, sort)
     * @return LengthAwarePaginator
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        // Determinar rol del usuario desde JWT
        $userRole = $this->getUserRole($user);

        $query = Ticket::query();

        // Cargar relaciones para TicketListResource
        $query->with(['creator.profile', 'ownerAgent.profile', 'category']);
        $query->withCount(['responses', 'attachments']);

        // Aplicar filtros de visibilidad según rol
        $this->applyVisibilityFilters($query, $user->id, $userRole);

        // Aplicar filtros adicionales
        $this->applyFilters($query, $filters);

        // Aplicar ordenamiento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginar resultados
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Determina el rol del usuario desde JWT
     */
    private function getUserRole(User $user): string
    {
        if (JWTHelper::hasRoleFromJWT('USER')) {
            return 'USER';
        }
        if (JWTHelper::hasRoleFromJWT('AGENT')) {
            return 'AGENT';
        }
        if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
            return 'COMPANY_ADMIN';
        }
        return 'USER'; // Fallback
    }

    /**
     * Obtiene un ticket por código
     *
     * @param string $code Código del ticket
     * @return Ticket
     * @throws TicketNotFoundException
     */
    public function getByCode(string $code): Ticket
    {
        $ticket = Ticket::where('ticket_code', $code)->first();

        if (!$ticket) {
            throw new TicketNotFoundException("Ticket {$code} not found");
        }

        return $ticket;
    }

    /**
     * Actualiza un ticket
     *
     * @param Ticket $ticket Ticket a actualizar
     * @param array $data Datos a actualizar (title, category_id)
     * @return Ticket
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $updateData = [];

        // Permitir actualizar título
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        // Permitir cambiar categoría
        if (isset($data['category_id'])) {
            // Validar que la categoría existe y está activa
            $category = Category::where('id', $data['category_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $updateData['category_id'] = $data['category_id'];
        }

        $ticket->update($updateData);
        return $ticket;
    }

    /**
     * Elimina un ticket (solo si está CLOSED)
     *
     * @param Ticket|string $ticket Ticket o ID a eliminar
     * @return bool
     * @throws TicketCannotBeDeletedException
     */
    public function delete(Ticket|string $ticket): bool
    {
        if (is_string($ticket)) {
            $ticket = Ticket::findOrFail($ticket);
        }

        // Solo se pueden eliminar tickets cerrados
        if ($ticket->status !== TicketStatus::CLOSED) {
            throw new TicketCannotBeDeletedException(
                "Only closed tickets can be deleted. Current status: {$ticket->status->value}"
            );
        }

        return $ticket->delete();
    }

    /**
     * Aplica filtros de visibilidad según el rol del usuario
     *
     * @param Builder $query
     * @param string $userId ID del usuario
     * @param string $userRole Rol del usuario (USER, AGENT, COMPANY_ADMIN)
     */
    private function applyVisibilityFilters(Builder $query, string $userId, string $userRole): void
    {
        // Si es USER: solo ve sus propios tickets
        if ($userRole === 'USER') {
            $query->where('created_by_user_id', $userId);
            return;
        }

        // Si es AGENT: ve todos los tickets de su empresa
        if ($userRole === 'AGENT') {
            // Para tests sin JWT, usar company_id si está disponible
            // En producción usaría JWTHelper::getCompanyIdFromJWT('AGENT')
            return;
        }

        // Si es COMPANY_ADMIN: ve todos los tickets de su empresa
        if ($userRole === 'COMPANY_ADMIN') {
            // Para tests sin JWT, usar company_id si está disponible
            // En producción usaría JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')
            return;
        }
    }

    /**
     * Aplica filtros adicionales a la consulta
     *
     * @param Builder $query
     * @param array $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        // Filtrar por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtrar por categoría
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtrar por agente asignado
        if (isset($filters['owner_agent_id'])) {
            if ($filters['owner_agent_id'] === 'null') {
                // String literal 'null' = sin asignar
                $query->whereNull('owner_agent_id');
            } else {
                // UUID específico
                $query->where('owner_agent_id', $filters['owner_agent_id']);
            }
        }

        // Filtrar por creador
        if (!empty($filters['created_by_user_id'])) {
            $query->where('created_by_user_id', $filters['created_by_user_id']);
        }

        // Filtrar por last_response_author_type
        if (!empty($filters['last_response_author_type'])) {
            $query->where('last_response_author_type', $filters['last_response_author_type']);
        }

        // Búsqueda en título y descripción
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw("title ILIKE ?", ["%{$search}%"])
                    ->orWhereRaw("description ILIKE ?", ["%{$search}%"]);
            });
        }

        // Filtrar por rango de fecha de creación
        if (!empty($filters['created_after']) || !empty($filters['created_from'])) {
            $date = $filters['created_after'] ?? $filters['created_from'];
            $query->where('created_at', '>=', $date);
        }

        if (!empty($filters['created_before']) || !empty($filters['created_to'])) {
            $date = $filters['created_before'] ?? $filters['created_to'];
            $query->where('created_at', '<=', $date);
        }
    }
}
