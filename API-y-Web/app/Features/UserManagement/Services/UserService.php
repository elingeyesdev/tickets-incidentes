<?php

namespace App\Features\UserManagement\Services;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserProfile;
use App\Shared\Enums\UserStatus;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User Service
 *
 * Servicio de gestión de usuarios.
 * Maneja toda la lógica de negocio relacionada con usuarios.
 */
class UserService
{
    /**
     * Crear un nuevo usuario con su perfil
     *
     * @param array $userData Datos del usuario
     * @param array $profileData Datos del perfil
     * @return User Usuario creado
     * @throws ValidationException
     */
    public function createUser(array $userData, array $profileData): User
    {
        // Validar email único
        if (User::where('email', $userData['email'])->exists()) {
            throw ValidationException::withField('email', 'El email ya está registrado');
        }

        return DB::transaction(function () use ($userData, $profileData) {
            // Generar user_code
            $userCode = CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code');

            // Preparar datos de usuario
            $termsAccepted = $userData['terms_accepted'] ?? false;

            // Crear usuario
            $user = User::create([
                'user_code' => $userCode,
                'email' => $userData['email'],
                'password_hash' => Hash::make($userData['password']),
                'email_verified' => $userData['email_verified'] ?? false,
                'onboarding_completed' => $userData['onboarding_completed'] ?? false,  // Nuevo campo de onboarding
                'status' => $userData['status'] ?? UserStatus::ACTIVE,
                'auth_provider' => $userData['auth_provider'] ?? 'local',
                'terms_accepted' => $termsAccepted,
                'terms_accepted_at' => $termsAccepted ? now() : null,
                'terms_version' => $userData['terms_version'] ?? null,
            ]);

            // Crear perfil
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $profileData['first_name'],
                'last_name' => $profileData['last_name'],
                'phone_number' => $profileData['phone_number'] ?? null,
                'avatar_url' => $profileData['avatar_url'] ?? null,
                'theme' => $profileData['theme'] ?? 'light',
                'language' => $profileData['language'] ?? 'es',
                'timezone' => $profileData['timezone'] ?? 'America/La_Paz',
            ]);

            return $user->load('profile');
        });
    }

    /**
     * Obtener usuario por ID
     *
     * @param string $userId
     * @return User
     * @throws NotFoundException
     */
    public function getUserById(string $userId): User
    {
        $user = User::with('profile')->find($userId);

        if (!$user) {
            throw NotFoundException::resource('Usuario', $userId);
        }

        return $user;
    }

    /**
     * Obtener usuario por email
     *
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::with('profile')->where('email', $email)->first();
    }

    /**
     * Obtener usuario por código
     *
     * @param string $userCode
     * @return User|null
     */
    public function getUserByCode(string $userCode): ?User
    {
        return User::with('profile')->where('user_code', $userCode)->first();
    }

    /**
     * Actualizar usuario
     *
     * @param string $userId
     * @param array $data
     * @return User
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function updateUser(string $userId, array $data): User
    {
        $user = $this->getUserById($userId);

        // Validar email único si se está cambiando
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->exists()) {
                throw ValidationException::withField('email', 'El email ya está registrado');
            }
        }

        // Actualizar solo campos permitidos
        $allowedFields = ['email', 'status'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $user->update($updateData);

        return $user->fresh(['profile']);
    }

    /**
     * Cambiar contraseña de usuario
     *
     * @param string $userId
     * @param string $newPassword
     * @param string|null $currentPassword Contraseña actual para validación
     * @return bool
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function changePassword(
        string $userId,
        string $newPassword,
        ?string $currentPassword = null
    ): bool {
        $user = $this->getUserById($userId);

        // Validar contraseña actual si se proporciona
        if ($currentPassword !== null) {
            if (!Hash::check($currentPassword, $user->password_hash)) {
                throw ValidationException::withField('current_password', 'La contraseña actual es incorrecta');
            }
        }

        $user->update([
            'password_hash' => Hash::make($newPassword),
        ]);

        return true;
    }

    /**
     * Suspender usuario
     *
     * @param string $userId
     * @param string|null $reason Motivo de la suspensión
     * @return User
     * @throws NotFoundException
     */
    public function suspendUser(string $userId, ?string $reason = null): User
    {
        $user = $this->getUserById($userId);

        $user->update([
            'status' => UserStatus::SUSPENDED,
        ]);

        // TODO: Registrar reason en tabla de auditoría cuando esté implementada
        // Por ahora, la directiva @audit en el schema se encargará de registrarlo

        return $user;
    }

    /**
     * Reactivar usuario suspendido
     *
     * @param string $userId
     * @return User
     * @throws NotFoundException
     */
    public function activateUser(string $userId): User
    {
        $user = $this->getUserById($userId);

        $user->update([
            'status' => UserStatus::ACTIVE,
        ]);

        return $user;
    }

    /**
     * Eliminar usuario (soft delete)
     *
     * @param string $userId
     * @return bool
     * @throws NotFoundException
     */
    public function deleteUser(string $userId): bool
    {
        $user = $this->getUserById($userId);

        // Marcar como eliminado y hacer soft delete
        $user->update([
            'status' => UserStatus::DELETED,
        ]);

        $user->delete();

        return true;
    }

    /**
     * Verificar email de usuario
     *
     * @param string $userId
     * @return User
     * @throws NotFoundException
     */
    public function verifyEmail(string $userId): User
    {
        $user = $this->getUserById($userId);

        $user->markEmailAsVerified();

        return $user;
    }

    /**
     * Aceptar términos y condiciones
     *
     * @param string $userId
     * @param string $version
     * @return User
     * @throws NotFoundException
     */
    public function acceptTerms(string $userId, string $version): User
    {
        $user = $this->getUserById($userId);

        $user->acceptTerms($version);

        return $user;
    }

    /**
     * Registrar login de usuario
     *
     * @param string $userId
     * @param string $ip
     * @return User
     * @throws NotFoundException
     */
    public function recordLogin(string $userId, string $ip): User
    {
        $user = $this->getUserById($userId);

        $user->recordLogin($ip);

        return $user;
    }

    /**
     * Registrar actividad de usuario
     *
     * @param string $userId
     * @return void
     */
    public function recordActivity(string $userId): void
    {
        $user = User::find($userId);

        if ($user) {
            $user->recordActivity();
        }
    }

    /**
     * Buscar usuarios con filtros y paginación
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function searchUsers(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = User::with('profile');

        // Filtro por búsqueda (email o código)
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por email verificado
        if (isset($filters['email_verified'])) {
            $query->where('email_verified', $filters['email_verified']);
        }

        // Filtro por proveedor de autenticación
        if (isset($filters['auth_provider'])) {
            $query->where('auth_provider', $filters['auth_provider']);
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Obtener estadísticas de usuarios
     *
     * @return array
     */
    public function getUserStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', UserStatus::ACTIVE)->count(),
            'suspended' => User::where('status', UserStatus::SUSPENDED)->count(),
            'deleted' => User::onlyTrashed()->count(),
            'verified' => User::where('email_verified', true)->count(),
            'unverified' => User::where('email_verified', false)->count(),
        ];
    }

    /**
     * Verificar si un usuario puede acceder al sistema
     *
     * @param string $userId
     * @return bool
     * @throws NotFoundException
     */
    public function canUserAccess(string $userId): bool
    {
        $user = $this->getUserById($userId);

        return $user->canAccess();
    }

    /**
     * Crear usuario desde solicitud de empresa
     *
     * Crea un usuario temporal para el admin de una nueva empresa.
     * El usuario recibe un password temporal que debe cambiar en el primer login.
     *
     * @param string $email Email del admin
     * @param string $companyName Nombre de la empresa (para generar nombre temporal)
     * @return array ['user' => User, 'temporary_password' => string]
     */
    public function createFromCompanyRequest(string $email, string $companyName): array
    {
        return DB::transaction(function () use ($email, $companyName) {
            // Generar user_code
            $userCode = CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code');

            // Extraer first_name y last_name del nombre de la empresa
            $nameParts = explode(' ', $companyName);
            $firstName = $nameParts[0] ?? 'Admin';
            $lastName = implode(' ', array_slice($nameParts, 1)) ?: 'User';

            // Generar password temporal (16 caracteres, seguro)
            $temporaryPassword = Str::random(16);

            // Crear usuario con password temporal
            $user = User::create([
                'user_code' => $userCode,
                'email' => $email,
                'password_hash' => Hash::make($temporaryPassword),
                'status' => UserStatus::ACTIVE,
                'email_verified' => false,
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'terms_version' => '1.0',
                'auth_provider' => 'local',
                'has_temporary_password' => true,
                'temporary_password_expires_at' => now()->addDays(7), // Expira en 7 días
            ]);

            // Crear perfil básico
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            return [
                'user' => $user->fresh(),
                'temporary_password' => $temporaryPassword,
            ];
        });
    }
}