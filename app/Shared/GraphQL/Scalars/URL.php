<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

/**
 * Scalar URL
 *
 * Valida que el valor sea una URL válida
 */
class URL extends ScalarType
{
    public string $name = 'URL';
    public ?string $description = 'Valid URL';

    public function serialize($value): string
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw new Error('Cannot represent value as URL: ' . Utils::printSafe($value));
        }
        return (string) $value;
    }

    public function parseValue($value): string
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            throw new Error('Cannot represent following value as URL: ' . Utils::printSafe($value));
        }
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (! $valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings as URL, got: ' . $valueNode->kind, $valueNode);
        }

        if (! filter_var($valueNode->value, FILTER_VALIDATE_URL)) {
            throw new Error('Query error: Not a valid URL: ' . Utils::printSafe($valueNode->value), $valueNode);
        }

        return $valueNode->value;
    }
}