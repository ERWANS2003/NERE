# Passer l'intranet en HTTPS (IIS, Windows Server)

Certificat fourni : `intranet-cert.pfx`
- Nom couvert : `OUA-NM-INTRANET.neremining.com`
- Validité : 23/10/2026 au 23/10/2027
- Auto-signé : les postes doivent l'approuver (étape 4), sinon le navigateur affiche une alerte.

## État du serveur (27/10/2026)

Appliqué sur `OUA-NM-INTRANET` (192.168.10.206), site IIS `ITSM Neré Mining`, application
`C:\inetpub\wwwroot\itsm-nere-mining` :

- certificat déjà importé (magasin `WebHosting`) et lié à `*:443` ;
- certificats clients ignorés (ils provoquaient une renégociation TLS refusée par les navigateurs) ;
- redirection HTTP vers HTTPS (règle `NERE - Forcer HTTPS` dans `applicationHost.config`) ;
- `.env` : `APP_URL=https://OUA-NM-INTRANET.neremining.com`, `SESSION_SECURE_COOKIE=true` ;
- sauvegardes : `appcmd restore backup avant-https-20261027-111159` et `.env.avant-https-20261027-111159`.

Durcissement appliqué le 27/10/2026 (après redémarrage) : TLS 1.0 et 1.1 désactivés, HSTS, version PHP masquée.
Sauvegardes : `C:Installsauvegardes-durcissement-20261027-112421` et `appcmd restore backup avant-durcissement-20261027-112421`.

Reste à faire : approuver le certificat sur les postes par GPO (étape 4).

Scripts : `scripts/https/` (PowerShell 5.1, à lancer **en administrateur**).

| Script | Où | Rôle |
| --- | --- | --- |
| `Activer-HTTPS.ps1` | serveur | importe le certificat, ajoute la liaison 443, ouvre le pare-feu, redirige HTTP vers HTTPS, met à jour `.env` |
| `Desactiver-HTTPS.ps1` | serveur | retour arrière complet en cas de problème |
| `Deployer-CertificatGPO.ps1` | contrôleur de domaine | fait approuver le certificat par tous les PC du domaine (GPO) |
| `Installer-CertificatClient.ps1` | poste client | fait approuver le certificat par un poste hors domaine |

## 1. Prérequis sur le serveur

- Module **IIS URL Rewrite** installé (déjà requis par le `web.config` de Laravel).
- Le nom `OUA-NM-INTRANET.neremining.com` résolu vers l'IP du serveur dans le **DNS interne**
  (enregistrement A). Sans DNS, pour un test sur un poste : ajouter la ligne
  `<IP du serveur>  OUA-NM-INTRANET.neremining.com` dans `C:\Windows\System32\drivers\etc\hosts`.
- Copier `intranet-cert.pfx` sur le serveur hors du dossier du site, par exemple `C:\Install\`.
  Ne jamais le placer dans le dépôt Git ni dans `public\`.

## 2. Activer le HTTPS

Dans une console PowerShell **Exécuter en tant qu'administrateur**, à la racine de l'application
(après `git pull`) :

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\https\Activer-HTTPS.ps1 -PfxPath "C:\Install\intranet-cert.pfx"
```

Le script demande le mot de passe du `.pfx`. Paramètres facultatifs :
`-SiteName` (par défaut, détecté automatiquement : le site dont le dossier contient `itsm-nere-mining`), `-HostName "OUA-NM-INTRANET.neremining.com"`,
`-AppPath "E:\web\itsm-nere-mining"`, `-SansRedirection`.

Il affiche à la fin le chemin de la sauvegarde du `.env` et du certificat public exporté
(`intranet-nere-mining.cer`, à côté du `.pfx`).

## 3. Vérifier

```powershell
Get-WebBinding | Format-Table protocol, bindingInformation
Get-ChildItem IIS:\SslBindings
```

Puis ouvrir `https://OUA-NM-INTRANET.neremining.com` depuis un poste : la page de connexion
s'affiche, `http://...` redirige vers `https://...`.

## 4. Faire approuver le certificat par les postes

Le certificat public est dans le dépôt : `scripts/https/certificats/intranet-nere-mining.cer`
(empreinte `DDFE6E8545A4CA68510CB68D248E6D5679611831`, valable jusqu'au 23/10/2027).

### Tous les PC du domaine (recommandé)

Par un **administrateur du domaine `neremining.com`**, sur un contrôleur de domaine
(serveurs DNS `10.226.70.31` / `10.226.70.32`) ou un poste avec les outils RSAT,
dans PowerShell en administrateur, à la racine d'une copie du dépôt :

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\https\Deployer-CertificatGPO.ps1 -CerPath .\scripts\https\certificats\intranet-nere-mining.cer
```

Le script crée la GPO `NERE - Certificat intranet HTTPS`, y place le certificat dans
« Autorités de certification racines de confiance » et la lie à la racine du domaine.
Pour ne cibler qu'une OU : `-Cible "OU=Postes,DC=neremining,DC=com"`.

Équivalent manuel dans la console GPMC : Configuration ordinateur > Stratégies >
Paramètres Windows > Paramètres de sécurité > Stratégies de clé publique >
Autorités de certification racines de confiance > Importer.

Les postes l'appliquent au prochain rafraîchissement (environ 90 min) ou tout de suite avec :

```powershell
gpupdate /force
```

Vérification sur un poste (doit afficher une ligne) :

```powershell
Get-ChildItem Cert:\LocalMachine\Root | Where-Object Thumbprint -eq 'DDFE6E8545A4CA68510CB68D248E6D5679611831'
```

### Poste hors domaine

En administrateur :

```powershell
.\scripts\https\Installer-CertificatClient.ps1 -CerPath .\scripts\https\certificats\intranet-nere-mining.cer
```

Diffuser uniquement le `.cer`, jamais le `.pfx` (il contient la clé privée).

## 5. En cas de problème

```powershell
.\scripts\https\Desactiver-HTTPS.ps1 -EnvBackup "<chemin affiché par Activer-HTTPS.ps1>"
```

| Symptôme | Cause probable |
| --- | --- |
| Alerte « connexion non privée » | certificat pas encore approuvé sur le poste (étape 4) |
| Alerte « nom non valide » | accès par l'IP ou un autre nom que `OUA-NM-INTRANET.neremining.com` |
| Déconnexion en boucle | `APP_URL` en `http` ou cache de config ancien : `php artisan config:cache` |
| Page inaccessible sur 443 | pare-feu réseau entre les postes et le serveur, ou DNS absent |
| Connexion coupée pendant la négociation TLS | site réglé sur « Accepter les certificats clients » : IIS > site > Paramètres SSL > Certificats clients : **Ignorer** (le script le fait) |

## 6. Renouvellement (avant le 23/10/2027)

Relancer `Activer-HTTPS.ps1` avec le nouveau `.pfx`, puis redistribuer le nouveau `.cer`
si le certificat reste auto-signé.
