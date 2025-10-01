<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

/**
 * Scalar PhoneNumber
 *
 * Valida que el valor sea un número de teléfono válido
 * Formato esperado: +[código país][número] (formato internacional E.164)
 * Ejemplo: "+59178901234"
 *
 * También acepta formatos más flexibles:
 * - Con espacios: "+591 789 01234"
 * - Con guiones: "+591-789-01234"
 * - Con paréntesis: "+591 (7) 890-1234"
 */
class PhoneNumber extends ScalarType
{
    /**
     * Nombre del scalar en el schema GraphQL
     */
    public string $name = 'PhoneNumber';

    /**
     * Descripción del scalar
     */
    public ?string $description = 'Número de teléfono en formato internacional E.164';

    /**
     * Patrón regex para validación flexible de teléfono
     * Acepta: +[1-3 dígitos][espacios/guiones][7-15 dígitos]
     */
    private const PHONE_REGEX = '/^\+[1-9]\d{1,2}[\s\-\(\)]?[\d\s\-\(\)]{7,15}$/';

    /**
     * Serializa un valor interno de PHP a un valor que puede ser enviado al cliente
     *
     * @param  mixed  $value
     * @return string
     * @throws Error
     */
    public function serialize($value): string
    {
        $cleanValue = $this->cleanPhoneNumber((string) $value);

        if (! $this->isValidPhoneNumber($cleanValue)) {
            throw new Error(
                'Cannot represent value as PhoneNumber: ' . Utils::printSafe($value)
            );
        }

        return $cleanValue;
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
        if (! is_string($value)) {
            throw new Error(
                'Cannot represent following value as PhoneNumber: ' . Utils::printSafe($value)
            );
        }

        $cleanValue = $this->cleanPhoneNumber($value);

        if (! $this->isValidPhoneNumber($cleanValue)) {
            throw new Error(
                'Not a valid phone number format. Expected: +[country code][number]'
            );
        }

        return $cleanValue;
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
                'Query error: Can only parse strings as PhoneNumber, got: ' . $valueNode->kind,
                $valueNode
            );
        }

        $cleanValue = $this->cleanPhoneNumber($valueNode->value);

        if (! $this->isValidPhoneNumber($cleanValue)) {
            throw new Error(
                'Query error: Not a valid phone number: ' . Utils::printSafe($valueNode->value),
                $valueNode
            );
        }

        return $cleanValue;
    }

    /**
     * Limpia el número de teléfono removiendo caracteres no numéricos (excepto +)
     *
     * @param  string  $phone
     * @return string
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Mantener solo números y el símbolo +
        return preg_replace('/[^\d+]/', '', $phone) ?? $phone;
    }

    /**
     * Valida si el número de teléfono es válido
     *
     * @param  string  $phone
     * @return bool
     */
    private function isValidPhoneNumber(string $phone): bool
    {
        // Debe empezar con + seguido de 8-15 dígitos
        return preg_match('/^\+\d{8,15}$/', $phone) === 1;
    }
}