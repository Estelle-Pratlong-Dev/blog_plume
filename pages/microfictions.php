<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'microfictions.php';
$textes = require __DIR__ . '/../data/textes.php';
$category = 'microfictions';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Microfictions — <?= e($site['name']) ?></title>
    <meta name="description" content="Des microfictions sous contraintes : trois éléments pour faire naître une histoire.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/microfictions.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ========================= PRÉSENTATION DE LA RUBRIQUE ========================= -->
        <section class="page-intro container">
            <p class="eyebrow">Microfictions</p>
            <h1>Trois éléments, une histoire</h1>
            <p class="intro-text">Un point de départ, quelques contraintes, et l’imagination fait le reste. Des histoires courtes pour explorer tous les genres.</p>
        </section>

        <!-- ============================== LECTURES ============================== -->
        <section class="container collection-section" aria-label="Textes de la rubrique">
            <?php require __DIR__ . '/../includes/liste-textes.php'; ?>
        </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>
