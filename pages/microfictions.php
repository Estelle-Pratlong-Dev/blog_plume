<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'microfictions.php';
$textes = require __DIR__ . '/../data/textes.php';
$hasMicrofictionArchives = array_filter($textes, static fn(array $text): bool => $text['categorie'] === 'microfictions' && !empty($text['archive'])) !== [];
$textes = array_filter($textes, static fn(array $text): bool => empty($text['archive']));
$category = 'microfictions';
$cartesGrille = true;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Micro-fictions — <?= e($site['name']) ?></title>
    <meta name="description" content="Des histoires brèves, un visuel d’inspiration et quelques contraintes pour une petite faim de lecture.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/microfictions.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/cartes-lectures.css') . '?v=' . filemtime(__DIR__ . '/../css/cartes-lectures.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ========================= PRÉSENTATION DE LA RUBRIQUE ========================= -->
        <section class="page-intro container">
            <p class="eyebrow">Micro-fictions</p>
            <h1>Des histoires brèves pour une petite faim de lecture</h1>
            <p class="intro-text">Un point de départ, quelques contraintes, et l’imagination fait le reste.</p>
        </section>

        <!-- ============================== LECTURES ============================== -->
        <section class="container collection-section" aria-label="Textes de la rubrique">
            <?php require __DIR__ . '/../includes/catalogue/liste-textes.php'; ?>
            <?php if ($hasMicrofictionArchives): ?>
                <p class="archive-link"><a class="text-link" href="<?= e(url('pages/lire-ou-relire.php#archives-microfictions')) ?>">Lire ou relire <span aria-hidden="true">→</span></a></p>
            <?php endif; ?>
        </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>
