<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Shared\Helpers\JWTHelper;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\ForbiddenException;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * JWT Context Directive - Complex Authorization Handler
 *
 * Validates authentication, roles, and context-specific access for GraphQL queries.
 * Supports multiple authorization scenarios:
 *
 * 1. Basic Auth Requirement:
 *    @jwtContext(requiresAuth: true)
 *
 * 2. Specific Roles:
 *    @jwtContext(requiresAuth: true, roles: ["PLATFORM_ADMIN"])
 *
 * 3. Context-Based Rules (for multi-context queries like companies, tickets):
 *    @jwtContext(contextRules: [
 *      { context: "MINIMAL", requiresAuth: false },
 *      { context: "EXPLORE", requiresAuth: true },
 *      { context: "MANAGEMENT", requiresAuth: true, roles: ["PLATFORM_ADMIN", "COMPANY_ADMIN"] }
 *    ])
 *
 * 4. Company Ownership Validation (for multi-tenant context):
 *    @jwtContext(validateCompanyOwnership: true, companyIdArg: "companyId")
 *
 * @package App\Shared\GraphQL\Directives
 */
class JwtContextDirective extends BaseDirective implements FieldMiddleware
{
    /**
     * GraphQL SDL definition for the directive
     */
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Complex JWT Context Authorization Directive

Validates authentication, roles, and context-specific access.
Supports multi-context queries with context-dependent authorization rules.

Usage:
  @jwtContext(contextRules: [
    { context: "MINIMAL", requiresAuth: false },
    { context: "EXPLORE", requiresAuth: true },
    { context: "MANAGEMENT", requiresAuth: true }
  ])
"""
directive @jwtContext(
  """Context-specific authorization rules"""
  contextRules: [ContextAuthRule!]
) on FIELD_DEFINITION

"""Context-specific authorization rule"""
input ContextAuthRule {
  """Context identifier (e.g., MINIMAL, EXPLORE, MANAGEMENT)"""
  context: String!
  
  """Whether this context requires authentication"""
  requiresAuth: Boolean!
  
  """Required roles for this context (null = any authenticated user)"""
  roles: [String!]
  
  """Custom error message for this context"""
  errorMessage: String
}
GRAPHQL;
    }

    /**
     * Handle field with authorization checks (Lighthouse FieldMiddleware pattern)
     *
     * @param FieldValue $fieldValue
     * @return void
     */
    public function handleField(FieldValue $fieldValue): void
    {
        $fieldValue->wrapResolver(fn (callable $resolver): \Closure => function (
            mixed $root,
            array $args,
            GraphQLContext $context,
            ResolveInfo $resolveInfo
        ) use ($resolver) {
            $this->validateAuthorization($args);
            return $resolver($root, $args, $context, $resolveInfo);
        });
    }

    /**
     * Validate authorization based on directive arguments and query context
     *
     * @param array $args
     * @throws GraphQLErrorWithExtensions
     */
    private function validateAuthorization(array $args): void
    {
        // Get context value from arguments (for multi-context queries)
        $contextValue = $args['context'] ?? null;
        
        // Get directive arguments
        $contextRules = $this->directiveArgument('contextRules');
        $requiresAuth = $this->directiveArgument('requiresAuth');
        $roles = $this->directiveArgument('roles', []);
        $validateCompanyOwnership = $this->directiveArgument('validateCompanyOwnership', false);
        $companyIdArg = $this->directiveArgument('companyIdArg');
        $errorMessage = $this->directiveArgument('errorMessage');

        // Determine authorization requirements
        if ($contextRules && $contextValue) {
            // Context-specific rules (for multi-context queries)
            $rule = $this->findContextRule($contextRules, $contextValue);
            
            if ($rule) {
                $this->validateContextRule($rule, $errorMessage);
            }
        } else {
            // Simple rules (apply to all)
            if ($requiresAuth === true || !empty($roles)) {
                $this->validateAuthenticationLogic($requiresAuth, $roles, $errorMessage);
            }
        }

        // Validate company ownership if required
        if ($validateCompanyOwnership && $companyIdArg) {
            $this->validateCompanyOwnershipLogic($args, $companyIdArg);
        }
    }

    /**
     * Find context rule matching the current context value
     *
     * @param array $contextRules
     * @param string|null $contextValue
     * @return array|null
     */
    private function findContextRule(array $contextRules, ?string $contextValue): ?array
    {
        if (!$contextValue) {
            return null;
        }

        foreach ($contextRules as $rule) {
            if (isset($rule['context']) && $rule['context'] === $contextValue) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Validate authorization based on context-specific rule
     *
     * @param array $rule
     * @param string|null $customErrorMessage
     * @throws GraphQLErrorWithExtensions
     */
    private function validateContextRule(array $rule, ?string $customErrorMessage = null): void
    {
        $requiresAuth = $rule['requiresAuth'] ?? false;
        $roles = $rule['roles'] ?? [];
        $errorMessage = $rule['errorMessage'] ?? $customErrorMessage;

        $this->validateAuthenticationLogic($requiresAuth, $roles, $errorMessage);
    }

    /**
     * Validate authentication and role requirements
     *
     * @param bool|null $requiresAuth
     * @param array $roles
     * @param string|null $customErrorMessage
     * @throws GraphQLErrorWithExtensions
     */
    private function validateAuthenticationLogic(
        ?bool $requiresAuth = null,
        array $roles = [],
        ?string $customErrorMessage = null
    ): void {
        $isAuthenticated = JWTHelper::isAuthenticated();

        // Check if authentication is required
        if ($requiresAuth === true && !$isAuthenticated) {
            $message = $customErrorMessage ?? 'Unauthenticated';
            throw new AuthenticationException($message);
        }

        // If no auth required and roles not specified, allow
        if (!$isAuthenticated && empty($roles)) {
            return;
        }

        // If roles specified, user must be authenticated
        if (!empty($roles) && !$isAuthenticated) {
            $message = $customErrorMessage ?? 'Unauthenticated';
            throw new AuthenticationException($message);
        }

        // Check role requirements
        if (!empty($roles) && $isAuthenticated) {
            if (!JWTHelper::hasAnyRole($roles)) {
                $message = $customErrorMessage ?? sprintf(
                    'Unauthorized: required roles %s',
                    implode(', ', $roles)
                );
                throw new ForbiddenException($message);
            }
        }
    }

    /**
     * Validate that user owns the company they're trying to access
     *
     * @param array $args
     * @param string $companyIdArg
     * @throws GraphQLErrorWithExtensions
     */
    private function validateCompanyOwnershipLogic(array $args, string $companyIdArg): void
    {
        if (!JWTHelper::isAuthenticated()) {
            throw new AuthenticationException('Unauthenticated');
        }

        $companyId = $args[$companyIdArg] ?? null;

        if (!$companyId) {
            return; // Company ID not provided in this query
        }

        $user = JWTHelper::getAuthenticatedUser();

        // Load user roles if not already loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Check if user has permission for this company
        $hasAccess = $user->roles->contains(function ($role) use ($companyId) {
            // PLATFORM_ADMIN can access any company
            if ($role->role_code === 'PLATFORM_ADMIN') {
                return true;
            }

            // COMPANY_ADMIN can only access their own company
            if ($role->role_code === 'COMPANY_ADMIN' && $role->company_id === $companyId) {
                return true;
            }

            // AGENT can access their own company
            if ($role->role_code === 'AGENT' && $role->company_id === $companyId) {
                return true;
            }

            return false;
        });

        if (!$hasAccess) {
            throw new ForbiddenException('Unauthorized');
        }
    }
}
