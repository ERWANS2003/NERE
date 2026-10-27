# HTTPS de l'intranet (IIS, Windows Server)

Adresse de l'intranet : **https://192.168.10.206**

Certificat auto-signé créé sur le serveur le 27/10/2026 :
- couvre l'IP `192.168.10.206` et les noms `OUA-NM-INTRANET.neremining.com` et `OUA-NM-INTRANET` ;
- empreinte `C044B43D23054999374BAF109CBB1678AFC21EFF`, valable jusqu'au **26/10/2028** ;
- certificat public : `scripts/https/certificats/intranet-nere-mining-ip.cer` (sans clé privée).

Tant qu'un poste n'a pas approuvé ce certificat (étape 2), le navigateur affiche
« Votre connexion n'est pas privée ».

## 1. État du serveur

`OUA-NM-INTRANET` (192.168.10.206), Windows Server 2016, site IIS `ITSM Neré Mining`,
application `C:\inetpub\wwwroot\itsm-nere-mining` :

- port 443 lié au certificat ci-dessus ;
- toute adresse (HTTP, ou un nom au lieu de l'IP) redirige en 301 vers `https://192.168.10.206/`
  (règle `NERE - Forcer HTTPS` dans `applicationHost.config`, non écrasée par un `git pull`) ;
- certificats clients ignorés (ils provoquaient une renégociation TLS refusée par les navigateurs) ;
- TLS 1.0 et 1.1 désactivés, TLS 1.2 seul ; en-tête HSTS ; version PHP masquée ;
- `.env` : `APP_ENV=production`, `APP_URL=https://192.168.10.206`, `SESSION_SECURE_COOKIE=true`.

Sauvegardes (restauration en administrateur sur le serveur) :

| Étape | IIS | Autre |
| --- | --- | --- |
| Passage en HTTPS | `appcmd restore backup avant-https-20261027-111159` | `.env.avant-https-20261027-111159` |
| Durcissement TLS | `appcmd restore backup avant-durcissement-20261027-112421` | `C:\Install\sauvegardes-durcissement-20261027-112421\` (registre TLS, `php.ini`) |
| Certificat IP | `appcmd restore backup avant-cert-ip-20261027-113820` | `.env.avant-cert-ip-20261027-113820` |

`appcmd` se trouve dans `C:\Windows\System32\inetsrv\`.

## 2. Faire approuver le certificat par les postes

Diffuser uniquement le `.cer`, jamais une clé privée.

### Tous les PC du domaine (GPO)

Par un **administrateur du domaine `neremining.com`**, sur un contrôleur de domaine
(`10.226.70.31` / `10.226.70.32`) ou un poste avec les outils RSAT, dans PowerShell en
administrateur, à la racine d'une copie du dépôt :

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\https\Deployer-CertificatGPO.ps1 -CerPath .\scripts\https\certificats\intranet-nere-mining-ip.cer
```

Le script crée la GPO `NERE - Certificat intranet HTTPS`, y place le certificat dans
« Autorités de certification racines de confiance » et la lie à la racine du domaine
(ou à une OU avec `-Cible "OU=Postes,DC=neremining,DC=com"`).

Équivalent manuel dans la console GPMC : Configuration ordinateur > Stratégies >
Paramètres Windows > Paramètres de sécurité > Stratégies de clé publique >
Autorités de certification racines de confiance > Importer.

Les postes l'appliquent sous 90 minutes environ, ou tout de suite avec `gpupdate /force`.

### Poste par poste (script)

Le plus simple : copier `scripts/https/poste/Securiser-Intranet.cmd` sur le poste (clé USB,
partage, e-mail) et double-cliquer dessus, puis accepter la demande administrateur. Le
certificat est intégré au fichier. Le script l'installe, retire l'ancien certificat
(`DDFE6E85...`), configure Firefox s'il est présent et teste la connexion à l'intranet.

Avec le dépôt, dans PowerShell en administrateur :

```powershell
.\scripts\https\Installer-CertificatClient.ps1 -CerPath .\scripts\https\certificats\intranet-nere-mining-ip.cer
```

Sans le dépôt, la même chose en une ligne (en administrateur, avec le `.cer` copié sur le poste) :

```powershell
Import-Certificate -FilePath .\intranet-nere-mining-ip.cer -CertStoreLocation Cert:\LocalMachine\Root
```

### Vérification sur un poste

Doit afficher une ligne, puis redémarrer le navigateur :

```powershell
Get-ChildItem Cert:\LocalMachine\Root | Where-Object Thumbprint -eq 'C044B43D23054999374BAF109CBB1678AFC21EFF'
```

Firefox utilise son propre magasin : activer `security.enterprise_roots.enabled` (about:config)
pour qu'il reprenne les certificats approuvés par Windows.

## 3. Renouvellement (avant le 26/10/2028)

Sur le serveur, en administrateur, à la racine de l'application :

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\https\Creer-CertificatAutoSigne.ps1
```

Le script sauvegarde IIS et `.env`, crée un nouveau certificat de 2 ans, le lie au port 443,
remet la redirection vers l'IP et exporte `C:\Install\intranet-nere-mining-ip.cer`.
Redéployer ensuite ce nouveau `.cer` sur les postes (étape 2) : l'ancien ne sera plus présenté.

## 4. En cas de problème

| Symptôme | Cause probable |
| --- | --- |
| Alerte « connexion non privée » | certificat pas encore approuvé sur le poste (étape 2) |
| Déconnexion en boucle | `APP_URL` en `http` ou ancien cache : `php artisan config:cache` |
| Page inaccessible sur 443 | pare-feu réseau entre le poste et le serveur |
| Connexion coupée pendant la négociation TLS | IIS > site > Paramètres SSL > Certificats clients : **Ignorer** |
| Vieux logiciel qui n'arrive plus à se connecter | il ne gère que TLS 1.0/1.1, désactivés volontairement |

## Autres scripts de `scripts/https/`

| Script | Rôle |
| --- | --- |
| `Activer-HTTPS.ps1` | installer un certificat fourni en `.pfx` (ex. certificat d'une autorité) |
| `Desactiver-HTTPS.ps1` | retirer liaison HTTPS et redirection, restaurer un `.env` sauvegardé |
