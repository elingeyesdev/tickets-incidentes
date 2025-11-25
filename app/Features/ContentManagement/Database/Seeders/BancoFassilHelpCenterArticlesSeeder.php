<?php

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use Illuminate\Database\Seeder;

/**
 * Banco Fassil Help Center Articles Seeder
 *
 * Creates sample help center articles for Banco Fassil with:
 * - Account opening procedures
 * - Credit service information
 * - Online banking guide
 */
class BancoFassilHelpCenterArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Banco Fassil S.A.')->first();

        if (!$company) {
            $this->command->error('Banco Fassil S.A. company not found.');
            return;
        }

        $category = ArticleCategory::first();

        if (!$category) {
            $this->command->error('No article categories available.');
            return;
        }

        $this->command->info('Creating Help Center articles for Banco Fassil...');

        $authorId = $company->admin_user_id;

        $articles = [
            [
                'title' => 'Cómo abrir una cuenta en Banco Fassil',
                'excerpt' => 'Procedimiento simple para abrir tu cuenta bancaria',
                'content' => "## Cómo Abrir una Cuenta en Banco Fassil\n\n### Requisitos\n\n1. **Documentos de Identidad**:\n   - Cédula de identidad original y fotocopia\n   - Para extranjeros: Pasaporte vigente\n\n2. **Comprobante de Domicilio**:\n   - Factura de servicios (agua, luz, gas)\n   - Recibo de alquiler\n   - No mayor a 3 meses\n\n3. **Comprobante de Ingresos**:\n   - Liquidación de sueldo\n   - Declaración de impuestos\n   - Certificado laboral\n\n### Pasos para Abrir Cuenta\n\n**Paso 1: Visita una Sucursal**\nPresenta los documentos en cualquier sucursal de Banco Fassil\n\n**Paso 2: Completa el Formulario**\nLlena la solicitud con tus datos personales\n\n**Paso 3: Verificación**\nNuestro equipo verifica tu información\n\n**Paso 4: Depósito Inicial**\nDeposita el monto mínimo requerido\n\n**Paso 5: Recibe tu Tarjeta**\nTu tarjeta de débito llega en 5-7 días hábiles\n\n### Horario de Atención\n\n- **Lunes a Viernes**: 08:30 - 17:00\n- **Sábado**: 08:30 - 12:30\n- **Domingos y Festivos**: Cerrado",
            ],
            [
                'title' => 'Servicios de crédito disponibles',
                'excerpt' => 'Conoce las opciones de crédito que Banco Fassil te ofrece',
                'content' => "## Servicios de Crédito Banco Fassil\n\n### Tipos de Crédito\n\n#### 1. Crédito Personal\n- **Monto**: Hasta Bs. 50,000\n- **Plazo**: 12-60 meses\n- **Tasa**: A partir del 8% anual\n- **Uso**: Libre disponibilidad\n\n#### 2. Crédito Hipotecario\n- **Monto**: Hasta Bs. 200,000\n- **Plazo**: Hasta 25 años\n- **Tasa**: A partir del 5% anual\n- **Uso**: Compra de vivienda\n\n#### 3. Crédito Vehicular\n- **Monto**: Hasta Bs. 100,000\n- **Plazo**: Hasta 7 años\n- **Tasa**: A partir del 7% anual\n- **Uso**: Compra de vehículos\n\n#### 4. Crédito Empresarial\n- **Monto**: Desde Bs. 100,000\n- **Plazo**: A medida\n- **Tasa**: Negociable\n- **Uso**: Capital de trabajo e inversión\n\n### Proceso de Solicitud\n\n1. Completar solicitud de crédito\n2. Presentar documentación requerida\n3. Evaluación de solvencia\n4. Aprobación (3-5 días hábiles)\n5. Desembolso de fondos",
            ],
            [
                'title' => 'Banca en línea - Guía de uso',
                'excerpt' => 'Cómo usar los servicios de banca en línea de Fassil',
                'content' => "## Banca en Línea Fassil\n\n### Registro\n\n1. Entra a www.fassil.com.bo\n2. Haz clic en \"Banca en Línea\"\n3. Ingresa tu número de cédula\n4. Crea tu contraseña personal\n5. Confirma tu correo electrónico\n\n### Funcionalidades Disponibles\n\n#### Consultas\n- Saldo de cuentas\n- Movimientos\n- Extractos\n- Tasa de cambio\n\n#### Transferencias\n- Transferencias entre cuentas propias\n- Transferencias a terceros (Fassil y otros bancos)\n- Pagos a proveedores\n\n#### Pago de Servicios\n- Pago de servicios básicos\n- Pago de tarjetas de crédito\n- Pago de préstamos\n\n### Seguridad\n\n✓ Contraseña segura (mínimo 8 caracteres)\n✓ Clave de seguridad SMS\n✓ Certificado digital SSL\n✓ No compartir credenciales\n✓ Cambiar contraseña cada 90 días\n\n### Soporte\n\nPara problemas técnicos:\n- **Línea de soporte**: 800-10-2500\n- **Email**: soporte.banca@fassil.com.bo\n- **Horario**: 24/7",
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

        $this->command->info('✅ Banco Fassil articles created!');
    }
}
