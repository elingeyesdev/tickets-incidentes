<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * YPFB Announcements Seeder - Crisis de Hidrocarburos Bolivianos (Nov 2025)
 *
 * Crea anuncios realistas que reflejan la situación actual de YPFB:
 * - Cambio de administración (Yussef Akly asumió 09-11-2025)
 * - Crisis de importación de combustibles (stock 2-3 días)
 * - Presión política de Rodrigo Paz (tolerancia cero corrupción)
 * - Despido de Armin Dorgathen (07-11-2025) por negligencia
 * - Investigación anticorrupción en marcha
 * - Plan 100 días de gestión
 * - Reestructuración financiera
 * - Iniciativas de exploración
 * - Medidas de modernización
 * - Cumplimiento y governance
 *
 * Anuncios reflejan la crisis múltiple:
 * - Producción en declive (40% última década)
 * - Pérdida mercado Argentina ($3.961B anuales)
 * - Deficit energético $502MM (2024)
 * - Deuda con proveedores internacionales
 * - Presión por garantizar abastecimiento nacional
 */
class YPFBAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // Find YPFB company
        $company = Company::where('name', 'YPFB Corporación')->first();

        if (!$company) {
            $this->command->error('❌ YPFB Corporación company not found. Please run RealBolivianCompaniesSeeder first.');
            return;
        }

        // Find company admin
        $admin = User::where('email', 'luis.sanchez@ypfb.gob.bo')->first();

        if (!$admin) {
            $this->command->error('❌ YPFB company admin not found.');
            return;
        }

        $this->command->info('⛽ Creando anuncios realistas de YPFB (Crisis de Hidrocarburos - Noviembre 2025)...');

        // ===== PUBLISHED ANNOUNCEMENTS =====

        // November 9 - NEWS: New Leadership
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'ANUNCIO OFICIAL: Nueva Administración de YPFB - Yussef Akly Asume Presidencia',
            'content' => "Estimado personal de YPFB,

El Presidente Rodrigo Paz ha designado oficialmente a Yussef Akly Flores como Presidente de Yacimientos Petrolíferos Fiscales Bolivianos a partir del 09 de noviembre de 2025.

PERFIL DEL NUEVO PRESIDENTE:
- Ingeniero industrial con especialización en gas y petróleo
- 18 años de experiencia en sector hidrocarburos
- Master en Oil & Natural Gas
- Experiencia en evaluación de proyectos, finanzas corporativas y supply chain

PRIORIDADES INMEDIATAS (Plan 100 Días):
1. Estabilizar crisis de importación de combustibles
2. Implementar controles anticorrupción agresivos
3. Ejecutar plan de exploración y nuevos proyectos
4. Modernizar infraestructura (SCADA, logística)
5. Renegociar líneas de crédito internacionales

COMPROMISO CON LA TRANSPARENCIA:
Bajo la dirección de Paz, YPFB implementa tolerancia cero contra corrupción, negligencia y sabotaje. Esperamos 100% cumplimiento normativo de todo el personal.

CONTINUIDAD OPERATIVA:
Todas las operaciones de producción, transporte y refinería continúan bajo supervisión 24/7. El cambio administrativo NO afecta operaciones.

Juntos, revertiremos la crisis energética de Bolivia.

Yussef Akly Flores
Presidente YPFB
Cochabamba, 09 de noviembre de 2025",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'leadership_change',
                'target_audience' => ['users', 'agents', 'admins'],
                'summary' => 'Yussef Akly asume presidencia de YPFB - Plan 100 días de transformación',
                'urgency' => 'HIGH',
            ],
            'published_at' => '2025-11-09 10:00:00',
        ]);

        // November 10 - ALERT: Fuel Emergency
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'ALERTA CRÍTICA: Situación de emergencia en importación de combustibles',
            'content' => "COMUNICADO URGENTE

A partir del 10 de noviembre de 2025, YPFB enfrenta una situación crítica en la importación de combustibles derivada de:

