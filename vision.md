C'est une excellente question. En réalité, **GLPI ne sert pas uniquement à l'IT**. Tu peux en faire un **portail de services d'entreprise (Enterprise Service Management - ESM)** où chaque département gère ses propres demandes.

Par exemple, chez **Néré Mining**, tu peux avoir plusieurs départements qui utilisent le même GLPI :

| Département | Types de tickets                                                           |
| ----------- | -------------------------------------------------------------------------- |
| IT          | Panne informatique, réseau, logiciels, comptes                             |
| RH          | Demande de recrutement, attestation, congés, intégration d'un employé      |
| HSE         | Déclaration d'accident, inspection sécurité, EPI, incident environnemental |
| Maintenance | Panne d'un équipement, demande d'intervention, maintenance préventive      |
| Achats      | Demande d'achat, suivi des commandes                                       |
| Logistique  | Demande de véhicule, transport, carburant                                  |
| Finance     | Demande de paiement, ouverture de budget, problème de facturation          |

Ainsi, une seule plateforme centralise toutes les demandes de l'entreprise.

---

# Architecture

```text
Employés
      │
      ▼
Portail GLPI
      │
      ▼
Choisir un service
      │
      ├── IT
      ├── RH
      ├── HSE
      ├── Maintenance
      ├── Finance
      └── Logistique
```

---

# Exemple de fonctionnement

Un employé clique sur

```text
Créer une demande
```

Puis choisit

```text
Service concerné

↓

IT
```

Le formulaire informatique apparaît.

---

Ou

```text
Service concerné

↓

HSE
```

Le formulaire HSE apparaît.

---

Ou

```text
Service concerné

↓

RH
```

Le formulaire RH apparaît.

---

# Exemple pour le service IT

Catégories

```text
Ordinateur

Internet

Email

VPN

Imprimante

SAP

Office

Téléphone
```

Le ticket est automatiquement envoyé à l'équipe IT.

---

# Exemple HSE

Catégories

```text
Accident

Presqu'accident

Inspection

Observation

Demande d'EPI

Incendie

Environnement
```

Le ticket est envoyé au responsable HSE.

---

# Exemple RH

Catégories

```text
Congé

Attestation

Recrutement

Formation

Contrat

Paie
```

Le ticket est affecté au service RH.

---

# Exemple Maintenance

Catégories

```text
Engin

Pompe

Convoyeur

Générateur

Climatisation

Électricité
```

Le ticket est envoyé au superviseur maintenance.

---

# Affectation automatique

Dans GLPI, tu peux créer des **règles d'affectation**.

Exemple :

| Si...                 | Alors...           |
| --------------------- | ------------------ |
| Service = IT          | Groupe Support IT  |
| Service = RH          | Groupe RH          |
| Service = HSE         | Groupe HSE         |
| Service = Maintenance | Groupe Maintenance |
| Service = Finance     | Groupe Finance     |

Ainsi, le bon groupe reçoit automatiquement la demande.

---

# Les groupes

```text
Support IT

↓

Jean

Ibrahim

Erwan
```

---

```text
Equipe HSE

↓

Ali

Mariam

Paul
```

---

```text
Equipe RH

↓

Awa

Fatou

Nadia
```

Chaque groupe ne voit que les tickets qui lui sont destinés, selon les profils et les droits configurés.

---

# Workflow

## Ticket IT

```text
Utilisateur

↓

Création

↓

Equipe IT

↓

Technicien

↓

Résolution

↓

Validation

↓

Fermeture
```

---

## Ticket RH

```text
Employé

↓

Demande de congé

↓

Responsable RH

↓

Validation

↓

Traitement

↓

Fermeture
```

---

## Ticket HSE

```text
Employé

↓

Déclaration d'accident

↓

Responsable HSE

↓

Enquête

↓

Actions correctives

↓

Clôture
```

---

# Tableau de bord

Chaque responsable voit uniquement les tickets de son service.

## Responsable IT

```text
Tickets IT ouverts

18

Critiques

2

En attente

5
```

---

## Responsable HSE

```text
Accidents

3

Inspections

14

Actions en retard

6
```

---

## Responsable RH

```text
Congés

18

Attestations

5

Recrutements

2
```

---

# Les droits

GLPI permet de limiter l'accès grâce aux **profils** et aux **groupes**.

| Profil          | Ce qu'il voit        |
| --------------- | -------------------- |
| Employé         | Ses propres demandes |
| Technicien IT   | Les tickets IT       |
| Responsable RH  | Les demandes RH      |
| Responsable HSE | Les incidents HSE    |
| Administrateur  | Tous les services    |

Ainsi, un technicien IT ne verra pas les dossiers RH confidentiels, et un agent RH n'accédera pas aux tickets techniques.

---

# Exemple complet chez Néré Mining

Imaginons qu'un employé ouvre le portail GLPI.

Il voit :

```text
==============================

PORTAIL DES SERVICES

1. Informatique (IT)

2. Ressources Humaines

3. HSE

4. Maintenance

5. Finance

6. Logistique

==============================
```

Il choisit **HSE**.

Puis :

```text
Déclarer un accident
```

Il remplit :

* Date
* Heure
* Lieu
* Description
* Personnes impliquées
* Gravité
* Photos

En cliquant sur **Envoyer** :

* le ticket est créé ;
* il est affecté automatiquement au groupe **HSE** ;
* une notification est envoyée au responsable HSE ;
* le suivi se fait jusqu'à la clôture.

---

## Ma recommandation

Pour une entreprise comme **Néré Mining**, je ne créerais pas un GLPI réservé à l'IT. Je mettrais en place un **portail unique de services** où chaque département (IT, HSE, RH, Maintenance, Finance, Logistique, etc.) dispose de :

* son propre catalogue de services ;
* ses catégories de demandes ;
* son groupe de traitement ;
* ses workflows de validation ;
* ses indicateurs et tableaux de bord.

Cette organisation simplifie l'expérience des employés, centralise les demandes et permet à chaque département de gérer ses activités sans mélanger les informations des autres services.
