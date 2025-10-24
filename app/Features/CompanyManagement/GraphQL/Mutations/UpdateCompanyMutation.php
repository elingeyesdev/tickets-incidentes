<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Services\CompanyService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class UpdateCompanyMutation extends BaseMutation
{
    public function __construct(
        private CompanyService $companyService
    ) {}

    public function __invoke($root, array $args, $context = null)
    {
        try {
            // Get authenticated user
            $authenticatedUser = JWTHelper::getAuthenticatedUser();

            // Buscar empresa
            $company = $this->companyService->findById($args['id']);

            if (!$company) {
                throw new Error('Company not found', null, null, null, null, null, [
                    'code' => 'COMPANY_NOT_FOUND',
                    'companyId' => $args['id']
                ]);
            }

            // Custom authorization: PLATFORM_ADMIN can update any company, COMPANY_ADMIN can only update their own
            $isPlatformAdmin = $authenticatedUser->hasRole('PLATFORM_ADMIN');
            $isCompanyAdmin = $authenticatedUser->hasRoleInCompany('COMPANY_ADMIN', $company->id);

            if (!$isPlatformAdmin && !$isCompanyAdmin) {
                // Check if user has COMPANY_ADMIN role for any company (but not this one)
                $hasCompanyAdminRoleElsewhere = $authenticatedUser->hasRole('COMPANY_ADMIN');

                if ($hasCompanyAdminRoleElsewhere) {
                    // User is a COMPANY_ADMIN but not for THIS company
                    throw new Error('This action is unauthorized', null, null, null, null, null, [
                        'code' => 'UNAUTHORIZED'
                    ]);
                } else {
                    // User doesn't have the required role at all
                    throw new Error('Unauthenticated', null, null, null, null, null, [
                        'code' => 'UNAUTHENTICATED'
                    ]);
                }
            }
            // Extraer datos de entrada
            $input = $args['input'];
            $data = [];

            // Campos básicos
            if (isset($input['name'])) {
                $data['name'] = $input['name'];
            }
            if (isset($input['legalName'])) {
                $data['legal_name'] = $input['legalName'];
            }
            if (isset($input['supportEmail'])) {
                $data['support_email'] = $input['supportEmail'];
            }
            if (isset($input['phone'])) {
                $data['phone'] = $input['phone'];
            }
            if (isset($input['website'])) {
                $data['website'] = $input['website'];
            }

            // Información de contacto
            if (isset($input['contactInfo'])) {
                $contactInfo = $input['contactInfo'];
                if (isset($contactInfo['address'])) {
                    $data['contact_address'] = $contactInfo['address'];
                }
                if (isset($contactInfo['city'])) {
                    $data['contact_city'] = $contactInfo['city'];
                }
                if (isset($contactInfo['state'])) {
                    $data['contact_state'] = $contactInfo['state'];
                }
                if (isset($contactInfo['country'])) {
                    $data['contact_country'] = $contactInfo['country'];
                }
                if (isset($contactInfo['postalCode'])) {
                    $data['contact_postal_code'] = $contactInfo['postalCode'];
                }
                if (isset($contactInfo['taxId'])) {
                    $data['tax_id'] = $contactInfo['taxId'];
                }
                if (isset($contactInfo['legalRepresentative'])) {
                    $data['legal_representative'] = $contactInfo['legalRepresentative'];
                }
            }

            // Configuración
            if (isset($input['config'])) {
                $config = $input['config'];
                if (isset($config['businessHours'])) {
                    $data['business_hours'] = $config['businessHours'];
                }
                if (isset($config['timezone'])) {
                    $data['timezone'] = $config['timezone'];
                }
                if (isset($config['settings'])) {
                    $data['settings'] = $config['settings'];
                }
            }

            // Marca/Identidad visual
            if (isset($input['branding'])) {
                $branding = $input['branding'];
                if (isset($branding['logoUrl'])) {
                    $data['logo_url'] = $branding['logoUrl'];
                }
                if (isset($branding['faviconUrl'])) {
                    $data['favicon_url'] = $branding['faviconUrl'];
                }
                if (isset($branding['primaryColor'])) {
                    $data['primary_color'] = $branding['primaryColor'];
                }
                if (isset($branding['secondaryColor'])) {
                    $data['secondary_color'] = $branding['secondaryColor'];
                }
            }

            // Actualizar empresa
            $updated = $this->companyService->update($company, $data);

            return $updated;

        } catch (Error $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new Error($e->getMessage(), null, null, null, null, $e, [
                'code' => 'VALIDATION_ERROR'
            ]);
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}