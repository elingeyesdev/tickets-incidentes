<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Http\Controllers;

use App\Features\ExternalIntegration\Http\Requests\CheckUserRequest;
use App\Features\ExternalIntegration\Http\Requests\ExternalLoginRequest;
use App\Features\ExternalIntegration\Http\Requests\ExternalRegisterRequest;
use App\Features\ExternalIntegration\Http\Requests\ValidateKeyRequest;
use App\Features\ExternalIntegration\Services\ExternalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * Controller para autenticación externa (Widget).
 * 
 * Todos los endpoints requieren el middleware 'service.api-key'
 * que valida la API Key y adjunta la empresa al request.
 */
#[OA\Tag(name: 'External Auth', description: 'Authentication for external widgets')]
class ExternalAuthController extends Controller
{
    public function __construct(
        private readonly ExternalAuthService $authService,
    ) {
    }

    // ========================================================================
    // VALIDATE KEY
    // ========================================================================

    #[OA\Post(
        path: '/api/external/validate-key',
        operationId: 'external_validate_key',
        description: 'Validates if the provided Service API Key is active and valid. Returns basic company information.',
        summary: 'Validate Service API Key',
        tags: ['External Auth'],
        parameters: [
            new OA\Parameter(
                name: 'X-Service-Key',
                description: 'Service API Key provided by the admin for external integration',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'sk_prod_...')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API Key is valid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'API Key válida'),
                        new OA\Property(
                            property: 'company',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'logoUrl', type: 'string', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid or missing API Key',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    /**
     * Valida que la API Key sea válida y retorna info de la empresa.
     * 
     * El middleware ya validó la key, así que solo retornamos éxito
     * con información básica de la empresa.
     * 
     * @param ValidateKeyRequest $request
     * @return JsonResponse
     */
    public function validateKey(ValidateKeyRequest $request): JsonResponse
    {
        $company = $request->get('_service_company');

        return response()->json([
            'success' => true,
            'message' => 'API Key válida',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logoUrl' => $company->logo_url,
            ],
        ]);
    }

    // ========================================================================
    // CHECK USER
    // ========================================================================

    #[OA\Post(
        path: '/api/external/check-user',
        operationId: 'external_check_user',
        description: 'Checks if a user with the given email exists in the system.',
        summary: 'Check if user exists',
        tags: ['External Auth'],
        parameters: [
            new OA\Parameter(
                name: 'X-Service-Key',
                description: 'Service API Key',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Check result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'exists', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'firstName', type: 'string'),
                                new OA\Property(property: 'lastName', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                            ],
                            type: 'object',
                            nullable: true
                        ),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    /**
     * Verifica si un email existe en Helpdesk.
     * 
     * Usado por el widget para decidir si mostrar login o registro.
     * 
     * @param CheckUserRequest $request
     * @return JsonResponse
     */
    public function checkUser(CheckUserRequest $request): JsonResponse
    {
        $result = $this->authService->checkUserExists($request->email);

        return response()->json([
            'success' => true,
            'exists' => $result['exists'],
            'user' => $result['user'],
        ]);
    }

    // ========================================================================
    // LOGIN (TRUSTED)
    // ========================================================================

