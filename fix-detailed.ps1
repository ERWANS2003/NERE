# ITSM NERE Mining - Detailed Fix Script for Windows Server
# This script attempts to fix common Laravel on IIS issues

Write-Host "=== ITSM NERE Mining - Detailed Fix Script ===" -ForegroundColor Green
Write-Host "Attempting to fix common issues..." -ForegroundColor Yellow

# 1. Install/Update Composer dependencies
Write-Host "`n1. Installing Composer Dependencies:" -ForegroundColor Cyan
try {
    Write-Host "Running composer install..."
    $composerResult = composer install --no-dev --optimize-autoloader 2>&1
    Write-Host $composerResult
    Write-Host "[OK] Composer install completed" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Composer install failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 2. Create missing directories
Write-Host "`n2. Creating Missing Directories:" -ForegroundColor Cyan
$requiredDirs = @(
    "storage\logs",
    "storage\framework",
    "storage\framework\cache",
    "storage\framework\sessions", 
    "storage\framework\views",
    "bootstrap\cache",
    "storage\app",
    "storage\app\public"
)

foreach ($dir in $requiredDirs) {
    if (-not (Test-Path $dir)) {
        try {
            New-Item -Path $dir -ItemType Directory -Force | Out-Null
            Write-Host "[CREATED] $dir" -ForegroundColor Green
        } catch {
            Write-Host "[ERROR] Failed to create $dir" -ForegroundColor Red
        }
    }
}

# 3. Clear and rebuild Laravel caches
Write-Host "`n3. Clearing Laravel Caches:" -ForegroundColor Cyan
$cacheCommands = @(
    "config:clear",
    "route:clear", 
    "view:clear",
    "cache:clear"
)

foreach ($command in $cacheCommands) {
    try {
        Write-Host "Running: php artisan $command"
        $result = php artisan $command 2>&1
        Write-Host $result
    } catch {
        Write-Host "[ERROR] Failed: php artisan $command" -ForegroundColor Red
    }
}

# 4. Generate application key if missing
Write-Host "`n4. Checking Application Key:" -ForegroundColor Cyan
if (Test-Path ".env") {
    $envContent = Get-Content ".env"
    $hasAppKey = $envContent | Where-Object { $_ -match "^APP_KEY=" -and $_ -notmatch "^APP_KEY=$" }
    
    if (-not $hasAppKey) {
        Write-Host "Generating application key..."
        try {
            php artisan key:generate
            Write-Host "[OK] Application key generated" -ForegroundColor Green
        } catch {
            Write-Host "[ERROR] Failed to generate application key" -ForegroundColor Red
        }
    } else {
        Write-Host "[OK] Application key exists" -ForegroundColor Green
    }
}

# 5. Run database migrations
Write-Host "`n5. Running Database Migrations:" -ForegroundColor Cyan
try {
    Write-Host "Running migrations..."
    $migrateResult = php artisan migrate --force 2>&1
    Write-Host $migrateResult
    Write-Host "[OK] Database migrations completed" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Migration failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 6. Seed database if needed
Write-Host "`n6. Seeding Database:" -ForegroundColor Cyan
try {
    Write-Host "Running database seeders..."
    $seedResult = php artisan db:seed --force 2>&1
    Write-Host $seedResult
    Write-Host "[OK] Database seeding completed" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Seeding failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 7. Create storage link
Write-Host "`n7. Creating Storage Link:" -ForegroundColor Cyan
try {
    $linkResult = php artisan storage:link 2>&1
    Write-Host $linkResult
    Write-Host "[OK] Storage link created" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Storage link failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 8. Optimize Laravel for production
Write-Host "`n8. Optimizing for Production:" -ForegroundColor Cyan
$optimizeCommands = @(
    "config:cache",
    "route:cache",
    "view:cache"
)

foreach ($command in $optimizeCommands) {
    try {
        Write-Host "Running: php artisan $command"
        $result = php artisan $command 2>&1
        Write-Host $result
    } catch {
        Write-Host "[ERROR] Failed: php artisan $command" -ForegroundColor Red
    }
}

# 9. Set proper file permissions (Windows equivalent)
Write-Host "`n9. Setting File Permissions:" -ForegroundColor Cyan
$writeableDirs = @("storage", "bootstrap\cache")

foreach ($dir in $writeableDirs) {
    if (Test-Path $dir) {
        try {
            # Grant full control to IIS_IUSRS and IUSR accounts
            icacls $dir /grant "IIS_IUSRS:(OI)(CI)F" /T 2>$null
            icacls $dir /grant "IUSR:(OI)(CI)F" /T 2>$null
            Write-Host "[OK] Set permissions for $dir" -ForegroundColor Green
        } catch {
            Write-Host "[WARNING] Could not set permissions for $dir" -ForegroundColor Yellow
        }
    }
}

# 10. Check web.config files
Write-Host "`n10. Checking Web.config Files:" -ForegroundColor Cyan

# Root web.config
if (-not (Test-Path "web.config")) {
    Write-Host "Creating root web.config..."
    $rootWebConfig = @'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Redirect to public folder" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions logicalGrouping="MatchAll" trackAllCaptures="false">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="public/{R:1}" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
'@
    $rootWebConfig | Out-File "web.config" -Encoding UTF8
    Write-Host "[CREATED] Root web.config" -ForegroundColor Green
}

# Public web.config
if (-not (Test-Path "public\web.config")) {
    Write-Host "Creating public\web.config..."
    $publicWebConfig = @'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Laravel Rewrite" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions logicalGrouping="MatchAll" trackAllCaptures="false">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php/{R:1}" />
                </rule>
            </rules>
        </rewrite>
        <defaultDocument>
            <files>
                <clear />
                <add value="index.php" />
            </files>
        </defaultDocument>
    </system.webServer>
</configuration>
'@
    $publicWebConfig | Out-File "public\web.config" -Encoding UTF8
    Write-Host "[CREATED] Public web.config" -ForegroundColor Green
}

Write-Host "`n=== Fix Script Complete ===" -ForegroundColor Green
Write-Host "All automatic fixes have been attempted." -ForegroundColor Yellow
Write-Host "Please test the website at http://192.168.10.206" -ForegroundColor Cyan
Write-Host "If issues persist, check the Laravel logs in storage\logs\" -ForegroundColor Cyan