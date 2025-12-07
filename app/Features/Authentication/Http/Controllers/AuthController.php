<?php declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Requests\RegisterRequest;
use App\Features\Authentication\Http\Requests\LoginRequest;
use App\Features\Authentication\Http\Requests\GoogleLoginRequest;
use App\Features\Authentication\Http\Resources\AuthPayloadResource;
use App\Features\Authentication\Http\Resources\AuthStatusResource;
use App\Features\Authentication\Http\Resources\RefreshPayloadResource;
use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Services\TokenService;
use App\Features\AuditLog\Services\ActivityLogService;
use App\Shared\Helpers\DeviceInfoParser;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Authentication Controller
 *
 * REST endpoints para autenticación de usuarios.
 * Todos los métodos delegan la lógica a AuthService para mantener separación de concerns.
 */
class AuthController
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService,
        private readonly TokenService $tokenService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * Register a new user
     *
     * Registra un nuevo usuario con email, contraseña y datos básicos.
     * Automáticamente crea una sesión y retorna tokens.
     *
     * @authenticated false
     * @response 201 {"accessToken": "...", "refreshToken": "...", "user": {...}, "sessionId": "...", ...}
     */
    #[OA\Post(
        path: '/api/auth/register',
        description: 'Creates a new user account with email and password authentication. Automatically generates JWT access token and refresh token. The refresh token is securely stored in an HttpOnly cookie for enhanced security. Upon successful registration, the user receives a verification email and can immediately start using the application.',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            description: 'User registration data',
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'passwordConfirmation', 'firstName', 'lastName', 'acceptsTerms', 'acceptsPrivacyPolicy'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        description: 'User email address (must be unique)',
                        type: 'string',
                        format: 'email',
                        example: 'user@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        description: 'Password (minimum 8 characters, must contain letters, numbers, and symbols)',
                        type: 'string',
                        format: 'password',
                        minLength: 8,
                        example: 'SecurePass123!'
                    ),
                    new OA\Property(
                        property: 'passwordConfirmation',
                        description: 'Password confirmation (must match password field)',
                        type: 'string',
                        format: 'password',
                        minLength: 8,
                        example: 'SecurePass123!'
                    ),
                    new OA\Property(
                        property: 'firstName',
                        description: 'User first name',
                        type: 'string',
                        maxLength: 255,
                        minLength: 2,
                        example: 'Juan'
                    ),
                    new OA\Property(
                        property: 'lastName',
                        description: 'User last name',
                        type: 'string',
                        maxLength: 255,
                        minLength: 2,
                        example: 'Pérez'
                    ),
                    new OA\Property(
                        property: 'acceptsTerms',
                        description: 'User must accept terms of service (must be true)',
                        type: 'boolean',
                        example: true
                    ),
                    new OA\Property(
                        property: 'acceptsPrivacyPolicy',
                        description: 'User must accept privacy policy (must be true)',
                        type: 'boolean',
                        example: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully. Returns authentication tokens and user data. Refresh token is set in HttpOnly cookie named "refresh_token".',
                headers: [
                    new OA\Header(
                        header: 'Set-Cookie',
                        description: 'HttpOnly cookie containing refresh token (name: refresh_token, path: /, httpOnly: true, sameSite: lax, maxAge: 43200 minutes)',
                        schema: new OA\Schema(type: 'string', example: 'refresh_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...; Path=/; HttpOnly; SameSite=Lax; Max-Age=2592000')
                    ),
                ],
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'accessToken', type: 'string', description: 'JWT access token for API authentication', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                        new OA\Property(property: 'refreshToken', type: 'string', description: 'Information message (actual token is in HttpOnly cookie)', example: 'Refresh token set in httpOnly cookie'),
                        new OA\Property(property: 'tokenType', type: 'string', description: 'Token type', example: 'Bearer'),
                        new OA\Property(property: 'expiresIn', type: 'integer', description: 'Access token expiration time in seconds', example: 2592000),
                        new OA\Property(
                            property: 'user',
                            description: 'Authenticated user information',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'userCode', type: 'string', example: 'USR-20241101-001'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'emailVerified', type: 'boolean', example: false),
                                new OA\Property(property: 'onboardingCompleted', type: 'boolean', example: false),
                                new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'INACTIVE', 'SUSPENDED'], example: 'ACTIVE'),
                                new OA\Property(property: 'displayName', type: 'string', example: 'Juan Pérez'),
                                new OA\Property(property: 'avatarUrl', type: 'string', example: null, nullable: true),
                                new OA\Property(property: 'theme', type: 'string', enum: ['light', 'dark'], example: 'light'),
                                new OA\Property(property: 'language', type: 'string', example: 'es'),
                                new OA\Property(
                                    property: 'roleContexts',
                                    description: 'User roles and their associated contexts',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'roleCode', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'], example: 'USER'),
                                            new OA\Property(property: 'roleName', type: 'string', example: 'Cliente'),
                                            new OA\Property(property: 'dashboardPath', type: 'string', example: '/tickets'),
                                            new OA\Property(
                                                property: 'company',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                                    new OA\Property(property: 'companyCode', type: 'string'),
                                                    new OA\Property(property: 'name', type: 'string'),
                                                ],
                                                type: 'object',
                                                nullable: true
                                            ),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'sessionId', type: 'string', format: 'uuid', description: 'Session identifier', example: '660e8400-e29b-41d4-a716-446655440011'),
                        new OA\Property(property: 'loginTimestamp', type: 'string', format: 'date-time', description: 'ISO 8601 timestamp', example: '2024-11-01T10:30:00+00:00'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - Invalid input data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The email field is required. (and 2 more errors)'),
                        new OA\Property(
                            property: 'errors',
                            description: 'Field-specific validation errors',
                            type: 'object',
                            example: [
                                'email' => ['El email es requerido.'],
                                'password' => ['La contraseña debe tener al menos 8 caracteres.'],
                                'acceptsTerms' => ['Debes aceptar los términos de servicio.'],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Email already registered',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Este email ya está registrado.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['email' => ['Este email ya está registrado.']]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // 1. Preparar datos para el servicio (REST camelCase → Service snake_case)
            $input = $this->mapInputToServiceFormat($request->validated());

            // 2. Extraer información del dispositivo desde contexto HTTP
            $deviceInfo = DeviceInfoParser::fromRequest($request);

            // 3. Delegar al servicio (TODA la lógica de negocio está aquí)
            $payload = $this->authService->register($input, $deviceInfo);

            // 4. Registrar actividad de registro
            $this->activityLogService->logRegister(
                userId: $payload['user']['id'],
                email: $payload['user']['email']
            );

            // 5. Retornar con refresh token en cookie (201 Created)
            return response()
                ->json(new AuthPayloadResource($payload), 201)
                ->cookie(
                    'refresh_token',
                    $payload['refresh_token'],
                    43200, // minutes
                    '/', // path
                    null, // domain
                    !app()->isLocal(), // secure
                    true, // httpOnly
                    false, // raw
                    'lax' // sameSite
                )
                ->cookie(
                    'jwt_token',
                    $payload['access_token'],
                    60, // minutes
                    '/',
                    null,
                    !app()->isLocal(),
                    false, // Not HttpOnly
                    false, // raw
                    'lax'
                );
        } catch (\Exception $e) {
            // Las excepciones son capturadas por ApiExceptionHandler middleware
            throw $e;
        }
    }

    /**
     * Login user
     *
     * Autentica un usuario con email y contraseña.
     * Retorna access token e inicia una nueva sesión.
     *
     * @authenticated false
     * @response 200 {"accessToken": "...", "refreshToken": "...", "user": {...}, "sessionId": "...", ...}
     */
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login user',
        description: 'Authenticates a user using email and password credentials. Generates a new JWT access token and refresh token for the session. The refresh token is securely stored in an HttpOnly cookie. Device information is automatically captured from the request headers for session tracking.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            description: 'Login credentials',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'password'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        description: 'User email address (case-insensitive)',
                        example: 'lukqs05@gmail.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        minLength: 8,
                        description: 'User password',
                        example: 'mklmklmkl'
                    ),
                    new OA\Property(
                        property: 'deviceName',
                        type: 'string',
                        nullable: true,
                        maxLength: 255,
                        description: 'Optional custom device name (auto-detected if not provided)',
                        example: 'fifon 15'
                    ),
                    new OA\Property(
                        property: 'rememberMe',
                        type: 'boolean',
                        nullable: true,
                        description: 'Keep session active for longer (currently not implemented)',
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful. Returns authentication tokens and user data. Refresh token is set in HttpOnly cookie named "refresh_token".',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'accessToken', type: 'string', description: 'JWT access token for API authentication', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                        new OA\Property(property: 'refreshToken', type: 'string', description: 'Information message (actual token is in HttpOnly cookie)', example: 'Refresh token set in httpOnly cookie'),
                        new OA\Property(property: 'tokenType', type: 'string', description: 'Token type', example: 'Bearer'),
                        new OA\Property(property: 'expiresIn', type: 'integer', description: 'Access token expiration time in seconds', example: 2592000),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            description: 'Authenticated user information',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'userCode', type: 'string', example: 'USR-20241101-001'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'emailVerified', type: 'boolean', example: true),
                                new OA\Property(property: 'onboardingCompleted', type: 'boolean', example: true),
                                new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'INACTIVE', 'SUSPENDED'], example: 'ACTIVE'),
                                new OA\Property(property: 'displayName', type: 'string', example: 'Juan Pérez'),
                                new OA\Property(property: 'avatarUrl', type: 'string', nullable: true, example: 'https://example.com/avatars/user.jpg'),
                                new OA\Property(property: 'theme', type: 'string', enum: ['light', 'dark'], example: 'light'),
                                new OA\Property(property: 'language', type: 'string', example: 'es'),
                                new OA\Property(
                                    property: 'roleContexts',
                                    type: 'array',
                                    description: 'User roles and their associated contexts',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'roleCode', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'], example: 'COMPANY_ADMIN'),
                                            new OA\Property(property: 'roleName', type: 'string', example: 'Administrador de Empresa'),
                                            new OA\Property(property: 'dashboardPath', type: 'string', example: '/empresa/dashboard'),
                                            new OA\Property(
                                                property: 'company',
                                                type: 'object',
                                                nullable: true,
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '770e8400-e29b-41d4-a716-446655440088'),
                                                    new OA\Property(property: 'companyCode', type: 'string', example: 'CMP-20241101-001'),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Acme Corp'),
                                                ]
                                            ),
                                        ]
                                    )
                                ),
                            ]
                        ),
                        new OA\Property(property: 'sessionId', type: 'string', format: 'uuid', description: 'Session identifier', example: '660e8400-e29b-41d4-a716-446655440011'),
                        new OA\Property(property: 'loginTimestamp', type: 'string', format: 'date-time', description: 'ISO 8601 timestamp', example: '2024-11-01T10:30:00+00:00'),
                    ]
                ),
                headers: [
                    new OA\Header(
                        header: 'Set-Cookie',
                        description: 'HttpOnly cookie containing refresh token (name: refresh_token, path: /, httpOnly: true, sameSite: lax, maxAge: 43200 minutes)',
                        schema: new OA\Schema(type: 'string', example: 'refresh_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...; Path=/; HttpOnly; SameSite=Lax; Max-Age=2592000')
                    ),
                ]
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - Invalid input data',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The email field is required.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            description: 'Field-specific validation errors',
                            example: [
                                'email' => ['El email es requerido.'],
                                'password' => ['La contraseña es requerida.'],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid credentials or user account is suspended/inactive',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials'),
                        new OA\Property(property: 'error', type: 'string', example: 'INVALID_CREDENTIALS'),
                    ]
                )
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // 1. Normalizar email (lowercase y trim)
            $email = strtolower(trim($request->input('email')));
            $password = $request->input('password');

            // 2. Extraer información del dispositivo desde contexto HTTP
            $deviceInfo = DeviceInfoParser::fromRequest($request);

            // Si se proveyó deviceName en el input, usarlo
            if (!empty($request->input('deviceName'))) {
                $deviceInfo['name'] = $request->input('deviceName');
            }

            // 3. Delegar al servicio (TODA la lógica de negocio está aquí)
            $payload = $this->authService->login($email, $password, $deviceInfo);

            // 4. Retornar con refresh token en cookie
            return response()
                ->json(new AuthPayloadResource($payload), 200)
                ->cookie(
                    'refresh_token',
                    $payload['refresh_token'],
                    43200, // minutes
                    '/', // path
                    null, // domain
                    !app()->isLocal(), // secure
                    true, // httpOnly
                    false, // raw
                    'lax' // sameSite
                )
                ->cookie(
                    'jwt_token',
                    $payload['access_token'],
                    60, // minutes
                    '/',
                    null,
                    !app()->isLocal(),
                    false, // Not HttpOnly
                    false, // raw
                    'lax'
                );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Login with Google OAuth
     *
     * Autentica usando Google ID token.
     * Si el usuario no existe, lo crea automáticamente.
     *
     * @authenticated false
     * @response 501 {"success": false, "message": "Google login not yet implemented"}
     */
    #[OA\Post(
        path: '/api/auth/login/google',
        summary: 'Login with Google OAuth (NOT IMPLEMENTED)',
        description: 'Authenticate user using Google ID token. If the user does not exist, it will be automatically created. NOTE: This endpoint is currently NOT IMPLEMENTED and will return HTTP 501 (Not Implemented). It is planned for a future release.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            description: 'Google authentication token',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['googleToken'],
                properties: [
                    new OA\Property(
                        property: 'googleToken',
                        type: 'string',
                        description: 'Google ID token obtained from Google OAuth flow',
                        example: 'eyJhbGciOiJSUzI1NiIsImtpZCI6IjZmNzI1NDEwMWY1NmU0M2...'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 501,
                description: 'Not Implemented - This feature is not yet available',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Google login not yet implemented'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - Invalid input data (when implemented)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The googleToken field is required.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            description: 'Field-specific validation errors',
                            example: ['googleToken' => ['The googleToken field is required.']]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid or expired Google token (when implemented)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid Google token'),
                        new OA\Property(property: 'error', type: 'string', example: 'INVALID_GOOGLE_TOKEN'),
                    ]
                )
            ),
        ]
    )]
    public function loginWithGoogle(GoogleLoginRequest $request): JsonResponse
    {
        // TODO: Implementar después de definir integración de Google OAuth
        return response()->json([
            'success' => false,
            'message' => 'Google login not yet implemented',
        ], 501);
    }

    /**
     * Refresh access token
     *
     * ⚠️ DEPRECATED: Use RefreshTokenController@refresh instead
     * Esta función se mantiene por compatibilidad pero no debe usarse.
     * La ruta `/api/auth/refresh` está mapeada a RefreshTokenController.
     *
     * @deprecated Use RefreshTokenController instead
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            // Obtener refresh token de múltiples fuentes en orden de prioridad
            $refreshToken = $request->header('X-Refresh-Token')
                ?? $request->cookie('refresh_token')
                ?? $request->input('refreshToken');

            if (!$refreshToken) {
                throw new AuthenticationException('Refresh token required');
            }

            $deviceInfo = DeviceInfoParser::fromRequest($request);

            $payload = $this->authService->refreshToken($refreshToken, $deviceInfo);

            return response()
                ->json(new RefreshPayloadResource($payload), 200)
                ->cookie(
                    'refresh_token',
                    $payload['refresh_token'],
                    43200, // minutes
                    '/', // path
                    null, // domain
                    !app()->isLocal(), // secure
                    true, // httpOnly
                    false, // raw
                    'lax' // sameSite
                );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get auth status
     *
     * Obtiene el estado de autenticación actual del usuario.
     * Retorna user, sesión actual, e información de tokens.
     *
     * @authenticated true
     * @response 200 {"isAuthenticated": true, "user": {...}, "currentSession": {...}, "tokenInfo": {...}}
     */
    #[OA\Get(
        path: '/api/auth/status',
        summary: 'Get authentication status',
        description: 'Retrieves the current authentication status for the authenticated user. Returns complete user information, active session details, and JWT token metadata. Requires a valid Bearer token in the Authorization header. This endpoint is useful for checking if a user session is still valid and retrieving updated user data.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authentication status retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'isAuthenticated', type: 'boolean', description: 'Always true for successful responses', example: true),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            description: 'Current authenticated user information',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'userCode', type: 'string', example: 'USR-20241101-001'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'emailVerified', type: 'boolean', example: true),
                                new OA\Property(property: 'onboardingCompleted', type: 'boolean', example: true),
                                new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'INACTIVE', 'SUSPENDED'], example: 'ACTIVE'),
                                new OA\Property(property: 'displayName', type: 'string', example: 'Juan Pérez'),
                                new OA\Property(property: 'avatarUrl', type: 'string', nullable: true, example: 'https://example.com/avatars/user.jpg'),
                                new OA\Property(property: 'theme', type: 'string', enum: ['light', 'dark'], example: 'light'),
                                new OA\Property(property: 'language', type: 'string', example: 'es'),
                                new OA\Property(
                                    property: 'roleContexts',
                                    type: 'array',
                                    description: 'User roles and their associated contexts',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'roleCode', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'], example: 'USER'),
                                            new OA\Property(property: 'roleName', type: 'string', example: 'Cliente'),
                                            new OA\Property(property: 'dashboardPath', type: 'string', example: '/tickets'),
                                            new OA\Property(
                                                property: 'company',
                                                type: 'object',
                                                nullable: true,
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '770e8400-e29b-41d4-a716-446655440088'),
                                                    new OA\Property(property: 'companyCode', type: 'string', example: 'CMP-20241101-001'),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Acme Corp'),
                                                ]
                                            ),
                                        ]
                                    )
                                ),
                            ]
                        ),
                        new OA\Property(
                            property: 'currentSession',
                            type: 'object',
                            nullable: true,
                            description: 'Current active session information (null if session not found)',
                            properties: [
                                new OA\Property(property: 'sessionId', type: 'string', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440011'),
                                new OA\Property(property: 'deviceName', type: 'string', example: 'Chrome on Windows'),
                                new OA\Property(property: 'ipAddress', type: 'string', format: 'ipv4', example: '192.168.1.100'),
                                new OA\Property(property: 'userAgent', type: 'string', example: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36...'),
                                new OA\Property(property: 'lastUsedAt', type: 'string', format: 'date-time', nullable: true, example: '2024-11-01T10:25:00+00:00'),
                                new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time', nullable: true, example: '2024-12-01T10:30:00+00:00'),
                                new OA\Property(property: 'isCurrent', type: 'boolean', example: true),
                            ]
                        ),
                        new OA\Property(
                            property: 'tokenInfo',
                            type: 'object',
                            description: 'JWT token metadata',
                            properties: [
                                new OA\Property(property: 'expiresIn', type: 'integer', description: 'Seconds until token expires', example: 2591000),
                                new OA\Property(property: 'issuedAt', type: 'string', format: 'date-time', description: 'Token issue timestamp', example: '2024-11-01T10:30:00+00:00'),
                                new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Invalid, expired, or missing access token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function status(Request $request): JsonResponse
    {
        try {
            // Obtener usuario autenticado (garantizado por auth:api middleware)
            $user = $request->user();

            if (!$user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Obtener token del header
            $authHeader = $request->header('Authorization', '');
            if (!$authHeader) {
                throw new AuthenticationException('Missing Authorization header');
            }

            $token = str_replace('Bearer ', '', $authHeader);

            // Validar token y obtener payload
            $tokenPayload = $this->tokenService->validateAccessToken($token);

            // Obtener sesión actual usando session_id del JWT
            $currentSession = null;
            $currentSessionId = $tokenPayload->session_id ?? null;
            if ($currentSessionId) {
                $currentSession = RefreshToken::query()
                    ->where('user_id', $user->id)
                    ->where('id', $currentSessionId)
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->first();

                // Marcar que es la sesión actual agregando un atributo
                if ($currentSession) {
                    $currentSession->setAttribute('is_current', true);
                }
            }

            // Cargar relaciones necesarias
            $user->load(['profile', 'userRoles']);

            $status = [
                'isAuthenticated' => true,
                'user' => $user,
                'currentSession' => $currentSession,
                'tokenInfo' => [
                    'expiresIn' => max(0, ($tokenPayload->exp ?? 0) - now()->timestamp),
                    'issuedAt' => now()->toIso8601String(),
                    'tokenType' => 'Bearer',
                ],
            ];

            return response()->json(new AuthStatusResource($status), 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Mapea inputs REST (camelCase) a formato esperado por AuthService (snake_case)
     *
     * También sanitiza y normaliza los datos:
     * - Email: lowercase y trimmed
     * - Nombres: Capitalizados y trimmed
     *
     * NOTA: acceptsTerms y acceptsPrivacyPolicy ya están validados por RegisterRequest
     * que valida con 'accepted', garantizando que sean true.
     *
     * @param array $input
     * @return array
     */
    private function mapInputToServiceFormat(array $input): array
    {
        return [
            'email' => strtolower(trim($input['email'])),
            'password' => $input['password'],
            'first_name' => $this->capitalizeName($input['firstName']),
            'last_name' => $this->capitalizeName($input['lastName']),
            'terms_accepted' => true, // Validado por RegisterRequest con 'accepted'
        ];
    }

    /**
     * Set JWT token in a cookie for web routes (Blade templates)
     *
     * Web routes need the JWT in a cookie because they use regular HTTP navigation
     * (window.location.href) which doesn't send custom headers like Authorization.
     *
     * API calls continue to use the Authorization header.
     */
    public function setWebToken(Request $request): JsonResponse
    {
        try {
            // Get token from Authorization header
            $authHeader = $request->header('Authorization', '');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json(['message' => 'Invalid or missing Authorization header'], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);

            // Validate token
            $this->tokenService->validateAccessToken($token);

            // Set token in cookie for web routes
            return response()->json(['message' => 'Web token set'])
                ->cookie(
                    'jwt_token',
                    $token,
                    3600,  // 60 minutes (same as access token TTL)
                    '/',
                    null,
                    !app()->isLocal(),  // Secure in production
                    false,  // NOT HttpOnly (JavaScript needs to read for API calls)
                    false,
                    'lax'
                );
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error setting web token: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Capitaliza nombres correctamente (Primera letra mayúscula de cada palabra)
     * También sanitiza quitando HTML tags
     *
     * @param string $name Nombre a capitalizar
     * @return string Nombre capitalizado y sanitizado
     */
    private function capitalizeName(string $name): string
    {
        $sanitized = strip_tags(trim($name));
        return ucwords($sanitized);
    }
}
