<?php

namespace App\Features\TicketManagement\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal del feature de Ticket Management
 *
 * Este seeder puede ser llamado desde DatabaseSeeder o independientemente
 * para sembrar datos del sistema de tickets.
 */
class TicketManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎫 Seeding Ticket Management data...');

        // Llamar a los seeders específicos
        $this->call([
            DefaultCategoriesSeeder::class,
        ]);

        $this->command->info('✅ Ticket Management data seeded successfully!');
    }
}
