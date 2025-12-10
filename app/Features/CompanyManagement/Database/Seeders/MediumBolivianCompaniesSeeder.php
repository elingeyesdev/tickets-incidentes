<?php

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Area;
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
 * Medium Bolivian Companies Seeder
 *
 * Crea empresas bolivianas MEDIANAS (grandes empresas nacionales, bancos regionales) con:
 * - company_code FIJO (formato CMP-2025-0000X) → determinístico, no duplica logos
 * - 1 Company Admin por empresa
 * - 4-7 Agentes por empresa (según tamaño)
 * - 3-5 Áreas/Departamentos críticos (estructura organizacional mediana)
 * - areas_enabled = true (funcionalidad activada)
 * - Logos copiados automáticamente de resources → storage (idempotente)
 * - Todos los usuarios con contraseña: mklmklmkl
 * - industry_id asignado correctamente
 *
 * Empresas MEDIANAS:
 * 1. Banco Fassil (CMP-2025-00007) - Servicios Financieros (5 áreas) - Banco mediano con presencia nacional
 * 2. Hipermaxi (CMP-2025-00008) - Retail/Supermercados (4 áreas) - Cadena de supermercados y farmacias más grande
 * 3. Sofía (CMP-2025-00009) - Alimentos/Avícola (5 áreas) - Líder en producción avícola y alimentos procesados
 * 4. Farmacorp (CMP-2025-00010) - Retail/Farmacias (4 áreas) - Cadena de farmacias más grande con 176 sucursales
 *
 * Estructura de logos (determinística, sin timestamps):
 * storage/app/public/company-logos/
 * ├── CMP-2025-00007/fassil-logo.png
 * ├── CMP-2025-00008/hipermaxi-logo.png
 * ├── CMP-2025-00009/sofia-logo.png
 * └── CMP-2025-00010/farmacorp-logo.png
 *
 * Beneficios:
 * - Idempotente: ejecutar múltiples veces no duplica logos
 * - Sin manual: logos se copian automáticamente desde resources
 * - Determinístico: mismo company_code = misma carpeta = misma URL
 */
class MediumBolivianCompaniesSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    
    // Contador para distribuir fechas (empresas medianas son CMP-2025-00007 al 00010 = 4 empresas)
    private int $companyIndex = 6; // Offset para continuar desde donde terminaron las Large

    private const COMPANIES = [
        [
            'company_code' => 'CMP-2025-00007',
            'name' => 'Banco Fassil S.A.',
            'legal_name' => 'Banco Fassil S.A. - Servicios Financieros',
            'description' => 'Institución financiera boliviana que ofrece servicios bancarios integrales con enfoque en la inclusión y desarrollo económico',
            'support_email' => 'soporte@fassil.com.bo',
            'phone' => '+59133158000',
            'city' => 'Santa Cruz',
            'address' => 'Libertad 765, Centro',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '151236547',
            'legal_rep' => 'Fernando Mendoza López',
            'website' => 'https://www.fassil.com.bo',
            'industry_code' => 'banking',
            'primary_color' => '#0066CC',
            'secondary_color' => '#003D7A',
            'logo_filename' => 'fassil-logo.png',
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
                [
                    'first_name' => 'María',
                    'last_name' => 'Rodríguez',
                    'email' => 'maria.rodriguez@fassil.com.bo',
                ],
                [
                    'first_name' => 'Roberto',
                    'last_name' => 'Salazar',
                    'email' => 'roberto.salazar@fassil.com.bo',
                ],
                [
                    'first_name' => 'Ana',
                    'last_name' => 'Fernández',
                    'email' => 'ana.fernandez@fassil.com.bo',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Operaciones Bancarias',
                    'description' => 'Procesamiento de transacciones, clearing interbancario',
                ],
                [
                    'name' => 'Créditos y Colocaciones',
                    'description' => 'Créditos empresariales, créditos personales, análisis crediticio',
                ],
                [
                    'name' => 'Atención al Cliente',
                    'description' => 'Servicio al cliente, resolución de consultas, gestión de reclamos',
                ],
                [
                    'name' => 'Gestión de Riesgos',
                    'description' => 'Riesgo crediticio, riesgo operacional, análisis de exposición, AML/CFT',
                ],
                [
                    'name' => 'Cumplimiento Regulatorio',
                    'description' => 'Compliance normativo, auditorías internas, normativas, KYC',
                ],
                [
                    'name' => 'Tecnología',
                    'description' => 'Sistemas bancarios, seguridad digital, ciberseguridad, infraestructura TI',
                ],
                [
                    'name' => 'Finanzas',
                    'description' => 'Presupuestos, análisis financiero',
                ],
                [
                    'name' => 'Contabilidad',
                    'description' => 'Registros contables, reportes financieros',
                ],
                [
                    'name' => 'Tesorería',
                    'description' => 'Gestión de caja, cobranzas, pagos',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, contratación, capacitación',
                ],
                [
                    'name' => 'Asuntos Legales',
                    'description' => 'Contratos, litigios, responsabilidad civil, asuntos corporativos',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00008',
            'name' => 'Hipermaxi S.A.',
            'legal_name' => 'Hipermaxi S.A. - Supermercados y Farmacias',
            'description' => 'Cadena de supermercados y farmacias más grande de Bolivia con 37 sucursales y presencia nacional',
            'support_email' => 'hipermaxi@hipermaxi.com',
            'phone' => '+59133425353',
            'city' => 'Santa Cruz',
            'address' => 'Av. Roca y Coronado 901, Barrio 4 de Noviembre',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '1028627025',
            'legal_rep' => 'Diego Guzmán de Rojas',
            'website' => 'https://www.hipermaxi.com',
            'industry_code' => 'supermarket',
            'primary_color' => '#0066CC',
            'secondary_color' => '#FF6600',
            'logo_filename' => 'hipermaxi-logo.png',
            'company_admin' => [
                'first_name' => 'Diego',
                'last_name' => 'Guzmán',
                'email' => 'diego.guzman@hipermaxi.com',
            ],
            'agents' => [
                [
                    'first_name' => 'Sandra',
                    'last_name' => 'Pérez',
                    'email' => 'sandra.perez@hipermaxi.com',
                ],
                [
                    'first_name' => 'Julio',
                    'last_name' => 'Ramírez',
                    'email' => 'julio.ramirez@hipermaxi.com',
                ],
                [
                    'first_name' => 'Patricia',
                    'last_name' => 'Méndez',
                    'email' => 'patricia.mendez@hipermaxi.com',
                ],
                [
                    'first_name' => 'Miguel',
                    'last_name' => 'Torres',
                    'email' => 'miguel.torres@hipermaxi.com',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Operaciones de Tiendas',
                    'description' => 'Gestión de supermercados y sucursales, atención en tienda, horarios operativos',
                ],
                [
                    'name' => 'Gestión de Inventarios',
                    'description' => 'Reposición, control de stock, inventario físico, rotación de productos',
                ],
                [
                    'name' => 'Control de Calidad',
                    'description' => 'Inspección de productos, estándares de calidad',
                ],
                [
                    'name' => 'Perecibles y Cadena de Frío',
                    'description' => 'Manejo de perecederos, almacenamiento en frío, temperatura controlada',
                ],
                [
                    'name' => 'Logística y Distribución',
                    'description' => 'Cadena de suministro, almacenes centrales, transporte de productos',
                ],
                [
                    'name' => 'Ventas y Comercial',
                    'description' => 'Negociaciones comerciales, canales de venta',
                ],
                [
                    'name' => 'Promociones y Precios',
                    'description' => 'Ofertas, estrategia de precios, promociones',
                ],
                [
                    'name' => 'Atención al Cliente',
                    'description' => 'Servicio en cajas, devoluciones, quejas, satisfacción del cliente',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, contratación, capacitación de personal',
                ],
                [
                    'name' => 'Finanzas',
                    'description' => 'Presupuestos, análisis financiero',
                ],
                [
                    'name' => 'Contabilidad',
                    'description' => 'Registros contables, reportes financieros',
                ],
                [
                    'name' => 'Sistemas e IT',
                    'description' => 'Sistemas de puntos de venta, inventarios, infraestructura TI',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00009',
            'name' => 'Sofía Ltda.',
            'legal_name' => 'Granja Avícola Integral Sofía Ltda.',
            'description' => 'Empresa boliviana líder en producción avícola y alimentos procesados con más de 49 años de experiencia y 3,000 empleados',
            'support_email' => 'proveedores@avicolasofia.com',
            'phone' => '+591800124141',
            'city' => 'Santa Cruz',
            'address' => 'Parque Industrial, Mz. 7',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '1020104020',
            'legal_rep' => 'Representante Legal Sofía',
            'website' => 'https://www.sofia.com.bo',
            'industry_code' => 'food_and_beverage',
            'primary_color' => '#D81A1B',
            'secondary_color' => '#FFFFFF',
            'logo_filename' => 'sofia-logo.png',
            'company_admin' => [
                'first_name' => 'Carlos',
                'last_name' => 'Villegas',
                'email' => 'carlos.villegas@avicolasofia.com',
            ],
            'agents' => [
                [
                    'first_name' => 'Rosa',
                    'last_name' => 'Mamani',
                    'email' => 'rosa.mamani@avicolasofia.com',
                ],
                [
                    'first_name' => 'Luis',
                    'last_name' => 'Choque',
                    'email' => 'luis.choque@avicolasofia.com',
                ],
                [
                    'first_name' => 'Teresa',
                    'last_name' => 'Quispe',
                    'email' => 'teresa.quispe@avicolasofia.com',
                ],
                [
                    'first_name' => 'Jorge',
                    'last_name' => 'Rojas',
                    'email' => 'jorge.rojas@avicolasofia.com',
                ],
                [
                    'first_name' => 'Marta',
                    'last_name' => 'Velasco',
                    'email' => 'marta.velasco@avicolasofia.com',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Producción Avícola',
                    'description' => 'Incubación, crianza, engorde, faenado de aves',
                ],
                [
                    'name' => 'Procesamiento de Alimentos',
                    'description' => 'Fabricación de pastas, harinas, galletas, chocolates, líneas de producción',
                ],
                [
                    'name' => 'Control de Calidad',
                    'description' => 'ISO 9001, ISO 22000, buenas prácticas de manufactura, análisis de productos',
                ],
                [
                    'name' => 'Logística',
                    'description' => 'Cadena de frío, almacenamiento',
                ],
                [
                    'name' => 'Distribución',
                    'description' => 'Distribución nacional, gestión de inventarios',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, contratación, capacitación, relaciones laborales',
                ],
                [
                    'name' => 'Seguridad y Salud Ocupacional',
                    'description' => 'Seguridad laboral, salud ocupacional, protección ambiental',
                ],
                [
                    'name' => 'Finanzas',
                    'description' => 'Presupuestos, análisis financiero',
                ],
                [
                    'name' => 'Contabilidad',
                    'description' => 'Contabilidad general, registros contables, reportes financieros',
                ],
                [
                    'name' => 'Tesorería',
                    'description' => 'Gestión de caja, cobranzas, pagos',
                ],
                [
                    'name' => 'Asuntos Legales',
                    'description' => 'Contratos, litigios, asuntos corporativos',
                ],
                [
                    'name' => 'Cumplimiento Normativo',
                    'description' => 'Cumplimiento normativo sanitario, regulaciones avícolas y alimentarias',
                ],
                [
                    'name' => 'Sistemas e IT',
                    'description' => 'Infraestructura TI, sistemas administrativos, automatización, ciberseguridad',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00010',
            'name' => 'Farmacorp S.A.',
            'legal_name' => 'Farmacias Corporativas S.A.',
            'description' => 'Cadena de farmacias más grande de Bolivia con 176 sucursales en todos los departamentos y certificación BPA de Agemed',
            'support_email' => 'info@farmacorp.com',
            'phone' => '+59161553333',
            'city' => 'Santa Cruz',
            'address' => 'Parque Industrial, Mza. 21-A',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '1015447026',
            'legal_rep' => 'María del Rosario Paz Gutiérrez',
            'website' => 'https://www.farmacorp.com',
            'industry_code' => 'pharmacy',
            'primary_color' => '#00A651',
            'secondary_color' => '#0066CC',
            'logo_filename' => 'farmacorp-logo.png',
            'company_admin' => [
                'first_name' => 'María',
                'last_name' => 'Paz',
                'email' => 'maria.paz@farmacorp.com',
            ],
            'agents' => [
                [
                    'first_name' => 'Juana',
                    'last_name' => 'Flores',
                    'email' => 'juana.flores@farmacorp.com',
                ],
                [
                    'first_name' => 'Pedro',
                    'last_name' => 'Sánchez',
                    'email' => 'pedro.sanchez@farmacorp.com',
                ],
                [
                    'first_name' => 'Carmen',
                    'last_name' => 'Vargas',
                    'email' => 'carmen.vargas@farmacorp.com',
                ],
                [
                    'first_name' => 'Alberto',
                    'last_name' => 'Mendoza',
                    'email' => 'alberto.mendoza@farmacorp.com',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Operaciones de Farmacias',
                    'description' => 'Gestión de 176 sucursales, atención farmacéutica, dispensación de medicamentos',
                ],
                [
                    'name' => 'Control de Calidad',
                    'description' => 'Inspecciones de calidad, estándares de productos',
                ],
                [
                    'name' => 'Buenas Prácticas de Almacenamiento (BPA)',
                    'description' => 'Certificación Agemed, control de temperatura, almacenamiento seguro',
                ],
                [
                    'name' => 'Farmacovigilancia',
                    'description' => 'Farmacovigilancia, reportes de seguridad de medicamentos',
                ],
                [
                    'name' => 'Cumplimiento Regulatorio',
                    'description' => 'Cumplimiento normativas sanitarias, regulaciones farmacéuticas, auditorías',
                ],
                [
                    'name' => 'Logística',
                    'description' => 'Cadena de suministro farmacéutico, almacenes centrales',
                ],
                [
                    'name' => 'Distribución',
                    'description' => 'Transporte seguro de medicamentos, gestión de inventarios',
                ],
                [
                    'name' => 'Atención al Cliente',
                    'description' => 'Atención farmacéutica, resolución de consultas, gestión de reclamos',
                ],
                [
                    'name' => 'Servicio al Cliente',
                    'description' => 'Consultas farmacéuticas, información de medicamentos',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, contratación, capacitación de farmacéuticos y personal',
                ],
                [
                    'name' => 'Finanzas',
                    'description' => 'Presupuestos, análisis financiero',
                ],
                [
                    'name' => 'Contabilidad',
                    'description' => 'Contabilidad general, registros contables, reportes financieros',
                ],
                [
                    'name' => 'Tesorería',
                    'description' => 'Gestión de caja, cobranzas, pagos',
                ],
                [
                    'name' => 'Sistemas e IT',
                    'description' => 'Sistemas de gestión farmacéutica, infraestructura TI, inventarios automatizados, ciberseguridad',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🏢 Creando empresas bolivianas MEDIANAS con datos profesionales...');

        // [IDEMPOTENCY] Verificar si ya existen todas las empresas medianas
        $codes = array_column(self::COMPANIES, 'company_code');
        $existingCount = Company::whereIn('company_code', $codes)->count();

        if ($existingCount >= count(self::COMPANIES)) {
            $this->command->info('[OK] Todas las empresas medianas ya existen. Saltando ejecución.');
            return;
        }

        foreach (self::COMPANIES as $companyData) {
            try {
                // [IDEMPOTENCY] Verificar si la empresa ya existe por company_code
                if (Company::where('company_code', $companyData['company_code'])->exists()) {
                    $this->command->info("[OK] Empresa {$companyData['company_code']} ya existe, saltando...");
                    $this->companyIndex++;
                    continue;
                }

                // Generar fecha aleatoria para esta empresa (julio - diciembre 2025)
                $createdAt = $this->getDistributedDate($this->companyIndex, 15); // 15 total empresas
                $this->command->info("  📅 Fecha de registro: {$createdAt->format('Y-m-d H:i:s')}");

                // 1. Crear Company Admin
                $admin = $this->createUser(
                    $companyData['company_admin']['first_name'],
                    $companyData['company_admin']['last_name'],
                    $companyData['company_admin']['email'],
                    $createdAt
                );

                // 2. Obtener industry_id
                $industry = CompanyIndustry::where('code', $companyData['industry_code'])->first();
                if (!$industry) {
                    $this->command->error("❌ Industria no encontrada: {$companyData['industry_code']}");
                    continue;
                }

                // 3. Preparar Logo y URL (ANTES de crear para optimizar queries)
                $logoUrl = null;
                if (isset($companyData['logo_filename'])) {
                    $logoUrl = $this->publishLogoAndGetUrl($companyData['company_code'], $companyData['logo_filename']);
                }

                // 4. Crear Empresa usando CompanyService (dispara CompanyCreated event → auto-crea categorías)
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
                    'settings' => ['areas_enabled' => true], // Configuración directa (empresas medianas usan áreas)
                ], $admin);

                // Actualizar created_at de la empresa
                $company->created_at = $createdAt;
                $company->updated_at = $createdAt;
                $company->save();

                $this->command->info("✅ Empresa '{$company->name}' creada con admin: {$admin->email}");
                $this->companyIndex++;

                // 5. Asignar rol COMPANY_ADMIN
                UserRole::create([
                    'user_id' => $admin->id,
                    'role_code' => 'COMPANY_ADMIN',
                    'company_id' => $company->id,
                    'is_active' => true,
                ]);

                // 6. Crear Agentes (escalonados días después de la empresa)
                foreach ($companyData['agents'] as $agentData) {
                    // Cada agente se crea entre 3-30 días después de la empresa
                    $agentCreatedAt = $createdAt->copy()->addDays(rand(3, 30))->addHours(rand(8, 18))->addMinutes(rand(0, 59));
                    
                    $agent = $this->createUser(
                        $agentData['first_name'],
                        $agentData['last_name'],
                        $agentData['email'],
                        $agentCreatedAt
                    );

                    UserRole::create([
                        'user_id' => $agent->id,
                        'role_code' => 'AGENT',
                        'company_id' => $company->id,
                        'is_active' => true,
                    ]);

                    $this->command->info("  └─ Agente creado: {$agent->email} ({$agentCreatedAt->format('Y-m-d')})");
                }

                // 7. Crear Áreas para la empresa
                $areasCount = count($companyData['areas']);
                $this->command->info("  ├─ Creando {$areasCount} áreas para la empresa...");
                foreach ($companyData['areas'] as $areaData) {
                    Area::create([
                        'company_id' => $company->id,
                        'name' => $areaData['name'],
                        'description' => $areaData['description'],
                        'is_active' => true,
                    ]);
                    $this->command->info("  │  └─ Área '{$areaData['name']}' creada");
                }

                if ($logoUrl) {
                    $this->command->info("  └─ Logo publicado: {$logoUrl}");
                }

            } catch (\Exception $e) {
                $this->command->error("❌ Error creando empresa: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ Seeder completado con éxito!');
    }

    /**
     * Copia el logo y retorna la URL pública.
     * Se ejecuta ANTES de crear la empresa para incluir la URL en el INSERT inicial.
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

    private function createUser(string $firstName, string $lastName, string $email, $createdAt = null): User
    {
        $userCode = CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code');
        $timestamp = $createdAt ?? now();

        $user = User::create([
            'user_code' => $userCode,
            'email' => $email,
            'password_hash' => Hash::make(self::PASSWORD),
            'email_verified' => true,
            'email_verified_at' => $timestamp,
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'terms_accepted' => true,
            'terms_accepted_at' => $timestamp,
            'terms_version' => 'v2.1',
            'onboarding_completed_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
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

    /**
     * Genera una fecha aleatoria distribuida entre julio y diciembre 2025
     */
    private function getDistributedDate(int $index, int $total): \Carbon\Carbon
    {
        $startDate = \Carbon\Carbon::create(2025, 7, 1, 0, 0, 0);
        $endDate = \Carbon\Carbon::create(2025, 12, 10, 23, 59, 59);
        
        $totalDays = $startDate->diffInDays($endDate);
        $daysPerCompany = $totalDays / $total;
        $baseDays = (int)($index * $daysPerCompany);
        
        $randomDays = rand(-3, 3);
        $finalDays = max(0, min($totalDays, $baseDays + $randomDays));
        
        return $startDate->copy()->addDays($finalDays)->addHours(rand(8, 18))->addMinutes(rand(0, 59));
    }
}
