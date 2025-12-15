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
 * YPFB Tickets Seeder - Daniela Villarroel
 * Temas: Refinación, control de calidad de combustibles
 */
class YPFBTicketsDanielaVillarroelSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'daniela.villarroel@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Marcelo', 'last_name' => 'Quispe', 'email' => 'marcelo.quispe.refineria5@gmail.com'],
        ['first_name' => 'Sandra', 'last_name' => 'Apaza', 'email' => 'sandra.apaza.calidad5@gmail.com'],
        ['first_name' => 'Hugo', 'last_name' => 'Chávez', 'email' => 'hugo.chavez.laboratorio5@gmail.com'],
        ['first_name' => 'Claudia', 'last_name' => 'Morales', 'email' => 'claudia.morales.produccion5@gmail.com'],
        ['first_name' => 'René', 'last_name' => 'Vargas', 'email' => 'rene.vargas.proceso5@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Daniela Villarroel...");

        $this->loadCompany();
        if (!$this->company) return;

        $this->loadAgent();
        if (!$this->agent) return;

        if ($this->alreadySeeded()) return;

        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Daniela Villarroel");
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
        if ($count >= self::TICKETS_PER_AGENT) { $this->command->info("[OK] Tickets ya existen."); return true; }
        return false;
    }

    private function loadAreas(): void
    {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = [
            'refinacion' => $areas->firstWhere('name', 'Refinación e Industrialización'),
            'exploracion' => $areas->firstWhere('name', 'Exploración y Producción'),
        ];
    }

    private function loadCategories(): void
    {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'equipo' => $cats->firstWhere('name', 'Problema de Equipo/Infraestructura') ?? $cats->first(),
            'seguridad' => $cats->firstWhere('name', 'Reporte de Seguridad') ?? $cats->first(),
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
        $this->createTicket1_CalidadDiesel();
        $this->createTicket2_ParadaEmergencia();
        $this->createTicket3_ContaminacionTanque();
        $this->createTicket4_OptimizacionProceso();
        $this->createTicket5_CertificacionLaboratorio();
    }

    // ==================== TICKET 1: CLOSED - CALIDAD DIÉSEL ====================
    private function createTicket1_CalidadDiesel(): void
    {
        $user = $this->users['Hugo'];
        $createdAt = Carbon::create(2025, 1, 15, 7, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['refinacion']->id ?? null,
            'title' => 'Lote diésel fuera de especificación - Contenido azufre 52 ppm (máx 50)',
            'description' => "Daniela,

Laboratorio detectó lote de diésel fuera de especificación.

**ANÁLISIS:**
- Lote: DSL-2025-01-142
- Volumen: 850,000 litros
- Contenido azufre: 52 ppm (especificación máx 50 ppm)
- Cetano: 48 (OK, mín 45)
- Densidad: 0.845 g/ml (OK)

**CAUSA PROBABLE:**
Crudo procesado con alto contenido de azufre (Campo Sábalo).

**OPCIONES:**
A) Reprocesar (costo USD 15,000, 2 días)
B) Blending con lote bajo en azufre
C) Vender como diésel industrial (menor precio)

¿Cuál opción autoriza?

Hugo Chávez
Jefe Laboratorio Refinería Cochabamba",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'user',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addHours(2),
            'resolved_at' => $createdAt->copy()->addDays(3),
            'closed_at' => $createdAt->copy()->addDays(5),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Hugo,

Opción B es la más eficiente. ¿Tenemos inventario bajo en azufre disponible para blending?

Si hay, calcula la proporción necesaria para llegar a 48 ppm (margen de seguridad). Si no hay, evalúa opción A.

