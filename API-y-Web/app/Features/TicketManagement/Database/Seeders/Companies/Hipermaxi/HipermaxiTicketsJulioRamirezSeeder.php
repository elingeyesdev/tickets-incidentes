<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Database\Seeders\Companies\Hipermaxi;

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
 * Hipermaxi Tickets Seeder - Julio Ramírez
 *
 * Crea 5 tickets asignados a Julio Ramírez (julio.ramirez@hipermaxi.com)
 * Temas: Delivery, pedidos online, app móvil, eCommerce
 *
 * Contexto: Lanzamiento de plataforma eCommerce Oct/Nov 2024
 * - App móvil con +27,000 productos
 * - Delivery propio con POS móvil
 * - Servicio de compras desde el extranjero
 *
 * Distribución: T1(Ene):CLOSED, T2(Mar):CLOSED, T3(Jul):CLOSED, T4(Oct):RESOLVED, T5(Dic):OPEN
 */
class HipermaxiTicketsJulioRamirezSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'julio.ramirez@hipermaxi.com';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Andrea', 'last_name' => 'Quispe', 'email' => 'andrea.quispe.delivery15@gmail.com'],
        ['first_name' => 'Roberto', 'last_name' => 'Salazar', 'email' => 'roberto.salazar.app15@gmail.com'],
        ['first_name' => 'Teresa', 'last_name' => 'Luna', 'email' => 'teresa.luna.pedido15@gmail.com'],
        ['first_name' => 'Pedro', 'last_name' => 'Vargas', 'email' => 'pedro.vargas.online15@gmail.com'],
        ['first_name' => 'Daniela', 'last_name' => 'Rojas', 'email' => 'daniela.rojas.hiper15@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("🛒 Creando tickets Hipermaxi para: Julio Ramírez...");

        $this->loadCompany();
        if (!$this->company) return;

        $this->loadAgent();
        if (!$this->agent) return;

        if ($this->alreadySeeded()) return;

        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Julio Ramírez");
    }

    private function loadCompany(): void
    {
        $this->company = Company::where('name', 'Hipermaxi S.A.')->first();
        if (!$this->company) $this->command->error('❌ Hipermaxi S.A. no encontrada.');
    }

    private function loadAgent(): void
    {
        $this->agent = User::where('email', self::AGENT_EMAIL)->first();
        if (!$this->agent) $this->command->error('❌ Agente no encontrado.');
    }

    private function alreadySeeded(): bool
    {
        $count = Ticket::where('company_id', $this->company->id)
            ->where('owner_agent_id', $this->agent->id)->count();
        if ($count >= self::TICKETS_PER_AGENT) {
            $this->command->info("[OK] Tickets ya existen. Saltando.");
            return true;
        }
        return false;
    }

    private function loadAreas(): void
    {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = [
            'logistica' => $areas->firstWhere('name', 'Logística y Distribución'),
            'sistemas' => $areas->firstWhere('name', 'Sistemas e IT'),
            'atencion' => $areas->firstWhere('name', 'Atención al Cliente'),
        ];
    }

    private function loadCategories(): void
    {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'producto' => $cats->firstWhere('name', 'Problema de Producto/Compra') ?? $cats->first(),
            'servicio' => $cats->firstWhere('name', 'Queja sobre Servicio/Tienda') ?? $cats->first(),
            'informacion' => $cats->firstWhere('name', 'Solicitud de Información/Disponibilidad') ?? $cats->first(),
        ];
    }

    private function createUsers(): void
    {
        foreach ($this->userPoolData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                'user_code' => CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code'), 'email' => $userData['email'],
                'password_hash' => Hash::make(self::PASSWORD), 'email_verified' => true,
                'email_verified_at' => now(), 'status' => UserStatus::ACTIVE, 'auth_provider' => 'local',
                'terms_accepted' => true, 'terms_accepted_at' => now()->subDays(rand(30, 300)),
                'terms_version' => 'v2.1', 'onboarding_completed_at' => now()->subDays(rand(30, 300)),
                ]
            );

            $isFemale = str_ends_with(strtolower($userData['first_name']), 'a');
            \App\Features\UserManagement\Models\UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
