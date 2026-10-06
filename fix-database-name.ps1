# Fix Database Name Issue
# The database name has hyphens but should have underscores

Write-Host "=== Fixing Database Name Issue ===" -ForegroundColor Green

# 1. Update .env file with correct database name
Write-Host "`n1. Updating .env Database Configuration:" -ForegroundColor Cyan
if (Test-Path ".env") {
    $envContent = Get-Content ".env"
    $newContent = @()
    
    foreach ($line in $envContent) {
        if ($line -match "^DB_DATABASE=") {
            $newContent += "DB_DATABASE=itsm_nere_mining"
            Write-Host "Fixed: DB_DATABASE=itsm_nere_mining (replaced hyphens with underscores)" -ForegroundColor Yellow
        } else {
            $newContent += $line
        }
    }
    
    $newContent | Out-File ".env" -Encoding UTF8
    Write-Host "[OK] .env file updated with correct database name" -ForegroundColor Green
} else {
    Write-Host "[ERROR] .env file not found!" -ForegroundColor Red
    exit 1
}

# 2. Create the database with correct name
Write-Host "`n2. Creating Database with Correct Name:" -ForegroundColor Cyan
try {
    # Try different methods to create the database
    Write-Host "Attempting to create database 'itsm_nere_mining'..."
    
    # Method 1: Using mysql command line
    $createDbSql = "CREATE DATABASE IF NOT EXISTS itsm_nere_mining CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    # Try with root user (no password)
    try {
        $result = mysql -u root -e $createDbSql 2>&1
        Write-Host "[OK] Database created successfully (root, no password)" -ForegroundColor Green
    } catch {
        # Try with root user and common passwords
        $commonPasswords = @("", "root", "password", "admin")
        $created = $false
        
        foreach ($pwd in $commonPasswords) {
            if ($pwd -eq "") {
                continue # already tried above
            }
            try {
                if ($pwd -eq "root" -or $pwd -eq "password" -or $pwd -eq "admin") {
                    $result = mysql -u root -p$pwd -e $createDbSql 2>&1
                    Write-Host "[OK] Database created successfully (root, password: $pwd)" -ForegroundColor Green
                    $created = $true
                    break
                }
            } catch {
                continue
            }
        }
        
        if (-not $created) {
            Write-Host "[WARNING] Could not create database automatically" -ForegroundColor Yellow
            Write-Host "Please create the database manually:" -ForegroundColor Cyan
            Write-Host "  1. Connect to MySQL as root" -ForegroundColor White
            Write-Host "  2. Run: CREATE DATABASE itsm_nere_mining CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" -ForegroundColor White
        }
    }
} catch {
    Write-Host "[WARNING] Database creation failed: $($_.Exception.Message)" -ForegroundColor Yellow
}

# 3. Clear Laravel config cache
Write-Host "`n3. Clearing Laravel Configuration Cache:" -ForegroundColor Cyan
try {
    php artisan config:clear
    Write-Host "[OK] Configuration cache cleared" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Failed to clear config cache" -ForegroundColor Red
}

# 4. Test database connection
Write-Host "`n4. Testing Database Connection:" -ForegroundColor Cyan
try {
    $dbTest = php artisan migrate:status 2>&1
    if ($dbTest -match "Migration name" -or $dbTest -match "Ran?") {
        Write-Host "[OK] Database connection successful!" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Database connection issue: $dbTest" -ForegroundColor Yellow
        Write-Host "You may need to manually create the database or check MySQL credentials" -ForegroundColor Cyan
    }
} catch {
    Write-Host "[ERROR] Database test failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 5. Run migrations if database connection works
Write-Host "`n5. Running Database Migrations:" -ForegroundColor Cyan
try {
    $migrateResult = php artisan migrate --force 2>&1
    if ($migrateResult -match "Migrating" -or $migrateResult -match "Nothing to migrate") {
        Write-Host "[OK] Migrations completed successfully" -ForegroundColor Green
        
        # Run seeders
        Write-Host "Running database seeders..."
        $seedResult = php artisan db:seed --force 2>&1
        Write-Host $seedResult
        Write-Host "[OK] Database seeded" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Migration issues: $migrateResult" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ERROR] Migration failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== Database Fix Complete ===" -ForegroundColor Green
Write-Host "Please test the website at http://192.168.10.206" -ForegroundColor Cyan
Write-Host "If you still get database errors, please manually create the database:" -ForegroundColor Yellow
Write-Host "  mysql -u root -p" -ForegroundColor White
Write-Host "  CREATE DATABASE itsm_nere_mining CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" -ForegroundColor White