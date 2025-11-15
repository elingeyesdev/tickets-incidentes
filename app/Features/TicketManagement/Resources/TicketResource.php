<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'created_by_user_id' => $this->created_by_user_id,
            'owner_agent_id' => $this->owner_agent_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'last_response_author_type' => $this->last_response_author_type,

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
                ] : null;
            }),

            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),

            'responses_count' => $this->when(isset($this->responses_count), $this->responses_count),
            'attachments_count' => $this->when(isset($this->attachments_count), $this->attachments_count),

            'timeline' => [
                'created_at' => $this->created_at->toIso8601String(),
                'first_response_at' => $this->first_response_at?->toIso8601String(),
                'resolved_at' => $this->resolved_at?->toIso8601String(),
                'closed_at' => $this->closed_at?->toIso8601String(),
            ],

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
