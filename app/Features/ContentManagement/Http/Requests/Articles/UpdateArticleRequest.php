<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests\Articles;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ya está protegido por middleware role:COMPANY_ADMIN
        return true;
    }

    public function rules(): array
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $articleId = $this->route('article'); // ID del artículo desde la ruta

        return [
            'title' => [
                'sometimes',
                'string',
                'min:3',
                'max:255',
                Rule::unique('help_center_articles', 'title')
                    ->where('company_id', $companyId)
                    ->ignore($articleId),
            ],
            'content' => 'sometimes|string|min:50|max:20000',
            'category_id' => 'sometimes|uuid|exists:article_categories,id',
            'excerpt' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Ya existe un artículo con este título en tu empresa',
            'title.min' => 'El título debe tener al menos 3 caracteres',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'content.min' => 'El contenido debe tener al menos 50 caracteres',
            'content.max' => 'El contenido no puede exceder 20000 caracteres',
            'category_id.uuid' => 'La categoría debe ser un UUID válido',
            'category_id.exists' => 'La categoría seleccionada no existe',
            'excerpt.max' => 'El resumen no puede exceder 500 caracteres',
        ];
    }
}
