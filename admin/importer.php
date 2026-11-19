<?php
require __DIR__ . '/_securite.php';
exiger_admin(true);
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['erreur' => 'Utilisez le formulaire d’import.']);
    exit;
}
if (isset($_SERVER['CONTENT_LENGTH']) && ini_parse_quantity(ini_get('post_max_size')) > 0
    && (int) $_SERVER['CONTENT_LENGTH'] > ini_parse_quantity(ini_get('post_max_size'))) {
    http_response_code(413);
    echo json_encode(['erreur' => 'Le fichier dépasse la limite de dépôt de cet hébergement. Réduisez la taille du document Word.'], JSON_UNESCAPED_UNICODE);
    exit;
}
verifier_csrf();
require __DIR__ . '/../includes/admin/docx.php';
try {
    $file = $_FILES['document'] ?? null;
    if (is_array($file) && in_array($file['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new InvalidArgumentException('Le document Word est trop lourd. La limite du blog est de 15 Mo ; l’hébergement peut appliquer une limite inférieure.');
    }
    if (!is_array($file) || !is_string($file['name'] ?? null) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Le document n’a pas pu être reçu. Réessayez avec un DOCX de 15 Mo maximum.');
    }
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'docx' || $file['size'] > DOCX_MAX_BYTES) {
        throw new InvalidArgumentException('Choisissez un fichier .docx de 15 Mo maximum.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/octet-stream'], true)) {
        throw new InvalidArgumentException('Le fichier reçu n’est pas un document Word reconnu.');
    }
    $archive = file_get_contents($file['tmp_name']);
    if ($archive === false) {
        throw new InvalidArgumentException('Impossible de lire le document.');
    }
    echo json_encode(importer_docx($archive, true, ($_POST['categorie'] ?? '') === 'ephemere'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo json_encode(['erreur' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['erreur' => 'L’import a échoué. Votre texte déjà saisi est conservé.']);
}
