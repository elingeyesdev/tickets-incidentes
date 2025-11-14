<?php

namespace App\Features\TicketManagement\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidFileType implements ValidationRule
{
    public const ALLOWED_TYPES = [
        // Documentos
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        // Imágenes
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp',
        // Comprimidos
        'zip', 'rar', '7z', 'tar', 'gz',
    ];

    public const FORBIDDEN_TYPES = [
        // Ejecutables
        'exe', 'bat', 'cmd', 'com', 'msi', 'app', 'dmg',
        // Scripts
        'sh', 'bash', 'ps1', 'vbs', 'js', 'jar',
        // Otros peligrosos
        'dll', 'sys', 'scr',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('El archivo debe ser un archivo válido.');
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (in_array($extension, self::FORBIDDEN_TYPES)) {
            $fail("El tipo de archivo .{$extension} no está permitido por razones de seguridad.");
            return;
        }

        if (!in_array($extension, self::ALLOWED_TYPES)) {
            $allowedList = implode(', ', self::ALLOWED_TYPES);
            $fail("El tipo de archivo debe ser uno de los siguientes: {$allowedList}.");
            return;
        }
    }
}
