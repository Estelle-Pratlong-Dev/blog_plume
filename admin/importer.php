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
verifier_csrf();
require __DIR__ . '/../includes/docx.php';
try {
    $file = $_FILES['document'] ?? null;
    if (!is_array($file) || !is_string($file['name'] ?? null) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Le document n’a pas pu être reçu. Réessayez avec un DOCX de moins de 5 Mo.');
    }
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'docx' || $file['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Choisissez un fichier .docx de moins de 5 Mo.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/octet-stream'], true)) {
        throw new InvalidArgumentException('Le fichier reçu n’est pas un document Word reconnu.');
    }
    $archive = file_get_contents($file['tmp_name']);
    if ($archive === false) {
        throw new InvalidArgumentException('Impossible de lire le document.');
    }
    echo json_encode(importer_docx($archive), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo json_encode(['erreur' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['erreur' => 'L’import a échoué. Votre texte déjà saisi est conservé.']);
}
