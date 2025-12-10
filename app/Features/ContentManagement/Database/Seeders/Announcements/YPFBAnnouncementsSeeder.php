<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders\Announcements;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use Illuminate\Database\Seeder;

/**
 * YPFB Announcements Seeder
 *
 * Crea anuncios realistas para YPFB Corporación (empresa estatal de hidrocarburos)
 * Basado en la crisis real de hidrocarburos bolivianos 2024-2025:
 * - Crisis de importación de combustibles
 * - Caída de producción y renta petrolera
 * - Pérdida del mercado argentino de gas
 * - Problemas logísticos de distribución
 * - Escándalos de corrupción y cambios de administración
 * - Nuevos proyectos de exploración
 *
 * Volumen: 20 anuncios (MAINTENANCE: 5, INCIDENT: 5, NEWS: 7, ALERT: 3)
 * Período: 5 enero 2025 - 8 diciembre 2025
 */
class YPFBAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📢 Creando anuncios para YPFB Corporación...');

        $company = Company::where('name', 'YPFB Corporación')->first();

        if (!$company) {
            $this->command->error('❌ YPFB Corporación no encontrada.');
            return;
        }

        // Idempotencia: Verificar si ya existen anuncios
        if (Announcement::where('company_id', $company->id)->exists()) {
            $this->command->info('✓ Anuncios ya existen para YPFB. Saltando...');
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

        $announcements = [
            // ========== MAINTENANCE (5 anuncios - 25%) ==========
            [
                'type' => 'MAINTENANCE',
                'title' => 'Mantenimiento programado Refinería Guillermo Elder Bell - 25/Enero',
                'content' => "Estimado personal y socios comerciales:\n\nSe realizará mantenimiento preventivo programado en la Refinería Guillermo Elder Bell ubicada en Cochabamba.\n\n**Detalles del mantenimiento:**\n- Fecha: Sábado 25 de enero de 2025\n- Horario: 00:00 AM - 08:00 AM\n- Sistemas afectados: Unidad de destilación primaria, torre de fraccionamiento\n- Duración estimada: 8 horas\n\n**Impacto en operaciones:**\n- Reducción temporal del 40% en producción de gasolina\n- Reducción temporal del 30% en producción de diésel\n- Transporte por oleoductos: Sin afectación\n\n**Acciones preventivas:**\n- Stock de seguridad activado en terminales La Paz y Santa Cruz\n- Distribuidores mayoristas notificados para planificación\n- Coordinación con estaciones de servicio del eje troncal\n\n**Trabajos a realizar:**\n- Inspección de equipos de destilación\n- Calibración de sensores de temperatura y presión\n- Limpieza de intercambiadores de calor\n- Pruebas de válvulas de seguridad\n\nAgradecemos su comprensión y planificación anticipada.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-01-25T00:00:00Z',
                    'scheduled_end' => '2025-01-25T08:00:00Z',
                    'actual_start' => '2025-01-25T00:10:00Z',
                    'actual_end' => '2025-01-25T07:45:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['refineria_guillermo_elder', 'produccion_gasolina', 'produccion_diesel'],
                ],
                'created_at' => '2025-01-18 09:00:00',
                'published_at' => '2025-01-18 11:00:00',
            ],

            [
                'type' => 'MAINTENANCE',
                'title' => 'URGENTE: Mantenimiento de emergencia Gasoducto Yacuiba-Río Grande',
                'content' => "**AVISO DE EMERGENCIA**\n\nSe ha detectado una fuga menor en el Gasoducto Yacuiba-Río Grande (GASYRG) en el tramo Km 287. Se procederá a mantenimiento de emergencia inmediato.\n\n**Detalles:**\n- Inicio: Hoy 12/Marzo - 06:00 AM\n- Duración estimada: 12-18 horas\n- Tramo afectado: Km 280-295\n- Impacto: Reducción del 25% en capacidad de transporte de gas\n\n**Acciones tomadas:**\n- Presión reducida en tramo afectado (seguridad)\n- Soldadores especializados en camino\n- Coordinación con operadores de campos Margarita y San Alberto\n- Comunicación con Brasil sobre reducción temporal de envíos\n\n**Seguridad:**\n- Área perimetral acordonada (500 metros)\n- Brigadas de emergencia desplegadas\n- Sin riesgo para comunidades cercanas\n\n**Actualización 14:00:** Reparación en progreso. Estimamos finalizar a las 20:00 horas.\n\nDisculpen las molestias. Mantendremos informados de avances.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'scheduled_start' => '2025-03-12T06:00:00Z',
                    'scheduled_end' => '2025-03-12T22:00:00Z',
                    'actual_start' => '2025-03-12T06:15:00Z',
                    'actual_end' => '2025-03-12T19:30:00Z',
                    'is_emergency' => true,
                    'affected_services' => ['gasoducto_yacuiba_rio_grande', 'transporte_gas', 'exportacion_brasil'],
                ],
                'created_at' => '2025-03-12 05:45:00',
                'published_at' => '2025-03-12 06:00:00',
            ],

            [
                'type' => 'MAINTENANCE',
                'title' => 'Mantenimiento anual Planta Separadora de Líquidos Gran Chaco - Mayo',
                'content' => "Informamos el mantenimiento anual programado de la Planta Separadora de Líquidos Gran Chaco, una de las instalaciones más importantes del país.\n\n**Cronograma:**\n- Fecha: 10-15 de Mayo de 2025\n- Duración: 5 días\n- Horario: Operación continua de equipos de mantenimiento\n\n**Sistemas en mantenimiento:**\n- Unidades de separación criogénica\n- Torres de absorción\n- Compresores de GLP\n- Sistema de almacenamiento\n\n**Impacto en producción:**\n- GLP: Reducción del 60% (5 días)\n- Gasolina natural: Reducción del 50%\n- Etano: Producción suspendida temporalmente\n\n**Medidas de contingencia:**\n- Importación adicional de GLP desde Argentina y Perú\n- Stock de seguridad en terminales de todo el país\n- Coordinación con distribuidores de GLP doméstico\n\n**Beneficios del mantenimiento:**\n- Extensión de vida útil de equipos (10 años adicionales)\n- Mejora de eficiencia del 8%\n- Cumplimiento de normativas ambientales actualizadas\n\n**Importante:** No se anticipa desabastecimiento. Stock nacional es suficiente para cubrir el período de mantenimiento.\n\nAgradecemos su comprensión.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-05-10T00:00:00Z',
                    'scheduled_end' => '2025-05-15T23:59:00Z',
                    'actual_start' => '2025-05-10T00:00:00Z',
                    'actual_end' => '2025-05-15T18:00:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['planta_gran_chaco', 'produccion_glp', 'gasolina_natural'],
                ],
                'created_at' => '2025-04-25 10:00:00',
                'published_at' => '2025-04-25 14:00:00',
            ],

            [
                'type' => 'MAINTENANCE',
                'title' => 'Actualización sistema SCADA nacional - Domingo 17/Agosto',
                'content' => "Estimado personal técnico y operativo:\n\nInformamos la actualización programada del Sistema SCADA (Supervisory Control and Data Acquisition) que monitorea toda la infraestructura de hidrocarburos del país.\n\n**Cronograma:**\n- Fecha: Domingo 17 de agosto de 2025\n- Horario: 01:00 AM - 07:00 AM\n- Duración: 6 horas\n\n**Sistemas afectados durante la actualización:**\n- Monitoreo remoto de pozos productores\n- Control de presión en oleoductos\n- Supervisión de refinerías\n- Alertas automáticas de seguridad\n\n**Durante el mantenimiento:**\n- Control manual en todas las instalaciones\n- Personal de guardia reforzado en puntos críticos\n- Comunicaciones por radio VHF activas\n- Protocolos de emergencia en alerta\n\n**Mejoras de la actualización:**\n- Nueva interfaz de usuario más intuitiva\n- Mayor velocidad de respuesta (de 5s a 1s)\n- Integración con sistema de alertas móviles\n- Mejor gestión de históricos y reportes\n- Cumplimiento con estándares internacionales IEC 62351\n\n**Responsable técnico:** Ingeniería y Proyectos Estratégicos\n\n**Contacto de emergencia:** Centro de Control Nacional - 800-10-0965",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'LOW',
                    'scheduled_start' => '2025-08-17T01:00:00Z',
                    'scheduled_end' => '2025-08-17T07:00:00Z',
                    'actual_start' => '2025-08-17T01:05:00Z',
                    'actual_end' => '2025-08-17T06:30:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['sistema_scada', 'monitoreo_remoto', 'control_oleoductos'],
                ],
                'created_at' => '2025-08-10 09:00:00',
                'published_at' => '2025-08-10 11:00:00',
            ],

            [
                'type' => 'MAINTENANCE',
                'title' => 'Mantenimiento preventivo Terminal de Arica - Noviembre',
                'content' => "**MANTENIMIENTO TERMINAL PORTUARIO ARICA**\n\nSe realizará mantenimiento programado en las instalaciones del Terminal de Recepción de Combustibles en Puerto de Arica, Chile.\n\n**Cronograma:**\n- Fecha: 8-10 de Noviembre de 2025\n- Duración: 72 horas\n- Horario: Operación suspendida temporalmente\n\n**Trabajos a realizar:**\n- Inspección de tanques de almacenamiento\n- Mantenimiento de bombas de transferencia\n- Calibración de sistemas de medición\n- Pruebas de sistemas contra incendios\n\n**Impacto en importaciones:**\n- Descarga de buques tanque: Suspendida 3 días\n- Buques programados reprogramados a días posteriores\n- Transporte terrestre desde terminal: Normal después del día 10\n\n**Medidas de contingencia:**\n- Stock de 15 días activado en terminales bolivianos\n- Coordinación con terminal alternativo de Ilo (Perú)\n- Comunicación con distribuidores mayoristas\n\n**Importante:** Este mantenimiento es obligatorio por normativas portuarias chilenas y garantiza la seguridad de nuestras operaciones de importación.\n\nNo se anticipa desabastecimiento a nivel nacional.",
                'status' => 'SCHEDULED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => '2025-11-08T00:00:00Z',
                    'scheduled_end' => '2025-11-10T23:59:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['terminal_arica', 'importacion_combustibles', 'logistica_internacional'],
                ],
                'created_at' => '2025-10-28 09:00:00',
                'published_at' => '2025-10-28 14:00:00',
            ],

            // ========== INCIDENT (5 anuncios - 25%) ==========
            [
                'type' => 'INCIDENT',
                'title' => 'CRÍTICO: Retraso en llegada de buque tanque con diésel importado',
                'content' => "**INCIDENTE CRÍTICO - LOGÍSTICA DE IMPORTACIÓN**\n\nReportamos retraso en la llegada del buque tanque MT \"Pacific Voyager\" con cargamento de diésel importado.\n\n**Detalles del incidente:**\n- Buque: MT Pacific Voyager\n- Origen: Houston, Texas\n- Destino: Puerto de Arica, Chile\n- Carga: 45,000 toneladas de diésel\n- Retraso: 5 días (condiciones climáticas adversas en Pacífico Sur)\n\n**Impacto estimado:**\n- Stock nacional actual: 8 días de consumo\n- Llegada original: 18 de Febrero\n- Nueva llegada estimada: 23 de Febrero\n- Stock restante al llegar: 3 días\n\n**Acciones tomadas:**\n- Activación de importación de emergencia desde Argentina (por tierra)\n- Racionalización de entregas a estaciones de servicio\n- Priorización de sectores estratégicos (transporte público, hospitales)\n- Comunicación con Ministerio de Hidrocarburos\n\n**Estado del incidente:** EN MONITOREO\n\n**Próxima actualización:** Mañana 09:00 AM\n\n**Contacto de prensa:** comunicacion@ypfb.gob.bo",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'CRITICAL',
                    'resolution_content' => null,
                    'affected_services' => ['importacion_diesel', 'abastecimiento_nacional', 'logistica_maritima'],
                    'started_at' => '2025-02-15T08:00:00Z',
                    'resolved_at' => null,
                ],
                'created_at' => '2025-02-15 08:30:00',
                'published_at' => '2025-02-15 09:00:00',
            ],

            [
                'type' => 'INCIDENT',
                'title' => 'RESUELTO: Interrupción de suministro de gas en zona industrial Santa Cruz',
                'content' => "**INCIDENTE RESUELTO**\n\nReportamos incidente de interrupción de suministro de gas natural en la zona industrial de Santa Cruz (Parque Industrial Norte).\n\n**Cronología del incidente:**\n- 07:45 AM: Reporte de caída de presión en red industrial\n- 08:00 AM: Identificación de fuga en estación de regulación ER-SCZ-12\n- 08:15 AM: Aislamiento del tramo afectado\n- 10:30 AM: Reparación completada\n- 10:45 AM: Servicio normalizado completamente\n\n**Empresas afectadas:**\n- 23 industrias (manufactura, textiles, alimentos)\n- Duración de afectación: 3 horas\n\n**Causa raíz:**\nFalla de válvula de regulación por fatiga de material (15 años de operación).\n\n**Resolución:**\n- Válvula reemplazada por unidad nueva\n- Pruebas de presión completadas satisfactoriamente\n- Suministro normalizado a todas las industrias\n\n**Acciones preventivas:**\n- Programa de reemplazo de válvulas antiguas acelerado\n- Inspección de 45 estaciones similares programada\n\n**Sin pérdidas de producción significativas reportadas por empresas afectadas.**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'resolution_content' => 'Válvula de regulación reemplazada. Suministro de gas normalizado. Programa de mantenimiento preventivo reforzado.',
                    'affected_services' => ['suministro_gas_industrial', 'parque_industrial_norte', 'red_distribucion_scz'],
                    'started_at' => '2025-04-08T07:45:00Z',
                    'resolved_at' => '2025-04-08T10:45:00Z',
                ],
                'created_at' => '2025-04-08 08:00:00',
                'published_at' => '2025-04-08 08:15:00',
            ],

            [
                'type' => 'INCIDENT',
                'title' => 'EN RESOLUCIÓN: Bloqueo de carreteras afecta distribución de combustibles',
                'content' => "**INCIDENTE EN CURSO**\n\nInformamos afectaciones en la distribución de combustibles debido a bloqueos de carreteras en el eje troncal del país.\n\n**Situación actual (12:30):**\n- 47 camiones cisterna retenidos en diferentes puntos\n- Puntos de bloqueo: Caracollo, Patacamaya, Warnes\n- Combustible retenido: ~1,200 m³ (gasolina y diésel)\n- Estaciones de servicio reportando stock bajo\n\n**Departamentos afectados:**\n- La Paz: 35% de estaciones con bajo stock\n- Oruro: 40% de estaciones con bajo stock\n- Cochabamba: Sin afectación (abastecimiento local)\n- Santa Cruz: Sin afectación (abastecimiento local)\n\n**Acciones tomadas:**\n- Coordinación con Policía Boliviana para corredores humanitarios\n- Gestión con Gobierno para diálogo con bloqueadores\n- Rutas alternativas evaluadas (costos adicionales significativos)\n- Comunicación directa con estaciones afectadas\n\n**Recomendaciones a la población:**\n- No saturar estaciones de servicio\n- Evitar compras de pánico\n- Stock nacional es suficiente, el problema es logístico\n\n**Próxima actualización:** 18:00 horas\n\n**Línea de información:** 800-10-0965",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'affected_services' => ['distribucion_combustibles', 'logistica_terrestre', 'estaciones_servicio'],
                    'started_at' => '2025-06-20T06:00:00Z',
                    'resolved_at' => null,
                ],
                'created_at' => '2025-06-20 12:00:00',
                'published_at' => '2025-06-20 12:30:00',
            ],

            [
                'type' => 'INCIDENT',
                'title' => 'RESUELTO: Falla de sistema de facturación electrónica - 4 horas de afectación',
                'content' => "**INCIDENTE RESUELTO**\n\nReportamos incidente en el Sistema de Facturación Electrónica de YPFB que afectó operaciones comerciales durante 4 horas.\n\n**Detalles del incidente:**\n- Fecha: 15 de Julio de 2025\n- Inicio: 10:15 AM\n- Fin: 14:20 PM\n- Sistema: Plataforma de facturación corporativa (integrada con SIN)\n\n**Síntomas reportados:**\n- Error al generar facturas electrónicas\n- Timeout en consultas al SIN\n- Imposibilidad de emitir notas de crédito\n- Clientes corporativos no podían recibir documentos\n\n**Impacto:**\n- 342 facturas pendientes de emisión\n- 12 clientes corporativos afectados\n- Ventas no afectadas (se registraron manualmente)\n\n**Causa raíz:**\nActualización de certificados SSL del SIN (Servicio de Impuestos Nacionales) sin notificación previa. Nuestro sistema no reconoció los nuevos certificados.\n\n**Resolución:**\n- Certificados actualizados en nuestros servidores\n- Todas las facturas pendientes emitidas exitosamente\n- Sincronización completa con SIN verificada\n\n**Acciones preventivas:**\n- Monitoreo automatizado de certificados implementado\n- Comunicación establecida con SIN para alertas previas\n\n**Disculpas por las molestias ocasionadas.**",
                'status' => 'ARCHIVED',
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'resolution_content' => 'Certificados SSL actualizados. Facturas pendientes emitidas. Sistema normalizado. Monitoreo preventivo implementado.',
                    'affected_services' => ['facturacion_electronica', 'integracion_sin', 'ventas_corporativas'],
                    'started_at' => '2025-07-15T10:15:00Z',
                    'resolved_at' => '2025-07-15T14:20:00Z',
                ],
                'created_at' => '2025-07-15 10:30:00',
                'published_at' => '2025-07-15 10:45:00',
            ],

            [
                'type' => 'INCIDENT',
                'title' => 'RESUELTO: Derrame menor controlado en campo San Alberto - Sin afectación ambiental',
                'content' => "**INCIDENTE DE SEGURIDAD - RESUELTO**\n\n**COMUNICADO OFICIAL YPFB**\n\nReportamos incidente de derrame menor en instalaciones del Campo San Alberto, Tarija.\n\n**Detalles del incidente:**\n- Fecha: 3 de Septiembre de 2025\n- Hora de detección: 06:30 AM\n- Ubicación: Área de separación, válvula VL-SA-0087\n- Sustancia: Condensado de gas (aprox. 2 m³)\n- Duración hasta contención: 45 minutos\n\n**Acciones inmediatas ejecutadas:**\n✓ Activación de protocolo de emergencia ambiental\n✓ Cierre de válvulas de aislamiento\n✓ Despliegue de material absorbente\n✓ Brigada de respuesta a emergencias en sitio\n✓ Notificación a autoridad ambiental (ABT)\n\n**Evaluación ambiental:**\n- Derrame contenido en área operativa pavimentada\n- NO alcanzó cuerpos de agua\n- NO afectación a suelo natural\n- NO afectación a comunidades\n- Fauna y flora: Sin impacto\n\n**Causa identificada:**\nCorrosión en junta de brida (detectada en inspección post-incidente).\n\n**Medidas correctivas:**\n- Brida y junta reemplazadas\n- Inspección de 120 juntas similares programada\n- Suelo contaminado removido y enviado a tratamiento\n\n**Certificación:**\nInspección de ABT realizada el 4/09. Acta de conformidad emitida.\n\n**YPFB reitera su compromiso con la seguridad operacional y protección ambiental.**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'resolution_content' => 'Derrame contenido y limpiado. Causa identificada y corregida. Certificación ambiental obtenida. Sin impacto ambiental.',
                    'affected_services' => ['campo_san_alberto', 'seguridad_ambiental', 'produccion_gas'],
                    'started_at' => '2025-09-03T06:30:00Z',
                    'resolved_at' => '2025-09-03T07:15:00Z',
                ],
                'created_at' => '2025-09-03 08:00:00',
                'published_at' => '2025-09-03 09:00:00',
            ],

            // ========== NEWS (7 anuncios - 35%) ==========
            [
                'type' => 'NEWS',
                'title' => 'YPFB anuncia plan de inversión de USD 500 millones en exploración 2025-2027',
                'content' => "**COMUNICADO OFICIAL - PLAN ESTRATÉGICO**\n\n¡YPFB anuncia el plan de inversión más ambicioso en exploración de la última década!\n\n**Inversión comprometida:**\n- Monto total: USD 500 millones (2025-2027)\n- 2025: USD 180 millones\n- 2026: USD 180 millones\n- 2027: USD 140 millones\n\n**Proyectos prioritarios:**\n\n**1. Bloque Aguaragüe Sur (Tarija):**\n- Inversión: USD 85 millones\n- Potencial: 2 TCF de gas natural\n- Inicio de perforación: Q2 2025\n\n**2. Incahuasi Fase II (Chuquisaca):**\n- Inversión: USD 120 millones\n- Objetivo: Aumentar producción 40%\n- Pozos nuevos: 4\n\n**3. Exploratorios Beni Norte:**\n- Inversión: USD 95 millones\n- Área: Nuevos bloques inexplorados\n- Estudios sísmicos: En curso\n\n**Objetivos nacionales:**\n- Revertir declinación de producción\n- Garantizar autosuficiencia energética\n- Recuperar mercados de exportación\n\n**Alianzas estratégicas:**\n- Petrobras (Brasil): En negociación\n- Shell: Carta de intención firmada\n- PDVSA (Venezuela): Cooperación técnica\n\n**Presidente de YPFB:**\n\"Este plan representa el compromiso del Estado boliviano con la soberanía energética. No podemos seguir dependiendo de importaciones.\"\n\n**Más información:** www.ypfb.gob.bo/plan2025",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'YPFB invertirá USD 500 millones en exploración durante 2025-2027. Proyectos en Tarija, Chuquisaca y Beni para revertir declinación productiva.',
                    'call_to_action' => 'Consulta los detalles del plan en www.ypfb.gob.bo/plan2025',
                ],
                'created_at' => '2025-01-20 09:00:00',
                'published_at' => '2025-01-20 10:00:00',
            ],

            [
                'type' => 'NEWS',
                'title' => 'YPFB supera meta de conexiones de gas domiciliario: 85,000 nuevos hogares',
                'content' => "**LOGRO INSTITUCIONAL**\n\n¡YPFB supera la meta anual de conexiones de gas domiciliario!\n\n**Resultados 2025 (al 30 de Junio):**\n- Meta anual: 80,000 conexiones\n- Ejecutadas: 85,247 conexiones\n- Cumplimiento: 106.5%\n- ¡El mejor semestre en la historia de YPFB!\n\n**Distribución por departamento:**\n- Santa Cruz: 28,500 conexiones (33%)\n- La Paz: 22,300 conexiones (26%)\n- Cochabamba: 18,100 conexiones (21%)\n- Tarija: 8,200 conexiones (10%)\n- Otros: 8,147 conexiones (10%)\n\n**Beneficios para las familias:**\n✓ Ahorro mensual promedio: Bs. 150-200\n✓ Combustible más limpio y seguro\n✓ Disponibilidad 24/7\n✓ Sin necesidad de recargas de GLP\n✓ Menor huella de carbono\n\n**Zonas prioritarias atendidas:**\n- Barrios periurbanos de capitales\n- Municipios intermedios\n- Zonas de expansión urbana\n\n**Inversión ejecutada:** USD 42 millones\n\n**Meta segundo semestre:** 82,000 conexiones adicionales\n\n**¿Cómo solicitar conexión?**\n- Web: www.ypfb.gob.bo/conexiones\n- Línea gratuita: 800-10-0965\n- Oficinas YPFB en todo el país\n\n**El gas natural llega a más hogares bolivianos cada día.**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'YPFB conectó 85,247 hogares a gas domiciliario en el primer semestre, superando la meta de 80,000. Ahorro mensual de Bs. 150-200 por familia.',
                    'call_to_action' => 'Solicita tu conexión en www.ypfb.gob.bo/conexiones',
                ],
                'created_at' => '2025-07-05 08:00:00',
                'published_at' => '2025-07-05 09:30:00',
            ],

            [
                'type' => 'NEWS',
                'title' => 'Nueva app YPFB Estaciones: encuentra combustible cerca de ti',
                'content' => "**LANZAMIENTO DIGITAL**\n\n¡Descarga la nueva **App YPFB Estaciones** y encuentra combustible al instante!\n\n**Disponible AHORA:**\n- Google Play Store (Android)\n- App Store (iOS)\n\nBusca: \"YPFB Estaciones Bolivia\"\n\n**Funcionalidades principales:**\n\n**1. Mapa de estaciones:**\n- Ubicación en tiempo real de todas las estaciones YPFB\n- Filtro por tipo de combustible (gasolina, diésel, GNV)\n- Distancia y tiempo de llegada\n- Navegación integrada con Google Maps/Waze\n\n**2. Disponibilidad de combustible:**\n- Estado de stock por estación (VERDE/AMARILLO/ROJO)\n- Alertas de desabastecimiento temporal\n- Notificaciones de reabastecimiento\n\n**3. Precios actualizados:**\n- Precios oficiales vigentes\n- Historial de precios\n- Comparador de estaciones cercanas\n\n**4. Servicios adicionales:**\n- Horarios de atención\n- Servicios disponibles (aire, agua, tienda)\n- Calificaciones de usuarios\n\n**Beneficios:**\n✓ Evita viajes innecesarios\n✓ Ahorra tiempo buscando combustible\n✓ Información oficial y verificada\n✓ Funciona sin conexión (modo offline)\n\n**Requisitos:**\n- Android 7.0+ o iOS 12+\n- Ubicación GPS activada\n\n**Soporte:**\n- WhatsApp: +591 2210-6565\n- Email: app@ypfb.gob.bo\n\n**¡Descárgala HOY y olvídate de buscar combustible!**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => 'all_users',
                    'summary' => 'Nueva app YPFB Estaciones permite encontrar estaciones de servicio, ver disponibilidad de combustible en tiempo real y navegar hacia ellas.',
                    'call_to_action' => 'Descarga la app YPFB Estaciones en Play Store o App Store',
                ],
                'created_at' => '2025-04-15 08:30:00',
                'published_at' => '2025-04-15 09:00:00',
            ],

            [
                'type' => 'NEWS',
                'title' => 'YPFB inaugura tres nuevas estaciones de servicio en La Paz',
                'content' => "**EXPANSIÓN DE RED DE SERVICIOS**\n\nYPFB inaugura tres nuevas estaciones de servicio en el departamento de La Paz, ampliando la cobertura nacional.\n\n**Nuevas estaciones:**\n\n**1. Estación YPFB Viacha:**\n- Ubicación: Av. Franz Tamayo, Viacha\n- Servicios: Gasolina, Diésel, GNV, Tienda 24h\n- Capacidad: 200 vehículos/hora\n- Inauguración: 20 de Mayo de 2025\n\n**2. Estación YPFB El Alto (Satélite):**\n- Ubicación: Av. Juan Pablo II, Distrito 8\n- Servicios: Gasolina, Diésel, GNV, Lavado\n- Capacidad: 180 vehículos/hora\n- Inauguración: 25 de Mayo de 2025\n\n**3. Estación YPFB Sopocachi:**\n- Ubicación: Av. 20 de Octubre, zona Sopocachi\n- Servicios: Gasolina, Diésel, tienda premium\n- Capacidad: 120 vehículos/hora\n- Inauguración: 30 de Mayo de 2025\n\n**Inversión total:** USD 8.5 millones\n\n**Empleos generados:** 75 empleos directos\n\n**Características modernas:**\n✓ Surtidores de última generación\n✓ Sistema de pago electrónico (QR y tarjetas)\n✓ Tiendas de conveniencia 24/7\n✓ Iluminación LED eficiente\n✓ Sistemas de seguridad avanzados\n\n**Promoción de inauguración:**\n- Primer tanque: 10% descuento (primeros 500 clientes)\n- Producto gratis en tienda con cada llenado\n\n**Red YPFB en La Paz:** Ahora 47 estaciones de servicio.",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'YPFB inaugura estaciones en Viacha, El Alto y Sopocachi. Inversión de USD 8.5 millones y 75 nuevos empleos.',
                    'call_to_action' => 'Visita las nuevas estaciones y aprovecha el 10% de descuento de inauguración',
                ],
                'created_at' => '2025-05-18 10:00:00',
                'published_at' => '2025-05-18 11:00:00',
            ],

            [
                'type' => 'NEWS',
                'title' => 'Actualización de protocolo de seguridad para transporte de combustibles',
                'content' => "**COMUNICADO NORMATIVO**\n\nYPFB informa la actualización del Protocolo de Seguridad para el Transporte de Combustibles, vigente desde el 1 de Agosto de 2025.\n\n**CAMBIOS PRINCIPALES:**\n\n**1. Nuevos requisitos para transportistas:**\n- Certificación anual obligatoria (antes bienal)\n- Capacitación en manejo de emergencias (16 horas)\n- GPS con reporte cada 5 minutos (antes 15 minutos)\n- Cámaras de cabina obligatorias\n\n**2. Vehículos:**\n- Inspección técnica semestral (antes anual)\n- Sistema de frenado ABS obligatorio\n- Límite de antigüedad: 15 años (antes 20 años)\n- Válvulas de emergencia con sensor automático\n\n**3. Rutas y horarios:**\n- Restricción de circulación nocturna en zonas urbanas\n- Rutas alternativas obligatorias en época de lluvias\n- Paradas obligatorias cada 4 horas de conducción\n\n**4. Documentación:**\n- Manifiesto electrónico (SIN integrado)\n- Hoja de seguridad del producto actualizada\n- Registro de conductores en base de datos YPFB\n\n**CAPACITACIONES PROGRAMADAS:**\n- La Paz: 15-17 de Julio\n- Santa Cruz: 22-24 de Julio\n- Cochabamba: 29-31 de Julio\n\n**Inscripciones:** transporte@ypfb.gob.bo\n\n**Plazo de adecuación:** 90 días desde el 1 de Agosto.\n\n**Multas por incumplimiento:** Bs. 5,000 - 50,000 según gravedad.\n\n**Manual completo:** www.ypfb.gob.bo/transporte-seguro",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'policy_update',
                    'target_audience' => 'business_clients',
                    'summary' => 'Nuevo protocolo de seguridad para transporte de combustibles: certificación anual, GPS cada 5 min, vehículos máximo 15 años. Vigente desde 1/Agosto/2025.',
                    'call_to_action' => 'Inscríbete a las capacitaciones obligatorias en transporte@ypfb.gob.bo',
                ],
                'created_at' => '2025-06-28 09:00:00',
                'published_at' => '2025-06-28 14:00:00',
            ],

            [
                'type' => 'NEWS',
                'title' => 'YPFB firma convenio con universidades para formación de ingenieros petroleros',
                'content' => "**ALIANZA EDUCATIVA**\n\nYPFB y las principales universidades bolivianas firman convenio histórico para formación de profesionales en hidrocarburos.\n\n**Universidades participantes:**\n- UMSA (La Paz)\n- UMSS (Cochabamba)\n- UAGRM (Santa Cruz)\n- UAJMS (Tarija)\n- UTB (Oruro)\n\n**Componentes del programa:**\n\n**1. Becas de estudio:**\n- 200 becas completas para ingeniería petrolera\n- 100 becas para carreras técnicas relacionadas\n- Cobertura: Matrícula + manutención mensual\n\n**2. Prácticas profesionales:**\n- 500 cupos anuales en instalaciones YPFB\n- 6 meses de práctica supervisada\n- Posibilidad de contratación posterior\n\n**3. Investigación conjunta:**\n- Laboratorios de petrología compartidos\n- Proyectos de investigación financiados\n- Intercambio de docentes y especialistas\n\n**4. Actualización curricular:**\n- Programas de estudio actualizados con industria\n- Módulos de tecnología de punta\n- Certificaciones internacionales incluidas\n\n**Inversión YPFB:** USD 15 millones en 5 años\n\n**Palabras del Ministro de Hidrocarburos:**\n\"Bolivia necesita profesionales propios para garantizar la soberanía energética. Este convenio es un paso histórico.\"\n\n**Postulaciones para becas:**\n- Apertura: 15 de Enero 2026\n- Web: www.ypfb.gob.bo/becas\n\n**El futuro energético de Bolivia se construye con educación.**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'YPFB y 5 universidades bolivianas firman convenio: 300 becas, 500 cupos de prácticas y USD 15 millones de inversión en formación de ingenieros.',
                    'call_to_action' => 'Postula a las becas desde enero 2026 en www.ypfb.gob.bo/becas',
                ],
                'created_at' => '2025-10-10 10:00:00',
                'published_at' => '2025-10-10 11:30:00',
            ],

            [
                'type' => 'NEWS',
                'title' => 'Avances en construcción de Planta de Biodiésel: 65% de progreso',
                'content' => "**PROYECTO ESTRATÉGICO - ACTUALIZACIÓN**\n\nYPFB informa avances significativos en la construcción de la Planta de Biodiésel I en El Alto.\n\n**Estado actual del proyecto:**\n- Avance físico: 65%\n- Avance financiero: 58%\n- Fecha de entrega estimada: Q2 2026\n- Inversión total: USD 150 millones\n\n**Hitos completados:**\n✓ Obra civil (cimentación y estructura): 100%\n✓ Instalación de tanques de almacenamiento: 90%\n✓ Sistema de tuberías principales: 75%\n✓ Equipos de proceso importados: 80% instalados\n✓ Sistema eléctrico: 60%\n✓ Sistema de control: En instalación\n\n**Próximos hitos:**\n- Diciembre 2025: Montaje de equipos críticos completado\n- Febrero 2026: Pruebas de integridad mecánica\n- Abril 2026: Comisionamiento y pruebas\n- Junio 2026: Inicio de operaciones comerciales\n\n**Capacidad de la planta:**\n- Producción: 14,000 barriles/día de biodiésel\n- Materia prima: Aceite de soya boliviano\n- Reducción de importaciones: 40% del diésel actual\n\n**Beneficios nacionales:**\n- 300 empleos directos\n- USD 200 millones en ahorro anual de divisas\n- Apoyo a productores de soya boliviana\n- Reducción de huella de carbono\n\n**Visita de obra:**\nAgenda una visita técnica: proyectos@ypfb.gob.bo\n\n**Bolivia construye su futuro energético sostenible.**",
                'status' => 'DRAFT',
                'metadata' => [
                    'news_type' => 'general_update',
                    'target_audience' => 'all_users',
                    'summary' => 'Planta de Biodiésel en El Alto alcanza 65% de avance. Capacidad de 14,000 barriles/día. Inicio de operaciones en junio 2026.',
                    'call_to_action' => 'Agenda una visita técnica al proyecto',
                ],
                'created_at' => '2025-11-15 09:00:00',
                'published_at' => null,
            ],

            // ========== ALERT (3 anuncios - 15%) ==========
            [
                'type' => 'ALERT',
                'title' => 'ALERTA: Nueva modalidad de estafa usando nombre de YPFB',
                'content' => "**ALERTA DE SEGURIDAD**\n\n⚠️ ADVERTENCIA IMPORTANTE ⚠️\n\nYPFB advierte a la población sobre una nueva modalidad de estafa que utiliza fraudulentamente el nombre de la empresa.\n\n**Modalidad detectada:**\n- Llamadas telefónicas de supuestos \"funcionarios de YPFB\"\n- Ofrecen trabajo en la empresa\n- Solicitan depósito de \"garantía\" (Bs. 500 - 2,000)\n- Proporcionan cuentas bancarias personales\n- Utilizan logos y nombres falsos de jefaturas\n\n**YPFB NUNCA:**\n❌ Solicita depósitos de dinero para procesos de contratación\n❌ Realiza ofertas laborales por WhatsApp\n❌ Pide datos bancarios por teléfono\n❌ Ofrece \"cupos de empleo\" a cambio de pago\n\n**Si recibe una llamada sospechosa:**\n1. NO proporcione datos personales\n2. NO realice ningún depósito\n3. Anote el número de teléfono\n4. Denuncie a:\n   - Policía: 110\n   - YPFB: 800-10-0965\n   - Email: denuncias@ypfb.gob.bo\n\n**Convocatorias oficiales:**\nTodas las convocatorias de empleo de YPFB se publican ÚNICAMENTE en:\n- www.ypfb.gob.bo/trabaja-con-nosotros\n- Periódicos de circulación nacional\n\n**Proteja su patrimonio. Desconfíe de ofertas \"demasiado buenas\".**\n\n**Esta alerta está vigente de forma permanente.**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'security',
                    'message' => 'Estafadores ofrecen falsos empleos en YPFB a cambio de depósitos. YPFB NUNCA solicita dinero por contrataciones.',
                    'action_required' => true,
                    'action_description' => 'Denunciar llamadas sospechosas a Policía (110) o YPFB (800-10-0965). NO realizar depósitos.',
                    'started_at' => '2025-03-01T00:00:00Z',
                    'ended_at' => '2025-12-31T23:59:59Z',
                    'affected_services' => ['todos'],
                ],
                'created_at' => '2025-02-28 09:00:00',
                'published_at' => '2025-03-01 08:00:00',
            ],

            [
                'type' => 'ALERT',
                'title' => 'IMPORTANTE: Actualización de registro para estaciones de servicio afiliadas',
                'content' => "**ALERTA REGULATORIA**\n\n**ACTUALIZACIÓN OBLIGATORIA DE REGISTRO**\n\nEn cumplimiento de la Resolución ANH N° 0245/2025, TODAS las estaciones de servicio afiliadas a YPFB deben actualizar su registro comercial.\n\n**¿A quiénes aplica?**\n- Estaciones de servicio con bandera YPFB\n- Distribuidores mayoristas de combustibles\n- Operadores de estaciones GNV\n\n**Documentos requeridos:**\n\n**Personas Jurídicas:**\n✓ NIT actualizado\n✓ Licencia de funcionamiento municipal (vigente)\n✓ Certificado de compatibilidad de uso de suelos\n✓ Póliza de seguro contra incendios (mínimo USD 500,000)\n✓ Certificado ambiental (RASIM actualizado)\n✓ Registro de tanques (calibración anual)\n✓ Planilla de empleados ante CNS\n\n**PLAZO LÍMITE:**\n**15 de Octubre de 2025**\n\n**⚠️ IMPORTANTE:**\nEstaciones que NO actualicen registro antes del plazo:\n- Suspensión de suministro de combustibles\n- Multa de hasta Bs. 100,000\n- Inhabilitación temporal de licencia\n\n**Formas de presentar documentos:**\n\n**Opción 1: Portal web**\n- Ingresa a: estaciones.ypfb.gob.bo\n- Sección \"Actualización 2025\"\n- Carga documentos digitalizados (PDF)\n\n**Opción 2: Presencial**\n- Oficinas regionales YPFB (L-V 8:30-16:30)\n- Llevar originales + copias\n\n**Soporte:**\n- WhatsApp: +591 2210-6565\n- Email: estaciones@ypfb.gob.bo\n- Línea gratuita: 800-10-0965\n\n**NO dejes para último momento. Actualiza HOY.**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'compliance',
                    'message' => 'Estaciones de servicio deben actualizar registro antes del 15 de Octubre 2025. Riesgo de suspensión de suministro.',
                    'action_required' => true,
                    'action_description' => 'Actualizar documentación vía estaciones.ypfb.gob.bo o presencial antes del 15/Oct/2025.',
                    'started_at' => '2025-08-01T00:00:00Z',
                    'ended_at' => '2025-10-15T23:59:59Z',
                    'affected_services' => ['estaciones_servicio', 'distribuidores_mayoristas', 'operadores_gnv'],
                ],
                'created_at' => '2025-07-28 09:00:00',
                'published_at' => '2025-08-01 08:00:00',
            ],

            [
                'type' => 'ALERT',
                'title' => 'AVISO: Posible desabastecimiento temporal por conflicto social en Potosí',
                'content' => "**ALERTA DE CONTINGENCIA**\n\n⚠️ AVISO PREVENTIVO ⚠️\n\nYPFB informa posible afectación en el abastecimiento de combustibles en el departamento de Potosí debido a conflicto social en curso.\n\n**Situación actual:**\n- Bloqueo parcial de accesos a Potosí capital\n- 8 camiones cisterna retenidos desde hace 12 horas\n- Suministro regular interrumpido\n\n**Stock actual en Potosí:**\n- Gasolina: 4 días de consumo normal\n- Diésel: 3 días de consumo normal\n- GLP: 5 días de consumo normal\n\n**Medidas activadas:**\n- Negociación con sectores sociales (en curso)\n- Rutas alternativas evaluadas (costo adicional)\n- Stock de reserva en Oruro disponible\n- Comunicación directa con estaciones de servicio\n\n**Recomendaciones a la población:**\n- Consumo responsable (no acaparar)\n- Evitar compras de pánico\n- Priorizar viajes esenciales\n- Seguir canales oficiales de información\n\n**Sectores prioritarios:**\n- Hospitales y centros de salud\n- Transporte público\n- Servicios de emergencia\n- Ambulancias\n\n**Actualizaciones:**\n- Twitter/X: @YPFBoficial\n- Web: www.ypfb.gob.bo/noticias\n- Línea: 800-10-0965\n\n**Próxima actualización:** Mañana 10:00 AM\n\n**YPFB trabaja para normalizar el abastecimiento lo antes posible.**",
                'status' => 'PUBLISHED',
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'service',
                    'message' => 'Posible desabastecimiento en Potosí por bloqueos. Stock para 3-5 días. Evitar compras de pánico.',
                    'action_required' => false,
                    'action_description' => null,
                    'started_at' => '2025-09-18T06:00:00Z',
                    'ended_at' => '2025-09-25T23:59:59Z',
                    'affected_services' => ['distribucion_potosi', 'abastecimiento_combustibles', 'transporte_terrestre'],
                ],
                'created_at' => '2025-09-18 08:00:00',
                'published_at' => '2025-09-18 08:30:00',
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

        $this->command->info('✅ 20 anuncios creados para YPFB (MAINTENANCE: 5, INCIDENT: 5, NEWS: 7, ALERT: 3)');
    }
}
