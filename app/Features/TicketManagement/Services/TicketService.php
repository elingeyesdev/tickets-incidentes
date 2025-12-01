<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Events\TicketCreated;
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
            'priority' => $data['priority'] ?? 'medium', // Default 'medium' if not provided
            'area_id' => $data['area_id'] ?? null, // Optional area_id
        ]);

        // Disparar evento de creación
        event(new TicketCreated($ticket));

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
        $query->with(['creator.profile', 'ownerAgent.profile', 'category', 'area']);
        $query->withCount(['responses', 'attachments']);

        // Aplicar filtros de visibilidad según rol
        $this->applyVisibilityFilters($query, $user->id, $userRole);

        // Aplicar filtros adicionales
        $this->applyFilters($query, $filters, $user->id);

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
        if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {
            return 'PLATFORM_ADMIN';
        }
        if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
            return 'COMPANY_ADMIN';
        }
        if (JWTHelper::hasRoleFromJWT('AGENT')) {
            return 'AGENT';
        }
        if (JWTHelper::hasRoleFromJWT('USER')) {
            return 'USER';
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

        // Permitir actualizar descripción
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        // Permitir actualizar prioridad
        if (isset($data['priority'])) {
            $updateData['priority'] = $data['priority'];
        }

        // Permitir actualizar área (including null to clear it)
        if (array_key_exists('area_id', $data)) {
            $updateData['area_id'] = $data['area_id'];
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
     * @param string $userRole Rol del usuario (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER)
     */
    private function applyVisibilityFilters(Builder $query, string $userId, string $userRole): void
    {
        // Si es PLATFORM_ADMIN: ve TODO (no aplicar filtros)
        if ($userRole === 'PLATFORM_ADMIN') {
            return;
        }

        // Si es COMPANY_ADMIN: ve todos los tickets de su empresa
        if ($userRole === 'COMPANY_ADMIN') {
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            return;
        }

        // Si es AGENT: ve todos los tickets de su empresa
        if ($userRole === 'AGENT') {
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            return;
        }

        // Si es USER: solo ve sus propios tickets
        if ($userRole === 'USER') {
            $query->where('created_by_user_id', $userId);
            return;
        }
    }

    /**
     * Aplica filtros adicionales a la consulta
     *
     * @param Builder $query
     * @param array $filters
     * @param string $userId
     */
    private function applyFilters(Builder $query, array $filters, string $userId): void
    {
        // Filtrar por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtrar por categoría
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtrar por prioridad
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Filtrar por área
        if (!empty($filters['area_id'])) {
            $query->where('area_id', $filters['area_id']);
        }

        // Filtrar por agente asignado
        if (isset($filters['owner_agent_id'])) {
            if ($filters['owner_agent_id'] === 'null') {
                // String literal 'null' = sin asignar
                $query->whereNull('owner_agent_id');
            } elseif ($filters['owner_agent_id'] === 'me') {
                // String literal 'me' = usuario autenticado
                $query->where('owner_agent_id', $userId);
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

        // Búsqueda en título, descripción, nombre de área y nombre de categoría
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw("title ILIKE ?", ["%{$search}%"])
                    ->orWhereRaw("description ILIKE ?", ["%{$search}%"])
                    ->orWhereHas('area', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', "%{$search}%");
                    });
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

    /**
     * Resuelve un ticket (cambia status a resolved)
     *
     * @param Ticket $ticket Ticket a resolver
     * @param array $data Datos opcionales (resolution_note)
     * @return Ticket Ticket resuelto
     */
    public function resolve(Ticket $ticket, array $data): Ticket
    {
        // Validar que el ticket no esté ya resuelto o cerrado
        if ($ticket->status === TicketStatus::RESOLVED) {
            throw new \RuntimeException('ALREADY_RESOLVED');
        }

        if ($ticket->status === TicketStatus::CLOSED) {
            throw new \RuntimeException('ALREADY_CLOSED');
        }

        // Actualizar el ticket
        $ticket->update([
            'status' => TicketStatus::RESOLVED,
            'resolved_at' => now(),
        ]);

        // Disparar evento
        event(new \App\Features\TicketManagement\Events\TicketResolved($ticket));

        return $ticket->fresh();
    }

    /**
     * Cierra un ticket (cambia status a closed)
     *
     * @param Ticket $ticket Ticket a cerrar
     * @param array $data Datos opcionales (close_note)
     * @return Ticket Ticket cerrado
     */
    public function close(Ticket $ticket, array $data): Ticket
    {
        // Validar que el ticket no esté ya cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            throw new \RuntimeException('ALREADY_CLOSED');
        }

        // Actualizar el ticket
        $ticket->update([
            'status' => TicketStatus::CLOSED,
            'closed_at' => now(),
        ]);

        // Disparar evento
        event(new \App\Features\TicketManagement\Events\TicketClosed($ticket));

        return $ticket->fresh();
    }

    /**
     * Reabre un ticket (cambia status a pending)
     *
     * @param Ticket $ticket Ticket a reabrir
     * @param array $data Datos opcionales (reopen_reason)
     * @return Ticket Ticket reabierto
     */
    public function reopen(Ticket $ticket, array $data): Ticket
    {
        // Validar que el ticket esté resolved o closed
        if (!in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
            throw new \RuntimeException('CANNOT_REOPEN');
        }

        // Actualizar el ticket
        $ticket->update([
            'status' => TicketStatus::PENDING,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Disparar evento
        event(new \App\Features\TicketManagement\Events\TicketReopened($ticket));

        return $ticket->fresh();
    }

    /**
     * Asigna un ticket a un agente
     *
     * @param Ticket $ticket Ticket a asignar
     * @param array $data Datos requeridos (new_agent_id) y opcionales (assignment_note)
     * @return Ticket Ticket asignado
     */
    public function assign(Ticket $ticket, array $data): Ticket
    {
        // Validar que new_agent_id existe
        $newAgent = User::findOrFail($data['new_agent_id']);

        // Validar que el nuevo agente tiene rol AGENT
        if (!$newAgent->hasRoleInCompany('AGENT', $ticket->company_id)) {
            throw new \RuntimeException('INVALID_AGENT_ROLE');
        }

        // Actualizar el ticket
        $ticket->update([
            'owner_agent_id' => $data['new_agent_id'],
        ]);

        // Disparar evento
        event(new \App\Features\TicketManagement\Events\TicketAssigned($ticket));

        // Enviar notificación al nuevo agente usando Notification facade
        \Notification::send($newAgent, new \App\Features\TicketManagement\Notifications\TicketAssignedNotification($ticket));

        return $ticket->fresh();
    }
}
