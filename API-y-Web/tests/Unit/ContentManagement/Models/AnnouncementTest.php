<?php

namespace Tests\Unit\ContentManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Enums\UrgencyLevel;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas unitarias para el modelo Announcement
 *
 * Prueba los casts del modelo, relaciones, scopes y métodos personalizados.
 * Total: 10 pruebas
 */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba 1: Verifica que el campo metadata se convierte a array
     */
    public function test_announcement_casts_metadata_to_array(): void
    {
        // Arrange & Act
        $announcement = Announcement::factory()->create([
            'type' => AnnouncementType::MAINTENANCE,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => '2025-11-09T10:00:00Z',
                'scheduled_end' => '2025-11-09T14:00:00Z',
                'is_emergency' => false,
            ],
        ]);

        // Assert
        $this->assertIsArray($announcement->metadata);
        $this->assertNotEmpty($announcement->metadata);
        $this->assertArrayHasKey('urgency', $announcement->metadata);
        $this->assertEquals('HIGH', $announcement->metadata['urgency']);
    }

    /**
     * Prueba 2: Verifica que el campo status se convierte al enum PublicationStatus
     */
    public function test_announcement_casts_status_to_enum(): void
    {
        // Arrange & Act
        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::DRAFT,
        ]);

        // Assert
        $this->assertInstanceOf(PublicationStatus::class, $announcement->status);
        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertEquals('DRAFT', $announcement->status->value);
    }

    /**
     * Prueba 3: Verifica que el campo type se convierte al enum AnnouncementType
     */
    public function test_announcement_casts_type_to_enum(): void
    {
        // Arrange & Act
        $announcement = Announcement::factory()->create([
            'type' => AnnouncementType::INCIDENT,
        ]);

        // Assert
        $this->assertInstanceOf(AnnouncementType::class, $announcement->type);
        $this->assertEquals(AnnouncementType::INCIDENT, $announcement->type);
        $this->assertEquals('INCIDENT', $announcement->type->value);
    }

    /**
     * Prueba 4: Prueba la relación de anuncio pertenece a empresa
     */
    public function test_belongs_to_company_relationship(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
        ]);

        // Assert
        $this->assertInstanceOf(Company::class, $announcement->company);
        $this->assertEquals($company->id, $announcement->company->id);
        $this->assertEquals($company->name, $announcement->company->name);
    }

    /**
     * Prueba 5: Prueba la relación de anuncio pertenece a autor (User)
     */
    public function test_belongs_to_author_relationship(): void
    {
        // Arrange
        $author = User::factory()->create();

        // Act
        $announcement = Announcement::factory()->create([
            'author_id' => $author->id,
        ]);

        // Assert
        $this->assertInstanceOf(User::class, $announcement->author);
        $this->assertEquals($author->id, $announcement->author->id);
        $this->assertEquals($author->email, $announcement->author->email);
    }

    /**
     * Prueba 6: isEditable() devuelve true para anuncios en estado DRAFT
     */
    public function test_is_editable_returns_true_for_draft(): void
    {
        // Arrange
        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::DRAFT,
        ]);

        // Act
        $result = $announcement->isEditable();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Prueba 7: isEditable() devuelve false para anuncios en estado PUBLISHED
     */
    public function test_is_editable_returns_false_for_published(): void
    {
        // Arrange
        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        // Act
        $result = $announcement->isEditable();

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Prueba 8: El scope published() filtra solo los anuncios PUBLISHED
     */
    public function test_is_published_scope_filters_correctly(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Create DRAFT announcements
        Announcement::factory()->count(2)->create([
            'company_id' => $company->id,
            'status' => PublicationStatus::DRAFT,
        ]);

        // Create PUBLISHED announcements
        Announcement::factory()->count(3)->create([
            'company_id' => $company->id,
            'status' => PublicationStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        // Act
        $publishedAnnouncements = Announcement::published()->get();

        // Assert
        $this->assertCount(3, $publishedAnnouncements);

        foreach ($publishedAnnouncements as $announcement) {
            $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
            $this->assertNotNull($announcement->published_at);
        }
    }

    /**
     * Prueba 9: El accesor scheduledFor analiza la fecha de los metadatos y devuelve una instancia de Carbon
     */
    public function test_scheduled_for_accessor_parses_from_metadata(): void
    {
        // Arrange
        $scheduledDate = '2025-11-08T08:00:00Z';

        $announcement = Announcement::factory()->create([
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'scheduled_for' => $scheduledDate,
                'urgency' => 'MEDIUM',
            ],
        ]);

        // Act
        $result = $announcement->scheduled_for;

        // Assert
        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2025-11-08 08:00:00', $result->format('Y-m-d H:i:s'));
    }

    /**
     * Prueba 10: formattedUrgency() devuelve una cadena de nivel de urgencia localizada
     */
    public function test_formatted_urgency_returns_localized_string(): void
    {
        // Arrange - Test HIGH urgency
        $announcement = Announcement::factory()->create([
            'type' => AnnouncementType::MAINTENANCE,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => '2025-11-09T10:00:00Z',
                'scheduled_end' => '2025-11-09T14:00:00Z',
            ],
        ]);

        // Act
        $result = $announcement->formattedUrgency();

        // Assert
        $this->assertIsString($result);
        $this->assertNotEmpty($result);

        // Debería devolver una cadena localizada (por ejemplo, "Alta", "High", dependiendo de la configuración regional)
        // Probamos que contiene los valores esperados para diferentes niveles de urgencia
        $this->assertContains(strtolower($result), ['alta', 'high', 'elevada']);

        // Arrange - Test MEDIUM urgency
        $announcement2 = Announcement::factory()->create([
            'metadata' => [
                'urgency' => 'MEDIUM',
            ],
        ]);

        // Act
        $result2 = $announcement2->formattedUrgency();

        // Assert
        $this->assertIsString($result2);
        $this->assertContains(strtolower($result2), ['media', 'medium', 'moderada']);

        // Arrange - Test CRITICAL urgency for incidents
        $announcement3 = Announcement::factory()->create([
            'type' => AnnouncementType::INCIDENT,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'is_resolved' => false,
                'started_at' => now()->toISOString(),
            ],
        ]);

        // Act
        $result3 = $announcement3->formattedUrgency();

        // Assert
        $this->assertIsString($result3);
        $this->assertContains(strtolower($result3), ['crítica', 'critical', 'critica']);
    }
}
