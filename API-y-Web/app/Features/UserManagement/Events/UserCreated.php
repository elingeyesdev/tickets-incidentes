<?php

namespace App\Features\UserManagement\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Created Event
 *
 * Disparado cuando se crea un nuevo usuario en el sistema.
 * Otros features pueden escuchar este evento para:
 * - Enviar email de bienvenida
 * - Crear registros de auditoría
 * - Inicializar configuraciones por defecto
 * - Sincronizar con servicios externos
 */
class UserCreated
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario creado
     * @param string|null $createdById ID del usuario que creó (null si auto-registro)
     * @param array $context Contexto adicional (ej: desde admin panel, auto-registro, etc.)
     */
    public function __construct(
        public User $user,
        public ?string $createdById = null,
        public array $context = []
    ) {}
}
