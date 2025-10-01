<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

/**
 * Scalar JSON
 *
 * Acepta cualquier valor JSON válido
 */
class JSON extends ScalarType
{
    public string $name = 'JSON';
    public ?string $description = 'Arbitrary JSON object';

    public function serialize($value)
    {
        return $value; // Ya puede ser array, object, etc
    }

    public function parseValue($value)
    {
        return $value; // Acepta cualquier valor
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        // Para JSON, aceptamos strings que contengan JSON válido
        if ($valueNode instanceof StringValueNode) {
            $decoded = json_decode($valueNode->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Error('Query error: Not valid JSON: ' . json_last_error_msg(), $valueNode);
            }
            return $decoded;
        }

        // También aceptamos objetos y arrays directamente
        return $valueNode;
    }
}