<?php

namespace App\Shared\GraphQL\Directives;

use App\Features\UserManagement\Models\User;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\ForbiddenException;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * @jwt Directive
 *
 * Sistema profesional de autenticación y autorización JWT para GraphQL
 *
 * Uso básico (solo autenticación):
 * ```graphql
 * type Query {
 *   myProfile: UserProfile! @jwt
 * }
 * ```
 *
 * Con roles específicos:
 * ```graphql
 * type Mutation {
 *   createCompany(input: CreateCompanyInput!): Company!
 *     @jwt(requires: [PLATFORM_ADMIN])
 * }
 * ```
 *
 * Con múltiples roles (OR):
 * ```graphql
 * type Query {
 *   companyUsers(companyId: UUID!): [UserCompanyInfo!]!
 *     @jwt(requires: [COMPANY_ADMIN, PLATFORM_ADMIN])
 * }
 * ```
 */
class JwtDirective extends BaseDirective implements FieldMiddleware
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Requiere autenticación JWT válida.
El token debe enviarse en el header: Authorization: Bearer <token>

Opcionalmente puede requerir roles específicos:
@jwt(requires: [PLATFORM_ADMIN])
"""
directive @jwt(
  """Roles requeridos (OR logic - cualquiera es suficiente)"""
  requires: [RoleCode!]
) on FIELD_DEFINITION
GRAPHQL;
    }

    /**
     * Wrap the resolver with JWT authentication check
     */
    public function handleField(FieldValue $fieldValue): void
    {
        // Obtener roles requeridos de la directiva (si aplica)
        $requiredRoles = $this->directiveArgValue('requires', null);

        $fieldValue->wrapResolver(fn (callable $resolver): \Closure => function (mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver, $requiredRoles) {
            // Obtener request
            $request = $context->request();


            // Verificar si hubo error en la validación del token (middleware)
            $jwtError = $request->attributes->get('jwt_error');
            if ($jwtError) {
                throw new AuthenticationException('Unauthenticated');
            }

            // Obtener user_id del token validado
            $userId = $request->attributes->get('jwt_user_id');
            if (!$userId) {
                throw new AuthenticationException('Unauthenticated');
            }

            // Cargar usuario completo con roles activos
            $user = User::with(['activeRoles'])->find($userId);

            if (!$user) {
                throw AuthenticationException::userNotFound();
            }

            // Verificar que el usuario esté activo
            if (!$user->isActive()) {
                throw AuthenticationException::accountSuspended();
            }

            // Si se requieren roles específicos, validar
            if ($requiredRoles && count($requiredRoles) > 0) {
                $this->validateUserRoles($user, $requiredRoles);
            }

            // Agregar usuario al contexto para que esté disponible en el resolver
            $context->user = $user;

            // Llamar al resolver original con el usuario en contexto
            return $resolver($root, $args, $context, $resolveInfo);
        });
    }

    /**
     * Valida que el usuario tenga al menos uno de los roles requeridos
     *
     * @param User $user
     * @param array $requiredRoles
     * @throws ForbiddenException
     */
    private function validateUserRoles(User $user, array $requiredRoles): void
    {
        // Obtener roles activos del usuario (códigos)
        $userRoleCodes = $user->activeRoles
            ->pluck('role_code')
            ->toArray();

        // Verificar si el usuario tiene al menos uno de los roles requeridos
        $hasRequiredRole = false;
        foreach ($requiredRoles as $requiredRole) {
            if (in_array($requiredRole, $userRoleCodes)) {
                $hasRequiredRole = true;
                break;
            }
        }

        if (!$hasRequiredRole) {
            // Return generic "Unauthenticated" to avoid revealing endpoint existence
            throw new AuthenticationException('Unauthenticated');
        }
    }
}
