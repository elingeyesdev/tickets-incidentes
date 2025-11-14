<?php

namespace App\Features\TicketManagement\Requests;

use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Rules\ValidFileType;
use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketAttachmentPolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB en KB
                new ValidFileType(),
            ],
            'response_id' => [
                'sometimes',
                'uuid',
                function ($attribute, $value, $fail) {
                    if ($value && !TicketResponse::find($value)) {
                        $fail('La respuesta seleccionada no existe.');
                    }
                },
            ],
        ];
    }
}
