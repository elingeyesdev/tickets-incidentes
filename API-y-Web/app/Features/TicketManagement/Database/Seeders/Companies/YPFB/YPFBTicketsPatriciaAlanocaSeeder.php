<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Database\Seeders\Companies\YPFB;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\AvatarHelper;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * YPFB Tickets Seeder - Patricia Alanoca
 *
 * Crea 5 tickets asignados a Patricia Alanoca (patricia.alanoca@ypfb.gob.bo)
 * Parte del conjunto de 12 seeders que crean 60 tickets totales para YPFB.
 *
 * Contexto: Crisis de hidrocarburos bolivianos 2024-2025
 * - Caída de producción de gas y petróleo
 * - Crisis de importación de combustibles
 * - Problemas logísticos y de distribución
 * - Presión por nuevos proyectos de exploración
 *
 * Distribución de estados según antigüedad:
 * - Tickets antiguos (Ene-Ago): 100% closed
 * - Tickets septiembre: 90% closed, 10% resolved
 * - Tickets octubre: 70% closed, 20% resolved, 10% pending
 * - Tickets noviembre: 40% closed, 30% resolved, 25% pending, 5% open
 * - Tickets diciembre: 20% closed, 30% resolved, 35% pending, 15% open
 */
class YPFBTicketsPatriciaAlanocaSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'patricia.alanoca@ypfb.gob.bo';
    private const AGENT_NAME = 'Patricia Alanoca';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    /**
     * Pool de usuarios (clientes internos/externos) con emails @gmail.com
     * Para empresa GRANDE: 16-25 usuarios en el pool total
     */
    private array $userPoolData = [
        ['first_name' => 'Roberto', 'last_name' => 'Garnica', 'email' => 'roberto.garnica.ypfb1@gmail.com'],
        ['first_name' => 'Sandra', 'last_name' => 'Quispe', 'email' => 'sandra.quispe.logistica1@gmail.com'],
        ['first_name' => 'Carlos', 'last_name' => 'Mendoza', 'email' => 'carlos.mendoza.distrib1@gmail.com'],
        ['first_name' => 'Amelia', 'last_name' => 'Torres', 'email' => 'amelia.torres.auditoria1@gmail.com'],
        ['first_name' => 'Javier', 'last_name' => 'Montecinos', 'email' => 'javier.montecinos.exp1@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para agente: " . self::AGENT_NAME . "...");

        // 1. Cargar empresa
        $this->loadCompany();
        if (!$this->company) {
            return;
        }

        // 2. Cargar agente
        $this->loadAgent();
        if (!$this->agent) {
            return;
        }

        // 3. Verificar idempotencia (verificar si ya hay tickets de este agente)
        if ($this->alreadySeeded()) {
            return;
        }

        // 4. Cargar áreas (YPFB tiene areas_enabled = true)
        $this->loadAreas();

        // 5. Cargar categorías
        $this->loadCategories();
        if (empty($this->categories)) {
            return;
        }

        // 6. Crear pool de usuarios
        $this->createUsers();

        // 7. Crear los 5 tickets para este agente
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para " . self::AGENT_NAME);
    }

    private function loadCompany(): void
    {
        $this->company = Company::where('name', 'YPFB Corporación')->first();

        if (!$this->company) {
            $this->command->error('❌ YPFB Corporación no encontrada. Ejecuta LargeBolivianCompaniesSeeder primero.');
        }
    }

    private function loadAgent(): void
    {
        $this->agent = User::where('email', self::AGENT_EMAIL)->first();

        if (!$this->agent) {
            $this->command->error('❌ Agente ' . self::AGENT_EMAIL . ' no encontrado.');
        }
    }

    private function alreadySeeded(): bool
    {
        $count = Ticket::where('company_id', $this->company->id)
            ->where('owner_agent_id', $this->agent->id)
            ->count();

        if ($count >= self::TICKETS_PER_AGENT) {
            $this->command->info("[OK] Tickets para " . self::AGENT_NAME . " ya existen ({$count}). Saltando.");
            return true;
        }
        return false;
    }

    private function loadAreas(): void
    {
        $areas = Area::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        $this->areas = [
            'exploracion' => $areas->firstWhere('name', 'Exploración y Evaluación'),
            'explotacion' => $areas->firstWhere('name', 'Explotación y Operaciones de Pozo'),
            'refinacion' => $areas->firstWhere('name', 'Refinación y Transformación'),
            'transporte' => $areas->firstWhere('name', 'Transporte y Logística de Hidrocarburos'),
            'comercializacion' => $areas->firstWhere('name', 'Comercialización y Ventas'),
            'seguridad' => $areas->firstWhere('name', 'Seguridad, Salud Ocupacional y Ambiente'),
            'ingenieria' => $areas->firstWhere('name', 'Ingeniería y Proyectos Estratégicos'),
            'administracion' => $areas->firstWhere('name', 'Administración, Finanzas y Recursos Humanos'),
        ];
    }

    private function loadCategories(): void
    {
        $categories = Category::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        // Categorías de energía según documentación
        $this->categories = [
            'interrupcion' => $categories->firstWhere('name', 'Incidente de Interrupción del Servicio')
                ?? $categories->first(),
            'equipo' => $categories->firstWhere('name', 'Problema de Equipo/Infraestructura')
                ?? $categories->first(),
            'facturacion' => $categories->firstWhere('name', 'Problema de Facturación')
                ?? $categories->first(),
            'seguridad' => $categories->firstWhere('name', 'Reporte de Seguridad')
                ?? $categories->first(),
            'consulta' => $categories->firstWhere('name', 'Consulta sobre Consumo/Tarifas')
                ?? $categories->first(),
        ];

        if (count(array_filter($this->categories)) < 1) {
            $this->command->error('❌ No hay categorías disponibles para YPFB.');
        }
    }

    private function createUsers(): void
    {
        foreach ($this->userPoolData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                'user_code' => CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code'),
                'email' => $userData['email'],
                'password_hash' => Hash::make(self::PASSWORD),
                'email_verified' => true,
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE,
                'auth_provider' => 'local',
                'terms_accepted' => true,
                'terms_accepted_at' => now()->subDays(rand(30, 300)),
                'terms_version' => 'v2.1',
                'onboarding_completed_at' => now()->subDays(rand(30, 300)),
                ]
            );

            $isFemale = str_ends_with(strtolower($userData['first_name']), 'a');

            \App\Features\UserManagement\Models\UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'avatar_url' => AvatarHelper::getRandom($isFemale ? 'female' : 'male'),
                    'phone_number' => '+591' . rand(70000000, 79999999),
                    'theme' => 'light',
                    'language' => 'es',
                    'timezone' => 'America/La_Paz',
                ]
            );

            UserRole::firstOrCreate(
                ['user_id' => $user->id, 'role_code' => 'USER', 'company_id' => $this->company->id],
                ['is_active' => true]
            );

            $this->users[$userData['first_name']] = $user;
        }
    }

    private function createTickets(): void
    {
        // TICKET 1: CLOSED - Febrero (importación crítica de combustibles)
        $this->createTicket1_ImportacionCombustibles();

        // TICKET 2: CLOSED - Abril (problema logístico de distribución)
        $this->createTicket2_ProblemaLogistico();

        // TICKET 3: CLOSED - Julio (falla en refinería)
        $this->createTicket3_FallaRefineria();

        // TICKET 4: RESOLVED - Octubre (consulta sobre tarifas industriales)
        $this->createTicket4_ConsultaTarifas();

        // TICKET 5: PENDING - Noviembre (reporte de fuga en gasoducto)
        $this->createTicket5_ReporteFuga();
    }

    // ==================== TICKET 1: CLOSED - CRISIS IMPORTACIÓN COMBUSTIBLES ====================
    private function createTicket1_ImportacionCombustibles(): void
    {
        $user = $this->users['Sandra'];
        $createdAt = Carbon::create(2025, 2, 18, 8, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['interrupcion']->id ?? null,
            'area_id' => $this->areas['transporte']->id ?? null,
            'title' => 'URGENTE: Retraso en buque con diésel importado - Stock crítico 5 días',
            'description' => "Patricia,

Tenemos una situación crítica con la importación de combustibles desde Houston.

**SITUACIÓN ACTUAL:**
- Buque MT \"Pacific Explorer\" retrasado 6 días por tormenta tropical
- Carga: 48,000 toneladas de diésel
- Puerto destino: Arica, Chile
- Stock nacional actual: 5 días de consumo normal

**IMPACTO PROYECTADO:**
- Si no llega en 72 horas, entraremos en racionamiento
- Estaciones de servicio en La Paz y Oruro reportan stock bajo
- Sector minero (Potosí, Oruro) ya reporta preocupación

**ACCIONES EN CURSO:**
1. Negociación con Petroperú para importación de emergencia por Ilo
2. Activación de reservas estratégicas en Santa Cruz
3. Coordinación con Ministerio para plan de contingencia

**COSTOS ADICIONALES ESTIMADOS:**
- Importación alternativa Argentina: +15% sobre precio normal
- Transporte terrestre de emergencia: +$2.5MM

Necesito autorización para activar Plan B (Argentina) si buque no confirma llegada antes de mañana 18:00.

Sandra Quispe
Jefa Logística e Importaciones",
            'status' => 'closed',
            'priority' => 'high',
            'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(4),
            'first_response_at' => $createdAt->copy()->addHours(1),
            'resolved_at' => $createdAt->copy()->addDays(3),
            'closed_at' => $createdAt->copy()->addDays(4),
        ]);

        // Respuesta 1: Agente
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->agent->id,
            'author_type' => 'agent',
            'content' => "Sandra,

