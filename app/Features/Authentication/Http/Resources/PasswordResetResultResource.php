<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Password Reset Result Resource
 *
 * Transforma el resultado de confirmación de reset a JSON.
 * Se usa en POST /password-reset/confirm.
 * Incluye tokens y usuario.
 */
class PasswordResetResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this['success'] ?? true,
            'message' => $this['message'] ?? 'Contraseña reseteada correctamente. Sesión iniciada automáticamente.',
            'accessToken' => $this['accessToken'],
            'refreshToken' => 'Token set in httpOnly cookie',
            'tokenType' => $this['tokenType'] ?? 'Bearer',
            'expiresIn' => $this['expiresIn'] ?? 2592000,
            'user' => new UserAuthInfoResource($this['user']),
        ];
    }
}
