<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders\Articles;

use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 3B Markets Help Center Articles Seeder
 *
 * Empresa: 3B Markets (Tiendas 3B Bolivia S.A.)
 * Industria: supermarket
 * Tamaño: PEQUEÑA
 *
 * Volumen: 10 artículos (rango 6-17)
 * Categorías obligatorias (mínimo 1 de cada):
 * - ACCOUNT_PROFILE
 * - SECURITY_PRIVACY
 * - BILLING_PAYMENTS
 * - TECHNICAL_SUPPORT
 *
 * Distribución:
 * - TECHNICAL_SUPPORT: 4 artículos (40%)
 * - ACCOUNT_PROFILE: 2 artículos (20%)
 * - BILLING_PAYMENTS: 2 artículos (20%)
 * - SECURITY_PRIVACY: 2 artículos (20%)
 *
 * Estados:
 * - PUBLISHED: 8 (80%)
 * - DRAFT: 2 (20%)
 *
 * Período: 5 enero 2025 - 8 diciembre 2025
 */
class ThreeBMarketsHelpCenterArticlesSeeder extends Seeder
{
    private ?Company $company = null;
    private ?User $author = null;
    private array $categories = [];

    public function run(): void
    {
        $this->command->info('📚 Creando artículos Help Center para 3B Markets...');

        // 1. Cargar empresa
        $this->company = Company::where('company_code', 'CMP-2025-00010')->first();
        if (!$this->company) {
            $this->command->error('❌ Empresa 3B Markets no encontrada.');
            return;
        }

        // 2. Idempotencia
        if (HelpCenterArticle::where('company_id', $this->company->id)->exists()) {
            $this->command->info('[OK] Artículos ya existen para 3B Markets. Saltando...');
            return;
        }

        // 3. Obtener autor (Company Admin)
        $this->author = User::where('email', 'roberto.gomez@tiendas3b.com.bo')->first();
        if (!$this->author) {
            $this->command->error('❌ Admin no encontrado.');
            return;
        }

        // 4. Cargar categorías globales
        $this->loadCategories();

        // 5. Crear artículos
        $this->createArticles();

        $this->command->info('✅ 10 artículos creados para 3B Markets.');
    }

    private function loadCategories(): void
    {
        $categoryKeys = [
            'ACCOUNT_PROFILE' => 'Cuenta y Perfil',
            'SECURITY_PRIVACY' => 'Seguridad y Privacidad',
            'BILLING_PAYMENTS' => 'Facturación y Pagos',
            'TECHNICAL_SUPPORT' => 'Soporte Técnico',
        ];

        foreach ($categoryKeys as $code => $name) {
            $category = ArticleCategory::where('code', $code)->first();
            if (!$category) {
                $category = ArticleCategory::create([
                    'code' => $code,
                    'name' => $name,
                    'description' => "Categoría de artículos: $name",
                    'is_active' => true,
                ]);
            }
            $this->categories[$code] = $category;
        }
    }

