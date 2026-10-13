# Demande d'informations — Intégration Active Directory (Intranet NÉRÉ Mining)

**Objet :** connecter l'intranet NÉRÉ Mining à l'Active Directory de l'entreprise
(authentification des employés et/ou synchronisation de l'annuaire).

**Destinataire :** Direction des Systèmes d'Information (DSI) / Administrateur Active Directory

**Serveur applicatif concerné :** `192.168.10.206` (serveur web IIS hébergeant l'intranet)

---

## 1. Contrôleur de domaine (serveur AD)

| Information demandée | Exemple | Réponse DSI |
|---|---|---|
| Nom DNS du contrôleur de domaine (FQDN) | `dc01.nere-mining.local` | |
| Adresse IP du contrôleur de domaine | `192.168.10.x` | |
| Contrôleur de domaine secondaire (si redondance) | `dc02.nere-mining.local` | |
| Nom de domaine Active Directory complet | `nere-mining.local` | |
| Nom NetBIOS du domaine (nom court) | `NEREMINING` | |

---

## 2. Protocole et port de connexion

| Information demandée | Exemple | Réponse DSI |
|---|---|---|
| Protocole autorisé | LDAP (389) ou **LDAPS chiffré (636)** | |
| Port à utiliser | `636` recommandé | |
| LDAPS est-il activé sur le DC ? (certificat SSL) | Oui / Non | |
| Si LDAPS : certificat racine (CA) à fournir pour le serveur | fichier `.cer` | |

> ⚠️ Pour l'authentification par mot de passe, **LDAPS (636) est fortement
> recommandé** : sans lui, les identifiants transitent en clair sur le réseau.

---

## 3. Structure de l'annuaire (DN / OU)

| Information demandée | Exemple | Réponse DSI |
|---|---|---|
| Base DN (racine du domaine) | `DC=nere-mining,DC=local` | |
| OU contenant les comptes employés | `OU=Employes,DC=nere-mining,DC=local` | |
| Faut-il inclure les sous-OU (recherche récursive) ? | Oui / Non | |
| OU(s) à exclure (comptes de service, désactivés…) | `OU=Comptes systeme` | |

---

## 4. Compte de service (lecture de l'annuaire)

Un compte dédié est nécessaire pour que l'application interroge l'AD.

| Information demandée | Exemple | Réponse DSI |
|---|---|---|
| Identifiant du compte de service (UPN ou DN) | `svc_intranet@nere-mining.local` | |
| Mot de passe du compte de service | (à transmettre de façon sécurisée) | |
| Droits : **lecture seule** sur l'annuaire | Confirmé | |
| Mot de passe à expiration désactivée (compte de service) | Oui / Non | |

> Ce compte n'a besoin que de **lire** l'annuaire (pas de droits d'admin).

---

## 5. Attributs utilisateurs disponibles

Merci de confirmer quels attributs AD sont renseignés et exploitables, et sous
quel nom exact :

| Donnée intranet | Attribut AD attendu | Renseigné ? (Oui/Non) |
|---|---|---|
| Identifiant de connexion | `sAMAccountName` | |
| Adresse e-mail | `mail` | |
| Prénom | `givenName` | |
| Nom | `sn` | |
| Nom complet affiché | `displayName` | |
| Département / service | `department` | |
| Poste / fonction | `title` | |
| Téléphone fixe | `telephoneNumber` | |
| Téléphone mobile | `mobile` | |
| Matricule RH (si présent) | `employeeID` ou autre | |
| Manager / responsable | `manager` | |

> Si les noms de départements dans l'AD diffèrent de ceux de l'intranet
> (ex. « IT » vs « Informatique »), merci de fournir la correspondance.

---

## 6. Accès réseau (pare-feu)

| Information demandée | Réponse DSI |
|---|---|
| Le serveur `192.168.10.206` peut-il joindre le DC sur le port 389/636 ? | |
| Une règle de pare-feu doit-elle être ouverte ? | |

---

## 7. Comportement souhaité (à valider ensemble)

À décider conjointement DSI / métier :

- [ ] **Authentification AD** : les employés se connectent avec leur login Windows
      (plus de mot de passe géré dans l'intranet) ?
- [ ] **Synchronisation de l'annuaire** : import/mise à jour automatique des
      employés depuis l'AD (nom, e-mail, service, poste, téléphone) ?
- [ ] Les deux ?
- [ ] Création automatique du compte intranet à la première connexion AD ?
- [ ] Fréquence de synchronisation souhaitée (temps réel à la connexion /
      toutes les heures / quotidienne) ?
- [ ] Que faire des comptes AD désactivés (bloquer l'accès à l'intranet) ?

---

## 8. Compte de test

Pour valider l'intégration sans impacter la production :

| Information demandée | Réponse DSI |
|---|---|
| Un compte AD de test (login + mot de passe) | |
| Appartenant à l'OU des employés | |

---

### Rappel technique (côté intranet, géré par nous)

- Extension PHP `ldap` à activer sur le serveur web (vérification de notre côté).
- Librairie `directorytree/ldaprecord-laravel` à installer (standard du marché).
- Les identifiants AD seront stockés dans le fichier `.env` du serveur
  (non versionné, non partagé).

---

*Document généré pour la demande d'accès AD — Intranet NÉRÉ Mining.*
