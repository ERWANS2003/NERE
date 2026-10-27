# Passer l'intranet en HTTPS (IIS, Windows Server)

Certificat fourni : `intranet-cert.pfx`
- Nom couvert : `UA-NM-INTRANET.neremining.com`
- Validité : 23/10/2026 au 23/10/2027
- Auto-signé : les postes doivent l'approuver (étape 4), sinon le navigateur affiche une alerte.

Scripts : `scripts/https/` (PowerShell 5.1, à lancer **en administrateur**).

| Script | Où | Rôle |
| --- | --- | --- |
| `Activer-HTTPS.ps1` | serveur | importe le certificat, ajoute la liaison 443, ouvre le pare-feu, redirige HTTP vers HTTPS, met à jour `.env` |
| `Desactiver-HTTPS.ps1` | serveur | retour arrière complet en cas de problème |
| `Installer-CertificatClient.ps1` | poste client | fait approuver le certificat par un poste (si pas de GPO) |

## 1. Prérequis sur le serveur

- Module **IIS URL Rewrite** installé (déjà requis par le `web.config` de Laravel).
- Le nom `UA-NM-INTRANET.neremining.com` résolu vers l'IP du serveur dans le **DNS interne**
  (enregistrement A). Sans DNS, pour un test sur un poste : ajouter la ligne
  `<IP du serveur>  UA-NM-INTRANET.neremining.com` dans `C:\Windows\System32\drivers\etc\hosts`.
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
`-SiteName "ITSM-NERE-Mining"`, `-HostName "UA-NM-INTRANET.neremining.com"`,
`-AppPath "E:\web\itsm-nere-mining"`, `-SansRedirection`.

Il affiche à la fin le chemin de la sauvegarde du `.env` et du certificat public exporté
(`intranet-nere-mining.cer`, à côté du `.pfx`).

## 3. Vérifier

```powershell
Get-WebBinding -Name "ITSM-NERE-Mining"
Get-ChildItem IIS:\SslBindings
```

Puis ouvrir `https://UA-NM-INTRANET.neremining.com` depuis un poste : la page de connexion
s'affiche, `http://...` redirige vers `https://...`.

## 4. Faire approuver le certificat par les postes

Recommandé, une fois pour tout le domaine (contrôleur de domaine, Gestion des stratégies de groupe) :

1. Créer ou modifier une GPO liée à l'OU des ordinateurs.
2. Configuration ordinateur > Stratégies > Paramètres Windows > Paramètres de sécurité >
   Stratégies de clé publique > **Autorités de certification racines de confiance**.
3. Clic droit > Importer > `intranet-nere-mining.cer`.
4. Sur un poste : `gpupdate /force`, puis redémarrer le navigateur.

Poste isolé (hors domaine), en administrateur :

```powershell
.\scripts\https\Installer-CertificatClient.ps1 -CerPath "\\serveur\partage\intranet-nere-mining.cer"
```

Diffuser uniquement le `.cer`, jamais le `.pfx` (il contient la clé privée).

## 5. En cas de problème

```powershell
.\scripts\https\Desactiver-HTTPS.ps1 -EnvBackup "<chemin affiché par Activer-HTTPS.ps1>"
```

| Symptôme | Cause probable |
| --- | --- |
| Alerte « connexion non privée » | certificat pas encore approuvé sur le poste (étape 4) |
| Alerte « nom non valide » | accès par l'IP ou un autre nom que `UA-NM-INTRANET.neremining.com` |
| Déconnexion en boucle | `APP_URL` en `http` ou cache de config ancien : `php artisan config:cache` |
| Page inaccessible sur 443 | pare-feu réseau entre les postes et le serveur, ou DNS absent |

## 6. Renouvellement (avant le 23/10/2027)

Relancer `Activer-HTTPS.ps1` avec le nouveau `.pfx`, puis redistribuer le nouveau `.cer`
si le certificat reste auto-signé.
