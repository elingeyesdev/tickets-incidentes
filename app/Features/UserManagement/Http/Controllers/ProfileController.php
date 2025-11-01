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
     */
    #[OA\Get(
        path: '/api/users/me/profile',
        operationId: 'get_my_profile',
        summary: 'Get authenticated user profile',
        description: 'Retrieve the complete profile information of the currently authenticated user',
        tags: ['User Profile'],
        security: [['bearerAuth' => []]],
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
                                new OA\Property(property: 'firstName', type: 'string', description: 'User first name'),
                                new OA\Property(property: 'lastName', type: 'string', description: 'User last name'),
                                new OA\Property(property: 'phoneNumber', type: 'string', nullable: true, description: 'Phone number (E.164 format)'),
                                new OA\Property(property: 'avatarUrl', type: 'string', nullable: true, description: 'Avatar image URL'),
                                new OA\Property(property: 'birthDate', type: 'string', format: 'date', nullable: true, description: 'Birth date'),
                                new OA\Property(property: 'bio', type: 'string', nullable: true, description: 'Biography'),
                                new OA\Property(property: 'theme', type: 'string', nullable: true, description: 'UI theme preference'),
                                new OA\Property(property: 'language', type: 'string', nullable: true, description: 'Language preference'),
                                new OA\Property(property: 'timezone', type: 'string', nullable: true, description: 'Timezone'),
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
     * Update authenticated user's profile. Throttled: 30 requests/hour.
     */
    #[OA\Patch(
        path: '/api/users/me/profile',
        operationId: 'update_my_profile',
        summary: 'Update authenticated user profile',
        description: 'Update profile information for the currently authenticated user. Throttled: 30 requests/hour. All fields are optional.',
        tags: ['User Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            description: 'Profile fields to update. All fields are optional.',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', maxLength: 255, nullable: true, description: 'User first name'),
                    new OA\Property(property: 'lastName', type: 'string', maxLength: 255, nullable: true, description: 'User last name'),
                    new OA\Property(property: 'phoneNumber', type: 'string', maxLength: 20, nullable: true, description: 'Phone number (E.164 format)'),
                    new OA\Property(property: 'avatar', type: 'string', nullable: true, description: 'Avatar URL or file path'),
                    new OA\Property(property: 'timezone', type: 'string', nullable: true, description: 'Timezone identifier'),
                    new OA\Property(property: 'birthDate', type: 'string', format: 'date', nullable: true, description: 'Birth date'),
                    new OA\Property(property: 'bio', type: 'string', maxLength: 1000, nullable: true, description: 'User biography'),
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
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'userId', type: 'string', format: 'uuid', description: 'User ID'),
                                new OA\Property(property: 'profile', type: 'object', description: 'Updated profile data'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', description: 'Last update timestamp'),
                            ]
                        )
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
     * Update authenticated user's preferences. Throttled: 50 requests/hour.
     */
    #[OA\Patch(
        path: '/api/users/me/preferences',
        operationId: 'update_my_preferences',
        summary: 'Update authenticated user preferences',
        description: 'Update preferences for the currently authenticated user. Throttled: 50 requests/hour. All fields are optional.',
        tags: ['User Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            description: 'Preference fields to update. All fields are optional.',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'theme',
                        type: 'string',
                        enum: ['light', 'dark'],
                        nullable: true,
                        description: 'UI theme preference'
                    ),
                    new OA\Property(
                        property: 'language',
                        type: 'string',
                        enum: ['en', 'es', 'fr', 'de'],
                        nullable: true,
                        description: 'Language preference (ISO 639-1)'
                    ),
                    new OA\Property(
                        property: 'pushNotifications',
                        type: 'boolean',
                        nullable: true,
                        description: 'Enable push notifications'
                    ),
                    new OA\Property(
                        property: 'emailNotifications',
                        type: 'boolean',
                        nullable: true,
                        description: 'Enable email notifications'
                    ),
                    new OA\Property(
                        property: 'weeklyDigest',
                        type: 'boolean',
                        nullable: true,
                        description: 'Enable weekly digest emails'
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
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'userId', type: 'string', format: 'uuid', description: 'User ID'),
                                new OA\Property(property: 'preferences', type: 'object', description: 'Updated preferences data'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', description: 'Last update timestamp'),
                            ]
                        )
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
