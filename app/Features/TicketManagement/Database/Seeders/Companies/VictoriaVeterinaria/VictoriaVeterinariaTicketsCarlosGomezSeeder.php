<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Database\Seeders\Companies\VictoriaVeterinaria;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Seeder;
use App\Shared\Helpers\AvatarHelper;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Victoria Veterinaria Tickets Seeder - Carlos Gómez
 *
 * Agente: Carlos Gómez (carlos.gomez@victoriavet.bo)
 * Empresa: Victoria Veterinaria (PEQUEÑA - veterinary)
 * Volumen: 8 tickets (empresa pequeña)
 * 
 * Conexión narrativa con anuncios:
 * - Jornada de esterilización (septiembre)
 * - Alerta temporada garrapatas (junio)
 * - Mantenimiento sala cirugía (julio)
 * - Día del Padre (junio)
 */
class VictoriaVeterinariaTicketsCarlosGomezSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const EXPECTED_TICKET_COUNT = 8;

    private Company $company;
    private array $categories = [];
    private User $agent;
    private array $users = [];

    private array $userPoolData = [
        ['first_name' => 'Fernanda', 'last_name' => 'Rojas', 'email' => 'fernanda.rojas.pets21@gmail.com'],
        ['first_name' => 'Luis', 'last_name' => 'Cárdenas', 'email' => 'luis.cardenas.vet21@gmail.com'],
        ['first_name' => 'Patricia', 'last_name' => 'Ortiz', 'email' => 'patricia.ortiz.mascotas21@gmail.com'],
        ['first_name' => 'Andrés', 'last_name' => 'Flores', 'email' => 'andres.flores.pet21@gmail.com'],
    ];

    public function run(): void
    {
        $this->command->info('🎫 Creando tickets para Victoria Veterinaria - Agente: Carlos Gómez...');

        $this->loadCompany();
        if (!$this->company) return;

        if ($this->alreadySeeded()) return;

        $this->loadCategories();
        if (empty($this->categories)) return;

        $this->loadAgent();
        if (!$this->agent) return;

        $this->createUsers();
        $this->createTickets();

        $this->command->info('✅ 8 tickets creados para Carlos Gómez');
    }

    private function loadCompany(): void
    {
        $this->company = Company::where('name', 'Victoria Veterinaria')->first();

        if (!$this->company) {
            $this->command->error('❌ Victoria Veterinaria no encontrada');
        }
    }

    private function alreadySeeded(): bool
    {
        $agent = User::where('email', 'carlos.gomez@victoriavet.bo')->first();
        if (!$agent) return false;

        $count = Ticket::where('company_id', $this->company->id)
            ->where('owner_agent_id', $agent->id)
            ->count();

        if ($count >= self::EXPECTED_TICKET_COUNT) {
            $this->command->info('✓ Tickets de Carlos Gómez ya existen. Saltando...');
            return true;
        }
        return false;
    }

    private function loadCategories(): void
    {
        $categories = Category::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        foreach ($categories as $category) {
            $name = $category->name;
            if (str_contains($name, 'Cita')) {
                $this->categories['cita'] = $category;
            } elseif (str_contains($name, 'Urgencia') || str_contains($name, 'Emergencia')) {
                $this->categories['emergencia'] = $category;
            } elseif (str_contains($name, 'Historial') || str_contains($name, 'Medicamento')) {
                $this->categories['historial'] = $category;
            } elseif (str_contains($name, 'Suministros') || str_contains($name, 'Medicamentos')) {
                $this->categories['suministros'] = $category;
            } elseif (str_contains($name, 'Problema')) {
                $this->categories['problema_cita'] = $category;
            }
        }

        if (count($this->categories) < 2) {
            $this->command->error('❌ Categorías insuficientes para Victoria Veterinaria');
        }
    }

    private function loadAgent(): void
    {
        $this->agent = User::where('email', 'carlos.gomez@victoriavet.bo')->first();

        if (!$this->agent) {
            $this->command->error('❌ Agente Carlos Gómez no encontrado');
        }
    }

    private function createUsers(): void
    {
        foreach ($this->userPoolData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                'user_code' => 'USR-' . strtoupper(substr(uniqid(), -8)),
                'email' => $userData['email'],
                'password_hash' => Hash::make(self::PASSWORD),
                'email_verified_at' => now()->subDays(rand(60, 200)),
                'status' => UserStatus::ACTIVE,
                'onboarding_completed_at' => now()->subDays(rand(30, 180)),
                ]
            );

            $isFemale = str_ends_with(strtolower($userData['first_name']), 'a');

            \App\Features\UserManagement\Models\UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
