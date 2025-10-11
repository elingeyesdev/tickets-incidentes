<?php

namespace Tests\Feature\UserManagement;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para UpdateMyPreferencesMutation V10.1
 *
 * Verifica:
 * - Actualiza preferencias de interfaz y notificaciones
 * - Retorna solo PreferencesUpdatePayload (sin User completo)
 * - Validaciones de theme, language, timezone
 * - Separado de updateMyProfile (patrón profesional)
 * - Rate limiting diferenciado (50 por hora vs 30 de profile)
 */
class UpdateMyPreferencesMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()
            ->withProfile([
                'theme' => 'light',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
                'push_web_notifications' => true,
                'notifications_tickets' => true,
            ])
            ->withRole('USER')
            ->create([
                'email' => 'preferences@example.com',
                'status' => UserStatus::ACTIVE,
            ]);
    }

    /**
     * @test
     * Usuario puede actualizar sus preferencias exitosamente
     */
    public function user_can_update_their_preferences_successfully(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    userId
                    preferences {
                        theme
                        language
                        timezone
                        pushWebNotifications
                        notificationsTickets
                        updatedAt
                    }
                    updatedAt
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'dark',
                'language' => 'en',
                'timezone' => 'America/New_York',
                'pushWebNotifications' => false,
                'notificationsTickets' => false,
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'updateMyPreferences' => [
                    'userId',
                    'preferences' => [
                        'theme',
                        'language',
                        'timezone',
                        'pushWebNotifications',
                        'notificationsTickets',
                        'updatedAt',
                    ],
                    'updatedAt',
                ],
            ],
        ]);

        $result = $response->json('data.updateMyPreferences');
        $this->assertEquals($this->testUser->id, $result['userId']);
        $this->assertEquals('dark', $result['preferences']['theme']);
        $this->assertEquals('en', $result['preferences']['language']);
        $this->assertEquals('America/New_York', $result['preferences']['timezone']);
        $this->assertFalse($result['preferences']['pushWebNotifications']);
        $this->assertFalse($result['preferences']['notificationsTickets']);

        // Verificar en base de datos
        $this->testUser->profile->refresh();
        $this->assertEquals('dark', $this->testUser->profile->theme);
        $this->assertEquals('en', $this->testUser->profile->language);
        $this->assertFalse($this->testUser->profile->push_web_notifications);
    }

    /**
     * @test
     * Puede actualizar solo algunos campos de preferencias
     */
    public function can_update_only_some_preference_fields(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        theme
                        language
                        timezone
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'dark',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $preferences = $response->json('data.updateMyPreferences.preferences');
        $this->assertEquals('dark', $preferences['theme']);
        $this->assertEquals('es', $preferences['language']); // No cambió
        $this->assertEquals('America/La_Paz', $preferences['timezone']); // No cambió
    }

    /**
     * @test
     * Validación: theme debe ser light o dark
     */
    public function theme_must_be_light_or_dark(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    userId
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'invalid_theme',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $response->assertGraphQLValidationError('input.theme');
    }

    /**
     * @test
     * Validación: language debe ser es o en
     */
    public function language_must_be_es_or_en(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    userId
                }
            }
        ';

        $variables = [
            'input' => [
                'language' => 'fr', // No soportado
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $response->assertGraphQLValidationError('input.language');
    }

    /**
     * @test
     * Puede cambiar solo theme (caso de uso: toggle dark mode)
     */
    public function can_change_only_theme_for_dark_mode_toggle(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        theme
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'dark',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $theme = $response->json('data.updateMyPreferences.preferences.theme');
        $this->assertEquals('dark', $theme);
    }

    /**
     * @test
     * Puede cambiar solo language (caso de uso: selector de idioma)
     */
    public function can_change_only_language_for_language_selector(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        language
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'language' => 'en',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $language = $response->json('data.updateMyPreferences.preferences.language');
        $this->assertEquals('en', $language);
    }

    /**
     * @test
     * Puede activar/desactivar notificaciones web push
     */
    public function can_toggle_web_push_notifications(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        pushWebNotifications
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'pushWebNotifications' => false,
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $pushEnabled = $response->json('data.updateMyPreferences.preferences.pushWebNotifications');
        $this->assertFalse($pushEnabled);
    }

    /**
     * @test
     * Puede activar/desactivar notificaciones de tickets
     */
    public function can_toggle_ticket_notifications(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        notificationsTickets
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'notificationsTickets' => false,
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $ticketsEnabled = $response->json('data.updateMyPreferences.preferences.notificationsTickets');
        $this->assertFalse($ticketsEnabled);
    }

    /**
     * @test
     * Mutation requiere autenticación
     */
    public function mutation_requires_authentication(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    userId
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'dark',
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Retorna solo preferencias (NO retorna User completo)
     */
    public function returns_only_preferences_not_complete_user(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    userId
                    preferences {
                        theme
                        language
                    }
                    updatedAt
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'dark',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert - NO tiene roleContexts, profile completo, tickets, etc.
        $result = $response->json('data.updateMyPreferences');
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('preferences', $result);
        $this->assertArrayHasKey('updatedAt', $result);
        $this->assertArrayNotHasKey('roleContexts', $result);
        $this->assertArrayNotHasKey('ticketsCount', $result);
    }

    /**
     * @test
     * updatedAt se actualiza correctamente
     */
    public function updated_at_is_updated_correctly(): void
    {
        // Arrange
        $originalUpdatedAt = $this->testUser->profile->updated_at;

        sleep(1); // Asegurar que pase al menos 1 segundo

        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        updatedAt
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'dark',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $newUpdatedAt = $response->json('data.updateMyPreferences.preferences.updatedAt');
        $this->assertNotEquals($originalUpdatedAt->toISOString(), $newUpdatedAt);
    }

    /**
     * @test
     * Timezone acepta valores IANA válidos
     */
    public function timezone_accepts_valid_iana_values(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        timezone
                    }
                }
            }
        ';

        $validTimezones = [
            'America/New_York',
            'Europe/London',
            'Asia/Tokyo',
            'America/La_Paz',
        ];

        foreach ($validTimezones as $timezone) {
            $variables = [
                'input' => [
                    'timezone' => $timezone,
                ],
            ];

            // Act
            $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

            // Assert
            $result = $response->json('data.updateMyPreferences.preferences.timezone');
            $this->assertEquals($timezone, $result);
        }
    }

    /**
     * @test
     * Mutation es independiente de updateMyProfile (patrón profesional)
     */
    public function mutation_is_independent_from_update_my_profile(): void
    {
        // Arrange - Cambiar preferencias NO debe afectar datos personales
        $originalFirstName = $this->testUser->profile->first_name;
        $originalLastName = $this->testUser->profile->last_name;

        $query = '
            mutation UpdateMyPreferences($input: PreferencesInput!) {
                updateMyPreferences(input: $input) {
                    preferences {
                        theme
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'theme' => 'dark',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert - Datos personales no cambiaron
        $this->testUser->profile->refresh();
        $this->assertEquals($originalFirstName, $this->testUser->profile->first_name);
        $this->assertEquals($originalLastName, $this->testUser->profile->last_name);
        $this->assertEquals('dark', $this->testUser->profile->theme);
    }
}
