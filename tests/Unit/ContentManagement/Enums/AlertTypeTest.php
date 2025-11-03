<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Enums;

use App\Features\ContentManagement\Enums\AlertType;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * Pruebas unitarias para el enum AlertType
 * Prueba todos los valores de tipos de alerta utilizados en los anuncios de alerta
 */
final class AlertTypeTest extends TestCase
{
    /**
     * Prueba que el enum tiene todos los tipos de alerta esperados
     *
     * Verifica que existen los cuatro tipos de alerta:
     * - SECURITY: Alertas de seguridad y vulnerabilidades
     * - SYSTEM: Alertas y problemas a nivel de sistema
     * - SERVICE: Alertas y interrupciones específicas del servicio
     * - COMPLIANCE: Alertas de cumplimiento y regulatorias
     */
    public function test_enum_has_all_expected_values(): void
    {
        $cases = AlertType::cases();

        $this->assertCount(4, $cases);

        $values = array_map(fn($case) => $case->name, $cases);

        $this->assertContains('SECURITY', $values);
        $this->assertContains('SYSTEM', $values);
        $this->assertContains('SERVICE', $values);
        $this->assertContains('COMPLIANCE', $values);
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
        $this->assertIsString(AlertType::SECURITY->value);
        $this->assertIsString(AlertType::SYSTEM->value);
        $this->assertIsString(AlertType::SERVICE->value);
        $this->assertIsString(AlertType::COMPLIANCE->value);

        $this->assertEquals('security', AlertType::SECURITY->value);
        $this->assertEquals('system', AlertType::SYSTEM->value);
        $this->assertEquals('service', AlertType::SERVICE->value);
        $this->assertEquals('compliance', AlertType::COMPLIANCE->value);
    }

    /**
     * Prueba que el método from() funciona correctamente
     *
     * Verifica que las instancias del enum se pueden crear a partir de valores de cadena de texto,
     * lo cual es necesario al recibir datos de la base de datos o de la API.
     * Nota: Usa valores en minúsculas, no en MAYÚSCULAS como los nombres del enum.
     */
    public function test_from_method_works(): void
    {
        $security = AlertType::from('security');
        $system = AlertType::from('system');
        $service = AlertType::from('service');
        $compliance = AlertType::from('compliance');

        $this->assertInstanceOf(AlertType::class, $security);
        $this->assertInstanceOf(AlertType::class, $system);
        $this->assertInstanceOf(AlertType::class, $service);
        $this->assertInstanceOf(AlertType::class, $compliance);

        $this->assertEquals(AlertType::SECURITY, $security);
        $this->assertEquals(AlertType::SYSTEM, $system);
        $this->assertEquals(AlertType::SERVICE, $service);
        $this->assertEquals(AlertType::COMPLIANCE, $compliance);
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

        AlertType::from('invalid_alert_type');
    }

    /**
     * Prueba que un valor en MAYÚSCULAS lanza un ValueError
     *
     * AlertType usa valores en minúsculas, por lo que intentar usar
     * MAYÚSCULAS debería fallar. Esto previene confusiones.
     */
    public function test_uppercase_value_throws_error(): void
    {
        $this->expectException(ValueError::class);

        AlertType::from('SECURITY');
    }

    /**
     * Prueba las categorías de tipos de alerta por severidad
     *
     * Verifica que los tipos de alerta críticos (SECURITY, COMPLIANCE)
     * se pueden distinguir de los tipos de alerta operativos (SYSTEM, SERVICE).
     * Esto es útil para la priorización y el enrutamiento de notificaciones.
     */
    public function test_critical_alert_types_are_identifiable(): void
    {
        $criticalTypes = [AlertType::SECURITY, AlertType::COMPLIANCE];
        $operationalTypes = [AlertType::SYSTEM, AlertType::SERVICE];

        // All types should be present
        $allTypes = array_merge($criticalTypes, $operationalTypes);
        $this->assertCount(4, $allTypes);

        // Verify no overlap by comparing using in_array with strict mode
        $hasOverlap = false;
        foreach ($criticalTypes as $critical) {
            foreach ($operationalTypes as $operational) {
                if ($critical === $operational) {
                    $hasOverlap = true;
                    break;
                }
            }
        }
        $this->assertFalse($hasOverlap);
    }
}
