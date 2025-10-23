<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use GraphQL\Error\Error;

class RequestCompanyMutation extends BaseMutation
{
    public function __construct(
        private CompanyRequestService $requestService
    ) {}

    public function __invoke($root, array $args, $context = null)
    {
        try {
            // Extraer datos de entrada
            $input = $args['input'];

            // Verificar si el email ya tiene una solicitud pendiente
            if ($this->requestService->hasPendingRequest($input['adminEmail'])) {
                throw new Error('A pending request already exists with this email', null, null, null, null, null, [
                    'code' => 'REQUEST_ALREADY_EXISTS',
                    'email' => $input['adminEmail']
                ]);
            }

            // Enviar solicitud (no se requiere autenticación - endpoint público)
            $request = $this->requestService->submit([
                'company_name' => $input['companyName'],
                'legal_name' => $input['legalName'] ?? null,
                'admin_email' => $input['adminEmail'],
                'business_description' => $input['businessDescription'],
                'website' => $input['website'] ?? null,
                'industry_type' => $input['industryType'],
                'estimated_users' => $input['estimatedUsers'] ?? null,
                'contact_address' => $input['contactAddress'] ?? null,
                'contact_city' => $input['contactCity'] ?? null,
                'contact_country' => $input['contactCountry'] ?? null,
                'contact_postal_code' => $input['contactPostalCode'] ?? null,
                'tax_id' => $input['taxId'] ?? null,
            ]);

            return $request;

        } catch (Error $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new Error($e->getMessage(), null, null, null, null, $e, [
                'code' => 'VALIDATION_ERROR'
            ]);
        } catch (\Exception $e) {
            throw new Error('Error submitting company request: ' . $e->getMessage());
        }
    }
}