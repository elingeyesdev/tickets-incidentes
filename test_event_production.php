<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Features\TicketManagement\Events\TicketCreated;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

echo "=== TEST: Evento TicketCreated se dispara en PRODUCCIÓN ===\n\n";

DB::beginTransaction();

try {
    // Registrar un listener para capturar el evento
    $eventFired = false;
    $capturedTicket = null;

    Event::listen(TicketCreated::class, function (TicketCreated $event) use (&$eventFired, &$capturedTicket) {
        $eventFired = true;
        $capturedTicket = $event->ticket;
        echo "✓ EVENTO CAPTURADO!\n";
        echo "  - Ticket Code: {$event->ticket->ticket_code}\n";
        echo "  - Title: {$event->ticket->title}\n";
    });

    // Crear ticket usando el servicio (como en producción)
    $user = User::factory()->withRole('USER')->create();
    $company = Company::factory()->create();
    $category = Category::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $ticketService = app(\App\Features\TicketManagement\Services\TicketService::class);

    echo "Creando ticket...\n";
    $ticket = $ticketService->create([
        'company_id' => $company->id,
        'category_id' => $category->id,
        'title' => 'Test Production Event',
        'description' => 'Verificando que el evento se dispara en producción',
    ], $user);

    echo "\nResultado:\n";
    echo "  - Ticket creado: {$ticket->ticket_code}\n";
    echo "  - Evento disparado: " . ($eventFired ? '✅ SÍ' : '❌ NO') . "\n";

    if ($eventFired && $capturedTicket) {
        echo "  - Ticket en evento coincide: " . ($capturedTicket->id === $ticket->id ? '✅ SÍ' : '❌ NO') . "\n";
    }

} finally {
    DB::rollBack();
}

echo "\n✓ Prueba completada\n";
