<?php

namespace Tests\Feature\UserManagement;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para MyProfileQuery
 *
 * Verifica:
 * - Retorna solo perfil del usuario autenticado
 * - No retorna User completo (sin roleContexts, tickets, etc.)
 * - Incluye preferencias de interfaz y notificaciones
 * - Requiere autenticación
 * - Estructura consistente para formularios de edición
 */
class MyProfileQueryTest extends TestCase
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
                'theme' => 'dark',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
                'push_web_notifications' => true,
                'notifications_tickets' => false,
            ])
            ->withRole('USER')
            ->create([
                'email' => 'ana@example.com',
                'status' => UserStatus::ACTIVE,
            ]);
    }

    /**
     * @test
     * Retorna perfil completo del usuario autenticado
     */
    public function returns_complete_profile_for_authenticated_user(): void
    {
        // Arrange
        $query = '
            query MyProfile {
                myProfile {
                    firstName
                    lastName
                    displayName
                    phoneNumber
                    avatarUrl
                    theme
                    language
                    timezone
                    pushWebNotifications
                    notificationsTickets
                    createdAt
                    updatedAt
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'myProfile' => [
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
            ],
        ]);

        $profile = $response->json('data.myProfile');
        $this->assertEquals('Ana', $profile['firstName']);
        $this->assertEquals('Martínez', $profile['lastName']);
        $this->assertEquals('Ana Martínez', $profile['displayName']);
        $this->assertEquals('+591 70123456', $profile['phoneNumber']);
        $this->assertEquals('https://example.com/avatar.jpg', $profile['avatarUrl']);
        $this->assertEquals('dark', $profile['theme']);
        $this->assertEquals('es', $profile['language']);
        $this->assertEquals('America/La_Paz', $profile['timezone']);
        $this->assertTrue($profile['pushWebNotifications']);
        $this->assertFalse($profile['notificationsTickets']);
    }

    /**
     * @test
     * Query requiere autenticación
     */
    public function query_requires_authentication(): void
    {
        // Arrange
        $query = '
            query MyProfile {
                myProfile {
                    firstName
                    lastName
                }
            }
        ';

        // Act
        $response = $this->graphQL($query);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * Retorna null para campos opcionales si no están configurados
     */
    public function returns_null_for_optional_fields_when_not_set(): void
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

        $query = '
            query MyProfile {
                myProfile {
                    firstName
                    lastName
                    phoneNumber
                    avatarUrl
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($user)->graphQL($query);

        // Assert
        $profile = $response->json('data.myProfile');
        $this->assertEquals('Test', $profile['firstName']);
        $this->assertEquals('User', $profile['lastName']);
        $this->assertNull($profile['phoneNumber']);
        $this->assertNull($profile['avatarUrl']);
    }

    /**
     * @test
     * displayName se genera automáticamente de firstName y lastName
     */
    public function display_name_is_automatically_generated(): void
    {
        // Arrange
        $query = '
            query MyProfile {
                myProfile {
                    firstName
                    lastName
                    displayName
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query);

        // Assert
        $profile = $response->json('data.myProfile');
        $expectedDisplayName = $profile['firstName'] . ' ' . $profile['lastName'];
        $this->assertEquals($expectedDisplayName, $profile['displayName']);
    }

    /**
     * @test
     * Retorna valores por defecto para preferencias
     */
    public function returns_default_values_for_preferences(): void
    {
        // Arrange - Usuario nuevo con preferencias por defecto
        $newUser = User::factory()
            ->withProfile() // Usa valores por defecto del factory
            ->withRole('USER')
            ->create();

        $query = '
            query MyProfile {
                myProfile {
                    theme
                    language
                    timezone
                    pushWebNotifications
                    notificationsTickets
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($newUser)->graphQL($query);

        // Assert
        $profile = $response->json('data.myProfile');
        $this->assertContains($profile['theme'], ['light', 'dark']);
        $this->assertContains($profile['language'], ['es', 'en']);
        $this->assertNotEmpty($profile['timezone']);
        $this->assertIsBool($profile['pushWebNotifications']);
        $this->assertIsBool($profile['notificationsTickets']);
    }

    /**
     * @test
     * Query es útil para formularios de edición de perfil
     */
    public function query_is_useful_for_profile_edit_forms(): void
    {
        // Arrange - Simular caso de uso real: cargar datos para formulario
        $query = '
            query MyProfile {
                myProfile {
                    firstName
                    lastName
                    phoneNumber
                    avatarUrl
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query);

        // Assert - Todos los campos necesarios para formulario están presentes
        $profile = $response->json('data.myProfile');
        $this->assertArrayHasKey('firstName', $profile);
        $this->assertArrayHasKey('lastName', $profile);
        $this->assertArrayHasKey('phoneNumber', $profile);
        $this->assertArrayHasKey('avatarUrl', $profile);
    }

    /**
     * @test
     * Query es útil para formularios de preferencias
     */
    public function query_is_useful_for_preferences_forms(): void
    {
        // Arrange - Simular caso de uso real: cargar preferencias
        $query = '
            query MyProfile {
                myProfile {
                    theme
                    language
                    timezone
                    pushWebNotifications
                    notificationsTickets
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query);

        // Assert - Todos los campos de preferencias están presentes
        $profile = $response->json('data.myProfile');
        $this->assertArrayHasKey('theme', $profile);
        $this->assertArrayHasKey('language', $profile);
        $this->assertArrayHasKey('timezone', $profile);
        $this->assertArrayHasKey('pushWebNotifications', $profile);
        $this->assertArrayHasKey('notificationsTickets', $profile);
    }

    /**
     * @test
     * phoneNumber y avatarUrl pueden ser null
     */
    public function optional_fields_can_be_null(): void
    {
        // Arrange - Usuario sin teléfono ni avatar
        $newUser = User::factory()
            ->withProfile([
                'phone_number' => null,
                'avatar_url' => null,
            ])
            ->withRole('USER')
            ->create();

        $query = '
            query MyProfile {
                myProfile {
                    phoneNumber
                    avatarUrl
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($newUser)->graphQL($query);

        // Assert
        $profile = $response->json('data.myProfile');
        $this->assertNull($profile['phoneNumber']);
        $this->assertNull($profile['avatarUrl']);
    }

    /**
     * @test
     * Timestamps son retornados en formato ISO 8601
     */
    public function timestamps_are_returned_in_iso_8601_format(): void
    {
        // Arrange
        $query = '
            query MyProfile {
                myProfile {
                    createdAt
                    updatedAt
                }
            }
        ';

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query);

        // Assert
        $profile = $response->json('data.myProfile');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $profile['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $profile['updatedAt']);
    }
}