    private function createArticles(): void
    {
        $articles = [
            // ══════════════════════════════════════════════════════════════
            // TECHNICAL_SUPPORT (4 artículos - 40%)
            // ══════════════════════════════════════════════════════════════

            // Artículo 1: Respuesta al INCIDENT de mayo (falla POS)
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => '¿Qué hacer si mi pago con tarjeta falla en caja?',
                'slug' => 'que-hacer-pago-tarjeta-falla-caja',
                'excerpt' => 'Guía paso a paso para resolver problemas al momento de pagar con tarjeta de débito o crédito en nuestras sucursales.',
                'content' => '
<h2>Problemas comunes al pagar con tarjeta</h2>

<p>Si experimenta dificultades al pagar con tarjeta en nuestras cajas, aquí le explicamos qué hacer en cada caso.</p>

<h3>1. La terminal muestra "Transacción rechazada"</h3>
<p>Esto puede ocurrir por varias razones:</p>
<ul>
    <li><strong>Fondos insuficientes:</strong> Verifique el saldo de su cuenta bancaria.</li>
    <li><strong>Límite de transacciones:</strong> Algunos bancos limitan el monto o número de compras diarias.</li>
    <li><strong>Tarjeta bloqueada:</strong> Contacte a su banco para verificar el estado de su tarjeta.</li>
</ul>

<h3>2. La transacción "queda en proceso" o se congela</h3>
<p>En este caso:</p>
<ol>
    <li>NO pase la tarjeta nuevamente (evita cobros duplicados).</li>
    <li>Espere a que el cajero consulte con el sistema.</li>
    <li>Si no hay confirmación, solicite pagar en efectivo.</li>
    <li>Verifique su extracto bancario en las siguientes horas.</li>
</ol>

<h3>3. Se realizó un cobro duplicado</h3>
<p>Si nota que le cobraron dos veces:</p>
<ol>
    <li>Conserve su factura y comprobante de la transacción.</li>
    <li>Tome captura de su extracto bancario.</li>
    <li>Reporte el caso en cualquier sucursal o a través de nuestro soporte.</li>
    <li>El reembolso se procesa en 5-10 días hábiles bancarios.</li>
</ol>

<h3>Alternativas de pago</h3>
<p>Si su tarjeta presenta problemas, puede pagar con:</p>
<ul>
    <li>Efectivo (bolivianos)</li>
    <li>Código QR de billeteras móviles (Tigo Money, etc.)</li>
    <li>Otra tarjeta de débito o crédito</li>
</ul>

<h3>¿Necesita más ayuda?</h3>
<p>Contacte a nuestro equipo de soporte para asistencia personalizada.</p>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 5, 20, 9, 0, 0),
                'published_at' => Carbon::create(2025, 5, 25, 8, 0, 0),
                'views_count' => 87,
            ],

            // Artículo 2: Respuesta a tickets sobre productos en mal estado
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => '¿Cómo reportar un producto en mal estado o defectuoso?',
                'slug' => 'como-reportar-producto-mal-estado',
                'excerpt' => 'Procedimiento para reportar productos vencidos, dañados o con problemas de calidad comprados en nuestras tiendas.',
                'content' => '
<h2>Nuestra garantía de calidad</h2>

<p>En 3B Markets nos comprometemos con la calidad de todos nuestros productos. Si encuentra un artículo en mal estado, queremos saberlo y resolverlo.</p>

<h3>Productos que puede reportar</h3>
<ul>
    <li>Alimentos perecederos en mal estado (lácteos, carnes, frutas)</li>
    <li>Productos con empaque dañado o roto</li>
    <li>Artículos vencidos</li>
    <li>Productos con plagas (insectos, etc.)</li>
    <li>Cualquier producto que no cumpla con su calidad esperada</li>
</ul>

<h3>¿Cómo reportar?</h3>

<h4>Opción 1: En sucursal (más rápida)</h4>
<ol>
    <li>Lleve el producto a cualquier sucursal 3B.</li>
    <li>Presente su factura de compra (si la tiene).</li>
    <li>Solicite hablar con el encargado de tienda.</li>
    <li>Recibirá reembolso o cambio inmediato.</li>
</ol>

<h4>Opción 2: Por soporte online</h4>
<ol>
    <li>Tome fotos del producto y del problema.</li>
    <li>Fotografíe la fecha de vencimiento y número de lote.</li>
    <li>Envíe un ticket de soporte con la información.</li>
    <li>Coordinaremos el reembolso o cambio.</li>
</ol>

<h3>¿Qué información incluir en su reporte?</h3>
<ul>
    <li>Nombre del producto</li>
    <li>Sucursal donde lo compró</li>
    <li>Fecha de compra (aproximada está bien)</li>
    <li>Descripción del problema</li>
    <li>Fotos si es posible</li>
</ul>

<h3>Tiempo de resolución</h3>
<ul>
    <li><strong>En sucursal:</strong> Inmediato</li>
    <li><strong>Por soporte:</strong> 1-3 días hábiles</li>
</ul>

<p><strong>Nota sobre productos sin factura:</strong> En casos de problemas graves de calidad (contaminación, plagas), procesamos el reembolso incluso sin factura.</p>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 4, 10, 10, 0, 0),
                'published_at' => Carbon::create(2025, 4, 18, 8, 0, 0),
                'views_count' => 65,
            ],

            // Artículo 3: Sobre cadena de frío
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => 'Guía para verificar la calidad de productos refrigerados',
                'slug' => 'guia-verificar-calidad-productos-refrigerados',
                'excerpt' => 'Consejos para identificar productos frescos y refrigerados en buen estado antes de comprarlos.',
                'content' => '
