<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Enums;

use App\Features\ContentManagement\Enums\NewsType;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * Pruebas unitarias para el enum NewsType
 * Prueba todos los valores de tipos de noticias utilizados en los anuncios de noticias
 */
final class NewsTypeTest extends TestCase
{
    /**
     * Prueba que el enum tiene todos los tipos de noticias esperados
     *
     * Verifica que existen los tres tipos de noticias:
     * - FEATURE_RELEASE: Lanzamientos de nuevas características y funcionalidades
     * - POLICY_UPDATE: Cambios en las políticas o términos de la empresa
     * - GENERAL_UPDATE: Noticias y actualizaciones generales
     */
    public function test_enum_has_all_expected_values(): void
    {
        $cases = NewsType::cases();

        $this->assertCount(3, $cases);

        $values = array_map(fn($case) => $case->name, $cases);

        $this->assertContains('FEATURE_RELEASE', $values);
        $this->assertContains('POLICY_UPDATE', $values);
        $this->assertContains('GENERAL_UPDATE', $values);
    }

    /**
     * Prueba que los valores del enum son cadenas de texto
     *
     * Verifica que el tipo de valor subyacente es una cadena de texto,
     * lo cual es requerido para el almacenamiento en la base de datos y las respuestas de la API.
     * Nota: Los valores usan snake_case por consistencia con los estándares de la API.
     */
    public function test_enum_values_are_strings(): void
    {
        $this->assertIsString(NewsType::FEATURE_RELEASE->value);
        $this->assertIsString(NewsType::POLICY_UPDATE->value);
        $this->assertIsString(NewsType::GENERAL_UPDATE->value);

        $this->assertEquals('feature_release', NewsType::FEATURE_RELEASE->value);
        $this->assertEquals('policy_update', NewsType::POLICY_UPDATE->value);
        $this->assertEquals('general_update', NewsType::GENERAL_UPDATE->value);
    }

    /**
     * Prueba que el método from() funciona correctamente
     *
     * Verifica que las instancias del enum se pueden crear a partir de valores de cadena de texto,
     * lo cual es necesario al recibir datos de la base de datos o de la API.
     * Nota: Usa valores en snake_case, no en MAYÚSCULAS como los nombres del enum.
     */
    public function test_from_method_works(): void
    {
        $featureRelease = NewsType::from('feature_release');
        $policyUpdate = NewsType::from('policy_update');
        $generalUpdate = NewsType::from('general_update');

        $this->assertInstanceOf(NewsType::class, $featureRelease);
        $this->assertInstanceOf(NewsType::class, $policyUpdate);
        $this->assertInstanceOf(NewsType::class, $generalUpdate);

        $this->assertEquals(NewsType::FEATURE_RELEASE, $featureRelease);
        $this->assertEquals(NewsType::POLICY_UPDATE, $policyUpdate);
        $this->assertEquals(NewsType::GENERAL_UPDATE, $generalUpdate);
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

        NewsType::from('invalid_news_type');
    }

    /**
     * Prueba que un valor en MAYÚSCULAS lanza un ValueError
     *
     * NewsType usa valores en snake_case, por lo que intentar usar
     * MAYÚSCULAS debería fallar. Esto previene confusiones.
     */
    public function test_uppercase_value_throws_error(): void
    {
        $this->expectException(ValueError::class);

        NewsType::from('FEATURE_RELEASE');
    }
}
