<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Models\CompanyOnboardingDetails;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Company Request Approval Simulation Seeder
 *
 * ARQUITECTURA NORMALIZADA: 
 * Este seeder crea enterprises con diferentes estados (pending, active, rejected)
 * para simular el flujo de aprobación de solicitudes.
 *
 * Crea:
 * - 4 empresas con status='pending' (solicitudes pendientes)
 * - 7 empresas con status='rejected' (solicitudes rechazadas)
 * - Las empresas 'active' ya existen creadas por otros seeders
 *
 * NOTA: Las empresas 'active' existentes no necesitan CompanyOnboardingDetails
 * porque fueron creadas directamente por un PLATFORM_ADMIN.
 *
 * Reviewer: lukqs05@gmail.com (PLATFORM_ADMIN)
 */
class CompanyRequestApprovalSimulationSeeder extends Seeder
{
    private User $platformAdmin;
    private Carbon $startDate;
    private Carbon $endDate;

    public function run(): void
    {
        // Obtener PLATFORM_ADMIN
        $this->platformAdmin = User::where('email', 'lukqs05@gmail.com')->first();
        if (!$this->platformAdmin) {
            $this->command->error('[ERROR] PLATFORM_ADMIN (lukqs05@gmail.com) no encontrado. Ejecuta DefaultUserSeeder primero.');
            return;
        }

        $this->startDate = Carbon::create(2025, 1, 1);
        $this->endDate = Carbon::now();

        // [IDEMPOTENCY] Verificar si el seeder ya fue ejecutado
        $pendingCount = Company::pending()->count();
        if ($pendingCount >= 4) {
            $this->command->info('[OK] Seeder ya fue ejecutado anteriormente. Saltando ejecución para evitar duplicados.');
            return;
        }

        $this->command->info('[INFO] Iniciando simulacion de solicitudes de empresa...');

        // Usar transacción para garantizar atomicidad
        DB::transaction(function () {
            // 1. Crear 4 empresas PENDING
            $this->createPendingCompanies();

            // 2. Crear 7 empresas REJECTED
            $this->createRejectedCompanies();
        });

        $this->command->info('[OK] Simulacion completada exitosamente');
    }

