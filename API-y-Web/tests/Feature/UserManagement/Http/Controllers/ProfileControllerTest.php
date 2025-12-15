<?php

namespace Tests\Feature\UserManagement\Http\Controllers;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para ProfileController REST API
 *
 * Migrado de tests GraphQL a REST:
 * - MyProfileQueryTest → show() tests
 * - UpdateMyProfileMutationTest → update() tests
 * - UpdateMyPreferencesMutationTest → updatePreferences() tests
 *
 * Endpoints:
 * - GET /api/users/me/profile
 * - PATCH /api/users/me/profile (rate limit: 30 per hour)
 * - PATCH /api/users/me/preferences (rate limit: 50 per hour)
 */
class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()
            ->withProfile([
                'first_name' => 'Ana',
                'last_name' => 'Martínez',
                'phone_number' => '+591 70123456',
                'avatar_url' => 'https://example.com/avatar.jpg',
                'theme' => 'light',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
                'push_web_notifications' => true,
                'notifications_tickets' => true,
            ])
            ->withRole('USER')
            ->create([
                'email' => 'ana@example.com',
                'status' => UserStatus::ACTIVE,
            ]);
    }

    // ========== GET /api/users/me/profile Tests ==========
    // Migrado de MyProfileQueryTest

    /**
     * @test
     * GET retorna perfil completo del usuario autenticado
     */
    public function show_returns_complete_profile_for_authenticated_user(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        // Act
        $response = $this->getJson('/api/users/me/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'firstName',
                'lastName',
                'displayName',
                'phoneNumber',
                'avatarUrl',
                'theme',
                'language',
                'timezone',
                'pushWebNotifications',
                'notificationsTickets',
                'createdAt',
                'updatedAt',
            ],
        ]);

        $profile = $response->json('data');
        $this->assertEquals('Ana', $profile['firstName']);
        $this->assertEquals('Martínez', $profile['lastName']);
        $this->assertEquals('Ana Martínez', $profile['displayName']);
        $this->assertEquals('+591 70123456', $profile['phoneNumber']);
        $this->assertEquals('https://example.com/avatar.jpg', $profile['avatarUrl']);
        $this->assertEquals('light', $profile['theme']);
        $this->assertEquals('es', $profile['language']);
        $this->assertEquals('America/La_Paz', $profile['timezone']);
        $this->assertTrue($profile['pushWebNotifications']);
        $this->assertTrue($profile['notificationsTickets']);
    }

    /**
     * @test
     * GET requiere autenticación
     */
    public function show_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/users/me/profile');

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * @test
     * GET retorna null para campos opcionales si no están configurados
     */
    public function show_returns_null_for_optional_fields_when_not_set(): void
    {
        // Arrange - Usuario sin teléfono ni avatar
        $user = User::factory()
            ->withProfile([
                'first_name' => 'Test',
                'last_name' => 'User',
                'phone_number' => null,
                'avatar_url' => null,
            ])
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($user);

        // Act
        $response = $this->getJson('/api/users/me/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $profile = $response->json('data');
        $this->assertEquals('Test', $profile['firstName']);
        $this->assertEquals('User', $profile['lastName']);
        $this->assertNull($profile['phoneNumber']);
        $this->assertNull($profile['avatarUrl']);
    }

    /**
     * @test
     * displayName se genera automáticamente de firstName y lastName
     */
    public function show_display_name_is_automatically_generated(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        // Act
        $response = $this->getJson('/api/users/me/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $profile = $response->json('data');
        $expectedDisplayName = $profile['firstName'] . ' ' . $profile['lastName'];
        $this->assertEquals($expectedDisplayName, $profile['displayName']);
    }

    /**
     * @test
     * GET retorna valores por defecto para preferencias
     */
    public function show_returns_default_values_for_preferences(): void
    {
        // Arrange - Usuario nuevo con preferencias por defecto
        $newUser = User::factory()
            ->withProfile() // Usa valores por defecto del factory
            ->withRole('USER')
            ->create();

        $token = $this->generateAccessToken($newUser);

        // Act
        $response = $this->getJson('/api/users/me/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $profile = $response->json('data');
        $this->assertContains($profile['theme'], ['light', 'dark']);
        $this->assertContains($profile['language'], ['es', 'en']);
        $this->assertNotEmpty($profile['timezone']);
        $this->assertIsBool($profile['pushWebNotifications']);
        $this->assertIsBool($profile['notificationsTickets']);
    }

    /**
     * @test
     * GET es útil para formularios de edición de perfil
     */
    public function show_is_useful_for_profile_edit_forms(): void
    {
        // Arrange - Simular caso de uso real: cargar datos para formulario
        $token = $this->generateAccessToken($this->testUser);

        // Act
        $response = $this->getJson('/api/users/me/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert - Todos los campos necesarios para formulario están presentes
        $profile = $response->json('data');
        $this->assertArrayHasKey('firstName', $profile);
        $this->assertArrayHasKey('lastName', $profile);
        $this->assertArrayHasKey('phoneNumber', $profile);
        $this->assertArrayHasKey('avatarUrl', $profile);
    }

    /**
     * @test
     * GET es útil para formularios de preferencias
     */
    public function show_is_useful_for_preferences_forms(): void
    {
        // Arrange - Simular caso de uso real: cargar preferencias
        $token = $this->generateAccessToken($this->testUser);

        // Act
        $response = $this->getJson('/api/users/me/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert - Todos los campos de preferencias están presentes
        $profile = $response->json('data');
        $this->assertArrayHasKey('theme', $profile);
        $this->assertArrayHasKey('language', $profile);
        $this->assertArrayHasKey('timezone', $profile);
        $this->assertArrayHasKey('pushWebNotifications', $profile);
        $this->assertArrayHasKey('notificationsTickets', $profile);
    }

    /**
     * @test
     * Timestamps son retornados en formato ISO 8601
     */
    public function show_timestamps_are_returned_in_iso_8601_format(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        // Act
        $response = $this->getJson('/api/users/me/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $profile = $response->json('data');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $profile['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $profile['updatedAt']);
    }

    // ========== PATCH /api/users/me/profile Tests ==========
    // Migrado de UpdateMyProfileMutationTest
    // Rate limit: 30 per hour

    /**
     * @test
     * PATCH puede actualizar perfil exitosamente
     */
    public function update_can_update_profile_successfully(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'firstName' => 'Juan Carlos',
            'lastName' => 'Pérez López',
            'phoneNumber' => '+591 75987654',
            'avatarUrl' => 'https://example.com/new-avatar.jpg',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/profile', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'userId',
                'profile' => [
                    'firstName',
                    'lastName',
                    'displayName',
                    'phoneNumber',
                    'avatarUrl',
                    'updatedAt',
                ],
                'updatedAt',
            ],
        ]);

        $result = $response->json('data');
        $this->assertEquals($this->testUser->id, $result['userId']);
        $this->assertEquals('Juan Carlos', $result['profile']['firstName']);
        $this->assertEquals('Pérez López', $result['profile']['lastName']);
        $this->assertEquals('Juan Carlos Pérez López', $result['profile']['displayName']);
        $this->assertEquals('+591 75987654', $result['profile']['phoneNumber']);
        $this->assertEquals('https://example.com/new-avatar.jpg', $result['profile']['avatarUrl']);

        // Verificar en base de datos
        $this->testUser->profile->refresh();
        $this->assertEquals('Juan Carlos', $this->testUser->profile->first_name);
        $this->assertEquals('Pérez López', $this->testUser->profile->last_name);
    }

    /**
     * @test
     * PATCH puede actualizar solo algunos campos
     */
    public function update_can_update_only_some_fields(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'firstName' => 'Juan Carlos',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/profile', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $profile = $response->json('data.profile');
        $this->assertEquals('Juan Carlos', $profile['firstName']);
        $this->assertEquals('Martínez', $profile['lastName']); // No cambió
        $this->assertEquals('+591 70123456', $profile['phoneNumber']); // No cambió
    }

    /**
     * @test
     * PATCH validación: firstName debe tener mínimo 2 caracteres
     */
    public function update_first_name_must_have_minimum_2_characters(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'firstName' => 'A',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/profile', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['firstName']);
    }

    /**
     * @test
     * PATCH validación: phoneNumber debe tener entre 10 y 20 caracteres
     */
    public function update_phone_number_must_be_valid_length(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'phoneNumber' => '123', // Muy corto
        ];

        // Act
        $response = $this->patchJson('/api/users/me/profile', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['phoneNumber']);
    }

    /**
     * @test
     * PATCH requiere autenticación
     */
    public function update_requires_authentication(): void
    {
        // Arrange
        $data = [
            'firstName' => 'Test',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/profile', $data);

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * @test
     * PATCH displayName se actualiza automáticamente
     */
    public function update_display_name_updates_automatically(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'firstName' => 'Carlos',
            'lastName' => 'Rodríguez',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/profile', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $profile = $response->json('data.profile');
        $this->assertEquals('Carlos Rodríguez', $profile['displayName']);
    }

    // ========== PATCH /api/users/me/preferences Tests ==========
    // Migrado de UpdateMyPreferencesMutationTest
    // Rate limit: 50 per hour

    /**
     * @test
     * PATCH puede actualizar preferencias exitosamente
     */
    public function update_preferences_can_update_successfully(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'America/New_York',
            'pushWebNotifications' => false,
            'notificationsTickets' => false,
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
        ]);

        $result = $response->json('data');
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
     * PATCH puede actualizar solo algunos campos de preferencias
     */
    public function update_preferences_can_update_only_some_fields(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'theme' => 'dark',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $preferences = $response->json('data.preferences');
        $this->assertEquals('dark', $preferences['theme']);
        $this->assertEquals('es', $preferences['language']); // No cambió
        $this->assertEquals('America/La_Paz', $preferences['timezone']); // No cambió
    }

    /**
     * @test
     * PATCH validación: theme debe ser light o dark
     */
    public function update_preferences_theme_must_be_light_or_dark(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'theme' => 'invalid_theme',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['theme']);
    }

    /**
     * @test
     * PATCH validación: language debe ser es o en
     */
    public function update_preferences_language_must_be_es_or_en(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'language' => 'fr', // No soportado
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['language']);
    }

    /**
     * @test
     * PATCH puede cambiar solo theme (caso de uso: toggle dark mode)
     */
    public function update_preferences_can_change_only_theme_for_dark_mode_toggle(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'theme' => 'dark',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $theme = $response->json('data.preferences.theme');
        $this->assertEquals('dark', $theme);
    }

    /**
     * @test
     * PATCH puede cambiar solo language (caso de uso: selector de idioma)
     */
    public function update_preferences_can_change_only_language_for_language_selector(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'language' => 'en',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $language = $response->json('data.preferences.language');
        $this->assertEquals('en', $language);
    }

    /**
     * @test
     * PATCH puede activar/desactivar notificaciones web push
     */
    public function update_preferences_can_toggle_web_push_notifications(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'pushWebNotifications' => false,
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $pushEnabled = $response->json('data.preferences.pushWebNotifications');
        $this->assertFalse($pushEnabled);
    }

    /**
     * @test
     * PATCH puede activar/desactivar notificaciones de tickets
     */
    public function update_preferences_can_toggle_ticket_notifications(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'notificationsTickets' => false,
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $ticketsEnabled = $response->json('data.preferences.notificationsTickets');
        $this->assertFalse($ticketsEnabled);
    }

    /**
     * @test
     * PATCH requiere autenticación
     */
    public function update_preferences_requires_authentication(): void
    {
        // Arrange
        $data = [
            'theme' => 'dark',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data);

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * @test
     * PATCH retorna solo preferencias (NO retorna User completo)
     */
    public function update_preferences_returns_only_preferences_not_complete_user(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'theme' => 'dark',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert - NO tiene roleContexts, profile completo, tickets, etc.
        $result = $response->json('data');
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('preferences', $result);
        $this->assertArrayHasKey('updatedAt', $result);
        $this->assertArrayNotHasKey('roleContexts', $result);
        $this->assertArrayNotHasKey('ticketsCount', $result);
    }

    /**
     * @test
     * PATCH updatedAt se actualiza correctamente
     */
    public function update_preferences_updated_at_is_updated_correctly(): void
    {
        // Arrange
        $originalUpdatedAt = $this->testUser->profile->updated_at;

        sleep(1); // Asegurar que pase al menos 1 segundo

        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'theme' => 'dark',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert
        $newUpdatedAt = $response->json('data.preferences.updatedAt');
        $this->assertNotEquals($originalUpdatedAt->toISOString(), $newUpdatedAt);
    }

    /**
     * @test
     * PATCH timezone acepta valores IANA válidos
     */
    public function update_preferences_timezone_accepts_valid_iana_values(): void
    {
        // Arrange
        $token = $this->generateAccessToken($this->testUser);

        $validTimezones = [
            'America/New_York',
            'Europe/London',
            'Asia/Tokyo',
            'America/La_Paz',
        ];

        foreach ($validTimezones as $timezone) {
            $data = [
                'timezone' => $timezone,
            ];

            // Act
            $response = $this->patchJson('/api/users/me/preferences', $data, [
                'Authorization' => "Bearer {$token}",
            ]);

            // Assert
            $result = $response->json('data.preferences.timezone');
            $this->assertEquals($timezone, $result);
        }
    }

    /**
     * @test
     * PATCH es independiente de update profile (patrón profesional)
     */
    public function update_preferences_is_independent_from_update_profile(): void
    {
        // Arrange - Cambiar preferencias NO debe afectar datos personales
        $originalFirstName = $this->testUser->profile->first_name;
        $originalLastName = $this->testUser->profile->last_name;

        $token = $this->generateAccessToken($this->testUser);

        $data = [
            'theme' => 'dark',
        ];

        // Act
        $response = $this->patchJson('/api/users/me/preferences', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert - Datos personales no cambiaron
        $this->testUser->profile->refresh();
        $this->assertEquals($originalFirstName, $this->testUser->profile->first_name);
        $this->assertEquals($originalLastName, $this->testUser->profile->last_name);
        $this->assertEquals('dark', $this->testUser->profile->theme);
    }
}
