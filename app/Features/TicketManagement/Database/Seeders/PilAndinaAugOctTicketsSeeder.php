<?php

namespace App\Features\TicketManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * PIL Andina Tickets Seeder - August to October 2025
 *
 * Crea tickets históricos distribuidos en el tiempo:
 * - AGOSTO: Mayoría CLOSED (hace 4 meses)
 * - SEPTIEMBRE: Mix CLOSED/RESOLVED (hace 3 meses)
 * - OCTUBRE: Mayoría RESOLVED (hace 2 meses)
 * - NOVIEMBRE-DICIEMBRE: Algunos PENDING/OPEN recientes
 *
 * Total: 35 tickets distribuidos para gráficos realistas
 */
class PilAndinaAugOctTicketsSeeder extends Seeder
{
    private Company $company;
    private array $categories;
    private array $agents;
    private array $users = [];
    private int $ticketCounter = 100; // Empezar en TKT-2025-00100

    public function run(): void
    {
        $this->command->info('🏭 Creando tickets históricos PIL Andina (Ago-Oct 2025)...');

        // Find PIL Andina company
        $this->company = Company::where('name', 'PIL Andina S.A.')->first();

        if (!$this->company) {
            $this->command->error('❌ PIL Andina S.A. no encontrada.');
            return;
        }

        // [IDEMPOTENCY] Verificar si ya fue ejecutado
        $existingCount = Ticket::where('company_id', $this->company->id)
            ->where('ticket_code', 'LIKE', 'TKT-2025-001%')
            ->count();
        
        if ($existingCount >= 35) {
            $this->command->info('[OK] Seeder ya ejecutado. Saltando...');
            return;
        }

        $this->loadCategories();
        $this->loadAgents();
        $this->loadUsers();

        if (empty($this->categories) || empty($this->agents) || empty($this->users)) {
            $this->command->error('❌ Faltan categorías, agentes o usuarios.');
            return;
        }

        // AGOSTO 2025: 12 tickets (10 CLOSED, 2 RESOLVED)
        $this->createAugustTickets();

        // SEPTIEMBRE 2025: 13 tickets (7 CLOSED, 6 RESOLVED)
        $this->createSeptemberTickets();

        // OCTUBRE 2025: 10 tickets (2 CLOSED, 7 RESOLVED, 1 PENDING)
        $this->createOctoberTickets();

        $this->command->info('✅ 35 tickets históricos creados exitosamente!');
    }

    private function loadCategories(): void
    {
        $categories = Category::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        $this->categories = [
            'equipment_issue' => $categories->firstWhere('name', 'Incidente de Producción'),
            'production_delay' => $categories->firstWhere('name', 'Incidente de Producción'),
            'quality_problem' => $categories->firstWhere('name', 'Problema de Calidad del Producto'),
            'supply_chain' => $categories->firstWhere('name', 'Problema de Cadena de Frío/Logística'),
            'safety_concern' => $categories->firstWhere('name', 'Incidente de Seguridad Alimentaria'),
        ];
    }

    private function loadAgents(): void
    {
        $this->agents = [
            'maria' => User::where('email', 'maria.condori@pilandina.com.bo')->first(),
            'roberto' => User::where('email', 'roberto.flores@pilandina.com.bo')->first(),
        ];
    }

