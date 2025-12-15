<?php

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use Illuminate\Database\Seeder;

/**
 * PIL Andina Help Center Articles Seeder (Updated)
 *
 * Crea artículos del Help Center para PIL Andina S.A. (Food & Beverage)
 * - 12 artículos totales
 * - 3 artículos por categoría (ACCOUNT_PROFILE, SECURITY_PRIVACY, BILLING_PAYMENTS, TECHNICAL_SUPPORT)
 * - Fechas entre enero-noviembre 2025
 * - 80% publicados, 20% en draft
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

        // [IDEMPOTENCY] Verificar si ya existen artículos
        if (HelpCenterArticle::where('company_id', $company->id)->exists()) {
            $this->command->info('✓ Artículos ya existen para PIL Andina. Saltando...');
            return;
        }

        // Cargar las 4 categorías globales
        $categories = [
            'ACCOUNT_PROFILE' => ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first(),
            'SECURITY_PRIVACY' => ArticleCategory::where('code', 'SECURITY_PRIVACY')->first(),
            'BILLING_PAYMENTS' => ArticleCategory::where('code', 'BILLING_PAYMENTS')->first(),
            'TECHNICAL_SUPPORT' => ArticleCategory::where('code', 'TECHNICAL_SUPPORT')->first(),
        ];

        if (in_array(null, $categories)) {
            $this->command->error('No article categories available.');
            return;
        }

        $this->command->info('📚 Creando artículos Help Center para PIL Andina...');

        $authorId = $company->admin_user_id;

        $articles = [
            // JULIO 2025 (1 artículo)
            [
                'category' => 'SECURITY_PRIVACY',
                'title' => 'Política de privacidad de datos de clientes',
                'excerpt' => 'Cómo PIL Andina protege y utiliza la información personal de clientes y proveedores',
                'content' => "Políticas de protección de datos",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(5)->setDay(15)->setTime(9, 0),
                'published_at' => now()->subMonths(5)->setDay(20)->setTime(8, 0),
                'views_count' => 125,
            ],

            // AGOSTO 2025 (2 artículos)
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => 'Información nutricional de productos PIL Andina',
                'excerpt' => 'Composición nutricional detallada de leche, yogur, quesos y otros lácteos PIL',
                'content' => "Tabla nutricional completa de productos PIL Andina",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(4)->setDay(8)->setTime(11, 0),
                'published_at' => now()->subMonths(4)->setDay(12)->setTime(8, 30),
                'views_count' => 98,
            ],
            [
                'category' => 'ACCOUNT_PROFILE',
                'title' => 'Cómo registrarme como distribuidor PIL Andina',
                'excerpt' => 'Requisitos y proceso para convertirse en distribuidor autorizado de productos PIL',
                'content' => "Guía de registro para distribuidores",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(4)->setDay(22)->setTime(9, 20),
                'published_at' => now()->subMonths(4)->setDay(25)->setTime(9, 0),
                'views_count' => 87,
            ],

            // SEPTIEMBRE 2025 (3 artículos)
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => '¿Cómo reportar problemas de calidad en productos PIL?',
                'excerpt' => 'Guía para reportar defectos, problemas de sabor o empaque en nuestros productos lácteos',
                'content' => "Procedimiento para reportar problemas con productos PIL Andina",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(3)->setDay(5)->setTime(10, 30),
                'published_at' => now()->subMonths(3)->setDay(8)->setTime(9, 0),
                'views_count' => 92,
            ],
            [
                'category' => 'BILLING_PAYMENTS',
                'title' => '¿Cómo entender mi factura de PIL Andina?',
                'excerpt' => 'Desglose de conceptos, impuestos y cargos en facturas de productos lácteos',
                'content' => "Explicación detallada de facturación",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(3)->setDay(15)->setTime(13, 30),
                'published_at' => now()->subMonths(3)->setDay(18)->setTime(9, 15),
                'views_count' => 78,
            ],
            [
                'category' => 'SECURITY_PRIVACY',
                'title' => 'Certificaciones de inocuidad y calidad alimentaria',
                'excerpt' => 'ISO 22000, HACCP y certificaciones de seguridad alimentaria de PIL Andina',
                'content' => "Certificaciones y estándares de calidad",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(3)->setDay(25)->setTime(10, 15),
                'published_at' => now()->subMonths(3)->setDay(28)->setTime(9, 30),
                'views_count' => 84,
            ],

            // OCTUBRE 2025 (3 artículos)
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => '¿Qué hacer si mi producto llegó en mal estado?',
                'excerpt' => 'Pasos a seguir cuando recibe un producto dañado, vencido o con defectos',
                'content' => "Procedimiento de reemplazo y devolución de productos",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(2)->setDay(3)->setTime(14, 15),
                'published_at' => now()->subMonths(2)->setDay(6)->setTime(10, 0),
                'views_count' => 66,
            ],
            [
                'category' => 'BILLING_PAYMENTS',
                'title' => 'Métodos de pago disponibles para distribuidores',
                'excerpt' => 'Transferencias bancarias, cheques y convenios de pago para órdenes al por mayor',
                'content' => "Opciones de pago para distribuidores",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(2)->setDay(12)->setTime(11, 20),
                'published_at' => now()->subMonths(2)->setDay(15)->setTime(10, 0),
                'views_count' => 62,
            ],
            [
                'category' => 'ACCOUNT_PROFILE',
                'title' => 'Actualizar datos de contacto de mi empresa',
                'excerpt' => 'Cómo modificar dirección, teléfono o email de contacto en el portal de distribuidores',
                'content' => "Procedimiento para actualizar información empresarial",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(2)->setDay(22)->setTime(15, 10),
                'published_at' => now()->subMonths(2)->setDay(25)->setTime(10, 30),
                'views_count' => 54,
            ],

            // NOVIEMBRE 2025 (2 artículos)
            [
                'category' => 'ACCOUNT_PROFILE',
                'title' => 'Gestión de usuarios en portal de proveedores',
                'excerpt' => 'Agregar, editar o eliminar usuarios con acceso al sistema de órdenes PIL Andina',
                'content' => "Administración de usuarios del portal",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(1)->setDay(8)->setTime(10, 45),
                'published_at' => now()->subMonths(1)->setDay(12)->setTime(11, 0),
                'views_count' => 38,
            ],
            [
                'category' => 'BILLING_PAYMENTS',
                'title' => 'Solicitar crédito comercial con PIL Andina',
                'excerpt' => 'Requisitos, documentos y proceso para obtener línea de crédito como distribuidor',
                'content' => "Proceso de solicitud de crédito",
                'status' => 'PUBLISHED',
                'created_at' => now()->subMonths(1)->setDay(20)->setTime(14, 0),
                'published_at' => now()->subMonths(1)->setDay(24)->setTime(9, 0),
                'views_count' => 42,
            ],

            // DICIEMBRE 2025 (1 artículo DRAFT)
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => '¿Dónde puedo comprar productos PIL Andina?',
                'excerpt' => 'Puntos de venta autorizados, distribuidores y tiendas online donde adquirir productos PIL',
                'content' => "Directorio de puntos de venta PIL Andina",
                'status' => 'DRAFT',
                'created_at' => now()->setDay(5)->setTime(16, 45),
                'published_at' => null,
                'views_count' => 0,
            ],
        ];

        foreach ($articles as $articleData) {
            try {
                HelpCenterArticle::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'title' => $articleData['title'],
                    ],
                    [
                        'category_id' => $categories[$articleData['category']]->id,
                        'author_id' => $authorId,
                        'excerpt' => $articleData['excerpt'],
                        'content' => $articleData['content'],
                        'status' => $articleData['status'],
                        'views_count' => $articleData['views_count'],
                        'created_at' => $articleData['created_at'],
                        'published_at' => $articleData['published_at'],
                    ]
                );

                $this->command->info("  ✓ {$articleData['title']}");
            } catch (\Exception $e) {
                $this->command->warn("  ⚠ Error: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ PIL Andina articles created!');
    }
}
