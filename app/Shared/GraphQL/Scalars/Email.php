<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

/**
 * Scalar Email
 *
 * Valida que el valor sea un email válido
 */
class Email extends ScalarType
{
    public string $name = 'Email';
    public ?string $description = 'Email address';

    public function serialize($value): string
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Error('Cannot represent value as Email: ' . Utils::printSafe($value));
        }
        return (string) $value;
    }

    public function parseValue($value): string
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Error('Cannot represent following value as Email: ' . Utils::printSafe($value));
        }
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (! $valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings as Email, got: ' . $valueNode->kind, $valueNode);
        }

        if (! filter_var($valueNode->value, FILTER_VALIDATE_EMAIL)) {
            throw new Error('Query error: Not a valid email: ' . Utils::printSafe($valueNode->value), $valueNode);
        }

        return $valueNode->value;
    }
}