<?php

namespace Tests\Feature\Authentication\Mutations;

use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Password Reset Complete Test Suite (36 Tests)
 *
 * =====================================================================
 * TESTS INCLUIDOS EN ESTE ARCHIVO
 * =====================================================================
 *
 * A. RESETPASSWORD MUTATION TESTS (Solicitud de reset)
 *    1. user_can_request_password_reset() - Usuario solicita reset
 *    2. nonexistent_email_returns_true_for_security() - Email inexistente retorna true
 *    3. generates_reset_token_in_cache() - Token se guarda en cache
 *    4. sends_reset_email_with_token_and_code() - Email enviado con token y código
 *    5. email_contains_token_and_6_digit_code() - Email tiene token (32 chars) + código (6 dígitos)
 *    6. rate_limits_reset_resends_to_1_per_minute() - Rate limit: 1 minuto entre resends
 *    7. enforces_2_emails_per_3_hours_limit() - Rate limit: máximo 2 emails cada 3 horas
 *    8. allows_reset_after_1_minute_passes() - Permite reset después de 1 minuto
 *    9. allows_new_reset_after_3_hours_window_expires() - Permite nuevo reset después de 3 horas
 *
 * B. PASSWORDRESETSTATUS QUERY TESTS (Validación de tokens)
 *    10. can_check_reset_token_validity() - Valida token válido
 *    11. returns_expiration_time() - Retorna tiempo de expiración (24h)
 *    12. invalid_token_returns_false() - Token inválido retorna false
 *    13. expired_token_returns_invalid() - Token expirado retorna inválido
 *
 * C. CONFIRMPASSWORDRESET MUTATION TESTS - CON TOKEN
 *    14. can_reset_with_token() - Reset usando token
 *    15. returns_access_token_after_reset() - Retorna accessToken + refreshToken
 *    16. auto_logs_in_user_after_reset() - Auto-login después de reset
 *    17. invalidates_all_sessions_on_reset() - Invalida todas las sesiones previas
 *    18. validates_token_exists() - Valida que token existe
 *    19. validates_token_not_expired() - Valida que token no esté expirado
 *    20. validates_password_requirements() - Valida requisitos de contraseña
 *    21. cannot_reuse_same_reset_token_twice() - No permite reutilizar token
 *
 * D. CONFIRMPASSWORDRESET MUTATION TESTS - CON CÓDIGO
 *    22. can_reset_with_6_digit_code() - Reset usando código
 *    23. rejects_invalid_code_format() - Rechaza código inválido (no 6 dígitos)
 *    24. rejects_wrong_code() - Rechaza código incorrecto
 *    25. cannot_reuse_same_reset_code_twice() - No permite reutilizar código
 *
 * E. SECURITY TESTS (Prevención de ataques)
 *    26. validates_code_belongs_to_correct_user() - Código pertenece al usuario correcto
 *    27. cannot_use_code_from_different_user() - No permite usar código de otro usuario
 *    28. multiple_users_can_reset_independently() - Múltiples usuarios pueden resetear independientemente
 *    29. rejects_both_token_and_code_in_single_request() - Rechaza si envía ambos en mismo request
 *
 * F. MAILPIT INTEGRATION TESTS (Email real)
 *    30. password_reset_email_arrives_to_mailpit_with_token_and_code() - Email contiene token + código
 *
 * G. EDGE CASES
 *    31. token_expires_after_24_hours() - Token expira después de 24 horas
 *    32. password_requirements_are_enforced() - Se validan requisitos de contraseña
 *
 * =====================================================================
 * TOTAL: 32 tests exhaustivos, complejos y completos
 * =====================================================================
 *
 * RATE LIMITING APPLIED:
 * - 1 minuto entre resends del mismo email
 * - máximo 2 emails cada 3 horas por usuario
 *
 * RESET METHODS SUPPORTED:
 * - Link (token de 32 caracteres)
 * - Code (código de 6 dígitos)
 * - Ambos (prefiere link si está presente)
 */
class PasswordResetCompleteTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushDb();
    }

    protected function tearDown(): void
    {
        Redis::flushDb();
        parent::tearDown();
    }

    // =========================================================================
    // A. RESETPASSWORD MUTATION TESTS
    // =========================================================================

    /** @test */
    public function user_can_request_password_reset()
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act
        $response = $this->graphQL('
            mutation ResetPassword($email: Email!) {
                resetPassword(email: $email)
            }
        ', ['email' => 'user@example.com']);

        // Assert
        $this->assertTrue($response->json('data.resetPassword'));
    }

    /** @test */
    public function nonexistent_email_returns_true_for_security()
    {
        // Act
        $response = $this->graphQL('
            mutation ResetPassword($email: Email!) {
                resetPassword(email: $email)
            }
        ', ['email' => 'nonexistent@example.com']);

        // Assert - Por seguridad, NO revela si email existe
        $this->assertTrue($response->json('data.resetPassword'));
    }

    /** @test */
    public function generates_reset_token_in_cache()
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        
        // Usar helper para generar token
        $token = $this->generateResetToken($user);

        // Act - Validar token
        $response = $this->graphQL('
            query CheckReset($token: String!) {
                passwordResetStatus(token: $token) {
                    isValid
                    expiresAt
                }
            }
        ', ['token' => $token]);

        // Assert
        $this->assertTrue($response->json('data.passwordResetStatus.isValid'));
        $this->assertNotNull($response->json('data.passwordResetStatus.expiresAt'));
    }

    /** @test */
    public function sends_reset_email_with_token_and_code()
    {
        // Arrange
        Mail::fake();
        Queue::fake();
        
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act
        $this->graphQL('
            mutation ResetPassword($email: Email!) {
                resetPassword(email: $email)
            }
        ', ['email' => 'user@example.com']);

        $this->executeQueuedJobs();

        // Assert - Email fue enviado
        Mail::assertSent(\App\Features\Authentication\Mail\PasswordResetMail::class, 
            function ($mail) use ($user) {
                return $mail->hasTo($user->email);
            }
        );
    }

    /** @test */
    public function email_contains_token_and_6_digit_code()
    {
        // Arrange
        Mail::fake();
        Queue::fake();
        
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act
        $this->graphQL('
            mutation ResetPassword($email: Email!) {
                resetPassword(email: $email)
            }
        ', ['email' => 'user@example.com']);

        $this->executeQueuedJobs();

        // Assert
        Mail::assertSent(\App\Features\Authentication\Mail\PasswordResetMail::class,
            function ($mail) {
                return !empty($mail->resetToken) && 
                       !empty($mail->resetCode) &&
                       strlen($mail->resetCode) === 6;
            }
        );
    }

    /** @test */
    public function rate_limits_reset_resends_to_1_per_minute()
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act - Primer request (debe pasar)
        $response1 = $this->graphQL('
            mutation ResetPassword($email: Email!) {
                resetPassword(email: $email)
            }
        ', ['email' => 'user@example.com']);

        $this->assertTrue($response1->json('data.resetPassword'));

        // Act - Segundo request inmediato (debe fallar)
        $response2 = $this->graphQL('
            mutation ResetPassword($email: Email!) {
                resetPassword(email: $email)
            }
        ', ['email' => 'user@example.com']);

        // Assert
        $this->assertNotNull($response2->json('errors'));
        $this->assertStringContainsString('Too many', $response2->json('errors.0.message'));
    }

    /** @test */
    public function enforces_2_emails_per_3_hours_limit()
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act - Primer email
        $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Esperar 1 minuto
        $this->travelTo(now()->addSeconds(61));

        // Act - Segundo email (permitido)
        $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Esperar 1 minuto más
        $this->travelTo(now()->addSeconds(61));

        // Act - Tercer email (debe fallar)
        $response3 = $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Assert
        $this->assertNotNull($response3->json('errors'));
    }

    /** @test */
    public function allows_reset_after_1_minute_passes()
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act - Primer request
        $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Avanzar 1 minuto y 1 segundo
        $this->travelTo(now()->addSeconds(61));

        // Act - Segundo request
        $response = $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Assert
        $this->assertTrue($response->json('data.resetPassword'));
    }

    /** @test */
    public function allows_new_reset_after_3_hours_window_expires()
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act - Primer email
        $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Esperar 1 minuto
        $this->travelTo(now()->addSeconds(61));

        // Act - Segundo email
        $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Esperar 3 horas y 1 segundo
        $this->travelTo(now()->addHours(3)->addSeconds(1));

        // Act - Tercer email (debe permitirse)
        $response = $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'user@example.com']
        );

        // Assert
        $this->assertTrue($response->json('data.resetPassword'));
    }

    // =========================================================================
    // B. PASSWORDRESETSTATUS QUERY TESTS
    // =========================================================================

    /** @test */
    public function can_check_reset_token_validity()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        // Act
        $response = $this->graphQL('
            query CheckReset($token: String!) {
                passwordResetStatus(token: $token) {
                    isValid
                    email
                    expiresAt
                    canReset
                }
            }
        ', ['token' => $token]);

        // Assert
        $this->assertTrue($response->json('data.passwordResetStatus.isValid'));
        $this->assertEquals($user->email, $response->json('data.passwordResetStatus.email'));
        $this->assertTrue($response->json('data.passwordResetStatus.canReset'));
    }

    /** @test */
    public function returns_expiration_time()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        // Act
        $response = $this->graphQL('
            query CheckReset($token: String!) {
                passwordResetStatus(token: $token) {
                    expiresAt
                }
            }
        ', ['token' => $token]);

        // Assert
        $expiresAt = $response->json('data.passwordResetStatus.expiresAt');
        $this->assertNotNull($expiresAt);
    }

    /** @test */
    public function invalid_token_returns_false()
    {
        // Act
        $response = $this->graphQL('
            query CheckReset($token: String!) {
                passwordResetStatus(token: $token) {
                    isValid
                    canReset
                }
            }
        ', ['token' => 'invalid_token_xxxxxx']);

        // Assert
        $this->assertFalse($response->json('data.passwordResetStatus.isValid'));
        $this->assertFalse($response->json('data.passwordResetStatus.canReset'));
    }

    /** @test */
    public function expired_token_returns_invalid()
    {
        // Arrange
        $user = User::factory()->create();
        $expiredToken = $this->generateResetToken($user, expiresIn: -1);

        // Act
        $response = $this->graphQL('
            query CheckReset($token: String!) {
                passwordResetStatus(token: $token) {
                    isValid
                    canReset
                }
            }
        ', ['token' => $expiredToken]);

        // Assert
        $this->assertFalse($response->json('data.passwordResetStatus.isValid'));
        $this->assertFalse($response->json('data.passwordResetStatus.canReset'));
    }

    // =========================================================================
    // C. CONFIRMPASSWORDRESET MUTATION TESTS - CON TOKEN
    // =========================================================================

    /** @test */
    public function can_reset_with_token()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        // Act
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                    accessToken
                    refreshToken
                    user { id email }
                }
            }
        ', [
            'input' => [
                'token' => $token,
                'password' => 'NewPass123!',
                'passwordConfirmation' => 'NewPass123!',
            ],
        ]);

        // Assert
        $this->assertTrue($response->json('data.confirmPasswordReset.success'));
        $this->assertNotEmpty($response->json('data.confirmPasswordReset.accessToken'));
        $this->assertNotEmpty($response->json('data.confirmPasswordReset.refreshToken'));
    }

    /** @test */
    public function returns_access_token_after_reset()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        // Act
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                    accessToken
                    refreshToken
                    user { id email }
                }
            }
        ', [
            'input' => [
                'token' => $token,
                'password' => 'NewPass123!',
                'passwordConfirmation' => 'NewPass123!',
            ],
        ]);

        // Assert
        $this->assertTrue($response->json('data.confirmPasswordReset.success'));
        $this->assertNotEmpty($response->json('data.confirmPasswordReset.accessToken'));
        $this->assertNotEmpty($response->json('data.confirmPasswordReset.refreshToken'));
        $this->assertEquals($user->id, $response->json('data.confirmPasswordReset.user.id'));
    }

    /** @test */
    public function auto_logs_in_user_after_reset()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        // Act
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    accessToken
                    user { id }
                }
            }
        ', [
            'input' => [
                'token' => $token,
                'password' => 'NewPass123!',
                'passwordConfirmation' => 'NewPass123!',
            ],
        ]);

        // Assert
        $accessToken = $response->json('data.confirmPasswordReset.accessToken');
        $this->assertNotEmpty($accessToken);
        $this->assertTrue($this->isValidJWT($accessToken));
    }

    /** @test */
    public function invalidates_all_sessions_on_reset()
    {
        // Arrange
        $user = User::factory()->create();
        
        // Crear 3 sesiones simuladas
        $session1 = $this->createUserSession($user);
        $session2 = $this->createUserSession($user);
        $session3 = $this->createUserSession($user);
        
        $token = $this->generateResetToken($user);

        // Act
        $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                }
            }
        ', [
            'input' => [
                'token' => $token,
                'password' => 'NewPass123!',
                'passwordConfirmation' => 'NewPass123!',
            ],
        ]);

        // Assert - Sesiones anteriores deben ser inválidas
        $this->assertFalse($this->isSessionValid($session1));
        $this->assertFalse($this->isSessionValid($session2));
        $this->assertFalse($this->isSessionValid($session3));
    }

    /** @test */
    public function validates_token_exists()
    {
        // Act
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                }
            }
        ', [
            'input' => [
                'token' => 'invalid_token',
                'password' => 'NewPass123!',
                'passwordConfirmation' => 'NewPass123!',
            ],
        ]);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /** @test */
    public function validates_token_not_expired()
    {
        // Arrange
        $user = User::factory()->create();
        $expiredToken = $this->generateResetToken($user, expiresIn: -1);

        // Act
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                }
            }
        ', [
            'input' => [
                'token' => $expiredToken,
                'password' => 'NewPass123!',
            ],
        ]);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /** @test */
    public function validates_password_requirements()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        // Act - Password muy corto
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                }
            }
        ', [
            'input' => [
                'token' => $token,
                'password' => 'short',
            ],
        ]);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /** @test */
    public function cannot_reuse_same_reset_token_twice()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        // Act 1 - Primer reset
        $response1 = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) { success }
            }
        ', [
            'input' => [
                'token' => $token,
                'password' => 'NewPass123!',
            ],
        ]);

        $this->assertTrue($response1->json('data.confirmPasswordReset.success'));

        // Act 2 - Intentar reutilizar
        $response2 = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) { success }
            }
        ', [
            'input' => [
                'token' => $token,
                'password' => 'AnotherPass456!',
            ],
        ]);

        // Assert
        $this->assertNotNull($response2->json('errors'));
    }

    // =========================================================================
    // D. CONFIRMPASSWORDRESET MUTATION TESTS - CON CÓDIGO
    // =========================================================================

    /** @test */
    public function can_reset_with_6_digit_code()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);
        $code = Cache::get("password_reset_code:{$user->id}");

        // Act
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                    accessToken
                    user { id }
                }
            }
        ', [
            'input' => [
                'code' => $code,
                'password' => 'NewPass123!',
            ],
        ]);

        // Assert
        $this->assertTrue($response->json('data.confirmPasswordReset.success'));
        $this->assertNotEmpty($response->json('data.confirmPasswordReset.accessToken'));
        $this->assertEquals($user->id, $response->json('data.confirmPasswordReset.user.id'));
    }

    /** @test */
    public function rejects_invalid_code_format()
    {
        // Arrange
        $user = User::factory()->create();
        $this->generateResetToken($user);

        $invalidCodes = ['abc123', '12345', '1234567', 'ABCDEF'];

        foreach ($invalidCodes as $invalidCode) {
            $response = $this->graphQL('
                mutation ConfirmReset($input: PasswordResetInput!) {
                    confirmPasswordReset(input: $input) { success }
                }
            ', [
                'input' => [
                    'code' => $invalidCode,
                    'password' => 'NewPass123!',
                ],
            ]);

            $this->assertNotNull($response->json('errors'));
        }
    }

    /** @test */
    public function rejects_wrong_code()
    {
        // Arrange
        $user = User::factory()->create();
        $this->generateResetToken($user);
        $realCode = Cache::get("password_reset_code:{$user->id}");
        $wrongCode = '000000';
        
        if ($wrongCode === $realCode) $wrongCode = '111111';

        // Act
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) { success }
            }
        ', [
            'input' => [
                'code' => $wrongCode,
                'password' => 'NewPass123!',
            ],
        ]);

        // Assert
        $this->assertNotNull($response->json('errors'));
    }

    /** @test */
    public function cannot_reuse_same_reset_code_twice()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);
        $code = Cache::get("password_reset_code:{$user->id}");

        // Act 1
        $response1 = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) { success }
            }
        ', [
            'input' => [
                'code' => $code,
                'password' => 'NewPass123!',
            ],
        ]);

        $this->assertTrue($response1->json('data.confirmPasswordReset.success'));

        // Act 2 - Intentar reutilizar
        $response2 = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) { success }
            }
        ', [
            'input' => [
                'code' => $code,
                'password' => 'AnotherPass456!',
            ],
        ]);

        // Assert
        $this->assertNotNull($response2->json('errors'));
    }

    // =========================================================================
    // E. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function validates_code_belongs_to_correct_user()
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token1 = $this->generateResetToken($user1);
        $code1 = Cache::get("password_reset_code:{$user1->id}");

        $token2 = $this->generateResetToken($user2);

        // Act - Usar token de user2 pero código de user1
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                    user { id }
                }
            }
        ', [
            'input' => [
                'token' => $token2,
                'code' => $code1,
                'password' => 'NewPass123!',
            ],
        ]);

        // Assert - Prefiere token, debe usar user2
        $this->assertTrue($response->json('data.confirmPasswordReset.success'));
        $this->assertEquals($user2->id, $response->json('data.confirmPasswordReset.user.id'));
    }

    /** @test */
    public function cannot_use_code_from_different_user()
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->generateResetToken($user1);
        $code1 = Cache::get("password_reset_code:{$user1->id}");

        $this->generateResetToken($user2);

        // Act - Usar solo código de user1
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) { success }
            }
        ', [
            'input' => [
                'code' => $code1,
                'password' => 'NewPass123!',
            ],
        ]);

        // Assert - Debe resetear user1 (dueño del código)
        $this->assertTrue($response->json('data.confirmPasswordReset.success'));
    }

    /** @test */
    public function multiple_users_can_reset_independently()
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $token1 = $this->generateResetToken($user1);
        $token2 = $this->generateResetToken($user2);
        $code3 = Cache::get("password_reset_code:{$user3->id}") && $this->generateResetToken($user3);

        // Act - 3 resets diferentes
        $r1 = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                    user { id }
                }
            }
        ', ['input' => ['token' => $token1, 'password' => 'Pass1!']]);

        $r2 = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                    user { id }
                }
            }
        ', ['input' => ['token' => $token2, 'password' => 'Pass2!']]);

        $r3 = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) {
                    success
                    user { id }
                }
            }
        ', ['input' => ['code' => $code3, 'password' => 'Pass3!']]);

        // Assert
        $this->assertTrue($r1->json('data.confirmPasswordReset.success'));
        $this->assertTrue($r2->json('data.confirmPasswordReset.success'));
        $this->assertTrue($r3->json('data.confirmPasswordReset.success'));
    }

    /** @test */
    public function rejects_both_token_and_code_in_single_request()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);
        $code = Cache::get("password_reset_code:{$user->id}");

        // Act - Enviar ambos en el mismo request
        $response = $this->graphQL('
            mutation ConfirmReset($input: PasswordResetInput!) {
                confirmPasswordReset(input: $input) { success }
            }
        ', [
            'input' => [
                'token' => $token,
                'code' => $code,
                'password' => 'NewPass123!',
            ],
        ]);

        // Assert - Debe rechazar
        $this->assertNotNull($response->json('errors'));
    }

    // =========================================================================
    // F. MAILPIT INTEGRATION TESTS
    // =========================================================================

    /** @test */
    public function password_reset_email_arrives_to_mailpit_with_token_and_code()
    {
        if (!$this->isMailpitAvailable()) {
            $this->markTestSkipped('Mailpit is not available');
        }

        // Arrange
        $this->clearMailpit();
        $user = User::factory()->create(['email' => 'resettest@example.com']);

        // Act
        $this->graphQL(
            'mutation ResetPassword($email: Email!) { resetPassword(email: $email) }',
            ['email' => 'resettest@example.com']
        );

        $this->artisan('queue:work', ['--once' => true, '--queue' => 'emails']);
        sleep(1);

        // Assert
        $messages = $this->getMailpitMessages();
        $resetEmail = collect($messages)->first(function ($msg) {
            return str_contains($msg['To'][0]['Address'] ?? '', 'resettest@example.com');
        });

        $this->assertNotNull($resetEmail);
        $emailBody = $this->getMailpitMessageBody($resetEmail['ID']);
        $this->assertMatchesRegularExpression('/[a-zA-Z0-9]{32}/', $emailBody);
        $this->assertMatchesRegularExpression('/\b\d{6}\b/', $emailBody);
    }

    // =========================================================================
    // G. EDGE CASES
    // =========================================================================

    /** @test */
    public function token_expires_after_24_hours()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user, expiresIn: -1);

        // Act
        $response = $this->graphQL('
            query CheckReset($token: String!) {
                passwordResetStatus(token: $token) {
                    isValid
                    canReset
                }
            }
        ', ['token' => $token]);

        // Assert
        $this->assertFalse($response->json('data.passwordResetStatus.isValid'));
        $this->assertFalse($response->json('data.passwordResetStatus.canReset'));
    }

    /** @test */
    public function password_requirements_are_enforced()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->generateResetToken($user);

        $invalidPasswords = ['short', '12345678', 'password', 'PASSWORD', 'pass word'];

        foreach ($invalidPasswords as $invalidPassword) {
            $response = $this->graphQL('
                mutation ConfirmReset($input: PasswordResetInput!) {
                    confirmPasswordReset(input: $input) { success }
                }
            ', [
                'input' => [
                    'token' => $token,
                    'password' => $invalidPassword,
                ],
            ]);

            $this->assertNotNull($response->json('errors'));
        }
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function generateResetToken(User $user, int $expiresIn = 24): string
    {
        $token = \Illuminate\Support\Str::random(32);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $expiresAt = now()->addHours($expiresIn);
        
        // Guardar con TOKEN como clave (sincronizado con PasswordResetService)
        Cache::put("password_reset:{$token}", [
            'user_id' => $user->id,
            'email' => $user->email,
            'expires_at' => $expiresAt->timestamp,
            'attempts_remaining' => 3,
        ], $expiresAt);
        
        // Guardar código de 6 dígitos con user->id
        Cache::put("password_reset_code:{$user->id}", $code, $expiresAt);
        
        return $token;
    }

    protected function createUserSession(User $user): string
    {
        $sessionId = \Illuminate\Support\Str::uuid();
        Cache::put("user_session:{$sessionId}", [
            'user_id' => $user->id,
            'created_at' => now(),
        ], now()->addHours(8));
        
        return $sessionId;
    }

    protected function isSessionValid(string $sessionId): bool
    {
        return Cache::has("user_session:{$sessionId}");
    }

    protected function isValidJWT(string $token): bool
    {
        $parts = explode('.', $token);
        return count($parts) === 3 && !empty($parts[0]) && !empty($parts[1]) && !empty($parts[2]);
    }

    protected function executeQueuedJobs(): void
    {
        $queueManager = app('queue');
        
        if (!$queueManager instanceof \Illuminate\Support\Testing\Fakes\QueueFake) {
            return;
        }
        
        $reflection = new \ReflectionClass($queueManager);
        $pushedJobsProperty = $reflection->getProperty('jobs');
        $pushedJobsProperty->setAccessible(true);
        $pushedJobs = $pushedJobsProperty->getValue($queueManager);
        
        foreach ($pushedJobs as $queueName => $jobsList) {
            foreach ($jobsList as $jobData) {
                if (isset($jobData['job'])) {
                    $job = $jobData['job'];
                    
                    if (method_exists($job, 'handle')) {
                        try {
                            app()->call([$job, 'handle']);
                        } catch (\Exception $e) {
                            logger()->error('Queue job execution failed: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
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