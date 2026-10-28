<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'romans.php';
$textes = require __DIR__ . '/../data/textes.php';
$category = 'romans';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Romans — <?= e($site['name']) ?></title>
    <meta name="description" content="Explorez les romans et les univers au long cours de L. Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/romans.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ========================= PRÉSENTATION DE LA RUBRIQUE ========================= -->
        <section class="page-intro container">
            <p class="eyebrow">Romans</p>
            <h1>Des mondes à habiter</h1>
            <p class="intro-text">Pour les envies de lectures plus longues : les romans et leurs univers prendront place ici.</p>
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
