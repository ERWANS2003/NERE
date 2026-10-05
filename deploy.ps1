# Script de déploiement pour Nere Mining ITSM
# À exécuter après un git pull

Write-Host "🚀 Déploiement Nere Mining ITSM..." -ForegroundColor Green

# 1. NPM dependencies
Write-Host "`n1️⃣  Installation des dépendances NPM..." -ForegroundColor Cyan
npm install
if ($LASTEXITCODE -ne 0) { Write-Host "❌ npm install échoué" -ForegroundColor Red; exit 1 }

# 2. Composer dependencies
Write-Host "`n2️⃣  Installation des dépendances Composer..." -ForegroundColor Cyan
composer install
if ($LASTEXITCODE -ne 0) { Write-Host "❌ composer install échoué" -ForegroundColor Red; exit 1 }

# 3. Composer autoload
Write-Host "`n3️⃣  Régénération de l'autoload Composer..." -ForegroundColor Cyan
composer dump-autoload -o
if ($LASTEXITCODE -ne 0) { Write-Host "❌ composer dump-autoload échoué" -ForegroundColor Red; exit 1 }

# 4. Build assets
Write-Host "`n4️⃣  Construction des assets Vite..." -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) { Write-Host "❌ npm run build échoué" -ForegroundColor Red; exit 1 }

# 5. Database migrations
Write-Host "`n5️⃣  Exécution des migrations..." -ForegroundColor Cyan
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { Write-Host "❌ Migrations échouées" -ForegroundColor Red; exit 1 }

# 6. Database seeders
Write-Host "`n6️⃣  Exécution des seeders..." -ForegroundColor Cyan
php artisan db:seed --class=IntranetSeeder --force
if ($LASTEXITCODE -ne 0) { Write-Host "❌ Seeders échoués" -ForegroundColor Red; exit 1 }

# 7. Verification
Write-Host "`n7️⃣  Vérification..." -ForegroundColor Cyan
php artisan route:list --path=intranet | Select-Object -First 5
php artisan test tests/Feature/Intranet/ --no-coverage --no-colors | Select-Object -Last 3

Write-Host "`n✅ Déploiement terminé avec succès!" -ForegroundColor Green
