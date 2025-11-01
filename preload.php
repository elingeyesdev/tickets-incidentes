<?php
/**
 * OPcache Preloader for Laravel
 * This file preloads the most commonly used PHP files into memory
 */

if (!function_exists('opcache_compile_file')) {
    return;
}

$projectRoot = __DIR__;

// Preload Laravel framework files
$frameworkFiles = [
    '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
    '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
    '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
    '/vendor/laravel/framework/src/Illuminate/Http/Request.php',
    '/vendor/laravel/framework/src/Illuminate/Http/Response.php',
];

// Preload core application files
$appFiles = [
    '/bootstrap/app.php',
];

$allFiles = array_merge($frameworkFiles, $appFiles);

foreach ($allFiles as $file) {
    $fullPath = $projectRoot . $file;
    if (file_exists($fullPath)) {
        try {
            opcache_compile_file($fullPath);
        } catch (Throwable $e) {
            // Ignore compilation errors
        }
    }
}

// Preload Composer autoloader files
if (file_exists($projectRoot . '/vendor/autoload.php')) {
    require_once $projectRoot . '/vendor/autoload.php';
}