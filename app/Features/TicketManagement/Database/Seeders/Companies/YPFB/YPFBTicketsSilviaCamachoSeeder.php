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
 * YPFB Tickets Seeder - Silvia Camacho
 * Temas: Gas domiciliario, clientes residenciales, conexiones nuevas
 */
class YPFBTicketsSilviaCamachoSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'silvia.camacho@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Juana', 'last_name' => 'Velásquez', 'email' => 'juana.velasquez.gas9@gmail.com'],
        ['first_name' => 'Roberto', 'last_name' => 'Limachi', 'email' => 'roberto.limachi.domicilio9@gmail.com'],
        ['first_name' => 'Elena', 'last_name' => 'Condori', 'email' => 'elena.condori.conexion9@gmail.com'],
        ['first_name' => 'Marco', 'last_name' => 'Zenteno', 'email' => 'marco.zenteno.resid9@gmail.com'],
        ['first_name' => 'Rosa', 'last_name' => 'Paco', 'email' => 'rosa.paco.cliente9@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Silvia Camacho...");
        $this->loadCompany();
        if (!$this->company) return;
        $this->loadAgent();
        if (!$this->agent) return;
        if ($this->alreadySeeded()) return;
        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();
        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Silvia Camacho");
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
            'interrupcion' => $cats->firstWhere('name', 'Incidente de Interrupción del Servicio') ?? $cats->first(),
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
        $this->createTicket1_ConexionNueva();
        $this->createTicket2_FacturaAlta();
        $this->createTicket3_FugaGas();
        $this->createTicket4_CambioMedidor();
        $this->createTicket5_CorteServicio();
    }

    private function createTicket1_ConexionNueva(): void {
        $user = $this->users['Elena'];
        $createdAt = Carbon::create(2025, 1, 10, 9, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Solicitud conexión gas domiciliario - Urbanización Los Álamos, Santa Cruz',
            'description' => "Buenos días,\n\nQuiero solicitar conexión de gas natural para mi domicilio.\n\n**DATOS:**\n- Nombre: Elena Condori\n- Dirección: Calle 5, Nro 234, Urbanización Los Álamos\n- Zona: Radial 26, Santa Cruz\n- Teléfono: 78567890\n\n**PREGUNTAS:**\n1. ¿Cuál es el costo de la conexión?\n2. ¿Cuánto demora?\n3. ¿Qué documentos necesito?\n4. ¿Hay red de gas en mi zona?\n\nGracias.\n\nElena Condori",
            'status' => 'closed', 'priority' => 'medium', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(35),
            'first_response_at' => $createdAt->copy()->addHours(4), 'resolved_at' => $createdAt->copy()->addDays(30), 'closed_at' => $createdAt->copy()->addDays(35),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Estimada Elena,\n\n¡Buenas noticias! Su zona sí tiene red de gas.\n\n**INFORMACIÓN:**\n- Costo conexión: Bs. 4,500 (incluye medidor y 10m de tubería interna)\n- Tiempo: 20-25 días hábiles después de aprobación\n- Documentos: CI, título de propiedad o contrato de alquiler, croquis de ubicación\n\n**SIGUIENTE PASO:**\nPresente documentos en oficina YPFB Av. San Martín. Horario: 8:00-16:00.\n\n¿Alguna otra consulta?\n\nSilvia Camacho", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Gracias por la información. ¿Puedo pagar en cuotas?", 'created_at' => $createdAt->copy()->addDays(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Sí, ofrecemos plan de pagos:\n- 6 cuotas sin interés de Bs. 750\n- O 12 cuotas de Bs. 395\n\nCoordine en oficina.", 'created_at' => $createdAt->copy()->addDays(1)->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Ya presenté documentos. Me dieron fecha de instalación: 8 de febrero.", 'created_at' => $createdAt->copy()->addDays(5)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "¡Ya tengo gas! Instalaron ayer. Todo funcionando bien. Gracias!", 'created_at' => $createdAt->copy()->addDays(30)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "¡Qué bueno! Bienvenida al servicio. Cualquier duda, estamos a sus órdenes. Cierro ticket.", 'created_at' => $createdAt->copy()->addDays(30)->addHours(2)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Ene)");
    }

    private function createTicket2_FacturaAlta(): void {
        $user = $this->users['Roberto'];
        $createdAt = Carbon::create(2025, 4, 15, 11, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Factura de abril muy alta - Bs. 280 cuando normalmente pago Bs. 80',
            'description' => "Hola,\n\nMi factura de abril llegó muy alta.\n\n**COMPARACIÓN:**\n- Marzo: Bs. 78\n- Abril: Bs. 280 (¡350% más!)\n\nNo cambié mis hábitos de consumo. Vivo solo y trabajo todo el día. Solo cocino en la noche.\n\n¿Pueden verificar si hay error en la lectura?\n\n**DATOS:**\n- NIS: 45678901\n- Medidor: 7890234\n- Dirección: Av. América 456, Cochabamba\n\nRoberto Limachi",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(12),
            'first_response_at' => $createdAt->copy()->addHours(3), 'resolved_at' => $createdAt->copy()->addDays(8), 'closed_at' => $createdAt->copy()->addDays(12),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Estimado Roberto,\n\nRevisé su cuenta. La lectura de abril fue 1,245 m³. Verificaré si es correcta.\n\nMientras tanto, ¿puede revisar si tiene fuga? Prueba sencilla:\n1. Cierre todas las llaves de gas\n2. Observe el medidor 10 minutos\n3. Si sigue marcando, hay fuga\n\n¿Puede hacer esta prueba hoy?\n\nSilvia", 'created_at' => $createdAt->copy()->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Hice la prueba. El medidor SÍ sigue marcando aunque todo está cerrado. ¿Tengo fuga?", 'created_at' => $createdAt->copy()->addHours(6)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Sí, hay fuga en su instalación interna. Envío técnico mañana entre 9-12 para detectar y reparar (sin costo por ser primera vez).\n\nSobre la factura: Pagará lo consumido pero le haremos plan de pagos si lo necesita.", 'created_at' => $createdAt->copy()->addHours(7)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "OK, espero al técnico mañana. Gracias.", 'created_at' => $createdAt->copy()->addHours(8)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "El técnico encontró la fuga debajo del calefón. Ya reparó. Ahora el medidor no marca cuando cierro todo.", 'created_at' => $createdAt->copy()->addDays(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Perfecto. Le envío plan de pagos para la factura: 4 cuotas de Bs. 70. ¿Le parece bien?", 'created_at' => $createdAt->copy()->addDays(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Sí, acepto el plan. Gracias por la ayuda.", 'created_at' => $createdAt->copy()->addDays(4)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Abr)");
    }

    private function createTicket3_FugaGas(): void {
        $user = $this->users['Juana'];
        $createdAt = Carbon::create(2025, 7, 3, 7, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['interrupcion']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'URGENTE: Olor a gas fuerte en mi cocina - Cerré la llave pero sigue oliendo',
            'description' => "URGENTE!!!\n\nHay olor a gas muy fuerte en mi casa. Cerré la llave del medidor pero sigue oliendo.\n\n**DATOS:**\n- Dirección: Calle Sucre 789, El Alto\n- Teléfono: 76543210\n- NIS: 23456789\n\n¿Pueden venir a revisar? Tengo miedo.\n\nJuana Velásquez",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(3),
            'first_response_at' => $createdAt->copy()->addMinutes(15), 'resolved_at' => $createdAt->copy()->addHours(4), 'closed_at' => $createdAt->copy()->addDays(3),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "¡Juana!\n\n**IMPORTANTE - HAGA ESTO AHORA:**\n1. NO encienda ni apague luces\n2. NO use celular dentro de casa\n3. Abra todas las ventanas\n4. Salga de la casa con su familia\n5. Espere afuera\n\nBrigada de emergencia sale ahora. Llegan en 30 minutos.\n\n¿Está usted sola?", 'created_at' => $createdAt->copy()->addMinutes(15)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Ya salí. Estoy con mis hijos en la vereda. Gracias.", 'created_at' => $createdAt->copy()->addMinutes(25)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Ya llegó la brigada. Están revisando.", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Encontraron el problema: una manguera del calentador estaba rota. Ya repararon. Podemos entrar a la casa.", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Me alegro que se resolvió sin incidentes. Por favor cambie todas las mangueras de más de 5 años. Es por su seguridad. Cierro ticket.", 'created_at' => $createdAt->copy()->addHours(3)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jul)");
    }

    private function createTicket4_CambioMedidor(): void {
        $user = $this->users['Marco'];
        $createdAt = Carbon::create(2025, 10, 12, 14, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['consulta']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Solicito cambio de medidor - El actual tiene 15 años y creo que marca de más',
            'description' => "Buenas tardes,\n\nMi medidor de gas tiene 15 años. Creo que está marcando de más porque mis facturas subieron sin razón.\n\n**DATOS:**\n- NIS: 56789012\n- Dirección: Zona Sur, calle 12, La Paz\n\n¿Cuánto cuesta cambiar el medidor? ¿Se puede verificar si está mal calibrado?\n\nMarco Zenteno",
            'status' => 'resolved', 'priority' => 'low', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(8),
            'first_response_at' => $createdAt->copy()->addHours(5), 'resolved_at' => $createdAt->copy()->addDays(8), 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Estimado Marco,\n\n15 años es mucho. Por norma, medidores deben calibrarse cada 10 años.\n\n**OPCIONES:**\n1. Verificación de calibración: Bs. 150 (si está bien, usted paga; si está mal, nosotros pagamos)\n2. Cambio de medidor: Bs. 800 (modelo nuevo digital)\n\nRecomiendo opción 1 primero. ¿Le agendo visita técnica?\n\nSilvia", 'created_at' => $createdAt->copy()->addHours(5)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Sí, que vengan a verificar. ¿Cuándo pueden?", 'created_at' => $createdAt->copy()->addDays(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Visita programada para el miércoles 16 octubre, entre 9-12. ¿Puede estar alguien en casa?", 'created_at' => $createdAt->copy()->addDays(1)->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Sí, estaré. Gracias.", 'created_at' => $createdAt->copy()->addDays(1)->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Técnico reportó: Medidor tenía error de +8%. Fue reemplazado sin costo para usted. Próxima factura será corregida. Marco resuelto.", 'created_at' => $createdAt->copy()->addDays(6)]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    private function createTicket5_CorteServicio(): void {
        $user = $this->users['Rosa'];
        $createdAt = Carbon::create(2025, 11, 20, 8, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id ?? null, 'area_id' => $this->areas['comercializacion']->id ?? null,
            'title' => 'Me cortaron el gas sin aviso - Dice que debo 3 meses pero yo pagué',
            'description' => "Buenas,\n\nHoy amanecí sin gas. Un técnico vino y cortó diciendo que debo 3 meses.\n\n**PERO YO PAGUÉ:**\n- Septiembre: Pagué el 5 de octubre (tengo comprobante)\n- Octubre: Pagué el 8 de noviembre (tengo comprobante)\n- Noviembre: Aún no llegó la factura\n\n¿Por qué me cortaron? Necesito que reconecten HOY porque tengo niños pequeños y hace frío.\n\n**DATOS:**\n- NIS: 67890123\n- Dirección: Zona 16 de Julio, El Alto\n\nRosa Paco",
            'status' => 'pending', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(3),
            'first_response_at' => $createdAt->copy()->addHours(1), 'resolved_at' => null, 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Estimada Rosa,\n\nLamento mucho la situación. Revisé su cuenta y efectivamente HAY PAGOS REGISTRADOS.\n\nEl problema fue un error en el sistema que no reflejó sus pagos. Es culpa nuestra.\n\n**ACCIÓN INMEDIATA:**\nEnvío técnico para reconectar HOY antes del mediodía. Sin ningún costo para usted.\n\n¿Puede enviarme fotos de sus comprobantes de pago para actualizar el sistema?\n\nSilvia", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Gracias! Adjunto fotos de los comprobantes. Espero al técnico.", 'created_at' => $createdAt->copy()->addHours(2)]);

        // URL placeholder: https://loremflickr.com/640/480/receipt,payment
        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'comprobante_pago_sept.jpg', 'file_path' => 'tickets/' . $ticket->id . '/comprobante_sept.jpg', 'file_type' => 'image/jpeg', 'file_size_bytes' => rand(100000, 300000), 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'comprobante_pago_oct.jpg', 'file_path' => 'tickets/' . $ticket->id . '/comprobante_oct.jpg', 'file_type' => 'image/jpeg', 'file_size_bytes' => rand(100000, 300000), 'created_at' => $createdAt->copy()->addHours(2)]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
