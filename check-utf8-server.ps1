# Diagnostic script for UTF-8 encoding issues on production server
# Usage: .\check-utf8-server.ps1

Write-Host "🔍 Nere Mining UTF-8 Encoding Diagnostic" -ForegroundColor Cyan
Write-Host "=======================================" -ForegroundColor Cyan

# Check 1: Middleware file exists
Write-Host "`n1️⃣  Checking EnsureUtf8Response middleware..." -ForegroundColor Yellow
if (Test-Path "app/Http/Middleware/EnsureUtf8Response.php") {
    Write-Host "  ✓ Middleware file exists" -ForegroundColor Green
} else {
    Write-Host "  ❌ Middleware file MISSING" -ForegroundColor Red
    Write-Host "  💡 Tip: Run 'git pull' to get the latest code" -ForegroundColor Yellow
}

# Check 2: Migration file exists
Write-Host "`n2️⃣  Checking UTF-8 migration..." -ForegroundColor Yellow
$migration = Get-ChildItem -Path "database/migrations" -Filter "*fix_intranet_utf8*" -ErrorAction SilentlyContinue
if ($migration) {
    Write-Host "  ✓ Migration file exists: $($migration.Name)" -ForegroundColor Green
} else {
    Write-Host "  ❌ Migration file MISSING" -ForegroundColor Red
}

# Check 3: Test blade files contain correct UTF-8
Write-Host "`n3️⃣  Checking blade files for French accents..." -ForegroundColor Yellow
$bladeFiles = Get-ChildItem -Path "resources/views/intranet" -Filter "*.blade.php" -Recurse
$frenchWords = @("Créer", "Département", "Sûreté", "Approvisionnement", "Sélectionnez")
$found = 0

foreach ($file in $bladeFiles) {
    $content = Get-Content $file.FullName -Encoding UTF8
    foreach ($word in $frenchWords) {
        if ($content -match [regex]::Escape($word)) {
            $found++
            break
        }
    }
}

if ($found -gt 0) {
    Write-Host "  ✓ French accents found in blade files ($found files)" -ForegroundColor Green
} else {
    Write-Host "  ⚠️  No French accents detected in blade files" -ForegroundColor Yellow
}

# Check 4: Test HTTP headers
Write-Host "`n4️⃣  Testing HTTP headers (Content-Type charset)..." -ForegroundColor Yellow
Write-Host "  Testing: http://127.0.0.1:8000/intranet" -ForegroundColor Gray

$testUrl = "http://127.0.0.1:8000/intranet"
try {
    $response = Invoke-WebRequest -Uri $testUrl -UseBasicParsing -ErrorAction Stop -TimeoutSec 5
    $contentType = $response.Headers['Content-Type']
    
    if ($contentType -match "charset=utf-?8") {
        Write-Host "  ✓ HTTP header has charset=utf-8: $contentType" -ForegroundColor Green
    } else {
        Write-Host "  ❌ HTTP header missing charset=utf-8: $contentType" -ForegroundColor Red
        Write-Host "  💡 Tip: Run 'composer dump-autoload' and 'php artisan config:cache'" -ForegroundColor Yellow
    }
    
    # Check 5: Test for broken UTF-8 patterns in response
    Write-Host "`n5️⃣  Scanning response for broken UTF-8 patterns..." -ForegroundColor Yellow
    $body = $response.Content
    
    $brokenPatterns = @(
        "CrÃ©er",
        "DÃ©partement",
        "SÃ©lectionnez",
        "SÃ»retÃ©",
        "Approvisionnement",
        "Ã©",
        "Ã§",
        "Ã®"
    )
    
    $foundBroken = $false
    foreach ($pattern in $brokenPatterns) {
        if ($body -match [regex]::Escape($pattern)) {
            Write-Host "  ❌ Found broken UTF-8: $pattern" -ForegroundColor Red
            $foundBroken = $true
        }
    }
    
    if (-not $foundBroken) {
        Write-Host "  ✓ No broken UTF-8 patterns detected" -ForegroundColor Green
    }
    
} catch [System.Net.WebException] {
    Write-Host "  ⚠️  Could not connect to http://127.0.0.1:8000/intranet" -ForegroundColor Yellow
    Write-Host "  💡 Tip: Make sure the development server is running (php artisan serve)" -ForegroundColor Yellow
}

# Summary
Write-Host "`n" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════" -ForegroundColor Cyan
Write-Host "📋 Diagnostic Summary" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════" -ForegroundColor Cyan

Write-Host "`n✅ If all checks above are green:" -ForegroundColor Green
Write-Host "   - Middleware is deployed"
Write-Host "   - UTF-8 headers are set correctly"
Write-Host "   - No broken UTF-8 patterns in responses"

Write-Host "`n❌ If you see red items:" -ForegroundColor Red
Write-Host "   1. Run: git pull origin main"
Write-Host "   2. Run: .\deploy.ps1"
Write-Host "   3. Run: php artisan config:cache"
Write-Host "   4. Run: php artisan route:cache"

Write-Host "`n💡 Quick fixes:" -ForegroundColor Yellow
Write-Host "   - Clear cache: php artisan cache:clear"
Write-Host "   - Recache routes: php artisan route:cache"
Write-Host "   - Recache config: php artisan config:cache"
Write-Host "   - Regenerate autoload: composer dump-autoload -o"

Write-Host "`n"
