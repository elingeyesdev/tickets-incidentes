<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\DeviceInfoParser;
use Illuminate\Support\Str;

/**
 * RegisterMutation
 *
 * Registra un nuevo usuario en el sistema.
 * Crea usuario + perfil, asigna rol USER por defecto, genera tokens y envía email de verificación.
 *
 * @usage GraphQL
 * ```graphql
 * mutation Register($input: RegisterInput!) {
 *   register(input: $input) {
 *     accessToken
 *     user { email profile { firstName } }
 *   }
 * }
 * ```
 */
class RegisterMutation extends BaseMutation
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Registrar nuevo usuario
     *
     * @param  mixed  $root
     * @param  array{input: array{email: string, password: string, passwordConfirmation: string, firstName: string, lastName: string}}  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array AuthPayload con tokens y datos del usuario
     * @throws \App\Shared\Exceptions\ValidationException Si email ya existe o datos inválidos
     */
    public function __invoke($root, array $args, $context = null): array
    {
        // 1. Preparar datos para el servicio (GraphQL camelCase → Service snake_case)
        $input = $this->mapInputToServiceFormat($args['input']);

        // 2. Extraer información del dispositivo desde contexto HTTP
        $deviceInfo = DeviceInfoParser::fromGraphQLContext($context);

        // 3. Llamar al servicio (TODA la lógica de negocio está aquí)
        $result = $this->authService->register($input, $deviceInfo);

        // 4. Transformar respuesta del servicio a formato GraphQL
        return $this->mapToGraphQLResponse($result);
    }

    /**
     * Mapea inputs GraphQL (camelCase) a formato esperado por AuthService (snake_case)
     *
     * También sanitiza y normaliza los datos:
     * - Email: lowercase y trimmed
     * - Nombres: Capitalizados y trimmed
     *
     * NOTA: acceptsTerms y acceptsPrivacyPolicy no se mapean aquí porque
     * Lighthouse ya valida con @rules(apply: ["accepted"]) que sean true.
     * Si el request llega a este punto, el usuario ya aceptó ambos.
     *
     * @param array{email: string, password: string, passwordConfirmation: string, firstName: string, lastName: string, acceptsTerms: bool, acceptsPrivacyPolicy: bool} $input
     * @return array{email: string, password: string, first_name: string, last_name: string, terms_accepted: bool}
     */
    private function mapInputToServiceFormat(array $input): array
    {
        return [
            'email' => strtolower(trim($input['email'])),
            'password' => $input['password'],
            'first_name' => $this->capitalizeName($input['firstName']),
            'last_name' => $this->capitalizeName($input['lastName']),
            'terms_accepted' => true, // Validado por Lighthouse con @rules(apply: ["accepted"])
        ];
    }

    /**
     * Capitaliza nombres correctamente (Primera letra mayúscula, resto minúsculas)
     * También sanitiza quitando HTML tags
     *
     * @param string $name Nombre a capitalizar
     * @return string Nombre capitalizado y sanitizado
     */
    private function capitalizeName(string $name): string
    {
        $sanitized = strip_tags(trim($name));
        return ucfirst(strtolower($sanitized));
    }

    /**
     * Mapea respuesta del servicio a formato GraphQL AuthPayload
     *
     * Transforma estructura del service a estructura esperada por el schema GraphQL.
     * Sigue la estructura de UserAuthInfo definida en graphql/shared/base-types.graphql
     *
     * @param array{user: \App\Features\UserManagement\Models\User, access_token: string, refresh_token: string, expires_in: int, requires_verification: bool} $result
     * @return array AuthPayload compatible con GraphQL schema
     */
    private function mapToGraphQLResponse(array $result): array
    {
        $user = $result['user'];

        // Cargar roles activos del usuario con relaciones necesarias
        $userRoles = $user->activeRoles()->with(['role', 'company'])->get();

        return [
            // Tokens
            'accessToken' => $result['access_token'],
            'refreshToken' => $result['refresh_token'],
            'tokenType' => 'Bearer',
            'expiresIn' => $result['expires_in'],

            // Usuario - Estructura UserAuthInfo (campos planos, NO nested profile)
            'user' => [
                'id' => $user->id,
                'userCode' => $user->user_code,
                'email' => $user->email,
                'emailVerified' => $user->email_verified,
                'status' => $user->status->value,
                'displayName' => $user->profile->display_name,
                'avatarUrl' => $user->profile->avatar_url,
                'theme' => $user->profile->theme,
                'language' => $user->profile->language,
            ],

            // Contextos de roles (con company que puede ser null)
            'roleContexts' => $this->buildRoleContexts($userRoles),

            // Metadata de sesión
            'sessionId' => Str::uuid()->toString(),
            'loginTimestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Construye array de roleContexts según estructura GraphQL
     *
     * Cada roleContext incluye:
     * - roleCode: Código del rol (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
     * - roleName: Nombre legible del rol
     * - company: null para roles sin empresa (USER, PLATFORM_ADMIN), objeto para roles con empresa (AGENT, COMPANY_ADMIN)
     * - dashboardPath: Ruta del dashboard según el rol
     *
     * @param \Illuminate\Database\Eloquent\Collection $userRoles
     * @return array
     */
    private function buildRoleContexts($userRoles): array
    {
        return $userRoles->map(function ($userRole) {
            $roleCode = strtoupper($userRole->role_code);

            // Mapear dashboard paths según rol
            $dashboardPaths = [
                'USER' => '/tickets',
                'AGENT' => '/agent/dashboard',
                'COMPANY_ADMIN' => '/admin/dashboard',
                'PLATFORM_ADMIN' => '/platform/dashboard',
            ];

            // Mapear nombres legibles de roles
            $roleNames = [
                'USER' => 'Cliente',
                'AGENT' => 'Agente de Soporte',
                'COMPANY_ADMIN' => 'Administrador de Empresa',
                'PLATFORM_ADMIN' => 'Administrador de Plataforma',
            ];

            $context = [
                'roleCode' => $roleCode,
                'roleName' => $roleNames[$roleCode] ?? $userRole->role->role_name,
                'dashboardPath' => $dashboardPaths[$roleCode] ?? '/dashboard',
            ];

            // Agregar company solo si el rol requiere empresa
            // USER y PLATFORM_ADMIN: company es null
            // AGENT y COMPANY_ADMIN: company tiene datos
            if ($userRole->company) {
                $context['company'] = [
                    'id' => $userRole->company->id,
                    'companyCode' => $userRole->company->company_code,
                    'name' => $userRole->company->name,
                    'logoUrl' => $userRole->company->logo_url,
                ];
            } else {
                $context['company'] = null;
            }

            return $context;
        })->toArray();
    }
}
