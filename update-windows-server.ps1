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

    Set-Location -Path $ProjectPath

    $branch = (& git branch --show-current).Trim()
    if ($LASTEXITCODE -ne 0) {
        throw "Could not determine the current Git branch."
    }
    if ($branch -ne "main") {
        throw "Expected branch 'main', found '$branch'. Switch to main before deploying."
    }

    $changes = & git status --porcelain
    if ($LASTEXITCODE -ne 0) {
        throw "Could not inspect the Git working tree."
    }

    Write-Host "Database backup confirmed; environment and working tree checks passed." -ForegroundColor Green

    Invoke-CheckedCommand -Executable "git" -Arguments @("pull", "--ff-only", "origin", "main")
    Invoke-CheckedCommand -Executable "composer" -Arguments @("install", "--no-dev", "--no-interaction", "--prefer-dist", "--optimize-autoloader")
    Invoke-CheckedCommand -Executable "npm" -Arguments @("ci", "--include=dev")
    Invoke-CheckedCommand -Executable "npm" -Arguments @("run", "build")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "optimize:clear")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "migrate", "--force")
    Invoke-CheckedCommand -Executable "php" -Arguments @("artisan", "optimize")
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
