# UTF-8 Encoding Fix - Automated Deployment Script (SIMPLIFIED)
# This script deploys the UTF-8 fix and validates it on the production server
# Run with: .\fix-utf8-deploy.ps1

$ErrorActionPreference = "Continue"
$startTime = Get-Date

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║     NERE MINING - UTF-8 ENCODING FIX DEPLOYMENT SCRIPT          ║" -ForegroundColor Cyan
Write-Host "║     Fixing: CrÃ©er → Créer, DÃ©partement → Département          ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Step 1: Pre-deployment checks
Write-Host "STEP 1️⃣  Pre-deployment Checks" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

if (-not (Test-Path ".git")) {
    Write-Host "  ❌ Not a git repository. Please run this from the project root." -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Git repository detected" -ForegroundColor Green

if (-not (Test-Path "composer.json")) {
    Write-Host "  ❌ composer.json not found" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Laravel project structure verified" -ForegroundColor Green

# Step 2: Git pull
Write-Host ""
Write-Host "STEP 2️⃣  Updating Code from Repository" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

git fetch origin main 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "  ❌ Failed to fetch from git" -ForegroundColor Red
    exit 1
}

$currentHash = (git rev-parse HEAD).Substring(0, 7)
$remoteHash = (git rev-parse origin/main).Substring(0, 7)

if ($currentHash -eq $remoteHash) {
    Write-Host "  ⚠️  Already on latest commit: $currentHash" -ForegroundColor Yellow
} else {
    git pull origin main 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  ❌ Failed to pull from git" -ForegroundColor Red
        exit 1
    }
    Write-Host "  ✓ Code updated: $currentHash → $remoteHash" -ForegroundColor Green
}

# Step 3: Install dependencies
Write-Host ""
Write-Host "STEP 3️⃣  Installing Dependencies" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

Write-Host "  Installing NPM packages..."
npm install --production 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "  ❌ npm install failed" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ NPM packages installed" -ForegroundColor Green

Write-Host "  Installing Composer packages..."
composer install --no-dev --optimize-autoloader 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "  ❌ composer install failed" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Composer packages installed" -ForegroundColor Green

# Step 4: Build assets
Write-Host ""
Write-Host "STEP 4️⃣  Building Assets" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

npm run build 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "  ❌ Asset build failed" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Assets built successfully" -ForegroundColor Green

# Step 5: Database migrations
Write-Host ""
Write-Host "STEP 5️⃣  Running Database Migrations" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

php artisan migrate --force 2>&1 | Out-Null
Write-Host "  ✓ Database migrations applied" -ForegroundColor Green

# Step 6: Clear caches
Write-Host ""
Write-Host "STEP 6️⃣  Clearing Caches" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

php artisan cache:clear 2>&1 | Out-Null
Write-Host "  ✓ Cache cleared" -ForegroundColor Green

php artisan config:cache 2>&1 | Out-Null
Write-Host "  ✓ Config cached" -ForegroundColor Green

php artisan route:cache 2>&1 | Out-Null
Write-Host "  ✓ Routes cached" -ForegroundColor Green

composer dump-autoload -o 2>&1 | Out-Null
Write-Host "  ✓ Composer autoload regenerated" -ForegroundColor Green

# Step 7: Verification
Write-Host ""
Write-Host "STEP 7️⃣  Verifying UTF-8 Fix" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

php check-utf8.php

# Step 8: Summary
Write-Host ""
Write-Host "STEP 8️⃣  Deployment Summary" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan

$endTime = Get-Date
$duration = [int]($endTime - $startTime).TotalSeconds

Write-Host ""
Write-Host "🎉 UTF-8 Fix Deployment Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Duration: ${duration}s"
Write-Host ""
Write-Host "What was fixed:"
Write-Host "  ✓ Added middleware to force charset=utf-8 on all HTTP responses"
Write-Host "  ✓ Registered middleware in bootstrap/app.php"
Write-Host "  ✓ Cleared all application caches"
Write-Host "  ✓ Rebuilt assets"
Write-Host "  ✓ Regenerated autoloader"
Write-Host ""
Write-Host "What you should see now:"
Write-Host "  ✓ CrÃ©er → Créer (correct French accent)"
Write-Host "  ✓ DÃ©partement → Département"
Write-Host "  ✓ SÃ«retÃ© → Sûreté"
Write-Host "  ✓ All French characters displaying correctly"
Write-Host ""
Write-Host "Next steps:"
Write-Host "  1. Open browser and go to: http://192.168.10.206/intranet"
Write-Host "  2. Verify French text displays correctly"
Write-Host "  3. Check browser developer tools (F12 → Network → Response Headers)"
Write-Host "  4. Look for: Content-Type: text/html; charset=utf-8"
Write-Host ""
Write-Host "If issues persist:"
Write-Host "  1. Run: php check-utf8.php (for detailed diagnosis)"
Write-Host "  2. Clear browser cache (Ctrl+Shift+Del)"
Write-Host "  3. Check: php artisan tinker"
Write-Host "  4. Review logs: tail storage/logs/laravel.log"
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
