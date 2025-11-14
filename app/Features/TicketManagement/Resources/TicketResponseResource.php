<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'content' => $this->content,
            'author_type' => $this->author_type->value,

            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->profile->full_name ?? $this->author->email,
                    'email' => $this->author->email,
                ];
            }),

            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
