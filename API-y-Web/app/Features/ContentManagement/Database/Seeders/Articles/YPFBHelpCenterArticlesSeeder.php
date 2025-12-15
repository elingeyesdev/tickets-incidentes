<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders\Articles;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

/**
 * YPFB Help Center Articles Seeder
 *
 * Crea artículos del centro de ayuda para YPFB Corporación.
 * Basado en los patrones de tickets y anuncios creados:
 * - Patrón 1: Incidente/Anuncio → Artículo explicativo
 * - Patrón 2: Tickets repetitivos → Artículo de autoayuda
 * - Patrón 3: Procesos comunes → Guías paso a paso
 *
 * Categorías globales obligatorias:
 * - ACCOUNT_PROFILE (15-20%)
 * - SECURITY_PRIVACY (10-15%)
 * - BILLING_PAYMENTS (25-35%)
 * - TECHNICAL_SUPPORT (35-45%)
 *
 * Volumen: 12 artículos
 * Período: 5 enero 2025 - 8 diciembre 2025
 * Estados: 80-85% PUBLISHED, 15-20% DRAFT
 */
class YPFBHelpCenterArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📚 Creando artículos Help Center para YPFB Corporación...');

        $company = Company::where('name', 'YPFB Corporación')->first();

        if (!$company) {
            $this->command->error('❌ YPFB Corporación no encontrada.');
            return;
        }

        // Verificar idempotencia
        if (HelpCenterArticle::where('company_id', $company->id)->exists()) {
            $this->command->info('✓ Artículos ya existen para YPFB. Saltando...');
            return;
        }

        // Buscar admin de la empresa usando UserRole
        $adminRole = \App\Features\UserManagement\Models\UserRole::where('company_id', $company->id)
            ->where('role_code', 'COMPANY_ADMIN')
            ->where('is_active', true)
            ->first();

        if (!$adminRole) {
            $this->command->error('❌ No se encontró el admin de YPFB.');
            return;
        }

        $author = \App\Features\UserManagement\Models\User::find($adminRole->user_id);

        // Obtener categorías globales existentes
        $categories = [
            'technical_support' => ArticleCategory::where('code', 'TECHNICAL_SUPPORT')->first(),
            'billing_payments' => ArticleCategory::where('code', 'BILLING_PAYMENTS')->first(),
            'account_profile' => ArticleCategory::where('code', 'ACCOUNT_PROFILE')->first(),
            'security_privacy' => ArticleCategory::where('code', 'SECURITY_PRIVACY')->first(),
        ];

        $articles = [
            // ========== TECHNICAL_SUPPORT (5 artículos - 42%) ==========
            [
                'category_key' => 'technical_support',
                'title' => '¿Qué hacer si detecta olor a gas en su hogar?',
                'slug' => 'que-hacer-si-detecta-olor-gas-hogar',
                'excerpt' => 'Guía de emergencia paso a paso para actuar correctamente ante una fuga de gas en su domicilio. Proteja a su familia siguiendo estos procedimientos de seguridad.',
                'content' => $this->getTechnicalContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 2, 20),
                'published_at' => Carbon::create(2025, 2, 20),
                'views_count' => rand(850, 1200),
            ],
            [
                'category_key' => 'technical_support',
                'title' => '¿Cómo solicitar una nueva conexión de gas domiciliario?',
                'slug' => 'como-solicitar-conexion-gas-domiciliario',
                'excerpt' => 'Requisitos, documentos necesarios y proceso completo para solicitar la instalación de gas natural en su hogar. Incluye tiempos estimados y costos.',
                'content' => $this->getTechnicalContent2(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 3, 5),
                'published_at' => Carbon::create(2025, 3, 5),
                'views_count' => rand(1500, 2200),
            ],
            [
                'category_key' => 'technical_support',
                'title' => 'Guía para convertir su vehículo a Gas Natural Vehicular (GNV)',
                'slug' => 'guia-conversion-vehiculo-gnv',
                'excerpt' => 'Todo lo que necesita saber sobre la conversión de su vehículo a GNV: talleres autorizados, costos, beneficios, requisitos y subvenciones disponibles.',
                'content' => $this->getTechnicalContent3(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 4, 15),
                'published_at' => Carbon::create(2025, 4, 15),
                'views_count' => rand(900, 1400),
            ],
            [
                'category_key' => 'technical_support',
                'title' => 'App YPFB Estaciones: Encuentra combustible cerca de ti',
                'slug' => 'app-ypfb-estaciones-guia-uso',
                'excerpt' => 'Aprenda a usar la nueva aplicación móvil de YPFB para encontrar estaciones de servicio, verificar disponibilidad de combustible y navegar hacia ellas.',
                'content' => $this->getTechnicalContent4(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 4, 18),
                'published_at' => Carbon::create(2025, 4, 18),
                'views_count' => rand(2000, 3500),
            ],
            [
                'category_key' => 'technical_support',
                'title' => '¿Qué hacer ante la escasez temporal de combustible?',
                'slug' => 'que-hacer-ante-escasez-combustible',
                'excerpt' => 'Recomendaciones oficiales de YPFB para manejar situaciones de desabastecimiento temporal. Evite compras de pánico y siga estos consejos.',
                'content' => $this->getTechnicalContent5(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 6, 22),
                'published_at' => Carbon::create(2025, 6, 22),
                'views_count' => rand(3000, 5000),
            ],

            // ========== BILLING_PAYMENTS (4 artículos - 33%) ==========
            [
                'category_key' => 'billing_payments',
                'title' => '¿Cómo interpretar su factura de gas natural?',
                'slug' => 'como-interpretar-factura-gas-natural',
                'excerpt' => 'Explicación detallada de cada sección de su factura de gas: consumo, tarifas, impuestos y cómo verificar que el cobro sea correcto.',
                'content' => $this->getBillingContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 3, 10),
                'published_at' => Carbon::create(2025, 3, 10),
                'views_count' => rand(1200, 1800),
            ],
            [
                'category_key' => 'billing_payments',
                'title' => '¿Cómo presentar un reclamo por facturación incorrecta?',
                'slug' => 'como-presentar-reclamo-facturacion',
                'excerpt' => 'Procedimiento oficial para reclamar si considera que su factura tiene errores. Incluye plazos, documentos y canales de atención.',
                'content' => $this->getBillingContent2(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 5, 8),
                'published_at' => Carbon::create(2025, 5, 8),
                'views_count' => rand(800, 1100),
            ],
            [
                'category_key' => 'billing_payments',
                'title' => 'Tarifas de gas natural industrial: Estructura y descuentos',
                'slug' => 'tarifas-gas-natural-industrial',
                'excerpt' => 'Información completa sobre la estructura tarifaria para clientes industriales, descuentos por volumen y contratos de largo plazo.',
                'content' => $this->getBillingContent3(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 7, 20),
                'published_at' => Carbon::create(2025, 7, 20),
                'views_count' => rand(400, 600),
            ],
            [
                'category_key' => 'billing_payments',
                'title' => 'Formas de pago disponibles para servicios YPFB',
                'slug' => 'formas-pago-servicios-ypfb',
                'excerpt' => 'Todas las opciones para pagar su factura de gas: bancos, apps móviles, puntos de pago, débito automático y pago QR.',
                'content' => $this->getBillingContent4(),
                'status' => 'DRAFT',
                'created_at' => Carbon::create(2025, 11, 10),
                'published_at' => null,
                'views_count' => 0,
            ],

            // ========== ACCOUNT_PROFILE (2 artículos - 17%) ==========
            [
                'category_key' => 'account_profile',
                'title' => '¿Cómo cambiar la titularidad de mi cuenta de gas?',
                'slug' => 'como-cambiar-titularidad-cuenta-gas',
                'excerpt' => 'Proceso para transferir la titularidad del servicio de gas a otra persona, ya sea por venta de inmueble, herencia u otra razón.',
                'content' => $this->getAccountContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 6, 5),
                'published_at' => Carbon::create(2025, 6, 5),
                'views_count' => rand(600, 900),
            ],
            [
                'category_key' => 'account_profile',
                'title' => '¿Cómo registrarse como cliente industrial de YPFB?',
                'slug' => 'registro-cliente-industrial-ypfb',
                'excerpt' => 'Guía completa para empresas que desean contratar suministro de gas natural: requisitos, documentación y proceso de contratación.',
                'content' => $this->getAccountContent2(),
                'status' => 'DRAFT',
                'created_at' => Carbon::create(2025, 11, 25),
                'published_at' => null,
                'views_count' => 0,
            ],

            // ========== SECURITY_PRIVACY (1 artículo - 8%) ==========
            [
                'category_key' => 'security_privacy',
                'title' => 'ALERTA: Cómo identificar estafas que usan el nombre de YPFB',
                'slug' => 'alerta-identificar-estafas-ypfb',
                'excerpt' => 'YPFB nunca solicita dinero para contrataciones. Aprenda a identificar fraudes y proteja su patrimonio de estafadores.',
                'content' => $this->getSecurityContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 3, 2),
                'published_at' => Carbon::create(2025, 3, 2),
                'views_count' => rand(1500, 2500),
            ],
        ];

        foreach ($articles as $data) {
            $category = $categories[$data['category_key']] ?? null;

            HelpCenterArticle::create([
                'company_id' => $company->id,
                'category_id' => $category?->id,
                'author_id' => $author->id,
                'title' => $data['title'],
                'excerpt' => $data['excerpt'],
                'content' => $data['content'],
                'status' => $data['status'],
                'views_count' => $data['views_count'],
                'published_at' => $data['published_at'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);
        }

        $this->command->info('✅ 12 artículos creados para YPFB (TECH: 5, BILLING: 4, ACCOUNT: 2, SECURITY: 1)');
    }

    private function getOrCreateCategories(string $companyId): array
    {
        $categoriesData = [
            'account_profile' => ['name' => 'Cuenta y Perfil', 'description' => 'Gestión de cuenta, datos personales y configuración'],
            'security_privacy' => ['name' => 'Seguridad y Privacidad', 'description' => 'Protección de datos, alertas de fraude y seguridad'],
            'billing_payments' => ['name' => 'Facturación y Pagos', 'description' => 'Facturas, métodos de pago, tarifas y reclamos'],
            'technical_support' => ['name' => 'Soporte Técnico', 'description' => 'Conexiones, emergencias, aplicaciones y servicios'],
        ];

        $result = [];
        foreach ($categoriesData as $key => $data) {
            $result[$key] = ArticleCategory::firstOrCreate(
                ['company_id' => $companyId, 'slug' => $key],
                ['name' => $data['name'], 'description' => $data['description'], 'is_active' => true]
            );
        }

        return $result;
    }

    private function getTechnicalContent1(): string
    {
        return "## Pasos inmediatos ante olor a gas\n\n**¡IMPORTANTE!** Si detecta olor a gas, siga estos pasos en orden:\n\n### 1. NO encienda ni apague nada eléctrico\n- No use interruptores de luz\n- No conecte/desconecte electrodomésticos\n- No use el celular DENTRO de la casa\n\n### 2. Abra puertas y ventanas\nVentile el área inmediatamente para dispersar el gas.\n\n### 3. Cierre la válvula de gas\nUbique la válvula principal (generalmente cerca del medidor) y gírela a posición cerrada.\n\n### 4. Evacúe el área\nSalga de la vivienda con su familia. Lleve a sus mascotas.\n\n### 5. Llame a emergencias DESDE AFUERA\n- **Línea YPFB Emergencias:** 800-10-0965\n- **Bomberos:** 119\n\n### ¿Cuándo es seguro regresar?\nSolo cuando un técnico autorizado de YPFB haya inspeccionado y dado el visto bueno.\n\n### Causas comunes de fugas\n- Juntas deterioradas\n- Mangueras vencidas\n- Conexiones flojas\n\n**Recuerde:** El gas natural tiene un odorizante (mercaptano) que facilita su detección. Cualquier olor inusual debe ser investigado.";
    }

    private function getTechnicalContent2(): string
    {
        return "## Proceso de solicitud de conexión\n\n### Requisitos\n1. Ser propietario o inquilino con autorización\n2. Inmueble en área con red de gas\n3. Instalación interna certificada\n\n### Documentos necesarios\n- Carnet de identidad del titular\n- Factura de luz o agua (prueba de domicilio)\n- Título de propiedad o contrato de alquiler\n- Plano de instalación interna (si ya existe)\n\n### Proceso paso a paso\n\n**Paso 1:** Solicite evaluación en oficinas YPFB o llamando al 800-10-0965\n\n**Paso 2:** Un técnico visitará su domicilio para evaluar factibilidad (sin costo)\n\n**Paso 3:** Recibirá presupuesto detallado (acometida + medidor + materiales)\n\n**Paso 4:** Pague el monto acordado (Bs. 2,000 - 4,000 según distancia)\n\n**Paso 5:** Instalación programada (15-45 días según zona)\n\n**Paso 6:** Inspección final y habilitación del servicio\n\n### Costos aproximados\n- Conexión estándar (hasta 10m): Bs. 2,500\n- Conexión extendida (10-20m): Bs. 3,500\n- Cada metro adicional: Bs. 80\n\n### Tiempos de instalación\n- Zona urbana: 15-30 días\n- Zona periurbana: 30-45 días\n\n**Importante:** La instalación interna (tuberías dentro de su casa) debe ser realizada por un instalador certificado ANTES de solicitar la conexión.";
    }

    private function getTechnicalContent3(): string
    {
        return "## Conversión a Gas Natural Vehicular\n\n### Beneficios del GNV\n- **Ahorro:** Hasta 60% en combustible\n- **Ecológico:** Menos emisiones contaminantes\n- **Subvención:** El GNV tiene precio regulado\n\n### ¿Mi vehículo puede convertirse?\n- Vehículos a gasolina: SÍ (la mayoría)\n- Vehículos diésel: NO (no es compatible)\n- Antigüedad máxima: 15 años\n\n### Proceso de conversión\n\n**1. Evaluación técnica**\nLleve su vehículo a un taller autorizado para evaluación.\n\n**2. Instalación del kit**\n- Duración: 1-2 días\n- Incluye: Cilindro, reductor, inyectores, computadora\n\n**3. Inspección obligatoria**\nEl vehículo debe pasar inspección en centro autorizado.\n\n**4. Obtención de código QR**\nRegistro obligatorio para cargar GNV.\n\n### Costos\n- Kit básico: Bs. 4,500 - 6,000\n- Kit premium: Bs. 6,000 - 8,000\n- Inspección: Bs. 150\n\n### Talleres autorizados\nConsulte la lista actualizada en:\n- www.ypfb.gob.bo/gnv\n- Llamando al 800-10-0965\n\n### Estaciones de GNV\nUse la App YPFB Estaciones para encontrar estaciones con GNV cerca de usted.";
    }

    private function getTechnicalContent4(): string
    {
        return "## Guía de uso App YPFB Estaciones\n\n### Descarga\n- **Android:** Google Play Store\n- **iOS:** App Store\nBusque: \"YPFB Estaciones Bolivia\"\n\n### Funcionalidades principales\n\n**1. Mapa de estaciones**\nVea todas las estaciones YPFB en un mapa interactivo. Filtre por tipo de combustible.\n\n**2. Disponibilidad en tiempo real**\n- 🟢 Verde: Stock normal\n- 🟡 Amarillo: Stock bajo\n- 🔴 Rojo: Sin stock\n\n**3. Navegación**\nPresione \"Ir\" para abrir navegación a la estación seleccionada.\n\n**4. Alertas**\nActive notificaciones para saber cuando una estación cercana se reabastece.\n\n### Cómo usar la app\n\n**Paso 1:** Abra la app y permita acceso a su ubicación\n\n**Paso 2:** El mapa mostrará estaciones cercanas automáticamente\n\n**Paso 3:** Toque una estación para ver detalles:\n- Dirección completa\n- Combustibles disponibles\n- Horarios\n- Servicios adicionales\n\n**Paso 4:** Presione \"Ir\" para navegación\n\n### Modo offline\nLa app funciona sin internet mostrando la última información cargada.\n\n### Reportar problemas\nSi la información no es precisa, use el botón \"Reportar\" dentro de la app.";
    }

    private function getTechnicalContent5(): string
    {
        return "## Recomendaciones ante escasez de combustible\n\n### ¿Por qué ocurre la escasez?\n- Bloqueos de carreteras\n- Problemas logísticos de importación\n- Mantenimiento de instalaciones\n- Alta demanda estacional\n\n### Lo que debe hacer\n\n**1. Mantener la calma**\nLa escasez es temporal. YPFB trabaja para normalizar el abastecimiento.\n\n**2. NO acumule combustible**\nComprar de más agrava el problema y crea filas innecesarias.\n\n**3. Optimice sus viajes**\n- Combine varias actividades en un solo viaje\n- Use transporte público si es posible\n- Comparta viajes con vecinos o colegas\n\n**4. Use la App YPFB**\nVerifique disponibilidad antes de ir a una estación.\n\n### Lo que NO debe hacer\n\n❌ Almacenar combustible en recipientes no autorizados (peligro de incendio)\n\n❌ Comprar combustible de \"revendedores\" (ilegal y peligroso)\n\n❌ Saturar estaciones provocando filas\n\n### Información oficial\n- Twitter/X: @YPFBoficial\n- Web: www.ypfb.gob.bo/noticias\n- Línea: 800-10-0965\n\n**Recuerde:** YPFB mantiene reservas estratégicas. La escasez puntual no significa crisis nacional.";
    }

    private function getBillingContent1(): string
    {
        return "## Entienda su factura de gas\n\n### Secciones de la factura\n\n**1. Datos del cliente**\n- Nombre del titular\n- Dirección de suministro\n- Código de cliente\n\n**2. Período de consumo**\n- Fecha de lectura anterior\n- Fecha de lectura actual\n- Días facturados\n\n**3. Consumo**\n- Lectura anterior (m³)\n- Lectura actual (m³)\n- Consumo del período (m³)\n- Equivalente en MMBTU\n\n**4. Detalle de cargos**\n- Cargo fijo: Monto mensual independiente del consumo\n- Cargo variable: Según m³ consumidos\n- Impuestos: IVA, IT\n\n### Cómo verificar su consumo\n\n**Paso 1:** Ubique su medidor\n\n**Paso 2:** Anote la lectura (números en negro)\n\n**Paso 3:** Compare con la lectura de su factura\n\nSi hay diferencia mayor al 5%, presente reclamo.\n\n### Consumo promedio por tipo de hogar\n- Familia pequeña (2 personas): 15-25 m³/mes\n- Familia mediana (4 personas): 25-40 m³/mes\n- Familia grande (6+ personas): 40-60 m³/mes\n\n**Tip:** El consumo aumenta en invierno por uso de calefacción.";
    }

    private function getBillingContent2(): string
    {
        return "## Proceso de reclamo por facturación\n\n### ¿Cuándo reclamar?\n- Consumo inusualmente alto\n- Cobros duplicados\n- Errores en datos del cliente\n- Servicios no solicitados\n\n### Pasos para reclamar\n\n**Paso 1:** Reúna documentación\n- Factura en cuestión\n- Facturas anteriores (comparación)\n- Fotos del medidor (lectura actual)\n\n**Paso 2:** Presente el reclamo\n- **Presencial:** Oficinas YPFB\n- **Teléfono:** 800-10-0965\n- **Email:** reclamos@ypfb.gob.bo\n\n**Paso 3:** Reciba número de caso\nGuárdelo para seguimiento.\n\n**Paso 4:** Espere inspección\nUn técnico visitará su domicilio (5-10 días hábiles).\n\n**Paso 5:** Resolución\n- Si hay error: Nota de crédito o factura corregida\n- Si no hay error: Notificación con explicación\n\n### Plazos\n- Respuesta inicial: 5 días hábiles\n- Resolución final: 15 días hábiles\n- Apelación: 10 días desde la resolución\n\n### Mientras se resuelve\n**No se suspenderá el servicio** si el reclamo está en proceso.";
    }

    private function getBillingContent3(): string
    {
        return "## Tarifas industriales de gas natural\n\n### Estructura tarifaria\n\n**Categoría A:** Consumo < 10,000 m³/día\n- Tarifa: USD 3.15/MMBTU\n- Cargo fijo: USD 150/mes\n\n**Categoría B:** Consumo 10,000-50,000 m³/día\n- Tarifa: USD 2.95/MMBTU\n- Cargo fijo: USD 300/mes\n\n**Categoría C:** Consumo > 50,000 m³/día\n- Tarifa: Negociable (desde USD 2.70/MMBTU)\n- Cargo fijo: Según contrato\n\n### Descuentos disponibles\n\n**Por plazo de contrato:**\n- 5 años: 5% descuento\n- 10 años: 12% descuento\n- 15 años: 18% descuento\n\n**Por pago adelantado:**\n- 7 días: 2% adicional\n- 15 días: 3% adicional\n\n**Por volumen garantizado:**\nDescuentos adicionales si se compromete a consumo mínimo.\n\n### Requisitos para contratar\n- Personería jurídica vigente\n- NIT activo\n- Declaración de consumo proyectado\n- Garantía bancaria (12 meses consumo)\n\n### Contacto comercial\n- Email: comercializacion@ypfb.gob.bo\n- Teléfono: (2) 2106500 ext. 2140";
    }

    private function getBillingContent4(): string
    {
        return "## Opciones de pago de su factura\n\n### Pago presencial\n\n**Bancos autorizados:**\n- Banco Unión\n- Banco Nacional de Bolivia\n- Banco Mercantil Santa Cruz\n- Banco de Crédito BCP\n\n**Puntos de pago:**\n- Farmacorp (todas las sucursales)\n- Tiendas SACI\n- Agentes Tigo Money\n\n### Pago digital\n\n**Apps bancarias:**\nTodas las apps de bancos autorizados permiten pagar con el código de cliente.\n\n**Billeteras móviles:**\n- Tigo Money\n- Simple\n- iPayment\n\n**Pago QR:**\nEscanee el código QR en su factura desde cualquier app bancaria.\n\n### Débito automático\n\n**¿Cómo activarlo?**\n1. Llene el formulario en oficinas YPFB\n2. Proporcione datos de su cuenta bancaria\n3. Firme autorización de débito\n\n**Beneficio:** 3% de descuento en cada factura.\n\n### Vencimiento y corte\n- Vencimiento: 20 de cada mes\n- Corte por mora: 60 días de atraso\n- Reconexión: Bs. 50 + pago de deuda";
    }

    private function getAccountContent1(): string
    {
        return "## Cambio de titularidad del servicio\n\n### ¿Cuándo se necesita?\n- Venta del inmueble\n- Fallecimiento del titular\n- Divorcio o separación\n- Cambio de inquilino\n\n### Requisitos\n\n**Por venta:**\n- Minuta de transferencia o título de propiedad\n- CI del nuevo propietario\n- Última factura pagada\n\n**Por fallecimiento:**\n- Certificado de defunción\n- Declaratoria de herederos o testamento\n- CI del nuevo titular\n\n**Por cambio de inquilino:**\n- Nuevo contrato de alquiler\n- Carta de cesión del anterior inquilino\n- CI del nuevo inquilino\n\n### Proceso\n\n**Paso 1:** Presente documentos en oficinas YPFB\n\n**Paso 2:** Firme formulario de cambio de titularidad\n\n**Paso 3:** El cambio se procesa en 5-10 días hábiles\n\n**Paso 4:** Recibirá confirmación y nueva credencial\n\n### Costo\n**Gratuito** - YPFB no cobra por este trámite.\n\n### Importante\n- El anterior titular queda libre de responsabilidad\n- Las deudas pendientes deben pagarse antes del cambio\n- El medidor no se cambia, solo el registro";
    }

    private function getAccountContent2(): string
    {
        return "## Registro como cliente industrial\n\n### ¿Quién puede registrarse?\nEmpresas con consumo proyectado mayor a 1,000 m³/día.\n\n### Documentación requerida\n\n**Documentos legales:**\n- Personería jurídica o matrícula de comercio\n- NIT vigente\n- Poder del representante legal\n- CI del representante\n\n**Documentos técnicos:**\n- Plano de ubicación de la planta\n- Proyección de consumo mensual\n- Descripción de uso del gas\n\n**Documentos financieros:**\n- Estados financieros (último año)\n- Referencias bancarias\n- Garantía requerida según volumen\n\n### Proceso de registro\n\n**Paso 1:** Solicitud inicial\nEnvíe carta de intención a comercializacion@ypfb.gob.bo\n\n**Paso 2:** Evaluación técnica\nYPFB evaluará factibilidad de conexión (30 días).\n\n**Paso 3:** Propuesta comercial\nRecibirá oferta de tarifa y condiciones.\n\n**Paso 4:** Negociación y contrato\nAfinamiento de términos y firma.\n\n**Paso 5:** Conexión física\nInstalación de medidor y acometida.\n\n### Tiempos estimados\n- Evaluación: 30 días\n- Negociación: 15-30 días\n- Instalación: 60-90 días\n\n### Contacto\nGerencia Comercial: (2) 2106500 ext. 2140";
    }

    private function getSecurityContent1(): string
    {
        return "## Identificar y evitar estafas\n\n### Modalidades de fraude detectadas\n\n**1. Ofertas de empleo falsas**\nEstafadores llaman ofreciendo trabajo en YPFB y piden dinero como \"garantía\".\n\n**2. Cobros de conexión falsos**\nPersonas que cobran por adelantado para \"acelerar\" conexiones de gas.\n\n**3. Revisión de instalaciones**\nSupuestos técnicos que cobran por \"inspecciones obligatorias\".\n\n### YPFB NUNCA:\n\n❌ Solicita dinero para procesos de contratación\n\n❌ Pide depósitos en cuentas personales\n\n❌ Ofrece empleos por WhatsApp o llamadas\n\n❌ Cobra por inspecciones no programadas\n\n### Cómo protegerse\n\n**1. Verifique identidad**\nExija credencial oficial de YPFB con foto.\n\n**2. No pague en efectivo**\nTodo pago legítimo es en bancos autorizados.\n\n**3. Confirme visitas**\nLlame al 800-10-0965 para verificar.\n\n### Si es víctima\n\n**1. Denuncie a la policía:** 110\n\n**2. Denuncie a YPFB:**\n- Línea: 800-10-0965\n- Email: denuncias@ypfb.gob.bo\n\n**3. Guarde evidencia:**\n- Números de teléfono\n- Nombres proporcionados\n- Comprobantes de depósito\n\n### Convocatorias legítimas\nSolo en www.ypfb.gob.bo/trabaja-con-nosotros";
    }
}
