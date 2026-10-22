# Administration centrale de l’intranet

Les écrans d’administration centrale sont réservés aux comptes super-administrateurs. Le hub `/admin/modules` permet d’activer ou de désactiver les cinq modules métiers sans supprimer leurs données.

## Gestion par module

- **Congés** (`/admin/time-off`) : créer et modifier les politiques, les désactiver sans effacer leur historique et ajuster les soldes annuels. Les jours consommés restent gérés par les approbations; le solde restant comprend l’allocation annuelle et les jours reportés. Les demandes se traitent depuis le workflow existant.
- **Ressources** (`/admin/resources`) : gérer catégories et métadonnées, publier ou désactiver une ressource. Les catégories ne peuvent pas être supprimées tant qu’elles contiennent des ressources. L’ajout de fichiers réutilise l’upload privé existant.
- **Base de connaissances** : le hub ouvre l’interface existante de gestion des articles et de leur publication.
- **Formulaires et services** : le hub ouvre la gestion existante des formulaires et soumissions; les services départementaux restent gérés dans les écrans de chaque département.
- **Tickets** (`/admin/tickets`) : vue globale filtrable. L’ouverture d’un ticket utilise son écran existant, conservant ainsi le workflow d’assignation, de statut, d’activités et de SLA.

Les interrupteurs agissent aussi côté serveur : une route métier d’un module désactivé renvoie une réponse 404. Les écrans d’administration restent disponibles afin de réactiver le module et ses données.
