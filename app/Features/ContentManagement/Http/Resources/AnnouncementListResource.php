<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
