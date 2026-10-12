# Configuration Active Directory pour NERE Mining ITSM

## 📋 Prérequis

Le système est prêt pour l'intégration Active Directory. Voici comment l'activer:

## 🔧 Étape 1: Activer l'extension PHP LDAP

### Sur le serveur de développement (Laragon):

1. Ouvrez le fichier `php.ini`:
   ```
   C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.ini
   ```

2. Recherchez la ligne:
   ```ini
   ;extension=ldap
   ```

3. Décommentez-la (enlevez le `;`):
   ```ini
   extension=ldap
   ```

4. Sauvegardez et redémarrez le serveur web

### Sur le serveur de production (Windows Server):

Même procédure, le chemin php.ini sera différent selon votre installation IIS/Apache.

## ⚙️ Étape 2: Configuration Active Directory

### 1. Obtenez les informations AD de NERE:

Vous aurez besoin de:
- **Adresse du serveur AD** (ex: `ad.nere-mining.com` ou `192.168.10.100`)
- **Port** (généralement `389` pour LDAP ou `636` pour LDAPS sécurisé)
- **Base DN** (ex: `DC=nere-mining,DC=com` ou `DC=nere,DC=local`)
- **Compte de service** : 
  - Username (ex: `svc_intranet@nere-mining.com`)
  - Password
- **OU des utilisateurs** (ex: `OU=Employees,DC=nere-mining,DC=com`)

### 2. Configurez le fichier `.env`:

```env
# Active Directory / LDAP Configuration
LDAP_ENABLED=true
LDAP_HOST=ldap://ad.nere-mining.com
LDAP_PORT=389
LDAP_BASE_DN="DC=nere-mining,DC=com"
LDAP_BIND_DN="CN=Service Intranet,CN=Users,DC=nere-mining,DC=com"
LDAP_BIND_PASSWORD=VotreMotDePasseSecurise
LDAP_USER_FILTER="(&(objectClass=user)(objectCategory=person))"
LDAP_USERS_OU="OU=Employees,DC=nere-mining,DC=com"
LDAP_AUTO_SYNC=true
LDAP_SYNC_SCHEDULE=daily
```

## 🧪 Étape 3: Tester la connexion

Une fois l'extension LDAP activée et configurée:

```bash
php artisan ldap:sync --test
```

Cette commande affiche:
- ✅ Si l'extension LDAP est chargée
- 📋 La configuration actuelle
- 🔗 Test de connexion au serveur AD

## 🔄 Étape 4: Synchroniser les utilisateurs

### Synchronisation manuelle:

```bash
php artisan ldap:sync
```

### Synchronisation automatique:

Le système peut synchroniser automatiquement si `LDAP_AUTO_SYNC=true`.
Ajoutez dans `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    if (config('ldap.auto_sync')) {
        $schedule->command('ldap:sync')
            ->cron(config('ldap.sync_schedule'));
    }
}
```

## 🔐 Fonctionnement de l'authentification

### Mode hybride (actuel):

1. **L'utilisateur se connecte** avec son identifiant NERE
2. **Le système vérifie d'abord AD** (si LDAP activé)
   - ✅ Si succès: création/mise à jour du compte local
   - ❌ Si échec: tentative en local
3. **Les rôles/permissions** sont gérés localement dans l'ITSM

### Avantages:

- ✅ Authentification centralisée (mot de passe Windows)
- ✅ Synchronisation automatique des employés
- ✅ Gestion fine des permissions dans l'ITSM
- ✅ Fallback local si AD indisponible

## 📞 Support

Pour toute question sur la configuration AD, contactez:
- **IT Support NERE**: support@nere-mining.com
- **Administrateur réseau**: Pour les accès AD

## ⚠️ Sécurité

- Utilisez LDAPS (port 636) en production pour chiffrer les communications
- Le compte de service doit avoir **uniquement les droits de lecture** sur l'AD
- Ne jamais committer les mots de passe dans Git (.env est dans .gitignore)

## 🎯 Prochaines étapes

Une fois LDAP configuré, vous pourrez:
1. Importer automatiquement tous les employés NERE
2. Activer le Single Sign-On (SSO)
3. Synchroniser les départements depuis AD
4. Gérer les groupes AD pour les permissions