SITUACIÓN ACTUAL:
✗ Stock nacional de diésel: ~52 horas (deberíamos tener 15+ días)
✗ Stock nacional de gasolina: ~68 horas
✗ Importamos 90% del diésel consumido
✗ Importamos 50% de la gasolina consumida
✗ Línea de crédito con Petroecuador agotada ($78.5MM de deuda)
✗ Proveedores exigen pago contado (crédito suspendido)

CAUSAS:
- Gestión deficiente de la importación (INVESTIGACIÓN EN CURSO)
- Falta de coordinación en logística
- Problemas de financiamiento

ACCIONES INMEDIATAS:
1. Gestión de línea de crédito emergente con CEPAL/BID
2. Negociación con Petroecuador para refinanciamiento
3. Evaluación de proveedores alternativos (Perú, Colombia)
4. Optimización de distribución (reducir pérdidas)

COMPROMISO:
El nuevo equipo de dirección garantiza abastecimiento nacional. Bajo presión del Presidente Paz, se implementan medidas EXTRAORDINARIAS para resolver esta crisis antes de fin de mes.

TODO EL PERSONAL: Máxima eficiencia en operaciones. NO toleraremos negligencia o sabotaje.

Yussef Akly
Presidente YPFB
10 de noviembre de 2025",
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'alert_type' => 'fuel_shortage',
                'action_required' => true,
                'action_description' => 'Todas las operaciones enfocadas en continuidad. Reportar anomalías inmediatamente a supervisores.',
                'started_at' => '2025-11-10T00:00:00Z',
            ],
            'published_at' => '2025-11-10 06:00:00',
        ]);

        // November 11 - NEWS: Corruption Investigation
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'INVESTIGACIÓN ANTICORRUPCIÓN: Auditoría especial ordenada por Presidencia',
            'content' => "Comunicado de Dirección - Información Oficial

La Presidencia de la República ha ordenado una auditoría especial en YPFB para investigar indicios de corrupción, mal manejo de fondos y negligencia en gestión anterior.

CONTEXTO:
- El Presidente Rodrigo Paz ha manifestado 'tolerancia cero contra corrupción'
- La Fiscal Anticorrupción ha abierto investigación formal
- Se ha solicitado alerta migratoria contra ex-presidente anterior (Armin Ludwig Dorgathen Tapia)

HALLAZGOS PRELIMINARES:
- Contrataciones favorables a empresas vinculadas
- Sobre-facturación en importación de combustibles
- Personal fantasma en nóminas
- Registros incompletos o faltantes (2023-2024)
- Pérdida estimada: $25-35 millones

ACCIONES TOMADAS:
✓ Auditoría interna intensificada (auditor externo KPMG contratado)
✓ Supervisión 3-firmas para aprobación de gastos
✓ Auditorías sorpresa mensuales a proveedores
✓ Transparencia total con Fiscalía

COMPROMISO CON EMPLEADOS:
- Empleados honestos: PROTECCIÓN Y APOYO
- Empleados involucrados: MÁXIMAS CONSECUENCIAS (legales)

Esperamos 100% cooperación. Denuncie irregularidades a: denuncias_anticorrupcion@ypfb.gob.bo (confidencial).

Yussef Akly
Presidente YPFB
11 de noviembre de 2025",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'governance_update',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Investigación anticorrupción - Auditoría especial en marcha',
                'urgency' => 'HIGH',
            ],
            'published_at' => '2025-11-11 08:00:00',
        ]);

        // November 15 - INCIDENT: Pipeline Aging Infrastructure
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'INCIDENTE POTENCIAL: Evaluación crítica de infraestructura de gasoductos',
            'content' => "BOLETÍN TÉCNICO - OPERACIONES CRÍTICAS

Se ha identificado una vulnerabilidad crítica en la infraestructura de gasoductos nacional que requiere atención urgente.

RED DE GASODUCTOS BOLIVIA - ESTADO ACTUAL:

