<?php

$publicKey = env('WEB_PUSH_VAPID_PUBLIC_KEY');
$privateKey = env('WEB_PUSH_VAPID_PRIVATE_KEY');
$allowedHosts = explode(',', (string) env(
    'WEB_PUSH_ALLOWED_HOSTS',
    'fcm.googleapis.com,updates.push.services.mozilla.com,push.services.mozilla.com,web.push.apple.com,notify.windows.com'
));

return [
    'enabled' => (bool) env('WEB_PUSH_ENABLED', false)
        && is_string($publicKey) && trim($publicKey) !== ''
        && is_string($privateKey) && trim($privateKey) !== '',
    'public_key' => $publicKey,
    'private_key' => $privateKey,
    'subject' => env('WEB_PUSH_VAPID_SUBJECT', 'mailto:intranet@nere.local'),
    'allowed_hosts' => array_values(array_filter(array_map(
        static fn (string $host): string => strtolower(trim($host)),
        $allowedHosts
    ))),
];
