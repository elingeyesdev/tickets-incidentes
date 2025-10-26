<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Shared\GraphQL\Errors\GraphQLErrorWithExtensions;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class ApproveCompanyRequestMutation extends BaseMutation
{
    public function __construct(
        private CompanyRequestService $requestService
    ) {}

    public function __invoke($root, array $args, $context = null)
    {
        try {
            // Obtener revisor (usuario autenticado - permisos manejados por directiva @auth)
            $reviewer = JWTHelper::getAuthenticatedUser();

            if (!$reviewer) {
                throw new GraphQLErrorWithExtensions('User not authenticated', [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Buscar solicitud
            $request = CompanyRequest::find($args['requestId']);

            if (!$request) {
                throw new GraphQLErrorWithExtensions('Request not found', [
                    'code' => 'REQUEST_NOT_FOUND',
                    'requestId' => $args['requestId']
                ]);
            }

            // Verificar si el admin_email ya existe (para determinar si creamos usuario nuevo)
            $existingUser = \App\Features\UserManagement\Models\User::where('email', $request->admin_email)->exists();
            $newUserCreated = !$existingUser;

            // Aprobar solicitud (crea empresa y asigna rol de admin)
            $company = $this->requestService->approve($request, $reviewer);

            // Cargar relaciones necesarias para la respuesta
            $company->load('adminUser.profile');

            // Construir respuesta profesional
            return [
                'success' => true,
                'message' => $newUserCreated
                    ? "Solicitud aprobada exitosamente. Se ha creado la empresa '{$company->name}' y se envió un email con las credenciales de acceso a {$request->admin_email}."
                    : "Solicitud aprobada exitosamente. Se ha creado la empresa '{$company->name}' y se asignó el rol de administrador al usuario existente.",
                'company' => [
                    'id' => $company->id,
                    'companyCode' => $company->company_code,
                    'name' => $company->name,
                    'legalName' => $company->legal_name,
                    'status' => $company->status,
                    'adminId' => $company->admin_user_id,
                    'adminEmail' => $company->adminUser->email,
                    'adminName' => $company->adminUser->profile
                        ? $company->adminUser->profile->first_name . ' ' . $company->adminUser->profile->last_name
                        : $company->adminUser->email,
                    'createdAt' => $company->created_at,
                ],
                'newUserCreated' => $newUserCreated,
                'notificationSentTo' => $request->admin_email,
            ];

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}