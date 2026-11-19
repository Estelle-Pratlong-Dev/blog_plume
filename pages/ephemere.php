<?php
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contenus.php';
$currentPage = 'ephemere.php';
$textes = textes_publics($site);
$monthlyPages = pages_mensuelles($textes);
$monthlyPage = reset($monthlyPages);
$now = horloge_publication($site);
$adventYear = (int) $now->format('Y');
$showAdvent = calendrier_avent_en_cours($site, $now);
if ($monthlyPage && !$showAdvent) {
    header('Cache-Control: private, no-store');
    header('Location: ' . url('pages/lecture.php?texte=' . rawurlencode($monthlyPage['id'])), true, 302);
    exit;
}
$allTexts = tous_textes();
$days = [];
foreach ($allTexts as $text) {
    if ($text['categorie'] === 'avent' && $text['statut'] === 'publie' && annee_avent_texte($text, $site) === $adventYear) {
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
    <title>De moi en mois — <?= e($site['name']) ?></title>
    <meta name="description" content="Les histoires de l’Avent, les invités du mois et les rendez-vous de passage de L . Yann.">
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
            <p class="eyebrow">De moi en mois</p>
            <h1 class="month-heading">Au fil des saisons et des envies,<br> un espace dédié à des histoires éphémères<br> et des découvertes inattendues</h1>
        </section>

        <!-- ======================== CALENDRIER DE NOËL ======================== -->
        <?php if ($showAdvent): ?>
            <section class="container collection-section" aria-labelledby="titre-avent">
                <p class="calendar-month">Décembre <?= $adventYear ?></p>
                <h2 id="titre-avent">Des histoires pour attendre Noël</h2>
                <p class="calendar-warning"><em><strong>Attention, ces histoires ne sont pas forcément des contes pour enfants.</strong> Alors avant de les partager avec vos chères têtes blondes, faites-en une première lecture… et décidez avec qui vous pouvez et voulez les partager.</em></p>
                <p><em>Chaque fenêtre s’ouvre à la date prévue et reste accessible jusqu’à la fin du mois.</em></p>
                <ol class="advent-calendar">
                    <?php for ($day = 1; $day <= (int) $site['ephemere']['calendrier']['dernier_jour']; $day++): ?>
                        <?php
                        $text = $days[$day] ?? null;
                        $opens = debut_jour_avent($site, $day);
                        $future = $opens > $now;
                        $available = !$future && $text !== null && texte_visible($text, $site, $now);
                        // La teinte suit la date et non la présence d’un texte.
                        $state = $future ? 'advent-future' : 'advent-open';
                        $title = $text['titre'] ?? 'Une histoire à découvrir';
                        $motif = $site['advent_motifs'][($day - 1) % count($site['advent_motifs'])];
                        $destination = $available ? 'pages/lecture.php?texte=' . rawurlencode($text['id']) : '';
                        ?>
                        <li class="<?= e($state) ?>">
                            <?php if ($available): ?>
                                <a href="<?= e(url($destination)) ?>" aria-label="Jour <?= $day ?> : <?= e($title) ?>">
                            <?php else: ?>
                                <span class="advent-pending" aria-label="Jour <?= $day ?> : <?= e($title) ?>">
                            <?php endif; ?>
                                <span class="advent-day"><?= sprintf('%02d', $day) ?></span>
                                <img class="advent-motif" src="<?= e(url($motif)) ?>" width="100" height="90" alt="" loading="lazy">
                                <span class="advent-title"><?= e($title) ?></span>
                            <?php if ($available): ?>
                                </a>
                            <?php else: ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                </ol>
                <section class="christmas-invitation" aria-labelledby="titre-invitation-noel">
                    <img class="christmas-invitation-decoration" src="<?= e(url('img/decorations/noel-sapin-renverse-cadeaux.svg')) ?>" width="132" height="120" alt="">
                    <h3 id="titre-invitation-noel">Et si vous profitiez de ces 24 jours pour écrire votre propre histoire de Noël ?</h3>
                    <p>Que vous pourriez lire ou conter à vos proches et à vos amis<br>au pied du sapin le 25 décembre ?</p>
                    <p><strong>A vos plumes !</strong></p>
                </section>
            </section>
        <?php endif; ?>
        <?php if (!$showAdvent): ?>
            <p class="container collection-section">La page de ce mois n’est pas encore disponible.</p>
        <?php endif; ?>
        <?php if ($showAdvent && $monthlyPage): ?>
            <p class="container collection-section"><a class="text-link" href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($monthlyPage['id']))) ?>"><?= e($monthlyPage['titre']) ?> <span aria-hidden="true">→</span></a></p>
        <?php endif; ?>
        <!-- ================= AUTRES RENDEZ-VOUS, SI PUBLIÉS ================= -->
        <?php $otherTexts = array_filter($textes, static fn(array $text): bool => $text['categorie'] === 'ephemere' && empty($text['mois_invite'])); ?>
        <?php if ($otherTexts !== []): ?>
            <section class="container collection-section" aria-labelledby="titre-rendez-vous">
                <h2 id="titre-rendez-vous">Les rendez-vous du moment</h2>
                <?php $category = 'ephemere'; $textes = $otherTexts; ?>
                <?php require __DIR__ . '/../includes/catalogue/liste-textes.php'; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>
