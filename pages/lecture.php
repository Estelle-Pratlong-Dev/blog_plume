<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'lecture.php';
require_once __DIR__ . '/../includes/contenus.php';
header('Cache-Control: no-store');
$textes = tous_textes();
$slug = is_string($_GET['texte'] ?? null) ? $_GET['texte'] : '';
if (!isset($textes[$slug]) || $textes[$slug]['statut'] !== 'publie') {
    require __DIR__ . '/obligatoires/404.php';
    exit;
}
$text = $textes[$slug];
$readingFont = isset(polices_lecture()[$text['police'] ?? 'defaut']) ? ($text['police'] ?? 'defaut') : 'defaut';
if (!texte_visible($text, $site)) {
    if ($text['categorie'] === 'avent') {
        require __DIR__ . '/indisponible.php';
    } else {
        require __DIR__ . '/obligatoires/404.php';
    }
    exit;
}
$categoryLabels = ['microfictions' => 'Microfictions', 'nouvelles' => 'Nouvelles', 'feuilleton' => 'Le feuilleton', 'romans' => 'Romans', 'avent' => 'Histoires de l’Avent', 'invite' => 'Invité·e du mois', 'ephemere' => 'Rubrique éphémère'];
$categoryFiles = ['microfictions' => 'pages/microfictions.php', 'nouvelles' => 'pages/nouvelles.php', 'feuilleton' => 'pages/feuilleton.php', 'romans' => 'pages/romans.php', 'avent' => 'pages/ephemere.php', 'invite' => 'pages/ephemere.php', 'ephemere' => 'pages/ephemere.php'];
$currentPage = basename($categoryFiles[$text['categorie']]);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($text['titre']) ?> — <?= e($site['name']) ?></title>
    <meta name="description" content="<?= e($text['introduction'] ?: $text['titre']) ?>">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/lecture.php?texte=' . rawurlencode($slug)) ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css') . '?v=' . filemtime(__DIR__ . '/../css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- =========================== TEXTE ET NAVIGATION =========================== -->
        <article class="reading-layout">
            <header class="reading-heading">
                <p class="eyebrow"><?= e($categoryLabels[$text['categorie']]) ?></p>
                <h1><?= e($text['titre']) ?></h1>
                <?php if ($text['introduction'] !== ''): ?>
                    <p class="intro-text"><?= e($text['introduction']) ?></p>
                <?php endif; ?>
            </header>
            <!-- ========================= ILLUSTRATION DU RÉCIT ========================= -->
            <?php if (!empty($text['image']['fichier'])): ?>
                <figure class="story-illustration">
                    <img src="<?= e(url('pages/image-texte.php?texte=' . rawurlencode($text['id']) . '&v=' . (int) $text['version'])) ?>" width="<?= (int) $text['image']['largeur'] ?>" height="<?= (int) $text['image']['hauteur'] ?>" alt="<?= e($text['image']['description']) ?>" fetchpriority="high">
                </figure>
            <?php endif; ?>
            <!-- ======================== CONFORT DE LECTURE ======================== -->
            <div class="reading-controls" id="reading-controls" role="group" aria-label="Taille du texte">
                <button class="reading-size-button" id="reading-smaller" type="button" title="Réduire la taille du texte" aria-label="Réduire la taille du texte" aria-controls="texte-lecture">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="6.5"/><path d="m15 15 6 6M7 10h6"/></svg>
                </button>
                <button class="reading-size-button" id="reading-larger" type="button" title="Agrandir la taille du texte" aria-label="Agrandir la taille du texte" aria-controls="texte-lecture">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="6.5"/><path d="m15 15 6 6M7 10h6M10 7v6"/></svg>
                </button>
                <span class="reading-size-status" id="reading-size-value" role="status" aria-live="polite">Taille du texte : 100 %</span>
            </div>
            <div id="texte-lecture" class="prose reading-text reading-font-<?= e($readingFont) ?><?= $site['limiter_copie'] ? ' protected-text' : '' ?>">
                <?php // HTML filtré à l’import et à l’enregistrement, puis défensivement à la lecture. ?>
                <?= nettoyer_contenu($text['contenu']) ?>
            </div>
            <p class="reading-rights">© <?= e($site['author']) ?> — Texte protégé. Toute reproduction nécessite une autorisation.</p>
            <?php require __DIR__ . '/../includes/participation-microfiction.php'; ?>
            <a class="text-link" href="<?= e(url($categoryFiles[$text['categorie']])) ?>">Retour à la rubrique <span aria-hidden="true">→</span></a>
        </article>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
    <script src="<?= e(url('js/lecture.js') . '?v=' . filemtime(__DIR__ . '/../js/lecture.js')) ?>" defer></script>
</body>
</html>
