<?php

namespace Tests\Feature\UserManagement;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para UpdateMyProfileMutation
 *
 * Verifica:
 * - Actualiza datos personales del perfil
 * - Retorna solo ProfileUpdatePayload (sin User completo)
 * - Validaciones de campos
 * - Requiere autenticación
 */
class UpdateMyProfileMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()
            ->withProfile([
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'phone_number' => '+591 70123456',
            ])
            ->withRole('USER')
            ->create([
                'email' => 'juan@example.com',
                'status' => UserStatus::ACTIVE,
            ]);
    }

    /**
     * @test
     * Usuario puede actualizar su perfil exitosamente
     */
    public function user_can_update_their_profile_successfully(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyProfile($input: UpdateProfileInput!) {
                updateMyProfile(input: $input) {
                    userId
                    profile {
                        firstName
                        lastName
                        displayName
                        phoneNumber
                        avatarUrl
                        updatedAt
                    }
                    updatedAt
                }
            }
        ';

        $variables = [
            'input' => [
                'firstName' => 'Juan Carlos',
                'lastName' => 'Pérez López',
                'phoneNumber' => '+591 75987654',
                'avatarUrl' => 'https://example.com/avatar.jpg',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'updateMyProfile' => [
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
            ],
        ]);

        $result = $response->json('data.updateMyProfile');
        $this->assertEquals($this->testUser->id, $result['userId']);
        $this->assertEquals('Juan Carlos', $result['profile']['firstName']);
        $this->assertEquals('Pérez López', $result['profile']['lastName']);
        $this->assertEquals('Juan Carlos Pérez López', $result['profile']['displayName']);
        $this->assertEquals('+591 75987654', $result['profile']['phoneNumber']);
        $this->assertEquals('https://example.com/avatar.jpg', $result['profile']['avatarUrl']);

        // Verificar en base de datos
        $this->testUser->profile->refresh();
        $this->assertEquals('Juan Carlos', $this->testUser->profile->first_name);
        $this->assertEquals('Pérez López', $this->testUser->profile->last_name);
    }

    /**
     * @test
     * Puede actualizar solo algunos campos
     */
    public function can_update_only_some_fields(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyProfile($input: UpdateProfileInput!) {
                updateMyProfile(input: $input) {
                    profile {
                        firstName
                        lastName
                        phoneNumber
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'firstName' => 'Juan Carlos',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $profile = $response->json('data.updateMyProfile.profile');
        $this->assertEquals('Juan Carlos', $profile['firstName']);
        $this->assertEquals('Pérez', $profile['lastName']); // No cambió
        $this->assertEquals('+591 70123456', $profile['phoneNumber']); // No cambió
    }

    /**
     * @test
     * Validación: firstName debe tener mínimo 2 caracteres
     */
    public function first_name_must_have_minimum_2_characters(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyProfile($input: UpdateProfileInput!) {
                updateMyProfile(input: $input) {
                    userId
                }
            }
        ';

        $variables = [
            'input' => [
                'firstName' => 'A',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $response->assertGraphQLValidationError('input.firstName');
    }

    /**
     * @test
     * Validación: phoneNumber debe tener entre 10 y 20 caracteres
     */
    public function phone_number_must_be_valid_length(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyProfile($input: UpdateProfileInput!) {
                updateMyProfile(input: $input) {
                    userId
                }
            }
        ';

        $variables = [
            'input' => [
                'phoneNumber' => '123', // Muy corto
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $response->assertGraphQLValidationError('input.phoneNumber');
    }

    /**
     * @test
     * Mutation requiere autenticación
     */
    public function mutation_requires_authentication(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyProfile($input: UpdateProfileInput!) {
                updateMyProfile(input: $input) {
                    userId
                }
            }
        ';

        $variables = [
            'input' => [
                'firstName' => 'Test',
            ],
        ];

        // Act
        $response = $this->graphQL($query, $variables);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /**
     * @test
     * displayName se actualiza automáticamente
     */
    public function display_name_updates_automatically(): void
    {
        // Arrange
        $query = '
            mutation UpdateMyProfile($input: UpdateProfileInput!) {
                updateMyProfile(input: $input) {
                    profile {
                        firstName
                        lastName
                        displayName
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'firstName' => 'Carlos',
                'lastName' => 'Rodríguez',
            ],
        ];

        // Act
        $response = $this->actingAsGraphQL($this->testUser)->graphQL($query, $variables);

        // Assert
        $profile = $response->json('data.updateMyProfile.profile');
        $this->assertEquals('Carlos Rodríguez', $profile['displayName']);
    }
}
