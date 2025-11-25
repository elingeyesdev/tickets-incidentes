<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Listeners;

use App\Features\CompanyManagement\Events\CompanyCreated;
use App\Features\TicketManagement\Services\CategoryService;
use Illuminate\Support\Facades\Log;

/**
 * Listener que auto-crea las 5 categorías por defecto cuando se crea una empresa
 *
 * Escucha el evento CompanyCreated y crea automáticamente 5 categorías de tickets
 * específicas según el industry_type de la empresa.
 *
 * Ejemplo:
 * - Empresa Technology → Bug Report, Feature Request, Performance Issue, Account & Access, Technical Support
 * - Empresa Healthcare → Patient Support, Appointment Issue, Medical Records, System Access, Billing & Insurance
 *
 * Este listener se ejecuta DENTRO de la transacción de CompanyService::create()
 */
class CreateDefaultCategoriesListener
{
    /**
     * Inyección de dependencias del servicio de categorías
     */
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * Handle del evento CompanyCreated
     *
     * @param CompanyCreated $event
     * @return void
     */
    public function handle(CompanyCreated $event): void
    {
        $company = $event->company;

        // Verificar que la empresa tenga industry_id y relación cargada
        if (!$company->industry_id) {
            Log::warning('Cannot create default categories: company has no industry_id', [
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);
            return;
        }

        // Cargar la relación industry si no está cargada
        if (!$company->relationLoaded('industry')) {
            $company->load('industry');
        }

        // Verificar que la industria exista
        if (!$company->industry) {
            Log::warning('Cannot create default categories: industry not found', [
                'company_id' => $company->id,
                'industry_id' => $company->industry_id,
            ]);
            return;
        }

        $industryCode = $company->industry->code;

        try {
            // Crear las 5 categorías por defecto según el industry code
            $categoriesCreated = $this->categoryService->createDefaultCategoriesForIndustry(
                $company->id,
                $industryCode
            );

            Log::info('Auto-created default categories for new company', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'industry_code' => $industryCode,
                'categories_created' => $categoriesCreated,
            ]);
        } catch (\Exception $e) {
            // Log del error pero NO lanzar excepción
            // Permitimos que la empresa se cree aunque falle la creación de categorías
            Log::error('Failed to create default categories for company', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'industry_code' => $industryCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // NO re-lanzar la excepción - queremos que la creación de empresa continúe
            // Las categorías pueden ser creadas manualmente después si es necesario
        }
    }
}
