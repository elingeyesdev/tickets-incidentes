<?php
// Test Spatie roles integration

// Ajustar paths para ejecutar desde /var/www/scripts/ dentro de Docker
$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Features\UserManagement\Models\User;

echo "=== TEST SPATIE ROLES ===\n\n";

// 1. Test Platform Admin
$admin = User::where('email', 'admin@sistema.com')->first();
if ($admin) {
    echo "1. PLATFORM ADMIN (admin@sistema.com)\n";
    $roles = $admin->getRoleNames();
    echo "   Roles en Spatie: " . (is_array($roles) ? implode(', ', $roles) : $roles->implode(', ')) . "\n";
    echo "   hasRole('PLATFORM_ADMIN'): " . ($admin->hasRole('PLATFORM_ADMIN') ? 'SI ✅' : 'NO ❌') . "\n";
    echo "   hasRole('USER'): " . ($admin->hasRole('USER') ? 'SI' : 'NO') . "\n\n";
} else {
    echo "❌ No se encontró admin@sistema.com\n\n";
}

// 2. Test Multi-role user
$multiRoleUser = User::whereHas('userRoles', function($q) {
    $q->where('role_code', 'PLATFORM_ADMIN');
})->whereHas('userRoles', function($q) {
    $q->where('role_code', 'COMPANY_ADMIN');
})->first();

if ($multiRoleUser) {
    echo "2. MULTI-ROLE USER ({$multiRoleUser->email})\n";
    $roles = $multiRoleUser->getRoleNames();
    echo "   Roles en Spatie: " . (is_array($roles) ? implode(', ', $roles) : $roles->implode(', ')) . "\n";
    echo "   hasRole('PLATFORM_ADMIN'): " . ($multiRoleUser->hasRole('PLATFORM_ADMIN') ? 'SI ✅' : 'NO ❌') . "\n";
    echo "   hasRole('COMPANY_ADMIN'): " . ($multiRoleUser->hasRole('COMPANY_ADMIN') ? 'SI ✅' : 'NO ❌') . "\n\n";
} else {
    echo "⚠️ No se encontró usuario multi-rol\n\n";
}

// 3. Contar usuarios sincronizados
$totalUsers = User::count();
$usersWithSpatieRoles = User::whereHas('roles')->count();

echo "3. ESTADÍSTICAS\n";
echo "   Total usuarios: {$totalUsers}\n";
echo "   Usuarios con roles Spatie: {$usersWithSpatieRoles}\n";
echo "   Porcentaje sincronizado: " . round(($usersWithSpatieRoles / $totalUsers) * 100, 1) . "%\n\n";

// 4. Verificar middleware híbrido existe
$middlewareFile = $basePath . '/app/Http/Middleware/SpatieRoleWithActiveRole.php';
echo "4. MIDDLEWARE HÍBRIDO\n";
echo "   Archivo existe: " . (file_exists($middlewareFile) ? 'SI ✅' : 'NO ❌') . "\n\n";

echo "=== TEST COMPLETADO ===\n";