    #[OA\Post(
        path: '/api/external/login',
        operationId: 'external_login_trusted',
        description: 'Authenticates a user via "Trusted Login" using the Service API Key. No password required if the request comes from a trusted source (validated by API Key).',
        summary: 'Trusted Login',
        tags: ['External Auth'],
        parameters: [
            new OA\Parameter(
                name: 'X-Service-Key',
                description: 'Service API Key',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'accessToken', type: 'string', example: 'eyJ0...'),
                        new OA\Property(property: 'expiresIn', type: 'integer', example: 3600),
                        new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Login failed (User not found or inactive)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'code', type: 'string', example: 'USER_NOT_FOUND'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    /**
     * Login automático para usuarios existentes.
     * 
     * Este login es "trusted" porque:
     * - Viene de un proyecto con API Key válida
     * - El usuario ya fue autenticado en el proyecto externo
     * 
     * No requiere contraseña, la confianza está en la API Key.
     * 
     * @param ExternalLoginRequest $request
     * @return JsonResponse
     */
    public function login(ExternalLoginRequest $request): JsonResponse
    {
        $company = $request->get('_service_company');

        $result = $this->authService->loginTrusted(
            email: $request->email,
            company: $company,
        );

        if (!$result['success']) {
            // Mapear errores a respuestas amigables
            $errorMessages = [
                'USER_NOT_FOUND' => 'Usuario no encontrado.',
                'USER_INACTIVE' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ];

            return response()->json([
                'success' => false,
                'code' => $result['error'],
                'message' => $errorMessages[$result['error']] ?? 'Error al iniciar sesión.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'accessToken' => $result['accessToken'],
            'expiresIn' => $result['expiresIn'],
            'tokenType' => 'Bearer',
        ]);
    }

    // ========================================================================
    // REGISTER
    // ========================================================================

    #[OA\Post(
        path: '/api/external/register',
        operationId: 'external_register',
        description: 'Registers a new user through the widget.',
        summary: 'External Register',
        tags: ['External Auth'],
        parameters: [
            new OA\Parameter(
                name: 'X-Service-Key',
                description: 'Service API Key',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'firstName', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'firstName', type: 'string'),
                    new OA\Property(property: 'lastName', type: 'string', nullable: true),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registration successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'accessToken', type: 'string'),
                        new OA\Property(property: 'expiresIn', type: 'integer'),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    /**
     * Registra un nuevo usuario desde el widget.
     * 
     * El usuario proporciona su contraseña para poder acceder
     * directamente a Helpdesk en el futuro.
     * 
     * @param ExternalRegisterRequest $request
     * @return JsonResponse
     */
    public function register(ExternalRegisterRequest $request): JsonResponse
    {
        $company = $request->get('_service_company');

        $result = $this->authService->registerUser(
            data: $request->validated(),
            company: $company,
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'code' => 'REGISTRATION_FAILED',
                'message' => 'Error al crear la cuenta.',
                'errors' => $result['errors'] ?? [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'accessToken' => $result['accessToken'],
            'expiresIn' => $result['expiresIn'],
            'tokenType' => 'Bearer',
            'message' => 'Cuenta creada exitosamente.',
        ], 201);
    }

    // ========================================================================
    // LOGIN MANUAL (con contraseña)
    // ========================================================================

    #[OA\Post(
        path: '/api/external/login-manual',
        operationId: 'external_login_manual',
        description: 'Standard login with email and password for the widget (fallback).',
        summary: 'Manual Login',
        tags: ['External Auth'],
        parameters: [
            new OA\Parameter(
                name: 'X-Service-Key',
                description: 'Service API Key',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'accessToken', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid Credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            )
        ]
    )]
    /**
     * Login manual con contraseña.
     * 
     * Usado cuando el login automático falla y el usuario
     * necesita autenticarse manualmente.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function loginManual(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $company = $request->get('_service_company');
        $email = strtolower(trim($request->email));

        // Buscar usuario
        $user = \App\Features\UserManagement\Models\User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Email o contraseña incorrectos.',
            ], 401);
        }

        // Verificar contraseña
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Email o contraseña incorrectos.',
            ], 401);
        }

        // Verificar usuario activo
        if ($user->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'code' => 'USER_INACTIVE',
                'message' => 'Tu cuenta está inactiva.',
            ], 403);
        }

        // Login exitoso - usar el servicio
        $result = $this->authService->loginTrusted($email, $company);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'code' => 'LOGIN_FAILED',
                'message' => 'Error al iniciar sesión.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'accessToken' => $result['accessToken'],
            'expiresIn' => $result['expiresIn'],
            'tokenType' => 'Bearer',
        ]);
    }

    // ========================================================================
    // REFRESH TOKEN (Widget)
    // ========================================================================

    #[OA\Post(
        path: '/api/external/refresh-token',
        operationId: 'external_refresh_token',
        description: 'Refreshes the widget access token using the current token.',
        summary: 'Refresh Token',
        tags: ['External Auth'],
        parameters: [
            new OA\Parameter(
                name: 'X-Service-Key',
                description: 'Service API Key',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'accessToken', type: 'string'),
                        new OA\Property(property: 'expiresIn', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid or expired token',
            )
        ]
    )]
    /**
     * Refresca el access token del widget.
     * 
     * A diferencia del refresh normal que usa cookies,
     * este acepta el token actual en el header y devuelve uno nuevo.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        // Obtener token del header Authorization
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'code' => 'TOKEN_REQUIRED',
                'message' => 'Token requerido.',
            ], 401);
        }

        $currentToken = substr($authHeader, 7);
        $company = $request->get('_service_company');

        try {
            // Validar el token actual (puede estar expirado hasta 5 minutos)
            $payload = $this->authService->validateTokenForRefresh($currentToken);

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'code' => 'TOKEN_INVALID',
                    'message' => 'Token inválido o expirado.',
                ], 401);
            }

            // Obtener usuario del payload
            $user = \App\Features\UserManagement\Models\User::find($payload['sub']);

            if (!$user || !$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'code' => 'USER_INACTIVE',
                    'message' => 'Usuario inactivo.',
                ], 403);
            }

            // Generar nuevo token
            $result = $this->authService->loginTrusted($user->email, $company);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'code' => 'REFRESH_FAILED',
                    'message' => 'Error al refrescar token.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'accessToken' => $result['accessToken'],
                'expiresIn' => $result['expiresIn'],
                'tokenType' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'code' => 'REFRESH_FAILED',
                'message' => 'Error al refrescar token.',
            ], 401);
        }
    }
}
