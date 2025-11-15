<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

echo "=== TEST ENDPOINT: UserB viewing UserA's ticket ===\n\n";

DB::beginTransaction();

try {
    $company = Company::factory()->create();
    $category = Category::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $userA = User::factory()->withRole('USER')->create();
    $userB = User::factory()->withRole('USER')->create();

    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'category_id' => $category->id,
        'created_by_user_id' => $userA->id,
        'status' => 'open',
    ]);

    echo "✓ Ticket creado: {$ticket->ticket_code}\n";
    echo "  - Creador: userA ({$userA->id})\n";
    echo "  - Viewer: userB ({$userB->id})\n\n";

    // Generate JWT for userB
    $tokenService = app(\App\Features\Authentication\Services\TokenService::class);
    $token = $tokenService->generateAccessToken($userB, 'test_session');

    // Make HTTP request
    $request = Request::create(
        "/api/tickets/{$ticket->ticket_code}",
        'GET',
        [],
        [],
        [],
        ['HTTP_AUTHORIZATION' => "Bearer {$token}"]
    );

    echo "Making request: GET /api/tickets/{$ticket->ticket_code}\n";
    echo "Authorization: Bearer [token]\n\n";

    $response = $kernel->handle($request);

    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Body:\n";
    echo json_encode(json_decode($response->getContent(), true), JSON_PRETTY_PRINT) . "\n";

} catch (\Throwable $e) {
    echo "✗ ERROR: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} finally {
    DB::rollBack();
}

echo "\n✓ Test completado\n";
