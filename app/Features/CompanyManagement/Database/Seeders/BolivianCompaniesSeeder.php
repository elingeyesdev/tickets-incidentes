<?php

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Bolivian Companies Seeder
 *
 * Crea 5 empresas bolivianas con:
 * - 1 Company Admin por empresa
 * - 2 Agentes por empresa
 * - Todos con contraseña: mklmklmkl
 *
 * Empresas:
 * 1. Soluciones TI Bolivia
 * 2. Electrónica Andina
 * 3. Transportes Conti
 * 4. Consultores Profesionales
 * 5. Servicios Integrales Bolivia
 */
class BolivianCompaniesSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';

    private const COMPANIES = [
        [
            'name' => 'Soluciones TI Bolivia',
            'legal_name' => 'Soluciones TI Bolivia SRL',
            'support_email' => 'soporte@solucionesti.bo',
            'phone' => '+59122123456',
            'city' => 'La Paz',
            'address' => 'Avenida 9 de Julio 1234',
            'postal_code' => '80001',
            'tax_id' => '1234567890',
            'legal_rep' => 'Carlos Rodríguez',
            'company_admin' => [
                'first_name' => 'Carlos',
                'last_name' => 'Rodríguez',
                'email' => 'carlos.rodriguez@solucionesti.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Juan',
                    'last_name' => 'Mamani',
                    'email' => 'juan.mamani@solucionesti.bo',
                ],
                [
                    'first_name' => 'Patricia',
                    'last_name' => 'Quispe',
                    'email' => 'patricia.quispe@solucionesti.bo',
                ],
            ],
        ],
        [
            'name' => 'Electrónica Andina',
            'legal_name' => 'Electrónica Andina SA',
            'support_email' => 'contacto@electroandina.bo',
            'phone' => '+59123456789',
            'city' => 'Cochabamba',
            'address' => 'Calle Presidente Urquidi 890',
            'postal_code' => '80010',
            'tax_id' => '0987654321',
            'legal_rep' => 'Miguel Flores',
            'company_admin' => [
                'first_name' => 'Miguel',
                'last_name' => 'Flores',
                'email' => 'miguel.flores@electroandina.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Roberto',
                    'last_name' => 'Ayllón',
                    'email' => 'roberto.ayllon@electroandina.bo',
                ],
                [
                    'first_name' => 'Verónica',
                    'last_name' => 'Coyla',
                    'email' => 'veronica.coyla@electroandina.bo',
                ],
            ],
        ],
        [
            'name' => 'Transportes Conti',
            'legal_name' => 'Transportes Conti Ltda',
            'support_email' => 'admin@transportesconti.bo',
            'phone' => '+59164789123',
            'city' => 'Santa Cruz',
            'address' => 'Tercer Anillo Solar Número 456',
            'postal_code' => '80020',
            'tax_id' => '5555666666',
            'legal_rep' => 'Javier Gutierrez',
            'company_admin' => [
                'first_name' => 'Javier',
                'last_name' => 'Gutierrez',
                'email' => 'javier.gutierrez@transportesconti.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Marco',
                    'last_name' => 'Suárez',
                    'email' => 'marco.suarez@transportesconti.bo',
                ],
                [
                    'first_name' => 'Magdalena',
                    'last_name' => 'Vargas',
                    'email' => 'magdalena.vargas@transportesconti.bo',
                ],
            ],
        ],
        [
            'name' => 'Consultores Profesionales',
            'legal_name' => 'Consultores Profesionales SRL',
            'support_email' => 'info@consultoresbo.bo',
            'phone' => '+59122987654',
            'city' => 'Oruro',
            'address' => 'Avenida Cívica 234',
            'postal_code' => '80030',
            'tax_id' => '1111222222',
            'legal_rep' => 'Fernando Pérez',
            'company_admin' => [
                'first_name' => 'Fernando',
                'last_name' => 'Pérez',
                'email' => 'fernando.perez@consultoresbo.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Diego',
                    'last_name' => 'Araya',
                    'email' => 'diego.araya@consultoresbo.bo',
                ],
                [
                    'first_name' => 'Alejandra',
                    'last_name' => 'Ponce',
                    'email' => 'alejandra.ponce@consultoresbo.bo',
                ],
            ],
        ],
        [
            'name' => 'Servicios Integrales Bolivia',
            'legal_name' => 'Servicios Integrales Bolivia SA',
            'support_email' => 'servicio@integralusbo.bo',
            'phone' => '+59176543210',
            'city' => 'Potosí',
            'address' => 'Calle del Comercio 567',
            'postal_code' => '80040',
            'tax_id' => '3333444444',
            'legal_rep' => 'Eduardo Montoya',
            'company_admin' => [
                'first_name' => 'Eduardo',
                'last_name' => 'Montoya',
                'email' => 'eduardo.montoya@integralusbo.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Francisco',
                    'last_name' => 'Valdez',
                    'email' => 'francisco.valdez@integralusbo.bo',
                ],
                [
                    'first_name' => 'Roxana',
                    'last_name' => 'López',
                    'email' => 'roxana.lopez@integralusbo.bo',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🏢 Creando 5 empresas bolivianas con admins y agentes...');

        foreach (self::COMPANIES as $companyData) {
            try {
                // Crear Company Admin
                $adminEmail = $companyData['company_admin']['email'];
                if (User::where('email', $adminEmail)->exists()) {
                    $this->command->warn("⚠ Admin ya existe: {$adminEmail}");
                    continue;
                }

                $admin = $this->createUser(
                    $companyData['company_admin']['first_name'],
                    $companyData['company_admin']['last_name'],
                    $adminEmail,
                );

                // Crear Empresa (usar CodeGenerator para company_code)
                $companyCode = CodeGenerator::generate('business.companies', CodeGenerator::COMPANY, 'company_code');

                $company = Company::create([
                    'company_code' => $companyCode,
                    'name' => $companyData['name'],
                    'legal_name' => $companyData['legal_name'],
                    'support_email' => $companyData['support_email'],
                    'phone' => $companyData['phone'],
                    'website' => 'https://www.' . strtolower(str_replace(' ', '', $companyData['name'])) . '.bo',
                    'contact_address' => $companyData['address'],
                    'contact_city' => $companyData['city'],
                    'contact_state' => 'Bolivia',
                    'contact_country' => 'Bolivia',
                    'contact_postal_code' => $companyData['postal_code'],
                    'tax_id' => $companyData['tax_id'],
                    'legal_representative' => $companyData['legal_rep'],
                    'business_hours' => [
                        'monday' => ['open' => '09:00', 'close' => '18:00'],
                        'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                        'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                        'thursday' => ['open' => '09:00', 'close' => '18:00'],
                        'friday' => ['open' => '09:00', 'close' => '17:00'],
                    ],
                    'timezone' => 'America/La_Paz',
                    'status' => 'active',
                    'admin_user_id' => $admin->id,
                ]);

                // Asignar rol COMPANY_ADMIN al admin
                UserRole::create([
                    'user_id' => $admin->id,
                    'role_code' => 'COMPANY_ADMIN',
                    'company_id' => $company->id,
                    'is_active' => true,
                ]);

                $this->command->info("✅ Empresa '{$company->name}' creada con admin: {$adminEmail}");

                // Crear 2 Agentes
                foreach ($companyData['agents'] as $agentData) {
                    $agentEmail = $agentData['email'];

                    if (User::where('email', $agentEmail)->exists()) {
                        $this->command->warn("⚠ Agente ya existe: {$agentEmail}");
                        continue;
                    }

                    $agent = $this->createUser(
                        $agentData['first_name'],
                        $agentData['last_name'],
                        $agentEmail,
                    );

                    // Asignar rol AGENT
                    UserRole::create([
                        'user_id' => $agent->id,
                        'role_code' => 'AGENT',
                        'company_id' => $company->id,
                        'is_active' => true,
                    ]);

                    $this->command->info("  └─ Agente creado: {$agentEmail}");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Error creando empresa: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ Seeder completado!');
    }

    private function createUser(string $firstName, string $lastName, string $email): User
    {
        // Usar CodeGenerator para generar el user_code
        $userCode = CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code');

        $user = User::create([
            'user_code' => $userCode,
            'email' => $email,
            'password_hash' => Hash::make(self::PASSWORD),
            'email_verified' => true,
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => 'v2.1',
            'onboarding_completed_at' => now(),
        ]);

        $user->profile()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => null,
            'theme' => 'light',
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);

        return $user;
    }
}
