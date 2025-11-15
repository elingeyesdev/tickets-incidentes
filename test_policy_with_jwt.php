<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\UserManagement\Services\TokenService;
use Illuminate\Support\Facades\DB;

echo "=== TEST POLICY WITH JWT CONTEXT ===\n\n";

DB::beginTransaction();

try {
    $company = Company::factory()->create();
    $category = Category::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'category_id' => $category->id,
        'created_by_user_id' => $userA->id,
        'status' => 'open',
    ]);

    echo "✓ Ticket creado por userA: {$ticket->ticket_code}\n";
    echo "  - userA ID: {$userA->id}\n";
    echo "  - userB ID: {$userB->id}\n\n";

    // Test 1: Without JWT (como hace el Gate directamente)
    echo "Test 1: Gate without JWT context\n";
    try {
        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $result = $gate->forUser($userB)->allows('view', $ticket);
        echo "  Result: " . ($result ? 'ALLOWED' : 'DENIED') . "\n";
    } catch (\Throwable $e) {
        echo "  ✗ ERROR: " . get_class($e) . "\n";
        echo "  Message: " . $e->getMessage() . "\n";
    }

    // Test 2: With JWT context (simulando el middleware)
    echo "\nTest 2: Gate WITH JWT context (simulating middleware)\n";
    try {
        // Generar JWT para userB
        $tokenService = app(\App\Features\Authentication\Services\TokenService::class);
        $token = $tokenService->generateAccessToken($userB, 'test_session');

        // Decodificar y establecer en request
        $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(env('JWT_SECRET'), 'HS256'));
        $payload = json_decode(json_encode($decoded), true);

        // Simular lo que hace el middleware
        request()->attributes->set('jwt_payload', $payload);

        // Ahora probar la policy
        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $result = $gate->forUser($userB)->allows('view', $ticket);
        echo "  Result: " . ($result ? 'ALLOWED' : 'DENIED') . "\n";
    } catch (\Throwable $e) {
        echo "  ✗ ERROR: " . get_class($e) . "\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

} finally {
    DB::rollBack();
}

echo "\n✓ Test completado\n";
