<?php

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Seeder;

class DemoCompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios demo existentes (creados por DemoUsersSeeder)
        $platformAdmin = User::where('email', 'admin@helpdesk.com')->first();

        // Usar platform admin como company admin para propósitos de demo
        // En un escenario real, cada empresa tendría su propio admin creado via CompanyRequestService
        if (!$platformAdmin) {
            $this->command->warn('⚠️  Demo users not found. Run DemoUsersSeeder first.');
            return;
        }

        // Crear 2 empresas demo con datos específicos
        $companies = [
            [
                'name' => 'Tech Solutions Inc.',
                'legal_name' => 'Tech Solutions Incorporated SRL',
                'support_email' => 'support@techsolutions.com',
                'website' => 'https://techsolutions.com',
                'contact_city' => 'Santa Cruz de la Sierra',
                'contact_country' => 'Bolivia',
                'admin_user_id' => $platformAdmin->id, // Usando platform admin para demo
            ],
            [
                'name' => 'Innovate Soft',
                'legal_name' => 'Innovate Software Solutions SRL',
                'support_email' => 'support@innovatesoft.com',
                'website' => 'https://innovatesoft.com',
                'contact_city' => 'La Paz',
                'contact_country' => 'Bolivia',
                'admin_user_id' => $platformAdmin->id, // Usando platform admin para demo
            ],
        ];

        foreach ($companies as $companyData) {
            Company::factory()->create($companyData);
        }

        // Crear 3 empresas aleatorias más
        Company::factory()->count(3)->create();

        // Crear 1 empresa suspendida
        Company::factory()->suspended()->create();

        // Crear algunas solicitudes de empresa pendientes
        CompanyRequest::factory()->count(3)->create();

        // Crear 1 solicitud aprobada y 1 rechazada
        CompanyRequest::factory()->approved()->create();
        CompanyRequest::factory()->rejected()->create();

        // Crear algunos seguidores (usuarios siguiendo empresas)
        $users = User::limit(5)->get();
        $allCompanies = Company::active()->limit(3)->get();

        foreach ($users as $user) {
            foreach ($allCompanies->random(2) as $company) {
                try {
                    CompanyFollower::create([
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    // Omitir duplicados
                    continue;
                }
            }
        }

        $this->command->info('✅ Demo companies, requests, and followers created successfully!');
    }
}
