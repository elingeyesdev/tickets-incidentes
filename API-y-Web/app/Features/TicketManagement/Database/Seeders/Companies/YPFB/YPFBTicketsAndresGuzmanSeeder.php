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
 * YPFB Tickets Seeder - Andrés Guzmán
 * Temas: Seguridad industrial, medio ambiente, HSE
 */
class YPFBTicketsAndresGuzmanSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'andres.guzman@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Rolando', 'last_name' => 'Mejía', 'email' => 'rolando.mejia.hse6@gmail.com'],
        ['first_name' => 'Valeria', 'last_name' => 'Soto', 'email' => 'valeria.soto.ambiente6@gmail.com'],
        ['first_name' => 'Milton', 'last_name' => 'Cruz', 'email' => 'milton.cruz.seguridad6@gmail.com'],
        ['first_name' => 'Lorena', 'last_name' => 'Pinto', 'email' => 'lorena.pinto.emergencias6@gmail.com'],
        ['first_name' => 'Sergio', 'last_name' => 'Montaño', 'email' => 'sergio.montano.planta6@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Andrés Guzmán...");

        $this->loadCompany();
        if (!$this->company) return;

        $this->loadAgent();
        if (!$this->agent) return;

        if ($this->alreadySeeded()) return;

        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Andrés Guzmán");
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
            'seguridad' => $areas->firstWhere('name', 'Seguridad, Medio Ambiente y Compliance'),
            'exploracion' => $areas->firstWhere('name', 'Exploración y Producción'),
        ];
    }

    private function loadCategories(): void
    {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'seguridad' => $cats->firstWhere('name', 'Reporte de Seguridad') ?? $cats->first(),
            'interrupcion' => $cats->firstWhere('name', 'Incidente de Interrupción del Servicio') ?? $cats->first(),
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
        $this->createTicket1_DerrameHidrocarburo();
        $this->createTicket2_AccidenteLaboral();
        $this->createTicket3_AuditoriaAmbiental();
        $this->createTicket4_SimulacroEmergencia();
        $this->createTicket5_QuejaComunidad();
    }

    // ==================== TICKET 1: CLOSED - DERRAME HIDROCARBURO ====================
    private function createTicket1_DerrameHidrocarburo(): void
    {
        $user = $this->users['Valeria'];
        $createdAt = Carbon::create(2025, 2, 5, 6, 15, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['seguridad']->id ?? null,
            'area_id' => $this->areas['seguridad']->id ?? null,
            'title' => 'EMERGENCIA AMBIENTAL: Derrame 500 litros diésel Campo San Alberto',
            'description' => "Andrés,

Derrame detectado esta madrugada en Campo San Alberto.

**DETALLES:**
- Hora: 05:30 AM
- Producto: Diésel
- Volumen estimado: 500 litros
- Ubicación: Área de carga de cisternas
- Causa: Rotura de manguera de transferencia

**ACCIONES TOMADAS:**
1. Área acordonada
2. Contención con barreras absorbentes
3. ABT notificada (protocolo legal)

**RIESGO:**
- Suelo contaminado ~80 m²
- No hay cuerpos de agua cercanos
- Sin riesgo de incendio (área despejada)

Necesito autorización para contratar empresa de remediación.

Valeria Soto
Coordinadora Medio Ambiente",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'user',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(15),
            'first_response_at' => $createdAt->copy()->addMinutes(30),
            'resolved_at' => $createdAt->copy()->addDays(10),
            'closed_at' => $createdAt->copy()->addDays(15),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Valeria,

Contratación de remediación autorizada. Usa a EnviroClean (contrato marco vigente).

Paralelo a remediación:
1. Investiga causa raíz (¿por qué falló la manguera?)
2. Documenta todo para ABT
3. Fotos antes/durante/después

¿Cuánto tiempo estima EnviroClean para remediar?",
            'created_at' => $createdAt->copy()->addMinutes(30),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "EnviroClean estima 7 días. Ya están en camino. Manguera tenía 5 años, debió cambiarse hace 1 año.",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Entonces falla de mantenimiento. Necesito lista de todas las mangueras de transferencia y su antigüedad. Programa reemplazo preventivo.",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Lista enviada a tu correo. 8 mangueras más necesitan reemplazo urgente.",
            'created_at' => $createdAt->copy()->addDays(1),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Remediación completada. ABT inspeccionó y aprobó cierre. Multa evitada por respuesta rápida.",
            'created_at' => $createdAt->copy()->addDays(9),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Excelente gestión Valeria. Envía informe final para archivo y lecciones aprendidas para compartir con otros campos. Cierro ticket.",
            'created_at' => $createdAt->copy()->addDays(10),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/oil,spill
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'fotos_derrame_san_alberto.zip',
            'file_path' => 'tickets/' . $ticket->id . '/fotos_derrame.zip',
            'file_type' => 'application/zip', 'file_size_bytes' => rand(2000000, 5000000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Feb)");
    }

    // ==================== TICKET 2: CLOSED - ACCIDENTE LABORAL ====================
    private function createTicket2_AccidenteLaboral(): void
    {
        $user = $this->users['Milton'];
        $createdAt = Carbon::create(2025, 4, 12, 10, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['seguridad']->id ?? null,
            'area_id' => $this->areas['seguridad']->id ?? null,
            'title' => 'Accidente laboral: Técnico con quemadura grado 2 - Refinería CBBA',
            'description' => "Andrés,

Accidente en turno de mañana.

**DATOS:**
- Afectado: Juan Carlos Ramos (Técnico de procesos)
- Lesión: Quemadura grado 2 en brazo derecho
- Causa: Salpicadura de líquido caliente
- Hora: 09:45 AM

**ATENCIÓN:**
- Primeros auxilios aplicados inmediatamente
- Trasladado a Clínica Los Olivos
- Estado: Estable, pronóstico bueno

**EQUIPO DE PROTECCIÓN:**
El trabajador NO llevaba mangas largas protectoras. Solo camiseta.

Investigo causa raíz. Adjunto reporte preliminar.

Milton Cruz
Supervisor HSE Refinería",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(12),
            'first_response_at' => $createdAt->copy()->addHours(1),
            'resolved_at' => $createdAt->copy()->addDays(8),
            'closed_at' => $createdAt->copy()->addDays(12),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Milton,

Prioridad: Salud del trabajador. ¿Ya habló con su familia?

Sobre el EPP:
1. ¿Por qué no usaba protección?
2. ¿Supervisor de turno verificó antes de la tarea?
3. ¿Hay registro de entrega de EPP?

Notifica a RRHH para gestionar seguro médico. Necesito informe completo en 48 horas.",
            'created_at' => $createdAt->copy()->addHours(1),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Familia notificada. Esposa está en clínica con él. Sobre EPP: dice que le quedaba incómodo y se lo quitó. Supervisor no verificó.",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Falla de supervisión entonces. Llama al supervisor a mi oficina mañana 8 AM. Y verifica tallas de EPP de todo el equipo, si están incómodos hay que ajustar.",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Reunión realizada. Supervisor recibió amonestación escrita. Revisión de EPP completa: 4 trabajadores necesitaban talla diferente. Ya se corrigió.",
            'created_at' => $createdAt->copy()->addDays(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Trabajador dado de alta. 15 días de licencia. Regresa el 28 de abril.",
            'created_at' => $createdAt->copy()->addDays(7),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Bien. Programa charla de seguridad para todo el turno usando este caso como ejemplo (sin nombres). Cierro ticket.",
            'created_at' => $createdAt->copy()->addDays(8),
        ]);

        // URL placeholder: https://loremflickr.com/640/480/safety,industrial
        TicketAttachment::create([
            'ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id,
            'file_name' => 'reporte_accidente_12abr2025.pdf',
            'file_path' => 'tickets/' . $ticket->id . '/reporte_accidente.pdf',
            'file_type' => 'application/pdf', 'file_size_bytes' => rand(200000, 400000),
            'created_at' => $createdAt,
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Abr)");
    }

    // ==================== TICKET 3: CLOSED - AUDITORÍA AMBIENTAL ====================
    private function createTicket3_AuditoriaAmbiental(): void
    {
        $user = $this->users['Valeria'];
        $createdAt = Carbon::create(2025, 7, 8, 8, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['seguridad']->id ?? null,
            'area_id' => $this->areas['seguridad']->id ?? null,
            'title' => 'Preparación auditoría ABT agosto 2025 - 3 no conformidades pendientes',
            'description' => "Andrés,

ABT programó auditoría ambiental para el 15-17 de agosto. Tenemos 3 no conformidades pendientes del año pasado.

**NO CONFORMIDADES ABIERTAS:**
1. NC-2024-07: Falta de monitoreo de emisiones en antorcha Campo Margarita
2. NC-2024-12: Disposición inadecuada de lodos de perforación
3. NC-2024-18: Plan de cierre de pozo PZ-45 no ejecutado

**ESTADO:**
- NC-07: 80% resuelto (equipo de monitoreo instalado, falta calibración)
- NC-12: 60% resuelto (contrato de disposición firmado, falta ejecución)
- NC-18: 0% (suspendido por falta de presupuesto)

**RIESGO:**
Si llegamos con NC abiertas, multa estimada USD 50,000-100,000.

¿Hay presupuesto para cerrar NC-18 antes de agosto?

Valeria Soto",
            'status' => 'closed', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'user',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(35),
            'first_response_at' => $createdAt->copy()->addHours(4),
            'resolved_at' => $createdAt->copy()->addDays(30),
            'closed_at' => $createdAt->copy()->addDays(35),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Valeria,

NC-18 es crítica. ¿Cuánto cuesta cerrar el pozo PZ-45?

Sobre las otras:
- NC-07: Que calibren equipo esta semana
- NC-12: Ejecuta disposición de lodos antes del 30 julio

Dame presupuesto y cronograma para NC-18 hoy.",
            'created_at' => $createdAt->copy()->addHours(4),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "NC-18: USD 85,000 para cierre completo. Tiempo: 20 días. Si empezamos el 20 julio, terminamos el 9 agosto. Justo antes de auditoría.",
            'created_at' => $createdAt->copy()->addHours(6),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Aprobado. USD 85,000 < USD 100,000 de multa potencial. Inicia el 20 julio. Monitorea diariamente.",
            'created_at' => $createdAt->copy()->addHours(8),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Actualización: NC-07 cerrada. NC-12 cerrada. NC-18 avanza bien, 70% completado.",
            'created_at' => $createdAt->copy()->addDays(20),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "¡Auditoría completada! 0 no conformidades abiertas. ABT felicitó la gestión. Sin multas.",
            'created_at' => $createdAt->copy()->addDays(30),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Excelente resultado Valeria. Felicita al equipo de mi parte. Cierro ticket.",
            'created_at' => $createdAt->copy()->addDays(30)->addHours(2),
        ]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jul)");
    }

    // ==================== TICKET 4: RESOLVED - SIMULACRO ====================
    private function createTicket4_SimulacroEmergencia(): void
    {
        $user = $this->users['Lorena'];
        $createdAt = Carbon::create(2025, 10, 2, 9, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['seguridad']->id ?? null,
            'area_id' => $this->areas['seguridad']->id ?? null,
            'title' => 'Resultados simulacro evacuación Refinería SCZ - Tiempo 8:45 min (meta 5 min)',
            'description' => "Andrés,

Simulacro de evacuación realizado ayer. Resultados por debajo del objetivo.

**RESULTADOS:**
- Tiempo evacuación total: 8 minutos 45 segundos
- Meta: 5 minutos
- Personal evacuado: 245 personas
- Punto encuentro: 100% llegaron al punto asignado

**PROBLEMAS DETECTADOS:**
1. Salida Norte bloqueada parcialmente (cajas almacenadas)
2. 12 personas no sabían ruta (nuevos ingresos sin inducción)
3. Sistema de alarma no se escuchó en área de calderas

**RECOMENDACIONES:**
- Liberar salida Norte permanentemente
- Inducción de emergencia para nuevos en primera semana
- Amplificar alarma en área de calderas

¿Aprueba presupuesto para mejoras? Estimado USD 3,500.

Lorena Pinto
Brigada de Emergencias",
            'status' => 'resolved', 'priority' => 'medium',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(10),
            'first_response_at' => $createdAt->copy()->addHours(3),
            'resolved_at' => $createdAt->copy()->addDays(10),
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Lorena,

8:45 es inaceptable. Aprobado el presupuesto USD 3,500.

Adicional:
1. Liberar salida Norte HOY (que firme el gerente de planta)
2. Lista de todos los nuevos sin inducción de emergencia - capacítalos esta semana
3. Nuevo simulacro en 30 días

¿Cuándo puedes instalar los amplificadores de alarma?",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Salida Norte liberada hoy mismo. Amplificadores llegan el viernes, instalación sábado. 8 personas sin inducción, las capacito mañana.",
            'created_at' => $createdAt->copy()->addHours(6),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Todo implementado. Simulacro sorpresa programado para el 5 de noviembre.",
            'created_at' => $createdAt->copy()->addDays(7),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Perfecto. Marco ticket resuelto. Espero resultados del próximo simulacro <5 min.",
            'created_at' => $createdAt->copy()->addDays(8),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    // ==================== TICKET 5: PENDING - QUEJA COMUNIDAD ====================
    private function createTicket5_QuejaComunidad(): void
    {
        $user = $this->users['Sergio'];
        $createdAt = Carbon::create(2025, 11, 25, 11, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)),
            'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['seguridad']->id ?? null,
            'area_id' => $this->areas['seguridad']->id ?? null,
            'title' => 'Queja comunidad Yapacaní: Reportan olor a gas desde campo Bulo Bulo',
            'description' => "Andrés,

Recibimos queja de la comunidad de Yapacaní.

**QUEJA:**
- Fuente: Dirigente vecinal Sr. Roberto Gutiérrez
- Reclamo: Olor fuerte a gas desde hace 3 días
- Zona: Comunidad Las Palmeras, 2 km del campo Bulo Bulo
- Personas afectadas: ~150 familias

**VERIFICACIÓN INICIAL:**
- Operaciones reporta: Sin fugas detectadas en instrumentación
- Antorcha: Operando normal
- Venteos programados: Ninguno en esa fecha

**POSIBLES CAUSAS:**
1. Fuga no detectada por instrumentos
2. Venteo de pozo terceros (hay operación privada a 5 km)
3. Causa natural (no relacionada con YPFB)

Necesito autorización para enviar brigada de verificación mañana.

Sergio Montaño
Relacionamiento Comunitario",
            'status' => 'pending', 'priority' => 'high',
            'owner_agent_id' => $this->agent->id, 'last_response_author_type' => 'agent',
            'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(5),
            'first_response_at' => $createdAt->copy()->addHours(2),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent',
            'content' => "Sergio,

Brigada autorizada. Pero no puede ser mañana, tiene que ser HOY. Una queja de este tipo puede escalar rápido a medios.

Lleva:
1. Detector de gases portátil
2. Cámara para documentar
3. Alguien de Relaciones Públicas por si hay prensa

Comunica al dirigente que estamos en camino. Reporta antes de las 6 PM.",
            'created_at' => $createdAt->copy()->addHours(2),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user',
            'content' => "Salimos en 30 min. Coordiné con RRPP, va Carla Mendoza. El dirigente agradeció la respuesta rápida.",
            'created_at' => $createdAt->copy()->addHours(3),
        ]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
