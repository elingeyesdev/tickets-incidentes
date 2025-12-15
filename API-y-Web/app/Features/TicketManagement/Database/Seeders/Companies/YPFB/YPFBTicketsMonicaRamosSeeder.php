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
 * YPFB Tickets Seeder - Mónica Ramos
 * Temas: Administración, finanzas, RRHH, presupuestos
 */
class YPFBTicketsMonicaRamosSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'monica.ramos@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Alberto', 'last_name' => 'Ríos', 'email' => 'alberto.rios.finanzas7@gmail.com'],
        ['first_name' => 'Gloria', 'last_name' => 'Vargas', 'email' => 'gloria.vargas.rrhh7@gmail.com'],
        ['first_name' => 'Jaime', 'last_name' => 'Ordóñez', 'email' => 'jaime.ordonez.contabilidad7@gmail.com'],
        ['first_name' => 'Maritza', 'last_name' => 'León', 'email' => 'maritza.leon.presupuesto7@gmail.com'],
        ['first_name' => 'Pablo', 'last_name' => 'Céspedes', 'email' => 'pablo.cespedes.tesoreria7@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Mónica Ramos...");
        $this->loadCompany();
        if (!$this->company) return;
        $this->loadAgent();
        if (!$this->agent) return;
        if ($this->alreadySeeded()) return;
        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();
        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Mónica Ramos");
    }

    private function loadCompany(): void { $this->company = Company::where('name', 'YPFB Corporación')->first(); if (!$this->company) $this->command->error('❌ YPFB no encontrada.'); }
    private function loadAgent(): void { $this->agent = User::where('email', self::AGENT_EMAIL)->first(); if (!$this->agent) $this->command->error('❌ Agente no encontrado.'); }
    private function alreadySeeded(): bool { $count = Ticket::where('company_id', $this->company->id)->where('owner_agent_id', $this->agent->id)->count(); if ($count >= self::TICKETS_PER_AGENT) { $this->command->info("[OK] Tickets ya existen."); return true; } return false; }
    
    private function loadAreas(): void {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = ['administracion' => $areas->firstWhere('name', 'Administración, Finanzas y Recursos Humanos')];
    }

    private function loadCategories(): void {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'facturacion' => $cats->firstWhere('name', 'Problema de Facturación') ?? $cats->first(),
            'consulta' => $cats->firstWhere('name', 'Consulta sobre Consumo/Tarifas') ?? $cats->first(),
        ];
    }

    private function createUsers(): void {
        foreach ($this->userPoolData as $ud) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                ]
            );
            ['user_id' => $user->id],
                ['first_name' => $ud['first_name'], 'last_name' => $ud['last_name'], 'avatar_url' => AvatarHelper::getRandom($isFemale ? 'female' : 'male'), 'phone_number' => '+591' . rand(70000000, 79999999), 'theme' => 'light', 'language' => 'es', 'timezone' => 'America/La_Paz']);
            UserRole::firstOrCreate(
                ['user_id' => $user->id, 'role_code' => 'USER', 'company_id' => $this->company->id],
                ['is_active' => true]
            );
            $this->users[$ud['first_name']] = $user;
        }
    }

    private function createTickets(): void {
        $this->createTicket1_PresupuestoEmergencia();
        $this->createTicket2_PagoProveedores();
        $this->createTicket3_AuditoriaCGE();
        $this->createTicket4_ContratoPersonal();
        $this->createTicket5_CierreContable();
    }

    private function createTicket1_PresupuestoEmergencia(): void {
        $user = $this->users['Maritza'];
        $createdAt = Carbon::create(2025, 1, 28, 9, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['administracion']->id ?? null,
            'title' => 'Solicitud reasignación presupuestaria USD 2.5MM - Reparación urgente refinería',
            'description' => "Mónica,\n\nNecesito autorización para reasignación presupuestaria de emergencia.\n\n**DETALLE:**\n- Monto: USD 2,500,000\n- Origen: Partida de inversiones 2025 (no ejecutada)\n- Destino: Reparación unidad de cracking (falla inesperada)\n\n**JUSTIFICACIÓN:**\nSin reparación, producción de gasolina cae 40%. Pérdida estimada USD 800K/mes.\n\n**CRONOGRAMA:**\n- Aprobación interna: Esta semana\n- Aprobación Ministerio: 15 días\n- Ejecución: Febrero\n\n¿Puedes gestionar con Directorio?\n\nMaritza León\nPresupuestos",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(20),
            'first_response_at' => $createdAt->copy()->addHours(2), 'resolved_at' => $createdAt->copy()->addDays(15), 'closed_at' => $createdAt->copy()->addDays(20),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Maritza,\n\nPrepara memorándum justificativo con análisis costo-beneficio. Lo presento al Directorio el jueves.\n\n¿Tienes el informe técnico de la falla del cracking?", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Sí, lo adjunto. Memorándum listo mañana temprano.", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Directorio aprobó. Gestiona con Ministerio la resolución formal.", 'created_at' => $createdAt->copy()->addDays(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Resolución ministerial recibida. Fondos disponibles desde mañana.", 'created_at' => $createdAt->copy()->addDays(14)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Excelente. Cierra el ticket y archiva documentación.", 'created_at' => $createdAt->copy()->addDays(15)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'solicitud_reasignacion_presupuesto.pdf', 'file_path' => 'tickets/' . $ticket->id . '/solicitud_presupuesto.pdf', 'file_type' => 'application/pdf', 'file_size_bytes' => rand(200000, 400000), 'created_at' => $createdAt]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Ene)");
    }

    private function createTicket2_PagoProveedores(): void {
        $user = $this->users['Pablo'];
        $createdAt = Carbon::create(2025, 4, 5, 14, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id ?? null, 'area_id' => $this->areas['administracion']->id ?? null,
            'title' => 'Proveedores con pagos vencidos >60 días - Riesgo de corte de servicios',
            'description' => "Mónica,\n\nTenemos 8 proveedores con pagos vencidos más de 60 días.\n\n**CRÍTICOS:**\n1. ENDE (electricidad plantas): USD 1.2MM - 75 días\n2. Repsol (servicios técnicos): USD 450K - 68 días\n3. Transredes (transporte): USD 890K - 62 días\n\nENDE amenaza con corte el viernes. Sin electricidad, paramos refinería.\n\n**CAUSA:**\nTraspasos del Ministerio de Hacienda retrasados Q1.\n\n¿Podemos usar fondo de contingencia para ENDE?\n\nPablo Céspedes\nTesorería",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addHours(1), 'resolved_at' => $createdAt->copy()->addDays(3), 'closed_at' => $createdAt->copy()->addDays(5),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Pablo,\n\nAutorizo usar contingencia para ENDE. USD 1.2MM, procesa hoy.\n\nPara Repsol y Transredes, negocia plan de pagos 30-60-90 días. ¿Aceptarían?", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "ENDE procesado. Repsol acepta plan. Transredes pide reunión mañana.", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Transredes aceptó. Todos con plan de pago. Situación estabilizada.", 'created_at' => $createdAt->copy()->addDays(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Bien manejado. Coordina con Hacienda para acelerar traspasos Q2. Cierro ticket.", 'created_at' => $createdAt->copy()->addDays(3)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Abr)");
    }

    private function createTicket3_AuditoriaCGE(): void {
        $user = $this->users['Jaime'];
        $createdAt = Carbon::create(2025, 7, 10, 8, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['administracion']->id ?? null,
            'title' => 'Preparación auditoría CGE 2024 - Lista de documentación requerida',
            'description' => "Mónica,\n\nContraloría confirmó auditoría especial para agosto. Periodo: Gestión 2024.\n\n**DOCUMENTACIÓN SOLICITADA:**\n1. Estados financieros auditados\n2. Conciliaciones bancarias 12 meses\n3. Contratos > USD 100K\n4. Planillas de personal y aportes\n5. Registros de activos fijos\n\n**ESTADO:**\n- Items 1, 2: Listos\n- Item 3: 85% (faltan 12 contratos de operaciones)\n- Item 4: Listo\n- Item 5: 70% (actualizando inventario Tarija)\n\n¿Podemos pedir prórroga para items 3 y 5?\n\nJaime Ordóñez\nContabilidad",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(25),
            'first_response_at' => $createdAt->copy()->addHours(3), 'resolved_at' => $createdAt->copy()->addDays(20), 'closed_at' => $createdAt->copy()->addDays(25),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Jaime,\n\nNo pidas prórroga, queda mal. Mejor acelera:\n- Contratos: Pide a Operaciones los 12 faltantes HOY, deadline viernes\n- Activos: Envía a alguien a Tarija si es necesario\n\n¿Quién tiene los contratos faltantes?", 'created_at' => $createdAt->copy()->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Ricardo Torres los tiene. Ya le escribí, dice que el viernes los envía.", 'created_at' => $createdAt->copy()->addHours(5)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Todo completo. Documentación entregada a CGE el 28 julio. Auditoría inicia 5 agosto.", 'created_at' => $createdAt->copy()->addDays(18)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Auditoría terminó. Dictamen limpio con 2 observaciones menores. Sin hallazgos graves.", 'created_at' => $createdAt->copy()->addDays(22)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Excelente resultado. Felicita al equipo. Cierro ticket.", 'created_at' => $createdAt->copy()->addDays(23)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jul)");
    }

    private function createTicket4_ContratoPersonal(): void {
        $user = $this->users['Gloria'];
        $createdAt = Carbon::create(2025, 9, 15, 10, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['administracion']->id ?? null,
            'title' => 'Renovación contratos eventuales 2026 - 85 personas, deadline octubre',
            'description' => "Mónica,\n\nContratos eventuales de 85 trabajadores vencen el 31 diciembre.\n\n**DISTRIBUCIÓN:**\n- Operaciones campos: 45 personas\n- Refinerías: 28 personas\n- Administrativos: 12 personas\n\n**PRESUPUESTO 2026:**\n- Requerido: USD 2.8MM\n- Aprobado: USD 2.4MM\n- Déficit: USD 400K\n\n**OPCIONES:**\n1. Renovar todos con reducción salarial 15%\n2. Reducir personal a 70 (no renovar 15)\n3. Solicitar ampliación presupuestaria\n\n¿Cuál opción prefieres?\n\nGloria Vargas\nRRHH",
            'status' => 'resolved', 'priority' => 'medium', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(12),
            'first_response_at' => $createdAt->copy()->addHours(4), 'resolved_at' => $createdAt->copy()->addDays(12), 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Gloria,\n\nOpción 1 no es viable (conflicto sindical). Opción 3 es difícil en contexto actual.\n\nAnaliza opción 2: ¿Cuáles 15 puestos son menos críticos? Dame lista con justificación para cada uno.", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Lista enviada. 8 administrativos y 7 operaciones. Adjunto análisis de cada puesto.", 'created_at' => $createdAt->copy()->addDays(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Revisé. Apruebo no renovar los 8 administrativos. Los 7 de operaciones son críticos, busca USD 200K adicional en otras partidas. Resuelvo ticket, seguimiento en presupuesto 2026.", 'created_at' => $createdAt->copy()->addDays(5)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'analisis_puestos_eventuales.xlsx', 'file_path' => 'tickets/' . $ticket->id . '/analisis_puestos.xlsx', 'file_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'file_size_bytes' => rand(50000, 150000), 'created_at' => $createdAt->copy()->addDays(3)]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Sep)");
    }

    private function createTicket5_CierreContable(): void {
        $user = $this->users['Alberto'];
        $createdAt = Carbon::create(2025, 11, 28, 15, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id ?? null, 'area_id' => $this->areas['administracion']->id ?? null,
            'title' => 'Cronograma cierre contable 2025 - Coordinación con todas las áreas',
            'description' => "Mónica,\n\nInicio preparación del cierre contable 2025.\n\n**CRONOGRAMA PROPUESTO:**\n- 15 dic: Corte de compras y servicios\n- 20 dic: Conciliación inventarios\n- 26 dic: Cierre parcial noviembre\n- 5 ene: Ajustes de auditoría\n- 15 ene: Estados financieros preliminares\n- 31 ene: Estados financieros finales\n\n**PENDIENTES:**\n- Confirmación de saldos con bancos\n- Inventario físico refinería (programar)\n- Provisiones de litigios (necesito info de Legal)\n\n¿Apruebas cronograma?\n\nAlberto Ríos\nGerente Finanzas",
            'status' => 'pending', 'priority' => 'medium', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(4),
            'first_response_at' => $createdAt->copy()->addHours(3), 'resolved_at' => null, 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Alberto,\n\nCronograma aprobado con ajuste: Estados finales el 25 enero (Directorio es el 28).\n\nSobre pendientes:\n- Bancos: Envía cartas hoy\n- Inventario: Coordina con Daniela Villarroel\n- Litigios: Yo hablo con Legal, te paso cifras el lunes\n\n¿Necesitas personal adicional para el cierre?", 'created_at' => $createdAt->copy()->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Ajustado a 25 enero. No necesito personal adicional por ahora. Cartas a bancos salen mañana.", 'created_at' => $createdAt->copy()->addHours(4)]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
