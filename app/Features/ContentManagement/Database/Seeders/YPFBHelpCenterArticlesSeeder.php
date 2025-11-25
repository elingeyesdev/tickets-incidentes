<?php

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use Illuminate\Database\Seeder;

/**
 * YPFB Help Center Articles Seeder
 *
 * Creates sample help center articles for YPFB with:
 * - Environmental incident reporting
 * - Gas and oil operations information
 * - Employment and development programs
 */
class YPFBHelpCenterArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'YPFB Corporación')->first();

        if (!$company) {
            $this->command->error('YPFB Corporación company not found.');
            return;
        }

        $category = ArticleCategory::first();

        if (!$category) {
            $this->command->error('No article categories available.');
            return;
        }

        $this->command->info('Creating Help Center articles for YPFB...');

        $authorId = $company->admin_user_id;

        $articles = [
            [
                'title' => 'Reportar derrames o accidentes ambientales',
                'excerpt' => 'Procedimiento para reportar incidentes ambientales',
                'content' => "## Reporte de Derrames y Accidentes Ambientales\n\n### ¿Qué es un Derrame?\n\nUna liberación no controlada de petróleo, gas natural o derivados al ambiente.\n\n### Cómo Reportar\n\n#### 1. Llamada Inmediata\n- **Línea de Emergencia**: +591-2-2106565\n- **Centro de Control**: Disponible 24/7\n- Proporciona ubicación exacta\n\n#### 2. Información Requerida\n- Ubicación del derrame (coordenadas GPS si es posible)\n- Tipo de producto derramado\n- Volumen estimado\n- Condiciones climáticas\n- Fotos o videos (si es seguro obtenerlos)\n\n#### 3. Equipo de Respuesta\nNuestro equipo:\n- Evalúa el incidente\n- Inicia contención\n- Documenta los daños\n- Informa a autoridades\n\n### Protocolo de Seguridad\n\n✓ NO acercarse directamente al derrame\n✓ Mantener distancia segura\n✓ Avisar a personas cercanas\n✓ Esperar instrucciones de personal especializado",
            ],
            [
                'title' => 'Información sobre operaciones de gas y petróleo',
                'excerpt' => 'Conozca las operaciones principales de YPFB',
                'content' => "## Operaciones de Gas y Petróleo en Bolivia\n\n### Campos Productivos Principales\n\n#### 1. Campos de Gas Natural\n- **Ubicación**: Departamento de Tarija\n- **Reservas**: 10.6 TCF (Trillion Cubic Feet)\n- **Producción**: Exportación a Argentina y Brasil\n\n#### 2. Campos de Petróleo\n- **Ubicación**: Departamento de Santa Cruz\n- **Reservas**: 430 millones de barriles\n- **Producción**: Abastecimiento interno y exportación\n\n### Cadena de Producción\n\n1. **Exploración**: Búsqueda de nuevas reservas\n2. **Explotación**: Extracción de recursos\n3. **Transporte**: Oleoductos y gasoductos\n4. **Refinación**: Procesamiento en refinerías\n5. **Distribución**: A mercados internos y externos\n\n### Impacto Económico\n\n- Generación de empleos\n- Ingresos fiscales\n- Desarrollo regional\n- Infraestructura\n\n### Compromiso Ambiental\n\nYPFB se compromete a:\n- Minimizar impacto ambiental\n- Cumplir regulaciones ambientales\n- Restaurar ecosistemas\n- Capacitación en seguridad",
            ],
            [
                'title' => 'Programas de empleo y desarrollo en YPFB',
                'excerpt' => 'Oportunidades laborales y capacitación profesional',
                'content' => "## Empleo y Desarrollo en YPFB\n\n### Oportunidades Laborales\n\n#### Posiciones Técnicas\n- Ingenieros de Petróleo\n- Ingenieros Civiles\n- Técnicos en Operaciones\n- Especialistas en Seguridad\n\n#### Posiciones Administrativas\n- Contadores\n- Analistas de Sistemas\n- Especialistas en Recursos Humanos\n- Abogados\n\n### Requisitos Generales\n\n✓ Título profesional\n✓ Experiencia según puesto (2-5 años)\n✓ Examen médico\n✓ Antecedentes limpios\n✓ Disponibilidad para trabajar en zonas remotas\n\n### Programa de Capacitación\n\n**YPFB ofrece:**\n- Formación técnica inicial\n- Capacitación continua\n- Programas de especialización\n- Becas para postgrados\n- Intercambios profesionales\n\n### Beneficios\n\n- Salario competitivo\n- Seguro de salud\n- Bonificaciones por desempeño\n- Oportunidades de ascenso\n- Ambiente laboral seguro",
            ],
        ];

        foreach ($articles as $articleData) {
            try {
                HelpCenterArticle::create([
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'author_id' => $authorId,
                    'title' => $articleData['title'],
                    'excerpt' => $articleData['excerpt'],
                    'content' => $articleData['content'],
                    'status' => 'PUBLISHED',
                    'views_count' => rand(0, 100),
                    'published_at' => now(),
                ]);

                $this->command->info("  ✓ Article: {$articleData['title']}");
            } catch (\Exception $e) {
                $this->command->warn("  ⚠ Error: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ YPFB articles created!');
    }
}
