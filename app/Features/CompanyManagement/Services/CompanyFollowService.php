<?php

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Events\CompanyFollowed;
use App\Features\CompanyManagement\Events\CompanyUnfollowed;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyFollowService
{
    /**
     * Máximo de empresas que un usuario puede seguir.
     */
    const MAX_FOLLOWS = 50;

    /**
     * Seguir una empresa.
     */
    public function follow(User $user, Company $company): CompanyFollower
    {
        // Verificar si ya está siguiendo
        if ($this->isFollowing($user, $company)) {
            throw new \Exception('You are already following this company');
        }

        // Verificar límite
        if ($this->getFollowedCount($user) >= self::MAX_FOLLOWS) {
            throw new \Exception(
                'You have reached the maximum number of companies you can follow'
            );
        }

        return DB::transaction(function () use ($user, $company) {
            // Crear registro de seguimiento
            $follower = CompanyFollower::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);

            // Disparar evento
            event(new CompanyFollowed($user, $company));

            return $follower;
        });
    }

    /**
     * Dejar de seguir una empresa.
     */
    public function unfollow(User $user, Company $company): bool
    {
        // Verificar si está siguiendo
        if (!$this->isFollowing($user, $company)) {
            throw new \Exception('You are not following this company');
        }

        return DB::transaction(function () use ($user, $company) {
            // Eliminar registro de seguimiento
            $deleted = CompanyFollower::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->delete();

            // Disparar evento
            event(new CompanyUnfollowed($user, $company));

            return $deleted > 0;
        });
    }

    /**
     * Verificar si el usuario está siguiendo una empresa.
     */
    public function isFollowing(User $user, Company $company): bool
    {
        return CompanyFollower::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->exists();
    }

    /**
     * Obtener empresas seguidas por el usuario.
     */
    public function getFollowedCompanies(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $followerRecords = CompanyFollower::where('user_id', $user->id)
            ->orderBy('followed_at', 'desc')
            ->get();

        $companyIds = $followerRecords->pluck('company_id')->toArray();

        // Retornar Colección Eloquent de modelos Company
        return Company::whereIn('id', $companyIds)->get();
    }

    /**
     * Obtener registros de seguidores con metadatos (para consulta myFollowedCompanies).
     */
    public function getFollowedWithMetadata(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return CompanyFollower::where('user_id', $user->id)
            ->with('company')
            ->orderBy('followed_at', 'desc')
            ->get();
    }

    /**
     * Obtener conteo de empresas que el usuario está siguiendo.
     */
    public function getFollowedCount(User $user): int
    {
        return CompanyFollower::where('user_id', $user->id)->count();
    }

    /**
     * Obtener seguidores de una empresa.
     */
    public function getFollowers(Company $company): \Illuminate\Database\Eloquent\Collection
    {
        $followerRecords = CompanyFollower::where('company_id', $company->id)
            ->orderBy('followed_at', 'desc')
            ->get();

        $userIds = $followerRecords->pluck('user_id')->toArray();

        // Retornar Colección Eloquent de modelos User
        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Obtener conteo de seguidores de una empresa.
     */
    public function getFollowersCount(Company $company): int
    {
        return CompanyFollower::where('company_id', $company->id)->count();
    }
}
