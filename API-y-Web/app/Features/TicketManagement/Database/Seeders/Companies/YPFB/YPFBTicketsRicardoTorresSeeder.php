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
 * YPFB Tickets Seeder - Ricardo Torres
 * Temas: Transporte de hidrocarburos, ductos, logística
 */
class YPFBTicketsRicardoTorresSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'ricardo.torres@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Mario', 'last_name' => 'Fernández', 'email' => 'mario.fernandez.ductos4@gmail.com'],
        ['first_name' => 'Carmen', 'last_name' => 'Rojas', 'email' => 'carmen.rojas.transporte4@gmail.com'],
        ['first_name' => 'Oscar', 'last_name' => 'Villca', 'email' => 'oscar.villca.logistica4@gmail.com'],
        ['first_name' => 'Natalia', 'last_name' => 'Guzmán', 'email' => 'natalia.guzman.operaciones4@gmail.com'],
        ['first_name' => 'Diego', 'last_name' => 'Parra', 'email' => 'diego.parra.mantenimiento4@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Ricardo Torres...");

        $this->loadCompany();
        if (!$this->company) return;

        $this->loadAgent();
        if (!$this->agent) return;

        if ($this->alreadySeeded()) return;

        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Ricardo Torres");
    }

    private function loadCompany(): void
    {
        $this->company = Company::where('name', 'YPFB Corporación')->first();
        if (!$this->company) $this->command->error('❌ YPFB no encontrada.');
    }

    private function loadAgent(): void
    {
        $this->agent = User::where('email', self::AGENT_EMAIL)->first();
        if (!$this->agent) $this->command->error('❌ Agente no encontrado.');
    }

    private function alreadySeeded(): bool
    {
        $count = Ticket::where('company_id', $this->company->id)->where('owner_agent_id', $this->agent->id)->count();
        if ($count >= self::TICKETS_PER_AGENT) { $this->command->info("[OK] Tickets ya existen. Saltando."); return true; }
        return false;
    }

    private function loadAreas(): void
    {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = [
            'transporte' => $areas->firstWhere('name', 'Transporte y Logística de Hidrocarburos'),
            'explotacion' => $areas->firstWhere('name', 'Explotación y Operaciones de Pozo'),
        ];
    }

    private function loadCategories(): void
    {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'equipo' => $cats->firstWhere('name', 'Problema de Equipo/Infraestructura') ?? $cats->first(),
            'interrupcion' => $cats->firstWhere('name', 'Incidente de Interrupción del Servicio') ?? $cats->first(),
        ];
    }

    private function createUsers(): void
    {
        foreach ($this->userPoolData as $ud) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                'user_code' => CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code'), 'email' => $ud['email'],
                'password_hash' => Hash::make(self::PASSWORD), 'email_verified' => true,
                'email_verified_at' => now(), 'status' => UserStatus::ACTIVE, 'auth_provider' => 'local',
                'terms_accepted' => true, 'terms_accepted_at' => now()->subDays(rand(30, 300)),
                'terms_version' => 'v2.1', 'onboarding_completed_at' => now()->subDays(rand(30, 300)),
                ]
            );

            $isFemale = str_ends_with(strtolower($ud['first_name']), 'a');
            ['user_id' => $user->id],
                [
                'first_name' => $ud['first_name'], 'last_name' => $ud['last_name'],
                'avatar_url' => AvatarHelper::getRandom($isFemale ? 'female' : 'male'),
                'phone_number' => '+591' . rand(70000000, 79999999),
                'theme' => 'light', 'language' => 'es', 'timezone' => 'America/La_Paz',
            ]);

            UserRole::firstOrCreate(
                ['user_id' => $user->id, 'role_code' => 'USER', 'company_id' => $this->company->id],
                ['is_active' => true]
            );
            $this->users[$ud['first_name']] = $user;
        }
    }

    private function createTickets(): void
    {
        $this->createTicket1_FugaGasoducto();
        $this->createTicket2_BloqueoCarretera();
        $this->createTicket3_MantenimientoBomba();
        $this->createTicket4_CapacidadTransporte();
        $this->createTicket5_InspeccionDucto();
    }

    // ==================== TICKET 1: CLOSED - FUGA EN GASODUCTO ====================
    private function createTicket1_FugaGasoducto(): void
    {
        $user = $this->users['Mario'];
        $createdAt = Carbon::create(2025, 2, 10, 6, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['transporte']->id ?? null,
            'title' => 'URGENTE: Fuga detectada Gasoducto Yacuiba-Río Grande Km 234',
            'description' => "Ricardo,

Centro de Control detectó caída de presión anómala en tramo Km 230-240 del gasoducto YRGB.

**DATOS TÉCNICOS:**
- Hora detección: 05:45 AM
- Presión normal: 85 bar
- Presión actual: 78 bar (caída de 7 bar en 45 min)
- Flujo: Reducción del 12%
- Ubicación estimada: Km 234 (sector rural, difícil acceso)

**ACCIONES TOMADAS:**
1. Brigada de emergencia despachada (ETA 2 horas)
2. Reducción de flujo preventiva
3. Comunidades cercanas notificadas (3 km radio)

Necesito autorización para reducir presión adicional si brigada confirma fuga mayor.

Mario Fernández
Supervisor NOC",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'user',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(4),
            'first_response_at' => $createdAt->copy()->addMinutes(15),
            'resolved_at' => $createdAt->copy()->addDays(2),
            'closed_at' => $createdAt->copy()->addDays(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Mario,

Autorizo reducción de presión a 70 bar si brigada lo requiere. Prioridad es seguridad.

Acciones paralelas:
1. Notifiqué a Gerencia y Ministerio
2. Helicóptero en standby para inspección aérea
3. Equipo de soldadura especializado alertado

Reporta cada 30 minutos. ¿Brigada ya salió?",
            'created_at' => $createdAt->copy()->addMinutes(15),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Brigada llegó al sitio. Fuga CONFIRMADA pero menor de lo esperado. Es en válvula de purga, no en tubería principal. Reparable en 6-8 horas.",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Entendido. Mantener presión reducida hasta reparación completa. ¿Necesitan equipo adicional?",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "No, tienen todo. Reparación terminada a las 16:30. Presión normalizada. Sin incidentes.",
            'created_at' => $createdAt->copy()->addHours(11),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Excelente respuesta. Envíame informe de cierre para archivo. Cierro ticket.",
            'created_at' => $createdAt->copy()->addDays(1),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/pipeline,industrial
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'reporte_fuga_km234.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/reporte_fuga.pdf',
            'file_type' => 'application/pdf', 'file_size_bytes' => rand(200000, 400000),
            'created_at' => $createdAt->copy()->addDays(2),
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Feb)");
    }

    // ==================== TICKET 2: CLOSED - BLOQUEO CARRETERA ====================
    private function createTicket2_BloqueoCarretera(): void
    {
        $user = $this->users['Carmen'];
        $createdAt = Carbon::create(2025, 4, 18, 8, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['interrupcion']->id ?? null,
            'area_id' => $this->areas['transporte']->id ?? null,
            'title' => 'Bloqueo carretera Oruro-La Paz: 18 cisternas varadas con combustible',
            'description' => "Ricardo,

Bloqueo de campesinos en Km 85 carretera Oruro-La Paz. Nuestras cisternas no pueden pasar.

**SITUACIÓN:**
- 18 cisternas YPFB varadas (lado Oruro)
- Carga: 12 con diésel, 6 con gasolina
- Valor aproximado: USD 1.2MM
- Tiempo varados: 4 horas

**RIESGO:**
- Estaciones de servicio La Paz reportan stock bajo
- Si no llegan hoy, mañana habrá desabastecimiento

¿Podemos coordinar ruta alternativa por Tambo Quemado?

Carmen Rojas
Coordinadora Transporte Terrestre",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(3),
            'first_response_at' => $createdAt->copy()->addHours(1),
            'resolved_at' => $createdAt->copy()->addDays(1),
            'closed_at' => $createdAt->copy()->addDays(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Carmen,

Ruta Tambo Quemado agrega 4 horas. Evaluemos primero cuánto dura el bloqueo.

Acciones:
1. Contacté a la Gobernación de Oruro - negociando con bloqueadores
2. Si no se resuelve en 2 horas, activamos ruta alternativa
3. Notifica a conductores que descansen y mantengan vehículos seguros

¿Los conductores están bien?",
            'created_at' => $createdAt->copy()->addHours(1),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Conductores bien, tienen agua y comida. ¿Hay novedades de la negociación?",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Gobernación logró corredor humanitario. Cisternas pueden pasar en 1 hora. Confirma cuando empiecen a moverse.",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Confirmado, cisternas pasando ahora. Llegarán a La Paz antes de las 8 PM.",
            'created_at' => $createdAt->copy()->addHours(5),
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Abr)");
    }

    // ==================== TICKET 3: CLOSED - MANTENIMIENTO BOMBA ====================
    private function createTicket3_MantenimientoBomba(): void
    {
        $user = $this->users['Diego'];
        $createdAt = Carbon::create(2025, 6, 22, 10, 15, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['transporte']->id ?? null,
            'title' => 'Falla bomba principal Estación de Bombeo EB-04 Camiri',
            'description' => "Ricardo,

Bomba principal de EB-04 Camiri falló esta mañana.

**DETALLES:**
- Bomba: BP-04-A (1,200 HP)
- Falla: Sobrecalentamiento motor, desconexión automática
- Bomba respaldo: BP-04-B operando al 100%
- Capacidad actual: 60% del normal

**IMPACTO:**
- Flujo reducido hacia Santa Cruz
- Puede afectar abastecimiento en 48-72 horas si no se repara

**DIAGNÓSTICO PRELIMINAR:**
- Rodamientos dañados
- Probable causa: Falta lubricación (error humano en último mantenimiento)

¿Autoriza traer técnico especializado desde La Paz?

Diego Parra
Jefe Mantenimiento Estaciones",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'user',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(6),
            'first_response_at' => $createdAt->copy()->addHours(2),
            'resolved_at' => $createdAt->copy()->addDays(4),
            'closed_at' => $createdAt->copy()->addDays(6),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Diego,

Autorizado. Envía técnico hoy. Mientras tanto:
1. Mantén BP-04-B operando con monitoreo continuo
2. Investiga el error de lubricación - necesito nombres
3. Solicita repuestos (rodamientos) de emergencia

¿Cuánto demora la reparación una vez tenga los repuestos?",
            'created_at' => $createdAt->copy()->addHours(2),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Técnico llega mañana 6 AM. Reparación estimada 2 días. Repuestos en camino desde SCZ.",
            'created_at' => $createdAt->copy()->addHours(5),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Actualización: Reparación completada. Bomba operando normal. Sobre el error de lubricación, fue el técnico Ríos. Ya hablé con él.",
            'created_at' => $createdAt->copy()->addDays(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Bien. Documenta el incidente y programa capacitación de refuerzo para todo el equipo de mantenimiento. Cierro ticket.",
            'created_at' => $createdAt->copy()->addDays(4),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/pump,industrial
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'foto_bomba_danada.jpg',
            'file_path' => 'tickets/' . $ticket->id . '/bomba_danada.jpg',
            'file_type' => 'image/jpeg', 'file_size_bytes' => rand(300000, 600000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jun)");
    }

    // ==================== TICKET 4: RESOLVED - CAPACIDAD TRANSPORTE ====================
    private function createTicket4_CapacidadTransporte(): void
    {
        $user = $this->users['Oscar'];
        $createdAt = Carbon::create(2025, 10, 8, 14, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['transporte']->id ?? null,
            'title' => 'Solicitud aumento capacidad transporte ducto Cochabamba para Q1 2026',
            'description' => "Ricardo,

Necesitamos planificar aumento de capacidad para el ducto Cochabamba-Oruro.

**PROYECCIÓN:**
- Demanda actual: 180,000 m³/día
- Capacidad ducto: 200,000 m³/día (90% utilización)
- Proyección Q1 2026: 220,000 m³/día (+22%)

**DRIVERS DE DEMANDA:**
1. Nueva planta de cemento en Oruro
2. Expansión termoeléctrica Guaracachi

**OPCIONES:**
A) Estación de compresión adicional (USD 2.5MM, 6 meses)
B) Ducto paralelo 10km (USD 8MM, 12 meses)
C) Optimización operativa (max +10%)

Recomiendo opción A. ¿Puedo preparar propuesta para Directorio?

Oscar Villca
Planificación de Capacidad",
            'status' => 'resolved', 'priority' => 'medium',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(8),
            'first_response_at' => $createdAt->copy()->addHours(5),
            'resolved_at' => $createdAt->copy()->addDays(8),
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Oscar,

Buen análisis. Coincido con opción A. Prepara propuesta incluyendo:
1. Cronograma detallado
2. Análisis de riesgo
3. ROI y payback
4. Alternativas si hay demoras

Fecha límite para Directorio: 15 noviembre. ¿Llegas?",
            'created_at' => $createdAt->copy()->addHours(5),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Sí, llego. Tengo borrador listo para el viernes.",
            'created_at' => $createdAt->copy()->addDays(2),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Recibí el borrador. Muy completo. Solo agrega escenario pesimista de costos (+20%). Marco como resuelto, seguimiento en proyecto aparte.",
            'created_at' => $createdAt->copy()->addDays(5),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/pipeline,engineering
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'propuesta_capacidad_cbba_oruro.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/propuesta_capacidad.pdf',
            'file_type' => 'application/pdf', 'file_size_bytes' => rand(400000, 800000),
            'created_at' => $createdAt->copy()->addDays(4),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    // ==================== TICKET 5: PENDING - INSPECCIÓN DUCTO ====================
    private function createTicket5_InspeccionDucto(): void
    {
        $user = $this->users['Natalia'];
        $createdAt = Carbon::create(2025, 11, 28, 9, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['transporte']->id ?? null,
            'title' => 'Resultados inspección PIG ducto Santa Cruz - 3 anomalías detectadas',
            'description' => "Ricardo,

Completamos corrida de PIG inteligente en ducto principal Santa Cruz. Resultados preocupantes.

**ANOMALÍAS DETECTADAS:**
1. Km 45: Pérdida de espesor 22% (crítico si >25%)
2. Km 112: Abolladura 3.5% diámetro
3. Km 189: Corrosión externa, pérdida 18%

**RECOMENDACIONES CONTRATISTA:**
- Km 45: Reparar en máximo 6 meses
- Km 112: Monitorear, no crítico
- Km 189: Reparar en 12 meses

**PRESUPUESTO REPARACIONES:**
- Estimado: USD 450,000

Necesito tu aprobación para iniciar proceso de licitación para reparaciones.

Natalia Guzmán
Integridad de Ductos",
            'status' => 'pending', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(6),
            'first_response_at' => $createdAt->copy()->addHours(4),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Natalia,

El Km 45 con 22% me preocupa. ¿Cuál es la tendencia de degradación? ¿Cuánto perdió desde la última inspección (2023)?

Antes de licitar necesito:
1. Informe completo del contratista
2. Análisis de riesgo por cada anomalía
3. Opciones de reparación (clamp, reemplazo, etc.)

¿Puedes tenerlo para el lunes?",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Sí, el lunes tengo todo. En 2023 el Km 45 tenía 15%, así que perdió 7% en 2 años. Ritmo preocupante.",
            'created_at' => $createdAt->copy()->addHours(5),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/pipeline,inspection
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'reporte_pig_scz_nov2025.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/reporte_pig.pdf',
            'file_type' => 'application/pdf', 'file_size_bytes' => rand(500000, 900000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
