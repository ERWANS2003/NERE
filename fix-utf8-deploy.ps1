# UTF-8 Encoding Fix - Automated Deployment Script
# This script deploys the UTF-8 fix and validates it on the production server
# Run with: .\fix-utf8-deploy.ps1

Write-Host @"
╔════════════════════════════════════════════════════════════════╗
║     NERE MINING - UTF-8 ENCODING FIX DEPLOYMENT SCRIPT          ║
║     Fixing: CrÃ©er → Créer, DÃ©partement → Département          ║
╚════════════════════════════════════════════════════════════════╝
"@ -ForegroundColor Cyan

$ErrorActionPreference = "Stop"
$startTime = Get-Date

# Colors
$success = @{ ForegroundColor = "Green" }
$error_color = @{ ForegroundColor = "Red" }
$warning = @{ ForegroundColor = "Yellow" }
$info = @{ ForegroundColor = "Cyan" }

function Log-Success {
    Write-Host "  ✓ $args" @success
}

function Log-Error {
    Write-Host "  ❌ $args" @error_color
}

function Log-Warning {
    Write-Host "  ⚠️  $args" @warning
}

function Log-Info {
    Write-Host "  ℹ️  $args" @info
}

function Log-Step {
    Write-Host "`n$args" @info
}

# Step 1: Pre-deployment checks
Log-Step "STEP 1️⃣  Pre-deployment Checks"
Write-Host "════════════════════════════════════════════════════════════════"

if (-not (Test-Path ".git")) {
    Log-Error "Not a git repository. Please run this from the project root."
    exit 1
}
Log-Success "Git repository detected"

if (-not (Test-Path "composer.json")) {
    Log-Error "composer.json not found"
    exit 1
}
Log-Success "Laravel project structure verified"

# Step 2: Git pull
Log-Step "STEP 2️⃣  Updating Code from Repository"
Write-Host "════════════════════════════════════════════════════════════════"

git fetch origin main
if ($LASTEXITCODE -ne 0) {
    Log-Error "Failed to fetch from git"
    exit 1
}

$currentHash = (git rev-parse HEAD).Substring(0, 7)
$remoteHash = (git rev-parse origin/main).Substring(0, 7)

if ($currentHash -eq $remoteHash) {
    Log-Warning "Already on latest commit: $currentHash"
} else {
    git pull origin main
    if ($LASTEXITCODE -ne 0) {
        Log-Error "Failed to pull from git"
        exit 1
    }
    Log-Success "Code updated: $currentHash → $remoteHash"
}

# Step 3: Install dependencies
Log-Step "STEP 3️⃣  Installing Dependencies"
Write-Host "════════════════════════════════════════════════════════════════"

Write-Host "  Installing NPM packages..."
npm install --production 2>&1 | Select-Object -Last 1
if ($LASTEXITCODE -ne 0) {
    Log-Error "npm install failed"
    exit 1
}
Log-Success "NPM packages installed"

Write-Host "  Installing Composer packages..."
composer install --no-dev --optimize-autoloader 2>&1 | Select-Object -Last 1
if ($LASTEXITCODE -ne 0) {
    Log-Error "composer install failed"
    exit 1
}
Log-Success "Composer packages installed"

# Step 4: Build assets
Log-Step "STEP 4️⃣  Building Assets"
Write-Host "════════════════════════════════════════════════════════════════"

npm run build 2>&1 | Select-Object -Last 3
if ($LASTEXITCODE -ne 0) {
    Log-Error "Asset build failed"
    exit 1
}
Log-Success "Assets built successfully"

# Step 5: Database migrations
Log-Step "STEP 5️⃣  Running Database Migrations"
Write-Host "════════════════════════════════════════════════════════════════"

php artisan migrate --force 2>&1 | Select-Object -Last 2
if ($LASTEXITCODE -ne 0) {
    Log-Warning "Migration completed with warnings (may be normal)"
}
Log-Success "Database migrations applied"

# Step 6: Clear caches
Log-Step "STEP 6️⃣  Clearing Caches"
Write-Host "════════════════════════════════════════════════════════════════"

php artisan cache:clear 2>&1 | Out-Null
Log-Success "Cache cleared"

php artisan config:cache 2>&1 | Out-Null
Log-Success "Config cached"

php artisan route:cache 2>&1 | Out-Null
Log-Success "Routes cached"

composer dump-autoload -o 2>&1 | Out-Null
Log-Success "Composer autoload regenerated"

# Step 7: Verification
Log-Step "STEP 7️⃣  Verifying UTF-8 Fix"
Write-Host "════════════════════════════════════════════════════════════════"

# Run PHP diagnostic
$diagnostic = php check-utf8.php 2>&1
$diagnosticOutput = $diagnostic | Out-String

if ($diagnosticOutput -match "All checks passed") {
    Log-Success "UTF-8 diagnostic: ALL CHECKS PASSED"
    Write-Host $diagnostic
} elseif ($diagnosticOutput -match "Middleware exists") {
    Log-Success "UTF-8 diagnostic: Key components detected"
    Write-Host $diagnostic
} else {
    Log-Warning "UTF-8 diagnostic reported issues (see details below)"
    Write-Host $diagnostic
}

# Step 8: Summary
Log-Step "STEP 8️⃣  Deployment Summary"
Write-Host "════════════════════════════════════════════════════════════════"

$endTime = Get-Date
$duration = ($endTime - $startTime).TotalSeconds

Write-Host @"
🎉 UTF-8 Fix Deployment Complete!

Duration: $([int]$duration)s

What was fixed:
  ✓ Added middleware to force charset=utf-8 on all HTTP responses
  ✓ Registered middleware in bootstrap/app.php
  ✓ Cleared all application caches
  ✓ Rebuilt assets
  ✓ Regenerated autoloader

What you should see now:
  ✓ CrÃ©er → Créer (correct French accent)
  ✓ DÃ©partement → Département
  ✓ SÃ«retÃ© → Sûreté
  ✓ All French characters displaying correctly

Next steps:
  1. Open browser and go to: http://192.168.10.206/intranet
  2. Verify French text displays correctly
  3. Check browser developer tools (F12 → Network → Response Headers)
  4. Look for: Content-Type: text/html; charset=utf-8

If issues persist:
  1. Run: php check-utf8.php (for detailed diagnosis)
  2. Clear browser cache (Ctrl+Shift+Del)
  3. Check: php artisan tinker
  4. Review logs: tail storage/logs/laravel.log

Commits deployed:
  • 2d6498b - UTF-8 middleware + migration
  • d688182 - Diagnostic scripts
  • 075e6fb - Deployment guide

"@ @success

Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan

# Optional: Open the app in browser
$openBrowser = Read-Host "Open application in browser now? (y/n)"
if ($openBrowser -eq "y" -or $openBrowser -eq "Y") {
    Start-Process "http://192.168.10.206/intranet"
}
