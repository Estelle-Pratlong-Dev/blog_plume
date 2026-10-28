<?php
require __DIR__ . '/includes/bootstrap.php';

// Aucun domaine fictif : le sitemap ne devient disponible qu’en production configurée.
if (!$site['production'] || $site['public_url'] === '') {
    http_response_code(404);
    exit;
}

header('Content-Type: application/xml; charset=utf-8');
$pages = ['', 'pages/ephemere.php', 'pages/microfictions.php', 'pages/nouvelles.php', 'pages/feuilleton.php', 'pages/romans.php', 'pages/a-propos.php', 'pages/contact.php'];
$textes = require __DIR__ . '/data/textes.php';
foreach (array_keys($textes) as $slug) {
    $pages[] = 'pages/lecture.php?texte=' . rawurlencode($slug);
}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($pages as $page): ?>
        <url><loc><?= e(rtrim($site['public_url'], '/') . '/' . $page) ?></loc></url>
    <?php endforeach; ?>
</urlset>
