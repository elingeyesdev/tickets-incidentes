<?php

namespace App\Features\TicketManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeder de categorías por defecto
 *
 * Crea categorías estándar para cada empresa existente.
 * Útil para inicializar el sistema con categorías comunes.
 */
class DefaultCategoriesSeeder extends Seeder
{
    /**
     * Categorías por defecto que se crearán para cada empresa
     */
    protected array $defaultCategories = [
        [
            'name' => 'Soporte Técnico',
            'description' => 'Problemas técnicos con el sistema, errores, bugs y dificultades de acceso',
            'is_active' => true,
        ],
        [
            'name' => 'Facturación',
            'description' => 'Consultas sobre pagos, facturas, suscripciones y aspectos financieros',
            'is_active' => true,
        ],
        [
            'name' => 'Cuenta y Perfil',
            'description' => 'Gestión de cuenta de usuario, cambios de información personal y configuración',
            'is_active' => true,
        ],
        [
            'name' => 'Reportes y Analíticas',
            'description' => 'Consultas sobre reportes, métricas, exportación de datos y análisis',
            'is_active' => true,
        ],
        [
            'name' => 'General',
            'description' => 'Consultas generales que no encajan en otras categorías',
            'is_active' => true,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📂 Creating default categories for all companies...');

        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found. Skipping category creation.');
            return;
        }

        $totalCreated = 0;

        foreach ($companies as $company) {
            foreach ($this->defaultCategories as $categoryData) {
                // Verificar si la categoría ya existe para esta empresa
                $exists = Category::where('company_id', $company->id)
                    ->where('name', $categoryData['name'])
                    ->exists();

                if (!$exists) {
                    Category::create([
                        'company_id' => $company->id,
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'is_active' => $categoryData['is_active'],
                    ]);

                    $totalCreated++;
                }
            }
        }

        $this->command->info("✅ Created {$totalCreated} categories for {$companies->count()} companies");
    }
}
