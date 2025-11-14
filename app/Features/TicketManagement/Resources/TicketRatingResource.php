<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'rating' => $this->rating,
            'comment' => $this->comment,

            'rated_agent' => $this->whenLoaded('ratedAgent', function () {
                return $this->ratedAgent ? [
                    'id' => $this->ratedAgent->id,
                    'name' => $this->ratedAgent->profile->full_name ?? $this->ratedAgent->email,
                ] : null;
            }),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
