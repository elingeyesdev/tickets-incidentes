<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'response_id' => $this->response_id,
            'uploaded_by_user_id' => $this->uploaded_by_user_id,
            'uploaded_by_name' => $this->whenLoaded('uploader', function () {
                return $this->uploader->profile?->full_name ?? $this->uploader->email;
            }),
            'file_name' => $this->file_name,
            'file_url' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size_bytes' => $this->file_size_bytes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
