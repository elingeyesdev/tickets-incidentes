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
 * YPFB Tickets Seeder - Javier Ortiz
 * Temas: Sistemas, tecnología, soporte técnico IT
 */
class YPFBTicketsJavierOrtizSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'javier.ortiz@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Verónica', 'last_name' => 'Choque', 'email' => 'veronica.choque.sistemas12@gmail.com'],
        ['first_name' => 'Raúl', 'last_name' => 'Oporto', 'email' => 'raul.oporto.redes12@gmail.com'],
        ['first_name' => 'Diana', 'last_name' => 'Tarqui', 'email' => 'diana.tarqui.soporte12@gmail.com'],
        ['first_name' => 'Henry', 'last_name' => 'Mamani', 'email' => 'henry.mamani.infra12@gmail.com'],
        ['first_name' => 'Pamela', 'last_name' => 'Vaca', 'email' => 'pamela.vaca.desarrollo12@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Javier Ortiz...");
        $this->loadCompany();
        if (!$this->company) return;
        $this->loadAgent();
        if (!$this->agent) return;
        if ($this->alreadySeeded()) return;
        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();
        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Javier Ortiz");
    }

    private function loadCompany(): void { $this->company = Company::where('name', 'YPFB Corporación')->first(); if (!$this->company) $this->command->error('❌ YPFB no encontrada.'); }
    private function loadAgent(): void { $this->agent = User::where('email', self::AGENT_EMAIL)->first(); if (!$this->agent) $this->command->error('❌ Agente no encontrado.'); }
    private function alreadySeeded(): bool { if (Ticket::where('company_id', $this->company->id)->where('owner_agent_id', $this->agent->id)->count() >= self::TICKETS_PER_AGENT) { $this->command->info("[OK] Tickets ya existen."); return true; } return false; }

    private function loadAreas(): void {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = ['tecnologia' => $areas->firstWhere('name', 'Tecnología y Sistemas de Información')];
    }

    private function loadCategories(): void {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'equipo' => $cats->firstWhere('name', 'Problema de Equipo/Infraestructura') ?? $cats->first(),
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
        $this->createTicket1_CaidaServidores();
        $this->createTicket2_AtaqueCibernetico();
        $this->createTicket3_MigracionSAP();
        $this->createTicket4_BackupFallido();
        $this->createTicket5_NuevoSistema();
    }

    private function createTicket1_CaidaServidores(): void {
        $user = $this->users['Raúl'];
        $createdAt = Carbon::create(2025, 1, 20, 2, 30, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['interrupcion']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'CRÍTICO: Data center principal sin energía - Todos los sistemas caídos',
            'description' => "Javier,\n\nData center offline desde 02:15.\n\n**SITUACIÓN:**\n- Corte eléctrico a las 02:10\n- UPS falló a los 5 minutos\n- Generador NO arrancó (falla mecánica)\n- Todos los servidores apagados\n\n**SISTEMAS AFECTADOS:**\n- SAP (contabilidad, compras)\n- SCADA (control de plantas)\n- Correo corporativo\n- Portal web\n\n**ACCIONES:**\n- Técnico de UPS en camino (ETA 1 hora)\n- Mecánico de generador también llamado\n\n¿Qué más hago?\n\nRaúl Oporto\nNOC Infraestructura",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(3),
            'first_response_at' => $createdAt->copy()->addMinutes(10), 'resolved_at' => $createdAt->copy()->addHours(6), 'closed_at' => $createdAt->copy()->addDays(3),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Raúl,\n\nPrioridad 1: Generador. Sin energía no hay nada.\n\nMientras:\n1. Activa sitio de contingencia Cochabamba para SCADA (plantas no pueden quedar sin monitoreo)\n2. Notifica a Gerencia General\n3. Llámame cada 30 minutos\n\n¿Tienes acceso remoto a CBBA?", 'created_at' => $createdAt->copy()->addMinutes(10)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Sí, CBBA activado. SCADA operando desde allá. Generador: batería muerta, mecánico la está cambiando.", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Generador arrancó. UPS cargando. Empiezo a levantar servidores en orden de prioridad.", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Todos los sistemas arriba. Último: correo, restaurado 08:30. Operación normal.", 'created_at' => $createdAt->copy()->addHours(6)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Buen trabajo bajo presión. Prepara informe post-incidente y plan para evitar esto (redundancia de generador). Cierro ticket.", 'created_at' => $createdAt->copy()->addHours(8)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Ene)");
    }

    private function createTicket2_AtaqueCibernetico(): void {
        $user = $this->users['Verónica'];
        $createdAt = Carbon::create(2025, 3, 15, 16, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['interrupcion']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'ALERTA: Posible intento de intrusión detectado en firewall - 50,000 intentos/hora',
            'description' => "Javier,\n\nFirewall reporta actividad anómala.\n\n**DATOS:**\n- Origen: IPs de Europa del Este (Rumania, Ucrania)\n- Destino: Puerto 443 (HTTPS) y 22 (SSH)\n- Volumen: 50,000 intentos por hora\n- Inicio: 15:30 hoy\n\n**ACCIONES TOMADAS:**\n- Bloqueo de rangos IP sospechosos\n- Análisis de logs en curso\n- No hay evidencia de intrusión exitosa aún\n\n¿Notificamos a autoridades?\n\nVerónica Choque\nSeguridad Informática",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'user', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addMinutes(20), 'resolved_at' => $createdAt->copy()->addDays(3), 'closed_at' => $createdAt->copy()->addDays(5),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Verónica,\n\nNo a autoridades aún. Primero confirmemos si es ataque dirigido o escaneo automatizado.\n\nAcciones:\n1. Activa MFA obligatorio para todos los accesos externos\n2. Desconecta VPN no esenciales temporalmente\n3. Revisa si hay cuentas con intentos de login fallidos recientes\n\n¿El ataque continúa o bajó después del bloqueo?", 'created_at' => $createdAt->copy()->addMinutes(20)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Bajó a 5,000/hora después del bloqueo. Es escaneo automatizado, no ataque dirigido (confirmo por patrón).", 'created_at' => $createdAt->copy()->addHours(2)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "OK. Mantén bloqueos 48 horas. Programa pentesting externo para la próxima semana para validar que estamos seguros.", 'created_at' => $createdAt->copy()->addHours(3)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Pentesting completo. Sin vulnerabilidades críticas. El intento de intrusión quedó bloqueado. Situación controlada.", 'created_at' => $createdAt->copy()->addDays(4)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Mar)");
    }

    private function createTicket3_MigracionSAP(): void {
        $user = $this->users['Pamela'];
        $createdAt = Carbon::create(2025, 6, 10, 8, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Proyecto migración SAP S/4HANA - Cronograma y recursos requeridos',
            'description' => "Javier,\n\nNecesito aprobar plan de migración SAP.\n\n**PROYECTO:**\n- Migrar de SAP ECC 6.0 a S/4HANA\n- Fin de soporte ECC: 2027\n\n**CRONOGRAMA PROPUESTO:**\n- Fase 1 (Análisis): 3 meses\n- Fase 2 (Desarrollo): 6 meses\n- Fase 3 (Pruebas): 3 meses\n- Fase 4 (Go-live): 1 mes\n- Total: 13 meses\n\n**RECURSOS:**\n- Consultoría externa: USD 800,000\n- Licencias nuevas: USD 400,000\n- Hardware: USD 200,000\n- Total: USD 1,400,000\n\n¿Puedo presentar al Directorio?\n\nPamela Vaca\nDesarrollo de Sistemas",
            'status' => 'closed', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(20),
            'first_response_at' => $createdAt->copy()->addHours(4), 'resolved_at' => $createdAt->copy()->addDays(15), 'closed_at' => $createdAt->copy()->addDays(20),
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Pamela,\n\nAntes de Directorio necesito:\n1. Análisis de riesgos (downtime esperado)\n2. Plan de contingencia si falla migración\n3. 3 cotizaciones de consultoras (no solo una)\n\n¿Para cuándo puedes tener esto?", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "10 días. Ya tengo 2 cotizaciones, falta una.", 'created_at' => $createdAt->copy()->addDays(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Documentación completa adjunta. La mejor oferta es Deloitte: USD 720K (10% menos).", 'created_at' => $createdAt->copy()->addDays(12)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Excelente. Presento al Directorio el jueves. Prepárate para presentar si te llaman.", 'created_at' => $createdAt->copy()->addDays(13)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Directorio aprobó! USD 1.3MM autorizados. Inicio fase 1 en agosto. Cierro ticket, seguimiento en proyecto aparte.", 'created_at' => $createdAt->copy()->addDays(16)]);

        TicketAttachment::create(['ticket_id' => $ticket->id, 'response_id' => null, 'uploaded_by_user_id' => $user->id, 'file_name' => 'propuesta_migracion_sap_s4hana.pdf', 'file_path' => 'tickets/' . $ticket->id . '/propuesta_sap.pdf', 'file_type' => 'application/pdf', 'file_size_bytes' => rand(500000, 1000000), 'created_at' => $createdAt->copy()->addDays(12)]);

        $this->command->info("  ✓ Ticket CLOSED: {$ticket->ticket_code} (Jun)");
    }

    private function createTicket4_BackupFallido(): void {
        $user = $this->users['Henry'];
        $createdAt = Carbon::create(2025, 10, 5, 7, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Backup nocturno falló 3 noches consecutivas - Base de datos producción sin respaldo',
            'description' => "Javier,\n\nBackup de BD producción no se ejecuta desde el martes.\n\n**ERRORES:**\n- Martes: \"Disk full\" en servidor de backup\n- Miércoles: \"Connection timeout\" a BD\n- Jueves: \"Disk full\" de nuevo\n\n**RIESGO:**\n- 72 horas sin backup\n- Si hay pérdida de datos, perdemos 3 días de transacciones\n\n**CAUSA:**\n- Disco de backup al 100%\n- Retención configurada a 90 días (muy largo)\n\n¿Autoriza reducir retención a 30 días para liberar espacio?\n\nHenry Mamani\nAdministrador de Base de Datos",
            'status' => 'resolved', 'priority' => 'high', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(5),
            'first_response_at' => $createdAt->copy()->addMinutes(30), 'resolved_at' => $createdAt->copy()->addDays(2), 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Henry,\n\nAutorizo reducir a 30 días. Pero primero:\n1. Copia los backups >30 días a cintas (archivo histórico)\n2. Luego borra de disco\n3. Ejecuta backup manual AHORA - no podemos seguir sin respaldo\n\n¿Cuánto espacio libera esto?", 'created_at' => $createdAt->copy()->addMinutes(30)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Libera 2.3 TB. Archivando a cinta ahora. Backup manual programado para 10 AM.", 'created_at' => $createdAt->copy()->addHours(1)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "Backup manual exitoso. Espacio libre: 2.1 TB. Retención ajustada a 30 días.", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Bien. Configura alerta cuando disco llegue a 80%. No quiero enterarme cuando ya esté lleno. Marco resuelto.", 'created_at' => $createdAt->copy()->addDays(1)]);

        $this->command->info("  ✓ Ticket RESOLVED: {$ticket->ticket_code} (Oct)");
    }

    private function createTicket5_NuevoSistema(): void {
        $user = $this->users['Diana'];
        $createdAt = Carbon::create(2025, 11, 22, 11, 0, 0);

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-YPFB-' . strtoupper(Str::random(6)), 'created_by_user_id' => $user->id, 'company_id' => $this->company->id,
            'category_id' => $this->categories['equipo']->id ?? null, 'area_id' => $this->areas['tecnologia']->id ?? null,
            'title' => 'Requerimiento: Sistema de gestión de tickets interno para soporte TI',
            'description' => "Javier,\n\nNecesitamos sistema de tickets para TI.\n\n**SITUACIÓN ACTUAL:**\n- Solicitudes llegan por email, teléfono, WhatsApp\n- No hay registro centralizado\n- No hay SLAs ni métricas\n- Usuarios se quejan de falta de seguimiento\n\n**REQUERIMIENTOS:**\n- Portal web para usuarios\n- Asignación automática por tipo de incidente\n- SLAs configurables\n- Dashboard de métricas\n- Integración con Active Directory\n\n**OPCIONES:**\n1. ServiceNow (USD 50K/año)\n2. Jira Service Management (USD 25K/año)\n3. Desarrollo interno (USD 40K única vez)\n\n¿Cuál opción prefieres?\n\nDiana Tarqui\nSoporte Usuario Final",
            'status' => 'pending', 'priority' => 'medium', 'owner_agent_id' => $this->agent->id,
            'last_response_author_type' => 'agent', 'created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addHours(5),
            'first_response_at' => $createdAt->copy()->addHours(4), 'resolved_at' => null, 'closed_at' => null,
        ]);

        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'author_type' => 'agent', 'content' => "Diana,\n\nBuen análisis. Mis comentarios:\n\n- ServiceNow: Muy caro para nuestro tamaño\n- Jira: Buena opción, ya tenemos experiencia Atlassian\n- Desarrollo interno: Riesgoso por mantenimiento futuro\n\nMe inclino por Jira. Programa demo con Atlassian para la próxima semana y evalúa si cumple todos los requerimientos.", 'created_at' => $createdAt->copy()->addHours(4)]);
        TicketResponse::create(['ticket_id' => $ticket->id, 'author_id' => $user->id, 'author_type' => 'user', 'content' => "OK, coordino demo para el miércoles. ¿Quieres participar?", 'created_at' => $createdAt->copy()->addHours(5)]);

        $this->command->info("  ✓ Ticket PENDING: {$ticket->ticket_code} (Nov)");
    }
}