Acabo de hablar con la presidencia. Tenemos luz verde para Plan B.

**DECISIONES TOMADAS:**
1. ✅ Activar importación de emergencia desde Argentina (YPF Argentina)
2. ✅ Autorizado costo adicional hasta $3MM
3. ✅ Coordinar con Aduanas para fast-track en frontera Villazón

**ACCIONES PARALELAS:**
- Comunicado a estaciones de servicio: NO hay crisis, solo precaución
- Prensa: Preparar comunicado preventivo si es necesario
- Minería: Contactar directamente a las 5 mayores para tranquilizar

Por favor confirma cuando YPF Argentina acepte el pedido.

Estamos en línea directa con el Ministerio.",
            'created_at' => $createdAt->copy()->addHours(1),
        ]);

        // Respuesta 2: Usuario
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Patricia,

Confirmado. YPF Argentina acepta envío de emergencia:
- 25,000 toneladas de diésel
- Transporte por camiones cisterna (85 unidades)
- Llegada estimada: 48-72 horas
- Costo: USD 78.50/barril (vs $72 normal) = +USD 1.8MM

El buque original confirmó llegada para el día 22 (4 días).

Con ambas fuentes, tendremos stock suficiente para 12 días.

Adjunto: Contrato de emergencia con YPF Argentina (PDF)",
            'created_at' => $createdAt->copy()->addHours(6),
        ]);

        // Respuesta 3: Agente (cierre)
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->agent->id,
            'author_type' => 'agent',
            'content' => "Excelente trabajo, Sandra.

