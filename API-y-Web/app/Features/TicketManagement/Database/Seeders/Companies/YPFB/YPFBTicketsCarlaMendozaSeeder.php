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
 * YPFB Tickets Seeder - Carla Mendoza
 *
 * Crea 5 tickets asignados a Carla Mendoza (carla.mendoza@ypfb.gob.bo)
 * Temas: Comercialización, ventas, contratos industriales, facturación
 *
 * Contexto: Crisis de hidrocarburos bolivianos 2024-2025
 * - Negociaciones con clientes industriales
 * - Problemas de facturación por medidores
 * - Renegociación de tarifas
 *
 * Distribución: T1(Ene):CLOSED, T2(Abr):CLOSED, T3(Jul):CLOSED, T4(Oct):RESOLVED, T5(Nov):PENDING
 */
class YPFBTicketsCarlaMendozaSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'carla.mendoza@ypfb.gob.bo';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Ramiro', 'last_name' => 'Suárez', 'email' => 'ramiro.suarez.industrial3@gmail.com'],
        ['first_name' => 'Patricia', 'last_name' => 'Velasco', 'email' => 'patricia.velasco.comercial3@gmail.com'],
        ['first_name' => 'Gonzalo', 'last_name' => 'Quiroga', 'email' => 'gonzalo.quiroga.ventas3@gmail.com'],
        ['first_name' => 'Adriana', 'last_name' => 'Peña', 'email' => 'adriana.pena.contratos3@gmail.com'],
        ['first_name' => 'Eduardo', 'last_name' => 'Blanco', 'email' => 'eduardo.blanco.factura3@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("⛽ Creando tickets YPFB para: Carla Mendoza...");

        $this->loadCompany();
        if (!$this->company) return;

        $this->loadAgent();
        if (!$this->agent) return;

        if ($this->alreadySeeded()) return;

        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Carla Mendoza");
    }

    private function loadCompany(): void
    {
        $this->company = Company::where('name', 'YPFB Corporación')->first();
        if (!$this->company) $this->command->error('❌ YPFB Corporación no encontrada.');
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
            'comercializacion' => $areas->firstWhere('name', 'Comercialización y Ventas'),
            'administracion' => $areas->firstWhere('name', 'Administración, Finanzas y Recursos Humanos'),
        ];
    }

    private function loadCategories(): void
    {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'facturacion' => $cats->firstWhere('name', 'Problema de Facturación') ?? $cats->first(),
            'consulta' => $cats->firstWhere('name', 'Consulta sobre Consumo/Tarifas') ?? $cats->first(),
        ];
    }

    private function createUsers(): void
    {
        foreach ($this->userPoolData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'user_code' => CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code'),
                    'password_hash' => Hash::make(self::PASSWORD),
                    'email_verified' => true,
                    'email_verified_at' => now(),
                    'status' => UserStatus::ACTIVE,
                    'auth_provider' => 'local',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(rand(30, 300)),
                    'terms_version' => 'v2.1',
                    'onboarding_completed_at' => now()->subDays(rand(30, 300)),
                ]
            );

            $isFemale = str_ends_with(strtolower($userData['first_name']), 'a');
            \App\Features\UserManagement\Models\UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
