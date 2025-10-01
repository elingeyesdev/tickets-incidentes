<?php

namespace App\Shared\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Generador de códigos únicos para el sistema
 *
 * Genera códigos legibles en formato: PREFIX-YYYY-NNNNN
 * Ejemplos:
 * - USR-2025-00123
 * - CMP-2025-00045
 * - TKT-2025-10567
 * - REQ-2025-00003
 *
 * @usage
 * ```php
 * $userCode = CodeGenerator::generate('users', 'USR');
 * // Resultado: USR-2025-00123
 * ```
 */
class CodeGenerator
{
    /**
     * Longitud del número secuencial (con padding)
     */
    private const SEQUENCE_LENGTH = 5;

    /**
     * Genera un código único para una tabla específica
     *
     * @param string $table Nombre de la tabla (ej: 'users', 'companies')
     * @param string $prefix Prefijo del código (ej: 'USR', 'CMP')
     * @param string $column Nombre de la columna del código (por defecto: '{table}_code')
     * @return string Código generado (ej: 'USR-2025-00123')
     */
    public static function generate(
        string $table,
        string $prefix,
        ?string $column = null
    ): string {
        $column = $column ?? self::getDefaultColumnName($table);
        $year = now()->year;
        $pattern = "{$prefix}-{$year}-%";

        // Obtener el último código del año actual
        $lastCode = DB::table($table)
            ->where($column, 'LIKE', $pattern)
            ->orderBy($column, 'desc')
            ->value($column);

        $nextNumber = $lastCode
            ? self::extractNumber($lastCode) + 1
            : 1;

        return self::format($prefix, $year, $nextNumber);
    }

    /**
     * Genera el siguiente código basándose en el último código existente
     *
     * @param string|null $lastCode Último código existente
     * @param string $prefix Prefijo del código
     * @return string Siguiente código
     */
    public static function generateNext(?string $lastCode, string $prefix): string
    {
        $year = now()->year;

        if (!$lastCode) {
            return self::format($prefix, $year, 1);
        }

        $lastYear = self::extractYear($lastCode);

        // Si cambió el año, reiniciar contador
        if ($lastYear !== $year) {
            return self::format($prefix, $year, 1);
        }

        $nextNumber = self::extractNumber($lastCode) + 1;
        return self::format($prefix, $year, $nextNumber);
    }

    /**
     * Formatea un código con el patrón estándar
     *
     * @param string $prefix Prefijo (ej: 'USR')
     * @param int $year Año (ej: 2025)
     * @param int $number Número secuencial (ej: 123)
     * @return string Código formateado (ej: 'USR-2025-00123')
     */
    public static function format(string $prefix, int $year, int $number): string
    {
        $paddedNumber = str_pad(
            (string) $number,
            self::SEQUENCE_LENGTH,
            '0',
            STR_PAD_LEFT
        );

        return "{$prefix}-{$year}-{$paddedNumber}";
    }

    /**
     * Extrae el número secuencial de un código
     *
     * @param string $code Código completo (ej: 'USR-2025-00123')
     * @return int Número extraído (ej: 123)
     */
    public static function extractNumber(string $code): int
    {
        $parts = explode('-', $code);
        return (int) end($parts);
    }

    /**
     * Extrae el año de un código
     *
     * @param string $code Código completo (ej: 'USR-2025-00123')
     * @return int Año extraído (ej: 2025)
     */
    public static function extractYear(string $code): int
    {
        $parts = explode('-', $code);
        return (int) ($parts[1] ?? 0);
    }

    /**
     * Extrae el prefijo de un código
     *
     * @param string $code Código completo (ej: 'USR-2025-00123')
     * @return string Prefijo extraído (ej: 'USR')
     */
    public static function extractPrefix(string $code): string
    {
        $parts = explode('-', $code);
        return $parts[0] ?? '';
    }

    /**
     * Valida que un código tenga el formato correcto
     *
     * @param string $code Código a validar
     * @return bool True si es válido
     */
    public static function isValid(string $code): bool
    {
        // Patrón: PREFIX-YYYY-NNNNN
        $pattern = '/^[A-Z]{3}-\d{4}-\d{' . self::SEQUENCE_LENGTH . '}$/';
        return (bool) preg_match($pattern, $code);
    }

    /**
     * Obtiene el nombre de columna por defecto para una tabla
     *
     * @param string $table Nombre de la tabla (ej: 'users')
     * @return string Nombre de columna (ej: 'user_code')
     */
    private static function getDefaultColumnName(string $table): string
    {
        // Convertir plural a singular: 'users' -> 'user'
        $singular = rtrim($table, 's');
        return "{$singular}_code";
    }

    /**
     * Prefijos estándar del sistema
     */
    public const USER = 'USR';
    public const COMPANY = 'CMP';
    public const COMPANY_REQUEST = 'REQ';
    public const TICKET = 'TKT';
    public const TICKET_RESPONSE = 'RSP';
    public const CATEGORY = 'CAT';
    public const MACRO = 'MCR';
}