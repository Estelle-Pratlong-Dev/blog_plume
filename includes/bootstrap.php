<?php
// Configuration et petites fonctions communes ; aucun chargement de contenus implicite.
$site = require __DIR__ . '/../config/site.php';
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

// Prépare le lien privé avant tout HTML. Les pages anonymes ne créent pas de session.
require_once __DIR__ . '/session-admin.php';
$adminVisible = false;
if (!str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')) {
    $adminVisible = afficher_raccourci_admin();
}
// Le HTML peut contenir un lien personnalisé ; il ne doit pas être partagé par un cache.
header('Cache-Control: private, no-store');