<h2>¿Cómo elegir productos frescos?</h2>

<p>Le compartimos una guía práctica para verificar la calidad de productos refrigerados antes de agregarlos a su carrito.</p>

<h3>Lácteos (leche, yogurt, queso)</h3>
<ul>
    <li><strong>Fecha de vencimiento:</strong> Revise siempre la fecha. Elija los de fecha más lejana.</li>
    <li><strong>Empaque:</strong> Sin hinchazón, abolladuras ni humedad exterior.</li>
    <li><strong>Temperatura:</strong> El refrigerador de la tienda debe estar frío al tacto.</li>
</ul>

<h3>Carnes y embutidos</h3>
<ul>
    <li><strong>Color:</strong> Rojo brillante para res, rosado para cerdo y pollo. Evite colores grisáceos.</li>
    <li><strong>Olor:</strong> No debe tener olor fuerte o desagradable.</li>
    <li><strong>Textura:</strong> Firme al tacto, no babosa ni pegajosa.</li>
    <li><strong>Empaque:</strong> Sin líquido excesivo ni roturas.</li>
</ul>

<h3>Frutas y verduras</h3>
<ul>
    <li><strong>Firmeza:</strong> Sin partes blandas ni magulladuras.</li>
    <li><strong>Apariencia:</strong> Sin moho, manchas oscuras ni insectos.</li>
    <li><strong>Hojas:</strong> En verduras de hoja, deben verse frescas, no marchitas.</li>
</ul>

<h3>¿Encontró un producto en mal estado?</h3>
<p>Si identifica un producto que no cumple con estas condiciones:</p>
<ol>
    <li>Informe a cualquier colaborador de la tienda.</li>
    <li>Ellos retirarán el producto y verificarán el lote.</li>
    <li>Agradecemos estos reportes porque nos ayudan a mantener la calidad.</li>
</ol>

