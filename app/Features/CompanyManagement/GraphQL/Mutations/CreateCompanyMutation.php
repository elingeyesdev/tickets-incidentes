<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\RoleService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class CreateCompanyMutation extends BaseMutation
{
    public function __construct(
        private CompanyService $companyService,
        private RoleService $roleService
    ) {}

    public function __invoke($root, array $args, $context = null)
    {
        try {
            // Obtener usuario autenticado (permisos manejados por directiva @auth)
            $authenticatedUser = JWTHelper::getAuthenticatedUser();

            if (!$authenticatedUser) {
                throw new Error('Usuario no autenticado', null, null, null, null, null, [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Extraer datos de entrada
            $input = $args['input'];

            // Buscar usuario admin
            $adminUser = User::find($input['adminUserId']);

            if (!$adminUser) {
                throw new Error('Usuario admin no encontrado', null, null, null, null, null, [
                    'code' => 'ADMIN_USER_NOT_FOUND',
                    'userId' => $input['adminUserId']
                ]);
            }

            // Preparar datos de empresa
            $data = [
                'name' => $input['name'],
                'legal_name' => $input['legalName'] ?? null,
                'support_email' => $input['supportEmail'] ?? null,
                'phone' => $input['phone'] ?? null,
                'website' => $input['website'] ?? null,
            ];

            // Información de contacto
            if (isset($input['contactInfo'])) {
                $contactInfo = $input['contactInfo'];
                $data['contact_address'] = $contactInfo['address'] ?? null;
                $data['contact_city'] = $contactInfo['city'] ?? null;
                $data['contact_state'] = $contactInfo['state'] ?? null;
                $data['contact_country'] = $contactInfo['country'] ?? null;
                $data['contact_postal_code'] = $contactInfo['postalCode'] ?? null;
                $data['tax_id'] = $contactInfo['taxId'] ?? null;
                $data['legal_representative'] = $contactInfo['legalRepresentative'] ?? null;
            }

            // Configuración inicial
            if (isset($input['initialConfig'])) {
                $config = $input['initialConfig'];
                $data['business_hours'] = $config['businessHours'] ?? null;
                $data['timezone'] = $config['timezone'] ?? 'America/La_Paz';
            }

            // Marca/Identidad visual
            if (isset($input['branding'])) {
                $branding = $input['branding'];
                $data['logo_url'] = $branding['logoUrl'] ?? null;
                $data['favicon_url'] = $branding['faviconUrl'] ?? null;
                $data['primary_color'] = $branding['primaryColor'] ?? '#007bff';
                $data['secondary_color'] = $branding['secondaryColor'] ?? '#6c757d';
            }

            // Crear empresa
            $company = $this->companyService->create($data, $adminUser);

            // Asignar rol COMPANY_ADMIN al usuario admin
            $this->roleService->assignRoleToUser(
                userId: $adminUser->id,
                roleCode: 'COMPANY_ADMIN',
                companyId: $company->id,
                assignedBy: $authenticatedUser->id
            );

            return $company;

        } catch (Error $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new Error($e->getMessage(), null, null, null, null, $e, [
                'code' => 'VALIDATION_ERROR'
            ]);
        } catch (\Exception $e) {
            throw new Error('Error al crear empresa: ' . $e->getMessage());
        }
    }
}