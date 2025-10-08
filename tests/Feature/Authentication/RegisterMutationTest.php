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
                    ]
                ]
            ]
        ]);

        // Verificar que tiene tokens
        $data = $response->json('data.register');
        $this->assertNotEmpty($data['accessToken']);
        $this->assertNotEmpty($data['refreshToken']);
        $this->assertNotEmpty($data['sessionId']);
        $this->assertIsInt($data['expiresIn']);

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
        $response->assertGraphQLErrorCategory('validation');
        $response->assertGraphQLValidationKeys(['input.password']);
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
        $response->assertGraphQLErrorCategory('validation');
        $response->assertGraphQLValidationKeys(['input.password']);
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
        $response->assertGraphQLErrorCategory('validation');
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
        $response->assertGraphQLErrorCategory('validation');
        $response->assertGraphQLValidationKeys(['input.acceptsTerms']);
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
        $response->assertGraphQLErrorCategory('validation');
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

        $response->assertGraphQLErrorCategory('validation');
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
