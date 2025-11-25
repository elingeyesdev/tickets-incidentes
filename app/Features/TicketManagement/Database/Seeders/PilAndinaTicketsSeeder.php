<?php

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
 * PIL Andina Tickets Seeder (Manufacturing)
 *
 * Crea tickets realistas para PIL Andina como empresa de MANUFACTURA con:
 * - Empleados/supervisores que reportan problemas de producción
 * - Tickets en diferentes estados (open, pending, resolved, closed)
 * - Respuestas entre supervisores y coordinadores técnicos
 * - Attachments (reportes, fotos de daños, etc)
 *
 * Escenarios simulados:
 * - Problemas con equipos (máquinas, refrigeradores)
 * - Retrasos en producción
 * - Problemas de calidad
 * - Supply chain (proveedores, materias primas)
 * - Seguridad industrial
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
        $this->command->info('🏭 Creando tickets realistas para PIL Andina (Manufacturing)...');

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

        // Create internal users (supervisors, coordinators)
        $this->createUsers();

        // Create tickets with manufacturing scenarios
        $this->createTickets();

        $this->command->info('✅ Seeder de tickets PIL Andina completado!');
    }

    private function loadCategories(): void
    {
        $categories = Category::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        // PIL Andina es manufacturing, usa categorías de esa industria
        $this->categories = [
            'equipment_issue' => $categories->firstWhere('name', 'Equipment Issue'),
            'production_delay' => $categories->firstWhere('name', 'Production Delay'),
            'quality_problem' => $categories->firstWhere('name', 'Quality Problem'),
            'supply_chain' => $categories->firstWhere('name', 'Supply Chain'),
            'safety_concern' => $categories->firstWhere('name', 'Safety Concern'),
        ];
    }

    private function loadAgents(): void
    {
        // Los agentes son coordinadores técnicos/supervisores de turno
        $this->agents = [
            'maria' => User::where('email', 'maria.condori@pilandina.com.bo')->first(),
            'roberto' => User::where('email', 'roberto.flores@pilandina.com.bo')->first(),
        ];
    }

    private function createUsers(): void
    {
        $usersData = [
            [
                'first_name' => 'Diego',
                'last_name' => 'Huanca',
                'email' => 'diego.huanca.supervisor@gmail.com',
                'role' => 'Supervisor Línea de Pasteurización',
            ],
            [
                'first_name' => 'Carmen',
                'last_name' => 'López',
                'email' => 'carmen.lopez.control.calidad@gmail.com',
                'role' => 'Jefe Control de Calidad',
            ],
            [
                'first_name' => 'Fernando',
                'last_name' => 'Quispe',
                'email' => 'fernando.quispe.mantenimiento@gmail.com',
                'role' => 'Coordinador Mantenimiento',
            ],
            [
                'first_name' => 'Leticia',
                'last_name' => 'Morales',
                'email' => 'leticia.morales.almacen@gmail.com',
                'role' => 'Responsable Almacén Materias Primas',
            ],
            [
                'first_name' => 'Marcos',
                'last_name' => 'Vargas',
                'email' => 'marcos.vargas.produccion@gmail.com',
                'role' => 'Supervisor Turno Noche',
            ],
            [
                'first_name' => 'Patricia',
                'last_name' => 'Gutiérrez',
                'email' => 'patricia.gutierrez.seguridad@gmail.com',
                'role' => 'Oficial Seguridad Industrial',
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
            $this->command->info("  ✓ Usuario creado: {$email} ({$userData['role']})");
        }
    }

    private function createTickets(): void
    {
        // Ticket 1: CLOSED - Máquina pasteurizadora dañada (resuelto)
        $this->createTicket1Closed();

        // Ticket 2: RESOLVED - Retraso en producción por personal
        $this->createTicket2Resolved();

        // Ticket 3: PENDING - Lotes con bajo contenido de grasa
        $this->createTicket3Pending();

        // Ticket 4: PENDING - Proveedor de envases retrasado
        $this->createTicket4Pending();

        // Ticket 5: OPEN - Temperatura anómala en refrigerador
        $this->createTicket5Open();

        // Ticket 6: CLOSED - Fuga en sistema de bombeo
        $this->createTicket6Closed();

        // Ticket 7: RESOLVED - Falla de enfriamiento en turno noche
        $this->createTicket7Resolved();

        // Ticket 8: PENDING - Yogur con sabor extraño en lote
        $this->createTicket8Pending();

        // Ticket 9: CLOSED - Incidente de seguridad en área de frío
        $this->createTicket9Closed();

        // Ticket 10: OPEN - Repuesto de válvula urgente
        $this->createTicket10Open();

        // Ticket 11: PENDING - Problema con sistema HVAC
        $this->createTicket11Pending();

        // Ticket 12: RESOLVED - Auditoría de calidad con hallazgos
        $this->createTicket12Resolved();
    }

    // ==================== TICKET 1: CLOSED ====================
    private function createTicket1Closed(): void
    {
        $user = $this->users['Diego'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00001',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipment_issue']->id,
            'title' => 'Máquina pasteurizadora presenta fugas en válvulas',
            'description' => "Buenos días,\n\nDurante el turno de hoy detecté fugas en las válvulas de la máquina pasteurizadora PLT-3000. El producto se está perdiendo y hay riesgo de contaminación cruzada.\n\nLa máquina está parcialmente operativa pero necesita reparación urgente. He parado la línea como medida preventiva.\n\n¿Pueden contactar al servicio técnico?",
            'status' => 'closed',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(13),
            'first_response_at' => now()->subDays(15)->addHours(1),
            'resolved_at' => now()->subDays(14),
            'closed_at' => now()->subDays(13),
        ]);

        // Response 1: Agent acknowledges
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Diego, gracias por el reporte inmediato.\n\nHe contactado al proveedor de mantenimiento Industrias TecniLar. Llegan mañana a las 8:00 AM con los repuestos necesarios para reemplazar las válvulas dañadas.\n\nMientras tanto, mantén la línea parada. Coordina con el turno de noche para aprovechar el tiempo muerto.",
            'created_at' => now()->subDays(15)->addHours(1),
            'updated_at' => now()->subDays(15)->addHours(1),
        ]);

        // Response 2: Technician confirms repair
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Actualización: El equipo técnico de TecniLar completó la reparación exitosamente.\n\n✓ Reemplazadas 4 válvulas de presión\n✓ Pruebas de presión realizadas correctamente\n✓ Máquina calibrada y lista para operación\n\nLa línea PLT-3000 puede reanudarse operaciones. Favor coordinar con producción.",
            'created_at' => now()->subDays(14)->addHours(10),
            'updated_at' => now()->subDays(14)->addHours(10),
        ]);

        // Response 3: User confirms
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Perfecto María. He verificado personalmente que la máquina está operativa. Reiniciamos producción a las 14:00. Gracias por la gestión rápida.",
            'created_at' => now()->subDays(13)->addHours(12),
            'updated_at' => now()->subDays(13)->addHours(12),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 2: RESOLVED ====================
    private function createTicket2Resolved(): void
    {
        $user = $this->users['Marcos'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00002',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['production_delay']->id,
            'title' => 'Retraso en producción - Falta de personal en turno noche',
            'description' => "Roberto,\n\nHoy en el turno de noche llegaron solo 3 de 8 operadores previstos. Dos llamaron tarde diciendo que estaban enfermos y no confirmaron asistencia.\n\nLa línea de yogur está parada desde las 22:00. Hemos perdido aproximadamente 2 horas de producción.\n\n¿Hay algún procedimiento para estos casos o necesito algo del área de RRHH?",
            'status' => 'resolved',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5)->addHours(8),
            'first_response_at' => now()->subDays(5)->addHours(2),
            'resolved_at' => now()->subDays(5)->addHours(8),
            'closed_at' => null,
        ]);

        // Response 1: Agent provides guidance
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Marcos, esto es importante. Para futuras ocasiones:\n\n1. Contacta inmediatamente a coordinador de turno (número en cartelera)\n2. RRHH puede derivar personal de otras áreas\n3. Documenta ausencias para análisis\n\nEsta vez: He hablado con RRHH. Pueden cubrir con 2 personas del área de empaque mañana. Reinicia la línea cuando sea posible.",
            'created_at' => now()->subDays(5)->addHours(2),
            'updated_at' => now()->subDays(5)->addHours(2),
        ]);

        // Response 2: User confirms action
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Gracias Roberto. He anotado los procedimientos. Logré reanimar la línea a las 23:45 con el personal disponible. La producción se recuperó parcialmente.",
            'created_at' => now()->subDays(5)->addHours(3),
            'updated_at' => now()->subDays(5)->addHours(3),
        ]);

        // Response 3: Agent closes
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Bien gestionado. Escalé el tema a RRHH para implementar protocolo de ausencias de último minuto. Marco como resuelto.",
            'created_at' => now()->subDays(5)->addHours(8),
            'updated_at' => now()->subDays(5)->addHours(8),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 3: PENDING ====================
    private function createTicket3Pending(): void
    {
        $user = $this->users['Carmen'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00003',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['quality_problem']->id,
            'title' => 'Análisis de calidad: Lotes con contenido de grasa por debajo de especificación',
            'description' => "María,\n\nEn el análisis de hoy detecté que 3 lotes de leche fresca (códigos LF-2025-0145, LF-2025-0146, LF-2025-0147) tienen contenido de grasa de 3.1% cuando la especificación requiere mínimo 3.6%.\n\nLos lotes fueron producidos ayer entre 14:00 y 16:00 en la línea PLT-2000.\n\nAdjunto reporte de laboratorio completo. ¿Es rechazable?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(1),
            'first_response_at' => now()->subDays(2)->addHours(3),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Attachment: Quality report
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'reporte_analisis_grasa_2025-11-24.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/reporte_analisis_grasa.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 234567,
            'created_at' => now()->subDays(2),
        ]);

        // Response 1: Agent asks for investigation
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Carmen, gracias por el reporte detallado.\n\nPor desviación de 0.5%, estos lotes son RECHAZABLES según norma técnica.\n\nEstoy investigando qué pasó en la línea PLT-2000 entre 14:00-16:00 ayer:\n- Verificación de calibración de sensores\n- Revisión de temperatura de pasteurización\n- Análisis de leche cruda entrante\n\nTe reporto en 2 horas con hallazgos.",
            'created_at' => now()->subDays(2)->addHours(3),
            'updated_at' => now()->subDays(2)->addHours(3),
        ]);

        // Response 2: Agent provides findings
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Hallazgos:\n\n1. El proveedor de leche cruda (Ganadería \"Los Andes\") entregó leche con 3.2% grasa ese día\n2. La línea está correctamente calibrada\n3. La desviación viene de la materia prima\n\nAcciones:\n- Rechazar los 3 lotes\n- Contactar al proveedor para análisis\n- Solicitar certificado de análisis previo a entregas futuras\n\n¿Apruebas rechazo de lotes?",
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 4: PENDING ====================
    private function createTicket4Pending(): void
    {
        $user = $this->users['Leticia'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00004',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['supply_chain']->id,
            'title' => 'Proveedor de envases de tetra pak no entrega en tiempo acordado',
            'description' => "Roberto,\n\nEl proveedor Envases Plus debía entregar 50,000 unidades de envases tetra pak 1L para yogur hoy 25 de noviembre.\n\nHastalas 17:00 aún no llega el envío. Sin estos envases tendremos que parar la línea de yogur mañana.\n\nLlamé al proveedor y dicen que estiman llegada para mañana 10:00 AM, pero esto va a afectar la producción planificada.\n\n¿Hay algún acuerdo de penalización por retraso o qué acciones tomar?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(18),
            'updated_at' => now()->subHours(5),
            'first_response_at' => now()->subHours(16),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Response 1: Agent investigates
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Leticia,\n\nHe verificado el contrato con Envases Plus. Hay cláusula de entrega garantizada con penalización de 0.5% del valor del pedido por cada día de retraso.\n\nEste retraso de 1 día = penalización de Bs. 850 aproximadamente.\n\nYa envié comunicación formal al proveedor citando cláusula y notificándoles de la penalización.",
            'created_at' => now()->subHours(16),
            'updated_at' => now()->subHours(16),
        ]);

        // Response 2: Agent provides alternative
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Alternativa mientras tanto:\n\nHe contactado a Envases Industriales Bolivia (proveedor backup). Pueden entregar 30,000 unidades mañana 9:00 AM para cubrir demanda crítica.\n\nEsto nos permite mantener la línea de yogur operativa sin paros.\n\nAutoriza esta compra emergente para no perder producción?",
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 5: OPEN ====================
    private function createTicket5Open(): void
    {
        $user = $this->users['Fernando'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00005',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipment_issue']->id,
            'title' => 'Alarma de temperatura anómala en refrigerador almacén PLT-REF-04',
            'description' => "Equipo de coordinación,\n\nA las 06:30 AM activó alarma en refrigerador PLT-REF-04 del almacén de productos terminados.\n\nTemperatura interna: 8°C (rango normal: 2-4°C)\nEstatus: Alarma activa, desconocemos causa\n\nProducto en riesgo: 2,000L de leche fresca (producción de ayer)\n\nNecesito diagnóstico urgente. ¿Es problema del compresor o del termostato?",
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
        $user = $this->users['Diego'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00006',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipment_issue']->id,
            'title' => 'Fuga de producto en sistema de bombeo línea PLT-2000',
            'description' => "Equipo de mantenimiento,\n\nEn la línea PLT-2000 detecté fuga de leche pasteurizada en la conexión de la bomba hacia el enfriador.\n\nLa pérdida es aproximadamente 50L/hora. He reducido velocidad de la línea para minimizar pérdidas.\n\nAdjunto foto del área con fuga.\n\n¿Es reparable en sitio o necesita cambio de componente?",
            'status' => 'closed',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(6),
            'first_response_at' => now()->subDays(8)->addHours(1),
            'resolved_at' => now()->subDays(7),
            'closed_at' => now()->subDays(6),
        ]);

        // Attachment: Photo of leak
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'fuga_bomba_plt2000.jpg',
            'file_path' => 'tickets/' . $ticket->id . '/fuga_bomba.jpg',
            'file_type' => 'image/jpeg',
            'file_size_bytes' => 567890,
            'created_at' => now()->subDays(8),
        ]);

        // Response 1: Agent diagnoses
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Diego, he revisado la foto.\n\nLa fuga es en la junta de la conexión. Necesita reemplazo de O-ring y sellos.\n\nEs reparable en sitio: ~20 minutos de trabajo. He desprogramado la línea PLT-2000 para mañana 08:00-09:00 AM.\n\nCoordin con turno para parada programada.",
            'created_at' => now()->subDays(8)->addHours(1),
            'updated_at' => now()->subDays(8)->addHours(1),
        ]);

        // Response 2: Repair completed
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Reparación completada exitosamente:\n\n✓ Reemplazados O-rings y sellos de la junta\n✓ Sistema presurizado y probado\n✓ Cero fugas detectadas\n✓ Línea PLT-2000 operativa\n\nTiempo de reparación: 18 minutos (eficiente).\n\nMarco como cerrado.",
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ]);

        // Response 3: User confirms
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Verificado personalmente. La línea está funcionando perfectamente sin fugas. Excelente trabajo del equipo de mantenimiento.",
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 7: RESOLVED ====================
    private function createTicket7Resolved(): void
    {
        $user = $this->users['Marcos'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00007',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['production_delay']->id,
            'title' => 'Sistema de enfriamiento falla en turno noche - Línea PLT-3000 sin control de temperatura',
            'description' => "Roberto,\n\nEl sistema de enfriamiento de la línea PLT-3000 falló durante el turno noche (23:30).\n\nTemperatura del producto subió de 4°C a 18°C en 45 minutos.\n\nPause la línea como medida preventiva. El producto en proceso podría no ser recuperable.\n\n¿Cuál es el status del compresor? ¿Hay repuesto disponible?",
            'status' => 'resolved',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(3),
            'first_response_at' => now()->subDays(4)->addHours(2),
            'resolved_at' => now()->subDays(3),
            'closed_at' => null,
        ]);

        // Response 1: Emergency response
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Marcos, activé protocolo de emergencia.\n\nDiagnóstico preliminar: Compresor comprimidor falló completamente (error sensor de presión).\n\nAcciones:\n- Producto en línea: RECHAZABLE por temperatura\n- Compresor de repuesto: En almacén disponible\n- Tiempo estimado de cambio: 2 horas\n\nAutoriza descargar la línea y proceder con cambio?",
            'created_at' => now()->subDays(4)->addHours(2),
            'updated_at' => now()->subDays(4)->addHours(2),
        ]);

        // Response 2: Procedure completed
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Cambio de compresor completado:\n\n✓ Compresor defectuoso desmontado\n✓ Compresor de repuesto instalado y conectado\n✓ Sistema presurizado y calibrado\n✓ Pruebas de temperatura: 3.8°C (dentro de especificación)\n\nLínea lista para reanudación.\n\nPérdida de producción: ~6 horas",
            'created_at' => now()->subDays(3)->addHours(8),
            'updated_at' => now()->subDays(3)->addHours(8),
        ]);

        // Response 3: User confirms
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Verificado. El equipo respondió rápidamente en plena madrugada. Rearrancamos la línea a las 06:00 AM.",
            'created_at' => now()->subDays(3)->addHours(9),
            'updated_at' => now()->subDays(3)->addHours(9),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 8: PENDING ====================
    private function createTicket8Pending(): void
    {
        $user = $this->users['Carmen'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00008',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['quality_problem']->id,
            'title' => 'Lote de yogur con sabor anómalo - Investigación requerida',
            'description' => "María,\n\nDurante control organoléptico hoy, 3 muestras del lote YG-2025-0234 (sabor frutilla) presentaron sabor extraño: amargo y astringente.\n\nEl lote: 5,000 unidades producidas ayer 22:00-23:30 en línea PLT-YOGUR.\n\nCausas posibles:\n- Contaminación de ingredientes\n- Error en concentración de cultivo láctico\n- Temperatura de fermentación incorrecta\n\nAdjunto análisis microbiológico preliminar.\n\n¿Este lote es recuperable o debe descartarse completamente?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(12),
            'updated_at' => now()->subHours(3),
            'first_response_at' => now()->subHours(10),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Attachment: Lab analysis
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'analisis_microbiologico_yg0234.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/analisis_micro.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 345678,
            'created_at' => now()->subHours(12),
        ]);

        // Response 1: Initial assessment
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Carmen, he revisado el análisis microbiológico.\n\nRezultados:\n- Recuento total de aerobios: NORMAL\n- Bacterias lácticas: BAJA (3.5M en vez de 8M esperadas)\n- Patógenos: NEGATIVO\n\nCausa probable: Error en inoculación del cultivo madre.\n\nDecisión: El lote NO es pérdida total. Puede ser:",
            'created_at' => now()->subHours(10),
            'updated_at' => now()->subHours(10),
        ]);

        // Response 2: Action plan
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Plan de acción:\n\nOpción 1 (Recomendada): Vender como \"producto promocional\" con 40% descuento. Microbiológicamente seguro, solo tiene defecto sensorial menor.\n\nOpción 2: Descartar 5,000 unidades por pérdida total.\n\n¿Qué autoriza?",
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 9: CLOSED ====================
    private function createTicket9Closed(): void
    {
        $user = $this->users['Patricia'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00009',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['safety_concern']->id,
            'title' => 'Incidente de seguridad: Empleado resbaló en piso mojado área de frío',
            'description' => "Equipo de gestión,\n\nA las 15:30 hubo un incidente en el área de almacén refrigerado.\n\nEmpleado: Juan Condori (operario de almacén)\nIncidente: Resbaló en piso mojado por condensación\nResultado: Caída, golpe en muñeca derecha (sin fractura aparente)\n\nHe documentado el incidente según protocolo y derivé a empleado a médico de empresa.\n\nAcciones inmediatas:\n- Colocadas señales de \"Piso mojado\"\n- Mejora drenaje en área\n\nNecesito reporte formal para expediente y evaluación de causa raíz.",
            'status' => 'closed',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(9),
            'first_response_at' => now()->subDays(10)->addHours(2),
            'resolved_at' => now()->subDays(9)->addHours(14),
            'closed_at' => now()->subDays(9)->addHours(14),
        ]);

        // Attachment: Incident report
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'reporte_incidente_seguridad_2025-11-15.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/reporte_incidente.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 234567,
            'created_at' => now()->subDays(10),
        ]);

        // Response 1: Investigation initiated
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Patricia, gracias por la documentación detallada.\n\nHe completado investigación de causa raíz:\n\nProblema raíz: Sistema de drenaje insuficiente en almacén refrigerado.\n\nAcciones correctivas:\n1. Mantenimiento: Mejorar drenaje (presupuesto: Bs. 2,500)\n2. Capacitación: Protocolos de seguridad en pisos mojados\n3. Equipamiento: Botas antideslizantes para personal de frío\n\nEmpleado: En recuperación, sin secuelas.",
            'created_at' => now()->subDays(10)->addHours(2),
            'updated_at' => now()->subDays(10)->addHours(2),
        ]);

        // Response 2: Actions completed
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Acciones completadas:\n\n✓ Drenaje mejorado en almacén refrigerado\n✓ Sistema de señalización reforzado\n✓ Capacitación de seguridad realizada (14 empleados)\n✓ Botas antideslizantes entregadas\n\nIncidente cerrado. Expediente enviado a RRHH para compensación.",
            'created_at' => now()->subDays(9)->addHours(14),
            'updated_at' => now()->subDays(9)->addHours(14),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 10: OPEN ====================
    private function createTicket10Open(): void
    {
        $user = $this->users['Fernando'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00010',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipment_issue']->id,
            'title' => 'Repuesto urgente: Válvula de alivio de presión línea PLT-2000',
            'description' => "Equipo de mantenimiento,\n\nDurante inspección programada hoy detecté que la válvula de alivio de presión (PRV-1202) en línea PLT-2000 está desgastada.\n\nRiesgo: Pérdida de control de presión que podría dañar equipos o causar accidente.\n\nNecesito:\n- Referencia: PRV-1202 SKF (marca alemana)\n- Cantidad: 1 unidad\n- Prioridad: ALTA\n\n¿Disponibilidad en almacén o necesito solicitar a proveedor?",
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
        $user = $this->users['Marcos'];
        $agent = $this->agents['maria'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00011',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['safety_concern']->id,
            'title' => 'Sistema HVAC falla - Temperatura en área de producción llega a 35°C',
            'description' => "María,\n\nDurante turno noche el sistema de aire acondicionado (HVAC) de la sala de producción falló.\n\nTemperatura subió a 35°C. Fue una situación incómoda pero no peligrosa (turno de noche con menos carga térmica).\n\nSin embargo, si esto ocurre en turno día (máxima producción) sería insostenible para:\n- Personal (riesgo de golpe de calor)\n- Producto (especialmente yogur que necesita frío)\n\n¿Qué urgencia para reparación del HVAC?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(2),
            'first_response_at' => now()->subHours(5),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Response 1: Priority assessment
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Marcos, es CRÍTICA.\n\nRiesgo:\n- Salud laboral (temperatura > 32°C causa estrés térmico)\n- Seguridad alimentaria (yogur requiere control de temperatura)\n- Continuidad operativa\n\nTengo 2 opciones:\n1. Reparación HVAC existente: 3-4 días\n2. Arrendar unidad mobile: 1 día (costo: Bs. 800/día)\n\nRecomiendo opción 2 mientras reparamos la principal. ¿Aprobado?",
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        // Response 2: Interim solution
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Acción tomada:\n\nYa contraté unidad mobile HVAC que llega mañana 08:00 AM.\n\nParalelo: Técnico especializado comenzará reparación de sistema principal mañana.\n\nEstimado: Sistema principal listo dentro de 3 días.",
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 12: RESOLVED ====================
    private function createTicket12Resolved(): void
    {
        $user = $this->users['Carmen'];
        $agent = $this->agents['roberto'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00012',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['quality_problem']->id,
            'title' => 'Auditoría de calidad - Hallazgos para seguimiento',
            'description' => "Roberto,\n\nCompletamos auditoría interna de calidad con los siguientes hallazgos:\n\nDEFICIENCIAS (requieren acción):\n1. Registros de temperatura incompletos en línea PLT-3000\n2. Muestras de validación no documentadas apropiadamente\n3. Capacitación de personal de calidad vencida\n\nPUNTOS FUERTES:\n- Protocolo de limpieza excelente\n- Documentación microbiológica completa\n- Trazabilidad de lotes perfecta\n\nAdjunto informe detallado.\n\n¿Cuando podemos definir plan de acción para deficiencias?",
            'status' => 'resolved',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subHours(8),
            'first_response_at' => now()->subDays(1)->addHours(4),
            'resolved_at' => now()->subHours(8),
            'closed_at' => null,
        ]);

        // Attachment: Audit report
        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => null,
            'uploaded_by_user_id' => $user->id,
            'file_name' => 'informe_auditoria_calidad_2025-11.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/informe_auditoria.pdf',
            'file_type' => 'application/pdf',
            'file_size_bytes' => 567890,
            'created_at' => now()->subDays(1),
        ]);

        // Response 1: Action plan
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Carmen, gracias por la auditoría completa.\n\nHe preparado plan de acción para las 3 deficiencias:\n\n1. Registros PLT-3000: Implementar sistema digital de logging\n   - Plazo: 2 semanas\n   - Responsable: Fernando (Mantenimiento)\n\n2. Muestras de validación: Capacitación del equipo\n   - Plazo: 1 semana\n   - Responsable: Tú (Carmen)\n\n3. Capacitación vencida: Programar cursos\n   - Plazo: 3 semanas\n   - Responsable: RRHH + Tú\n\n¿Apruebas este timeline?",
            'created_at' => now()->subDays(1)->addHours(4),
            'updated_at' => now()->subDays(1)->addHours(4),
        ]);

        // Response 2: User confirms
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
            'author_type' => 'user',
            'content' => "Perfecto Roberto. El timeline es realista. Me comprometo a cumplir los puntos que me corresponden.\n\nPropongo seguimiento mensual con auditorías internas cada trimestre.",
            'created_at' => now()->subDays(1)->addHours(6),
            'updated_at' => now()->subDays(1)->addHours(6),
        ]);

        // Response 3: Confirmation
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Excelente propuesta Carmen. Auditorías trimestrales mejora continuidad.\n\nMarco este ticket como resuelto. El plan de acción está en movimiento.",
            'created_at' => now()->subHours(8),
            'updated_at' => now()->subHours(8),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code}");
    }
}
