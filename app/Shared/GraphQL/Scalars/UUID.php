<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use Ramsey\Uuid\Uuid as RamseyUuid;

/**
 * Scalar UUID v4
 *
 * Valida que el valor sea un UUID válido en formato v4
 * Formato: 8-4-4-4-12 caracteres hexadecimales
 * Ejemplo: "550e8400-e29b-41d4-a716-446655440000"
 */
class UUID extends ScalarType
{
    /**
     * Nombre del scalar en el schema GraphQL
     */
    public string $name = 'UUID';

    /**
     * Descripción del scalar
     */
    public ?string $description = 'UUID v4 para identificadores únicos del sistema';

    /**
     * Serializa un valor interno de PHP a un valor que puede ser enviado al cliente
     *
     * @param  mixed  $value
     * @return string
     * @throws Error
     */
    public function serialize($value): string
    {
        if (! RamseyUuid::isValid((string) $value)) {
            throw new Error(
                'Cannot represent value as UUID: ' . Utils::printSafe($value)
            );
        }

        return (string) $value;
    }

    /**
     * Parsea un valor de entrada del cliente a un valor interno de PHP
     *
     * @param  mixed  $value
     * @return string
     * @throws Error
     */
    public function parseValue($value): string
    {
        if (! is_string($value) || ! RamseyUuid::isValid($value)) {
            throw new Error(
                'Cannot represent following value as UUID: ' . Utils::printSafe($value)
            );
        }

        return $value;
    }

    /**
     * Parsea un valor literal del AST a un valor interno de PHP
     *
     * @param  Node  $valueNode
     * @param  array<string, mixed>|null  $variables
     * @return string
     * @throws Error
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (! $valueNode instanceof StringValueNode) {
            throw new Error(
                'Query error: Can only parse strings as UUID, got: ' . $valueNode->kind,
                $valueNode
            );
        }

        if (! RamseyUuid::isValid($valueNode->value)) {
            throw new Error(
                'Query error: Not a valid UUID: ' . Utils::printSafe($valueNode->value),
                $valueNode
            );
        }

        return $valueNode->value;
    }
}