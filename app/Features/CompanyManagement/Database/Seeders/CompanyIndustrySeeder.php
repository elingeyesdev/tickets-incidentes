<?php

namespace App\Features\CompanyManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyIndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industries = [
            [
                'code' => 'technology',
                'name' => 'Tecnología',
                'description' => 'Desarrollo de software, IT, SaaS',
            ],
            [
                'code' => 'healthcare',
                'name' => 'Salud',
                'description' => 'Hospitales, clínicas, servicios médicos',
            ],
            [
                'code' => 'education',
                'name' => 'Educación',
                'description' => 'Escuelas, universidades, capacitación',
            ],
            [
                'code' => 'finance',
                'name' => 'Finanzas',
                'description' => 'Bancos, seguros, inversiones',
            ],
            [
                'code' => 'retail',
                'name' => 'Comercio',
                'description' => 'Tiendas, e-commerce, minoristas',
            ],
            [
                'code' => 'manufacturing',
                'name' => 'Manufactura',
                'description' => 'Producción, fabricación industrial',
            ],
            [
                'code' => 'real_estate',
                'name' => 'Bienes Raíces',
                'description' => 'Inmobiliarias, construcción',
            ],
            [
                'code' => 'hospitality',
                'name' => 'Hospitalidad',
                'description' => 'Hoteles, restaurantes, turismo',
            ],
            [
                'code' => 'transportation',
                'name' => 'Transporte',
                'description' => 'Logística, delivery, movilidad',
            ],
            [
                'code' => 'professional_services',
                'name' => 'Servicios Profesionales',
                'description' => 'Consultoría, legal, contabilidad',
            ],
            [
                'code' => 'media',
                'name' => 'Medios',
                'description' => 'Publicidad, marketing, comunicaciones',
            ],
            [
                'code' => 'energy',
                'name' => 'Energía',
                'description' => 'Electricidad, petróleo, renovables',
            ],
            [
                'code' => 'agriculture',
                'name' => 'Agricultura',
                'description' => 'Cultivos, ganadería, agroindustria',
            ],
            [
                'code' => 'government',
                'name' => 'Gobierno',
                'description' => 'Entidades públicas, municipios',
            ],
            [
                'code' => 'non_profit',
                'name' => 'ONGs',
                'description' => 'Organizaciones sin fines de lucro',
            ],
            [
                'code' => 'other',
                'name' => 'Otros',
                'description' => 'Industrias no clasificadas',
            ],
        ];

        foreach ($industries as $industry) {
            DB::table('business.company_industries')->insert([
                'code' => $industry['code'],
                'name' => $industry['name'],
                'description' => $industry['description'],
                'created_at' => now(),
            ]);
        }

        $this->command->info('✅ Successfully seeded ' . count($industries) . ' company industries');
    }
}
