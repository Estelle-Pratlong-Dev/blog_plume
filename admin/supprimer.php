<?php
require __DIR__ . '/_securite.php';
exiger_admin();
require __DIR__ . '/../includes/images-texte.php';
$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$text = tous_textes()[$id] ?? null;
if ($text === null) {
    http_response_code(404);
    exit('Texte introuvable.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();
    try {
        if (champ_post('confirmation') !== 'supprimer') {
            throw new InvalidArgumentException('Confirmez la suppression du texte.');
        }
        $image = supprimer_texte($id, (int) champ_post('version'));
        supprimer_image_texte($image);
        header('Location: ' . url('admin/index.php'), true, 303);
        exit;
    } catch (RuntimeException | InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}
$adminTitle = 'Supprimer un texte';
$authenticated = true;
require __DIR__ . '/_entete.php';
?>
        <!-- ======================== CONFIRMATION DE SUPPRESSION ======================== -->
        <?php if ($error !== ''): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif; ?>
        <p>Vous allez supprimer définitivement <strong>« <?= e($text['titre']) ?> »</strong> et son éventuelle image.</p>
        <p>Cette action est irréversible depuis l’administration. Pour simplement masquer le texte, remettez-le en brouillon dans l’éditeur.</p>
        <?php if ($text['categorie'] === 'avent'): ?>
            <p>Le jour <?= (int) $text['jour'] ?> du calendrier sera libéré.</p>
        <?php endif; ?>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="version" value="<?= (int) $text['version'] ?>">
            <button class="button button-dark" type="submit" name="confirmation" value="supprimer">Supprimer définitivement</button>
            <a class="text-link" href="<?= e(url('admin/editer.php?id=' . rawurlencode($id))) ?>">Annuler et revenir au texte</a>
        </form>
<?php require __DIR__ . '/_pied.php'; ?>
