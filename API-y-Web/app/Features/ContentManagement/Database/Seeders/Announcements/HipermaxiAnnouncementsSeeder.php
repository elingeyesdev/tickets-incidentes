<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders\Announcements;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use Illuminate\Database\Seeder;

/**
 * Hipermaxi Announcements Seeder
 *
 * Crea anuncios realistas para Hipermaxi S.A. (cadena de supermercados)
 * Basado en contexto real 2024-2025:
 * - Lanzamiento de plataforma eCommerce
 * - Nueva sucursal en Cochabamba
 * - Servicio de delivery y app móvil
 * - Promociones y ofertas especiales
 *
 * Volumen: 15 anuncios (MAINTENANCE: 4, INCIDENT: 3, NEWS: 5, ALERT: 3)
 * Período: 5 enero 2025 - 8 diciembre 2025
 */
class HipermaxiAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📢 Creando anuncios para Hipermaxi S.A....');

        $company = Company::where('name', 'Hipermaxi S.A.')->first();

        if (!$company) {
            $this->command->error('❌ Hipermaxi S.A. no encontrada.');
            return;
        }

        // Idempotencia
        if (Announcement::where('company_id', $company->id)->exists()) {
            $this->command->info('✓ Anuncios ya existen para Hipermaxi. Saltando...');
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

        $announcements = [
            // ========== MAINTENANCE (4 anuncios - 27%) ==========
            [
                'type' => 'MAINTENANCE',
                'title' => 'Mantenimiento programado App Hipermaxi - Domingo 19/Enero',
                'content' => "Estimados clientes:\n\nRealizaremos mantenimiento programado en nuestra aplicación móvil y plataforma web.\n\n**Fecha:** Domingo 19 de enero de 2025\n**Horario:** 02:00 AM - 06:00 AM\n**Duración:** 4 horas aproximadamente\n\n**Servicios afectados:**\n- App Hipermaxi (Android/iOS)\n- www.hipermaxi.com\n- Pedidos online y delivery\n\n**Servicios NO afectados:**\n- Todas las tiendas físicas operarán normalmente\n- Farmacias Hipermaxi abiertas 24h\n\n**Mejoras que implementaremos:**\n- Mayor velocidad de carga\n- Mejor experiencia de búsqueda de productos\n- Nuevas opciones de pago\n\n¡Gracias por su comprensión!",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'LOW',
                    'scheduled_start' => '2025-01-19T02:00:00Z',
                    'scheduled_end' => '2025-01-19T06:00:00Z',
                    'actual_start' => '2025-01-19T02:05:00Z',
                    'actual_end' => '2025-01-19T05:30:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['app_movil', 'sitio_web', 'delivery'],
                ],
                'created_at' => '2025-01-15 09:00:00',
                'published_at' => '2025-01-15 10:00:00',
            ],
            [
                'type' => 'MAINTENANCE',
                'title' => 'Actualización sistema de cajas - Sucursales Santa Cruz',
                'content' => "Informamos que actualizaremos el sistema de puntos de venta en sucursales de Santa Cruz.\n\n**Fechas:** 15-17 de Marzo 2025\n**Horario:** Durante horario nocturno (22:00 - 06:00)\n**Sucursales:** Todas las de Santa Cruz (12 tiendas)\n\n**Mejoras:**\n- Procesamiento de pagos más rápido\n- Integración mejorada con códigos QR\n- Nuevos terminales POS modernos\n\n**Impacto para clientes:**\n- Durante el día: Operación 100% normal\n- Posibles demoras mínimas en primeras horas tras actualización\n\nLos equipos técnicos estarán en cada sucursal para garantizar una transición sin problemas.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-03-15T22:00:00Z',
                    'scheduled_end' => '2025-03-17T06:00:00Z',
                    'actual_start' => '2025-03-15T22:00:00Z',
                    'actual_end' => '2025-03-17T05:45:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['cajas_registradoras', 'sistema_pos', 'pagos_qr'],
                ],
                'created_at' => '2025-03-10 10:00:00',
                'published_at' => '2025-03-10 14:00:00',
            ],
            [
                'type' => 'MAINTENANCE',
                'title' => 'Renovación cámaras frigoríficas - Sucursal Equipetrol',
                'content' => "Comunicamos que realizaremos renovación de equipos de refrigeración en nuestra sucursal de Equipetrol.\n\n**Fecha:** 5-7 de Junio 2025\n**Sucursal:** Hipermaxi Equipetrol (Av. San Martín)\n\n**Secciones temporalmente limitadas:**\n- Lácteos frescos\n- Carnes y embutidos\n- Productos congelados\n\n**Alternativas para clientes:**\n- Sucursal Ventura Mall (a 10 minutos)\n- Sucursal Urbarí (a 15 minutos)\n- Pedido online con entrega normal\n\n**Nota:** La sección de abarrotes, limpieza, bebidas y farmacia operarán con normalidad.\n\nAgradecemos su comprensión. Esta inversión garantiza la mejor calidad en productos frescos.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-06-05T00:00:00Z',
                    'scheduled_end' => '2025-06-07T23:59:00Z',
                    'actual_start' => '2025-06-05T00:00:00Z',
                    'actual_end' => '2025-06-07T18:00:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['refrigeracion', 'lacteos', 'carnes', 'congelados'],
                ],
                'created_at' => '2025-05-28 09:00:00',
                'published_at' => '2025-05-28 11:00:00',
            ],
            [
                'type' => 'MAINTENANCE',
                'title' => 'Migración de base de datos - App y Web - Noviembre',
                'content' => "Informamos sobre mantenimiento mayor en nuestros sistemas digitales.\n\n**Fecha:** Sábado 15 de Noviembre 2025\n**Horario:** 01:00 AM - 08:00 AM\n**Duración:** 7 horas\n\n**Servicios afectados:**\n- Aplicación móvil Hipermaxi\n- Sitio web hipermaxi.com\n- Sistema de delivery\n- Historial de compras\n\n**Beneficios post-migración:**\n- Sistema más rápido y estable\n- Mayor capacidad para promociones\n- Mejor experiencia de usuario\n\n**Tiendas físicas:** Operación normal 100%\n\nPedimos disculpas por las molestias.",
                'status' => 'SCHEDULED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-11-15T01:00:00Z',
                    'scheduled_end' => '2025-11-15T08:00:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['app_movil', 'sitio_web', 'delivery', 'base_datos'],
                ],
                'created_at' => '2025-11-01 09:00:00',
                'published_at' => '2025-11-01 14:00:00',
            ],

            // ========== INCIDENT (3 anuncios - 20%) ==========
            [
                'type' => 'INCIDENT',
                'title' => 'RESUELTO: Intermitencia en pagos con código QR',
                'content' => "**INCIDENTE RESUELTO**\n\nReportamos incidente que afectó pagos con código QR en nuestras tiendas.\n\n**Cronología:**\n- 09:15 AM: Primeros reportes de rechazos de pago QR\n- 09:30 AM: Identificamos problema en gateway de pagos\n- 10:45 AM: Proveedor resuelve el problema\n- 11:00 AM: Servicio normalizado completamente\n\n**Impacto:**\n- Duración: 1 hora 45 minutos\n- Sucursales afectadas: Todas\n- Pagos en efectivo y tarjeta: Sin afectación\n\n**Causa:**\nProblema técnico del lado del proveedor de pagos QR.\n\n**Acciones tomadas:**\n- Activamos líneas de caja adicionales\n- Ofrecimos descuento 5% a clientes afectados\n\nDisculpas por las molestias ocasionadas.",
                'status' => 'ARCHIVED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'resolution_content' => 'Problema resuelto por proveedor de pagos. Servicio QR normalizado.',
                    'affected_services' => ['pagos_qr', 'cajas'],
                    'started_at' => '2025-02-22T09:15:00Z',
                    'resolved_at' => '2025-02-22T11:00:00Z',
                ],
                'created_at' => '2025-02-22 09:30:00',
                'published_at' => '2025-02-22 09:45:00',
            ],
            [
                'type' => 'INCIDENT',
                'title' => 'EN RESOLUCIÓN: Demoras en entregas por bloqueos de carreteras',
                'content' => "**AVISO IMPORTANTE**\n\nInformamos que estamos experimentando demoras en entregas de pedidos online debido a bloqueos de carreteras.\n\n**Situación actual:**\n- Bloqueos en accesos a El Alto y zona norte de La Paz\n- 45 pedidos con demora de 2-4 horas\n- Entregas en Santa Cruz y Cochabamba: Normales\n\n**Acciones tomadas:**\n- Contactando a cada cliente afectado personalmente\n- Rutas alternativas evaluadas\n- Compensación: Envío gratis en próxima compra\n\n**Pedidos afectados:**\n- La Paz zona norte\n- El Alto todas las zonas\n- Viacha\n\n**Próxima actualización:** En 3 horas\n\nAgradecemos su paciencia.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'affected_services' => ['delivery', 'logistica', 'entregas_domicilio'],
                    'started_at' => '2025-05-08T08:00:00Z',
                    'resolved_at' => null,
                ],
                'created_at' => '2025-05-08 10:00:00',
                'published_at' => '2025-05-08 10:15:00',
            ],
            [
                'type' => 'INCIDENT',
                'title' => 'RESUELTO: Productos faltantes en pedidos online - Problema de sincronización',
                'content' => "**INCIDENTE RESUELTO**\n\nDurante el fin de semana, algunos clientes reportaron productos faltantes en sus pedidos de delivery.\n\n**Problema identificado:**\nError de sincronización entre inventario en tienda y plataforma online, causando que se confirmaran productos sin stock real.\n\n**Impacto:**\n- Período: Viernes 18:00 - Domingo 10:00\n- Pedidos afectados: 127\n- Productos faltantes promedio: 2-3 por pedido\n\n**Resolución:**\n- Corregimos el error de sincronización\n- Llamamos a cada cliente afectado\n- Ofrecimos: Reembolso completo + 15% descuento próxima compra\n\n**Mejoras implementadas:**\n- Sincronización en tiempo real (antes cada 30 min)\n- Alertas automáticas de bajo stock\n\nPedimos disculpas por las molestias.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'resolution_content' => 'Error de sincronización corregido. Sistema actualizado a tiempo real.',
                    'affected_services' => ['inventario_online', 'delivery', 'app_movil'],
                    'started_at' => '2025-07-11T18:00:00Z',
                    'resolved_at' => '2025-07-13T10:00:00Z',
                ],
                'created_at' => '2025-07-13 11:00:00',
                'published_at' => '2025-07-13 12:00:00',
            ],

            // ========== NEWS (5 anuncios - 33%) ==========
            [
                'type' => 'NEWS',
                'title' => '¡Bienvenidos a Hipermaxi Online! Nueva plataforma de eCommerce',
                'content' => "**¡GRAN LANZAMIENTO!**\n\n🛒 Presentamos **Hipermaxi Online**, nuestra nueva plataforma de comercio electrónico.\n\n**Disponible ahora:**\n- App móvil (Android e iOS)\n- Sitio web: www.hipermaxi.com\n\n**Características:**\n- +27,000 productos disponibles\n- Productos frescos, congelados y farmacia\n- Entrega a domicilio o retiro en tienda\n- Múltiples formas de pago: QR, tarjetas, efectivo\n\n**Promoción de lanzamiento:**\n🎉 **Envío GRATIS** en tu primera compra\n🎉 **10% de descuento** con código: ONLINE10\n\n**Cobertura inicial:**\n- Santa Cruz (todas las zonas)\n- La Paz y El Alto\n- Cochabamba\n\n¡Descarga la app y compra desde la comodidad de tu hogar!\n\n**Play Store:** Buscar \"Hipermaxi\"\n**App Store:** Buscar \"Hipermaxi Bolivia\"",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Lanzamiento de plataforma eCommerce con +27,000 productos, delivery y múltiples formas de pago.',
                    'call_to_action' => 'Descarga la app y usa código ONLINE10 para 10% de descuento',
                ],
                'created_at' => '2025-01-10 08:00:00',
                'published_at' => '2025-01-10 09:00:00',
            ],
            [
                'type' => 'NEWS',
                'title' => '¡Nueva sucursal en Cochabamba! Hipermaxi Zona Sur',
                'content' => "**INAUGURACIÓN**\n\n🎊 Hipermaxi abre su **séptima sucursal en Cochabamba**\n\n**Ubicación:**\nAv. Panamericana, Zona Sur de Cochabamba\n\n**Fecha de apertura:** 8 de Abril de 2025\n**Horario:** 8:00 AM - 22:00 PM\n\n**Lo que encontrarás:**\n- +20,000 productos\n- Panadería y pastelería propia\n- Carnicería con cortes premium\n- Farmacia Hipermaxi\n- Estacionamiento amplio (200 espacios)\n\n**Empleos generados:**\n- 130 empleos directos\n- +700 empleos indirectos\n\n**Promociones de inauguración:**\n- 20% descuento en productos seleccionados\n- Sorteo de electrodomésticos (primera semana)\n- Degustaciones gratuitas\n\n¡Te esperamos!\n\n**Hipermaxi - Donde haces rendir más tu dinero**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Nueva sucursal en zona sur de Cochabamba con 20,000 productos y 130 empleos directos.',
                    'call_to_action' => 'Visítanos en Av. Panamericana y aprovecha 20% de descuento inaugural',
                ],
                'created_at' => '2025-04-01 09:00:00',
                'published_at' => '2025-04-01 10:00:00',
            ],
            [
                'type' => 'NEWS',
                'title' => 'Nuevo servicio: Compras desde el extranjero para tu familia en Bolivia',
                'content' => "**NUEVO SERVICIO**\n\n🌎 ¿Vives en el extranjero? Ahora puedes enviar compras a tu familia en Bolivia.\n\n**¿Cómo funciona?**\n1. Descarga la app Hipermaxi (disponible mundialmente)\n2. Crea tu cuenta con dirección en Bolivia\n3. Selecciona productos y dirección de entrega\n4. Paga con tarjeta internacional\n5. Tu familia recibe los productos en su puerta\n\n**Beneficios:**\n- Productos frescos y de calidad\n- Envío a cualquier ciudad con cobertura Hipermaxi\n- Seguimiento en tiempo real del pedido\n- Notificación cuando se entrega\n\n**Ideal para:**\n- Cumpleaños y fechas especiales\n- Ayuda mensual a padres o abuelos\n- Fiestas de fin de año\n\n**Cobertura:** Santa Cruz, La Paz, El Alto, Cochabamba, Montero\n\n¡Mantente cerca de los tuyos con Hipermaxi!",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Bolivianos en el extranjero pueden comprar y enviar productos a sus familias en Bolivia.',
                    'call_to_action' => 'Descarga la app y realiza tu primera compra internacional',
                ],
                'created_at' => '2025-05-20 10:00:00',
                'published_at' => '2025-05-20 12:00:00',
            ],
            [
                'type' => 'NEWS',
                'title' => 'Programa de Lealtad Hipermaxi Club: ¡Acumula puntos y gana!',
                'content' => "**¡NUEVO PROGRAMA!**\n\n⭐ Presentamos **Hipermaxi Club**, nuestro programa de lealtad.\n\n**¿Cómo funciona?**\n- Por cada Bs. 10 de compra = 1 punto\n- Puntos acumulables en compras físicas y online\n- Puntos canjeables por productos y descuentos\n\n**Niveles de membresía:**\n\n🥉 **Bronce** (0-499 puntos)\n- Ofertas exclusivas semanales\n\n🥈 **Plata** (500-1499 puntos)\n- 5% descuento adicional en cumpleaños\n- Acceso anticipado a promociones\n\n🥇 **Oro** (1500+ puntos)\n- 10% descuento permanente\n- Delivery gratis siempre\n- Caja preferencial en tiendas\n\n**Cómo inscribirse:**\n- En cualquier caja de tienda\n- Desde la app Hipermaxi\n- En www.hipermaxi.com/club\n\n**Lanzamiento:** 1 de Agosto 2025\n\n¡Empieza a acumular puntos con tu próxima compra!",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Lanzamiento del programa de lealtad con tres niveles y beneficios exclusivos.',
                    'call_to_action' => 'Inscríbete en Hipermaxi Club y empieza a acumular puntos',
                ],
                'created_at' => '2025-07-25 09:00:00',
                'published_at' => '2025-07-25 10:00:00',
            ],
            [
                'type' => 'NEWS',
                'title' => 'Hipermaxi compromiso con el medio ambiente: Bolsas reutilizables',
                'content' => "**INICIATIVA VERDE**\n\n🌱 Hipermaxi se compromete con el medio ambiente.\n\n**Cambios a partir de Octubre 2025:**\n\n**1. Bolsas reutilizables:**\n- Bolsa ecológica Hipermaxi: Bs. 5\n- Duradera, lavable, resistente\n- Disponible en todos los diseños\n\n**2. Incentivo para clientes:**\n- Trae tu propia bolsa = 2 puntos extra en Hipermaxi Club\n- Bolsa olvidada = Alquilamos por Bs. 1 (devolvemos al traerla)\n\n**3. Meta 2026:**\n- Reducir uso de plástico un 70%\n- Todas las sucursales con contenedores de reciclaje\n\n**Impacto esperado:**\n- 5 millones menos de bolsas plásticas al año\n- Reducción de huella de carbono\n\n**Fecha de inicio:** 1 de Octubre 2025\n\nJuntos cuidamos Bolivia 🇧🇴",
                'status' => 'DRAFT',
                'metadata' => [
                    'news_type' => 'policy_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Transición a bolsas reutilizables y programa de incentivos ecológicos.',
                    'call_to_action' => 'Trae tu propia bolsa y gana puntos extra',
                ],
                'created_at' => '2025-09-15 09:00:00',
                'published_at' => null,
            ],

            // ========== ALERT (3 anuncios - 20%) ==========
            [
                'type' => 'ALERT',
                'title' => 'ALERTA: Productos falsificados vendidos fuera de tiendas Hipermaxi',
                'content' => "**ALERTA DE SEGURIDAD**\n\n⚠️ Se han detectado personas vendiendo productos supuestamente \"de Hipermaxi\" fuera de nuestras tiendas.\n\n**Lo que sabemos:**\n- Vendedores ambulantes en mercados y ferias\n- Productos con etiquetas falsificadas\n- Precios \"demasiado bajos\" (señal de alerta)\n- Principalmente: Embutidos, lácteos, productos de limpieza\n\n**HIPERMAXI NUNCA:**\n❌ Vende productos fuera de sus sucursales o online\n❌ Autoriza revendedores ambulantes\n❌ Ofrece productos \"de remate\" en la calle\n\n**Si lo ofrecen:**\n1. NO compre\n2. Verifique la procedencia\n3. Denuncie en nuestras tiendas\n\n**Productos genuinos solo en:**\n- 37 sucursales Hipermaxi\n- App y web oficial\n- Delivery propio de Hipermaxi\n\n**Reporte a:** hipermaxi@hipermaxi.com o WhatsApp +591 3342-5353",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'security',
                    'message' => 'Productos falsificados detectados. Solo compre en tiendas oficiales o plataforma online.',
                    'action_required' => true,
                    'action_description' => 'No comprar a vendedores ambulantes. Denunciar casos sospechosos.',
                    'started_at' => '2025-03-20T00:00:00Z',
                    'ended_at' => '2025-12-31T23:59:59Z',
                    'affected_services' => ['todos'],
                ],
                'created_at' => '2025-03-18 09:00:00',
                'published_at' => '2025-03-20 08:00:00',
            ],
            [
                'type' => 'ALERT',
                'title' => 'RECORDATORIO: Actualiza tu app para evitar problemas de pago',
                'content' => "**AVISO IMPORTANTE**\n\n📱 Si usas la app Hipermaxi, verifica que tengas la versión más reciente.\n\n**Versión actual:**\n- Android: 3.2.1\n- iOS: 3.2.0\n\n**Problemas con versiones antiguas:**\n- Pagos rechazados sin motivo\n- Error al aplicar cupones de descuento\n- Productos no se agregan al carrito\n- App se cierra inesperadamente\n\n**Cómo actualizar:**\n1. Abre Play Store o App Store\n2. Busca \"Hipermaxi\"\n3. Si ves \"Actualizar\", presiona\n4. Espera a que termine\n5. Abre la app y verifica en Perfil > Versión\n\n**Fecha límite:** Las versiones anteriores a 3.0 dejarán de funcionar el 30 de Septiembre 2025.\n\n**¿Necesitas ayuda?**\n- WhatsApp: +591 3342-5353\n- Email: soporte@hipermaxi.com",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'alert_type' => 'service',
                    'message' => 'Actualizar app a versión 3.2+ para evitar problemas de pago y funcionamiento.',
                    'action_required' => true,
                    'action_description' => 'Actualizar app desde Play Store o App Store antes del 30 de septiembre.',
                    'started_at' => '2025-08-15T00:00:00Z',
                    'ended_at' => '2025-09-30T23:59:59Z',
                    'affected_services' => ['app_movil', 'pagos', 'cupones'],
                ],
                'created_at' => '2025-08-12 09:00:00',
                'published_at' => '2025-08-15 09:00:00',
            ],
            [
                'type' => 'ALERT',
                'title' => 'IMPORTANTE: Cambio en política de devoluciones',
                'content' => "**AVISO DE CAMBIO DE POLÍTICA**\n\n📋 A partir del 1 de Diciembre 2025, actualizamos nuestra política de devoluciones.\n\n**Cambios principales:**\n\n**Plazo de devolución:**\n- Antes: 7 días\n- Ahora: 15 días (más tiempo para usted)\n\n**Productos perecederos:**\n- Reclamo dentro de 24 horas de compra\n- Presentar ticket y producto\n- Reembolso o cambio inmediato\n\n**Productos no perecederos:**\n- 15 días con ticket de compra\n- Producto sin usar y en empaque original\n- Devolución en efectivo o crédito de tienda\n\n**Sin cambios:**\n- Medicamentos no son devolvables (normativa sanitaria)\n- Productos de higiene personal abiertos\n\n**Dónde aplica:**\n- Todas las sucursales\n- Compras online\n\n**Vigencia:** 1 de Diciembre 2025\n\n**Más información:** www.hipermaxi.com/devoluciones",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'alert_type' => 'compliance',
                    'message' => 'Nueva política de devoluciones con plazo extendido a 15 días.',
                    'action_required' => false,
                    'started_at' => '2025-12-01T00:00:00Z',
                    'ended_at' => null,
                    'affected_services' => ['devoluciones', 'atencion_cliente'],
                ],
                'created_at' => '2025-11-20 09:00:00',
                'published_at' => '2025-11-20 14:00:00',
            ],
        ];

        foreach ($announcements as $data) {
            Announcement::create([
                'company_id' => $company->id,
                'author_id' => $author->id,
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

        $this->command->info('✅ 15 anuncios creados para Hipermaxi (MAINTENANCE: 4, INCIDENT: 3, NEWS: 5, ALERT: 3)');
    }
}
