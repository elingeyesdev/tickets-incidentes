<?php

namespace App\Features\TicketManagement\Http\Resources;

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
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'last_response_author_type' => $this->last_response_author_type,

            // IDs for filtering/relationships
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'area_id' => $this->area_id,
            'created_by_user_id' => $this->created_by_user_id,
            'owner_agent_id' => $this->owner_agent_id,

            // Human-readable names
            'creator_name' => $this->creator->profile->full_name ?? $this->creator->email,
            'owner_agent_name' => $this->ownerAgent->profile->full_name ?? null,
            'category_name' => $this->category->name,
            'area_name' => $this->area?->name,

            // Related data (loaded when needed)
            'created_by_user' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->profile->full_name ?? $this->creator->email,
                    'email' => $this->creator->email,
                ];
            }),
            'owner_agent' => $this->whenLoaded('ownerAgent', function () {
                return $this->ownerAgent ? [
                    'id' => $this->ownerAgent->id,
                    'name' => $this->ownerAgent->profile->full_name ?? $this->ownerAgent->email,
                    'email' => $this->ownerAgent->email,
                ] : null;
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'area' => $this->whenLoaded('area', function () {
                return $this->area ? [
                    'id' => $this->area->id,
                    'name' => $this->area->name,
                ] : null;
            }),
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ];
            }),

            // Counts
            'responses_count' => $this->responses_count ?? 0,
            'attachments_count' => $this->attachments_count ?? 0,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
