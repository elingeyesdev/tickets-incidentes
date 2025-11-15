<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== DEBUG ROUTE MODEL BINDING ===\n\n";

// Simular lo que hace el test
\Illuminate\Support\Facades\DB::beginTransaction();

try {
    $company = \App\Features\CompanyManagement\Models\Company::factory()->create();
    $category = \App\Features\TicketManagement\Models\Category::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);
    $user = \App\Features\UserManagement\Models\User::factory()->create();

    $ticket = \App\Features\TicketManagement\Models\Ticket::factory()->create([
        'company_id' => $company->id,
        'category_id' => $category->id,
        'created_by_user_id' => $user->id,
        'title' => 'Test ticket',
        'description' => 'Test description',
        'status' => 'open',
    ]);

    echo "✓ Ticket creado:\n";
    echo "  - ID: {$ticket->id}\n";
    echo "  - ticket_code: {$ticket->ticket_code}\n\n";

    // Test 1: Búsqueda directa
    echo "Test 1 - Búsqueda directa por ticket_code:\n";
    $found1 = \App\Features\TicketManagement\Models\Ticket::where('ticket_code', $ticket->ticket_code)->first();
    echo $found1 ? "  ✓ ENCONTRADO (ID: {$found1->id})\n" : "  ✗ NO ENCONTRADO\n";

    // Test 2: Simular el binding con firstOrFail
    echo "\nTest 2 - firstOrFail() (como en Route::bind):\n";
    try {
        $found2 = \App\Features\TicketManagement\Models\Ticket::where('ticket_code', $ticket->ticket_code)->firstOrFail();
        echo "  ✓ ENCONTRADO (ID: {$found2->id})\n";
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        echo "  ✗ ModelNotFoundException lanzada\n";
    }

    // Test 3: Verificar con query log
    echo "\nTest 3 - Con query log activado:\n";
    \Illuminate\Support\Facades\DB::enableQueryLog();
    $found3 = \App\Features\TicketManagement\Models\Ticket::where('ticket_code', $ticket->ticket_code)->first();
    $queries = \Illuminate\Support\Facades\DB::getQueryLog();
    echo "  Query ejecutada:\n";
    foreach ($queries as $query) {
        echo "    SQL: {$query['query']}\n";
        echo "    Bindings: " . json_encode($query['bindings']) . "\n";
    }
    echo $found3 ? "  ✓ Resultado: ENCONTRADO\n" : "  ✗ Resultado: NO ENCONTRADO\n";

    // Test 4: Verificar con nueva conexión
    echo "\nTest 4 - Con nueva instancia de modelo:\n";
    $newModel = new \App\Features\TicketManagement\Models\Ticket;
    $found4 = $newModel->where('ticket_code', $ticket->ticket_code)->first();
    echo $found4 ? "  ✓ ENCONTRADO\n" : "  ✗ NO ENCONTRADO\n";

} finally {
    \Illuminate\Support\Facades\DB::rollBack();
}

echo "\n✓ Test completado\n";
