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
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: '/api/users/me/profile',
        summary: 'Get authenticated user profile',
        description: 'Retrieve the profile information of the currently authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User Management - Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'firstName', type: 'string'),
                                new OA\Property(property: 'lastName', type: 'string'),
                                new OA\Property(property: 'phoneNumber', type: 'string', nullable: true),
                                new OA\Property(property: 'avatarUrl', type: 'string', nullable: true),
                                new OA\Property(property: 'birthDate', type: 'string', format: 'date', nullable: true),
                                new OA\Property(property: 'bio', type: 'string', nullable: true),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
    #[OA\Patch(
        path: '/api/users/me/profile',
        summary: 'Update authenticated user profile',
        description: 'Update profile information for the currently authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User Management - Profile'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'lastName', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'phoneNumber', type: 'string', maxLength: 20, nullable: true),
                    new OA\Property(property: 'avatarUrl', type: 'string', format: 'url', maxLength: 500, nullable: true),
                    new OA\Property(property: 'birthDate', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'bio', type: 'string', maxLength: 1000, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'userId', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'profile', type: 'object'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
    #[OA\Patch(
        path: '/api/users/me/preferences',
        summary: 'Update authenticated user preferences',
        description: 'Update preferences for the currently authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['User Management - Profile'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'language', type: 'string', enum: ['en', 'es', 'fr'], nullable: true),
                    new OA\Property(property: 'timezone', type: 'string', maxLength: 50, nullable: true),
                    new OA\Property(property: 'theme', type: 'string', enum: ['light', 'dark', 'auto'], nullable: true),
                    new OA\Property(
                        property: 'notificationSettings',
                        type: 'object',
                        nullable: true,
                        properties: [
                            new OA\Property(property: 'emailNotifications', type: 'boolean'),
                            new OA\Property(property: 'pushNotifications', type: 'boolean'),
                            new OA\Property(property: 'smsNotifications', type: 'boolean'),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preferences updated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'userId', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'preferences', type: 'object'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
