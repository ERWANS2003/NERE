<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nom de l'entreprise
    |--------------------------------------------------------------------------
    */
    'company_name' => env('COMPANY_NAME', 'NÉRÉ Mining'),

    /*
    |--------------------------------------------------------------------------
    | Logo de l'entreprise
    |--------------------------------------------------------------------------
    */
    'company_logo' => env('COMPANY_LOGO', '/images/logo-nere-mining.png'),

    /*
    |--------------------------------------------------------------------------
    | Couleurs de la marque
    |--------------------------------------------------------------------------
    */
    'brand_colors' => [
        'primary' => '#C83530',   // Rouge NÉRÉ
        'secondary' => '#F9C74F', // Jaune NÉRÉ
        'dark' => '#1A1A1A',      // Noir
        'brown' => '#4A3F35',     // Brun
    ],

    /*
    |--------------------------------------------------------------------------
    | Départements
    |--------------------------------------------------------------------------
    | Liste des départements de l'entreprise avec leurs codes et icônes
    */
    'departments' => [
        'ADMIN' => [
            'name' => 'Administration',
            'icon' => 'building',
            'image' => '/images/departments/01-administration.png',
            'color' => '#C83530',
        ],
        'RH' => [
            'name' => 'Ressources Humaines',
            'icon' => 'users',
            'image' => '/images/departments/02-ressources-humaines.png',
            'color' => '#F9C74F',
        ],
        'SECURITY' => [
            'name' => 'Sécurité',
            'icon' => 'shield',
            'image' => '/images/departments/03-securite.png',
            'color' => '#4A3F35',
        ],
        'MINING' => [
            'name' => 'Mining',
            'icon' => 'pickaxe',
            'image' => '/images/departments/04-mining.png',
            'color' => '#8B4513',
        ],
        'HSE' => [
            'name' => 'HSE',
            'icon' => 'heart-pulse',
            'image' => '/images/departments/05-hse.png',
            'color' => '#90BE6D',
        ],
        'PROCESSING' => [
            'name' => 'Processing',
            'icon' => 'factory',
            'image' => '/images/departments/06-processing.png',
            'color' => '#577590',
        ],
        'SCM' => [
            'name' => 'SCM',
            'icon' => 'package',
            'image' => '/images/departments/07-scm.png',
            'color' => '#F94144',
        ],
        'IT' => [
            'name' => 'IT',
            'icon' => 'laptop',
            'image' => '/images/departments/08-it.png',
            'color' => '#277DA1',
        ],
        'RELCOM' => [
            'name' => 'Relations Communautaires',
            'icon' => 'handshake',
            'image' => '/images/departments/09-relations-communautaires.png',
            'color' => '#F3722C',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de l'upload de fichiers
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_size' => 10 * 1024 * 1024, // 10 MB
        'allowed_types' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 
            'ppt', 'pptx', 'jpg', 'jpeg', 'png', 
            'gif', 'zip', 'rar'
        ],
        'disk' => 'public',
        'paths' => [
            'announcements' => 'announcements',
            'resources' => 'resources',
            'forms' => 'forms',
            'avatars' => 'avatars',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration du calendrier
    |--------------------------------------------------------------------------
    */
    'calendar' => [
        'default_view' => 'dayGridMonth',
        // Clés = valeurs de l'enum calendar_events.type. Birthday et arrival ne sont
        // pas stockés : ils sont calculés depuis users.birth_date / users.hire_date.
        // « text » : le jaune n'est lisible qu'avec un texte sombre.
        'event_types' => [
            'meeting' => ['name' => 'Réunion', 'color' => '#C83530', 'text' => '#FFFFFF'],
            'training' => ['name' => 'Formation', 'color' => '#4F7F3A', 'text' => '#FFFFFF'],
            'event' => ['name' => 'Événement', 'color' => '#1A1A1A', 'text' => '#FFFFFF'],
            'birthday' => ['name' => 'Anniversaire', 'color' => '#FFD25A', 'text' => '#1A1A1A'],
            'arrival' => ['name' => 'Arrivée', 'color' => '#F0B52B', 'text' => '#1A1A1A'],
            // Absences approuvées (congés) : bleu clair, non stocké dans calendar_events.
            'time_off' => ['name' => 'Absence', 'color' => '#577590', 'text' => '#FFFFFF'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des annonces
    |--------------------------------------------------------------------------
    */
    'announcements' => [
        'types' => [
            'general' => ['name' => 'Général', 'icon' => 'megaphone'],
            'event' => ['name' => 'Événement', 'icon' => 'calendar'],
            'alert' => ['name' => 'Alerte', 'icon' => 'alert-triangle'],
            'policy' => ['name' => 'Politique', 'icon' => 'book-open'],
        ],
        'priorities' => [
            'low' => ['name' => 'Basse', 'color' => 'gray'],
            'medium' => ['name' => 'Moyenne', 'color' => 'yellow'],
            'high' => ['name' => 'Haute', 'color' => 'red'],
        ],
        'items_per_page' => 10,
        'pinned_max' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration de la recherche
    |--------------------------------------------------------------------------
    */
    'search' => [
        'items_per_page' => 20,
        'max_results' => 100,
        'min_query_length' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des congés (Time Off)
    |--------------------------------------------------------------------------
    */
    'time_off' => [
        'default_annual_days' => 30,
        'default_sick_days' => 15,
        'request_advance_days' => 7,
        'max_consecutive_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Paramètres de pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default' => 15,
        'announcements' => 10,
        'employees' => 20,
        'resources' => 15,
        'calendar_events' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets du dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard_widgets' => [
        'announcements' => ['enabled' => true, 'order' => 1],
        'calendar' => ['enabled' => true, 'order' => 2],
        'quick_links' => ['enabled' => true, 'order' => 3],
        'recent_resources' => ['enabled' => true, 'order' => 4],
        'birthdays' => ['enabled' => true, 'order' => 5],
        'statistics' => ['enabled' => true, 'order' => 6],
    ],

    /*
    |--------------------------------------------------------------------------
    | Jours fériés du Burkina Faso
    |--------------------------------------------------------------------------
    */
    'holidays' => [
        '01-01' => 'Nouvel An',
        '01-03' => 'Journée de la Femme',
        '05-01' => 'Fête du Travail',
        '08-05' => 'Journée de la Révolution',
        '12-11' => 'Fête Nationale',
        '12-25' => 'Noël',
        // Dates variables (Pâques, Tabaski, etc.) à calculer dynamiquement
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => ['database', 'mail'],
        'events' => [
            'announcement_created' => true,
            'calendar_event_created' => true,
            'calendar_event_reminder' => true,
            'time_off_request_created' => true,
            'time_off_request_approved' => true,
            'time_off_request_rejected' => true,
        ],
    ],

];
