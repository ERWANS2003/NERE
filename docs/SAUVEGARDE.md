# Sauvegarde et restauration de l'intranet

## Sauvegarde automatique

Chaque nuit à 2 h, la tâche planifiée **Backup ITSM Néré Mining** (compte SYSTEM)
lance `scripts\sauvegarde\Sauvegarder-Intranet.ps1`. Le script produit
`C:\Backups\ITSM\intranet_AAAAMMJJ_HHmmss.zip`, qui contient :

| Élément | Contenu |
| --- | --- |
| `base.dump` | base PostgreSQL `itsm_nere_mining` (format `pg_dump -F c`) |
| `storage-app\` | fichiers déposés : pièces jointes des tickets et des annonces |
| `env.txt` | copie du `.env` (avec `APP_KEY` et les mots de passe) |

- Les archives de plus de 14 jours sont supprimées.
- Le dossier `C:\Backups\ITSM` est réservé aux administrateurs et à SYSTEM, car il contient le `.env`.
- L'état de la dernière sauvegarde s'affiche dans **Admin > Santé du serveur**. Il passe en rouge après 36 h sans sauvegarde réussie.

Pour lancer une sauvegarde à la main, dans PowerShell en administrateur sur le serveur :

```powershell
powershell -ExecutionPolicy Bypass -File C:\inetpub\wwwroot\itsm-nere-mining\scripts\sauvegarde\Sauvegarder-Intranet.ps1
```

## Copie hors du serveur

Par défaut, les sauvegardes restent sur le disque du serveur : si ce disque est perdu,
elles le sont aussi. Le lecteur `E:` ne suffit pas, car c'est une partition du même
disque virtuel que `C:`.

Pour copier chaque archive sur un partage réseau, ajoutez `-Copie` aux arguments de la
tâche planifiée. Le compte ordinateur du serveur (`NEREMINING\OUA-NM-INTRANET$`, utilisé
par SYSTEM sur le réseau) doit avoir le droit d'écriture sur ce partage.

```
-NoProfile -ExecutionPolicy Bypass -File "C:\inetpub\wwwroot\itsm-nere-mining\scripts\sauvegarde\Sauvegarder-Intranet.ps1" -Copie "\\serveur\sauvegardes\intranet"
```

La même durée de conservation (14 jours) s'applique à la copie. La page Santé indique
si une copie est configurée.

## Restauration

Sur le serveur, en administrateur. L'opération remplace les données actuelles.

1. Extraire l'archive voulue, par exemple dans `C:\Install\restauration`.
2. Mettre le site en maintenance :
   ```powershell
   cd C:\inetpub\wwwroot\itsm-nere-mining
   C:\PHP\php.exe artisan down
   ```
3. Restaurer la base. `pg_restore` demande le mot de passe `DB_PASSWORD` du `.env` :
   ```powershell
   & 'C:\Program Files\PostgreSQL\18\bin\pg_restore.exe' -h 127.0.0.1 -U <DB_USERNAME> -d itsm_nere_mining --clean --if-exists C:\Install\restauration\base.dump
   ```
4. Remettre les fichiers déposés :
   ```powershell
   robocopy C:\Install\restauration\storage-app C:\inetpub\wwwroot\itsm-nere-mining\storage\app /E
   ```
5. Si le `.env` a été perdu, le recopier depuis `env.txt`. Sans le bon `APP_KEY`,
   les sessions sont perdues et les valeurs chiffrées deviennent illisibles.
6. Relancer le site :
   ```powershell
   C:\PHP\php.exe artisan optimize
   C:\PHP\php.exe artisan up
   ```
