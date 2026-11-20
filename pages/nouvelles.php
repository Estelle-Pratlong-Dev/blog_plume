<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'nouvelles.php';
$textes = require __DIR__ . '/../data/textes.php';
$category = 'nouvelles';
$cartesGrille = true;
$afficherImages = false;
$textes = classement_lectures($textes, $site['nouvelles_visibles'])['nouvelles'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouvelles — <?= e($site['name']) ?></title>
    <meta name="description" content="Découvrez les nouvelles de L . Yann et explorez des imaginaires singuliers.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/nouvelles.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('img/identite/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/cartes-lectures.css') . '?v=' . filemtime(__DIR__ . '/../css/cartes-lectures.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ========================= PRÉSENTATION DE LA RUBRIQUE ========================= -->
        <section class="page-intro container">
            <p class="eyebrow">Nouvelles</p>
            <h1>Le temps d’une échappée</h1>
            <p class="intro-text">Des histoires courtes à lire d’une traite.</p>
        </section>

        <!-- ============================== LECTURES ============================== -->
        <section class="container collection-section" aria-label="Textes de la rubrique">
            <?php require __DIR__ . '/../includes/catalogue/liste-textes.php'; ?>
            <p class="archive-link"><a class="text-link" href="<?= e(url('pages/lire-ou-relire.php')) ?>">Lire ou relire <span aria-hidden="true">→</span></a></p>
        </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>
