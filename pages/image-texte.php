<?php
// Une image suit exactement la visibilité du récit : aucun accès aux brouillons ou jours futurs.
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contenus.php';
require __DIR__ . '/../includes/images-texte.php';
$id = is_string($_GET['texte'] ?? null) ? $_GET['texte'] : '';
$text = tous_textes()[$id] ?? null;
if ($text === null || !texte_visible($text, $site) || empty($text['image']['fichier'])) {
    http_response_code(404);
    exit;
}
try {
    $filename = chemin_image_texte($text['image']['fichier']);
} catch (InvalidArgumentException $exception) {
    http_response_code(404);
    exit;
}
if (!is_file($filename)) {
    http_response_code(404);
    exit;
}
$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . $types[pathinfo($filename, PATHINFO_EXTENSION)]);
header('Content-Length: ' . filesize($filename));
header('Content-Disposition: inline');
readfile($filename);
