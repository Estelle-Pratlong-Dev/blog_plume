<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$currentPage = '404.php';
http_response_code(403);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lecture à venir — <?= e($site['name']) ?></title>
    <meta name="description" content="La page demandée n’existe pas. Retrouvez les rubriques du blog de L. Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/obligatoires/404.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ PAGE INTROUVABLE ============================ -->
        <section class="page-intro container">
            <p class="eyebrow">Le calendrier de l’Avent</p>
            <h1>Un peu de patience…</h1>
            <p class="intro-text">Cette fenêtre s’ouvrira à la date prévue, à minuit, heure de Paris.</p>
            <a class="button" href="<?= e(url('index.php')) ?>">Revenir à l’accueil <span aria-hidden="true">→</span></a>
        </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>
