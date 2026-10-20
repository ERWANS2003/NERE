<?php

namespace App\Support;

use App\Models\Permission;
use Illuminate\Support\Collection;

class PermissionCatalog
{
    /**
     * @var array<string, string>
     */
    private const GROUPS = [
        'dashboard' => 'Intranet',
        'directory' => 'Intranet',
        'analytics' => 'Intranet',
        'tickets' => 'Support informatique',
        'forms' => 'Formulaires et demandes',
        'submissions' => 'Formulaires et demandes',
        'time-off' => 'Formulaires et demandes',
        'announcements' => 'Communication et ressources',
        'calendar' => 'Communication et ressources',
        'resources' => 'Communication et ressources',
        'departments' => 'Organisation',
        'users' => 'Administration système',
        'roles' => 'Administration système',
        'settings' => 'Administration système',
    ];

    /**
     * @return array<string, array<int, array{nom: string, slug: string, description: string}>>
     */
    public static function groupedDefinitions(): array
    {
        return [
            'Intranet' => [
                [
                    'nom' => 'Accéder au tableau de bord',
                    'slug' => 'dashboard.view',
                    'description' => 'Consulter la page d’accueil et les informations générales de l’intranet.',
                ],
                [
                    'nom' => 'Consulter l’annuaire',
                    'slug' => 'directory.view',
                    'description' => 'Rechercher des collaborateurs dans l’annuaire interne.',
                ],
                [
                    'nom' => 'Consulter les indicateurs',
                    'slug' => 'analytics.view',
                    'description' => 'Afficher les tableaux de bord et les indicateurs de l’intranet.',
                ],
            ],
            'Support informatique' => [
                [
                    'nom' => 'Consulter les tickets',
                    'slug' => 'tickets.view',
                    'description' => 'Afficher les tickets de support autorisés pour le périmètre de l’utilisateur.',
                ],
                [
                    'nom' => 'Créer des tickets',
                    'slug' => 'tickets.create',
                    'description' => 'Soumettre une nouvelle demande de support informatique.',
                ],
                [
                    'nom' => 'Traiter les tickets',
                    'slug' => 'tickets.update',
                    'description' => 'Mettre à jour le statut et les informations de traitement des tickets.',
                ],
                [
                    'nom' => 'Assigner les tickets',
                    'slug' => 'tickets.assign',
                    'description' => 'Affecter un ticket à un technicien ou à une équipe.',
                ],
            ],
            'Formulaires et demandes' => [
                [
                    'nom' => 'Consulter les formulaires',
                    'slug' => 'forms.view',
                    'description' => 'Accéder aux formulaires disponibles pour son périmètre.',
                ],
                [
                    'nom' => 'Gérer les formulaires',
                    'slug' => 'forms.manage',
                    'description' => 'Créer, modifier et désactiver les formulaires du périmètre autorisé.',
                ],
                [
                    'nom' => 'Consulter les demandes',
                    'slug' => 'submissions.view',
                    'description' => 'Consulter les demandes soumises dans le périmètre autorisé.',
                ],
                [
                    'nom' => 'Valider les demandes',
                    'slug' => 'submissions.review',
                    'description' => 'Approuver, rejeter ou faire avancer une demande.',
                ],
                [
                    'nom' => 'Approuver les congés',
                    'slug' => 'time-off.approve',
                    'description' => 'Traiter les demandes de congé dans le périmètre de responsabilité.',
                ],
            ],
            'Communication et ressources' => [
                [
                    'nom' => 'Consulter les annonces',
                    'slug' => 'announcements.view',
                    'description' => 'Afficher les annonces publiées pour l’intranet ou son département.',
                ],
                [
                    'nom' => 'Gérer les annonces',
                    'slug' => 'announcements.manage',
                    'description' => 'Publier, modifier et retirer des annonces dans le périmètre autorisé.',
                ],
                [
                    'nom' => 'Consulter le calendrier',
                    'slug' => 'calendar.view',
                    'description' => 'Afficher les événements du calendrier de l’intranet.',
                ],
                [
                    'nom' => 'Gérer le calendrier',
                    'slug' => 'calendar.manage',
                    'description' => 'Créer, modifier et supprimer des événements du calendrier.',
                ],
                [
                    'nom' => 'Consulter les ressources',
                    'slug' => 'resources.view',
                    'description' => 'Accéder aux documents et ressources partagés.',
                ],
                [
                    'nom' => 'Gérer les ressources',
                    'slug' => 'resources.manage',
                    'description' => 'Ajouter, modifier et retirer des ressources partagées.',
                ],
            ],
            'Organisation' => [
                [
                    'nom' => 'Consulter les départements',
                    'slug' => 'departments.view',
                    'description' => 'Consulter les informations des départements.',
                ],
                [
                    'nom' => 'Gérer les départements',
                    'slug' => 'departments.manage',
                    'description' => 'Modifier le référentiel des départements selon les droits attribués.',
                ],
            ],
            'Administration système' => [
                [
                    'nom' => 'Consulter les utilisateurs',
                    'slug' => 'users.view',
                    'description' => 'Consulter les comptes utilisateurs et leurs informations.',
                ],
                [
                    'nom' => 'Gérer les utilisateurs',
                    'slug' => 'users.manage',
                    'description' => 'Créer, modifier, désactiver des comptes et gérer leurs affectations.',
                ],
                [
                    'nom' => 'Gérer les rôles et permissions',
                    'slug' => 'roles.manage',
                    'description' => 'Créer les rôles et leur associer des permissions.',
                ],
                [
                    'nom' => 'Gérer les paramètres système',
                    'slug' => 'settings.manage',
                    'description' => 'Modifier la configuration générale de l’intranet et ses intégrations.',
                ],
            ],
        ];
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public static function group(Collection $permissions): Collection
    {
        return $permissions
            ->groupBy(fn (Permission $permission): string => self::groupFor($permission->slug))
            ->sortKeys();
    }

    public static function groupFor(string $slug): string
    {
        $prefix = explode('.', $slug, 2)[0];

        return self::GROUPS[$prefix] ?? 'Autres permissions';
    }
}
