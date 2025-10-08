<?php

namespace Tests\Feature\GraphQL;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test Error Formatting (DEV vs PROD)
 *
 * Verifica que los errores se formateen correctamente según el entorno.
 */
class ErrorFormattingTest extends TestCase
{
    /**
     * Test: Validation error en DESARROLLO muestra información completa
     *
     * @return void
     */
    public function test_validation_error_shows_full_info_in_development(): void
    {
        // Simular entorno de desarrollo
        config(['app.env' => 'development']);
        config(['app.debug' => true]);

        $response = $this->postJson('/graphql', [
            'query' => '
                mutation {
                    register(input: {
                        email: "test@example.com"
                        password: "SecurePass123!"
                        passwordConfirmation: "WrongPassword!"
                        firstName: "Test"
                        lastName: "User"
                        acceptsTerms: true
                        acceptsPrivacyPolicy: true
                    }) {
                        accessToken
                    }
                }
            '
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'errors' => [
                '*' => [
                    'message',
                    'locations',  // Debe estar presente en DEV
                    'path',       // Debe estar presente en DEV
                    'extensions' => [
                        'code',
                        'category',
                        'validation',  // Estructura detallada en DEV
                        'timestamp',
                        'environment', // Debe estar en DEV
                    ]
                ]
            ]
        ]);

        $error = $response->json('errors.0');

        // Verificar que tenga campos de debugging
        $this->assertArrayHasKey('locations', $error, 'DEV debe tener locations');
        $this->assertArrayHasKey('path', $error, 'DEV debe tener path');
        $this->assertArrayHasKey('environment', $error['extensions'], 'DEV debe tener environment');
        $this->assertArrayHasKey('validation', $error['extensions'], 'DEV debe tener validation');

        // Verificar código de error
        $this->assertEquals('VALIDATION_ERROR', $error['extensions']['code']);
        $this->assertEquals('validation', $error['extensions']['category']);
    }

    /**
     * Test: Validation error en PRODUCCIÓN oculta información sensible
     *
     * @return void
     */
    public function test_validation_error_hides_sensitive_info_in_production(): void
    {
        // Simular entorno de producción
        config(['app.env' => 'production']);
        config(['app.debug' => false]);

        $response = $this->postJson('/graphql', [
            'query' => '
                mutation {
                    register(input: {
                        email: "test@example.com"
                        password: "SecurePass123!"
                        passwordConfirmation: "WrongPassword!"
                        firstName: "Test"
                        lastName: "User"
                        acceptsTerms: true
                        acceptsPrivacyPolicy: true
                    }) {
                        accessToken
                    }
                }
            '
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'errors' => [
                '*' => [
                    'message',
                    'extensions' => [
                        'code',
                        'category',
                        'fieldErrors', // Estructura user-friendly en PROD
                        'timestamp',
                    ]
                ]
            ]
        ]);

        $error = $response->json('errors.0');

        // Verificar que NO tenga campos de debugging
        $this->assertArrayNotHasKey('locations', $error, 'PROD NO debe tener locations');
        $this->assertArrayNotHasKey('path', $error, 'PROD NO debe tener path');
        $this->assertArrayNotHasKey('environment', $error['extensions'], 'PROD NO debe tener environment');
        $this->assertArrayNotHasKey('validation', $error['extensions'], 'PROD NO debe tener validation (usar fieldErrors)');
        $this->assertArrayNotHasKey('stacktrace', $error['extensions'], 'PROD NO debe tener stacktrace');

        // Verificar que tenga fieldErrors
        $this->assertArrayHasKey('fieldErrors', $error['extensions'], 'PROD debe tener fieldErrors');
        $this->assertIsArray($error['extensions']['fieldErrors']);

        // Verificar código de error (debe ser igual en ambos entornos)
        $this->assertEquals('VALIDATION_ERROR', $error['extensions']['code']);
        $this->assertEquals('validation', $error['extensions']['category']);

        // Verificar mensaje genérico en PROD
        $this->assertStringContainsString('válidos', strtolower($error['message']), 'PROD debe tener mensaje genérico');
    }

    /**
     * Test: Comparar estructura DEV vs PROD lado a lado
     *
     * @return void
     */
    public function test_dev_vs_prod_comparison(): void
    {
        $query = '
            mutation {
                register(input: {
                    email: "test@example.com"
                    password: "SecurePass123!"
                    passwordConfirmation: "WrongPassword!"
                    firstName: "Test"
                    lastName: "User"
                    acceptsTerms: true
                    acceptsPrivacyPolicy: true
                }) {
                    accessToken
                }
            }
        ';

        // === DESARROLLO ===
        config(['app.env' => 'development', 'app.debug' => true]);
        $devResponse = $this->postJson('/graphql', ['query' => $query]);
        $devError = $devResponse->json('errors.0');

        // === PRODUCCIÓN ===
        config(['app.env' => 'production', 'app.debug' => false]);
        $prodResponse = $this->postJson('/graphql', ['query' => $query]);
        $prodError = $prodResponse->json('errors.0');

        // === COMPARACIONES ===

        // Código debe ser IGUAL en ambos
        $this->assertEquals(
            $devError['extensions']['code'],
            $prodError['extensions']['code'],
            'El código de error debe ser igual en DEV y PROD'
        );

        // Mensaje debe ser DIFERENTE
        $this->assertNotEquals(
            $devError['message'],
            $prodError['message'],
            'El mensaje debe ser diferente en DEV vs PROD'
        );

        // DEV tiene campos que PROD no tiene
        $this->assertArrayHasKey('locations', $devError);
        $this->assertArrayNotHasKey('locations', $prodError);

        $this->assertArrayHasKey('path', $devError);
        $this->assertArrayNotHasKey('path', $prodError);

        // DEV tiene 'validation', PROD tiene 'fieldErrors'
        $this->assertArrayHasKey('validation', $devError['extensions']);
        $this->assertArrayNotHasKey('validation', $prodError['extensions']);

        $this->assertArrayNotHasKey('fieldErrors', $devError['extensions']);
        $this->assertArrayHasKey('fieldErrors', $prodError['extensions']);

        echo "\n\n=== DEV ERROR ===\n";
        echo json_encode($devError, JSON_PRETTY_PRINT);

        echo "\n\n=== PROD ERROR ===\n";
        echo json_encode($prodError, JSON_PRETTY_PRINT);
    }

    /**
     * Test: EnvironmentErrorFormatter detecta entorno correctamente
     *
     * @return void
     */
    public function test_environment_formatter_detects_production(): void
    {
        config(['app.env' => 'production']);
        $this->assertTrue(\App\Shared\GraphQL\Errors\EnvironmentErrorFormatter::isProduction());

        config(['app.env' => 'development']);
        $this->assertFalse(\App\Shared\GraphQL\Errors\EnvironmentErrorFormatter::isProduction());

        config(['app.env' => 'local']);
        $this->assertFalse(\App\Shared\GraphQL\Errors\EnvironmentErrorFormatter::isProduction());

        config(['app.env' => 'staging']);
        $this->assertFalse(\App\Shared\GraphQL\Errors\EnvironmentErrorFormatter::isProduction());
    }
}