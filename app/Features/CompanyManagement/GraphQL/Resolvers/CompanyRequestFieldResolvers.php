<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Resolvers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;

/**
 * Field resolvers para CompanyRequest type
 *
 * Resuelve campos computados que requieren relaciones.
 */
class CompanyRequestFieldResolvers
{
    /**
     * Resolver para CompanyRequest.reviewedByName
     *
     * Obtiene el nombre del usuario que revisó la solicitud.
     *
     * @param \App\Features\CompanyManagement\Models\CompanyRequest $companyRequest
     * @return string|null
     */
    public function reviewedByName($companyRequest): ?string
    {
        if (!$companyRequest->reviewed_by) {
            return null;
        }

        $reviewer = User::find($companyRequest->reviewed_by);

        if (!$reviewer || !$reviewer->profile) {
            return null;
        }

        return trim("{$reviewer->profile->first_name} {$reviewer->profile->last_name}");
    }

    /**
     * Resolver para CompanyRequest.createdCompanyName
     *
     * Obtiene el nombre de la empresa creada después de aprobar la solicitud.
     *
     * @param \App\Features\CompanyManagement\Models\CompanyRequest $companyRequest
     * @return string|null
     */
    public function createdCompanyName($companyRequest): ?string
    {
        if (!$companyRequest->created_company_id) {
            return null;
        }

        $company = Company::find($companyRequest->created_company_id);

        return $company?->name;
    }
}
