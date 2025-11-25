<?php

declare(strict_types=1);

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
 * YPFB Tickets Seeder - Crisis de Hidrocarburos Bolivianos (Noviembre 2025)
 *
 * Simula la crisis real que enfrenta YPFB bajo la administración de Rodrigo Paz:
 * - Caída de producción de petróleo y gas (40% menos en última década)
 * - Crisis de importación de combustibles (90% diésel, 50% gasolina importados)
 * - Pérdida de mercado argentino (exportación de gas terminó)
 * - Deficit energético de $502 millones en 2024
 * - Ingresos bajaron 10.6% en 2024 a $4.422 billones
 * - Problemas logísticos de distribución nacional
 * - Advertencias de Paz contra corrupción en YPFB
 * - Gestión de nuevas exploración (56 proyectos exploratorios)
 *
 * Tickets que representan:
 * - Crisis de importación de combustibles
 * - Problemas logísticos de distribución
 * - Proyectos de exploración y producción
 * - Asuntos de corrupción/auditoría interna
 * - Negociaciones con mercados internacionales
 * - Problemas de infraestructura y refinerías
 * - Gestión de personal administrativo
 */
class YPFBTicketsSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';

    private Company $company;
    private array $categories;
    private array $agents;
    private array $users = [];

    public function run(): void
    {
        $this->command->info('⛽ Creando tickets realistas para YPFB (Crisis de Hidrocarburos Bolivianos - Noviembre 2025)...');

        // Find YPFB company
        $this->company = Company::where('name', 'YPFB Corporación')->first();

        if (!$this->company) {
            $this->command->error('❌ YPFB Corporación no encontrada. Ejecuta RealBolivianCompaniesSeeder primero.');
            return;
        }

        // Get categories
        $this->loadCategories();

        if (empty($this->categories)) {
            $this->command->error('❌ No hay categorías disponibles. Ejecuta DefaultCategoriesSeeder primero.');
            return;
        }

        // Get agents (Patricia Alanoca and Miguel Pacheco)
        $this->loadAgents();

        // Create internal users (directivos, jefes de departamento)
        $this->createUsers();

        // Create tickets reflecting YPFB crisis
        $this->createTickets();

        $this->command->info('✅ Seeder de tickets YPFB completado!');
    }

    private function loadCategories(): void
    {
        $categories = Category::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        // YPFB es energía, usa categorías relevantes
        $this->categories = [
            'supply_chain' => $categories->firstWhere('name', 'Service Outage'),
            'equipment_issue' => $categories->firstWhere('name', 'Equipment Problem'),
            'production_delay' => $categories->firstWhere('name', 'Maintenance Request'),
            'quality_problem' => $categories->firstWhere('name', 'Billing Dispute'),
            'safety_concern' => $categories->firstWhere('name', 'Safety Concern'),
        ];
    }

    private function loadAgents(): void
    {
        $this->agents = [
            'patricia' => User::where('email', 'patricia.alanoca@ypfb.gob.bo')->first(),
            'miguel' => User::where('email', 'miguel.pacheco@ypfb.gob.bo')->first(),
        ];
    }

    private function createUsers(): void
    {
        $usersData = [
            [
                'first_name' => 'Yussef',
                'last_name' => 'Akly',
                'email' => 'yussef.akly@ypfb.gob.bo',
                'role' => 'Presidente YPFB (Designado por Paz - Nov 2025)',
            ],
            [
                'first_name' => 'Margot',
                'last_name' => 'Ayala',
                'email' => 'margot.ayala@anh.gob.bo',
                'role' => 'Directora ANH - Agencia Nacional de Hidrocarburos',
            ],
            [
                'first_name' => 'Javier',
                'last_name' => 'Montecinos',
                'email' => 'javier.montecinos@ypfb.gob.bo',
                'role' => 'Jefe Área Exploración y Producción',
            ],
            [
                'first_name' => 'Sandra',
                'last_name' => 'Quispe',
                'email' => 'sandra.quispe@ypfb.gob.bo',
                'role' => 'Jefa Logística e Importaciones',
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'email' => 'carlos.mendoza@ypfb.gob.bo',
                'role' => 'Jefe Distribución de Combustibles',
            ],
            [
                'first_name' => 'Amalia',
                'last_name' => 'Torres',
                'email' => 'amalia.torres@ypfb.gob.bo',
                'role' => 'Directora de Auditoría Interna',
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Garnica',
                'email' => 'roberto.garnica@ypfb.gob.bo',
                'role' => 'Jefe Operaciones Refinería Guillermo Elder',
            ],
        ];

        foreach ($usersData as $userData) {
            $email = $userData['email'];

            $user = User::where('email', $email)->first();

            if ($user) {
                $this->command->warn("⚠ Usuario ya existe: {$email}");
                $this->users[$userData['first_name']] = $user;
                continue;
            }

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
        // Crisis de importación de combustibles
        $this->createTicket1_FuelImportCrisis();

        // Pérdida de mercado argentino
        $this->createTicket2_ArgentinaGasMarket();

        // Problemas logísticos de distribución nacional
        $this->createTicket3_DistributionLogistics();

        // Exploración y nuevos proyectos
        $this->createTicket4_ExplorationProjects();

        // Auditoría por corrupción (advertencia de Paz)
        $this->createTicket5_CorruptionAudit();

        // Crisis de caja/tesorería
        $this->createTicket6_TreasuryDebt();

        // Problemas en refinería Guillermo Elder
        $this->createTicket7_RefineryIssue();

        // Contratación de personal técnico especializado
        $this->createTicket8_HiringEngineers();

        // Infraestructura de gasoductos
        $this->createTicket9_PipelineInfrastructure();

        // Negociaciones con proveedores internacionales
        $this->createTicket10_SupplierNegotiations();

        // Problema de almacenamiento de combustibles
        $this->createTicket11_StorageCapacity();

        // Compliance y presión política de Paz
        $this->createTicket12_ComplianceGovernance();
    }

    // ==================== TICKET 1: OPEN - CRISIS DE IMPORTACIÓN ====================
    private function createTicket1_FuelImportCrisis(): void
    {
        $user = $this->users['Sandra'];
        $agent = $this->agents['patricia'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00001',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['supply_chain']->id,
            'title' => 'URGENTE: Crisis de importación - Stock de combustibles para 2-3 días',
            'description' => "Patricia,

Situación crítica en importación de combustibles.

STATUS ACTUAL (Nov 25, 2025):
- Stock nacional diésel: ~52 horas (deberíamos tener 15+ días)
- Stock nacional gasolina: ~68 horas
- Importamos 90% del diésel consumido nacionalmente
- Importamos 50% de la gasolina consumida

PROBLEMA INMEDIATO:
1. Envío de 15,000 barriles de diésel de Arica (Chile) retrasado 5 días
2. Buque tanque que debería llegar hoy está en puerto de origen por falta de dólares
3. Línea de crédito con Petroecuador agotada (deuda acumulada: $78.5MM)
4. Proveedores exigen pago contado (perdimos crédito después de 2 impagos en octubre)

IMPACTO:
- Riesgo de desabastecimiento en La Paz y Santa Cruz en 48 horas
- Transporte de pasajeros en riesgo
- Operaciones mineras podrían paralizar

¿Qué gestiones podemos hacer con CEPAL o Banco Interamericano para línea de crédito emergente?
Presidencia Paz presionando. Necesitamos solución antes del 27-11-2025.

Nota: Armin Dorgathen fue despedido por esto. No podemos fallar nuevamente.",
            'status' => 'open',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(1),
            'first_response_at' => now()->subHours(3),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Sandra, he coordinado con Yussef (nuevo presidente).

ACCIONES EN CURSO:
1. Gestión con CEPAL por línea puente
2. Contacto directo con Petroecuador para renovar crédito (máximo 10 días)
3. Evaluación de compra spot en mercado spot si precio < $75/barril

PARALELO - COMUNICADO A PRESIDENCIA:
He informado a Paz que esto requiere decisión política urgente sobre:
- Desbloqueo de líneas de crédito estratégicas
- O decisión de aumentar precio combustibles domésticos

Manejo de comunicación: Mantener confidencial. Paz amenazó con procesos judiciales a quien 'sabotee' distribución.

Status next: Reunión Yussef + Margot (ANH) + Finanzas mañana 06:00 AM.",
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(1),
        ]);

        $this->command->info("  ✓ Ticket OPEN creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 2: PENDING - PÉRDIDA MERCADO ARGENTINA ====================
    private function createTicket2_ArgentinaGasMarket(): void
    {
        $user = $this->users['Javier'];
        $agent = $this->agents['miguel'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00002',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['supply_chain']->id,
            'title' => 'Post-mortem: Pérdida de mercado Argentina - Contrato gas finalizado',
            'description' => "Miguel,

Análisis retrospectivo de la pérdida del mercado argentino (contrato finalizado junio 2024).

CONTEXTO HISTÓRICO:
- 2014: Bolivia exportaba 17.608 MMm³/año a Argentina por $6.011 billones
- 2024: Bolivia exportó 8.062 MMm³/año = $2.050 billones
- Reducción: ~54% en volumen, ~66% en ingresos

CAUSA RAÍZ - FALTA DE EXPLORACIÓN:
- Décadas sin inversión en nuevos campos exploratorios
- Campos maduros (San Alberto, Margarita) en declinación natural
- Producción total Bolivia: 21.766 Bm³ (2012) → 13.122 Bm³ (2023) = -40%

MERCADO ARGENTINA AHORA:
- Argentina desarrolló producción propia (Vaca Muerta - shale)
- Ya no necesita gas boliviano
- Contratos no renovados

PANORAMA 2025:
- Se perdieron ingresos anuales por $3.961 billones
- Este dinero se usaría para importar combustibles
- Ahora Bolivia COMPRA más gas de lo que vende

PREGUNTA ESTRATÉGICA:
¿Invertir ahora en 56 proyectos exploratorios (plan actual) puede recuperar mercados perdidos?
¿Cuántos años hasta que sean productivos?
¿Tenemos capital para esperar?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(3),
            'first_response_at' => now()->subDays(7),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Javier, análisis sombrío pero realista.

REALIDAD 2025:
- Recuperar Argentina: IMPOSIBLE en mediano plazo
- Vaca Muerta es competencia directa
- Brasil también desarrolló pre-sal

ALTERNATIVA - NUEVOS MERCADOS:
1. Perú: Refinería Talara (conversación con Petroperú en curso)
2. Chile: Demanda por regasificación en Mejillones
3. Brasil: Mercado spot si precio competitivo

TIMING CRÍTICO:
- 56 proyectos exploratorios mencionados por Paz: realista?
- Costo estimado: ~$4.5-6 billones (capital que no tenemos)
- Tiempo hasta producción: 5-8 años

Mi recomendación: Enfocar en 5-6 proyectos de ALTO IMPACTO en vez de 56 'salpicar'.
Presentar a Yussef + Margot propuesta de cartera optimizada.

Status: Pendiente aprobación de dirección.",
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(3),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 3: PENDING - LOGÍSTICA DISTRIBUCIÓN ====================
    private function createTicket3_DistributionLogistics(): void
    {
        $user = $this->users['Carlos'];
        $agent = $this->agents['patricia'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00003',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['production_delay']->id,
            'title' => 'Fallo logístico: Distribución retrasada a provincias - Cuello de botella La Paz',
            'description' => "Patricia,

Crisis logística de distribución nacional.

PROBLEMA:
1. Camiones cisterna (distribuidoras privadas) se quedan en La Paz esperando 24-48 horas carga
2. Capacidad depósito La Paz: 2.100 m³ (diseño: máximo 1.500 m³ en stock)
3. Presión en bomba de descarga: sobrecalentamiento (falla frecuente)
4. Resultado: Retrasos de 2-3 días en abastecimiento a Santa Cruz, Cochabamba, Potosí

CAUSA:
- Coordinación con puertos (Arica) deficiente
- Aduanas Chile retiene carga 12-18 horas sin justificación
- Sistema SCADA de monitoreo descalibrado

IMPACTO ECONÓMICO:
- Distribuidoras privadas pagan multa por atraso entrega (Bs. 15,000-20,000/día por cisterna)
- Algunos clientes (minería) cambian a proveedores alternativos
- Pérdida marginal: ~Bs. 2.5MM/día

CONVERSACIÓN CON DISTRIBUIDORAS PRIVADAS:
Amenazan con 'buscar otras opciones si seguimos con estos retrasos'. Significa: import directo desde Arica/Callao, saltándonos.

¿Cuándo podemos invertir en:
1. Expansión depósito La Paz ($2.5MM)
2. Sistema SCADA moderno ($1.2MM)
3. Personal técnico calibración?

Paz visita La Paz el 28-11. Necesito respuesta para reportar progreso.",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(1),
            'first_response_at' => now()->subDays(4),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Carlos, esto es CRÍTICO.

Si distribuidoras se van a importación directa = pérdida control nacional.

GESTIÓN PARALELA:
1. Contacté a Aduanas Antofagasta (presión diplomática vía Yussef)
2. Evaluando compra de 2 bombas de descarga backup (leasing: $8,500/mes c/u)
3. Coordinación con distribuidoras: ofrecemos 'corredor express' para grandes volúmenes

REALIDAD FINANCIERA:
- Presupuesto inversión 2025: CERO disponible
- Opciones:
  a) Leasing equipo (corto plazo, caro)
  b) Renegociar contrato con distribuidoras (aceptar margen menor)
  c) Crédito BID/CAF (trámite 4-6 meses)

Status: Escalado a Yussef para decisión. Este es problema 'Paz' (corrupción/negligencia).",
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(1),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 4: OPEN - EXPLORACIÓN PROYECTOS ====================
    private function createTicket4_ExplorationProjects(): void
    {
        $user = $this->users['Javier'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00004',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['production_delay']->id,
            'title' => 'Evaluación de viabilidad: 56 proyectos exploratorios anunciados por Paz',
            'description' => "Equipo técnico,

Paz anunció en campaña: 'YPFB desarrolla 56 proyectos exploratorios para revertir declinación'.

NECESITO CLARIDAD INMEDIATA:

1. ¿De dónde salen estos 56 proyectos?
   - ¿Estudios pre-existentes?
   - ¿Greenfield exploration?
   - ¿Partnerships con privados?

2. PRESUPUESTO ESTIMADO:
   - Costo exploración promedio: $80-120MM por proyecto
   - 56 × $100MM = $5.600 BILLONES
   - YPFB presupuesto anual: $4.422 billones
   - ¿De dónde sacamos $5.6 billones?

3. TIMELINE:
   - Desde exploración a producción: 5-8 años
   - Paz espera resultados INMEDIATOS
   - ¿Tenemos presión para acelerar?

4. CONTEXTO POLÍTICO:
   - Paz presionando contra 'corrupción' y 'negligencia'
   - Cada retraso será interpretado como sabotaje
   - Riesgo: Culpar a 'burócratas incompetentes' y reemplazarlos

ACCIÓN REQUERIDA:
- Revisar viabilidad técnica REAL de estos 56 proyectos
- Hacer cost-benefit analysis
- Presentar plan REALISTA a Paz (no prometedor)

RECOMENDACIÓN:
'Sr. Presidente, en vez de 56 proyectos (riesgo dispersión), proponemos 6 megaproyectos de ALTO IMPACTO'

Este es un tema de sobrevivencia política para YPFB.",
            'status' => 'open',
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->command->info("  ✓ Ticket OPEN creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 5: PENDING - AUDITORÍA CORRUPCIÓN ====================
    private function createTicket5_CorruptionAudit(): void
    {
        $user = $this->users['Amalia'];
        $agent = $this->agents['miguel'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00005',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['quality_problem']->id,
            'title' => 'CONFIDENCIAL: Auditoría especial por indicios de corrupción (Presión de Paz)',
            'description' => "Miguel,

INFORMACIÓN CLASIFICADA - SOLO PARA DIRECCIÓN.

Paz ordenó auditoría especial contra gestión anterior (Armin Dorgathen - DESPEDIDO 7-11-2025).

INDICIOS INVESTIGADOS:
1. Contratación favores a empresa 'TecnoImport SA' por importación combustibles
   - Sobre-facturación estimada: $12-18MM
   - Propietario: Familiar de funcionario (conflicto interés)

2. Licitaciones no competitivas para transporte combustibles
   - Empresas 'amigas' adjudicadas sin público proceso
   - Pérdida estimada: $8-12MM en márgenes

3. Desaparición de registros (2023-2024) de auditoría de caja
   - 47 movimientos no documentados
   - Monto: $2.3MM

4. Personal fantasma en nómina depósito La Paz
   - 8 personas cobran sin asistir
   - Costo anual: $180,000

FISCALÍA INVOLUCRADA:
- Fiscal anticorrupción solicitó ALERTA MIGRATORIA contra Dorgathen
- Posible extradición si sale de Bolivia

PARA YPFB:
- Imagen dañada: 'Corrupción desenfrenada'
- Presión de Paz para 'demostrar cambio'
- Riesgo: Más ejecutivos involucrados

ACCIÓN RECOMENDADA:
- Cooperación plena con Fiscalía
- Recuperar fondos (litigio)
- Implementar controles internos FUERTES

Status: Amalia (Auditoría Interna) coordinando con Fiscalía.",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(2),
            'first_response_at' => now()->subDays(6),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Amalia, situación delicada.

COORDINACIÓN CON FISCALÍA:
1. Proporcionamos todos registros de auditoría interna (2022-2025)
2. Identifiquemos beneficiarios reales de TecnoImport (empresa fantasma probable)
3. Registros de nómina: verificar asistencia física vs. registros

PARALELO - CONTROL INTERNO REFORZADO:
- Yussef firmó orden de AUDIT SORPRESA mensual (random proveedores)
- Sistema de aprobación de compras: requiere 3 firmas (antes era 1)
- Auditoría externa (KPMG) contratada para 2025

COMUNICADO A PRESIDENCIA:
'YPFB implementando medidas anticorrupción más estrictas que cualquier empresa pública'.

RIESGO REPUTACIONAL:
- Medios ya reportan 'corrupción masiva' en YPFB
- Necesitamos comunicado de Paz diciendo 'gobierno nuevo = tolerancia cero'
- De lo contrario: pérdida de credibilidad

Status: Reunión con Fiscalía programada para 1-12-2025.",
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(2),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 6: CLOSED - DEUDA TESORERÍA ====================
    private function createTicket6_TreasuryDebt(): void
    {
        $user = $this->users['Yussef'];
        $agent = $this->agents['patricia'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00006',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['supply_chain']->id,
            'title' => 'Resolución: Reestructuración deuda YPFB - $502MM deficit energético 2024',
            'description' => "Patricia,

Yussef (nuevo presidente) solicita estrategia reestructuración deuda.

CONTEXTO FISCAL:
- Ingresos 2024: $4.422 billones
- Gasto operativo: $4.924 billones
- Deficit operacional: $502 millones
- Deuda acumulada: ~$3.2 billones (estimado con Petroecuador + proveedores)

CAUSA RAÍZ:
- Exportaciones cayeron (perdida Argentina, producción en declive)
- Costos operativos fijos (no se pueden reducir sin afectar producción)
- Importación combustibles deficitaria: importar > exportar

OPCIONES PRESENTADAS:
1. Aumentar precio combustibles domésticos (+25-35%)
   - Efecto: Reducir consumo subsidio, aumentar ingresos
   - Riesgo político: Población rechaza aumentos

2. Aumentar precio gas a industrial/minería (-5-10%)
   - Efecto: Aumentar ingresos sin afectar consumidor
   - Realidad: Minería ya es baja (desempleo)

3. Buscar financiamiento externo (CAF, BID, IDB)
   - Efecto: Corto plazo, resolver flujo
   - Realidad: CAF/BID piden 'ajustes estructurales'

4. Privatizar operaciones no-core
   - Efecto: Entrada de efectivo único
   - Realidad: Políticamente imposible

DECISIÓN TOMADA (Yussef + Paz):
- Opción mixta: Aumentar precio doméstico +12% (moderado)
- Mantener gas industrial subsidio
- Buscar línea puente CAF por $250MM

Implementación: enero 2026.",
            'status' => 'closed',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(1),
            'first_response_at' => now()->subDays(3),
            'resolved_at' => now()->subDays(1),
            'closed_at' => now()->subDays(1),
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Yussef,

Opción elegida (+12% doméstico) es REALISTA.

ANÁLISIS:
- +12% = aumento de ~Bs. 0.50/litro en gasolina (Bs. 4.80 → 5.30)
- Recupera ~$150-180MM anuales (30% de deficit)
- Sector minería/industrial mantiene subsidio (no afecta producción)

COMUNICADO A POBLACIÓN (propuesta):
'Ajuste tarifario necesario para mantener abastecimiento nacional. Inversión en exploración asegura combustibles a largo plazo.'

IMPLEMENTACIÓN:
- Decreto presidencial: diciembre 2025
- Efectivo: enero 2026
- Período de transición: 3 meses (con comunicación)

CAF CONTACTADO:
- Línea puente $250MM: viable (2-3 meses trámite)
- Condiciones: Auditoría completa + plan anticorrupción (implementado)

Status: CERRADO. Decreto a revisión de Paz.",
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(1),
        ]);

        $this->command->info("  ✓ Ticket CLOSED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 7: RESOLVED - PROBLEMAS REFINERÍA ====================
    private function createTicket7_RefineryIssue(): void
    {
        $user = $this->users['Roberto'];
        $agent = $this->agents['miguel'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00007',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipment_issue']->id,
            'title' => 'Falla de equipo: Unidad destilación Refinería Guillermo Elder + plan mantenimiento',
            'description' => "Miguel,

Falla en unidad de destilación (fraccional tower) en refinería Guillermo Elder, Cochabamba.

EVENTO:
- Fecha: 22-11-2025, 14:30
- Equipo: Destillation Column (proceso clave crudo → combustibles)
- Falla: Sobrepresión en línea vapor (válvula stuck)
- Resultado: Parada de unidad por 36 horas

IMPACTO:
- Producción refinería: 30,000 barriles/día → parada completa
- Gasolina producida: CERO (durante parada)
- Diésel producido: CERO
- Fuel oil industrial: CERO

FINANCIERO:
- Ingresos no percibidos: ~$900,000/día
- Costo reparación: $45,000
- Presión de Paz: Máxima (después de Dorgathen despedido)

CAUSA:
- Falta de mantenimiento preventivo (histórico)
- Equipo original 1992 (33 años operación)
- Repuestos: costosos, lead time 6 semanas

ACCIONES TOMADAS:
1. Equipo técnico cambió válvula (reparación temporal)
2. Unidad back online (23-11-2025, 08:00)
3. Inspeccionado equipo: vida útil CRÍTICA

RECOMENDACIÓN URGENTE:
- Planificar overhaul COMPLETO del destillation column: 2026
- Presupuesto: $2.8MM (cambio completo)
- Timing: Parada de 45 días (capacidad perdida)
- Alternativa: Arrendar unidad mobile (costoso pero rapido)

¿Podemos presupuestar esto para 2026? ¿O esperamos siguiente falla?",
            'status' => 'resolved',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(1),
            'first_response_at' => now()->subDays(2),
            'resolved_at' => now()->subDays(1),
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Roberto,

Sobrealzar criticidad: Equipo de 33 años.

PLAN PROPUESTO:
1. Q1 2026: Overhaul major (parada 45 días)
   - Costo: $2.8MM
   - Recuperación: $27MM en producción adicional (estimado)

2. Paralelo: Mantenimiento preventivo mensual (en operación)
   - Costo: $18,000/mes
   - Beneficio: Detectar problemas antes de falla

PRESUPUESTO:
- 2026: $2.8MM capital + $216,000 operativo = $3.016MM
- Comparar: Arriendo unidad mobile = $150,000/mes = $1.8MM/año (ineficiente)

COMUNICADO A YUSSEF:
'Inversión overhaul refinería = recuperar 30,000 bbl/día. Retorno: 4 meses.'

TIMING CRÍTICO:
- Diciembre: Planificar detalle técnico
- Enero: Contratar contratista
- Febrero: Procurar partes
- Marzo-abril: Ejecución

Status: RESUELTO. Plan de mantenimiento 2026 aprobado. Roberto: coordina con procurement para contratar reparador.",
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(1),
        ]);

        $this->command->info("  ✓ Ticket RESOLVED creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 8: PENDING - CONTRATACIÓN PERSONAL ====================
    private function createTicket8_HiringEngineers(): void
    {
        $user = $this->users['Javier'];
        $agent = $this->agents['patricia'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00008',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['production_delay']->id,
            'title' => 'Contratación urgente: Ingenieros especializados exploración y producción',
            'description' => "Patricia,

Necesitamos reforzar equipo técnico para ejecutar plan de exploración.

SITUACIÓN ACTUAL:
- Equipo exploración YPFB: 12 ingenieros (muy bajo)
- 56 proyectos exploratorios anunciados (o 6 megaproyectos realistas)
- Ratio: 1 ingeniero por ~5-10 proyectos (insuficiente)

COMPETENCIA POR TALENTO:
- Minería internacional (BHP, Rio Tinto, Vale) pagan 40-60% más
- Ingenieros bolivianos se van a Perú/Chile/Colombia
- Escasez de especialistas en gas/petróleo en región

PUESTOS CRÍTICOS A LLENAR:
1. Ingeniero senior exploración (seismic interpretation)
   - Salario: $4,500-6,000/mes
   - Candidatos: Escasos (2-3 en mercado)

2. Ingeniero reservoirs (3-4 personas)
   - Salario: $3,500-4,500/mes
   - Candidatos: Disponibles pero competencia

3. Ingeniero producción/perforación (2-3 personas)
   - Salario: $3,000-4,000/mes
   - Candidatos: Moderados

COSTO TOTAL CONTRATACIÓN:
- 6-8 nuevos ingenieros = $200,000-300,000/año
- Capacitación adicional: $80,000/año
- Total: ~$380,000/año

PROCESO:
- Publicar convocatoria: 2 semanas
- Entrevistas: 2-3 semanas
- Onboarding: 1 mes
- Timeline total: 8-10 semanas

¿Aprueba presupuesto para contratación? ¿O esperamos que plan falle por falta de personal?",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(1),
            'first_response_at' => now()->subDays(3),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Javier,

Contratación de talento = factor CRÍTICO para éxito de exploración.

PROPUESTA:
- Presupuesto 2025: $250,000 para 4-5 ingenieros
- Resto: 2026+ (depende de financiamiento CAF/BID)

ESTRATEGIA RECLUTAMIENTO:
1. Contactar ingenieros en minería (BHP, Rio Tinto) - ofrecer proyecto emocionante
2. Networking con universidades (UMSA, UNI) - búsqueda de jóvenes promesa
3. Propuesta atractiva: 'Reverting energy crisis' = marca personal

COMPENSACIÓN:
- Salario: Comprimido (YPFB limitado)
- Benefit: Stock options (futura privatización)
- Oportunidad: Participar en megaproyectos históricos

RIESGO:
- Si talento se va a competencia = detrás de schedule
- Necesitamos personas retenerlas (presupuestar retención: bonos, carrera)

Status: Solicité a RRHH publicar convocatoria (inicio 27-11-2025).",
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(1),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 9: OPEN - INFRAESTRUCTURA GASODUCTOS ====================
    private function createTicket9_PipelineInfrastructure(): void
    {
        $user = $this->users['Javier'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00009',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['equipment_issue']->id,
            'title' => 'Evaluación crítica: Infraestructura de gasoductos - Edad promedio 30+ años',
            'description' => "Equipo técnico,

ALERTA INFRAESTRUCTURA:

Bolivia tiene red de gasoductos valiosa pero VIEJA:

RED PRINCIPAL:
1. Gasoducto San Alberto-Arica (850 km)
   - Construido: 1972
   - Edad: 53 años
   - Capacidad original: 34.6 MMm³/día
   - Capacidad actual: ~20 MMm³/día (degradación)

2. Gasoducto La Paz-Arica (950 km)
   - Construido: 1980
   - Edad: 45 años
   - Estado: Requiere reemplazo parcial

3. Red interna distribución (2,000+ km)
   - Edad promedio: 30+ años
   - Mantenimiento: REACTIVO (solo cuando falla)
   - Fugas: ~8-12% gas (pérdida financiera)

RIESGOS:
1. Fuga catastrófica (corrosión interna)
2. Caída de presión (desabastecimiento)
3. Contaminación (corrosión libera óxidos)

COSTO REEMPLAZO:
- Gasoducto nuevo 850 km: ~$1.5-2.0 billones
- Red interna: ~$800MM
- Total: ~$2.8 billones

CONTEXTO:
- YPFB no tiene dinero
- Asociación público-privada: opción (pero Paz rechaza 'privatización')
- Internacional: Perú, Colombia, Brasil invierten en infraestructura

PREGUNTA:
¿Podemos desarrollar exploración si no aseguramos infraestructura confiable?

Plan de acción requerido: URGENTE.",
            'status' => 'open',
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->command->info("  ✓ Ticket OPEN creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 10: PENDING - NEGOCIACIONES PROVEEDORES ====================
    private function createTicket10_SupplierNegotiations(): void
    {
        $user = $this->users['Sandra'];
        $agent = $this->agents['miguel'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00010',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['supply_chain']->id,
            'title' => 'Negociación: Renovación línea crédito Petroecuador - Deuda $78.5MM',
            'description' => "Miguel,

Crisis crediticia inmediata.

SITUACIÓN:
- Deuda YPFB a Petroecuador: $78.5MM
- Línea de crédito aprobada: $100MM
- Línea utilizada: 100% agotada
- Status: Petroecuador suspendió entregas nuevas hasta pago

HISTÓRICO DE PAGOS:
- Octubre 2024: Pago NO realizado (no había dólares)
- Noviembre 2024: Pago NO realizado (no había dólares)
- Diciembre 2024: ??? (Paz presionando 'resolver ahora')

COMUNICACIÓN PETROECUADOR (confidencial):
- 'Bolivia no es prioridad' (Perú, Colombia pagan al día)
- Opciones:
  a) Pagar $78.5MM inmediatamente (IMPOSIBLE)
  b) Refinanciamiento a 18 meses (interés: 5.5% = $4.3MM adicional)
  c) Cortar relaciones y buscar proveedor alternativo (Perú, Colombia)

ALTERNATIVA - PROVEEDORES NUEVOS:
1. Petroperú (Perú)
   - Refinería Talara: 90,000 bbl/día capacidad
   - Precio: Competitivo
   - Condición: Pago 30 días contra documento
   - Riesgo: Pérdida de relación Petroecuador

2. Ecopetrol (Colombia)
   - Precio: Más caro (+2%)
   - Plazo: 45 días
   - Logística: Más difícil (transporte terrestre)

RECOMENDACIÓN:
- Negociar con Petroecuador: Refinanciamiento 18 meses
- Paralelo: Contactar Petroperú como Plan B
- Comunicado a Paz: 'Diversificando proveedores para no depender de uno'

¿Presupuesto para gestión? Necesito viajar a Quito (negociación en persona).",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subHours(30),
            'updated_at' => now()->subHours(5),
            'first_response_at' => now()->subHours(24),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Sandra,

Negociación CRÍTICA. Aprobado viajar a Quito.

ESTRATEGIA:
1. Enfoque: 'Alianza regional Bolivia-Ecuador' (no transaccional)
2. Propuesta: Refinanciamiento 18 meses con pago inicial $20MM (enero)
3. Paralelo: Mencionar Petroperú como alternativa (presión ligera)

CONTEXTO ECUATORIANO:
- Petroecuador en crisis similiar (deuda, producción cayendo)
- Interés en alianza con Bolivia = mercado cautivo
- Pero: No pueden perdonar deuda (presión interna)

TIMING:
- Vuelo: Mañana 26-11
- Reunión: 27-11 (Directivos Petroecuador)
- Objetivo: Acuerdo firmado 28-11

COMUNICADO POST-REUNIÓN:
- Anunciar 'Alianza energética Bolivia-Ecuador'
- Mencionar Paz como impulsador
- Generar goodwill político

Presupuesto aprobado: $4,500 (pasaje, hotel, viáticos).",
            'created_at' => now()->subHours(24),
            'updated_at' => now()->subHours(5),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 11: PENDING - CAPACIDAD ALMACENAMIENTO ====================
    private function createTicket11_StorageCapacity(): void
    {
        $user = $this->users['Carlos'];
        $agent = $this->agents['patricia'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00011',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['production_delay']->id,
            'title' => 'Capacidad de almacenamiento: Depósitos nacionales en máximo - Cuello botella crítico',
            'description' => "Patricia,

CRISIS DE ESPACIO DE ALMACENAMIENTO:

DIAGNÓSTICO ACTUAL (Nov 25, 2025):

Depósito La Paz:
- Capacidad diseño: 1,500 m³
- Stock actual: 2,100 m³ (140% capacidad!)
- Estado: Sobrecargado, bombas bajo presión, riesgo falla

Depósito Santa Cruz:
- Capacidad diseño: 2,000 m³
- Stock actual: 2,800 m³ (140% capacidad)
- Estado: Válvulas venteo abierto (pérdida evaporación)

Depósito Cochabamba:
- Capacidad diseño: 800 m³
- Stock actual: 1,050 m³ (131% capacidad)
- Estado: Alerta crítica

RED NACIONAL DE DEPÓSITOS:
- Capacidad total: 8,500 m³
- Stock total: 11,200 m³ (132% capacidad!)
- Situación: INSOSTENIBLE

CAUSA:
- Importaciones llegan en volúmenes, falta flujo de salida
- Distribuidoras lentas (problema logística anterior)
- Sobreflujo = riesgo de derrames

RIESGOS INMEDIATOS:
1. Falla estructural de depósito
2. Derrame ambiental (Bs. 500,000+ multa)
3. Pérdida por evaporación: ~50,000 litros/mes = Bs. 2.5MM/año
4. Presión regulatoria ambiental (Paz presionando sostenibilidad)

SOLUCIONES:
A) CORTO PLAZO (emergencia):
   - Alquilar tanques móviles: 2-3 unidades (Bs. 8,000/mes c/u)
   - Acelerar distribución (coordinación mejor)

B) MEDIANO PLAZO (6-12 meses):
   - Expandir depósito La Paz: +1,000 m³ (Bs. 3.5MM)
   - Construir nuevo depósito regional (Oruro): 1,500 m³ (Bs. 5MM)

C) LARGO PLAZO (2026+):
   - Red nacional optimizada (Bs. 25MM inversión)

¿Cuál es prioritario? Tanques móviles son caros pero rápido.
Sin solución: Riesgo de derrames en 2-3 semanas.",
            'status' => 'pending',
            'owner_agent_id' => $agent->id,
            'last_response_author_type' => 'agent',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subHours(8),
            'first_response_at' => now()->subDays(1),
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketResponse::create([
            'ticket_id' => $ticket->id,
            'author_id' => $agent->id,
            'author_type' => 'agent',
            'content' => "Carlos,

Alquiler tanques móviles es URGENTE.

ACCIÓN INMEDIATA:
- He contactado a 2 proveedores de tanques móviles (Bolivia + Argentina)
- Capacidad disponible: 3 unidades de 600 m³ c/una
- Costo: Bs. 7,500/mes (menos que estimado)
- Entrega: 5-7 días

INSTALACIÓN:
- La Paz: 1 tanque (reserva de seguridad)
- Santa Cruz: 1 tanque (buffer distribución)
- Cochabamba: 1 tanque (completa red)

EFECTO:
- Reduce presión depósitos de 132% → 95% (seguro)
- Costo mensual: Bs. 22,500 (~$3,300/mes)
- ROI: Evitar derrame = ahorro de Bs. 500,000+

PRESUPUESTO:
- 2025: Bs. 67,500 (3 meses: nov-dic-ene)
- 2026: Bs. 90,000 (12 meses)
- Vs. expansión depósito: Bs. 3.5MM (más caro, más lento)

Status: Autorizado alquiler. Esperar contrato firmado (48 horas).",
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subHours(8),
        ]);

        $this->command->info("  ✓ Ticket PENDING creado: {$ticket->ticket_code}");
    }

    // ==================== TICKET 12: OPEN - GOVERNANCE COMPLIANCE PAZ ====================
    private function createTicket12_ComplianceGovernance(): void
    {
        $user = $this->users['Yussef'];

        $ticket = Ticket::create([
            'ticket_code' => 'TKT-2025-00012',
            'created_by_user_id' => $user->id,
            'company_id' => $this->company->id,
            'category_id' => $this->categories['quality_problem']->id,
            'title' => 'ESTRATEGIA: Plan 100 días YPFB bajo nueva administración Paz',
            'description' => "Equipo de dirección,

Paz asumió presidencia: 08-11-2025 (hace 17 días).

MANDATO CLARO:
'Terminar con corrupción, garantizar combustibles, modernizar YPFB'

REALIDAD POLÍTICA:
- Paz despidió a Dorgathen (17 días) por 'negligencia'
- Paz amenaza con procesos judicales a 'saboteadores'
- Prensa reporta 'corrupción masiva' en YPFB
- Expectativa pública: CAMBIO INMEDIATO

PRESIÓN SOBRE YUSSEF (nuevo presidente):
- Si combustibles se agotan = CULPA SUYA (no Dorgathen)
- Si exploración no avanza = CULPA SUYA
- Si corrupción persiste = CULPA SUYA

PLAN 100 DÍAS PROPUESTO:

DÍA 1-30 (Estabilización):
✓ Resolver crisis importación (gasolina/diésel)
✓ Implementar controles anticorrupción
✓ Comunicados públicos (confianza)
✓ Auditoría de corrupción (show)

DÍA 31-60 (Modernización):
✓ Anunciar plan exploración (56 o 6 proyectos)
✓ Renovar línea crédito Petroecuador
✓ Iniciar estudios SCADA/logística
✓ Contrataciones técnicas

DÍA 61-100 (Consolidación):
✓ Primeros resultados exploración
✓ Reducción precios combustibles (si finanzas lo permiten)
✓ Cumplimiento de promesas (comunicación)
✓ Proyección 2026 (inversión, empleo)

RIESGOS:
1. Si crisis importación NO se resuelve: FRACASO total
2. Si exploración NOT visible: 'Promesas incumplidas'
3. Si corrupción NOT se castiga: 'Más de lo mismo'

CLAVE:
- VISIBLE PROGRESS (aunque sea pequeño)
- COMUNICACIÓN CLARA (qué estamos haciendo)
- CULPABLES PÚBLICOS (Dorgathen ya, buscar más)

PREGUNTA PARA YUSSEF:
¿Estamos aligned con Paz en expectativas realistas?
¿Paz entiende que algunos problemas necesitan 2-3 años?

Este es el período más crítico. Una falla = YPFB vuelve a crisis.",
            'status' => 'open',
            'owner_agent_id' => null,
            'last_response_author_type' => 'none',
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(6),
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->command->info("  ✓ Ticket OPEN creado: {$ticket->ticket_code}");
    }
}
