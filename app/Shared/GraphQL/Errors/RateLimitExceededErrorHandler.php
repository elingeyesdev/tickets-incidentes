<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

use App\Shared\Exceptions\RateLimitExceededException;
use Closure;
use GraphQL\Error\Error;
use Nuwave\Lighthouse\Execution\ErrorHandler;

final class RateLimitExceededErrorHandler implements ErrorHandler
{
    public function handle(Error $error, Closure $next): Error
    {
        $previous = $error->getPrevious();

        if ($previous instanceof RateLimitExceededException) {
            // The exception already implements ClientAware and ProvidesExtensions
            // We just need to make sure its extensions are added to the error.
            $error->extensions = array_merge($error->extensions ?? [], $previous->getExtensions());
        }

        return $next($error);
    }
}
