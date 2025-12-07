<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\CompanyRequestStatus;
use App\Shared\Helpers\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Company Request Approval Simulation Seeder
 *
 * Este seeder SIMULA el flujo de aprobación de solicitudes de empresa SIN DUPLICAR data.
 *
 * Toma las 12 empresas existentes (6 LARGE + 4 MEDIUM + 2 SMALL) y crea CompanyRequest
 * records con status='approved' vinculados a esas empresas.
 *
 * Además crea:
 * - 4 solicitudes PENDING (sin empresa asociada)
 * - 7 solicitudes REJECTED (sin empresa asociada)
 *
 * Total: 23 CompanyRequest records
 * - 12 APPROVED (con created_company_id vinculado)
 * - 4 PENDING (created_company_id = NULL)
 * - 7 REJECTED (created_company_id = NULL, con rejection_reason)
 *
 * Distribución de fechas:
 * - Aprobadas: desde 1-ene-2025 hasta hoy, distribuidas
 * - Pendientes: últimos 7 días (más recientes)
 * - Rechazadas: últimos 30 días, distribuidas
 *
 * Idempotencia:
 * - Si el seeder ya fue ejecutado (12+ APPROVED records), se salta automáticamente
 * - Evita duplicados al re-correr db:seed
 *
 * Reviewer: lukqs05@gmail.com (PLATFORM_ADMIN)
 *
 * IMPORTANTE: NO DUPLICA DATA
 * - Busca empresas por company_code (determinístico, ya existen)
 * - Verifica si ya existe CompanyRequest para esa empresa (checked by created_company_id)
 * - Usa IDs reales de BD, no hardcodeados
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
        $approvedCount = CompanyRequest::where('status', CompanyRequestStatus::APPROVED->value)->count();
        if ($approvedCount >= 12) {
            $this->command->info('[OK] Seeder ya fue ejecutado anteriormente. Saltando ejecución para evitar duplicados.');
            return;
        }

        $this->command->info('[INFO] Iniciando simulacion de aprobacion de solicitudes...');

        // Usar transacción para garantizar atomicidad
        DB::transaction(function () {
            // 1. Crear CompanyRequest APPROVED para las 12 empresas existentes
            $this->createApprovedRequests();

            // 2. Crear 4 CompanyRequest PENDING
            $this->createPendingRequests();

            // 3. Crear 6 CompanyRequest REJECTED
            $this->createRejectedRequests();
        });

        $this->command->info('[OK] Simulacion completada exitosamente');
    }

    /**
     * Crear 12 CompanyRequest APPROVED vinculados a las empresas existentes
     */
    private function createApprovedRequests(): void
    {
        // Array con los company_codes de las 12 empresas que ya existen
        $companyCodes = [
            // LARGE (6 empresas)
            'CMP-2025-00001', // PIL Andina
            'CMP-2025-00002', // YPFB
            'CMP-2025-00003', // Entel
            'CMP-2025-00004', // Tigo
            'CMP-2025-00005', // CBN
            'CMP-2025-00006', // Banco Mercantil Santa Cruz

            // MEDIUM (4 empresas)
            'CMP-2025-00007', // Banco Fassil
            'CMP-2025-00008', // Hipermaxi
            'CMP-2025-00009', // Sofía Ltda
            'CMP-2025-00010', // Farmacorp

            // SMALL (2 empresas de 5)
            'CMP-2025-00011', // Victoria Veterinaria
            'CMP-2025-00012', // Iris Computer
        ];

        $approvedCount = 0;
        $skippedCount = 0;

        foreach ($companyCodes as $index => $companyCode) {
            // Buscar la empresa por company_code
            $company = Company::where('company_code', $companyCode)->first();

            if (!$company) {
                $this->command->warn("[WARN] Empresa no encontrada: {$companyCode}");
                continue;
            }

            // IMPORTANTE: Verificar si ya existe CompanyRequest aprobada para esta empresa
            $existingRequest = CompanyRequest::where('created_company_id', $company->id)->first();
            if ($existingRequest) {
                $this->command->info("[OK] CompanyRequest ya existe para {$companyCode}");
                $skippedCount++;
                continue;
            }

            // Calcular fecha de aprobación distribuyendo desde 1-ene-2025 hasta hoy
            $daysElapsed = $this->startDate->diffInDays($this->endDate);
            $dayOffset = (int) ($index * ($daysElapsed / 12)); // Distribuir entre 12 empresas
            $approvedAt = $this->startDate->clone()->addDays($dayOffset);

            // Generar request_code determinístico basado en company_code (IDEMPOTENTE)
            $requestCode = $this->generateRequestCodeForCompany($companyCode);

            // Crear CompanyRequest APPROVED
            $request = CompanyRequest::create([
                'request_code' => $requestCode,

                // Datos de la empresa (tomados de la empresa existente)
                'company_name' => $company->name,
                'legal_name' => $company->legal_name,
                'admin_email' => $company->support_email,
                'company_description' => $company->description,
                'request_message' => "Solicitud de incorporación de empresa: {$company->name}",
                'website' => $company->website,
                'industry_id' => $company->industry_id,
                'estimated_users' => 50, // Valor por defecto
                'contact_address' => $company->contact_address,
                'contact_city' => $company->contact_city,
                'contact_country' => $company->contact_country,
                'contact_postal_code' => $company->contact_postal_code,
                'tax_id' => $company->tax_id,

                // Estado de aprobación
                'status' => CompanyRequestStatus::APPROVED->value,
                'reviewed_by' => $this->platformAdmin->id,
                'reviewed_at' => $approvedAt,
                'created_company_id' => $company->id,
            ]);

            // Ajustar timestamps a las fechas de aprobación para histórico consistente
            $request->update([
                'created_at' => $approvedAt->clone()->subDays(random_int(1, 5)), // Solicitud 1-5 días antes
                'updated_at' => $approvedAt,
            ]);

            $approvedCount++;
            $this->command->line("  [OK] [" . ($index + 1) . "/12] {$companyCode} ({$company->name})");
        }

        $this->command->info("[OK] {$approvedCount} solicitudes APPROVED creadas" . ($skippedCount > 0 ? ", {$skippedCount} ya existian" : ''));
    }

    /**
     * Crear 4 CompanyRequest PENDING (sin empresa asociada)
     */
    private function createPendingRequests(): void
    {
        $pendingCompanies = [
            [
                'company_name' => 'TechStartup Bolivia Inc.',
                'legal_name' => 'TechStartup Bolivia Inc. S.R.L.',
                'admin_email' => 'contacto@techstartup-bo.dev',
                'website' => 'https://techstartup-bo.dev',
                'tax_id' => 'TAX-PENDING-001',
                'industry_code' => 'technology',
                'city' => 'La Paz',
            ],
            [
                'company_name' => 'Telecomunicaciones Global Bolivia',
                'legal_name' => 'Telecomunicaciones Global Bolivia S.A.',
                'admin_email' => 'contacto@telecom-global-bo.bo',
                'website' => 'https://telecom-global-bo.bo',
                'tax_id' => 'TAX-PENDING-002',
                'industry_code' => 'telecommunications',
                'city' => 'Santa Cruz',
            ],
            [
                'company_name' => 'Servicios Financieros Corporativo',
                'legal_name' => 'Servicios Financieros Corporativo Ltda.',
                'admin_email' => 'soporte@finserv-corp-bo.bo',
                'website' => 'https://finserv-corp-bo.bo',
                'tax_id' => 'TAX-PENDING-003',
                'industry_code' => 'finance',
                'city' => 'Cochabamba',
            ],
            [
                'company_name' => 'Soluciones Comerciales Integradas',
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
            // Verificar si no existe ya
            if (CompanyRequest::where('admin_email', $data['admin_email'])->exists()) {
                $this->command->info("[OK] CompanyRequest PENDING ya existe para {$data['admin_email']}");
                continue;
            }

            $industry = CompanyIndustry::where('code', $data['industry_code'])->first();
            if (!$industry) {
                $this->command->warn("[WARN] Industria no encontrada: {$data['industry_code']}");
                continue;
            }

            // Fechas: últimos 7 días
            $createdAt = $this->endDate->clone()->subDays(random_int(1, 7));

            $request = CompanyRequest::create([
                'request_code' => $this->generateUniqueRequestCode(),
                'company_name' => $data['company_name'],
                'legal_name' => $data['legal_name'],
                'admin_email' => $data['admin_email'],
                'company_description' => "Solicitud de incorporación para {$data['company_name']}",
                'request_message' => "Nueva solicitud de registro de empresa. Por favor, revisar documentación adjunta.",
                'website' => $data['website'],
                'industry_id' => $industry->id,
                'estimated_users' => random_int(10, 100),
                'contact_city' => $data['city'],
                'contact_country' => 'Bolivia',
                'tax_id' => $data['tax_id'],

                // PENDING: sin revisar
                'status' => CompanyRequestStatus::PENDING->value,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_company_id' => null,

                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $pendingCount++;
            $this->command->line("  [PENDING] [" . ($index + 1) . "/4] {$data['company_name']}");
        }

        $this->command->info("[OK] {$pendingCount} solicitudes PENDING creadas");
    }

    /**
     * Crear 7 CompanyRequest REJECTED (sin empresa asociada, con motivo de rechazo)
     */
    private function createRejectedRequests(): void
    {
        $rejectionReasons = [
            'Documentación legal incompleta o vencida',
            'NIT/Tax ID duplicado en el sistema',
            'Datos de contacto inválidos o no verificables',
            'Empresa con historial de incumplimiento legal',
            'Información de industria no coincide con actividades declaradas',
            'Email administrativo rechaza la invitación de verificación',
            'Solicitud duplicada con solicitud anterior rechazada',
            'Incumplimiento de estándares de seguridad requeridos',
            'Empresa sin verificación de existencia legal',
        ];

        $rejectedCompanies = [
            [
                'company_name' => 'Distribuidora de Alimentos Andina',
                'legal_name' => 'Distribuidora de Alimentos Andina S.A.',
                'admin_email' => 'contacto@distribuiodora-alimentos-rejected.bo',
                'tax_id' => 'TAX-REJECTED-001',
                'industry_code' => 'food_and_beverage',
            ],
            [
                'company_name' => 'Productora Audiovisual Ltda.',
                'legal_name' => 'Productora Audiovisual Ltda.',
                'admin_email' => 'info@productora-audiovisual-rejected.bo',
                'tax_id' => 'TAX-REJECTED-002',
                'industry_code' => 'media',
            ],
            [
                'company_name' => 'Transporte Bolivia Corporativo',
                'legal_name' => 'Transporte Bolivia Corporativo S.R.L.',
                'admin_email' => 'admin@transporte-bolivia-rejected.bo',
                'tax_id' => 'TAX-REJECTED-003',
                'industry_code' => 'transportation',
            ],
            [
                'company_name' => 'Comercio Internacional Boliviano',
                'legal_name' => 'Comercio Internacional Boliviano Corp.',
                'admin_email' => 'contacto@comercio-int-rejected.bo',
                'tax_id' => 'TAX-REJECTED-004',
                'industry_code' => 'professional_services',
            ],
            [
                'company_name' => 'Servicios Hoteleros Premium',
                'legal_name' => 'Servicios Hoteleros Premium S.A.',
                'admin_email' => 'admin@hoteles-premium-rejected.bo',
                'tax_id' => 'TAX-REJECTED-005',
                'industry_code' => 'hospitality',
            ],
            [
                'company_name' => 'Consultoría Tributaria Integral',
                'legal_name' => 'Consultoría Tributaria Integral Ltda.',
                'admin_email' => 'info@consultoria-tributaria-rejected.bo',
                'tax_id' => 'TAX-REJECTED-006',
                'industry_code' => 'finance',
            ],
            [
                'company_name' => 'Agroproductos Innovadores Ltda.',
                'legal_name' => 'Agroproductos Innovadores Ltda.',
                'admin_email' => 'contacto@agroproductos-innovadores-rejected.bo',
                'tax_id' => 'TAX-REJECTED-007',
                'industry_code' => 'agriculture',
            ],
        ];

        $rejectedCount = 0;

        foreach ($rejectedCompanies as $index => $data) {
            // Verificar si no existe ya
            if (CompanyRequest::where('admin_email', $data['admin_email'])->exists()) {
                $this->command->info("[OK] CompanyRequest REJECTED ya existe para {$data['admin_email']}");
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

            // Mapear índice a reason (modulo para repetir si es necesario)
            $reasonIndex = $index % count($rejectionReasons);

            $request = CompanyRequest::create([
                'request_code' => $this->generateUniqueRequestCode(),
                'company_name' => $data['company_name'],
                'legal_name' => $data['legal_name'],
                'admin_email' => $data['admin_email'],
                'company_description' => "Solicitud de incorporación para {$data['company_name']}",
                'request_message' => "Nueva solicitud de registro de empresa.",
                'website' => null,
                'industry_id' => $industry->id,
                'estimated_users' => random_int(10, 50),
                'contact_country' => 'Bolivia',
                'tax_id' => $data['tax_id'],

                // REJECTED: con motivo
                'status' => CompanyRequestStatus::REJECTED->value,
                'reviewed_by' => $this->platformAdmin->id,
                'reviewed_at' => $rejectedAt,
                'rejection_reason' => $rejectionReasons[$reasonIndex],
                'created_company_id' => null,

                'created_at' => $createdAt,
                'updated_at' => $rejectedAt,
            ]);

            $rejectedCount++;
            $this->command->line("  [REJECTED] [" . ($index + 1) . "/6] {$data['company_name']} - {$rejectionReasons[$reasonIndex]}");
        }

        $this->command->info("[OK] {$rejectedCount} solicitudes REJECTED creadas");
    }

    /**
     * Generar un request_code único (REQ-YYYY-NNNNN) de forma idempotente
     * Basado en el company_code para ser determinístico
     */
    private function generateRequestCodeForCompany(string $companyCode): string
    {
        // Extraer número del company_code (ej: CMP-2025-00001 → 00001)
        $parts = explode('-', $companyCode);
        $number = end($parts);

        return sprintf('REQ-2025-%s', $number);
    }

    /**
     * Generar un request_code único (REQ-YYYY-NNNNN) para solicitudes PENDING/REJECTED
     * Comienza desde 00013 (después de las 12 APPROVED que van del 00001 al 00012)
     */
    private function generateUniqueRequestCode(): string
    {
        $year = date('Y');
        // Obtener todos los request_codes existentes de este año
        $existingCodes = CompanyRequest::whereYear('created_at', $year)
            ->pluck('request_code')
            ->toArray();

        // Extraer números y encontrar el máximo
        $numbers = array_map(function ($code) {
            $parts = explode('-', $code);
            return (int) end($parts);
        }, $existingCodes);

        $maxNumber = !empty($numbers) ? max($numbers) : 12;
        $sequence = $maxNumber + 1;

        return sprintf('REQ-%s-%05d', $year, $sequence);
    }
}
