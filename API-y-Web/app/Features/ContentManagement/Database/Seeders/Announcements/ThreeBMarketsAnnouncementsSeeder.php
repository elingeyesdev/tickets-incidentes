<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders\Announcements;

use App\Features\ContentManagement\Models\Announcement;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

/**
 * 3B Markets Announcements Seeder
 *
 * Empresa: 3B Markets (Tiendas 3B Bolivia S.A.)
 * Industria: supermarket
 * Tamaño: PEQUEÑA
 *
 * Volumen: 12 anuncios
 * - MAINTENANCE: 3 (25%)
 * - INCIDENT: 3 (25%)
 * - NEWS: 4 (33%)
 * - ALERT: 2 (17%)
 *
 * Período: 5 enero 2025 - 8 diciembre 2025
 */
class ThreeBMarketsAnnouncementsSeeder extends Seeder
{
    private ?Company $company = null;
    private ?User $author = null;

    public function run(): void
    {
        $this->command->info('📢 Creando anuncios para 3B Markets...');

        // 1. Cargar empresa
        $this->company = Company::where('name', '3B Markets')->first();
        if (!$this->company) {
            $this->command->error('❌ Empresa 3B Markets no encontrada.');
            return;
        }

        // 2. Idempotencia
        if (Announcement::where('company_id', $this->company->id)->exists()) {
            $this->command->info('✓ Anuncios ya existen para 3B Markets. Saltando...');
            return;
        }

        // 3. Obtener autor (Company Admin) usando UserRole
        $adminRole = \App\Features\UserManagement\Models\UserRole::where('company_id', $this->company->id)
            ->where('role_code', 'COMPANY_ADMIN')
            ->where('is_active', true)
            ->first();

        if (!$adminRole) {
            $this->command->error('❌ No se encontró el admin de 3B Markets.');
            return;
        }

        $this->author = User::find($adminRole->user_id);

        // 4. Crear anuncios
        $this->createAnnouncements();

        $this->command->info('✅ 12 anuncios creados para 3B Markets.');
    }

