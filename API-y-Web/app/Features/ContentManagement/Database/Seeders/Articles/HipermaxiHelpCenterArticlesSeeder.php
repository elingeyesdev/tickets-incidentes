<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders\Articles;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

/**
 * Hipermaxi Help Center Articles Seeder
 *
 * Crea artículos basados en patrones de tickets y anuncios:
 * - Uso de la app y plataforma eCommerce
 * - Programa Hipermaxi Club
 * - Políticas de devolución
 * - Delivery y pedidos online
 * - Productos perecederos y calidad
 *
 * Volumen: 10 artículos (TECHNICAL_SUPPORT: 4, BILLING_PAYMENTS: 3, ACCOUNT_PROFILE: 2, SECURITY_PRIVACY: 1)
 * Período: 5 enero 2025 - 8 diciembre 2025
 * Estados: 83% PUBLISHED, 17% DRAFT
 */
class HipermaxiHelpCenterArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📚 Creando artículos Help Center para Hipermaxi S.A....');

        $company = Company::where('name', 'Hipermaxi S.A.')->first();

        if (!$company) {
            $this->command->error('❌ Hipermaxi S.A. no encontrada.');
            return;
        }

        if (HelpCenterArticle::where('company_id', $company->id)->exists()) {
            $this->command->info('✓ Artículos ya existen para Hipermaxi. Saltando...');
            return;
        }

        // Buscar admin de la empresa usando UserRole
        $adminRole = \App\Features\UserManagement\Models\UserRole::where('company_id', $company->id)
            ->where('role_code', 'COMPANY_ADMIN')
            ->where('is_active', true)
            ->first();

        if (!$adminRole) {
            $this->command->error('❌ No se encontró el admin de Hipermaxi.');
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
            // ========== TECHNICAL_SUPPORT (4 artículos - 40%) ==========
            [
                'category_key' => 'technical_support',
                'title' => '¿Cómo usar la App Hipermaxi para hacer pedidos?',
                'slug' => 'como-usar-app-hipermaxi-pedidos',
                'excerpt' => 'Guía paso a paso para descargar la app, crear tu cuenta, buscar productos y realizar tu primer pedido con entrega a domicilio.',
                'content' => $this->getTechnicalContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 1, 15),
                'published_at' => Carbon::create(2025, 1, 15),
                'views_count' => rand(2500, 4000),
            ],
            [
                'category_key' => 'technical_support',
                'title' => '¿Qué hacer si mi pedido llega incompleto?',
                'slug' => 'pedido-llega-incompleto-que-hacer',
                'excerpt' => 'Procedimiento para reportar productos faltantes en tu pedido de delivery y obtener reembolso o reenvío de productos.',
                'content' => $this->getTechnicalContent2(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 3, 20),
                'published_at' => Carbon::create(2025, 3, 20),
                'views_count' => rand(1200, 1800),
            ],
            [
                'category_key' => 'technical_support',
                'title' => '¿Cómo actualizar la app Hipermaxi?',
                'slug' => 'como-actualizar-app-hipermaxi',
                'excerpt' => 'Instrucciones para actualizar la aplicación en Android e iOS y evitar problemas de funcionamiento.',
                'content' => $this->getTechnicalContent3(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 8, 18),
                'published_at' => Carbon::create(2025, 8, 18),
                'views_count' => rand(800, 1200),
            ],
            [
                'category_key' => 'technical_support',
                'title' => '¿Cómo hacer compras desde el extranjero para mi familia en Bolivia?',
                'slug' => 'compras-extranjero-familia-bolivia',
                'excerpt' => 'Guía para bolivianos en el exterior que desean enviar compras de supermercado a sus familiares en Bolivia.',
                'content' => $this->getTechnicalContent4(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 6, 5),
                'published_at' => Carbon::create(2025, 6, 5),
                'views_count' => rand(600, 900),
            ],

            // ========== BILLING_PAYMENTS (3 artículos - 30%) ==========
            [
                'category_key' => 'billing_payments',
                'title' => '¿Por qué el precio en la app es diferente al de la tienda?',
                'slug' => 'precio-app-diferente-tienda',
                'excerpt' => 'Explicación sobre las diferencias de precios entre canales y cómo reclamar si el precio de la app no se respeta.',
                'content' => $this->getBillingContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 2, 10),
                'published_at' => Carbon::create(2025, 2, 10),
                'views_count' => rand(1000, 1500),
            ],
            [
                'category_key' => 'billing_payments',
                'title' => '¿Cómo funciona el programa Hipermaxi Club?',
                'slug' => 'como-funciona-hipermaxi-club',
                'excerpt' => 'Todo sobre el programa de lealtad: cómo inscribirse, acumular puntos, niveles de membresía y canjear beneficios.',
                'content' => $this->getBillingContent2(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 8, 5),
                'published_at' => Carbon::create(2025, 8, 5),
                'views_count' => rand(1500, 2200),
            ],
            [
                'category_key' => 'billing_payments',
                'title' => '¿Cómo solicitar reembolso por productos dañados?',
                'slug' => 'solicitar-reembolso-productos-danados',
                'excerpt' => 'Proceso para devolver productos en mal estado y obtener reembolso en efectivo o crédito de tienda.',
                'content' => $this->getBillingContent3(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 4, 12),
                'published_at' => Carbon::create(2025, 4, 12),
                'views_count' => rand(900, 1300),
            ],

            // ========== ACCOUNT_PROFILE (2 artículos - 20%) ==========
            [
                'category_key' => 'account_profile',
                'title' => '¿Cómo crear una cuenta en Hipermaxi Online?',
                'slug' => 'crear-cuenta-hipermaxi-online',
                'excerpt' => 'Pasos para registrarte en la app o sitio web, verificar tu cuenta y empezar a comprar.',
                'content' => $this->getAccountContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 1, 12),
                'published_at' => Carbon::create(2025, 1, 12),
                'views_count' => rand(2000, 3000),
            ],
            [
                'category_key' => 'account_profile',
                'title' => '¿Cómo inscribirse en Hipermaxi Club desde la app?',
                'slug' => 'inscribirse-hipermaxi-club-app',
                'excerpt' => 'Guía rápida para unirte al programa de lealtad y empezar a acumular puntos con tus compras.',
                'content' => $this->getAccountContent2(),
                'status' => 'DRAFT',
                'created_at' => Carbon::create(2025, 11, 20),
                'published_at' => null,
                'views_count' => 0,
            ],

            // ========== SECURITY_PRIVACY (1 artículo - 10%) ==========
            [
                'category_key' => 'security_privacy',
                'title' => 'ALERTA: Cómo identificar fraudes que usan el nombre de Hipermaxi',
                'slug' => 'alerta-fraudes-nombre-hipermaxi',
                'excerpt' => 'Aprende a identificar estafas y vendedores no autorizados que falsifican productos o promociones de Hipermaxi.',
                'content' => $this->getSecurityContent1(),
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 3, 25),
                'published_at' => Carbon::create(2025, 3, 25),
                'views_count' => rand(800, 1200),
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

        $this->command->info('✅ 10 artículos creados para Hipermaxi (TECH: 4, BILLING: 3, ACCOUNT: 2, SECURITY: 1)');
    }

    private function getOrCreateCategories(string $companyId): array
    {
        $categoriesData = [
            'account_profile' => ['name' => 'Cuenta y Perfil', 'description' => 'Registro, datos personales y configuración de cuenta'],
            'security_privacy' => ['name' => 'Seguridad y Privacidad', 'description' => 'Protección de datos, alertas de fraude'],
            'billing_payments' => ['name' => 'Facturación y Pagos', 'description' => 'Precios, promociones, reembolsos y programa de lealtad'],
            'technical_support' => ['name' => 'Soporte Técnico', 'description' => 'Uso de la app, pedidos online y delivery'],
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
        return "## Guía completa de la App Hipermaxi\n\n### Paso 1: Descarga la app\n- **Android:** Buscar \"Hipermaxi\" en Google Play Store\n- **iOS:** Buscar \"Hipermaxi Bolivia\" en App Store\n\n### Paso 2: Crea tu cuenta\n1. Abre la app y toca \"Registrarse\"\n2. Ingresa tu número de celular\n3. Recibirás un código SMS de verificación\n4. Completa nombre y correo\n\n### Paso 3: Agrega dirección de entrega\n1. Ve a Perfil > Direcciones\n2. Toca \"Agregar dirección\"\n3. Usa el mapa para ubicar tu casa\n4. Agrega referencias (color de casa, esquina, etc.)\n\n### Paso 4: Haz tu pedido\n1. Navega por categorías o usa el buscador\n2. Toca un producto para ver detalles\n3. Presiona \"Agregar al carrito\"\n4. Cuando termines, ve al carrito\n5. Revisa tu pedido y elige hora de entrega\n6. Selecciona método de pago\n7. Confirma tu pedido\n\n### Formas de pago\n- Efectivo (el repartidor lleva cambio)\n- Tarjeta de débito/crédito (POS móvil)\n- Código QR (todas las apps bancarias)\n\n### Seguimiento\nRecibe notificaciones cuando tu pedido esté en camino y cuando llegue.";
    }

    private function getTechnicalContent2(): string
    {
        return "## ¿Pedido incompleto? Así puedes reclamar\n\n### ¿Qué hacer inmediatamente?\n1. **Verifica el ticket:** Compara lo que recibiste con lo que dice el ticket\n2. **Toma fotos:** Fotografía los productos recibidos y el ticket\n3. **Reporta en la app:** Dentro de las primeras 24 horas\n\n### Cómo reportar en la app\n1. Abre la app Hipermaxi\n2. Ve a \"Mis Pedidos\"\n3. Selecciona el pedido afectado\n4. Toca \"Reportar problema\"\n5. Selecciona \"Productos faltantes\"\n6. Marca los productos que no recibiste\n7. Adjunta fotos si las tienes\n8. Envía el reporte\n\n### ¿Qué pasa después?\n- Recibirás confirmación en 2-4 horas\n- Te contactaremos para ofrecer:\n  - **Opción A:** Envío de productos faltantes (mismo día o siguiente)\n  - **Opción B:** Reembolso completo de productos faltantes\n  - **Opción C:** Crédito en tu cuenta (vale más)\n\n### Plazos\n- Reportar: Dentro de 24 horas\n- Respuesta: Máximo 4 horas\n- Resolución: Máximo 48 horas\n\n### ¿Necesitas ayuda?\n- WhatsApp: +591 3342-5353\n- Email: hipermaxi@hipermaxi.com";
    }

    private function getTechnicalContent3(): string
    {
        return "## Cómo actualizar la App Hipermaxi\n\n### ¿Por qué actualizar?\n- Evitar errores de pago\n- Acceder a nuevas funciones\n- Mayor velocidad y estabilidad\n\n### Versión actual recomendada\n- Android: **3.2.1**\n- iOS: **3.2.0**\n\n### Actualizar en Android\n1. Abre Google Play Store\n2. Toca tu foto de perfil (arriba derecha)\n3. Toca \"Gestionar apps y dispositivo\"\n4. Busca Hipermaxi en la lista\n5. Si dice \"Actualizar\", tócalo\n6. Espera a que termine\n\n### Actualizar en iPhone\n1. Abre App Store\n2. Toca tu foto de perfil (arriba derecha)\n3. Baja para ver actualizaciones pendientes\n4. Busca Hipermaxi\n5. Toca \"Actualizar\"\n\n### ¿Cómo saber mi versión?\n1. Abre la app Hipermaxi\n2. Ve a Perfil (icono de persona)\n3. Baja hasta \"Información\"\n4. Verás el número de versión\n\n### Problemas comunes\n**\"No puedo actualizar\"**\n- Verifica conexión a internet\n- Libera espacio en tu celular\n- Reinicia el celular\n\n**\"Después de actualizar no funciona\"**\n- Desinstala la app\n- Reinstala desde la tienda\n- Inicia sesión nuevamente";
    }

    private function getTechnicalContent4(): string
    {
        return "## Compras desde el extranjero\n\n### ¿Quién puede usar este servicio?\nBolivianos que viven en:\n- España, Estados Unidos, Argentina\n- Brasil, Chile, Italia\n- Cualquier país del mundo\n\n### ¿Cómo funciona?\n1. Tú haces el pedido desde el extranjero\n2. Pagas con tu tarjeta internacional\n3. Hipermaxi entrega a tu familia en Bolivia\n\n### Paso a paso\n\n**1. Descarga la app**\nLa app está disponible en todos los países.\n\n**2. Crea cuenta con dirección boliviana**\nUsa la dirección de tu familiar en Bolivia.\n\n**3. Haz tu pedido**\nSelecciona productos normalmente.\n\n**4. En el pago**\nElige \"Tarjeta internacional\"\nIngresa datos de tu tarjeta\n\n**5. Confirma**\nTu familiar recibirá SMS cuando llegue el pedido.\n\n### Cobertura de entrega\n- Santa Cruz (todas las zonas)\n- La Paz y El Alto\n- Cochabamba\n- Montero\n\n### Preguntas frecuentes\n\n**¿Cobran extra por pago internacional?**\nNo, Hipermaxi no cobra extra. Tu banco puede aplicar cargos por transacción internacional.\n\n**¿Puedo programar entregas recurrentes?**\nSí, puedes programar pedidos mensuales.\n\n**¿Mi familiar necesita la app?**\nNo, solo necesita estar en la dirección para recibir.";
    }

    private function getBillingContent1(): string
    {
        return "## Diferencias de precio App vs Tienda\n\n### ¿Por qué pueden ser diferentes?\n\n**Promociones exclusivas online:**\nAlgunas ofertas solo aplican para compras en la app o web.\n\n**Actualización de precios:**\nLos precios en tienda se actualizan cada mañana. La app puede tener el precio actualizado antes.\n\n**Error humano:**\nA veces el cartel de precio en góndola no se actualiza correctamente.\n\n### ¿Cuál precio aplica?\n\n**Si compras en app:** El precio que muestra la app.\n**Si compras en tienda:** El precio de góndola.\n\n### ¿Qué hacer si no respetan el precio?\n\n**En tienda:**\n1. Muestra la app al cajero\n2. Si no lo acepta, pide hablar con supervisor\n3. Muestra captura de pantalla con fecha visible\n\n**Si ya pagaste:**\n1. Guarda tu ticket\n2. Toma captura de pantalla de la app\n3. Ve a Atención al Cliente en la tienda\n4. O reporta en la app: Perfil > Ayuda > Problema de precio\n\n### Política de Hipermaxi\n**Siempre respetamos el precio más bajo para el cliente** cuando hay discrepancia comprobable entre canales.";
    }

    private function getBillingContent2(): string
    {
        return "## Programa Hipermaxi Club\n\n### ¿Qué es?\nPrograma de lealtad donde acumulas puntos con cada compra y los canjeas por productos o descuentos.\n\n### ¿Cómo acumular puntos?\n- Por cada Bs. 10 de compra = 1 punto\n- Aplica en tiendas físicas y compras online\n- Los puntos se acumulan automáticamente\n\n### Niveles de membresía\n\n**🥉 Bronce (0-499 puntos)**\n- Ofertas semanales exclusivas\n- Promociones de cumpleaños\n\n**🥈 Plata (500-1,499 puntos)**\n- Todo lo de Bronce +\n- 5% descuento en tu cumpleaños\n- Acceso anticipado a promociones\n\n**🥇 Oro (1,500+ puntos)**\n- Todo lo de Plata +\n- 10% descuento permanente\n- Delivery gratis siempre\n- Caja preferencial en tiendas\n\n### ¿Cómo inscribirse?\n- En caja: Di tu CI al cajero\n- En app: Perfil > Hipermaxi Club > Inscribirse\n- Web: www.hipermaxi.com/club\n\n### ¿Cuándo vencen los puntos?\nLos puntos vencen 12 meses después de su acumulación.\n\n### ¿Cómo canjear?\n1. En caja menciona tus puntos\n2. O en la app al pagar";
    }

    private function getBillingContent3(): string
    {
        return "## Reembolso por productos dañados\n\n### ¿Qué productos aplican?\n- Productos vencidos\n- Productos en mal estado\n- Empaques rotos o contaminados\n- Productos que no funcionan (electrodomésticos)\n\n### Plazos para reclamar\n\n**Productos perecederos:**\n- Carne, lácteos, congelados: 24 horas\n\n**Productos no perecederos:**\n- Abarrotes, limpieza: 15 días\n\n**Electrodomésticos:**\n- 15 días (30 días si es defecto de fábrica)\n\n### ¿Qué necesitas?\n1. Ticket de compra (físico o digital)\n2. Producto en su estado actual\n3. Tu carnet de identidad\n\n### ¿Dónde reclamar?\n\n**Opción A - En tienda:**\nAcude a Atención al Cliente con ticket y producto.\n\n**Opción B - Por la app:**\nPerfil > Ayuda > Producto dañado\nSube fotos del producto y ticket.\n\n### ¿Qué puedes elegir?\n- Reembolso en efectivo\n- Cambio por otro producto\n- Crédito en tu cuenta Hipermaxi\n\n### Tiempo de resolución\n- En tienda: Inmediato\n- Por app: 24-48 horas";
    }

    private function getAccountContent1(): string
    {
        return "## Crear cuenta en Hipermaxi Online\n\n### Requisitos\n- Celular con número boliviano\n- Correo electrónico válido\n- App descargada o acceso a web\n\n### Crear cuenta en la App\n\n**Paso 1:** Abre la app y toca \"Registrarse\"\n\n**Paso 2:** Ingresa tu número de celular\n- Debe ser número boliviano (7xxxxxxx)\n- Recibirás código SMS\n\n**Paso 3:** Ingresa el código\n- 6 dígitos enviados por SMS\n- Tienes 5 minutos para ingresarlo\n\n**Paso 4:** Completa tu perfil\n- Nombre completo\n- Correo electrónico\n- Contraseña (mínimo 8 caracteres)\n\n**Paso 5:** Agrega dirección\n- Usa el mapa para ubicar tu casa\n- Agrega referencias claras\n\n### Crear cuenta en la Web\n1. Ve a www.hipermaxi.com\n2. Clic en \"Iniciar sesión\"\n3. Clic en \"¿No tienes cuenta?\"\n4. Sigue los mismos pasos\n\n### ¿Problemas con el SMS?\n- Verifica que tu número sea correcto\n- Espera 2 minutos entre intentos\n- Revisa que tengas señal\n- Contacta soporte si persiste";
    }

    private function getAccountContent2(): string
    {
        return "## Inscribirse en Hipermaxi Club desde la App\n\n### ¿Qué necesitas?\n- Cuenta activa en Hipermaxi\n- App actualizada (v3.0 o superior)\n\n### Pasos para inscribirte\n\n**Paso 1:** Abre la app e inicia sesión\n\n**Paso 2:** Ve a tu Perfil\nToca el icono de persona abajo a la derecha.\n\n**Paso 3:** Busca \"Hipermaxi Club\"\nEstá en la sección de beneficios.\n\n**Paso 4:** Toca \"Inscribirse\"\n\n**Paso 5:** Acepta términos y condiciones\n\n**Paso 6:** ¡Listo!\nYa eres miembro. Empiezas en nivel Bronce.\n\n### ¿Cómo ver mis puntos?\n1. Abre la app\n2. Ve a Perfil\n3. Toca \"Hipermaxi Club\"\n4. Verás tu saldo de puntos y nivel\n\n### Vincular compras anteriores\nSi ya tenías tarjeta física, contacta a Atención al Cliente para migrar tus puntos.";
    }

    private function getSecurityContent1(): string
    {
        return "## Cuidado con fraudes\n\n### Estafas detectadas\n\n**1. Vendedores ambulantes**\nPersonas vendiendo productos \"de Hipermaxi\" en mercados o ferias a precios muy bajos. Estos productos pueden ser falsificados, vencidos o robados.\n\n**2. Promociones falsas por WhatsApp**\nMensajes tipo \"Hipermaxi regala Bs. 500 en compras\" que piden datos personales o dinero.\n\n**3. Páginas web falsas**\nSitios que imitan hipermaxi.com pero con direcciones diferentes.\n\n### Hipermaxi NUNCA:\n❌ Vende fuera de sus tiendas o app oficial\n❌ Pide dinero por adelantado\n❌ Solicita datos bancarios por WhatsApp\n❌ Regala dinero por compartir mensajes\n\n### Cómo protegerte\n\n**Verifica la fuente:**\n- Web oficial: www.hipermaxi.com\n- App oficial: Buscar \"Hipermaxi\" en tiendas oficiales\n\n**No compartas:**\n- Contraseñas\n- Códigos de verificación\n- Datos de tarjetas\n\n### ¿Fuiste víctima?\n1. Cambia tu contraseña inmediatamente\n2. Reporta a hipermaxi@hipermaxi.com\n3. Denuncia a la policía si perdiste dinero";
    }
}
