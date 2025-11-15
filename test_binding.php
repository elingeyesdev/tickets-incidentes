<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST ROUTE MODEL BINDING ===\n\n";

// Obtener el último ticket
$ticket = \App\Features\TicketManagement\Models\Ticket::latest()->first();

if ($ticket) {
    echo "✓ Ticket encontrado en DB:\n";
    echo "  - ticket_code: {$ticket->ticket_code}\n";
    echo "  - ID: {$ticket->id}\n\n";

    // Test 1: Búsqueda directa por ticket_code
    $found = \App\Features\TicketManagement\Models\Ticket::where('ticket_code', $ticket->ticket_code)->first();
    echo "Test 1 - Búsqueda por ticket_code:\n";
    echo $found ? "  ✓ ENCONTRADO\n" : "  ✗ NO ENCONTRADO\n";

    // Test 2: Simular lo que hace el binding
    echo "\nTest 2 - Simulación de Route::bind():\n";
    try {
        $binding = \App\Features\TicketManagement\Models\Ticket::where('ticket_code', $ticket->ticket_code)->firstOrFail();
        echo "  ✓ Binding funcionó correctamente\n";
        echo "  - Devolvió ID: {$binding->id}\n";
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        echo "  ✗ firstOrFail() lanzó ModelNotFoundException\n";
    }

    // Test 3: Verificar tabla
    echo "\nTest 3 - Verificar tabla del modelo:\n";
    echo "  - Tabla: {$ticket->getTable()}\n";
    echo "  - Connection: {$ticket->getConnectionName()}\n";

} else {
    echo "✗ No hay tickets en la base de datos\n";
    echo "  Esto es normal si acabas de reiniciar.\n";
}
