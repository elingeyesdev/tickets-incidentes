<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use GraphQL\Error\Error;
use Nuwave\Lighthouse\Execution\ErrorHandler;

/**
 * GraphQL Error Extensions Preservation Handler
 *
 * Preserves extensions (error codes and metadata) from GraphQL\Error\Error objects
 * that are thrown directly in resolvers.
 *
 * Problem:
 * - Mutations throw: new Error('message', ..., ['code' => 'SOME_CODE'])
 * - Lighthouse's error pipeline doesn't preserve these extensions
 * - Tests fail when accessing $errors[0]['extensions']['code']
 *
 * Solution:
 * - Extracts extensions from GraphQL Error via getExtensions()
 * - Merges them into the formatted error response
 * - Preserves custom error codes and metadata
 *
 * Usage:
 * Register in config/lighthouse.php error_handlers array BEFORE ReportingErrorHandler
 */
class GraphQLErrorPreservationHandler implements ErrorHandler
{
    public function __invoke(?Error $error, \Closure $next): ?array
    {
        // Pass to next handler first to get base error structure
        $result = $next($error);

        // If no error or no result, return as-is
        if ($error === null || $result === null) {
            return $result;
        }

        // Extract extensions directly from GraphQL Error if they exist
        $errorExtensions = $error->getExtensions();

        if ($errorExtensions && is_array($errorExtensions)) {
            // Ensure extensions array exists
            if (!isset($result['extensions'])) {
                $result['extensions'] = [];
            }

            // Merge error extensions into result (custom extensions take precedence)
            foreach ($errorExtensions as $key => $value) {
                if (!isset($result['extensions'][$key])) {
                    $result['extensions'][$key] = $value;
                }
            }
        }

        return $result;
    }
}
