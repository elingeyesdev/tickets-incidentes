<?php

namespace Tests\Feature\CompanyManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyFollowService
 *
 * Tests unitarios:
 * - follow() - Crea registro de follow
 * - follow() - Lanza excepción si ya sigue (ALREADY_FOLLOWING)
 * - follow() - Lanza excepción si excede 50 (MAX_FOLLOWS_EXCEEDED)
 * - unfollow() - Elimina registro
 * - unfollow() - Lanza excepción si no sigue (NOT_FOLLOWING)
 * - isFollowing() - Retorna true/false correctamente
 * - getFollowedCompanies() - Retorna collection de empresas
 * - getFollowedWithMetadata() - Retorna con followedAt
 * - getFollowedCount() - Retorna conteo correcto
 * - getFollowers() - Retorna seguidores de empresa
 * - getFollowersCount() - Retorna conteo de seguidores
 */
class CompanyFollowServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyFollowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CompanyFollowService::class);
    }

    /** @test */
    public function follow_creates_follow_record()
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();

        // Act
        $follower = $this->service->follow($user, $company);

        // Assert
        $this->assertInstanceOf(CompanyFollower::class, $follower);
        $this->assertEquals($user->id, $follower->user_id);
        $this->assertEquals($company->id, $follower->company_id);

        $this->assertDatabaseHas('business.company_followers', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function follow_throws_exception_when_already_following()
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();

        // Usuario ya sigue la empresa
        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You are already following this company');

        $this->service->follow($user, $company);
    }

    /** @test */
    public function follow_throws_exception_when_exceeding_max_follows()
    {
        // Arrange
        $user = User::factory()->create();

        // Crear 50 follows (límite máximo)
        Company::factory()->count(50)->create()->each(function ($company) use ($user) {
            CompanyFollower::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);
        });

        $company51 = Company::factory()->create();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You have reached the maximum number of companies you can follow');

        $this->service->follow($user, $company51);
    }

    /** @test */
    public function unfollow_deletes_follow_record()
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();

        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Act
        $result = $this->service->unfollow($user, $company);

        // Assert
        $this->assertTrue($result);

        $this->assertDatabaseMissing('business.company_followers', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function unfollow_throws_exception_when_not_following()
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();

        // Usuario NO sigue la empresa

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You are not following this company');

        $this->service->unfollow($user, $company);
    }

    /** @test */
    public function is_following_returns_true_when_following()
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();

        CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Act
        $result = $this->service->isFollowing($user, $company);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function is_following_returns_false_when_not_following()
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();

        // Act
        $result = $this->service->isFollowing($user, $company);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function get_followed_companies_returns_collection_of_companies()
    {
        // Arrange
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['name' => 'Company A']);
        $company2 = Company::factory()->create(['name' => 'Company B']);
        $company3 = Company::factory()->create(['name' => 'Company C']);

        // Usuario sigue company1 y company2
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $company1->id]);
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $company2->id]);

        // Act
        $followed = $this->service->getFollowedCompanies($user);

        // Assert
        $this->assertCount(2, $followed);

        $companyIds = $followed->pluck('id')->toArray();
        $this->assertContains($company1->id, $companyIds);
        $this->assertContains($company2->id, $companyIds);
        $this->assertNotContains($company3->id, $companyIds);
    }

    /** @test */
    public function get_followed_with_metadata_returns_with_followed_at()
    {
        // Arrange
        $user = User::factory()->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $follower1 = CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company1->id,
        ]);

        $follower2 = CompanyFollower::create([
            'user_id' => $user->id,
            'company_id' => $company2->id,
        ]);

        // Act
        $followed = $this->service->getFollowedWithMetadata($user);

        // Assert
        $this->assertCount(2, $followed);

        foreach ($followed as $follower) {
            $this->assertInstanceOf(CompanyFollower::class, $follower);
            $this->assertNotNull($follower->followed_at);
            $this->assertNotNull($follower->company);
        }
    }

    /** @test */
    public function get_followed_count_returns_correct_count()
    {
        // Arrange
        $user = User::factory()->create();

        Company::factory()->count(5)->create()->each(function ($company) use ($user) {
            CompanyFollower::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);
        });

        // Act
        $count = $this->service->getFollowedCount($user);

        // Assert
        $this->assertEquals(5, $count);
    }

    /** @test */
    public function get_followed_count_returns_zero_when_not_following_any()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $count = $this->service->getFollowedCount($user);

        // Assert
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function get_followers_returns_collection_of_users()
    {
        // Arrange
        $company = Company::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // user1 y user2 siguen la empresa
        CompanyFollower::create(['user_id' => $user1->id, 'company_id' => $company->id]);
        CompanyFollower::create(['user_id' => $user2->id, 'company_id' => $company->id]);

        // Act
        $followers = $this->service->getFollowers($company);

        // Assert
        $this->assertCount(2, $followers);

        $userIds = $followers->pluck('id')->toArray();
        $this->assertContains($user1->id, $userIds);
        $this->assertContains($user2->id, $userIds);
        $this->assertNotContains($user3->id, $userIds);
    }

    /** @test */
    public function get_followers_count_returns_correct_count()
    {
        // Arrange
        $company = Company::factory()->create();

        User::factory()->count(7)->create()->each(function ($user) use ($company) {
            CompanyFollower::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);
        });

        // Act
        $count = $this->service->getFollowersCount($company);

        // Assert
        $this->assertEquals(7, $count);
    }

    /** @test */
    public function get_followers_count_returns_zero_when_no_followers()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $count = $this->service->getFollowersCount($company);

        // Assert
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function max_follows_constant_is_50()
    {
        // Assert
        $this->assertEquals(50, CompanyFollowService::MAX_FOLLOWS);
    }
}
