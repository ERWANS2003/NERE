# ITSM NERE Mining - Detailed Diagnostic Script for Windows Server
# This script performs comprehensive diagnostics for Laravel on IIS

Write-Host "=== ITSM NERE Mining - Detailed Diagnostics ===" -ForegroundColor Green
Write-Host "Starting comprehensive diagnostic..." -ForegroundColor Yellow

# 1. Check current directory and Laravel installation
Write-Host "`n1. Checking Laravel Installation:" -ForegroundColor Cyan
$currentPath = Get-Location
Write-Host "Current directory: $currentPath"

if (Test-Path "artisan") {
    Write-Host "[OK] Laravel artisan file found" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Laravel artisan file not found!" -ForegroundColor Red
}

if (Test-Path "composer.json") {
    Write-Host "[OK] composer.json found" -ForegroundColor Green
} else {
    Write-Host "[ERROR] composer.json not found!" -ForegroundColor Red
}

# 2. Check .env file
Write-Host "`n2. Checking Environment Configuration:" -ForegroundColor Cyan
if (Test-Path ".env") {
    Write-Host "[OK] .env file exists" -ForegroundColor Green
    
    # Read .env content safely
    $envContent = Get-Content ".env" -ErrorAction SilentlyContinue
    if ($envContent) {
        Write-Host "Environment variables found:"
        $envContent | Where-Object { $_ -match "^(APP_|DB_|LOG_)" } | ForEach-Object {
            if ($_ -notmatch "PASSWORD|SECRET|KEY") {
                Write-Host "  $_"
            } else {
                $key = ($_ -split "=")[0]
                Write-Host "  $key=***"
            }
        }
    }
} else {
    Write-Host "[ERROR] .env file missing!" -ForegroundColor Red
}

# 3. Check PHP installation and version
Write-Host "`n3. Checking PHP:" -ForegroundColor Cyan
try {
    $phpVersion = php -v 2>$null
    if ($phpVersion) {
        $firstLine = $phpVersion.Split("`n")[0]
        Write-Host "[OK] PHP found: $firstLine" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] PHP not found or not in PATH!" -ForegroundColor Red
    }
} catch {
    Write-Host "[ERROR] PHP check failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 4. Check Composer
Write-Host "`n4. Checking Composer:" -ForegroundColor Cyan
try {
    $composerVersion = composer --version 2>$null
    if ($composerVersion) {
        Write-Host "[OK] Composer found: $composerVersion" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] Composer not found!" -ForegroundColor Red
    }
} catch {
    Write-Host "[ERROR] Composer check failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 5. Check Laravel dependencies
Write-Host "`n5. Checking Laravel Dependencies:" -ForegroundColor Cyan
if (Test-Path "vendor") {
    Write-Host "[OK] vendor directory exists" -ForegroundColor Green
    
    if (Test-Path "vendor\autoload.php") {
        Write-Host "[OK] Composer autoload file exists" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] Composer autoload.php missing!" -ForegroundColor Red
    }
} else {
    Write-Host "[ERROR] vendor directory missing - run 'composer install'" -ForegroundColor Red
}

# 6. Check critical Laravel files and directories
Write-Host "`n6. Checking Laravel Structure:" -ForegroundColor Cyan
$criticalFiles = @(
    "bootstrap\app.php",
    "config\app.php",
    "routes\web.php",
    "app\Http\Kernel.php"
)

$criticalDirs = @(
    "storage\logs",
    "storage\framework",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "bootstrap\cache"
)

foreach ($file in $criticalFiles) {
    if (Test-Path $file) {
        Write-Host "[OK] $file exists" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] $file missing!" -ForegroundColor Red
    }
}

foreach ($dir in $criticalDirs) {
    if (Test-Path $dir) {
        Write-Host "[OK] $dir exists" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] $dir missing!" -ForegroundColor Red
        try {
            New-Item -Path $dir -ItemType Directory -Force | Out-Null
            Write-Host "[FIXED] Created $dir" -ForegroundColor Yellow
        } catch {
            Write-Host "[FAILED] Could not create $dir" -ForegroundColor Red
        }
    }
}

# 7. Check file permissions (Windows equivalent)
Write-Host "`n7. Checking File Permissions:" -ForegroundColor Cyan
$storageWritable = $false
try {
    $testFile = "storage\test-write.tmp"
    "test" | Out-File $testFile -ErrorAction Stop
    Remove-Item $testFile -ErrorAction SilentlyContinue
    $storageWritable = $true
    Write-Host "[OK] storage directory is writable" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] storage directory not writable!" -ForegroundColor Red
}

# 8. Check Laravel configuration
Write-Host "`n8. Checking Laravel Configuration:" -ForegroundColor Cyan
try {
    $configCheck = php artisan config:show app.name 2>&1
    if ($configCheck -match "ITSM") {
        Write-Host "[OK] Laravel configuration accessible" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Laravel config may have issues: $configCheck" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ERROR] Cannot access Laravel config: $($_.Exception.Message)" -ForegroundColor Red
}

# 9. Check database connection
Write-Host "`n9. Testing Database Connection:" -ForegroundColor Cyan
try {
    $dbTest = php artisan migrate:status 2>&1
    if ($dbTest -match "Migration name" -or $dbTest -match "Ran?") {
        Write-Host "[OK] Database connection working" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Database connection issues: $dbTest" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ERROR] Database test failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 10. Check recent Laravel logs
Write-Host "`n10. Checking Recent Logs:" -ForegroundColor Cyan
$logFile = "storage\logs\laravel.log"
if (Test-Path $logFile) {
    Write-Host "[OK] Laravel log file exists" -ForegroundColor Green
    try {
        $recentLogs = Get-Content $logFile -Tail 20 -ErrorAction SilentlyContinue
        if ($recentLogs) {
            Write-Host "Recent log entries (last 20 lines):"
            $recentLogs | ForEach-Object { 
                if ($_ -match "ERROR|CRITICAL|EMERGENCY") {
                    Write-Host "  $_" -ForegroundColor Red
                } elseif ($_ -match "WARNING") {
                    Write-Host "  $_" -ForegroundColor Yellow
                } else {
                    Write-Host "  $_"
                }
            }
        } else {
            Write-Host "[INFO] Log file is empty" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "[ERROR] Cannot read log file: $($_.Exception.Message)" -ForegroundColor Red
    }
} else {
    Write-Host "[WARNING] No Laravel log file found" -ForegroundColor Yellow
}

# 11. Check web.config for IIS
Write-Host "`n11. Checking IIS Configuration:" -ForegroundColor Cyan
if (Test-Path "web.config") {
    Write-Host "[OK] web.config exists" -ForegroundColor Green
} else {
    Write-Host "[ERROR] web.config missing for IIS!" -ForegroundColor Red
}

if (Test-Path "public\web.config") {
    Write-Host "[OK] public\web.config exists" -ForegroundColor Green
} else {
    Write-Host "[ERROR] public\web.config missing!" -ForegroundColor Red
}

Write-Host "`n=== Diagnostic Complete ===" -ForegroundColor Green
Write-Host "Review the output above for any [ERROR] or [WARNING] items." -ForegroundColor Yellow
Write-Host "Next: Run fix-detailed.ps1 to attempt automatic fixes." -ForegroundColor Cyan