Gasoducto San Alberto-Arica (850 km):
- Construido: 1972 (53 años de operación)
- Capacidad original: 34.6 MMm³/día
- Capacidad actual: ~20 MMm³/día (degradación 42%)
- Estado: Requiere inspección completa
- Riesgo: Fuga catastrófica por corrosión interna

Gasoducto La Paz-Arica (950 km):
- Construido: 1980 (45 años de operación)
- Estado: Requiere reemplazo parcial
- Costo estimado: $1.2-1.5 billones

Red interna de distribución (2,000+ km):
- Edad promedio: 30+ años
- Fugas: ~8-12% gas perdido (pérdida económica significativa)
- Mantenimiento: Actualmente REACTIVO (solo cuando falla)

PLAN DE ACCIÓN:
1. Inspección completa (próximas 4 semanas) con tecnología de ultrasonido
2. Mapeo de riesgos de corrosión
3. Plan de reemplazo estratégico (2026-2028)
4. Presupuesto: ~$2.8 billones (solicitud a CAF/BID)

OPERACIONES:
- NO hay interrupción del servicio
- Monitoreo intensivo 24/7 en puntos críticos
- Equipo técnico en alerta máxima

Este es un desafío de largo plazo para la seguridad operativa de Bolivia.

Yussef Akly
Presidente YPFB
15 de noviembre de 2025",
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'is_resolved' => false,
                'started_at' => '2025-11-15T00:00:00Z',
                'affected_services' => ['Transporte de Gas Nacional', 'Infraestructura'],
                'technical_assessment' => 'critical',
            ],
            'published_at' => '2025-11-15 09:30:00',
        ]);

        // November 18 - NEWS: Exploration Plan
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'ESTRATEGIA EXPLORACIÓN: Plan de reversión de declinación de producción',
            'content' => "Anuncio de Dirección - Plan Energético Nacional

En respuesta a la crisis de producción y la pérdida de mercados de exportación, YPFB presenta su PLAN DE EXPLORACIÓN Y NUEVOS PROYECTOS.

REALIDAD ACTUAL:
- Producción gas: Cayó 40% en última década (21.766 Bm³ en 2012 → 13.122 Bm³ en 2023)
- Producción petróleo: Cayó de 18.6 millones bbl/año (2014) a 8.6 millones (2023)
- Mercados perdidos: Argentina finalizó contrato de gas (ganancia anual perdida: $3.961B)
- Necesidad: Nuevos campos exploratorios urgentemente

PLAN PRESENTADO:
Evaluación de 56 proyectos exploratorios en diferentes etapas:
- 6 megaproyectos de alta prioridad (impacto rápido)
- 15 proyectos complementarios (mediano plazo)
- 35 estudios de viabilidad (largo plazo)

PRESUPUESTO Y FINANCIAMIENTO:
- Costo estimado: $4.5-6 billones USD (5-8 años)
- Financiamiento: CAF, BID, alianzas público-privadas
- ROI esperado: Recuperar 15+ años de exportación

TIMING:
- Primer pozo exploratorio: Enero 2026 (estudio de viabilidad completado)
- Producción esperada: 2028-2030 (primeros campos productivos)

EMPLEO Y CRECIMIENTO:
- 500+ empleos directos (ingenieros, técnicos, trabajadores)
- 2,000+ empleos indirectos (logística, servicios)
- Reactivación económica en regiones productoras

Bajo la presidencia de Paz, revertiremos la crisis energética de Bolivia.

Yussef Akly
Presidente YPFB
18 de noviembre de 2025",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'strategic_initiative',
                'target_audience' => ['users', 'agents', 'admins'],
                'summary' => 'Plan de exploración - 56 proyectos para revertir declinación de producción',
                'urgency' => 'HIGH',
            ],
            'published_at' => '2025-11-18 10:00:00',
        ]);

        // November 22 - MAINTENANCE: Refinery Overhaul
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'MANTENIMIENTO URGENTE: Overhaul Refinería Guillermo Elder - Q1 2026',
            'content' => "Notificación Técnica - Planificación Operativa

La Refinería Guillermo Elder requerirá mantenimiento mayor durante Q1 2026 (45 días de parada).

