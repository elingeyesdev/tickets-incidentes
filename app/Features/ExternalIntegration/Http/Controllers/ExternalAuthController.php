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

/**
 * Controller para autenticación externa (Widget).
 * 
 * Todos los endpoints requieren el middleware 'service.api-key'
 * que valida la API Key y adjunta la empresa al request.
 * 
 * Endpoints:
 * - POST /api/external/validate-key  → Verifica que la API Key sea válida
 * - POST /api/external/check-user    → Verifica si un email existe
 * - POST /api/external/login         → Login automático (trusted)
 * - POST /api/external/register      → Registro con contraseña
 */
class ExternalAuthController extends Controller
{
    public function __construct(
        private readonly ExternalAuthService $authService,
    ) {}

    // ========================================================================
    // VALIDATE KEY
    // ========================================================================

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
