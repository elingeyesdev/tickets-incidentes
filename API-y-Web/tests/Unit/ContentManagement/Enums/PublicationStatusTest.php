<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Enums;

use App\Features\ContentManagement\Enums\PublicationStatus;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * Pruebas unitarias para el enum PublicationStatus
 * Prueba todos los posibles valores de estado de publicación
 */
final class PublicationStatusTest extends TestCase
{
    /**
     * Prueba que el enum tiene todos los valores esperados
     *
     * Verifica que existen los cuatro estados de publicación:
     * - DRAFT: Estado inicial al crear contenido
     * - SCHEDULED: Contenido programado para publicación futura
     * - PUBLISHED: Contenido visible para los usuarios
     * - ARCHIVED: Contenido que ya no es visible pero se conserva
     */
    public function test_enum_has_all_expected_values(): void
    {
        $cases = PublicationStatus::cases();

        $this->assertCount(4, $cases);

        $values = array_map(fn($case) => $case->name, $cases);

        $this->assertContains('DRAFT', $values);
        $this->assertContains('SCHEDULED', $values);
        $this->assertContains('PUBLISHED', $values);
        $this->assertContains('ARCHIVED', $values);
    }

    /**
     * Prueba que los valores del enum son cadenas de texto
     *
     * Verifica que el tipo de valor subyacente es una cadena de texto,
     * lo cual es requerido para el almacenamiento en la base de datos y las respuestas de la API
     */
    public function test_enum_values_are_strings(): void
    {
        $this->assertIsString(PublicationStatus::DRAFT->value);
        $this->assertIsString(PublicationStatus::SCHEDULED->value);
        $this->assertIsString(PublicationStatus::PUBLISHED->value);
        $this->assertIsString(PublicationStatus::ARCHIVED->value);

        $this->assertEquals('DRAFT', PublicationStatus::DRAFT->value);
        $this->assertEquals('SCHEDULED', PublicationStatus::SCHEDULED->value);
        $this->assertEquals('PUBLISHED', PublicationStatus::PUBLISHED->value);
        $this->assertEquals('ARCHIVED', PublicationStatus::ARCHIVED->value);
    }

    /**
     * Prueba que el método from() funciona correctamente
     *
     * Verifica que las instancias del enum se pueden crear a partir de valores de cadena de texto,
     * lo cual es necesario al recibir datos de la base de datos o de la API
     */
    public function test_from_method_works(): void
    {
        $draft = PublicationStatus::from('DRAFT');
        $scheduled = PublicationStatus::from('SCHEDULED');
        $published = PublicationStatus::from('PUBLISHED');
        $archived = PublicationStatus::from('ARCHIVED');

        $this->assertInstanceOf(PublicationStatus::class, $draft);
        $this->assertInstanceOf(PublicationStatus::class, $scheduled);
        $this->assertInstanceOf(PublicationStatus::class, $published);
        $this->assertInstanceOf(PublicationStatus::class, $archived);

        $this->assertEquals(PublicationStatus::DRAFT, $draft);
        $this->assertEquals(PublicationStatus::SCHEDULED, $scheduled);
        $this->assertEquals(PublicationStatus::PUBLISHED, $published);
        $this->assertEquals(PublicationStatus::ARCHIVED, $archived);
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

        PublicationStatus::from('INVALID');
    }
}
