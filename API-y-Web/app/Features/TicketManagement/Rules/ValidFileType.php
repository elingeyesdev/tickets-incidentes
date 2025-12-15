<?php

namespace App\Features\TicketManagement\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidFileType implements ValidationRule
{
    public const ALLOWED_TYPES = [
        // Documentos (8) - Helpdesk común
        'pdf', 'txt', 'log', 'doc', 'docx', 'xls', 'xlsx', 'csv',
        // Imágenes (7) - Screenshots/evidencia
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
        // Videos (1) - Demostración de problemas
        'mp4',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('El archivo debe ser un archivo válido.');
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        // Solo verificar whitelist - cualquier tipo no permitido es rechazado
        if (!in_array($extension, self::ALLOWED_TYPES)) {
            $allowedList = implode(', ', self::ALLOWED_TYPES);
            $fail("Tipo de archivo no permitido (.{$extension}). Tipos permitidos: {$allowedList}.");
        }
    }
}
