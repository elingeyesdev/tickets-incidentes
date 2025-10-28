<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mark Onboarding Completed Resource
 *
 * Transforma el resultado de marcar onboarding como completado a JSON.
 * Se usa en POST /onboarding/completed.
 */
class MarkOnboardingCompletedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this['success'] ?? true,
            'message' => $this['message'] ?? 'Onboarding completado exitosamente',
            'user' => new UserAuthInfoResource($this['user']),
        ];
    }
}
