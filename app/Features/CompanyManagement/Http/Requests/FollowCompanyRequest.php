<?php

namespace App\Features\CompanyManagement\Http\Requests;

use App\Features\CompanyManagement\Models\CompanyFollower;
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
        return [];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $company = $this->route('company');
            $user = $this->user();

            if (!$company) {
                $validator->errors()->add('company', 'La empresa no existe.');
                return;
            }

            // Verificar si ya sigue esta empresa
            $alreadyFollowing = CompanyFollower::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->exists();

            if ($alreadyFollowing) {
                $validator->errors()->add(
                    'company',
                    'Ya estás siguiendo esta empresa.'
                );
                return;
            }

            // Verificar límite de 50 empresas seguidas
            $followedCount = CompanyFollower::where('user_id', $user->id)->count();

            if ($followedCount >= 50) {
                $validator->errors()->add(
                    'company',
                    'Has alcanzado el límite máximo de 50 empresas seguidas.'
                );
            }
        });
    }
}
