<?php

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use Illuminate\Database\Seeder;

/**
 * Tigo Help Center Articles Seeder
 */
class TigoHelpCenterArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Tigo Bolivia S.A.')->first();

        if (!$company) {
            $this->command->error('Tigo Bolivia S.A. company not found.');
            return;
        }

        $category = ArticleCategory::first();

        if (!$category) {
            $this->command->error('No article categories available.');
            return;
        }

        $this->command->info('Creating Help Center articles for Tigo...');

        $authorId = $company->admin_user_id;

        $articles = [
            [
                'title' => 'Cómo contratar servicios Tigo',
                'excerpt' => 'Guía para contratar internet, celular o televisión',
                'content' => "## Cómo Contratar Servicios Tigo\n\n### Opciones de Contratación\n\n#### 1. En Línea\n- Entra a www.tigo.com.bo\n- Selecciona tu plan\n- Completa tus datos\n- Realiza el pago\n- Activación en 24 horas\n\n#### 2. En Tienda Tigo\n- Visita la sucursal más cercana\n- Elige tu plan con un asesor\n- Completa documentos\n- Realiza el pago\n- Recibe tu SIM o equipo\n\n#### 3. Por Teléfono\n- Llama al +591-800-17-5000\n- Indica el servicio deseado\n- El asesor gestiona tu contrato\n- Pago por transferencia bancaria\n\n### Planes Disponibles\n\n#### Internet Fijo\n- 10 Mbps - Bs. 99\n- 30 Mbps - Bs. 149\n- 50 Mbps - Bs. 199\n- 100 Mbps - Bs. 299\n\n#### Planes Celulares\n- Prepago: Recarga desde Bs. 10\n- Postpago: Desde Bs. 99 mensuales\n- Planes ejecutivos: A medida\n\n#### Televisión\n- Plan Básico: 80 canales\n- Plan Plus: 150 canales\n- Plan Premium: 200+ canales + películas\n\n### Documentos Requeridos\n\n- Cédula de identidad\n- Comprobante de domicilio\n- Comprobante de ingresos (para postpago)",
            ],
            [
                'title' => 'Solución de problemas de conexión',
                'excerpt' => 'Pasos para resolver problemas comunes de internet',
                'content' => "## Solución de Problemas de Conexión\n\n### Problema: Internet Lento\n\n**Paso 1: Reinicia el módem**\n- Desconecta el cable de poder\n- Espera 30 segundos\n- Vuelve a conectar\n- Espera 2 minutos\n\n**Paso 2: Verifica la distancia**\n- Coloca el módem en posición central\n- Evita obstáculos grandes\n- Mantén alejado de otros aparatos electrónicos\n\n**Paso 3: Revisa el número de dispositivos**\n- Desconecta dispositivos que no uses\n- Cierra aplicaciones que consumen datos\n- Usa ethernet para mayor velocidad\n\n### Problema: Sin Conexión\n\n**Paso 1: Verifica las luces del módem**\n- Luz roja = Sin señal de Tigo\n- Luz amarilla = Conectando\n- Luz verde = Conectado\n\n**Paso 2: Comprueba el cable**\n- Verifica conexiones físicas\n- Reemplaza cable si está dañado\n- Prueba en otro puerto\n\n**Paso 3: Reinicia el módem**\n(Ver pasos anteriores)\n\n### Problema: Conexión Inestable\n\n**Paso 1: Actualiza el firmware**\n- Accede a 192.168.1.1\n- Busca actualización disponible\n- Descarga e instala\n\n**Paso 2: Cambia la frecuencia WiFi**\n- En configuración del módem\n- Intenta frecuencia 2.4GHz o 5GHz\n\n**Paso 3: Contacta a soporte**\n- Si persiste el problema\n- Llama al +591-800-17-5000",
            ],
            [
                'title' => 'Información sobre tarifas y promociones',
                'excerpt' => 'Conozca nuestras promociones actuales y beneficios',
                'content' => "## Tarifas y Promociones Tigo\n\n### Promociones Vigentes\n\n#### Promoción Internet + Celular\n- **50 Mbps + Plan Celular 5GB**\n- Bs. 249 mensuales\n- 2 primeros meses: Bs. 149\n- Vigencia: Hasta 31 de diciembre\n\n#### Promoción Triple Play\n- **Internet 30Mbps + TV + Celular**\n- Bs. 349 mensuales\n- Instalación gratuita\n- Router WiFi 6 incluido\n\n#### Descuentos por Fidelidad\n- 1 año: 5% descuento\n- 2 años: 10% descuento\n- 3+ años: 15% descuento\n\n### Beneficios Adicionales\n\n✓ Instalación y configuración gratis\n✓ Modem inalámbrico incluido\n✓ Llamadas internacionales incluidas\n✓ Protección contra virus\n✓ Soporte técnico 24/7\n✓ Antivirus y firewall incluidos\n\n### Programa de Referidos\n\n- Refiere a un amigo: **Bs. 50**\n- Tu amigo obtiene: **Primer mes 50% descuento**\n- Sin límite de referidos\n\n### Garantía de Servicio\n\n- Disponibilidad: 99.9%\n- Tiempo de respuesta: Máximo 24 horas\n- Reemplazo de equipos: Garantía 12 meses\n- Servicio de respaldo: Datos ilimitados para emergencias",
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

        $this->command->info('✅ Tigo articles created!');
    }
}
