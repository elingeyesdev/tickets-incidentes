<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Http\Controllers;

use App\Features\AuditLog\Services\ActivityLogService;
use App\Features\UserManagement\Http\Requests\UpdatePreferencesRequest;
use App\Features\UserManagement\Http\Requests\UpdateProfileRequest;
use App\Features\UserManagement\Http\Requests\UploadAvatarRequest;
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
        private readonly ProfileService $profileService,
        private readonly ActivityLogService $activityLogService
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
                                new OA\Property(property: 'firstName', type: 'string', description: 'User first name', example: 'Juan'),
                                new OA\Property(property: 'lastName', type: 'string', description: 'User last name', example: 'Pérez'),
                                new OA\Property(property: 'displayName', type: 'string', description: 'Display name (firstName + lastName)', example: 'Juan Pérez'),
                                new OA\Property(property: 'phoneNumber', type: 'string', nullable: true, description: 'Phone number', example: '+56912345678'),
                                new OA\Property(property: 'avatarUrl', type: 'string', nullable: true, description: 'Avatar image URL', example: 'https://example.com/avatars/user123.jpg'),
                                new OA\Property(property: 'theme', type: 'string', description: 'UI theme preference', enum: ['light', 'dark'], example: 'light'),
                                new OA\Property(property: 'language', type: 'string', description: 'Language preference', enum: ['es', 'en'], example: 'es'),
                                new OA\Property(property: 'timezone', type: 'string', description: 'Timezone identifier', example: 'America/Santiago'),
                                new OA\Property(property: 'pushWebNotifications', type: 'boolean', description: 'Push web notifications enabled', example: true),
                                new OA\Property(property: 'notificationsTickets', type: 'boolean', description: 'Ticket notifications enabled', example: true),
                                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Profile creation timestamp', example: '2025-01-15T10:30:00Z'),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', description: 'Last update timestamp', example: '2025-11-01T14:25:30Z'),
                            ]
                        )
                    ],
                    example: [
                        'data' => [
                            'firstName' => 'Juan',
                            'lastName' => 'Pérez',
                            'displayName' => 'Juan Pérez',
                            'phoneNumber' => '+56912345678',
                            'avatarUrl' => 'https://example.com/avatars/user123.jpg',
                            'theme' => 'light',
                            'language' => 'es',
                            'timezone' => 'America/Santiago',
                            'pushWebNotifications' => true,
                            'notificationsTickets' => true,
                            'createdAt' => '2025-01-15T10:30:00Z',
                            'updatedAt' => '2025-11-01T14:25:30Z',
                        ]
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
                    new OA\Property(
                        property: 'firstName',
                        type: 'string',
                        minLength: 2,
                        maxLength: 100,
                        nullable: true,
                        description: 'User first name',
                        example: 'María'
                    ),
                    new OA\Property(
                        property: 'lastName',
                        type: 'string',
                        minLength: 2,
                        maxLength: 100,
                        nullable: true,
                        description: 'User last name',
                        example: 'González'
                    ),
                    new OA\Property(
                        property: 'phoneNumber',
                        type: 'string',
                        minLength: 10,
                        maxLength: 20,
                        nullable: true,
                        description: 'Phone number (digits, spaces, +, -, (, ) allowed)',
                        example: '+56912345678'
                    ),
                    new OA\Property(
                        property: 'avatarUrl',
                        type: 'string',
                        format: 'uri',
                        maxLength: 2048,
                        nullable: true,
                        description: 'Avatar image URL',
                        example: 'https://example.com/avatars/maria.jpg'
                    ),
                ],
                example: [
                    'firstName' => 'María',
                    'lastName' => 'González',
                    'phoneNumber' => '+56987654321',
                    'avatarUrl' => 'https://example.com/avatars/maria.jpg'
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
                                new OA\Property(property: 'userId', type: 'string', format: 'uuid', description: 'User ID', example: '9d4e8c91-5f2a-4b3e-8a1f-2c3d4e5f6a7b'),
                                new OA\Property(
                                    property: 'profile',
                                    type: 'object',
                                    description: 'Updated profile data (ProfileResource with 12 fields)',
                                    properties: [
                                        new OA\Property(property: 'firstName', type: 'string', example: 'María'),
                                        new OA\Property(property: 'lastName', type: 'string', example: 'González'),
                                        new OA\Property(property: 'displayName', type: 'string', example: 'María González'),
                                        new OA\Property(property: 'phoneNumber', type: 'string', nullable: true, example: '+56987654321'),
                                        new OA\Property(property: 'avatarUrl', type: 'string', nullable: true, example: 'https://example.com/avatars/maria.jpg'),
                                        new OA\Property(property: 'theme', type: 'string', example: 'light'),
                                        new OA\Property(property: 'language', type: 'string', example: 'es'),
                                        new OA\Property(property: 'timezone', type: 'string', example: 'America/Santiago'),
                                        new OA\Property(property: 'pushWebNotifications', type: 'boolean', example: true),
                                        new OA\Property(property: 'notificationsTickets', type: 'boolean', example: true),
                                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00Z'),
                                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2025-11-01T15:45:20Z'),
                                    ]
                                ),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', description: 'Last update timestamp', example: '2025-11-01T15:45:20Z'),
                            ]
                        )
                    ],
                    example: [
                        'data' => [
                            'userId' => '9d4e8c91-5f2a-4b3e-8a1f-2c3d4e5f6a7b',
                            'profile' => [
                                'firstName' => 'María',
                                'lastName' => 'González',
                                'displayName' => 'María González',
                                'phoneNumber' => '+56987654321',
                                'avatarUrl' => 'https://example.com/avatars/maria.jpg',
                                'theme' => 'light',
                                'language' => 'es',
                                'timezone' => 'America/Santiago',
                                'pushWebNotifications' => true,
                                'notificationsTickets' => true,
                                'createdAt' => '2025-01-15T10:30:00Z',
                                'updatedAt' => '2025-11-01T15:45:20Z',
                            ],
                            'updatedAt' => '2025-11-01T15:45:20Z',
                        ]
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'firstName' => ['First name must be at least 2 characters'],
                                'phoneNumber' => ['Phone number format is invalid'],
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        // Capturar valores anteriores para auditoría
        $oldProfile = $user->profile?->only(['first_name', 'last_name', 'display_name', 'phone_number', 'avatar_url']);

        $profile = $this->profileService->updateProfile(
            $user->id,
            $request->validated()
        );

        // Registrar actividad
        $this->activityLogService->log(
            action: 'profile_updated',
            userId: $user->id,
            entityType: 'user',
            entityId: $user->id,
            oldValues: $oldProfile,
            newValues: $request->validated()
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
                        description: 'UI theme preference',
                        example: 'dark'
                    ),
                    new OA\Property(
                        property: 'language',
                        type: 'string',
                        enum: ['es', 'en'],
                        nullable: true,
                        description: 'Language preference (ISO 639-1)',
                        example: 'en'
                    ),
                    new OA\Property(
                        property: 'timezone',
                        type: 'string',
                        nullable: true,
                        description: 'Timezone identifier (IANA timezone database)',
                        example: 'America/New_York'
                    ),
                    new OA\Property(
                        property: 'pushWebNotifications',
                        type: 'boolean',
                        nullable: true,
                        description: 'Enable push web notifications',
                        example: false
                    ),
                    new OA\Property(
                        property: 'notificationsTickets',
                        type: 'boolean',
                        nullable: true,
                        description: 'Enable ticket notifications',
                        example: true
                    ),
                ],
                example: [
                    'theme' => 'dark',
                    'language' => 'en',
                    'timezone' => 'America/New_York',
                    'pushWebNotifications' => false,
                    'notificationsTickets' => true,
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
                                new OA\Property(property: 'userId', type: 'string', format: 'uuid', description: 'User ID', example: '9d4e8c91-5f2a-4b3e-8a1f-2c3d4e5f6a7b'),
                                new OA\Property(
                                    property: 'preferences',
                                    type: 'object',
                                    description: 'Updated preferences data (PreferencesResource with 6 fields)',
                                    properties: [
                                        new OA\Property(property: 'theme', type: 'string', enum: ['light', 'dark'], example: 'dark'),
                                        new OA\Property(property: 'language', type: 'string', enum: ['es', 'en'], example: 'en'),
                                        new OA\Property(property: 'timezone', type: 'string', example: 'America/New_York'),
                                        new OA\Property(property: 'pushWebNotifications', type: 'boolean', example: false),
                                        new OA\Property(property: 'notificationsTickets', type: 'boolean', example: true),
                                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2025-11-01T16:20:15Z'),
                                    ]
                                ),
                                new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', description: 'Last update timestamp', example: '2025-11-01T16:20:15Z'),
                            ]
                        )
                    ],
                    example: [
                        'data' => [
                            'userId' => '9d4e8c91-5f2a-4b3e-8a1f-2c3d4e5f6a7b',
                            'preferences' => [
                                'theme' => 'dark',
                                'language' => 'en',
                                'timezone' => 'America/New_York',
                                'pushWebNotifications' => false,
                                'notificationsTickets' => true,
                                'updatedAt' => '2025-11-01T16:20:15Z',
                            ],
                            'updatedAt' => '2025-11-01T16:20:15Z',
                        ]
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'theme' => ['Theme must be either "light" or "dark"'],
                                'language' => ['Language must be either "es" or "en"'],
                                'timezone' => ['Invalid timezone'],
                            ]
                        ),
                    ]
                )
            ),
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

    /**
     * Upload authenticated user's avatar image. Throttled: 10 requests/hour.
     */
    #[OA\Post(
        path: '/api/users/me/avatar',
        operationId: 'upload_my_avatar',
        summary: 'Upload user avatar image',
        description: 'Upload and store avatar image for the authenticated user. Throttled: 10 requests/hour. Supported formats: JPEG, PNG, GIF, WebP. Max size: 5 MB.',
        tags: ['User Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Avatar image file (multipart/form-data)',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['avatar'],
                    properties: [
                        new OA\Property(
                            property: 'avatar',
                            type: 'string',
                            format: 'binary',
                            description: 'Avatar image file'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avatar uploaded successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Avatar uploaded successfully'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'avatarUrl',
                                    type: 'string',
                                    format: 'uri',
                                    example: 'http://localhost:8000/storage/avatars/550e8400-e29b-41d4-a716-446655440000/1731774123_profile.jpg'
                                ),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['avatar' => ['Avatar must not exceed 5 MB']]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        try {
            $avatarUrl = $this->profileService->uploadAvatarFile(
                $user->id,
                $request->file('avatar')
            );

            return response()->json([
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatarUrl' => $avatarUrl,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error uploading avatar',
                'errors' => ['avatar' => [$e->getMessage()]]
            ], 422);
        }
    }
}
