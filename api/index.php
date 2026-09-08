<?php

// Vercel Serverless Function for Laravel
// This file handles all requests and routes them to Laravel

use Illuminate\Http\Request;

// Set memory limit and execution time for serverless environment
ini_set('memory_limit', '1024M');
set_time_limit(30);

// Define paths for Vercel serverless environment
define('LARAVEL_START', microtime(true));

// Set up autoloader
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    // Fallback for build process
    require __DIR__.'/../bootstrap/autoload.php';
}

// Bootstrap Laravel application
$app = require_once __DIR__.'/../bootstrap/app.php';

// Handle the incoming request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create request from globals (Vercel provides these)
$request = Request::capture();

try {
    $response = $kernel->handle($request);
    
    // Send the response
    $response->send();
    
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    // Log error and return 500
    error_log('Laravel Error: ' . $e->getMessage());
    
    if (getenv('APP_DEBUG') === 'true') {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
        echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        echo 'Trace: ' . $e->getTraceAsString();
    } else {
        http_response_code(500);
        echo 'Application Error';
    }
}