<?php

namespace App\Features\UserManagement\Database\Seeders;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\Role;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo Users Seeder
 *
 * Crea usuarios de demostración para desarrollo y testing.
 * NO ejecutar en producción.
 *
 * Usuarios creados:
 * - admin@helpdesk.com (PLATFORM_ADMIN)
 * - agent@empresa.com (AGENT + USER)
 * - user@example.com (USER)
 */
class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar que estamos en ambiente local/desarrollo
        if (app()->environment('production')) {
            $this->command->warn('⚠️  Demo users seeder no se ejecuta en producción');
            return;
        }

        $this->createPlatformAdmin();
        $this->createAgent();
        $this->createRegularUser();
        $this->createRandomUsers(10);

        $this->command->info('✅ Usuarios de demostración creados exitosamente');
        $this->command->newLine();
        $this->command->table(
            ['Email', 'Password', 'Roles'],
            [
                ['admin@helpdesk.com', 'password', 'PLATFORM_ADMIN'],
                ['agent@empresa.com', 'password', 'AGENT + USER'],
                ['user@example.com', 'password', 'USER'],
                ['+ 10 usuarios random', 'password', 'USER'],
            ]
        );
    }

    /**
     * Crear administrador de plataforma
     */
    private function createPlatformAdmin(): void
    {
        $user = User::create([
            'user_code' => 'USR-2025-00001',
            'email' => 'admin@helpdesk.com',
            'password_hash' => Hash::make('password'),
            'email_verified' => true,
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => 'v2.1',
        ]);

        $user->profile()->create([
            'first_name' => 'Admin',
            'last_name' => 'Platform',
            'phone_number' => '+591 70000001',
            'theme' => 'dark',
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_code' => 'platform_admin',
            'company_id' => null,
            'is_active' => true,
        ]);

        $this->command->info("✓ Platform Admin: admin@helpdesk.com");
    }

    /**
     * Crear agente de soporte
     */
    private function createAgent(): void
    {
        $user = User::create([
            'user_code' => 'USR-2025-00002',
            'email' => 'agent@empresa.com',
            'password_hash' => Hash::make('password'),
            'email_verified' => true,
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => 'v2.1',
        ]);

        $user->profile()->create([
            'first_name' => 'María',
            'last_name' => 'García',
            'phone_number' => '+591 70000002',
            'theme' => 'light',
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);

        // Asignar rol USER (global)
        UserRole::create([
            'user_id' => $user->id,
            'role_code' => 'user',
            'company_id' => null,
            'is_active' => true,
        ]);

        // Asignar rol AGENT (requiere empresa, usaremos null por ahora)
        // TODO: Cuando tengamos CompanyManagement, asignar a empresa real
        UserRole::create([
            'user_id' => $user->id,
            'role_code' => 'agent',
            'company_id' => null, // TODO: Asignar empresa cuando exista CompanyManagement
            'is_active' => true,
        ]);

        $this->command->info("✓ Agent: agent@empresa.com");
    }

    /**
     * Crear usuario regular
     */
    private function createRegularUser(): void
    {
        $user = User::create([
            'user_code' => 'USR-2025-00003',
            'email' => 'user@example.com',
            'password_hash' => Hash::make('password'),
            'email_verified' => true,
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => 'v2.1',
        ]);

        $user->profile()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'phone_number' => '+591 70000003',
            'theme' => 'light',
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_code' => 'user',
            'company_id' => null,
            'is_active' => true,
        ]);

        $this->command->info("✓ Regular User: user@example.com");
    }

    /**
     * Crear usuarios aleatorios usando factories
     */
    private function createRandomUsers(int $count): void
    {
        User::factory()
            ->count($count)
            ->withProfile()
            ->create()
            ->each(function (User $user) {
                UserRole::create([
                    'user_id' => $user->id,
                    'role_code' => 'user',
                    'company_id' => null,
                    'is_active' => true,
                ]);
            });

        $this->command->info("✓ {$count} random users created");
    }
}