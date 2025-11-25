<?php

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use Illuminate\Database\Seeder;

/**
 * PIL Andina Help Center Articles Seeder
 */
class PilAndinaHelpCenterArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'PIL Andina S.A.')->first();

        if (!$company) {
            $this->command->error('PIL Andina S.A. company not found.');
            return;
        }

        $category = ArticleCategory::first();

        if (!$category) {
            $this->command->error('No article categories available.');
            return;
        }

        $this->command->info('Creating Help Center articles for PIL Andina...');

        $authorId = $company->admin_user_id;

        $articles = [
            [
                'title' => 'Cómo reportar problemas con productos PIL',
                'excerpt' => 'Guía paso a paso para reportar problemas de calidad o defectos en nuestros productos',
                'content' => "## Reportar Problemas con Productos PIL\n\n### Paso 1: Información del Producto\nAntes de reportar, ten a mano:\n- Número de lote del producto\n- Fecha de vencimiento\n- Descripción detallada del problema\n- Fecha de compra\n\n### Paso 2: Contacto Disponible\nPuedes reportar de las siguientes formas:\n1. **Teléfono**: +591 44260164\n2. **Email**: soporte@pilandina.com.bo\n3. **Portal Web**: https://pilandina.com.bo\n\n### Paso 3: Información Requerida\nNos proporcionarás:\n- Tus datos de contacto completos\n- Descripción del problema\n- Fotos del producto (si es posible)\n- Comprobante de compra\n\n### Paso 4: Seguimiento\nTe proporcionaremos un número de ticket para seguimiento del caso.\nTiempo de respuesta: 24-48 horas hábiles.\n\n## Tipos de Problemas Comunes\n\n- **Defecto de envase**: Fugas o daños en la presentación\n- **Problemas de sabor**: Cambio anormal en sabor u olor\n- **Textura anormal**: Separación o cambios físicos\n- **Defectos de empaque**: Etiquetas dañadas o información incorrecta",
            ],
            [
                'title' => 'Preguntas frecuentes sobre productos PIL',
                'excerpt' => 'Respuestas a las preguntas más comunes sobre nuestros productos lácteos',
                'content' => "## Preguntas Frecuentes PIL\n\n### ¿Cuál es la vida útil de los productos PIL?\n\nNuestros productos tienen diferentes fechas de vencimiento según el tipo:\n- **Leche fresca**: 7-10 días refrigerada\n- **Leche larga vida**: 6 meses sin refrigeración\n- **Yogur**: 30 días refrigerado\n- **Quesos**: 60-90 días refrigerados\n\n### ¿Cómo almacenar correctamente los productos?\n\n1. Mantener en lugar fresco y seco\n2. Refrigerar inmediatamente después de comprar\n3. No exponer a luz solar directa\n4. Respetar fechas de vencimiento\n\n### ¿Son productos naturales?\n\nSí, utilizamos leche de calidad con ingredientes naturales. No contienen conservantes artificiales añadidos.\n\n### ¿Dónde puedo comprar productos PIL?\n\nNuestros productos están disponibles en:\n- Supermercados principales\n- Tiendas de abarrotes\n- Distribuidoras de lácteos\n- Plataformas de compra online",
            ],
            [
                'title' => 'Información nutricional de productos PIL',
                'excerpt' => 'Detalles nutricionales y recomendaciones de consumo',
                'content' => "## Información Nutricional PIL\n\n### Leche Fresca PIL\n\n**Composición por 100ml:**\n- Energía: 61 kcal\n- Proteína: 3.2g\n- Grasa: 3.6g\n- Carbohidratos: 4.8g\n- Calcio: 120mg\n- Vitamina A: 40 UI\n\n### Beneficios de Consumir Lácteos\n\n1. **Fortalecimiento óseo**: Alto contenido de calcio\n2. **Desarrollo muscular**: Proteínas de calidad\n3. **Inmunidad**: Vitaminas y minerales esenciales\n4. **Salud digestiva**: Contiene lactobacilos beneficiosos\n\n### Recomendaciones de Consumo\n\n- Niños (4-8 años): 2 tazas diarias\n- Niños (9-13 años): 3 tazas diarias\n- Adultos: 3 tazas diarias\n- Embarazadas: 4 tazas diarias\n\n### Alergias e Intolerancias\n\nContiene **lactosa y proteínas de leche de vaca**. Si tienes intolerancia a la lactosa, consulta nuestros productos deslactosados.",
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

        $this->command->info('✅ PIL Andina articles created!');
    }
}
