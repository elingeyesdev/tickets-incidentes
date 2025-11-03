<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Announcement Resource
 *
 * Transform announcement model into JSON response.
 * Includes company name and author name from relationships.
 */
class AnnouncementResource extends JsonResource
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
            'company_id' => $this->company_id,
            'company_name' => $this->company->name,
            'author_id' => $this->author_id,
            'author_name' => $this->getAuthorName(),
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'metadata' => $this->metadata,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get author name from relationship.
     * Handles null author (soft deleted users) gracefully.
     *
     * @return string
     */
    private function getAuthorName(): string
    {
        if (!$this->author) {
            return 'Usuario Eliminado';
        }

        if (!$this->author->profile) {
            return $this->author->email;
        }

        return $this->author->profile->display_name;
    }
}
