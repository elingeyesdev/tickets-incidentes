<?php

namespace Tests\Feature\Authentication;

use App\Features\Authentication\Events\UserRegistered;
use App\Features\Authentication\Jobs\SendEmailVerificationJob;
use App\Features\Authentication\Mail\EmailVerificationMail;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Email Verification Flow Test
 *
 * Prueba el flujo completo de verificación de email:
 * 1. Registro de usuario
 * 2. Evento UserRegistered se dispara
 * 3. Job SendEmailVerificationJob se encola
 * 4. Email se envía correctamente
 * 5. Token se almacena en cache
 * 6. verifyEmail mutation funciona
 * 7. resendVerification mutation funciona
 */
class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar migrations
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_sends_verification_email_on_registration()
    {
        // Fake queue y mail
        Queue::fake();
        Event::fake([UserRegistered::class]);

        // Ejecutar mutation de registro
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                    user {
                        id
                        email
                        emailVerified
                    }
                }
            }
        ', [
            'input' => [
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Test',
                'lastName' => 'User',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ],
        ]);

        // Verificar respuesta exitosa
        $response->assertJson([
            'data' => [
                'register' => [
                    'user' => [
                        'email' => 'test@example.com',
                        'emailVerified' => false, // Inicialmente no verificado
                    ],
                ],
            ],
        ]);

        // Verificar que el evento UserRegistered se disparó
        Event::assertDispatched(UserRegistered::class, function ($event) {
            return $event->user->email === 'test@example.com'
                && !empty($event->verificationToken);
        });

        // Verificar que el job fue encolado
        Queue::assertPushed(SendEmailVerificationJob::class, function ($job) {
            return $job->user->email === 'test@example.com'
                && !empty($job->verificationToken);
        });
    }

    /** @test */
    public function it_stores_verification_token_in_cache()
    {
        // Registrar usuario
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    user { id }
                }
            }
        ', [
            'input' => [
                'email' => 'cache.test@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Cache',
                'lastName' => 'Test',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ],
        ]);

        $userId = $response->json('data.register.user.id');

        // Verificar que el token existe en cache
        $cacheKey = "email_verification:{$userId}";
        $this->assertTrue(Cache::has($cacheKey));

        $token = Cache::get($cacheKey);
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertGreaterThan(32, strlen($token)); // Token debe ser suficientemente largo
    }

    /** @test */
    public function it_verifies_email_with_valid_token()
    {
        // Crear usuario no verificado
        $user = User::factory()->create([
            'email' => 'verify.test@example.com',
            'email_verified' => false,
        ]);

        // Crear token en cache
        $token = 'test_verification_token_' . bin2hex(random_bytes(32));
        Cache::put("email_verification:{$user->id}", $token, now()->addHours(24));

        // Ejecutar mutation de verificación
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                    message
                    canResend
                }
            }
        ', [
            'token' => $token,
        ]);

        // Verificar respuesta exitosa
        $response->assertJson([
            'data' => [
                'verifyEmail' => [
                    'success' => true,
                    'canResend' => false,
                ],
            ],
        ]);

        // Verificar que el usuario está verificado en BD
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);

        // Verificar que el token fue eliminado del cache
        $this->assertFalse(Cache::has("email_verification:{$user->id}"));
    }

    /** @test */
    public function it_fails_with_invalid_token()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                    message
                    canResend
                }
            }
        ', [
            'token' => 'invalid_token_12345',
        ]);

        // Verificar que falló
        $response->assertJson([
            'data' => [
                'verifyEmail' => [
                    'success' => false,
                    'canResend' => true,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_fails_if_email_already_verified()
    {
        // Crear usuario ya verificado
        $user = User::factory()->create([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        // Crear token en cache
        $token = 'test_token_' . bin2hex(random_bytes(32));
        Cache::put("email_verification:{$user->id}", $token, now()->addHours(24));

        // Intentar verificar
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                    message
                }
            }
        ', [
            'token' => $token,
        ]);

        // Verificar que falló
        $response->assertJson([
            'data' => [
                'verifyEmail' => [
                    'success' => false,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_resends_verification_email()
    {
        Queue::fake();

        // Crear usuario no verificado
        $user = User::factory()->create([
            'email' => 'resend.test@example.com',
            'email_verified' => false,
        ]);

        // Autenticar usuario
        $this->actingAs($user, 'api');

        // Ejecutar mutation de reenvío
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ResendVerification {
                resendVerification {
                    success
                    message
                    canResend
                }
            }
        ');

        // Verificar respuesta exitosa
        $response->assertJson([
            'data' => [
                'resendVerification' => [
                    'success' => true,
                    'canResend' => false,
                ],
            ],
        ]);

        // Verificar que el job fue encolado nuevamente
        Queue::assertPushed(SendEmailVerificationJob::class);

        // Verificar que hay un nuevo token en cache
        $this->assertTrue(Cache::has("email_verification:{$user->id}"));
    }

    /** @test */
    public function it_fails_resend_if_already_verified()
    {
        // Crear usuario ya verificado
        $user = User::factory()->create([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        // Autenticar usuario
        $this->actingAs($user, 'api');

        // Intentar reenviar
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ResendVerification {
                resendVerification {
                    success
                    message
                }
            }
        ');

        // Verificar que falló con mensaje apropiado
        $response->assertJson([
            'data' => [
                'resendVerification' => [
                    'success' => false,
                ],
            ],
        ]);

        $this->assertStringContainsString(
            'ya está verificado',
            $response->json('data.resendVerification.message')
        );
    }

    /** @test */
    public function it_shows_email_verification_status()
    {
        // Crear usuario no verificado
        $user = User::factory()->create([
            'email' => 'status.test@example.com',
            'email_verified' => false,
        ]);

        // Autenticar usuario
        $this->actingAs($user, 'api');

        // Consultar status
        $response = $this->graphQL(/** @lang GraphQL */ '
            query EmailVerificationStatus {
                emailVerificationStatus {
                    isVerified
                    email
                    canResend
                    attemptsRemaining
                }
            }
        ');

        // Verificar respuesta
        $response->assertJson([
            'data' => [
                'emailVerificationStatus' => [
                    'isVerified' => false,
                    'email' => 'status.test@example.com',
                    'canResend' => true,
                    'attemptsRemaining' => 3,
                ],
            ],
        ]);
    }

    /** @test */
    public function complete_email_verification_flow()
    {
        Queue::fake();
        Mail::fake();

        // 1. Usuario se registra
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation Register($input: RegisterInput!) {
                register(input: $input) {
                    accessToken
                    user {
                        id
                        email
                        emailVerified
                    }
                }
            }
        ', [
            'input' => [
                'email' => 'complete.flow@example.com',
                'password' => 'SecurePass123!',
                'passwordConfirmation' => 'SecurePass123!',
                'firstName' => 'Complete',
                'lastName' => 'Flow',
                'acceptsTerms' => true,
                'acceptsPrivacyPolicy' => true,
            ],
        ]);

        $userId = $response->json('data.register.user.id');
        $this->assertFalse($response->json('data.register.user.emailVerified'));

        // 2. Job fue encolado
        Queue::assertPushed(SendEmailVerificationJob::class);

        // 3. Simular procesamiento del job
        $user = User::find($userId);
        $token = Cache::get("email_verification:{$userId}");
        $this->assertNotNull($token);

        // 4. Usuario hace click en el email y verifica
        $verifyResponse = $this->graphQL(/** @lang GraphQL */ '
            mutation VerifyEmail($token: String!) {
                verifyEmail(token: $token) {
                    success
                    message
                }
            }
        ', [
            'token' => $token,
        ]);

        $this->assertTrue($verifyResponse->json('data.verifyEmail.success'));

        // 5. Verificar que el usuario está verificado
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());

        // 6. Token eliminado del cache
        $this->assertFalse(Cache::has("email_verification:{$userId}"));
    }
}
