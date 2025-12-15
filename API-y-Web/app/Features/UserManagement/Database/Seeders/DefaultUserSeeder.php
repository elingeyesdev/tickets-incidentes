<?php

namespace App\Features\UserManagement\Database\Seeders;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Default User Seeder
 *
 * Crea un usuario PLATFORM_ADMIN por defecto.
 * Se ejecuta automáticamente en el entrypoint del contenedor.
 *
 * Datos del usuario:
 * - Email: lukqs05@gmail.com
 * - Password: mklmklmkl
 * - Rol: PLATFORM_ADMIN
 * - Status: ACTIVE
 * - Onboarding: COMPLETED
 */
class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'lukqs05@gmail.com';

        // Verificar si el usuario ya existe
        if (User::where('email', $email)->exists()) {
            $this->command->info("✓ Usuario default ya existe: {$email}");
            return;
        }

        try {
            $user = User::create([
                'user_code' => 'USR-DEFAULT-ADMIN',
                'email' => $email,
                'password_hash' => Hash::make('mklmklmkl'),
                'email_verified' => true,
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE,
                'auth_provider' => 'local',
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'terms_version' => 'v2.1',
                'onboarding_completed_at' => now(), // Onboarding completado
            ]);

            // Crear perfil del usuario
            $user->profile()->create([
                'first_name' => 'luke',
                'last_name' => 'de la quintana',
                'phone_number' => null,
                'theme' => 'light',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
            ]);

            // Asignar rol PLATFORM_ADMIN
            UserRole::create([
                'user_id' => $user->id,
                'role_code' => 'PLATFORM_ADMIN',
                'company_id' => null,
                'is_active' => true,
            ]);

            $this->command->info("✅ Usuario default creado: {$email} (PLATFORM_ADMIN)");
        } catch (\Exception $e) {
            $this->command->error("❌ Error al crear usuario default: {$e->getMessage()}");
        }

        // Crear usuario PLATFORM_ADMIN + USER
        $email2 = 'admin-user@gmail.com';

        if (User::where('email', $email2)->exists()) {
            $this->command->info("✓ Usuario admin-user ya existe: {$email2}");
            return;
        }

        try {
            $user2 = User::create([
                'user_code' => 'USR-ADMIN-USER',
                'email' => $email2,
                'password_hash' => Hash::make('mklmklmkl'),
                'email_verified' => true,
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE,
                'auth_provider' => 'local',
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'terms_version' => 'v2.1',
                'onboarding_completed_at' => now(),
            ]);

            // Crear perfil del usuario
            $user2->profile()->create([
                'first_name' => 'admin',
                'last_name' => 'user',
                'phone_number' => null,
                'theme' => 'light',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
            ]);

            // Asignar rol PLATFORM_ADMIN
            UserRole::create([
                'user_id' => $user2->id,
                'role_code' => 'PLATFORM_ADMIN',
                'company_id' => null,
                'is_active' => true,
            ]);

            // Asignar rol USER
            UserRole::create([
                'user_id' => $user2->id,
                'role_code' => 'USER',
                'company_id' => null,
                'is_active' => true,
            ]);

            $this->command->info("✅ Usuario creado: {$email2} (PLATFORM_ADMIN + USER)");
        } catch (\Exception $e) {
            $this->command->error("❌ Error al crear usuario admin-user: {$e->getMessage()}");
        }
    }
}
