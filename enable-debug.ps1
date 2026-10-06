# Enable Debug Mode for Laravel
# This will show detailed error messages instead of generic 500 errors

Write-Host "=== Enabling Debug Mode ===" -ForegroundColor Green

if (Test-Path ".env") {
    # Read current .env content
    $envContent = Get-Content ".env"
    
    # Update APP_DEBUG to true
    $newContent = @()
    $debugFound = $false
    
    foreach ($line in $envContent) {
        if ($line -match "^APP_DEBUG=") {
            $newContent += "APP_DEBUG=true"
            $debugFound = $true
            Write-Host "Updated APP_DEBUG=true" -ForegroundColor Yellow
        } else {
            $newContent += $line
        }
    }
    
    # If APP_DEBUG was not found, add it
    if (-not $debugFound) {
        $newContent += "APP_DEBUG=true"
        Write-Host "Added APP_DEBUG=true" -ForegroundColor Yellow
    }
    
    # Write back to .env
    $newContent | Out-File ".env" -Encoding UTF8
    
    # Clear config cache to apply changes
    Write-Host "Clearing config cache..." -ForegroundColor Cyan
    try {
        php artisan config:clear
        Write-Host "[OK] Config cache cleared" -ForegroundColor Green
    } catch {
        Write-Host "[WARNING] Could not clear config cache" -ForegroundColor Yellow
    }
    
    Write-Host "`n[IMPORTANT] Debug mode is now enabled!" -ForegroundColor Red
    Write-Host "You should now see detailed error messages instead of generic 500 errors." -ForegroundColor Yellow
    Write-Host "Remember to run disable-debug.ps1 after fixing the issues!" -ForegroundColor Yellow
    
} else {
    Write-Host "[ERROR] .env file not found!" -ForegroundColor Red
}