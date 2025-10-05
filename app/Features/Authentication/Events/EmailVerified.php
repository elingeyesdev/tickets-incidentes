<?php

namespace App\Features\Authentication\Events;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Email Verified Event
 *
 * Disparado cuando un usuario verifica exitosamente su email.
 */
class EmailVerified
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user Usuario cuyo email fue verificado
     */
    public function __construct(
        public User $user
    ) {}
}