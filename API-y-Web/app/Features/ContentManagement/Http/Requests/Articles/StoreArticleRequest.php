<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ya está protegido por middleware role:COMPANY_ADMIN
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|uuid|exists:article_categories,id',
            'title' => 'required|string|min:3|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string|min:50|max:20000',
            'action' => 'nullable|in:draft,publish',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es requerida',
            'category_id.uuid' => 'La categoría debe ser un UUID válido',
            'category_id.exists' => 'La categoría seleccionada no existe',
            'title.required' => 'El título es requerido',
            'title.min' => 'El título debe tener al menos 3 caracteres',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'content.required' => 'El contenido es requerido',
            'content.min' => 'El contenido debe tener al menos 50 caracteres',
            'content.max' => 'El contenido no puede exceder 20000 caracteres',
            'excerpt.max' => 'El resumen no puede exceder 500 caracteres',
        ];
    }
}