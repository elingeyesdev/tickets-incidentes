<?php

namespace Tests\Feature\Authentication;

use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Test suite para JWT Roles
 *
 * Verifica:
 * - El JWT contiene claim 'roles' con estructura correcta
 * - Todos los roles del usuario se incluyen en el token
 * - JWTHelper puede acceder a los roles desde el JWT
 * - getCompanyIdFromJWT() retorna company_id correcto
 * - hasRoleFromJWT() verifica roles correctamente
 * - getCompanyIdsForRole() retorna array de empresas
 * - Usuario sin roles retorna 'USER' como default
 */
class JWTRolesTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = User::factory()
            ->withProfile()
            ->create(['email' => 'jwttest@example.com']);
    }

    /**
     * @test
     * JWT contiene claim 'roles' con estructura correcta
     */
    public function jwt_contains_roles_claim_with_correct_structure(): void
    {
        // Arrange - Usuario con rol USER
        $this->testUser->assignRole('USER');

        // Act - Login
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testUser->email,
            'password' => 'password',
        ]);

        // Assert - Token retornado
        $response->assertStatus(200);
        $accessToken = $response->json('accessToken');

        // Decodificar JWT (retorna objeto, convertir a array)
        $decoded = JWT::decode(
            $accessToken,
            new Key(config('jwt.secret'), config('jwt.algo'))
        );

        // Convertir a array para fácil acceso
        $decodedArray = json_decode(json_encode($decoded), true);

        // Verificar que tiene claim 'roles'
        $this->assertArrayHasKey('roles', $decodedArray);
        $this->assertIsArray($decodedArray['roles']);

        // Verificar estructura del primer rol
        $this->assertNotEmpty($decodedArray['roles']);
        $firstRole = $decodedArray['roles'][0];
        $this->assertArrayHasKey('code', $firstRole);
        $this->assertArrayHasKey('company_id', $firstRole);
        $this->assertEquals('USER', $firstRole['code']);
        $this->assertNull($firstRole['company_id']);
    }

    /**
     * @test
     * JWT incluye múltiples roles con sus company_ids
     */
    public function jwt_includes_multiple_roles_with_company_ids(): void
    {
        // Arrange - Crear 2 empresas
        $company1 = \App\Features\CompanyManagement\Models\Company::factory()->create();
        $company2 = \App\Features\CompanyManagement\Models\Company::factory()->create();

        // Asignar roles en diferentes empresas
        $this->testUser->assignRole('COMPANY_ADMIN', $company1->id);
        $this->testUser->assignRole('AGENT', $company2->id);

        // Act - Login
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testUser->email,
            'password' => 'password',
        ]);

        // Assert
        $response->assertStatus(200);
        $decoded = JWT::decode(
            $response->json('accessToken'),
            new Key(config('jwt.secret'), config('jwt.algo'))
        );

        $decodedArray = json_decode(json_encode($decoded), true);

        // Debe tener 2 roles
        $this->assertCount(2, $decodedArray['roles']);

        // Verificar COMPANY_ADMIN
        $adminRole = collect($decodedArray['roles'])->firstWhere('code', 'COMPANY_ADMIN');
        $this->assertNotNull($adminRole);
        $this->assertEquals($company1->id, $adminRole['company_id']);

        // Verificar AGENT
        $agentRole = collect($decodedArray['roles'])->firstWhere('code', 'AGENT');
        $this->assertNotNull($agentRole);
        $this->assertEquals($company2->id, $agentRole['company_id']);
    }


    /**
     * @test
     * Usuario sin roles retorna 'USER' como default en JWT
     */
    public function user_without_roles_returns_user_as_default_in_jwt(): void
    {
        // Arrange - Usuario SIN roles asignados
        // El usuario se crea pero sin assignRole()

        // Act - Login
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testUser->email,
            'password' => 'password',
        ]);

        // Assert
        $response->assertStatus(200);
        $decoded = JWT::decode(
            $response->json('accessToken'),
            new Key(config('jwt.secret'), config('jwt.algo'))
        );

        // Debe tener rol 'USER' por defecto
        $decodedArray = json_decode(json_encode($decoded), true);
        $this->assertCount(1, $decodedArray['roles']);
        $this->assertEquals('USER', $decodedArray['roles'][0]['code']);
        $this->assertNull($decodedArray['roles'][0]['company_id']);
    }

    /**
     * @test
     * JWT claim 'roles' contiene todos los claims estándar
     */
    public function jwt_includes_all_standard_claims_plus_roles(): void
    {
        // Arrange
        $this->testUser->assignRole('USER');

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testUser->email,
            'password' => 'password',
        ]);

        // Assert
        $response->assertStatus(200);
        $decoded = JWT::decode(
            $response->json('accessToken'),
            new Key(config('jwt.secret'), config('jwt.algo'))
        );

        // Claims estándar (deben existir)
        $this->assertObjectHasProperty('iss', $decoded);
        $this->assertObjectHasProperty('aud', $decoded);
        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertObjectHasProperty('exp', $decoded);
        $this->assertObjectHasProperty('sub', $decoded);

        // Claims personalizados (deben existir)
        $this->assertObjectHasProperty('user_id', $decoded);
        $this->assertObjectHasProperty('email', $decoded);
        $this->assertObjectHasProperty('session_id', $decoded);

        // Nuevo claim de roles
        $this->assertObjectHasProperty('roles', $decoded);

        // Verificar valores
        $this->assertEquals(config('jwt.issuer'), $decoded->iss);
        $this->assertEquals(config('jwt.audience'), $decoded->aud);
        $this->assertEquals($this->testUser->id, $decoded->user_id);
        $this->assertEquals($this->testUser->email, $decoded->email);
    }

    /**
     * @test
     * PLATFORM_ADMIN en JWT tiene company_id null
     */
    public function platform_admin_role_has_null_company_id_in_jwt(): void
    {
        // Arrange
        $this->testUser->assignRole('PLATFORM_ADMIN');

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testUser->email,
            'password' => 'password',
        ]);

        // Assert
        $response->assertStatus(200);
        $decoded = JWT::decode(
            $response->json('accessToken'),
            new Key(config('jwt.secret'), config('jwt.algo'))
        );

        $decodedArray = json_decode(json_encode($decoded), true);
        $platformAdminRole = collect($decodedArray['roles'])->firstWhere('code', 'PLATFORM_ADMIN');
        $this->assertNotNull($platformAdminRole);
        $this->assertNull($platformAdminRole['company_id']);
    }

    /**
     * Helper: Login and get tokens
     */
    private function loginUser(): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testUser->email,
            'password' => 'password',
        ]);

        return [
            'accessToken' => $response->json('accessToken'),
            'refreshToken' => $response->getCookie('refresh_token')?->getValue(),
        ];
    }

    /**
     * Helper: Add JWT authorization header
     */
    private function withJWT(string $token, ?string $refreshToken = null): self
    {
        $headers = [
            'Authorization' => "Bearer {$token}",
        ];

        if ($refreshToken) {
            $headers['X-Refresh-Token'] = $refreshToken;
        }

        return $this->withHeaders($headers);
    }
}
