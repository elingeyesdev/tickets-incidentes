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
use App\Shared\Utilities\DeviceInfoParser;
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
        summary: 'Register a new user',
        description: 'Create a new user account and return authentication tokens',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'passwordConfirmation', 'firstName', 'lastName', 'acceptsTerms', 'acceptsPrivacyPolicy'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'passwordConfirmation', type: 'string', format: 'password'),
                    new OA\Property(property: 'firstName', type: 'string', maxLength: 255),
                    new OA\Property(property: 'lastName', type: 'string', maxLength: 255),
                    new OA\Property(property: 'acceptsTerms', type: 'boolean'),
                    new OA\Property(property: 'acceptsPrivacyPolicy', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 409, description: 'Email already exists'),
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

            // 4. Retornar con refresh token en cookie
            return response()
                ->json(new AuthPayloadResource($payload), 201)
                ->cookie(
                    'refresh_token',
                    $payload['refresh_token'],
                    minutes: 43200, // 30 días
                    path: '/',
                    domain: null,
                    secure: !app()->isLocal(),
                    httpOnly: true,
                    sameSite: 'lax'
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
        description: 'Authenticate user with email and password',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'deviceName', type: 'string', nullable: true),
                    new OA\Property(property: 'rememberMe', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
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
                    minutes: 43200,
                    path: '/',
                    domain: null,
                    secure: !app()->isLocal(),
                    httpOnly: true,
                    sameSite: 'lax'
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
     * @response 200 {"accessToken": "...", "refreshToken": "...", "user": {...}, ...}
     */
    #[OA\Post(
        path: '/api/auth/login/google',
        summary: 'Login with Google',
        description: 'Authenticate user using Google ID token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['googleToken'],
                properties: [
                    new OA\Property(property: 'googleToken', type: 'string', description: 'Google ID token'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Invalid token'),
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
     * Renueva el access token usando refresh token.
     * El refresh token puede venir en header X-Refresh-Token, cookie, o body.
     *
     * @authenticated false
     * @response 200 {"accessToken": "...", "refreshToken": "...", "tokenType": "Bearer", "expiresIn": 2592000}
     */
    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Refresh access token',
        description: 'Get a new access token using refresh token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string', nullable: true, description: 'Refresh token (if not in header/cookie)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token refreshed successfully'),
            new OA\Response(response: 401, description: 'Invalid or missing refresh token'),
        ]
    )]
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
                    minutes: 43200,
                    path: '/',
                    domain: null,
                    secure: !app()->isLocal(),
                    httpOnly: true,
                    sameSite: 'lax'
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
        description: 'Get current authenticated user status and session information',
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Authentication status retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
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

            // Obtener sesión actual usando query directa
            $currentSession = RefreshToken::query()
                ->where('user_id', $user->id)
                ->where('id', $tokenPayload['session_id'])
                ->first();

            // Cargar relaciones necesarias
            $user->load(['profile', 'userRoles']);

            $status = [
                'isAuthenticated' => true,
                'user' => $user,
                'currentSession' => $currentSession,
                'tokenInfo' => [
                    'expiresIn' => $tokenPayload['exp'] - now()->timestamp,
                    'issuedAt' => now()->setTimestamp($tokenPayload['iat'])->toIso8601String(),
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
     * Capitaliza nombres correctamente (Primera letra mayúscula, resto minúsculas)
     * También sanitiza quitando HTML tags
     *
     * @param string $name Nombre a capitalizar
     * @return string Nombre capitalizado y sanitizado
     */
    private function capitalizeName(string $name): string
    {
        $sanitized = strip_tags(trim($name));
        return ucfirst(strtolower($sanitized));
    }
}