NO autorizo venta como industrial, perdemos margen.",
            'created_at' => $createdAt->copy()->addHours(2),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Sí hay. Lote DSL-2025-01-138 tiene 42 ppm. Blending 60/40 nos da 48 ppm. Procedo?",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Autorizado. Procede con blending y repite análisis antes de liberar.",
            'created_at' => $createdAt->copy()->addHours(5),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Blending completado. Nuevo análisis: 47.8 ppm azufre. Dentro de especificación. Lote liberado.",
            'created_at' => $createdAt->copy()->addDays(2),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Perfecto. Envía certificado de calidad y cierra el ticket.",
            'created_at' => $createdAt->copy()->addDays(2)->addHours(2),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/laboratory,chemistry
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'analisis_diesel_lote_142.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/analisis_diesel.pdf',
            'file_type' => 'application/pdf', 'file_size_bytes' => rand(150000, 300000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Ene)");
    }

    // ==================== TICKET 2: CLOSED - PARADA EMERGENCIA ====================
    private function createTicket2_ParadaEmergencia(): void
    {
        $user = $this->users['René'];
        $createdAt = Carbon::create(2025, 3, 22, 14, 20, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['seguridad']->id ?? null,
            'area_id' => $this->areas['refinacion']->id ?? null,
            'title' => 'EMERGENCIA: Parada no programada Unidad Destilación - Fuga vapor alta presión',
            'description' => "Daniela,

Parada de emergencia en Unidad de Destilación Primaria.

**INCIDENTE:**
- Hora: 14:05
- Causa: Fuga de vapor en línea de alimentación (800 psi)
- Acción: ESD activado automáticamente
- Personal: Evacuado, sin heridos

**ESTADO ACTUAL:**
- Unidad offline
- Enfriamiento en curso
- Producción: 0 barriles

**IMPACTO:**
- Pérdida producción: 12,000 bpd
- Duración estimada parada: 24-48 horas

Necesito autorización para ingreso de contratista de soldadura especializada.

René Vargas
Supervisor Turno Refinería",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(4),
            'first_response_at' => $createdAt->copy()->addMinutes(20),
            'resolved_at' => $createdAt->copy()->addDays(2),
            'closed_at' => $createdAt->copy()->addDays(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "René,

Contratista autorizado. Prioridad absoluta es reparar sin comprometer seguridad.

Confirma:
1. ¿Línea despresurizada?
2. ¿Bloqueos instalados?
3. ¿Permiso de trabajo en caliente emitido?

Reporta cada 2 horas hasta reanudación.",
            'created_at' => $createdAt->copy()->addMinutes(20),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Sí a todo. Soldadura iniciará en 1 hora cuando termine enfriamiento. 8 PM estimada.",
            'created_at' => $createdAt->copy()->addHours(2),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Soldadura completada 11 PM. Prueba hidrostática mañana 6 AM.",
            'created_at' => $createdAt->copy()->addHours(9),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Prueba OK. Unidad arrancando. Producción normal en 4 horas.",
            'created_at' => $createdAt->copy()->addHours(18),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Excelente respuesta del equipo. Prepara informe de incidente para Gerencia y RRHH. Incluye causa raíz y acciones preventivas. Cierro ticket.",
            'created_at' => $createdAt->copy()->addDays(1),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/refinery,steam
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'foto_fuga_vapor_ud01.jpg',
            'file_path' => 'tickets/' . $ticket->id . '/fuga_vapor.jpg',
            'file_type' => 'image/jpeg', 'file_size_bytes' => rand(400000, 700000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Mar)");
    }

    // ==================== TICKET 3: CLOSED - CONTAMINACIÓN TANQUE ====================
    private function createTicket3_ContaminacionTanque(): void
    {
        $user = $this->users['Sandra'];
        $createdAt = Carbon::create(2025, 6, 10, 9, 45, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['refinacion']->id ?? null,
            'title' => 'Contaminación con agua Tanque TK-205 - 1.2MM litros gasolina afectados',
            'description' => "Daniela,

Detectamos contaminación con agua en tanque de gasolina TK-205.

**ANÁLISIS:**
- Producto: Gasolina Especial
- Volumen afectado: 1,200,000 litros
- Contenido agua: 0.8% (máx permitido 0.05%)
- Valor producto: ~USD 900,000

**CAUSA PROBABLE:**
Ingreso de agua lluvia por sello de techo flotante dañado.

**OPCIONES:**
A) Tratamiento con coalescedor (recupera 95%, 5 días)
B) Mezcla gradual con producto seco
C) Venta como producto degradado (pérdida 15%)

Adjunto análisis de laboratorio.

Sandra Apaza
Control de Calidad",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'user',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(10),
            'first_response_at' => $createdAt->copy()->addHours(3),
            'resolved_at' => $createdAt->copy()->addDays(7),
            'closed_at' => $createdAt->copy()->addDays(10),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Sandra,

Opción A es la correcta. ¿Tenemos capacidad en coalescedor o hay que alquilar equipo externo?

Mientras tanto:
1. Aislar TK-205
2. Reparar sello de techo (prioridad)
3. Inventario de tanques afectados por lluvias recientes

Autorizo gasto hasta USD 50,000 para recuperación.",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Coalescedor propio disponible. Iniciamos mañana. Sello ya reparado.",
            'created_at' => $createdAt->copy()->addHours(6),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Día 3: Contenido agua bajó a 0.15%. Continuamos tratamiento.",
            'created_at' => $createdAt->copy()->addDays(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Tratamiento completo. Agua final: 0.03%. Recuperamos 1,140,000 litros (95%). Lote liberado.",
            'created_at' => $createdAt->copy()->addDays(6),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Excelente recuperación. Pérdida final 5% es aceptable vs 15% si vendíamos degradado. Buen trabajo Sandra. Cierro ticket.",
            'created_at' => $createdAt->copy()->addDays(7),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/tank,industrial
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'analisis_contaminacion_tk205.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/analisis_contaminacion.pdf',
            'file_type' => 'application/pdf', 'file_size_bytes' => rand(200000, 400000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jun)");
    }

    // ==================== TICKET 4: RESOLVED - OPTIMIZACIÓN PROCESO ====================
    private function createTicket4_OptimizacionProceso(): void
    {
        $user = $this->users['Marcelo'];
        $createdAt = Carbon::create(2025, 10, 5, 11, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['refinacion']->id ?? null,
            'title' => 'Propuesta optimización Unidad Cracking - Aumento rendimiento 3.5%',
            'description' => "Daniela,

Identificamos oportunidad de mejora en Unidad de Cracking Catalítico.

**PROPUESTA:**
Ajuste de parámetros operativos basado en análisis de datos 2024:
- Temperatura reactor: +8°C
- Presión: -5 psi
- Velocidad espacial: +0.3 h⁻¹

**BENEFICIO ESTIMADO:**
- Aumento rendimiento gasolina: 3.5%
- Reducción coque: 8%
- Ahorro anual: USD 1.2MM

**INVERSIÓN:**
- Ninguna (solo ajuste operativo)
- Requiere prueba piloto 72 horas

¿Autoriza prueba piloto semana próxima?

Marcelo Quispe
Ingeniero de Procesos",
            'status' => 'resolved', 'priority' => 'medium',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(12),
            'first_response_at' => $createdAt->copy()->addHours(6),
            'resolved_at' => $createdAt->copy()->addDays(12),
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Marcelo,

Interesante propuesta. Antes de autorizar prueba necesito:
1. Análisis de riesgos (impacto en catalizador)
2. Plan de contingencia si resultados negativos
3. Aprobación de Seguridad Industrial

¿Puedes tener esto para el miércoles?",
            'created_at' => $createdAt->copy()->addHours(6),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Miércoles tengo todo. Ya hablé con Seguridad, solo necesitan el documento formal.",
            'created_at' => $createdAt->copy()->addDays(1),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Documentación lista. Adjunto. Seguridad aprobó.",
            'created_at' => $createdAt->copy()->addDays(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Revisé documentación. Autorizo prueba piloto inicio lunes. Marco ticket resuelto. Seguimiento de resultados en ticket separado.",
            'created_at' => $createdAt->copy()->addDays(5),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/refinery,chemical
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'propuesta_optimizacion_fcc.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/propuesta_fcc.pdf',
            'file_type' => 'application/pdf', 'file_size_bytes' => rand(300000, 600000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    // ==================== TICKET 5: PENDING - CERTIFICACIÓN LABORATORIO ====================
    private function createTicket5_CertificacionLaboratorio(): void
    {
        $user = $this->users['Claudia'];
        $createdAt = Carbon::create(2025, 11, 20, 15, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null,
            'area_id' => $this->areas['refinacion']->id ?? null,
            'title' => 'Renovación certificación ISO 17025 laboratorio - Auditoría enero 2026',
            'description' => "Daniela,

Nuestra certificación ISO 17025 vence en febrero 2026. Auditoría de renovación programada para enero.

**ESTADO ACTUAL:**
- 8 de 12 procedimientos actualizados
- 3 equipos pendientes de calibración
- 2 analistas necesitan recertificación

**PRESUPUESTO REQUERIDO:**
- Calibración equipos: USD 8,500
- Recertificación personal: USD 3,200
- Auditoría IBNORCA: USD 5,000
- **Total: USD 16,700**

**CRONOGRAMA:**
- Completar procedimientos: 15 dic
- Calibrar equipos: 20 dic
- Recertificación personal: 5 ene
- Pre-auditoría interna: 10 ene
- Auditoría IBNORCA: 20-22 ene

¿Aprueba presupuesto para iniciar trámites?

Claudia Morales
Coordinadora SGC Laboratorio",
            'status' => 'pending', 'priority' => 'medium',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(5),
            'first_response_at' => $createdAt->copy()->addHours(4),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Claudia,

Presupuesto razonable. Aprobado. Pero tengo dudas:

1. ¿Los 4 procedimientos pendientes tienen dueño asignado?
2. ¿Cuáles equipos faltan calibrar?
3. ¿Cuál es el riesgo si no renovamos a tiempo?

Confirma estos puntos y procede con los trámites.",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "1. Sí, Hugo tiene 2 y yo tengo 2. Terminamos 10 dic seguro.\n2. Cromatógrafo, densímetro digital y titulador automático.\n3. Sin ISO 17025 no podemos emitir certificados válidos para exportación. Problema serio.",
            'created_at' => $createdAt->copy()->addHours(5),
        ]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
