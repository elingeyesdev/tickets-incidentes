<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ArticleCategoryResource
 *
 * Transform ArticleCategory model into JSON response.
 * Only includes public fields needed for Help Center navigation.
 */
class ArticleCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
