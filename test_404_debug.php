<?php

// Quick test to debug 404 handler
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate a request to a non-existent announcement (using a valid UUID)
$fakeUuid = '123e4567-e89b-12d3-a456-426614174000';
$request = \Illuminate\Http\Request::create("/api/announcements/{$fakeUuid}", 'GET');
$request->headers->set('Accept', 'application/json');
$request->headers->set('Authorization', 'Bearer fake_token_for_test');

try {
    $response = $app->handle($request);
    echo "Status: " . $response->getStatusCode() . PHP_EOL;
    echo "Content: " . $response->getContent() . PHP_EOL;
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}
