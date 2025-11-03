<?php

declare(strict_types=1);

namespace Tests\Unit\ContentManagement\Enums;

use App\Features\ContentManagement\Enums\AnnouncementType;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * Pruebas unitarias para el enum AnnouncementType
 * Prueba todos los valores de tipos de anuncio y sus esquemas de metadatos
 */
final class AnnouncementTypeTest extends TestCase
{
    /**
     * Prueba que el enum tiene exactamente cuatro tipos de anuncio
     *
     * Verifica que existen los cuatro tipos de anuncio:
     * - MAINTENANCE: Ventanas de mantenimiento programado
     * - INCIDENT: Incidentes y caídas del sistema
     * - NEWS: Noticias y actualizaciones de la empresa
     * - ALERT: Alertas críticas que requieren atención
     */
    public function test_enum_has_four_types(): void
    {
        $cases = AnnouncementType::cases();

        $this->assertCount(4, $cases);

        $values = array_map(fn($case) => $case->name, $cases);

        $this->assertContains('MAINTENANCE', $values);
        $this->assertContains('INCIDENT', $values);
        $this->assertContains('NEWS', $values);
        $this->assertContains('ALERT', $values);

        // Verify enum values are strings
        $this->assertIsString(AnnouncementType::MAINTENANCE->value);
        $this->assertIsString(AnnouncementType::INCIDENT->value);
        $this->assertIsString(AnnouncementType::NEWS->value);
        $this->assertIsString(AnnouncementType::ALERT->value);
    }

    /**
     * Prueba que el método metadataSchema() devuelve un array
     *
     * Cada tipo de anuncio debe proporcionar un esquema de metadatos
     * que define los campos requeridos y opcionales para ese tipo.
     * Este esquema se utiliza para la validación y la documentación de la API.
     */
    public function test_metadata_schema_method_returns_array(): void
    {
        $maintenanceSchema = AnnouncementType::MAINTENANCE->metadataSchema();
        $incidentSchema = AnnouncementType::INCIDENT->metadataSchema();
        $newsSchema = AnnouncementType::NEWS->metadataSchema();
        $alertSchema = AnnouncementType::ALERT->metadataSchema();

        $this->assertIsArray($maintenanceSchema);
        $this->assertIsArray($incidentSchema);
        $this->assertIsArray($newsSchema);
        $this->assertIsArray($alertSchema);

        // Cada esquema debe tener las claves 'required' y 'optional'
        $this->assertArrayHasKey('required', $maintenanceSchema);
        $this->assertArrayHasKey('optional', $maintenanceSchema);

        $this->assertArrayHasKey('required', $incidentSchema);
        $this->assertArrayHasKey('optional', $incidentSchema);

        $this->assertArrayHasKey('required', $newsSchema);
        $this->assertArrayHasKey('optional', $newsSchema);

        $this->assertArrayHasKey('required', $alertSchema);
        $this->assertArrayHasKey('optional', $alertSchema);
    }

    /**
     * Prueba que cada tipo de anuncio tiene campos requeridos únicos
     *
     * Diferentes tipos de anuncio tienen diferentes requisitos de metadatos:
     * - MAINTENANCE: scheduled_start, scheduled_end, affected_services
     * - INCIDENT: started_at, is_resolved, affected_services
     * - NEWS: news_type, target_audience, summary
     * - ALERT: alert_type, message, action_required
     */
    public function test_each_type_has_unique_required_fields(): void
    {
        $maintenanceSchema = AnnouncementType::MAINTENANCE->metadataSchema();
        $incidentSchema = AnnouncementType::INCIDENT->metadataSchema();
        $newsSchema = AnnouncementType::NEWS->metadataSchema();
        $alertSchema = AnnouncementType::ALERT->metadataSchema();

        // MAINTENANCE debe requerir scheduled_start y scheduled_end
        $this->assertContains('scheduled_start', $maintenanceSchema['required']);
        $this->assertContains('scheduled_end', $maintenanceSchema['required']);
        $this->assertContains('is_emergency', $maintenanceSchema['required']);

        // INCIDENT debe requerir started_at y is_resolved
        $this->assertContains('started_at', $incidentSchema['required']);
        $this->assertContains('is_resolved', $incidentSchema['required']);

        // NEWS debe requerir news_type y target_audience
        $this->assertContains('news_type', $newsSchema['required']);
        $this->assertContains('target_audience', $newsSchema['required']);
        $this->assertContains('summary', $newsSchema['required']);

        // ALERT debe requerir alert_type y message
        $this->assertContains('alert_type', $alertSchema['required']);
        $this->assertContains('message', $alertSchema['required']);
        $this->assertContains('action_required', $alertSchema['required']);

        // Verifica que los campos requeridos son diferentes entre tipos
        $this->assertNotEquals($maintenanceSchema['required'], $incidentSchema['required']);
        $this->assertNotEquals($incidentSchema['required'], $newsSchema['required']);
        $this->assertNotEquals($newsSchema['required'], $alertSchema['required']);
    }

    /**
     * Prueba que el método from() funciona correctamente
     *
     * Verifica que las instancias del enum se pueden crear a partir de valores de cadena de texto
     */
    public function test_from_method_works(): void
    {
        $maintenance = AnnouncementType::from('MAINTENANCE');
        $incident = AnnouncementType::from('INCIDENT');
        $news = AnnouncementType::from('NEWS');
        $alert = AnnouncementType::from('ALERT');

        $this->assertInstanceOf(AnnouncementType::class, $maintenance);
        $this->assertInstanceOf(AnnouncementType::class, $incident);
        $this->assertInstanceOf(AnnouncementType::class, $news);
        $this->assertInstanceOf(AnnouncementType::class, $alert);

        $this->assertEquals(AnnouncementType::MAINTENANCE, $maintenance);
        $this->assertEquals(AnnouncementType::INCIDENT, $incident);
        $this->assertEquals(AnnouncementType::NEWS, $news);
        $this->assertEquals(AnnouncementType::ALERT, $alert);
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

        AnnouncementType::from('INVALID');
    }
}
