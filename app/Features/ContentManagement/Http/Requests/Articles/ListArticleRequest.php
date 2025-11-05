<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;

class ListArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Todos los usuarios autenticados pueden listar (la restricción es por visibilidad, no por autorización)
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'company_id' => 'sometimes|uuid|exists:business.companies,id',
            'category' => 'sometimes|string|in:ACCOUNT_PROFILE,SECURITY_PRIVACY,BILLING_PAYMENTS,TECHNICAL_SUPPORT',
            'status' => 'sometimes|string|in:draft,published',
            'search' => 'sometimes|string|max:255',
            'sort' => 'sometimes|string|in:-views,views,-created_at,created_at,title,-title',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.uuid' => 'El ID de empresa debe ser un UUID válido',
            'company_id.exists' => 'La empresa especificada no existe',
            'category.in' => 'La categoría especificada no es válida',
            'status.in' => 'El estado debe ser "draft" o "published"',
            'sort.in' => 'El parámetro de ordenamiento no es válido',
            'per_page.max' => 'El máximo de items por página es 100',
        ];
    }

    public function validated($key = null, $default = null)
    {
        return parent::validated($key, $default);
    }
}
