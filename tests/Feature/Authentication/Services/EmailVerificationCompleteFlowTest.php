<?php

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test Completo del Flujo de Verificación de Email
 *
 * Flujo profesional:
 * 1. Usuario se registra → recibe email con token
 * 2. Query emailVerificationStatus → estado "no verificado"
 * 3. Mutation verifyEmail → verifica con token
 * 4. Query emailVerificationStatus → estado "verificado"
 * 5. Mutation resendVerification → falla (ya verificado)
 *
 * También prueba:
 * - Resend antes de verificar
 * - Tokens inválidos/expirados
 * - Rate limiting
 */
class EmailVerificationCompleteFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * FLUJO COMPLETO: Registro → Estado → Verify → Estado verificado
     */
    public function complete_email_verification_flow_works_correctly(): void
    {
        // =====================================================
        // PASO 1: REGISTRO (genera token de verificación)
        // =====================================================
        $registerQuery = '
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                    refreshToken
                    user {
                        id
                        email
                        emailVerified
                        onboardingCompleted
                    }
                }
            }
        ';

        $registerVars = [
            'input' => [
                'email' => 'flowtest@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Flow',
                'lastName' => 'Test',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ],
        ];

        $registerResponse = $this->graphQL($registerQuery, $registerVars);

        $accessToken = $registerResponse->json('data.register.accessToken');
        $userId = $registerResponse->json('data.register.user.id');

        // Verificar que el usuario NO está verificado
        $this->assertFalse($registerResponse->json('data.register.user.emailVerified'));

        // =====================================================
        // PASO 2: VERIFICAR ESTADO (NO VERIFICADO)
        // =====================================================
        $statusQuery = '
            query {
                emailVerificationStatus {
                    isVerified
                    email
                    canResend
                    attemptsRemaining
                }
            }
        ';

        $statusResponse = $this->withJWT($accessToken)->graphQL($statusQuery);

        $statusResponse->assertJson([
            'data' => [
                'emailVerificationStatus' => [
                    'isVerified' => false,
                    'email' => 'flowtest@example.com',
                    'canResend' => true,
                    'attemptsRemaining' => 3,
                ],
            ],
        ]);

        // =====================================================
        // PASO 3: OBTENER TOKEN DE VERIFICACIÓN (simulado)
        // =====================================================
        // En producción, el token viene por email
        // En test, lo obtenemos del cache
        $verificationToken = Cache::get("email_verification:{$userId}");
        $this->assertNotEmpty($verificationToken, 'El token de verificación debe existir en cache');

        // =====================================================
        // PASO 4: VERIFICAR EMAIL CON TOKEN
        // =====================================================
        $verifyQuery = '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                    message
                    canResend
                }
            }
        ';

        $verifyVars = ['token' => $verificationToken];

        $verifyResponse = $this->graphQL($verifyQuery, $verifyVars);

        $verifyResponse->assertJson([
            'data' => [
                'verifyEmail' => [
                    'success' => true,
                    'message' => '¡Email verificado exitosamente! Ya puedes usar todas las funciones del sistema.',
                    'canResend' => false,
                ],
            ],
        ]);

        // =====================================================
        // PASO 5: VERIFICAR ESTADO (VERIFICADO)
        // =====================================================
        $statusAfterResponse = $this->withJWT($accessToken)->graphQL($statusQuery);

        $statusAfterResponse->assertJson([
            'data' => [
                'emailVerificationStatus' => [
                    'isVerified' => true,
                    'email' => 'flowtest@example.com',
                    'canResend' => false,
                    'attemptsRemaining' => 0,
                ],
            ],
        ]);

        // Verificar en la base de datos
        $user = User::find($userId);
        $this->assertTrue($user->email_verified);
        $this->assertNotNull($user->email_verified_at);

        // Verificar que el token fue eliminado del cache
        $this->assertNull(Cache::get("email_verification:{$userId}"));
    }

    /**
     * @test
     * Puede reenviar email ANTES de verificar
     */
    public function can_resend_verification_email_before_verifying(): void
    {
        // Arrange - Crear usuario sin verificar
        Queue::fake(); // No enviar emails reales

        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['email_verified' => false]);

        $accessToken = $this->loginAsUser($user);

        // Act - Reenviar verificación
        $resendQuery = '
            mutation {
                resendVerification {
                    success
                    message
                    canResend
                    resendAvailableAt
                }
            }
        ';

        $response = $this->withJWT($accessToken)->graphQL($resendQuery);

        // Assert
        $response->assertJson([
            'data' => [
                'resendVerification' => [
                    'success' => true,
                    'message' => 'Email de verificación enviado correctamente. Revisa tu bandeja de entrada.',
                    'canResend' => false, // No puede reenviar inmediatamente (rate limit)
                ],
            ],
        ]);

        $this->assertNotNull($response->json('data.resendVerification.resendAvailableAt'));
    }

    /**
     * @test
     * NO puede reenviar email si ya está verificado
     */
    public function cannot_resend_verification_if_already_verified(): void
    {
        // Arrange - Usuario verificado
        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->verified()
            ->create();

        $accessToken = $this->loginAsUser($user);

        // Act
        $resendQuery = '
            mutation {
                resendVerification {
                    success
                    message
                    canResend
                }
            }
        ';

        $response = $this->withJWT($accessToken)->graphQL($resendQuery);

        // Assert
        $response->assertJson([
            'data' => [
                'resendVerification' => [
                    'success' => false,
                    'message' => 'El email ya está verificado',
                    'canResend' => false,
                ],
            ],
        ]);
    }

    /**
     * @test
     * NO puede verificar con token inválido
     */
    public function cannot_verify_with_invalid_token(): void
    {
        $verifyQuery = '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                    message
                    canResend
                }
            }
        ';

        $verifyVars = ['token' => 'invalid-token-12345'];

        // Act
        $response = $this->graphQL($verifyQuery, $verifyVars);

        // Assert - Sistema global de errores
        $response->assertJson([
            'data' => [
                'verifyEmail' => [
                    'success' => false,
                    'canResend' => true,
                ],
            ],
        ]);

        $message = $response->json('data.verifyEmail.message');
        $this->assertStringContainsString('invalid', strtolower($message));
    }

    /**
     * @test
     * NO puede verificar con token expirado
     */
    public function cannot_verify_with_expired_token(): void
    {
        // Arrange - Usuario con token expirado
        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['email_verified' => false]);

        // Simular token expirado (generado hace 25 horas)
        $expiredToken = hash('sha256', $user->id . now()->subHours(25)->timestamp);

        $verifyQuery = '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                    message
                }
            }
        ';

        // Act
        $response = $this->graphQL($verifyQuery, ['token' => $expiredToken]);

        // Assert
        $response->assertJson([
            'data' => [
                'verifyEmail' => [
                    'success' => false,
                ],
            ],
        ]);

        $message = $response->json('data.verifyEmail.message');
        $this->assertStringContainsString('expired', strtolower($message));
    }

    /**
     * @test
     * emailVerificationStatus requiere autenticación JWT
     */
    public function email_verification_status_requires_authentication(): void
    {
        $query = '
            query {
                emailVerificationStatus {
                    isVerified
                }
            }
        ';

        // Act - Sin token JWT
        $response = $this->graphQL($query);

        // Assert - Sistema global de errores (AuthenticationException)
        $response->assertGraphQLErrorMessage('Unauthenticated');

        $errors = $response->json('errors');
        $this->assertEquals('UNAUTHENTICATED', $errors[0]['extensions']['code']);
    }

    /**
     * @test
     * resendVerification requiere autenticación JWT
     */
    public function resend_verification_requires_authentication(): void
    {
        $query = '
            mutation {
                resendVerification {
                    success
                }
            }
        ';

        // Act - Sin token JWT
        $response = $this->graphQL($query);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /**
     * @test
     * verifyEmail NO requiere autenticación (token identifica al usuario)
     */
    public function verify_email_does_not_require_authentication(): void
    {
        // Arrange
        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['email_verified' => false]);

        // Generar token válido
        $token = hash('sha256', $user->id . now()->timestamp);
        Cache::put("email_verification:{$user->id}", $token, now()->addHours(24));

        $verifyQuery = '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                }
            }
        ';

        // Act - Sin JWT (el token de verificación es suficiente)
        $response = $this->graphQL($verifyQuery, ['token' => $token]);

        // Assert - Debe funcionar
        $response->assertJson([
            'data' => [
                'verifyEmail' => [
                    'success' => true,
                ],
            ],
        ]);
    }

    /**
     * @test
     * emailVerificationStatus retorna información correcta
     */
    public function email_verification_status_returns_correct_information(): void
    {
        // Arrange - Usuario no verificado
        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create([
                'email' => 'statustest@example.com',
                'email_verified' => false,
            ]);

        $accessToken = $this->loginAsUser($user);

        // Act
        $query = '
            query {
                emailVerificationStatus {
                    isVerified
                    email
                    verificationSentAt
                    canResend
                    resendAvailableAt
                    attemptsRemaining
                }
            }
        ';

        $response = $this->withJWT($accessToken)->graphQL($query);

        // Assert
        $status = $response->json('data.emailVerificationStatus');

        $this->assertFalse($status['isVerified']);
        $this->assertEquals('statustest@example.com', $status['email']);
        $this->assertTrue($status['canResend']);
        $this->assertEquals(3, $status['attemptsRemaining']);
        $this->assertNotNull($status['verificationSentAt']);
        $this->assertNotNull($status['resendAvailableAt']);
    }

    /**
     * Helper: Login as user and get JWT token
     */
    private function loginAsUser(User $user): string
    {
        $loginQuery = '
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                }
            }
        ';

        $response = $this->graphQL($loginQuery, [
            'input' => [
                'email' => $user->email,
                'password' => 'password', // Default password from factory
                'rememberMe' => false,
            ],
        ]);

        return $response->json('data.login.accessToken');
    }

    /**
     * Helper: Add JWT authorization header
     */
    private function withJWT(string $token): self
    {
        return $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
    }
}
