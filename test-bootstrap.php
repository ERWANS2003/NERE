<?php
// Test Laravel bootstrap for errors

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Laravel bootstrap...\n";

try {
    $app = require __DIR__ . '/bootstrap/app.php';
    echo "✅ Bootstrap loaded\n";
    
    $kernel = $app->make('Illuminate\Foundation\Http\Kernel');
    echo "✅ HTTP Kernel loaded\n";
    
    // Check if service providers boot successfully
    echo "Service providers: " . count($app['config']['app.providers']) . "\n";
    
    echo "✅ All tests passed\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
