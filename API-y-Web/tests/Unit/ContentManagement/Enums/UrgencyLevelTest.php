<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Enums;

use App\Features\ContentManagement\Enums\UrgencyLevel;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * Pruebas unitarias para el enum UrgencyLevel
 * Prueba todos los valores de nivel de urgencia utilizados en los anuncios
 */
final class UrgencyLevelTest extends TestCase
{
    /**
     * Prueba que el enum tiene todos los niveles de urgencia esperados
     *
     * Verifica que existen los cuatro niveles de urgencia:
     * - LOW: Problemas menores, no se requiere acción inmediata
     * - MEDIUM: Problemas moderados, se requiere acción pronto
     * - HIGH: Problemas significativos, se requiere acción rápida
     * - CRITICAL: Problemas graves, se requiere acción inmediata
     */
    public function test_enum_has_all_expected_values(): void
    {
        $cases = UrgencyLevel::cases();

        $this->assertCount(4, $cases);

        $values = array_map(fn($case) => $case->name, $cases);

        $this->assertContains('LOW', $values);
        $this->assertContains('MEDIUM', $values);
        $this->assertContains('HIGH', $values);
        $this->assertContains('CRITICAL', $values);
    }

    /**
     * Prueba que los valores del enum son cadenas de texto
     *
     * Verifica que el tipo de valor subyacente es una cadena de texto,
     * lo cual es requerido para el almacenamiento en la base de datos y las respuestas de la API
     */
    public function test_enum_values_are_strings(): void
    {
        $this->assertIsString(UrgencyLevel::LOW->value);
        $this->assertIsString(UrgencyLevel::MEDIUM->value);
        $this->assertIsString(UrgencyLevel::HIGH->value);
        $this->assertIsString(UrgencyLevel::CRITICAL->value);

        $this->assertEquals('LOW', UrgencyLevel::LOW->value);
        $this->assertEquals('MEDIUM', UrgencyLevel::MEDIUM->value);
        $this->assertEquals('HIGH', UrgencyLevel::HIGH->value);
        $this->assertEquals('CRITICAL', UrgencyLevel::CRITICAL->value);
    }

    /**
     * Prueba que el método from() funciona correctamente
     *
     * Verifica que las instancias del enum se pueden crear a partir de valores de cadena de texto,
     * lo cual es necesario al recibir datos de la base de datos o de la API
     */
    public function test_from_method_works(): void
    {
        $low = UrgencyLevel::from('LOW');
        $medium = UrgencyLevel::from('MEDIUM');
        $high = UrgencyLevel::from('HIGH');
        $critical = UrgencyLevel::from('CRITICAL');

        $this->assertInstanceOf(UrgencyLevel::class, $low);
        $this->assertInstanceOf(UrgencyLevel::class, $medium);
        $this->assertInstanceOf(UrgencyLevel::class, $high);
        $this->assertInstanceOf(UrgencyLevel::class, $critical);

        $this->assertEquals(UrgencyLevel::LOW, $low);
        $this->assertEquals(UrgencyLevel::MEDIUM, $medium);
        $this->assertEquals(UrgencyLevel::HIGH, $high);
        $this->assertEquals(UrgencyLevel::CRITICAL, $critical);
    }

    /**
     * Prueba que un valor inválido lanza un ValueError
     *
     * Verifica que al intentar crear un enum a partir de un valor de cadena de texto inválido
     * se lanza la excepción ValueError esperada
     */
    public function test_invalid_value_throws_error(): void
    {
        $this->expectException(ValueError::class);

        UrgencyLevel::from('INVALID');
    }

    /**
     * Prueba el orden de los niveles de urgencia
     *
     * Verifica que los niveles de urgencia se pueden comparar y ordenar correctamente.
     * Esto es útil para filtrar y priorizar anuncios.
     */
    public function test_urgency_levels_have_logical_order(): void
    {
        $levels = UrgencyLevel::cases();

        $this->assertEquals(UrgencyLevel::LOW, $levels[0]);
        $this->assertEquals(UrgencyLevel::MEDIUM, $levels[1]);
        $this->assertEquals(UrgencyLevel::HIGH, $levels[2]);
        $this->assertEquals(UrgencyLevel::CRITICAL, $levels[3]);
    }
}
