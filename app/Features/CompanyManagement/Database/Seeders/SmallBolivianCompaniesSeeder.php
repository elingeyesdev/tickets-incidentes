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
use Illuminate\Support\Facades\Storage;
/**
 * Small Bolivian Companies Seeder
 *
 * Crea 5 empresas bolivianas PEQUEÑAS con:
 * - company_code FIJO (formato CMP-2025-0001X) → determinístico
 * - 1 Company Admin por empresa
 * - 2 Agentes por empresa
 * - SIN Áreas/Departamentos (estructura plana)
 * - areas_enabled = false
 * - Logos copiados automáticamente de resources → storage
 * - Todos los usuarios con contraseña: mklmklmkl
 *
 * Empresas PEQUEÑAS:
 * 1. Victoria Veterinaria (CMP-2025-00011) - Salud/Veterinaria
 * 2. Iris Computer (CMP-2025-00012) - Tecnología/Retail
 * 3. Tienda de ropa BLEETZER (CMP-2025-00013) - Retail/Moda
 * 4. ISI Vapes (CMP-2025-00014) - Retail/Vapes
 * 5. 3B Markets (CMP-2025-00015) - Retail/Supermercado
 */
class SmallBolivianCompaniesSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';

    private const COMPANIES = [
        [
            'company_code' => 'CMP-2025-00011',
            'name' => 'Victoria Veterinaria',
            'legal_name' => 'Veterinaria Victoria S.R.L.',
            'description' => 'Clínica veterinaria y tienda de mascotas. Cuidado integral para tus mascotas.',
            'support_email' => 'contacto@victoriavet.bo',
            'phone' => '+59139221234',
            'city' => 'Santa Cruz',
            'address' => 'Av. Principal #123, Zona Mercado',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '1020304050',
            'legal_rep' => 'Juan Pérez',
            'website' => 'https://victoriavet.bo',
            'industry_code' => 'veterinary',
            'primary_color' => '#00A859',
            'secondary_color' => '#FFFFFF',
            'logo_filename' => 'victoria-veterinaria-logo.png',
            'company_admin' => [
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'juan.perez@victoriavet.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Ana',
                    'last_name' => 'López',
                    'email' => 'ana.lopez@victoriavet.bo',
                ],
                [
                    'first_name' => 'Carlos',
                    'last_name' => 'Gómez',
                    'email' => 'carlos.gomez@victoriavet.bo',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00012',
            'name' => 'Iris Computer',
            'legal_name' => 'Iris Computer Bolivia S.R.L.',
            'description' => 'Tecnología con alma gamer. Venta de computadoras, componentes y accesorios de alta gama.',
            'support_email' => 'iriscomputerjlp@gmail.com',
            'phone' => '+59175680145',
            'city' => 'Santa Cruz',
            'address' => 'Calle Pedro Suárez Arana Nro 3180',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '1020304051',
            'legal_rep' => 'Eduardo Mamani',
            'website' => 'https://iriscomputer.com.bo',
            'industry_code' => 'electronics',
            'primary_color' => '#6A0DAD',
            'secondary_color' => '#000000',
            'logo_filename' => 'iris-computer.png',
            'company_admin' => [
                'first_name' => 'Eduardo',
                'last_name' => 'Mamani',
                'email' => 'eduardo.mamani@iriscomputer.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Eugenio',
                    'last_name' => 'Vargas',
                    'email' => 'eugenio.vargas@iriscomputer.bo',
                ],
                [
                    'first_name' => 'Miguel',
                    'last_name' => 'Torres',
                    'email' => 'miguel.torres@iriscomputer.bo',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00013',
            'name' => 'BLEETZER',
            'legal_name' => 'Comercial Bleetzer S.R.L.',
            'description' => 'Tienda de ropa. Moda y estilo para todos. Ropa casual y formal.',
            'support_email' => 'contacto@bleetzer.bo',
            'phone' => '+59170012345',
            'city' => 'La Paz',
            'address' => 'Shopping Norte, Local 15',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '1020304052',
            'legal_rep' => 'María López',
            'website' => 'https://bleetzer.bo',
            'industry_code' => 'retail',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
            'logo_filename' => 'bleetzer-logo.png',
            'company_admin' => [
                'first_name' => 'María',
                'last_name' => 'López',
                'email' => 'maria.lopez@bleetzer.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Sofía',
                    'last_name' => 'Mendoza',
                    'email' => 'sofia.mendoza@bleetzer.bo',
                ],
                [
                    'first_name' => 'Pedro',
                    'last_name' => 'Aliaga',
                    'email' => 'pedro.aliaga@bleetzer.bo',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00014',
            'name' => 'ISI Vapes',
            'legal_name' => 'ISI Vapes Importaciones',
            'description' => 'Venta de vaporizadores, esencias y accesorios. La mejor calidad en vapes.',
            'support_email' => 'ventas@isivapes.bo',
            'phone' => '+59170054321',
            'city' => 'Santa Cruz',
            'address' => 'Av. San Martín, Equipetrol',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '1020304053',
            'legal_rep' => 'Carlos Ruiz',
            'website' => 'https://isivapes.bo',
            'industry_code' => 'retail',
            'primary_color' => '#00FFFF',
            'secondary_color' => '#000000',
            'logo_filename' => 'ISI-Vapes-logos.png',
            'company_admin' => [
                'first_name' => 'Carlos',
                'last_name' => 'Ruiz',
                'email' => 'carlos.ruiz@isivapes.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Jorge',
                    'last_name' => 'Claros',
                    'email' => 'jorge.claros@isivapes.bo',
                ],
                [
                    'first_name' => 'Luis',
                    'last_name' => 'Fernández',
                    'email' => 'luis.fernandez@isivapes.bo',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00015',
            'name' => '3B Markets',
            'legal_name' => 'Tiendas 3B Bolivia S.A.',
            'description' => 'Bueno, Bonito y Barato. Cadena de supermercados de descuento.',
            'support_email' => 'contacto@tiendas3b.com.bo',
            'phone' => '+59133334444',
            'city' => 'Santa Cruz',
            'address' => 'Av. Banzer, 4to Anillo',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '1020304054',
            'legal_rep' => 'Roberto Gómez',
            'website' => 'https://tiendas3b.com.bo',
            'industry_code' => 'supermarket',
            'primary_color' => '#E31E24',
            'secondary_color' => '#FFFFFF',
            'logo_filename' => '3B-market-logo.png',
            'company_admin' => [
                'first_name' => 'Roberto',
                'last_name' => 'Gómez',
                'email' => 'roberto.gomez@tiendas3b.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Lucía',
                    'last_name' => 'Paz',
                    'email' => 'lucia.paz@tiendas3b.com.bo',
                ],
                [
                    'first_name' => 'Mario',
                    'last_name' => 'Soria',
                    'email' => 'mario.soria@tiendas3b.com.bo',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🏢 Creando 5 empresas bolivianas PEQUEÑAS...');

        // [IDEMPOTENCY] Verificar si ya existen un numero significativo de estas empresas
        // Verificamos por claves para ser más precisos
        $codes = array_column(self::COMPANIES, 'company_code');
        $existingCount = Company::whereIn('company_code', $codes)->count();
        
        if ($existingCount >= count(self::COMPANIES)) {
            $this->command->info('[OK] Todas las empresas pequeñas ya existen. Saltando ejecución.');
            return;
        }

        foreach (self::COMPANIES as $companyData) {
            try {
                // [IDEMPOTENCY] Verificar si la empresa ya existe por company_code
                if (Company::where('company_code', $companyData['company_code'])->exists()) {
                    $this->command->info("[OK] Empresa {$companyData['company_code']} ya existe, saltando...");
                    continue;
                }

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

                // 3. Preparar Logos y URLs
                $logoUrl = null;
                if (isset($companyData['logo_filename'])) {
                    // Copiar y obtener URL antes de crear para insertar en un solo query
                    $logoUrl = $this->publishLogoAndGetUrl($companyData['company_code'], $companyData['logo_filename']);
                }

                // 4. Crear Empresa
                $companyService = app(CompanyService::class);
                $company = $companyService->create([
                    'company_code' => $companyData['company_code'],
                    'name' => $companyData['name'],
                    'legal_name' => $companyData['legal_name'],
                    'description' => $companyData['description'],
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
                    'primary_color' => $companyData['primary_color'],
                    'secondary_color' => $companyData['secondary_color'],
                    'logo_url' => $logoUrl,
                    'favicon_url' => $logoUrl, // Usamos el mismo logo como favicon por defecto
                    'business_hours' => [
                        'monday' => ['open' => '09:00', 'close' => '18:00'],
                        'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                        'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                        'thursday' => ['open' => '09:00', 'close' => '18:00'],
                        'friday' => ['open' => '09:00', 'close' => '18:00'],
                        'saturday' => ['open' => '09:00', 'close' => '13:00'],
                    ],
                    'timezone' => 'America/La_Paz',
                    'status' => 'active',
                    'industry_id' => $industry->id,
                    'settings' => ['areas_enabled' => false], // Configuración directa
                ], $admin);

                $this->command->info("✅ Empresa '{$company->name}' creada con admin: {$admin->email}");

                // 5. Asignar rol COMPANY_ADMIN
                UserRole::create([
                    'user_id' => $admin->id,
                    'role_code' => 'COMPANY_ADMIN',
                    'company_id' => $company->id,
                    'is_active' => true,
                ]);

                // 6. Crear Agentes
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
                
                if ($logoUrl) {
                    $this->command->info("  └─ Logo publicado: {$logoUrl}");
                }

            } catch (\Exception $e) {
                $this->command->error("❌ Error creando empresa: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ Seeder de empresas pequeñas completado con éxito!');
    }

    /**
     * Copia el logo y retorna la URL pública.
     */
    private function publishLogoAndGetUrl(string $companyCode, string $logoFilename): ?string
    {
        $sourcePath = app_path("Features/CompanyManagement/resources/logos/{$logoFilename}");

        if (!file_exists($sourcePath)) {
            $this->command->warn("  ⚠  Logo no encontrado en resources: {$logoFilename}");
            return null;
        }

        try {
            $storagePath = "company-logos/{$companyCode}";
            
            if (!Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->makeDirectory($storagePath);
            }

            $fullPath = "{$storagePath}/{$logoFilename}";
            $fileContent = file_get_contents($sourcePath);
            Storage::disk('public')->put($fullPath, $fileContent);

            return asset("storage/{$fullPath}");
        } catch (\Exception $e) {
            $this->command->error("  ❌ Error copiando logo: {$e->getMessage()}");
            return null;
        }
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