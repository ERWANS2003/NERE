# 🔧 Fixing UTF-8 Encoding Issues on Production Server

## Problem
Text displays incorrectly: `CrÃ©er` instead of `Créer`, `DÃ©partement` instead of `Département`

## Root Cause
HTTP responses were missing `charset=utf-8` in the `Content-Type` header.

## Solution Implemented
- ✅ New middleware: `app/Http/Middleware/EnsureUtf8Response.php`
- ✅ Registered in `bootstrap/app.php` for all web requests
- ✅ Migration for documentation: `database/migrations/2026_10_05_130846_fix_intranet_utf8_collation.php`

## Step-by-Step Fix on Production Server

### 1. SSH into the server
```powershell
cd C:\inetpub\wwwroot\itsm-nere-mining
```

### 2. Pull the latest code
```bash
git pull origin main
```

### 3. Run the automated deployment script
```powershell
.\deploy.ps1
```

This will:
- ✓ Install npm dependencies
- ✓ Install composer dependencies
- ✓ Regenerate composer autoload
- ✓ Build assets with Vite
- ✓ Run migrations
- ✓ Run seeders
- ✓ Cache routes and config

### 4. Verify the fix

#### Option A: Run PHP diagnostic
```bash
php check-utf8.php
```

Expected output:
```
✅ Passed checks (6+):
   • Middleware exists
   • Migration exists
   • French text in views OK
   • PHP encoding OK
   • Middleware class loads OK
   • File encoding OK
```

#### Option B: Run PowerShell diagnostic (from your workstation)
```powershell
.\check-utf8-server.ps1
```

#### Option C: Manual verification
Open browser and go to `http://192.168.10.206/intranet`
- ✅ Should see: `Créer une demande` (correct)
- ❌ Should NOT see: `CrÃ©er une demande` (broken)

### 5. If issues persist

#### Quick Fixes
```bash
# Clear all caches
php artisan cache:clear

# Recache config
php artisan config:cache

# Recache routes
php artisan route:cache

# Regenerate autoload
composer dump-autoload -o

# Restart PHP-FPM (if using PHP-FPM)
# On Windows: restart the AppPool in IIS Manager
```

#### Check PHP Configuration
Edit `C:\php\php.ini` and verify:
```ini
default_charset = "UTF-8"
```

#### Check IIS/Web Server Headers
In IIS Manager:
1. Go to the Nere Mining site
2. HTTP Response Headers
3. Verify no conflicting charset settings

#### Check .htaccess (if using Apache)
Make sure `AddDefaultCharset UTF-8` is NOT overriding the header.

### 6. Verify from multiple angles

#### Test 1: HTTP Headers
```powershell
(Invoke-WebRequest -Uri "http://192.168.10.206/intranet").Headers['Content-Type']
# Should contain: text/html; charset=utf-8
```

#### Test 2: Page Source (Browser)
Press F12 → Sources → check HTML meta charset tag
Should have: `<meta charset="utf-8">`

#### Test 3: Database
```sql
-- Connect to database and verify stored data
SELECT name FROM intranet_departments LIMIT 1;
-- Should display: Administration de la mine, Ressources humaines, etc.
```

## Files Changed in This Fix

| File | Change |
|------|--------|
| `app/Http/Middleware/EnsureUtf8Response.php` | NEW - Middleware that forces charset=utf-8 |
| `bootstrap/app.php` | UPDATED - Register middleware for all web requests |
| `database/migrations/2026_10_05_130846_fix_intranet_utf8_collation.php` | NEW - Documentation |
| `check-utf8.php` | NEW - Diagnostic script (CLI) |
| `check-utf8-server.ps1` | NEW - Diagnostic script (PowerShell) |

## How It Works

### Before (Broken)
```
HTTP/1.1 200 OK
Content-Type: text/html
Content-Length: 45235

<body>...CrÃ©er une demande...</body>
```

Browser doesn't know it's UTF-8 → interprets as wrong encoding → displays broken text.

### After (Fixed)
```
HTTP/1.1 200 OK
Content-Type: text/html; charset=utf-8
Content-Length: 45235

<body>...Créer une demande...</body>
```

Browser sees `charset=utf-8` → interprets correctly → displays `Créer` properly.

## Technical Details

### Middleware Implementation
```php
// app/Http/Middleware/EnsureUtf8Response.php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);
    
    // Force charset=utf-8 on all responses
    if (!$response->headers->has('Content-Type')) {
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    } elseif (strpos($response->headers->get('Content-Type'), 'charset') === false) {
        $contentType = $response->headers->get('Content-Type');
        $response->headers->set('Content-Type', $contentType . '; charset=utf-8');
    }
    
    return $response;
}
```

### PostgreSQL
PostgreSQL handles UTF-8 natively, so database migrations are not needed.

### Blade Templates
All French text in `.blade.php` files is correctly encoded as UTF-8:
- ✅ `Créer une demande`
- ✅ `Département`
- ✅ `Sûreté`
- ✅ `Approvisionnement`
- ✅ `Sélectionnez`

## Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Still see `CrÃ©er` | Clear browser cache (Ctrl+Shift+Del), then refresh |
| Middleware not loaded | Run `composer dump-autoload -o` |
| Headers not set | Check IIS is not overriding Content-Type |
| Database shows wrong encoding | Database is fine, issue is HTTP headers |
| Partial fix (some pages OK, some not) | Check if route caching is stale: `php artisan route:cache` |

## Rollback (if needed)
```bash
git revert 2d6498b  # Revert UTF-8 fix commit
git pull
composer dump-autoload
php artisan migrate:rollback
```

## Support

Run diagnostics:
```bash
php check-utf8.php          # PHP diagnostic
.\check-utf8-server.ps1     # PowerShell diagnostic
php artisan tinker          # Interactive debugging
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

---

**Last Updated:** 2026-10-05  
**Status:** Production Ready ✅  
**Commits:** 2d6498b (UTF-8 middleware), d688182 (Diagnostic scripts)
