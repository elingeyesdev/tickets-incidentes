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
        // Get existing demo users (created by DemoUsersSeeder)
        $platformAdmin = User::where('email', 'admin@helpdesk.com')->first();
        $companyAdmin1 = User::where('email', 'company-admin@techsolutions.com')->first();
        $companyAdmin2 = User::where('email', 'company-admin@innovatesoft.com')->first();

        // Create 5 demo companies with specific data
        $companies = [
            [
                'name' => 'Tech Solutions Inc.',
                'legal_name' => 'Tech Solutions Incorporated SRL',
                'support_email' => 'support@techsolutions.com',
                'website' => 'https://techsolutions.com',
                'contact_city' => 'Santa Cruz de la Sierra',
                'contact_country' => 'Bolivia',
                'admin_user_id' => $companyAdmin1->id,
            ],
            [
                'name' => 'Innovate Soft',
                'legal_name' => 'Innovate Software Solutions SRL',
                'support_email' => 'support@innovatesoft.com',
                'website' => 'https://innovatesoft.com',
                'contact_city' => 'La Paz',
                'contact_country' => 'Bolivia',
                'admin_user_id' => $companyAdmin2->id,
            ],
        ];

        foreach ($companies as $companyData) {
            Company::factory()->create($companyData);
        }

        // Create 3 more random companies
        Company::factory()->count(3)->create();

        // Create 1 suspended company
        Company::factory()->suspended()->create();

        // Create some pending company requests
        CompanyRequest::factory()->count(3)->create();

        // Create 1 approved and 1 rejected request
        CompanyRequest::factory()->approved()->create();
        CompanyRequest::factory()->rejected()->create();

        // Create some followers (users following companies)
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
                    // Skip duplicates
                    continue;
                }
            }
        }

        $this->command->info('✅ Demo companies, requests, and followers created successfully!');
    }
}
