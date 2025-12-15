<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Database\Seeders\Companies\ThreeBMarkets;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\Category;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\AvatarHelper;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 3B Markets Tickets Seeder - Agente: Lucía Paz
 *
 * Empresa: 3B Markets (Tiendas 3B Bolivia S.A.)
 * Industria: supermarket
 * Tamaño: PEQUEÑA
 * areas_enabled: FALSE
 *
 * Tickets: 8 (por agente según reglas para empresa pequeña)
 * Pool de usuarios: 3-5 clientes con @gmail.com
 *
 * Período: 5 enero 2025 - 10 diciembre 2025
 * Distribución de estados según antigüedad
 */
class ThreeBMarketsTicketsLuciaPazSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const EXPECTED_TICKETS_PER_AGENT = 8;

    private Company $company;
    private User $agent;
    private array $categories = [];
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Carla', 'last_name' => 'Méndez', 'email' => 'carla.mendez.3b18@gmail.com'],
        ['first_name' => 'Roberto', 'last_name' => 'Vaca', 'email' => 'roberto.vaca.3b18@gmail.com'],
        ['first_name' => 'Julia', 'last_name' => 'Flores', 'email' => 'julia.flores.3b18@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info('🎫 Creando tickets para 3B Markets (Agente: Lucía Paz)...');

        // 1. Cargar empresa
        $this->company = Company::where('company_code', 'CMP-2025-00010')->first();
        if (!$this->company) {
            $this->command->error('❌ Empresa 3B Markets no encontrada.');
            return;
        }

        // 2. Cargar agente
        $this->agent = User::where('email', 'lucia.paz@tiendas3b.com.bo')->first();
        if (!$this->agent) {
            $this->command->error('❌ Agente lucia.paz@tiendas3b.com.bo no encontrado.');
            return;
        }

        // 3. Idempotencia por agente
        $existingCount = Ticket::where('company_id', $this->company->id)
            ->where('assigned_to', $this->agent->id)
            ->count();
        if ($existingCount >= self::EXPECTED_TICKETS_PER_AGENT) {
            $this->command->info('[OK] Tickets de Lucía Paz ya existen. Saltando...');
            return;
        }

        // 4. Cargar/crear categorías
        $this->loadCategories();

        // 5. Crear pool de usuarios
        $this->createUsers();

        // 6. Crear tickets
        $this->createTickets();

        $this->command->info('✅ 8 tickets creados para Lucía Paz.');
    }

    private function loadCategories(): void
    {
        // Categorías de supermarket según industrytypes
        $categoryNames = [
            'producto_compra' => 'Problema de Producto/Compra',
            'cadena_frio' => 'Problema de Cadena de Frío',
            'disponibilidad' => 'Solicitud de Información/Disponibilidad',
            'facturacion' => 'Problema de Facturación/Cobro',
            'servicio' => 'Queja sobre Servicio/Tienda',
        ];

        foreach ($categoryNames as $key => $name) {
            $category = Category::where('company_id', $this->company->id)
                ->where('name', $name)
                ->first();

            if (!$category) {
                $category = Category::create([
                    'company_id' => $this->company->id,
                    'name' => $name,
                    'description' => "Categoría: $name para 3B Markets",
                    'is_active' => true,
                    'created_at' => now()->subMonths(11),
                ]);
            }

            $this->categories[$key] = $category;
        }
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
                'terms_accepted_at' => now()->subDays(rand(30, 180)),
                'terms_version' => 'v2.1',
                'onboarding_completed_at' => now()->subDays(rand(30, 180)),
                ]
            );

            $isFemale = str_ends_with(strtolower($userData['first_name']), 'a');

            \App\Features\UserManagement\Models\UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
