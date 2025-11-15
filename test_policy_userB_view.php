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

echo "=== TEST POLICY: UserB viewing UserA's ticket ===\n\n";

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

    echo "✓ Ticket creado por userA: {$ticket->ticket_code}\n";
    echo "  - userA ID: {$userA->id}\n";
    echo "  - userB ID: {$userB->id}\n\n";

    // Test policy directly
    echo "Test 1: Policy->view() for UserB (should be FALSE)\n";
    try {
        $policy = app(\App\Features\TicketManagement\Policies\TicketPolicy::class);
        $result = $policy->view($userB, $ticket);
        echo "  Result: " . ($result ? 'TRUE (ALLOWED)' : 'FALSE (DENIED)') . "\n";
    } catch (\Throwable $e) {
        echo "  ✗ ERROR: " . get_class($e) . "\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  Stack:\n";
        foreach ($e->getTrace() as $i => $trace) {
            echo "    #{$i} ";
            if (isset($trace['file'])) {
                echo $trace['file'] . ':' . $trace['line'];
            }
            if (isset($trace['class'])) {
                echo ' ' . $trace['class'] . $trace['type'] . $trace['function'] . '()';
            } elseif (isset($trace['function'])) {
                echo ' ' . $trace['function'] . '()';
            }
            echo "\n";
        }
    }

} finally {
    DB::rollBack();
}

echo "\n✓ Test completado\n";
