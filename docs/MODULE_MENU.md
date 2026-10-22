# Module de choix des menus

Le module Menu est autonome de l’intranet. Il permet à un gestionnaire de publier une semaine de menus, aux employés de s’inscrire avec leur compte Active Directory et de choisir un menu pour chaque jour, puis de télécharger les commandes au format Excel.

## Accès et permissions

- Les employés doivent ouvrir une session avec Active Directory et utiliser le lien de la campagne.
- Le nom provient du compte Active Directory. L’employé saisit son département; les départements déjà connus lui sont suggérés.
- Seuls les super-administrateurs et les utilisateurs auxquels le rôle **Gestionnaire Menu** a été attribué peuvent gérer les campagnes et exporter les commandes.
- Le rôle et la permission `menu.manage` sont créés par la migration du module. L’attribution des rôles se fait dans l’administration centrale.

## Utilisation

1. Tout utilisateur AD ouvre `/menu` pour voir les semaines actuellement ouvertes et accéder à son formulaire d’inscription.
2. Le gestionnaire ouvre `/menu/gestion`, sélectionne une semaine ISO et renseigne les menus du lundi au vendredi. Les cinq dates sont générées automatiquement et le titre est créé à partir de la période sélectionnée.
3. Le gestionnaire peut aussi partager le lien d’une campagne. Chaque employé ne voit et ne modifie que sa propre inscription.
4. L’employé renseigne son département et choisit éventuellement un menu pour chacun des jours proposés. Un jour sans choix reste sans commande.
5. Le gestionnaire peut clôturer la campagne. Il peut télécharger le classeur Excel tant que la campagne n’est pas en brouillon.

Le classeur contient une feuille par jour ouvré, nommée avec le jour et sa date. Chaque feuille suit le modèle de la liste SOGA : inscrits regroupés par département, plats choisis dans la colonne « Observations », couleurs distinctes pour chaque menu, nombre total d’inscrits et de plats, puis deux repas imprévus (un par menu). Le dessert n’est pas inclus dans le classeur.

## Déploiement

Le déploiement doit exécuter les migrations Laravel, notamment celles créant les campagnes, menus, inscriptions, choix et permissions du module :

```powershell
php artisan migrate --force
```

PhpSpreadsheet, requis pour générer le classeur, est déjà inclus dans les dépendances Composer du projet.
