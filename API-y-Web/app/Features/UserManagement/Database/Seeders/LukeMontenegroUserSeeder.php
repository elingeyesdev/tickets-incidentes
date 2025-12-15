<?php

namespace App\Features\UserManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\AvatarHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Luke Montenegro Demo User Seeder
 *
 * Crea el usuario macedonomontenegro999@gmail.com para demostración del dashboard de usuario.
 *
 * Este usuario tendrá:
 * - 12 tickets creados a diferentes empresas (PIL Andina, Victoria Veterinaria, Tigo)
 * - Distribución de estados: CLOSED (4), RESOLVED (3), PENDING (3), OPEN (2)
 * - Distribución de prioridades: HIGH (2), MEDIUM (6), LOW (4)
 * - Seguirá a las empresas para ver sus anuncios
 * - Tickets distribuidos temporalmente en los últimos 6 meses
 *
 * Credenciales:
 * Email: montenegroluke999@gmail.com
 * Password: mklmklmkl
 */
class LukeMontenegroUserSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const EMAIL = 'montenegroluke999@gmail.com';

    private ?User $user = null;
    private ?Company $pilAndina = null;
    private ?Company $victoriaVet = null;
    private ?Company $tigo = null;
    private array $categories = [];

    public function run(): void
    {
        $this->command->info('👤 Creando usuario demo Luke Montenegro...');

        // [IDEMPOTENCY] Verificar si ya existe
        if (User::where('email', self::EMAIL)->exists()) {
            $this->command->info('[OK] Usuario Luke Montenegro ya existe. Saltando...');
            return;
        }

        // Cargar empresas
        $this->loadCompanies();

        if (!$this->pilAndina || !$this->victoriaVet) {
            $this->command->error('❌ Empresas requeridas no encontradas.');
            return;
        }

        // 1. Crear usuario
        $this->createUser();

        // 2. Asignar rol USER
        $this->assignUserRole();

        // 3. Seguir empresas
        $this->followCompanies();

        // 4. Cargar categorías
        $this->loadCategories();

        // 5. Crear tickets
        $this->createTickets();

        $this->command->info('');
        $this->command->info('✅ ¡Usuario Luke Montenegro creado exitosamente!');
        $this->command->info('');
        $this->command->info('📧 Email: ' . self::EMAIL);
        $this->command->info('🔑 Password: ' . self::PASSWORD);
        $this->command->info('🎫 Tickets: 12 (CLOSED: 4, RESOLVED: 3, PENDING: 3, OPEN: 2)');
    }

    private function loadCompanies(): void
    {
        $this->pilAndina = Company::where('name', 'PIL Andina S.A.')->first();
        $this->victoriaVet = Company::where('name', 'Victoria Veterinaria')->first();
        $this->tigo = Company::where('name', 'Tigo Bolivia')->first();

        if ($this->pilAndina) {
            $this->command->info("  ✓ PIL Andina encontrada");
        }
        if ($this->victoriaVet) {
            $this->command->info("  ✓ Victoria Veterinaria encontrada");
        }
        if ($this->tigo) {
            $this->command->info("  ✓ Tigo Bolivia encontrada");
        }
    }

    private function createUser(): void
    {
        $this->user = User::create([
            'user_code' => 'USR-LUKE-DEMO-001',
            'email' => self::EMAIL,
            'password_hash' => Hash::make(self::PASSWORD),
            'email_verified' => true,
            'email_verified_at' => now()->subMonths(6),
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'terms_accepted' => true,
            'terms_accepted_at' => now()->subMonths(6),
            'terms_version' => 'v2.1',
            'onboarding_completed_at' => now()->subMonths(6),
        ]);

        $this->user->profile()->create([
            'first_name' => 'Lucas',
            'last_name' => 'Montenegro',
            'phone_number' => '+59170123456',
            'avatar_url' => AvatarHelper::getRandom('male'),
            'theme' => 'light',
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);

        $this->command->info("  ✓ Usuario creado: {$this->user->email}");
    }

    private function assignUserRole(): void
    {
        UserRole::create([
            'user_id' => $this->user->id,
            'role_code' => 'USER',
            'company_id' => null,
            'is_active' => true,
        ]);
        $this->command->info('  ✓ Rol USER asignado');
    }

    private function followCompanies(): void
    {
        $companies = [$this->pilAndina, $this->victoriaVet];
        
        if ($this->tigo) {
            $companies[] = $this->tigo;
        }

        foreach ($companies as $company) {
            if ($company) {
                DB::table('business.user_company_followers')->insertOrIgnore([
                    'user_id' => $this->user->id,
                    'company_id' => $company->id,
                    'followed_at' => now()->subMonths(rand(1, 5)),
                ]);
                $this->command->info("  ✓ Siguiendo: {$company->name}");
            }
        }
    }

    private function loadCategories(): void
    {
        // Para PIL Andina
        $pilCategories = Category::where('company_id', $this->pilAndina->id)
            ->where('is_active', true)
            ->get();

        $this->categories['pil'] = [
            'calidad' => $pilCategories->first(fn($c) => str_contains($c->name, 'Calidad')) ?? $pilCategories->first(),
            'produccion' => $pilCategories->first(fn($c) => str_contains($c->name, 'Producción') || str_contains($c->name, 'Incidente')) ?? $pilCategories->first(),
            'logistica' => $pilCategories->first(fn($c) => str_contains($c->name, 'Logística') || str_contains($c->name, 'Cadena')) ?? $pilCategories->first(),
        ];

        // Para Victoria Veterinaria
        $vetCategories = Category::where('company_id', $this->victoriaVet->id)
            ->where('is_active', true)
            ->get();

        $this->categories['vet'] = [
            'consulta' => $vetCategories->first(fn($c) => str_contains($c->name, 'Consulta')) ?? $vetCategories->first(),
            'emergencia' => $vetCategories->first(fn($c) => str_contains($c->name, 'Emergencia') || str_contains($c->name, 'Urgencia')) ?? $vetCategories->first(),
            'cita' => $vetCategories->first(fn($c) => str_contains($c->name, 'Cita')) ?? $vetCategories->first(),
        ];

        // Para Tigo si existe
        if ($this->tigo) {
            $tigoCategories = Category::where('company_id', $this->tigo->id)
                ->where('is_active', true)
                ->get();

            $this->categories['tigo'] = [
                'internet' => $tigoCategories->first(fn($c) => str_contains($c->name, 'Internet') || str_contains($c->name, 'Conectividad')) ?? $tigoCategories->first(),
                'soporte' => $tigoCategories->first(fn($c) => str_contains($c->name, 'Soporte') || str_contains($c->name, 'Técnico')) ?? $tigoCategories->first(),
            ];
        }
    }

    private function createTickets(): void
    {
        $this->command->info('');
        $this->command->info('🎫 Creando tickets de demostración...');

        // ============================================================
        // PIL ANDINA TICKETS (5 tickets)
        // ============================================================

        // TICKET 1: CLOSED - 5 meses atrás
        $this->createPilTicket1Closed();

        // TICKET 2: CLOSED - 4 meses atrás
        $this->createPilTicket2Closed();

        // TICKET 3: RESOLVED - 3 meses atrás
        $this->createPilTicket3Resolved();

        // TICKET 4: PENDING - 1 semana atrás
        $this->createPilTicket4Pending();

        // TICKET 5: OPEN - Hoy
        $this->createPilTicket5Open();

        // ============================================================
        // VICTORIA VETERINARIA TICKETS (5 tickets)
        // ============================================================

        // TICKET 6: CLOSED - 4 meses atrás
        $this->createVetTicket1Closed();

        // TICKET 7: RESOLVED - 2 meses atrás
        $this->createVetTicket2Resolved();

        // TICKET 8: RESOLVED - 1 mes atrás
        $this->createVetTicket3Resolved();

        // TICKET 9: PENDING - 3 días atrás
        $this->createVetTicket4Pending();

        // TICKET 10: OPEN - Ayer
        $this->createVetTicket5Open();

        // ============================================================
        // TIGO TICKETS (2 tickets si existe)
        // ============================================================
        if ($this->tigo) {
            // TICKET 11: CLOSED - 3 meses atrás
            $this->createTigoTicket1Closed();

            // TICKET 12: PENDING - 5 días atrás
            $this->createTigoTicket2Pending();
        }
    }

    // ========================================================================
    // PIL ANDINA TICKETS
    // ========================================================================

    private function createPilTicket1Closed(): void
    {
        $createdAt = now()->subMonths(5)->subDays(rand(1, 15));
        $category = $this->categories['pil']['calidad'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-PIL-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->pilAndina->id,
            'category_id' => $category?->id,
            'title' => 'Yogurt PIL con fecha de vencimiento incorrecta',
            'description' => "Buenos días,\n\nCompré un yogurt PIL sabor frutilla en el supermercado Hipermaxi y noté que la fecha de vencimiento impresa está borrosa y parece haber sido alterada.\n\nLote: YG-2024-0892\nFecha de compra: " . $createdAt->format('d/m/Y') . "\n\n¿Pueden verificar si este lote tiene algún problema? Adjunto foto del empaque.\n\nSaludos.",
            'status' => 'closed',
            'priority' => 'medium',
            'owner_agent_id' => $this->getAgentFor($this->pilAndina),
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addHours(4),
            'resolved_at' => $createdAt->copy()->addDays(3),
            'closed_at' => $createdAt->copy()->addDays(5),
        ]);

        $this->createAgentResponse($ticket, $this->pilAndina, 
            "Estimado Lucas,\n\nGracias por reportar este inconveniente. Hemos verificado el lote YG-2024-0892 y confirmamos que el producto está en perfectas condiciones.\n\nLa impresión borrosa se debió a un problema menor en la línea de empaque que ya fue corregido.\n\nComo medida de buena voluntad, le ofrecemos un cupón de descuento del 20% para su próxima compra de productos PIL.\n\nDisculpe las molestias ocasionadas.",
            $createdAt->copy()->addHours(4)
        );

        $this->createUserResponse($ticket, 
            "Muchas gracias por la respuesta rápida y por el cupón. Seguiré comprando productos PIL.",
            $createdAt->copy()->addDays(5)
        );

        $this->command->info('  ✓ [PIL] Ticket CLOSED: Yogurt con fecha incorrecta');
    }

    private function createPilTicket2Closed(): void
    {
        $createdAt = now()->subMonths(4)->subDays(rand(5, 20));
        $category = $this->categories['pil']['logistica'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-PIL-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->pilAndina->id,
            'category_id' => $category?->id,
            'title' => 'Leche llegó a temperatura inadecuada',
            'description' => "Hola,\n\nHoy recibí un pedido de 6 litros de leche PIL por delivery y cuando llegó, la leche estaba tibia. El repartidor tardó 3 horas en llegar desde que hice el pedido.\n\nNúmero de orden: DEL-78432\nProductos: 6x Leche PIL Entera 1L\n\n¿Estos productos son seguros para consumir? Me preocupa la cadena de frío.\n\nGracias.",
            'status' => 'closed',
            'priority' => 'high',
            'owner_agent_id' => $this->getAgentFor($this->pilAndina),
            'last_response_author_type' => 'user',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(3),
            'first_response_at' => $createdAt->copy()->addHours(2),
            'resolved_at' => $createdAt->copy()->addDays(2),
            'closed_at' => $createdAt->copy()->addDays(3),
        ]);

        $this->createAgentResponse($ticket, $this->pilAndina,
            "Estimado Lucas,\n\nLamentamos mucho este inconveniente con la cadena de frío.\n\nPor seguridad alimentaria, le recomendamos NO consumir esos productos. Hemos programado el reemplazo GRATUITO de los 6 litros de leche para mañana entre 9:00 y 11:00 AM.\n\nAdemás, hemos reportado este incidente a nuestro equipo de logística para evitar que vuelva a ocurrir.",
            $createdAt->copy()->addHours(2)
        );

        $this->createUserResponse($ticket,
            "Perfecto, recibí los productos de reemplazo en perfectas condiciones. Gracias por la rápida gestión.",
            $createdAt->copy()->addDays(3)
        );

        $this->command->info('  ✓ [PIL] Ticket CLOSED: Leche temperatura inadecuada');
    }

    private function createPilTicket3Resolved(): void
    {
        $createdAt = now()->subMonths(3)->subDays(rand(1, 10));
        $category = $this->categories['pil']['calidad'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-PIL-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->pilAndina->id,
            'category_id' => $category?->id,
            'title' => 'Queso Mantecoso con sabor extraño',
            'description' => "Buenas tardes,\n\nCompré un Queso Mantecoso PIL y tiene un sabor ligeramente amargo, diferente al sabor normal.\n\nLote: QM-2024-1456\nFecha de vencimiento: " . now()->addMonth()->format('d/m/Y') . "\nLugar de compra: Supermercado Ketal\n\n¿Es normal este sabor o el producto tiene algún defecto?",
            'status' => 'resolved',
            'priority' => 'low',
            'owner_agent_id' => $this->getAgentFor($this->pilAndina),
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(4),
            'first_response_at' => $createdAt->copy()->addHours(6),
            'resolved_at' => $createdAt->copy()->addDays(4),
            'closed_at' => null,
        ]);

        $this->createAgentResponse($ticket, $this->pilAndina,
            "Estimado Lucas,\n\nGracias por el reporte. Hemos analizado el lote QM-2024-1456 y detectamos una variación menor en la fermentación que afectó el sabor.\n\nEl producto es SEGURO para consumir, pero entendemos que el sabor no es el esperado.\n\nLe ofrecemos:\n- Reembolso completo\n- O reemplazo por un producto de otro lote\n\n¿Cuál opción prefiere?",
            $createdAt->copy()->addHours(6)
        );

        $this->createAgentResponse($ticket, $this->pilAndina,
            "Hemos procesado el reembolso a su cuenta. Debería ver el monto reflejado en 3-5 días hábiles.\n\nGracias por ayudarnos a mejorar nuestros productos.",
            $createdAt->copy()->addDays(4)
        );

        $this->command->info('  ✓ [PIL] Ticket RESOLVED: Queso con sabor extraño');
    }

    private function createPilTicket4Pending(): void
    {
        $createdAt = now()->subDays(7);
        $category = $this->categories['pil']['produccion'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-PIL-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->pilAndina->id,
            'category_id' => $category?->id,
            'title' => 'Dificultad para encontrar Leche Deslactosada en supermercados',
            'description' => "Hola,\n\nDesde hace 2 semanas no encuentro Leche PIL Deslactosada en ningún supermercado de Santa Cruz (probé en Hipermaxi, Ketal y Fidalga).\n\n¿Hay algún problema de producción? ¿Cuándo volverá a estar disponible?\n\nSoy intolerante a la lactosa y dependo de este producto.\n\nGracias.",
            'status' => 'pending',
            'priority' => 'medium',
            'owner_agent_id' => $this->getAgentFor($this->pilAndina),
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(2),
            'first_response_at' => $createdAt->copy()->addHours(5),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->createAgentResponse($ticket, $this->pilAndina,
            "Estimado Lucas,\n\nGracias por contactarnos y disculpe las molestias.\n\nEfectivamente tuvimos un problema de producción con la línea de leche deslactosada debido a mantenimiento programado. La producción ya se reactivó y estimamos que el producto estará disponible en supermercados dentro de 5-7 días.\n\nLe recomendamos consultar en la sucursal de Hipermaxi de la Av. San Martín, que será una de las primeras en recibir el producto.\n\n¿Hay algo más en lo que podamos ayudarle?",
            $createdAt->copy()->addHours(5)
        );

        $this->command->info('  ✓ [PIL] Ticket PENDING: Leche Deslactosada no disponible');
    }

    private function createPilTicket5Open(): void
    {
        $createdAt = now()->subHours(3);
        $category = $this->categories['pil']['calidad'];

        Ticket::create([
            'ticket_code' => 'TKT-PIL-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->pilAndina->id,
            'category_id' => $category?->id,
            'title' => 'Consulta sobre información nutricional del Yogurt Griego',
            'description' => "Buenos días,\n\nEstoy buscando información detallada sobre el contenido nutricional del nuevo Yogurt Griego PIL, específicamente:\n\n- Contenido de proteínas por porción\n- ¿Es apto para diabéticos?\n- ¿Contiene probióticos?\n\nLa información en el empaque no es muy clara.\n\nGracias de antemano.",
            'status' => 'open',
            'priority' => 'low',
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->command->info('  ✓ [PIL] Ticket OPEN: Consulta información nutricional');
    }

    // ========================================================================
    // VICTORIA VETERINARIA TICKETS
    // ========================================================================

    private function createVetTicket1Closed(): void
    {
        $createdAt = now()->subMonths(4)->subDays(rand(1, 10));
        $category = $this->categories['vet']['consulta'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-VET-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->victoriaVet->id,
            'category_id' => $category?->id,
            'title' => 'Consulta sobre vacunación de cachorro',
            'description' => "Hola,\n\nTengo un cachorro Golden Retriever de 2 meses llamado Max. ¿Cuándo debería llevarlo para su primera vacuna?\n\n¿Necesito cita previa o puedo ir directamente?\n\nGracias.",
            'status' => 'closed',
            'priority' => 'medium',
            'owner_agent_id' => $this->getAgentFor($this->victoriaVet),
            'last_response_author_type' => 'user',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(7),
            'first_response_at' => $createdAt->copy()->addHours(3),
            'resolved_at' => $createdAt->copy()->addDays(1),
            'closed_at' => $createdAt->copy()->addDays(7),
        ]);

        $this->createAgentResponse($ticket, $this->victoriaVet,
            "¡Hola Lucas!\n\nQué lindo que tengas un nuevo cachorro Golden, son una raza maravillosa.\n\nA los 2 meses ya debería recibir su primera dosis de vacuna polivalente (moquillo, hepatitis, parvovirus). Te recomiendo traerlo la próxima semana.\n\nCalendario sugerido:\n- 2 meses: Primera dosis\n- 3 meses: Segunda dosis + leptospirosis\n- 4 meses: Tercera dosis + rabia\n\nPuedes agendar cita online en nuestra web o llamar al 322-1234.\n\n¡Te esperamos!",
            $createdAt->copy()->addHours(3)
        );

        $this->createUserResponse($ticket,
            "¡Gracias por la información! Agendé cita para este sábado. Max ya está vacunado y todo salió perfecto.",
            $createdAt->copy()->addDays(7)
        );

        $this->command->info('  ✓ [VET] Ticket CLOSED: Consulta vacunación cachorro');
    }

    private function createVetTicket2Resolved(): void
    {
        $createdAt = now()->subMonths(2)->subDays(rand(5, 15));
        $category = $this->categories['vet']['emergencia'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-VET-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->victoriaVet->id,
            'category_id' => $category?->id,
            'title' => 'Mi perro vomitó algo que no reconozco',
            'description' => "¡Urgente!\n\nMi perro Max acaba de vomitar algo de color verde oscuro y está muy decaído. Normalmente es muy activo.\n\nHoy comió su alimento normal (Royal Canin) más temprano. No sé si comió algo en el jardín.\n\n¿Es grave? ¿Debo llevarlo inmediatamente?",
            'status' => 'resolved',
            'priority' => 'high',
            'owner_agent_id' => $this->getAgentFor($this->victoriaVet),
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addHours(8),
            'first_response_at' => $createdAt->copy()->addMinutes(15),
            'resolved_at' => $createdAt->copy()->addHours(8),
            'closed_at' => null,
        ]);

        $this->createAgentResponse($ticket, $this->victoriaVet,
            "Lucas, el vómito verde oscuro puede indicar que comió plantas o pasto, lo cual no es grave generalmente. Sin embargo, si está MUY decaído, te recomiendo traerlo para una revisión.\n\nMientras tanto:\n- No le des comida por 2-3 horas\n- Solo agua en pequeñas cantidades\n- Observa si vomita de nuevo\n\nNuestra emergencia está disponible: 322-1234",
            $createdAt->copy()->addMinutes(15)
        );

        $this->createAgentResponse($ticket, $this->victoriaVet,
            "Lucas, seguimiento: ¿Cómo está Max? ¿Mejoró después de las indicaciones?",
            $createdAt->copy()->addHours(6)
        );

        $this->createUserResponse($ticket,
            "¡Sí! Max ya está mucho mejor, comió un poco de arroz con pollo y no vomitó más. Parece que solo fue algo que comió en el jardín. Gracias por la respuesta rápida.",
            $createdAt->copy()->addHours(7)
        );

        $this->createAgentResponse($ticket, $this->victoriaVet,
            "¡Excelente noticia! El arroz con pollo hervido es perfecto para estos casos. Mantenlo con dieta blanda un par de días más y todo estará bien. Un gusto haber ayudado.",
            $createdAt->copy()->addHours(8)
        );

        $this->command->info('  ✓ [VET] Ticket RESOLVED: Perro vomitó');
    }

    private function createVetTicket3Resolved(): void
    {
        $createdAt = now()->subMonths(1)->subDays(rand(1, 10));
        $category = $this->categories['vet']['cita'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-VET-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->victoriaVet->id,
            'category_id' => $category?->id,
            'title' => 'Problema al agendar cita online',
            'description' => "Hola,\n\nIntento agendar una cita para el baño de mi perro Max pero el sistema me da error cuando selecciono el horario.\n\nYa probé en Chrome y Firefox, siempre dice \"Error al procesar la solicitud\".\n\n¿Pueden ayudarme a agendar la cita manualmente?",
            'status' => 'resolved',
            'priority' => 'low',
            'owner_agent_id' => $this->getAgentFor($this->victoriaVet),
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(1),
            'first_response_at' => $createdAt->copy()->addHours(2),
            'resolved_at' => $createdAt->copy()->addDays(1),
            'closed_at' => null,
        ]);

        $this->createAgentResponse($ticket, $this->victoriaVet,
            "Hola Lucas,\n\nDisculpa el inconveniente con el sistema de citas. Hemos identificado un bug que ya fue reportado al equipo técnico.\n\nMientras tanto, he agendado manualmente tu cita:\n\n📅 Fecha: " . $createdAt->copy()->addDays(3)->format('l d/m/Y') . "\n⏰ Hora: 10:30 AM\n🐕 Servicio: Baño completo para Max\n\n¿Te funciona este horario?",
            $createdAt->copy()->addHours(2)
        );

        $this->createUserResponse($ticket,
            "Perfecto, ese horario me funciona. ¡Gracias por la ayuda!",
            $createdAt->copy()->addHours(4)
        );

        $this->createAgentResponse($ticket, $this->victoriaVet,
            "¡Listo! Tu cita está confirmada. Te esperamos con Max. El sistema de citas ya fue reparado por si necesitas agendar futuras citas online.",
            $createdAt->copy()->addDays(1)
        );

        $this->command->info('  ✓ [VET] Ticket RESOLVED: Problema agendar cita');
    }

    private function createVetTicket4Pending(): void
    {
        $createdAt = now()->subDays(3);
        $category = $this->categories['vet']['consulta'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-VET-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->victoriaVet->id,
            'category_id' => $category?->id,
            'title' => 'Consulta sobre alimentación para perro con sobrepeso',
            'description' => "Hola,\n\nMi perro Max tiene algo de sobrepeso según el último chequeo (está en 38kg y debería estar en 32kg aprox).\n\n¿Qué tipo de alimento me recomiendan? ¿Hay alguna marca especial baja en calorías?\n\nTambién quisiera saber cuántas veces al día debería alimentarlo y si debo eliminar las golosinas completamente.\n\nGracias.",
            'status' => 'pending',
            'priority' => 'medium',
            'owner_agent_id' => $this->getAgentFor($this->victoriaVet),
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(1),
            'first_response_at' => $createdAt->copy()->addHours(4),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->createAgentResponse($ticket, $this->victoriaVet,
            "Hola Lucas,\n\nGracias por preocuparte por la salud de Max. El sobrepeso en Golden Retrievers es común pero manejable.\n\nRecomendaciones:\n\n🍖 **Alimento**: Royal Canin Maxi Light o Hill's Metabolic\n📏 **Cantidad**: 300-350g diarios dividido en 2 porciones\n🍬 **Golosinas**: Reducir al mínimo (máx 10% de calorías diarias)\n🏃 **Ejercicio**: Mínimo 45 min de caminata diaria\n\n¿Te gustaría agendar una consulta nutricional para un plan personalizado? Cuesta Bs. 150 e incluye seguimiento por 3 meses.",
            $createdAt->copy()->addHours(4)
        );

        $this->command->info('  ✓ [VET] Ticket PENDING: Consulta alimentación');
    }

    private function createVetTicket5Open(): void
    {
        $createdAt = now()->subDays(1);
        $category = $this->categories['vet']['consulta'];

        Ticket::create([
            'ticket_code' => 'TKT-VET-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->victoriaVet->id,
            'category_id' => $category?->id,
            'title' => 'Precio de esterilización para perro adulto',
            'description' => "Hola,\n\nEstoy considerando esterilizar a mi perro Max (Golden Retriever, 1 año, 35kg).\n\n¿Cuál es el precio de la esterilización?\n¿Qué incluye el procedimiento?\n¿Cuántos días de recuperación necesita?\n\nGracias.",
            'status' => 'open',
            'priority' => 'low',
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->command->info('  ✓ [VET] Ticket OPEN: Precio esterilización');
    }

    // ========================================================================
    // TIGO TICKETS (si existe)
    // ========================================================================

    private function createTigoTicket1Closed(): void
    {
        $createdAt = now()->subMonths(3)->subDays(rand(1, 15));
        $category = $this->categories['tigo']['internet'] ?? null;

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-TIG-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->tigo->id,
            'category_id' => $category?->id,
            'title' => 'Internet lento durante horario nocturno',
            'description' => "Buenas noches,\n\nDesde hace una semana mi internet Tigo Home está muy lento entre las 20:00 y 23:00 horas.\n\nMi plan es de 100 Mbps pero las pruebas de velocidad muestran solo 15-20 Mbps en ese horario.\n\n¿Hay algún problema en la zona? Mi dirección es Av. Monseñor Rivero #456.\n\nGracias.",
            'status' => 'closed',
            'priority' => 'medium',
            'owner_agent_id' => $this->getAgentFor($this->tigo),
            'last_response_author_type' => 'user',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addHours(6),
            'resolved_at' => $createdAt->copy()->addDays(3),
            'closed_at' => $createdAt->copy()->addDays(5),
        ]);

        $this->createAgentResponse($ticket, $this->tigo,
            "Estimado Lucas,\n\nGracias por reportar este inconveniente. Hemos detectado saturación en el nodo de su zona durante horas pico.\n\nNuestro equipo técnico está realizando una ampliación de capacidad que se completará en 48 horas.\n\nComo compensación, le estamos aplicando 50GB adicionales de datos móviles a su línea registrada.\n\nDisculpe las molestias.",
            $createdAt->copy()->addHours(6)
        );

        $this->createUserResponse($ticket,
            "Confirmo que la velocidad ya mejoró significativamente. Ahora tengo los 100 Mbps completos incluso en horario nocturno. Gracias por la solución y los datos adicionales.",
            $createdAt->copy()->addDays(5)
        );

        $this->command->info('  ✓ [TIGO] Ticket CLOSED: Internet lento nocturno');
    }

    private function createTigoTicket2Pending(): void
    {
        $createdAt = now()->subDays(5);
        $category = $this->categories['tigo']['soporte'] ?? null;

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-TIG-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $this->user->id,
            'company_id' => $this->tigo->id,
            'category_id' => $category?->id,
            'title' => 'Factura con cargos que no reconozco',
            'description' => "Hola,\n\nEn mi última factura de Tigo Home aparecen 2 cargos que no reconozco:\n\n1. \"Servicio Premium TV\" - Bs. 45\n2. \"Extensión WiFi\" - Bs. 30\n\nNo solicité ninguno de estos servicios. ¿Pueden verificar y hacer el ajuste correspondiente?\n\nNúmero de cliente: 12345678\n\nGracias.",
            'status' => 'pending',
            'priority' => 'medium',
            'owner_agent_id' => $this->getAgentFor($this->tigo),
            'last_response_author_type' => 'agent',
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDays(2),
            'first_response_at' => $createdAt->copy()->addHours(8),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->createAgentResponse($ticket, $this->tigo,
            "Estimado Lucas,\n\nGracias por contactarnos. He verificado su cuenta y efectivamente estos cargos fueron aplicados por error.\n\nEstamos procesando la nota de crédito por Bs. 75 que se verá reflejada en su próxima factura.\n\nTambién hemos eliminado estos servicios de su cuenta para que no vuelvan a facturarse.\n\n¿Hay algo más en lo que pueda ayudarle?",
            $createdAt->copy()->addHours(8)
        );

        $this->command->info('  ✓ [TIGO] Ticket PENDING: Factura con cargos erróneos');
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    private function getAgentFor(Company $company): ?string
    {
        $agent = User::whereHas('userRoles', function ($q) use ($company) {
            $q->where('role_code', 'AGENT')
              ->where('company_id', $company->id)
              ->where('is_active', true);
        })->first();

        return $agent?->id;
    }

    private function createAgentResponse(Ticket $ticket, Company $company, string $content, Carbon $createdAt): void
    {
        $agentId = $this->getAgentFor($company);
        if (!$agentId) {
            $agentId = $company->admin_user_id;
        }

        if ($agentId) {
            TicketResponse::create([
                'ticket_id' => $ticket->id,
                'author_id' => $agentId,
                'author_type' => 'agent',
                'content' => $content,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function createUserResponse(Ticket $ticket, string $content, Carbon $createdAt): void
    {
        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->user->id,
            'author_type' => 'user',
            'content' => $content,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
