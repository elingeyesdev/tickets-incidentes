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
 * YPFB Tickets Seeder - Héctor Morales
 * Temas: Ingeniería, proyectos de expansión, obras civiles
 */
class YPFBTicketsHectorMoralesSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'hector.morales@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Freddy', 'last_name' => 'Torrico', 'email' => 'freddy.torrico.proyectos8@gmail.com'],
        ['first_name' => 'Lourdes', 'last_name' => 'Mamani', 'email' => 'lourdes.mamani.ingenieria8@gmail.com'],
        ['first_name' => 'Ramón', 'last_name' => 'Gutiérrez', 'email' => 'ramon.gutierrez.obras8@gmail.com'],
        ['first_name' => 'Silvia', 'last_name' => 'Quiroga', 'email' => 'silvia.quiroga.diseno8@gmail.com'],
        ['first_name' => 'Víctor', 'last_name' => 'Arandia', 'email' => 'victor.arandia.construccion8@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Héctor Morales...");
        $this->loadCompany();
        if (!$this->company) return;
        $this->loadAgent();
        if (!$this->agent) return;
        if ($this->alreadySeeded()) return;
        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();
        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Héctor Morales");
    }

    private function loadCompany(): void { $this->company = Company::where('name', 'YPFB Corporación')->first(); if (!$this->company) $this->command->error('❌ YPFB no encontrada.'); }
    private function loadAgent(): void { $this->agent = User::where('email', self::AGENT_EMAIL)->first(); if (!$this->agent) $this->command->error('❌ Agente no encontrado.'); }
    private function alreadySeeded(): bool { if (Ticket::where('company_id', $this->company->id)->where('owner_agent_id', $this->agent->id)->count() >= self::TICKETS_PER_AGENT) { $this->command->info("[OK] Tickets ya existen."); return true; } return false; }

    private function loadAreas(): void {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = ['tecnologia' => $areas->firstWhere('name', 'Tecnología y Sistemas de Información') ?? $areas->first()];
    }

    private function loadCategories(): void {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = ['equipo' => $cats->firstWhere('name', 'Problema de Equipo/Infraestructura') ?? $cats->first()];
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
        $this->createTicket1_ExpansionPlanta();
        $this->createTicket2_RetrasoObra();
        $this->createTicket3_LicitacionFallida();
        $this->createTicket4_DisenoTecnico();
        $this->createTicket5_InspeccionObra();
    }

    private function createTicket1_ExpansionPlanta(): void {
        $user = $this->users['Freddy'];
        $createdAt = Carbon::create(2025, 2, 18, 9, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Proyecto expansión Planta Separadora Río Grande - Fase 2 USD 45MM',
            'description' => "Héctor,\n\nPresento propuesta para Fase 2 de expansión Planta Río Grande.\n\n**ALCANCE:**\n- Aumento capacidad procesamiento: +50 MMSCFD\n- Nueva torre de deshidratación\n- Ampliación almacenamiento LPG (4 tanques adicionales)\n\n**INVERSIÓN:**\n- CAPEX: USD 45,000,000\n- Ingeniería: USD 3.5MM\n- Equipamiento: USD 28MM\n- Construcción: USD 12MM\n- Contingencia: USD 1.5MM\n\n**CRONOGRAMA:**\n- Ingeniería: 8 meses\n- Construcción: 18 meses\n- Comisionamiento: 4 meses\n- Total: 30 meses\n\n**ROI:**\n- Ingresos adicionales: USD 18MM/año\n- Payback: 2.5 años\n\n¿Puedo preparar estudio de factibilidad para Directorio?\n\nFreddy Torrico\nGerente de Proyectos",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(30),
            'first_response_at' => $createdAt->copy()->addHours(5), 'resolved_at' => $createdAt->copy()->addDays(25), 'closed_at' => $createdAt->copy()->addDays(30),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Freddy,\n\nNúmeros atractivos. Prepara factibilidad pero incluye:\n1. Análisis de riesgo (precios commodity, demanda)\n2. Comparación con alternativa modular\n3. Cronograma más agresivo (¿podemos en 24 meses?)\n\nFecha límite para Directorio: 15 abril.", 'created_at' => $createdAt->copy()->addHours(5)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Entendido. Modular es 20% más caro pero 6 meses más rápido. Incluyo ambas opciones.", 'created_at' => $createdAt->copy()->addDays(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Estudio completo adjunto. Recomiendo opción tradicional con cronograma optimizado de 26 meses.", 'created_at' => $createdAt->copy()->addDays(20)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Excelente trabajo. Presento al Directorio el jueves. Prepárate para presentar si te llaman.", 'created_at' => $createdAt->copy()->addDays(21)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Directorio aprobó! USD 45MM autorizados. Inicio ingeniería en mayo.", 'created_at' => $createdAt->copy()->addDays(25)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'factibilidad_rio_grande_fase2.pdf', 'file_path' => 'tickets/' . $ticket->id . '/factibilidad.pdf', 'file_type' => 'application/pdf', 'file_size_bytes' => rand(3000000, 6000000), 'created_at' => $createdAt->copy()->addDays(20)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Feb)");
    }

    private function createTicket2_RetrasoObra(): void {
        $user = $this->users['Víctor'];
        $createdAt = Carbon::create(2025, 5, 12, 10, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Retraso 45 días en obra estación de bombeo - Contratista incumple cronograma',
            'description' => "Héctor,\n\nObra de nueva estación de bombeo EB-08 está retrasada 45 días.\n\n**ESTADO:**\n- Avance real: 62%\n- Avance programado: 78%\n- Retraso: 45 días\n\n**CAUSAS ALEGADAS POR CONTRATISTA:**\n1. Lluvias extraordinarias en abril\n2. Demora en entrega de bombas (proveedor internacional)\n3. Falta de personal calificado\n\n**MI ANÁLISIS:**\n- Lluvias: Solo 10 días de para real\n- Bombas: Llegaron hace 15 días, sin instalar\n- Personal: Han rotado 3 jefes de obra en 4 meses\n\n¿Aplico penalidades del contrato o negocio nuevo cronograma?\n\nVíctor Arandia\nSupervisor de Obras",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(15),
            'first_response_at' => $createdAt->copy()->addHours(2), 'resolved_at' => $createdAt->copy()->addDays(10), 'closed_at' => $createdAt->copy()->addDays(15),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Víctor,\n\nCita al contratista mañana a las 9 AM en mi oficina. Lleva el contrato y la bitácora de obra.\n\nMi posición: Aplico penalidad parcial (25 días) y exijo plan de recuperación con dedicación exclusiva del mejor jefe de obra que tengan.", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Reunión hecha. Aceptaron penalidad de 25 días (USD 37,500). Nuevo jefe de obra desde lunes.", 'created_at' => $createdAt->copy()->addDays(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Avance recuperando. Esta semana subió a 70%. Nuevo cronograma viable.", 'created_at' => $createdAt->copy()->addDays(8)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Bien. Monitorea semanalmente y reporta. Cierro este ticket, seguimiento en reuniones de proyecto.", 'created_at' => $createdAt->copy()->addDays(10)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (May)");
    }

    private function createTicket3_LicitacionFallida(): void {
        $user = $this->users['Lourdes'];
        $createdAt = Carbon::create(2025, 7, 20, 14, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Licitación gasoducto sur declarada desierta - Solo 1 oferente no cumplió requisitos',
            'description' => "Héctor,\n\nLicitación para construcción gasoducto sur (USD 18MM) declarada desierta.\n\n**RESULTADO:**\n- Oferentes inscritos: 4\n- Oferentes que presentaron: 1 (Constructora Andina)\n- Resultado: No cumple requisitos técnicos mínimos\n\n**PROBLEMA:**\nProyecto estaba programado para iniciar en septiembre. Sin contrato, perdemos la temporada seca para construcción.\n\n**OPCIONES:**\n1. Relanzar licitación (2 meses mínimo)\n2. Contratación directa excepcional (riesgoso legalmente)\n3. Dividir proyecto en 2 tramos más pequeños\n\n¿Cuál opción seguimos?\n\nLourdes Mamani\nContrataciones de Proyectos",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(20),
            'first_response_at' => $createdAt->copy()->addHours(3), 'resolved_at' => $createdAt->copy()->addDays(15), 'closed_at' => $createdAt->copy()->addDays(20),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Lourdes,\n\nOpción 2 descartada, no vale el riesgo legal.\n\nAnaliza opción 3: ¿Dividir atrae más oferentes? ¿Hay constructoras medianas que pueden con tramos de USD 9MM?\n\nSi es viable, relanza en 2 tramos la próxima semana.", 'created_at' => $createdAt->copy()->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Sí, hay 3 constructoras medianas interesadas en tramos más pequeños. Preparo bases para 2 licitaciones.", 'created_at' => $createdAt->copy()->addDays(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Licitaciones publicadas. Tramo 1: 6 inscritos. Tramo 2: 5 inscritos. Apertura el 20 agosto.", 'created_at' => $createdAt->copy()->addDays(10)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Ambos tramos adjudicados! Firmas de contrato esta semana. Inicio obra 15 septiembre.", 'created_at' => $createdAt->copy()->addDays(18)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jul)");
    }

    private function createTicket4_DisenoTecnico(): void {
        $user = $this->users['Silvia'];
        $createdAt = Carbon::create(2025, 10, 8, 9, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Revisión diseño técnico planta de compresión - Observaciones del consultor',
            'description' => "Héctor,\n\nConsultor externo revisó diseño de nueva planta de compresión y tiene observaciones.\n\n**OBSERVACIONES MAYORES:**\n1. Diseño de fundaciones no considera actividad sísmica zona 3\n2. Sistema eléctrico subdimensionado para expansión futura\n3. Falta redundancia en sistema de control\n\n**IMPACTO:**\n- Correcciones cuestan aprox USD 180K adicionales\n- Retraso en ingeniería: 6 semanas\n\n¿Autoriza modificaciones o buscamos segunda opinión?\n\nSilvia Quiroga\nIngeniería de Diseño",
            'status' => 'resolved', 'priority' => 'medium', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(8),
            'first_response_at' => $createdAt->copy()->addHours(4), 'resolved_at' => $createdAt->copy()->addDays(8), 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Silvia,\n\nLas observaciones suenan válidas. USD 180K ahora es mejor que problemas después.\n\nAutorizo las modificaciones. Pero quiero reunión con el diseñador original para entender por qué no se consideraron estos puntos inicialmente.", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Reunión hecha. El diseñador usó norma antigua. Actualizamos especificaciones para futuros proyectos.", 'created_at' => $createdAt->copy()->addDays(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Bien. Procede con modificaciones. Marco resuelto, seguimiento en proyecto.", 'created_at' => $createdAt->copy()->addDays(4)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'observaciones_consultor_planta_compresion.pdf', 'file_path' => 'tickets/' . $ticket->id . '/observaciones.pdf', 'file_type' => 'application/pdf', 'file_size_bytes' => rand(500000, 1000000), 'created_at' => $createdAt]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    private function createTicket5_InspeccionObra(): void {
        $user = $this->users['Ramón'];
        $createdAt = Carbon::create(2025, 11, 22, 11, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Inspección final Estación de Regulación Villamontes - Pendiente cierre administrativo',
            'description' => "Héctor,\n\nInspección técnica final de estación Villamontes completada.\n\n**RESULTADO:**\n- Obra civil: Aprobada\n- Mecánica: Aprobada con observaciones menores\n- Electricidad: Aprobada\n- Instrumentación: Pendiente calibración 3 transmisores\n\n**OBSERVACIONES MENORES (5 días para corregir):**\n1. Pintura de tuberías incompleta en área de válvulas\n2. Falta señalización de seguridad en 2 puntos\n3. Calibración transmisores de presión\n\n**CIERRE ADMINISTRATIVO:**\n- Acta de recepción provisional: Lista para firma\n- Garantía de obra: Vigente hasta dic 2026\n\n¿Firmo acta provisional mientras contratista corrige?\n\nRamón Gutiérrez\nFiscalización de Obras",
            'status' => 'pending', 'priority' => 'low', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(5),
            'first_response_at' => $createdAt->copy()->addHours(4), 'resolved_at' => null, 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Ramón,\n\nSí, firma acta provisional con las observaciones documentadas. Retén 5% del pago final hasta que corrijan.\n\n¿Los transmisores de presión son críticos para operación o pueden operar mientras calibran?", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "No son críticos, hay redundancia. Pueden operar con los otros 3 transmisores mientras calibran.", 'created_at' => $createdAt->copy()->addHours(5)]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
