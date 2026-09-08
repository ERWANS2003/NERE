<?php

// Bootstrap Laravel pour Vercel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Gérer la requête
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

try {
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    http_response_code(500);
    if (getenv('APP_DEBUG') === 'true') {
        echo "Error: " . $e->getMessage();
    } else {
        echo "Application Error";
    }
}