**RESUMEN DE LA CRISIS:**
- Duración: 4 días
- Costo adicional total: USD 1.8MM
- Impacto en abastecimiento: NINGUNO (gracias a acción rápida)
- Lecciones: Necesitamos aumentar stock de seguridad de 5 a 10 días

**PRÓXIMOS PASOS:**
1. Informe post-mortem para Directorio
2. Revisar contratos con navieras (penalidades por retraso)
3. Propuesta de aumento de reservas estratégicas

Cierro este ticket como RESUELTO. Buen trabajo del equipo.

Patricia Alanoca",
            'created_at' => $createdAt->copy()->addDays(3),
        ]);

        // Attachment
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'contrato_emergencia_ypf_argentina.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/contrato_emergencia.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => rand(150000, 500000),
            'created_at' => $createdAt->copy()->addHours(6),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code} (Feb)");
    }

    // ==================== TICKET 2: CLOSED - PROBLEMA LOGÍSTICO DISTRIBUCIÓN ====================
    private function createTicket2_ProblemaLogistico(): void
    {
        $user = $this->users['Carlos'];
        $createdAt = Carbon::create(2025, 4, 12, 14, 15, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['transporte']->id ?? null,
            'title' => 'Falla de bomba de descarga Terminal La Paz - 23 cisternas en espera',
            'description' => "Patricia,

Tenemos una situación grave en Terminal de Combustibles La Paz.

**PROBLEMA:**
- Bomba de descarga principal (BDP-LP-01) falló a las 12:30
- Bomba de respaldo (BDP-LP-02) operando al 60% capacidad
- Acumulación: 23 camiones cisterna esperando descarga
- Tiempo de espera promedio: 8 horas (normal: 2 horas)

**CAUSA TÉCNICA:**
- Eje de bomba fracturado (fatiga de material, equipo de 2008)
- Repuesto NO disponible en país
- Lead time importación: 15-20 días

**IMPACTO EN DISTRIBUCIÓN:**
- Retrasos en entregas a estaciones de servicio zona norte LP
- Transportistas molestos (pérdidas por horas de espera)
- Posible multa contractual por demoras

**OPCIONES:**
A) Arrendar bomba temporal (costo: $3,500/día)
B) Desviar descargas a Terminal Oruro (+150km, +12 horas)
C) Operar 24/7 con bomba de respaldo (riesgo de sobrecarga)

