<?php

namespace App\Support;

/**
 * Jeu d'icônes unique de l'intranet (Lucide, licence ISC), servi depuis
 * resources/icons/lucide : aucune police ni CDN, l'intranet fonctionne sur le
 * réseau local de la mine sans accès à Internet.
 *
 * Les anciens noms Font Awesome restent acceptés (avec ou sans préfixe
 * « fa- ») pour les icônes passées dynamiquement par les vues.
 */
class Icons
{
    public const FALLBACK = 'circle-help';

    /** Nom Font Awesome → nom Lucide. */
    private const ALIASES = [
        'edit' => 'pencil',
        'times' => 'x',
        'xmark' => 'x',
        'trash' => 'trash-2',
        'home' => 'house',
        'cog' => 'settings',
        'sign-out-alt' => 'log-out',
        'sync' => 'refresh-cw',
        'shield-alt' => 'shield',
        'sitemap' => 'network',
        'building' => 'building-2',
        'user-tag' => 'user-cog',
        'user-slash' => 'user-x',
        'check-circle' => 'circle-check',
        'exclamation-circle' => 'circle-alert',
        'exclamation-triangle' => 'triangle-alert',
        'info-circle' => 'info',
        'circle-info' => 'info',
        'gauge-high' => 'gauge',
        'puzzle-piece' => 'puzzle',
        'file-lines' => 'file-text',
        'bullhorn' => 'megaphone',
        'list-check' => 'list-checks',
    ];

    /** @var array<string, string> */
    private static array $cache = [];

    public static function resolve(?string $name): string
    {
        $name = strtolower(trim((string) $name));
        $name = preg_replace('/^fa[srb]?\s+/', '', $name) ?? '';
        $name = preg_replace('/^fa-/', '', $name) ?? '';
        $name = self::ALIASES[$name] ?? $name;

        // Le nom ne sert que de clé de fichier : aucun caractère de chemin accepté.
        if (preg_match('/^[a-z0-9-]+$/', $name) !== 1 || ! is_file(self::path($name))) {
            return self::FALLBACK;
        }

        return $name;
    }

    /** Contenu intérieur du SVG (tracés), prêt à être placé dans une balise <svg>. */
    public static function inner(?string $name): string
    {
        $name = self::resolve($name);

        return self::$cache[$name] ??= self::extract((string) file_get_contents(self::path($name)));
    }

    private static function path(string $name): string
    {
        return resource_path('icons/lucide/'.$name.'.svg');
    }

    private static function extract(string $svg): string
    {
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svg, $matches);

        return trim(preg_replace('/\s+/', ' ', $matches[1] ?? '') ?? '');
    }
}
