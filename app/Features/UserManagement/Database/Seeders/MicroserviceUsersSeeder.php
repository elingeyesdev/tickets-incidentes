<?php

namespace App\Features\UserManagement\Database\Seeders;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Microservices Service Account Seeder
 *
 * Crea usuarios de servicio para los microservicios externos.
 * Estos usuarios tienen rol PLATFORM_ADMIN para acceso total de lectura.
 *
 * IMPORTANTE: Estos usuarios son para autenticación service-to-service.
 * Los microservicios usan el JWT de estos usuarios para consultar la API.
 *
 * Usuarios creados:
 * - ratings-microservice@system.internal (Microservicio de Ratings & Honores)
 * - macros-microservice@system.internal (Microservicio de Macros & Notas)
 */
class MicroserviceUsersSeeder extends Seeder
{
    /**
     * Microservicios a crear
     */
    private array $microservices = [
        [
            'email' => 'ratings-microservice@system.internal',
            'user_code' => 'SVC-RATINGS-MS',
            'first_name' => 'Ratings',
            'last_name' => 'Microservice',
            'description' => 'Microservicio de Ratings & Honores (Compañero 1)',
            'password' => 'RatingsMS2024!Secure',
        ],
        [
            'email' => 'macros-microservice@system.internal',
            'user_code' => 'SVC-MACROS-MS',
            'first_name' => 'Macros',
            'last_name' => 'Microservice',
            'description' => 'Microservicio de Macros & Notas Internas (Compañero 2)',
            'password' => 'MacrosMS2024!Secure',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🔧 Creando usuarios de servicio para microservicios...');
        $this->command->info('');

        foreach ($this->microservices as $ms) {
            $this->createMicroserviceUser($ms);
        }

        $this->command->info('');
        $this->command->info('📋 Los microservicios deben usar estos usuarios para autenticarse via JWT.');
        $this->command->info('   Pueden hacer login con email/password para obtener tokens.');
        $this->command->info('');
    }

    /**
     * Crear un usuario de microservicio
     */
    private function createMicroserviceUser(array $config): void
    {
        $email = $config['email'];

        // Verificar si el usuario ya existe
        if (User::where('email', $email)->exists()) {
            $this->command->info("  ✓ Usuario ya existe: {$email}");
            return;
        }

        try {
            // Usar contraseña fija del config
            $password = $config['password'];

            $user = User::create([
                'user_code' => $config['user_code'],
                'email' => $email,
                'password_hash' => Hash::make($password),
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
            $user->profile()->create([
                'first_name' => $config['first_name'],
                'last_name' => $config['last_name'],
                'phone_number' => null,
                'theme' => 'dark',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
            ]);

            // Asignar rol PLATFORM_ADMIN (acceso total de lectura)
            UserRole::create([
                'user_id' => $user->id,
                'role_code' => 'PLATFORM_ADMIN',
                'company_id' => null,
                'is_active' => true,
            ]);

            $this->command->info("  ✅ Creado: {$email}");
            $this->command->info("     Password: {$password}");
            $this->command->info("     Rol: PLATFORM_ADMIN");
            $this->command->info("     {$config['description']}");
            $this->command->info('');

        } catch (\Exception $e) {
            $this->command->error("  ❌ Error al crear {$email}: {$e->getMessage()}");
        }
    }
}
