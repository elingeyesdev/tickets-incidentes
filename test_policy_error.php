<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Support\Facades\DB;

echo "=== TEST POLICY ERROR ===\n\n";

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

    // Test 1: Can userB view the ticket (should be false)
    echo "Test 1: Can userB view ticket created by userA?\n";
    try {
        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $result = $gate->forUser($userB)->allows('view', $ticket);
        echo "  Result: " . ($result ? 'TRUE (allowed)' : 'FALSE (denied)') . "\n";
    } catch (\Throwable $e) {
        echo "  ✗ ERROR: " . get_class($e) . "\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    // Test 2: Can userA update when pending
    echo "\nTest 2: Can userA update ticket when status=pending?\n";
    $ticket->status = \App\Features\TicketManagement\Enums\TicketStatus::PENDING;
    $ticket->save();

    try {
        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        $result = $gate->forUser($userA)->allows('update', $ticket);
        echo "  Result: " . ($result ? 'TRUE (allowed)' : 'FALSE (denied)') . "\n";
    } catch (\Throwable $e) {
        echo "  ✗ ERROR: " . get_class($e) . "\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

} finally {
    DB::rollBack();
}

echo "\n✓ Test completado\n";
