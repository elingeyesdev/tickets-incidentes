<?php

namespace Tests\Unit\ContentManagement\Rules;

use App\Features\ContentManagement\Rules\ValidAnnouncementMetadata;
use PHPUnit\Framework\TestCase;

class ValidAnnouncementMetadataTest extends TestCase
{
    /**
     * Prueba 1: Valida la estructura de metadatos de mantenimiento
     */
    public function test_validates_maintenance_metadata_structure(): void
    {
        $rule = new ValidAnnouncementMetadata('MAINTENANCE');

        $metadata = [
            'urgency' => 'MEDIUM',
            'scheduled_start' => '2025-11-09 10:00:00',
            'scheduled_end' => '2025-11-09 14:00:00',
            'is_emergency' => false,
            'affected_services' => ['reports', 'analytics']
        ];

        $this->assertTrue($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba 2: Mantenimiento requiere scheduled_start
     */
    public function test_maintenance_requires_scheduled_start(): void
    {
        $rule = new ValidAnnouncementMetadata('MAINTENANCE');

        $metadata = [
            'urgency' => 'MEDIUM',
            // 'scheduled_start' no está presente
            'scheduled_end' => '2025-11-09 14:00:00',
            'is_emergency' => false
        ];

        $this->assertFalse($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba 3: scheduled_end de mantenimiento debe ser posterior a scheduled_start
     */
    public function test_maintenance_scheduled_end_after_start(): void
    {
        $rule = new ValidAnnouncementMetadata('MAINTENANCE');

        $metadata = [
            'urgency' => 'MEDIUM',
            'scheduled_start' => '2025-11-09 14:00:00',
            'scheduled_end' => '2025-11-09 10:00:00', // Antes del inicio (inválido)
            'is_emergency' => false
        ];

        $this->assertFalse($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba 4: Incidente requiere started_at
     */
    public function test_incident_requires_started_at(): void
    {
        $rule = new ValidAnnouncementMetadata('INCIDENT');

        $metadata = [
            'urgency' => 'CRITICAL',
            'is_resolved' => false,
            // 'started_at' no está presente
            'affected_services' => ['login', 'api']
        ];

        $this->assertFalse($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba 5: resolution_content de incidente es requerido cuando is_resolved=true
     */
    public function test_incident_resolution_content_required_when_resolved(): void
    {
        $rule = new ValidAnnouncementMetadata('INCIDENT');

        $metadata = [
            'urgency' => 'HIGH',
            'is_resolved' => true,
            'started_at' => '2025-11-02 18:45:00',
            'resolved_at' => '2025-11-02 20:30:00',
            // 'resolution_content' no está presente pero es requerido cuando is_resolved=true
        ];

        $this->assertFalse($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba 6: Noticias requiere que target_audience sea un array
     */
    public function test_news_requires_target_audience_array(): void
    {
        $rule = new ValidAnnouncementMetadata('NEWS');

        $metadata = [
            'news_type' => 'feature_release',
            'target_audience' => 'users', // String en lugar de array (inválido)
            'summary' => 'New feature announcement for all users'
        ];

        $this->assertFalse($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba 7: action_description de alerta es requerido cuando action_required=true
     */
    public function test_alert_action_description_required_when_action_required(): void
    {
        $rule = new ValidAnnouncementMetadata('ALERT');

        $metadata = [
            'urgency' => 'CRITICAL',
            'alert_type' => 'security',
            'message' => 'Critical security alert requiring immediate action',
            'action_required' => true,
            // 'action_description' no está presente pero es requerido cuando action_required=true
            'started_at' => '2025-11-02 22:00:00'
        ];

        $this->assertFalse($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba 8: La regla devuelve mensajes de error correctos
     */
    public function test_rule_returns_correct_error_messages(): void
    {
        // Probar MAINTENANCE sin scheduled_start
        $maintenanceRule = new ValidAnnouncementMetadata('MAINTENANCE');
        $maintenanceMetadata = [
            'urgency' => 'MEDIUM',
            'scheduled_end' => '2025-11-09 14:00:00',
            'is_emergency' => false
        ];

        $this->assertFalse($maintenanceRule->passes('metadata', $maintenanceMetadata));
        $this->assertStringContainsString('scheduled_start', $maintenanceRule->message());

        // Probar INCIDENT sin started_at
        $incidentRule = new ValidAnnouncementMetadata('INCIDENT');
        $incidentMetadata = [
            'urgency' => 'CRITICAL',
            'is_resolved' => false
        ];

        $this->assertFalse($incidentRule->passes('metadata', $incidentMetadata));
        $this->assertStringContainsString('started_at', $incidentRule->message());

        // Probar NEWS con target_audience inválido
        $newsRule = new ValidAnnouncementMetadata('NEWS');
        $newsMetadata = [
            'news_type' => 'feature_release',
            'target_audience' => 'invalid',
            'summary' => 'Test summary'
        ];

        $this->assertFalse($newsRule->passes('metadata', $newsMetadata));
        $this->assertStringContainsString('target_audience', $newsRule->message());

        // Probar ALERT sin action_description
        $alertRule = new ValidAnnouncementMetadata('ALERT');
        $alertMetadata = [
            'urgency' => 'HIGH',
            'alert_type' => 'system',
            'message' => 'System alert message',
            'action_required' => true,
            'started_at' => '2025-11-02 22:00:00'
        ];

        $this->assertFalse($alertRule->passes('metadata', $alertMetadata));
        $this->assertStringContainsString('action_description', $alertRule->message());
    }

    /**
     * Prueba Adicional: Metadatos de INCIDENTE válidos con resolución
     */
    public function test_validates_incident_metadata_with_resolution(): void
    {
        $rule = new ValidAnnouncementMetadata('INCIDENT');

        $metadata = [
            'urgency' => 'HIGH',
            'is_resolved' => true,
            'started_at' => '2025-11-02 18:45:00',
            'resolved_at' => '2025-11-02 20:30:00',
            'resolution_content' => 'Issue fixed by restarting the authentication server',
            'ended_at' => '2025-11-02 20:30:00',
            'affected_services' => ['login', 'api']
        ];

        $this->assertTrue($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba Adicional: Metadatos de NEWS válidos con call_to_action
     */
    public function test_validates_news_metadata_with_call_to_action(): void
    {
        $rule = new ValidAnnouncementMetadata('NEWS');

        $metadata = [
            'news_type' => 'feature_release',
            'target_audience' => ['users', 'agents'],
            'summary' => 'New Excel export feature available for all tickets',
            'call_to_action' => [
                'text' => 'Learn More',
                'url' => 'https://docs.company.com/excel-export'
            ]
        ];

        $this->assertTrue($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba Adicional: Metadatos de ALERT válidos sin acción requerida
     */
    public function test_validates_alert_metadata_without_action_required(): void
    {
        $rule = new ValidAnnouncementMetadata('ALERT');

        $metadata = [
            'urgency' => 'HIGH',
            'alert_type' => 'service',
            'message' => 'Service degradation detected on payment processing',
            'action_required' => false,
            'started_at' => '2025-11-02 20:00:00',
            'affected_services' => ['payments']
        ];

        $this->assertTrue($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba Adicional: MAINTENANCE con actual_start y actual_end opcionales
     */
    public function test_validates_maintenance_with_actual_times(): void
    {
        $rule = new ValidAnnouncementMetadata('MAINTENANCE');

        $metadata = [
            'urgency' => 'HIGH',
            'scheduled_start' => '2025-11-09 10:00:00',
            'scheduled_end' => '2025-11-09 14:00:00',
            'is_emergency' => false,
            'actual_start' => '2025-11-09 09:58:00',
            'actual_end' => '2025-11-09 13:45:00',
            'affected_services' => ['database', 'reports']
        ];

        $this->assertTrue($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba Adicional: INCIDENT no resuelto (is_resolved=false) no requiere campos de resolución
     */
    public function test_incident_unresolved_does_not_require_resolution_fields(): void
    {
        $rule = new ValidAnnouncementMetadata('INCIDENT');

        $metadata = [
            'urgency' => 'CRITICAL',
            'is_resolved' => false,
            'started_at' => '2025-11-02 18:45:00',
            'affected_services' => ['login', 'api']
            // No resolution_content o resolved_at (válido para incidentes no resueltos)
        ];

        $this->assertTrue($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba Adicional: ALERT con action_required=false puede omitir action_description
     */
    public function test_alert_without_action_required_can_omit_action_description(): void
    {
        $rule = new ValidAnnouncementMetadata('ALERT');

        $metadata = [
            'urgency' => 'CRITICAL',
            'alert_type' => 'security',
            'message' => 'Potential security breach detected, investigating',
            'action_required' => false,
            'started_at' => '2025-11-02 22:00:00'
            // No action_description (válido cuando action_required=false)
        ];

        $this->assertTrue($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba Adicional: NEWS con array target_audience vacío debería fallar
     */
    public function test_news_requires_at_least_one_target_audience(): void
    {
        $rule = new ValidAnnouncementMetadata('NEWS');

        $metadata = [
            'news_type' => 'general_update',
            'target_audience' => [], // Array vacío (inválido - debe tener al menos 1)
            'summary' => 'General update for the platform'
        ];

        $this->assertFalse($rule->passes('metadata', $metadata));
    }

    /**
     * Prueba Adicional: Validación de urgencia de MAINTENANCE
     */
    public function test_maintenance_validates_urgency_enum(): void
    {
        $rule = new ValidAnnouncementMetadata('MAINTENANCE');

        // Urgencia válida
        $validMetadata = [
            'urgency' => 'HIGH',
            'scheduled_start' => '2025-11-09 10:00:00',
            'scheduled_end' => '2025-11-09 14:00:00',
            'is_emergency' => false
        ];
        $this->assertTrue($rule->passes('metadata', $validMetadata));

        // Urgencia inválida (CRITICAL no permitido para MAINTENANCE)
        $invalidMetadata = [
            'urgency' => 'CRITICAL',
            'scheduled_start' => '2025-11-09 10:00:00',
            'scheduled_end' => '2025-11-09 14:00:00',
            'is_emergency' => false
        ];
        $this->assertFalse($rule->passes('metadata', $invalidMetadata));
    }

    /**
     * Prueba Adicional: La urgencia de ALERT debe ser solo HIGH o CRITICAL
     */
    public function test_alert_urgency_must_be_high_or_critical(): void
    {
        $rule = new ValidAnnouncementMetadata('ALERT');

        // Válido: Urgencia HIGH
        $validHighMetadata = [
            'urgency' => 'HIGH',
            'alert_type' => 'system',
            'message' => 'System alert with high urgency',
            'action_required' => false,
            'started_at' => '2025-11-02 22:00:00'
        ];
        $this->assertTrue($rule->passes('metadata', $validHighMetadata));

        // Válido: Urgencia CRITICAL
        $validCriticalMetadata = [
            'urgency' => 'CRITICAL',
            'alert_type' => 'security',
            'message' => 'Critical security alert',
            'action_required' => false,
            'started_at' => '2025-11-02 22:00:00'
        ];
        $this->assertTrue($rule->passes('metadata', $validCriticalMetadata));

        // Inválido: Urgencia MEDIUM (no permitido para ALERT)
        $invalidMetadata = [
            'urgency' => 'MEDIUM',
            'alert_type' => 'system',
            'message' => 'System alert with medium urgency',
            'action_required' => false,
            'started_at' => '2025-11-02 22:00:00'
        ];
        $this->assertFalse($rule->passes('metadata', $invalidMetadata));
    }
}
