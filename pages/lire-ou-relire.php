<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = '';
$publicTexts = require __DIR__ . '/../data/textes.php';
$textes = classement_lectures($publicTexts, $site['nouvelles_visibles'])['archives'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lire ou relire — <?= e($site['name']) ?></title>
    <meta name="description" content="Retrouvez les romans terminés et les nouvelles à relire de L . Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/lire-ou-relire.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('img/identite/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/cartes-lectures.css') . '?v=' . filemtime(__DIR__ . '/../css/cartes-lectures.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ========================== LECTURES À RETROUVER ========================== -->
        <section class="page-intro container">
            <p class="eyebrow">Les histoires restent</p>
            <h1>Lire ou relire</h1>
            <p class="intro-text archives-intro">Les romans terminés, les nouvelles et les micro-fictions à retrouver.</p>
        </section>
        <?php if ($textes === []): ?>
            <section class="container collection-section" aria-label="Lectures à retrouver">
                <p>Les premières histoires à relire arriveront bientôt.</p>
            </section>
        <?php else: ?>
            <?php foreach (['romans' => 'Romans', 'nouvelles' => 'Nouvelles', 'microfictions' => 'Micro-fictions'] as $category => $label): ?>
                <?php if (array_filter($textes, static fn(array $text): bool => $text['categorie'] === $category) !== []): ?>
                    <section class="container collection-section" aria-labelledby="archives-<?= e($category) ?>">
                        <h2 id="archives-<?= e($category) ?>"><?= e($label) ?></h2>
                        <?php $afficherImages = false; ?>
                        <?php require __DIR__ . '/../includes/catalogue/liste-textes.php'; ?>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <nav class="container collection-section archive-navigation" aria-label="Revenir aux lectures actuelles">
            <a class="text-link" href="<?= e(url('pages/romans.php')) ?>">Le roman en cours <span aria-hidden="true">→</span></a>
            <a class="text-link" href="<?= e(url('pages/nouvelles.php')) ?>">Les nouvelles <span aria-hidden="true">→</span></a>
            <a class="text-link" href="<?= e(url('pages/microfictions.php')) ?>">Les micro-fictions <span aria-hidden="true">→</span></a>
        </nav>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>