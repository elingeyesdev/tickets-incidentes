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
 * Hipermaxi Tickets Seeder - Sandra Pérez
 *
 * Crea 5 tickets asignados a Sandra Pérez (sandra.perez@hipermaxi.com)
 * Parte del conjunto de 4 seeders que crean 20 tickets totales para Hipermaxi.
 *
 * Contexto: Cadena de supermercados más grande de Bolivia
 * - Lanzamiento de eCommerce Oct/Nov 2024
 * - 37 sucursales + 37 farmacias
 * - Delivery propio y app móvil
 *
 * Temas de Sandra: Atención al cliente, devoluciones, quejas servicio tienda
 *
 * Distribución de estados:
 * - T1 (Feb): CLOSED
 * - T2 (Abr): CLOSED
 * - T3 (Jun): CLOSED
 * - T4 (Oct): RESOLVED
 * - T5 (Nov): PENDING
 */
class HipermaxiTicketsSandraPerezSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const AGENT_EMAIL = 'sandra.perez@hipermaxi.com';
    private const TICKETS_PER_AGENT = 5;

    private Company $company;
    private ?User $agent = null;
    private array $areas = [];
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Carlos', 'last_name' => 'Mamani', 'email' => 'carlos.mamani.compras14@gmail.com'],
        ['first_name' => 'Rosario', 'last_name' => 'Quiroga', 'email' => 'rosario.quiroga.cliente14@gmail.com'],
        ['first_name' => 'Juan', 'last_name' => 'Condori', 'email' => 'juan.condori.scz14@gmail.com'],
        ['first_name' => 'María', 'last_name' => 'Flores', 'email' => 'maria.flores.hiper14@gmail.com'],
        ['first_name' => 'Luis', 'last_name' => 'Choque', 'email' => 'luis.choque.super14@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info("🛒 Creando tickets Hipermaxi para: Sandra Pérez...");

        $this->loadCompany();
        if (!$this->company) return;

        $this->loadAgent();
        if (!$this->agent) return;

        if ($this->alreadySeeded()) return;

        $this->loadAreas();
        $this->loadCategories();
        $this->createUsers();
        $this->createTickets();

        $this->command->info("✅ " . self::TICKETS_PER_AGENT . " tickets creados para Sandra Pérez");
    }

    private function loadCompany(): void
    {
        $this->company = Company::where('name', 'Hipermaxi S.A.')->first();
        if (!$this->company) {
            $this->command->error('❌ Hipermaxi S.A. no encontrada.');
        }
    }

    private function loadAgent(): void
    {
        $this->agent = User::where('email', self::AGENT_EMAIL)->first();
        if (!$this->agent) {
            $this->command->error('❌ Agente ' . self::AGENT_EMAIL . ' no encontrado.');
        }
    }

    private function alreadySeeded(): bool
    {
        $count = Ticket::where('company_id', $this->company->id)
            ->where('owner_agent_id', $this->agent->id)
            ->count();

        if ($count >= self::TICKETS_PER_AGENT) {
            $this->command->info("[OK] Tickets para Sandra Pérez ya existen ({$count}). Saltando.");
            return true;
        }
        return false;
    }

    private function loadAreas(): void
    {
        $areas = Area::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->areas = [
            'atencion' => $areas->firstWhere('name', 'Atención al Cliente'),
            'operaciones' => $areas->firstWhere('name', 'Operaciones de Tiendas'),
            'calidad' => $areas->firstWhere('name', 'Control de Calidad'),
        ];
    }

    private function loadCategories(): void
    {
        $cats = Category::where('company_id', $this->company->id)->where('is_active', true)->get();
        $this->categories = [
            'producto' => $cats->firstWhere('name', 'Problema de Producto/Compra') ?? $cats->first(),
            'servicio' => $cats->firstWhere('name', 'Queja sobre Servicio/Tienda') ?? $cats->first(),
            'facturacion' => $cats->firstWhere('name', 'Problema de Facturación/Cobro') ?? $cats->first(),
        ];
    }

    private function createUsers(): void
    {
        foreach ($this->userPoolData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                'user_code' => CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code'),
                'email' => $userData['email'],
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
