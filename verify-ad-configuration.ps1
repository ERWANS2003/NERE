# Verify the application's configured Active Directory connection.
# Run from the project root: .\verify-ad-configuration.ps1

$ErrorActionPreference = "Stop"
$ProjectPath = $PSScriptRoot
$OriginalLocation = Get-Location

$Steps = @(
    @{ Name = "Clear cached Laravel configuration"; Arguments = @("artisan", "config:clear") },
    @{ Name = "Test LDAP bind"; Arguments = @("artisan", "ldap:test") },
    @{ Name = "Test AD search without importing users"; Arguments = @("artisan", "ldap:sync", "--dry-run") },
    @{ Name = "Rebuild Laravel caches"; Arguments = @("artisan", "optimize") }
)

try {
    if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
        throw "PHP was not found in PATH. Install/configure PHP before running this script."
    }

    if (-not (Test-Path (Join-Path $ProjectPath "artisan"))) {
        throw "Laravel's artisan file was not found beside this script."
    }

    Set-Location -Path $ProjectPath

    foreach ($Step in $Steps) {
        Write-Host "`n=== $($Step.Name) ===" -ForegroundColor Cyan
        $Arguments = $Step.Arguments
        & php @Arguments

        if ($LASTEXITCODE -ne 0) {
            throw "Command 'php $($Arguments -join ' ')' failed with exit code $LASTEXITCODE. Remaining checks were not run."
        }
    }

    Write-Host "`nAD configuration checks completed successfully." -ForegroundColor Green
} catch {
    Write-Host "`nAD configuration check failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
} finally {
    Set-Location -Path $OriginalLocation
}
