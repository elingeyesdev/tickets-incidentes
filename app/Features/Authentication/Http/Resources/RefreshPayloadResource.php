<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Refresh Payload Resource
 *
 * Transforma el payload de refresh de token a JSON.
 * Se usa solo en el endpoint refresh.
 */
class RefreshPayloadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'accessToken' => $this['access_token'],
            'refreshToken' => 'New token set in httpOnly cookie',
            'tokenType' => $this['token_type'] ?? 'Bearer',
            'expiresIn' => $this['expires_in'] ?? 2592000, // 30 days
        ];
    }
}
