<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Database\Seeder;

class UpdateBolivianCompanyLogosSeeder extends Seeder
{
    /**
     * Actualizar logos de empresas bolivianas
     * Solo ACTUALIZA URLs, no descarga ni elimina nada
     */
    private array $bolivianCompanies = [
        'b6916ab4-9c64-4305-8054-958c97131ea3' => [
            'name' => 'PIL Andina S.A.',
            'logoUrl' => 'http://localhost:8000/storage/company-logos/b6916ab4-9c64-4305-8054-958c97131ea3/1731779800_pil-andina-logo.png',
        ],
        'ec198568-b237-432b-81ae-76d263596471' => [
            'name' => 'Banco Fassil S.A.',
            'logoUrl' => 'http://localhost:8000/storage/company-logos/ec198568-b237-432b-81ae-76d263596471/1731779801_fassil-logo.png',
        ],
        '4dfda053-4bfa-42a0-ae58-cc8e96bebf0c' => [
            'name' => 'YPFB Corporación',
            'logoUrl' => 'http://localhost:8000/storage/company-logos/4dfda053-4bfa-42a0-ae58-cc8e96bebf0c/1731779802_ypfb-logo.png',
        ],
        'd5e69850-ba31-4e28-bb99-f97468bd72c5' => [
            'name' => 'Tigo Bolivia S.A.',
            'logoUrl' => 'http://localhost:8000/storage/company-logos/d5e69850-ba31-4e28-bb99-f97468bd72c5/1731779803_tigo-logo.png',
        ],
        'dea3f6fe-d906-414c-b4b3-d72f729492f8' => [
            'name' => 'Cervecería Boliviana Nacional S.A.',
            'logoUrl' => 'http://localhost:8000/storage/company-logos/dea3f6fe-d906-414c-b4b3-d72f729492f8/1731779804_cbn-logo.png',
        ],
    ];

    public function run(): void
    {
        echo "\n╔════════════════════════════════════════════════════════╗\n";
        echo "║  Actualizando URLs de Logos - Empresas Bolivianas      ║\n";
        echo "║  (SIN ELIMINAR DATOS EXISTENTES)                       ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";

        $updated = 0;
        $failed = 0;

        foreach ($this->bolivianCompanies as $companyId => $data) {
            $company = Company::find($companyId);

            if (!$company) {
                echo "⚠️  Empresa no encontrada (ID: {$companyId})\n";
                $failed++;
                continue;
            }

            echo "📦 {$data['name']}\n";
            echo "   ID: {$company->id}\n";

            // Actualizar solo el logo_url
            $company->update(['logo_url' => $data['logoUrl']]);

            echo "   ✅ Logo URL actualizada\n";
            echo "   📍 {$data['logoUrl']}\n\n";
            $updated++;
        }

        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║ ✅ COMPLETADO                                          ║\n";
        echo "║    Actualizadas: {$updated}                                      ║\n";
        echo "║    Fallidas: {$failed}                                          ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";
    }
}
