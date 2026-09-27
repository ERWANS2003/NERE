<?php

/**
 * Router for PHP's built-in server, used as the Railway start command.
 *
 * The built-in server has no rewrite rules, so every request would otherwise be
 * handed to index.php -- including compiled Vite assets under /build, which the
 * app serves as static files. Returning false lets the server stream an existing
 * file itself; everything else falls through to Laravel.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
