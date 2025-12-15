<?php

namespace Tests\Feature\ContentManagement\Articles;

use Tests\TestCase;
use App\Features\ContentManagement\Models\ArticleCategory;

/**
 * List Help Center Categories Tests
 *
 * FASE 1: Tests para listar las 4 categorías globales de artículos
 * Endpoint: GET /api/help-center/categories
 *
 * Este endpoint es PÚBLICO (no requiere autenticación) ya que las categorías
 * son datos básicos necesarios para navegar el Help Center.
 *
 * Las 4 categorías son globales (seeded en migrations):
 * - ACCOUNT_PROFILE: Account & Profile
 * - SECURITY_PRIVACY: Security & Privacy
 * - BILLING_PAYMENTS: Billing & Payments
 * - TECHNICAL_SUPPORT: Technical Support
 */
class ListCategoriesTest extends TestCase
{
    /**
     * Test 1: Usuarios no autenticados pueden listar categorías
     *
     * VALIDACIÓN:
     * - El endpoint es público (no requiere token de autenticación)
     * - Devuelve HTTP 200 OK
     * - Response tiene estructura esperada: { "success": true, "data": [...] }
     *
     * CONTEXTO:
     * Las categorías son información pública necesaria para que cualquier usuario
     * pueda navegar el Help Center, incluso antes de autenticarse.
     */
    public function test_unauthenticated_user_can_list_categories(): void
    {
        // Arrange: No se necesita setup - categorías existen en DB

        // Act: Hacer request SIN token de autenticación
        $response = $this->getJson('/api/help-center/categories');

        // Assert: Verifica que el endpoint es público
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'description',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test 2: El endpoint devuelve exactamente 4 categorías
     *
     * VALIDACIÓN:
     * - DB tiene exactamente 4 categorías (seeded en migrations)
     * - Response data tiene exactamente 4 elementos
     * - Las 4 categorías corresponden a: ACCOUNT_PROFILE, SECURITY_PRIVACY,
     *   BILLING_PAYMENTS, TECHNICAL_SUPPORT
     *
     * CONTEXTO:
     * El sistema tiene un número fijo de categorías globales definidas en el
     * modelado V9.0. Este número no cambia dinámicamente.
     */
    public function test_returns_exactly_four_categories(): void
    {
        // Arrange: Verificar que DB tiene exactamente 4 categorías
        $categoriesInDb = ArticleCategory::count();
        $this->assertEquals(4, $categoriesInDb, 'DB debe tener exactamente 4 categorías');

        // Act: Obtener categorías del endpoint
        $response = $this->getJson('/api/help-center/categories');

        // Assert: Verifica cantidad exacta en response
        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');

        // Assert: Verifica que son las 4 categorías esperadas por código
        $data = $response->json('data');
        $codes = array_column($data, 'code');

        $expectedCodes = [
            'ACCOUNT_PROFILE',
            'SECURITY_PRIVACY',
            'BILLING_PAYMENTS',
            'TECHNICAL_SUPPORT',
        ];

        // Verifica que todos los códigos esperados están presentes
        foreach ($expectedCodes as $expectedCode) {
            $this->assertContains(
                $expectedCode,
                $codes,
                "La categoría {$expectedCode} debe estar presente en la respuesta"
            );
        }
    }

    /**
     * Test 3: Cada categoría incluye todos los campos esperados
     *
     * VALIDACIÓN:
     * - Cada categoría tiene: id (UUID), code, name, description
     * - Los tipos son correctos (strings)
     * - No hay campos faltantes
     * - Los valores no son null ni vacíos
     *
     * CONTEXTO:
     * Los campos devueltos deben coincidir con el schema de ArticleCategory
     * y la documentación de API (content-mgmt-api-docs.md líneas 926-957)
     */
    public function test_categories_include_expected_fields(): void
    {
        // Act: Obtener categorías
        $response = $this->getJson('/api/help-center/categories');

        // Assert: Estructura JSON esperada
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',       // UUID string
                        'code',     // Código único de categoría
                        'name',     // Nombre display de categoría
                        'description', // Descripción de categoría
                    ],
                ],
            ]);

        // Assert: Validar tipos y valores no vacíos para cada categoría
        $data = $response->json('data');

        foreach ($data as $category) {
            // Verifica que ID es UUID válido (formato string de 36 caracteres con guiones)
            $this->assertIsString($category['id']);
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $category['id'],
                'El ID debe ser un UUID válido'
            );

            // Verifica que code es string no vacío
            $this->assertIsString($category['code']);
            $this->assertNotEmpty($category['code'], 'Code no debe estar vacío');

            // Verifica que name es string no vacío
            $this->assertIsString($category['name']);
            $this->assertNotEmpty($category['name'], 'Name no debe estar vacío');

            // Verifica que description es string no vacío
            $this->assertIsString($category['description']);
            $this->assertNotEmpty($category['description'], 'Description no debe estar vacía');
        }
    }

    /**
     * Test 4: Las categorías se devuelven en orden consistente y esperado
     *
     * VALIDACIÓN:
     * - Las categorías tienen un orden determinístico
     * - Orden esperado: ACCOUNT_PROFILE → SECURITY_PRIVACY → BILLING_PAYMENTS → TECHNICAL_SUPPORT
     * - El orden es siempre el mismo (no aleatorio)
     *
     * CONTEXTO:
     * El orden de categorías es importante para la UX del Help Center.
     * Las categorías más generales (Account) van primero, las técnicas al final.
     * Este orden debe ser consistente en todas las requests.
     */
    public function test_categories_are_in_expected_order(): void
    {
        // Arrange: Define el orden esperado de categorías
        $expectedOrder = [
            'ACCOUNT_PROFILE',      // 1. Cuenta y perfil (más general)
            'SECURITY_PRIVACY',     // 2. Seguridad y privacidad
            'BILLING_PAYMENTS',     // 3. Facturación y pagos
            'TECHNICAL_SUPPORT',    // 4. Soporte técnico (más específico)
        ];

        // Act: Obtener categorías del endpoint
        $response = $this->getJson('/api/help-center/categories');

        // Assert: Verifica status
        $response->assertStatus(200);

        // Assert: Extrae los códigos en el orden devuelto
        $data = $response->json('data');
        $actualOrder = array_column($data, 'code');

        // Assert: Verifica que el orden es exactamente el esperado
        $this->assertEquals(
            $expectedOrder,
            $actualOrder,
            'Las categorías deben estar en el orden esperado: ' . implode(' → ', $expectedOrder)
        );

        // Assert adicional: Verifica que el orden es consistente haciendo múltiples requests
        $secondResponse = $this->getJson('/api/help-center/categories');
        $secondData = $secondResponse->json('data');
        $secondOrder = array_column($secondData, 'code');

        $this->assertEquals(
            $actualOrder,
            $secondOrder,
            'El orden de categorías debe ser consistente entre requests'
        );
    }
}
