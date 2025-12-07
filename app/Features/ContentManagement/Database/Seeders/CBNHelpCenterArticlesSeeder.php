<?php

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use Illuminate\Database\Seeder;

/**
 * Cervecería Boliviana Nacional Help Center Articles Seeder
 */
class CBNHelpCenterArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Cervecería Boliviana Nacional S.A.')->first();

        if (!$company) {
            $this->command->error('Cervecería Boliviana Nacional S.A. company not found.');
            return;
        }

        $category = ArticleCategory::first();

        if (!$category) {
            $this->command->error('No article categories available.');
            return;
        }

        $this->command->info('Creating Help Center articles for CBN...');

        $authorId = $company->admin_user_id;

        $articles = [
            [
                'title' => 'Historia y tradición de CBN',
                'excerpt' => 'Conozca la historia de Cervecería Boliviana Nacional',
                'content' => "## Historia de Cervecería Boliviana Nacional\n\n### Orígenes\n\nCervecería Boliviana Nacional fue fundada en **1877** en La Paz, convirtiéndose en la empresa cervecera más antigua de Bolivia.\n\n### Hitos Importantes\n\n**1877** - Fundación en La Paz\n**1950** - Creación de la marca **Paceña**\n**1980** - Introducción de **Huari** en el mercado\n**2000** - Expansión a Cochabamba\n**2015** - Innovación en línea de cervezas artesanales\n\n### Marcas Principales\n\n#### Paceña\n- Cerveza clásica de pilsner\n- 4.8% alcohol\n- Producida desde 1950\n- Icono de identidad boliviana\n\n#### Huari\n- Cerveza oscura (Munich Dunkel)\n- 5.2% alcohol\n- Sabor robusto y maltoso\n- Preferida en épocas de frío\n\n#### Nuevas Líneas\n- Cervezas artesanales\n- Cerveza sin alcohol\n- Cervezas con sabores regionales\n\n### Compromiso Actual\n\n✓ Calidad premium\n✓ Ingredientes seleccionados\n✓ Proceso tradicional\n✓ Innovación constante\n✓ Sostenibilidad ambiental\n\n### Presencia en Bolivia\n\n- Plantas en: La Paz, Cochabamba, Santa Cruz\n- Distribución nacional\n- Exportación a países vecinos",
            ],
            [
                'title' => 'Información nutricional de bebidas CBN',
                'excerpt' => 'Detalles nutricionales de nuestras bebidas',
                'content' => "## Información Nutricional CBN\n\n### Cerveza Paceña (350ml)\n\n**Composición:**\n- Energía: 148 kcal\n- Carbohidratos: 10g\n- Proteína: 1.5g\n- Sodio: 20mg\n- % Alcohol: 4.8%\n\n### Cerveza Huari (350ml)\n\n**Composición:**\n- Energía: 162 kcal\n- Carbohidratos: 12g\n- Proteína: 1.8g\n- Sodio: 25mg\n- % Alcohol: 5.2%\n\n### Cerveza Sin Alcohol (350ml)\n\n**Composición:**\n- Energía: 98 kcal\n- Carbohidratos: 8g\n- Proteína: 0.5g\n- Sodio: 15mg\n- % Alcohol: 0.0%\n\n### Ingredientes Principales\n\n- **Malta de cebada**: Base principal\n- **Lúpulos**: Para sabor y aroma\n- **Levadura**: Para fermentación\n- **Agua**: Destilada y filtrada\n- **Conservantes naturales**: Dióxido de carbono\n\n### Información Importante\n\n⚠️ Contiene gluten (derivado de cebada)\n⚠️ No recomendado durante embarazo\n⚠️ Consumo responsable recomendado\n✓ Apta para mayores de 18 años\n✓ Almacenar en lugar fresco\n✓ Consumir antes de la fecha de vencimiento",
            ],
            [
                'title' => 'Responsabilidad social y sustentabilidad',
                'excerpt' => 'Nuestro compromiso con el ambiente y la comunidad',
                'content' => "## Responsabilidad Social CBN\n\n### Compromiso Ambiental\n\n#### Reducción de Residuos\n- Botellas 100% reciclables\n- Programa de devolución de envases\n- Reducción de plástico en empaques\n- Tratamiento de aguas residuales\n\n#### Energías Limpias\n- Instalación de paneles solares\n- Reducción de consumo energético\n- Eficiencia en procesos productivos\n- Auditorías ambientales periódicas\n\n#### Gestión del Agua\n- Sistema de recirculación\n- Tratamiento antes de descargar\n- Conservación de recursos hídricos\n- Monitoreo de calidad\n\n### Responsabilidad Social\n\n#### Empleabilidad\n- Generación de 2,000+ empleos directos\n- Capacitación continua\n- Condiciones laborales seguras\n- Oportunidades de crecimiento\n\n#### Comunidad\n- Apoyo a emprendimientos locales\n- Programas de educación\n- Patrocinio de eventos culturales\n- Donaciones a organizaciones sociales\n\n#### Consumo Responsable\n- Campañas contra conducción bajo efecto\n- Promoción de consumo moderado\n- Información sobre productos\n- Apoyo a programas de salud\n\n### Certificaciones\n\n✓ ISO 9001 (Calidad)\n✓ ISO 14001 (Ambiental)\n✓ OHSAS 18001 (Seguridad)\n✓ Estándares internacionales de calidad",
            ],
        ];

        foreach ($articles as $articleData) {
            try {
                // [IDEMPOTENCY] Use firstOrCreate to prevent duplicates
                HelpCenterArticle::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'title' => $articleData['title'],
                    ],
                    [
                        'category_id' => $category->id,
                        'author_id' => $authorId,
                        'excerpt' => $articleData['excerpt'],
                        'content' => $articleData['content'],
                        'status' => 'PUBLISHED',
                        'views_count' => rand(0, 100),
                        'published_at' => now(),
                    ]
                );

                $this->command->info("  ✓ Article: {$articleData['title']}");
            } catch (\Exception $e) {
                $this->command->warn("  ⚠ Error: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ CBN articles created!');
    }
}