    private function createAnnouncements(): void
    {
        $announcements = [
            // ══════════════════════════════════════════════════════════════
            // MAINTENANCE (3 anuncios - 25%)
            // ══════════════════════════════════════════════════════════════
            [
                'type' => 'MAINTENANCE',
                'title' => 'Mantenimiento programado de sistema de cajas - Domingo 26/01',
                'content' => "Estimados clientes,\n\nLes informamos que el **domingo 26 de enero de 2025** realizaremos mantenimiento preventivo en nuestro sistema de puntos de venta.\n\n**Horario:** 02:00 AM - 05:00 AM\n\n**Afectación:**\n- Cobros con tarjeta pueden presentar lentitud\n- Sistema de facturación electrónica temporalmente fuera de línea\n\n**Recomendación:** Si realiza compras en ese horario, tenga efectivo disponible como alternativa.\n\nAgradecemos su comprensión.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'LOW',
                    'scheduled_start' => '2025-01-26T02:00:00-04:00',
                    'scheduled_end' => '2025-01-26T05:00:00-04:00',
                    'actual_start' => '2025-01-26T02:05:00-04:00',
                    'actual_end' => '2025-01-26T04:45:00-04:00',
                    'is_emergency' => false,
                    'affected_services' => ['sistema_cajas', 'cobro_tarjetas', 'facturacion_electronica'],
                ],
                'created_at' => '2025-01-20 09:00:00',
                'published_at' => '2025-01-20 10:00:00',
            ],
            [
                'type' => 'MAINTENANCE',
                'title' => 'Actualización de precios y sistema de inventarios - Sábado 15/03',
                'content' => "Comunicamos que el **sábado 15 de marzo** realizaremos actualización masiva de precios y sincronización de inventarios en todas las sucursales.\n\n**Horario:** 03:00 AM - 06:00 AM\n\n**Afectación:**\n- Etiquetas de precios pueden mostrar valores antiguos temporalmente\n- Sistema de consulta de stock no disponible\n\nEste mantenimiento es parte de nuestra mejora continua para ofrecer precios siempre actualizados.\n\nGracias por su preferencia.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-03-15T03:00:00-04:00',
                    'scheduled_end' => '2025-03-15T06:00:00-04:00',
                    'actual_start' => '2025-03-15T03:00:00-04:00',
                    'actual_end' => '2025-03-15T05:30:00-04:00',
                    'is_emergency' => false,
                    'affected_services' => ['sistema_precios', 'inventarios', 'consulta_stock'],
                ],
                'created_at' => '2025-03-10 08:30:00',
                'published_at' => '2025-03-10 09:00:00',
            ],
            [
                'type' => 'MAINTENANCE',
                'title' => 'Mantenimiento de equipos de refrigeración - Sucursales Zona Norte',
                'content' => "Informamos que el **miércoles 18 de junio** personal técnico realizará mantenimiento preventivo a los equipos de refrigeración en las sucursales de la Zona Norte de Santa Cruz.\n\n**Horario:** 22:00 PM - 04:00 AM (siguiente día)\n\n**Sucursales afectadas:**\n- 3B Banzer Km 4\n- 3B Villa 1ro de Mayo\n- 3B Radial 17 1/2\n\n**Nota:** Durante este período, algunos productos refrigerados pueden no estar disponibles temporalmente.\n\nAgradecemos su comprensión.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'LOW',
                    'scheduled_start' => '2025-06-18T22:00:00-04:00',
                    'scheduled_end' => '2025-06-19T04:00:00-04:00',
                    'actual_start' => '2025-06-18T22:15:00-04:00',
                    'actual_end' => '2025-06-19T03:45:00-04:00',
                    'is_emergency' => false,
                    'affected_services' => ['refrigeracion_zona_norte', 'productos_perecederos'],
                ],
                'created_at' => '2025-06-12 10:00:00',
                'published_at' => '2025-06-12 14:00:00',
            ],

            // ══════════════════════════════════════════════════════════════
            // INCIDENT (3 anuncios - 25%)
            // ══════════════════════════════════════════════════════════════
            [
                'type' => 'INCIDENT',
                'title' => 'URGENTE: Intermitencia en cobros con tarjeta a nivel nacional',
                'content' => "**⚠️ INCIDENTE EN CURSO**\n\nEstamos experimentando problemas con nuestro proveedor de servicios de cobro con tarjeta de débito y crédito.\n\n**Inicio:** 15 de mayo de 2025, 14:30\n\n**Afectación:**\n- Cobros con tarjeta pueden fallar o demorar\n- QR de billeteras móviles funcionando intermitentemente\n\n**Recomendación:** Por favor, tenga efectivo disponible para sus compras mientras resolvemos el problema.\n\nNuestro equipo técnico está trabajando con el proveedor para restablecer el servicio lo antes posible.\n\n**Última actualización:** 15:45 - Continuamos trabajando en la solución.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'CRITICAL',
                    'resolution_content' => 'Servicio de cobro con tarjeta restablecido completamente. La falla fue causada por problemas en la red del proveedor de POS. Todas las transacciones pendientes fueron procesadas correctamente.',
                    'affected_services' => ['cobro_tarjetas', 'pos_nacional', 'qr_billeteras'],
                    'started_at' => '2025-05-15T14:30:00-04:00',
                    'resolved_at' => '2025-05-15T18:45:00-04:00',
                ],
                'created_at' => '2025-05-15 14:35:00',
                'published_at' => '2025-05-15 14:40:00',
            ],
            [
                'type' => 'INCIDENT',
                'title' => 'Problema con sistema de facturación electrónica - RESUELTO',
                'content' => "Informamos que durante la mañana del **23 de julio** experimentamos problemas con el sistema de facturación electrónica del SIN (Servicio de Impuestos Nacionales).\n\n**Período afectado:** 09:00 AM - 11:30 AM\n\n**Afectación:**\n- Facturas emitidas con demora\n- Algunos clientes recibieron facturas sin código QR temporalmente\n\n**Estado:** ✅ RESUELTO\n\nEl problema fue causado por saturación en los servidores del SIN. Todas las facturas fueron regularizadas.\n\nSi tiene algún inconveniente con su factura de ese día, acérquese a cualquier sucursal con su comprobante.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'resolution_content' => 'Sistema de facturación restablecido. Todas las facturas pendientes fueron procesadas y enviadas a los clientes.',
                    'affected_services' => ['facturacion_electronica', 'sin_integracion'],
                    'started_at' => '2025-07-23T09:00:00-04:00',
                    'resolved_at' => '2025-07-23T11:30:00-04:00',
                ],
                'created_at' => '2025-07-23 09:15:00',
                'published_at' => '2025-07-23 09:20:00',
            ],
            [
                'type' => 'INCIDENT',
                'title' => 'Falla en sistema de aire acondicionado - Sucursal 4to Anillo',
                'content' => "Comunicamos que la sucursal **3B 4to Anillo** presenta una falla temporal en el sistema de aire acondicionado.\n\n**Inicio:** 10 de octubre, 10:00 AM\n\n**Estado actual:** En reparación\n\n**Nota:** La tienda permanece abierta con ventilación alternativa. Algunos productos sensibles al calor pueden tener disponibilidad limitada.\n\nPedimos disculpas por las molestias. Nuestro equipo de mantenimiento está trabajando para resolver el problema hoy mismo.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'resolution_content' => 'Sistema de aire acondicionado reparado. La sucursal opera con normalidad.',
                    'affected_services' => ['climatizacion_4to_anillo', 'productos_refrigerados'],
                    'started_at' => '2025-10-10T10:00:00-04:00',
                    'resolved_at' => '2025-10-10T16:30:00-04:00',
                ],
                'created_at' => '2025-10-10 10:15:00',
                'published_at' => '2025-10-10 10:20:00',
            ],

            // ══════════════════════════════════════════════════════════════
            // NEWS (4 anuncios - 33%)
            // ══════════════════════════════════════════════════════════════
            [
                'type' => 'NEWS',
                'title' => '¡Nuevos productos Mr. Power para el cuidado del hogar!',
                'content' => "Nos complace anunciar la ampliación de nuestra línea de productos de limpieza **Mr. Power**.\n\n**Nuevos productos disponibles:**\n- 🧹 Limpiador multiusos con aroma lavanda\n- 🍋 Desengrasante de cocina concentrado\n- 🌸 Suavizante de ropa \"Frescura Primaveral\"\n- 🧴 Jabón líquido para manos antibacterial\n\n**Precio especial de lanzamiento:** 15% de descuento durante febrero.\n\nEncuentra estos productos en la sección de limpieza de todas nuestras tiendas.\n\n¡Calidad 3B al mejor precio!",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Nueva línea de productos de limpieza Mr. Power con descuento de lanzamiento del 15%.',
                    'call_to_action' => 'Visita tu tienda 3B más cercana',
                ],
                'created_at' => '2025-02-05 08:00:00',
                'published_at' => '2025-02-05 09:00:00',
            ],
            [
                'type' => 'NEWS',
                'title' => '¡Celebramos nuestro Aniversario con la Semana del Ahorro 3B!',
                'content' => "🎉 **¡Estamos de fiesta!** 🎉\n\n3B Markets celebra su aniversario y queremos festejarlo contigo.\n\n**Del 10 al 17 de Agosto de 2025**\n\n**Ofertas especiales:**\n- 🍚 Arroz San Felipe 1kg: Bs. 8.50 (antes Bs. 10.50)\n- 🍝 Fideo San Felipe 400g: Bs. 4.00 (antes Bs. 5.50)\n- 🧴 Pack Mr. Power 3 productos: 2x1\n- 🥛 Leche PIL 1L: Bs. 7.00\n- 🛢️ Aceite Fino 1L: Precio de costo\n\n**Límite:** 5 unidades por producto por cliente.\n\n¡Los esperamos en todas nuestras sucursales!",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Promociones especiales por aniversario de 3B Markets del 10 al 17 de agosto.',
                    'call_to_action' => '¡No te pierdas estas ofertas únicas!',
                ],
                'created_at' => '2025-08-05 08:00:00',
                'published_at' => '2025-08-08 08:00:00',
            ],
            [
                'type' => 'NEWS',
                'title' => 'Nueva sucursal 3B en Montero - ¡Gran Inauguración!',
                'content' => "📍 **¡Llegamos a Montero!**\n\nNos complace anunciar la apertura de nuestra nueva sucursal en la ciudad de Montero.\n\n**Dirección:** Av. Warnes esq. Calle Sucre, Zona Central\n\n**Fecha de inauguración:** Sábado 20 de septiembre de 2025\n\n**Horario de atención:**\n- Lunes a Sábado: 08:00 - 21:00\n- Domingos: 09:00 - 13:00\n\n**Promociones de inauguración:**\n- 20% de descuento en toda la tienda el día de apertura\n- Regalos sorpresa para los primeros 100 clientes\n- Degustaciones de productos San Felipe\n\n¡Te esperamos!",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Apertura de nueva sucursal 3B en Montero con promociones de inauguración.',
                    'call_to_action' => 'Visita nuestra nueva tienda en Montero',
                ],
                'created_at' => '2025-09-10 10:00:00',
                'published_at' => '2025-09-12 08:00:00',
            ],
            [
                'type' => 'NEWS',
                'title' => 'Actualización de política de devoluciones y cambios',
                'content' => "Estimados clientes,\n\nLes informamos sobre actualizaciones en nuestra **Política de Devoluciones y Cambios**, efectivas desde el 1 de noviembre de 2025.\n\n**Cambios principales:**\n\n1. **Plazo extendido:** Ahora tiene 7 días (antes 3) para realizar cambios o devoluciones.\n\n2. **Productos perecederos:** Devolución dentro de 24 horas con factura original.\n\n3. **Productos no alimenticios:** Cambio o devolución hasta 15 días después de la compra.\n\n**Requisitos:**\n- Factura o comprobante de compra\n- Producto en empaque original (si aplica)\n- Identificación del comprador\n\nPara más información, consulte en cualquier sucursal o escriba a nuestro soporte.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'policy_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Actualización de política de devoluciones con plazos extendidos para beneficio del cliente.',
                    'call_to_action' => 'Consulta los detalles en tu sucursal más cercana',
                ],
                'created_at' => '2025-10-20 09:00:00',
                'published_at' => '2025-10-25 08:00:00',
            ],

            // ══════════════════════════════════════════════════════════════
            // ALERT (2 anuncios - 17%)
            // ══════════════════════════════════════════════════════════════
            [
                'type' => 'ALERT',
                'title' => '⚠️ ALERTA: Retiro voluntario de lote de yogurt',
                'content' => "**AVISO IMPORTANTE DE SEGURIDAD ALIMENTARIA**\n\nPor precaución, estamos retirando voluntariamente el siguiente producto:\n\n**Producto:** Yogurt Bebible Frutilla 1L - Marca PIL\n**Lote afectado:** YG-2025-0847\n**Fecha de vencimiento:** 15/04/2025\n\n**Motivo:** Posible alteración de sabor detectada en control de calidad.\n\n**Qué hacer si compró este producto:**\n1. Verifique el número de lote en el empaque\n2. Si coincide, NO lo consuma\n3. Devuélvalo en cualquier sucursal 3B para reembolso completo\n\n**No se requiere factura para la devolución de este lote.**\n\nPedimos disculpas por cualquier inconveniente.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'CRITICAL',
                    'alert_type' => 'security',
                    'message' => 'Retiro voluntario de lote de yogurt por precaución de calidad',
                    'action_required' => true,
                    'action_description' => 'Verificar lote y devolver producto si corresponde',
                    'started_at' => '2025-04-05T00:00:00-04:00',
                    'ended_at' => '2025-04-30T23:59:59-04:00',
                    'affected_services' => ['productos_lacteos', 'seguridad_alimentaria'],
                ],
                'created_at' => '2025-04-05 07:00:00',
                'published_at' => '2025-04-05 08:00:00',
            ],
            [
                'type' => 'ALERT',
                'title' => '🔒 Actualización obligatoria: Nuevo sistema de facturación electrónica',
                'content' => "**AVISO IMPORTANTE**\n\nDe acuerdo con las nuevas disposiciones del Servicio de Impuestos Nacionales (SIN), a partir del **1 de diciembre de 2025** todas las facturas se emitirán bajo el nuevo sistema de facturación electrónica.\n\n**¿Qué significa para usted?**\n\n1. **NIT obligatorio:** Para facturas con nombre, debe proporcionar su NIT.\n2. **Factura digital:** Recibirá su factura por correo electrónico (opcional).\n3. **Código QR:** Todas las facturas incluirán código QR para verificación.\n\n**Acción requerida:**\nSi desea recibir facturas electrónicas por email, registre su correo en caja o en nuestra página web.\n\nPara más información, consulte con nuestro personal o visite www.impuestos.gob.bo",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'compliance',
                    'message' => 'Nuevo sistema de facturación electrónica obligatorio desde diciembre',
                    'action_required' => true,
                    'action_description' => 'Registrar email para recibir facturas electrónicas',
                    'started_at' => '2025-11-01T00:00:00-04:00',
                    'ended_at' => '2025-12-31T23:59:59-04:00',
                    'affected_services' => ['facturacion', 'atencion_cliente'],
                ],
                'created_at' => '2025-10-28 09:00:00',
                'published_at' => '2025-11-01 08:00:00',
            ],
        ];

        foreach ($announcements as $data) {
            Announcement::create([
                'company_id' => $this->company->id,
                'author_id' => $this->author->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => $data['status'],
                'metadata' => $data['metadata'],
                'published_at' => $data['published_at'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);
        }
    }
}
