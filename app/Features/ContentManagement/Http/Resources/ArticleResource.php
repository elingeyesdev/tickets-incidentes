<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Resources;

use App\Features\CompanyManagement\Http\Resources\CompanyMinimalResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => new CompanyMinimalResource($this->whenLoaded('company')),
            'author_id' => $this->author_id,
            'author_name' => $this->getAuthorName(),
            'category_id' => $this->category_id,
            'category' => [
                'id' => $this->category?->id,
                'code' => $this->category?->code,
                'name' => $this->category?->name,
            ],
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status,
            'views_count' => $this->views_count,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get author name with null-deletion handling
     */
    private function getAuthorName(): ?string
    {
        if (!$this->author) {
            return null;
        }

        return $this->author->name ?? $this->author->email ?? 'Usuario desconocido';
    }
}