SITUACIÓN ACTUAL:
- Equipo destilación: Construido 1992 (33 años de operación)
- Falla registrada: 22-11-2025 (válvula stuck - reparación temporal)
- Estado: Vida útil crítica - riesgo de falla catastrófica
- Capacidad: 30,000 bbl/día (CERO durante parada)

MANTENIMIENTO REQUERIDO:
✓ Overhaul completo destillation column: $2.8MM
✓ Reemplazo de sellos y componentes críticos
✓ Inspección 100% equipo principal
✓ Calibración sistemas de control

IMPACTO:
- Ingresos no percibidos: ~$900,000/día (45 días = $40.5MM)
- Costo reparación: $2.8MM
- ROI: 1.8 meses

CRONOGRAMA:
- Diciembre 2025: Licitación y contratación
- Enero 2026: Procura de partes
- Febrero-Abril 2026: Ejecución de overhaul

ALTERNATIVA EVALUADA:
- Arriendo unidad mobile: $1.8MM/año (menos eficiente, más caro a largo plazo)
- RECOMENDACIÓN: Overhaul definitivo

OPERACIONES PARALELAS:
- Refinería a capacidad reducida (si es necesario)
- Importación de derivados (si es necesario)
- Comunicación clara a distribuidoras

Inversión necesaria para garantizar seguridad operativa.

Yussef Akly
Presidente YPFB
22 de noviembre de 2025",
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2026-02-01T00:00:00Z',
                'scheduled_end' => '2026-04-15T23:59:59Z',
                'is_emergency' => false,
                'affected_services' => ['Producción de Derivados', 'Gasolina', 'Diésel'],
                'estimated_cost_usd' => 2800000,
            ],
            'published_at' => '2025-11-22 14:00:00',
        ]);

        // November 24 - NEWS: Financial Restructuring
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'REESTRUCTURACIÓN FINANCIERA: Plan de estabilidad fiscal y ajuste tarifario',
            'content' => "Anuncio Oficial - Decisión Administrativa

YPFB ha elaborado un plan integral de reestructuración financiera aprobado por la Presidencia de la República.

REALIDAD FISCAL ACTUAL:
- Ingresos 2024: $4.422 billones
- Gastos operativos: $4.924 billones
- Deficit operacional: $502 millones
- Deuda acumulada: ~$3.2 billones (Petroecuador, proveedores, bancos)

CAUSAS ESTRUCTURALES:
1. Caída de producción (40% menos en última década)
2. Pérdida de mercado Argentina (exportación terminada)
3. Costos operativos fijos (no reducibles sin afectar producción)
4. Necesidad de importación de combustibles (90% diésel, 50% gasolina)

PLAN DE ACCIÓN APROBADO:

Medida 1: Ajuste tarifario moderado (+12%)
- Implementación: Enero 2026
- Efecto: Aumentar ingresos ~$150-180MM anuales
- Impacto consumidor: ~Bs. 0.50/litro adicional
- Sector minería/industrial: MANTIENE subsidio (protección competitividad)

Medida 2: Refinanciamiento de deuda
- Solicitud línea puente CAF: $250MM (2-3 meses)
- Negociación Petroecuador: Refinanciamiento 18 meses
- Condición: Auditoría completa + plan anticorrupción (implementado)

Medida 3: Mejora operativa
- Reducción de fugas en distribución (8-12% → 3-5%)
- Optimización logística de importaciones
- Automatización de procesos (SCADA)

COMUNICACIÓN PÚBLICA:
'Ajuste tarifario necesario para mantener abastecimiento. Inversión en exploración asegura combustibles a largo plazo.'

TRANSPARENCIA TOTAL:
- Informes mensuales de ingresos/gastos (público)
- Auditoría externa (KPMG) cada trimestre
- Participación de sociedades civiles en oversight

Este es un plan REALISTA y SOSTENIBLE para YPFB.

