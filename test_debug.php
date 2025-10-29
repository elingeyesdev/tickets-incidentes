<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create test data
$company = \App\Features\CompanyManagement\Models\Company::factory()->create();
$platformAdmin = \App\Features\UserManagement\Models\User::factory()
    ->withProfile()
    ->withRole('PLATFORM_ADMIN')
    ->create();

// Create users with different statuses
$activeUser = \App\Features\UserManagement\Models\User::factory()
    ->withProfile()
    ->withRole('USER')
    ->create(['status' => \App\Shared\Enums\UserStatus::ACTIVE]);

$suspendedUser = \App\Features\UserManagement\Models\User::factory()
    ->withProfile()
    ->withRole('USER')
    ->create(['status' => \App\Shared\Enums\UserStatus::SUSPENDED]);

// Generate token
$token = \App\Shared\Helpers\JWTHelper::generateAccessToken($platformAdmin);

// Make request
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Accept' => 'application/json',
])->get('http://localhost:8000/api/users?status=SUSPENDED');

echo "Status Code: " . $response->status() . "\n";
echo "Response Body:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
