<?php

// Valeurs par défaut de la supervision réseau, au sommet de l'héritage
// groupe > sous-groupe > équipement > capteur (comme le groupe racine de PRTG).
return [
    // Entrée « Supervision réseau » dans le menu : masquée tant que le module est en essai.
    'show_in_menu' => (bool) env('MONITOR_SHOW_IN_MENU', false),
    'interval_seconds' => (int) env('MONITOR_INTERVAL_SECONDS', 60),
    // Comme PRTG : pas d'alerte pour une panne de moins de 10 minutes (coupures brèves, capteurs instables).
    'notify_delay_minutes' => (int) env('MONITOR_NOTIFY_DELAY_MINUTES', 10),
    'notify_repeat_minutes' => (int) env('MONITOR_NOTIFY_REPEAT_MINUTES', 0),
    'escalate_after_minutes' => (int) env('MONITOR_ESCALATE_AFTER_MINUTES', 30),
    'snmp_community' => env('MONITOR_SNMP_COMMUNITY', 'public'),
    'snmp_timeout' => (float) env('MONITOR_SNMP_TIMEOUT', 2),
    'raw_days' => 7,
    'hourly_days' => 400,
    'queue' => env('MONITOR_QUEUE', 'monitor'),
    // Adresse d'un webhook entrant (Microsoft Teams, Slack, Google Chat, Mattermost, Discord…) :
    // les alertes y sont aussi publiées. Vide = désactivé.
    'webhook_url' => env('MONITOR_WEBHOOK_URL'),
];
