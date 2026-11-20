<?php
// Configuration et petites fonctions communes ; aucun chargement de contenus implicite.
$site = require (is_file(__DIR__ . '/../config/site.php') ? __DIR__ . '/../config/site.php' : __DIR__ . '/../config/site.example.php');
date_default_timezone_set('Europe/Paris');

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Base stable, y compris depuis pages/obligatoires/ et admin/.
function url(string $path = ''): string
{
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = preg_replace('#/(?:pages|admin)(?:/.*)?$#', '', $directory);
    $base = $base === '/' || $base === '.' ? '' : rtrim($base, '/');
    return $base . '/' . ltrim($path, '/');
}

if (!$site['production']) {
    header('X-Robots-Tag: noindex, nofollow');
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
if ($site['production'] && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=86400');
}

// Prépare le lien privé avant tout HTML. Les pages anonymes ne créent pas de session.
require_once __DIR__ . '/admin/session-admin.php';
$adminVisible = false;
if (!str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')) {
    $adminVisible = afficher_raccourci_admin();
}
// Le HTML peut contenir un lien personnalisé ; il ne doit pas être partagé par un cache.
header('Cache-Control: private, no-store');