Yussef Akly
Presidente YPFB
24 de noviembre de 2025",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'financial_policy',
                'target_audience' => ['users', 'agents', 'admins'],
                'summary' => 'Plan de estabilidad fiscal - Ajuste tarifario +12% y refinanciamiento de deuda',
                'urgency' => 'HIGH',
            ],
            'published_at' => '2025-11-24 11:00:00',
        ]);

        // ===== DRAFT ANNOUNCEMENTS (En preparación) =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Modernización Logística: Sistema SCADA y optimización distribución',
            'content' => "PROYECTO EN DESARROLLO

Sistema de control automatizado (SCADA) para:
1. Monitoreo remoto 24/7 de todo transporte de gas
2. Detección automática de anomalías
3. Control de presión y caudal en tiempo real
4. Integración con centros de distribución

Beneficios:
- Reducción de pérdidas por fugas: 12% → 3%
- Respuesta a emergencias: <5 minutos
- Eficiencia operativa: +25%

Costo: $3.2MM
Financiamiento: CAF/BID
Timeline: 12 meses implementación

Estado: En evaluación de presupuesto.",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'technology_project',
                'target_audience' => ['agents'],
                'summary' => 'Sistema SCADA moderno para logística de hidrocarburos',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Programa de Contratación: Ingenieros especializados en exploración',
            'content' => "CONVOCATORIA EN PREPARACIÓN

YPFB abrirá línea de contratación para:

1. Ingenieros Senior Exploración (Seismic Interpretation)
   - 2-3 posiciones
   - Salario: $4,500-6,000/mes
   - Experiencia: 10+ años

2. Ingenieros Reservoirs
   - 3-4 posiciones
   - Salario: $3,500-4,500/mes
   - Especialización: Análisis de presión, modelamiento

3. Ingenieros Producción/Perforación
   - 2-3 posiciones
   - Salario: $3,000-4,000/mes
   - Experiencia mínima: 5 años

Proceso:
- Convocatoria: Fin de noviembre
- Entrevistas: Diciembre
- Onboarding: Enero 2026

Total nuevas contrataciones: 8-10 ingenieros
Presupuesto: ~$380,000/año

Se aceptarán candidatos internacionales (visa trabajo facilitada).

Estado: Pendiente aprobación final.",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'recruitment',
                'target_audience' => ['agents'],
                'summary' => 'Programa de contratación de ingenieros especializados',
            ],
            'published_at' => null,
        ]);

        // ===== SCHEDULED ANNOUNCEMENTS (Próximos a publicarse) =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Negociaciones internacionales: Alianza energética Bolivia-Ecuador',
            'content' => "COMUNICADO PRÓXIMO (26-11-2025)

Yussef Akly viaja a Quito para:

1. Negociar refinanciamiento de deuda con Petroecuador
   - Deuda actual: $78.5MM
   - Propuesta: Refinanciamiento 18 meses
   - Condiciones: Pago inicial $20MM (enero 2026)

2. Establecer 'Alianza energética Bolivia-Ecuador'
   - Colaboración en exploración y producción
   - Intercambio de tecnología
   - Mercado regional común

3. Evaluar compra spot de combustibles
   - Negociación de precios
   - Condiciones de entrega
   - Alternativa si Petroecuador no acepta refinanciamiento

CONTEXTO:
- Petroecuador enfrenta crisis similar (producción en declive)
- Bolivia ofrece alianza estratégica (no transaccional)
- Mensaje a Paz: 'Diversificando proveedores'

CRONOGRAMA:
- Salida: 26 de noviembre
- Reunión: 27 de noviembre 06:00 AM
- Retorno: 28 de noviembre

Resultado esperado: Acuerdo firmado.",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'news_type' => 'international_relations',
                'target_audience' => ['agents'],
                'summary' => 'Negociaciones con Petroecuador - Alianza regional',
                'scheduled_for' => '2025-11-26T10:00:00Z',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Capacitación Anticorrupción: Programa obligatorio para todo personal',
            'content' => "ANUNCIO PROGRAMADO (01-12-2025)

En línea con la política de tolerancia cero del Presidente Paz, YPFB implementa:

