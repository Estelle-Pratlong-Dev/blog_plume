<?php
// Images éditoriales privées : format vérifié, nom aléatoire, jamais servies directement.
function chemin_image_texte(string $filename): string
{
    if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)) {
        throw new InvalidArgumentException('Référence d’image invalide.');
    }
    return __DIR__ . '/../storage/images/' . $filename;
}

function recevoir_image_texte(array $file, string $description): array
{
    $description = trim($description);
    if ($description === '' || mb_strlen($description) > 250) {
        throw new InvalidArgumentException('Décrivez l’image en 250 caractères maximum. Cette description est obligatoire pour son texte alternatif.');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('L’image n’a pas pu être reçue. Choisissez un JPEG, PNG ou WebP de moins de 5 Mo.');
    }
    if (!is_int($file['size'] ?? null) || $file['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('L’image doit faire moins de 5 Mo.');
    }
    $info = @getimagesize($file['tmp_name']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($info === false || !isset($extensions[$mime]) || ($info['mime'] ?? '') !== $mime || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 20000000) {
        throw new InvalidArgumentException('Choisissez une image JPEG, PNG ou WebP valide, de 20 millions de pixels maximum.');
    }
    $directory = __DIR__ . '/../storage/images';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Le stockage des images est indisponible.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], chemin_image_texte($filename))) {
        throw new RuntimeException('L’image n’a pas pu être enregistrée.');
    }
    chmod(chemin_image_texte($filename), 0600);
    return ['fichier' => $filename, 'type' => $mime, 'largeur' => $info[0], 'hauteur' => $info[1], 'description' => $description];
}

function supprimer_image_texte(?array $image): void
{
    if ($image !== null && is_string($image['fichier'] ?? null)) {
        $filename = chemin_image_texte($image['fichier']);
        if (is_file($filename)) {
            unlink($filename);
        }
    }
}
