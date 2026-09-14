# Déploiement ITSM Néré Mining sur Railway

## Prérequis

- Railway CLI ou compte Railway.app
- PostgreSQL 13+
- Node.js 18+
- PHP 8.3+

## Variables d'environnement requises sur Railway

```
APP_NAME=ITSM Néré Mining
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-domain>.up.railway.app
APP_KEY=base64:<generate-with-artisan-key-generate>

DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
BROADCAST_CONNECTION=log

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=<your-username>
MAIL_PASSWORD=<your-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="ITSM Néré Mining"

LOG_CHANNEL=stack
LOG_LEVEL=info
```

## Instructions de déploiement

### Via Railway CLI

1. **Installer Railway CLI**
   ```bash
   npm install -g @railway/cli
   ```

2. **Se connecter à Railway**
   ```bash
   railway login
   ```

3. **Créer un nouveau projet**
   ```bash
   railway init
   ```

4. **Ajouter une base de données PostgreSQL**
   ```bash
   railway add
   # Sélectionner PostgreSQL
   ```

5. **Configurer les variables d'environnement**
   ```bash
   railway variables
   # Ajouter toutes les variables listées ci-dessus
   ```

6. **Déployer**
   ```bash
   railway up
   ```

### Via Railway Web Dashboard

1. Allez sur https://railway.app
2. Créez un nouveau projet
3. Connectez votre repository GitHub
4. Railway détectera le `Procfile` automatiquement
5. Configurez PostgreSQL comme service
6. Ajoutez les variables d'environnement via le dashboard
7. Le déploiement commence automatiquement à chaque push sur `main`

## Migrations

Les migrations s'exécutent automatiquement lors du déploiement grâce à la section `release` du `Procfile`.

Pour exécuter manuellement une migration après déploiement :

```bash
railway run php artisan migrate --force
```

## Vérification après déploiement

1. **Vérifier les logs**
   ```bash
   railway logs
   ```

2. **Accéder à l'application**
   - URL: https://<your-domain>.up.railway.app

3. **Tester la base de données**
   ```bash
   railway run php artisan tinker
   >>> User::count()
   ```

4. **Vérifier les assets**
   - S'assurer que les assets CSS/JS se chargent correctement

## Dépannage

### Migration échouée
```bash
railway run php artisan migrate:rollback --force
railway run php artisan migrate --force
```

### Cache invalide
```bash
railway run php artisan config:clear
railway run php artisan view:clear
railway run php artisan cache:clear
```

### Problèmes de permissions
```bash
railway run php artisan storage:link
railway run chmod -R 775 storage bootstrap/cache
```

## Monitoring

- **Logs**: `railway logs` ou via le dashboard
- **Métriques**: CPU, RAM via le dashboard Railway
- **Erreurs**: Vérifier les logs de l'application
- **Performance**: Utiliser NewRelic/DataDog (optionnel)

## Rollback

Pour revenir à une version précédente :

```bash
# Voir l'historique des déploiements
railway logs --deployment

# Redéployer une version précédente
git revert <commit-hash>
git push origin main
```

## Sauvegarde de la base de données

Railway inclut les sauvegardes PostgreSQL. Pour exporter manuellement :

```bash
railway run pg_dump $DATABASE_URL > backup-$(date +%Y%m%d).sql
```

## Coûts et limites

- **PostgreSQL**: 5 GB stockage gratuit, puis $2/GB
- **Web Dyno**: $5/mois (1 GB RAM)
- **Plan Hobby**: Gratuit jusqu'à 100 heures/mois
- Consultez https://railway.app/pricing

## Support

- Documentation: https://docs.railway.app
- Statut: https://status.railway.app
- Communauté Discord: https://discord.gg/railway
