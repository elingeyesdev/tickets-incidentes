<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Auth Payload Resource
 *
 * Transforma el payload de autenticación a JSON.
 * Se usa en register, login, y confirmPasswordReset.
 *
 * NOTA: Replicates RegisterMutation and LoginMutation behavior:
 * - Si AuthService retorna session_id (login), lo usa
 * - Si NO retorna session_id (register), genera uno nuevo (como RegisterMutation)
 */
class AuthPayloadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'accessToken' => $this['access_token'],
            'refreshToken' => 'Refresh token set in httpOnly cookie',
            'tokenType' => $this['token_type'] ?? 'Bearer',
            'expiresIn' => $this['expires_in'] ?? 2592000, // 30 days
            'user' => new UserAuthInfoResource($this['user']),
            // Si no hay session_id, generar uno nuevo (como RegisterMutation)
            'sessionId' => $this['session_id'] ?? Str::uuid()->toString(),
            'loginTimestamp' => isset($this['login_timestamp'])
                ? $this['login_timestamp']->toIso8601String()
                : now()->toIso8601String(),
        ];
    }
}
