# 🔧 Correction des Caractères Mal Affichés

## ❌ Problème
Textes affichés incorrectement sur le site:
- `CrÃ©er une demande` au lieu de `Créer une demande`
- `DÃ©partement` au lieu de `Département`
- `SÃ©lectionnez` au lieu de `Sélectionnez`

## ✅ Solution
Un **middleware** a été ajouté pour forcer l'encodage UTF-8 sur toutes les réponses HTTP.

## 🚀 Déploiement - 2 OPTIONS

### Option 1️⃣: Script Automatisé (RECOMMANDÉ)
```powershell
cd C:\inetpub\wwwroot\itsm-nere-mining
.\fix-utf8-deploy.ps1
```

**Ce script fait automatiquement:**
- ✓ Met à jour le code (git pull)
- ✓ Installe les dépendances (npm, composer)
- ✓ Construit les assets
- ✓ Lance les migrations
- ✓ Vide tous les caches
- ✓ Valide la correction UTF-8
- ✓ Affiche un résumé

**Durée:** ~2-3 minutes

### Option 2️⃣: Manuel
```powershell
cd C:\inetpub\wwwroot\itsm-nere-mining

git pull origin main
npm install
composer install
npm run build
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
composer dump-autoload -o

# Vérifier
php check-utf8.php
```

## ✅ Vérification

### Dans le navigateur
1. Aller à: `http://192.168.10.206/intranet`
2. Vérifier que les accents s'affichent correctement:
   - ✓ `Créer une demande`
   - ✓ `Département`
   - ✓ `Sûreté`

### En ligne de commande
```bash
php check-utf8.php
```

Doit afficher: `✅ All checks passed`

### Vérifier les headers HTTP
```powershell
# PowerShell
(Invoke-WebRequest -Uri "http://192.168.10.206/intranet").Headers['Content-Type']

# Doit afficher: text/html; charset=utf-8
```

## 🔍 Diagnostic

Si le problème persiste:

### 1. Diagnostic complet
```bash
php check-utf8.php
```

### 2. Vider le cache du navigateur
- Appuyer sur `Ctrl+Shift+Del`
- Cocher "Cookies et fichiers en cache"
- Cliquer "Supprimer"
- Rafraîchir la page (`F5`)

### 3. Vider les caches Laravel
```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### 4. Vérifier les logs
```bash
tail -50 storage/logs/laravel.log
```

## 🛠️ Fichiers Modifiés

| Fichier | Changement |
|---------|-----------|
| `app/Http/Middleware/EnsureUtf8Response.php` | 🆕 Nouveau middleware |
| `bootstrap/app.php` | ✏️ Middleware enregistré |
| `database/migrations/2026_10_05_130846_fix_intranet_utf8_collation.php` | 🆕 Documentation |
| `check-utf8.php` | 🆕 Script diagnostic PHP |
| `check-utf8-server.ps1` | 🆕 Script diagnostic PowerShell |
| `fix-utf8-deploy.ps1` | 🆕 Script déploiement automatisé |

## 📋 Ce qui a été changé dans le code

### Middleware (nouveau fichier)
```php
// app/Http/Middleware/EnsureUtf8Response.php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);
    
    // Ajoute charset=utf-8 à toutes les réponses
    if (!$response->headers->has('Content-Type')) {
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    } elseif (strpos($response->headers->get('Content-Type'), 'charset') === false) {
        $contentType = $response->headers->get('Content-Type');
        $response->headers->set('Content-Type', $contentType . '; charset=utf-8');
    }
    
    return $response;
}
```

### Enregistrement dans bootstrap/app.php
```php
->withMiddleware(function (Middleware $middleware): void {
    // Force UTF-8 encoding on all responses
    $middleware->append(\App\Http\Middleware\EnsureUtf8Response::class);
    // ...
})
```

## ❓ Questions Fréquentes

### Q: Pourquoi ce problème maintenant?
**A:** Le middleware n'était pas déployé sur le serveur. C'était un problème côté HTTP headers, pas de la base de données.

### Q: La base de données est-elle OK?
**A:** Oui! PostgreSQL gère UTF-8 nativement. Le problème était que le navigateur ne savait pas que la réponse était en UTF-8.

### Q: Faut-il redémarrer quelque chose?
**A:** Non, normalement pas. Mais si les problèmes persistent:
- Redémarrer l'AppPool dans IIS Manager
- Ou redémarrer PHP-FPM si utilisé

### Q: Ça va casser quelque chose?
**A:** Non! C'est juste un header HTTP supplémentaire. Aucun risque.

### Q: Je peux revenir en arrière?
**A:** Oui:
```bash
git revert 2d6498b
composer dump-autoload
php artisan config:clear
```

## 📞 Support

Pour plus d'aide:
1. Lire: `FIX-UTF8-PRODUCTION.md` (guide complet)
2. Lancer: `php check-utf8.php` (diagnostic)
3. Vérifier logs: `storage/logs/laravel.log`

---

**Version:** 1.0  
**Date:** 2026-10-05  
**Status:** ✅ Production-Ready