    private function loadUsers(): void
    {
        $emails = [
            'diego.huanca.supervisor@gmail.com',
            'carmen.lopez.control.calidad@gmail.com',
            'fernando.quispe.mantenimiento@gmail.com',
            'leticia.morales.almacen@gmail.com',
            'marcos.vargas.produccion@gmail.com',
            'patricia.gutierrez.seguridad@gmail.com',
        ];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $this->users[] = $user;
            }
        }
    }

    private function getTicketCode(): string
    {
        return 'TKT-2025-' . str_pad($this->ticketCounter++, 5, '0', STR_PAD_LEFT);
    }

    // ==================== AGOSTO 2025 ====================
    private function createAugustTickets(): void
    {
        $this->command->info('  📅 Agosto 2025: 12 tickets...');

        // Ticket 1: CLOSED - Inicio de agosto
        $this->createClosedTicket(
            Carbon::create(2025, 8, 2, 9, 30),
            Carbon::create(2025, 8, 3, 16, 0),
            Carbon::create(2025, 8, 4, 10, 0),
            $this->users[0],
            $this->agents['maria'],
            $this->categories['equipment_issue'],
            'Mantenimiento preventivo - Línea PLT-1000',
            "María,\n\nSegún el cronograma de mantenimiento preventivo, la línea PLT-1000 debe recibir servicio esta semana.\n\n¿Coordinamos con producción para programar la parada?",
            3
        );

        // Ticket 2: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 5, 14, 15),
            Carbon::create(2025, 8, 5, 18, 30),
            Carbon::create(2025, 8, 6, 9, 0),
            $this->users[1],
            $this->agents['roberto'],
            $this->categories['quality_problem'],
            'Análisis microbiológico - Lote YG-2025-0089',
            "Roberto,\n\nEl lote YG-2025-0089 requiere análisis microbiológico complementario antes de liberar.\n\nMuestras ya están en laboratorio.",
            2
        );

        // Ticket 3: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 8, 10, 45),
            Carbon::create(2025, 8, 9, 14, 20),
            Carbon::create(2025, 8, 10, 11, 0),
            $this->users[2],
            $this->agents['maria'],
            $this->categories['equipment_issue'],
            'Calibración de sensores de temperatura',
            "Equipo,\n\nLos sensores de temperatura de las cámaras de frío necesitan calibración anual.\n\nAdjunto cronograma propuesto.",
            2
        );

        // Ticket 4: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 12, 8, 0),
            Carbon::create(2025, 8, 13, 10, 30),
            Carbon::create(2025, 8, 14, 15, 0),
            $this->users[3],
            $this->agents['roberto'],
            $this->categories['supply_chain'],
            'Actualización de proveedores certificados',
            "Roberto,\n\nNecesito actualizar la lista de proveedores certificados para materias primas.\n\n¿Hay nuevos proveedores aprobados este trimestre?",
            3
        );

        // Ticket 5: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 15, 13, 20),
            Carbon::create(2025, 8, 16, 9, 45),
            Carbon::create(2025, 8, 17, 14, 0),
            $this->users[4],
            $this->agents['maria'],
            $this->categories['production_delay'],
            'Optimización de línea de pasteurización',
            "María,\n\nHe identificado un cuello de botella en la línea de pasteurización.\n\nCon un ajuste podríamos aumentar throughput 15%.",
            4
        );

        // Ticket 6: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 18, 11, 0),
            Carbon::create(2025, 8, 19, 16, 30),
            Carbon::create(2025, 8, 20, 10, 0),
            $this->users[5],
            $this->agents['roberto'],
            $this->categories['safety_concern'],
            'Renovación de extintores - Área de producción',
            "Roberto,\n\n5 extintores en el área de producción vencen este mes.\n\nNecesito coordinar recarga con proveedores.",
            2
        );

        // Ticket 7: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 20, 15, 30),
            Carbon::create(2025, 8, 21, 11, 0),
            Carbon::create(2025, 8, 22, 9, 30),
            $this->users[0],
            $this->agents['maria'],
            $this->categories['equipment_issue'],
            'Repuesto filtros de aire línea PLT-2000',
            "María,\n\nLos filtros de aire de la línea PLT-2000 están al 80% de capacidad.\n\nConviene reemplazarlos ahora antes que afecten producción.",
            3
        );

        // Ticket 8: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 23, 9, 15),
            Carbon::create(2025, 8, 24, 14, 0),
            Carbon::create(2025, 8, 25, 16, 0),
            $this->users[1],
            $this->agents['roberto'],
            $this->categories['quality_problem'],
            'Validación de proceso de esterilización',
            "Roberto,\n\nDebemos validar el proceso de esterilización según norma ISO.\n\n¿Tienes disponibilidad para coordinar pruebas?",
            2
        );

        // Ticket 9: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 26, 10, 45),
            Carbon::create(2025, 8, 27, 13, 20),
            Carbon::create(2025, 8, 28, 11, 0),
            $this->users[2],
            $this->agents['maria'],
            $this->categories['production_delay'],
            'Capacitación operadores - Nueva maquinaria',
            "María,\n\nLa nueva envasadora automática llega la próxima semana.\n\nNecesitamos capacitar a 8 operadores antes de la instalación.",
            3
        );

        // Ticket 10: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 8, 28, 14, 0),
            Carbon::create(2025, 8, 29, 10, 30),
            Carbon::create(2025, 8, 30, 15, 0),
            $this->users[3],
            $this->agents['roberto'],
            $this->categories['supply_chain'],
            'Negociación contrato - Envases biodegradables',
            "Roberto,\n\nHe encontrado proveedor de envases biodegradables con precios competitivos.\n\n¿Revisamos propuesta comercial?",
            4
        );

        // Ticket 11: RESOLVED (fin de agosto)
        $this->createResolvedTicket(
            Carbon::create(2025, 8, 29, 11, 30),
            Carbon::create(2025, 8, 30, 14, 0),
            $this->users[4],
            $this->agents['maria'],
            $this->categories['equipment_issue'],
            'Upgrade software sistema SCADA',
            "María,\n\nEl proveedor de SCADA lanzó actualización con mejoras de seguridad.\n\n¿Procedemos con upgrade?",
            2
        );

        // Ticket 12: RESOLVED
        $this->createResolvedTicket(
            Carbon::create(2025, 8, 30, 16, 0),
            Carbon::create(2025, 8, 31, 10, 45),
            $this->users[5],
            $this->agents['roberto'],
            $this->categories['safety_concern'],
            'Simulacro de evacuación - Programación septiembre',
            "Roberto,\n\nDebemos programar simulacro de evacuación para septiembre.\n\n¿Qué fecha es conveniente?",
            3
        );
    }

    // ==================== SEPTIEMBRE 2025 ====================
    private function createSeptemberTickets(): void
    {
        $this->command->info('  📅 Septiembre 2025: 13 tickets...');

        // Tickets 1-7: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 9, 2, 8, 30),
            Carbon::create(2025, 9, 3, 14, 0),
            Carbon::create(2025, 9, 4, 10, 0),
            $this->users[0],
            $this->agents['maria'],
            $this->categories['quality_problem'],
            'Inspección sanitaria - Preparación documentación',
            "María,\n\nLa inspección sanitaria está programada para el 15 de septiembre.\n\nNecesito revisar documentación obligatoria.",
            3
        );

        $this->createClosedTicket(
            Carbon::create(2025, 9, 5, 13, 15),
            Carbon::create(2025, 9, 6, 11, 30),
            Carbon::create(2025, 9, 7, 9, 0),
            $this->users[1],
            $this->agents['roberto'],
            $this->categories['equipment_issue'],
            'Bomba hidráulica con ruido anormal',
            "Roberto,\n\nLa bomba H-204 está generando ruido metálico inusual.\n\nPosible desgaste de rodamientos.",
            2
        );

        $this->createClosedTicket(
            Carbon::create(2025, 9, 8, 10, 0),
            Carbon::create(2025, 9, 9, 15, 20),
            Carbon::create(2025, 9, 10, 13, 0),
            $this->users[2],
            $this->agents['maria'],
            $this->categories['production_delay'],
            'Ajuste de recetas - Producto bajo en grasa',
            "María,\n\nAlgunos lotes salen con contenido graso ligeramente bajo.\n\nNecesito ajustar parámetros de formulación.",
            4
        );

        $this->createClosedTicket(
            Carbon::create(2025, 9, 11, 9, 45),
            Carbon::create(2025, 9, 12, 14, 0),
            Carbon::create(2025, 9, 13, 11, 30),
            $this->users[3],
            $this->agents['roberto'],
            $this->categories['supply_chain'],
            'Retraso entrega leche cruda - Ganadería Altiplano',
            "Roberto,\n\nGanadería Altiplano retrasó entrega por problemas logísticos.\n\n¿Activamos proveedor backup?",
            3
        );

        $this->createClosedTicket(
            Carbon::create(2025, 9, 14, 14, 30),
            Carbon::create(2025, 9, 15, 10, 15),
            Carbon::create(2025, 9, 16, 16, 0),
            $this->users[4],
            $this->agents['maria'],
            $this->categories['safety_concern'],
            'Fuga menor amoniaco - Sistema refrigeración',
            "URGENTE María,\n\nDetectamos fuga menor de amoniaco en compresor CR-03.\n\nÁrea evacuada preventivamente.",
            2
        );

        $this->createClosedTicket(
            Carbon::create(2025, 9, 17, 11, 0),
            Carbon::create(2025, 9, 18, 13, 45),
            Carbon::create(2025, 9, 19, 10, 0),
            $this->users[5],
            $this->agents['roberto'],
            $this->categories['equipment_issue'],
            'Sistema eléctrico - Fluctuaciones de voltaje',
            "Roberto,\n\nHemos detectado fluctuaciones de voltaje que afectan equipos sensibles.\n\nNecesitamos estabilizador de línea.",
            3
        );

        $this->createClosedTicket(
            Carbon::create(2025, 9, 20, 15, 20),
            Carbon::create(2025, 9, 21, 11, 0),
            Carbon::create(2025, 9, 22, 14, 30),
            $this->users[0],
            $this->agents['maria'],
            $this->categories['quality_problem'],
            'Certificación ISO 22000 - Auditoría interna',
            "María,\n\nProgramemos auditoría interna antes de certificación ISO 22000.\n\nPropongo última semana de septiembre.",
            4
        );

        // Tickets 8-13: RESOLVED
        $this->createResolvedTicket(
            Carbon::create(2025, 9, 23, 9, 0),
            Carbon::create(2025, 9, 24, 14, 30),
            $this->users[1],
            $this->agents['roberto'],
            $this->categories['production_delay'],
            'Personal insuficiente turno tarde',
            "Roberto,\n\nEl turno tarde tiene déficit de 3 operadores desde hace 2 semanas.\n\n¿Hay reclutamiento en proceso?",
            2
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 9, 24, 13, 45),
            Carbon::create(2025, 9, 25, 10, 15),
            $this->users[2],
            $this->agents['maria'],
            $this->categories['equipment_issue'],
            'Actualización firmware controladores PLC',
            "María,\n\nLos PLCs de línea tienen firmware desactualizado.\n\nVersión nueva corrige bugs críticos.",
            3
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 9, 25, 14, 0),
            Carbon::create(2025, 9, 26, 11, 30),
            $this->users[3],
            $this->agents['roberto'],
            $this->categories['supply_chain'],
            'Evaluación nuevo proveedor cultivos lácticos',
            "Roberto,\n\nProveedor BioCultivos ofrece cultivos con mejor rendimiento.\n\n¿Coordinamos pruebas piloto?",
            2
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 9, 26, 10, 30),
            Carbon::create(2025, 9, 27, 15, 0),
            $this->users[4],
            $this->agents['maria'],
            $this->categories['quality_problem'],
            'Desviación pH en lotes matutinos',
            "María,\n\nLotes del turno mañana presentan pH ligeramente elevado.\n\nPosible problema calibración medidores.",
            3
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 9, 27, 11, 15),
            Carbon::create(2025, 9, 28, 13, 0),
            $this->users[5],
            $this->agents['roberto'],
            $this->categories['safety_concern'],
            'Actualización plan emergencias químicas',
            "Roberto,\n\nEl plan de emergencias químicas necesita actualización.\n\nNuevos productos requieren procedimientos específicos.",
            4
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 9, 29, 14, 30),
            Carbon::create(2025, 9, 30, 10, 45),
            $this->users[0],
            $this->agents['maria'],
            $this->categories['production_delay'],
            'Implementación sistema Kanban almacén',
            "María,\n\nPropongo implementar sistema Kanban para gestión de inventario.\n\nReduciría tiempos de búsqueda 40%.",
            2
        );
    }

    // ==================== OCTUBRE 2025 ====================
    private function createOctoberTickets(): void
    {
        $this->command->info('  📅 Octubre 2025: 10 tickets...');

        // Tickets 1-2: CLOSED
        $this->createClosedTicket(
            Carbon::create(2025, 10, 1, 9, 0),
            Carbon::create(2025, 10, 2, 14, 30),
            Carbon::create(2025, 10, 3, 11, 0),
            $this->users[1],
            $this->agents['roberto'],
            $this->categories['equipment_issue'],
            'Reemplazo rodamientos transportador principal',
            "Roberto,\n\nEl transportador principal presenta vibración excesiva.\n\nDiagnóstico: Rodamientos desgastados, requieren reemplazo urgente.",
            3
        );

        $this->createClosedTicket(
            Carbon::create(2025, 10, 4, 13, 20),
            Carbon::create(2025, 10, 5, 10, 45),
            Carbon::create(2025, 10, 6, 15, 0),
            $this->users[2],
            $this->agents['maria'],
            $this->categories['quality_problem'],
            'Lote yogur con viscosidad fuera de rango',
            "María,\n\nLote YG-2025-0234 tiene viscosidad 15% por debajo del estándar.\n\nPosible error en tiempo de fermentación.",
            2
        );

        // Tickets 3-9: RESOLVED
        $this->createResolvedTicket(
            Carbon::create(2025, 10, 7, 10, 15),
            Carbon::create(2025, 10, 8, 14, 0),
            $this->users[3],
            $this->agents['roberto'],
            $this->categories['supply_chain'],
            'Negociación volumen - Envases PET',
            "Roberto,\n\nCon el aumento de producción, podríamos negociar mejor precio en envases.\n\n¿Contactamos a proveedor?",
            3
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 10, 10, 11, 30),
            Carbon::create(2025, 10, 11, 15, 20),
            $this->users[4],
            $this->agents['maria'],
            $this->categories['production_delay'],
            'Parada programada - Limpieza profunda líneas',
            "María,\n\nEn 2 semanas toca limpieza profunda de todas las líneas.\n\nNecesito coordinar parada de 48 horas.",
            4
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 10, 14, 9, 45),
            Carbon::create(2025, 10, 15, 13, 0),
            $this->users[5],
            $this->agents['roberto'],
            $this->categories['safety_concern'],
            'Instalación alarmas monóxido carbono',
            "Roberto,\n\nPor normativa nueva, necesitamos alarmas CO en área calderas.\n\n¿Apruebas cotización de Bs. 8,500?",
            2
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 10, 17, 14, 0),
            Carbon::create(2025, 10, 18, 11, 15),
            $this->users[0],
            $this->agents['maria'],
            $this->categories['equipment_issue'],
            'Sistema de etiquetado - Impresión defectuosa',
            "María,\n\nLa impresora de etiquetas está generando códigos barra ilegibles.\n\nAfecta trazabilidad del producto.",
            3
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 10, 21, 10, 30),
            Carbon::create(2025, 10, 22, 14, 45),
            $this->users[1],
            $this->agents['roberto'],
            $this->categories['quality_problem'],
            'Análisis vida útil - Nuevos cultivos probióticos',
            "Roberto,\n\nCon los nuevos cultivos probióticos, debemos validar vida útil.\n\nPropongo estudio de 90 días.",
            2
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 10, 25, 13, 15),
            Carbon::create(2025, 10, 26, 10, 0),
            $this->users[2],
            $this->agents['maria'],
            $this->categories['production_delay'],
            'Optimización cambio sabores línea yogur',
            "María,\n\nEl cambio entre sabores toma 45 minutos.\n\nCon mejor procedimiento podríamos reducir a 25 minutos.",
            4
        );

        $this->createResolvedTicket(
            Carbon::create(2025, 10, 28, 11, 0),
            Carbon::create(2025, 10, 29, 15, 30),
            $this->users[3],
            $this->agents['roberto'],
            $this->categories['supply_chain'],
            'Diversificación proveedores leche cruda',
            "Roberto,\n\nDependemos mucho de 2 proveedores.\n\nConviene agregar 2 proveedores más para reducir riesgo.",
            3
        );

        // Ticket 10: PENDING (más reciente)
        $this->createPendingTicket(
            Carbon::create(2025, 10, 30, 14, 45),
            $this->users[4],
            $this->agents['maria'],
            $this->categories['equipment_issue'],
            'Evaluación compra pasteurizador adicional',
            "María,\n\nCon el crecimiento proyectado para 2026, necesitaremos capacidad adicional.\n\n¿Evaluamos inversión en pasteurizador nuevo?",
            2
        );
    }

    // ==================== HELPER METHODS ====================

    private function getRandomPriority(): string
    {
        // Distribution: 55% medium, 25% high, 20% low (realistic distribution)
        $rand = rand(1, 100);
        
        if ($rand <= 20) {
            return 'low';
        } elseif ($rand <= 75) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    private function createClosedTicket(
        Carbon $createdAt,
        Carbon $resolvedAt,
        Carbon $closedAt,
        User $user,
        User $agent,
        ?Category $category,
        string $title,
        string $description,
        int $responseCount
    ): void {
        $ticket = Ticket::create([
            'ticket_code' => $this->getTicketCode(),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $category?->id,
            'title' => $title,
            'description' => $description,
            'status' => 'closed',
            'priority' => $this->getRandomPriority(),
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $closedAt,
            'first_response_at' => $createdAt->copy()->addHours(rand(1, 4)),
            'resolved_at' => $resolvedAt,
            'closed_at' => $closedAt,
        ]);

        // Agent first response
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Entendido. He revisado tu solicitud y procedo con la gestión correspondiente. Te mantendré informado del avance.",
            'created_at' => $ticket->first_response_at,
            'updated_at' => $ticket->first_response_at,
        ]);

        // Additional responses
        for ($i = 1; $i < $responseCount; $i++) {
            $authorType = $i % 2 === 0 ? 'agent' : 'user';
            $authorId = $authorType === 'agent' ? $agent->id : $user->id;
            $responseTime = $createdAt->copy()->addHours(rand(4, 24) * $i);

            TicketResponse::create([
                'ticket_id' => $ticket->id,
                'author_id' => $authorId,
                'author_type' => $authorType,
                'content' => $authorType === 'agent' 
                    ? "Actualización: Se completó la gestión exitosamente. Problema resuelto."
                    : "Perfecto, confirmado de mi lado. Gracias por la gestión.",
                'created_at' => $responseTime,
                'updated_at' => $responseTime,
            ]);
        }
    }

    private function createResolvedTicket(
        Carbon $createdAt,
        Carbon $resolvedAt,
        User $user,
        User $agent,
        ?Category $category,
        string $title,
        string $description,
        int $responseCount
    ): void {
        $ticket = Ticket::create([
            'ticket_code' => $this->getTicketCode(),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $category?->id,
            'title' => $title,
            'description' => $description,
            'status' => 'resolved',
            'priority' => $this->getRandomPriority(),
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $resolvedAt,
            'first_response_at' => $createdAt->copy()->addHours(rand(2, 6)),
            'resolved_at' => $resolvedAt,
            'closed_at' => null,
        ]);

        // Responses
        for ($i = 0; $i < $responseCount; $i++) {
            $authorType = $i % 2 === 0 ? 'agent' : 'user';
            $authorId = $authorType === 'agent' ? $agent->id : $user->id;
            $responseTime = $createdAt->copy()->addHours(rand(2, 12) * ($i + 1));

            TicketResponse::create([
                'ticket_id' => $ticket->id,
                'author_id' => $authorId,
                'author_type' => $authorType,
                'content' => $authorType === 'agent'
                    ? "En proceso. He coordinado con el área correspondiente. Avance al 80%."
                    : "Entendido, quedo pendiente de los resultados finales.",
                'created_at' => $responseTime,
                'updated_at' => $responseTime,
            ]);
        }
    }

    private function createPendingTicket(
        Carbon $createdAt,
        User $user,
        User $agent,
        ?Category $category,
        string $title,
        string $description,
        int $responseCount
    ): void {
        $ticket = Ticket::create([
            'ticket_code' => $this->getTicketCode(),
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $category?->id,
            'title' => $title,
            'description' => $description,
            'status' => 'pending',
            'priority' => $this->getRandomPriority(),
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addHours(rand(4, 12)),
            'first_response_at' => $createdAt->copy()->addHours(rand(1, 3)),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        // Responses
        for ($i = 0; $i < $responseCount; $i++) {
            $authorType = $i % 2 === 0 ? 'agent' : 'user';
            $authorId = $authorType === 'agent' ? $agent->id : $user->id;
            $responseTime = $createdAt->copy()->addHours(rand(1, 6) * ($i + 1));

            TicketResponse::create([
                'ticket_id' => $ticket->id,
                'author_id' => $authorId,
                'author_type' => $authorType,
                'content' => $authorType === 'agent'
                    ? "Estoy investigando. Necesito más información del área técnica antes de proceder."
                    : "Ok, quedo atento a tu respuesta.",
                'created_at' => $responseTime,
                'updated_at' => $responseTime,
            ]);
        }
    }
}
