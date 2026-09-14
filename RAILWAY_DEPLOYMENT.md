# 🚀 Déploiement ITSM Néré Mining sur Railway

## ⚡ Quick Start (5 minutes)

### 1. Créer un projet Railway
```bash
# Via CLI
npm i -g @railway/cli
railway login
railway init

# Ou via https://railway.app - cliquez sur "New Project"
```

### 2. Configurer la base de données
```bash
railway add
# Sélectionner PostgreSQL
```

### 3. Ajouter les secrets/variables d'environnement
Via le dashboard Railway ou via CLI :

```bash
railway variables
```

Variables minimales requises :
```
APP_NAME=ITSM Néré Mining
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-railway-domain>.up.railway.app
APP_KEY=base64:<generate-with-php-artisan-key-generate>

DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

MAIL_MAILER=log
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

### 4. Connecter le repository GitHub
1. Allez sur https://railway.app/dashboard
2. Connectez votre GitHub
3. Sélectionnez le repository `NERE`
4. Railway détecte le `Procfile` automatiquement

### 5. Déployer
```bash
# Automatique à chaque push sur main
git push origin main

# Ou déploiement manuel
railway up
```

## 📊 Vérifier le déploiement

```bash
# Voir les logs en temps réel
railway logs

# Vérifier la connexion DB
railway run php artisan tinker
>>> DB::connection()->getPdo()

# Voir tous les utilisateurs
railway run php artisan tinker
>>> App\Models\User::count()
```

## 🔗 Accéder à l'application

- URL générée automatiquement par Railway
- Format: `https://<project-name>-production.up.railway.app`
- Admin: voir `.env` pour les credentials de test

## 💡 Tips

- **Logs**: `railway logs` pour dépanner
- **SSH**: `railway shell` pour accès à la machine
- **Database**: `railway connect postgres` pour SQL shell
- **Scale**: Augmenter les ressources via le dashboard

## ✅ Checklist de production

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] APP_KEY généré
- [ ] MAIL_MAILER configuré
- [ ] HTTPS activé (Railway par défaut)
- [ ] Migrations exécutées
- [ ] Assets compilés (`npm run build`)
- [ ] Sessions stockées en DB
- [ ] Cache en DB
- [ ] Logs archivés

## 📞 Support

- Docs: https://docs.railway.app
- CLI Help: `railway help`
- Discord: https://discord.gg/railway

---

Pour déploiement manuel complet, voir `DEPLOYMENT.md`
