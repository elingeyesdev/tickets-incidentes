<?php

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RegisterMutation Feature Tests
 *
 * Tests para la mutation de registro de usuarios.
 * Cubre casos exitosos, validaciones, y rate limiting.
 */
class RegisterMutationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Usuario puede registrarse exitosamente
     */
    public function test_user_can_register_successfully(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                    refreshToken
                    tokenType
                    expiresIn
                    user {
                        id
                        userCode
                        email
                        emailVerified
                        status
                        displayName
                        avatarUrl
                        theme
                        language
                    }
                    roleContexts {
                        roleCode
                        roleName
                        dashboardPath
                        company {
                            id
                            name
                        }
                    }
                    sessionId
                    loginTimestamp
                }
            }
        ', [
            'input' => [
                'email' => 'newuser@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar respuesta
        $response->assertJson([
            'data' => [
                'register' => [
                    'tokenType' => 'Bearer',
                    'user' => [
                        'email' => 'newuser@example.com',
                        'emailVerified' => false,
                        'status' => 'ACTIVE',
                        'displayName' => 'John Doe',
                        'theme' => 'light',
                        'language' => 'es',
                    ],
                    'roleContexts' => [
                        [
                            'roleCode' => 'USER',
                            'roleName' => 'Cliente',
                            'dashboardPath' => '/tickets',
                            'company' => null,  // USER no tiene empresa
                        ]
                    ]
                ]
            ]
        ]);

        // Verificar que tiene tokens
        $data = $response->json('data.register');
        $this->assertNotEmpty($data['accessToken']);
        // RefreshToken ahora devuelve mensaje informativo (no el token real)
        $this->assertEquals('Token stored in secure HttpOnly cookie', $data['refreshToken']);
        $this->assertNotEmpty($data['sessionId']);
        $this->assertIsInt($data['expiresIn']);

        // ✅ NUEVO: Verificar que el refresh token fue almacenado para tests
        $refreshToken = \App\Features\Authentication\GraphQL\Mutations\RegisterMutation::getLastRefreshToken();
        $this->assertNotEmpty($refreshToken, 'Refresh token debe estar almacenado para tests');
        $this->assertGreaterThan(40, strlen($refreshToken), 'Refresh token debe tener longitud válida');

        // Verificar que se creó en la base de datos
        $this->assertDatabaseHas('auth.users', [
            'email' => 'newuser@example.com',
            'email_verified' => false,
            'status' => 'active',  // PostgreSQL ENUM usa lowercase
        ]);

        // Verificar que se creó el perfil
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user->profile);
        $this->assertEquals('John', $user->profile->first_name);
        $this->assertEquals('Doe', $user->profile->last_name);
        $this->assertEquals('light', $user->profile->theme);
        $this->assertEquals('es', $user->profile->language);

        // ✅ NUEVO: Verificar que terms_accepted_at NO es null
        $this->assertNotNull($user->terms_accepted_at, 'terms_accepted_at should not be null when user registers');
        $this->assertTrue($user->terms_accepted, 'terms_accepted should be true');
        $this->assertEquals('v2.1', $user->terms_version);

        // ✅ NUEVO: Verificar que se asignó rol USER automáticamente
        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $user->id,
            'role_code' => 'USER',  // UPPERCASE según RolesSeeder
            'company_id' => null,  // USER no requiere empresa según constraint de BD
            'is_active' => true,
        ]);

        // ✅ NUEVO: Verificar que el usuario tiene exactamente 1 rol activo
        $this->assertEquals(1, $user->activeRoles()->count());
    }

    /**
     * Test: No puede registrarse con email duplicado
     */
    public function test_cannot_register_with_duplicate_email(): void
    {
        // Crear usuario existente manualmente
        $user = User::create([
            'user_code' => 'USR-00001',
            'email' => 'existing@example.com',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'status' => UserStatus::ACTIVE,
            'terms_accepted' => true,
        ]);

        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                }
            }
        ', [
            'input' => [
                'email' => 'existing@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar que retorna error de validación
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test: No puede registrarse con contraseña débil
     */
    public function test_cannot_register_with_weak_password(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                }
            }
        ', [
            'input' => [
                'email' => 'test@example.com',
                'password' => '123', // Muy corta (mínimo 8)
                'passwordConfirmation' => '123',
                'firstName' => 'Test',
                'lastName' => 'User',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar error de validación en password
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test: Confirmación de contraseña debe coincidir
     */
    public function test_password_confirmation_must_match(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                }
            }
        ', [
            'input' => [
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'DifferentPass123!', // No coincide
                'firstName' => 'Test',
                'lastName' => 'User',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar error de validación
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test: Campos requeridos no pueden estar vacíos
     */
    public function test_required_fields_cannot_be_empty(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                }
            }
        ', [
            'input' => [
                'email' => '', // Vacío
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => '', // Vacío
                'lastName' => 'User',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar errores de validación en múltiples campos
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test: Términos y políticas deben ser aceptados
     */
    public function test_terms_and_privacy_must_be_accepted(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                }
            }
        ', [
            'input' => [
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Test',
                'lastName' => 'User',
                'acceptsTerms' => false, // No acepta términos
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar error de validación
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test: Email debe tener formato válido
     */
    public function test_email_must_have_valid_format(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                }
            }
        ', [
            'input' => [
                'email' => 'invalid-email-format', // Sin @
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Test',
                'lastName' => 'User',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar error de validación en email
        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test: Nombres tienen longitud mínima y máxima
     */
    public function test_names_have_length_constraints(): void
    {
        // Test nombre muy corto
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                }
            }
        ', [
            'input' => [
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'A', // Muy corto (mínimo 2)
                'lastName' => 'User',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        $response->assertJsonStructure(['errors']);
    }

    /**
     * Test: Email se normaliza a lowercase
     */
    public function test_email_is_normalized_to_lowercase(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    user {
                        email
                    }
                }
            }
        ', [
            'input' => [
                'email' => 'MixedCase@EXAMPLE.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Test',
                'lastName' => 'User',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar que email se guardó en lowercase
        $response->assertJson([
            'data' => [
                'register' => [
                    'user' => [
                        'email' => 'mixedcase@example.com'
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseHas('auth.users', [
            'email' => 'mixedcase@example.com'
        ]);
    }

    /**
     * Test: Nombres se capitalizan correctamente
     */
    public function test_names_are_capitalized_correctly(): void
    {
        $response = $this->graphQL('
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    user {
                        displayName
                    }
                }
            }
        ', [
            'input' => [
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'jOHN', // Mixto
                'lastName' => 'DOE', // Mayúsculas
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ]
        ]);

        // Verificar capitalización correcta
        $response->assertJson([
            'data' => [
                'register' => [
                    'user' => [
                        'displayName' => 'John Doe'
                    ]
                ]
            ]
        ]);

        // Verificar en BD
        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals('John', $user->profile->first_name);
        $this->assertEquals('Doe', $user->profile->last_name);
    }
}
