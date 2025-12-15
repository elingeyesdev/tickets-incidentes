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
 * Hipermaxi Tickets Seeder - Miguel Torres
 *
 * Crea 5 tickets asignados a Miguel Torres (miguel.torres@hipermaxi.com)
 * Temas: Productos perecederos, cadena de frío, calidad alimentaria
 *
 * Distribución: T1(Mar):CLOSED, T2(May):CLOSED, T3(Jun):CLOSED, T4(Sep):RESOLVED, T5(Nov):PENDING
 */
class HipermaxiTicketsMiguelTorresSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'miguel.torres@hipermaxi.com';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Mónica', 'last_name' => 'Suárez', 'email' => 'monica.suarez.calidad17@gmail.com'],
        ['first_name' => 'Ricardo', 'last_name' => 'Condori', 'email' => 'ricardo.condori.fresco17@gmail.com'],
        ['first_name' => 'Elena', 'last_name' => 'Pardo', 'email' => 'elena.pardo.carne17@gmail.com'],
        ['first_name' => 'Gonzalo', 'last_name' => 'Torrez', 'email' => 'gonzalo.torrez.lacteo17@gmail.com'],
        ['first_name' => 'Silvia', 'last_name' => 'Mamani', 'email' => 'silvia.mamani.congel17@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("🛒 Creando tickets Hipermaxi para: Miguel Torres...");

        $this->loadCompany();
        if (!$this->company) return;

        $this->loadAgent();
        if (!$this->agent) return;

        if ($this->alreadySeeded()) return;

        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Miguel Torres");
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
            'perecibles' => $areas->firstWhere('name', 'Perecibles y Cadena de Frío'),
            'calidad' => $areas->firstWhere('name', 'Control de Calidad'),
            'atencion' => $areas->firstWhere('name', 'Atención al Cliente'),
        ];
    }

    private function loadCategories(): void
    {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'frio' => $cats->firstWhere('name', 'Problema de Cadena de Frío') ?? $cats->first(),
            'producto' => $cats->firstWhere('name', 'Problema de Producto/Compra') ?? $cats->first(),
            'servicio' => $cats->firstWhere('name', 'Queja sobre Servicio/Tienda') ?? $cats->first(),
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
