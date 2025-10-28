<?php

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
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
        // PASO 1: REGISTRO (genera token de verificación) - REST
        // =====================================================
        $registerResponse = $this->postJson('/api/auth/register', [
            'email' => 'flowtest@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'Flow',
            'lastName' => 'Test',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ]);

        $accessToken = $registerResponse->json('accessToken');
        $userId = $registerResponse->json('user.id');

        // Verificar que el usuario NO está verificado
        $this->assertFalse($registerResponse->json('user.emailVerified'));

        // =====================================================
        // PASO 2: VERIFICAR ESTADO (NO VERIFICADO) - REST
        // =====================================================
        $statusResponse = $this->withHeaders([
            'Authorization' => "Bearer {$accessToken}"
        ])->getJson('/api/auth/email/status');

        $statusResponse->assertJson([
            'isVerified' => false,
            'email' => 'flowtest@example.com',
            'canResend' => true,
            'attemptsRemaining' => 3,
        ]);

        // =====================================================
        // PASO 3: OBTENER TOKEN DE VERIFICACIÓN (simulado)
        // =====================================================
        // En producción, el token viene por email
        // En test, lo obtenemos del cache
        $verificationToken = Cache::get("email_verification:{$userId}");
        $this->assertNotEmpty($verificationToken, 'El token de verificación debe existir en cache');

        // =====================================================
        // PASO 4: VERIFICAR EMAIL CON TOKEN - REST
        // =====================================================
        $verifyResponse = $this->postJson('/api/auth/email/verify', [
            'token' => $verificationToken
        ]);

        $verifyResponse->assertJson([
            'success' => true,
            'message' => '¡Email verificado exitosamente! Ya puedes usar todas las funciones del sistema.',
            'canResend' => false,
        ]);

        // =====================================================
        // PASO 5: VERIFICAR ESTADO (VERIFICADO) - REST
        // =====================================================
        $statusAfterResponse = $this->withHeaders([
            'Authorization' => "Bearer {$accessToken}"
        ])->getJson('/api/auth/email/status');

        $statusAfterResponse->assertJson([
            'isVerified' => true,
            'email' => 'flowtest@example.com',
            'canResend' => false,
            'attemptsRemaining' => 0,
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
     * Puede reenviar email ANTES de verificar - REST
     */
    public function can_resend_verification_email_before_verifying(): void
    {
        // Arrange - Crear usuario sin verificar
        Queue::fake(); // No enviar emails reales

        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['email_verified' => false]);

        $accessToken = $this->generateAccessToken($user);

        // Act - Reenviar verificación
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$accessToken}"
        ])->postJson('/api/auth/email/verify/resend');

        // Assert
        $response->assertJson([
            'success' => true,
            'message' => 'Email de verificación enviado correctamente. Revisa tu bandeja de entrada.',
            'canResend' => false, // No puede reenviar inmediatamente (rate limit)
        ]);

        $this->assertNotNull($response->json('resendAvailableAt'));
    }

    /**
     * @test
     * NO puede reenviar email si ya está verificado - REST
     */
    public function cannot_resend_verification_if_already_verified(): void
    {
        // Arrange - Usuario verificado
        $user = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->verified()
            ->create();

        $accessToken = $this->generateAccessToken($user);

        // Act
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$accessToken}"
        ])->postJson('/api/auth/email/verify/resend');

        // Assert
        $response->assertJson([
            'success' => false,
            'message' => 'El email ya está verificado',
            'canResend' => false,
        ]);
    }

    /**
     * @test
     * NO puede verificar con token inválido - REST
     */
    public function cannot_verify_with_invalid_token(): void
    {
        // Act
        $response = $this->postJson('/api/auth/email/verify', [
            'token' => 'invalid-token-12345'
        ]);

        // Assert - Sistema global de errores
        $response->assertJson([
            'success' => false,
            'canResend' => true,
        ]);

        $message = $response->json('message');
        $this->assertStringContainsString('invalid', strtolower($message));
    }

    /**
     * @test
     * NO puede verificar con token expirado - REST
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

        // Act
        $response = $this->postJson('/api/auth/email/verify', [
            'token' => $expiredToken
        ]);

        // Assert
        $response->assertJson([
            'success' => false,
        ]);

        $message = $response->json('message');
        $this->assertStringContainsString('expired', strtolower($message));
    }

    /**
     * @test
     * emailVerificationStatus requiere autenticación JWT - REST
     */
    public function email_verification_status_requires_authentication(): void
    {
        // Act - Sin token JWT
        $response = $this->getJson('/api/auth/email/status');

        // Assert - Debe retornar 401 Unauthorized
        $response->assertStatus(401);
    }

    /**
     * @test
     * resendVerification requiere autenticación JWT - REST
     */
    public function resend_verification_requires_authentication(): void
    {
        // Act - Sin token JWT
        $response = $this->postJson('/api/auth/email/verify/resend');

        // Assert - Debe retornar 401 Unauthorized
        $response->assertStatus(401);
    }

    /**
     * @test
     * verifyEmail NO requiere autenticación (token identifica al usuario) - REST
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

        // Act - Sin JWT (el token de verificación es suficiente)
        $response = $this->postJson('/api/auth/email/verify', [
            'token' => $token
        ]);

        // Assert - Debe funcionar
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * @test
     * emailVerificationStatus retorna información correcta - REST
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

        $accessToken = $this->generateAccessToken($user);

        // Act
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$accessToken}"
        ])->getJson('/api/auth/email/status');

        // Assert
        $this->assertFalse($response->json('isVerified'));
        $this->assertEquals('statustest@example.com', $response->json('email'));
        $this->assertTrue($response->json('canResend'));
        $this->assertEquals(3, $response->json('attemptsRemaining'));
        $this->assertNotNull($response->json('verifiedAt'));
        $this->assertNotNull($response->json('resendAvailableAt'));
    }

    /**
     * @test
     * Verification email arrives to mailpit - REST
     */
    public function verification_email_arrives_to_mailpit(): void
    {
        // Skip if Mailpit not available
        if (!$this->isMailpitAvailable()) {
            $this->markTestSkipped('Mailpit is not available');
        }

        // Arrange - Clean mailpit and Redis
        $this->clearMailpit();
        \Illuminate\Support\Facades\Redis::connection('default')->flushdb();

        // Act - Register user (generates verification email)
        $registerResponse = $this->postJson('/api/auth/register', [
            'email' => 'verifytest@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'Verify',
            'lastName' => 'Test',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ]);

        $registerResponse->assertStatus(201);

        // Process queued jobs (Redis queue)
        $this->artisan('queue:work', ['--once' => true, '--queue' => 'emails']);
        sleep(1);

        // Assert - Email arrived to mailpit
        $messages = $this->getMailpitMessages();
        $verificationEmail = collect($messages)->first(function ($msg) {
            return str_contains($msg['To'][0]['Address'] ?? '', 'verifytest@example.com');
        });

        $this->assertNotNull($verificationEmail, 'Verification email should arrive to Mailpit');

        // Verify email contains verification token
        $emailBody = $this->getMailpitMessageBody($verificationEmail['ID']);
        $this->assertMatchesRegularExpression('/\?token=[a-zA-Z0-9]+/', $emailBody, 'Email should contain verification token');
    }

    protected function isMailpitAvailable(): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('http://mailpit:8025/api/v1/messages');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function clearMailpit(): void
    {
        try {
            \Illuminate\Support\Facades\Http::delete('http://mailpit:8025/api/v1/messages');
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    protected function getMailpitMessages(): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('http://mailpit:8025/api/v1/messages');
            return $response->json('messages') ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getMailpitMessageBody(string $messageId): string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get("http://mailpit:8025/api/v1/message/{$messageId}");
            return $response->json('HTML') ?? $response->json('Text') ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
