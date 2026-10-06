# Disable Debug Mode for Laravel
# This restores production security by hiding detailed error messages

Write-Host "=== Disabling Debug Mode ===" -ForegroundColor Green

if (Test-Path ".env") {
    # Read current .env content
    $envContent = Get-Content ".env"
    
    # Update APP_DEBUG to false
    $newContent = @()
    
    foreach ($line in $envContent) {
        if ($line -match "^APP_DEBUG=") {
            $newContent += "APP_DEBUG=false"
            Write-Host "Updated APP_DEBUG=false" -ForegroundColor Yellow
        } else {
            $newContent += $line
        }
    }
    
    # Write back to .env
    $newContent | Out-File ".env" -Encoding UTF8
    
    # Clear config cache to apply changes
    Write-Host "Clearing config cache..." -ForegroundColor Cyan
    try {
        php artisan config:clear
        php artisan config:cache
        Write-Host "[OK] Config updated and cached" -ForegroundColor Green
    } catch {
        Write-Host "[WARNING] Could not update config cache" -ForegroundColor Yellow
    }
    
    Write-Host "`n[OK] Debug mode disabled - production security restored" -ForegroundColor Green
    
} else {
    Write-Host "[ERROR] .env file not found!" -ForegroundColor Red
}