Recomiendo opción A mientras llega repuesto.

Carlos Mendoza
Jefe Distribución de Combustibles",
            'status' => 'closed',
            'priority' => 'high',
            'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(18),
            'first_response_at' => $createdAt->copy()->addHours(2),
            'resolved_at' => $createdAt->copy()->addDays(16),
            'closed_at' => $createdAt->copy()->addDays(18),
        ]);

        // Respuesta 1: Agente
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->agent->id,
            'author_type' => 'agent',
            'content' => "Carlos,

Apruebo opción A (arriendo bomba temporal) con las siguientes condiciones:

1. Contrato máximo 20 días (mientras llega repuesto)
2. Proveedor certificado (envíame 2 cotizaciones)
3. Instalación supervisada por Ingeniería

**PARALELO:**
- Autorizo importación de repuesto por vía aérea (courier express)
- Costo adicional aceptable para reducir lead time a 7 días

**COMUNICACIÓN:**
- Notifica a transportistas sobre situación y plan de solución
- Ofrece descuento del 5% en próximo servicio como compensación

Mantenme informado del progreso cada 12 horas.",
            'created_at' => $createdAt->copy()->addHours(2),
        ]);

        // Respuesta 2: Usuario
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Patricia,

Actualización día 7:

1. Bomba temporal instalada y operando (100% capacidad)
2. Cola de cisternas eliminada (operación normal)
3. Repuesto original llega mañana por DHL Express
4. Instalación programada para fin de semana

Transportistas satisfechos con la gestión. Solo 2 presentaron reclamo formal (atendidos).

Total costo de la contingencia: USD 28,500
- Arriendo bomba: $24,500 (7 días)
- Courier express: $4,000