PROGRAMA OBLIGATORIO DE ANTICORRUPCIÓN

Módulos:
1. Código de ética y conducta
2. Detección de fraude y malversación
3. Protocolos de denuncia
4. Consecuencias legales

Duración: 4 horas (puede hacerse online)
Plazo: Debe completarse antes del 15-01-2026
Asistencia: 100% obligatoria

Certificados: Emitidos por Dirección de Compliance
No cumplimiento: Puede resultar en desvinculación

Estado: Módulos en revisión final.",
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'compliance',
                'action_required' => true,
                'scheduled_for' => '2025-12-01T00:00:00Z',
                'deadline' => '2026-01-15T23:59:59Z',
            ],
            'published_at' => null,
        ]);

        // ===== ARCHIVED ANNOUNCEMENTS (Histórico de la crisis) =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Cambio administrativo: Transición de liderazgo en YPFB',
            'content' => "ARCHIVADO - Información histórica

El 07 de noviembre de 2025, el entonces Presidente Armin Ludwig Dorgathen Tapia fue separado del cargo por el Presidente Rodrigo Paz.

RAZONES OFICIALES:
- Negligencia en gestión de importación de combustibles
- Falta de respuesta a crisis de abastecimiento
- Advertencia previa de Paz no fue atendida

CONTEXTO:
La crisis de combustibles (stock 2-3 días) fue la gota que derramó el vaso. Paz amenazó con procesos judicales contra cualquier funcionario que 'sabotee' el abastecimiento.

INVESTIGACIÓN EN CURSO:
- Fiscalía anticorrupción abrió investigación formal
- Auditoría interna identificó irregularidades
- Alerta migratoria solicitada contra ex-presidente
- Estimado de pérdidas: $25-35 millones

Este cambio marca el inicio de una nueva era en YPFB bajo Yussef Akly y la presión transformadora de Paz.",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'news_type' => 'historical_record',
                'target_audience' => ['admins'],
                'summary' => 'Cambio de administración - Separación de Armin Dorgathen',
            ],
            'published_at' => '2025-11-07 18:00:00',
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Contexto: Crisis energética de Bolivia - Últimos 12 meses',
            'content' => "ARCHIVADO - Análisis situacional

Durante 2024 y principios de 2025, YPFB enfrentó una crisis múltiple:

CRISIS DE PRODUCCIÓN:
- Gas: Cayó 40% en última década (17.766 Bm³ → 13.122 Bm³)
- Petróleo: Cayó 54% en última década
- Campos maduros (San Alberto, Margarita) en declinación natural

CRISIS DE MERCADOS:
- Argentina: Contrato de gas terminado (junio 2024)
- Ingresos de exportación: Cayeron 66% respecto a 2014
- Pérdida anual: $3.961 billones en ingresos

CRISIS FISCAL:
- Deficit 2024: $502 millones
- Ingresos 2024: $4.422 billones (-10.6% vs 2023)
- Deuda acumulada: ~$3.2 billones

CRISIS DE ABASTECIMIENTO:
- Bolivia importa 90% del diésel
- Bolivia importa 50% de la gasolina
- Escasez nacional derivó en desabastecimiento (línea de crédito agotada)

Esta crisis derivó en el cambio de administración y la llegada de Paz con agenda transformadora.

Contexto: Noviembre 2025 representa un punto de inflexión.",
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'news_type' => 'situation_analysis',
                'target_audience' => ['admins'],
                'summary' => 'Análisis de crisis energética boliviana 2024-2025',
            ],
            'published_at' => '2025-11-08 14:00:00',
        ]);

        $this->command->info('✅ Anuncios YPFB creados exitosamente!');
        $this->command->info('📢 Anuncios publicados: 7');
        $this->command->info('📝 Borradores (en desarrollo): 2');
        $this->command->info('⏰ Programados (próximos): 2');
        $this->command->info('📋 Archivados (histórico): 2');
        $this->command->info('');
        $this->command->info('Total: 13 anuncios reflejando la situación REAL de YPFB');
    }
}
