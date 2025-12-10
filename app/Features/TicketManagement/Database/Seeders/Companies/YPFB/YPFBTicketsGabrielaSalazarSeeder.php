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
 * YPFB Tickets Seeder - Gabriela Salazar
 * Temas: Exportaciones de gas, contratos internacionales, mercados externos
 */
class YPFBTicketsGabrielaSalazarSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'gabriela.salazar@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Mauricio', 'last_name' => 'Claros', 'email' => 'mauricio.claros.export11@gmail.com'],
        ['first_name' => 'Fernanda', 'last_name' => 'Quisbert', 'email' => 'fernanda.quisbert.brasil11@gmail.com'],
        ['first_name' => 'Ernesto', 'last_name' => 'Soliz', 'email' => 'ernesto.soliz.argentina11@gmail.com'],
        ['first_name' => 'Carolina', 'last_name' => 'Pacheco', 'email' => 'carolina.pacheco.comercio11@gmail.com'],
        ['first_name' => 'Andrés', 'last_name' => 'Mendoza', 'email' => 'andres.mendoza.inter11@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Gabriela Salazar...");
        $this->loadCompany();
        if (!$this->company) return;
        $this->loadAgent();
        if (!$this->agent) return;
        if ($this->alreadySeeded()) return;
        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();
        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Gabriela Salazar");
    }

    private function loadCompany(): void { $this->company = Company::where('name', 'YPFB Corporación')->first(); if (!$this->company) $this->command->error('❌ YPFB no encontrada.'); }
    private function loadAgent(): void { $this->agent = User::where('email', self::AGENT_EMAIL)->first(); if (!$this->agent) $this->command->error('❌ Agente no encontrado.'); }
    private function alreadySeeded(): bool { if (Ticket::where('company_id', $this->company->id)->where('owner_agent_id', $this->agent->id)->count() >= self::TICKETS_PER_AGENT) { $this->command->info("[OK] Tickets ya existen."); return true; } return false; }

    private function loadAreas(): void {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = ['comercializacion' => $areas->firstWhere('name', 'Comercialización y Ventas')];
    }

    private function loadCategories(): void {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'consulta' => $cats->firstWhere('name', 'Consulta sobre Consumo/Tarifas') ?? $cats->first(),
            'facturacion' => $cats->firstWhere('name', 'Problema de Facturación') ?? $cats->first(),
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
        $this->createTicket1_RenegociacionBrasil();
        $this->createTicket2_PagoArgentina();
        $this->createTicket3_NuevoMercado();
        $this->createTicket4_AuditoriaVolumenes();
        $this->createTicket5_ProyeccionDemanda();
    }

    private function createTicket1_RenegociacionBrasil(): void {
        $user = $this->users['Fernanda'];
        $createdAt = Carbon::create(2025, 1, 8, 9, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Propuesta Petrobras: Reducción volumen contratado 30 a 20 MMSCFD desde marzo',
            'description' => "Gabriela,\n\nPetrobras envió propuesta formal para reducir volumen.\n\n**PROPUESTA DE PETROBRAS:**\n- Volumen actual: 30 MMSCFD\n- Volumen propuesto: 20 MMSCFD (-33%)\n- Vigencia: Desde marzo 2025\n- Justificación: Aumento producción pre-sal brasileño\n\n**IMPACTO PARA YPFB:**\n- Pérdida ingresos: USD 85MM/año\n- Penalidad contractual: Solo aplicable si bajan de 15 MMSCFD\n\n**OPCIONES:**\n1. Aceptar reducción\n2. Negociar compensación (mejor precio por menor volumen)\n3. Invocar cláusula de disputas\n\n¿Cuál posición tomamos para la reunión del 15?\n\nFernanda Quisbert\nEjecutiva Brasil",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(30),
            'first_response_at' => $createdAt->copy()->addHours(3), 'resolved_at' => $createdAt->copy()->addDays(25), 'closed_at' => $createdAt->copy()->addDays(30),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Fernanda,\n\nPosición para negociación:\n\n1. NO aceptamos reducción sin compensación\n2. Si bajan a 20 MMSCFD, precio sube 15%\n3. Extensión de contrato 3 años adicionales\n4. Cláusula take-or-pay más estricta\n\nPrepara contrapropuesta. Reúnete conmigo mañana 10 AM para afinar posición.", 'created_at' => $createdAt->copy()->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "OK, mañana 10 AM en tu oficina. Llevo borrador de contrapropuesta.", 'created_at' => $createdAt->copy()->addHours(5)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Actualización: 3 rondas de negociación. Petrobras aceptó 22 MMSCFD con +8% precio y extensión 2 años.", 'created_at' => $createdAt->copy()->addDays(20)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Buen resultado. Minimizamos pérdida a USD 40MM/año y aseguramos contrato hasta 2028. Prepara adenda para firma.", 'created_at' => $createdAt->copy()->addDays(21)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Adenda firmada. Entra en vigor marzo 1.", 'created_at' => $createdAt->copy()->addDays(25)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'propuesta_petrobras_ene2025.pdf', 'file_path' => 'tickets/' . $ticket->id . '/propuesta_petrobras.pdf', 'file_type' => 'application/pdf', 'file_size_bytes' => rand(300000, 600000), 'created_at' => $createdAt]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Ene)");
    }

    private function createTicket2_PagoArgentina(): void {
        $user = $this->users['Ernesto'];
        $createdAt = Carbon::create(2025, 4, 10, 11, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'ENARSA Argentina con 120 días de mora - USD 180MM pendientes',
            'description' => "Gabriela,\n\nArgentina sigue sin pagar.\n\n**DEUDA:**\n- Monto: USD 180,000,000\n- Antigüedad: 120 días\n- Facturas: Dic 2024, Ene 2025, Feb 2025, Mar 2025\n\n**GESTIONES REALIZADAS:**\n- 8 comunicaciones formales\n- 2 reuniones con ENARSA\n- Se prometió pago \"próxima semana\" 3 veces\n\n**JUSTIFICACIÓN ARGENTINA:**\n- Crisis cambiaria\n- Falta de dólares en Banco Central\n\n¿Escalamos a nivel gubernamental?\n\nErnesto Soliz\nEjecutivo Argentina",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(40),
            'first_response_at' => $createdAt->copy()->addHours(2), 'resolved_at' => $createdAt->copy()->addDays(35), 'closed_at' => $createdAt->copy()->addDays(40),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Ernesto,\n\nSí, escalamos. Prepara nota para Ministro de Hidrocarburos con todos los antecedentes.\n\nParalelo:\n1. Notifica reducción de suministro 20% si no hay pago en 15 días\n2. Activa cláusula de intereses moratorios\n\n¿Cuál es la penalidad contractual por mora >90 días?", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Penalidad: LIBOR + 3% por día. Ya son USD 2.4MM en intereses. Envío nota al Ministro hoy.", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Reunión Ministros realizada. Argentina compromete plan de pagos: USD 45MM mensual x 4 meses. Primer pago el 20 de mayo.", 'created_at' => $createdAt->copy()->addDays(20)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Primer pago USD 45MM recibido ayer. Plan en marcha.", 'created_at' => $createdAt->copy()->addDays(32)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Excelente. Monitorea cumplimiento mensual. Cierro este ticket, seguimiento en reporte mensual.", 'created_at' => $createdAt->copy()->addDays(33)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Abr)");
    }

    private function createTicket3_NuevoMercado(): void {
        $user = $this->users['Carolina'];
        $createdAt = Carbon::create(2025, 7, 5, 10, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Oportunidad: Paraguay interesado en gas boliviano - Posible nuevo mercado',
            'description' => "Gabriela,\n\nParaguay hizo acercamiento exploratorio.\n\n**PROPUESTA PARAGUAY:**\n- Volumen interés: 5-8 MMSCFD\n- Uso: Generación eléctrica\n- Inicio deseado: 2027\n\n**DESAFÍOS:**\n- No hay gasoducto a Paraguay\n- Distancia: ~450 km desde Yacuiba\n- Inversión infraestructura: USD 600-800MM\n\n**OPCIONES:**\n1. Gasoducto nuevo (costoso pero permanente)\n2. GNL por camión (menor volumen, inmediato)\n3. Swap con Argentina (ellos entregan gas a Paraguay)\n\n¿Vale la pena explorar?\n\nCarolina Pacheco\nDesarrollo Nuevos Mercados",
            'status' => 'closed', 'priority' => 'medium', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(25),
            'first_response_at' => $createdAt->copy()->addHours(5), 'resolved_at' => $createdAt->copy()->addDays(20), 'closed_at' => $createdAt->copy()->addDays(25),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Carolina,\n\nOpción 3 (swap) es la más viable corto plazo. Exploremos:\n\n1. ¿Argentina aceptaría entregar gas a Paraguay a cambio de crédito con nosotros?\n2. ¿Paraguay está dispuesto a pagar precio competitivo?\n\nPrograma reunión exploratoria con ambos países. Nivel técnico primero.", 'created_at' => $createdAt->copy()->addHours(5)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Argentina abierta a swap si les descontamos de deuda. Paraguay ofrece USD 4.50/MMBTU. ¿Viablele?", 'created_at' => $createdAt->copy()->addDays(10)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "USD 4.50 bajo pero considerando que cobramos deuda argentina, puede servir. Prepara MOU trilateral para presentar a Ministerio.", 'created_at' => $createdAt->copy()->addDays(11)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "MOU listo. Ministerio aprobó explorar. Delegación viaja a Asunción el 15 agosto.", 'created_at' => $createdAt->copy()->addDays(18)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jul)");
    }

    private function createTicket4_AuditoriaVolumenes(): void {
        $user = $this->users['Mauricio'];
        $createdAt = Carbon::create(2025, 10, 3, 9, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Discrepancia volúmenes medidos: YPFB vs Petrobras diferencia 2.3% septiembre',
            'description' => "Gabriela,\n\nAuditoría mensual detectó discrepancia.\n\n**CIFRAS SEPTIEMBRE:**\n- Medición YPFB: 632.5 MMSCF\n- Medición Petrobras: 617.8 MMSCF\n- Diferencia: 14.7 MMSCF (2.3%)\n\n**IMPACTO ECONÓMICO:**\n- USD 1.2MM si Petrobras tiene razón\n\n**CAUSA PROBABLE:**\n- Calibración cromatógrafos (última hace 8 meses)\n\n¿Autorizas auditoría conjunta con Petrobras para determinar cuál medición es correcta?\n\nMauricio Claros\nMedición y Fiscalización",
            'status' => 'resolved', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(15),
            'first_response_at' => $createdAt->copy()->addHours(2), 'resolved_at' => $createdAt->copy()->addDays(15), 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Mauricio,\n\nSí, autorizo auditoría conjunta. Coordina con Fernanda para programar.\n\nParalelo:\n1. Calibra nuestros equipos ANTES de la auditoría\n2. Revisa histórico: ¿siempre medimos más o es nuevo?\n\n¿Cuándo pueden venir los de Petrobras?", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Petrobras puede el 15 octubre. Calibración nuestra programada para el 12. Histórico: los últimos 6 meses siempre medimos 1-2% más.", 'created_at' => $createdAt->copy()->addDays(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Auditoría completada. Nuestro cromatógrafo tenía desvío de +1.8%. Petrobras tenía razón. Ajuste a favor de ellos: USD 950K.", 'created_at' => $createdAt->copy()->addDays(13)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Entendido. Emite nota de crédito. Y programa calibración cada 3 meses en adelante para evitar esto. Marco resuelto.", 'created_at' => $createdAt->copy()->addDays(14)]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    private function createTicket5_ProyeccionDemanda(): void {
        $user = $this->users['Andrés'];
        $createdAt = Carbon::create(2025, 11, 18, 14, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Proyección demanda exportación 2026-2030: Necesito datos para planificación',
            'description' => "Gabriela,\n\nEstoy preparando el plan estratégico 2026-2030 y necesito proyecciones de demanda de exportación.\n\n**INFORMACIÓN REQUERIDA:**\n1. Volúmenes comprometidos con Brasil (por año)\n2. Volúmenes comprometidos con Argentina (por año)\n3. Probabilidad de nuevos mercados (Paraguay, Chile)\n4. Proyección de producción nacional disponible\n\n**URGENCIA:**\n- Directorio revisa plan el 5 diciembre\n- Necesito datos antes del 25 noviembre\n\n¿Puedes ayudarme con esta información?\n\nAndrés Mendoza\nPlanificación Estratégica",
            'status' => 'pending', 'priority' => 'medium', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(4),
            'first_response_at' => $createdAt->copy()->addHours(3), 'resolved_at' => null, 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Andrés,\n\nPuedo tener la información el 22. Te adelanto:\n\n**BRASIL:**\n- 2026-2028: 22 MMSCFD (contrato vigente)\n- 2029-2030: Por negociar (probable reducción adicional)\n\n**ARGENTINA:**\n- 2026: 14 MMSCFD\n- 2027-2030: Depende de renegociación 2026\n\n**NUEVOS MERCADOS:**\n- Paraguay: 30% probabilidad para 2027\n- Chile: <10% (tienen LNG más barato)\n\nEnvío documento completo el viernes.", 'created_at' => $createdAt->copy()->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Perfecto, espero el documento el viernes. Gracias!", 'created_at' => $createdAt->copy()->addHours(4)]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
