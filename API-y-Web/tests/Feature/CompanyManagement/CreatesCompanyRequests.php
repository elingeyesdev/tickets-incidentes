<?php

namespace Tests\Feature\CompanyManagement;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyOnboardingDetails;

/**
 * Helper trait para crear empresas en tests con la arquitectura normalizada.
 * 
 * ARQUITECTURA NORMALIZADA:
 * - Las solicitudes son Company con status='pending', 'active', 'rejected'
 * - Los detalles de onboarding están en CompanyOnboardingDetails
 */
trait CreatesCompanyRequests
{
    /**
     * Crear empresa pendiente con detalles de onboarding
     */
    protected function createPendingCompanyWithOnboarding(array $companyOverrides = [], array $onboardingOverrides = []): Company
    {
        $company = Company::factory()->pending()->create($companyOverrides);

        $onboardingData = array_merge([
            'company_id' => $company->id,
            'submitter_email' => $companyOverrides['support_email'] ?? fake()->email(),
        ], $onboardingOverrides);

        CompanyOnboardingDetails::factory()->create($onboardingData);

        return $company->fresh('onboardingDetails');
    }

    /**
     * Crear empresa rechazada con detalles de onboarding
     */
    protected function createRejectedCompanyWithOnboarding(array $companyOverrides = [], array $onboardingOverrides = []): Company
    {
        $company = Company::factory()->rejected()->create($companyOverrides);

        $onboardingData = array_merge([
            'company_id' => $company->id,
            'submitter_email' => $companyOverrides['support_email'] ?? fake()->email(),
            'rejection_reason' => 'Test rejection reason',
        ], $onboardingOverrides);

        CompanyOnboardingDetails::factory()->create($onboardingData);

        return $company->fresh('onboardingDetails');
    }

    /**
     * Crear empresa activa (sin necesidad de onboarding details)
     */
    protected function createActiveCompany(array $overrides = []): Company
    {
        return Company::factory()->create(array_merge(['status' => 'active'], $overrides));
    }
}
