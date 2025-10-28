<?php

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserProfile;
use App\Shared\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test suite completo para LoginMutation
 *
 * Verifica:
 * - Login exitoso con credenciales válidas
 * - Retorna misma estructura que RegisterMutation (AuthPayload)
 * - Genera access_token y refresh_token válidos
 * - Maneja errores con sistema global de errores
 * - Valida diferentes estados de usuario
 * - Previene login con credenciales inválidas
 */
class LoginMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private string $testPassword = 'SecurePass123!';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario de prueba con perfil y rol
        $this->testUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create([
                'email' => 'logintest@example.com',
                'password_hash' => Hash::make($this->testPassword),
                'email_verified' => true,
                'status' => UserStatus::ACTIVE,
            ]);
    }

    /**
     * @test
     * Test más importante: Login exitoso retorna AuthPayload completo
     */
    public function user_can_login_successfully(): void
    {
        // Arrange - REST API payload
        $payload = [
            'email' => 'logintest@example.com',
            'password' => $this->testPassword,
            'deviceName' => 'Test Device',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert - Status code
        $response->assertStatus(200);

        // Verificar estructura JSON
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

        $loginData = $response->json();

        // Verificar tokens
        $this->assertNotEmpty($loginData['accessToken']);
        // RefreshToken ahora devuelve mensaje informativo (no el token real en JSON)
        $this->assertEquals('Refresh token set in httpOnly cookie', $loginData['refreshToken']);
        $this->assertEquals('Bearer', $loginData['tokenType']);
        $this->assertIsInt($loginData['expiresIn']);

        // Verificar que el refresh token está en la cookie
        $this->assertNotEmpty($response->cookie('refresh_token'), 'Refresh token debe estar en cookie');

        // Verificar datos del usuario
        $this->assertEquals($this->testUser->id, $loginData['user']['id']);
        $this->assertEquals($this->testUser->email, $loginData['user']['email']);
        $this->assertTrue($loginData['user']['emailVerified']);
        $this->assertEquals('ACTIVE', $loginData['user']['status']);

        // Verificar roleContexts
        $this->assertCount(1, $loginData['user']['roleContexts']);
        $this->assertEquals('USER', $loginData['user']['roleContexts'][0]['roleCode']);
        $this->assertEquals('/tickets', $loginData['user']['roleContexts'][0]['dashboardPath']);

        // Verificar sessionId y timestamp
        $this->assertNotEmpty($loginData['sessionId']);
        $this->assertNotEmpty($loginData['loginTimestamp']);
    }

    /**
     * @test
     * Login falla con credenciales inválidas
     */
    public function cannot_login_with_invalid_credentials(): void
    {
        $payload = [
            'email' => 'logintest@example.com',
            'password' => 'WrongPassword123!',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert - Sistema global de errores (REST)
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Credenciales inválidas. Verifica tu email y contraseña.',
            'code' => 'INVALID_CREDENTIALS',
            'category' => 'authentication',
        ]);
    }

    /**
     * @test
     * Login falla con email que no existe
     */
    public function cannot_login_with_nonexistent_email(): void
    {
        $payload = [
            'email' => 'nonexistent@example.com',
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Credenciales inválidas. Verifica tu email y contraseña.',
            'code' => 'INVALID_CREDENTIALS',
        ]);
    }

    /**
     * @test
     * Email es case-insensitive (normalizado a lowercase)
     */
    public function email_login_is_case_insensitive(): void
    {
        $payload = [
            'email' => 'LOGINTEST@EXAMPLE.COM', // Uppercase
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert - Debe funcionar
        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'email' => 'logintest@example.com', // Normalizado
            ],
        ]);
    }

    /**
     * @test
     * No puede hacer login con cuenta suspendida
     */
    public function cannot_login_with_suspended_account(): void
    {
        // Arrange - Suspender cuenta
        $this->testUser->update(['status' => UserStatus::SUSPENDED]);

        $payload = [
            'email' => 'logintest@example.com',
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert - Sistema global de errores
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Tu cuenta está suspendida. Contacta al administrador.',
            'code' => 'ACCOUNT_SUSPENDED',
        ]);
    }

    /**
     * @test
     * Login funciona correctamente sin email verificado
     * (La verificación de email NO bloquea el login)
     */
    public function can_login_without_verified_email(): void
    {
        // Arrange - Email no verificado
        $this->testUser->update(['email_verified' => false]);

        $payload = [
            'email' => 'logintest@example.com',
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert - Login exitoso
        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'emailVerified' => false,
            ],
        ]);

        $this->assertNotEmpty($response->json('accessToken'));
    }

    /**
     * @test
     * Validación: email es requerido
     */
    public function email_is_required(): void
    {
        $payload = [
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert - Error de validación (422)
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'code',
            'errors' => ['email'],
        ]);
    }

    /**
     * @test
     * Validación: password es requerido
     */
    public function password_is_required(): void
    {
        $payload = [
            'email' => 'logintest@example.com',
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'code',
            'errors' => ['password'],
        ]);
    }

    /**
     * @test
     * Los tokens JWT son válidos y pueden ser decodificados
     */
    public function generated_jwt_tokens_are_valid(): void
    {
        $payload = [
            'email' => 'logintest@example.com',
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert
        $response->assertStatus(200);
        $accessToken = $response->json('accessToken');
        $refreshTokenMessage = $response->json('refreshToken');

        // Verificar formato JWT (3 partes separadas por puntos)
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+$/', $accessToken);

        // RefreshToken ahora es un mensaje informativo (el token real está en cookie)
        $this->assertEquals('Refresh token set in httpOnly cookie', $refreshTokenMessage);

        // Verificar que el refresh token está en la cookie
        $this->assertNotEmpty($response->cookie('refresh_token'));
    }

    /**
     * @test
     * Login con usuario con múltiples roles retorna todos los roleContexts
     */
    public function login_with_multiple_roles_returns_all_contexts(): void
    {
        // Arrange - Agregar rol PLATFORM_ADMIN al usuario (no requiere empresa)
        $this->testUser->assignRole('PLATFORM_ADMIN');

        $payload = [
            'email' => 'logintest@example.com',
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert
        $response->assertStatus(200);
        $roleContexts = $response->json('user.roleContexts');

        $this->assertCount(2, $roleContexts);

        $roleCodes = collect($roleContexts)->pluck('roleCode')->toArray();
        $this->assertContains('USER', $roleCodes);
        $this->assertContains('PLATFORM_ADMIN', $roleCodes);
    }

    /**
     * @test
     * Estructura de respuesta es idéntica a RegisterMutation
     */
    public function response_structure_matches_register_mutation(): void
    {
        // Esta es la garantía de que el cliente puede usar el mismo código
        // para manejar las respuestas de login y register

        $payload = [
            'email' => 'logintest@example.com',
            'password' => $this->testPassword,
        ];

        // Act
        $response = $this->postJson('/api/auth/login', $payload);

        // Assert - Misma estructura que RegisterMutation
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'accessToken',
            'refreshToken',
            'tokenType',
            'expiresIn',
            'user' => [
                'roleContexts',
            ],
            'sessionId',
            'loginTimestamp',
        ]);

        // Verificar tipos
        $loginData = $response->json();
        $this->assertIsString($loginData['accessToken']);
        $this->assertIsString($loginData['refreshToken']);
        $this->assertIsString($loginData['tokenType']);
        $this->assertIsInt($loginData['expiresIn']);
        $this->assertIsArray($loginData['user']);
        $this->assertIsArray($loginData['user']['roleContexts']);
        $this->assertIsString($loginData['sessionId']);
        $this->assertIsString($loginData['loginTimestamp']);
    }
}