<h3>Nuestro compromiso</h3>
<p>3B Markets realiza controles de temperatura y frescura varias veces al día. Si a pesar de esto encuentra un producto en mal estado después de comprarlo, lo cambiaremos o reembolsaremos sin problema.</p>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 6, 25, 14, 0, 0),
                'published_at' => Carbon::create(2025, 7, 2, 9, 0, 0),
                'views_count' => 42,
            ],

            // Artículo 4: Sobre sucursales y horarios
            [
                'category' => 'TECHNICAL_SUPPORT',
                'title' => 'Horarios de atención y ubicación de sucursales 3B',
                'slug' => 'horarios-atencion-ubicacion-sucursales',
                'excerpt' => 'Encuentre la sucursal 3B más cercana y conozca nuestros horarios de atención regulares y en feriados.',
                'content' => '
<h2>Nuestras sucursales en Santa Cruz</h2>

<h3>Zona Norte</h3>
<ul>
    <li><strong>3B Banzer Km 4:</strong> Av. Banzer entre 3er y 4to anillo</li>
    <li><strong>3B Villa 1ro de Mayo:</strong> Av. Principal, Zona Villa 1ro de Mayo</li>
    <li><strong>3B Radial 17 1/2:</strong> Radial 17 1/2, cerca del mercado</li>
</ul>

<h3>Zona Sur</h3>
<ul>
    <li><strong>3B 4to Anillo:</strong> 4to Anillo entre Av. San Martín y Piraí</li>
</ul>

<h3>Otras ciudades</h3>
<ul>
    <li><strong>Montero:</strong> Av. Warnes esq. Calle Sucre (Zona Central)</li>
    <li><strong>El Torno:</strong> Próximamente</li>
</ul>

<h3>Horarios regulares</h3>
<table>
    <tr><th>Día</th><th>Horario</th></tr>
    <tr><td>Lunes a Sábado</td><td>08:00 - 21:00</td></tr>
    <tr><td>Domingos</td><td>09:00 - 13:00</td></tr>
</table>

<h3>Horarios en feriados</h3>
<p>En feriados nacionales, nuestras tiendas atienden en horario especial:</p>
<ul>
    <li><strong>Año Nuevo, Carnaval, Viernes Santo:</strong> Cerrado</li>
    <li><strong>Otros feriados:</strong> 08:00 - 14:00</li>
</ul>

<h3>¿Cómo confirmar horarios específicos?</h3>
<p>Para confirmar horarios en fechas especiales, puede:</p>
<ul>
    <li>Revisar nuestros anuncios en la sección de Noticias</li>
    <li>Enviar una consulta a soporte</li>
    <li>Llamar directamente a la sucursal</li>
</ul>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 1, 10, 10, 0, 0),
                'published_at' => Carbon::create(2025, 1, 15, 8, 0, 0),
                'views_count' => 120,
            ],

            // ══════════════════════════════════════════════════════════════
            // BILLING_PAYMENTS (2 artículos - 20%)
            // ══════════════════════════════════════════════════════════════

            // Artículo 5: Política de reembolsos
            [
                'category' => 'BILLING_PAYMENTS',
                'title' => 'Política de devoluciones, cambios y reembolsos',
                'slug' => 'politica-devoluciones-cambios-reembolsos',
                'excerpt' => 'Conozca nuestros plazos, requisitos y procedimientos para devolver o cambiar productos comprados en 3B Markets.',
                'content' => '
<h2>Nuestra política de satisfacción</h2>

<p>En 3B Markets queremos que esté 100% satisfecho con sus compras. Si necesita devolver o cambiar un producto, aquí le explicamos cómo hacerlo.</p>

<h3>Plazos para devoluciones y cambios</h3>

<table>
    <tr><th>Tipo de producto</th><th>Plazo</th></tr>
    <tr><td>Productos perecederos (lácteos, carnes, etc.)</td><td>24 horas</td></tr>
    <tr><td>Alimentos no perecederos</td><td>7 días</td></tr>
    <tr><td>Productos de limpieza y cuidado personal</td><td>15 días</td></tr>
    <tr><td>Otros productos no alimenticios</td><td>15 días</td></tr>
</table>

<h3>Requisitos para devolución</h3>
<ol>
    <li><strong>Factura o comprobante de compra</strong> (original)</li>
    <li><strong>Producto en empaque original</strong> (si aplica)</li>
    <li><strong>Identificación</strong> del comprador</li>
</ol>

<h3>¿Qué productos NO se pueden devolver?</h3>
<ul>
    <li>Productos consumidos parcialmente (excepto por problemas de calidad)</li>
    <li>Artículos de higiene personal abiertos</li>
    <li>Productos con promoción "sin devolución" (claramente señalizados)</li>
</ul>

<h3>Opciones de reembolso</h3>
<ul>
    <li><strong>Efectivo:</strong> Si pagó en efectivo</li>
    <li><strong>Crédito a tarjeta:</strong> Si pagó con tarjeta (5-10 días hábiles)</li>
    <li><strong>Vale de compra:</strong> Puede solicitar un vale por el monto</li>
</ul>

<h3>Devoluciones por problemas de calidad</h3>
<p>Si el producto tiene un defecto de calidad (vencido, dañado, contaminado):</p>
<ul>
    <li>No tiene límite de tiempo (dentro de lo razonable)</li>
    <li>La factura es opcional en casos graves</li>
    <li>Puede elegir reembolso o cambio + compensación</li>
</ul>

<h3>¿Dónde realizar devoluciones?</h3>
<p>En cualquier sucursal 3B, en el área de Atención al Cliente o directamente en caja.</p>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 10, 25, 10, 0, 0),
                'published_at' => Carbon::create(2025, 11, 1, 8, 0, 0),
                'views_count' => 58,
            ],

            // Artículo 6: Facturación electrónica (respuesta a ALERT)
            [
                'category' => 'BILLING_PAYMENTS',
                'title' => 'Nuevo sistema de facturación electrónica: Todo lo que debe saber',
                'slug' => 'nuevo-sistema-facturacion-electronica-guia',
                'excerpt' => 'Guía completa sobre el nuevo sistema de facturación electrónica, cómo registrar su email y qué cambia para usted.',
                'content' => '
<h2>Facturación electrónica 2025</h2>

<p>A partir del 1 de diciembre de 2025, 3B Markets emite todas las facturas bajo el nuevo sistema de facturación electrónica del SIN.</p>

<h3>¿Qué cambia para usted?</h3>

<h4>Facturas con NIT</h4>
<ul>
    <li>Ahora es obligatorio proporcionar su NIT para facturas a nombre</li>
    <li>Puede recibir su factura por email automáticamente</li>
    <li>Todas las facturas incluyen código QR para verificación</li>
</ul>

<h4>Facturas sin NIT</h4>
<ul>
    <li>Se emite como "Consumidor Final"</li>
    <li>No requiere registro de email</li>
    <li>Válida para sus registros personales</li>
</ul>

<h3>¿Cómo registrar mi email para facturas digitales?</h3>

<h4>Opción 1: En caja</h4>
<ol>
    <li>Al momento de pagar, solicite registrar su email</li>
    <li>Proporcione su NIT y dirección de correo</li>
    <li>A partir de ese momento, recibirá todas sus facturas por email</li>
</ol>

<h4>Opción 2: Por soporte</h4>
<ol>
    <li>Envíe un ticket con su NIT, nombre completo y email</li>
    <li>Le confirmaremos el registro en 24 horas</li>
</ol>

<h3>Beneficios de la factura electrónica</h3>
<ul>
    <li>Reciba su factura instantáneamente por email</li>
    <li>Acceso a historial de compras</li>
    <li>Más ecológico (menos papel)</li>
    <li>Ofertas exclusivas para usuarios registrados</li>
</ul>

<h3>¿Puedo seguir pidiendo factura impresa?</h3>
<p>Sí. Aunque esté registrado para facturas digitales, siempre puede solicitar una copia impresa en caja.</p>

<h3>Validación de facturas</h3>
<p>Puede verificar cualquier factura en el portal del SIN: <strong>www.impuestos.gob.bo</strong> escaneando el código QR.</p>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 11, 5, 9, 0, 0),
                'published_at' => Carbon::create(2025, 11, 10, 8, 0, 0),
                'views_count' => 34,
            ],

            // ══════════════════════════════════════════════════════════════
            // ACCOUNT_PROFILE (2 artículos - 20%)
            // ══════════════════════════════════════════════════════════════

            // Artículo 7: Registro y beneficios
            [
                'category' => 'ACCOUNT_PROFILE',
                'title' => '¿Cómo crear una cuenta y cuáles son los beneficios?',
                'slug' => 'como-crear-cuenta-beneficios',
                'excerpt' => 'Aprenda a registrarse en 3B Markets y descubra los beneficios exclusivos para clientes registrados.',
                'content' => '
<h2>Únase a la comunidad 3B</h2>

<p>Crear una cuenta en 3B Markets es gratuito y le da acceso a beneficios exclusivos.</p>

<h3>Beneficios de tener cuenta</h3>
<ul>
    <li>🎁 <strong>Ofertas exclusivas</strong> por email</li>
    <li>📧 <strong>Facturas electrónicas</strong> automáticas</li>
    <li>🔔 <strong>Notificaciones</strong> de promociones y eventos</li>
    <li>💬 <strong>Soporte prioritario</strong> por ticket</li>
    <li>📊 <strong>Historial de compras</strong> (próximamente)</li>
</ul>

<h3>¿Cómo registrarse?</h3>

<h4>Opción 1: En línea</h4>
<ol>
    <li>Visite nuestra página de registro</li>
    <li>Complete sus datos personales</li>
    <li>Verifique su email</li>
    <li>¡Listo! Ya puede acceder a su cuenta</li>
</ol>

<h4>Opción 2: En sucursal</h4>
<ol>
    <li>Solicite registrarse al momento de pagar</li>
    <li>Proporcione su email y datos básicos</li>
    <li>Recibirá un email de confirmación</li>
</ol>

<h3>Datos requeridos para el registro</h3>
<ul>
    <li>Nombre completo</li>
    <li>Email válido</li>
    <li>Número de celular (opcional)</li>
    <li>NIT (opcional, para facturas)</li>
</ul>

<h3>Privacidad de sus datos</h3>
<p>Sus datos están protegidos y solo los usamos para mejorar su experiencia de compra. Nunca compartimos información con terceros sin su consentimiento.</p>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 2, 10, 11, 0, 0),
                'published_at' => Carbon::create(2025, 2, 18, 8, 0, 0),
                'views_count' => 45,
            ],

            // Artículo 8: Actualizar datos
            [
                'category' => 'ACCOUNT_PROFILE',
                'title' => '¿Cómo actualizar mis datos personales o email?',
                'slug' => 'como-actualizar-datos-personales-email',
                'excerpt' => 'Guía para modificar su información personal, cambiar su email o actualizar su NIT en su cuenta 3B.',
                'content' => '
<h2>Mantenga sus datos actualizados</h2>

<p>Es importante que su información esté actualizada para recibir sus facturas correctamente y no perderse nuestras ofertas.</p>

<h3>¿Qué datos puede actualizar?</h3>
<ul>
    <li>Nombre completo</li>
    <li>Dirección de email</li>
    <li>Número de teléfono</li>
    <li>NIT para facturación</li>
    <li>Preferencias de notificación</li>
</ul>

<h3>¿Cómo actualizar mis datos?</h3>

<h4>Por soporte (recomendado)</h4>
<ol>
    <li>Envíe un ticket indicando qué datos desea cambiar</li>
    <li>Proporcione sus datos actuales para verificación</li>
    <li>Indique los nuevos datos</li>
    <li>Le confirmaremos el cambio en 24-48 horas</li>
</ol>

<h4>En sucursal</h4>
<ol>
    <li>Acérquese a cualquier caja</li>
    <li>Solicite actualizar sus datos</li>
    <li>Presente identificación si es un cambio mayor</li>
</ol>

<h3>Cambio de email</h3>
<p>Si necesita cambiar su email:</p>
<ol>
    <li>Le enviaremos un código de verificación a su email actual</li>
    <li>Después de confirmar, se actualizará al nuevo email</li>
    <li>Recibirá confirmación en ambas direcciones</li>
</ol>

<h3>⚠️ Importante</h3>
<p>Si perdió acceso a su email anterior, necesitará verificar su identidad proporcionando:</p>
<ul>
    <li>NIT registrado</li>
    <li>Número de teléfono (si lo registró)</li>
    <li>Número de factura reciente</li>
</ul>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 3, 15, 10, 0, 0),
                'published_at' => Carbon::create(2025, 3, 22, 8, 0, 0),
                'views_count' => 28,
            ],

            // ══════════════════════════════════════════════════════════════
            // SECURITY_PRIVACY (2 artículos - 20%)
            // ══════════════════════════════════════════════════════════════

            // Artículo 9: Seguridad alimentaria (respuesta a ALERT de retiro)
            [
                'category' => 'SECURITY_PRIVACY',
                'title' => 'Retiro de productos: Cómo verificar lotes y qué hacer',
                'slug' => 'retiro-productos-verificar-lotes-que-hacer',
                'excerpt' => 'Guía sobre qué hacer cuando anunciamos un retiro voluntario de productos por razones de seguridad.',
                'content' => '
<h2>Seguridad alimentaria en 3B</h2>

<p>Ocasionalmente, por precaución, retiramos voluntariamente productos que podrían tener problemas de calidad. Aquí le explicamos qué hacer.</p>

<h3>¿Cómo saber si hay un retiro activo?</h3>
<ul>
    <li>Publicamos alertas en la sección de <strong>Anuncios</strong></li>
    <li>Enviamos emails a clientes registrados</li>
    <li>Colocamos avisos en tiendas</li>
</ul>

<h3>Información que publicamos</h3>
<ul>
    <li>Nombre exacto del producto</li>
    <li>Marca</li>
    <li>Número de lote afectado</li>
    <li>Fecha de vencimiento del lote</li>
    <li>Motivo del retiro</li>
</ul>

<h3>¿Cómo verificar el lote de mi producto?</h3>
<ol>
    <li>Busque el número de lote en el empaque (usualmente dice "Lote:" o "Lot:")</li>
    <li>Compare con el número mencionado en el anuncio</li>
    <li>Verifique también la fecha de vencimiento</li>
</ol>

<h3>Si su producto está afectado</h3>
<ol>
    <li><strong>NO lo consuma</strong></li>
    <li>Llévelo a cualquier sucursal 3B</li>
    <li>No necesita factura para estos casos</li>
    <li>Le haremos reembolso completo inmediato</li>
</ol>

<h3>¿Y si ya lo consumí?</h3>
<p>En la mayoría de los casos, los retiros son preventivos. Sin embargo:</p>
<ul>
    <li>Si presenta síntomas, consulte a un médico</li>
    <li>Reporte su caso a nuestro soporte</li>
    <li>Cubriremos gastos médicos si el producto causó daño (casos documentados)</li>
</ul>

<h3>Nuestro compromiso</h3>
<p>Realizamos retiros voluntarios porque su seguridad es lo primero. Preferimos actuar con precaución antes que poner en riesgo su salud.</p>
',
                'status' => 'PUBLISHED',
                'created_at' => Carbon::create(2025, 4, 8, 10, 0, 0),
                'published_at' => Carbon::create(2025, 4, 12, 8, 0, 0),
                'views_count' => 72,
            ],

            // Artículo 10: DRAFT (20%) - En elaboración
            [
                'category' => 'SECURITY_PRIVACY',
                'title' => 'Política de privacidad y protección de datos personales',
                'slug' => 'politica-privacidad-proteccion-datos',
                'excerpt' => 'Información sobre cómo 3B Markets protege y utiliza sus datos personales de acuerdo a la normativa vigente.',
                'content' => '
<h2>Su privacidad es importante para nosotros</h2>

<p>[BORRADOR - Artículo en elaboración]</p>

<h3>Datos que recopilamos</h3>
<p>Pendiente de revisión legal...</p>

<h3>Cómo usamos sus datos</h3>
<p>Pendiente de revisión legal...</p>

<h3>Sus derechos</h3>
<p>Pendiente de revisión legal...</p>

<h3>Contacto</h3>
<p>Para consultas sobre privacidad, escriba a soporte...</p>
',
                'status' => 'DRAFT',
                'created_at' => Carbon::create(2025, 11, 20, 14, 0, 0),
                'published_at' => null,
                'views_count' => 0, // DRAFT = 0 views
            ],
        ];

        foreach ($articles as $data) {
            $category = $this->categories[$data['category']];

            HelpCenterArticle::create([
                'company_id' => $this->company->id,
                'author_id' => $this->author->id,
                'category_id' => $category->id,
                'title' => $data['title'],
                'excerpt' => $data['excerpt'],
                'content' => trim($data['content']),
                'status' => $data['status'],
                'views_count' => $data['views_count'],
                'created_at' => $data['created_at'],
                'published_at' => $data['published_at'],
                'updated_at' => $data['created_at']->copy()->addDays(rand(1, 7)),
            ]);

            $statusIcon = $data['status'] === 'PUBLISHED' ? '✓' : '📝';
            $this->command->info("  {$statusIcon} [{$data['category']}] {$data['title']}");
        }
    }
}