    /**
     * Crear 4 empresas con status='pending' (solicitudes pendientes)
     */
    private function createPendingCompanies(): void
    {
        $pendingCompanies = [
            [
                'name' => 'TechStartup Bolivia Inc.',
                'legal_name' => 'TechStartup Bolivia Inc. S.R.L.',
                'admin_email' => 'contacto@techstartup-bo.dev',
                'website' => 'https://techstartup-bo.dev',
                'tax_id' => 'TAX-PENDING-001',
                'industry_code' => 'technology',
                'city' => 'La Paz',
            ],
            [
                'name' => 'Telecomunicaciones Global Bolivia',
                'legal_name' => 'Telecomunicaciones Global Bolivia S.A.',
                'admin_email' => 'contacto@telecom-global-bo.bo',
                'website' => 'https://telecom-global-bo.bo',
                'tax_id' => 'TAX-PENDING-002',
                'industry_code' => 'telecommunications',
                'city' => 'Santa Cruz',
            ],
            [
                'name' => 'Servicios Financieros Corporativo',
                'legal_name' => 'Servicios Financieros Corporativo Ltda.',
                'admin_email' => 'soporte@finserv-corp-bo.bo',
                'website' => 'https://finserv-corp-bo.bo',
                'tax_id' => 'TAX-PENDING-003',
                'industry_code' => 'finance',
                'city' => 'Cochabamba',
            ],
            [
                'name' => 'Soluciones Comerciales Integradas',
                'legal_name' => 'Soluciones Comerciales Integradas S.R.L.',
                'admin_email' => 'info@soluciones-comerciales-bo.bo',
                'website' => 'https://soluciones-comerciales-bo.bo',
                'tax_id' => 'TAX-PENDING-004',
                'industry_code' => 'professional_services',
                'city' => 'Tarija',
            ],
        ];

        $pendingCount = 0;

        foreach ($pendingCompanies as $index => $data) {
            // Verificar si no existe ya (por email en onboarding)
            $existingOnboarding = CompanyOnboardingDetails::where('submitter_email', $data['admin_email'])->first();
            if ($existingOnboarding) {
                $this->command->info("[OK] Empresa PENDING ya existe para {$data['admin_email']}");
                continue;
            }

            $industry = CompanyIndustry::where('code', $data['industry_code'])->first();
            if (!$industry) {
                $this->command->warn("[WARN] Industria no encontrada: {$data['industry_code']}");
                continue;
            }

            // Fechas: últimos 7 días
            $createdAt = $this->endDate->clone()->subDays(random_int(1, 7));

            // Generar códigos
            $companyCode = CodeGenerator::generate('business.companies', 'CMP', 'company_code');
            $requestCode = 'REQ-' . date('Y') . '-' . str_pad((string) ($index + 13), 5, '0', STR_PAD_LEFT);

            // Crear empresa con status='pending'
            $company = Company::create([
                'company_code' => $companyCode,
                'name' => $data['name'],
                'legal_name' => $data['legal_name'],
                'description' => "Solicitud de incorporación para {$data['name']}",
                'website' => $data['website'],
                'industry_id' => $industry->id,
                'support_email' => $data['admin_email'],
                'contact_city' => $data['city'],
                'contact_country' => 'Bolivia',
                'tax_id' => $data['tax_id'],
                'status' => 'pending',
                'settings' => [],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Crear detalles de onboarding
            CompanyOnboardingDetails::create([
                'company_id' => $company->id,
                'request_code' => $requestCode,
                'request_message' => "Nueva solicitud de registro de empresa. Por favor, revisar documentación adjunta.",
                'estimated_users' => random_int(10, 100),
                'submitter_email' => $data['admin_email'],
            ]);

            $pendingCount++;
            $this->command->line("  [PENDING] [" . ($index + 1) . "/4] {$data['name']}");
        }

        $this->command->info("[OK] {$pendingCount} empresas PENDING creadas");
    }

    /**
     * Crear 7 empresas con status='rejected' (solicitudes rechazadas)
     */
    private function createRejectedCompanies(): void
    {
        $rejectionReasons = [
            'Documentación legal incompleta o vencida',
            'NIT/Tax ID duplicado en el sistema',
            'Datos de contacto inválidos o no verificables',
            'Empresa con historial de incumplimiento legal',
            'Información de industria no coincide con actividades declaradas',
            'Email administrativo rechaza la invitación de verificación',
            'Solicitud duplicada con solicitud anterior rechazada',
        ];

        $rejectedCompanies = [
            [
                'name' => 'Distribuidora de Alimentos Andina',
                'legal_name' => 'Distribuidora de Alimentos Andina S.A.',
                'admin_email' => 'contacto@distribuiodora-alimentos-rejected.bo',
                'tax_id' => 'TAX-REJECTED-001',
                'industry_code' => 'food_and_beverage',
            ],
            [
                'name' => 'Productora Audiovisual Ltda.',
                'legal_name' => 'Productora Audiovisual Ltda.',
                'admin_email' => 'info@productora-audiovisual-rejected.bo',
                'tax_id' => 'TAX-REJECTED-002',
                'industry_code' => 'media',
            ],
            [
                'name' => 'Transporte Bolivia Corporativo',
                'legal_name' => 'Transporte Bolivia Corporativo S.R.L.',
                'admin_email' => 'admin@transporte-bolivia-rejected.bo',
                'tax_id' => 'TAX-REJECTED-003',
                'industry_code' => 'transportation',
            ],
            [
                'name' => 'Comercio Internacional Boliviano',
                'legal_name' => 'Comercio Internacional Boliviano Corp.',
                'admin_email' => 'contacto@comercio-int-rejected.bo',
                'tax_id' => 'TAX-REJECTED-004',
                'industry_code' => 'professional_services',
            ],
            [
                'name' => 'Servicios Hoteleros Premium',
                'legal_name' => 'Servicios Hoteleros Premium S.A.',
                'admin_email' => 'admin@hoteles-premium-rejected.bo',
                'tax_id' => 'TAX-REJECTED-005',
                'industry_code' => 'hospitality',
            ],
            [
                'name' => 'Consultoría Tributaria Integral',
                'legal_name' => 'Consultoría Tributaria Integral Ltda.',
                'admin_email' => 'info@consultoria-tributaria-rejected.bo',
                'tax_id' => 'TAX-REJECTED-006',
                'industry_code' => 'finance',
            ],
            [
                'name' => 'Agroproductos Innovadores Ltda.',
                'legal_name' => 'Agroproductos Innovadores Ltda.',
                'admin_email' => 'contacto@agroproductos-innovadores-rejected.bo',
                'tax_id' => 'TAX-REJECTED-007',
                'industry_code' => 'agriculture',
            ],
        ];

        $rejectedCount = 0;

        foreach ($rejectedCompanies as $index => $data) {
            // Verificar si no existe ya (por email en onboarding)
            $existingOnboarding = CompanyOnboardingDetails::where('submitter_email', $data['admin_email'])->first();
            if ($existingOnboarding) {
                $this->command->info("[OK] Empresa REJECTED ya existe para {$data['admin_email']}");
                continue;
            }

            $industry = CompanyIndustry::where('code', $data['industry_code'])->first();
            if (!$industry) {
                $this->command->warn("[WARN] Industria no encontrada: {$data['industry_code']}");
                continue;
            }

            // Fechas: últimos 30 días
            $createdAt = $this->endDate->clone()->subDays(random_int(8, 30));
            $rejectedAt = $createdAt->clone()->addDays(random_int(1, 5));

            // Generar códigos
            $companyCode = CodeGenerator::generate('business.companies', 'CMP', 'company_code');
            $requestCode = 'REQ-' . date('Y') . '-' . str_pad((string) ($index + 17), 5, '0', STR_PAD_LEFT);

            // Crear empresa con status='rejected'
            $company = Company::create([
                'company_code' => $companyCode,
                'name' => $data['name'],
                'legal_name' => $data['legal_name'],
                'description' => "Solicitud de incorporación para {$data['name']}",
                'website' => null,
                'industry_id' => $industry->id,
                'support_email' => $data['admin_email'],
                'contact_country' => 'Bolivia',
                'tax_id' => $data['tax_id'],
                'status' => 'rejected',
                'settings' => [],
                'created_at' => $createdAt,
                'updated_at' => $rejectedAt,
            ]);

            // Crear detalles de onboarding con rechazo
            CompanyOnboardingDetails::create([
                'company_id' => $company->id,
                'request_code' => $requestCode,
                'request_message' => "Nueva solicitud de registro de empresa.",
                'estimated_users' => random_int(10, 50),
                'submitter_email' => $data['admin_email'],
                'reviewed_by' => $this->platformAdmin->id,
                'reviewed_at' => $rejectedAt,
                'rejection_reason' => $rejectionReasons[$index % count($rejectionReasons)],
            ]);

            $rejectedCount++;
            $this->command->line("  [REJECTED] [" . ($index + 1) . "/7] {$data['name']} - {$rejectionReasons[$index % count($rejectionReasons)]}");
        }

        $this->command->info("[OK] {$rejectedCount} empresas REJECTED creadas");
    }
}
