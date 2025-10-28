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
            'accessToken' => $this['accessToken'],
            'refreshToken' => 'New token set in httpOnly cookie',
            'tokenType' => $this['tokenType'] ?? 'Bearer',
            'expiresIn' => $this['expiresIn'] ?? 2592000, // 30 days
        ];
    }
}