Aprendizaje: Necesitamos stock de repuestos críticos en país.",
            'created_at' => $createdAt->copy()->addDays(7),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code} (Abr)");
    }

    // ==================== TICKET 3: CLOSED - FALLA EN REFINERÍA ====================
    private function createTicket3_FallaRefineria(): void
    {
        $user = $this->users['Roberto'];
        $createdAt = Carbon::create(2025, 7, 8, 3, 45, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['refinacion']->id ?? null,
            'title' => 'ALERTA: Parada de emergencia Unidad FCC Refinería Guillermo Elder',
            'description' => "Patricia,

Reporte de emergencia desde Refinería Guillermo Elder Bell.

**INCIDENTE:**
- Hora: 03:15 AM (hoy)
- Unidad: FCC (Craqueo Catalítico Fluido)
- Evento: Parada de emergencia automática (ESD activado)
- Causa preliminar: Alta temperatura en regenerador (680°C vs límite 650°C)

**ESTADO ACTUAL:**
- Unidad FCC: FUERA DE LÍNEA
- Producción afectada: Gasolina de alto octano, queroseno
- Otras unidades: Operando normalmente (destilación, reformado)
- Sin lesiones de personal
- Sin daños ambientales

**IMPACTO EN PRODUCCIÓN:**
- Gasolina: -12,000 barriles/día
- Queroseno: -3,000 barriles/día
- Duración estimada de parada: 48-72 horas (evaluación en curso)

**INVESTIGACIÓN EN CURSO:**
- Equipo de inspección evaluando regenerador
- Probable causa: Falla en sistema de control de temperatura
- Informe preliminar: 12 horas

Mantengo línea abierta. Próxima actualización: 08:00 AM.

Roberto Garnica
Jefe Operaciones Refinería",
            'status' => 'closed',
            'priority' => 'high',
            'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addMinutes(30),
            'resolved_at' => $createdAt->copy()->addDays(3),
            'closed_at' => $createdAt->copy()->addDays(5),
        ]);

        // Respuesta 1: Agente
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->agent->id,
            'author_type' => 'agent',
            'content' => "Roberto,

Gracias por el reporte inmediato. Esto es prioridad 1.

**ACCIONES AUTORIZADAS:**
1. Contratar inspección externa si es necesario (especialistas FCC)
2. Autorizo horas extras ilimitadas para equipo de mantenimiento
3. Coordino con Comercialización para importar gasolina faltante

**COMUNICACIÓN:**
- Informé a Presidencia (copia a Ministro)
- Preparando comunicado de prensa preventivo
- Estaciones de servicio: Sin cambios por ahora (stock suficiente)

**SEGURIDAD:**
- Confirma que protocolo de evacuación está activo
- Bomberos en standby según protocolo

Espero actualización a las 08:00 AM.",
            'created_at' => $createdAt->copy()->addMinutes(30),
        ]);

        // Respuesta 2: Usuario (actualización)
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Patricia,

**ACTUALIZACIÓN 08:00 AM:**

Causa raíz identificada: Sensor de temperatura defectuoso (lectura falsa alta).
- Temperatura real: 648°C (dentro de límite)
- Sensor calibrado incorrectamente durante último mantenimiento

**PLAN DE ACCIÓN:**
1. Reemplazo de sensor (stock disponible)
2. Pruebas de integridad de sistema de control
3. Reinicio gradual de unidad FCC

**TIMELINE:**
- Hoy 14:00: Sensor reemplazado
- Hoy 18:00: Pruebas de control
- Mañana 06:00: Reinicio de unidad (modo reducido)
- Pasado mañana: Operación normal

**BUENAS NOTICIAS:**
- No hay daño en equipos
- Parada fue preventiva correcta (sistema funcionó bien)
- Producción perdida: ~30,000 barriles (recuperable)

Adjunto: Informe técnico preliminar.",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        // Attachment
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'informe_tecnico_fcc_julio2025.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/informe_tecnico_fcc.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => rand(200000, 600000),
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code} (Jul)");
    }

    // ==================== TICKET 4: RESOLVED - CONSULTA TARIFAS INDUSTRIALES ====================
    private function createTicket4_ConsultaTarifas(): void
    {
        $user = $this->users['Javier'];
        $createdAt = Carbon::create(2025, 10, 15, 10, 20, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null,
            'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Consulta: Revisión de tarifas de gas natural para nuevos proyectos industriales',
            'description' => "Patricia,

Tenemos 3 empresas industriales consultando sobre tarifas de gas para nuevos proyectos en Santa Cruz.

**EMPRESAS INTERESADAS:**
1. Frigorífico Santa Rita (ampliación planta)
   - Consumo proyectado: 15,000 m³/día
   - Inicio operaciones: Q1 2026

2. Acería del Oriente (nueva planta)
   - Consumo proyectado: 45,000 m³/día
   - Inicio operaciones: Q3 2026

3. Cementos Warnes (switch de diésel a gas)
   - Consumo proyectado: 28,000 m³/día
   - Inicio operaciones: Q2 2026

**CONSULTAS ESPECÍFICAS:**
- ¿Estructura tarifaria para consumo >10,000 m³/día?
- ¿Descuentos por contratos de largo plazo (5-10 años)?
- ¿Disponibilidad de conexión en zonas industriales?
- ¿Garantía de suministro ante crisis de producción?

Estas empresas representan ~88,000 m³/día adicionales. Potencial de ingresos significativo.

¿Podemos agendar una reunión con Comercialización para definir propuesta?

Javier Montecinos
Jefe Área Exploración y Producción",
            'status' => 'resolved',
            'priority' => 'medium',
            'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(8),
            'first_response_at' => $createdAt->copy()->addHours(3),
            'resolved_at' => $createdAt->copy()->addDays(8),
            'closed_at' => null,
        ]);

        // Respuesta 1: Agente
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->agent->id,
            'author_type' => 'agent',
            'content' => "Javier,

