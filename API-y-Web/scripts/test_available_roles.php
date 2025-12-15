<?php
// Test available-roles endpoint

$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Features\UserManagement\Models\User;
use App\Features\Authentication\Services\TokenService;

echo "=== TEST AVAILABLE-ROLES ENDPOINT ===\n\n";

// Find a user with multiple roles including COMPANY_ADMIN or AGENT
$user = User::whereHas('userRoles', function($q) {
    $q->where('role_code', 'COMPANY_ADMIN')
      ->whereNotNull('company_id');
})->first();

if (!$user) {
    echo "❌ No se encontró usuario con rol COMPANY_ADMIN\n";
    exit(1);
}

echo "Usuario: {$user->email}\n";
echo "Roles en auth.user_roles:\n";

foreach ($user->userRoles()->active()->get() as $userRole) {
    echo "  - {$userRole->role_code}";
    if ($userRole->company_id) {
        echo " (company_id: {$userRole->company_id})";
    }
    echo "\n";
}

echo "\n";

// Simulate what availableRoles() returns
$roles = $user->getAllRolesForJWT();

echo "Roles enriquecidos (como los devuelve availableRoles):\n";

foreach ($roles as $role) {
    $dashboardPaths = [
        'PLATFORM_ADMIN' => '/app/admin/dashboard',
        'COMPANY_ADMIN' => '/app/company/dashboard',
        'AGENT' => '/app/agent/dashboard',
        'USER' => '/app/user/dashboard',
    ];

    $enriched = [
        'code' => $role['code'],
        'company_id' => $role['company_id'] ?? null,
        'dashboard_path' => $dashboardPaths[$role['code']] ?? '/app/dashboard',
    ];

    // Agregar datos de empresa si tiene company_id
    if ($role['company_id']) {
        $company = \App\Features\CompanyManagement\Models\Company::find($role['company_id']);
        if ($company) {
            $enriched['company_name'] = $company->name;
            $enriched['logo_url'] = $company->logo_url;
            $enriched['industry_name'] = $company->industry?->name ?? null;
            $enriched['primary_color'] = $company->primary_color;
            $enriched['status'] = $company->status;
        }
    }

    echo "\n  Rol: {$enriched['code']}\n";
    foreach ($enriched as $key => $value) {
        if ($key === 'code') continue;
        $displayValue = $value ?? 'null';
        echo "    {$key}: {$displayValue}\n";
    }
}

echo "\n=== TEST COMPLETADO ===\n";
