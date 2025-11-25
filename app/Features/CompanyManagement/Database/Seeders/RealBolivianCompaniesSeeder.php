<?php

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Real Bolivian Companies Seeder
 *
 * Crea 5 empresas bolivianas reales con:
 * - 1 Company Admin por empresa
 * - 2 Agentes por empresa
 * - Todos con contraseña: mklmklmkl
 * - industry_id asignado correctamente
 *
 * Empresas:
 * 1. PIL Andina - Productos Lácteos
 * 2. Banco Fassil - Servicios Financieros
 * 3. YPFB - Petróleo y Gas
 * 4. Tigo - Telecomunicaciones
 * 5. Cervecería Boliviana Nacional - Bebidas
 *
 * Nota: Help Center articles ahora se crean en seeders separados
 * dentro del feature ContentManagement (PilAndinaHelpCenterArticlesSeeder, etc.)
 */
class RealBolivianCompaniesSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';

    private const COMPANIES = [
        [
            'name' => 'PIL Andina S.A.',
            'legal_name' => 'PIL Andina S.A. - Productora Integral Láctea',
            'support_email' => 'soporte@pilandina.com.bo',
            'phone' => '+59144260164',
            'city' => 'Cochabamba',
            'address' => 'Colcapirhua Avenida Blanco Galindo Km. 10.5',
            'state' => 'Cochabamba',
            'postal_code' => '00000',
            'tax_id' => '151099010',
            'legal_rep' => 'Javier Rodríguez García',
            'website' => 'https://pilandina.com.bo',
            'industry_code' => 'manufacturing',
            'company_admin' => [
                'first_name' => 'Javier',
                'last_name' => 'Rodríguez',
                'email' => 'javier.rodriguez@pilandina.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'María',
                    'last_name' => 'Condori',
                    'email' => 'maria.condori@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Roberto',
                    'last_name' => 'Flores',
                    'email' => 'roberto.flores@pilandina.com.bo',
                ],
            ],
        ],
        [
            'name' => 'Banco Fassil S.A.',
            'legal_name' => 'Banco Fassil S.A. - Servicios Financieros',
            'support_email' => 'soporte@fassil.com.bo',
            'phone' => '+59133158000',
            'city' => 'Santa Cruz',
            'address' => 'Libertad 765, Centro',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '151236547',
            'legal_rep' => 'Fernando Mendoza López',
            'website' => 'https://www.fassil.com.bo',
            'industry_code' => 'finance',
            'company_admin' => [
                'first_name' => 'Fernando',
                'last_name' => 'Mendoza',
                'email' => 'fernando.mendoza@fassil.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Laura',
                    'last_name' => 'Gutierrez',
                    'email' => 'laura.gutierrez@fassil.com.bo',
                ],
                [
                    'first_name' => 'Carlos',
                    'last_name' => 'Morales',
                    'email' => 'carlos.morales@fassil.com.bo',
                ],
            ],
        ],
        [
            'name' => 'YPFB Corporación',
            'legal_name' => 'Yacimientos Petrolíferos Fiscales Bolivianos S.A.',
            'support_email' => 'contacto@ypfb.gob.bo',
            'phone' => '+59122106565',
            'city' => 'La Paz',
            'address' => 'Calle Bueno Nº 185, Centro',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '151070001',
            'legal_rep' => 'Luis Alberto Sánchez Fernández',
            'website' => 'https://www.ypfb.gob.bo',
            'industry_code' => 'energy',
            'company_admin' => [
                'first_name' => 'Luis',
                'last_name' => 'Sánchez',
                'email' => 'luis.sanchez@ypfb.gob.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Patricia',
                    'last_name' => 'Alanoca',
                    'email' => 'patricia.alanoca@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Miguel',
                    'last_name' => 'Pacheco',
                    'email' => 'miguel.pacheco@ypfb.gob.bo',
                ],
            ],
        ],
        [
            'name' => 'Tigo Bolivia S.A.',
            'legal_name' => 'Tigo Bolivia S.A. - Telecomunicaciones',
            'support_email' => 'soporte@tigo.com.bo',
            'phone' => '+5913800175000',
            'city' => 'La Paz',
            'address' => 'Av. Ballivián, Edificio Green Tower, Calacoto',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '151158963',
            'legal_rep' => 'Ricardo Martínez Huerta',
            'website' => 'https://www.tigo.com.bo',
            'industry_code' => 'professional_services',
            'company_admin' => [
                'first_name' => 'Ricardo',
                'last_name' => 'Martínez',
                'email' => 'ricardo.martinez@tigo.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Andrea',
                    'last_name' => 'Vargas',
                    'email' => 'andrea.vargas@tigo.com.bo',
                ],
                [
                    'first_name' => 'David',
                    'last_name' => 'Suárez',
                    'email' => 'david.suarez@tigo.com.bo',
                ],
            ],
        ],
        [
            'name' => 'Cervecería Boliviana Nacional S.A.',
            'legal_name' => 'Cervecería Boliviana Nacional S.A. - Bebidas',
            'support_email' => 'soporte@cbn.bo',
            'phone' => '+59122455455',
            'city' => 'La Paz',
            'address' => 'Av. Montes #400, Zona Sopocachi',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '151095874',
            'legal_rep' => 'Alejandro Reyes Montoya',
            'website' => 'https://www.cbn.bo',
            'industry_code' => 'manufacturing',
            'company_admin' => [
                'first_name' => 'Alejandro',
                'last_name' => 'Reyes',
                'email' => 'alejandro.reyes@cbn.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Sofía',
                    'last_name' => 'Castellanos',
                    'email' => 'sofia.castellanos@cbn.bo',
                ],
                [
                    'first_name' => 'Juan',
                    'last_name' => 'Espinoza',
                    'email' => 'juan.espinoza@cbn.bo',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🏢 Creando 5 empresas bolivianas reales con datos profesionales...');

        foreach (self::COMPANIES as $companyData) {
            try {
                // 1. Crear Company Admin
                $admin = $this->createUser(
                    $companyData['company_admin']['first_name'],
                    $companyData['company_admin']['last_name'],
                    $companyData['company_admin']['email'],
                );

                // 2. Obtener industry_id
                $industry = CompanyIndustry::where('code', $companyData['industry_code'])->first();
                if (!$industry) {
                    $this->command->error("❌ Industria no encontrada: {$companyData['industry_code']}");
                    continue;
                }

                // 3. Crear Empresa usando CompanyService (dispara CompanyCreated event → auto-crea categorías)
                $companyCode = CodeGenerator::generate('business.companies', CodeGenerator::COMPANY, 'company_code');

                $companyService = app(CompanyService::class);
                $company = $companyService->create([
                    'company_code' => $companyCode,
                    'name' => $companyData['name'],
                    'legal_name' => $companyData['legal_name'],
                    'support_email' => $companyData['support_email'],
                    'phone' => $companyData['phone'],
                    'website' => $companyData['website'],
                    'contact_address' => $companyData['address'],
                    'contact_city' => $companyData['city'],
                    'contact_state' => $companyData['state'],
                    'contact_country' => 'Bolivia',
                    'contact_postal_code' => $companyData['postal_code'],
                    'tax_id' => $companyData['tax_id'],
                    'legal_representative' => $companyData['legal_rep'],
                    'business_hours' => [
                        'monday' => ['open' => '08:30', 'close' => '18:00'],
                        'tuesday' => ['open' => '08:30', 'close' => '18:00'],
                        'wednesday' => ['open' => '08:30', 'close' => '18:00'],
                        'thursday' => ['open' => '08:30', 'close' => '18:00'],
                        'friday' => ['open' => '08:30', 'close' => '17:00'],
                        'saturday' => ['open' => '09:00', 'close' => '13:00'],
                    ],
                    'timezone' => 'America/La_Paz',
                    'status' => 'active',
                    'industry_id' => $industry->id,
                ], $admin);

                $this->command->info("✅ Empresa '{$company->name}' creada con admin: {$admin->email}");

                // 4. Asignar rol COMPANY_ADMIN
                UserRole::create([
                    'user_id' => $admin->id,
                    'role_code' => 'COMPANY_ADMIN',
                    'company_id' => $company->id,
                    'is_active' => true,
                ]);

                // 5. Crear 2 Agentes
                foreach ($companyData['agents'] as $agentData) {
                    $agent = $this->createUser(
                        $agentData['first_name'],
                        $agentData['last_name'],
                        $agentData['email'],
                    );

                    UserRole::create([
                        'user_id' => $agent->id,
                        'role_code' => 'AGENT',
                        'company_id' => $company->id,
                        'is_active' => true,
                    ]);

                    $this->command->info("  └─ Agente creado: {$agent->email}");
                }


            } catch (\Exception $e) {
                $this->command->error("❌ Error creando empresa: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ Seeder completado con éxito!');
    }

    private function createUser(string $firstName, string $lastName, string $email): User
    {
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
