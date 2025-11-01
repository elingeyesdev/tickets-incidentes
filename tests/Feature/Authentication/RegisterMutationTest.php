<?php

namespace Authentication;

use App\Features\UserManagement\Models\User;
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
        $payload = [
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        // Verificar que la respuesta es 201 Created
        $response->assertStatus(201);

        // Verificar la estructura de la respuesta
        $response->assertJsonStructure([
            'accessToken',
            'refreshToken',
            'tokenType',
            'expiresIn',
            'user' => [
                'id',
                'userCode',
                'email',
                'emailVerified',
                'onboardingCompleted',
                'status',
                'displayName',
                'avatarUrl',
                'theme',
                'language',
                'roleContexts' => [
                    '*' => [
                        'roleCode',
                        'roleName',
                        'dashboardPath',
                    ],
                ],
            ],
            'sessionId',
            'loginTimestamp',
        ]);

        // Verificar valores específicos
        $response->assertJsonPath('tokenType', 'Bearer');
        $response->assertJsonPath('user.email', 'newuser@example.com');
        $response->assertJsonPath('user.emailVerified', false);
        $response->assertJsonPath('user.onboardingCompleted', false);
        $response->assertJsonPath('user.status', 'ACTIVE');
        $response->assertJsonPath('user.displayName', 'John Doe');
        $response->assertJsonPath('user.roleContexts.0.roleCode', 'USER');

        // Verificar tokens
        $data = $response->json();
        $this->assertNotEmpty($data['accessToken']);
        $this->assertEquals('Refresh token set in httpOnly cookie', $data['refreshToken']);
        $this->assertNotEmpty($data['sessionId']);
        $this->assertIsInt($data['expiresIn']);

        // Verificar que la cookie del refresh token está presente
        $response->assertCookie('refresh_token');

        // Verificar que se creó en la base de datos
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'email_verified' => false,
            'status' => 'active',  // PostgreSQL ENUM usa lowercase
        ]);

        // Obtener usuario para verificaciones adicionales
        $user = User::where('email', 'newuser@example.com')->first();

        // Verificar onboarding_completed_at es null (usuario recién registrado no ha completado onboarding)
        $this->assertNull($user->onboarding_completed_at);

        // Verificar que se creó el perfil
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
        $this->assertDatabaseHas('user_roles', [
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
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $payload = [
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        // Verificar que retorna error de validación 422
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'code',
            'errors' => ['email'],
        ]);
        $response->assertJsonPath('errors.email.0', 'Este email ya está registrado.');
    }

    /**
     * Test: No puede registrarse con contraseña débil
     */
    public function test_cannot_register_with_weak_password(): void
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => '123', // Muy corta (mínimo 8)
            'passwordConfirmation' => '123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    /**
     * Test: Confirmación de contraseña debe coincidir
     */
    public function test_password_confirmation_must_match(): void
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'DifferentPass123!', // No coincide
            'firstName' => 'Test',
            'lastName' => 'User',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('passwordConfirmation');
    }

    /**
     * Test: Campos requeridos no pueden estar vacíos
     */
    public function test_required_fields_cannot_be_empty(): void
    {
        $payload = [
            'email' => '', // Vacío
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => '', // Vacío
            'lastName' => 'User',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'firstName']);
    }

    /**
     * Test: Términos y políticas deben ser aceptados
     */
    public function test_terms_and_privacy_must_be_accepted(): void
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'Test',
            'lastName' => 'User',
            'acceptsTerms' => false, // No acepta términos
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('acceptsTerms');
    }

    /**
     * Test: Email debe tener formato válido
     */
    public function test_email_must_have_valid_format(): void
    {
        $payload = [
            'email' => 'invalid-email-format', // Sin @
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'Test',
            'lastName' => 'User',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /**
     * Test: Nombres tienen longitud mínima y máxima
     */
    public function test_names_have_length_constraints(): void
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'A', // Muy corto (mínimo 2)
            'lastName' => 'User',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('firstName');
    }

    /**
     * Test: Email se normaliza a lowercase
     */
    public function test_email_is_normalized_to_lowercase(): void
    {
        $payload = [
            'email' => 'MixedCase@EXAMPLE.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'Test',
            'lastName' => 'User',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('user.email', 'mixedcase@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'mixedcase@example.com'
        ]);
    }

    /**
     * Test: Nombres se capitalizan correctamente
     */
    public function test_names_are_capitalized_correctly(): void
    {
        $payload = [
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'jOHN', // Mixto
            'lastName' => 'DOE', // Mayúsculas
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('user.displayName', 'John Doe');

        // Verificar en BD
        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals('John', $user->profile->first_name);
        $this->assertEquals('Doe', $user->profile->last_name);
    }
}
