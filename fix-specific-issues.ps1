# ITSM NERE Mining - Fix Specific Issues from Diagnostics
# This script addresses the specific errors found in the diagnostic output

Write-Host "=== Fixing Specific Issues ===" -ForegroundColor Green

# 1. Fix .env configuration for production
Write-Host "`n1. Updating .env Configuration:" -ForegroundColor Cyan
if (Test-Path ".env") {
    $envContent = Get-Content ".env"
    $newContent = @()
    
    foreach ($line in $envContent) {
        if ($line -match "^APP_URL=") {
            $newContent += "APP_URL=http://192.168.10.206"
            Write-Host "Updated APP_URL to production server" -ForegroundColor Yellow
        } elseif ($line -match "^DB_HOST=") {
            $newContent += "DB_HOST=127.0.0.1"
            Write-Host "Updated DB_HOST to localhost" -ForegroundColor Yellow
        } elseif ($line -match "^DB_DATABASE=") {
            $newContent += "DB_DATABASE=itsm_nere_mining"
            Write-Host "Updated DB_DATABASE" -ForegroundColor Yellow
        } elseif ($line -match "^APP_DEBUG=") {
            $newContent += "APP_DEBUG=true"
            Write-Host "Kept debug mode enabled for troubleshooting" -ForegroundColor Yellow
        } else {
            $newContent += $line
        }
    }
    
    # Add missing environment variables if not found
    $hasAppUrl = $envContent | Where-Object { $_ -match "^APP_URL=" }
    if (-not $hasAppUrl) {
        $newContent += "APP_URL=http://192.168.10.206"
        Write-Host "Added APP_URL" -ForegroundColor Yellow
    }
    
    $newContent | Out-File ".env" -Encoding UTF8
    Write-Host "[OK] .env file updated" -ForegroundColor Green
}

# 2. Recreate autoload files
Write-Host "`n2. Rebuilding Composer Autoload:" -ForegroundColor Cyan
try {
    composer dump-autoload --optimize
    Write-Host "[OK] Composer autoload rebuilt" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Failed to rebuild autoload" -ForegroundColor Red
}

# 3. Ensure database exists and is accessible
Write-Host "`n3. Database Setup:" -ForegroundColor Cyan
try {
    # Try to create database if it doesn't exist
    Write-Host "Attempting to create database..."
    $createDbCmd = 'mysql -u root -e "CREATE DATABASE IF NOT EXISTS itsm_nere_mining CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'
    Invoke-Expression $createDbCmd 2>$null
    Write-Host "[OK] Database creation attempted" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Could not create database - may already exist" -ForegroundColor Yellow
}

# 4. Reset Laravel completely
Write-Host "`n4. Complete Laravel Reset:" -ForegroundColor Cyan
$resetCommands = @(
    "config:clear",
    "cache:clear", 
    "route:clear",
    "view:clear"
)

foreach ($cmd in $resetCommands) {
    try {
        Write-Host "Running: php artisan $cmd"
        php artisan $cmd
    } catch {
        Write-Host "[WARNING] Failed: $cmd" -ForegroundColor Yellow
    }
}

# 5. Generate fresh application key
Write-Host "`n5. Generating Fresh Application Key:" -ForegroundColor Cyan
try {
    php artisan key:generate --force
    Write-Host "[OK] New application key generated" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Failed to generate key" -ForegroundColor Red
}

# 6. Fix storage permissions (Windows/IIS specific)
Write-Host "`n6. Fixing Storage Permissions:" -ForegroundColor Cyan
$paths = @("storage", "bootstrap\cache", "public")
foreach ($path in $paths) {
    if (Test-Path $path) {
        try {
            # Give full control to IIS user accounts
            icacls $path /grant "Everyone:(OI)(CI)F" /T /Q 2>$null
            icacls $path /grant "IIS_IUSRS:(OI)(CI)F" /T /Q 2>$null
            icacls $path /grant "IUSR:(OI)(CI)F" /T /Q 2>$null
            Write-Host "[OK] Fixed permissions for $path" -ForegroundColor Green
        } catch {
            Write-Host "[WARNING] Could not set permissions for $path" -ForegroundColor Yellow
        }
    }
}

# 7. Ensure index.php is correct
Write-Host "`n7. Verifying index.php:" -ForegroundColor Cyan
if (Test-Path "public\index.php") {
    Write-Host "[OK] public\index.php exists" -ForegroundColor Green
} else {
    Write-Host "[ERROR] public\index.php missing!" -ForegroundColor Red
    # Create a basic index.php if missing
    $indexContent = @'
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
'@
    $indexContent | Out-File "public\index.php" -Encoding UTF8
    Write-Host "[CREATED] public\index.php" -ForegroundColor Green
}

# 8. Test Laravel installation
Write-Host "`n8. Testing Laravel Installation:" -ForegroundColor Cyan
try {
    $testResult = php artisan --version 2>&1
    Write-Host "Laravel version: $testResult" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Laravel not responding" -ForegroundColor Red
}

Write-Host "`n=== Specific Issues Fix Complete ===" -ForegroundColor Green
Write-Host "Please test the website now at http://192.168.10.206" -ForegroundColor Cyan
Write-Host "If you still get errors, they should now show detailed information." -ForegroundColor Yellow