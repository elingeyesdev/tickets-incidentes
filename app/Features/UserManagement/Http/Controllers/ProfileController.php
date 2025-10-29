<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Http\Controllers;

use App\Features\UserManagement\Http\Requests\UpdatePreferencesRequest;
use App\Features\UserManagement\Http\Requests\UpdateProfileRequest;
use App\Features\UserManagement\Http\Resources\PreferencesResource;
use App\Features\UserManagement\Http\Resources\ProfileResource;
use App\Features\UserManagement\Services\ProfileService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Profile Controller
 *
 * Handles user profile and preferences operations.
 */
class ProfileController
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {}

    /**
     * Get authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $profile = $this->profileService->getProfileByUserId($user->id);

        return response()->json(['data' => new ProfileResource($profile)]);
    }

    /**
     * Update authenticated user's profile.
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $profile = $this->profileService->updateProfile(
            $user->id,
            $request->validated()
        );

        // Touch the user to update updated_at timestamp
        $user->touch();

        // Reload the user to get fresh updated_at
        $user->refresh();

        return response()->json([
            'data' => [
                'userId' => $user->id,
                'profile' => (new ProfileResource($profile))->resolve($request),
                'updatedAt' => $user->updated_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Update authenticated user's preferences.
     *
     * @param UpdatePreferencesRequest $request
     * @return JsonResponse
     */
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $preferences = $this->profileService->updatePreferences(
            $user->id,
            $request->validated()
        );

        // Touch the user to update updated_at timestamp
        $user->touch();

        // Reload the user to get fresh updated_at
        $user->refresh();

        return response()->json([
            'data' => [
                'userId' => $user->id,
                'preferences' => (new PreferencesResource($preferences))->resolve($request),
                'updatedAt' => $user->updated_at->toIso8601String(),
            ]
        ]);
    }
}
