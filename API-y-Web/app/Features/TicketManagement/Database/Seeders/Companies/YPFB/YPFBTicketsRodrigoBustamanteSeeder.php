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
 * YPFB Tickets Seeder - Rodrigo Bustamante
 * Temas: Estaciones de servicio, GNV, distribución minorista
 */
class YPFBTicketsRodrigoBustamanteSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'rodrigo.bustamante@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Gustavo', 'last_name' => 'Rojas', 'email' => 'gustavo.rojas.estacion10@gmail.com'],
        ['first_name' => 'Patricia', 'last_name' => 'Nina', 'email' => 'patricia.nina.gnv10@gmail.com'],
        ['first_name' => 'Walter', 'last_name' => 'Ticona', 'email' => 'walter.ticona.surtidor10@gmail.com'],
        ['first_name' => 'Miriam', 'last_name' => 'Flores', 'email' => 'miriam.flores.gasolinera10@gmail.com'],
        ['first_name' => 'Carlos', 'last_name' => 'Apaza', 'email' => 'carlos.apaza.combustible10@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Rodrigo Bustamante...");
        $this->loadCompany();
        if (!$this->company) return;
        $this->loadAgent();
        if (!$this->agent) return;
        if ($this->alreadySeeded()) return;
        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();
        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Rodrigo Bustamante");
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
            'interrupcion' => $cats->firstWhere('name', 'Incidente de Interrupción del Servicio') ?? $cats->first(),
            'equipo' => $cats->firstWhere('name', 'Problema de Equipo/Infraestructura') ?? $cats->first(),
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
        $this->createTicket1_FallaSurtidor();
        $this->createTicket2_DesabastecimientoGNV();
        $this->createTicket3_CalidadCombustible();
        $this->createTicket4_NuevaEstacion();
        $this->createTicket5_ProblemasPago();
    }

    private function createTicket1_FallaSurtidor(): void {
        $user = $this->users['Walter'];
        $createdAt = Carbon::create(2025, 2, 5, 6, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Estación Km 7 El Alto: 3 de 6 surtidores fuera de servicio desde ayer',
            'description' => "Rodrigo,\n\n3 surtidores fallaron ayer tarde.\n\n**SURTIDORES DAÑADOS:**\n- Surtidor 2: Error E15 (bomba no arranca)\n- Surtidor 4: Pantalla no enciende\n- Surtidor 5: Flujo intermitente\n\n**IMPACTO:**\n- Solo 3 surtidores operando\n- Filas de 20+ vehículos\n- Quejas de clientes\n\nNecesitamos técnico urgente.\n\nWalter Ticona\nGerente Estación Km 7",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(4),
            'first_response_at' => $createdAt->copy()->addHours(1), 'resolved_at' => $createdAt->copy()->addDays(2), 'closed_at' => $createdAt->copy()->addDays(4),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Walter,\n\nEnvío técnico HOY. Llega antes del mediodía.\n\nMientras tanto, ¿el tanque subterráneo tiene nivel normal? A veces el Error E15 es por aire en la bomba.", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Tanque al 70%, normal. El problema es eléctrico creo.", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Técnico llegó. Problema era un fusible quemado en el panel. Los 3 surtidores ya funcionan.", 'created_at' => $createdAt->copy()->addHours(8)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Bien. Pide al técnico que revise todo el panel para prevenir. Cierro ticket.", 'created_at' => $createdAt->copy()->addHours(9)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Feb)");
    }

    private function createTicket2_DesabastecimientoGNV(): void {
        $user = $this->users['Patricia'];
        $createdAt = Carbon::create(2025, 4, 22, 14, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['interrupcion']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Desabastecimiento GNV Zona Sur La Paz - 5 estaciones sin gas hace 2 días',
            'description' => "Rodrigo,\n\nHay crisis de GNV en Zona Sur.\n\n**ESTACIONES SIN GAS:**\n1. YPFB Calacoto\n2. YPFB Achumani\n3. YPFB Irpavi\n4. YPFB Obrajes\n5. YPFB Cota Cota\n\n**SITUACIÓN:**\n- Sin abastecimiento desde domingo\n- Filas de 50+ vehículos en estaciones privadas\n- Taxis protestan en redes sociales\n- Prensa empezando a cubrir\n\n¿Qué está pasando con el suministro?\n\nPatricia Nina\nCoordinadora GNV La Paz",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addMinutes(30), 'resolved_at' => $createdAt->copy()->addDays(3), 'closed_at' => $createdAt->copy()->addDays(5),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Patricia,\n\nHay falla en planta compresora Senkata. Reparación en curso, ETA 24 horas.\n\nMientras tanto:\n1. Prioriza taxis con voucher de emergencia\n2. Comunica a prensa que es temporal\n3. Coordina con estaciones privadas para absorber demanda\n\nTe informo apenas restablezcan.", 'created_at' => $createdAt->copy()->addMinutes(30)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "OK entendido. ¿Puedo decir a prensa que mañana se normaliza?", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Sí, confirma que entre mañana y pasado mañana se normaliza 100%.", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Compresora reparada. Estaciones recibiendo gas desde esta mañana. Filas normalizándose.", 'created_at' => $createdAt->copy()->addDays(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Perfecto. Prepara informe de crisis para Gerencia. Cierro ticket.", 'created_at' => $createdAt->copy()->addDays(3)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Abr)");
    }

    private function createTicket3_CalidadCombustible(): void {
        $user = $this->users['Gustavo'];
        $createdAt = Carbon::create(2025, 7, 8, 10, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Quejas de 8 clientes: Gasolina "adulterada" dañó vehículos - Estación Av. América',
            'description' => "Rodrigo,\n\n8 clientes reportan problemas con gasolina de ayer.\n\n**QUEJAS:**\n- 3 vehículos no arrancan\n- 2 con luz de check engine\n- 3 con motor que \"jalonea\"\n\nTodos cargaron gasolina especial ayer entre 4-7 PM.\n\n**RIESGO:**\n- Clientes amenazan con denuncia a Defensa del Consumidor\n- Uno ya publicó en Facebook\n\nNecesito que tomen muestra del tanque urgente.\n\nGustavo Rojas\nGerente Estación Av. América",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(10),
            'first_response_at' => $createdAt->copy()->addHours(1), 'resolved_at' => $createdAt->copy()->addDays(7), 'closed_at' => $createdAt->copy()->addDays(10),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Gustavo,\n\nEnvío laboratorio móvil HOY. Suspende venta de gasolina especial hasta resultados.\n\nRecoge datos de contacto de los 8 afectados. SI confirmo problema de calidad, YPFB asume reparaciones.\n\n¿Cuándo fue la última cisterna que recibiste?", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Última cisterna: ayer 2 PM. Lote G-2025-07-1245. Datos de afectados listos.", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Laboratorio tomó muestras. Resultados en 48 horas. No vendas gasolina especial hasta entonces.", 'created_at' => $createdAt->copy()->addHours(6)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "¿Ya hay resultados?", 'created_at' => $createdAt->copy()->addDays(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Sí. AGUA EN EL COMBUSTIBLE. 2.3% (máx permitido 0.05%). Problema fue en cisterna de transporte, no en refinería.\n\nAcciones:\n1. Vaciar tu tanque y limpiar\n2. Reparaciones a afectados cubiertas por YPFB\n3. Reclamo a transportista", 'created_at' => $createdAt->copy()->addDays(3)->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Entendido. Ya contacté a los 8 clientes. Aliviados de que YPFB responda.", 'created_at' => $createdAt->copy()->addDays(4)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'lista_afectados_gasolina.pdf', 'file_path' => 'tickets/' . $ticket->id . '/lista_afectados.pdf', 'file_type' => 'application/pdf', 'file_size_bytes' => rand(50000, 150000), 'created_at' => $createdAt->copy()->addHours(2)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jul)");
    }

    private function createTicket4_NuevaEstacion(): void {
        $user = $this->users['Miriam'];
        $createdAt = Carbon::create(2025, 10, 15, 9, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Solicitud apertura nueva estación de servicio - Zona Plan 3000, Santa Cruz',
            'description' => "Rodrigo,\n\nQuiero abrir estación de servicio YPFB en Plan 3000.\n\n**DATOS:**\n- Ubicación: Av. Principal, manzana 45\n- Terreno: 2,500 m² (propiedad propia)\n- Inversión estimada: USD 450,000\n- Demanda: Alta (zona sin estaciones YPFB en 5 km)\n\n**PREGUNTAS:**\n1. ¿Qué requisitos necesito?\n2. ¿Hay cupos disponibles para nuevas estaciones?\n3. ¿Cuánto demora el proceso?\n\nMiriam Flores",
            'status' => 'resolved', 'priority' => 'low', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(10),
            'first_response_at' => $createdAt->copy()->addHours(6), 'resolved_at' => $createdAt->copy()->addDays(10), 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Miriam,\n\nBuena ubicación, Plan 3000 sí necesita más estaciones.\n\n**REQUISITOS PRINCIPALES:**\n1. Título de propiedad legalizado\n2. Estudio de impacto ambiental\n3. Licencia municipal de funcionamiento\n4. Capital mínimo demostrable: USD 300K\n5. Plan de negocios\n\n**PROCESO:**\n- Duración: 8-12 meses\n- Cupos: Sí hay para SCZ\n\n¿Quiere que le envíe el formulario de solicitud?", 'created_at' => $createdAt->copy()->addHours(6)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Sí por favor, envíeme el formulario. Tengo todos los documentos.", 'created_at' => $createdAt->copy()->addDays(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Formulario adjunto. Complete y presente en oficina regional SCZ. Le asignarán un ejecutivo de cuenta. Marco resuelto, seguimiento en proceso de apertura.", 'created_at' => $createdAt->copy()->addDays(2)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $this->agent->id, 'file_name' => 'formulario_nueva_estacion_ypfb.pdf', 'file_path' => 'tickets/' . $ticket->id . '/formulario.pdf', 'file_type' => 'application/pdf', 'file_size_bytes' => rand(200000, 400000), 'created_at' => $createdAt->copy()->addDays(2)]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    private function createTicket5_ProblemasPago(): void {
        $user = $this->users['Carlos'];
        $createdAt = Carbon::create(2025, 11, 25, 16, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Sistema de pago con tarjeta caído en 12 estaciones de Cochabamba',
            'description' => "Rodrigo,\n\nSistema POS no funciona en Cochabamba desde las 3 PM.\n\n**AFECTADAS:**\n12 estaciones YPFB en todo Cochabamba.\n\n**PROBLEMA:**\n- POS muestra \"Sin conexión\"\n- Solo podemos cobrar en efectivo\n- Clientes molestos, muchos se van\n\n¿Es problema de YPFB o del banco?\n\nCarlos Apaza\nSupervisor Regional CBBA",
            'status' => 'pending', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(3),
            'first_response_at' => $createdAt->copy()->addHours(1), 'resolved_at' => null, 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Carlos,\n\nEs problema del banco, no nuestro. Confirmé con IT que nuestros servidores están bien.\n\nBanco Unión tiene falla regional. Esperan resolver en 2 horas.\n\nMientras tanto:\n1. Acepta solo efectivo\n2. Pon cartel visible\n3. Ofrece ubicación del cajero más cercano a clientes\n\nTe aviso cuando se restablezca.", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "OK entendido. Ya puse carteles. ¿Alguna novedad del banco?", 'created_at' => $createdAt->copy()->addHours(2)]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
