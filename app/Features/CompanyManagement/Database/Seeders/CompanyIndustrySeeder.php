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
                'description' => 'Seguros, inversiones',
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
                'description' => 'Inmobiliarias, arrendamiento',
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
                'code' => 'telecommunications',
                'name' => 'Telecomunicaciones',
                'description' => 'Operadores de telefonía móvil/fija, ISPs y servicios de telecom',
            ],
            [
                'code' => 'food_and_beverage',
                'name' => 'Alimentos y Bebidas',
                'description' => 'Productores, procesadores y distribuidores de alimentos y bebidas',
            ],
            [
                'code' => 'pharmacy',
                'name' => 'Farmacéutica / Farmacias',
                'description' => 'Cadenas de farmacias, distribución farmacéutica y productos de salud',
            ],
            [
                'code' => 'electronics',
                'name' => 'Electrónica y Hardware',
                'description' => 'Tiendas y distribuidores de equipos electrónicos, componentes y hardware',
            ],
            [
                'code' => 'banking',
                'name' => 'Banca',
                'description' => 'Bancos comerciales y servicios bancarios',
            ],
            [
                'code' => 'supermarket',
                'name' => 'Supermercado',
                'description' => 'Cadenas de supermercados y tiendas de abarrotes',
            ],
            [
                'code' => 'veterinary',
                'name' => 'Veterinaria',
                'description' => 'Clínicas veterinarias, servicios de cuidado animal y tiendas para mascotas',
            ],
            [
                'code' => 'insurance',
                'name' => 'Seguros',
                'description' => 'Seguros de vida, seguros comerciales, seguros de salud y pólizas especializadas',
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
                'code' => 'construction',
                'name' => 'Construcción',
                'description' => 'Empresas constructoras, obras civiles, proyectos inmobiliarios',
            ],
            [
                'code' => 'environment',
                'name' => 'Medio Ambiente',
                'description' => 'Consultorías ambientales, reciclaje, energías renovables, ONGs ambientales',
            ],
            [
                'code' => 'other',
                'name' => 'Otros',
                'description' => 'Industrias no clasificadas',
            ],
        ];

        foreach ($industries as $industry) {
            \App\Features\CompanyManagement\Models\CompanyIndustry::updateOrCreate(
                ['code' => $industry['code']],
                [
                    'name' => $industry['name'],
                    'description' => $industry['description'],
                ]
            );
        }

        $this->command->info('✅ Successfully seeded ' . count($industries) . ' company industries');
    }
}
