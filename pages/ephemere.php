<?php
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contenus.php';
$currentPage = 'ephemere.php';
$textes = textes_publics($site);
$guest = invite_du_mois($site);
$now = horloge_publication($site);
$allTexts = tous_textes();
$days = [];
foreach ($allTexts as $text) {
    if ($text['categorie'] === 'avent' && $text['statut'] === 'publie') {
        $days[(int) $text['jour']] = $text;
    }
}
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rubrique éphémère — <?= e($site['name']) ?></title>
    <meta name="description" content="Les histoires de l’Avent, les invités du mois et les rendez-vous de passage de L Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/ephemere.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/ephemere.css') . '?v=' . filemtime(__DIR__ . '/../css/ephemere.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ========================== INTRODUCTION ========================== -->
        <section class="page-intro container">
            <p class="eyebrow">La rubrique éphémère</p>
            <h1>Des histoires qui vont et viennent</h1>
            <p class="intro-text">Au fil des saisons et des envies, un espace pour les textes de passage et les découvertes inattendues.</p>
        </section>

        <!-- ==================== INVITÉ EN TÊTE, SI PRÉSENT ==================== -->
        <?php if ($guest !== null): ?>
            <section class="container collection-section" id="invite" aria-labelledby="titre-invite">
                <div class="guest-panel">
                    <p class="eyebrow">L’invité·e du mois</p>
                    <h2 id="titre-invite"><?= e($guest['titre']) ?></h2>
                    <p><?= e($guest['introduction']) ?></p>
                    <a class="text-link" href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($guest['id']))) ?>">Découvrir le texte <span aria-hidden="true">→</span></a>
                </div>
            </section>
        <?php endif; ?>

        <!-- ======================== CALENDRIER DE L’AVENT ======================== -->
        <?php if ($site['ephemere']['mode'] === 'avent'): ?>
            <section class="container collection-section" aria-labelledby="titre-avent">
                <p class="eyebrow">Une histoire par jour</p>
                <h2 id="titre-avent">Les histoires de l’Avent</h2>
                <p>Chaque fenêtre s’ouvre à la date prévue, à minuit, heure de Paris. Les textes déjà ouverts restent accessibles.</p>
                <ol class="advent-calendar">
                    <?php for ($day = 1; $day <= 24; $day++): ?>
                        <?php
                        $text = $days[$day] ?? null;
                        $available = $text !== null && texte_visible($text, $site);
                        $opens = debut_jour_avent($site, $day);
                        $future = $opens > $now;
                        $label = $future ? 'Le ' . $opens->format('d/m') : 'Texte à venir';
                        $motif = $site['advent_motifs'][($day - 1) % count($site['advent_motifs'])];
                        ?>
                        <li<?= $future ? ' class="advent-future"' : '' ?>>
                            <?php if ($available): ?>
                                <a href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($text['id']))) ?>" aria-label="Jour <?= $day ?> : <?= e($text['titre']) ?>">
                                    <span><?= sprintf('%02d', $day) ?></span>
                                    <img class="advent-motif" src="<?= e(url($motif)) ?>" width="80" height="64" alt="" loading="lazy">
                                    <small>Lire l’histoire</small>
                                </a>
                            <?php else: ?>
                                <span class="advent-pending" aria-label="Jour <?= $day ?>, <?= e($label) ?>">
                                    <span><?= sprintf('%02d', $day) ?></span>
                                    <img class="advent-motif" src="<?= e(url($motif)) ?>" width="80" height="64" alt="" loading="lazy">
                                    <small><?= e($label) ?></small>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                </ol>
            </section>
        <?php endif; ?>

        <?php $openAdvent = array_filter($textes, static fn(array $story): bool => $story['categorie'] === 'avent'); ?>
        <?php if ($site['ephemere']['mode'] === 'avent' && $openAdvent !== []): ?>
            <section class="container collection-section advent-library" aria-labelledby="titre-histoires-ouvertes">
                <p class="eyebrow">Les fenêtres déjà ouvertes</p>
                <h2 id="titre-histoires-ouvertes">Retrouver les histoires</h2>
                <?php $category = 'avent'; ?>
                <?php require __DIR__ . '/../includes/liste-textes.php'; ?>
            </section>
        <?php endif; ?>

        <!-- ================= AUTRES RENDEZ-VOUS, SI PUBLIÉS ================= -->
        <?php $otherTexts = array_filter($textes, static fn(array $text): bool => $text['categorie'] === 'ephemere'); ?>
        <?php if ($otherTexts !== []): ?>
            <section class="container collection-section" aria-labelledby="titre-rendez-vous">
                <h2 id="titre-rendez-vous">Les rendez-vous du moment</h2>
                <?php $category = 'ephemere'; ?>
                <?php require __DIR__ . '/../includes/liste-textes.php'; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>
