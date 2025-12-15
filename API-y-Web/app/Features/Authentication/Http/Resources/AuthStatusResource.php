<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Auth Status Resource
 *
 * Transforma el estado de autenticación a JSON.
 * Se usa en GET /auth/status.
 * Incluye información completa del usuario, sesión actual e info de tokens.
 */
class AuthStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'isAuthenticated' => $this['isAuthenticated'] ?? true,
            'user' => new UserAuthInfoResource($this['user']),
            'currentSession' => $this['currentSession']
                ? new SessionInfoResource($this['currentSession'])
                : null,
            'tokenInfo' => [
                'expiresIn' => $this['tokenInfo']['expiresIn'] ?? 2592000,
                'issuedAt' => $this['tokenInfo']['issuedAt'] ?? now()->toIso8601String(),
                'tokenType' => $this['tokenInfo']['tokenType'] ?? 'Bearer',
            ],
        ];
    }
}
