<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'title' => $this->title,
            'status' => $this->status->value,
            'last_response_author_type' => $this->last_response_author_type,

            'creator_name' => $this->creator->profile->full_name ?? $this->creator->email,
            'owner_agent_name' => $this->ownerAgent->profile->full_name ?? null,
            'category_name' => $this->category->name,

            'responses_count' => $this->responses_count ?? 0,
            'attachments_count' => $this->attachments_count ?? 0,

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
