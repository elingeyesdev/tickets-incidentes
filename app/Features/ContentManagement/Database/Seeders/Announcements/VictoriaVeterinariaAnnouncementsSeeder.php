<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders\Announcements;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use Illuminate\Database\Seeder;

/**
 * Victoria Veterinaria Announcements Seeder
 *
 * Empresa: Victoria Veterinaria (CMP-2025-00011)
 * Industria: veterinary (Clínica veterinaria y tienda de mascotas)
 * Contexto: Clínica veterinaria pequeña en Santa Cruz que ofrece servicios
 *           de consulta, emergencias, vacunación, cirugías menores, 
 *           grooming y venta de productos para mascotas.
 *
 * Anuncios típicos:
 * - Campañas de vacunación
 * - Horarios especiales/emergencias
 * - Nuevos servicios (grooming, productos)
 * - Incidentes (sistema de citas, cortes de luz)
 * - Alertas de salud animal (brotes, temporadas)
 */
class VictoriaVeterinariaAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📢 Creando anuncios para Victoria Veterinaria...');

        $company = Company::where('name', 'Victoria Veterinaria')->first();

        if (!$company) {
            $this->command->error('❌ Victoria Veterinaria no encontrada');
            return;
        }

        // Idempotencia: Verificar si ya existen anuncios
        if (Announcement::where('company_id', $company->id)->exists()) {
            $this->command->info('✓ Anuncios ya existen para Victoria Veterinaria. Saltando...');
            return;
        }

        // Buscar admin de la empresa usando UserRole
        $adminRole = \App\Features\UserManagement\Models\UserRole::where('company_id', $company->id)
            ->where('role_code', 'COMPANY_ADMIN')
            ->where('is_active', true)
            ->first();

        if (!$adminRole) {
            $this->command->error('❌ No se encontró el admin de Victoria Veterinaria.');
            return;
        }

        $author = \App\Features\UserManagement\Models\User::find($adminRole->user_id);

        $announcements = [
            // ===============================================
            // ENERO 2025 - Inicio de operaciones
            // ===============================================
            
            // NEWS - Campaña de vacunación antirrábica
            [
                'type' => 'NEWS',
                'title' => 'Campaña de Vacunación Antirrábica 2025 - Precios Especiales',
                'content' => "¡Protege a tu mascota! Victoria Veterinaria inicia su Campaña Anual de Vacunación Antirrábica.\n\n**Fechas:** Del 15 al 31 de enero de 2025\n**Precio promocional:** Bs. 30 (precio regular: Bs. 50)\n\n**¿Por qué es importante?**\nLa rabia es una enfermedad mortal transmisible a humanos. La vacuna anual es OBLIGATORIA por ley municipal.\n\n**Incluye:**\n- Vacuna antirrábica certificada\n- Certificado oficial para trámites\n- Registro en libreta sanitaria\n\n**Horarios de atención:**\nLunes a Viernes: 8:00 AM - 6:00 PM\nSábados: 8:00 AM - 1:00 PM\n\n📞 Agenda tu cita: +591 3922 1234",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Vacunación antirrábica a precio especial durante todo enero',
                    'call_to_action' => 'Agenda tu cita ahora',
                ],
                'created_at' => '2025-01-08 09:00:00',
                'published_at' => '2025-01-08 09:30:00',
            ],

            // MAINTENANCE - Limpieza y desinfección profunda
            [
                'type' => 'MAINTENANCE',
                'title' => 'Mantenimiento de Instalaciones - Sábado 18 de Enero',
                'content' => "Estimados clientes,\n\nLes informamos que el sábado 18 de enero realizaremos limpieza y desinfección profunda de nuestras instalaciones.\n\n**Horario de mantenimiento:**\n- Sábado 18/01: NO HABRÁ ATENCIÓN\n\n**Retomamos actividades:**\n- Lunes 20/01: Horario normal desde las 8:00 AM\n\n**¿Tienes una emergencia?**\nEn caso de emergencias veterinarias durante el sábado, puedes contactarnos al:\n📱 WhatsApp: +591 7000 0000 (solo emergencias)\n\nPara agendar citas para la semana siguiente, escríbenos desde el domingo.\n\nGracias por tu comprensión.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-01-18T00:00:00Z',
                    'scheduled_end' => '2025-01-18T23:59:00Z',
                    'actual_start' => '2025-01-18T00:00:00Z',
                    'actual_end' => '2025-01-18T23:59:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['consultas', 'emergencias', 'tienda'],
                ],
                'created_at' => '2025-01-12 10:00:00',
                'published_at' => '2025-01-12 10:30:00',
            ],

            // ===============================================
            // FEBRERO 2025
            // ===============================================

            // NEWS - Nueva veterinaria en el equipo
            [
                'type' => 'NEWS',
                'title' => 'Bienvenida a la Dra. Patricia Rojas - Nueva Veterinaria',
                'content' => "¡Excelentes noticias! Victoria Veterinaria se complace en dar la bienvenida a nuestra nueva médica veterinaria.\n\n**Dra. Patricia Rojas Mendoza**\n- Médica Veterinaria - Universidad Autónoma Gabriel René Moreno\n- Especialización en Medicina Interna de Pequeños Animales\n- 5 años de experiencia en clínicas de Santa Cruz\n\n**Áreas de especialidad:**\n- Medicina interna (diagnóstico y tratamiento)\n- Dermatología veterinaria\n- Nutrición y dietética animal\n- Medicina preventiva\n\n**Disponibilidad:**\nMartes a Sábado: 9:00 AM - 5:00 PM\n\nCon la incorporación de la Dra. Rojas, ampliamos nuestra capacidad de atención y reducimos los tiempos de espera para consultas programadas.\n\n📞 Agenda tu cita: +591 3922 1234",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Nueva veterinaria se une al equipo de Victoria Veterinaria',
                    'call_to_action' => null,
                ],
                'created_at' => '2025-02-03 08:30:00',
                'published_at' => '2025-02-03 09:00:00',
            ],

            // INCIDENT - Corte de energía eléctrica
            [
                'type' => 'INCIDENT',
                'title' => 'Corte de Energía - Atención Limitada',
                'content' => "**ACTUALIZACIÓN 11:30 AM:** El servicio eléctrico ha sido restaurado. Retomamos atención normal.\n\n---\n\n**Reporte inicial (9:15 AM):**\n\nEstimados clientes,\n\nDebido a un corte de energía eléctrica en el sector, estamos operando con las siguientes limitaciones:\n\n**Servicios DISPONIBLES:**\n✓ Consultas veterinarias (luz natural)\n✓ Emergencias (con generador de respaldo)\n✓ Venta de productos (sin tarjeta)\n\n**Servicios NO DISPONIBLES:**\n✗ Sistema de citas online\n✗ Rayos X\n✗ Equipos de diagnóstico que requieren electricidad\n✗ Pagos con tarjeta/QR\n\n**Métodos de pago aceptados:**\nSolo efectivo hasta restablecer el servicio.\n\nCRE estima que el servicio se restablecerá antes del mediodía.\n\nDisculpen las molestias. Estamos haciendo nuestro mejor esfuerzo para atenderlos.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'resolution_content' => 'Energía eléctrica restaurada a las 11:30 AM. Todos los servicios operan con normalidad.',
                    'affected_services' => ['sistema_citas', 'rayos_x', 'pagos_electronicos'],
                    'started_at' => '2025-02-14T09:15:00Z',
                    'resolved_at' => '2025-02-14T11:30:00Z',
                ],
                'created_at' => '2025-02-14 09:20:00',
                'published_at' => '2025-02-14 09:25:00',
            ],

            // NEWS - Nuevo servicio de grooming
            [
                'type' => 'NEWS',
                'title' => '¡Nuevo Servicio! Grooming y Estética Canina',
                'content' => "¡Tu mascota se lo merece! Victoria Veterinaria estrena servicio de **Grooming y Estética Canina**.\n\n**Servicios disponibles:**\n🐕 **Baño Medicado** - Bs. 50-80 (según tamaño)\n   - Shampoo antiparasitario o dermatológico\n   - Secado profesional\n   - Limpieza de oídos\n   - Corte de uñas\n\n🐕 **Baño + Corte de Pelo** - Bs. 80-150 (según tamaño/raza)\n   - Todo lo anterior +\n   - Corte según estándar de raza o a pedido\n   - Corte de pelo alrededor de ojos y patas\n\n🐕 **Paquete Completo SPA** - Bs. 120-200\n   - Baño medicado\n   - Corte de pelo\n   - Limpieza dental superficial\n   - Vaciado de glándulas anales\n   - Perfume y moño decorativo\n\n**Horarios de Grooming:**\nMartes a Sábado: 10:00 AM - 4:00 PM\n*(Último ingreso: 3:00 PM)*\n\n**IMPORTANTE:**\n- Traer libreta de vacunas al día\n- Dejar a la mascota mínimo 2 horas\n- Agendar con anticipación\n\n📞 Reservas: +591 3922 1234\n💬 WhatsApp: +591 7000 0000",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Nuevo servicio de baño, corte y estética para perros',
                    'call_to_action' => 'Agenda el primer baño de tu mascota',
                ],
                'created_at' => '2025-02-20 08:00:00',
                'published_at' => '2025-02-20 08:30:00',
            ],

            // ===============================================
            // MARZO 2025
            // ===============================================

            // ALERT - Brote de parvovirus
            [
                'type' => 'ALERT',
                'title' => 'ALERTA: Brote de Parvovirus en la Zona - Protege a tu Cachorro',
                'content' => "⚠️ **ALERTA SANITARIA**\n\nLa Alcaldía Municipal reporta un brote de Parvovirus Canino en sectores de Santa Cruz.\n\n**¿Qué es el parvovirus?**\nEnfermedad viral altamente contagiosa y potencialmente mortal en cachorros no vacunados. Causa:\n- Vómitos severos\n- Diarrea con sangre\n- Deshidratación extrema\n- Alta mortalidad sin tratamiento\n\n**¿Cómo se transmite?**\n- Contacto con heces de perros infectados\n- Objetos contaminados (platos, juguetes)\n- Suelo contaminado (parques, veredas)\n\n**ACCIÓN REQUERIDA - URGENTE:**\n\n✅ **Si tu cachorro NO está vacunado:**\n   - NO lo saques a la calle\n   - Agenda vacunación INMEDIATA\n   - Precio especial de emergencia: Bs. 80 (incluye consulta)\n\n✅ **Si tu cachorro está vacunado:**\n   - Verifica que tenga las 3 dosis completas\n   - Evita contacto con perros desconocidos\n   - Desinfecta zapatos al llegar a casa\n\n⚠️ **Síntomas de alerta:**\nSi tu cachorro presenta vómito + diarrea, acude INMEDIATAMENTE. El parvovirus puede matar en 48-72 horas.\n\n**Campaña de Vacunación de Emergencia:**\nHasta el 31 de marzo - Precio especial para cachorros\n\n📞 Emergencias: +591 3922 1234\n💬 WhatsApp 24/7: +591 7000 0000",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'CRITICAL',
                    'alert_type' => 'security',
                    'message' => 'Brote de parvovirus - Vacuna a tu cachorro urgentemente',
                    'action_required' => true,
                    'action_description' => 'Vacunar cachorros no inmunizados antes del 31 de marzo',
                    'started_at' => '2025-03-05T00:00:00Z',
                    'ended_at' => '2025-03-31T23:59:59Z',
                    'affected_services' => ['todos'],
                ],
                'created_at' => '2025-03-05 07:00:00',
                'published_at' => '2025-03-05 07:30:00',
            ],

            // ===============================================
            // ABRIL 2025
            // ===============================================

            // MAINTENANCE - Sistema de agendamiento online
            [
                'type' => 'MAINTENANCE',
                'title' => 'Actualización Sistema de Citas Online - Domingo 13 de Abril',
                'content' => "Estimados clientes,\n\nEstaremos realizando una actualización mayor a nuestro sistema de agendamiento de citas online.\n\n**Fecha y hora:**\nDomingo 13 de abril\n11:00 PM - 3:00 AM (lunes 14)\n\n**Servicios afectados:**\n- Sistema web de citas: NO DISPONIBLE\n- Consulta de historial online: NO DISPONIBLE\n- Recordatorios automáticos: NO SE ENVIARÁN\n\n**Servicios NO afectados:**\n- Atención presencial: NORMAL (lunes desde las 8 AM)\n- Teléfono para citas: DISPONIBLE desde las 8 AM lunes\n- WhatsApp: DISPONIBLE\n- Emergencias: SIN CAMBIOS\n\n**Mejoras incluidas:**\n✓ Nueva interfaz más intuitiva\n✓ Recordatorios vía WhatsApp\n✓ Consulta de resultados de laboratorio\n✓ Historial de vacunación digital\n✓ Pago online de consultas\n\n**¿Tienes una cita el lunes?**\nNo te preocupes, todas las citas agendadas previamente se mantienen. Te enviaremos confirmación por SMS el lunes por la mañana.\n\nGracias por tu paciencia.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'LOW',
                    'scheduled_start' => '2025-04-13T23:00:00Z',
                    'scheduled_end' => '2025-04-14T03:00:00Z',
                    'actual_start' => '2025-04-13T23:05:00Z',
                    'actual_end' => '2025-04-14T02:45:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['sistema_citas_online', 'historial_online', 'recordatorios'],
                ],
                'created_at' => '2025-04-08 09:00:00',
                'published_at' => '2025-04-08 10:00:00',
            ],

            // NEWS - Alianza con pet shop
            [
                'type' => 'NEWS',
                'title' => 'Alianza con PetShop La Mascota - Descuentos Exclusivos',
                'content' => "¡Tenemos una gran noticia para nuestros clientes!\n\nVictoria Veterinaria se une en alianza estratégica con **PetShop La Mascota**, la tienda de productos para mascotas más grande de Santa Cruz.\n\n**Beneficios para clientes de Victoria Veterinaria:**\n\n🎁 **Descuento del 15%** en todos los productos con tu carnet de cliente\n\n🎁 **Descuento del 20%** en alimentos prescritos por nuestros veterinarios\n\n🎁 **Acumulación de puntos** por cada compra (1 punto = Bs. 1)\n   - 100 puntos = 1 baño gratis en Victoria Vet\n   - 250 puntos = 1 consulta gratis\n   - 500 puntos = 1 vacuna gratis\n\n🎁 **Delivery gratis** en compras mayores a Bs. 150\n\n**¿Cómo obtener tu carnet de cliente?**\n1. Visítanos en Victoria Veterinaria\n2. Registra tus datos (solo 2 minutos)\n3. Recibe tu tarjeta digital vía WhatsApp\n4. ¡Empieza a disfrutar los descuentos!\n\n**PetShop La Mascota - Ubicaciones:**\n- Sucursal Norte: Av. Roca y Coronado\n- Sucursal Centro: 3er Anillo Interno\n- Delivery: +591 3 333 3333\n\n*Promoción válida presentando carnet digital o físico de Victoria Veterinaria.*",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Descuentos exclusivos del 15-20% en PetShop La Mascota para clientes',
                    'call_to_action' => 'Solicita tu carnet de cliente',
                ],
                'created_at' => '2025-04-22 08:30:00',
                'published_at' => '2025-04-22 09:00:00',
            ],

            // ===============================================
            // MAYO 2025
            // ===============================================

            // INCIDENT - Problemas con proveedor de vacunas
            [
                'type' => 'INCIDENT',
                'title' => 'Demora en Abastecimiento de Vacunas - Stock Limitado',
                'content' => "**ACTUALIZACIÓN 18/05 - 4:00 PM:**\nRecibimos el stock completo de vacunas. Servicio de vacunación restablecido al 100%.\n\n---\n\n**Reporte inicial:**\n\nEstimados clientes,\n\nDebido a demoras en la importación de vacunas por parte de nuestro proveedor, enfrentamos stock limitado temporal:\n\n**Vacunas DISPONIBLES (stock limitado):**\n✓ Antirrábica\n✓ Séxtuple/Óctuple (solo para primeras dosis urgentes)\n\n**Vacunas AGOTADAS temporalmente:**\n✗ Refuerzos anuales (séxtuple)\n✗ Tos de las perreras\n✗ Leucemia felina\n✗ Triple felina\n\n**¿Qué estamos haciendo?**\n- Stock de emergencia ya en camino desde La Paz\n- Llegada estimada: 18 de mayo\n- Prioridad a cachorros con esquemas incompletos\n\n**¿Tienes una cita para vacuna?**\nNuestro equipo te contactará vía WhatsApp para:\n- Confirmar si hay stock de tu vacuna\n- Reagendar si es necesario (sin costo)\n- Ofrecerte alternativas\n\n**Cachorros con esquemas en curso:**\nNo te preocupes, es seguro esperar 1-2 semanas entre dosis. Tu cachorro está protegido.\n\nDisculpen las molestias. Trabajamos para solucionarlo a la brevedad.\n\n📞 Consultas: +591 3922 1234",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'resolution_content' => 'Stock completo recibido el 18/05. Todas las vacunas disponibles nuevamente.',
                    'affected_services' => ['vacunacion'],
                    'started_at' => '2025-05-12T08:00:00Z',
                    'resolved_at' => '2025-05-18T16:00:00Z',
                ],
                'created_at' => '2025-05-12 08:30:00',
                'published_at' => '2025-05-12 09:00:00',
            ],

            // ===============================================
            // JUNIO 2025
            // ===============================================

            // NEWS - Día del Padre - Promoción
            [
                'type' => 'NEWS',
                'title' => 'Día del Padre: Paquete Especial Papá y su Mejor Amigo',
                'content' => "🎉 **PROMOCIÓN DÍA DEL PADRE** 🎉\n\n¿Buscas el regalo perfecto para papá? ¡Un día especial con su mejor amigo peludo!\n\n**PAQUETE ESPECIAL \"PAPÁ Y SU MEJOR AMIGO\"**\nPrecio: Bs. 199 (Ahorro de Bs. 100)\n\n**Incluye:**\n🐕 Chequeo veterinario completo\n🐕 Baño + corte de pelo profesional\n🐕 Desparasitación interna\n🐕 Corte de uñas y limpieza de oídos\n🐕 Sesión de fotos profesional (5 fotos digitales)\n🐕 Collar personalizado con nombre grabado\n\n**EXTRA GRATIS:**\n📸 Marco portarretratos \"Mi Papá y Yo\"\n🎁 Bolsa de snacks premium (200g)\n\n**Vigencia:**\nDel 10 al 20 de junio de 2025\n\n**¿Cómo adquirirlo?**\n1. Agenda tu cita mencionando \"Paquete Día del Padre\"\n2. Lleva a tu mascota el día elegido\n3. Nosotros nos encargamos del resto\n4. Recoge a tu mejor amigo renovado + fotos + regalos\n\n**Cupos limitados:** 30 paquetes disponibles\n**Tiempo de servicio:** 3-4 horas\n\n📞 Reservas: +591 3922 1234\n💬 WhatsApp: +591 7000 0000\n\n*Un día especial para papá y su compañero de aventuras.*",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Paquete especial Día del Padre con grooming, chequeo y sesión de fotos',
                    'call_to_action' => 'Reserva ahora - Cupos limitados',
                ],
                'created_at' => '2025-06-05 08:00:00',
                'published_at' => '2025-06-05 09:00:00',
            ],

            // ALERT - Temporada de garrapatas
            [
                'type' => 'ALERT',
                'title' => 'Temporada de Garrapatas - Prevención es Clave',
                'content' => "⚠️ **ALERTA ESTACIONAL**\n\n¡Llegó la temporada de garrapatas! Junio-Septiembre son los meses de mayor actividad parasitaria en Santa Cruz.\n\n**¿Por qué son peligrosas las garrapatas?**\n- Transmiten enfermedades graves (Ehrlichiosis, Babesiosis)\n- Causan anemia severa en cachorros\n- Pueden afectar a humanos (Fiebre Manchada)\n\n**Síntomas de infestación:**\n❗ Rascado excesivo\n❗ Puntos negros en la piel (garrapatas adheridas)\n❗ Pérdida de apetito\n❗ Debilidad, encías pálidas\n❗ Fiebre\n\n**ACCIÓN REQUERIDA:**\n\n✅ **Desparasitación externa MENSUAL**\n   - Pipetas: Bs. 40-60 (según peso)\n   - Tabletas masticables: Bs. 70-90\n   - Collares: Bs. 80-120 (duración 3-8 meses)\n\n✅ **Revisión semanal** especialmente en:\n   - Orejas\n   - Entre los dedos\n   - Axilas e ingles\n   - Cuello\n\n✅ **Test de Ehrlichiosis** si tu perro:\n   - No está desparasitado hace +60 días\n   - Ha tenido garrapatas recientemente\n   - Muestra síntomas\n\n**PROMOCIÓN DE PREVENCIÓN:**\nHasta el 30 de junio:\n- Pipeta + Consulta preventiva: Bs. 70\n- Test Ehrlichiosis + Tratamiento (si es positivo): Bs. 150\n\n**NO ESPERES A VER SÍNTOMAS** - La prevención es más barata que el tratamiento.\n\n📞 Consultas: +591 3922 1234\n💬 WhatsApp: +591 7000 0000",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'security',
                    'message' => 'Temporada de garrapatas - Desparasita a tu mascota mensualmente',
                    'action_required' => true,
                    'action_description' => 'Desparasitar mascotas antes del 30 de junio - Promoción especial',
                    'started_at' => '2025-06-15T00:00:00Z',
                    'ended_at' => '2025-06-30T23:59:59Z',
                    'affected_services' => ['todos'],
                ],
                'created_at' => '2025-06-15 08:00:00',
                'published_at' => '2025-06-15 08:30:00',
            ],

            // ===============================================
            // JULIO 2025
            // ===============================================

            // MAINTENANCE - Remodelación sala de cirugía
            [
                'type' => 'MAINTENANCE',
                'title' => 'Remodelación Sala de Cirugía - 26-27 de Julio',
                'content' => "Estimados clientes,\n\nComo parte de nuestro compromiso con la excelencia, realizaremos la remodelación y equipamiento de nuestra sala de cirugía.\n\n**Fechas:**\nSábado 26 y Domingo 27 de julio\n\n**Servicios NO DISPONIBLES:**\n✗ Cirugías programadas (esterilizaciones, castraciones)\n✗ Cirugías menores (extracciones dentales, tumores pequeños)\n✗ Rayos X (equipo será reubicado)\n\n**Servicios DISPONIBLES:**\n✓ Consultas veterinarias (horario normal)\n✓ Vacunación\n✓ Desparasitación\n✓ Emergencias MÉDICAS (no quirúrgicas)\n✓ Grooming y baño\n✓ Venta de productos\n\n**¿Tienes una cirugía programada?**\nNuestro equipo ya te contactó para:\n- Reagendar SIN COSTO para la siguiente semana\n- O derivarte a clínica aliada (si es urgente)\n\n**Emergencias quirúrgicas (26-27 julio):**\nSerán atendidas en:\n📍 Clínica Veterinaria del Este\nAv. Busch, entre 3er y 4to anillo\n📞 +591 3 366 6666\n\n**Mejoras incluidas:**\n✓ Nuevo equipamiento de anestesia inhalatoria\n✓ Lámpara quirúrgica LED de alta intensidad\n✓ Monitor de signos vitales digital\n✓ Sistema de esterilización por autoclave nuevo\n✓ Piso y paredes antibacteriales\n\n**Retomamos cirugías:**\nLunes 28 de julio - 8:00 AM\n\nGracias por confiar en nosotros.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-07-26T00:00:00Z',
                    'scheduled_end' => '2025-07-27T23:59:00Z',
                    'actual_start' => '2025-07-26T00:00:00Z',
                    'actual_end' => '2025-07-27T23:59:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['cirugias', 'rayos_x'],
                ],
                'created_at' => '2025-07-15 09:00:00',
                'published_at' => '2025-07-15 10:00:00',
            ],

            // ===============================================
            // AGOSTO 2025
            // ===============================================

            // NEWS - Nueva función: Recetas digitales
            [
                'type' => 'NEWS',
                'title' => 'Recetas Digitales - Accede a tus Prescripciones desde tu Celular',
                'content' => "📱 **NUEVA FUNCIONALIDAD**\n\n¡Di adiós a las recetas en papel! Victoria Veterinaria estrena sistema de **Recetas Digitales**.\n\n**¿Cómo funciona?**\n\n1️⃣ **Durante la consulta:**\n   El veterinario registra la receta en el sistema\n\n2️⃣ **Recibes por WhatsApp:**\n   - PDF con la receta completa\n   - Firma digital del veterinario\n   - Código QR de verificación\n   - Instrucciones detalladas de administración\n\n3️⃣ **Compra donde quieras:**\n   Presenta la receta digital en cualquier veterinaria o pet shop\n\n**Ventajas:**\n✓ Nunca pierdas una receta\n✓ Historial completo de medicamentos\n✓ Recordatorios de horarios de medicación\n✓ Alertas cuando se acabe el medicamento\n✓ Reorden fácil (si requiere receta continua)\n\n**¿Y si necesito la receta en papel?**\nNo hay problema, también imprimimos. Tú eliges.\n\n**Disponible desde:**\n1 de agosto de 2025\n\nSin costo adicional para todos nuestros clientes.\n\n**Requisitos:**\n- Número de celular registrado\n- WhatsApp activo\n\n¿No tienes WhatsApp? Podemos enviarlo por email.\n\n📞 Más información: +591 3922 1234",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Recetas digitales vía WhatsApp - No más recetas en papel',
                    'call_to_action' => 'Actualiza tus datos de contacto',
                ],
                'created_at' => '2025-07-28 08:00:00',
                'published_at' => '2025-07-28 09:00:00',
            ],

            // INCIDENT - Sistema de recetas digitales con fallas
            [
                'type' => 'INCIDENT',
                'title' => 'Fallas en Envío de Recetas Digitales - En Resolución',
                'content' => "**ACTUALIZACIÓN 10/08 - 3:00 PM:**\nProblema resuelto. Sistema de recetas digitales operando con normalidad. Todas las recetas pendientes fueron enviadas.\n\n---\n\n**Reporte inicial (10/08 - 10:00 AM):**\n\nEstimados clientes,\n\nDetectamos fallas en el envío automático de recetas digitales vía WhatsApp.\n\n**Situación:**\n- Recetas generadas: ✓ OK\n- Almacenamiento: ✓ OK\n- Envío WhatsApp: ✗ FALLANDO (70% de envíos)\n\n**Recetas afectadas:**\nGeneradas entre el 8 y 10 de agosto (aprox. 25 recetas)\n\n**Solución temporal:**\nSi no recibiste tu receta digital:\n1. Llámanos al +591 3922 1234\n2. Proporciónanos tu nombre y fecha de consulta\n3. Te la enviaremos manualmente por email o WhatsApp\n\nO pasa por la clínica y te la imprimimos GRATIS.\n\n**¿Qué causó el problema?**\nActualización del proveedor de WhatsApp Business API generó incompatibilidad.\n\n**Solución definitiva:**\nNuestro equipo técnico está migrando a un nuevo servidor. Estimamos solución antes de las 3:00 PM.\n\nDisculpen las molestias. Todas las recetas están guardadas de forma segura y serán reenviadas automáticamente.\n\n📞 Consultas: +591 3922 1234\n📧 Email: contacto@victoriavet.bo",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'resolution_content' => 'Migración a nuevo servidor completada. Sistema estable. Recetas pendientes enviadas automáticamente.',
                    'affected_services' => ['recetas_digitales_whatsapp'],
                    'started_at' => '2025-08-08T08:00:00Z',
                    'resolved_at' => '2025-08-10T15:00:00Z',
                ],
                'created_at' => '2025-08-10 10:15:00',
                'published_at' => '2025-08-10 10:30:00',
            ],

            // ===============================================
            // SEPTIEMBRE 2025
            // ===============================================

            // NEWS - Jornada de esterilización gratuita
            [
                'type' => 'NEWS',
                'title' => 'Jornada Gratuita de Esterilización - Septiembre por Control Poblacional',
                'content' => "🏥 **JORNADA SOCIAL - ESTERILIZACIÓN GRATUITA**\n\nVictoria Veterinaria, en coordinación con la Alcaldía Municipal, realizará jornada de esterilización GRATUITA para perros y gatos.\n\n**Fecha:** Sábado 20 de septiembre de 2025\n**Horario:** 7:00 AM - 1:00 PM\n**Lugar:** Instalaciones de Victoria Veterinaria\n\n**Cupos:** 40 animales (por orden de llegada)\n\n**Incluye:**\n✓ Cirugía de esterilización (hembras) o castración (machos)\n✓ Anestesia general\n✓ Medicamentos post-operatorios (3 días)\n✓ Control post-operatorio (7 días después)\n✓ Certificado de esterilización\n\n**COMPLETAMENTE GRATIS**\n\n**Requisitos:**\n1. Animal sano (sin enfermedades activas)\n2. Ayuno de 12 horas (sin agua ni comida)\n3. Peso mínimo: 2 kg\n4. Edad: 6 meses a 8 años\n5. Presentar CI del propietario (copia)\n\n**¿Cómo inscribirme?**\n📍 Presencial: Desde el lunes 8 de septiembre en Victoria Veterinaria\n📞 Teléfono: +591 3922 1234 (de 8 AM a 6 PM)\n💬 WhatsApp: +591 7000 0000\n\n**IMPORTANTE:**\n- Solo 1 animal por familia\n- Cupos limitados a 40\n- Confirmar asistencia 24h antes o se pierde el cupo\n- Recoger al animal el mismo día (5-7 PM)\n\n**¿Por qué esterilizar?**\n✓ Controla la sobrepoblación\n✓ Reduce cáncer reproductivo\n✓ Mejora comportamiento (menos peleas/marcaje)\n✓ Aumenta expectativa de vida\n\n¡Ayúdanos a controlar la población de animales en situación de calle!\n\n📞 Inscripciones: +591 3922 1234",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Esterilización gratuita para 40 animales el 20 de septiembre',
                    'call_to_action' => 'Inscríbete desde el 8 de septiembre',
                ],
                'created_at' => '2025-09-03 08:00:00',
                'published_at' => '2025-09-03 09:00:00',
            ],

            // ===============================================
            // OCTUBRE 2025
            // ===============================================

            // NEWS - Horario extendido
            [
                'type' => 'NEWS',
                'title' => 'Nuevo Horario: Ahora Abrimos los Domingos',
                'content' => "📅 **AMPLIAMOS HORARIOS DE ATENCIÓN**\n\nPor pedido de nuestros clientes, Victoria Veterinaria ahora abre **LOS DOMINGOS**.\n\n**NUEVOS HORARIOS (desde el 5 de octubre):**\n\n🗓️ **Lunes a Viernes:**\n   8:00 AM - 7:00 PM\n   *(Última consulta: 6:30 PM)*\n\n🗓️ **Sábados:**\n   8:00 AM - 5:00 PM\n   *(Última consulta: 4:30 PM)*\n\n🗓️ **Domingos:** ¡NUEVO!\n   9:00 AM - 1:00 PM\n   *(Última consulta: 12:30 PM)*\n\n**Servicios disponibles los domingos:**\n✓ Consultas veterinarias\n✓ Emergencias\n✓ Vacunación\n✓ Desparasitación\n✓ Venta de productos\n✓ Grooming (con cita previa)\n\n**Servicios NO disponibles los domingos:**\n✗ Cirugías programadas\n✗ Análisis de laboratorio (resultados disponibles el lunes)\n\n**¿Cómo agendar cita para domingo?**\n- Online: www.victoriavet.bo (disponible 24/7)\n- WhatsApp: +591 7000 0000\n- Teléfono: +591 3922 1234 (lunes a sábado)\n\n**Veterinarios de turno domingos:**\n- Dra. Patricia Rojas\n- Dr. Carlos Gómez (suplente)\n\n¡Más opciones para cuidar a tu mascota!\n\n📞 Agenda ahora: +591 3922 1234",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Ahora abrimos los domingos de 9 AM a 1 PM',
                    'call_to_action' => 'Agenda tu cita dominical',
                ],
                'created_at' => '2025-09-28 08:00:00',
                'published_at' => '2025-09-28 09:00:00',
            ],

            // ===============================================
            // NOVIEMBRE 2025 - Anuncios recientes
            // ===============================================

            // ALERT - Temporada de pirotecnia (fin de año)
            [
                'type' => 'ALERT',
                'title' => 'ALERTA: Temporada de Pirotecnia - Protege a tu Mascota del Estrés',
                'content' => "⚠️ **ALERTA DE BIENESTAR ANIMAL**\n\nSe aproxima la temporada de mayor uso de pirotecnia (fin de año). Las mascotas sufren niveles EXTREMOS de estrés.\n\n**¿Cómo afecta la pirotecnia a las mascotas?**\n- Taquicardia (ritmo cardíaco elevado)\n- Hiperventilación\n- Temblores incontrolables\n- Pérdida de control de esfínteres\n- Intentos de escape (riesgo de pérdida)\n- Ataques de pánico\n\n**Casos graves:**\n- Paro cardíaco en animales con problemas cardíacos\n- Desmayos por hipotensión\n- Autolesiones (saltar ventanas, romper dientes)\n\n**ACCIÓN REQUERIDA - PLANIFICA AHORA:**\n\n🔵 **NIVEL 1: Mascota con ansiedad LEVE**\n   → Refugio seguro + música/TV alta + compañía\n   → Costo: Bs. 0 (medidas en casa)\n\n🟡 **NIVEL 2: Mascota con ansiedad MODERADA**\n   → Lo anterior + Calmantes naturales\n   → Consulta + tratamiento: Bs. 80-120\n   → Iniciar 3-5 días ANTES de fin de año\n\n🔴 **NIVEL 3: Mascota con ansiedad SEVERA**\n   → Lo anterior + Medicación ansiolítica\n   → Consulta especializada: Bs. 150\n   → Requiere prescripción médica\n   → Iniciar 7 días ANTES de fin de año\n\n**Servicio de Guardería Fin de Año:**\nPara casos extremos, ofrecemos:\n- Hospedaje 24/31 Diciembre (2 noches)\n- Ambiente controlado (sin ruidos externos)\n- Supervisión veterinaria permanente\n- Medicación ansiolítica incluida\n- Costo: Bs. 300 por mascota\n- **Cupos limitados: 15 animales**\n\n**Consultas preventivas:**\nHasta el 20 de diciembre: Descuento del 20% en consultas pre-pirotecnia\n\n**NO ESPERES AL 31 DE DICIEMBRE** - Los medicamentos ansiolíticos requieren días de anticipación para ser efectivos.\n\n📞 Agenda consulta preventiva: +591 3922 1234\n💬 WhatsApp: +591 7000 0000\n\n*Campaña \"Fin de Año sin Pirotecnia\" - Alcaldía Municipal*",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'security',
                    'message' => 'Temporada de pirotecnia - Planifica protección para tu mascota',
                    'action_required' => true,
                    'action_description' => 'Agendar consulta preventiva antes del 20 de diciembre',
                    'started_at' => '2025-11-15T00:00:00Z',
                    'ended_at' => '2025-12-20T23:59:59Z',
                    'affected_services' => ['todos'],
                ],
                'created_at' => '2025-11-15 08:00:00',
                'published_at' => '2025-11-15 08:30:00',
            ],

            // MAINTENANCE - Inventario de fin de año (SCHEDULED - futuro)
            [
                'type' => 'MAINTENANCE',
                'title' => 'Cierre por Inventario Anual - 31 Diciembre',
                'content' => "Estimados clientes,\n\nLes informamos que realizaremos nuestro inventario anual de fin de año.\n\n**Cierre total:**\nMiércoles 31 de diciembre de 2025\nTodo el día (00:00 - 23:59)\n\n**NO HABRÁ ATENCIÓN:**\n✗ Consultas\n✗ Emergencias presenciales\n✗ Grooming\n✗ Ventas\n✗ Cirugías\n\n**¿Tienes una emergencia el 31?**\nSerá atendida en:\n📍 **Clínica Veterinaria del Este**\n   Av. Busch, entre 3er y 4to anillo\n   📞 +591 3 366 6666\n   Atención 24/7\n\n📍 **Clínica Veterinaria San Francisco**\n   4to Anillo, Km 6.5\n   📞 +591 3 355 5555\n   Atención 24/7\n\n**Retomamos actividades:**\nJueves 1 de enero de 2026 - Horario normal desde las 9:00 AM\n\n**Recomendaciones:**\n- Compra alimento/medicamentos con anticipación\n- Agenda citas para después del 1 de enero\n- Guarda el número de las clínicas de emergencia\n\n**IMPORTANTE:**\nSi tu mascota tiene tratamiento crónico, asegúrate de tener medicación suficiente para el 31 de diciembre.\n\n¡Feliz Año Nuevo! Nos vemos en 2026.\n\n📞 Consultas: +591 3922 1234",
                'status' => 'SCHEDULED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-12-31T00:00:00Z',
                    'scheduled_end' => '2025-12-31T23:59:00Z',
                    'actual_start' => null,
                    'actual_end' => null,
                    'is_emergency' => false,
                    'affected_services' => ['todos'],
                ],
                'created_at' => '2025-11-20 09:00:00',
                'published_at' => '2025-12-15 09:00:00', // Publicación futura
            ],

            // ===============================================
            // DICIEMBRE 2025 - Anuncios muy recientes/draft
            // ===============================================

            // NEWS - Balance del año (DRAFT - en edición)
            [
                'type' => 'NEWS',
                'title' => '2025: Un Año de Crecimiento Junto a Ustedes - Gracias',
                'content' => "**[BORRADOR - EN REVISIÓN]**\n\n¡Queridos clientes y amigos de Victoria Veterinaria!\n\nMientras cerramos el 2025, queremos compartir con ustedes algunos logros alcanzados GRACIAS A SU CONFIANZA:\n\n**Números que nos enorgullecen:**\n🐾 **2,847 consultas** realizadas\n🐾 **1,234 vacunas** aplicadas\n🐾 **456 cirugías** exitosas\n🐾 **892 baños** y sesiones de grooming\n🐾 **156 emergencias** atendidas 24/7\n\n**Mejoras implementadas en 2025:**\n✅ Incorporación Dra. Patricia Rojas\n✅ Nuevo servicio de grooming (febrero)\n✅ Recetas digitales (agosto)\n✅ Horario domingos (octubre)\n✅ Remodelación sala de cirugía\n✅ Alianza con PetShop La Mascota\n✅ Sistema de citas mejorado\n\n**Impacto social:**\n🏥 Jornada gratuita de esterilización: 40 animales\n🏥 Campaña antirrábica: 234 vacunas a precio social\n🏥 Charlas educativas en escuelas: 3 eventos\n\n**Planes para 2026:**\n🎯 Servicio de hospitalización 24/7\n🎯 Laboratorio clínico propio\n🎯 Área de fisioterapia y rehabilitación\n🎯 Programa de adopciones responsables\n\n**GRACIAS POR CONFIAR EN NOSOTROS**\n\nCada consulta, cada llamada de emergencia, cada confianza depositada... nos motiva a ser mejores cada día.\n\n¡Felices fiestas! Nos vemos en 2026 con más amor por los animales.\n\n**El equipo de Victoria Veterinaria** 🐾\n\n---\n\n*Nota del editor: Validar números finales con contabilidad antes de publicar el 28/12.*",
                'status' => 'DRAFT',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Resumen del año 2025 y agradecimiento a clientes',
                    'call_to_action' => null,
                ],
                'created_at' => '2025-12-05 14:30:00',
                'published_at' => null, // DRAFT
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

        $this->command->info('✅ ' . count($announcements) . ' anuncios creados para Victoria Veterinaria');
    }
}
