<?php
// Test Spatie Integration

$user = \App\Features\UserManagement\Models\User::first();

echo "User: " . $user->email . "\n";

$roles = $user->getRoleNames();
$rolesArray = is_array($roles) ? $roles : $roles->toArray();
echo "Spatie Roles: " . implode(', ', $rolesArray) . "\n";

echo "Has PLATFORM_ADMIN: " . ($user->hasRole('PLATFORM_ADMIN') ? 'YES' : 'NO') . "\n";
echo "Has COMPANY_ADMIN: " . ($user->hasRole('COMPANY_ADMIN') ? 'YES' : 'NO') . "\n";
echo "Has AGENT: " . ($user->hasRole('AGENT') ? 'YES' : 'NO') . "\n";
echo "Has USER: " . ($user->hasRole('USER') ? 'YES' : 'NO') . "\n";

// Verificar permisos
echo "\n--- Permissions ---\n";
echo "Can manage-platform: " . ($user->can('manage-platform') ? 'YES' : 'NO') . "\n";
echo "Can manage-company: " . ($user->can('manage-company') ? 'YES' : 'NO') . "\n";