Excelente oportunidad comercial. Coordino con Comercialización.

**INFORMACIÓN PRELIMINAR:**

**Tarifas vigentes (consumo >10,000 m³/día):**
- Tarifa base: USD 2.85/MMBTU
- Cargo por capacidad: USD 0.15/MMBTU
- Total promedio: USD 3.00/MMBTU

**Descuentos por contrato largo plazo:**
- 5 años: 5% descuento
- 10 años: 12% descuento
- 15 años: 18% descuento

**SOBRE GARANTÍA DE SUMINISTRO:**
Este es tema sensible dado el contexto actual de producción. Necesitamos analizar caso por caso.

**REUNIÓN PROGRAMADA:**
- Fecha: 22 de Octubre, 10:00 AM
- Lugar: Oficina Central YPFB, Sala 3B
- Asistentes: Comercialización, Legal, tú y yo

¿Puedes confirmar disponibilidad y traer información detallada de cada proyecto?",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        // Respuesta 2: Usuario
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Patricia,

Confirmado para el 22 de octubre.

Prepararé:
- Fichas técnicas de cada proyecto
- Proyección de consumo mensual
- Información financiera de las empresas
- Cronogramas de construcción

Una consulta adicional: ¿Hay posibilidad de tarifa preferencial si las 3 empresas firman contrato conjunto (pool de compra)?

Eso podría ser atractivo para ellos y nos asegura volumen.",
            'created_at' => $createdAt->copy()->addDays(1),
        ]);

        // Respuesta 3: Agente (resolución)
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->agent->id,
            'author_type' => 'agent',
            'content' => "Javier,

La reunión del 22 fue exitosa. Resumen:

**ACUERDOS ALCANZADOS:**

1. **Frigorífico Santa Rita:** Contrato 7 años, tarifa USD 2.80/MMBTU
2. **Acería del Oriente:** Contrato 10 años, tarifa USD 2.65/MMBTU (mayor volumen)
3. **Cementos Warnes:** Contrato 5 años, tarifa USD 2.90/MMBTU

**POOL DE COMPRA:**
- Aprobado descuento adicional del 3% si firman contrato conjunto
- Las 3 empresas aceptaron propuesta

**PRÓXIMOS PASOS:**
- Legal prepara contratos (15 días)
- Firma estimada: 15 de Noviembre
- Ingeniería evalúa conexiones (ya iniciado)

**VALOR TOTAL DEL NEGOCIO:**
- Ingresos proyectados: USD 8.5MM/año
- Duración promedio contratos: 7.3 años
- Valor total: ~USD 62MM

