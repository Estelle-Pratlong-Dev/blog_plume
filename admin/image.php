<?php
require __DIR__ . '/_securite.php';
exiger_admin();
require __DIR__ . '/../includes/images-texte.php';
$id = is_string($_GET['texte'] ?? null) ? $_GET['texte'] : '';
$image = tous_textes()[$id]['image'] ?? null;
if (!is_array($image) || !is_string($image['fichier'] ?? null)) {
    http_response_code(404);
    exit;
}
try {
    $filename = chemin_image_texte($image['fichier']);
} catch (InvalidArgumentException $exception) {
    http_response_code(404);
    exit;
}
$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
if (!is_file($filename)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . $types[pathinfo($filename, PATHINFO_EXTENSION)]);
header('Content-Length: ' . filesize($filename));
header('Content-Disposition: inline');
readfile($filename);
