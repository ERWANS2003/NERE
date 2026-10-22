# Update an existing Laravel deployment on Windows Server / IIS.
# Run from the project root in an elevated PowerShell session:
# .\update-windows-server.ps1 -BackupConfirmed

param(
    [switch]$BackupConfirmed
)

$ErrorActionPreference = "Stop"
$ProjectPath = $PSScriptRoot
$OriginalLocation = Get-Location

function Invoke-CheckedCommand {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Executable,

        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [string[]]$Arguments
    )

    Write-Host "`n> $Executable $($Arguments -join ' ')" -ForegroundColor Cyan
    & $Executable @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "Command '$Executable $($Arguments -join ' ')' failed with exit code $LASTEXITCODE."
    }
}

try {
    if (-not $BackupConfirmed) {
        throw "Back up the production database first, then rerun with -BackupConfirmed."
    }

    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = [Security.Principal.WindowsPrincipal]::new($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw "Run this deployment script from an elevated PowerShell session."
    }

    foreach ($command in @("git", "php", "composer", "npm", "iisreset")) {
        if (-not (Get-Command $command -ErrorAction SilentlyContinue)) {
            throw "Required command '$command' was not found in PATH."
        }
    }

    if (-not (Test-Path (Join-Path $ProjectPath "artisan"))) {
        throw "Laravel's artisan file was not found beside this script."
    }

    $phpVersion = (& php -r "echo PHP_VERSION;").Trim()
    if ($LASTEXITCODE -ne 0 -or [version]$phpVersion -lt [version]"8.3") {
        throw "PHP 8.3 or newer is required; detected '$phpVersion'."
    }

    Set-Location -Path $ProjectPath

    $envPath = Join-Path $ProjectPath ".env"
    if (-not (Test-Path $envPath)) {
        throw "Production .env is missing. Create and configure it before deploying."
    }

    $envContent = Get-Content -Path $envPath -Raw
    if ($envContent -notmatch "(?m)^APP_KEY=base64:.+") {
        throw "APP_KEY is missing from .env. Do not generate a new key during an update; restore the existing production key."
    }

    if ($envContent -match "(?m)^APP_DEBUG=true\s*$") {
        throw "APP_DEBUG is true in .env. Disable debug mode before deploying to production."
    }

    $branch = (& git branch --show-current).Trim()
    if ($LASTEXITCODE -ne 0) {
        throw "Could not determine the current Git branch."
    }
    if ($branch -ne "main") {
        throw "Expected branch 'main', found '$branch'. Switch to main before deploying."
    }

    $changes = & git status --porcelain --untracked-files=normal
    if ($LASTEXITCODE -ne 0) {
        throw "Could not inspect the Git working tree."
    }
    if ($changes) {
        throw "The Git working tree is not clean. Preserve or commit the server-side changes before deploying."
    }

    Write-Host "Database backup confirmed; environment and clean working tree checks passed." -ForegroundColor Green

    Invoke-CheckedCommand -Executable "git" -Arguments @("pull", "--ff-only", "origin", "main")
    Invoke-CheckedCommand -Executable "composer" -Arguments @("install", "--no-dev", "--no-interaction", "--prefer-dist", "--optimize-autoloader")
    Invoke-CheckedCommand -Executable "npm" -Arguments @("ci", "--include=dev")
    Invoke-CheckedCommand -Executable "npm" -Arguments @("run", "build")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "optimize:clear")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "migrate", "--force")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "db:seed", "--class=Database\Seeders\ResourceSeeder", "--force")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "optimize")

    Write-Host "`n> Verifying central administration routes" -ForegroundColor Cyan
    $routeListJson = & php artisan route:list --json
    if ($LASTEXITCODE -ne 0) {
        throw "Could not verify the registered Laravel routes."
    }

    try {
        $registeredRoutes = @($routeListJson | ConvertFrom-Json)
    } catch {
        throw "Laravel returned invalid JSON while verifying registered routes: $($_.Exception.Message)"
    }

    $registeredRouteNames = @($registeredRoutes | ForEach-Object { $_.name })
    $requiredRoutes = @(
        "admin.modules.index",
        "admin.time-off.index",
        "admin.resources.index",
        "admin.tickets.index"
    )
    $missingRoutes = @($requiredRoutes | Where-Object { $_ -notin $registeredRouteNames })
    if ($missingRoutes.Count -gt 0) {
        throw "Central administration routes are missing: $($missingRoutes -join ', ')."
    }
    Write-Host "Central administration routes are registered." -ForegroundColor Green

    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "migrate:status")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "queue:restart")
    Invoke-CheckedCommand -Executable "iisreset" -Arguments @()

    Write-Host "`nApplication update completed successfully." -ForegroundColor Green
    Write-Host "Check the login page, intranet, and Laravel log before closing the maintenance window."
} catch {
    Write-Host "`nDeployment stopped: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
} finally {
    Set-Location -Path $OriginalLocation
}