Excelente trabajo consiguiendo estos clientes. Ticket resuelto.",
            'created_at' => $createdAt->copy()->addDays(8),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code} (Oct)");
    }

    // ==================== TICKET 5: PENDING - REPORTE DE FUGA EN GASODUCTO ====================
    private function createTicket5_ReporteFuga(): void
    {
        $user = $this->users['Amelia'];
        $createdAt = Carbon::create(2025, 11, 25, 7, 15, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['seguridad']->id ?? null,
            'area_id' => $this->areas['seguridad']->id ?? null,
            'title' => 'Reporte de posible fuga - Gasoducto Carrasco Km 145 - Evaluación urgente',
            'description' => "Patricia,

Recibimos reporte de posible fuga en el Gasoducto Carrasco (tramo Cochabamba-Santa Cruz).

**DETALLES DEL REPORTE:**
- Ubicación: Km 145 (cerca de comunidad Yapacaní)
- Reportado por: Comunarios locales
- Hora del reporte: 06:45 AM (hoy)
- Descripción: \"Olor a gas en área cercana al gasoducto\"

**ACCIONES INMEDIATAS TOMADAS:**
1. Brigada de inspección desplegada (ETA: 2 horas)
2. Centro de Control monitoreando presión del tramo
3. Coordinación con Bomberos locales (preventivo)
4. Comunidad notificada para mantener distancia

**DATOS TÉCNICOS DEL TRAMO:**
- Gasoducto: 18\" diámetro
- Presión operativa: 450 PSI
- Última inspección: Marzo 2025 (sin anomalías)
- Antigüedad: 28 años

**MONITOREO EN TIEMPO REAL:**
- Presión actual: Normal (sin caída significativa)
- Flujo: Normal
- No hay alarmas en SCADA

**POSIBLES CAUSAS:**
- Fuga real (corrosión, daño externo)
- Actividad agrícola cercana (quema)
- Falso positivo (gases naturales del terreno)

Mantengo comunicación activa con la brigada. Próximo reporte: 11:00 AM.

Amelia Torres
Directora de Auditoría Interna
(Coordinando con Seguridad y Ambiente)",
            'status' => 'pending',
            'priority' => 'high',
            'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addHours(4),
            'first_response_at' => $createdAt->copy()->addMinutes(20),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Respuesta 1: Agente
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->agent->id,
            'author_type' => 'agent',
            'content' => "Amelia,

Recibido. Esto es prioridad máxima.

**DECISIONES:**
1. Si brigada confirma fuga: REDUCIR PRESIÓN inmediatamente
2. Comunicado a comunidad a través de autoridades locales
3. Standby para cierre parcial de válvulas si es necesario

**ESCALAMIENTO:**
- He informado a Presidencia YPFB
- Ministerio de Hidrocarburos en copia
- ABT (Autoridad Ambiental) notificada preventivamente

**RECURSOS ADICIONALES:**
- Autorizo helicóptero para inspección aérea si es necesario
- Equipo de soldadura especializado en standby (Cochabamba)

Por favor confirma cuando brigada llegue al sitio.

Mantengamos comunicación cada 30 minutos.",
            'created_at' => $createdAt->copy()->addMinutes(20),
        ]);

        // Respuesta 2: Usuario (actualización)
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Patricia,

**ACTUALIZACIÓN 11:15 AM - Brigada en sitio**

**HALLAZGOS:**
- Fuga CONFIRMADA pero MENOR
- Ubicación exacta: Km 145.3, válvula de purga VP-145
- Tipo: Fuga en junta (no en tubería principal)
- Volumen estimado: ~500 m³/día (mínimo)
- Riesgo: BAJO (área despejada, sin ignición)

**CAUSA IDENTIFICADA:**
- Junta de brida deteriorada (corrosión externa)
- Daño por humedad y contacto con suelo

**PLAN DE REPARACIÓN:**
1. Reducción de presión localizada (ya ejecutado)
2. Reemplazo de junta (4-6 horas de trabajo)
3. Prueba de hermeticidad
4. Restauración de presión normal

**IMPACTO:**
- NO es necesario cierre del gasoducto
- Abastecimiento a Santa Cruz: SIN AFECTACIÓN
- Comunidad: Informada, tranquila

**EQUIPO EN SITIO:**
- 6 técnicos de mantenimiento
- 2 ingenieros de seguridad
- 1 soldador certificado
- Equipo de bomberos (observando)

Estimo resolución completa hoy antes de las 18:00.

Siguiente actualización: 14:00.",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        // Attachment: Imagen del sitio
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'foto_valvula_fuga_km145.jpg',
            'file_path' => 'tickets/' . $ticket->id . '/foto_valvula_fuga.jpg',
            'file_type' => 'image/jpeg',
            'file_size_bytes' => rand(300000, 800000),
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code} (Nov)");
    }
}
