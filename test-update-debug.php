<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\Role;
use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;

// Crear admin con factory
echo "Creating admin...\n";
$user = User::factory()->create();
$role = Role::findByCode('COMPANY_ADMIN');
$company = Company::factory()->create();

// Crear UserRole manualmente
$userRole = \App\Features\UserManagement\Models\UserRole::create([
    'user_id' => $user->id,
    'role_id' => $role->id,
    'role_code' => $role->role_code,
    'company_id' => $company->id,
]);

echo "Admin created: {$user->id}\n";
echo "Company: {$company->id}\n";

// Crear artículo
echo "Creating article...\n";
$category = ArticleCategory::first();
$article = HelpCenterArticle::factory()->create([
    'company_id' => $company->id,
    'author_id' => $user->id,
    'category_id' => $category->id,
    'title' => 'Test Article',
    'content' => str_repeat('Test content. ', 10),
    'status' => 'DRAFT',
]);

echo "Article created: {$article->id}\n";

// Simular request de actualización
echo "\nSimulating PUT request...\n";

try {
    // Crear token JWT (usando TokenService)
    $tokenService = app(\App\Features\Authentication\Services\TokenService::class);
    $tokens = $tokenService->generateTokens($user, $userRole);

    // Simular request
    $request = Illuminate\Http\Request::create(
        "/api/help-center/articles/{$article->id}",
        'PUT',
        ['title' => 'Updated Title', 'content' => str_repeat('Updated content. ', 10)]
    );

    $request->headers->set('Authorization', 'Bearer ' . $tokens['access_token']);

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);

    echo "Status: {$response->getStatusCode()}\n";
    echo "Response:\n{$response->getContent()}\n";

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
}
