<?php

namespace App\Features\UserManagement\Services;

use App\Features\UserManagement\Models\UserProfile;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;

/**
 * Profile Service
 *
 * Servicio de gestión de perfiles de usuario.
 * Maneja información personal y preferencias.
 */
class ProfileService
{
    /**
     * Obtener perfil por user ID
     *
     * @param string $userId
     * @return UserProfile
     * @throws NotFoundException
     */
    public function getProfileByUserId(string $userId): UserProfile
    {
        $profile = UserProfile::where('user_id', $userId)->first();

        if (!$profile) {
            throw NotFoundException::resource('Perfil de usuario', $userId);
        }

        return $profile;
    }

    /**
     * Actualizar información personal del perfil
     *
     * @param string $userId
     * @param array $data
     * @return UserProfile
     * @throws NotFoundException
     */
    public function updatePersonalInfo(string $userId, array $data): UserProfile
    {
        $profile = $this->getProfileByUserId($userId);

        // Campos permitidos para información personal
        $allowedFields = ['first_name', 'last_name', 'phone_number'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $profile->update($updateData);

        return $profile->fresh();
    }

    /**
     * Actualizar perfil completo (V10.1 - unificado para UpdateMyProfile)
     * Actualiza SOLO datos personales: firstName, lastName, phoneNumber, avatarUrl
     *
     * @param string $userId
     * @param array $data
     * @return UserProfile
     * @throws NotFoundException
     */
    public function updateProfile(string $userId, array $data): UserProfile
    {
        $profile = $this->getProfileByUserId($userId);

        // Campos permitidos (snake_case para BD)
        $allowedFields = ['first_name', 'last_name', 'phone_number', 'avatar_url'];

        // Convertir de camelCase a snake_case si es necesario
        $snakeData = [];
        foreach ($data as $key => $value) {
            $snakeKey = match($key) {
                'firstName' => 'first_name',
                'lastName' => 'last_name',
                'phoneNumber' => 'phone_number',
                'avatarUrl' => 'avatar_url',
                default => $key
            };
            $snakeData[$snakeKey] = $value;
        }

        $updateData = array_intersect_key($snakeData, array_flip($allowedFields));

        $profile->update($updateData);

        return $profile->fresh();
    }

    /**
     * Actualizar preferencias completas (V10.1 - unificado para UpdateMyPreferences)
     * Actualiza preferencias de UI y notificaciones
     *
     * @param string $userId
     * @param array $data
     * @return UserProfile
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function updatePreferences(string $userId, array $data): UserProfile
    {
        $profile = $this->getProfileByUserId($userId);

        // Validar tema
        if (isset($data['theme']) && !in_array($data['theme'], ['light', 'dark'])) {
            throw ValidationException::withField('theme', 'El tema debe ser "light" o "dark"');
        }

        // Validar idioma
        if (isset($data['language']) && !in_array($data['language'], ['es', 'en'])) {
            throw ValidationException::withField('language', 'El idioma debe ser "es" o "en"');
        }

        // Campos permitidos (snake_case para BD)
        $allowedFields = ['theme', 'language', 'timezone', 'push_web_notifications', 'notifications_tickets'];

        // Convertir de camelCase a snake_case si es necesario
        $snakeData = [];
        foreach ($data as $key => $value) {
            $snakeKey = match($key) {
                'pushWebNotifications' => 'push_web_notifications',
                'notificationsTickets' => 'notifications_tickets',
                default => $key
            };
            $snakeData[$snakeKey] = $value;
        }

        $updateData = array_intersect_key($snakeData, array_flip($allowedFields));

        $profile->update($updateData);

        return $profile->fresh();
    }

    /**
     * Actualizar avatar del usuario
     *
     * @param string $userId
     * @param string $avatarUrl
     * @return UserProfile
     * @throws NotFoundException
     */
    public function updateAvatar(string $userId, string $avatarUrl): UserProfile
    {
        $profile = $this->getProfileByUserId($userId);

        $profile->updateAvatar($avatarUrl);

        return $profile->fresh();
    }

    /**
     * Eliminar avatar del usuario
     *
     * @param string $userId
     * @return UserProfile
     * @throws NotFoundException
     */
    public function removeAvatar(string $userId): UserProfile
    {
        $profile = $this->getProfileByUserId($userId);

        $profile->update(['avatar_url' => null]);

        return $profile->fresh();
    }

    /**
     * Actualizar preferencias de interfaz (tema, idioma, zona horaria)
     *
     * @param string $userId
     * @param array $preferences
     * @return UserProfile
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function updateUIPreferences(string $userId, array $preferences): UserProfile
    {
        $profile = $this->getProfileByUserId($userId);

        // Validar tema
        if (isset($preferences['theme']) && !in_array($preferences['theme'], ['light', 'dark'])) {
            throw ValidationException::withField('theme', 'El tema debe ser "light" o "dark"');
        }

        // Validar idioma
        if (isset($preferences['language']) && !in_array($preferences['language'], ['es', 'en'])) {
            throw ValidationException::withField('language', 'El idioma debe ser "es" o "en"');
        }

        $profile->updateUIPreferences(
            $preferences['theme'] ?? $profile->theme,
            $preferences['language'] ?? $profile->language,
            $preferences['timezone'] ?? $profile->timezone
        );

        return $profile->fresh();
    }

    /**
     * Actualizar preferencias de notificaciones
     *
     * @param string $userId
     * @param array $preferences
     * @return UserProfile
     * @throws NotFoundException
     */
    public function updateNotificationPreferences(string $userId, array $preferences): UserProfile
    {
        $profile = $this->getProfileByUserId($userId);

        $profile->updateNotificationPreferences(
            $preferences['push_web_notifications'] ?? $profile->push_web_notifications,
            $preferences['notifications_tickets'] ?? $profile->notifications_tickets
        );

        return $profile->fresh();
    }

    /**
     * Registrar actividad en el perfil
     *
     * @param string $userId
     * @return void
     */
    public function recordActivity(string $userId): void
    {
        $profile = UserProfile::where('user_id', $userId)->first();

        if ($profile) {
            $profile->recordActivity();
        }
    }

    /**
     * Buscar perfiles por nombre
     *
     * @param string $search
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchByName(string $search, int $limit = 10)
    {
        return UserProfile::with('user')
            ->searchByName($search)
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener perfiles por idioma
     *
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProfilesByLanguage(string $language)
    {
        return UserProfile::with('user')
            ->byLanguage($language)
            ->get();
    }

    /**
     * Obtener perfiles con notificaciones habilitadas
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProfilesWithNotifications()
    {
        return UserProfile::with('user')
            ->withNotifications()
            ->get();
    }
}