<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;

/**
 * Guides interactifs pas à pas, adaptés au rôle de chaque utilisateur.
 *
 * Une étape porte la route sur laquelle elle s'affiche (« route »), l'élément
 * mis en lumière (« target ») et, pour la première étape d'une page, l'adresse
 * où conduire l'utilisateur (« url »). Une étape « click » attend que
 * l'utilisateur clique lui-même sur l'élément : le guide reprend ensuite sur la
 * page suivante. Le même contenu alimente le centre d'aide en version écrite.
 */
class IntranetGuides
{
    public const ROLES = [
        'tous' => 'Tous les collaborateurs',
        'responsable' => 'Responsables et chefs de département',
        'traitement' => 'Équipes qui traitent les demandes',
        'technicien' => 'Techniciens informatiques',
        'gestion' => 'Gestionnaires et administrateurs',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function for(User $user): array
    {
        $modules = app(IntranetModuleRegistry::class)->all();
        $on = fn (string $module) => $modules[$module]['enabled'] ?? false;
        $itId = Department::query()->where('code', 'IT')->where('is_active', true)->value('id');

        $guides = [
            'decouverte' => [
                'title' => 'Découvrir l’intranet',
                'summary' => 'Le tour complet de l’interface : menu, accès rapides, recherche, notifications et profil.',
                'role' => 'tous',
                'steps' => array_map(fn (array $step) => [...$step, 'route' => ['intranet.home', 'intranet.dashboard']], IntranetTutorial::stepsFor($user)),
            ],
        ];
        $guides['decouverte']['steps'][0]['url'] = route('intranet.dashboard');

        if ($on('time_off')) {
            $guides['conges'] = self::timeOff();
        }
        if ($on('forms')) {
            $guides['demande'] = self::departmentRequest();
        }
        if ($on('tickets')) {
            $guides['ticket'] = self::ticket();
        }
        if ($user->canChooseMenu()) {
            $guides['menu'] = self::menu();
        }
        if ($on('chat')) {
            $guides['chat'] = self::chat();
        }
        $guides['annuaire'] = self::directory();
        $guides['notifications'] = self::notifications();

        if ($on('time_off') && $user->canApproveTimeOff()) {
            $guides['approbations'] = self::approvals();
        }
        if ($user->can('create', Announcement::class)) {
            $guides['annonce'] = self::announcement($user);
        }
        if ($on('forms') && $user->hasDepartmentProcessingAccess()) {
            $guides['traitement'] = self::processing($user);
        }
        if ($on('tickets') && $itId && $user->canHandleIn($itId)) {
            $guides['tickets-support'] = self::ticketSupport($user);
        }
        if ($user->hasPermission('menu.manage')) {
            $guides['menu-gestion'] = self::menuManagement();
        }

        foreach ($guides as $id => &$guide) {
            $guide['id'] = $id;
            $guide['minutes'] = max(1, (int) ceil(count($guide['steps']) * 0.4));
        }

        return $guides;
    }

    private static function step(string|array $route, ?string $target, string $title, string $description, array $extra = []): array
    {
        return ['route' => $route, 'target' => $target, 'title' => $title, 'description' => $description, ...$extra];
    }

    private static function timeOff(): array
    {
        $index = 'intranet.time-off.index';
        $create = 'intranet.time-off.create';

        return [
            'title' => 'Demander un congé',
            'summary' => 'Lire ses soldes, remplir une demande, suivre la décision et annuler si besoin.',
            'role' => 'tous',
            'steps' => [
                self::step($index, '[data-tour="conges-soldes"]', 'Vos soldes de congés', 'Chaque carte correspond à un type de congé. Le grand chiffre indique les jours qui vous restent, la barre la part déjà utilisée sur l’année.', [
                    'url' => route($index),
                    'tip' => 'Le solde n’est débité qu’à l’approbation. Une demande refusée ou annulée vous rend vos jours.',
                ]),
                self::step($index, '[data-tour="conges-historique"]', 'L’historique de vos demandes', 'Toutes vos demandes y figurent avec leurs dates, le nombre de jours et le statut : En attente, Approuvée, Refusée ou Annulée.'),
                self::step($index, '[data-tour="conges-annuler"]', 'Annuler une demande', 'Tant qu’une demande est en attente, ou approuvée mais pas encore commencée, le bouton « Annuler » la retire. Vos jours sont recrédités automatiquement.'),
                self::step($index, '[data-tour="conges-nouvelle"]', 'Créer une demande', 'Cliquez sur « Demander un congé » pour ouvrir le formulaire. Le guide continue sur la page suivante.', ['click' => true]),
                self::step($create, '[data-tour="conges-type"]', '1. Le type de congé', 'Choisissez le type : congé annuel, maladie, permission… Si ce type exige un préavis, le nombre de jours à respecter s’affiche juste en dessous.'),
                self::step($create, '[data-tour="conges-dates"]', '2. Les dates', 'Indiquez le premier et le dernier jour d’absence. La date de fin ne peut pas précéder la date de début. Une durée estimée en jours ouvrés apparaît dès que les deux dates sont remplies.', [
                    'tip' => 'Les samedis, dimanches et jours fériés ne sont pas décomptés de votre solde.',
                ]),
                self::step($create, '[data-tour="conges-demi"]', '3. Une demi-journée ?', 'Cochez cette case pour une absence d’une demi-journée : une seule date est alors retenue et 0,5 jour est décompté.'),
                self::step($create, '[data-tour="conges-motif"]', '4. Le motif', 'Facultatif, mais utile à votre responsable pour décider vite : formation, raison familiale, rendez-vous médical…'),
                self::step($create, '[data-tour="conges-urgence"]', '5. Le contact d’urgence', 'Facultatif : un numéro ou une personne à joindre pendant votre absence.'),
                self::step($create, '[data-tour="conges-envoyer"]', '6. Envoyer la demande', 'Relisez puis cliquez sur « Soumettre la demande ». En cas d’erreur (solde insuffisant, dates qui se chevauchent), un message rouge s’affiche en haut du formulaire et rien n’est envoyé.', ['click' => true]),
                self::step($index, '[data-tour="conges-historique"]', 'Et maintenant ?', 'Votre demande apparaît « En attente ». Votre responsable est prévenu immédiatement ; s’il ne répond pas sous 48 h, un rappel lui est envoyé. Vous recevez la décision dans la cloche et en notification Windows.'),
            ],
        ];
    }

    private static function departmentRequest(): array
    {
        return [
            'title' => 'Faire une demande à un département',
            'summary' => 'Trouver le bon département, choisir le formulaire, l’envoyer puis suivre son traitement.',
            'role' => 'tous',
            'steps' => [
                self::step('intranet.departments.index', '[data-tour="departements-liste"]', 'Les départements', 'Chaque carte présente un département et ses services. C’est ici que commencent toutes les démarches : attestation, matériel, intervention, accès…', [
                    'url' => route('intranet.departments.index'),
                ]),
                self::step('intranet.departments.index', '[data-tour="departement-carte"]', 'Ouvrir un département', 'Cliquez sur le département qui traite votre besoin, par exemple les Ressources humaines pour une attestation.', ['click' => true]),
                self::step('intranet.departments.show', '[data-tour="departement-onglets"]', 'Les onglets', '« Services » présente ce que propose le département et donne accès aux demandes en ligne ; « Équipe » liste les personnes à contacter.'),
                self::step('intranet.departments.show', '[data-tour="service-carte"]', 'Choisir le service', 'Repérez le service qui correspond à votre besoin et cliquez sur « Ouvrir » : le formulaire de demande s’affiche.', ['click' => true]),
                self::step('intranet.forms.show', '[data-tour="formulaire-champs"]', 'Remplir le formulaire', 'Les champs marqués d’un astérisque rouge sont obligatoires. Soyez précis : dates, quantités, lieu… Une demande complète est traitée sans aller-retour.'),
                self::step('intranet.forms.show', '[data-tour="formulaire-envoyer"]', 'Envoyer', 'Cliquez sur « Envoyer la demande ». Les personnes chargées de ce formulaire sont prévenues aussitôt.', ['click' => true]),
                self::step('intranet.submissions.show', '[data-tour="demande-entete"]', 'Le suivi de votre demande', 'Votre demande a un numéro et un statut : « En attente » tant qu’elle n’est pas décidée, puis « Approuvée » ou « Rejetée ». Le commentaire du responsable s’affiche plus bas sur cette page.'),
                self::step('intranet.submissions.index', '[data-tour="mes-demandes-filtres"]', 'Toutes vos demandes', 'Retrouvez ici l’ensemble de vos demandes. Filtrez par statut pour voir celles qui attendent encore une réponse.', [
                    'url' => route('intranet.submissions.index'),
                ]),
                self::step('intranet.submissions.index', '[data-tour="mes-demandes-liste"]', 'Ouvrir une demande', 'Cliquez sur « Voir » pour relire une demande et la réponse reçue. Vous êtes aussi prévenu dans la cloche à chaque décision.'),
            ],
        ];
    }

    private static function ticket(): array
    {
        $create = 'intranet.tickets.create';

        return [
            'title' => 'Signaler un problème informatique',
            'summary' => 'Créer un ticket clair, joindre une capture, échanger avec le support et suivre la résolution.',
            'role' => 'tous',
            'steps' => [
                self::step($create, '[data-tour="ticket-suggestions"]', 'Avant d’écrire', 'Des fiches de la base de connaissances règlent souvent le problème en deux minutes (imprimante, mot de passe, Wi-Fi). Jetez-y un œil.', ['url' => route($create)]),
                self::step($create, '#subject', '1. L’objet', 'Résumez le problème en une phrase précise. Bien : « L’imprimante du 2e étage affiche Bourrage papier ». Trop vague : « Problème imprimante ».', ['url' => route($create)]),
                self::step($create, '[data-tour="ticket-classement"]', '2. Catégorie et priorité', 'La catégorie oriente le ticket vers le bon technicien. Réservez la priorité « Urgente » aux situations où votre travail est totalement bloqué ; « Normale » convient à la plupart des cas.'),
                self::step($create, '#description', '3. La description', 'Indiquez ce qui se passe, depuis quand, le message d’erreur exact et ce que vous avez déjà essayé. Plus c’est précis, plus la résolution est rapide.'),
                self::step($create, '#attachments', '4. Les pièces jointes', 'Ajoutez une capture d’écran du message d’erreur ou une photo du matériel (PDF, image, Word, Excel ; plusieurs fichiers possibles).', [
                    'tip' => 'Capture d’écran rapide sous Windows : touches Windows + Maj + S.',
                ]),
                self::step($create, '[data-tour="ticket-envoyer"]', '5. Envoyer', 'Cliquez sur « Envoyer au support ». Les techniciens sont notifiés immédiatement.', ['click' => true]),
                self::step('intranet.tickets.show', '[data-tour="ticket-entete"]', 'Votre ticket', 'Il reçoit un numéro, un statut (Nouveau, En cours, En attente, Résolu, Fermé) et la priorité choisie. Vous êtes prévenu à chaque changement.'),
                self::step('intranet.tickets.show', '[data-tour="ticket-echanges"]', 'Échanger avec le support', 'Le technicien peut vous poser une question ici. Répondez dans ce fil, en ajoutant au besoin une nouvelle capture : tout l’historique reste au même endroit.'),
                self::step('intranet.tickets.index', '[data-tour="tickets-liste"]', 'Tous vos tickets', 'Cette page liste vos tickets avec leur statut. Utilisez la recherche par numéro ou par objet pour en retrouver un.', ['url' => route('intranet.tickets.index')]),
            ],
        ];
    }

    private static function menu(): array
    {
        $show = 'menu.registration.show';

        return [
            'title' => 'Choisir ses repas de la semaine',
            'summary' => 'Ouvrir la semaine, choisir un plat par jour, enregistrer et télécharger son récapitulatif.',
            'role' => 'tous',
            'steps' => [
                self::step('menu.portal', '[data-tour="menu-semaine"]', 'Les semaines ouvertes', 'Chaque carte est une semaine de menus dont les inscriptions sont ouvertes. Cliquez sur « Choisir mes menus ».', [
                    'url' => route('menu.portal'),
                    'click' => true,
                ]),
                self::step($show, '[data-tour="menu-departement"]', '1. Votre département', 'Indiquez votre département : la restauration prépare les repas par département. Commencez à taper pour voir les suggestions.'),
                self::step($show, '[data-tour="menu-jours"] > section', '2. Un choix par jour', 'Voici le lundi : faites de même pour chaque jour. Cliquez sur Menu 1 ou Menu 2, ou cochez « Pas de repas ce jour ». Le dessert est le même pour tous.', [
                    'tip' => 'Vous pouvez modifier vos choix autant de fois que nécessaire jusqu’à la clôture des inscriptions.',
                ]),
                self::step($show, '[data-tour="menu-enregistrer"]', '3. Enregistrer', 'Cliquez sur « Enregistrer mes choix ». Un message vert confirme l’enregistrement.', ['click' => true]),
                self::step($show, '[data-tour="menu-telecharger"]', '4. Votre récapitulatif', 'Téléchargez le PDF de vos choix pour les garder sous la main. Ce bouton apparaît dès que vos choix sont enregistrés.'),
            ],
        ];
    }

    private static function chat(): array
    {
        $show = 'intranet.chat.show';

        return [
            'title' => 'Utiliser le chat',
            'summary' => 'Les salons, l’envoi et la correction des messages, les messages non lus.',
            'role' => 'tous',
            'steps' => [
                self::step($show, '[data-tour="chat-salons"]', 'Les salons', '« Général » réunit tout le personnel. Chaque département a son propre salon, réservé à ses membres. Les salons privés sont créés par un administrateur. Une pastille rouge indique les messages non lus.', [
                    'url' => route('intranet.chat.index'),
                ]),
                self::step($show, '[data-tour="chat-fil"]', 'La conversation', 'Les nouveaux messages arrivent seuls, sans recharger la page. Survolez un de vos messages pour le modifier ou le supprimer pendant 15 minutes.'),
                self::step($show, '[data-tour="chat-saisie"]', 'Écrire un message', 'Tapez votre texte puis appuyez sur Entrée pour l’envoyer ; Maj + Entrée passe à la ligne. Les liens deviennent cliquables.', [
                    'tip' => 'Les messages sont conservés 6 mois. Restez courtois : les chefs de département et les administrateurs peuvent supprimer un message.',
                ]),
            ],
        ];
    }

    private static function directory(): array
    {
        return [
            'title' => 'Trouver un collègue',
            'summary' => 'Rechercher dans l’annuaire et utiliser la recherche rapide.',
            'role' => 'tous',
            'steps' => [
                self::step('intranet.directory', '[data-tour="annuaire-recherche"]', 'La recherche', 'Tapez un nom, un prénom, un poste ou un numéro : les résultats se mettent à jour pendant la saisie.', ['url' => route('intranet.directory')]),
                self::step('intranet.directory', '[data-tour="annuaire-liste"]', 'Les fiches', 'Chaque fiche donne le poste, le département et les coordonnées. Cliquez sur le téléphone ou l’adresse pour appeler ou écrire directement.'),
                self::step('intranet.directory', '#intranet-search-trigger', 'La recherche rapide', 'Depuis n’importe quelle page, ce champ cherche à la fois les collègues, les départements, les annonces et les documents.', [
                    'tip' => 'Raccourci clavier : Ctrl + K.',
                ]),
            ],
        ];
    }

    private static function notifications(): array
    {
        $index = 'intranet.notifications.index';

        return [
            'title' => 'Ne rien manquer avec les notifications',
            'summary' => 'Activer les notifications Windows et gérer la cloche.',
            'role' => 'tous',
            'steps' => [
                self::step($index, '[data-tour="notifications-push"]', 'Les notifications Windows', 'Cliquez sur « Activer les notifications push » puis acceptez la demande du navigateur : les alertes de l’intranet s’afficheront dans Windows avec un son, même onglet fermé. À faire sur chaque ordinateur que vous utilisez.', [
                    'url' => route($index),
                ]),
                self::step($index, '[data-tour="notifications-liste"]', 'L’historique', 'Toutes vos notifications sont gardées ici. Cliquez sur une notification pour ouvrir l’élément concerné : elle est alors marquée comme lue.'),
                self::step($index, '#intranet-notifications-trigger', 'La cloche', 'Le chiffre sur la cloche indique les notifications non lues. Un clic affiche les dernières, depuis n’importe quelle page.'),
            ],
        ];
    }

    private static function approvals(): array
    {
        $page = 'intranet.time-off.approvals';

        return [
            'title' => 'Valider les congés de mon équipe',
            'summary' => 'Examiner une demande, l’approuver ou la refuser avec un commentaire.',
            'role' => 'responsable',
            'steps' => [
                self::step($page, null, 'Les demandes à approuver', 'Cette page réunit les demandes de congé de vos collaborateurs en attente de décision. Vous êtes prévenu à chaque nouvelle demande, puis relancé si elle attend plus de 48 h.', [
                    'url' => route($page),
                ]),
                self::step($page, '[data-tour="approbation-demande"]', 'Lire la demande', 'Vous voyez le collaborateur, le type de congé, les dates, le nombre de jours décomptés et le motif éventuel.'),
                self::step($page, '[data-tour="approbation-boutons"]', 'Décider', 'Cliquez sur « Approuver » ou « Refuser ». Un champ de commentaire s’ouvre : il est transmis au collaborateur, indispensable en cas de refus. Confirmez pour valider.', [
                    'tip' => 'L’approbation débite le solde du collaborateur. Il est notifié immédiatement de votre décision.',
                ]),
                self::step($page, '#intranet-time-off-approvals-link', 'Accès rapide', 'Ce lien du menu affiche le nombre de demandes en attente.', ['zone' => 'sidebar']),
            ],
        ];
    }

    private static function announcement(User $user): array
    {
        $create = 'intranet.announcements.create';

        return [
            'title' => 'Publier une annonce',
            'summary' => 'Rédiger, cibler le bon public, joindre un document et publier.',
            'role' => 'responsable',
            'steps' => [
                self::step($create, '#title', '1. Le titre', 'Un titre court et explicite, visible dans la liste et dans la notification : « Coupure d’eau mardi 14 h-16 h ».', ['url' => route($create)]),
                self::step($create, '#body', '2. Le contenu', 'Donnez l’essentiel en premier : quoi, quand, qui est concerné, que faut-il faire.'),
                self::step($create, '#priority', '3. La priorité', '« Info » pour l’actualité, « Important » pour ce qui demande une attention, « Urgent » pour une consigne immédiate. Les annonces les plus prioritaires sont mises en avant.'),
                self::step($create, '#department_id', '4. Le public', $user->is_super_admin
                    ? 'Tout le personnel ou un seul département : seul ce public voit l’annonce et reçoit la notification.'
                    : 'Choisissez tout le personnel ou votre seul département : seul ce public voit l’annonce et reçoit la notification.'),
                self::step($create, '#attachment', '5. Une pièce jointe', 'Facultatif : note de service, planning, affiche (PDF, image, Word, Excel, PowerPoint, 10 Mo maximum).'),
                self::step($create, '[data-tour="annonce-publier"]', '6. Publier', 'Cliquez sur « Publier » : le public choisi reçoit aussitôt une notification. Vous pourrez modifier ou supprimer l’annonce ensuite.', ['click' => true]),
            ],
        ];
    }

    private static function processing(User $user): array
    {
        $queue = 'intranet.submissions.queue';
        $show = 'intranet.submissions.show';

        return [
            'title' => 'Traiter les demandes de mon département',
            'summary' => 'Trier la file, prendre en charge une demande et la décider.',
            'role' => 'traitement',
            'steps' => [
                self::step($queue, '[data-tour="file-raccourcis"]', 'Vos raccourcis', '« Demandes en attente » montre tout ce qui n’est pas encore décidé ; « Prises en charge par moi » seulement les demandes que vous suivez.', ['url' => route($queue)]),
                self::step($queue, '#assignment', 'Les filtres', 'Filtrez par statut, par attribution (« À prendre en charge », « Prises en charge par moi ») et par département si vous en traitez plusieurs.'),
                self::step($queue, '[data-tour="file-liste"]', 'La file', 'Les demandes sont classées de la plus ancienne à la plus récente : traitez en priorité le haut de la liste.'),
                self::step($queue, '[data-tour="file-liste"] a', 'Ouvrir une demande', 'Cliquez sur « Ouvrir » en face d’une demande pour la traiter.', ['click' => true]),
                self::step($show, '[data-tour="demande-prise-en-charge"]', 'Prendre en charge', 'Cliquez sur « Prendre en charge » : la demande vous est attribuée et vos collègues savent qu’elle est suivie. Le demandeur voit qui s’en occupe.'),
                self::step($show, '[data-tour="demande-decision"]', 'Décider', $user->is_super_admin || $user->isDepartmentHead()
                    ? 'Rédigez un commentaire puis cliquez sur « Approuver » ou « Rejeter ». Le demandeur est notifié immédiatement avec votre commentaire.'
                    : 'La décision finale (approuver ou rejeter) revient au chef de département. Préparez le dossier pour qu’il puisse trancher rapidement.'),
                self::step($show, null, 'À retenir', 'Les boutons « Prendre en charge » et de décision n’apparaissent que sur une demande encore en attente. Une fois décidée, la demande reste consultable ici avec le commentaire et l’auteur de la décision. Revenez à la file avec le lien en bas de page.'),
            ],
        ];
    }

    private static function ticketSupport(User $user): array
    {
        $show = 'intranet.tickets.show';

        return [
            'title' => 'Traiter les tickets du support',
            'summary' => 'Trier les tickets, les prendre, les attribuer, échanger et résoudre.',
            'role' => 'technicien',
            'steps' => [
                self::step('intranet.tickets.index', '[data-tour="tickets-filtres"]', 'Rechercher et filtrer', 'Retrouvez un ticket par numéro ou par objet et filtrez par statut pour voir les tickets ouverts ou en cours.', ['url' => route('intranet.tickets.index')]),
                self::step('intranet.tickets.index', '[data-tour="tickets-liste"]', 'La liste', 'Vous voyez tous les tickets du support avec leur priorité, leur statut et la personne assignée.'),
                self::step('intranet.tickets.index', '[data-tour="tickets-liste"] a', 'Ouvrir un ticket', 'Cliquez sur le numéro d’un ticket (par exemple IT-000123) pour l’ouvrir.', ['click' => true]),
                self::step($show, '[data-tour="ticket-traitement"]', 'Prendre et traiter', 'Cliquez sur « Prendre en charge » pour vous l’attribuer, puis mettez à jour le statut et la priorité et cliquez sur « Enregistrer le traitement ».', [
                    'tip' => 'Vous pouvez attribuer un ticket à un technicien de niveau inférieur au vôtre, jamais à un niveau égal ou supérieur. Les tickets proches de leur délai déclenchent une alerte.',
                ]),
                self::step($show, '[data-tour="ticket-echanges"]', 'Échanger', 'Répondez au demandeur dans ce fil. Cochez « Note interne » pour un commentaire visible seulement des techniciens.'),
            ],
        ];
    }

    private static function menuManagement(): array
    {
        $index = 'menu.manager.campaigns';

        return [
            'title' => 'Gérer les menus du personnel',
            'summary' => 'Préparer une semaine, la publier, suivre les choix, saisir pour les personnes sans compte et exporter.',
            'role' => 'gestion',
            'steps' => [
                self::step($index, '[data-tour="menu-gestion-actions"]', 'Les actions sur une semaine', '« Ouvrir le lien » montre la page des employés ; « Export Excel » produit la commande par jour et par département ; « Suivi des choix » liste qui a choisi ou non ; « Clôturer » arrête les inscriptions. Une semaine clôturée peut être rouverte ou supprimée.', [
                    'url' => route($index),
                ]),
                self::step($index, '[data-tour="menu-gestion-lien"]', 'Le lien à transmettre', 'Les employés désignés sont notifiés à la publication, mais vous pouvez aussi partager ce lien.'),
                self::step($index, '[data-tour="menu-gestion-personnes"]', 'Les personnes sans compte', 'Pour le personnel absent de l’intranet : ajoutez-les une fois ici, puis saisissez leurs choix depuis le « Suivi des choix » de chaque semaine.'),
                self::step($index, '[data-tour="menu-gestion-preparer"]', 'Préparer une semaine', 'Cliquez sur « Préparer une semaine ».', ['click' => true]),
                self::step('menu.manager.create', '[data-tour="menu-creation-semaine"]', '1. La semaine', 'Choisissez la semaine (du lundi au vendredi), la date de clôture des inscriptions (facultative) et l’état : « Brouillon » pour préparer sans publier, « Publier » pour notifier tout de suite les employés.'),
                self::step('menu.manager.create', '[data-tour="menu-creation-plats"]', '2. Les plats', 'Pour chacun des cinq jours, saisissez le Menu 1, le Menu 2 et le dessert. Tous les champs sont obligatoires. Enregistrez en bas de page.'),
            ],
        ];
    }
}
