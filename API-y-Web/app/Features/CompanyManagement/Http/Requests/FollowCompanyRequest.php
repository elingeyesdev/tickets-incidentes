<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Follow Company Request
 *
 * Validación para seguir una empresa.
 * Equivalente a GraphQL Mutation: followCompany
 */
class FollowCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return JWTHelper::isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // No hay body, el ID de la empresa viene en la URL
        // Las validaciones de negocio se manejan en CompanyFollowService
        return [];
    }
}
