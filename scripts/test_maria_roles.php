<?php

$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Features\UserManagement\Models\User;

echo "=== TEST MARIA CONDORI ROLES ===\n\n";

$maria = User::where('email', 'maria.condori@example.com')->first();

if (!$maria) {
    echo "❌ Usuario maria.condori@example.com NO encontrado\n";
    exit(1);
}

echo "✅ Usuario encontrado: {$maria->email}\n";
echo "   ID: {$maria->id}\n";
echo "   Nombre: {$maria->name}\n\n";

// Verificar roles en Spatie
$spatieRoles = $maria->getRoleNames();
echo "Roles en Spatie:\n";
if (is_array($spatieRoles)) {
    foreach ($spatieRoles as $role) {
        echo "   - {$role}\n";
    }
} else {
    foreach ($spatieRoles as $role) {
        echo "   - {$role}\n";
    }
}

echo "\nVerificaciones hasRole():\n";
echo "   hasRole('USER'): " . ($maria->hasRole('USER') ? 'SI ✅' : 'NO ❌') . "\n";
echo "   hasRole('AGENT'): " . ($maria->hasRole('AGENT') ? 'SI' : 'NO') . "\n";
echo "   hasRole('COMPANY_ADMIN'): " . ($maria->hasRole('COMPANY_ADMIN') ? 'SI' : 'NO') . "\n";
echo "   hasRole('PLATFORM_ADMIN'): " . ($maria->hasRole('PLATFORM_ADMIN') ? 'SI' : 'NO') . "\n";

// Verificar roles en user_roles
$userRoles = DB::table('auth.user_roles')
    ->where('user_id', $maria->id)
    ->where('is_active', true)
    ->get();

echo "\nRoles en auth.user_roles ({$userRoles->count()}):\n";
foreach ($userRoles as $role) {
    echo "   - {$role->role_code} (company: " . ($role->company_id ?? 'null') . ")\n";
}

echo "\n=== TEST COMPLETADO ===\n";
