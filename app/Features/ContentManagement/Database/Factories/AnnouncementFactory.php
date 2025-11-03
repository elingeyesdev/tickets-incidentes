<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Factories;

use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Features\ContentManagement\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            AnnouncementType::MAINTENANCE,
            AnnouncementType::INCIDENT,
            AnnouncementType::NEWS,
            AnnouncementType::ALERT,
        ]);

        return [
            'company_id' => Company::factory(),
            'author_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'type' => $type,
            'status' => PublicationStatus::DRAFT,
            'metadata' => $this->generateMetadataForType($type),
            'published_at' => null,
        ];
    }

    /**
     * Generate metadata based on announcement type
     */
    private function generateMetadataForType(AnnouncementType $type): array
    {
        return match($type) {
            AnnouncementType::MAINTENANCE => [
                'urgency' => $this->faker->randomElement(['LOW', 'MEDIUM', 'HIGH']),
                'scheduled_start' => now()->addDays(2)->toIso8601String(),
                'scheduled_end' => now()->addDays(2)->addHours(4)->toIso8601String(),
                'is_emergency' => false,
                'affected_services' => ['api', 'reports'],
            ],
            AnnouncementType::INCIDENT => [
                'urgency' => $this->faker->randomElement(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
                'is_resolved' => false,
                'started_at' => now()->subHours(2)->toIso8601String(),
                'affected_services' => ['login', 'api'],
            ],
            AnnouncementType::NEWS => [
                'news_type' => $this->faker->randomElement(['feature_release', 'policy_update', 'general_update']),
                'target_audience' => ['users', 'agents'],
                'summary' => $this->faker->sentence(),
            ],
            AnnouncementType::ALERT => [
                'urgency' => $this->faker->randomElement(['HIGH', 'CRITICAL']),
                'alert_type' => $this->faker->randomElement(['security', 'system', 'service', 'compliance']),
                'message' => $this->faker->sentence(),
                'action_required' => false,
                'started_at' => now()->toIso8601String(),
            ],
        };
    }

    /**
     * Indicate that the announcement is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PublicationStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the announcement is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            $metadata = $attributes['metadata'];
            $metadata['scheduled_for'] = now()->addDays(1)->toIso8601String();

            return [
                'status' => PublicationStatus::SCHEDULED,
                'metadata' => $metadata,
            ];
        });
    }

    /**
     * Indicate that the announcement is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PublicationStatus::ARCHIVED,
            'published_at' => now()->subDays(30),
        ]);
    }

    /**
     * Create a maintenance type announcement.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AnnouncementType::MAINTENANCE,
            'metadata' => $this->generateMetadataForType(AnnouncementType::MAINTENANCE),
        ]);
    }

    /**
     * Create an incident type announcement.
     */
    public function incident(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AnnouncementType::INCIDENT,
            'metadata' => $this->generateMetadataForType(AnnouncementType::INCIDENT),
        ]);
    }

    /**
     * Create a news type announcement.
     */
    public function news(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AnnouncementType::NEWS,
            'metadata' => $this->generateMetadataForType(AnnouncementType::NEWS),
        ]);
    }

    /**
     * Create an alert type announcement.
     */
    public function alert(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AnnouncementType::ALERT,
            'metadata' => $this->generateMetadataForType(AnnouncementType::ALERT),
        ]);
    }
}
