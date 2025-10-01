<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

/**
 * Scalar HexColor
 *
 * Valida que el valor sea un color hexadecimal válido
 * Formatos aceptados:
 * - #RRGGBB (6 caracteres): "#FF5733"
 * - #RGB (3 caracteres): "#F57"
 *
 * Siempre normaliza al formato de 6 caracteres (#RRGGBB)
 */
class HexColor extends ScalarType
{
    /**
     * Nombre del scalar en el schema GraphQL
     */
    public string $name = 'HexColor';

    /**
     * Descripción del scalar
     */
    public ?string $description = 'Color hexadecimal en formato #RRGGBB o #RGB';

    /**
     * Patrón regex para validación de color hex
     * Acepta: #RGB o #RRGGBB
     */
    private const HEX_COLOR_REGEX = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    /**
     * Serializa un valor interno de PHP a un valor que puede ser enviado al cliente
     *
     * @param  mixed  $value
     * @return string
     * @throws Error
     */
    public function serialize($value): string
    {
        $normalizedValue = $this->normalizeHexColor((string) $value);

        if ($normalizedValue === null) {
            throw new Error(
                'Cannot represent value as HexColor: ' . Utils::printSafe($value)
            );
        }

        return $normalizedValue;
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
                'Cannot represent following value as HexColor: ' . Utils::printSafe($value)
            );
        }

        $normalizedValue = $this->normalizeHexColor($value);

        if ($normalizedValue === null) {
            throw new Error(
                'Not a valid hex color format. Expected: #RRGGBB or #RGB'
            );
        }

        return $normalizedValue;
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
                'Query error: Can only parse strings as HexColor, got: ' . $valueNode->kind,
                $valueNode
            );
        }

        $normalizedValue = $this->normalizeHexColor($valueNode->value);

        if ($normalizedValue === null) {
            throw new Error(
                'Query error: Not a valid hex color: ' . Utils::printSafe($valueNode->value),
                $valueNode
            );
        }

        return $normalizedValue;
    }

    /**
     * Normaliza el color hexadecimal al formato de 6 caracteres (#RRGGBB)
     *
     * @param  string  $color
     * @return string|null
     */
    private function normalizeHexColor(string $color): ?string
    {
        // Convertir a mayúsculas para consistencia
        $color = strtoupper(trim($color));

        // Validar formato básico
        if (! preg_match(self::HEX_COLOR_REGEX, $color)) {
            return null;
        }

        // Si es formato corto (#RGB), expandir a #RRGGBB
        if (strlen($color) === 4) {
            return '#' .
                   $color[1] . $color[1] .
                   $color[2] . $color[2] .
                   $color[3] . $color[3];
        }

        // Ya está en formato largo
        return $color;
    }
}