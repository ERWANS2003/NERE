<?php

namespace App\Support;

use App\Models\User;

/**
 * Étapes du tutoriel de prise en main, adaptées aux accès de l'utilisateur.
 *
 * Chaque étape vise un élément par sélecteur CSS ; le script ignore côté
 * navigateur une étape dont la cible est absente de la page courante (par
 * exemple les accès rapides, présents seulement sur le tableau de bord).
 */
class IntranetTutorial
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function stepsFor(User $user): array
    {
        $modules = app(IntranetModuleRegistry::class)->all();
        $firstName = ($user->first_name ?? null) ?: explode(' ', trim((string) $user->name))[0];
        $isHead = $user->is_super_admin || $user->isDepartmentHead();

        $steps = [
            [
                'id' => 'bienvenue',
                'title' => 'Bonjour '.$firstName.', bienvenue sur votre intranet',
                'description' => 'En deux minutes, découvrez où trouver vos services, vos demandes et les informations de l’entreprise. L’élément présenté est mis en lumière à chaque étape.',
                'target' => null,
            ],
            [
                'id' => 'accueil',
                'title' => 'Votre point de départ',
                'description' => 'Le lien Accueil vous ramène au tableau de bord, où sont réunies les annonces, les demandes récentes et les événements à venir.',
                'target' => '#intranet-home-link',
                'zone' => 'sidebar',
            ],
            [
                'id' => 'acces-rapides',
                'title' => 'Les accès rapides',
                'description' => 'Ces raccourcis lancent les actions les plus courantes : faire une demande, signaler un problème informatique, suivre vos dossiers ou consulter l’agenda.',
                'target' => '#intranet-quick-actions',
            ],
            [
                'id' => 'demandes',
                'title' => 'Suivre vos demandes',
                'description' => 'Toutes les demandes que vous envoyez arrivent ici, avec leur statut et les réponses des équipes.',
                'target' => '#intranet-requests-link',
                'zone' => 'sidebar',
            ],
            [
                'id' => 'services',
                'title' => 'Les services de l’entreprise',
                'description' => self::servicesDescription($modules),
                'tip' => $modules['tickets']['enabled']
                    ? 'Un problème d’ordinateur ou d’accès ? Ouvrez un ticket depuis « Support informatique ».'
                    : null,
                'target' => '#intranet-services-section',
                'zone' => 'sidebar',
            ],
        ];

        if ($modules['forms']['enabled'] && $user->hasDepartmentProcessingAccess()) {
            $steps[] = [
                'id' => 'traitement',
                'title' => 'Les demandes à traiter',
                'description' => 'Vous pouvez traiter des demandes pour votre département : elles vous attendent dans cette file, de la plus ancienne à la plus récente.',
                'target' => '#intranet-queue-link',
                'zone' => 'sidebar',
            ];
        }

        $steps[] = [
            'id' => 'entreprise',
            'title' => 'La vie de l’entreprise',
            'description' => 'Annonces, agenda, départements, annuaire et ressources documentaires : tout ce qui concerne vos collègues et l’actualité de NÉRÉ Mining.',
            'target' => '#intranet-company-section',
            'zone' => 'sidebar',
        ];

        if ($isHead) {
            $steps[] = [
                'id' => 'pilotage',
                'title' => 'Le pilotage',
                'description' => $user->is_super_admin
                    ? 'Suivez l’activité de toute l’entreprise dans Analytics et gérez les comptes, les rôles et les modules depuis l’Administration.'
                    : 'Analytics présente l’activité de vos départements : volumes, délais de traitement, congés et effectifs.',
                'target' => '#intranet-management-section',
                'zone' => 'sidebar',
            ];
        }

        array_push($steps,
            [
                'id' => 'recherche',
                'title' => 'Tout retrouver en un instant',
                'description' => 'Recherchez un collègue, un département, une annonce ou un document depuis n’importe quelle page.',
                'tip' => 'Raccourci clavier : Ctrl + K (⌘ + K sur Mac).',
                'target' => '#intranet-search-trigger',
            ],
            [
                'id' => 'notifications',
                'title' => 'Vos notifications',
                'description' => 'La cloche signale les réponses à vos demandes, les nouvelles annonces et les rappels qui vous concernent.',
                'target' => '#intranet-notifications-trigger',
            ],
            [
                'id' => 'profil',
                'title' => 'Votre profil et l’aide',
                'description' => 'Mettez à jour vos coordonnées depuis ce menu. Vous pourrez y relancer ce guide à tout moment avec « Revoir le tutoriel ».',
                'target' => '#intranet-profile-menu',
                'opens' => 'profile',
            ],
            [
                'id' => 'demarrage',
                'title' => 'Vous êtes prêt !',
                'description' => 'Voici trois premières actions pour bien démarrer. Vous pouvez aussi terminer le guide et explorer librement.',
                'target' => null,
                'checklist' => array_values(array_filter([
                    ['label' => 'Vérifier mes coordonnées', 'url' => route('profil.edit')],
                    $modules['forms']['enabled']
                        ? ['label' => 'Découvrir les services des départements', 'url' => route('intranet.departments.index')]
                        : null,
                    ['label' => 'Trouver un collègue dans l’annuaire', 'url' => route('intranet.directory')],
                ])),
            ],
        );

        return $steps;
    }

    /**
     * Ne cite que les services réellement activés par l'administration.
     *
     * @param  array<string, array{enabled: bool}>  $modules
     */
    private static function servicesDescription(array $modules): string
    {
        $services = array_values(array_filter([
            $modules['forms']['enabled'] ? 'les démarches auprès des départements' : null,
            $modules['knowledge_base']['enabled'] ? 'la base de connaissances' : null,
            $modules['tickets']['enabled'] ? 'le support informatique' : null,
        ]));

        if ($services === []) {
            return 'Cette rubrique regroupera les services de l’entreprise dès qu’ils seront activés.';
        }

        $last = array_pop($services);
        $list = $services === [] ? $last : implode(', ', $services).' et '.$last;

        return 'Retrouvez ici '.$list.'.';
    }
}
