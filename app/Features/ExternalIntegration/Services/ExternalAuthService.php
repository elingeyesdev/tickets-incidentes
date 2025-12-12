<?php

declare(strict_types=1);

namespace App\Features\ExternalIntegration\Services;

use App\Features\Authentication\Services\TokenService;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\Role;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Service para autenticación externa (Widget).
 * 
 * Maneja la lógica de:
 * - Verificar si un usuario existe
 * - Crear usuarios desde proyectos externos
 * - Generar tokens JWT para el widget
 */
class ExternalAuthService
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    // ========================================================================
    // VERIFICACIÓN DE USUARIO
    // ========================================================================

    /**
     * Verifica si un email existe en Helpdesk.
     * 
     * @param string $email
     * @return array{exists: bool, user: array|null}
     */
    public function checkUserExists(string $email): array
    {
        $email = strtolower(trim($email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'exists' => false,
                'user' => null,
            ];
        }

        return [
            'exists' => true,
            'user' => [
                'id' => $user->id,
                'displayName' => $user->display_name,
                'email' => $user->email,
            ],
        ];
    }

    // ========================================================================
    // LOGIN AUTOMÁTICO (TRUSTED)
    // ========================================================================

    /**
     * Login automático para usuarios que ya existen.
     * 
     * Este login es "trusted" porque:
     * 1. Viene de un proyecto con API Key válida
     * 2. El usuario ya fue autenticado en el proyecto externo
     * 
     * @param string $email
     * @param Company $company
     * @return array{success: bool, accessToken?: string, expiresIn?: int, error?: string}
     */
    public function loginTrusted(string $email, Company $company): array
    {
        $email = strtolower(trim($email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'USER_NOT_FOUND',
            ];
        }

        // Verificar que el usuario está activo
        if ($user->status !== 'ACTIVE') {
            return [
                'success' => false,
                'error' => 'USER_INACTIVE',
            ];
        }

        // Asegurar que tenga rol USER en la empresa
        $this->ensureUserRoleInCompany($user, $company);

        // Generar JWT con rol USER en la empresa
        $token = $this->generateWidgetToken($user, $company);

        return [
            'success' => true,
            'accessToken' => $token,
            'expiresIn' => config('jwt.ttl', 60) * 60, // En segundos
        ];
    }

    // ========================================================================
    // REGISTRO DE USUARIO
    // ========================================================================

    /**
     * Registra un nuevo usuario desde el widget.
     * 
     * @param array $data {email, firstName, lastName, password}
     * @param Company $company
     * @return array{success: bool, accessToken?: string, expiresIn?: int, errors?: array}
     */
    public function registerUser(array $data, Company $company): array
    {
        $email = strtolower(trim($data['email']));

        // Verificar que no exista
        if (User::where('email', $email)->exists()) {
            return [
                'success' => false,
                'errors' => ['email' => ['Este email ya está registrado.']],
            ];
        }

        try {
            $user = DB::transaction(function () use ($data, $email, $company) {
                // Crear usuario
                $user = User::create([
                    'email' => $email,
                    'password' => Hash::make($data['password']),
                    'status' => 'ACTIVE',
                    'email_verified_at' => now(), // Auto-verificado para widget
                ]);

                // Crear perfil
                $user->profile()->create([
                    'first_name' => $this->sanitizeName($data['firstName']),
                    'last_name' => $this->sanitizeName($data['lastName'] ?? ''),
                ]);

                // Asignar rol USER en la empresa
                $this->ensureUserRoleInCompany($user, $company);

                return $user;
            });

            // Generar JWT
            $token = $this->generateWidgetToken($user, $company);

            return [
                'success' => true,
                'accessToken' => $token,
                'expiresIn' => config('jwt.ttl', 60) * 60,
            ];

        } catch (\Exception $e) {
            \Log::error('[ExternalAuthService] Error registrando usuario', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'errors' => ['general' => ['Error al crear la cuenta. Intenta de nuevo.']],
            ];
        }
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Asegura que el usuario tenga rol USER en la empresa especificada.
     */
    private function ensureUserRoleInCompany(User $user, Company $company): void
    {
        $userRole = Role::findByCode('USER');

        if (!$userRole) {
            \Log::error('[ExternalAuthService] Rol USER no encontrado');
            return;
        }

        // Verificar si ya tiene el rol en esta empresa
        $exists = $user->roles()
            ->wherePivot('role_id', $userRole->id)
            ->wherePivot('company_id', $company->id)
            ->exists();

        if (!$exists) {
            // Asignar rol USER en la empresa
            $user->roles()->attach($userRole->id, [
                'id' => Str::uuid(),
                'company_id' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('[ExternalAuthService] Rol USER asignado', [
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);
        }
    }

    /**
     * Genera un JWT para el widget con el rol USER activo.
     */
    private function generateWidgetToken(User $user, Company $company): string
    {
        // Obtener el user_role específico de USER en esta empresa
        $userRole = Role::findByCode('USER');
        
        $userRoleRecord = $user->roles()
            ->wherePivot('role_id', $userRole->id)
            ->wherePivot('company_id', $company->id)
            ->first();

        if (!$userRoleRecord) {
            throw new \Exception('No se encontró el rol USER para generar token');
        }

        // Generar token con active_role
        return $this->tokenService->generateAccessToken(
            user: $user,
            activeRoleId: $userRoleRecord->pivot->id,
        );
    }

    /**
     * Sanitiza y capitaliza un nombre.
     */
    private function sanitizeName(string $name): string
    {
        // Quitar HTML y espacios extra
        $name = strip_tags(trim($name));
        
        // Capitalizar primera letra de cada palabra
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    // ========================================================================
    // TOKEN REFRESH
    // ========================================================================

    /**
     * Valida un token para refresh.
     * Permite tokens expirados hasta 5 minutos.
     * 
     * @param string $token
     * @return array|null Payload del token o null si inválido
     */
    public function validateTokenForRefresh(string $token): ?array
    {
        try {
            // Intentar decodificar sin validar expiración
            $payload = $this->tokenService->decodeTokenWithoutValidation($token);
            
            if (!$payload || !isset($payload['sub'])) {
                return null;
            }

            // Verificar que no esté expirado por más de 5 minutos (grace period)
            $exp = $payload['exp'] ?? 0;
            $gracePeriod = 5 * 60; // 5 minutos
            
            if (time() > ($exp + $gracePeriod)) {
                // Token expirado hace más de 5 minutos
                \Log::info('[ExternalAuthService] Token expirado hace más de 5 min', [
                    'exp' => $exp,
                    'now' => time(),
                    'diff' => time() - $exp,
                ]);
                return null;
            }

            return $payload;

        } catch (\Exception $e) {
            \Log::warning('[ExternalAuthService] Error validando token para refresh', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
