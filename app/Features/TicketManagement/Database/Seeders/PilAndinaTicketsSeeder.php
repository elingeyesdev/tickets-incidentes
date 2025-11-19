todos<?php

namespace App\Features\TicketManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * PIL Andina Tickets Seeder
 *
 * Crea tickets realistas para PIL Andina con:
 * - Usuarios con @gmail.com que crean tickets
 * - Tickets en diferentes estados (open, pending, resolved, closed)
 * - Respuestas entre usuarios y agentes
 * - Attachments simulados
 *
 * Escenarios simulados:
 * - Problemas con productos (calidad, vencimiento, empaque)
 * - Consultas sobre pedidos y distribución
 * - Problemas técnicos con el sistema
 * - Facturación y pagos
 */
class PilAndinaTicketsSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';

    private Company $company;
    private array $categories;
    private array $agents;
    private array $users = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎫 Creando tickets realistas para PIL Andina...');

        // Find PIL Andina company
        $this->company = Company::where('name', 'PIL Andina S.A.')->first();

        if (!$this->company) {
            $this->command->error('❌ PIL Andina S.A. no encontrada. Ejecuta RealBolivianCompaniesSeeder primero.');
            return;
        }

        // Get categories
        $this->loadCategories();

        if (empty($this->categories)) {
            $this->command->error('❌ No hay categorías disponibles. Ejecuta DefaultCategoriesSeeder primero.');
            return;
        }

        // Get agents
        $this->loadAgents();

        // Create users with @gmail.com
        $this->createUsers();

        // Create tickets with different scenarios
        $this->createTickets();

        $this->command->info('✅ Seeder de tickets PIL Andina completado!');
    }

    private function loadCategories(): void
    {
        $categories = Category::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        $this->categories = [
            'soporte_tecnico' => $categories->firstWhere('name', 'Soporte Técnico'),
            'facturacion' => $categories->firstWhere('name', 'Facturación'),
            'cuenta' => $categories->firstWhere('name', 'Cuenta y Perfil'),
            'reportes' => $categories->firstWhere('name', 'Reportes y Analíticas'),
            'general' => $categories->firstWhere('name', 'General'),
        ];
    }

    private function loadAgents(): void
    {
        $this->agents = [
            'maria' => User::where('email', 'maria.condori@pilandina.com.bo')->first(),
            'roberto' => User::where('email', 'roberto.flores@pilandina.com.bo')->first(),
        ];
    }

    private function createUsers(): void
    {
        $usersData = [
            [
                'first_name' => 'Carlos',
                'last_name' => 'Mamani',
                'email' => 'carlos.mamani.distribuidor@gmail.com',
                'business' => 'Distribuidora La Esperanza',
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'López',
                'email' => 'ana.lopez.ventas@gmail.com',
                'business' => 'Supermercado El Ahorro',
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Quispe',
                'email' => 'pedro.quispe.tienda@gmail.com',
                'business' => 'Tienda Don Pedro',
            ],
            [
                'first_name' => 'Rosa',
                'last_name' => 'Fernández',
                'email' => 'rosa.fernandez.minimarket@gmail.com',
                'business' => 'Minimarket Rosita',
            ],
            [
                'first_name' => 'Luis',
                'last_name' => 'Torrez',
                'email' => 'luis.torrez.distribuciones@gmail.com',
                'business' => 'Distribuciones LT',
            ],
            [
                'first_name' => 'María',
                'last_name' => 'Gutiérrez',
                'email' => 'maria.gutierrez.abarrotes@gmail.com',
                'business' => 'Abarrotes María',
            ],
            [
                'first_name' => 'Jorge',
                'last_name' => 'Vargas',
                'email' => 'jorge.vargas.comercial@gmail.com',
                'business' => 'Comercial Vargas',
            ],
            [
                'first_name' => 'Silvia',
                'last_name' => 'Mendoza',
                'email' => 'silvia.mendoza.lacteos@gmail.com',
                'business' => 'Lácteos del Valle',
            ],
        ];

        foreach ($usersData as $userData) {
            $email = $userData['email'];

            // Check if user already exists
            $user = User::where('email', $email)->first();

            if ($user) {
                $this->command->warn("⚠ Usuario ya existe: {$email}");
                $this->users[$userData['first_name']] = $user;
                continue;
            }

            // Create user
            $user = User::create([
                'user_code' => 'USR-' . strtoupper(Str::random(8)),
                'email' => $email,
                'password_hash' => Hash::make(self::PASSWORD),
                'email_verified' => true,
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE,
                'auth_provider' => 'local',
                'terms_accepted' => true,
                'terms_accepted_at' => now()->subDays(rand(30, 180)),
                'terms_version' => 'v2.1',
                'onboarding_completed_at' => now()->subDays(rand(30, 180)),
            ]);

            $user->profile()->create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'phone_number' => '+591' . rand(70000000, 79999999),
                'theme' => 'light',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
            ]);

            // Assign USER role for PIL Andina
            UserRole::create([
                'user_id' => $user->id,
                'role_code' => 'USER',
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            $this->users[$userData['first_name']] = $user;
            $this->command->info("  ✓ Usuario creado: {$email} ({$userData['business']})");
        }
    }

    private function createTickets(): void
    {
        // Ticket 1: CLOSED - Problema con producto vencido (resuelto satisfactoriamente)
        $this->createTicket1Closed();

        // Ticket 2: RESOLVED - Consulta sobre pedido retrasado
        $this->createTicket2Resolved();

        // Ticket 3: PENDING - Error en facturación (en proceso)
        $this->createTicket3Pending();

        // Ticket 4: PENDING - Problema con el sistema de pedidos
        $this->createTicket4Pending();

        // Ticket 5: OPEN - Nueva consulta sobre productos
        $this->createTicket5Open();

        // Ticket 6: CLOSED - Problema con empaque dañado
        $this->createTicket6Closed();

        // Ticket 7: RESOLVED - Consulta sobre descuentos
        $this->createTicket7Resolved();

        // Ticket 8: PENDING - Error al exportar reportes
        $this->createTicket8Pending();

        // Ticket 9: CLOSED - Cambio de datos de facturación
        $this->createTicket9Closed();

        // Ticket 10: OPEN - Consulta sobre nuevos productos
        $this->createTicket10Open();

        // Ticket 11: PENDING - Problema con entrega de pedido
        $this->createTicket11Pending();

        // Ticket 12: RESOLVED - Consulta sobre fechas de vencimiento
        $this->createTicket12Resolved();
    }

    // ==================== TICKET 1: CLOSED ====================
    private function createTicket1Closed(): void
    {
        $user = $this->users['Carlos'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00001',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['general']->id,
            'title' => 'Producto con fecha de vencimiento muy cercana',
            'description' => "Hola, recibí un lote de yogur PIL de 1 litro sabor frutilla (Lote: 25A1045) y la fecha de vencimiento es en 3 días. Esto me preocupa porque mis clientes no van a querer comprar productos tan cerca del vencimiento.\n\n¿Es normal recibir productos con tan poco tiempo? ¿Podrían hacer un cambio del lote?",
            'status' => 'closed',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(13),
            'first_response_at' => now()->subDays(15)->addHours(2),
            'resolved_at' => now()->subDays(14),
            'closed_at' => now()->subDays(13),
        ]);

        // Response 1: Agent acknowledges
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimado Carlos, gracias por contactarnos.\n\nEntiendo su preocupación sobre la fecha de vencimiento del lote 25A1045. Déjeme verificar la información del envío y coordinar con el área de logística para solucionar este inconveniente.\n\n¿Podría proporcionarme el número de su pedido o factura para hacer el seguimiento?",
            'created_at' => now()->subDays(15)->addHours(2),
            'updated_at' => now()->subDays(15)->addHours(2),
        ]);

        // Response 2: User provides info
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Claro María, el número de pedido es PED-2025-00156 y la factura es FAC-000789. Recibí el envío ayer por la mañana.",
            'created_at' => now()->subDays(15)->addHours(4),
            'updated_at' => now()->subDays(15)->addHours(4),
        ]);

        // Response 3: Agent confirms solution
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Gracias Carlos. He verificado su pedido y efectivamente hubo un error en el despacho de ese lote.\n\nYa coordiné con logística y mañana mismo le estaremos enviando un lote nuevo con fecha de vencimiento de 30 días. El envío no tiene costo adicional y pueden conservar el lote anterior para venta rápida o devolverlo si lo prefieren.\n\nDisculpe las molestias ocasionadas.",
            'created_at' => now()->subDays(14)->addHours(10),
            'updated_at' => now()->subDays(14)->addHours(10),
        ]);

        // Response 4: User confirms satisfaction
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Perfecto María, muchas gracias por la solución rápida. Recibiré el nuevo lote mañana. Pueden cerrar el ticket.",
            'created_at' => now()->subDays(13)->addHours(8),
            'updated_at' => now()->subDays(13)->addHours(8),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 2: RESOLVED ====================
    private function createTicket2Resolved(): void
    {
        $user = $this->users['Ana'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00002',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['general']->id,
            'title' => 'Pedido retrasado - Urgente para promoción',
            'description' => "Buenos días,\n\nTengo un pedido programado para el día de hoy (PED-2025-00178) que incluye leche PIL y yogur que necesito urgentemente para una promoción que arranca mañana.\n\nEl pedido debía llegar a las 8:00 AM y ya son las 11:00 AM. ¿Pueden darme información sobre el estado?",
            'status' => 'resolved',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5)->addHours(7),
            'first_response_at' => now()->subDays(5)->addMinutes(30),
            'resolved_at' => now()->subDays(5)->addHours(7),
            'closed_at' => null,
        ]);

        // Response 1: Agent investigates
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimada Ana, disculpe el retraso.\n\nEstoy verificando el estado de su pedido PED-2025-00178 con el área de logística. Le confirmo en los próximos 15 minutos.",
            'created_at' => now()->subDays(5)->addMinutes(30),
            'updated_at' => now()->subDays(5)->addMinutes(30),
        ]);

        // Response 2: Agent provides update
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Ana, he verificado con logística:\n\nEl camión de reparto tuvo un inconveniente mecánico menor en ruta, pero ya está resuelto. Su pedido llegará aproximadamente a las 13:30 PM (en 2 horas).\n\nComo disculpa por el retraso, le estamos agregando 10 litros de leche PIL de cortesía en su próximo pedido.\n\n¿Le parece bien esta solución?",
            'created_at' => now()->subDays(5)->addHours(1),
            'updated_at' => now()->subDays(5)->addHours(1),
        ]);

        // Response 3: User accepts
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Gracias Roberto por el seguimiento. Está bien, esperaré hasta las 13:30. El detalle de la leche de cortesía es muy apreciado.",
            'created_at' => now()->subDays(5)->addHours(1)->addMinutes(15),
            'updated_at' => now()->subDays(5)->addHours(1)->addMinutes(15),
        ]);

        // Response 4: Agent confirms resolution
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Perfecto Ana. He confirmado con el conductor que su pedido está en camino y llegará en el horario indicado.\n\nMarco este ticket como resuelto. Si el pedido no llega o tiene algún problema, no dude en reabrir el ticket o contactarnos.",
            'created_at' => now()->subDays(5)->addHours(7),
            'updated_at' => now()->subDays(5)->addHours(7),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 3: PENDING ====================
    private function createTicket3Pending(): void
    {
        $user = $this->users['Pedro'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00003',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id,
            'title' => 'Error en factura - Monto duplicado',
            'description' => "Hola,\n\nRevisando mi factura FAC-001234 del mes pasado, noto que me están cobrando dos veces el mismo pedido PED-2025-00145.\n\nEl pedido fue por 50 unidades de leche PIL de 1L, pero en la factura aparece duplicado (100 unidades en total). Adjunto captura de pantalla de mi pedido y la factura.\n\n¿Pueden revisar y corregir esto?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(1),
            'first_response_at' => now()->subDays(2)->addHours(4),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Attachment: Screenshot of invoice
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'factura_duplicada_captura.png',
            'file_path' => 'tickets/' . $ticket->id . '/factura_duplicada_captura.png',
            'file_type' => 'image/png',
            'file_size_bytes' => 234567,
            'created_at' => now()->subDays(2),
        ]);

        // Response 1: Agent acknowledges
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimado Pedro, gracias por reportar este problema.\n\nHe recibido su captura y efectivamente veo que hay una inconsistencia. Estoy derivando este caso al área de facturación para que revisen y emitan una nota de crédito si corresponde.\n\nLe responderé en máximo 24 horas con la solución.",
            'created_at' => now()->subDays(2)->addHours(4),
            'updated_at' => now()->subDays(2)->addHours(4),
        ]);

        // Response 2: Agent provides update
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Pedro, el área de facturación ha confirmado el error. Efectivamente hubo una duplicación en el sistema.\n\nYa están generando la nota de crédito NC-000456 por el monto duplicado (Bs. 250). La nota de crédito estará disponible mañana y se aplicará automáticamente a su próxima factura.\n\n¿Está de acuerdo con esta solución?",
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 4: PENDING ====================
    private function createTicket4Pending(): void
    {
        $user = $this->users['Rosa'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00004',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['soporte_tecnico']->id,
            'title' => 'No puedo realizar pedidos en el sistema',
            'description' => "Buenas tardes,\n\nDesde ayer estoy intentando realizar un pedido a través del portal web pero me aparece un error cuando intento confirmar:\n\n\"Error: No se pudo procesar su pedido. Intente nuevamente más tarde.\"\n\nYa intenté desde dos navegadores diferentes (Chrome y Firefox) y el error persiste. ¿Hay algún problema con el sistema?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(18),
            'updated_at' => now()->subHours(5),
            'first_response_at' => now()->subHours(16),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Response 1: Agent asks for details
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimada Rosa, lamento este inconveniente.\n\nPara ayudarla mejor, necesito algunos detalles:\n\n1. ¿En qué paso del proceso aparece el error? (Al agregar productos, al confirmar, al pagar?)\n2. ¿Podría tomar una captura de pantalla del error?\n3. ¿Cuál es su usuario en el portal?\n\nMientras tanto, verificaré si hay algún problema reportado en el sistema.",
            'created_at' => now()->subHours(16),
            'updated_at' => now()->subHours(16),
        ]);

        // Response 2: User provides details
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Gracias Roberto. Le respondo:\n\n1. El error aparece cuando hago clic en 'Confirmar Pedido' después de revisar el resumen.\n2. Adjunto captura del error.\n3. Mi usuario es: rosa.minimarket\n\nEl pedido que quiero hacer incluye 30 litros de leche PIL y 20 yogures.",
            'created_at' => now()->subHours(15),
            'updated_at' => now()->subHours(15),
        ]);

        // Attachment: Error screenshot
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'error_sistema_pedidos.png',
            'file_path' => 'tickets/' . $ticket->id . '/error_sistema_pedidos.png',
            'file_type' => 'image/png',
            'file_size_bytes' => 156789,
            'created_at' => now()->subHours(15),
        ]);

        // Response 3: Agent investigating
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Gracias por la información Rosa.\n\nHe verificado su cuenta y encontré el problema: hay un límite de crédito pendiente de actualizar en su perfil que está bloqueando pedidos nuevos.\n\nEstoy escalando esto al área de créditos para que actualicen su límite. Le confirmo la solución en las próximas horas.",
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 5: OPEN ====================
    private function createTicket5Open(): void
    {
        $user = $this->users['Luis'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00005',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['general']->id,
            'title' => '¿Tienen disponibilidad de leche deslactosada?',
            'description' => "Hola,\n\nVarios de mis clientes me han estado preguntando por leche deslactosada PIL. He visto que lanzaron una nueva línea de productos deslactosados.\n\n¿Cuándo estará disponible para distribuidores? ¿Cuáles son los precios y presentaciones?\n\nEstoy muy interesado en incluirla en mi catálogo.",
            'status' => 'open',
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->command->info("  ✓ Ticket OPEN creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 6: CLOSED ====================
    private function createTicket6Closed(): void
    {
        $user = $this->users['María'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00006',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['general']->id,
            'title' => 'Cajas de yogur llegaron dañadas',
            'description' => "Buenos días,\n\nAcabo de recibir mi pedido PED-2025-00289 y 3 cajas de yogur PIL de frutilla llegaron con el cartón dañado y algunos envases rotos.\n\nAdjunto fotos del daño. ¿Pueden hacer el cambio de estas cajas?",
            'status' => 'closed',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'user',
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(6),
            'first_response_at' => now()->subDays(8)->addHours(1),
            'resolved_at' => now()->subDays(7),
            'closed_at' => now()->subDays(6),
        ]);

        // Attachment: Damaged boxes photos
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'cajas_danadas_foto1.jpg',
            'file_path' => 'tickets/' . $ticket->id . '/cajas_danadas_foto1.jpg',
            'file_type' => 'image/jpeg',
            'file_size_bytes' => 567890,
            'created_at' => now()->subDays(8),
        ]);

        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'cajas_danadas_foto2.jpg',
            'file_path' => 'tickets/' . $ticket->id . '/cajas_danadas_foto2.jpg',
            'file_type' => 'image/jpeg',
            'file_size_bytes' => 523456,
            'created_at' => now()->subDays(8),
        ]);

        // Response 1: Agent apologizes and arranges replacement
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimada María, lamento mucho este inconveniente.\n\nHe visto las fotos y efectivamente el daño es considerable. Esto no debería suceder y vamos a investigar qué pasó en el transporte.\n\nMañana mismo le estaremos enviando 3 cajas nuevas de reemplazo sin costo adicional. Las cajas dañadas pueden conservarlas para productos que aún estén en buen estado o devolverlas cuando llegue el nuevo envío.\n\n¿Le parece bien?",
            'created_at' => now()->subDays(8)->addHours(1),
            'updated_at' => now()->subDays(8)->addHours(1),
        ]);

        // Response 2: User thanks
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Perfecto María, muchas gracias por la solución rápida. Voy a separar los envases que están bien y devolveré los rotos con el conductor mañana.",
            'created_at' => now()->subDays(8)->addHours(2),
            'updated_at' => now()->subDays(8)->addHours(2),
        ]);

        // Response 3: Agent confirms delivery
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "María, confirmo que las 3 cajas de reemplazo fueron entregadas hoy. El conductor también recogió las unidades dañadas.\n\n¿Todo llegó en orden? Si está conforme, marco el ticket como resuelto.",
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ]);

        // Response 4: User confirms
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Sí María, todo perfecto. Las cajas llegaron en excelente estado esta vez. Gracias por la atención, pueden cerrar el ticket.",
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 7: RESOLVED ====================
    private function createTicket7Resolved(): void
    {
        $user = $this->users['Jorge'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00007',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['facturacion']->id,
            'title' => 'Consulta sobre descuentos por volumen',
            'description' => "Buenas tardes,\n\nEstoy interesado en aumentar mi volumen de pedidos mensuales. Actualmente pido alrededor de 500 unidades al mes.\n\n¿Qué descuentos por volumen manejan? ¿A partir de qué cantidad aplican?\n\nMe gustaría conocer las opciones para planificar mejor mis pedidos.",
            'status' => 'resolved',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(3),
            'first_response_at' => now()->subDays(4)->addHours(3),
            'resolved_at' => now()->subDays(3),
            'closed_at' => null,
        ]);

        // Response 1: Agent provides discount info
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimado Jorge, gracias por su interés en aumentar su volumen de compras.\n\nNuestros descuentos por volumen son:\n\n- 500-999 unidades/mes: 3% de descuento\n- 1,000-1,999 unidades/mes: 5% de descuento\n- 2,000-4,999 unidades/mes: 7% de descuento\n- 5,000+ unidades/mes: 10% de descuento (+ beneficios adicionales)\n\nEstos descuentos se aplican sobre el precio de lista y se calculan mensualmente.\n\nSi está interesado en un contrato de volumen, puedo conectarlo con nuestro equipo comercial para negociar condiciones especiales.\n\n¿Le gustaría más información?",
            'created_at' => now()->subDays(4)->addHours(3),
            'updated_at' => now()->subDays(4)->addHours(3),
        ]);

        // Response 2: User asks for more details
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Gracias Roberto por la información. Me interesa mucho el rango de 1,000-1,999 unidades.\n\n¿Los descuentos se aplican automáticamente en el sistema o hay que solicitarlos cada mes?\n\nY sí, me gustaría hablar con el equipo comercial sobre un contrato.",
            'created_at' => now()->subDays(4)->addHours(5),
            'updated_at' => now()->subDays(4)->addHours(5),
        ]);

        // Response 3: Agent explains and connects
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Jorge, los descuentos se aplican automáticamente en el sistema al cierre de mes. Cuando alcanza el volumen correspondiente, el descuento se refleja en su factura mensual.\n\nHe enviado sus datos a nuestro ejecutivo comercial, Carlos Moreno. Él lo contactará en las próximas 24 horas para coordinar una reunión y discutir un posible contrato de volumen.\n\nSu email es: carlos.moreno@pilandina.com.bo\n\n¿Hay algo más en lo que pueda ayudarle?",
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        // Response 4: User satisfied
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Perfecto Roberto, quedo a la espera del contacto de Carlos. Muchas gracias por toda la información y la gestión.",
            'created_at' => now()->subDays(3)->addHours(1),
            'updated_at' => now()->subDays(3)->addHours(1),
        ]);

        // Response 5: Agent marks resolved
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Un gusto ayudarle Jorge. Marco este ticket como resuelto. Si tiene más consultas, no dude en abrir un nuevo ticket o contactarnos directamente.",
            'created_at' => now()->subDays(3)->addHours(2),
            'updated_at' => now()->subDays(3)->addHours(2),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 8: PENDING ====================
    private function createTicket8Pending(): void
    {
        $user = $this->users['Silvia'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00008',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['reportes']->id,
            'title' => 'No se puede exportar reporte de ventas a Excel',
            'description' => "Hola,\n\nEstoy intentando exportar mi reporte de ventas del mes de octubre a Excel desde el portal, pero cuando hago clic en 'Exportar' no pasa nada.\n\nIntentélvarias veces y con diferentes rangos de fechas, pero el problema persiste. Necesito ese reporte para mi contador.\n\n¿Pueden ayudarme?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(12),
            'updated_at' => now()->subHours(3),
            'first_response_at' => now()->subHours(10),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Response 1: Agent asks for details
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimada Silvia, lamento este inconveniente.\n\nPara ayudarla mejor, ¿podría indicarme:\n\n1. ¿Qué navegador está utilizando?\n2. ¿Ve algún mensaje de error, o simplemente no pasa nada?\n3. ¿El botón de exportar se ve deshabilitado o activo?\n\nMientras tanto, voy a verificar si hay algún problema reportado en el sistema de reportes.",
            'created_at' => now()->subHours(10),
            'updated_at' => now()->subHours(10),
        ]);

        // Response 2: User provides info
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Gracias María. Le respondo:\n\n1. Estoy usando Google Chrome (versión más reciente)\n2. No aparece ningún mensaje de error, solo que no descarga nada\n3. El botón se ve activo y cuando hago clic se pone en gris por un segundo, pero luego vuelve a normal y no pasa nada\n\nNecesito urgente ese reporte porque mi contador lo necesita para mañana.",
            'created_at' => now()->subHours(9),
            'updated_at' => now()->subHours(9),
        ]);

        // Response 3: Agent investigating
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Silvia, he reportado esto al equipo técnico. Parece que hay un problema con la generación de reportes de fechas antiguas.\n\nComo solución temporal, ¿podría enviarme por correo el rango de fechas exacto que necesita? Yo puedo generar el reporte manualmente desde el sistema administrativo y enviárselo en las próximas 2 horas.\n\nMientras tanto, el equipo técnico está trabajando en solucionar el problema del portal.",
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 9: CLOSED ====================
    private function createTicket9Closed(): void
    {
        $user = $this->users['Carlos'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00009',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['cuenta']->id,
            'title' => 'Actualización de NIT y razón social',
            'description' => "Buenos días,\n\nNecesito actualizar los datos de facturación de mi cuenta. Cambié mi NIT y razón social.\n\nDatos nuevos:\n- NIT: 1234567890\n- Razón Social: Distribuidora La Esperanza SRL\n\nAdjunto certificado del nuevo NIT.",
            'status' => 'closed',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(9),
            'first_response_at' => now()->subDays(10)->addHours(2),
            'resolved_at' => now()->subDays(9)->addHours(10),
            'closed_at' => now()->subDays(9)->addHours(10),
        ]);

        // Attachment: NIT certificate
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'certificado_nit_nuevo.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/certificado_nit_nuevo.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 345678,
            'created_at' => now()->subDays(10),
        ]);

        // Response 1: Agent confirms receipt
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimado Carlos, he recibido su solicitud y el certificado del nuevo NIT.\n\nEstoy procesando el cambio en el sistema. La actualización estará lista en máximo 24 horas.\n\nLe confirmaré cuando esté completado.",
            'created_at' => now()->subDays(10)->addHours(2),
            'updated_at' => now()->subDays(10)->addHours(2),
        ]);

        // Response 2: Agent confirms completion
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Carlos, los datos de facturación han sido actualizados exitosamente:\n\n✓ NIT: 1234567890\n✓ Razón Social: Distribuidora La Esperanza SRL\n\nA partir de su próxima factura, aparecerán los nuevos datos. Si necesita una factura rectificativa de facturas anteriores, por favor indíqueme los números de factura.\n\nMarco este ticket como resuelto y cerrado.",
            'created_at' => now()->subDays(9)->addHours(10),
            'updated_at' => now()->subDays(9)->addHours(10),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 10: OPEN ====================
    private function createTicket10Open(): void
    {
        $user = $this->users['Ana'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00010',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['general']->id,
            'title' => 'Información sobre quesos PIL - Nuevas variedades',
            'description' => "Hola,\n\nHe visto en redes sociales que PIL lanzó nuevas variedades de quesos (queso andino y queso light).\n\n¿Ya están disponibles para distribuidores? ¿Cuáles son los precios y presentaciones?\n\nMis clientes han estado preguntando por estas novedades.",
            'status' => 'open',
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'created_at' => now()->subMinutes(45),
            'updated_at' => now()->subMinutes(45),
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->command->info("  ✓ Ticket OPEN creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 11: PENDING ====================
    private function createTicket11Pending(): void
    {
        $user = $this->users['Pedro'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00011',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['general']->id,
            'title' => 'Pedido llegó incompleto - Faltan productos',
            'description' => "Buenas tardes,\n\nRecibí hoy mi pedido PED-2025-00312 pero está incompleto. Faltan:\n\n- 10 litros de leche PIL entera\n- 5 kg de queso mozzarella\n\nEn la factura aparecen cobrados pero no vinieron en el envío. El conductor dijo que eso era todo lo que tenía para entregar.\n\n¿Pueden verificar qué pasó y enviar los productos faltantes?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(2),
            'first_response_at' => now()->subHours(5),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Response 1: Agent acknowledges and investigates
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimado Pedro, lamento este problema.\n\nEstoy verificando su pedido PED-2025-00312 con el almacén y logística para entender qué ocurrió.\n\nLe responderé en máximo 1 hora con la solución.",
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        // Response 2: Agent provides solution
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Pedro, he verificado con logística:\n\nEfectivamente hubo un error en el despacho. Los productos faltantes quedaron en el almacén por error del operador.\n\nMañana a primera hora (antes de las 10:00 AM) le estaremos enviando:\n- 10 litros de leche PIL entera\n- 5 kg de queso mozzarella\n\nSin costo adicional de envío. Como disculpa, también le estamos agregando 3 litros de yogur PIL de cortesía.\n\nDisculpe las molestias. ¿Le parece bien esta solución?",
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 12: RESOLVED ====================
    private function createTicket12Resolved(): void
    {
        $user = $this->users['Luis'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00012',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['general']->id,
            'title' => 'Consulta sobre almacenamiento y vida útil de productos',
            'description' => "Hola,\n\nTengo algunas dudas sobre el almacenamiento correcto de los productos PIL:\n\n1. ¿A qué temperatura debo mantener la leche y yogures?\n2. ¿Cuánto tiempo después de abrir un envase es seguro venderlo?\n3. ¿Los quesos también necesitan refrigeración?\n\nQuiero asegurarme de que estoy manejando correctamente los productos para mantener su calidad.",
            'status' => 'resolved',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subHours(8),
            'first_response_at' => now()->subDays(1)->addHours(4),
            'resolved_at' => now()->subHours(8),
            'closed_at' => null,
        ]);

        // Response 1: Agent provides detailed answer
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Estimado Luis, excelentes preguntas. Le proporciono la información detallada:\n\n**1. Temperatura de almacenamiento:**\n- Leche fresca: 2°C a 4°C (refrigeración)\n- Yogur: 2°C a 6°C (refrigeración)\n- Leche larga vida sin abrir: Temperatura ambiente (max 25°C)\n- Leche larga vida abierta: 2°C a 4°C (refrigeración)\n\n**2. Vida útil después de abrir:**\n- Leche fresca: 2-3 días refrigerada\n- Leche larga vida: 3-5 días refrigerada\n- Yogur: Hasta la fecha de vencimiento si se mantiene refrigerado\n\n**3. Quesos:**\nSÍ, todos nuestros quesos requieren refrigeración continua a 2°C-6°C:\n- Queso fresco: Consumir antes de la fecha de vencimiento\n- Queso mozzarella: Mantener en su líquido original\n- Queso maduro: Puede durar más tiempo pero siempre refrigerado\n\n**Recomendaciones adicionales:**\n✓ Nunca romper la cadena de frío\n✓ Verificar sellos y empaques antes de vender\n✓ Rotar productos: PEPS (Primero en Entrar, Primero en Salir)\n✓ Limpiar refrigeradores semanalmente\n\n¿Tiene alguna otra consulta?",
            'created_at' => now()->subDays(1)->addHours(4),
            'updated_at' => now()->subDays(1)->addHours(4),
        ]);

        // Response 2: User thanks and asks follow-up
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Muchas gracias Roberto, información muy clara y útil.\n\nUna última pregunta: ¿Tienen algún manual o guía descargable sobre esto que pueda compartir con mi personal?",
            'created_at' => now()->subDays(1)->addHours(6),
            'updated_at' => now()->subDays(1)->addHours(6),
        ]);

        // Response 3: Agent provides resource
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "¡Por supuesto Luis!\n\nTenemos una \"Guía de Buenas Prácticas de Almacenamiento\" disponible en nuestro portal:\n\n👉 Portal > Recursos > Guías y Manuales > Almacenamiento de Productos\n\nTambién le envío el link directo por correo electrónico. Es un PDF descargable que puede imprimir y compartir con su equipo.\n\nSi necesita capacitación presencial para su personal, también ofrecemos ese servicio. Avíseme si le interesa.\n\nMarco este ticket como resuelto. ¡Que tenga un excelente día!",
            'created_at' => now()->subHours(8),
            'updated_at' => now()->subHours(8),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code}");
    }
}
